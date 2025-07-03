<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace frontend\api\models;

use Yii;
use yii\base\Model;
use yii\helpers\ArrayHelper;

use common\models\OrderModel;
use common\models\OrderGoodsModel;
use common\models\RefundModel;
use common\models\DepositTradeModel;
use common\models\OrderExtmModel;
use common\models\OrderLogModel;
use common\models\RegionModel;

use common\library\Language;
use common\library\Timezone;
use common\library\Page;
use common\library\Def;
use common\library\Plugin;

use frontend\api\library\Formatter;

/**
 * @Id OrderForm.php 2019.1.3 $
 * @author yxyc
 */
class OrderForm extends Model
{
	public $enter = 'buyer';
	public $errors = null;

	/**
	 * 获取订单数据
	 */
	public function formData($post = null)
	{
		$query = OrderModel::find()->alias('o')
			->select('o.order_id,o.order_sn,o.gtype,o.otype,o.dtype,o.buyer_id,o.buyer_name,o.seller_id,o.seller_name,o.status,o.evaluation_status,o.payment_code,o.payment_name,o.goods_amount,o.order_amount,o.postscript,o.memo,o.add_time,o.pay_time,o.ship_time,o.finished_time,o.evaluation_time,oe.freight,oe.deliveryName')
			->joinWith('orderExtm oe', false)
			->where(['>', 'o.order_id', 0])
			->orderBy(['o.order_id' => SORT_DESC]);

		// 指定获取某个店铺的订单
		if ($post->store_id) {
			$query->andWhere(['o.seller_id' => $post->store_id]);
		}

		if ($post->otype) {
			$query->andWhere(['otype' => $post->otype]);
		}
		if ($post->gtype) {
			$query->andWhere(['gtype' => $post->gtype]);
		}

		// 卖家获取订单管理数据
		if ($this->enter == 'seller') {
			$query->andWhere(['o.seller_id' => Yii::$app->user->id]);
			$query->addSelect('oe.consignee,oe.phone_mob,region_id,address');
		}
		// 买家获取我的订单数据
		else {
			$query->andWhere(['o.buyer_id' => Yii::$app->user->id]);
		}
		// 根据订单状态筛选订单
		if (isset($post->type) && ($status = Def::getOrderStatusTranslator($post->type)) > -1) {
			// 待发货（包含待发货、待配送）
			if ($status == Def::ORDER_ACCEPTED) {
				$query->andWhere(['in', 'o.status', [Def::ORDER_ACCEPTED, Def::ORDER_DELIVERING]]);
			}
			// 待收货（包含已发货，待使用）
			elseif ($status == Def::ORDER_SHIPPED) {
				$query->andWhere(['in', 'o.status', [Def::ORDER_SHIPPED, Def::ORDER_USING]]);
			} else $query->andWhere(['o.status' => $status]);
		}
		// 根据评价状态筛选
		if (isset($post->evaluation_status) && ($post->evaluation_status === 0 || $post->evaluation_status === 1)) {
			$query->andWhere(['evaluation_status' => intval($post->evaluation_status)]);
		}

		// 获取指定的时间段的订单
		if ($post->begin) {
			$query->andWhere(['>=', 'o.add_time', Timezone::gmstr2time($post->begin)]);
		}
		if ($post->end) {
			$query->andWhere(['<=', 'o.add_time', Timezone::gmstr2time($post->end)]);
		}
		// 根据订单编号筛选
		if ($post->order_sn) {
			$query->andWhere(['o.order_sn' => $post->order_sn]);
		}
		// 根据买家用户名筛选
		if ($post->buyer_name) {
			$query->andWhere(['o.buyer_name' => $post->buyer_name]);
		}
		// 根据收货人姓名或手机号筛选
		if ($post->consignee) {
			$query->andWhere(['or', ['oe.consignee' => $post->consignee], ['oe.phone_mob' => $post->consignee]]);
		}
		// 根据商品名称
		if ($post->keyword) {
			$allId = OrderGoodsModel::find()->select('order_id')->where(['like', 'goods_name', $post->keyword])->column();
			$query->andWhere(['in', 'o.order_id', $allId]);
		}

		// 是否获取订单商品数据
		if (isset($post->queryitem) && ($post->queryitem === true)) {
			$query->with(['orderGoods' => function ($model) {
				$model->select('id,spec_id,order_id,goods_id,goods_name,goods_image,specification,price,quantity');
			}]);
		}

		$page = Page::getPage($query->count(), $post->page_size, false, $post->page);
		$list = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
		foreach ($list as $key => $value) {
			$list[$key] = $this->formatDate($value);

			if (($trade = DepositTradeModel::find()->select('tradeNo,bizOrderId,bizIdentity')->where(['bizOrderId' => $value['order_sn'], 'bizIdentity' => Def::TRADE_ORDER])->asArray()->one())) {
				$list[$key] = array_merge($list[$key], $trade);
			}

			// 查看是否有退款
			if (($refund = $this->getOrderRefund($value))) {
				$list[$key] = array_merge($list[$key], $refund);
			}
			if (isset($value['orderGoods'])) {
				$list[$key]['items'] = $this->formatImage($value['orderGoods']);
				unset($list[$key]['orderGoods']);
			}

			// 对卖家订单返回收（取）货人信息
			if (in_array($this->enter, ['seller'])) {
				if ($value['region_id'] && ($array = RegionModel::getArray($value['region_id']))) {
					$list[$key]['consignee'] = array_merge([
						'name' => $value['consignee'],
						'phone_mob' => $value['phone_mob'],
						'address' => $value['address']
					], $array);
					unset($list[$key]['phone_mob'], $list[$key]['address'], $list[$key]['region_id']);
				}
			}
		}

		return array($list, $page);
	}

