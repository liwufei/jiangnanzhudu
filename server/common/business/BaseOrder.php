<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace common\business;

use yii;
use yii\helpers\ArrayHelper;

use common\models\OrderModel;
use common\models\OrderLogModel;
use common\models\OrderGoodsModel;
use common\models\OrderExtmModel;
use common\models\CartModel;
use common\models\GoodsStatisticsModel;
use common\models\AddressModel;
use common\models\CouponModel;
use common\models\CouponsnModel;
use common\models\IntegralModel;
use common\models\DistributeSettingModel;
use common\models\RegionModel;
use common\models\StoreModel;

use common\library\Basewind;
use common\library\Def;
use common\library\Timezone;
use common\library\Language;
use common\library\Plugin;

/**
 * @Id BaseOrder.php 2018.7.12 $
 * @author mosir
 */

class BaseOrder
{
	/**
	 * 订单类型
	 * @var string $otype
	 */
	protected $otype;

	/**
	 * 页面提交参数
	 * @var object $post
	 */
	public $post;

	/**
	 * 其他额外参数
	 * @var array $params
	 */
	public $params;

	/**
	 * 错误捕捉
	 * @var object $errors
	 */
	public $errors;

	public function __construct($otype, $post = null, $params = [])
	{
		$this->otype 	= $otype;
		$this->post 	= $post;
		$this->params 	= $params;
	}

	/**
	 * 提交订单信息 
	 */
	public function submit($data = [])
	{
		return 0;
	}

	/**
	 * 插入订单相关信息
	 * @return int $insertFail 插入失败数
	 * @return array $result 包含店铺和订单号的数组
	 */
	public function insertOrderData($base_info = [], $goods_info = [], $consignee_info = [], $delivery_info = [])
	{
		$result = [];
		$fails = [];
		foreach ($base_info as $store_id => $store) {
			if (!($order_id = $this->insertOrder($base_info[$store_id]))) {
				$this->errors = Language::get('create_order_failed');
				break;
			}

			if (!($this->insertOrderGoods($order_id, $goods_info['orderList'][$store_id]))) {
				$fails[] = $order_id;
				$this->errors = Language::get('create_order_failed');
				break;
			}

			if ($consignee_info) {
				if (!($this->insertOrderExtm($order_id, $consignee_info, $delivery_info[$store_id]))) {
					$fails[] = $order_id;
					$this->errors = Language::get('create_order_failed');
					break;
				}
			}
			if (!($this->insertOrderLog($order_id))) {
				$fails[] = $order_id;
				$this->errors = Language::get('create_order_failed');
				break;
			}

			$result[$store_id] = $order_id;
		}

		// 考虑合并付款的情况下（优惠，积分抵扣等问题），必须保证本批次的所有订单都插入成功，要不都删除本次订单
		$fails = array_unique($fails);
		if (count($fails) > 0) {
			$this->deleteOrderData($fails);
			return false;
		}

		return $result;
	}

	/**
	 * 插入订单表数据 
	 */
	public function insertOrder($order_info = [])
	{
		$model = new OrderModel();
		foreach ($order_info as $key => $value) {
			$model->$key = $value;
		}
		return $model->save() ? $model->order_id : 0;
	}

	/**
	 * 插入商品信息 
	 */
	public function insertOrderGoods($order_id = 0, $list = [])
	{
		// 是否有邀请成交的商品
		$list = DistributeSettingModel::orderInvite($list);

		foreach ($list['items'] as $value) {
			$model = new OrderGoodsModel();
			$model->order_id = $order_id;
			$model->goods_id = $value['goods_id'];
			$model->goods_name = $value['goods_name'];
			$model->spec_id = $value['spec_id'];
			$model->specification = $value['specification'] ? $value['specification'] : '';
			$model->price = $value['price'];
			$model->quantity = $value['quantity'];
			$model->goods_image = $value['goods_image'] ? $value['goods_image'] : '';

			if (!empty($value['invite'])) {
				$model->inviteType = $value['invite']['type'];
				$model->inviteRatio = serialize($value['invite']['ratio']);
				$model->inviteUid  = $value['invite']['uid'];
			}
			$model->save();
		}
		return true;
	}

	/**
	 * 插入收货人信息 
	 */
	public function insertOrderExtm($order_id = 0, $consignee_info  = [], $delivery_info = [])
	{
		$model = new OrderExtmModel();

		if (is_array($delivery_info)) {
			$consignee_info = array_merge($consignee_info, $delivery_info);
		}

		$columns = $model->getTableSchema()->columns;
		$fields = array_keys(ArrayHelper::getColumn($columns, 'name'));

		$model->order_id = $order_id;
		foreach ($consignee_info as $key => $value) {
			if (in_array($key, $fields)) {
				$model->$key = $value;
			}
		}
		return $model->save();
	}

