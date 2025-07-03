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

use common\models\UserModel;

use common\library\Plugin;
use common\library\Language;
use common\library\Timezone;

/**
 * @Id DepositRechargeExportForm.php 2018.8.3 $
 * @author mosir
 */
class DepositRechargeExportForm extends Model
{
	public $errors = null;

	public static function download($list)
	{
		// 文件数组
		$record_xls = array();
		$lang_bill = array(
			'end_time' 		=> '充值时间',
			'tradeNo' 		=> '交易号',
			'orderId' 		=> '商户订单号',
			'username' 		=> '充值用户',
			'phone_mob'		=> '用户手机号',
			'amount' 		=> '充值金额',
			'method'		=> '充值方式',
			'status' 		=> '状态',
			'reamrk'		=> '充值备注',
			'examine' 		=> '操作员',
		);
		$record_xls[] = array_values($lang_bill);
		$folder = 'RECHARGE_' . Timezone::localDate('Ymdhis', Timezone::gmtime());

		$record_value = array();
		foreach ($list as $key => $value) {
			foreach ($lang_bill as $k => $v) {
				if ($k == 'method') {
					if (($client = Plugin::getInstance('payment')->build($value['payment_code']))) {
						if ($payment = $client->getInfo()) {
							$value[$k] = $payment['name'];
						}
					}
				}
				$record_value[$k] = $value[$k] ? $value[$k] : '';
			}

			$record_value['end_time'] = Timezone::localDate('Y/m/d H:i:s', $value['end_time']);
			$record_value['orderId'] = '\'' . $value['orderId'];
			$record_value['tradeNo'] = '\'' . $value['tradeNo'];
			$record_value['status'] = Language::get(strtolower($value['status']));
			if ($array = UserModel::find()->select('username,phone_mob')->where(['userid' => $value['userid']])->asArray()->one()) {
				$record_value = array_merge($record_value, $array);
			}

			$record_xls[] = $record_value;
		}

		return \common\library\Page::export([
			'models' 	=> $record_xls,
			'filename' 	=> $folder,
		]);
	}
}
