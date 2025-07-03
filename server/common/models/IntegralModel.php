<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace common\models;

use Yii;
use yii\db\ActiveRecord;

use common\models\IntegralSettingModel;
use common\models\IntegralLogModel;
use common\models\GoodsIntegralModel;
use common\models\OrderModel;
use common\models\StoreModel;

use common\library\Language;

/**
 * @Id IntegralModel.php 2018.3.22 $
 * @author mosir
 */

class IntegralModel extends ActiveRecord
{
	/**
	 * @inheritdoc
	 */
	public static function tableName()
	{
		return '{{%integral}}';
	}

	// 关联表
	public function getUser()
	{
		return parent::hasOne(UserModel::className(), ['userid' => 'userid']);
	}

	/**
	 * 创建积分账户
	 */
	public static function createAccount($userid, $amount = 0)
	{
		$model = new IntegralModel();
		$model->userid = $userid;
		$model->amount = $amount;
		if (!$model->save()) {
			return false;
		}
		return $model;
	}

	/**
	 * 插入积分记录，并返回最新的余额 
	 */
	public static function updateIntegral($data = [])
	{
		extract($data);

		// 当积分开关未启用的情况下，在数组中的积分变动类型继续执行
		$allow = ['return_integral', 'buying_has_integral', 'selling_has_integral'];
		if (!in_array($type, $allow) && !IntegralSettingModel::getSysSetting('enabled')) {
			return false;
		}

		if (empty($points) || empty($userid)) {
			return false;
		}

		$amount = $points;
		if (isset($flow) && $flow == 'minus') $amount = -$points;

		if (!parent::find()->where(['userid' => $userid])->exists()) {
			if (!self::createAccount($userid, $amount)) {
				return false;
			}
			$balance = $amount;
		} else {
			parent::updateAllCounters(['amount' => $amount], ['userid' => $userid]);
			$balance = parent::find()->select('amount')->where(['userid' => $userid])->scalar();
		}

		if (IntegralLogModel::addLog(array_merge($data, ['balance' => $balance]))) {
			return $balance;
		}
		return false;
	}

	/**
	 * 订单取消/全额退款后，归还买家之前被预扣积分 
	 */
	public static function returnIntegral($order = [])
	{
		$model = IntegralLogModel::find()->select('log_id,changes')->where(['order_id' => $order['order_id'], 'state' => 'frozen', 'type' => 'buying_pay_integral'])->one();
		if ($model && ($points = abs($model->changes))) {
			$data = [
				'userid'  => $order['buyer_id'],
				'type'    => 'return_integral',
				'order_id' => $order['order_id'],
				'order_sn' => $order['order_sn'],
				'points'  => $points,
				'flag'    => Language::get('return_integral_for_cancel_order')
			];

			if (self::updateIntegral($data)) {
				$model->state = 'cancel';
				return $model->save();
			}
		}

		return false;
	}

	/**
	 * 订单支付成功后
	 * 给买家赠送积分
	 * 该逻辑受是否开启积分插件的影响
	 */
	public static function givesIntegral($order = [])
	{
		if (!IntegralSettingModel::getSysSetting('enabled')) {
			return false;
		}

		$store = StoreModel::find()->select('sgrade')->where(['store_id' => $order['seller_id']])->one();
		if (!$store) {
			return false;
		}

		// 订单付款金额（如果要减掉运费可在此拓展）
		$realOrderAmount = $order['order_amount'];

		// 赠送积分
		$gives = round($realOrderAmount * IntegralSettingModel::getSysSetting(['buygoods', $store->sgrade]), 2);
		if ($gives > 0) {
			if (self::updateIntegral([
				'userid'  => $order['buyer_id'],
				'type'    => 'buying_has_integral',
				'points'  => $gives,
				'order_id' => $order['order_id'],
				'order_sn' => $order['order_sn'],
				'flag'	  => sprintf(Language::get('buying_has_integral_logtext'), $order['order_sn']),
			])) {
				return true;
			}
		}

		return false;
	}