	/**
	 * 插入订单操作日志
	 */
	public function insertOrderLog($order_id = 0)
	{
		return OrderLogModel::create($order_id, Language::get('已下单'));
	}

	/**
	 * 插入合并付款提交订单过程中，如有插入失败的订单，则删除批次所有订单
	 */
	public function deleteOrderData($result = [])
	{
		foreach ($result as $order_id) {
			if (!$order_id) continue;
			OrderModel::deleteAll(['order_id' => $order_id]);
			OrderGoodsModel::deleteAll(['order_id' => $order_id]);
			OrderExtmModel::deleteAll(['order_id' => $order_id]);
			OrderLogModel::deleteAll(['order_id' => $order_id]);
		}
	}

	/**
	 * 处理订单基本信息，返回有效的订单信息数组 
	 */
	public function handleOrderInfo($goods_info = [])
	{
		// 返回基本信息
		$result = [];

		$post = ArrayHelper::toArray($this->post);
		foreach ($goods_info['orderList'] as $store_id => $order) {
			$result[$store_id] = array(
				'order_sn'      =>  OrderModel::genOrderSn($store_id),
				'otype'         =>  $goods_info['otype'],
				'gtype'    		=>  implode(',', $goods_info['gtype']),
				'seller_id'    	=>  $order['store_id'],
				'seller_name'   =>  addslashes($order['store_name']),
				'buyer_id'      =>  Yii::$app->user->id,
				'buyer_name'    =>  addslashes(Yii::$app->user->identity->username),
				'status'       	=>  Def::ORDER_PENDING,
				'add_time'      =>  Timezone::gmtime(),
				'goods_amount'  =>  $order['amount'],
				'anonymous'     =>  isset($post['anonymous'][$store_id]) ? intval($post['anonymous'][$store_id]) : 0,
				'postscript'   	=>  trim($post['postscript'][$store_id]),
				'dtype'			=>  $post['delivery']['type'],
			);
		}
		if (empty($result)) {
			$this->errors = Language::get('base_info_check_fail');
			return false;
		}
		return $result;
	}

	/**
	 * 获取我的收货地址
	 * @param int $addr_id 只查询指定记录
	 */
	public function getMyAddress($addr_id = 0)
	{
		$query = AddressModel::find()->where(['userid' => Yii::$app->user->id])->orderBy(['defaddr' => SORT_DESC])->indexBy('addr_id');
		if ($addr_id) {
			return $query->andWhere(['addr_id' => intval($addr_id)])->asArray()->one();
		}
		return $query->asArray()->all();
	}

	/**
	 * 获取本次订单的各个店铺的可用优惠券 
	 */
	public function getStoreCouponList($goods_info = [])
	{
		foreach ($goods_info['orderList'] as $store_id => $order) {
			$goods_info['orderList'][$store_id]['coupons'] = CouponModel::getAvailableByOrder($order);
		}
		return $goods_info;
	}

	/**
	 * 提交订单时
	 * 取得有效的订单折扣信息，如积分抵扣，店铺优惠券的合理性，返回各个优惠减少的金额 
	 */
	public function getAllDiscountByPost($goods_info = [])
	{
		$result = $discount_info = [];

		// 验证买家使用多少积分抵扣货款的有效性
		if (isset($goods_info['integralExchange']) && ($result = $goods_info['integralExchange'])) {

			!isset($this->post->exchange_integral) && $this->post->exchange_integral = 0;
			if ($this->post->exchange_integral > $result['maxPoints']) {
				$this->errors = Language::get('order_can_use_max_integral') . $result['maxPoints'];
				return false;
			} elseif ($this->post->exchange_integral > 0) {
				$discount_info['integral'] = array(
					'amount' 		=> round($this->post->exchange_integral * $result['rate'], 2),
					'points' 		=> $this->post->exchange_integral,
					'orderIntegral' => $result['orderIntegral']
				);
			}
		}

		// 验证买家使用的优惠券的有效性
		$goods_info = $this->getStoreCouponList($goods_info);
		$result = $goods_info['orderList'];

		if (isset($this->post->coupon_sn) && !empty($this->post->coupon_sn)) {
			foreach ($this->post->coupon_sn as $store_id => $coupon_sn) {
				if (isset($result[$store_id]['coupons']) && !empty($result[$store_id]['coupons'])) {
					foreach ($result[$store_id]['coupons'] as $key => $value) {
						if ($coupon_sn == $value['coupon_sn']) {
							$discount_info['coupon'][$store_id] = array('money' => $value['money'], 'coupon_sn' => $coupon_sn);
							break;
						}
					}
				}
			}
		}

		$client = Plugin::getInstance('promote')->build();
		foreach (array_keys($goods_info['orderList']) as $store_id) {

			// 处理首单立减
			if ($promote = $client->getOrderExclusiveInfo($store_id, $goods_info['orderList'][$store_id])) {
				$discount_info['exclusive'][$store_id] = array('amount' => $promote['price']);
			}

			// 处理满优惠信息
			if ($promote = $client->getOrderFullPreferInfo($store_id, $goods_info['orderList'][$store_id])) {
				$discount_info['fullprefer'][$store_id] = array('amount' => $promote['price']);
			}
		}

		return $discount_info;
	}

