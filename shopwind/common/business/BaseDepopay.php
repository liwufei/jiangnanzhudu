<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace common\business;

use yii;

use common\models\DepositAccountModel;
use common\models\DepositTradeModel;
use common\models\DepositRecordModel;

use common\library\Language;
use common\library\Timezone;
use common\library\Def;

/**
 * @Id BaseDepopay.php 2018.4.12 $
 * @author mosir
 */

class BaseDepopay
{
	/**
	 * 交易类型
	 * @var string $dtype
	 */
	protected $dtype;

	/**
	 * 页面提交参数
	 * @var object $post
	 */
	public $post;

	/**
	 * 其他额外参数
	 * @var array $params
	 */
	public $params;

	/**
	 * 错误捕捉
	 * @var object $errors
	 */
	public $errors;

	public function __construct($dtype, $post = null, $params = [])
	{
		$this->dtype 	= $dtype;
		$this->post 	= $post;
		$this->params 	= $params;
	}

	/*  更新交易状态 */
	public function updateTradeStatus($tradeNo, $params = [])
	{
		if (($model = DepositTradeModel::find()->where(['tradeNo' => $tradeNo])->one()) && $params) {
			foreach ($params as $key => $value) {
				$model->$key = $value;
			}
			if ($model->save()) {
				return true;
			}
		}
		return false;
	}

	/*  更新订单状态 */
	public function updateOrderStatus($id, $params = [])
	{
		if (($model = \common\models\OrderModel::find()->where(['or', ['order_sn' => $id], ['order_id' => $id]])->one()) && $params) {
			foreach ($params as $key => $value) {
				$model->$key = $value;
			}
			return $model->save();
		}
		return false;
	}

	/* 插入账户收支记录，并变更账户余额 */
	public function insertDepositRecord($params = [], $changeBalance = true)
	{
		$model = new DepositRecordModel();
		foreach ($params as $key => $value) {
			$model->$key = $value;
		}
		if ($model->save()) {
			if ($changeBalance == true) {
				$field = $params['fundtype'];
				if (DepositAccountModel::updateAll([$field => $params['balance']], ['userid' => $params['userid']]) === false) {
					$model->delete();
					return false;
				}
				return true;
			}
			return true;
		}
		return false;
	}

	/* 系统扣费（交易，提现，转账等） */
	public function sysChargeback($tradeNo, $trade_info, $rate, $type = 'trade_fee')
	{
		// 费率不合理，不进行扣点
		if (!$rate || $rate <= 0 || $rate > 1) return true;

		$fee  = round($trade_info['amount'] * $rate, 2);

		if ($fee <= 0) {
			return true;
		}

		if (is_array($type) || empty($type)) {
			$remark	= Language::get('trade_fee') . '[' . $tradeNo . ']';
		} else $remark = Language::get($type) . '[' . $tradeNo . ']';

		$time = Timezone::gmtime();
		$data_trade = array(
			'tradeNo'		=>	DepositTradeModel::genTradeNo(),
			'bizOrderId'	=>  DepositTradeModel::genTradeNo(12, 'bizOrderId'),
			'bizIdentity'	=>  Def::TRADE_CHARGE,
			'buyer_id'		=>	$trade_info['userid'],
			'seller_id'		=>	0,
			'amount'		=>	$fee,
			'status'		=>	'SUCCESS',
			'payment_code' 	=>  'deposit',
			'payType'		=>	'INSTANT',
			'flow'			=>	'outlay',
			'title'			=>	Language::get('chargeback'),
			'add_time'		=>	$time,
			'pay_time'		=>	$time,
			'end_time'		=>	$time,
		);

		$model = new DepositTradeModel();
		foreach ($data_trade as $key => $val) {
			$model->$key = $val;
		}

		if ($model->save()) {
			$data_record = array(
				'tradeNo'		=>	$data_trade['tradeNo'],
				'userid'		=>	$trade_info['userid'],
				'amount'		=>  $fee,
				'balance'		=>	DepositAccountModel::updateDepositMoney($trade_info['userid'], $fee, 'reduce'),
				'tradeType'		=>  'SERVICE',
				'fundtype'		=>	'money',
				'flow'			=>	'outlay',
				'name'			=>	Language::get('chargeback'),
				'remark'		=>  $remark,
				'created'		=>  $time
			);
			return $this->insertDepositRecord($data_record, false);
		}
	}

	/**
	 * 如果是使用余额支付，且账户有不可提现金额
	 * 则解除该部分金额的提现额度限制，避免不可提现金额一直存在
	 */
	public function relieveUserNodrawal($tradeNo, $userid = 0, $money = 0)
	{
		$query = DepositTradeModel::find()->select('payment_code')->where(['tradeNo' => $tradeNo])->one();
		if (!$query || ($query->payment_code != 'deposit')) {
			return true;
		}

		$model = DepositAccountModel::find()->select('account_id,nodrawal')->where(['userid' => $userid])->one();
		if ($model && ($model->nodrawal > 0)) {
			$model->nodrawal = ($model->nodrawal - $money) > 0 ? $model->nodrawal - $money : 0;
			return $model->save();
		}
		return false;
	}

	public function setErrors($errorCode = '', $errorMsg = '')
	{
		if ($errorCode) {
			$ex = new DepopayException();
			if (isset($ex->errorMsg[$errorCode])) {
				$errorMsg = $ex->errorMsg[$errorCode];
			}
		}

		$this->errors = $errorMsg;
	}
}

class DepopayException
{
	var $errorMsg;

	function __construct()
	{

		$this->errorMsg = array(
			"10000" => "系统异常！",
			"10001" => "交易金额不能小于零！",
			"50001" => "交易异常！扣除的交易服务费小于零元，或者所扣除的交易服务费大于交易金额。",
			"50002" => "交易异常！交易金额小于零元。",
			"50003" => "订单异常！找不到商户订单号。",
			"50004" => "充值异常！找不到指定的银行卡信息。",
			"50005" => "交易异常！无法正确添加充值记录。",
			"50006" => "对不起！在退款给买家过程中，插入收支记录失败。",
			"50007" => "对不起！订单退款记录添加失败。",
			"50008" => "对不起！卖家收支记录添加过程中出现异常。",
			"50009" => "平台扣除卖家手续费出现异常！",
			"50010" => "对不起！无法通过商户订单号查询到该订单的交易号。",
			"50011" => "交易异常！转账过程中，无法正确添加转出记录。",
			"50012" => "交易异常！转账过程中，无法正确添加转入记录。",
			"50013" => "退款异常！无法正确修改订单状态。",
			"50014" => "对不起！订单日志插入失败。",
			"50015" => "平台扣除转账手续费出现异常！",
			"50016" => "对不起！提现过程中插入收支记录出现异常。",
			"50017" => "对不起！提现过程中冻结资金更新出现异常。",
			"50018" => "对不起！在插入提现银行卡信息过程中出现错误。",
			"50019" => "对不起！您的账户余额不足。",
			"50020" => "交易异常！插入收支记录过程中出现问题。",
			"50021" => "交易异常！无法正确修改订单状态。",
			"50022" => "操作异常！买家确认收货后无法正常修改交易状态。",
			"50023" => "交易异常！取消订单中退回给买家款项时出现插入错误。",
			"50024" => "交易异常！无法正确修改交易状态信息。",
			"50025" => "交易异常！支付接口未配置",
			"60001" => "交易异常！购买应用中无法正常支付",
			"60002" => "更新所购买应用的过期时间出现异常！",
			"60003" => "无法正常变更购买应用记录中的状态",
			"70001" => "提现到支付宝钱包接口异常",
		);
	}
}
