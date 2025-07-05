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

use common\models\RegionModel;
use common\models\DeliveryTemplateModel;
use common\models\StoreModel;

use common\library\Plugin;
use common\library\Language;

/**
 * @Id LocalityDelivery.php 2023.3.12 $
 * @author mosir
 */

class LocalityDelivery extends ExpressDelivery
{
	protected $dtype = 'locality';

	/**
	 * 验证配送方式是否合法
	 */
	public function validDeliveryInfo($list = [])
	{
		if (!parent::validDeliveryInfo($list)) {
			return false;
		}

		// 验证起送金额
		foreach ($list as $store_id => $order) {
			$query = DeliveryTemplateModel::find()->select('basemoney')->where(['store_id' => $store_id, 'type' => $this->dtype])
				->orderBy(['enabled' => SORT_DESC, 'id' => SORT_DESC])->one();
			if (!$query || (floatval($query->basemoney) > floatval($order['amount']))) {
				$this->errors = Language::get('订单未满足起送金额');
				return false;
			}
		}

		// 验证是否在配送时间【营业时间】
		if (StoreModel::getBusstate(null, $store_id) === 0) {
			$this->errors = sprintf('商家【%s】已打烊，无法配送', $order['store_name']);
			return false;
		}

		return true;
	}

	/**
	 * 获取送货时间
	 */
	public function getDeliveryTime($store_id = 0)
	{
		$period = '';
		if (isset($this->post->delivery->period->$store_id)) {// 对象指变量，待改善
			if ($value = $this->post->delivery->period->$store_id) {
				$period = $value->day . ' ' .  $value->hour;
			}
		}

		return $period;
	}

	/**
	 * 非商户自配情况下，查询平台(商户)是否有足够的余额发单
	 * @param $freight 发单所需运费
	 */
	public function validMerMoney($store_id, $freight)
	{
		$code = StoreModel::find()->select('deliveryCode')->where(['store_id' => $store_id])->scalar();
		$client = Plugin::getInstance('locality')->build($code);
		if (!$client || !$client->isInstall()) {
			$this->errors = Language::get('同城配送插件未配置');
			return false;
		}

		$balance = $client->queryMoney($store_id);
		if ($freight > $balance) {
			$this->errors = Language::get('订单创建失败：商户侧余额不足无法派单');
			return false;
		}
		return true;
	}

	/**
	 * 验证收货地址是否在配送范围内[1:验证区域 2:验证距离]
	 * @param $destid 目的地ID
	 * @param $longitude 目的地经度
	 * @param $latitude 目的地纬度
	 */
	public function validDeliveryArea($list = [], $destid = 0, $latitude = 0, $longitude = 0)
	{
		foreach ($list as $store_id) {
			$delivery = DeliveryTemplateModel::find()->where(['store_id' => $store_id, 'type' => $this->dtype])
				->orderBy(['enabled' => SORT_DESC, 'id' => SORT_DESC])->one();

			if ($rules = unserialize($delivery->rules)) {
				foreach ($rules['arrived'] as $area) {
					if ($area['dests']) {
						$temp = $area['dests'];
						arsort($temp); // 级数多的在前
						foreach (array_values($temp) as $item) {
							$lastid = end($item);
							if ($lastid && ($all = RegionModel::getDescendantIds($lastid))) {

								// 验证配送区域
								if (in_array($destid, $all)) {

									// 验证配送距离
									if ($this->validDistance($store_id, $latitude, $longitude)) {
										return true;
									}
								}
							}
						}
					}
				}
			}
		}

		return false;
	}

	/**
	 * 验证配送距离
	 */
	private function validDistance($store_id = 0, $latitude = 0, $longitude = 0)
	{
		$model = StoreModel::find()->select('latitude,longitude,radius')->where(['store_id' => $store_id]);
		if (!($query = $model->one()) || !$query->latitude || !$query->longitude) {
			$this->errors = Language::get('创建订单失败：同城配送门店未配置');
			return false;
		}

		$fields = "(6371 * acos(cos(radians($latitude)) * cos(radians(latitude)) * cos(radians(longitude) - radians($longitude)) + sin(radians($latitude)) * sin(radians(latitude))))*1000 as distance";
		$record = $model->addSelect($fields)->asArray()->one();
		if (!$record || (floatval($record['distance']) > floatval($record['radius']) * 1000)) {
			$this->errors = Language::get('创建订单失败：超出配送范围');
			return false;
		}

		return true;
	}

	/**
	 * 下单前查询运费【根据同城配送插件】
	 */
	public function queryFee()
	{
		// 第三方骑手或商家自配
		$code = StoreModel::find()->select('deliveryCode')->where(['store_id' => $this->post->store_id])->scalar();
		if (!$code || !($client = Plugin::getInstance('locality')->build($code)) || !$client->isInstall()) {
			$this->errors = Language::get('同城配送插件未配置');
			return false;
		}

		if (!($freight = $client->queryFee($this->post))) {
			$this->errors = Language::get('运费查询异常：') . $client->errors;
			return false;
		}

		return ['freight' => round($freight, 2), 'name' => $client->getInfo()['name'], 'code' => $code];
	}
}
