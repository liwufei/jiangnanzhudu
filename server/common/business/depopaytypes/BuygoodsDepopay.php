<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace common\business\depopaytypes;

use yii;

use common\models\OrderModel;
use common\models\OrderGoodsModel;
use common\models\OrderLogModel;
use common\models\OrderExtmModel;
use common\models\GoodsStatisticsModel;
use common\models\DepositTradeModel;
use common\models\TeambuyLogModel;
use common\models\StoreModel;
use common\models\IntegralModel;

use common\library\Timezone;
use common\library\Def;
use common\library\Plugin;

/**
 * @Id BuygoodsDepopay.php 2018.7.18 $
 * @author mosir
 */

class BuygoodsDepopay extends OutlayDepopay
{
	/**
	 * 针对财务明细的资金用途，值有：在线支付：PAY；充值：RECHARGE；提现：WITHDRAW；服务费：SERVICE；转账：TRANSFER；返现：REGIVE；扣费：CHARGE
	 */
	public $_tradeType 	= 'PAY';

	/**
	 * 支付类型，值有：即时到帐：INSTANT；担保交易：SHIELD；货到付款：COD
	 */
	public $_payType   	= 'SHIELD';

	public function submit($data = [])
	{
		extract($data);

		if ($trade_info['amount'] < 0) {
			$this->setErrors('10001');
			return false;
		}

		$tradeNo = $extra_info['tradeNo'];

		if (!DepositTradeModel::find()->where(['tradeNo' => $tradeNo])->exists()) {
			$model = new DepositTradeModel();
			$model->tradeNo = $tradeNo;
			$model->bizOrderId = $extra_info['bizOrderId'];
			$model->bizIdentity = $extra_info['bizIdentity'];
			$model->buyer_id = $trade_info['userid'];
			$model->seller_id = $trade_info['party_id'];
			$model->amount = $trade_info['amount'];
			$model->status = 'PENDING';
			$model->payType = $this->_payType;
			$model->flow = $this->_flow;
			$model->title = $extra_info['title'];
			$model->buyer_remark = isset($this->post->remark) ? $this->post->remark : '';
			$model->add_time = Timezone::gmtime();

			return $model->save() ? true : false;
		}
		return true;
	}

	/* 支付成功响应通知 */
	public function notify($data = [])
	{
		extract($data);

		// 处理交易基本信息
		$base_info = parent::handleTradeInfo($trade_info, $extra_info);
		if (!$base_info) {
			return false;
		}

		$tradeNo = $extra_info['tradeNo'];
		list($nextOrderStatus, $nextTradeStatus) = $this->getNextStatus($extra_info['gtype']);

		// 修改交易状态为待发货/待使用
		if (!parent::updateTradeStatus($tradeNo, ['status' => $nextTradeStatus, 'pay_time' => Timezone::gmtime()])) {
			$this->setErrors('50024');
			return false;
		}

		// 如果是余额支付，则处理买家支出记录，并变更账户余额
		if ($extra_info['payment_code'] == 'deposit') {
			if (!$this->insertRecordInfo($trade_info, $extra_info)) {
				$this->setErrors('50020');
				return false;
			}
		}

		// 修改订单状态为待发货/待使用
		if (!parent::updateOrderStatus($extra_info['order_id'], ['status' => $nextOrderStatus, 'pay_time' => Timezone::gmtime()])) {
			$this->setErrors('50021');
			return false;
		}


		// 如果是拼团订单
		if ($extra_info['otype'] == 'teambuy') {
			if (!$this->updateTeamBuyInfo($trade_info['userid'], $extra_info['order_id'], $nextOrderStatus)) {
				$this->setErrors('50024');
				return false;
			}
		} else {

			// 订单操作日志
			//OrderLogModel::change($extra_info['order_id'], Language::get('order_ispayed'));
			OrderLogModel::create($extra_info['order_id'], $nextOrderStatus);

			// 如果是同城配送且为实物订单
			if ($extra_info['gtype'] == 'material' && $extra_info['dtype'] == 'locality') {
				$this->createWaybill($extra_info);
			}
		}

		// 更新累计销售件数
		foreach (OrderGoodsModel::find()->select('goods_id,quantity')->where(['order_id' => $extra_info['order_id']])->all() as $query) {
			GoodsStatisticsModel::updateStatistics($query->goods_id, 'sales', $query->quantity);
		}

		// 支付成功处理赠送积分
		IntegralModel::givesIntegral($extra_info);

		return true;
	}

