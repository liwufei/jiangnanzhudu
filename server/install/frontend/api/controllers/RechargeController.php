<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace frontend\api\controllers;

use Yii;
use yii\helpers\ArrayHelper;

use common\models\RechargeSettingModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Message;
use common\library\Page;
use frontend\api\library\Respond;

/**
 * @Id RechargeController.php 2018.8.22 $
 * @author mosir
 */

class RechargeController extends \common\base\BaseApiController
{
	/**
	 * 获取充值配置
	 * @api 接口访问地址: http://api.xxx.com/recharge/setting
	 */
	public function actionSetting()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(true)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true);

		$list = RechargeSettingModel::find()
			->select('money,reward')
			->orderBy(['money' => SORT_ASC])
			->asArray()->all();

		return $respond->output(true, null, $list);
	}
}
