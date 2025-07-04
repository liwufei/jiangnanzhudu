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

use common\models\OrderModel;
use common\models\RefundModel;
use common\models\DepositTradeModel;
use common\models\OrderGoodsModel;
use common\models\RefundMessageModel;
use common\models\UserModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Timezone;
use common\library\Page;
use common\library\Def;

/**
 * @Id RefundForm.php 2018.10.17 $
 * @author mosir
 */
class RefundForm extends Model
{
	public $visitor = 'buyer';
	public $errors = null;
	
	/**
	 * 获取退款记录
	 */
	public function formData($post = null, $pageper = 4)
	{
		$query = RefundModel::find()->alias('r')
			->select('r.refund_id,r.refund_sn,r.title,r.buyer_id,r.seller_id,r.total_fee,r.refund_total_fee,r.created,r.finished,r.status,r.intervene,dt.bizOrderId,dt.bizIdentity')
			->joinWith('depositTrade dt', false)
			->orderBy(['created' => SORT_DESC]);

		if($this->visitor == 'seller') {
			$query->where(['r.seller_id' => Yii::$app->user->id]);
		} else {
			$query->where(['r.buyer_id' => Yii::$app->user->id]);
		}

		$page = Page::getPage($query->count(), $pageper);
		$list = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
		foreach ($list as $key => $value)
        {
			$list[$key]['buyer_name'] = UserModel::find()->select('username')->where(['userid' => $value['buyer_id']])->scalar();
			$list[$key]['seller_name'] = UserModel::find()->select('username')->where(['userid' => $value['seller_id']])->scalar();

			if(($value['bizIdentity'] == Def::TRADE_ORDER) && $value['bizOrderId']) {
				if(($order = OrderModel::find()->select('order_id,order_sn,seller_name as store_name')->where(['order_sn' => $value['bizOrderId']])->asArray()->one())) {
					$list[$key] += $order;
				}
			}
			$list[$key]['status_label'] = Language::get('REFUND_'.strtoupper($value['status']));
        }

		return array($list, $page);
	}
	
	/**
	 * 申请/编辑退款信息的数据验证及返回数据
	 */
	public function getData($post = null, $redirect = true)
	{
		// edit
		if($post->id)
		{
			if(!($refund = RefundModel::find()->alias('r')->select('r.*,dt.bizOrderId,dt.bizIdentity')->joinWith('depositTrade dt', false)->where(['refund_id' => $post->id, 'r.buyer_id' => Yii::$app->user->id])->asArray()->one())) {
				$this->errors = Language::get('no_such_refund');
				return false;
			}
			
			if(!in_array($refund['status'], ['WAIT_SELLER_AGREE','SELLER_REFUSE_BUYER','WAIT_SELLER_CONFIRM'])) {
				$this->errors = Language::get('not_allow_edit');
				return false;
			}
		}
		
		// add
		else
		{
			if(!$post->order_id || !($order = OrderModel::find()->select('order_id,order_sn,buyer_id,buyer_name,seller_id,seller_name,status')->where(['order_id' => $post->order_id, 'buyer_id' => Yii::$app->user->id])->one())) {
				$this->errors = Language::get('no_such_order');
				return false;
			}
			
			// 如果订单状态是已付款、待成团、待配送、待收货、待使用的订单，且不是交易完成的、交易关闭的可以申请退款
			if(!in_array($order->status, [Def::ORDER_ACCEPTED, Def::ORDER_TEAMING, Def::ORDER_DELIVERING, Def::ORDER_SHIPPED, Def::ORDER_USING])) { 
				$this->errors = Language::get('order_not_apply_refund');
				return false;
			}	
			
			if(!($trade = DepositTradeModel::find()->select('tradeNo,bizOrderId,bizIdentity,payment_code')->where(['bizIdentity' => Def::TRADE_ORDER, 'bizOrderId' => $order->order_sn])->one())) {
				$this->errors = Language::get('such_order_no_trade');
				return false;
			}
			
			// 如果是货到付款的订单，不允许退款
			if(in_array($trade->payment_code, ['cod'])) {
				$this->errors = Language::get('cod_order_refund_disabled');
				return false;
			}
			
			// 如果已存在退款记录，则直接访问
			if(($refund = RefundModel::find()->select('refund_id,tradeNo')->where(['tradeNo' => $trade->tradeNo, 'buyer_id' => Yii::$app->user->id])->one())) {
				if($redirect) {
					Yii::$app->controller->redirect(['refund/detail', 'id' => $refund->refund_id]);
				} else {
					$this->errors = Language::get('order_has_refund');
				}
				return false;
			}

			// 提交表单初始数据
			list($realGoodsAmount, $realFreight, $realOrderAmount) = OrderModel::getRealAmount($post->order_id);
			$refund = array_merge([], ArrayHelper::toArray($trade));
			$refund['goods_fee'] = $realGoodsAmount;
			$refund['freight'] = $realFreight;
			$refund['total_fee'] = $realOrderAmount;
		}

		return array($refund, $order, $trade);
	}

	public function valid($post = null)
	{
		return true;
	}