	/**
	 * 提交订单时
	 * 检验折扣信息和订单总价的合理性
	 */
	public function checkAllDiscountForOrderAmount(&$base_info, &$discount_info, $delivery_info, $integralExchangeRate = 0)
	{
		$amount = 0;
		foreach ($base_info as $store_id => $order_info) {

			// 商品总价
			$goodsAmount = floatval($order_info['goods_amount']);

			// 未含运费
			$storeAmount = $goodsAmount;

			// 优惠项目
			$exclusiveDiscount = $couponDiscount = $fullpreferDiscount = 0;

			// 处理每个订单的首购礼金[没有满金额要求]
			if (isset($discount_info['exclusive'][$store_id]['amount'])) {
				$exclusiveDiscount = $discount_info['exclusive'][$store_id]['amount'];
				if ($exclusiveDiscount > 0) {
					// 如果优惠大于商品总价
					// if ($exclusiveDiscount > $goodsAmount) {
					// 	$this->errors = Language::get('discount_gt_storeAmount');
					// 	return false;
					// }
					$storeAmount -= $exclusiveDiscount;
				}
			}

			// 每个订单的满优惠优惠
			if (isset($discount_info['fullprefer'][$store_id]['amount'])) {
				$fullpreferDiscount = $discount_info['fullprefer'][$store_id]['amount'];
				if ($fullpreferDiscount > 0) {
					// 如果优惠大于商品总价
					// if ($fullpreferDiscount > $goodsAmount) {
					// 	$this->errors = Language::get('discount_gt_storeAmount');
					// 	return false;
					// }

					if ($fullpreferDiscount <= $goodsAmount) {
						$storeAmount -= $fullpreferDiscount;
					}
				}
			}

			// 每个订单的店铺优惠券优惠
			if (isset($discount_info['coupon'][$store_id]['money'])) {
				$couponDiscount = $discount_info['coupon'][$store_id]['money'];
				if ($couponDiscount > 0) {
					// 如果优惠大于商品总价
					// if ($couponDiscount > $goodsAmount) {
					// 	$this->errors = Language::get('discount_gt_storeAmount');
					// 	return false;
					// }

					if ($couponDiscount <= $goodsAmount) {
						$storeAmount -= $couponDiscount;
					}
				}
			}

			if ($storeAmount < 0) $storeAmount = 0;

			// 运费
			$freight = isset($delivery_info[$store_id]) ? floatval($delivery_info[$store_id]['freight']) : 0;

			// 包含运费
			$storeAmount += $freight;

			// 返回的数据
			$base_info[$store_id]['order_amount'] 	= $storeAmount;
			$base_info[$store_id]['goods_amount']   = $goodsAmount;
			$base_info[$store_id]['discount']		= $goodsAmount - $storeAmount + $freight; // 实际优惠金额

			// 所有订单实际支付的金额汇总（未使用积分前）
			$amount	+= $storeAmount;
		}

		// 情况一：所有订单减去折扣之后的总额为零，那么说明已经不能再使用积分来抵扣了
		// 情况二：所有订单减去折扣之后的总额不为零，则判断积分抵扣的金额是否合理（如使用积分抵扣后订单总额为负，则不合理）
		if (isset($discount_info['integral']['amount']) && ($discount_info['integral']['amount'] > 0)) {
			if (($amount <= 0) || ($discount_info['integral']['amount'] > $amount)) {
				$this->errors = Language::get('integral_gt_amount');
				return false;
			}
		}

		// 至此说明所使用的积分抵扣值是合理的（不大于订单总价了，或者本次订单没有使用积分来抵扣，如果使用了积分抵扣，还要继续判断哪个订单使用了多少积分来抵扣，用分摊来计算）
		if (($amount > 0) && (isset($discount_info['integral']['amount']) && ($discount_info['integral']['amount'] > 0))) {
			foreach ($base_info as $store_id => $order_info) {
				$rate = $discount_info['integral']['orderIntegral']['items'][$store_id] / $discount_info['integral']['orderIntegral']['totalPoints'];
				$sharePoints = round($rate * $discount_info['integral']['points'], 2);
				$shareMoney = round($rate * $discount_info['integral']['points'] * $integralExchangeRate, 2);

				// 在这里已经不用判断各个订单分摊的积分，是否抵扣完订单总价甚至抵扣为负值的情况了，因为最多能抵扣完， 不会出现负值的情况
				$discount_info['integral']['shareIntegral'][$store_id] = ['money' => $shareMoney, 'points' => $sharePoints];

				// 返回的数据
				$base_info[$store_id]['order_amount'] 	-= $shareMoney;
				//$base_info[$store_id]['discount']		+= $shareMoney;
			}
		}

		return true;
	}

