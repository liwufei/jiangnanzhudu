<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace frontend\api\controllers;

use Yii;
use yii\helpers\ArrayHelper;

use common\models\OrderModel;
use common\models\OrderLogModel;
use common\models\OrderGoodsModel;
use common\models\OrderExtmModel;
use common\models\DepositTradeModel;
use common\models\RefundModel;
use common\models\RegionModel;
use common\models\OrderExpressModel;
use common\models\IntegralLogModel;
use common\models\StoreModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Business;
use common\library\Plugin;
use common\library\Def;
use common\library\Timezone;

use frontend\api\library\Respond;
use frontend\api\library\Formatter;

/**
 * @Id OrderController.php 2019.11.20 $
 * @author yxyc
 */

class OrderController extends \common\base\BaseApiController
{
	/**
	 * 获取所有订单列表数据
	 * @api 接口访问地址: https://www.xxx.com/api/order/list
	 */
	public function actionList()
	{
		// TODO
	}

	/**
	 * 获取单条订单信息
	 * @api 接口访问地址: https://www.xxx.com/api/order/read
	 */
	public function actionRead()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(true)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['order_id', 'buyer_id', 'seller_id']);
		$post->order_id = $this->getOrderId($post);

		if (!$post->order_id) {
			return $respond->output(Respond::PARAMS_INVALID, Language::get('order_id_sn_empty'));
		}

		$query = OrderModel::find()->alias('o')->select('o.order_id,o.order_sn,o.gtype,o.otype,o.dtype,o.buyer_id,o.buyer_name,o.seller_id,o.seller_name,o.status,o.evaluation_status,o.payment_code,o.payment_name,o.goods_amount,o.order_amount,o.discount,o.postscript,o.memo,o.add_time,o.pay_time,o.ship_time,o.receive_time,o.finished_time,o.evaluation_time,oe.freight')
			->joinWith('orderExtm oe', false)
			->where(['o.order_id' => $post->order_id]);

		if (isset($post->buyer_id) || isset($post->seller_id)) {
			if ($post->buyer_id) {
				$query->andWhere(['buyer_id' => $post->buyer_id]);
			}
			if ($post->seller_id) {
				$query->andWhere(['seller_id' => $post->seller_id]);
			}
		} else $query->andWhere(['or', ['buyer_id' => Yii::$app->user->id], ['seller_id' => Yii::$app->user->id]]);

		if (!($record = $query->asArray()->one())) {
			return $respond->output(Respond::RECORD_NOTEXIST, Language::get('no_such_order'));
		}

		if (($trade = DepositTradeModel::find()->select('tradeNo,bizOrderId,bizIdentity,payType')->where(['bizOrderId' => $record['order_sn'], 'bizIdentity' => Def::TRADE_ORDER])->asArray()->one())) {
			$record = array_merge($record, $trade);
			if (($refund = RefundModel::find()->select('refund_sn,status as refund_status')->where(['tradeNo' => $trade['tradeNo']])->andWhere(['!=', 'status', 'CLOSED'])->asArray()->one())) {
				$record = array_merge($record, $refund);
			}
		}
		if ($orderIntegral = IntegralLogModel::find()->select('money')->where(['order_id' => $post->order_id, 'type' => 'buying_pay_integral'])->one()) {
			$record['integralMoney'] = abs($orderIntegral->money);
		}

		$model = new \frontend\api\models\OrderForm();
		$record = $model->formatDate($record);

		return $respond->output(true, null, $record);
	}

	/**
	 * 提交预支付购物订单
	 * @api 接口访问地址: https://www.xxx.com/api/order/create
	 */
	public function actionCreate()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(true)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true);

		// 购物车/搭配购/拼团订单
		if (!isset($post->otype) || !in_array($post->otype, ['normal', 'mealbuy', 'teambuy'])) {
			$post->otype = 'normal';
		}

		$model = new \frontend\home\models\OrderForm(['otype' => $post->otype]);
		if (($goods_info = $model->getGoodsInfo($post)) === false) {
			return $respond->output(Respond::RECORD_NOTEXIST, $model->errors);
		}

		// 不能同时结算实物商品和虚拟服务类商品
		if (count($goods_info['gtype']) > 1) {
			return $respond->output(Respond::PARAMS_INVALID, Language::get('goodstype_invalid'));
		}

		// 如果是自己店铺的商品，则不能购买
		if (in_array(Yii::$app->user->id, array_keys($goods_info['orderList']))) {
			return $respond->output(Respond::HANDLE_INVALID, Language::get('can_not_buy_yourself'));
		}

		// 获取订单模型
		$order_type = Business::getInstance('order')->build($post->otype, $post);
		$result = $order_type->submit(array(
			'goods_info' => $goods_info
		));
		if (empty($result)) {
			return $respond->output(Respond::PARAMS_INVALID, $order_type->errors);
		}

		// 清理购物车商品等操作
		foreach ($result as $store_id => $order_id) {
			$order_type->afterInsertOrder($order_id,  $store_id, $goods_info['orderList'][$store_id]);
		}

		// 有可能是支付多个订单
		$bizOrderId = implode(',', OrderModel::find()->select('order_sn')->where(['in', 'order_id', array_values($result)])->column());

		// 到收银台付款
		return $respond->output(true, null, ['bizOrderId' => $bizOrderId, 'bizIdentity' => Def::TRADE_ORDER]);
	}

	/**
	 * 更新订单状态
	 * @api 接口访问地址: https://www.xxx.com/api/order/update
	 */
	public function actionUpdate()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(true)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['order_id', 'status']);
		$post->order_id = $this->getOrderId($post);

		if (!$post->order_id) {
			return $respond->output(Respond::PARAMS_INVALID, Language::get('order_id_sn_empty'));
		}

		$query = OrderModel::find()->where(['or', ['buyer_id' => Yii::$app->user->id], ['seller_id' => Yii::$app->user->id]])->andWhere(['order_id' => $post->order_id]);
		if (!($order = $query->one())) {
			return $respond->output(Respond::RECORD_NOTEXIST, Language::get('no_such_order'));
		}

		// 只接受目标为：取消订单/发货/确认收货/待付款（必须是货到付款）/待收货（商家自配待配送）的状态变更
		if (!isset($post->status) || !in_array($post->status, [Def::ORDER_CANCELED, Def::ORDER_SHIPPED, Def::ORDER_FINISHED, Def::ORDER_PENDING])) {
			return $respond->output(Respond::PARAMS_INVALID, Language::get('unsupport_status'));
		}

		// 如果目标为待付款，则必须是货到付款的订单
		if ($post->status == Def::ORDER_PENDING && $order->payment_code != 'cod') {
			return $respond->output(Respond::PARAMS_INVALID, Language::get('unsupport_status'));
		}

		$model = new \frontend\api\models\OrderForm();

		// 取消订单
		if ($post->status == Def::ORDER_CANCELED) {
			if (!$model->orderCancel($post, $order)) {
				return $respond->output(Respond::HANDLE_INVALID, Language::get('handle_fail'));
			}
		}

		// 发货
		if ($post->status == Def::ORDER_SHIPPED && $order->dtype == 'express') {
			if (!$model->orderShipped($post, $order)) {
				return $respond->output(Respond::HANDLE_INVALID, $model->errors);
			}
		}

		// 配送[必须是同城配送 商家自配的订单，且订单状态是待配送]
		if ($post->status == Def::ORDER_SHIPPED && $order->dtype == 'locality') {
			if (!$model->orderDelivered($post, $order)) {
				return $respond->output(Respond::HANDLE_INVALID, $model->errors);
			}
		}

		// 买家确认收货[完成交易]
		if ($post->status == Def::ORDER_FINISHED) {
			if (!$model->orderFinished($post, $order)) {
				return $respond->output(Respond::HANDLE_INVALID, $model->errors);
			}
		}

		// 买家确认收货（针对货到付款，买家确实收货后，下一步是 待付款）
		if ($post->status == Def::ORDER_PENDING) {
			if (!$model->orderConfirm($post, $order)) {
				return $respond->output(Respond::HANDLE_INVALID, $model->errors);
			}
		}

		$result = OrderModel::find()
			->select('status,add_time,pay_time,ship_time,receive_time,finished_time')
			->where(['order_id' => $post->order_id])
			->asArray()->one();

		return $respond->output(true, null, $result);
	}

	/**
	 * 获取预提交订单数据集合
	 * @api 接口访问地址: https://www.xxx.com/api/order/build
	 */
	public function actionBuild()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(true)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['store_id']);

		// 购物车/搭配购/拼团订单
		if (!in_array($post->otype, ['normal', 'mealbuy', 'teambuy'])) {
			$post->otype == 'normal';
		}

		return $this->build($respond, $post->otype, $post);
	}

	/**
	 * 获取订单商品数据
	 * @api 接口访问地址: https://www.xxx.com/api/order/goods
	 */
	public function actionGoods()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(true)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['order_id']);
		$post->order_id = $this->getOrderId($post);

		if (!$post->order_id) {
			return $respond->output(Respond::PARAMS_INVALID, Language::get('order_id_sn_empty'));
		}

		$list = OrderGoodsModel::find()->select('id,goods_id,spec_id,goods_name,goods_image,price,quantity,specification,comment,evaluation,o.order_id,o.order_sn,o.buyer_id,o.seller_id')
			->joinWith('order o', false)->where(['or', ['buyer_id' => Yii::$app->user->id], ['seller_id' => Yii::$app->user->id]])
			->andWhere(['o.order_id' => $post->order_id])->asArray()->all();

		foreach ($list as $key => $value) {
			$list[$key]['subtotal'] = sprintf('%.2f', round($value['price'] * $value['quantity'], 2));
			$list[$key]['goods_image'] = Formatter::path($value['goods_image'], 'goods');
		}

		return $respond->output(true, null, ['list' => $list]);
	}

	/**
	 * 根据支付商户号获取订单商品数据
	 * 【可能是多个订单的商品复合，该接口主要给微信小程序审核用】
	 * @api 接口访问地址: https://www.xxx.com/api/order/detail
	 */
	public function actionDetail()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(false)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true);

		if (!$post->payno) {
			return $respond->output(Respond::PARAMS_INVALID, Language::get('order_id_sn_empty'));
		}

		$all = DepositTradeModel::find()->select('bizOrderId')
			->where(['payTradeNo' => $post->payno, 'bizIdentity' => Def::TRADE_ORDER])
			->column();

		$orders = OrderModel::find()->select('order_id')->where(['in', 'order_sn', $all])->column();

		$list = [];
		if ($orders) {
			$list = OrderGoodsModel::find()->select('goods_id,goods_name,goods_image,price,quantity,specification')
				->where(['in', 'order_id', $orders])
				->asArray()->all();

			foreach ($list as $key => $value) {
				$list[$key]['goods_image'] = Formatter::path($value['goods_image'], 'goods');
			}
		}

		return $respond->output(true, null, $list);
	}

	/**
	 * 获取订单收货人数据
	 * @api 接口访问地址: https://www.xxx.com/api/order/extm
	 */
	public function actionExtm()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(true)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['order_id']);
		$post->order_id = $this->getOrderId($post);

		if (!$post->order_id) {
			return $respond->output(Respond::PARAMS_INVALID, Language::get('order_id_sn_empty'));
		}

		$order = OrderModel::find()->select('order_id,order_sn,buyer_id,seller_id')
			->where(['or', ['buyer_id' => Yii::$app->user->id], ['seller_id' => Yii::$app->user->id]])
			->andWhere(['order_id' => $post->order_id])->asArray()->one();

		if (!$order) {
			return $respond->output(Respond::RECORD_NOTEXIST, Language::get('no_such_order'));
		}

		$extm = OrderExtmModel::find()->select('consignee,region_id,address,phone_mob,freight,deliveryName')
			->where(['order_id' => $post->order_id])->asArray()->one();

		if (!$extm) {
			return $respond->output(Respond::RECORD_NOTEXIST, Language::get('no_order_extm'));
		}

		$result = array_merge($order, $extm);
		if ($array = RegionModel::getArray($extm['region_id'])) {
			$result = array_merge($result, $array);
		}

		return $respond->output(true, null, $result);
	}

	/** 
	 * 对订单的评价
	 * @api 接口访问地址: https://www.xxx.com/api/order/evaluate
	 */
	public function actionEvaluate()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(true)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['order_id']);
		$post->order_id = $this->getOrderId($post);

		if (!$post->order_id) {
			return $respond->output(Respond::PARAMS_INVALID, Language::get('order_id_sn_empty'));
		}

		$model = new \frontend\home\models\Buyer_orderEvaluateForm();
		if (!($orderInfo = $model->formData($post))) {
			return $respond->output(Respond::PARAMS_INVALID, $model->errors);
		}

		if (!$model->submit(ArrayHelper::toArray($post), $orderInfo)) {
			return $respond->output(Respond::HANDLE_INVALID, $model->errors);
		}

		return $respond->output(true);
	}

	/** 
	 * 对买家的评价回复
	 * @api 接口访问地址: https://www.xxx.com/api/order/replyevaluate
	 */
	public function actionReplyevaluate()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(true)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['spec_id', 'order_id']);
		$post->order_id = $this->getOrderId($post);

		if (!$post->order_id) {
			return $respond->output(Respond::PARAMS_INVALID, Language::get('order_id_sn_empty'));
		}

		$model = new \frontend\home\models\Seller_orderEvaluateForm();
		if (!($result = $model->submit($post))) {
			return $respond->output(Respond::HANDLE_INVALID, $model->errors);
		}

		return $respond->output(true, null, $result);
	}

	/** 
	 * 买家对单个商品修改评价
	 * @api 接口访问地址: http://api.xxx.com/order/editevaluate
	 */
	public function actionEditevaluate()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(true)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['spec_id', 'order_id']);
		$post->order_id = $this->getOrderId($post);

		if (!$post->order_id) {
			return $respond->output(Respond::PARAMS_INVALID, Language::get('order_id_sn_empty'));
		}

		$order = OrderModel::find()->select('buyer_id,seller_id')->where(['order_id' => $post->order_id])->one();
		if (!$order || ($order->buyer_id != Yii::$app->user->id)) {
			return $respond->output(Respond::RECORD_NOTEXIST, Language::get('no_such_item'));
		}

		if (($model = OrderGoodsModel::find()->where(['spec_id' => $post->spec_id, 'order_id' => $post->order_id])->one())) {
			$model->evaluation = $post->evaluation;
			$model->comment = addslashes($post->comment);

			// 评价晒图
			$model->images = '';
			if (isset($post->images) && $post->images) {
				$images = ArrayHelper::toArray($post->images);
				$model->images = (is_array($images) && count($images) > 0) ? json_encode($images) : '';
			}

			if ($model->save()) {
				// 更新店铺信用度
				StoreModel::updateAllCounters(['credit_value' => StoreModel::evaluationToValue($post->evaluation)], ['store_id' => $order->seller_id]);
			}
		}

		return $respond->output(true);
	}

	/**
	 * 获取订单发货信息
	 * @api 接口访问地址: https://www.xxx.com/api/order/exrpess
	 */
	public function actionExpress()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(true)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['order_id']);
		$post->order_id = $this->getOrderId($post);

		$result = OrderExpressModel::find()->select('code,company,number')
			->where(['order_id' => $post->order_id])
			//->asArray()->all(); // 一个订单有多条快递单
			->asArray()->one();

		return $respond->output(true, null, $result);
	}

	/**
	 * 获取订单物流跟踪数据
	 * @api 接口访问地址: https://www.xxx.com/api/order/logistic
	 */
	public function actionLogistic()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(true)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['order_id']);
		$post->order_id = $this->getOrderId($post);

		if (!$post->order_id) {
			return $respond->output(Respond::PARAMS_INVALID, Language::get('order_id_sn_empty'));
		}

		if (!($order = OrderModel::find()->alias('o')->select('o.order_id,order_sn,buyer_id,seller_id,oe.deliveryCode')
			->joinWith('orderExtm oe', false)
			->where(['o.order_id' => $post->order_id])
			->andWhere(['in', 'status', [Def::ORDER_SHIPPED, Def::ORDER_FINISHED]])
			->andWhere(['or', ['buyer_id' => Yii::$app->user->id], ['seller_id' => Yii::$app->user->id]])
			->asArray()->one())) {

			return $respond->output(Respond::RECORD_NOTEXIST, Language::get('no_such_order'));
		}

		// 每个订单发货用的快递插件会有不同
		$client = Plugin::getInstance('express')->build($order['deliveryCode']);
		if (!$client || !$client->isInstall()) {
			return $respond->output(Respond::HANDLE_INVALID, Language::get('no_such_express_plugin'));
		}

		$result = [];
		$list = OrderExpressModel::find()->alias('e')->select('e.*,oe.phone_mob')
			->where(['e.order_id' => $post->order_id])
			->joinWith('orderExtm oe', false)
			->asArray()->all();

		foreach ($list as $value) {
			$array = $client->submit($post, (object) $value);
			$result[] = array_merge($order, $array ? $array : []);
		}

		return $respond->output(true, null, $result);
	}

	/**
	 * 获取订单状态时间线
	 * @api 接口访问地址: https://www.xxx.com/api/order/timeline
	 */
	public function actionTimeline()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(true)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['order_id']);
		$post->order_id = $this->getOrderId($post);

		if (!$post->order_id) {
			return $respond->output(Respond::PARAMS_INVALID, Language::get('order_id_sn_empty'));
		}

		if (!($list = OrderLogModel::find()->select('status,remark,created')->where(['order_id' => $post->order_id])->orderBy(['id' => SORT_DESC])->asArray()->all())) {
			return $respond->output(Respond::RECORD_NOTEXIST, Language::get('no_such_order'));
		}

		foreach ($list as $key => $value) {
			$list[$key]['created'] = Timezone::localDate('Y-m-d H:i:s', $value['created']);
		}

		return $respond->output(true, null, $list);
	}

	/**
	 * 订单导出
	 * @api 接口访问地址: https://www.xxx.com/api/order/export
	 */
	public function actionExport()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(true)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true);
		$query = OrderModel::find()->alias('o')->select('o.order_id,o.order_sn,o.buyer_id,o.buyer_name,o.seller_id,o.buyer_name,o.seller_name as store_name,o.order_amount,o.status,o.add_time,o.pay_time,o.ship_time,o.finished_time,o.payment_name,o.postscript,oe.consignee,oe.region_id,oe.address,oe.phone_mob')
			->joinWith('orderExtm oe', false)
			->orderBy(['o.order_id' => SORT_DESC])
			->where(['or', ['buyer_id' => Yii::$app->user->id], ['seller_id' => Yii::$app->user->id]])
			->andWhere(['in', 'o.order_id', $post->items]);

		if ($query->count() == 0) {
			return $respond->output(Respond::RECORD_NOTEXIST, Language::get('no_data'));
		}
		$file = \backend\models\OrderExportForm::download($query->asArray()->all(), true);
		return $respond->output(true, null, $file);
	}

	/**
	 * 订单商品导出
	 * @api 接口访问地址: https://www.xxx.com/api/order/exportitems
	 */
	public function actionExportitems()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(true)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true);
		$query = OrderModel::find()->alias('o')->select('o.order_id,o.order_sn,o.buyer_name,o.seller_name as store_name,o.order_amount,o.status,o.add_time,o.pay_time,o.ship_time,o.finished_time,o.payment_name,o.postscript,oe.consignee,oe.region_id,oe.address,oe.phone_mob')
			->joinWith('orderExtm oe', false)
			->with(['orderGoods' => function ($q) {
				$q->select('order_id,goods_id,goods_name,quantity,specification')->orderBy(['id' => SORT_ASC]);
			}])
			->orderBy(['o.order_id' => SORT_DESC])
			->where(['or', ['buyer_id' => Yii::$app->user->id], ['seller_id' => Yii::$app->user->id]])
			->andWhere(['in', 'o.order_id', $post->items]);

		if ($query->count() == 0) {
			return $respond->output(Respond::RECORD_NOTEXIST, Language::get('no_data'));
		}

		$file = \backend\models\OrderExportItemsForm::download($query->asArray()->all(), true);
		return $respond->output(true, null, $file);
	}

	/**
	 * 从具体实例获取预支付订单数据
	 * @param string $otype = 'normal' 取购物车商品(可能包含多个店铺的商品)
	 * 				 $otype = 'mealbuy' 取搭配购商品(只会有一个店铺的商品)
	 * 				 $otype = 'teambuy' 拼团商品(只会有一个店铺且一个商品)
	 */
	public function build($respond, $otype = 'normal', $post = null)
	{
		$model = new \frontend\home\models\OrderForm(['otype' => $otype]);
		if (($goods_info = $model->getGoodsInfo($post)) === false) {
			return $respond->output(Respond::RECORD_NOTEXIST, $model->errors);
		}

		// 如果是自己店铺的商品，则不能购买
		if (in_array(Yii::$app->user->id, array_keys($goods_info['orderList']))) {
			return $respond->output(Respond::HANDLE_INVALID, Language::get('can_not_buy_yourself'));
		}

		// 获取订单模型
		$order_type = Business::getInstance('order')->build($otype, $post);

		// 获取订单数据
		if (($list = $order_type->formData($goods_info)) === false) {
			return $respond->output(Respond::RECORD_NOTEXIST, $order_type->errors);
		}

		return $respond->output(true, null, ['list' => $list]);
	}

	private function getOrderId($post)
	{
		if (isset($post->order_id)) {
			return $post->order_id;
		}

		if (isset($post->order_sn) && !empty($post->order_sn)) {
			return OrderModel::find()->select('order_id')->where(['order_sn' => $post->order_sn])->scalar();
		}

		return 0;
	}
}
