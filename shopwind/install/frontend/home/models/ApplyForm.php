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

use common\models\StoreModel;
use common\models\SgradeModel;
use common\models\UploadedFileModel;
use common\models\CategoryStoreModel;
use common\models\IntegralModel;
use common\models\IntegralSettingModel;
use common\models\DeliveryTemplateModel;
use common\models\RegionModel;
use common\models\UserPrivModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Timezone;
use common\library\Def;

/**
 * @Id ApplyForm.php 2018.10.4 $
 * @author mosir
 */
class ApplyForm extends Model
{
	public $store_id = 0;
	public $errors = null;

	public function valid($post = null)
	{
		foreach (['store_name', 'owner', 'identity_card', 'contacter', 'phone'] as $field) {
			if (empty($post->$field)) {
				$this->errors = Language::get($field . '_empty');
				return false;
			}
		}

		if (!Basewind::isPhone($post->phone)) {
			$this->errors = Language::get('phone_mob_invalid');
			return false;
		}

		if (($store = StoreModel::find()->select('store_id')->where(['store_name' => $post->store_name])->one())) {
			if (!$this->store_id || ($this->store_id != $store->store_id)) {
				$this->errors = Language::get('store_name_existed');
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

		if (!$this->store_id || !($model = StoreModel::findOne($this->store_id))) {
			$model = new StoreModel();
			$model->store_id = Yii::$app->user->id;
			$model->sort_order = 255;
			$model->add_time = Timezone::gmtime();
			$model->stype = $post->stype != 'company' ? 'personal' : $post->stype; // 个人/企业
		} else {
			// 如果是店铺已开通，则不允许编辑
			if ($model->state == Def::STORE_OPEN) {
				$this->errors = Language::get('handle_invalid');
				return false;
			}

			// 如果店铺被平台关闭，则不允许编辑
			if ($model->state == Def::STORE_CLOSED) {
				$this->errors = Language::get('store_closed');
				return false;
			}

			// 如果不是平台审核后未通过模式，则不允许编辑
			if ($model->state != Def::STORE_NOPASS) {
				$this->errors = Language::get('store_verfiying');
				return false;
			}

			// 编辑后，清空之前审核记录
			$model->apply_remark = '';
		}

		$model->state = SgradeModel::find()->select('need_confirm')->where(['id' => $post->sgrade])->scalar() ? Def::STORE_APPLYING : Def::STORE_OPEN;
		$fields = ['store_name', 'owner', 'identity_card', 'region_id', 'address', 'phone', 'tel', 'sgrade', 'contacter'];
		foreach ($fields as $key => $value) {
			if (isset($post->$value)) {
				$model->$value = $post->$value;
			}
		}

		// 图片字段
		$fields = ['identity_front', 'identity_back', 'business_license'];
		foreach ($fields as $key => $value) {
			$model->$value = $this->getFileSavePath($post->$value);
		}

		//  验证证件上传完整性
		if (!$this->checkIdentity($model)) {
			return false;
		}

		if (!$model->save()) {
			$this->errors = $model->errors ? $model->errors : Language::get('apply_fail');
			return false;
		}

		if ($post->cate_id > 0) {
			CategoryStoreModel::deleteAll(['store_id' => $model->store_id]);

			$query = new CategoryStoreModel();
			$query->store_id = $model->store_id;
			$query->cate_id = $post->cate_id;
			$query->save();
		}

		if (!$this->store_id) {

			// 添加店铺所有权
			$this->insertStorePrivs($model->store_id);
		}

		// 不需要审核，店铺直接开通
		if ($model->state) {
			// 给商家赠送开店积分
			IntegralModel::updateIntegral([
				'userid'  => Yii::$app->user->id,
				'type'    => 'openshop',
				'points'  => IntegralSettingModel::getSysSetting('openshop')
			]);
		}

		return $model;
	}

	/**
	 * 检测身份证、营业执照
	 */
	private function checkIdentity($post)
	{
		if (empty($post->identity_front) || empty($post->identity_back)) {
			$this->errors = Language::get('identity_empty');
			return false;
		}

		if ($post->stype == 'company') {
			if (empty($post->business_license)) {
				$this->errors = Language::get('business_license_empty');
				return false;
			}
		}

		return true;
	}

	/**
	 * 添加店铺所有权
	 */
	private function insertStorePrivs($store_id = 0)
	{
		$model = new UserPrivModel();
		$model->userid = Yii::$app->user->id;
		$model->store_id = $store_id;
		$model->privs = 'all';
		return $model->save();
	}

	/**
	 * 如果是本地存储，存相对地址，如果是云存储，存完整地址
	 */
	private function getFileSavePath($image = '')
	{
		if (stripos($image, Def::fileSaveUrl()) !== false) {
			return str_replace(Def::fileSaveUrl() . '/', '', $image);
		}
		return $image;
	}
}
