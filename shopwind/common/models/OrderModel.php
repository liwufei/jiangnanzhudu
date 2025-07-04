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
use yii\helpers\ArrayHelper;

use common\models\OrderGoodsModel;
use common\models\OrderExtmModel;
use common\models\GoodsSpecModel;
use common\models\GoodsModel;
use common\models\RegionModel;
use common\models\CodModel;

use common\library\Timezone;
use common\library\Language;
use common\plugins\promote\integral\Integral;

/**
 * @Id OrderModel.php 2018.4.1 $
 * @author mosir
 */

class OrderModel extends ActiveRecord
{
	/**
	 * @inheritdoc
	 */
	public static function tableName()
	{
		return '{{%order}}';
	}

	// 关联表
	public function getOrderExtm()
	{
		return parent::hasOne(OrderExtmModel::className(), ['order_id' => 'order_id']);
	}
	// 关联表
	public function getOrderGoods()
	{
		return parent::hasMany(OrderGoodsModel::className(), ['order_id' => 'order_id']);
	}
	// 关联表
	public function getOrderLog()
	{
		return parent::hasMany(OrderLogModel::className(), ['order_id' => 'order_id']);
	}
	// 关联表
	public function getStore()
	{
		return parent::hasOne(StoreModel::className(), ['store_id' => 'seller_id']);
	}
	// 关联表
	public function getOrderBuyerInfo()
	{
		return parent::hasOne(UserModel::className(), ['userid' => 'buyer_id']);
	}
	// 关联表
	public function getOrderSellerInfo()
	{
		return parent::hasOne(UserModel::className(), ['userid' => 'seller_id']);
	}
	// 关联表
	public function getDepositTrade()
	{
		return parent::hasOne(DepositTradeModel::className(), ['bizOrderId' => 'order_sn', 'buyer_id' => 'buyer_id']);
	}

	public static function genOrderSn($ext = '')
	{
		// 选择一个随机的方案
		mt_srand((float) microtime() * 1000000);

		$order_sn = Timezone::gmtime() . str_pad(mt_rand(1, 99), 2, '0', STR_PAD_LEFT) . $ext;

		if (!parent::find()->select('order_sn')->where(['order_sn' => $order_sn])->exists()) {
			return $order_sn;
		}
		// 如果有重复的，则重新生成
		return self::genOrderSn();
	}

	/**
	 *    修改订单中商品的库存，可以是减少也可以是加回
	 *    @param     string $action     [+:加回， -:减少]
	 *    @param     int    $order_id   订单ID
	 *    @return    bool
	 */
	public static function changeStock($action = '', $order_id = 0)
	{
		if (in_array($action, array('+', '-')) && $order_id) {
			// 获取订单商品列表
			$ordergoods = OrderGoodsModel::find()->select('goods_id,spec_id,quantity')->where(['order_id' => $order_id])->asArray()->all();
			if ($ordergoods) {
				// 依次改变库存
				foreach ($ordergoods as $goods) {
					$change = ($action == '-') ? -$goods['quantity'] : $goods['quantity'];
					GoodsSpecModel::updateAllCounters(['stock' => $change], ['spec_id' => $goods['spec_id']]);
					GoodsModel::clearCache($goods['goods_id']);
				}
			}
		}
		return true;
	}

	/* 获取订单的支付标题 */
	public static function getSubjectOfPay($order_id = 0)
	{
		$query = OrderGoodsModel::find()->select('goods_name')->where(['order_id' => $order_id])->orderBy(['id' => SORT_ASC]);

		$subject = $query->one()->goods_name;
		if ($query->count() > 1) {
			$subject .= Language::get('and_more');
		}
		return addslashes($subject);
	}

