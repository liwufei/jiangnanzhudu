<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace backend\controllers;

use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;

use common\models\OrderModel;
use common\models\DepositTradeModel;
use common\models\OrderExpressModel;
use common\models\OrderGoodsModel;
use common\models\RegionModel;
use common\models\RefundModel;
use common\models\UserModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Message;
use common\library\Resource;
use common\library\Timezone;
use common\library\Page;
use common\library\Def;
use common\library\Setting;

/**
 * @Id OrderController.php 2018.8.2 $
 * @author mosir
 */

class OrderController extends \common\base\BaseAdminController
{
	public function actionIndex()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['limit', 'page']);

		if (!Yii::$app->request->isAjax) {
			$this->params['filtered'] = $this->getConditions($post);
			$this->params['search_options'] = $this->getSearchOption();
			$this->params['payment_list'] = $this->getPayments();
			$this->params['status_list'] = $this->getStatus();

			$this->params['_foot_tags'] = Resource::import([
				'script' => 'javascript/jquery.ui/jquery.ui.js,javascript/jquery.ui/i18n/' . Yii::$app->language . '.js',
				'style' => 'javascript/jquery.ui/themes/smoothness/jquery.ui.css'
			]);
			$this->params['page'] = Page::seo(['title' => Language::get('order_list')]);
			return $this->render('../order.index.html', $this->params);
		} else {
			$query = OrderModel::find()->select('order_id,order_sn,buyer_id,buyer_name,seller_name as store_name,order_amount,payment_name,status,add_time,pay_time,finished_time');
			$query = $this->getConditions($post, $query)->orderBy(['order_id' => SORT_DESC]);

			$page = Page::getPage($query->count(), $post->limit ? $post->limit : 10);
			$list = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
			foreach ($list as $key => $value) {
				$list[$key]['items'] = OrderGoodsModel::find()->select('goods_name')->where(['order_id' => $value['order_id']])->column();
				$list[$key]['tradeNo'] = DepositTradeModel::find()->select('tradeNo')->where(['bizOrderId' => $value['order_sn']])->scalar();
				$list[$key]['add_time'] = Timezone::localDate('Y-m-d H:i:s', $value['add_time']);
				$list[$key]['pay_time'] = Timezone::localDate('Y-m-d H:i:s', $value['pay_time']);
				$list[$key]['finished_time'] = Timezone::localDate('Y-m-d H:i:s', $value['finished_time']);
				$list[$key]['status'] = Def::getOrderStatus($value['status']);

				if ($phone = UserModel::find()->select('phone_mob')->where(['userid' => $value['buyer_id']])->scalar()) {
					$list[$key]['buyer_phone'] = $phone;
				}

				if ($refund = RefundModel::find()->select('refund_total_fee')->where(['status' => 'SUCCESS', 'tradeNo' => $list[$key]['tradeNo']])->one()) {
					$list[$key]['refund_amount'] = $refund->refund_total_fee;
				}
			}

			return Json::encode(['code' => 0, 'msg' => '', 'count' => $query->count(), 'data' => $list]);
		}
	}

	public function actionView()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['id']);
		if (!$post->id) {
			return Message::warning(Language::get('no_such_order'));
		}
		if (!($order = OrderModel::find()->alias('o')->select('o.*,oe.consignee,oe.region_id,oe.phone_mob,oe.address,oe.deliveryName')
			->where(['o.order_id' => $post->id])
			->joinWith('orderExtm oe', false)
			->with('orderGoods')->asArray()->one())) {

			return Message::warning(Language::get('no_such_order'));
		}

		if ($order['ship_time'] > 0) {
			$order['express'] = OrderExpressModel::find()->select('company,number')->where(['order_id' => $post->id])->asArray()->one();
		}
		if ($address = RegionModel::getArray($order['region_id'])) {
			$order['address'] = implode('', $address) . $order['address'];
		}
		$this->params['order'] = $order;

		$this->params['page'] = Page::seo(['title' => Language::get('order_view')]);
		return $this->render('../order.view.html', $this->params);
	}

	/**
	 * 订单时效设置
	 */
	public function actionTimeline()
	{
		if (!Yii::$app->request->isPost) {
			$this->params['setting'] = Setting::getInstance()->getAll();

			$this->params['page'] = Page::seo(['title' => Language::get('order_timeline')]);
			return $this->render('../order.timeline.html', $this->params);
		} else {
			$post = Basewind::trimAll(Yii::$app->request->post(), true);

			$model = new \backend\models\SettingForm();
			if (!$model->save($post, true)) {
				return Message::warning($model->errors);
			}
			return Message::display(Language::get('edit_ok'), ['order/timeline']);
		}
	}

	/* 订单走势（图表）本月和上月的数据统计 */
	public function actionTrend()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true);

		list($curMonthAmount, $curMonthQuantity, $curDays, $beginMonth, $endMonth) = $this->getMonthTrend(Timezone::gmtime());
		list($preMonthAmount, $preMonthQuantity, $preDays) = $this->getMonthTrend($beginMonth - 1);

		$series = array($curMonthAmount, $preMonthAmount);
		$legend = array('本月销售额', '上月销售额');
		if ($post->type != 'amount') {
			$series = array($curMonthQuantity, $preMonthQuantity);
			$legend = array('本月订单量', '上月订单量');
		}

		$days = $curDays > $preDays ? $curDays : $preDays;

		// 获取日期列表
		$xaxis = array();
		for ($day = 1; $day <= $days; $day++) {
			$xaxis[] = $day;
		}

		$this->params['echart'] = array(
			'id'		=>  mt_rand(),
			'theme' 	=> 'macarons',
			'width'		=> '100%',
			'height'    => 360,
			'option'  	=> json_encode([
				'grid' => ['left' => '20', 'right' => '20', 'top' => '80', 'bottom' => '20', 'containLabel' => true],
				'tooltip' 	=> ['trigger' => 'axis'],
				'legend'	=> [
					'data' => $legend
				],
				'calculable' => true,
				'xAxis' => [
					[
						'type' => 'category',
						'data' => $xaxis
					]
				],
				'yAxis' => [
					[
						'type' => 'value'
					]
				],
				'series' => [
					[
						'name' => $legend[0],
						'type' => 'line',
						'data' => $series[0],
						'smooth' => true
					],
					[
						'name' => $legend[1],
						'type' => 'line',
						'data' => $series[1],
						'smooth' => true
					]
				]
			])
		);

		$this->params['page'] = Page::seo(['title' => Language::get('order_trend')]);
		return $this->render('../echarts.html', $this->params);
	}

	/**
	 * 月数据统计 
	 * 拍下付款后即统计
	 */
	private function getMonthTrend($month = 0)
	{
		// 本月
		if (!$month) $month = Timezone::gmtime();

		// 获取当月的开始时间戳和结束那天的时间戳
		list($beginMonth, $endMonth) = Timezone::getMonthDay(Timezone::localDate('Y-m', $month));

		$list = DepositTradeModel::find()->select('amount,pay_time')->where(['bizIdentity' => Def::TRADE_ORDER])->andWhere(['>=', 'pay_time', $beginMonth])->andWhere(['<=', 'pay_time', $endMonth])->asArray()->all();

		// 该月有多少天
		$days = round(($endMonth - $beginMonth) / (24 * 3600));

		// 按天算归类
		$amount = $quantity = array();
		foreach ($list as $key => $val) {
			$day = Timezone::localDate('d', $val['pay_time']);

			if (isset($amount[$day - 1])) {
				$amount[$day - 1] += $val['amount'];
				$quantity[$day - 1]++;
			} else {
				$amount[$day - 1] = $val['amount'];
				$quantity[$day - 1] = 1;
			}
		}

		// 给天数补全
		for ($day = 1; $day <= $days; $day++) {
			if (!isset($amount[$day - 1])) {
				$amount[$day - 1] = 0;
				$quantity[$day - 1] = 0;
			}
		}
		// 按日期顺序排序
		ksort($amount);
		ksort($quantity);

		return array($amount, $quantity, $days, $beginMonth, $endMonth);
	}

	public function actionExport()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true);
		if ($post->id) $post->id = explode(',', $post->id);

		$query = OrderModel::find()->alias('o')->select('o.order_id,o.order_sn,o.buyer_id,o.buyer_name,o.seller_id,o.seller_name as store_name,o.order_amount,o.status,o.add_time,o.pay_time,o.ship_time,o.finished_time,o.payment_name,o.postscript,oe.consignee,oe.region_id,oe.address,oe.phone_mob')
			->joinWith('orderExtm oe', false)
			->orderBy(['o.order_id' => SORT_DESC]);
		if (!empty($post->id)) {
			$query->andWhere(['in', 'o.order_id', $post->id]);
		} else {
			$query = $this->getConditions($post, $query)->limit(100);
		}
		if ($query->count() == 0) {
			return Message::warning(Language::get('no_data'));
		}
		return \backend\models\OrderExportForm::download($query->asArray()->all());
	}

	private function getSearchOption()
	{
		return array(
			'order_sn' 		=> Language::get('order_sn'),
			'payment_name'	=> Language::get('payment_name'),
			'seller_name'	=> Language::get('store_name'),
			'buyer_name' 	=> Language::get('buyer_name'),
		);
	}

	private function getPayments()
	{
		return OrderModel::find()->select('payment_name')->where(['!=', 'payment_name', ''])->groupBy(['payment_name'])->column();
	}

	private function getStatus($status = null)
	{
		$result = [
			Def::ORDER_PENDING		=> Language::get('order_pending'),
			Def::ORDER_TEAMING 		=> Language::get('order_teaming'),
			Def::ORDER_ACCEPTED		=> Language::get('order_accepted'),
			Def::ORDER_DELIVERING	=> Language::get('order_delivering'),
			Def::ORDER_SHIPPED		=> Language::get('order_shipped'),
			//Def::ORDER_USING		=> Language::get('order_using'),
			Def::ORDER_FINISHED		=> Language::get('order_finished'),
			Def::ORDER_CANCELED		=> Language::get('order_canceled'),
		];
		if ($status !== null) {
			return isset($result[$status]) ? $result[$status] : '';
		}
		return $result;
	}

	private function getConditions($post, $query = null)
	{
		if ($query === null) {
			foreach (array_keys(ArrayHelper::toArray($post)) as $field) {
				if (in_array($field, ['search_name', 'status', 'add_time_from', 'add_time_to', 'pay_time_from', 'pay_time_to', 'payment_name', 'order_amount_from', 'order_amount_to'])) {
					return true;
				}
			}
			return false;
		}

		if ($post->field && $post->search_name && in_array($post->field, array_keys($this->getSearchOption()))) {
			$query->andWhere(['like', $post->field, $post->search_name]);
		}
		if (isset($post->status) && $post->status !== '') {
			$query->andWhere(['status' => intval($post->status)]);
		}

		if ($post->add_time_from) $post->add_time_from = Timezone::gmstr2time($post->add_time_from);
		if ($post->add_time_to) $post->add_time_to = Timezone::gmstr2time_end($post->add_time_to);
		if ($post->add_time_from && $post->add_time_to) {
			$query->andWhere(['and', ['>=', 'add_time', $post->add_time_from], ['<=', 'add_time', $post->add_time_to]]);
		}
		if ($post->add_time_from && (!$post->add_time_to || ($post->add_time_to <= $post->add_time_from))) {
			$query->andWhere(['>=', 'add_time', $post->add_time_from]);
		}
		if (!$post->add_time_from && ($post->add_time_to && ($post->add_time_to > Timezone::gmtime()))) {
			$query->andWhere(['<=', 'add_time', $post->add_time_to]);
		}

		if ($post->pay_time_from) $post->pay_time_from = Timezone::gmstr2time($post->pay_time_from);
		if ($post->pay_time_to) $post->pay_time_to = Timezone::gmstr2time_end($post->pay_time_to);
		if ($post->pay_time_from && $post->pay_time_to) {
			$query->andWhere(['and', ['>=', 'pay_time', $post->pay_time_from], ['<=', 'pay_time', $post->pay_time_to]]);
		}
		if ($post->pay_time_from && (!$post->pay_time_to || ($post->pay_time_to <= $post->pay_time_from))) {
			$query->andWhere(['>=', 'pay_time', $post->pay_time_from]);
		}
		if (!$post->pay_time_from && ($post->pay_time_to && ($post->pay_time_to > Timezone::gmtime()))) {
			$query->andWhere(['<=', 'pay_time', $post->pay_time_to]);
		}

		if ($post->order_amount_from) $post->order_amount_from = floatval($post->order_amount_from);
		if ($post->order_amount_to) $post->order_amount_to = floatval($post->order_amount_to);
		if ($post->order_amount_from && $post->order_amount_to) {
			$query->andWhere(['and', ['>=', 'order_amount', $post->order_amount_from], ['<=', 'order_amount', $post->order_amount_to]]);
		}
		if ($post->order_amount_from && (!$post->order_amount_to || ($post->order_amount_to < 0))) {
			$query->andWhere(['>=', 'order_amount', $post->order_amount_from]);
		}
		if (!$post->order_amount_from && ($post->order_amount_to > 0)) {
			$query->andWhere(['<=', 'order_amount', $post->order_amount_to]);
		}
		if ($post->payment_name) {
			$query->andWhere(['payment_name' => $post->payment_name]);
		}
		return $query;
	}
}
