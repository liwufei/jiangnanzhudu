<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace frontend\home\models;

use Yii;
use yii\base\Model;
use yii\helpers\ArrayHelper;

use common\models\CouponModel;

use common\library\Language;
use common\library\Timezone;

/**
 * @Id Seller_couponForm.php 2018.11.20 $
 * @author luckey
 */
class Seller_couponForm extends Model
{
	public $id = 0;
	public $store_id = 0;
	public $errors = null;

	public function valid($post)
	{
		if (!$post->name) {
			$this->errors = Language::get('coupon_name_required');
			return false;
		}
		if (!$post->money || ($post->money < 0)) {
			$this->errors = Language::get('coupon_value_not');
			return false;
		}
		if (!$post->amount || ($post->amount < 0)) {
			$this->errors = Language::get('amount_gt_zero');
			return false;
		}
		if (!$post->total || ($post->total < 0)) {
			$this->errors = Language::get('coupon_total_required');
			return false;
		}
		if (empty($post->start_time) || empty($post->end_time)) {
			$this->errors = Language::get('time_invalid');
			return false;
		}
		if (Timezone::gmstr2time_end($post->end_time) < Timezone::gmstr2time($post->start_time)) {
			$this->errors = Language::get('end_gt_start');
			return false;
		}

		return true;
	}

	public function save($post, $valid = true)
	{
		if ($valid === true && !$this->valid($post)) {
			return false;
		}
		if (!$this->id || !($model = CouponModel::find()->where(['id' => $this->id, 'store_id' => $this->store_id])->one())) {
			$model = new CouponModel();
			$model->store_id = $this->store_id;
			$model->available = 1;
			$model->use_times = 1;
			$model->total = floatval($post->total);
			$model->surplus = floatval($post->total);
		} else {
			// 如果是编辑，到期时重新生效
			if ($model->surplus > 0) {
				$model->available = 1;
			}
		}

		$model->name = $post->name;
		$model->money = $post->money;
		$model->start_time = Timezone::gmstr2time($post->start_time);
		$model->end_time = stripos($post->end_time, ':') == false ? Timezone::gmstr2time_end($post->end_time) : Timezone::gmstr2time($post->end_time);
		$model->amount = $post->amount;
		$model->received = isset($post->received) ? intval($post->received) : 0;
		$model->items = isset($post->items) && is_array($items = Arrayhelper::toArray($post->items)) ? implode(',', $items) : '';

		if (!$model->save()) {
			$this->errors = $model->errors;
			return false;
		}
		return ArrayHelper::toArray($model);
	}
}