	/**
	 * 获取每笔订单，订单总额，商品总额等各项实际的金额（考虑优惠，积分抵扣，运费等情况） 
	 * 咱不考虑调价的情况，如果后续有需要，则再次拓展
	 */
	public static function getRealAmount($order_id = 0)
	{
		$realGoodsAmount = $realFreight = $realOrderAmount = 0;

		// 如商品90 运费10  优惠-40 积分-10 付款50
		$order = parent::find()->select('goods_amount,discount,order_amount')->where(['order_id' => $order_id])->one();

		if ($order) {

			if ($freight = OrderExtmModel::find()->select('freight')->where(['order_id' => $order_id])->scalar()) {
				$realFreight = $freight;
			}

			//$integral = IntegralLogModel::find()->select('money')->where(['order_id' => $order_id, 'type' => 'buying_pay_integral'])->one();
			//$realGoodsAmount = $realOrderAmount + $order->discount + ($integral ? abs($integral->money) : 0);
			$realGoodsAmount = $order->goods_amount;
			$realOrderAmount = $order->order_amount;
		}

		return array($realGoodsAmount, $realFreight, $realOrderAmount);
	}

	/**
	 * 合并付款情况下，检查每个订单是否都支持货到付款 
	 * 必须所有的合并中的订单都支持货到付款，才显示货到付款的支付方式
	 */
	public static function checkMergePayCodPaymentEnable($orderList = [])
	{
		$cod_payments   = [];
		foreach ($orderList as $order_id => $order_info) {
			if (!($payment = self::checkCodPaymentEnable($order_info))) {
				return [];
			}
			$cod_payments[$order_id] = ArrayHelper::toArray($payment);
		}

		return $cod_payments;
	}

	/* 检查单个订单是否支持货到付款 */
	public static function checkCodPaymentEnable($order_info = [])
	{
		// 拼团订单不支持货到付款
		if ($order_info['otype'] == 'teambuy') {
			return false;
		}

		$payment = CodModel::checkAndGetInfo(intval($order_info['seller_id']));
		if (!$payment || empty($payment->regions)) {
			return false;
		}

		$regions = unserialize($payment->regions);
		if (empty($regions) || !is_array($regions)) {
			return false;
		}

		// 取得支持货到付款地区的所有下级地区
		$all = array();
		foreach ($regions as $id) {
			$all = array_merge($all, (array)RegionModel::getDescendantIds($id));
		}

		// 去掉重复值
		$all = array_unique($all);

		// 找出收货人地址信息
		$consignee = OrderExtmModel::find()->select('region_id')->where(['order_id' => $order_info['order_id']])->one();

		// 查看订单中指定的地区是否在可货到付款的地区列表中，如果在，则显示货到付款的付款方式
		if (!in_array($consignee->region_id, $all)) {
			return false;
		}
		return $payment;
	}

	/**
	 * 更新每笔订单的支付方式
	 * @param $payments 有可能是payment_info 有可能是 cod_payments
	 */
	public static function updateOrderPayment(&$orderInfo = [], $payments = [], $isCod = true)
	{
		foreach ($orderInfo['orderList'] as $key => $order_info) {
			$payment_info = $payments;

			//（合并付款下）取不同的卖家的货到付款方式
			if ($isCod === true) {
				$payment_info = $payments[$order_info['order_id']];
			}

			// 保存支付方式
			$edit_data = array(
				'payment_code'  =>  $payment_info['code'],
				'payment_name'  =>  $payment_info['name'],
			);

			// 如果支付方式变更了
			if ($order_info['payment_code'] != $payment_info['code']) {
				$edit_data['pay_alter'] = 1;
			} else $edit_data['pay_alter'] = 0;

			$model = parent::find()->select('order_id')->where(['order_id' => $order_info['order_id']])->one();
			foreach ($edit_data as $k => $v) {
				$model->$k = $v;
			}
			if ($model->save() === false) {
				return false;
			}

			// 更新引用数值
			$orderInfo['orderList'][$key] = array_merge($orderInfo['orderList'][$key], $edit_data);
		}

		return true;
	}
}
