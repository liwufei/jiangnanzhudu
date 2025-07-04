<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace backend\models;

use Yii;
use yii\base\Model;

use common\models\WeixinSettingModel;
use common\models\UploadedFileModel;

use common\library\Language;

/**
 * @Id WeixinSettingForm.php 2018.8.27 $
 * @author mosir
 */
class WeixinSettingForm extends Model
{
	public $id = 0;

	/**
	 * mp/applet/merapplet
	 */
	public $code = 'mp';
	public $errors = null;

	public function valid($post)
	{
		if (empty($post->name)) {
			$this->errors = Language::get('name_empty');
			return false;
		}
		if (empty($post->appid)) {
			$this->errors = Language::get('appid_empty');
			return false;
		}
		if (empty($post->appsecret)) {
			$this->errors = Language::get('appsecret_empty');
			return false;
		}
		if ($this->code == 'mp' && empty($post->token)) {
			$this->errors = Language::get('token_empty');
			return false;
		}
		return true;
	}

	public function save($post, $valid = true)
	{
		if ($valid === true && !$this->valid($post)) {
			return false;
		}

		if (!$this->id || !($model = WeixinSettingModel::findOne($this->id))) {
			if (!($model = WeixinSettingModel::find()->where(['code' => $this->code])->orderBy(['id' => SORT_DESC])->one())) {
				$model = new WeixinSettingModel();
				$model->code = $this->code;
			}
		}

		if ($image = UploadedFileModel::getInstance()->upload('codeurl', 'weixin/', 0, 0, $this->code)) {
			$model->codeurl = $image;
		}

		foreach ($post as $key => $value) {
			if (in_array($key, ['name', 'appid', 'appsecret', 'token', 'autoreg'])) {
				$model->$key = $value;
			}
		}

		if (!$model->save()) {
			$this->errors = $model->errors;
			return false;
		}
		return true;
	}
}
