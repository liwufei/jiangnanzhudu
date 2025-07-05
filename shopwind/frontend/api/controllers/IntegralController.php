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

use common\models\GoodsModel;
use common\models\IntegralModel;
use common\models\IntegralSettingModel;
use common\models\IntegralLogModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Timezone;
use common\library\Page;
use common\library\Plugin;
use frontend\api\library\Respond;
use frontend\api\library\Formatter;

/**
 * @Id IntegralController.php 2018.10.15 $
 * @author yxyc
 */

class IntegralController extends \common\base\BaseApiController
{
	/**
	 * 获取平台积分设置
	 * @api 接口访问地址: https://www.xxx.com/api/integral/setting
	 */
	public function actionSetting()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(false)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true);
		return $respond->output(true, null, IntegralSettingModel::getSysSetting());
	}

	/**
	 * 获取积分用户列表
	 * @api 接口访问地址: https://www.xxx.com/api/integral/user
	 */
	public function actionUser()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(false)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['page', 'page_size']);

		$query = IntegralModel::find()->alias('i')->select('i.amount,u.userid,u.username')->joinWith('user u', false)->indexBy('userid')->orderBy(['u.userid' => SORT_ASC]);
		$page = Page::getPage($query->count(), $post->page_size, false, $post->page);
		$list = $query->offset($page->offset)->limit($page->limit)->asArray()->all();

		return $respond->output(true, null, ['list' => $list, 'pagination' => Page::formatPage($page, false)]);
	}

	/**
	 * 获取当前用户积分信息
	 * @api 接口访问地址: https://www.xxx.com/api/integral/read
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

		if ($client = Plugin::getInstance('promote')->build('integral')) {
			if (!($record = $client->getData($post))) {
				return $respond->output(Respond::PARAMS_INVALID, $client->errors);
			}
		}

		return $respond->output(true, null, $record);
	}

	/**
	 * 获取积分商品列表
	 * @api 接口访问地址: https://www.xxx.com/api/integral/goods
	 */
	public function actionGoods()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(false)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['page', 'page_size']);

		$query = GoodsModel::find()->alias('g')->select('g.goods_id,g.default_image as goods_image,g.goods_name,g.price,g.add_time,gi.max_exchange,s.store_id,s.store_name,gst.sales')->joinWith('goodsIntegral gi', false)->joinWith('store s', false)->joinWith('goodsStatistics gst', false)->where(['and', ['s.state' => 1, 'g.if_show' => 1, 'g.closed' => 0], ['>', 'gi.max_exchange', 0]]);
		if ($post->orderby) {
			$orderBy = Basewind::trimAll(explode('|', $post->orderby));
			if (in_array($orderBy[0], array_keys($this->getOrders())) && in_array(strtolower($orderBy[1]), ['desc', 'asc'])) {
				$query->orderBy([$orderBy[0] => strtolower($orderBy[1]) == 'asc' ? SORT_ASC : SORT_DESC]);
			} else $query->orderBy(['g.add_time' => SORT_DESC]);
		} else $query->orderBy(['g.add_time' => SORT_DESC]);
		if ($post->keyword) {
			$query->andWhere(['like', 'goods_name', $post->keyword]);
		}

		$page = Page::getPage($query->count(), $post->page_size, false, $post->page);
		$list = $query->offset($page->offset)->limit($page->limit)->asArray()->all();

		$rate = IntegralSettingModel::getSysSetting('rate');
		foreach ($list as $key => $value) {
			$list[$key]['exchange_rate'] = floatval($rate);
			$list[$key]['exchange_integral'] = floatval($value['max_exchange']);
			$list[$key]['exchange_money'] = round($value['max_exchange'] * $rate, 2);
			$exchange_price = $value['price'] - $list[$key]['exchange_money'];
			if ($exchange_price < 0) {
				$list[$key]['exchange_integral'] = round($value['price'] / $rate, 2);
				$list[$key]['exchange_money'] = floatval($value['price']);
				$exchange_price = 0;
			}
			$list[$key]['exchange_price'] = $exchange_price;
			unset($list[$key]['max_exchange']);
			$list[$key]['goods_image'] = Formatter::path($value['goods_image'], 'goods');
		}

		return $respond->output(true, null, ['list' => $list, 'pagination' => Page::formatPage($page, false)]);
	}

	/**
	 * 当前用户签到领积分
	 * @api 接口访问地址: https://www.xxx.com/api/integral/signin
	 */
	public function actionSignin()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(true)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true);

		if (!IntegralSettingModel::getSysSetting('enabled')) {
			return $respond->output(Respond::HANDLE_INVALID, Language::get('integral_disabled'));
		}

		$query = IntegralLogModel::find()->select('userid,add_time')->where(['userid' => Yii::$app->user->id, 'type' => 'signin'])->orderBy(['log_id' => SORT_DESC])->one();
		if ($query && Timezone::localDate('Ymd', Timezone::gmtime()) == Timezone::localDate('Ymd', $query->add_time)) {
			return $respond->output(Respond::HANDLE_INVALID, Language::get('have_get_integral_for_signin'));
		}

		// 签到领取积分金额
		$points = IntegralSettingModel::getSysSetting('signin');
		if ($points <= 0) {
			return $respond->output(Respond::HANDLE_INVALID, Language::get('signin_amount_le0'));
		}
		if (($balance = IntegralModel::updateIntegral(['userid' => Yii::$app->user->id, 'type' => 'signin', 'points' => $points, 'flag' => sprintf(Language::get('signin_integral_flag'), $points)])) === false) {
			return $respond->output(Respond::HANDLE_INVALID, Language::get('signin_integral_fail'));
		}

		return $respond->output(true, null, ['userid' => $query->userid, 'balance' => $balance, 'value' => $points]);
	}

	/**
	 * 支持的排序规则
	 */
	private function getOrders()
	{
		return array(
			'add_time'    => Language::get('add_time'),
			'sales'       => Language::get('sales'),
			'price'       => Language::get('price'),
		);
	}
}
