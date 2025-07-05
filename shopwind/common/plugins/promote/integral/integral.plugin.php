<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace common\plugins\promote\integral;

use yii;
use yii\helpers\ArrayHelper;

use common\models\IntegralModel;
use common\models\IntegralLogModel;
use common\models\IntegralSettingModel;

use common\library\Language;
use common\library\Timezone;
use common\plugins\BasePromote;

/**
 * @Id integral.plugin.php 2018.6.5 $
 * @author mosir
 */

class Integral extends BasePromote
{
	/**
	 * 插件实例
	 * @var string $code
	 */
	protected $code = 'integral';

	/**
	 * 获取会员积分信息
	 */
	public function getData($post = null)
	{
		if (!IntegralSettingModel::getSysSetting('enabled')) {
			$this->errors = Language::get('integral_disabled');
			return false;
		}

		if (Yii::$app->user->isGuest) {
			$this->errors = Language::get('login_please');
			return false;
		}

		// 会员当前的可用积分
		if (!($result = IntegralModel::find()->where(['userid' => Yii::$app->user->id])->asArray()->one())) {
			$result = ArrayHelper::toArray(IntegralModel::createAccount(Yii::$app->user->id));
		}

		// 会员当前被冻结的积分
		$points = 0;
		$list = IntegralLogModel::find()->select('changes')->where(['userid' => Yii::$app->user->id, 'state' => 'frozen'])->all();
		foreach ($list as $value) {
			$points += abs(floatval($value->changes));
		}
		$result['frozen'] = $points;

		// 今天是否可以签到领积分（每天一次）
		if (($signIntegral = IntegralSettingModel::getSysSetting('signin')) > 0) {
			$result['signined'] = false;
			$result['signIntegral'] = $signIntegral;

			$query = IntegralLogModel::find()->select('add_time')->where(['type' => 'signin', 'userid' => Yii::$app->user->id])->orderBy(['log_id' => SORT_DESC])->one();
			if ($query && Timezone::localDate('Ymd', Timezone::gmtime()) == Timezone::localDate('Ymd', $query->add_time)) {
				$result['signined'] = true;
			}
		}

		return $result;
	}
}
