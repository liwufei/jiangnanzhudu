<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace common\plugins\promote\fullprefer;

use yii;
use yii\helpers\ArrayHelper;

use common\models\PromoteSettingModel;

use common\library\Timezone;
use common\library\Language;
use common\plugins\BasePromote;

/**
 * @Id fullprefer.plugin.php 2018.6.5 $
 * @author mosir
 */

class Fullprefer extends BasePromote
{
	/**
	 * 插件实例
	 * @var string $code
	 */
	protected $code = 'fullprefer';

	public function valid($post)
	{
		if ($post->amount <= 0) {
			$this->errors = Language::get('not_allempty');
			return false;
		}

		if ((!$post->discount && !$post->decrease) || ($post->discount && $post->decrease)) {
			$this->errors = Language::get('pls_select_type');
			return false;
		}

		if ($post->discount) {
			if ($post->discount <= 0 || $post->discount >= 10) {
				$this->errors = Language::get('discount_invalid');
				return false;
			}
		} elseif ($post->decrease) {
			if ($post->decrease <= 0) {
				$this->errors = Language::get('price_le_0');
				return false;
			} elseif ($post->amount <= $post->decrease) {
				$this->errors = Language::get('amount_le_decrease');
				return false;
			}
		}

		return true;
	}

	public function save($post, $valid = true)
	{
		if ($valid === true && !$this->valid($post)) {
			return false;
		}

		if (($message = $this->checkAvailable($this->params->store_id, true, false)) !== true) {
			$this->errors = $message;
			return false;
		}

		if (!($model = PromoteSettingModel::find()->where(['appid' => $this->code, 'store_id' => $this->params->store_id])->orderBy(['psid' => SORT_DESC])->one())) {
			$model = new PromoteSettingModel();
			$model->add_time = Timezone::gmtime();
		}

		if ($post->discount) {
			$post->discount = round(floatval($post->discount), 1);
			$post->type = 'discount';
			unset($post->decrease);
		} elseif ($post->decrease) {
			$post->decrease = abs(floatval($post->decrease));
			$post->type = 'decrease';
			unset($post->discount);
		}
		$model->appid = $this->code;
		$model->store_id = $this->params->store_id;
		$model->status = intval($post->status);

		unset($post->status);
		$model->rules = serialize(ArrayHelper::toArray($post));

		if (!$model->save()) {
			$this->errors = $model->errors;
			return false;
		}
		PromoteSettingModel::deleteAll(['and', ['appid' => $this->code], ['store_id' => $this->params->store_id], ['!=', 'psid', $model->psid]]);
		return true;
	}

	public function read()
	{
		if (($result = $this->getSetting($this->params->store_id))) {
			unset($result['rules']['type']);
			$result = array_merge(['status' => $result['status']], $result['rules']);
		}

		return $result;
	}
}
