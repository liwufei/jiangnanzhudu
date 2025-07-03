<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace backend\controllers;

use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;

use common\models\UserModel;
use common\models\DepositTradeModel;
use common\models\DepositRecordModel;
use common\models\DepositWithdrawModel;
use common\models\DepositRechargeModel;
use common\models\DepositAccountModel;
use common\models\DepositSettingModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Message;
use common\library\Resource;
use common\library\Page;
use common\library\Timezone;
use common\library\Plugin;

/**
 * @Id DepositController.php 2018.8.3 $
 * @author mosir
 */

class DepositController extends \common\base\BaseAdminController
{
	public function actionIndex()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['limit', 'page']);

		if (!Yii::$app->request->isAjax) {
			$this->params['filtered'] = $this->getConditions($post);
			$this->params['search_options'] = $this->getSearchOption();
			$this->params['pay_status_list'] = $this->getPayStatus();

			$this->params['_foot_tags'] = Resource::import([
				'script' => 'javascript/inline_edit.js,javascript/jquery.ui/jquery.ui.js,javascript/jquery.ui/i18n/' . Yii::$app->language . '.js',
				'style' => 'javascript/jquery.ui/themes/smoothness/jquery.ui.css'
			]);

			$this->params['page'] = Page::seo(['title' => Language::get('account_list')]);
			return $this->render('../deposit.index.html', $this->params);
		} else {
			$query = DepositAccountModel::find()->select('account_id,account,money,frozen,pay_status,add_time,userid');
			$query = $this->getConditions($post, $query)->orderBy(['account_id' => SORT_DESC]);

			$page = Page::getPage($query->count(), $post->limit ? $post->limit : 10);
			$list = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
			foreach ($list as $key => $value) {
				$list[$key]['add_time'] = Timezone::localDate('Y-m-d H:i:s', $value['add_time']);

				if (($username = UserModel::find()->select('username')->where(['userid' => $value['userid']])->scalar())) {
					$list[$key]['username'] = $username;
				}
				$list[$key]['trade_rate'] = DepositSettingModel::getDepositSetting($value['userid'], 'trade_rate');
			}

			return Json::encode(['code' => 0, 'msg' => '', 'count' => $query->count(), 'data' => $list]);
		}
	}

	public function actionEdit()
	{
		$id = intval(Yii::$app->request->get('id'));
		if (!$id || !($account = DepositAccountModel::findOne($id))) {
			return Message::warning(Language::get('no_such_account'));
		}

		if (!Yii::$app->request->isPost) {
			$this->params['account'] = ArrayHelper::toArray($account);
			$this->params['page'] = Page::seo(['title' => Language::get('deposit_account')]);
			return $this->render('../deposit.account.html', $this->params);
		} else {
			$post = Basewind::trimAll(Yii::$app->request->post(), true);

			$model = new \backend\models\DepositAccountForm(['account_id' => $id]);
			if (!$model->save($post, true)) {
				return Message::warning($model->errors);
			}
			return Message::display(Language::get('edit_ok'), ['deposit/index']);
		}
	}

	/**
	 * 目前只有用户不存在了，才允许删除
	 */
	public function actionDelete()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true);
		foreach (explode(',', $post->id) as $id) {
			if ($id && ($model = DepositAccountModel::findOne($id)) && !UserModel::findOne($model->userid)) {
				if (!$model->delete()) {
					return Message::warning($model->errors);
				}
			}
		}
		return Message::display(Language::get('drop_ok'));
	}

	/* 异步修改数据 */
	public function actionEditcol()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['id']);
		if (in_array($post->column, ['pay_status'])) {
			if (!($model = DepositAccountModel::findOne($post->id))) {
				return Message::warning(Language::get('no_data'));
			}
			$model->{$post->column} = $post->value ? 'ON' : 'OFF';
			if (!$model->save()) {
				return Message::warning($model->errors);
			}
			return Message::display(Language::get('edit_ok'));
		}
	}

	public function actionSetting()
	{
		if (!Yii::$app->request->isPost) {
			$this->params['setting'] = DepositSettingModel::getSystemSetting();
			$this->params['page'] = Page::seo(['title' => Language::get('deposit_setting')]);
			return $this->render('../deposit.setting.html', $this->params);
		} else {
			$post = Basewind::trimAll(Yii::$app->request->post(), true);

			$model = new \backend\models\DepositSettingForm(['userid' => 0]);
			if (!$model->save($post, true)) {
				return Message::warning($model->errors);
			}
			return Message::display(Language::get('edit_ok'));
		}
	}

	/* 异步修改数据 */
	public function actionEditsetcol()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['userid']);
		if (in_array($post->column, ['trade_rate'])) {
			if (!($model = DepositSettingModel::find()->where(['userid' => $post->userid])->one())) {
				$model = new DepositSettingModel();
				$model->userid = $post->userid;
			}

			$value = floatval($post->value);
			if ($value >= 1 || $value < 0) {
				return Message::warning(Language::get('number_error'));
			}

			$model->{$post->column} = $value;
			if (!$model->save()) {
				return Message::warning($model->errors);
			}
			return Message::display(Language::get('edit_ok'));
		}
	}

	/**
	 * 交易记录
	 */
	public function actionTradelist()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['limit', 'page']);

		if (!Yii::$app->request->isAjax) {
			$this->params['filtered'] = $this->getTradeConditions($post);

			$statuslist = DepositTradeModel::find()->select('status')->groupBy('status')->column();
			foreach ($statuslist as $value) {
				$this->params['status_list'][$value] = Language::get('TRADE_' . $value);
			}
			$this->params['search_options'] = array('tradeNo' => Language::get('tradeNo'), 'payTradeNo' => Language::get('payTradeNo'), 'bizOrderId' => Language::get('orderId'));

			$this->params['_foot_tags'] = Resource::import([
				'script' => 'javascript/jquery.ui/jquery.ui.js,javascript/jquery.ui/i18n/' . Yii::$app->language . '.js',
				'style' => 'javascript/jquery.ui/themes/smoothness/jquery.ui.css'
			]);

			$this->params['page'] = Page::seo(['title' => Language::get('deposit_tradelist')]);
			return $this->render('../deposit.tradelist.html', $this->params);
		} else {
			$query = DepositTradeModel::find()->alias('dt')
				->select('trade_id,tradeNo,payTradeNo,bizOrderId,title,amount,status,flow,buyer_id,seller_id,add_time,pay_time,end_time,payment_code');

			$query = $this->getTradeConditions($post, $query)->orderBy(['trade_id' => SORT_DESC]);
			$page = Page::getPage($query->count(), $post->limit ? $post->limit : 10);

			$list = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
			foreach ($list as $key => $value) {
				$list[$key]['add_time'] = Timezone::localDate('Y-m-d H:i:s', $value['add_time']);
				$list[$key]['pay_time'] = Timezone::localDate('Y-m-d H:i:s', $value['pay_time']);
				$list[$key]['end_time'] = Timezone::localDate('Y-m-d H:i:s', $value['end_time']);
				$list[$key]['buyer'] = UserModel::find()->select('username')->where(['userid' => $value['buyer_id']])->scalar();
				$list[$key]['status'] = Language::get('TRADE_' . strtoupper($value['status']));

				if (($client = Plugin::getInstance('payment')->build($value['payment_code']))) {
					if ($payment = $client->getInfo()) {
						$list[$key]['payment'] = $payment;
					}
				}

				$partyInfo = DepositTradeModel::getPartyInfoByRecord($value['buyer_id'], $value);
				$list[$key]['party'] = $partyInfo['name'];
			}

			return Json::encode(['code' => 0, 'msg' => '', 'count' => $query->count(), 'data' => $list]);
		}
	}

	/**
	 * 指定用户的收支记录
	 */
	public function actionRecordlist()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['userid', 'limit', 'page']);

		if (!Yii::$app->request->isAjax) {

			$this->params['_foot_tags'] = Resource::import([
				'script' => 'javascript/jquery.ui/jquery.ui.js,javascript/jquery.ui/i18n/' . Yii::$app->language . '.js',
				'style' => 'javascript/jquery.ui/themes/smoothness/jquery.ui.css'
			]);

			$this->params['page'] = Page::seo(['title' => Language::get('deposit_recordlist')]);
			return $this->render('../deposit.recordlist.html', $this->params);
		} else {
			$query = DepositRecordModel::find()->alias('dr')
				->joinWith('depositTrade dt', false)
				->joinWith('user u', false)
				->select('dr.userid,dr.amount,dr.balance,dr.name,dr.flow,dt.tradeNo,dt.payTradeNo,dt.bizOrderId,dt.bizIdentity,dt.add_time,dt.pay_time,dt.end_time,dt.payment_code,dt.buyer_id,dt.seller_id,u.username')
				->where(['dr.userid' => $post->userid, 'fundtype' => 'money'])
				->orderBy(['record_id' => SORT_DESC]);

			$page = Page::getPage($query->count(), $post->limit ? $post->limit : 10);

			$list = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
			foreach ($list as $key => $value) {
				$list[$key]['add_time'] = Timezone::localDate('Y-m-d H:i:s', $value['add_time']);
				$list[$key]['pay_time'] = Timezone::localDate('Y-m-d H:i:s', $value['pay_time']);
				$list[$key]['end_time'] = Timezone::localDate('Y-m-d H:i:s', $value['end_time']);

				if (($client = Plugin::getInstance('payment')->build($value['payment_code']))) {
					if ($payment = $client->getInfo()) {
						$list[$key]['payment'] = $payment;
					}
				}

				$partyInfo = DepositTradeModel::getPartyInfoByRecord($value['userid'], $value);
				$list[$key]['party'] = $partyInfo['name'];
			}

			return Json::encode(['code' => 0, 'msg' => '', 'count' => $query->count(), 'data' => $list]);
		}
	}

	public function actionDrawlist()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['limit', 'page']);

		if (!Yii::$app->request->isAjax) {
			$this->params['filtered'] = $this->getTradeConditions($post);
			$this->params['status_list'] = array(
				'SUCCESS' => Language::get('TRADE_SUCCESS'),
				'VERIFY' => Language::get('TRADE_VERIFY')
			);
			$this->params['search_options'] = array('tradeNo' => Language::get('tradeNo'), 'orderId' => Language::get('orderId'));

			if ($payments = DepositWithdrawModel::find()->select('drawtype')->groupBy('drawtype')->column()) {
				foreach ($payments as $value) {
					$this->params['payment_list'][$value] = $value == 'alipay' ? '支付宝' : ($value == 'wxpay' ? '微信零钱' : '银行卡');
				}
			}

			$this->params['_foot_tags'] = Resource::import([
				'script' => 'javascript/jquery.ui/jquery.ui.js,javascript/jquery.ui/i18n/' . Yii::$app->language . '.js',
				'style' => 'javascript/jquery.ui/themes/smoothness/jquery.ui.css'
			]);

			$this->params['page'] = Page::seo(['title' => Language::get('deposit_drawlist')]);
			return $this->render('../deposit.drawlist.html', $this->params);
		} else {
			$query = DepositWithdrawModel::find()->alias('dw')
				->select('dw.*,dt.trade_id,dt.tradeNo,dt.add_time,dt.end_time,dt.amount,dt.status')
				->joinWith('depositTrade dt', false);
			$query = $this->getTradeConditions($post, $query)->orderBy(['draw_id' => SORT_DESC]);

			$page = Page::getPage($query->count(), $post->limit ? $post->limit : 10);
			$list = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
			foreach ($list as $key => $value) {
				$list[$key]['amount'] = $value['amount'] - $value['fee'];
				$list[$key]['add_time'] = Timezone::localDate('Y-m-d H:i:s', $value['add_time']);
				$list[$key]['end_time'] = Timezone::localDate('Y-m-d H:i:s', $value['end_time']);
				if ($user = UserModel::find()->select('username,phone_mob')->where(['userid' => $value['userid']])->asArray()->one()) {
					$list[$key] = array_merge($list[$key], $user);
				}
			}

			return Json::encode(['code' => 0, 'msg' => '', 'count' => $query->count(), 'data' => $list]);
		}
	}

	/* 提现审核（通过） */
	public function actionDrawverify()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['id']);
		if (!$post->id || !($record = DepositWithdrawModel::find()->alias('dw')->select('dw.*,dt.tradeNo,dt.status,dt.amount')->joinWith('depositTrade dt', false)->where(['draw_id' => $post->id])->asArray()->one())) {
			return Message::warning(Language::get('no_such_draw'));
		}
		if ($record['status'] != 'VERIFY') {
			return Message::warning(Language::get('drawal_status_error'));
		}
		if ($post->method == 'online' && !in_array($record['drawtype'], ['alipay', 'wxpay'])) {
			return Message::warning(Language::get('drawtype_disallow'));
		}

		// 变更交易状态
		$model = new DepositWithdrawModel();
		if (!$model->handleMoney($record['tradeNo'], $post->method)) {
			return Message::warning($model->errors);
		}

		return Message::display(Language::get('drawal_ok'));
	}

	/* 提现审核（拒绝） */
	public function actionDrawrefuse()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['id']);
		if (!$post->id || !($record = DepositWithdrawModel::find()->alias('dw')->select('dw.*,dt.tradeNo,dt.status,dt.amount')->joinWith('depositTrade dt', false)->where(['draw_id' => $post->id])->asArray()->one())) {
			return Message::warning(Language::get('no_such_draw'));
		}
		if ($record['status'] != 'VERIFY') {
			return Message::warning(Language::get('drawal_status_error'));
		}
		if (!$post->remark) {
			return Message::warning(Language::get('refuse_remark_empty'));
		}

		// 变更交易状态
		if (($model = DepositTradeModel::find()->where(['tradeNo' => $record['tradeNo']])->one())) {
			$model->status = 'CLOSED';
			$model->end_time = Timezone::gmtime();
			if (!$model->save()) {
				return Message::warning($model->errors);
			}
			// 管理员增加备注（拒绝原因）
			DepositRecordModel::updateAll(['remark' => $post->remark], ['tradeNo' => $record['tradeNo'], 'userid' => $record['userid'], 'tradeType' => 'WITHDRAW']);

			// 扣减当前用户的冻结金额
			DepositAccountModel::updateDepositMoney($record['userid'], $record['amount'], 'reduce', 'frozen');

			// 将冻结金额退回到账户余额（变更账户余额）
			$query = new DepositRecordModel();
			$query->tradeNo = $record['tradeNo'];
			$query->userid = $record['userid'];
			$query->amount = $record['amount'];
			$query->balance = DepositAccountModel::updateDepositMoney($record['userid'], $record['amount']);
			$query->tradeType =  'TRANSFER';
			$query->fundtype = 'money';
			$query->name = Language::get('draw_return');
			$query->flow = 'income';
			$query->remark = $post->remark;
			$query->created = Timezone::gmtime();
			if (!$query->save()) {
				// TODO...
			}
		}
		return Message::display(Language::get('refuse_draw_ok'));
	}

	/* 管理员手动给账户充值/扣费 */
	public function actionRecharge()
	{
		$id = intval(Yii::$app->request->get('id', 0));
		if (!$id || !($account = DepositAccountModel::findOne($id))) {
			return Message::warning(Language::get('no_such_account'));
		}
		if (!Yii::$app->request->isPost) {
			$this->params['account'] = ArrayHelper::toArray($account);

			$this->params['page'] = Page::seo(['title' => Language::get('deposit_recharge')]);
			return $this->render('../deposit.recharge.html', $this->params);
		} else {
			$post = Basewind::trimAll(Yii::$app->request->post(), true);

			$model = new \backend\models\DepositRechargeForm(['userid' => $account->userid]);
			if (!($store = $model->save($post, true))) {
				return Message::warning($model->errors);
			}
			return Message::display(Language::get($post->money_change == 'add' ? '充值成功' : '扣费成功'), ['deposit/tradelist']);
		}
	}

	public function actionRechargelist()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['limit', 'page']);

		if (!Yii::$app->request->isAjax) {
			$this->params['filtered'] = $this->getTradeConditions($post);
			$this->params['status_list'] = array(
				'PENDING' => Language::get('TRADE_PENDING'),
				'SUCCESS' => Language::get('TRADE_SUCCESS'),
				'CLOSED'  => Language::get('TRADE_CLOSED')
			);
			$this->params['search_options'] = array('tradeNo' => Language::get('tradeNo'), 'orderId' => Language::get('orderId'));
			$this->params['payment_list'] = $this->getPayments('RECHARGE');

			$this->params['_foot_tags'] = Resource::import([
				'script' => 'javascript/jquery.ui/jquery.ui.js,javascript/jquery.ui/i18n/' . Yii::$app->language . '.js',
				'style' => 'javascript/jquery.ui/themes/smoothness/jquery.ui.css'
			]);

			$this->params['page'] = Page::seo(['title' => Language::get('deposit_rechargelist')]);
			return $this->render('../deposit.rechargelist.html', $this->params);
		} else {
			$query = DepositRechargeModel::find()->alias('dr')
				->select('dr.*,dt.trade_id,dt.tradeNo,dt.payment_code,dt.add_time,dt.pay_time,dt.end_time,dt.amount,dt.status,dt.buyer_remark as remark')
				->joinWith('depositTrade dt', false);
			$query = $this->getTradeConditions($post, $query)->orderBy(['recharge_id' => SORT_DESC]);

			$page = Page::getPage($query->count(), $post->limit ? $post->limit : 10);
			$list = $query->offset($page->offset)->limit($page->limit)->asArray()->all();

			foreach ($list as $key => $value) {
				$list[$key]['add_time'] = Timezone::localDate('Y-m-d H:i:s', $value['add_time']);
				$list[$key]['pay_time'] = Timezone::localDate('Y-m-d H:i:s', $value['pay_time']);
				$list[$key]['end_time'] = Timezone::localDate('Y-m-d H:i:s', $value['end_time']);
				$list[$key]['status'] = Language::get('TRADE_' . strtoupper($value['status']));

				if ($array = UserModel::find()->select('username,phone_mob')->where(['userid' => $value['userid']])->asArray()->one()) {
					$list[$key] = array_merge($list[$key], $array);
				}

				if (($client = Plugin::getInstance('payment')->build($value['payment_code']))) {
					if ($payment = $client->getInfo()) {
						$list[$key]['payment'] = $payment;
					}
				}
			}

			return Json::encode(['code' => 0, 'msg' => '', 'count' => $query->count(), 'data' => $list]);
		}
	}

	/* 月账单下载 */
	public function actionMonthbill()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['userid']);

		if (!Yii::$app->request->isAjax) {
			$this->params['page'] = Page::seo(['title' => Language::get('deposit_monthbill')]);
			return $this->render('../deposit.monthbill.html', $this->params);
		} else {
			$query = DepositRecordModel::find()->alias('dr')->select('dr.record_id,dr.tradeNo,dr.amount,dr.userid,dr.flow,dr.tradeType,dt.end_time')
				->joinWith('depositTrade dt', false)
				->where(['and', ['status' => 'SUCCESS', 'userid' => $post->userid, 'fundtype' => 'money'], ['>', 'end_time', 0]])
				->orderBy(['record_id' => SORT_DESC]);

			// 也可以限制为当年12个月的数据
			$list = $query->limit(10000)->asArray()->all();

			// 按月进行归类
			$monthbill = array();
			foreach ($list as $key => $value) {
				$month = Timezone::localDate('Y-m', $value['end_time']);

				$monthbill[$month]['month'] = $month;
				$monthbill[$month][$value['flow'] . '_money'] += $value['amount'];
				$monthbill[$month][$value['flow'] . '_count'] += 1;

				// 如果是支出，判断是否是服务费
				if ($value['flow'] == 'outlay' && ($value['tradeType'] == 'SERVICE')) {
					$monthbill[$month][$value['tradeType'] . '_money'] += $value['amount'];
					$monthbill[$month][$value['tradeType'] . '_count'] += 1;
				}
			}

			$list = array_values($monthbill);
			return Json::encode(['code' => 0, 'msg' => '', 'data' => $list]);
		}
	}

	/* 下载某个用户某个月的对账单 */
	public function actionDownloadbill()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['userid']);
		if (!$post->userid || !$post->month) {
			return Message::warning(Language::get('downloadbill_fail'));
		}
		return DepositAccountModel::downloadbill($post->userid, $post->month);
	}

	public function actionExport()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true);
		if ($post->id) $post->id = explode(',', $post->id);

		if ($post->model == 'account') {
			$query = DepositAccountModel::find()->alias('da')
				->select('da.*,u.username')
				->joinWith('user u', false)
				->orderBy(['account_id' => SORT_DESC]);

			if (!empty($post->id)) {
				$query->andWhere(['in', 'account_id', $post->id]);
			} else {
				$query = $this->getConditions($post, $query)->limit(100);
			}
			if ($query->count() == 0) {
				return Message::warning(Language::get('no_data'));
			}
			$model = new \backend\models\DepositAccountExportForm();
		}
		if ($post->model == 'trade') {
			$query = DepositTradeModel::find()->alias('dt')
				->select('dt.trade_id,dt.tradeNo,dt.bizOrderId,dt.bizIdentity,dt.payment_code,dt.buyer_id,dt.seller_id,dt.title,dt.amount,dt.status,dt.flow,dt.end_time')
				->orderBy(['trade_id' => SORT_DESC]);

			if (!empty($post->id)) {
				$query->andWhere(['in', 'trade_id', $post->id]);
			} else {
				$query = $this->getTradeConditions($post, $query)->limit(100);
			}
			if ($query->count() == 0) {
				return Message::warning(Language::get('no_data'));
			}
			$model = new \backend\models\DepositTradeExportForm();
		}
		if ($post->model == 'draw') {
			$query = DepositWithdrawModel::find()->alias('dw')
				->select('dw.*,dt.trade_id,dt.tradeNo,dt.bizIdentity,dt.payment_code,dt.buyer_id,dt.seller_id,dt.add_time,dt.end_time,dt.amount,dt.status,dt.buyer_remark as remark')
				->joinWith('depositTrade dt', false)
				->orderBy(['draw_id' => SORT_DESC]);

			if (!empty($post->id)) {
				$query->andWhere(['in', 'draw_id', $post->id]);
			} else {
				$query = $this->getTradeConditions($post, $query)->limit(100);
			}
			if ($query->count() == 0) {
				return Message::warning(Language::get('no_data'));
			}
			$model = new \backend\models\DepositDrawExportForm();
		}
		if ($post->model == 'recharge') {
			$query = DepositRechargeModel::find()->alias('dr')
				->select('dr.*,dt.trade_id,dt.tradeNo,dt.bizIdentity,dt.payment_code,dt.buyer_id,dt.seller_id,dt.end_time,dt.amount,dt.status,dt.buyer_remark as remark')
				->joinWith('depositTrade dt', false)
				->orderBy(['recharge_id' => SORT_DESC]);

			if (!empty($post->id)) {
				$query->andWhere(['in', 'recharge_id', $post->id]);
			} else {
				$query = $this->getTradeConditions($post, $query)->limit(100);
			}
			if ($query->count() == 0) {
				return Message::warning(Language::get('no_data'));
			}
			$model = new \backend\models\DepositRechargeExportForm();
		}
		return $model->download($query->asArray()->all());
	}

	private function getSearchOption()
	{
		return array(
			'account'	=> Language::get('account'),
			'username' 	=> Language::get('username'),
		);
	}

	private function getPayments($bizIdentity = '')
	{
		$query = DepositTradeModel::find()->select('payment_code')->where(['!=', 'payment_code', ''])->groupBy(['payment_code']);
		if ($bizIdentity) $query->andWhere(['bizIdentity' => $bizIdentity]);
		$list = $query->column();

		$array = [];
		foreach ($list as $value) {
			if (($client = Plugin::getInstance('payment')->build($value))) {
				if ($payment = $client->getInfo()) {
					$array[$value] = $payment['name'];
				}
			}
		}

		return $array;
	}

	private function getPayStatus()
	{
		return array('ON' => Language::get('yes'), 'OFF' => Language::get('no'));
	}

	private function getConditions($post, $query = null)
	{
		if ($query === null) {
			foreach (array_keys(ArrayHelper::toArray($post)) as $field) {
				if (in_array($field, ['search_name', 'pay_status', 'add_time_from', 'add_time_to', 'money_from', 'money_to'])) {
					return true;
				}
			}
			return false;
		}

		if ($post->field && $post->search_name && in_array($post->field, array_keys($this->getSearchOption()))) {
			if ($post->field == 'username') {
				$userid = UserModel::find()->select('userid')->where(['username' => $post->search_name])->scalar();
				$query->andWhere(['userid' => intval($userid)]);
			} else {
				$query->andWhere(['like', $post->field, $post->search_name]);
			}
		}
		if ($post->pay_status) {
			$query->andWhere(['pay_status' => (strtoupper($post->pay_status) == 'ON') ? 'ON' : 'OFF']);
		}
		if ($post->add_time_from) $post->add_time_from = Timezone::gmstr2time($post->add_time_from);
		if ($post->add_time_to) $post->add_time_to = Timezone::gmstr2time_end($post->add_time_to);
		if ($post->add_time_from && $post->add_time_to) {
			$query->andWhere(['and', ['>=', 'add_time', $post->add_time_from], ['<=', 'add_time', $post->add_time_to]]);
		}
		if ($post->add_time_from && (!$post->add_time_to || ($post->add_time_to <= $post->add_time_from))) {
			$query->andWhere(['>=', 'add_time', $post->add_time_from]);
		}
		if (!$post->add_time_from && ($post->add_time_to && ($post->add_time_to > Timezone::gmtime()))) {
			$query->andWhere(['<=', 'add_time', $post->add_time_to]);
		}

		if ($post->money_from) $post->money_from = floatval($post->money_from);
		if ($post->money_to) $post->money_to = floatval($post->money_to);
		if ($post->money_from && $post->money_to) {
			$query->andWhere(['and', ['>=', 'money', $post->money_from], ['<=', 'money', $post->money_to]]);
		}
		if ($post->money_from && (!$post->money_to || ($post->money_to < 0))) {
			$query->andWhere(['>=', 'money', $post->money_from]);
		}
		if (!$post->money_from && ($post->money_to > 0)) {
			$query->andWhere(['<=', 'money', $post->money_to]);
		}
		return $query;
	}

	private function getTradeConditions($post, $query = null)
	{
		if ($query === null) {
			foreach (array_keys(ArrayHelper::toArray($post)) as $field) {
				if (in_array($field, ['search_name', 'status', 'add_time_from', 'add_time_to', 'end_time_from', 'end_time_to', 'amount_from', 'amount_to', 'payment_code', 'drawtype'])) {
					return true;
				}
			}
			return false;
		}

		if ($post->field && $post->search_name && in_array($post->field, ['bizOrderId', 'orderId', 'tradeNo', 'payTradeNo', 'username'])) {
			$query->andWhere(['like', $post->field, $post->search_name]);
		}
		if ($post->status) {
			$query->andWhere(['dt.status' => $post->status]);
		}
		if ($post->add_time_from) $post->add_time_from = Timezone::gmstr2time($post->add_time_from);
		if ($post->add_time_to) $post->add_time_to = Timezone::gmstr2time_end($post->add_time_to);
		if ($post->add_time_from && $post->add_time_to) {
			$query->andWhere(['and', ['>=', 'dt.add_time', $post->add_time_from], ['<=', 'dt.add_time', $post->add_time_to]]);
		}
		if ($post->add_time_from && (!$post->add_time_to || ($post->add_time_to <= $post->add_time_from))) {
			$query->andWhere(['>=', 'dt.add_time', $post->add_time_from]);
		}
		if (!$post->add_time_from && ($post->add_time_to && ($post->add_time_to > Timezone::gmtime()))) {
			$query->andWhere(['<=', 'dt.add_time', $post->add_time_to]);
		}

		if ($post->end_time_from) $post->end_time_from = Timezone::gmstr2time($post->end_time_from);
		if ($post->end_time_to) $post->end_time_to = Timezone::gmstr2time_end($post->end_time_to);
		if ($post->end_time_from && $post->end_time_to) {
			$query->andWhere(['and', ['>=', 'dt.add_time', $post->end_time_from], ['<=', 'dt.add_time', $post->end_time_to]]);
		}
		if ($post->end_time_from && (!$post->end_time_to || ($post->end_time_to <= $post->end_time_from))) {
			$query->andWhere(['>=', 'dt.add_time', $post->end_time_from]);
		}
		if (!$post->end_time_from && ($post->end_time_to && ($post->end_time_to > Timezone::gmtime()))) {
			$query->andWhere(['<=', 'dt.add_time', $post->end_time_to]);
		}

		if ($post->amount_from) $post->amount_from = floatval($post->amount_from);
		if ($post->amount_to) $post->amount_to = floatval($post->amount_to);
		if ($post->amount_from && $post->amount_to) {
			$query->andWhere(['and', ['>=', 'dt.amount', $post->amount_from], ['<=', 'dt.amount', $post->amount_to]]);
		}
		if ($post->amount_from && (!$post->amount_to || ($post->amount_to < 0))) {
			$query->andWhere(['>=', 'dt.amount', $post->amount_from]);
		}
		if (!$post->amount_from && ($post->amount_to > 0)) {
			$query->andWhere(['<=', 'dt.amount', $post->amount_to]);
		}

		if ($post->payment_code) {
			$query->andWhere(['payment_code' => $post->payment_code]);
		}
		if ($post->drawtype) {
			$query->andWhere(['drawtype' => $post->drawtype]);
		}
		return $query;
	}
}
