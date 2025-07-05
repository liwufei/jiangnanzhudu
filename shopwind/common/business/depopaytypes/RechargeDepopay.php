<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace common\business\depopaytypes;

use yii;

use common\models\DepositAccountModel;
use common\models\DepositTradeModel;
use common\models\DepositSettingModel;
use common\models\DepositRecordModel;
use common\models\DepositRechargeModel;
use common\models\RechargeSettingModel;

use common\library\Language;
use common\library\Timezone;
use common\library\Def;

/**
 * @Id RechargeDepopay.php 2018.7.22 $
 * @author mosir
 */

class RechargeDepopay extends IncomeDepopay
{
	/**
	 * 针对财务明细的资金用途，值有：在线支付：PAY；充值：RECHARGE；提现：WITHDRAW；服务费：SERVICE；转账：TRANSFER；返现：REGIVE；扣费：CHARGE
	 */
	public $_tradeType = 'RECHARGE';

	public function submit($data = [])
	{
		extract($data);

		// 处理交易基本信息
		$base_info = parent::handleTradeInfo($trade_info, $extra_info);
		if (!$base_info) {
			return false;
		}

		//$tradeNo = $extra_info['tradeNo'];

		// 插入充值记录
		if (!$this->insertRechargeInfo($trade_info, $extra_info)) {
			$this->setErrors('50005');
			return false;
		}

		return true;
	}

	/* 插入交易记录，充值记录 */
	private function insertRechargeInfo($trade_info, $extra_info)
	{
		// 如果添加有记录，则不用再添加了
		if (!DepositTradeModel::find()->where(['tradeNo' => $extra_info['tradeNo']])->exists()) {
			$bizOrderId	= DepositTradeModel::genTradeNo(12, 'bizOrderId');

			// 增加交易记录
			$data_trade = array(
				'tradeNo'		=> $extra_info['tradeNo'],
				'payTradeNo'	=> DepositTradeModel::genPayTradeNo(),
				'bizOrderId'	=> $bizOrderId,
				'bizIdentity'	=> Def::TRADE_RECHARGE,
				'buyer_id'		=> $trade_info['userid'],
				'seller_id'		=> $trade_info['party_id'],
				'amount'		=> $trade_info['amount'],
				'status'		=> 'PENDING',
				'payment_code'	=> $this->post->payment_code,
				'payType'		=> $this->_payType,
				'flow'     		=> $this->_flow,
				'title'			=> Language::get($this->post->fundtype == 'bond' ? '充值到保证金' : '充值到钱包'),
				'buyer_remark'	=> $this->post->remark ? $this->post->remark : '',
				'add_time'		=> Timezone::gmtime()
			);

			$model = new DepositTradeModel();
			foreach ($data_trade as $key => $val) {
				$model->$key = $val;
			}

			if ($model->save(false) == true) {
				$query = new DepositRechargeModel();
				$query->orderId = $bizOrderId;
				$query->userid = $trade_info['userid'];
				$query->fundtype = $this->post->fundtype;
				$result = $query->save();
			}
		}
		return $result;
	}

	/**
	 * 线上充值响应通知 
	 */
	public function notify($orderInfo = [])
	{
		$time = Timezone::gmtime();

		foreach ($orderInfo['tradeList'] as $tradeInfo) {

			// 修改交易状态
			DepositTradeModel::updateAll(['status' => 'SUCCESS', 'pay_time' => $time, 'end_time' => $time], ['tradeNo' => $tradeInfo['tradeNo']]);

			// 充值资金类型
			$fundtype = DepositRechargeModel::find()->select('fundtype')->where(['orderId' => $tradeInfo['bizOrderId']])->scalar();

			// 充值到钱包 & 保证金
			return $this->updateMoney($tradeInfo, $fundtype);
		}
	}

	private function updateMoney($tradeInfo = [], $fundtype = 'money')
	{
		// 插入充值者收入记录，并更新账户余额表
		$model = new DepositRecordModel();
		$model->tradeNo = $tradeInfo['tradeNo'];
		$model->userid = $tradeInfo['buyer_id'];
		$model->amount =  $tradeInfo['amount'];
		$model->balance = DepositAccountModel::updateDepositMoney($tradeInfo['buyer_id'], $tradeInfo['amount'], 'add', $fundtype);
		$model->tradeType = $this->_tradeType;
		$model->fundtype = $fundtype;
		$model->flow = $this->_flow;
		$model->name = Language::get($this->_tradeType);
		$model->created = Timezone::gmtime();

		if (!$model->save()) {
			$this->errors = Language::get('trade_fail');
			return false;
		}

		// 充值返现
		if ($fundtype == 'money') {
			$transaction = Yii::$app->db->beginTransaction();
			try {
				if (!$this->rebate($tradeInfo)) {
					throw new \Exception(Language::get('handle_fail'));
				}
				$transaction->commit();
				return true;
			} catch (\Exception $e) {
				$transaction->rollBack();
				return false;
			}
		}
	}

	/**
	 * 充值到钱包返钱
	 * 调整为按充值设置的饭钱金额饭钱，而不是按比率[先保留]
	 */
	public function rebate($tradeInfo = [])
	{
		// 如果充值返金额比例为零，则不处理
		// $rate = floatval(DepositSettingModel::getDepositSetting($tradeInfo['buyer_id'], 'regive_rate'));
		// $amount = round($tradeInfo['amount'] * $rate, 2);
		// if (!$rate || $amount <= 0) {
		// 	return true;
		// }

		// 如果已返过，则不处理
		if (DepositTradeModel::find()->where(['bizOrderId' => $tradeInfo['tradeNo'], 'bizIdentity' => Def::TRADE_REGIVE])->exists()) {
			return true;
		}

		// 根据充值金额获取返钱金额
		$amount = RechargeSettingModel::find()->select('reward')->where(['money' => $tradeInfo['amount']])->scalar();
		if (!$amount || $amount <= 0) return true;

		// 增加交易记录
		$data_trade = array(
			'tradeNo'		=> DepositTradeModel::genTradeNo(),
			'payTradeNo'	=> DepositTradeModel::genPayTradeNo(),
			'bizOrderId'	=> $tradeInfo['tradeNo'],
			'bizIdentity'	=> Def::TRADE_REGIVE,
			'buyer_id'		=> $tradeInfo['buyer_id'],
			'seller_id'		=> 0,
			'amount'		=> $amount,
			'status'		=> 'SUCCESS',
			'payment_code'	=> 'deposit',
			'payType'		=> $this->_payType,
			'flow'     		=> $this->_flow,
			'title'			=> Language::get('recharge_give'),
			'buyer_remark'	=> '',
			'add_time'		=> Timezone::gmtime(),
			'pay_time'		=> Timezone::gmtime(),
			'end_time'		=> Timezone::gmtime()
		);

		$model = new DepositTradeModel();
		foreach ($data_trade as $key => $value) {
			$model->$key = $value;
		}

		if ($model->save(false) == true) {
			$data_trade['userid'] = $data_trade['buyer_id'];
			$data_trade['tradeType'] = 'REGIVE';
			$data_trade['name'] = $data_trade['title'];
			$extra_info['tradeNo'] = $data_trade['tradeNo'];
			return parent::insertRecordInfo($data_trade, $extra_info);
		}

		return false;
	}
}