	/**
	 * 修改拼团订单信息
	 */
	public function updateTeamBuyInfo($userid, $order_id, $nextOrderStatus = '')
	{
		$query = TeambuyLogModel::find()->select('logid,teamid,people')->where(['userid' => $userid, 'order_id' => $order_id])->one();
		if (!$query) {
			return false;
		}

		// 付款时间
		$query->pay_time = Timezone::gmtime();
		if ($query->save()) {

			// 订单操作日志
			//OrderLogModel::change($order_id, Language::get('order_ispayed'));
			OrderLogModel::create($order_id, Def::ORDER_TEAMING);

			// 把订单状态调整为待成团
			parent::updateOrderStatus($order_id, ['status' => Def::ORDER_TEAMING]);
		}

		// 找出已付款的拼单
		$teambuylogs = TeambuyLogModel::find()->select('logid,order_id')->where(['and', ['teamid' => $query->teamid, 'status' => 0], ['>', 'pay_time', 0]]);

		// 满足成团条件
		if ($teambuylogs->count() >= $query->people) {
			foreach ($teambuylogs->all() as $model) {
				$model->status = 1; // 设置为成团状态
				if ($model->save()) {

					// 订单操作日志
					OrderLogModel::create($model->order_id, $nextOrderStatus);

					// 修改状态为待发货/待使用
					parent::updateOrderStatus($model->order_id, ['status' => $nextOrderStatus]);

					// 如果是同城配送且为实物订单
					$order = OrderModel::find()->where(['order_id' => $model->order_id])->asArray()->one();
					if ($order['gtype'] == 'material' && $order['dtype'] == 'locality') {
						$this->createWaybill($order);
					}
				}
			}
		}

		return true;
	}

	/**
	 * 如果是同城配送，创建运单
	 */
	private function createWaybill($order = [])
	{
		try {

			$code = StoreModel::find()->select('deliveryCode')->where(['store_id' => $order['seller_id']])->scalar();
			if (!$code || !($client = Plugin::getInstance('locality')->build($code)) || !$client->isInstall()) {
				return false;
			}

			// 修改订单状态为待配送
			OrderModel::updateAll(['status' => Def::ORDER_DELIVERING], ['order_id' => $order['order_id']]);
			OrderLogModel::change($order['order_id'], Def::ORDER_DELIVERING, sprintf('订单由 %s 提供配送服务', $client->getInfo()['name']));

			$model = OrderExtmModel::find()->select('order_id,deliverTime')->where(['order_id' => $order['order_id']])->one();
			if ($model->deliverTime) {
				list($timer) = explode('-', $model->deliverTime);
				$order['deliverTime'] = Timezone::gmstr2time(str_replace(['年', '月', '日'], ['-', '-', ''], $timer));
			}

			if ($result = $client->addOrder($order)) {
				$model->distance = $result->distance;
				$model->deliverFee = $result->deliverFee;
				$model->deliveryCode = $code;
				return $model->save();
			} else {

				// 记录失败原因
				OrderLogModel::create($order['order_id'], Def::ORDER_DELIVERING, $client->errors);
				return false;
			}
		} catch (\Exception $e) {
			return false;
		}
	}

	/**
	 * 付款后，针对服务类商品订单，下一个状态是：待使用
	 * 其他类型订单，下一个状态是：待发货
	 */
	private function getNextStatus($gtype = 'material')
	{
		// 如果是服务类商品，则下一个状态是待使用
		if ($gtype == 'service') {
			return [Def::ORDER_USING, 'USING'];
		}

		return [Def::ORDER_ACCEPTED, 'ACCEPTED'];
	}
}
