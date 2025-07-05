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
 * @Id FullpreferController.php 2018.12.8 $
 * @author yxyc
 */

class FullpreferController extends \common\base\BaseApiController
{
	/**
	 * 获取店铺满优惠信息
	 * @api 接口访问地址: https://www.xxx.com/api/fullprefer/read
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

		// 店铺满优惠
		$client = Plugin::getInstance('promote')->build('fullprefer', (object) ['store_id' => Yii::$app->user->id]);
		if ($client) $result = $client->read();

		return $respond->output(true, null, $result);
	}

	/**
	 * 设置满优惠信息
	 * @api 接口访问地址: https://www.xxx.com/api/fullprefer/update
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

		$client = Plugin::getInstance('promote')->build('fullprefer', (object) ['store_id' => Yii::$app->user->id]);
		if (!$client || !$client->save($post, true)) {
			return $respond->output(Respond::CURD_FAIL, $client->errors);
		}

		return $respond->output(true);
	}
}
