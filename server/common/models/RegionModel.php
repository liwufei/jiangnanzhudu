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

use common\library\Basewind;
use common\library\Language;
use common\library\Tree;

/**
 * @Id RegionModel.php 2018.4.22 $
 * @author mosir
 */

class RegionModel extends ActiveRecord
{
	/**
	 * @inheritdoc
	 */
	public static function tableName()
	{
		return '{{%region}}';
	}

	/**
	 * 取得地区列表
	 * @param int $parent_id 大于等于0表示取某个地区的下级地区，小于0表示取所有地区
	 * @param bool $shown    只取显示的地区
	 * @return array
	 */
	public static function getList($parent_id = -1, $shown = true, $cached = true)
	{
		$cache = Yii::$app->cache;
		$cachekey = md5((__METHOD__) . var_export(func_get_args(), true));
		$data = $cache->get($cachekey);
		if ($data === false || !$cached) {
			$query = parent::find();
			if ($shown) $query->andWhere(['if_show' => 1]);
			if ($parent_id >= 0) $query->where(['parent_id' => $parent_id]);
			$data = $query->orderBy(['sort_order' => SORT_ASC, 'region_id' => SORT_ASC])->asArray()->all();

			$cache->set($cachekey, $data, 3600);
		}
		return $data;
	}

	/* 所有地区树结构 */
	public static function getTree($parent_id = -1, $shown = true, $layer = 0, $cached = true)
	{
		$cache = Yii::$app->cache;
		$cachekey = md5((__METHOD__) . var_export(func_get_args(), true));
		$data = $cache->get($cachekey);
		if ($data === false || !$cached) {
			$categories = self::getList(-1, $shown, $cached);

			if ($categories) {
				$tree = new Tree();
				$tree->setTree($categories, 'region_id', 'parent_id', 'name');
				$data = $tree->getArrayList($parent_id > 0 ? $parent_id : 0, $layer);

				//第二个参数即是我们要缓存的数据 
				//第三个参数是缓存时间，如果是0，意味着永久缓存。默认是0 
				$cache->set($cachekey, $data, 3600);
			}
		}

		return $data;
	}

	/**
	 * 取得所有地区 
	 * 保留级别缩进效果，一般用于select
	 * @return array(21 => 'abc', 22 => '&nbsp;&nbsp;');
	 */
	public static function getOptions($parent_id = -1, $except = null, $layer = 0, $shown = true, $space = '')
	{
		$regions = self::getList($parent_id, $shown, false);

		$tree = new Tree();
		$tree->setTree($regions, 'region_id', 'parent_id', 'name');

		return $tree->getOptions($layer, 0, $except, ($parent_id == -1 && $space !== false) ? '&nbsp;&nbsp;' : $space);
	}

	/* 寻找某ID的所有父级 */
	public static function getParents($id = 0, $selfIn = true)
	{
		$result = array();
		if (!$id) return $result;

		if ($selfIn) $result[] = $id;
		while (($query = parent::find()->select('region_id,parent_id,name')->where(['region_id' => $id])->one())) {
			if ($query->parent_id) $result[] = $query->parent_id;
			$id = $query->parent_id;
		}
		return array_reverse($result);
	}

	/**
	 * 取得某分类的子孙分类id
	 * @param int  $id     分类id
	 * @param bool $cached 是否缓存
	 * @param bool $shown  只取显示的地区
	 * @param bool $selfin 是否包含自身id
	 * @return array(1,2,3,4...)
	 */
	public static function getDescendantIds($id = 0, $cached = true, $shown = false, $selfin = true)
	{
		$cache = Yii::$app->cache;
		$cachekey = md5((__METHOD__) . var_export(func_get_args(), true));
		$data = $cache->get($cachekey);
		if ($data === false || !$cached) {
			$conditions = $shown ? ['if_show' => 1] : null;

			$tree = new Tree();
			$data = $tree->recursive(new RegionModel(), $conditions)->getArrayList($id, 'region_id', 'parent_id', 'name')->fields($selfin);

			$cache->set($cachekey, $data, 3600);
		}
		return $data;
	}

	/**
	 * 获取省市数据
	 * 地区数据第一级是省
	 */
	public static function getProvinceCity()
	{
		$provinces = self::getList(0);
		foreach ($provinces as $key => $province) {
			$provinces[$key]['cities'] = self::getList($province['region_id']);
		}
		return $provinces;
	}

	/**
	 * 获取省市区地址数组
	 */
	public static function getArray($region_id = 0)
	{
		$result = [];
		$array = self::getNames(intval($region_id), false);

		$fields = ['province', 'city', 'district'];
		foreach ($fields as $key => $field) {
			$result[$field] = isset($array[$key]) ? $array[$key] : '';
		}

		return $result;
	}

	/**
	 * 根据ID获取完整地址名称
	 * @param int|string $region_id
	 * @return array|string
	 */
	public static function getNames($region_id = 0, $split = '')
	{
		$result = [];
		do {
			$query = parent::find()->select('name,parent_id')->where(['region_id' => $region_id])->one();
			if ($query) {
				$result[] = $query->name;
				$region_id = $query->parent_id;
			}
		} while ($query);

		if ($result) $result = array_reverse($result);
		if ($split === false) return $result;

		return $result ? implode($split, $result) : '';
	}

	/**
	 * 通过IP自动获取本地城市id
	 */
	public static function getCityIdByIp($cached = true)
	{
		$cache = Yii::$app->cache;
		$cachekey = md5((__METHOD__) . var_export(func_get_args(), true));
		$data = $cache->get($cachekey);
		if ($data === false || !$cached) {
			$ip = Yii::$app->request->userIP;
			$address = self::getAddressByIp($ip);
			if ($address && isset($address['local']) && !$address['local']) {
				$province 	= $address['province'];
				$city 		= $address['city'];

				$regionProvince = parent::find()->select('region_id,name')->where(['parent_id' => 0])->andWhere(['in', 'name', [$province, str_replace('省', '', $province)]])->one();

				if ($regionProvince) {
					$regionCity = parent::find()->select('region_id,name')->where(['parent_id' => $regionProvince->region_id])->andWhere(['in', 'name', [$city, str_replace('市', '', $city)]])->one();
					if ($regionCity) {
						$data = $regionCity->region_id;
						$cache->set($cachekey, $data, 3600);
					}
				}
			}
		}
		return $data ? $data : 0;
	}

	/**
	 * 使用淘宝的IP库
	 * @api https://ip.taobao.com
	 */
	public static function getAddressByIp($ip = '')
	{
		if (empty($ip) || in_array($ip, ['127.0.0.1', 'localhost'])) {
			return ['city' => Language::get('local')];
		}

		$result = Basewind::curl('https://ip.taobao.com/outGetIpInfo.php?ip=' . $ip);
		$result = json_decode($result);
		if ($result->code == 0 && $result->data->city) {
			return array_merge(
				['province' => $result->data->region, 'city' => $result->data->city],
				['local' => $result->data->city_id == 'local' ? true : false]
			);
		}
		return [];
	}
}
