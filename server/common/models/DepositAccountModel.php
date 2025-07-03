<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace common\models;

use Yii;
use yii\db\ActiveRecord;
use yii\web\Response;

use common\models\DepositRecordModel;
use common\models\UserModel;

use common\library\Basewind;
use common\library\Timezone;
use common\library\Language;
use common\library\Message;

/**
 * @Id DepositAccountModel.php 2018.4.5 $
 * @author mosir
 */

class DepositAccountModel extends ActiveRecord
{
	/**
	 * @inheritdoc
	 */
	public static function tableName()
	{
		return '{{%deposit_account}}';
	}

	// 关联表
	public function getUser()
	{
		return parent::hasOne(UserModel::className(), ['userid' => 'userid']);
	}

	public static function checkAccount($account, $userid = 0)
	{
		if (empty($account)) {
			return false;
		}
		if (parent::find()->select('account_id')->where(['account' => $account])->andWhere(['<>', 'userid', $userid])->one()) {
			return false;
		}
		return true;
	}

	public static function getAccountInfo($userid = 0)
	{
		if (!$userid) return false;

		if (!($model = parent::find()->where(['userid' => $userid])->one())) {
			$query = UserModel::find()->select('userid,email,phone_mob,username')->where(['userid' => $userid])->one();

			$model = new DepositAccountModel();
			$model->userid = $userid;
			$model->account = self::createAccountName($query);
			$model->money = 0;
			$model->frozen = 0;
			$model->bond = 0;
			$model->nodrawal = 0;
			$model->password = md5('123456');
			$model->real_name = $query->username;
			$model->pay_status = 'ON';
			$model->add_time = Timezone::gmtime();
			$model->last_update = Timezone::gmtime();
			if (!$model->save()) {
				return false;
			}
		}

		return $model;
	}

	private static function createAccountName($userInfo = null, $random = false)
	{
		$account = null;
		if (!$random) {
			if ($userInfo->phone_mob) {
				$account = $userInfo->phone_mob;
			} elseif ($userInfo->email) {
				$account = $userInfo->email;
			}
			if (!isset($account) || !$account) {
				return self::createAccountName($userInfo, true);
			}
		} else {
			$account = Timezone::gmtime() . '@qq.com';
		}

		if (!self::checkAccount($account)) {
			return self::createAccountName($userInfo, true);
		}
		return $account;
	}

	public static function checkEnoughMoney($money, $userid = 0, $field = 'money')
	{
		if (empty($money) || !$userid) return false;

		if (!($query = parent::find()->select($field)->where(['userid' => $userid])->one())) {
			return false;
		}

		return ($query->$field >= $money);
	}

	/** 
	 * 验证账户密码，参数可选 userid|account
	 */
	public static function checkAccountPassword($password, $param = 0)
	{
		$query = parent::find()->select('account_id')->where(['password' => md5($password)]);
		if (is_numeric($param)) {
			$query->andWhere(['userid' => intval($param)]);
		} elseif (Basewind::isEmail($param) || Basewind::isPhone($param)) {
			$query->andWhere(['account' => $param]);
		}

		if (Yii::$app->request->isAjax) {
			Yii::$app->response->format = Response::FORMAT_JSON;
		}
		return $query->exists() ? true : false;
	}

	/**
	 * 更新账户余额/冻结金额/保证金，并返回最新的值
	 * @param string $field money|frozen|bond
	 * @param string $change  add|reduce
	 */
	public static function updateDepositMoney($userid = 0, $amount = 0, $change = 'add', $field = 'money')
	{
		if (($model = self::getAccountInfo($userid))) {
			if ($amount > 0) {
				$model->updateCounters([$field => ($change == 'add') ? $amount : -$amount]);
			}
			return floatval($model->$field);
		}

		return false;
	}

	/* 下载某个月的对账单 */
	public static function downloadbill($userid = 0, $month = '', $fundtype = 'money')
	{
		if (empty($month) || !$userid) {
			return Message::warning(Language::get('downloadbill_fail'));
		}

		list($beginMonth, $endMonth) = Timezone::getMonthDay($month);

		$monthbill = DepositRecordModel::find()->alias('dr')
			->select('dr.*,dt.bizOrderId,dt.title,dt.buyer_id,dt.seller_id,dt.payment_code,dt.end_time')
			->joinWith('depositTrade dt', false)
			->where(['userid' => $userid, 'status' => 'SUCCESS', 'fundtype' => $fundtype])
			->andWhere(['>=', 'end_time', $beginMonth])
			->andWhere(['<=', 'end_time', $endMonth])
			->orderBy(['record_id' => SORT_ASC])
			->asArray()->all();

		if (!$monthbill) {
			return Message::warning(Language::get('downloadbill_fail'));
		}

		// xls文件数组
		$record_xls = [];

		$lang_bill = array(
			'end_time'		=>  '日期',
			'tradeNo' 		=> 	'交易号',
			'bizOrderId'	=> 	'商户订单号',
			'other_account' => 	'对方账号',
			'income_money' 	=> 	'收入金额（+元）',
			'outlay_money' 	=> 	'支出金额（-元）',
			'balance'		=>	'账户余额（元）',
			'payment_code'  =>  '支付方式',
			'title' 		=> 	'交易标题',
			'remark'		=>	'备注',
		);
		$record_xls[] = array_values($lang_bill);
		$depositAccount = self::getAccountInfo($userid);
		$folder = 'bill_' . Timezone::localDate('Ym', $beginMonth) . '_' . $depositAccount['account'];

		$bill_value = [];
		foreach ($lang_bill as $key => $val) {
			$bill_value[$key] = '';
		}
		foreach ($monthbill as $key => $bill) {
			$bill_value['end_time']		= Timezone::localDate('Y-m-d H:i:s', $bill['end_time']);
			$bill_value['tradeNo']		= $bill['tradeNo'];
			$bill_value['bizOrderId']	= $bill['bizOrderId'];
			$bill_value['balance']		= $bill['balance'];
			$bill_value['payment_code'] = Language::get($bill['payment_code']);
			$bill_value['title']		= $bill['title'];
			$bill_value['remark']   	= $bill['remark'];

			if ($bill['flow'] == 'income') {
				$bill_value['outlay_money'] = 0;
				$bill_value['income_money']	= $bill['amount'];
			} else {
				$bill_value['income_money'] = 0;
				$bill_value['outlay_money'] = $bill['amount'];
			}

			// 交易的对方信息
			$partyInfo = DepositTradeModel::getPartyInfoByRecord($userid, $bill);
			if ($partyInfo) {
				$bill_value['other_account'] = $partyInfo['name'];
			}

			$record_xls[] = $bill_value;
		}

		return \common\library\Page::export([
			'models' 	=> $record_xls,
			'filename' 	=> $folder,
		]);
	}
}
