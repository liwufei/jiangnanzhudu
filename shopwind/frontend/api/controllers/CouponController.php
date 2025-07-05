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

use common\models\CouponModel;
use common\models\CouponsnModel;
use common\models\UserModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Timezone;
use common\library\Page;

use frontend\api\library\Respond;
use frontend\api\library\Formatter;

/**
 * @Id CouponController.php 2019.1.15 $
 * @author yxyc
 */

class CouponController extends \common\base\BaseApiController
{
	/**
	 * 获取优惠券列表
	 * @api 接口访问地址: https://www.xxx.com/api/coupon/list
	 */
	public function actionList()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(false)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['store_id', 'page', 'page_size']);

		$query = CouponModel::find()->alias('c')->select('c.id,c.name,c.money,c.amount,c.image,c.total,c.surplus,c.start_time,c.end_time,c.items,s.store_id,s.store_name,s.store_logo')
			->joinWith('store s', false)
			->where(['received' => 1, 'available' => 1])
			->andWhere(['>', 'c.end_time', Timezone::gmtime()])
			->andWhere(['or', ['total' => 0], ['and', ['>', 'total', 0], ['>', 'surplus', 0]]])
			->orderBy(['id' => SORT_DESC]);
		if (isset($post->store_id)) $query->andWhere(['c.store_id' => $post->store_id]);
		if (isset($post->items) && !empty($post->items)) {
			$query->andWhere(['in', 'c.id', explode(',', $post->items)]);
		}
		if ($post->orderby) {
			list($field, $by) = explode('|', $post->orderby);
			$query->orderBy([$field => strtolower($by) == 'desc' ? SORT_DESC : SORT_ASC]);
		}

		$page = Page::getPage($query->count(), $post->page_size, false, $post->page);
		$list = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
		foreach ($list as $key => $value) {
			$list[$key]['start_time'] = Timezone::localDate('Y-m-d H:i:s', $value['start_time']);
			$list[$key]['end_time'] = Timezone::localDate('Y-m-d H:i:s', $value['end_time']);
			$list[$key]['image'] = Formatter::path($value['image']);
			$list[$key]['store_logo'] = Formatter::path($value['store_logo'], 'store');
			$list[$key]['amount'] = floatval($value['amount']);
			$list[$key]['money'] = floatval($value['money']);
			$list[$key]['items'] = array_map('intval', explode(',', $value['items']));
		}

		return $respond->output(true, null, ['list' => $list, 'pagination' => Page::formatPage($page, false)]);
	}

	/**
	 * 获取优惠券发送/领取列表
	 * @api 接口访问地址: http://api.xxx.com/coupon/record
	 */
	public function actionRecord()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(false)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['id', 'page', 'page_size']);

		$query = CouponsnModel::find()->alias('sn')->select('sn.*,c.id,c.name,c.received')
			->joinWith('coupon c', false)
			->orderBy(['created' => SORT_DESC]);
		if ($post->id) $query->andWhere(['coupon_id' => $post->id]);

		$page = Page::getPage($query->count(), $post->page_size, false, $post->page);
		$list = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
		foreach ($list as $key => $value) {
			if ($user = UserModel::find()->select('phone_mob,username')->where(['userid' => $value['userid']])->asArray()->one()) {
				$list[$key] = array_merge($value, $user);
			}
			$list[$key]['created'] = Timezone::localDate('Y-m-d H:i:s', $value['created']);
		}

		return $respond->output(true, null, ['list' => $list, 'pagination' => Page::formatPage($page, false)]);
	}

	/**
	 * 获取优惠券单条信息
	 * @api 接口访问地址: https://www.xxx.com/api/coupon/read
	 */
	public function actionRead()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(false)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['id']);

		$record = CouponModel::find()->alias('c')->select('c.id,c.name,c.money,c.amount,c.image,c.total,c.surplus,c.start_time,c.end_time,c.available,c.received,c.items,s.store_id,s.store_name,s.store_logo')
			->joinWith('store s', false)
			->where(['id' => $post->id])->asArray()->one();
		if (!$record) {
			return $respond->output(Respond::RECORD_NOTEXIST, Language::get('no_such_coupon'));
		}
		$record['start_time'] = Timezone::localDate('Y-m-d H:i:s', $record['start_time']);
		$record['end_time'] = Timezone::localDate('Y-m-d H:i:s', $record['end_time']);
		$record['image'] = Formatter::path($record['image']);
		$record['store_logo'] = Formatter::path($record['store_logo'], 'store');
		$record['amount'] = floatval($record['amount']);
		$record['money'] = floatval($record['money']);
		$record['items'] = $record['items'] ? array_map('intval', explode(',', $record['items'])) : '';

		return $respond->output(true, null, $record);
	}

	/**
	 * 插入优惠券信息
	 * @api 接口访问地址: https://www.xxx.com/api/coupon/add
	 */
	public function actionAdd()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(true)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true);

		$model = new \frontend\home\models\Seller_couponForm(['store_id' => Yii::$app->user->id]);
		if (($record = $model->save($post, true)) === false) {
			return $respond->output(Respond::HANDLE_INVALID, $model->errors);
		}

		return $respond->output(true);
	}

	/**
	 * 更新优惠券信息
	 * @api 接口访问地址: https://www.xxx.com/api/coupon/update
	 */
	public function actionUpdate()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(true)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['id', 'total']);

		$model = new \frontend\home\models\Seller_couponForm(['store_id' => Yii::$app->user->id, 'id' => $post->id]);
		if (($record = $model->save($post, true)) === false) {
			return $respond->output(Respond::HANDLE_INVALID, $model->errors);
		}

		return $respond->output(true, null, $record);
	}

	/**
	 * 商家删除优惠券信息
	 * @api 接口访问地址: https://www.xxx.com/api/coupon/delete
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

		if (!$post->id || !($model = CouponModel::find()->where(['id' => $post->id, 'store_id' => Yii::$app->user->id])->one())) {
			return $respond->output(Respond::RECORD_NOTEXIST, Language::get('no_such_coupon'));
		}

		// 失效了/没领用过才可以删除
		if (($model->total != $model->surplus) && $model->end_time > Timezone::gmtime()) {
			return $respond->output(Respond::HANDLE_INVALID, Language::get('drop_disabled'));
		}
		if ($model->delete() === false) {
			return $respond->output(Respond::HANDLE_INVALID, Language::get('drop_fail'));
		}
		// 删除后把用户领取的优惠券号也删除
		CouponsnModel::deleteAll(['coupon_id' => $post->id]);

		return $respond->output(true);
	}

	/**
	 * 点击领取优惠券
	 * @api 接口访问地址: https://www.xxx.com/api/coupon/receive
	 */
	public function actionReceive()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(true)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['id']);

		if (!($coupon = CouponModel::find()->where(['id' => $post->id, 'received' => 1, 'available' => 1])->andWhere(['>', 'end_time', Timezone::gmtime()])->one())) {
			return $respond->output(Respond::RECORD_NOTEXIST, Language::get('no_such_coupon'));
		}
		if ($coupon->store_id == Yii::$app->user->id) {
			return $respond->output(Respond::HANDLE_INVALID, Language::get('not_receive_self'));
		}
		if ($coupon->total > 0 && $coupon->surplus <= 0) {
			return $respond->output(Respond::HANDLE_INVALID, Language::get('coupon_receive_all'));
		}

		if (CouponsnModel::find()->where(['userid' => Yii::$app->user->id, 'coupon_id' => $coupon->id])->andWhere(['>', 'remain_times', 0])->exists()) {
			return $respond->output(Respond::CURD_FAIL, Language::get('coupon_has_receive'));
		}

		$model = new CouponsnModel();
		$model->coupon_sn = $model->createRandom();
		$model->coupon_id = $coupon->id;
		$model->remain_times = 1;
		$model->userid = Yii::$app->user->id;
		$model->created = Timezone::gmtime();
		if (!$model->save()) {
			return $respond->output(Respond::CURD_FAIL, $model->errors);
		}
		if (!CouponModel::updateAllCounters(['surplus' => -1], ['id' => $coupon->id])) {
			$model->delete();
			return $respond->output(Respond::CURD_FAIL, Language::get('receive_fail'));
		}
		return $respond->output(true, Language::get('receive_success'), ['coupon_sn' => $model->coupon_sn]);
	}

	/**
	 * 主动发放优惠券
	 * @api 接口访问地址: http://api.xxx.com/coupon/send
	 */
	public function actionSend()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(true)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['id', 'quantity']);
		if (!$post->quantity) $post->quantity = 1;

		if (!($coupon = CouponModel::find()->where(['id' => $post->id, 'received' => 0])->one())) {
			return $respond->output(Respond::RECORD_NOTEXIST, Language::get('no_such_coupon'));
		}
		if (!$coupon->available) {
			return $respond->output(Respond::HANDLE_INVALID, Language::get('已失效的优惠券不能发放'));
		}
		if ($coupon->store_id != Yii::$app->user->id) {
			return $respond->output(Respond::HANDLE_INVALID, Language::get('您无权发放优惠券'));
		}
		if ($coupon->total > 0 && $coupon->surplus <= 0) {
			return $respond->output(Respond::HANDLE_INVALID, Language::get('优惠券已发放完'));
		}
		if (!$post->users || !($users = ArrayHelper::toArray($post->users)) || count($users) <= 0) {
			return $respond->output(Respond::HANDLE_INVALID, Language::get('请选择发放用户'));
		}

		if ((count($users) * $post->quantity > $coupon->surplus)) {
			return $respond->output(Respond::HANDLE_INVALID, Language::get('优惠券不足以发放，请减少发放量'));
		}

		$transaction = Yii::$app->db->beginTransaction();
		try {
			foreach ($users as $userid) {
				for ($num = 1; $num <= $post->quantity; $num++) { // 允许一个人派发多张
					$model = new CouponsnModel();
					$model->coupon_sn = $model->createRandom();
					$model->coupon_id = $coupon->id;
					$model->remain_times = 1;
					$model->userid = intval($userid);
					$model->created = Timezone::gmtime();
					if (!$model->save()) {
						return false;
					}
				}
			}

			if (!CouponModel::updateAllCounters(['surplus' => - ($post->quantity * count($users))], ['id' => $coupon->id])) {
				return false;
			}
			$transaction->commit();
			return $respond->output(true, Language::get('send_success'), CouponModel::find()->where(['id' => $coupon->id])->asArray()->one());
		} catch (\Exception $e) {
			$transaction->rollBack();
			return false;
		}

		return $respond->output(false, Language::get('send_fail'));
	}
}
