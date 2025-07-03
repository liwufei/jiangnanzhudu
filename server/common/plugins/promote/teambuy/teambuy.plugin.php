<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace common\plugins\promote\teambuy;

use yii;
use yii\helpers\ArrayHelper;

use common\models\TeambuyModel;
use common\library\Language;
use common\plugins\BasePromote;

/**
 * @Id teambuy.plugin.php 2018.6.5 $
 * @author mosir
 */

class Teambuy extends BasePromote
{
	/**
     * 插件实例
	 * @var string $code
	 */
    protected $code = 'teambuy';

	public function valid(&$post)
	{
		$result = [];

		if (($message = $this->checkAvailable($this->params->store_id, true, false)) !== true) {
			$this->errors = $message;
			return false;
		}

		if (!$post->goods_id) {
			$this->errors = Language::get('fill_goods');
			return false;
		}
		if (TeambuyModel::find()->where(['goods_id' => $post->goods_id])->andWhere(['!=', 'id', $this->params->id])->exists()) {
			$this->errors = Language::get('goods_has_set_teambuy');
			return false;
		}
		if (empty($post->specs) || !is_object($post->specs)) {
			$this->errors = Language::get('fill_spec');
			return false;
		}

		// 目前只考虑打折类型的优惠
		foreach ($post->specs as $key => $value) {
			if (!$value->discount || $value->discount >= 10 || $value->discount <= 0) {
				$this->errors = Language::get('invalid_price');
				return false;
			}

			$result[$key] = ['price' => $value->discount, 'type' => 'discount'];
		}
		if ($result) {
			$post->specs = (object) $result;
		}

		return true;
	}

	public function save($post, $valid = true)
	{
		if ($valid === true && !$this->valid($post)) {
			return false;
		}

		if (!$this->params->id || !($model = TeambuyModel::find()->where(['id' => $this->params->id, 'store_id' => $this->params->store_id])->one())) {
			$model = new TeambuyModel();
		}

		if (!isset($post->people) || $post->people < 2) {
			$post->people = 2;
		}

		$model->title = $post->title ? $post->title : Language::get('twopeople');
		$model->goods_id = $post->goods_id;
		$model->specs = serialize(ArrayHelper::toArray($post->specs));
		$model->store_id = $this->params->store_id;
		$model->status = 1;
		$model->people = $post->people;

		if (!$model->save()) {
			$this->errors = $model->errors;
			return false;
		}
		return true;
	}
}

