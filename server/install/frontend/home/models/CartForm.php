<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace frontend\home\models;

use Yii;
use yii\base\Model;

use common\models\StoreModel;
use common\models\GoodsSpecModel;
use common\models\CouponModel;

use common\library\Language;
use common\library\Timezone;
use common\library\Plugin;
use common\library\Page;

/**
 * @Id CartForm.php 2018.7.10 $
 * @author mosir
 */
class CartForm extends Model
{
	public $errors = null;

	/**
	 *  used in PC/H5/API
	 */
	public function formData($list = [])
	{
		$result = [];
		if ($list && $list['items']) {
			foreach ($list['items'] as $goods) {
				$goods['subtotal'] = sprintf('%.2f', round($goods['price'] * $goods['quantity'], 2));
				$goods['goods_image'] = Page::urlFormat($goods['goods_image']);
				$result['list'][$goods['store_id']]['items'][$goods['product_id']] = $goods;

				if ($store = StoreModel::find()->select('store_id,store_name,store_logo')->where(['store_id' => $goods['store_id']])->asArray()->one()) {
					$store['store_logo'] = Page::urlFormat($store['store_logo']);
					$result['list'][$goods['store_id']] = array_merge($result['list'][$goods['store_id']], $store);
				}

				// 店铺金额统计
				if (!isset($result['list'][$goods['store_id']]['total'])) $result['list'][$goods['store_id']]['total'] = 0;
				$result['list'][$goods['store_id']]['total'] += $goods['subtotal'];
			}
			$result['amount'] = sprintf('%.2f', $list ? $list['amount'] : 0);
		}
		return $result;
	}

	/**
	 * 店铺满优惠
	 */
	public function getCartFullprefer($list = [])
	{
		if (empty($list)) return null;

		$client = Plugin::getInstance('promote')->build('fullprefer');
		foreach ($list as $store_id => $cart) {
			if ($client->checkAvailable($store_id, false)) {
				$promote = $client->getSetting($store_id);
				if (isset($promote['status']) && $promote['status']) {
					if ($promote['rules']['type'] == 'discount') {
						$list[$store_id]['storeFullPreferInfo'] = array(
							'text' => sprintf('购满%s元可享%s折', $promote['rules']['amount'], $promote['rules']['discount']),
							'amount' => $promote['rules']['amount'],
							'prefer' => ['label' => '满折', 'type' => 'discount', 'value' => sprintf('%.2f', $promote['rules']['discount'])],
						);
					} else {
						$list[$store_id]['storeFullPreferInfo'] = array(
							'text' => sprintf('购满%s元可减%s元', $promote['rules']['amount'], $promote['rules']['decrease']),
							'amount' => $promote['rules']['amount'],
							'prefer' => ['label' => '满减', 'type' => 'decrease', 'value' => sprintf('%.2f', $promote['rules']['decrease'])],
						);
					}
				}
			}
		}
		return $list;
	}

	/**
	 * 是否显示领取优惠券按钮
	 */
	public function getCouponEnableReceive($list = [])
	{
		if ($list) {
			foreach ($list as $store_id => $cart) {
				if (CouponModel::find()->where(['received' => 1, 'available' => 1, 'store_id' => $store_id])->andWhere(['>', 'end_time', Timezone::gmtime()])->andWhere(['or', ['total' => 0], ['and', ['>', 'total', 0], ['>', 'surplus', 0]]])->exists()) {
					$list[$store_id]['couponReceive'] = 1;
				}
			}
		}
		return $list;
	}

	public function valid($post, $extra = [])
	{
		if (!$post->spec_id) {
			$this->errors = Language::get('select_specs');
			return false;
		}
		if (!$post->quantity) {
			$this->errors = Language::get('quantity_invalid');
			return false;
		}

		// 是否有商品
		if (!($specInfo = GoodsSpecModel::find()->alias('gs')->select('g.store_id, g.goods_id, g.goods_name, g.spec_name_1, g.spec_name_2, g.default_image, gs.spec_id, gs.spec_1, gs.spec_2, gs.stock, gs.price,gs.image')->joinWith('goods g', false)->where(['spec_id' => $post->spec_id])->asArray()->one())) {
			$this->errors = Language::get('no_such_goods');
			return false;
		}

		// 如果是自己店铺的商品，则不能购买
		if ($specInfo['store_id'] == Yii::$app->user->id) {
			$this->errors = Language::get('can_not_buy_yourself');
			return false;
		}
		if ($specInfo['stock'] < $post->quantity) {
			$this->errors = Language::get('no_enough_goods');
			return false;
		}

		// 读取促销价格
		$client = Plugin::getInstance('promote')->build();
		if (($result = $client->getProInfo($specInfo['goods_id'], $post->spec_id, $extra)) !== false) {
			if ($result['price'] != $specInfo['price']) {
				$specInfo['price'] = $result['price'];
			}
		}

		return array_merge($specInfo, ['quantity' => $post->quantity]);
	}

	/**
	 * 返回购物车数据
	 */
	public function getCart()
	{
		return Yii::$app->cart->find();
	}
}
