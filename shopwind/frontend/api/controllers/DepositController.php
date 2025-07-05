<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace frontend\api\controllers;

use Yii;

use common\models\UserModel;
use common\models\OrderModel;
use common\models\StoreModel;
use common\models\OrderGoodsModel;
use common\models\DepositAccountModel;
use common\models\DepositTradeModel;
use common\models\DepositRecordModel;
use common\models\DepositSettingModel;
use common\models\DepositWithdrawModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Timezone;
use common\library\Page;
use common\library\Def;
use common\library\Plugin;

use frontend\api\library\Respond;
use frontend\api\library\Formatter;

/**
 * @Id DepositController.php 2018.11.15 $
 * @author yxyc
 */

class DepositController extends \common\base\BaseApiController
{
	/**
	 * 读取用户资产信息
	 * @api 接口访问地址: https://www.xxx.com/api/deposit/read
	 */
	public function actionRead()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(true)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true);

		$model = UserModel::find()->select('userid,username')->where(['userid' => Yii::$app->user->id]);
		if (!($record = $model->asArray()->one())) {
			return $respond->output(Respond::HANDLE_INVALID, Language::get('handle_exception'));
		}

		if (!($query = DepositAccountModel::getAccountInfo($record['userid']))) {
			return $respond->output(Respond::HANDLE_INVALID, Language::get('handle_exception'));
		}

		foreach ($query as $key => $value) {
			if (in_array($key, ['account', 'money', 'bond', 'nodrawal', 'frozen', 'pay_status'])) {
				$record[$key] = $key == 'pay_status' ? ($value == 'ON' ? 1 : 0) : $value;
			}
		}

		return $respond->output(true, null, $record);
	}

	/**
	 * 更新用户资产信息
	 * @api 接口访问地址: https://www.xxx.com/api/deposit/update
	 */
	public function actionUpdate()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(true)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['pay_status']);
		$post->pay_status = $post->pay_status ? 'ON' : 'OFF';

		// 修改支付密码，才用验证手机短信
		if (isset($post->password)) {
			// 手机短信验证
			if (!($smser = Plugin::getInstance('sms')->autoBuild())) {
				return $respond->output(Respond::HANDLE_INVALID, Language::get('handle_exception'));
			}

			// 兼容微信session不同步问题
			if ($post->verifycodekey) {
				$smser->setSessionByCodekey($post->verifycodekey);
			}

			$post->code = $post->verifycode;
		}

		$model = new \frontend\home\models\DepositConfigForm();
		if (!$model->save($post, isset($post->password) ? true : false)) {
			return $respond->output(Respond::PARAMS_INVALID, $model->errors);
		}
		return $respond->output(true, null);
	}

	/**
	 * 获取交易记录信息
	 * @api 接口访问地址: https://www.xxx.com/api/deposit/tradelist
	 */
	public function actionTradelist()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(true)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['frozen', 'page', 'page_size']);

		$query = DepositTradeModel::find()->select('trade_id,tradeNo,outTradeNo,payTradeNo,bizOrderId,bizIdentity,buyer_id,seller_id,amount,status,payment_code,flow,title,buyer_remark,seller_remark,add_time,pay_time,end_time')
			->where(['or', ['buyer_id' => Yii::$app->user->id], ['seller_id' => Yii::$app->user->id]])
			->orderBy(['trade_id' => SORT_DESC]);

		if ($post->tradeNo) {
			$query->andwhere(['tradeNo' => $post->tradeNo]);
		}
		if ($post->bizOrderId) {
			$query->andwhere(['bizOrderId' => $post->bizOrderId]);
		}
		if ($post->keyword) {
			$query->andwhere(['like', 'title', $post->keyword]);
		}
		if ($post->flow) {
			$query->andwhere(['flow' => $post->flow]);
		}
		if ($post->bizIdentity) {
			$query->andWhere(['bizIdentity' => $post->bizIdentity]);
		}

		// 如果查询的是冻结的记录
		// 目前只冻结待审核的提现，如果还有其他类型的冻结交易，则加到此
		if ($post->frozen) {
			$query->andwhere(['bizIdentity' => 'DRAW', 'status' => 'VERIFY']);
		}

		if ($post->status) {
			$query->andWhere(['status' => $post->status]);
		}
		// 获取指定的时间段的订单
		if ($post->begin) {
			$query->andWhere(['>=', 'add_time', Timezone::gmstr2time($post->begin)]);
		}
		if ($post->end) {
			$query->andWhere(['<=', 'add_time', Timezone::gmstr2time($post->end)]);
		}

		$page = Page::getPage($query->count(), $post->page_size, false, $post->page);
		$list = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
		foreach ($list as $key => $value) {
			$list[$key] = $this->formatDate($value);
			$list[$key]['payment_name'] = Language::get($value['payment_code']);

			if ($value['seller_id'] == Yii::$app->user->id) {
				$list[$key]['flow'] = ($value['flow'] == 'income') ?  'outlay' : 'income';
			}
			if ($value['buyer_id']) {
				$identity = UserModel::find()->select('portrait,username')->where(['userid' => $value['buyer_id']])->one();
				$list[$key]['buyer_portrait'] = Formatter::path($identity->portrait, 'portrait');
				$list[$key]['buyer_name'] = $identity->username;
			}
			if ($value['seller_id']) {
				$identity = UserModel::find()->select('portrait,username')->where(['userid' => $value['seller_id']])->one();
				$list[$key]['seller_portrait'] = Formatter::path($identity->portrait, 'portrait');
				$list[$key]['seller_name'] = $identity->username;
				$list[$key]['store_name'] = StoreModel::find()->select('store_name')->where(['store_id' => $value['seller_id']])->scalar();
			}
		}

		$this->params = ['list' => $list, 'pagination' => Page::formatPage($page, false)];
		return $respond->output(true, null, $this->params);
	}

	/**
	 * 获取交易单条记录信息
	 * @api 接口访问地址: https://www.xxx.com/api/deposit/trade
	 */
	public function actionTrade()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(true)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['trade_id']);

		if (!isset($post->trade_id) && !isset($post->tradeNo)) {
			return $respond->output(Respond::PARAMS_INVALID, Language::get('trade_id and tradeNo empty'));
		}
		$query = DepositTradeModel::find()->select('trade_id,tradeNo,outTradeNo,payTradeNo,bizOrderId,bizIdentity,buyer_id,seller_id,amount,status,payment_code,flow,title,buyer_remark,seller_remark,add_time,pay_time,end_time')->where(['or', ['buyer_id' => Yii::$app->user->id], ['seller_id' => Yii::$app->user->id]])->orderBy(['trade_id' => SORT_DESC]);
		if (isset($post->trade_id)) {
			$query->andWhere(['trade_id' => $post->trade_id]);
		}
		if (isset($post->tradeNo)) {
			$query->andWhere(['tradeNo' => $post->tradeNo]);
		}
		if (!($record = $query->asArray()->one())) {
			return $respond->output(Respond::RECORD_NOTEXIST, Language::get('trade record empty'));
		}
		if ($record['seller_id'] == Yii::$app->user->id) {
			$record['flow'] = ($record['flow'] == 'income') ?  'outlay' : 'income';
		}
		// 商品订单，读取商品信息
		if ($record['bizIdentity'] == Def::TRADE_ORDER) {
			$orderInfo = OrderModel::find()->select('order_id,order_sn,order_amount')->where(['order_sn' => $record['bizOrderId']])->asArray()->one();
			if ($orderInfo) {
				$items = OrderGoodsModel::find()->select('spec_id,order_id,goods_id,goods_name,goods_image,specification,price,quantity')->where(['order_id' => $orderInfo['order_id']])->asArray()->all();
				foreach ($items as $key => $value) {
					$items[$key]['goods_image'] = Formatter::path($value['goods_image'], 'goods');
				}
				$orderInfo['items'] = $items;
			}
			$record['orderInfo'] = $orderInfo;
		}

		$record = $this->formatDate($record);
		$record['payment_name'] = Language::get($record['payment_code']);

		return $respond->output(true, null, $record);
	}

	/**
	 * 获取收支明细列表信息
	 * @api 接口访问地址: https://www.xxx.com/api/deposit/recordlist
	 */
	public function actionRecordlist()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(true)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['page', 'page_size']);
		if (!isset($post->fundtype)) $post->fundtype = 'money';

		$query = DepositRecordModel::find()->alias('dr')
			->select('dr.record_id,dr.userid,dr.amount,dr.balance,dr.flow,dr.name,dt.title,dt.tradeNo,dt.bizOrderId,dt.bizIdentity,dt.add_time,dt.pay_time,dt.end_time,u.username,u.nickname,u.portrait')
			->joinWith('depositTrade dt', false)->joinWith('user u', false)
			->where(['dr.userid' => Yii::$app->user->id, 'dr.fundtype' => $post->fundtype])
			->orderBy(['record_id' => SORT_DESC]);
		if ($post->bizIdentity) {
			$query->andWhere(['bizIdentity' => $post->bizIdentity]);
		}
		if ($post->tradeNo) {
			$query->andWhere(['tradeNo' => $post->tradeNo]);
		}
		if ($post->tradeType) {
			$query->andWhere(['in', 'tradeType', explode(',', $post->tradeType)]);
		}
		if ($post->flow) {
			$query->andWhere(['dr.flow' => $post->flow]);
		}
		// 获取指定的时间段的订单
		if ($post->begin) {
			$query->andWhere(['>=', 'created', Timezone::gmstr2time($post->begin)]);
		}
		if ($post->end) {
			$query->andWhere(['<=', 'created', Timezone::gmstr2time($post->end)]);
		}

		$page = Page::getPage($query->count(), $post->page_size, false, $post->page);
		$list = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
		foreach ($list as $key => $value) {
			$list[$key] = $this->formatDate($value);
			$list[$key]['portrait'] = Formatter::path($value['portrait'], 'portrait');
		}
		$this->params = ['list' => $list, 'pagination' => Page::formatPage($page, false)];
		return $respond->output(true, null, $this->params);
	}

	/**
	 * 获取收支明细单条信息
	 * @api 接口访问地址: https://www.xxx.com/api/deposit/record
	 */
	public function actionRecord()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(true)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['record_id']);
		if (!isset($post->fundtype)) $post->fundtype = 'money';

		$query = DepositRecordModel::find()->alias('dr')
			->select('dr.record_id,dr.userid,dr.amount,dr.balance,dr.flow,dr.name,dr.created,dt.title,dt.tradeNo,dt.bizOrderId,dt.bizIdentity,dt.add_time,dt.pay_time,dt.end_time,u.username,u.nickname,u.portrait')
			->joinWith('depositTrade dt', false)
			->joinWith('user u', false)
			->where(['and', ['dr.userid' => Yii::$app->user->id, 'dr.fundtype' => $post->fundtype], ['record_id' => $post->record_id]])
			->orderBy(['record_id' => SORT_DESC]);

		if (!($record = $query->asArray()->one())) {
			return $respond->output(Respond::RECORD_NOTEXIST, Language::get('no such record'));
		}

		$record = $this->formatDate($record);
		$record['portrait'] = Formatter::path($record['portrait'], 'portrait');

		return $respond->output(true, null, $record);
	}

	/**
	 * 充值到钱包|保证金
	 * @api 接口访问地址: https://www.xxx.com/api/deposit/recharge
	 */
	public function actionRecharge()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(true)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true);
		$post->payment_code = $respond->getRealPaymentcode($post);

		$model = new \frontend\home\models\DepositRechargeForm();
		list($tradeNo, $extra) = $model->formData($post);
		if ($model->errors) {
			return $respond->output(Respond::PARAMS_INVALID, $model->errors);
		}

		// 获取交易数据
		list($errorMsg, $orderInfo) = DepositTradeModel::checkAndGetTradeInfo($tradeNo, Yii::$app->user->id);
		if ($errorMsg !== false) {
			return $respond->output(Respond::PARAMS_INVALID, $model->errors);
		}

		// 生成支付参数
		$payment = Plugin::getInstance('payment')->build($post->payment_code, $post);
		$payform = $payment->pay($orderInfo);
		if ($payform === false) {
			return $respond->output(Respond::PARAMS_INVALID, $payment->errors);
		}

		return $respond->output(true, null, $payform);
	}

	/**
	 * 钱包提现
	 * @api 接口访问地址: https://www.xxx.com/api/deposit/drawal
	 */
	public function actionDrawal()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(true)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['id']);

		$model = new \frontend\home\models\DepositWithdrawconfirmForm();
		if (!($tradeNo = $model->save($post))) {
			return $respond->output(Respond::PARAMS_INVALID, $model->errors);
		}

		// $trade = DepositTradeModel::find()->select('title,bizOrderId,payTradeNo,amount')->where(['tradeNo' => $tradeNo])->one();
		// $drawal = DepositWithdrawModel::find()->select('name,account,drawtype,terminal,fee')->where(['orderId' => $trade->bizOrderId])->asArray()->one();

		// if ($drawal) {
		// 	$code = $this->getCode($drawal['drawtype'], $drawal['terminal']);
		// 	$payment = Plugin::getInstance('payment')->build($code);
		// 	if (!in_array($code, ['alipay', 'wxpay', 'wxapppay', 'wxmppay']) || !($payment_info = $payment->getInfo()) || !$payment_info['enabled']) {
		// 		return $respond->output(Respond::HANDLE_INVALID, Language::get('interface_disabled'));
		// 		return false;
		// 	}

		// 	$params['amount'] = $trade->amount - $drawal->fee;
		// 	$params['payTradeNo'] = $trade->payTradeNo ? $trade->payTradeNo : DepositTradeModel::genPayTradeNo();
		// 	$params['title'] = $trade->title;
		// 	$params['payee'] = $drawal;

		// 	if (!$payment->transfer($params)) {
		// 		return $respond->output(Respond::HANDLE_INVALID, $payment->errors ? $payment->errors : Language::get('transfer_fail'));
		// 		return false;
		// 	}
		// }

		return $respond->output(true, null, ['tradeNo' => $tradeNo]);
	}

	/**
	 * 获取不同的提现终端的CODE值
	 * 主要是针对提现至微信零钱场景，不同的终端对应不同的OPENID值
	 */
	private function getCode($drawtype = '', $terminal = '')
	{
		if ($drawtype == 'wxpay') {

			if ($terminal == 'APP') {
				return 'wxapppay';
			}
			if ($terminal == 'MP') {
				return 'wxmppay';
			}
		}

		return $drawtype;
	}

	/**
	 * 获取资金配置
	 * @api 接口访问地址: https://www.xxx.com/api/deposit/setting
	 */
	public function actionSetting()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(true)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['userid']);
		$record = DepositSettingModel::getDepositSetting($post->userid);
		return $respond->output(true, null, $record);
	}

	/**
	 * 格式化时间
	 */
	private function formatDate($record)
	{
		$fields = ['add_time', 'pay_time', 'end_time', 'created'];
		foreach ($fields as $field) {
			isset($record[$field]) && $record[$field] = Timezone::localDate('Y-m-d H:i:s', $record[$field]);
		}
		return $record;
	}
}
