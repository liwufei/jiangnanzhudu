<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace common\business\deliverytypes;


use yii;
use yii\helpers\ArrayHelper;

use common\models\DeliveryTemplateModel;
use common\library\Language;
use common\business\BaseDelivery;

/**
 * @Id ExpressDelivery.php 2023.3.12 $
 * @author mosir
 */

class ExpressDelivery extends BaseDelivery
{
	protected $dtype = 'express';

	/**
	 * 处理收货人信息
	 */
	public function handleConsigneeInfo($goods_info = [])
	{
		// 当结算的商品为实物商品时，需要验证收货地址
		if (implode(',', $goods_info['gtype']) == 'material') {

			// 验证收货人信息是否完整
			if (!($consignee_info = $this->validConsigneeInfo())) {
				return false;
			}

			return $consignee_info;
		}

		return [];
	}

	/**
	 * 处理配送信息
	 */
	public function handleDeliveryInfo($goods_info = [], $consignee_info = [])
	{
		if (!$consignee_info) {
			return [];
		}

		// 验证配送方式是否合理
		if (!$this->validDeliveryInfo($goods_info['orderList'])) {
			return false;
		}

		// 验证收货地址是否在配送范围
		if (!$this->validDeliveryArea(array_keys($goods_info['orderList']), $consignee_info['region_id'], $consignee_info['latitude'], $consignee_info['longitude'])) {
			if (!$this->errors) $this->errors = Language::get('no_delivery');
			return false;
		}

		// 获取运费【可考虑重新获取】
		$freight = 0;
		$result = [];
		foreach ($this->post->delivery->list as $store_id => $delivery) {
			$freight += floatval($delivery->freight);
			$result[$store_id] = [
				'freight'  => $delivery->freight,
				'deliveryName' => $delivery->name,
				'deliverTime' => $this->getDeliveryTime($store_id)
			];

			// 如果是第三方平台骑手发单，则需验证商户余额是否可发单
			if (!$this->validMerMoney($store_id, $freight)) {
				return false;
			}
		}

		return $result;
	}

	/**
	 * 验证收货人信息是否合法
	 */
	public function validConsigneeInfo()
	{
		if (empty($this->post->consignee)) {
			$this->errors = Language::get('consignee_empty');
			return false;
		}

		if (empty($this->post->phone_mob)) {
			$this->errors = Language::get('phone_mob_required');
			return false;
		}

		if (!$this->post->region_id || !$this->post->address) {
			$this->errors = Language::get('region_empty');
			return false;
		}

		return ArrayHelper::toArray($this->post);
	}

	/**
	 * 验证配送方式是否合法
	 */
	public function validDeliveryInfo($list = [])
	{
		if (!isset($this->post->delivery) || !is_object($this->post->delivery)) {
			$this->errors = Language::get('shipping_required');
			return false;
		}

		if (!isset($this->post->delivery->list) || !is_object($this->post->delivery->list)) {
			$this->errors = Language::get('shipping_required');
			return false;
		}

		foreach ($this->post->delivery->list as $store_id => $delivery) {
			if (!isset($delivery->freight)) { // 不支持配送 & 运费查询异常
				$this->errors = isset($delivery->error) ? $delivery->error :  Language::get('no_delivery');
				return false;
			}
		}

		$allows = DeliveryTemplateModel::getTypes();
		if (!in_array($this->post->delivery->type, $allows)) {
			$this->errors = Language::get('shipping_required');
			return false;
		}

		return true;
	}

	/**
	 * 查询运费【根据运费模板】
	 */
	public function queryFee()
	{
		$result = DeliveryTemplateModel::queryFee($this->post->store_id, $this->post->region_id, $this->post->weight, $this->dtype);
		return $result ? $result : false;
	}
}
