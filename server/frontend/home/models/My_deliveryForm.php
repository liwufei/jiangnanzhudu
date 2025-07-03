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

use common\models\DeliveryTemplateModel;
use common\library\Language;
use common\library\Timezone;

/**
 * @Id My_deliveryForm.php 2018.10.2 $
 * @author luckey
 */
class My_deliveryForm extends Model
{
	public $id = 0;
	public $store_id = null;
	public $errors = null;

	public function valid($post)
	{
		if (empty($post->name)) {
			$this->errors = Language::get('name_empty');
			return false;
		}

		$arrived = ArrayHelper::toArray($post->rules->arrived);
		if (empty($arrived)) {
			$this->errors = Language::get('rules_empty');
			return false;
		}

		if (!$this->checkVal($post)) {
			return false;
		}

		return true;
	}

	public function save($post, $valid = true)
	{
		if ($valid === true && !$this->valid($post)) {
			return false;
		}

		if (!$this->id || !($model = DeliveryTemplateModel::find()->where(['id' => $this->id, 'store_id' => $this->store_id])->one())) {
			$model = new DeliveryTemplateModel();
			$model->store_id = $this->store_id;
			$model->type = $post->type;
			$model->label = Language::get($post->type);
			$model->created = Timezone::gmtime();
		}

		if (isset($post->name)) $model->name = $post->name;
		if (isset($post->basemoney)) $model->basemoney = floatval($post->basemoney);
		if (isset($post->rules)) $model->rules = serialize(ArrayHelper::toArray($post->rules));
		if (isset($post->enabled)) $model->enabled = intval($post->enabled);

		if (!$model->save()) {
			$this->errors = $model->errors;
			return false;
		}

		// 只保留一个启用的
		if ($model->enabled) {
			DeliveryTemplateModel::updateAll(['enabled' => 0], ['and', ['store_id' => $model->store_id], ['type' => $model->type], ['!=', 'id', $model->id]]);
		}

		return true;
	}

	/**
	 * 校验是否为数值
	 */
	private function checkVal($post)
	{
		$rules = $post->rules;
		foreach ($rules->arrived as $key => $value) {
			if ($key > 0 && is_object($value->dests) && empty(get_object_vars($value->dests))) {
				$this->errors = Language::get('area_empty');
				return false;
			}
			foreach (['start', 'postage', 'plus', 'postageplus'] as $field) {
				if (!is_numeric($value->$field) || $value->$field < 0 || $value->$field == '') {
					$this->errors = Language::get('item_invalid');
					return false;
				}
			}
		}

		if ($post->type == 'locality') {
			if (!is_numeric($post->basemoney) || $post->basemoney < 0 || $post->basemoney == '') {
				$this->errors = Language::get('item_invalid');
				return false;
			}
		}

		return true;
	}
}
