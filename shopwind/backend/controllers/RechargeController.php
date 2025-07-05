<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace backend\controllers;

use Yii;
use yii\helpers\ArrayHelper;

use common\models\RechargeSettingModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Message;
use common\library\Page;

/**
 * @Id RechargeController.php 2018.8.22 $
 * @author mosir
 */

class RechargeController extends \common\base\BaseAdminController
{
	public function actionSetting()
	{
		$this->params['list'] = RechargeSettingModel::find()->orderBy(['money' => SORT_ASC])->asArray()->all();

		if (!Yii::$app->request->isPost) {
			$this->params['page'] = Page::seo(['title' => Language::get('recharge_setting')]);
			return $this->render('../recharge.setting.html', $this->params);
		} else {
			$post = Basewind::trimAll(Yii::$app->request->post());

			if (empty($post['money'])) {
				return Message::warning(Language::get('请设置规则'));
			}

			$rules = [];
			foreach ($post['money'] as $key => $value) {
				if ($value > 0 && $post['reward'][$key] >= 0) {
					$rules[$value] = ['money' => $value, 'reward' => $post['reward'][$key]];
				}
			}

			RechargeSettingModel::deleteAll();
			foreach ($rules as $value) {
				$model = new RechargeSettingModel();
				$model->money = $value['money'];
				$model->reward = $value['reward'];
				$model->save();
			}

			return Message::display(Language::get('add_ok'));
		}
	}
}
