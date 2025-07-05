<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace common\plugins\payment\deposit;


use yii;
use yii\helpers\Url;

use common\models\DepositTradeModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Def;

use common\plugins\BasePayment;

/**
 * @Id deposit.plugin.php 2018.7.23 $
 * @author mosir
 */

class Deposit extends BasePayment
{
	/**
	 * 网关地址
	 * @var string $gateway
	 */
	public $gateway;

	/**
	 * 支付插件实例
	 * @var string $code
	 */
	protected $code = 'deposit';

	/* 获取支付表单 */
	public function pay($orderInfo = array())
	{
		// 支付网关商户订单号
		$payTradeNo = $this->getPayTradeNo($orderInfo);

		// 因为是余额支付，所以直接处理业务
		if ($this->payNotify($payTradeNo) === false) {
			if (!$this->errors) $this->errors = Language::get('pay_fail');
			return false;
		}

		return ['status' => 'SUCCESS', 'payTradeNo' => $payTradeNo];
	}

	public function payNotify($payTradeNo = '')
	{
		if (empty($payTradeNo)) {
			$this->errors = Language::get('order_info_empty');
			return false;
		}
		if (!($orderInfo = DepositTradeModel::getTradeInfoForNotify($payTradeNo))) {
			$this->errors = Language::get('order_info_empty');
			return false;
		}

		if (in_array($orderInfo['bizIdentity'], array(Def::TRADE_ORDER))) {
			return parent::handleOrderAfterNotify($orderInfo, ['target' => Def::ORDER_ACCEPTED]);
		} elseif (in_array($orderInfo['bizIdentity'], array(Def::TRADE_BUYAPP))) {
			return parent::handleBuyappAfterNotify($orderInfo, ['target' => Def::ORDER_ACCEPTED]);
		}
		return true;
	}
}
