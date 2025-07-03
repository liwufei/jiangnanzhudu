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

use common\models\OrderGoodsModel;
use common\models\OrderExtmModel;
use common\models\RegionModel;
use common\models\GoodsSpecModel;

use common\library\Language;

/**
 * @Id BaseLocality.php 2018.9.5 $
 * @author mosir
 */

class BaseLocality extends BasePlugin
{
	/**
	 * 同城配送插件系列
	 * @var string $instance
	 */
	protected $instance = 'locality';

	public function getData($order_id = 0)
	{
		$weightall = 0;
		$items = OrderGoodsModel::find()->select('goods_name,spec_id,quantity')->where(['order_id' => $order_id])->asArray()->all();
		foreach ($items as $key => $item) {
			$query = GoodsSpecModel::find()->select('weight')->where(['spec_id' => $item['spec_id']])->one();
			$items[$key]['weight'] = $query ? $query->weight : 0;
			$weightall += $items[$key]['weight'];
		}

		$extm = OrderExtmModel::find()->select('consignee,region_id,address,phone_mob,longitude,latitude')->where(['order_id' => $order_id])->asArray()->one();
		if ($extm) {
			$address = RegionModel::getArray($extm['region_id']);
			$array['address'] = implode('', $address) . $extm['address'];
			unset($extm['region_id']);
		}

		return array_merge($extm ? $extm : [], ['weightall' => $weightall, 'items' => $items]);
	}

	/**
	 * 下单
	 */
	public function addOrder($order = [], $business_type = 0)
	{
		return true;
	}

	public function cancelOrder($orderId, $reason = '')
	{
		return true;
	}

	public function createShop($post)
	{
		return true;
	}


	/**
	 * 查询余额
	 */
	public function queryMoney($store_id = 0, $category = 1)
	{
		// 返还一个较大的值即可
		return 100000;
	}

	public function result($target = false)
	{
		if ($target) echo json_encode(['status' => 'ok']);
		else echo json_encode(['status' => 'fail']);
	}

	public function getRoute()
	{
		return [
			'index' => 7, // 排序
			'text'  => Language::get('plugin_locality'),
			'url'   => Url::toRoute(['plugin/index', 'instance' => 'locality']),
			'priv'  => ['key' => 'plugin|locality|all', 'label' => Language::get('plugin_locality')]
		];
	}
}
