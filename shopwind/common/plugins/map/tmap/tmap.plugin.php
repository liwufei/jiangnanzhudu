<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace common\plugins\map\tmap;

use yii;

use common\library\Basewind;
use common\plugins\BaseMap;

/**
 * @Id tmap.plugin.php 2018.9.5 $
 * @author mosir
 */

class Tmap extends BaseMap
{
	/**
	 * 网关地址
	 * @var string $gateway
	 */
	protected $gateway = 'https://apis.map.qq.com/ws/geocoder/v1/';

	/**
	 * 插件实例
	 * @var string $code
	 */
	protected $code = 'tmap';

	/**
	 * 通过经纬度获取省市区数据
	 */
	public function getAddress($latitude, $longitude)
	{
		$response = Basewind::curl($this->gateway . '?key=' . $this->config['key'] . '&location=' . implode(',', [$latitude, $longitude]));
		$response = json_decode($response);
		if ($response->status != 0) {
			$this->errors = $response->message;
			return false;
		}

		$name = '';
		$result = $response->result;
		if (isset($result->formatted_addresses)) {
			if (isset($result->formatted_addresses->recommend)) {
				$name = $result->formatted_addresses->recommend;
			} else if (isset($result->formatted_addresses->rough)) {
				$name = $result->formatted_addresses->rough;
			}
		} else if ($address = $result->address_component) {
			$name = $address->district . $address->street . $address->street_number;
		}

		return [
			'province' => $response->result->address_component->province,
			'city' => $response->result->address_component->city,
			'name' => $name ? $name : $result->address
		];
	}
}
