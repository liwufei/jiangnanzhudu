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

use common\models\OrderModel;
use common\models\RefundModel;
use common\models\IntegralModel;
use common\models\DistributeModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Def;

/**
 * @Id RefundAgreeForm.php 2018.10.19 $
 * @author mosir
 */
class RefundAgreeForm extends Model
{
	public $errors = null;

	/**
	 * 同意退款申请
	 */
	public function submit($post = null)
	{
		if (!$post->id || !($refund = RefundModel::find()->alias('r')->select('r.*,dt.tradeNo,dt.payTradeNo,dt.bizOrderId,dt.bizIdentity')->joinWith('depositTrade dt', false)->where(['refund_id' => $post->id, 'r.seller_id' => Yii::$app->user->id])->andWhere(['not in', 'r.status', ['SUCCESS', 'CLOSED']])->asArray()->one())) {
			$this->errors = Language::get('agree_disallow');
			return false;
		}

		if (($refund['bizIdentity'] == Def::TRADE_ORDER) && $refund['bizOrderId']) {
			$order = OrderModel::find()->where(['order_sn' => $refund['bizOrderId']])->asArray()->one();
		}

		// 目前只考虑普通购物订单的退款，如果需要考虑其他业务的退款，请再这里拓展
		if (!$order) {
			$this->errors = Language::get('no_such_order');
			return false;
		}

		// 修改为卖家处理退款
		RefundModel::updateAll(['intervene' => 0], ['refund_id' => $post->id]);

		$amount	= $refund['refund_total_fee'];
		$chajia	= round($refund['total_fee'] - $amount, 2);

		// 转到对应的业务实例，不同的业务实例用不同的文件处理，如购物，卖出商品，充值，提现等，每个业务实例又继承支出或者收入 
		$depopay_type = \common\library\Business::getInstance('depopay')->build('refund', $post);
		$result = $depopay_type->submit(array(
			'trade_info' => array('userid' => $order['seller_id'], 'party_id' => $order['buyer_id'], 'amount' => $amount),
			'extra_info' => $order + array('tradeNo' => $refund['tradeNo'], 'payTradeNo' => $refund['payTradeNo'], 'chajia' => $chajia, 'refund_sn' => $refund['refund_sn'], 'refund_id' => $post->id, 'operator' => 'seller')
		));

		if ($result !== true) {
			$this->errors = $depopay_type->errors;
			return false;
		}

		// （非全额退款）退款后分销三级返佣处理
		DistributeModel::distributeInvite($order);

		//（全额退款）退款后的积分返还/退回
		if ($chajia == 0) {
			IntegralModel::returnIntegral($order);
		}

		// 短信提醒：卖家同意退款，通知买家
		Basewind::sendMailMsgNotify(
			$order,
			[],
			[
				'sender' => $order['seller_id'],
				'receiver'  => $order['buyer_id'],
				'key' 		=> 'tobuyer_refund_agree_notify',
			]
		);

		return true;
	}
}
