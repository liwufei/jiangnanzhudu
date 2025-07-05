<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace backend\models;

use Yii;
use yii\base\Model;

use common\library\Language;
use common\library\Timezone;

/**
 * @Id DepositDrawExportForm.php 2018.8.3 $
 * @author mosir
 */
class DepositDrawExportForm extends Model
{
	public $errors = null;

	public static function download($list)
	{
		// 文件数组
		$record_xls = array();
		$lang_bill = array(
			'tradeNo'			=> '交易号',
			'orderId'			=> '商户订单号',
			'drawtype'			=> '提现渠道',
			'name'				=> '收款人姓名',
			'account' 			=> '收款账号',
			'amount'			=> '提现金额',
			'status'			=> '状态',
			'remark'			=> '提现备注',
			'add_time' 			=> '申请时间',
			'end_time'			=> '完成时间',
		);
		$record_xls[] = array_values($lang_bill);
		$folder = 'DRAW_' . Timezone::localDate('Ymdhis', Timezone::gmtime());

		$record_value = array();
		foreach ($list as $key => $value) {
			foreach ($lang_bill as $k => $v) {

				if (in_array($k, ['add_time', 'end_time'])) {
					$value[$k] = Timezone::localDate('Y/m/d H:i:s', $value[$k]);
				}
				if ($k == 'drawtype') {
					$value[$k] = $value[$k] == 'alipay' ? '支付宝' : ($value[$k] == 'wxpay' ? '微信零钱' : '银行卡');
				}
				if ($k == 'account') {
					$value[$k] = $value['bank'] ? $value['bank'] . '(' . $value[$k] . ')' : $value[$k];
				}

				$record_value[$k] = $value[$k] ? $value[$k] : '';
			}

			$record_value['orderId'] = '\'' . $value['orderId'];
			$record_value['tradeNo'] = '\'' . $value['tradeNo'];
			$record_value['status'] = Language::get(strtolower($value['status']));

			$record_xls[] = $record_value;
		}

		return \common\library\Page::export([
			'models' 	=> $record_xls,
			'filename' 	=> $folder,
		]);
	}
}
