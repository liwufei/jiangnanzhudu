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
use yii\helpers\ArrayHelper;

use common\models\StoreModel;
use common\models\DeliveryTimerModel;
use common\models\DeliveryTemplateModel;
use common\models\OrderModel;
use common\models\OrderLogModel;
use common\models\OrderExtmModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Timezone;
use common\library\Plugin;
use common\library\Def;
use common\library\Page;
use common\library\Business;
use common\models\PluginModel;
use frontend\api\library\Respond;

/**
 * @Id DeliveryController.php 2018.10.20 $
 * @author yxyc
 */

class DeliveryController extends \common\base\BaseApiController
{
	/**
	 * 获取指定店铺运费模版列表
	 * @api 接口访问地址: https://www.xxx.com/api/delivery/list
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
		if (!isset($post->store_id)) $post->store_id = Yii::$app->user->id;

		$query = DeliveryTemplateModel::find()->where(['store_id' => $post->store_id])->orderBy(['id' => SORT_DESC]);
		if ($post->type) $query->andWhere(['type' => $post->type]);

		$page = Page::getPage($query->count(), $post->page_size, false, $post->page);
		$list = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
		foreach ($list as $key => $value) {
			$list[$key]['enabled'] = intval($value['enabled']);
			$list[$key]['rules'] = unserialize($value['rules']);
			$list[$key]['created'] = Timezone::localDate('Y-m-d', $value['created']);
		}

		return $respond->output(true, null, ['list' => $list, 'pagination' => Page::formatPage($page, false)]);
	}

	/**
	 * 读取指定ID运费模板
	 * @api 接口访问地址: https://www.xxx.com/api/delivery/read
	 */
	public function actionRead()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(true)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['id']);

		$record = DeliveryTemplateModel::find()->where(['id' => $post->id])->asArray()->one();
		if ($record) {
			$record['rules'] = unserialize($record['rules']);
			$record['created'] = Timezone::localDate('Y-m-d H:i:s', $record['created']);
		}

		return $respond->output(true, null, $record);
	}

	/**
	 * 更新运费模板
	 * @api 接口访问地址: https://www.xxx.com/api/delivery/update
	 */
	public function actionUpdate()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(true)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['id']);

		// 如果不是卖家，不给更新
		if (!StoreModel::find()->where(['store_id' => Yii::$app->user->id])->exists()) {
			return $respond->output(Respond::HANDLE_INVALID, Language::get('no_seller'));
		}

		$model = new \frontend\home\models\My_deliveryForm(['store_id' => Yii::$app->user->id, 'id' => $post->id]);
		if (!$model->save($post, true)) {
			return $respond->output(Respond::HANDLE_INVALID, $model->errors);
		}

		return $respond->output(true);
	}

	/**
	 * 删除运费模板
	 * @api 接口访问地址: https://www.xxx.com/api/delivery/delete
	 */
	public function actionDelete()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(true)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['id']);

		//if (!DeliveryTemplateModel::find()->where(['store_id' => Yii::$app->user->id])->andWhere(['<>', 'id', $post->id])->exists()) {
		//return $respond->output(Respond::HANDLE_INVALID, Language::get('last_tempalte'));
		//}

		if (!$post->id || (!$model = DeliveryTemplateModel::find()->where(['store_id' => Yii::$app->user->id,  'id' => $post->id])->one()) || !$model->delete()) {
			return $respond->output(Respond::RECORD_NOTEXIST, Language::get('drop_fail'));
		}

		return $respond->output(true);
	}

	/**
	 * 获取指定店铺在用的运费模板
	 * @api 接口访问地址: https://www.xxx.com/api/delivery/template
	 */
	public function actionTemplate()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(false)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['store_id']);
		if (!isset($post->type)) $post->type = 'locality';

		// 运费模板
		$query = DeliveryTemplateModel::find()->where(['store_id' => $post->store_id, 'type' => $post->type])
			->orderBy(['enabled' => SORT_DESC, 'id' => SORT_DESC]);

		if (!($record = $query->asArray()->one())) {
			$record = DeliveryTemplateModel::addFirstTemplate($post->store_id, $post->type);
		}

		if ($record) {
			$record['rules'] = unserialize($record['rules']);
			$record['created'] = Timezone::localDate('Y-m-d H:i:s', $record['created']);
		}

		return $respond->output(true, null, $record);
	}

	/**
	 * 获取快递公司列表
	 * @api 接口访问地址: https://www.xxx.com/api/delivery/company
	 */
	public function actionCompany()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(false)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true);

		$list = [];
		if ($client = Plugin::getInstance('express')->autoBuild()) {
			$companys = $client->getCompanys();
			foreach ($companys as $key => $value) {
				$list[] = array('code' => $key, 'name' => $value);
			}
			// $this->params = ['plugin' => $client->getCode(), 'config' => $client->getConfig(), 'list' => $list];
		}
		return $respond->output(true, null, ['list' => $list]);
	}

	/**
	 * 获取已安装的配送方式类型列表
	 * @api 接口访问地址: https://www.xxx.com/api/delivery/types
	 */
	public function actionTypes()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(true)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['enabled']);
		if (!isset($post->enabled)) $post->enabled = 1;

		$list = PluginModel::find()->select('name,code')->where(['instance' => $post->category, 'enabled' => intval($post->enabled)])->asArray()->all();
		return $respond->output(true, null, $list);
	}

	/**
	 * 获取指定店铺物流配送时效【预留，或待优化与同城配送兼容】
	 * @api 接口访问地址: https://www.xxx.com/api/delivery/timer
	 */
	public function actionTimer()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(false)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['store_id']);
		if (!isset($post->store_id) || !$post->store_id) $post->store_id = Yii::$app->user->id;

		$record = DeliveryTimerModel::find()->select('id,store_id,rules')
			->where(['store_id' => $post->store_id])
			->orderBy(['id' => SORT_DESC])
			->asArray()->one();

		if ($record && $record['rules']) {
			$rules = unserialize($record['rules']);

			$now = Timezone::gmtime();
			$today = Timezone::localDate('Y-m-d 00:00:00', true);

			// 格式化时间显示文本
			if (is_array($rules)) {
				foreach ($rules as $key => $value) {
					$day = intval($value['day']);
					$rules[$key]['arrived'] = ($day == 0 ? '（今日）' : ($day == 1 ? '（明天）' : '')) . Timezone::localDate('m月d日', Timezone::gmstr2time($today) + $day * 24 * 3600) . ' ' . $value['time'];

					$start = Timezone::gmstr2time(Timezone::localDate('Y-m-d') . $value['start'] . ':00');
					$end = Timezone::gmstr2time(Timezone::localDate('Y-m-d') . $value['end'] . ':59');

					if (empty($record['result']) && ($now > $start && $now < $end)) {
						$record['result'] = '最快' . $rules[$key]['arrived'] . ' 前送达';
					}
				}
				$record['rules'] = $rules;
			}
		}

		return $respond->output(true, null, $record ? $record : []);
	}

	/**
	 * 更新店铺物流配送时效【预留，或待优化与同城配送兼容】
	 * @api 接口访问地址: https://www.xxx.com/api/delivery/timerupdate
	 */
	public function actionTimerupdate()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(true)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true);
		if (isset($post->rules)) $post->rules = ArrayHelper::toArray($post->rules);

		// 如果不是卖家，不给与更新
		if (!StoreModel::find()->where(['store_id' => Yii::$app->user->id])->exists()) {
			return $respond->output(Respond::HANDLE_INVALID, Language::get('no_seller'));
		}

		if (!($model = DeliveryTimerModel::find()->where(['store_id' => Yii::$app->user->id])->one())) {
			$model = new DeliveryTimerModel();
			$model->created = Timezone::gmtime();
			$model->store_id = Yii::$app->user->id;
		}
		$model->name = '物流配送时效';
		$model->rules = $post->rules ? serialize($post->rules) : '';

		if (!$model->save()) {
			return $respond->output(Respond::HANDLE_INVALID, $model->errors);
		}

		return $respond->output(true);
	}

	/**
	 * 查询运费【下单时查询，商品详情页查询】等
	 * 同城配送 & 快递发货
	 * @api 接口访问地址: https://www.xxx.com/api/delivery/freight
	 */
	public function actionFreight()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(true)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true);

		$result = [];
		foreach ($post as $order) {
			$client = Business::getInstance('delivery')->build($order->type, $order);
			if (!($array = $client->queryFee())) {
				$array['error'] = $client->errors ? $client->errors : Language::get('query_fail');
			}
			$result[$order->store_id] = array_merge(['type' => $client->getCode()], $array);
		}

		return $respond->output(true, null, $result);
	}

	/**
	 * 获取骑士配送信息H5页面
	 * 用于显示骑手轨迹
	 * @api 接口访问地址: https://www.xxx.com/api/delivery/trackurl
	 */
	public function actionTrackurl()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(true)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true);

		$code = OrderExtmModel::find()->select('deliveryCode')->where(['order_id' => $post->order_id])->scalar();
		if (!($client = Plugin::getInstance('locality')->build($code))) {
			return $respond->output(Respond::RECORD_NOTEXIST, Language::get('no_plugin'));
		}

		if (!($order = OrderModel::find()->select('order_sn')->where(['order_id' => $post->order_id])->one())) {
			return $respond->output(Respond::RECORD_NOTEXIST, Language::get('no_such_order'));
		}

		$result = $client->trackurl($order->order_sn);
		return $respond->output(true, null, $result);
	}

	/**
	 * 同城配送【外网访问，不要加通讯验签】
	 * 第三方平台配送状态变更通知
	 * @api 接口访问地址: https://www.xxx.com/api/delivery/notify
	 */
	public function actionNotify()
	{
		$post = json_decode(file_get_contents('php://input'));

		$code = OrderExtmModel::find()->select('deliveryCode')->where(['order_id' => $post->order_id])->scalar();
		if (!($client = Plugin::getInstance('locality')->build($code))) {
			return false;
		}

		if ($order = OrderModel::find()->where(['order_sn' => $post->order_id])->one()) {

			list($status, $remark) = $client->getStatus($post);
			if ($status) {
				if ($order->status == Def::ORDER_CANCELED) {
					OrderLogModel::change($order->order_id, $status, $remark);
					OrderLogModel::create($order->order_id, Def::ORDER_CANCELED);
				} else OrderLogModel::create($order->order_id, $status, $remark);
			}

			// 订单取消之重发单[退款导致的取消订单等，不要重发单]
			if ($client->isReAdd($post->order_status) && $order->status == Def::ORDER_DELIVERING) {
				$client->reOrder(ArrayHelper::toArray($order));
			}

			// 配送完成信号
			if ($client->isFinished($post->order_status)) {
				OrderModel::updateAll(['status' => Def::ORDER_SHIPPED], ['order_id' => $order->order_id]);
			}

			return $client->result(true);
		}

		return $client->result(false);
	}
}