	/**
	 * 取消订单
	 * @desc 适用买家或卖家取消订单
	 */
	public function orderCancel($post, $order = null)
	{
		// 只有待付款且未发货（针对货到付款）的订单才可以取消
		if (!($order->status == Def::ORDER_PENDING && !$order->ship_time)) {
			$this->errors = Language::get('unsupport_status');
			return false;
		}

		if ($order->buyer_id == Yii::$app->user->id) {
			$model = new \frontend\home\models\Buyer_orderCancelForm();
		} else {
			$model = new \frontend\home\models\Seller_orderCancelForm();
		}

		$orders = [$order->order_id => ArrayHelper::toArray($order)];
		return $model->submit($post, $orders, false);
	}

	/**
	 * 卖家发货
	 */
	public function orderShipped($post, $order = null)
	{
		if ($order->seller_id != Yii::$app->user->id) {
			$this->errors = Language::get('handle_invalid');
			return false;
		}

		// 如果订单状态不是待发货 & 已发货，则不允许发货操作
		if (!in_array($order->status, [Def::ORDER_ACCEPTED, Def::ORDER_SHIPPED])) {
			$this->errors = Language::get('unsupport_status');
			return false;
		}

		$model = new \frontend\home\models\Seller_orderShippedForm();
		if (!$model->submit($post, $order, false)) {
			$this->errors = $model->errors;
			return false;
		}
		return true;
	}

	/**
	 * 卖家配送[同城配送]
	 */
	public function orderDelivered($post, $order = null)
	{
		if ($order->seller_id != Yii::$app->user->id) {
			$this->errors = Language::get('handle_invalid');
			return false;
		}

		// 如果订单状态不是待配送，则不允许发货操作
		if (!in_array($order->status, [Def::ORDER_DELIVERING])) {
			$this->errors = Language::get('unsupport_status');
			return false;
		}

		// 如果订单不是商家自配，则不允许操作
		if ($query = OrderExtmModel::find()->select('deliveryCode')->where(['order_id' => $order->order_id])->one()) {
			if ($query->deliveryCode != 'msd') {
				$this->errors = Language::get('handle_invalid');
				return false;
			}
		}

		$order->status = Def::ORDER_SHIPPED;
		if (!$order->save()) {
			$this->errors = $order->errors;
			return false;
		}

		// 如果是小程序订单，则上传发货信息给微信（小程序上架要求：实物订单必须上传发货信息）
		if ($order->payment_code == 'wxmppay') {
			$client = Plugin::getInstance('other')->build('wxship');
			if ($client->isInstall()) {
				if ($client->upload($order)) {
					// 0=未退，1=已推，2=已重推，微信限制发货信息只能推送2次
					$order->shipwx = $order->shipwx ? $order->shipwx + 1 : 1;
					$order->save();
				}
			}
		}

		// 更新交易状态
		DepositTradeModel::updateAll(['status' => 'SHIPPED'], [
			'bizOrderId' => $order->order_sn,
			'bizIdentity' => Def::TRADE_ORDER,
			'seller_id' => $order->seller_id
		]);

		// 记录订单操作日志
		OrderLogModel::create($order->order_id, Def::ORDER_SHIPPED, $post->remark, addslashes($order->buyer_name));

		return true;
	}

