<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace common\plugins\other\brush;

use yii;

use common\models\UserModel;
use common\models\OrderModel;
use common\models\OrderGoodsModel;
use common\models\OrderExtmModel;
use common\models\StoreModel;
use common\models\GoodsModel;
use common\models\GoodsSpecModel;
use common\models\GoodsStatisticsModel;

use common\library\Def;
use common\library\Language;
use common\library\Plugin;
use common\library\Timezone;

use common\plugins\BaseOther;

/**
 * @Id brush.plugin.php 2018.9.5 $
 * @author mosir
 */

class Brush extends BaseOther
{
	/**
	 * 插件实例
	 * @var string $code
	 */
	public $code = 'brush';

	/**
	 * 下单，支付，发货，收货，评价
	 */
	public function create($store_id, $specs = [])
	{
		if (!($order_id = $this->createOrder($store_id, $specs))) {
			return false;
		}
		if (!$this->payOrder($order_id)) {
			return false;
		}
		if (!$this->shipOrder($order_id)) {
			return false;
		}
		if (!$this->confirmOrder($order_id)) {
			return false;
		}
		if (!$this->evalOrder($order_id)) {
			return false;
		}

		return true;
	}

	/**
	 * 创建订单
	 */
	public function createOrder($store_id, $specs = [])
	{
		if (!$buyer = $this->getBuyer($store_id)) {
			return false;
		}

		if (!($store = StoreModel::find()->select('store_id,store_name')->where(['store_id' => $store_id])->one())) {
			$this->errors = Language::get('no_data');
			return false;
		}

		if (!($order_id = $this->insertOrder($buyer, $store))) {
			return false;
		}

		if (!$this->insertOrderGoods($order_id, $specs)) {
			return false;
		}

		if (!$this->insertOrderExtm($order_id, $buyer)) {
			return false;
		}

		return $order_id;
	}

	/**
	 * 支付
	 */
	public function payOrder($order_id)
	{
		if (!($model = OrderModel::findOne($order_id))) {
			return false;
		}

		$payment = Plugin::getInstance('payment')->build('deposit')->getInfo();

		$model->pay_time = Timezone::gmtime();
		$model->payment_code = 'deposit';
		$model->payment_name = $payment['name'];
		$model->status = Def::ORDER_ACCEPTED;
		if (!($model->save())) {
			$this->errors = $model->errors;
			return false;
		}

		// 更新销量
		$list = OrderGoodsModel::find()->where(['order_id' => $order_id])->all();
		foreach ($list as $value) {
			GoodsStatisticsModel::updateStatistics($value->goods_id, 'sales', $value->quantity);
		}

		return true;
	}

	/**
	 * 发货
	 */
	public function shipOrder($order_id)
	{
		if (!($model = OrderModel::findOne($order_id))) {
			return false;
		}

		$model->dtype = 'express';
		$model->ship_time = Timezone::gmtime();
		$model->status = Def::ORDER_SHIPPED;
		if (!($model->save())) {
			$this->errors = $model->errors;
			return false;
		}

		return true;
	}

	/**
	 * 确认收货
	 */
	public function confirmOrder($order_id)
	{
		if (!($model = OrderModel::findOne($order_id))) {
			return false;
		}

		$model->receive_time = Timezone::gmtime();
		$model->finished_time = Timezone::gmtime();
		$model->status = Def::ORDER_FINISHED;
		if (!($model->save())) {
			$this->errors = $model->errors;
			return false;
		}

		return true;
	}

	/**
	 * 评价
	 */
	public function evalOrder($order_id)
	{
		if (!($order = OrderModel::find()->select('order_id,buyer_id,seller_id')->where(['order_id' => $order_id])->asArray()->one())) {
			return false;
		}

		$specs = OrderGoodsModel::find()->select('spec_id')->where(['order_id' => $order_id])->all();

		$list = [];
		foreach ($specs as $spec) {
			$list[$spec->spec_id] = [
				'value' => mt_rand(4, 5),
				'comment' => $this->getComment(),
			];
		}

		$model = new \frontend\home\models\Buyer_orderEvaluateForm();

		$post['evaluations'] = [
			'goods' => $list,
			'store' => [
				'service' => 5,
				'shipped' => 5
			]
		];

		if (!$model->submit($post, $order)) {
			$this->errors = $model->errors;
			return false;
		}

		return true;
	}

