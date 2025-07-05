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

use common\library\Language;
use common\library\Timezone;
use common\library\Def;

/**
 * @Id TransferDepopay.php 2018.4.15 $
 * @author mosir
 */

class TransferDepopay extends OutlayDepopay
{
	/**
	 * 针对财务明细的资金用途，值有：在线支付：PAY；充值：RECHARGE；提现：WITHDRAW；服务费：SERVICE；转账：TRANSFER；返现：REGIVE；扣费：CHARGE
	 */
    public $_tradeType = 'TRANSFER';
	
	public function submit($data = [])
	{
        extract($data);
		
        // 处理交易基本信息
        $base_info = parent::handleTradeInfo($trade_info, $extra_info);
        if (!$base_info) {
            return false;
        }
	
		//$tradeNo = $extra_info['tradeNo'];
		
		// 开始插入收支记录
		if(!$this->insertRecordInfo($trade_info, $extra_info)) {
			return false;
		}
		
		// 如果有转账手续费，则扣除转出账户的手续费
		if($transfer_rate = DepositSettingModel::getDepositSetting($trade_info['userid'], 'transfer_rate')) {
			if(!parent::sysChargeback($extra_info['tradeNo'], $trade_info, $transfer_rate, 'transfer_fee')) {
				$this->setErrors("50015");
				return false;
			}
		}
					
		return true;
	}
	
	/* 插入收支记录，并变更账户余额 */
	public function insertRecordInfo($trade_info, $extra_info)
	{
		$time = Timezone::gmtime();
		$bizOrderId	= DepositTradeModel::genTradeNo(12, 'bizOrderId');
		
		$data_trade = array(
			'tradeNo'		=>	$extra_info['tradeNo'],
			'bizOrderId'	=>  $bizOrderId,
			'bizIdentity'	=>  Def::TRADE_TRANS,
			'buyer_id'		=>	$trade_info['userid'],
			'seller_id'		=>	$trade_info['party_id'],
			'amount'		=>	$trade_info['amount'],
			'status'		=>	'SUCCESS',
			'payment_code'  =>  'deposit',
			'payType'		=>  $this->_payType,
			'flow'			=>	$this->_flow,
			'title'			=>	Language::get(strtolower($this->_tradeType)),
			'buyer_remark'	=>	$this->post->remark ? $this->post->remark : '',
			'add_time'		=>	$time,
			'pay_time'		=>	$time,
			'end_time'		=>	$time
		);
		
		$model = new DepositTradeModel();
		foreach($data_trade as $key => $val) {
			$model->$key = $val;
		}
		
		if($model->save(false) == true)
		{
			// 转出记录
			if(parent::insertRecordInfo($trade_info, $extra_info))
			{
				// 转入的账户
				$data_record['tradeNo']			= $extra_info['tradeNo'];
				$data_record['userid'] 			= $trade_info['party_id'];
				$data_record['balance']			= DepositAccountModel::updateDepositMoney($trade_info['party_id'], $trade_info['amount']); // 增加后的余额
				$data_record['tradeType']		= $this->_tradeType;
				$data_record['flow']			= 'income';
				$data_record['fundtype']		= 'money';
				$data_record['amount'] 			= $trade_info['amount'];
				$data_record['created']			= Timezone::gmtime();

				if(!parent::insertDepositRecord($data_record, false)) {
					$this->setErrors("50012");
					return false;
				}
				
				return true;
			}
			else
			{
				$this->setErrors("50011");
				return false;
			}
		}
	}
}