<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace common\plugins;

use yii;
use yii\helpers\Url;
use yii\helpers\ArrayHelper;

use common\models\PromoteSettingModel;
use common\models\PromoteItemModel;
use common\models\AppmarketModel;
use common\models\OrderModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Plugin;
use common\library\Timezone;
use common\library\Def;

/**
 * @Id BasePromote.php 2018.6.4 $
 * @author mosir
 */

class BasePromote extends BasePlugin
{
	/**
	 * 营销插件系列
	 * @var string $instance
	 */
	protected $instance = 'promote';

	/**
	 * 检查营销工具是否可用
	 * @var string $message 是否返回验证未通过信息
	 * @var boolean $force 是否验证商家启用状态
	 */
	public function checkAvailable($store_id = 0, $message = true, $force = true)
	{
		$model = new AppmarketModel();

		$result = $model->checkAvailable($this->code, $store_id, $force);
		if (!$result) {
			return $message ? $model->errors : false;
		}

		return true;
	}

	/**
	 * 获取全局优惠策略
	 */
	public function getSetting($store_id = 0, $params = [], $format = true)
	{
		return PromoteSettingModel::getInfo($this->code, $store_id, $params, $format);
	}

	/**
	 * 获取商品级优惠策略
	 */
	public function getItemInfo($store_id = 0, $params = [], $format = true)
	{
		return PromoteItemModel::getInfo($this->code, $store_id, $params, $format);
	}

	/**
	 * 获取商品优惠价格【包括秒杀等】
	 * 当有多个优惠价格时，按照优先级获取
	 */
	public function getProInfo($goods_id = 0, $spec_id = 0, $force = true, $rules = [])
	{
		// 优先级顺序
		if (empty($rules)) $rules = ['limitbuy'];

		foreach ($rules as $code) {
			$result = Plugin::getInstance('promote')->build($code)->getItemProInfo($goods_id, $spec_id, $force);
			if ($result) break;
		}

		return $result ? $result : false;
	}

	/**
	 * 获取订单首单立减
	 * 如果需要验证订单金额的可以在此拓展 
	 */
	public function getOrderExclusiveInfo($store_id = 0, $order_info = [])
	{
		$result = [];

		// 验证是否为新客[下过单且状态不是取消的就算]
		if (!OrderModel::find()->where([
			'and',
			['buyer_id' => Yii::$app->user->id, 'seller_id' => $store_id],
			['!=', 'status', Def::ORDER_CANCELED]
		])->exists()) {
			$client = Plugin::getInstance('promote')->build('exclusive');
			if ($client->checkAvailable($store_id, false)) {
				$promote = $client->getSetting($store_id);
				if (isset($promote['status']) && $promote['status']) {
					$result = ['price' => $promote['rules']['decrease']];
				}
			}
		}

		return $result;
	}


	/**
	 * 获取订单满优惠
	 */
	public function getOrderFullPreferInfo($store_id = 0, $order_info = [])
	{
		$result = [];

		$client = Plugin::getInstance('promote')->build('fullprefer');
		if ($client->checkAvailable($store_id, false)) {
			$promote = $client->getSetting($store_id);
			if (isset($promote['status']) && $promote['status']) {
				$amount = floatval($promote['rules']['amount']);
				if ($amount <= $order_info['amount']) { // 未含运费
					$result = ['amount' => $amount, 'type' => $promote['rules']['type']];
					if ($promote['rules']['type'] == 'discount') {
						$decrease = round($order_info['amount'] * (10 - $promote['rules']['discount']) / 10, 2);
						$result = array_merge($result, [
							'text' => sprintf(Language::get('order_for_fullperfer_discount'), $amount, $promote['rules']['discount']),
							'price' => $decrease,
							'discount' => $promote['rules']['discount']
						]);
					} elseif ($promote['rules']['type'] == 'decrease') {
						$decrease = $promote['rules']['decrease'];
						$result = array_merge($result, [
							'text' => sprintf(Language::get('order_for_fullperfer_decrease'), $amount, $decrease),
							'price' => $decrease
						]);
					}
				}
			}
		}
		return $result;
	}

