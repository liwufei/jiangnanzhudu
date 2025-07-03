<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace frontend\api\controllers;

use Yii;

use common\models\AddressModel;
use common\models\RegionModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Page;

use frontend\api\library\Respond;

/**
 * @Id AddressController.php 2018.10.15 $
 * @author yxyc
 */

class AddressController extends \common\base\BaseApiController
{
	/**
	 * 获取用户收货地址列表
	 * @api 接口访问地址: https://www.xxx.com/api/address/list
	 */
	public function actionList()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(true)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['page', 'page_size']);

		$query = AddressModel::find()->where(['userid' => Yii::$app->user->id])->orderBy(['defaddr' => SORT_DESC, 'addr_id' => SORT_DESC]);
		$page = Page::getPage($query->count(), $post->page_size, false, $post->page);
		$list = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
		foreach ($list as $key => $value) {
			$list[$key] = array_merge($value, RegionModel::getArray($value['region_id']));
		}

		return $respond->output(true, Language::get('address_list'), ['list' => $list, 'pagination' => Page::formatPage($page, false)]);
	}

	/**
	 * 获取用户附近的收货地址列表
	 * @api 接口访问地址: https://www.xxx.com/api/address/nearby
	 */
	public function actionNearby()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(true)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['page', 'page_size']);

		$query = AddressModel::find()->where(['userid' => Yii::$app->user->id]);
		$query->getNearbyConditions($post, $query)->addOrderBy(['defaddr' => SORT_DESC]);

		$page = Page::getPage($query->count(), $post->page_size, false, $post->page);
		$list = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
		foreach ($list as $key => $value) {
			$list[$key] = array_merge($value, RegionModel::getArray($value['region_id']));
		}

		return $respond->output(true, Language::get('address_list'), ['list' => $list, 'pagination' => Page::formatPage($page, false)]);
	}

	/**
	 * 获取收货地址单条信息
	 * @api 接口访问地址: https://www.xxx.com/api/address/read
	 */
	public function actionRead()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(true)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['addr_id']);

		$record = AddressModel::find()->where(['userid' => Yii::$app->user->id, 'addr_id' => $post->addr_id])->asArray()->one();
		$record = array_merge($record, RegionModel::getArray($record['region_id']));
		return $respond->output(true, null, $record);
	}

	/**
	 * 插入收货地址信息
	 * @api 接口访问地址: https://www.xxx.com/api/address/add
	 */
	public function actionAdd()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(true)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['region_id']);

		$model = new \frontend\api\models\AddressForm();
		if (!$model->valid($post)) {
			return $respond->output(Respond::PARAMS_INVALID, $model->errors);
		}

		if (($record = $model->save($post, false)) === false) {
			return $respond->output(Respond::CURD_FAIL, Language::get('address_add_fail'));
		}

		$record = array_merge($record, RegionModel::getArray($record['region_id']));
		return $respond->output(true, null, $record);
	}

	/**
	 * 更新收货地址信息
	 * @api 接口访问地址: https://www.xxx.com/api/address/update
	 */
	public function actionUpdate()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(true)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['addr_id', 'region_id']);

		$model = new \frontend\api\models\AddressForm(['addr_id' => $post->addr_id]);
		if (!$model->exists($post)) {
			return $respond->output(Respond::RECORD_NOTEXIST, $model->errors);
		}
		if (!$model->valid($post)) {
			return $respond->output(Respond::PARAMS_INVALID, $model->errors);
		}
		if (($record = $model->save($post, false)) === false) {
			return $respond->output(Respond::CURD_FAIL, Language::get('address_update_fail'));
		}

		$record = array_merge($record, RegionModel::getArray($record['region_id']));
		return $respond->output(true, null, $record);
	}

	/**
	 * 删除收货地址信息
	 * @api 接口访问地址: https://www.xxx.com/api/address/delete
	 */
	public function actionDelete()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(true)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['addr_id']);

		if (!AddressModel::deleteAll(['and', ['userid' => Yii::$app->user->id], ['addr_id' => $post->addr_id]])) {
			return $respond->output(Respond::CURD_FAIL, Language::get('address_delete_fail'));
		}

		return $respond->output(true);
	}

	/**
	 * 根据坐标计算店铺距离，然后根据距离倒序显示数据
	 * @param int $distance  要检索的距离（米）
	 */
	private function getNearbyConditions($post, $query, $distance = 2000)
	{
		$latitude = $post->latitude;
		$longitude = $post->longitude;

		if (!$latitude || $latitude < 0 || !$longitude || $longitude < 0) {
			return $query;
		}

		// 这个效率待验证
		$fields = "(6371 * acos(cos(radians($latitude)) * cos(radians(latitude)) * cos(radians(longitude) - radians($longitude)) + sin(radians($latitude)) * sin(radians(latitude))))*1000 as distance";
		$query->addSelect($fields)->orderBy(['distance' => SORT_ASC]);

		// 如限定距离，加这行
		//$query->having(['<', 'distance', $distance]);

		return $query;
	}
}
