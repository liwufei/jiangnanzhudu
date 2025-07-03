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
use yii\helpers\ArrayHelper;

use common\models\DepositTradeModel;
use common\models\OrderLogModel;
use common\models\OrderExpressModel;
use common\models\OrderExtmModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Timezone;
use common\library\Def;
use common\library\Plugin;

/**
 * @Id Seller_orderShippedForm.php 2018.9.19 $
 * @author mosir
 */
class Seller_orderShippedForm extends Model
{
	public $store_id = 0;
	public $errors = null;

	/**
	 * 发货
	 */
	public function submit($post = null, $order = null)
	{
		if (!$post->number) {
			$this->errors = Language::get('express_no_empty');
			return false;
		}

		$isModify = $order->ship_time ? true : false;
		$order->status = Def::ORDER_SHIPPED;
		if (!$order->ship_time) {
			$order->ship_time = Timezone::gmtime();
		}

		if (!$order->save()) {
			$this->errors = $order->errors;
			return false;
		}

		// 如果需要支持多条快递单，则拓展此
		if (!($express = OrderExpressModel::find()->where(['order_id' => $order->order_id])->one())) {
			$express = new OrderExpressModel();
			$express->order_id = $order->order_id;
		}

		// 取得一个可用的快递跟踪插件
		if (($client = Plugin::getInstance('express')->autoBuild())) {
			if (!$post->code) {
				$this->errors = Language::get('express_company_empty');
				return false;
			}
			OrderExtmModel::updateAll(['deliveryCode' => $client->getCode()], ['order_id' => $order->order_id]);
			$express->company = $client->getCompanyName($post->code);
		}

		$express->code = $post->code;
		$express->number = $post->number;
		if (!$express->save()) {
			$this->errors = $express->errors;
			return false;
		}

		// 如果是小程序订单，则上传发货信息给微信（小程序上架要求：实物订单必须上传发货信息）
		if ($order->payment_code == 'wxmppay') {
			$client = Plugin::getInstance('other')->build('wxship');
			if ($client->isInstall()) {
				if ($client->upload($order)) {
					// 0=未退，1=已推，2=已重推，微信限制发货信息只能推送2次
					$order->shipwx = $order->shipwx ? $order->shipwx + 1 : 1;
					$order->save();
				}
			}
		}

		// 更新交易状态
		DepositTradeModel::updateAll(['status' => 'SHIPPED'], ['bizOrderId' => $order->order_sn, 'bizIdentity' => Def::TRADE_ORDER, 'seller_id' => $order->seller_id]);

		// 记录订单操作日志
		if (!$isModify) {
			OrderLogModel::create($order->order_id, Def::ORDER_SHIPPED, '', addslashes($order->buyer_name));
		}

		// 短信和邮件提醒： 已发货通知买家
		Basewind::sendMailMsgNotify(
			array_merge(ArrayHelper::toArray($order), ['express_no' => $post->number]),
			[
				'receiver' 	=> $order->buyer_id,
				'key' 		=> 'tobuyer_shipped_notify'
			],
			[
				'sender' 	=> $order->seller_id,
				'receiver'  => OrderExtmModel::find()->select('phone_mob')->where(['order_id' => $order->order_id])->scalar(), // 收货人的手机号
				'key' 		=> 'tobuyer_shipped_notify',
			]
		);

		return true;
	}
}
