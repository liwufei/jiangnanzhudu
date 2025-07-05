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
use common\models\IntegralModel;
use common\models\IntegralSettingModel;

use common\library\Language;
use common\library\Business;
use common\library\Page;

/**
 * @Id OrderForm.php 2018.7.12 $
 * @author mosir
 */
class OrderForm extends Model
{
	// order type, item data from where, eg: normal|mealbuy|teambuy ...
	public $otype 	= 'normal';

	// goods type, eg: material
	//public $gtype 	= 'material';

	public $errors 	= null;

	/**
	 * 从购物车/搭配购/拼团/实例中取商品
	 */
	public function getGoodsInfo($post = null)
	{
		if (isset($post->specs) && is_string($post->specs)) {
			$post->specs = json_decode($post->specs);
		}

		// 购物车订单
		if ($this->otype == 'normal') {
			$post->store_id = isset($post->store_id) ? intval($post->store_id) : 0; // 如果传值只结算一个店铺
		}

		list($goodsList, $extra) = Business::getInstance('order')->build($this->otype, $post)->getOrderGoodsList();
		if (empty($goodsList)) {
			$this->errors = Language::get('goods_empty');
			return false;
		}

		// 按店铺归类商品，顺带验证库存够不够
		$storeGoodsList = [];
		foreach ($goodsList as $key => $goods) {
			$storeGoodsList[$goods['store_id']][$key] = $goods;
		}

		// 库存够不够
		if (($errorMsg = $this->checkBeyondStock($goodsList)) !== true) {
			$this->errors = $errorMsg;
			return false;
		}

		$result = array_merge($this->formatData($storeGoodsList, $extra), ['otype' => $this->otype]);

		// 是否允许使用积分抵扣
		if (IntegralSettingModel::getSysSetting('enabled')) {
			$result['integralExchange']	= IntegralModel::getIntegralByOrders($goodsList);
		}
		return $result;
	}

	private function formatData(array $storeGoodsList, $extra = [])
	{
		if ($this->otype == 'mealbuy') {
			return $this->formatDataOfMeal($storeGoodsList, $extra);
		}
		return $this->formatDataOfNormal($storeGoodsList);
	}

	/**
	 * 购物车订单/拼团订单数据
	 */
	private function formatDataOfNormal(array $storeGoodsList, $cart = [])
	{
		$result = ['amount' => 0, 'gtype' => []];

		foreach ($storeGoodsList as $store_id => $items) {
			$storeAmount = $storeQuantity = $storeWeight = 0;
			foreach ($items as $key => $goods) {
				$items[$key]['subtotal'] = sprintf('%.2f', $goods['quantity'] * $goods['price']);
				$storeAmount += floatval($items[$key]['subtotal']);
				$storeQuantity += intval($goods['quantity']);
				$storeWeight += floatval($goods['weight']) * intval($goods['quantity']);
				$result['gtype'][] = $goods['gtype'];
			}
			$store = StoreModel::find()->select('store_id, store_name, store_logo, sgrade as sgrade_id,bustime')->where(['store_id' => $store_id])->asArray()->one();
			if ($store) $store['store_logo'] = Page::urlFormat($store['store_logo'], Yii::$app->params['default_store_logo']);
			$result['orderList'][$store_id] = array_merge(
				[
					'items' => $items,
					'amount' => round($storeAmount, 2),
					'quantity' => $storeQuantity,
					'weight' => round($storeWeight, 2),
					'busopen' => StoreModel::getBusstate($store['bustime'])
				],
				$store ? $store : []
			);

			// 记录商品类型种类(去重)
			$result['gtype'] = array_unique($result['gtype']);

			// 统计各个订单的总额（商品的原价之和，并非订单最终的优惠价格，此值仅作为后续计算各个订单所占总合并订单金额的分摊比例用）
			$result['amount'] += $storeAmount;
		}
		return $result;
	}

	/**
	 * 搭配购订单数据
	 */
	private function formatDataOfMeal(array $storeGoodsList, $meal = [])
	{
		$result = ['amount' => 0, 'gtype' => []];

		foreach ($storeGoodsList as $store_id => $items) {
			$storeAmount = $storeQuantity = $storeWeight = 0;
			foreach ($items as $key => $goods) {
				$items[$key]['subtotal'] = sprintf('%.2f', $goods['quantity'] * $goods['price']);
				$storeAmount += floatval($items[$key]['subtotal']);
				$storeQuantity += intval($goods['quantity']);
				$storeWeight += floatval($goods['weight']) * intval($goods['quantity']);
				$result['gtype'][] = $goods['gtype'];
			}
			$store = StoreModel::find()->select('store_id, store_name, store_logo, sgrade as sgrade_id, bustime, qq')->where(['store_id' => $store_id])->asArray()->one();
			if ($store) $store['store_logo'] = Page::urlFormat($store['store_logo'], Yii::$app->params['default_store_logo']);
			$result['orderList'][$store_id] = array_merge(
				[
					'items' => $items,
					'oldAmount' => $storeAmount,
					'amount' => $meal['price'],
					'quantity' => $storeQuantity,
					'weight' => round($storeWeight, 2),
					'busopen' => StoreModel::getBusstate($store['bustime'])
				],
				$store ? $store : []
			);

			// 记录商品类型种类(去重)
			$result['gtype'] = array_values(array_unique($result['gtype']));

			// 统计各个订单的总额（商品的原价之和，并非订单最终的优惠价格，此值仅作为后续计算各个订单所占总合并订单金额的分摊比例用）
			$result['amount'] += $meal['price'];
		}

		return $result;
	}

	/**
	 * 验证库存够不够 
	 */
	private function checkBeyondStock(array $goodsList)
	{
		$message = '';
		foreach ($goodsList as $key => $goods) {
			if ($goods['stock'] < $goods['quantity']) {
				$message = '商品【' . $goods['goods_name'] . '】' . $goods['specification'] . '，' . Language::get('stock') . '仅剩' . $goods['stock'] . '件';
				return $message;
			}
		}
		return true;
	}
}