	/**
	 * 订单完成后
	 * 将买家使用的抵扣积分划拨给商家
	 * 该逻辑不受是否开启积分插件的影响
	 */
	public static function distributeIntegral($order = [])
	{
		// 买家使用抵扣的积分，给卖家
		$model = IntegralLogModel::find()->select('log_id,changes')->where(['order_id' => $order['order_id'], 'type' => 'buying_pay_integral', 'state' => 'frozen'])->one();
		if ($points = abs($model->changes)) {
			// 把冻结的积分发给商家
			if (self::updateIntegral([
				'userid'  => $order['seller_id'],
				'type'    => 'selling_has_integral',
				'points'  => $points,
				'order_id' => $order['order_id'],
				'order_sn' => $order['order_sn'],
				'flag'	  => sprintf(Language::get('selling_has_integral_logtext'), $order['order_sn']),
			])) {
				// 把冻结的记录状态改为完成
				$model->state = 'finished';
				return $model->save();
			}
		}

		return false;
	}

	/**
	 * 订单页，获取积分信息，以便做验证 
	 */
	public static function getIntegralByOrders($goodsList = [])
	{
		$maxPoints = $getPoints = $rate = 0;
		$orderIntegral = [];

		if (IntegralSettingModel::getSysSetting('enabled')) {
			// 积分兑换比率
			if (!($rate = IntegralSettingModel::getSysSetting('rate'))) {
				$rate = 0;
			}

			$integralRate = [];
			foreach ($goodsList as $goods) {
				// 获取店铺等级对应的积分比率
				if (!isset($integralRate[$goods['store_id']])) {
					$store = StoreModel::find()->select('sgrade')->where(['store_id' => $goods['store_id']])->one();
					$integralRate[$goods['store_id']] = IntegralSettingModel::getSysSetting(['buygoods', $store->sgrade]);
				}

				// （计算获得赠送的积分）如果店铺所处的等级的购物赠送积分比率大于零
				if ($integralRate[$goods['store_id']] > 0) {
					$sgradeRate = $integralRate[$goods['store_id']];
					$getPoints += $goods['price'] * $goods['quantity'] * $sgradeRate;
				}

				// （计算可最多使用多少积分抵扣） 如果积分兑换比率大于零
				if ($rate > 0) {
					$goods_integral = GoodsIntegralModel::find()->select('max_exchange')->where(['goods_id' => $goods['goods_id']])->one();

					// 如果单个商品的最大积分抵扣小于或等于单个商品的价格，则是合理的，否则，仅取能抵扣完商品价格的积分值
					$max_exchange_price = round($goods_integral->max_exchange * $rate, 2);
					if ($max_exchange_price > $goods['price']) {
						$max_exchange_price = $goods['price'];
					}

					// 购物车中每个商品可使用的最大抵扣积分值
					$goodsMaxPoints = ($max_exchange_price / $rate) * $goods['quantity'];

					// 每个订单最多可使用的最大抵扣积分值
					if (!isset($orderIntegral[$goods['store_id']])) $orderIntegral[$goods['store_id']] = 0;
					$orderIntegral[$goods['store_id']] += $goodsMaxPoints;

					$maxPoints += $goodsMaxPoints;
				}
			}
		}

		// 获取用户拥有的积分
		if (($integral = parent::find()->select('amount')->where(['userid' => Yii::$app->user->id])->one())) {
			$userIntegral = $integral->amount;
		} else {
			$userIntegral = 0;
		}

		$maxPoints = round($maxPoints, 2);
		$getPoints = round($getPoints, 2);

		if ($maxPoints > $userIntegral) {
			foreach ($orderIntegral as $key => $value) {
				$orderIntegral[$key] = round($value * ($userIntegral / $maxPoints), 2);
			}
			$maxPoints = $userIntegral;
		}

		$result = [
			'maxPoints' => $maxPoints,
			'userIntegral' => $userIntegral,
			'getPoints' => $getPoints,
			'rate' => $rate,
			'orderIntegral' => array('totalPoints' => $maxPoints, 'items' => $orderIntegral)
		];

		return $result;
	}
}