	/**
	 * 保存退款记录
	 */
	public function save($post = null, $get = null, $valid = true)
	{
		if ($valid === true && !$this->valid($post)) {
			return false;
		}

		list($refund, $order, $trade) = $this->getData($get, false);
		if (!$refund) {
			return false;
		}

		// 检查提交的数据
		if (!$this->checkData($post, $refund)) {
			return false;
		}

		// add
		if (!$get->id || !($model = RefundModel::findOne($get->id))) {
			$model = new RefundModel();
			$model->tradeNo = $refund['tradeNo'];
			$model->refund_sn = RefundModel::genRefundSn();
			$model->title = $this->getRefundTitle($get->order_id);
			$model->buyer_id = Yii::$app->user->id;
			$model->seller_id = $order->seller_id;
			$model->goods_fee = $refund['goods_fee'];
			$model->freight = $refund['freight'];
			$model->total_fee = $refund['total_fee'];
			$model->created = Timezone::gmtime();

			//买家已经申请退款，等待卖家同意
			$model->status = 'WAIT_SELLER_AGREE';
		}
		// edit
		else {
			// 退款申请等待卖家确认中（买家修改后）
			$model->status = 'WAIT_SELLER_CONFIRM';
		}

		$model->refund_goods_fee    = $post->refund_goods_fee ? $post->refund_goods_fee : 0;
		$model->refund_freight = $post->refund_freight ? $post->refund_freight : 0;
		$model->refund_total_fee 	= $model->refund_goods_fee + $model->refund_freight;
		$model->shipped				= $post->shipped;
		$model->refund_reason		= htmlspecialchars($post->refund_reason);
		$model->refund_desc			= htmlspecialchars($post->refund_desc);
		if (!$model->save()) {
			$this->errors = $model->errors ? $model->errors : Language::get('refund_save_fail');
			return false;
		}

		$query = new RefundMessageModel();
		$query->owner_id = Yii::$app->user->id;
		$query->owner_role = 'buyer';
		$query->refund_id = $model->refund_id;
		$query->content = sprintf(Language::get($get->id ? 'refund_content_change' : 'refund_content_apply'), $post->refund_goods_fee, $post->refund_freight, Language::get('shipped_' . $post->shipped), $post->refund_reason, $post->refund_desc);
		$query->created = Timezone::gmtime();
		if (!$query->save()) {
			$this->errors = $model->errors ? $model->errors : Language::get('refund_message_save_fail');
			return false;
		}

		// 如果是添加退款申请
		if (!$get->id) {
			// 短信提醒： 买家已申请退款通知卖家
			Basewind::sendMailMsgNotify(
				ArrayHelper::toArray($order),
				[],
				[
					'sender' => $order->seller_id,
					'receiver' => $order->seller_id,
					'key' => 'toseller_refund_apply_notify',
				]
			);
		}

		return $model;
	}

	/**
	 * 检测退款信息
	 */
	public function checkData($post = null, $refund = array())
	{
		$realAmount = [];

		// 订单实际金额信息 - 其他类型交易的退款，在此拓展
		if (($refund['bizIdentity'] == Def::TRADE_ORDER) && $refund['bizOrderId']) {
			if (($order = OrderModel::find()->select('order_id')->where(['order_sn' => $refund['bizOrderId']])->one())) {
				$realAmount = OrderModel::getRealAmount($order->order_id);
			}
		}
		list($realGoodsAmount, $realFreight, $realOrderAmount) = $realAmount;

		if ((!$post->refund_goods_fee && !$post->refund_freight) || (floatval($post->refund_goods_fee) + floatval($post->refund_freight)) < 0) {
			$this->errors = Language::get('refund_fee_ge0');
			return false;
		} elseif (floatval($post->refund_goods_fee) > $realGoodsAmount) {
			$this->errors = Language::get('refund_fee_error');
			return false;
		}
		if ($post->refund_freight && floatval($post->refund_freight) < 0) {
			$this->errors = Language::get('refund_freight_ge0');
			return false;
		}

		if (floatval($post->refund_freight) > $realFreight) {
			$this->errors = Language::get('refund_freight_error');
			return false;
		}
		// if (!in_array($post->shipped, [0, 1, 2])) {
		// 	$this->errors = Language::get('select_refund_shipped');
		// 	return false;
		// }
		if (empty($post->refund_reason)) {
			$this->errors = Language::get('select_refund_reason');
			return false;
		}
		return true;
	}

	public function getShippedOptions()
	{
		return array(Language::get('shipped_0'), Language::get('shipped_1'), Language::get('shipped_2'));
	}
	public function getRefundReasonOptions()
	{
		$reasons = array();
		for ($i = 0; $i <= 7; $i++) {
			$reasons[$i] = Language::get('reason_' . $i);
		}
		return $reasons;
	}

	/**
	 * 获取退款标题
	 */
	public function getRefundTitle($order_id = 0)
	{
		if (($query = OrderGoodsModel::find()->select('goods_name')->where(['order_id' => $order_id])->orderBy(['id' => SORT_DESC]))) {
			return addslashes($query->one()->goods_name . ($query->count() > 1 ? Language::get('and_more') : ''));
		}
		return '';
	}
}