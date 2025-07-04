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
use common\models\AppmarketModel;
use common\models\DistributeModel;
use common\models\DistributeSettingModel;
use common\models\DistributeMerchantModel;
use common\models\UploadedFileModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Resource;
use common\library\Page;
use common\library\Plugin;
use common\library\Weixin;

use frontend\api\library\Respond;
use frontend\api\library\Formatter;

/**
 * @Id DistributeController.php 2019.12.8 $
 * @author yxyc
 */

class DistributeController extends \common\base\BaseApiController
{
	/**
	 * 获取分销商品列表
	 * @api 接口访问地址: https://www.xxx.com/api/distribute/list
	 */
	public function actionList()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(true)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['store_id', 'page', 'page_size']);

		$model = new \frontend\home\models\DistributeGoodsForm();
		list($list, $page) = $model->formData($post, $post->page_size, false, $post->page);

		foreach ($list as $key => $value) {
			$list[$key]['default_image'] = Formatter::path($value['default_image'], 'goods');
		}

		$this->params = ['list' => $list, 'pagination' => Page::formatPage($page, false)];
		return $respond->output(true, null, $this->params);
	}

	/**
	 * 获取商品分销比率
	 * @api 接口访问地址: https://www.xxx.com/api/distribute/read
	 */
	public function actionRead()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(true)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['goods_id']);

		if (!($goods = GoodsModel::find()->select('goods_id,goods_name,default_image as goods_image')->where(['goods_id' => $post->goods_id])->asArray()->one())) {
			return $respond->output(Respond::RECORD_NOTEXIST, Language::get('no_such_item'));
		}
		if (!($result = DistributeSettingModel::find()->select('ratio1,ratio2,ratio3,enabled')->where(['item_id' => $post->goods_id, 'type' => 'goods'])->asArray()->one())) {
			return $respond->output(Respond::RECORD_NOTEXIST, Language::get('no_such_item'));
		}
		$result = array_merge($result, $goods, ['goods_image' => Formatter::path($goods['goods_image'], 'goods')]);

		return $respond->output(true, null, $result);
	}

	/**
	 * 更新商品分销设置
	 * @api 接口访问地址: https://www.xxx.com/api/distribute/update
	 */
	public function actionUpdate()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(true)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['goods_id', 'enabled']);
		$post = $this->absratio($post);

		// 查询该商品是否为当前卖家的
		if (!$post->goods_id || !GoodsModel::find()->where(['goods_id' => $post->goods_id, 'store_id' => Yii::$app->user->id])->exists()) {
			return $respond->output(Respond::PARAMS_INVALID, Language::get('no_such_item'));
		}

		// 商家是否已购买，并在使用期限内
		$client = Plugin::getInstance('promote')->build('distribute');
		if (!$client->checkAvailable(Yii::$app->user->id, false)) {
			return $respond->output(Respond::HANDLE_INVALID, Language::get('handle_exception'));
		}

		// 比率是否合适
		if (($post->ratio1 + $post->ratio2 + $post->ratio3 >= 1) || ($post->ratio1 + $post->ratio2 + $post->ratio3 <= 0)) {
			return $respond->output(Respond::PARAMS_INVALID, Language::get('ratio_invalid'));
		}

		if (!$model = DistributeSettingModel::find()->where(['item_id' => $post->goods_id, 'type' => 'goods'])->one()) {
			$model = new DistributeSettingModel();
		}

		$model->enabled 	= $post->enabled;
		$model->ratio1 		= floatval($post->ratio1);
		$model->ratio2 		= floatval($post->ratio2);
		$model->ratio3 		= floatval($post->ratio3);
		$model->type 		= 'goods';
		$model->item_id		= $post->goods_id;

		if (!$model->save()) {
			return $respond->output(Respond::CURD_FAIL, $model->errors);
		}

		return $respond->output(true);
	}

	/**
	 * 删除分销商品
	 * @api 接口访问地址: https://www.xxx.com/api/distribute/delete
	 */
	public function actionDelete()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(true)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['goods_id']);

		// 只能删除自己的商品
		if (!$post->goods_id || !GoodsModel::find()->where(['store_id' => Yii::$app->user->id, 'goods_id' => $post->goods_id])->exists()) {
			return $respond->output(Respond::PARAMS_INVALID, Language::get('no_such_goods'));
		}

		DistributeSettingModel::deleteAll(['type' => 'goods', 'item_id' => $post->goods_id]);

		return $respond->output(true);
	}

	/**
	 * 分销订单
	 * @api 接口访问地址: https://www.xxx.com/api/distribute/order
	 */
	public function actionOrder()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(true)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true);

		$model = new \frontend\home\models\DistributeOrderForm();
		list($list, $page) = $model->formData($post, $post->page_size, false, $post->page);

		$this->params = ['list' => $list, 'pagination' => Page::formatPage($page, false)];
		return $respond->output(true, null, $this->params);
	}

	/**
	 * 分销团队
	 * @api 接口访问地址: https://www.xxx.com/api/distribute/team
	 */
	public function actionTeam()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(true)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['id', 'page', 'page_size']);

		$model = new \frontend\home\models\DistributeTeamForm();
		list($list, $page) = $model->formData($post, $post->page_size, false, $post->page);
		foreach ($list as $key => $value) {
			$list[$key]['portrait'] = Formatter::path($value['portrait'], 'portrait');
		}

		$this->params = ['list' => $list, 'pagination' => Page::formatPage($page, false)];
		return $respond->output(true, null, $this->params);
	}

	/**
	 * 设置为分销商品
	 * @api 接口访问地址: https://www.xxx.com/api/distribute/choice
	 */
	public function actionChoice()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(true)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['goods_id']);

		$model = new \frontend\home\models\DistributeGoodsForm();
		if ($model->choice($post) === false) {
			return $respond->output(Respond::CURD_FAIL, $model->errors);
		}

		return $respond->output(true);
	}

	/**
	 * 分销商入驻
	 * @api 接口访问地址: https://www.xxx.com/api/distribute/apply
	 */
	public function actionApply()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(true)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true);

		// 平台是否开放分销
		if (($query = AppmarketModel::find()->select('status')->where(['appid' => 'distribute'])->one()) && !$query->status) {
			return $respond->output(Respond::HANDLE_INVALID, Language::get('apply_disabled'));
		}

		$model = new \frontend\home\models\DistributeApplyForm();
		if (!$model->save($post)) {
			return $respond->output(Respond::HANDLE_INVALID, $model->errors);
		}

		return $respond->output(true);
	}

	/**
	 * 查询分销商信息
	 * @api 接口访问地址: https://www.xxx.com/api/distribute/merchant
	 */
	public function actionMerchant()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(true)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true);

		if (!($record = DistributeMerchantModel::find()->select('parent_id,phone_mob,status,name,userid,username,logo,remark')->where(['userid' => Yii::$app->user->id])->asArray()->one())) {
			return $respond->output(Respond::RECORD_NOTEXIST, Language::get('no_such_item'));
		}
		$record['logo'] = Formatter::path($record['logo'], 'portrait');
		$record['inviteCode'] = DistributeModel::getInviteCode(['type' => 'register', 'id' => 0, 'uid' => Yii::$app->user->id]);

		return $respond->output(true, null, $record);
	}

	/**
	 * 获取分销商推广海报
	 * @api 接口访问地址: https://www.xxx.com/api/distribute/qrcode
	 */
	public function actionQrcode()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(true)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true);

		if (!($record = DistributeMerchantModel::find()->alias('dm')->select('dm.dmid,u.portrait')->joinWith('user u', false)->where(['dm.userid' => Yii::$app->user->id])->asArray()->one())) {
			return $respond->output(Respond::RECORD_NOTEXIST, Language::get('no_such_item'));
		}
		$record['portrait'] = Formatter::path($record['portrait'], 'store');

		$path = UploadedFileModel::getSavePath('qrcode/poster/');
		$wxacode = $path . "wxacode" . md5($post->page) . ".png";
		if (!file_exists($wxacode)) {
			$response = Weixin::getInstance(null, 0, 'applet')->getWxaCode(['path' => $post->page, 'width' => 280], $wxacode);
			if ($response === false) {

				// 如果没有上线小程序，则无法获取小程序码，则使用H5页二维码取代
				list($wxacode) = Page::generateQRCode('qrcode/poster/', ['text' => Basewind::mobileUrl(true) . $post->page, 'size' => 280]);
				if (!$wxacode) {
					return $respond->output(Respond::HANDLE_INVALID, Language::get('handle_exception'));
				}
			}
		}

		// 生成海报
		$config = [
			'image' => [['url' => $record['portrait'], 'left' => 300, 'top' => 680, 'width' => 150, 'height' => 150], ['url' => $wxacode, 'left' => 230, 'top' => 870, 'width' => 300, 'height' => 300]],
			'background' => Resource::getResourceUrl('system/merchantQrcode.jpg')
		];

		try {
			$qrcode = Page::createPoster($config, $path . "poster" . md5($post->page) . ".png");
			$result['poster'] = str_replace(Yii::getAlias('@public'), Basewind::baseUrl(), $qrcode);
			return $respond->output(true, null, $result);
		} catch (\Exception $e) {
			return $respond->output(Respond::HANDLE_INVALID, $e->getMessage());
		}
	}

	/**
	 * 禁止负值或非数值处理
	 */
	private function absratio($post)
	{
		$post->ratio1 = abs(floatval($post->ratio1));
		$post->ratio2 = abs(floatval($post->ratio2));
		$post->ratio3 = abs(floatval($post->ratio3));

		return $post;
	}
}
