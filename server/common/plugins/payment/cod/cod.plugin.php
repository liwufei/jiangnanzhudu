<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace common\plugins\payment\cod;

use yii;

use common\models\OrderModel;
use common\models\OrderLogModel;
use common\models\DepositTradeModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Def;

use common\plugins\BasePayment;

/**
 * @Id cod.plugin.php 2018.7.24 $
 * @author mosir
 */
class Cod extends BasePayment
{
	/**
	 * 网关地址
	 * @var string $gateway
	 */
	public $gateway;

	/**
	 * 支付插件实例
	 * @var string $code
	 */
	protected $code = 'cod';

	/* 获取支付表单 */
	public function pay($orderInfo = [])
	{
		// 支付网关商户订单号
		$payTradeNo = $this->getPayTradeNo($orderInfo);

		// 因为是货到付款，所以直接处理业务
		if ($this->payNotify($payTradeNo) === false) {
			($this->errors === null) && $this->errors = Language::get('pay_fail');
			return false;
		}

		return ['status' => 'SUCCESS', 'payTradeNo' => $payTradeNo];
	}

	public function payNotify($payTradeNo = '')
	{
		if (empty($payTradeNo)) {
			$this->errors = Language::get('order_info_empty');
			return false;
		}
		if (!($orderInfo = DepositTradeModel::getTradeInfoForNotify($payTradeNo))) {
			$this->errors = Language::get('order_info_empty');
			return false;
		}

		if (in_array($orderInfo['bizIdentity'], array(Def::TRADE_ORDER)) && ($orderInfo['payment_code'] == 'cod')) {

			// 实际上只有一次循环
			foreach ($orderInfo['tradeList'] as $tradeInfo) {

				// 修改交易状态为待发货
				$model = DepositTradeModel::find()->where(['tradeNo' => $tradeInfo['tradeNo'], 'status' => 'PENDING'])->one();
				$model->status = 'ACCEPTED';
				if ($model->update()) {
					OrderModel::updateAll(['status' => Def::ORDER_ACCEPTED], ['status' => Def::ORDER_PENDING, 'order_sn' => $tradeInfo['bizOrderId']]);
				}

				$orderInfo = $tradeInfo['order_info'];

				// 订单操作记录
				OrderLogModel::change($orderInfo['order_id'], Def::ORDER_ACCEPTED);

				// 邮件提醒：订单已确认，等待安排发货
				Basewind::sendMailMsgNotify(
					$orderInfo,
					array(
						'receiver' 	=> $orderInfo['buyer_id'],
						'key' => 'tobuyer_confirm_cod_order_notify',
					)
				);
			}
		}
		return true;
	}
}
