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

use common\library\Basewind;
use common\library\Plugin;

use frontend\api\library\Respond;

/**
 * @Id ExclusiveController.php 2018.12.8 $
 * @author yxyc
 */

class ExclusiveController extends \common\base\BaseApiController
{
	/**
	 * 获取首单立减配置
	 * @api 接口访问地址: https://www.xxx.com/api/exclusive/read
	 */
	public function actionRead()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(true)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true);

		$client = Plugin::getInstance('promote')->build('exclusive', (object) ['store_id' => Yii::$app->user->id]);
		if ($client) $result = $client->read();

		return $respond->output(true, null, $result);
	}

	/**
	 * 设置首单立减优惠信息
	 * @api 接口访问地址: https://www.xxx.com/api/exclusive/update
	 */
	public function actionUpdate()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(true)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['status']);

		$client = Plugin::getInstance('promote')->build('exclusive', (object) ['store_id' => Yii::$app->user->id]);
		if (!$client || !$client->save($post, true)) {
			return $respond->output(Respond::CURD_FAIL, $client->errors);
		}

		return $respond->output(true);
	}
}
