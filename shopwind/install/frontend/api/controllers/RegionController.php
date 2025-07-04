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

use common\models\RegionModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Plugin;
use common\library\Page;

use frontend\api\library\Respond;

/**
 * @Id RegionController.php 2018.10.15 $
 * @author yxyc
 */

class RegionController extends \common\base\BaseApiController
{
	/**
	 * 获取地区列表
	 * @api 接口访问地址: https://www.xxx.com/api/region/list
	 */
	public function actionList()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(false)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['parent_id', 'if_show']);

		$model = new \frontend\api\models\RegionForm();
		list($list, $page) = $model->formData($post, true);

		// 非全量获取的情况下，才允许获取下级
		if (isset($post->querychild) && ($post->querychild === true) && isset($post->parent_id)) {
			foreach ($list as $key => $value) {
				$post->parent_id = $value['region_id'];
				list($children) = $model->formData($post, false);
				$list[$key]['children'] = $children;
			}
		}
		$this->params = ['list' => $list, 'pagination' => Page::formatPage($page, false)];

		return $respond->output(true, Language::get('region_list'), $this->params);
	}

	/**
	 * 获取树型结构地区列表
	 * @api 接口访问地址: https://www.xxx.com/api/region/tree
	 */
	public function actionTree()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(false)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['layer', 'parent_id']);
		if (!isset($post->parent_id)) $post->parent_id = -1;

		$list = RegionModel::getTree($post->parent_id, true, $post->layer, false);
		return $respond->output(true, Language::get('region_list'), $list);
	}

	/**
	 * 获取地区单条信息
	 * @api 接口访问地址: https://www.xxx.com/api/region/read
	 */
	public function actionRead()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(false)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['region_id']);

		$record = RegionModel::find()->where(['region_id' => $post->region_id])->asArray()->one();
		if ($record && isset($post->querychild) && ($post->querychild === true)) {
			$record['children'] = RegionModel::find()->where(['parent_id' => $record['region_id']])->asArray()->all();
		}

		return $respond->output(true, null, $record);
	}

	/**
	 * 插入地区信息
	 * @api 接口访问地址: https://www.xxx.com/api/region/add
	 */
	public function actionAdd()
	{
		exit('根据需要开放');

		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(false)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['parent_id', 'if_show', 'sort_order']);

		$model = new \frontend\api\models\RegionForm();
		if (!$model->valid($post)) {
			return $respond->output(Respond::PARAMS_INVALID, $model->errors);
		}

		if (!$model->save($post, false)) {
			return $respond->output(Respond::CURD_FAIL, Language::get('region_add_fail'));
		}

		return $respond->output(true, null, ['region_id' => $model->region_id]);
	}

	/**
	 * 更新地区信息
	 * @api 接口访问地址: https://www.xxx.com/api/region/update
	 */
	public function actionUpdate()
	{
		exit('根据需要开放');

		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(false)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['region_id', 'parent_id']);

		$model = new \frontend\api\models\RegionForm(['region_id' => $post->region_id]);
		if (!$model->exists($post)) {
			return $respond->output(Respond::RECORD_NOTEXIST, $model->errors);
		}
		if (!$model->valid($post)) {
			return $respond->output(Respond::PARAMS_INVALID, $model->errors);
		}
		if (!$model->save($post, false)) {
			return $respond->output(Respond::CURD_FAIL, Language::get('region_update_fail'));
		}

		return $respond->output(true, null, ['region_id' => $model->region_id]);
	}

	/**
	 * 删除地区信息
	 * @api 接口访问地址: https://www.xxx.com/api/region/delete
	 */
	public function actionDelete()
	{
		exit('根据需要开放');

		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(false)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['region_id']);

		if (!RegionModel::deleteAll(['region_id' => $post->region_id])) {
			return $respond->output(Respond::CURD_FAIL, Language::get('region_delete_fail'));
		}

		return $respond->output(true);
	}

	/**
	 * 根据经纬度获取地址信息
	 * @api 接口访问地址: https://www.xxx.com/api/region/geocode
	 */
	public function actionGeocode()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(false)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true);

		$result = [];
		$client = Plugin::getInstance('map')->autoBuild();
		if (!$client || !$client->isInstall()) {
			return $respond->output(Respond::HANDLE_INVALID, Language::get('no_plugin'));
		}
		if (!($result = $client->getAddress($post->latitude, $post->longitude))) {
			return $respond->output(Respond::HANDLE_INVALID, $client->errors ? $client->errors : Language::get('no_data'));
		}

		return $respond->output(true, null, $result);
	}
}
