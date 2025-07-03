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

use common\models\UserModel;
use common\models\DistributeMerchantModel;
use common\models\DistributeSettingModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Timezone;
use common\library\Def;

/**
 * @Id DistributeApplyForm.php 2018.10.23 $
 * @author mosir
 */
class DistributeApplyForm extends Model
{
	public $errors = null;

	public function valid($post)
	{
		if (empty($post->name)) {
			$this->errors = Language::get('name_required');
			return false;
		}
		if (empty($post->phone_mob)) {
			$this->errors = Language::get('phone_mob_invalid');
			return false;
		}
		if (Basewind::isPhone($post->phone_mob) == false) {
			$this->errors = Language::get('phone_mob_invalid');
			return false;
		}

		return true;
	}

	public function save($post, $valid = true)
	{
		if ($valid === true && !$this->valid($post)) {
			return false;
		}

		if (!($model = DistributeMerchantModel::find()->where(['userid' => Yii::$app->user->id])->one())) {
			$model = new DistributeMerchantModel();
			$model->userid 		= Yii::$app->user->id;
			$model->created		= Timezone::gmtime();
			$model->username 	= Yii::$app->user->identity->username;
			$model->status		= Def::STORE_APPLYING; // 如果分销商不需要审核后开通，请给值 Def::STORE_OPEN

			// 如果有推荐人，构建上下级分销商关系
			if (($invites = DistributeSettingModel::getInvites('register'))) {
				$parent_id = isset($invites[0]) ? intval($invites[0]) : 0;
				if (($parent_id != $model->userid) && (UserModel::find()->where(['userid' => $parent_id])->exists())) {
					$model->parent_id = $parent_id;
				}
			}
		} else if ($model->status == Def::STORE_OPEN) {
			$this->errors = Language::get('you_has_store');
			return false;
		}
		// 审核中
		else {
			$model->status = Def::STORE_APPLYING;
		}

		$model->phone_mob 	= $post->phone_mob;
		$model->name 		= $post->name;

		if (!$model->save()) {
			$this->errors = $model->errors;
			return false;
		}
		return true;
	}
}
