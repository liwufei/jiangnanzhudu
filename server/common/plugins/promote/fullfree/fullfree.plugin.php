<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace common\plugins\promote\fullfree;

use yii;
use yii\helpers\ArrayHelper;

use common\models\PromoteSettingModel;

use common\library\Timezone;
use common\library\Language;
use common\plugins\BasePromote;

/**
 * @Id fullfree.plugin.php 2018.6.5 $
 * @author mosir
 */

class Fullfree extends BasePromote
{
	/**
	 * 插件实例
	 * @var string $code
	 */
	protected $code = 'fullfree';

	public function valid(&$post)
	{
		if (isset($post->quantity)) {
			$post->quantity = intval($post->quantity);
		}
		if ($post->amount) {
			$post->amount = abs(floatval($post->amount));
		}

		if (!$post->amount && $post->quantity <= 0) {
			$this->errors = Language::get('not_allempty');
			return false;
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

		$model->appid = $this->code;
		$model->store_id = $this->params->store_id;
		$model->status = $post->status;

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
			$result = array_merge(['status' => $result['status']], $result['rules']);
		}

		return $result;
	}
}