	/**
	 * 获取订单满包邮设置 
	 */
	public function getOrderFullfree($store_id = 0, $goods_info = [])
	{
		$result = [];

		$client = Plugin::getInstance('promote')->build('fullfree');
		if ($client->checkAvailable($store_id, false)) {
			$promote = $client->getSetting($store_id);
			if (isset($promote['status']) && $promote['status']) {
				if (($goods_info['amount'] >= $promote['rules']['amount']) && ($promote['rules']['amount'] > 0)) {
					$result = array('title' => sprintf(Language::get('free_amount_ship_title'), $promote['rules']['amount']));
				} elseif (($goods_info['quantity'] >= $promote['rules']['quantity']) && ($promote['rules']['quantity'] > 0)) {
					$result = array('title' => sprintf(Language::get('free_acount_ship_title'), $promote['rules']['quantity']));
				}
			}
		}
		return $result ? $result : false;
	}

	/**
	 * 获取订单提交页面显示该订单所有营销工具信息（兼容多店铺合并付款） 
	 */
	public function getOrderAllPromoteInfo($store_id = 0, &$goods_info = [])
	{
		$order_info = $goods_info['orderList'][$store_id];

		// 获取首单立减
		$goods_info['orderList'][$store_id]['exclusive'] = $this->getOrderExclusiveInfo($store_id, $order_info);

		// 获取搭配购优惠 
		if ($goods_info['otype'] == 'mealbuy') {
			$goods_info['orderList'][$store_id]['mealprefer'] = [
				'text' => Language::get('submit_order_reduce'),
				'price' => $order_info['oldAmount'] - $order_info['amount']
			];
		}

		// 判断商品金额（不含运费）是否满足满优惠设置
		$goods_info['orderList'][$store_id]['fullprefer'] = $this->getOrderFullPreferInfo($store_id, $order_info);
	}

	/**
	 * 保存卖家设置的某个商品对应的营销工具信息 
	 */
	public function savePromoteItem($store_id = 0, $post = [])
	{
		$post = Basewind::trimAll($post, true);
		if (!isset($post->goods_id) || empty($post->goods_id)) {
			return false;
		}

		if (isset($post->config) && !empty($post->config)) {
			$config = serialize(ArrayHelper::toArray($post->config));

			if ($this->code == 'exclusive') {
				if (isset($post->config->discount) && $post->config->discount) $post->config->discount = floor(abs($post->config->discount) * 100) / 100;
				if (isset($post->config->decrease) && $post->config->decrease) $post->config->decrease = floor(abs($post->config->decrease) * 100) / 100;

				if (!$post->config->discount) unset($post->config->discount);
				if (isset($post->config->discount) && $post->config->discount) unset($post->config->decrease);

				if ((!isset($post->config->discount) || !$post->config->discount) && (!isset($post->config->decrease) || !$post->config->decrease)) {
					$config = '';
				}
			}
			$post->config = $config;
		}

		if (($item = PromoteItemModel::getInfo($this->code, $store_id, ['goods_id' => $post->goods_id], false))) {
			return PromoteItemModel::updateAll($post, ['piid' => $item['piid']]);
		} else {
			$model = new PromoteItemModel();
			$model->add_time = Timezone::gmtime();
			$model->appid = $this->code;
			$model->store_id = $store_id;
			foreach ($post as $key => $value) {
				$model->$key = $value;
			}
			return $model->save();
		}
	}

	public function getRoute()
	{
		return [
			'index' => 1, // 排序
			'text'	=> Language::get('plugin_promote'),
			'url'	=> Url::toRoute('promote/index'),
			'priv'  => ['key' => 'promote|all', 'depends' => 'appmarket|all', 'label' => Language::get('plugin_promote')]
		];
	}
}