	/**
	 * 插入订单表数据 
	 */
	public function insertOrder($buyer, $store)
	{
		$model = new OrderModel();
		$model->buyer_id = $buyer->userid;
		$model->buyer_name = addslashes($buyer->username);
		$model->seller_id = $store->store_id;
		$model->seller_name = addslashes($store->store_name);
		$model->order_sn = OrderModel::genOrderSn($store->store_id);
		$model->otype = 'normal';
		$model->gtype = 'material';
		$model->status = Def::ORDER_PENDING;
		$model->add_time = Timezone::gmtime();
		$model->goods_amount = 0;
		$model->order_amount = 0;
		$model->anonymous = 0;
		$model->postscript = '';
		$model->memo = '刷单';

		if (!($model->save())) {
			$this->errors = $model->errors;
			return false;
		}

		return $model->order_id;
	}

	/**
	 * 插入商品信息 
	 */
	public function insertOrderGoods($order_id = 0, $specs = [])
	{
		foreach ($specs as $spec_id) {
			$spec = GoodsSpecModel::find()->select('goods_id,price')->where(['spec_id' => $spec_id])->one();
			$goods = GoodsModel::find()->select('goods_name,default_image')->where(['goods_id' => $spec->goods_id])->one();

			$model = new OrderGoodsModel();
			$model->order_id = $order_id;
			$model->goods_id = $spec->goods_id;
			$model->goods_name = $goods->goods_name;
			$model->spec_id = $spec_id;
			$model->specification = '';
			$model->price = $spec->price;
			$model->quantity = mt_rand(1, 5);
			$model->goods_image = $spec->image ? $spec->image : $goods->default_image;
			if (!$model->save()) {
				$this->errors = $model->errors;
				return false;
			}

			$amount = $model->price * $model->quantity;
			OrderModel::updateAll(['order_amount' => $amount, 'goods_amount' => $amount], ['order_id' => $order_id]);
		}

		return true;
	}

	/**
	 * 插入收货人信息 
	 */
	public function insertOrderExtm($order_id = 0, $buyer)
	{
		$model = new OrderExtmModel();

		$model->order_id = $order_id;
		$model->consignee = '匿名用户'; //$buyer->username;
		$model->region_id = 4;
		$model->address = sprintf('朝阳路%s号宇峰大厦%s栋', mt_rand(1, 100), mt_rand(1, 100));
		$model->phone_tel = '';
		$model->phone_mob = $buyer->phone_mob ? $buyer->phone_mob : '139' . mt_rand(10000000, 99999999);
		$model->deliveryName = '快递发货';
		$model->deliveryCode = 'kuaidi100';
		$model->freight = 0;

		if (!$model->save()) {
			$this->errors = $model->errors;
			return false;
		}
		return true;
	}

	/**
	 * 创建买家
	 */
	public function createUser()
	{
		$model = new \frontend\home\models\UserRegisterForm();

		do {
			$model->username = UserModel::generateName();
			$model->password  = mt_rand(1000, 9999);
			$model->phone_mob = '';
			$user = $model->register(['nickname' => $model->username]);
		} while (!$user);

		return $user;
	}

	/**
	 * 随机获取一个买家，如果没有则创建
	 */
	public function getBuyer($store_id)
	{
		$id = mt_rand(1, 1000);  // 从现有库中获取
		if ($id == $store_id || !($user = UserModel::findOne($id))) {
			$user = $this->createUser();
		}

		return $user;
	}

	/**
	 * 获取随机的评价文字
	 */
	public function getComment()
	{
		$index = mt_rand(1, 8);

		$array = [
			'很好用，价格很实惠',
			'老板态度很好，点赞',
			'这是购物体验最好的一次，下次再来',
			'还好没有入坑，产品质量很好，物流很快',
			'物流很快，老板态度很好，很愉快的一次购物体验',
			'超出预期了，产品非常不错！',
			'东西非常好，一定给朋友推荐',
			'东西很好，客服很专业，咨询什么都能回答我的疑问'
		];

		return $array[$index];
	}
}