	/**
	 * 买家确认收货
	 * 不包含货到付款的收货操作
	 */
	public function orderFinished($post, $order = null)
	{
		// 交易信息 
		if (!($tradeInfo = DepositTradeModel::find()->where(['bizIdentity' => Def::TRADE_ORDER, 'bizOrderId' => $order->order_sn, 'buyer_id' => Yii::$app->user->id])->asArray()->one())) {
			$this->errors = Language::get('no_such_order');
			return false;
		}

		// 如果不是待收货，则不可以确认收货
		if ($order->status != Def::ORDER_SHIPPED) {
			$this->errors = Language::get('unsupport_status');
			return false;
		}

		$model = new \frontend\home\models\Buyer_orderConfirmForm();
		if (!$model->submit($post, $order, $tradeInfo, false)) {
			$this->errors = $model->errors;
			return false;
		}
		return true;
	}

	/**
	 * 买家确认收货
	 * 针对货到付款情形，将订单状态修改为 待支付
	 */
	public function orderConfirm($post, $order = null)
	{
		if ($order->buyer_id != Yii::$app->user->id) {
			$this->errors = Language::get('handle_invalid');
			return false;
		}

		// 如果不是已发货，则不可以确认收货
		if (!$order->ship_time) {
			$this->errors = Language::get('unsupport_status');
			return false;
		}

		// 修改订单状态
		OrderModel::updateAll(['status' => $post->status, 'receive_time' => Timezone::gmtime()], ['order_id' => $order->order_id]);

		// 修改交易状态
		DepositTradeModel::updateAll(['status' => 'PENDING'], ['bizIdentity' => Def::TRADE_ORDER, 'bizOrderId' => $order->order_sn, 'buyer_id' => Yii::$app->user->id]);

		// 记录订单操作日志
		OrderLogModel::create($order->order_id, Language::get('order_received'));
		OrderLogModel::create($order->order_id, $post->status, Language::get('buyer_confirm'), addslashes($order->buyer_name));

		return true;
	}

	/**
	 * 格式化时间
	 */
	public function formatDate($record)
	{
		$fields = ['add_time', 'pay_time', 'ship_time', 'receive_time', 'finished_time', 'evaluation_time'];
		foreach ($fields as $field) {
			isset($record[$field]) && $record[$field] = Timezone::localDate('Y-m-d H:i:s', $record[$field]);
		}
		return $record;
	}

	/**
	 * 格式化路径
	 */
	public function formatImage($list)
	{
		foreach ($list as $key => $value) {
			if (isset($list[$key]['goods_image'])) {
				$list[$key]['goods_image'] = Formatter::path($value['goods_image'], 'goods');
			}
		}
		return $list;
	}

	/**
	 * 获取订单是否有退款
	 */
	private function getOrderRefund($order = [])
	{
		// 是否申请过退款
		$tradeNo = DepositTradeModel::find()->select('tradeNo')->where(['bizIdentity' => Def::TRADE_ORDER, 'bizOrderId' => $order['order_sn']])->scalar();

		if (!empty($tradeNo) && ($refund = RefundModel::find()->select('refund_id,refund_sn,status as refund_status')->where(['tradeNo' => $tradeNo])->asArray()->one())) {
			return $refund;
		}

		return false;
	}
}
