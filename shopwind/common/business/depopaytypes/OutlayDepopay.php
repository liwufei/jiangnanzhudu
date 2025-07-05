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
use common\library\Language;
use common\library\Timezone;
use common\business\BaseDepopay;

/**
 * @Id OutlayDepopay.php 2018.4.15 $
 * @author mosir
 */
 
class OutlayDepopay extends BaseDepopay
{
	/**
	 * 资金流出交易
	 */
    protected $_flow = 'outlay';

	/**
	 * 支付类型，值有：即时到帐：INSTANT；担保交易：SHIELD；货到付款：COD
	 */
	public $_payType   	= 'INSTANT';

	/**
	 * 针对财务明细的资金用途，值有：在线支付：PAY；充值：RECHARGE；提现：WITHDRAW；服务费：SERVICE；转账：TRANSFER；返现：REGIVE；扣费：CHARGE
	 */
	public $_tradeType = 'PAY';
	
	public function handleTradeInfo($trade_info, $extra_info = [])
	{
		// 只有余额支付（且不是退款交易），才需验证金额是否足够
		if($extra_info['payment_code'] !== 'deposit' || $extra_info === false) {
			return true;
		}
		
		// 验证是否有足够的金额用于支出
		if(isset($trade_info['amount'])) {
			
			$money = $trade_info['amount'];
			if($money < 0) {
				$this->setErrors("50002");
				return false;
			}
			
			// 如果需要扣服务费，则加上服务费后再验证
			if(isset($trade_info['fee'])) {
				if($trade_info['fee'] < 0) {
					$this->setErrors("50001");
					return false;
				}
				$money += $trade_info['fee'];
			}
			
			if(!DepositAccountModel::checkEnoughMoney($money, $trade_info['userid'])) {
				$this->setErrors("50019");
				return false;
			}
		}
		return true;
	}

	/**
	 * 插入支出（扣款）记录，并变更账户余额
	 */
	public function insertRecordInfo($trade_info, $extra_info, $fundtype = 'money')
	{	
		// 加此判断，目的为允许提交订单金额为零的处理
		if($trade_info['amount'] == 0) {
			return true;
		}

		$data_record = array(
			'tradeNo'		=>	$extra_info['tradeNo'],
			'userid'		=>	$trade_info['userid'],
			'amount'		=> 	$trade_info['amount'],
			'balance'		=>	DepositAccountModel::updateDepositMoney($trade_info['userid'],  $trade_info['amount'], 'reduce', $fundtype), // 同时更新余额
			'tradeType'		=>  $this->_tradeType,
			'fundtype'		=>	$fundtype,
			'flow'			=>	$this->_flow,
			'name'			=>  isset($trade_info['name']) ? $trade_info['name'] : Language::get($this->_tradeType),
			'created'		=>  Timezone::gmtime()
		);

		// 插入支出记录
		return parent::insertDepositRecord($data_record, false);
	}
}