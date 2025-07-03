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

use common\models\RegionModel;

use common\library\Timezone;
use common\library\Language;

/**
 * @Id DeliveryTemplateModel.php 2018.5.7 $
 * @author mosir
 */

class DeliveryTemplateModel extends ActiveRecord
{
	/**
	 * @inheritdoc
	 */
	public static function tableName()
	{
		return '{{%delivery_template}}';
	}

	/**
	 * 获取指定地址的运费
	 * @param $dtype 值为locality时，获取的是商家自配的运费
	 */
	public static function queryFee($store_id, $region_id, $weight = 1, $dtype = 'locality')
	{
		// 找出运费模板
		$delivery = parent::find()->where(['store_id' => $store_id, 'type' => $dtype])
			->orderBy(['enabled' => SORT_DESC, 'id' => SORT_DESC])->asArray()->one();

		// 如果没有运费模板, 则创建
		if (!$delivery) {
			$delivery = self::addFirstTemplate($store_id, $dtype);
		}

		// 格式化数据
		$template = self::getCityLogistic($delivery, $region_id);
		$freight = $template['postage'];
		if ($weight > $template['start'] && $template['plus'] > 0) {
			$freight += ceil(($weight - $template['start']) / $template['plus']) * $template['postageplus'];
		}

		return ['freight' => round($freight, 2), 'name' => Language::get($dtype)];
	}

	public static function addFirstTemplate($store_id = 0, $type = 'locality', $config = [])
	{
		if (!in_array($type, self::getTypes())) {
			return false;
		}

		$rules['arrived'] = [array_merge(
			[
				'dests' => 0,
				'start' => 1,
				'postage' => 6,
				'plus' => 1,
				'postageplus' => 3
			],
			is_array($config) ? $config : []
		)];

		$model = new DeliveryTemplateModel();
		$model->name 			= '默认运费';
		$model->store_id 		= intval($store_id);
		$model->type 			= $type;
		$model->label 			= Language::get($type);
		$model->basemoney 		= $type == 'locality' ? 29 : 0;
		$model->rules 			= serialize($rules);
		$model->enabled 		= 1;
		$model->created			= Timezone::gmtime();

		return $model->save() ? ArrayHelper::toArray($model) : false;
	}

	public static function getCityLogistic($delivery, $destid = 0, $cached = true)
	{
		$cache = Yii::$app->cache;
		$cachekey = md5((__METHOD__) . var_export(func_get_args(), true));
		$data = $cache->get($cachekey);
		if ($data === false || !$cached) {
			$data = self::getTypeLogistic($delivery, $destid);
			$cache->set($cachekey, $data, 3600);
		}
		return $data;
	}

	/**
	 * 获取指定目的地的运费规则
	 * 快递发货：没有指定地区配送规则时，使用默认运费规则
	 * 同城配送：没有指定地区配送规则时，该地址不配送
	 * @param int $destid 运送目的地ID，有可能是城市ID，也有可能是省ID或镇ID
	 */
	public static function getTypeLogistic($delivery, $destid = 0)
	{
		$rules = is_array($delivery['rules']) ? $delivery['rules'] : unserialize($delivery['rules']);
		if (is_array($rules)) {

			$default = [];
			foreach ($rules['arrived'] as $key => $area) {
				if ($key == 0) {
					$default = array_merge(['type' => $delivery['type'], 'name' => Language::get($delivery['type'])], self::getFields($area));
				}
				if ($area['dests']) {
					$temp = $area['dests'];
					arsort($temp); // 级数多的在前
					foreach (array_values($temp) as $item) {
						$lastid = end($item);
						if ($lastid && ($all = RegionModel::getDescendantIds($lastid))) {
							if (in_array($destid, $all)) {
								return array_merge($default, self::getFields($area));
							}
						}
					}
				}
			}
			return $default;
		}
		return [];
	}

	private static function getFields($item)
	{
		unset($item['dests']);
		return $item;
	}

	/**
	 * 支持的配送方式
	 */
	public static function getTypes()
	{
		// 快递发货、同城配送
		return ['express', 'locality'];
	}
}
