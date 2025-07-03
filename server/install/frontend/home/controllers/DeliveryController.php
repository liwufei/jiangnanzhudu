<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace frontend\home\controllers;

use Yii;
use yii\helpers\ArrayHelper;

use common\models\OrderModel;
use common\models\OrderLogModel;
use common\models\OrderExtmModel;

use common\library\Plugin;
use common\library\Def;

/**
 * @Id DeliveryController.php 2018.10.20 $
 * @author yxyc
 */

class DeliveryController extends \common\base\BaseController
{
	/**
	 * 同城配送【达达】
	 * 【消息通知地址】接收第三方平台配送状态变更通知
	 */
	public function actionNotify()
	{
		$post = json_decode(file_get_contents('php://input'));

		if (!($order = OrderModel::find()->where(['order_sn' => $post->order_id])->one())) {
			return false;
		}

		$code = OrderExtmModel::find()->select('deliveryCode')->where(['order_id' => $order->order_id])->scalar();
		if (!($client = Plugin::getInstance('locality')->build($code))) {
			return false;
		}

		list($status, $remark) = $client->getStatus($post);
		if ($status) {
			if ($order->status == Def::ORDER_CANCELED) {
				OrderLogModel::change($order->order_id, $status, $remark);
				OrderLogModel::create($order->order_id, Def::ORDER_CANCELED);
			} else OrderLogModel::create($order->order_id, $status, $remark);
		}

		// 订单取消之重发单[退款导致的取消订单等，不要重发单]
		if ($client->isReAdd($post->order_status) && $order->status == Def::ORDER_DELIVERING) {
			$client->reOrder(ArrayHelper::toArray($order));
		}

		// 配送完成信号
		if ($client->isFinished($post->order_status)) {
			OrderModel::updateAll(['status' => Def::ORDER_SHIPPED], ['order_id' => $order->order_id]);
		}

		return $client->result(true);
	}
}
