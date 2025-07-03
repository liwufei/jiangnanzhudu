<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace common\business\ordertypes;

use yii;

use common\models\GoodsModel;
use common\models\CartModel;

use common\library\Page;
use common\library\Business;

use common\business\BaseOrder;
use common\library\Plugin;

/**
 * @Id NormalOrder.php 2018.7.12 $
 * @author mosir
 */

class NormalOrder extends BaseOrder
{
	protected $otype = 'normal';

	/**
	 * 获取订单附加数据
	 */
	public function formData($goods_info = [])
	{
		// 获取店铺可用优惠券
		$goods_info = $this->getStoreCouponList($goods_info);

		// 获取订单所有营销策略
		$client = Plugin::getInstance('promote')->build();
		foreach (array_keys($goods_info['orderList']) as $store_id) {
			$client->getOrderAllPromoteInfo($store_id, $goods_info);
		}

		return $goods_info;
	}

	/** 
	 * 提交生成的订单
	 * @return array $result 包含店铺和订单号的数组
	 */
	public function submit($data = [])
	{
		extract($data);

		// 处理订单基本信息
		if (!($base_info = parent::handleOrderInfo($goods_info))) {
			return false;
		}

		// 处理订单收货人信息
		$delivery_type = Business::getInstance('delivery')->build($this->post->delivery->type, $this->post);
		if (($consignee_info = $delivery_type->handleConsigneeInfo($goods_info)) === false) {
			$this->errors = $delivery_type->errors;
			return false;
		}

		// 处理配送费及配送能力
		if (($delivery_info = $delivery_type->handleDeliveryInfo($goods_info, $consignee_info)) === false) {
			$this->errors = $delivery_type->errors;
			return false;
		}

		// 获取订单折扣信息
		if (($discount_info = $this->getAllDiscountByPost($goods_info)) === false) {
			return false;
		}

		// 检验折扣信息和订单总价的合理性
		if (!isset($goods_info['integralExchange']['rate'])) $goods_info['integralExchange']['rate'] = 0;
		if (!$this->checkAllDiscountForOrderAmount($base_info, $discount_info, $delivery_info, $goods_info['integralExchange']['rate'])) {
			return false;
		}

		// 至此说明订单的信息都是可靠的，可以开始入库了
		if (($result = parent::insertOrderData($base_info, $goods_info, $consignee_info, $delivery_info)) === false) {
			return false;
		}

		// 更新优惠券的使用次数
		if (isset($discount_info['coupon'])) {
			parent::updateCouponRemainTimes($result, $discount_info['coupon']);
		}

		// 保存每个订单使用的积分数额（处理合并付款订单的积分分摊）
		if (isset($discount_info['integral'])) {
			parent::saveIntegralInfoByOrder($result, $discount_info['integral']);
		}

		return $result;
	}

	/**
	 * 获取购物车中的商品数据
	 * 用来计算订单可使用的最大积分值等
	 */
	public function getOrderGoodsList()
	{
		$query = CartModel::find()->alias('c')
			->select('gs.spec_id,gs.spec_1,gs.spec_2,gs.stock,gs.weight,c.id,c.userid,c.store_id,c.goods_id,c.goods_name,c.goods_image,c.gtype,c.specification,c.price,c.quantity,c.product_id,c.selected')
			->joinWith('goodsSpec gs', false)
			->where(['userid' => Yii::$app->user->id, 'selected' => 1])->indexBy('product_id');
		if ($this->post->store_id) {
			$query->andWhere(['store_id' => $this->post->store_id]);
		}

		$result = $query->asArray()->all();
		foreach ($result as $key => $value) {
			$result[$key]['goods_image'] = Page::urlFormat($value['goods_image'], Yii::$app->params['default_goods_image']);
			$result[$key]['gtype'] = GoodsModel::find()->select('type')->where(['goods_id' => $value['goods_id']])->scalar();

			// 如果规格已经不存在，则删除购物车该规格商品记录
			if (!$value['spec_id']) {
				unset($result[$key]);
				CartModel::deleteAll(['id' => $value['id']]);
			}
		}

		return array($result, null);
	}
}