	/**
	 * 更新优惠券的使用次数 
	 */
	public function updateCouponRemainTimes($result = [], $coupon = [])
	{
		foreach ($result as $store_id => $order_id) {
			if (isset($coupon[$store_id]['coupon_sn'])) {
				$query = CouponsnModel::find()->where(['coupon_sn' => $coupon[$store_id]['coupon_sn']])->one();
				if ($query->remain_times > 0) {
					$query->updateCounters(['remain_times' => -1]);
				}
			}
		}
	}

	/**
	 * 保存订单使用的积分数额 
	 */
	public function saveIntegralInfoByOrder($result = [], $integral = [])
	{
		if (!empty($result) && isset($integral['points']) && ($integral['points'] > 0)) {
			foreach ($result as $store_id => $order_id) {
				if ($integral['shareIntegral'][$store_id]['points'] > 0) {
					$order = OrderModel::find()->select('order_sn, buyer_id')->where(['order_id' => $order_id])->one();

					// 扣减操作
					IntegralModel::updateIntegral([
						'userid' 	=> $order->buyer_id,
						'type'    	=> 'buying_pay_integral',
						'order_id'	=> $order_id,
						'order_sn'	=> $order->order_sn,
						'points'  	=> $integral['shareIntegral'][$store_id]['points'],
						'money' 	=> $integral['shareIntegral'][$store_id]['money'],
						'state'   	=> 'frozen',
						'flow'    	=> 'minus'
					]);
				}
			}
		}
	}

	/**
	 * 下单完成后的操作，如清空购物车，更新库存等
	 */
	public function afterInsertOrder($order_id, $store_id, $list)
	{
		// 如是同城配送订单，创建或更新骑手门店信息【更新主要是考虑位置坐标改变同步到骑手门店】
		$order = OrderModel::find()->select('dtype')->where(['order_id' => $order_id])->one();
		if ($order->dtype == 'locality') {
			$code = OrderExtmModel::find()->select('deliveryCode')->where(['order_id' => $order_id])->scalar();
			$client = Plugin::getInstance('locality')->build($code);
			if ($client && $client->isInstall()) {
				$store = StoreModel::find()->select('store_id,store_name,address,region_id,longitude,latitude,contacter,phone')->where(['store_id' => $store_id])->one();
				if ($regions = RegionModel::getNames($store->region_id)) {
					$store->address = $regions . $store->address;
				}
				$client->createShop($store);
			}
		}

		// 清空指定购物车
		if (in_array($this->otype, ['normal'])) {
			CartModel::deleteAll(['userid' => Yii::$app->user->id, 'selected' => 1, 'store_id' => $store_id]);
		}

		// 减去商品库存
		OrderModel::changeStock('-', $order_id);

		// 更新下单次数（非销量）
		foreach ($list['items'] as $goods) {
			GoodsStatisticsModel::updateStatistics($goods['goods_id'], 'orders');
		}

		$orderInfo = OrderModel::find()->where(['order_id' => $order_id])->asArray()->one();

		// 邮件提醒： 买家已下单通知自己 
		Basewind::sendMailMsgNotify($orderInfo, ['receiver' => Yii::$app->user->id, 'key' => 'tobuyer_new_order_notify']);

		// 短信和邮件提醒： 买家已下单通知卖家 
		Basewind::sendMailMsgNotify(
			$orderInfo,
			[
				'receiver' => $orderInfo['seller_id'],
				'key' => 'toseller_new_order_notify',
			],
			[
				'sender' => $orderInfo['seller_id'],
				'receiver' => $orderInfo['seller_id'],
				'key' => 'toseller_new_order_notify',
			]
		);
	}
}
