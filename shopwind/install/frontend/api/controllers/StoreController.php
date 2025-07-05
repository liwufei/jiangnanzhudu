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
use yii\helpers\ArrayHelper;

use common\models\StoreModel;
use common\models\GoodsModel;
use common\models\CollectModel;
use common\models\RegionModel;
use common\models\ScategoryModel;
use common\models\SgradeModel;
use common\models\UploadedFileModel;
use common\models\OrderModel;
use common\models\DeliveryTemplateModel;
use common\models\GcategoryModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Timezone;
use common\library\Page;
use common\library\Def;
use common\library\Plugin;

use frontend\api\library\Respond;
use frontend\api\library\Formatter;

/**
 * @Id StoreController.php 2018.12.5 $
 * @author yxyc
 */

class StoreController extends \common\base\BaseApiController
{
	/**
	 * 获取店铺列表
	 * @api 接口访问地址: https://www.xxx.com/api/store/list
	 */
	public function actionList()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(false)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['cate_id', 'sgrade', 'region_id', 'page', 'page_size']);
		$query = StoreModel::find()->alias('s')->select('s.store_id,s.store_name,s.tel,s.credit_value,s.praise_rate,s.stype,s.sgrade,s.add_time,s.store_logo,s.qq,s.region_id,s.address,cs.cate_id')
			->joinWith('categoryStore cs', false)
			->where(['state' => Def::STORE_OPEN]);
		$query = $this->getConditions($query, $post);

		$page = Page::getPage($query->count(), $post->page_size, false, $post->page);
		$list = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
		foreach ($list as $key => $value) {
			$list[$key] = $this->formatImagesUrl($value);
			$list[$key]['add_time'] = Timezone::localDate('Y-m-d H:i:s', $value['add_time']);

			// 店铺在售商品总数
			$list[$key]['goods_count'] = GoodsModel::getCountOfStore($value['store_id']);

			// 店铺被收藏数
			$list[$key]['collects'] = CollectModel::find()->where(['type' => 'store', 'item_id' => $value['store_id']])->count();

			// 店铺分类
			$list[$key]['cate_name'] = ScategoryModel::find()->select('cate_name')->where(['cate_id' => $value['cate_id']])->scalar();

			// 店铺所在地省市区地址信息
			$list[$key] = array_merge($list[$key], (array) RegionModel::getArray($value['region_id']));
		}

		return $respond->output(true, null, ['list' => $list, 'pagination' => Page::formatPage($page, false)]);
	}

	/**
	 * 获取附近商家列表[按距离从近到远]
	 * @api 接口访问地址: https://www.xxx.com/api/store/nearby
	 */
	public function actionNearby()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(false)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['cate_id', 'scategory', 'page', 'page_size']);

		$query = StoreModel::find()->alias('s')->select('s.store_id,store_name,store_logo,deliveryCode')->joinWith('categoryStore cs', false)->where(['state' => Def::STORE_OPEN]);
		$query = $this->getNearbyConditions($post, $query);

		// 商品分类
		$allId = [];
		if ($post->cate_id) {
			$allId = GcategoryModel::getDescendantIds($post->cate_id);
			$limit = GoodsModel::find()->select('store_id')->where(['in', 'cate_id', $allId])->groupBy('store_id')->column();
			$query->andWhere(['in', 's.store_id', $limit]);
		}

		// 店铺分类
		if ($post->scategory) {
			$array = ScategoryModel::getDescendantIds($post->scategory);
			$query->andWhere(['in', 'cate_id', $array]);
		}
		// 关键词
		if ($post->keyword) {
			$query->andWhere(['like', 'store_name', $post->keyword]);
		}

		$page = Page::getPage($query->count(), $post->page_size, false, $post->page);
		$list = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
		foreach ($list as $key => $value) {
			$list[$key]['store_logo'] = Formatter::path($value['store_logo'], 'store');

			// 获取店铺前10个商品
			$model = GoodsModel::find()->alias('g')->select('g.goods_id,goods_name,price,default_image,default_spec,spec_qty,sales')
				->joinWith('goodsStatistics gst',  false)
				->where(['if_show' => 1, 'closed' => 0, 'store_id' => $value['store_id']]);
			if ($allId) $model->andWhere(['in', 'cate_id', $allId]);

			// 店铺商品排序
			if (isset($post->orderby) && in_array($post->orderby, ['sales|desc', 'price|desc', 'price|asc', 'views|desc', 'add_time|desc', 'add_time|asc', 'comments|desc'])) {
				$orderBy = Basewind::trimAll(explode('|', $post->orderby));
				$model->orderBy([(in_array($orderBy[0], ['add_time', 'price']) ? 'g.' . $orderBy[0] : $orderBy[0]) => strtolower($orderBy[1]) == 'asc' ? SORT_ASC : SORT_DESC, 'g.goods_id' => SORT_DESC]);
			} else $model->orderBy(['gst.sales' => SORT_DESC, 'g.goods_id' => SORT_DESC]);

			if ($items = $model->limit(10)->asArray()->all()) {
				foreach ($items as $k => $v) {
					$items[$k]['default_image'] = Formatter::path($v['default_image'], 'goods');
				}
				$list[$key]['items'] = $items;
			}

			// 店铺销量[如果订单量大，再考虑把时间限制为月销量]
			if ($number = OrderModel::find()->select('order_id')->where(['and', ['seller_id' => $value['store_id']], ['>', 'pay_time', 0]])->limit(1000)->count()) {
				$list[$key]['sales'] = $number;
			}

			// 复购（回头客）
			if ($number = OrderModel::find()->select('order_id')->where(['and', ['seller_id' => $value['store_id']], ['>', 'pay_time', 0]])->groupBy('buyer_id')->count()) {
				$list[$key]['rebuys'] = $number;
			}

			// 起送价 & 配送方式
			if ($client = Plugin::getInstance('locality')->build($value['deliveryCode'])) {
				$list[$key]['delivery'] = [
					'name' => $client->getInfo()['name'],
					'basemoney' => floatval(DeliveryTemplateModel::find()->select('basemoney')->where(['store_id' => $value['store_id'], 'type' => 'locality'])->scalar())
				];
			}
		}

		return $respond->output(true, null, ['list' => $list, 'pagination' => Page::formatPage($page, false)]);
	}

	/**
	 * 获取店铺单条信息
	 * @api 接口访问地址: https://www.xxx.com/api/store/read
	 */
	public function actionRead()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(false)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['store_id']);

		$query = StoreModel::find()->alias('s')->select('s.store_id,store_name,tel,s.phone,contacter,s.radius,credit_value,praise_rate,stype,sgrade,state,close_reason,apply_remark,add_time,bustime,deliveryCode,certification,business_license,banner,pcbanner,store_logo,qq,longitude,latitude,address,region_id,description,cs.cate_id')
			->joinWith('categoryStore cs', false)
			->where(['s.store_id' => $post->store_id]);
		if (!($record = $query->asArray()->one())) {
			return $respond->output(Respond::RECORD_NOTEXIST, Language::get('no_such_store'));
		}

		$record = $this->formatImagesUrl($record);
		$record['add_time'] = Timezone::localDate('Y-m-d H:i:s', $record['add_time']);

		// 商家营业 & 打烊
		if ($record['bustime']) {
			$record['busopen'] = StoreModel::getBusstate($record['bustime']);
			$record['bustime'] = explode('~', $record['bustime']);
		}

		if ($record['certification']) {
			$record['certification'] = explode(',', $record['certification']);
		}
		// 同城配送
		if ($record['deliveryCode']) {
			if ($client = Plugin::getInstance('locality')->build($record['deliveryCode'])) {
				$record['deliveryName'] = $client->getInfo()['name'];
			}
		}

		// 店铺所在类目
		if ($record['cate_id'] && ($ancestor = ScategoryModel::getAncestor($record['cate_id']))) {
			$array = [];
			foreach ($ancestor as $key => $value) {
				$array[] = $value['cate_name'];
			}
			$record['category'] = $array;
		}

		// 店铺在售商品总数
		$record['goods_count'] = GoodsModel::getCountOfStore($post->store_id);

		// 店铺是否被当前访客收藏
		if (!Yii::$app->user->isGuest) {
			$record['becollected'] = CollectModel::find()->where(['type' => 'store', 'item_id' => $post->store_id, 'userid' => Yii::$app->user->id])->exists();
		}

		// 店铺被收藏数
		$record['collects'] = CollectModel::find()->where(['type' => 'store', 'item_id' => $post->store_id])->count();

		// 店铺所在地省市区地址信息
		$regions = RegionModel::getArray($record['region_id']);
		return $respond->output(true, null, array_merge($record, is_array($regions) ? $regions : []));
	}

	/**
	 * 插入店铺信息
	 * @api 接口访问地址: https://www.xxx.com/api/store/add
	 */
	public function actionAdd()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(false)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['region_id', 'cate_id', 'sgrade']);

		$model = new \frontend\home\models\ApplyForm(['store_id' => Yii::$app->user->id]);
		if (!($store = $model->save($post, true))) {
			return $respond->output(Respond::CURD_FAIL, $model->errors);
		}
		foreach ($store as $key => $value) {
			if (!in_array($key, ['apply_remark', 'state', 'store_id'])) {
				unset($store->$key);
			}
		}

		return $respond->output(true, null, ArrayHelper::toArray($store));
	}

	/**
	 * 更新店铺信息
	 * @api 接口访问地址: https://www.xxx.com/api/store/update
	 */
	public function actionUpdate()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(true)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true);
		if (!($model = StoreModel::find()->where(['store_id' => Yii::$app->user->id])->one())) {
			return $respond->output(Respond::RECORD_NOTEXIST, Language::get('no_such_store'));
		}

		if ($post->store_name) {
			if (StoreModel::find()->where(['and', ['store_name' => $post->store_name], ['!=', 'store_id', Yii::$app->user->id]])->exists()) {
				return $respond->output(Respond::PARAMS_INVALID, Language::get('store_name_existed'));
			}
			$model->store_name = $post->store_name;
		}

		$fields = ['bustime', 'deliveryCode', 'store_logo', 'banner', 'pcbanner', 'tel', 'qq', 'latitude', 'longitude', 'radius', 'region_id', 'address', 'description'];
		foreach ($fields as $key => $value) {
			if (isset($post->$value)) {
				if (in_array($value, ['store_logo', 'banner', 'pcbanner'])) {
					$post->$value = $post->$value ? $this->getFileSavePath($post->$value) : '';

					// 删除旧文件
					if ($model->$value != $post->$value) {
						UploadedFileModel::deleteFileByName($model->$value);
					}
				}
				if ($value == 'bustime') {
					$post->$value = $post->$value ? implode('~', ArrayHelper::toArray($post->$value)) : '';
				}
				if ($value == 'deliveryCode' && empty($post->$value)) {
					return $respond->output(Respond::PARAMS_INVALID, Language::get('配送方式不能为空'));
				}

				$model->$value = $post->$value;
			}
		}

		//  注意考虑全删的情况
		if (isset($post->swiper)) {
			if ($array = ArrayHelper::toArray($post->swiper)) {
				foreach ($array as $key => $value) {
					$array[$key]['url'] = $this->getFileSavePath($value['url']);
				}
			}
			$model->swiper = $array ? json_encode($array) : '';
		}

		if (!$model->save()) {
			return $respond->output(Respond::CURD_FAIL, $model->errors);
		}

		if ($post->longitude || $post->latitude || $post->region_id) {
			$client = Plugin::getInstance('locality')->build($model->deliveryCode);
			if ($client && $client->isInstall()) {
				if ($regions = RegionModel::getNames($model->region_id)) {
					$model->address = $regions . $model->address;
				}
				if (!$client->createShop($model)) { // 同步更新同城配送门店位置信息
					return $respond->output(Respond::PARAMS_INVALID, $client->errors);
				}
			}
		}
		// 更新订单表中的商家名称
		if ($post->store_name) {
			OrderModel::updateAll(['seller_name' => $post->store_name], ['seller_id' => Yii::$app->user->id]);
		}

		return $respond->output(true, null, ['store_id' => Yii::$app->user->id]);
	}

	/**
	 * 删除店铺信息
	 * @api 接口访问地址: https://www.xxx.com/api/store/delete
	 */
	public function actionDelete() {}

	/**
	 * 获取店铺轮播图
	 * @api 接口访问地址: https://www.xxx.com/api/store/swiper
	 */
	public function actionSwiper()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(false)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['store_id']);

		$query = StoreModel::find()->select('swiper')->where(['store_id' => $post->store_id]);
		if (!($record = $query->asArray()->one())) {
			return $respond->output(Respond::RECORD_NOTEXIST, Language::get('no_such_store'));
		}

		$list = [];
		if ($record['swiper'] && ($swiper = json_decode($record['swiper'], true))) {
			foreach ($swiper as $key => $value) {
				$list[$key]['url'] = Formatter::path($value['url']);
				$list[$key]['link'] = $value['link'];
			}
		}

		return $respond->output(true, null, array_values($list));
	}

	/**
	 * 获取店铺动态评分
	 * @api 接口访问地址: https://www.xxx.com/api/store/dynamiceval
	 */
	public function actionDynamiceval()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(false)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['store_id']);
		$this->params = StoreModel::dynamicEvaluation($post->store_id);

		return $respond->output(true, null, $this->params);
	}

	/**
	 * 获取店铺主体信息（该信息为隐私数据，只允许获取自己店铺的）
	 * @api 接口访问地址: https://www.xxx.com/api/store/privacy
	 */
	public function actionPrivacy()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(true)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true);

		$fields = ['identity_front', 'identity_back', 'business_license'];
		$record = StoreModel::find()->select(implode(',', array_merge($fields, ['owner', 'identity_card'])))->where(['store_id' => Yii::$app->user->id])->asArray()->one();
		if ($record) {
			foreach ($fields as $field) {
				$record[$field] = Formatter::path($record[$field]);
			}
		}
		return $respond->output(true, null, $record);
	}

	/**
	 * 店铺收藏
	 * @api 接口访问地址: https://www.xxx.com/api/store/collect
	 */
	public function actionCollect()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(true)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['store_id']);

		// 验证店铺是否存在
		if (!isset($post->store_id) || !StoreModel::find()->where(['store_id' => $post->store_id])->exists()) {
			return $respond->output(Respond::RECORD_NOTEXIST, Language::get('no_such_store'));
		}

		// 如果是取消收藏
		if (isset($post->remove) && ($post->remove === true)) {
			if (!CollectModel::deleteAll(['and', ['item_id' => $post->store_id], ['type' => 'store', 'userid' => Yii::$app->user->id]])) {
				return $respond->output(Respond::CURD_FAIL, Language::get('collect_store_fail'));
			}
		} else {
			if (!($model = CollectModel::find()->where(['userid' => Yii::$app->user->id, 'type' => 'store', 'item_id' => $post->store_id])->one())) {
				$model = new CollectModel();
			}
			$model->userid = Yii::$app->user->id;
			$model->type = 'store';
			$model->item_id = $post->store_id;
			$model->add_time = Timezone::gmtime();
			if (!$model->save()) {
				return $respond->output(Respond::CURD_FAIL, Language::get('collect_store_fail'));
			}
		}

		return $respond->output(true, null, ['store_id' => $post->store_id]);
	}

	/**
	 * 获取店铺等级列表
	 * @api 接口访问地址: https://www.xxx.com/api/store/grades
	 */
	public function actionGrades()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(false)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['page', 'page_size']);

		$query = SgradeModel::find()->select('id,name,goods_limit,space_limit,charge,need_confirm,description')->orderBy(['sort_order' => SORT_ASC]);
		$page = Page::getPage($query->count(), $post->page_size, false, $post->page);
		$list = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
		$this->params = ['list' => $list, 'pagination' => Page::formatPage($page, false)];

		return $respond->output(true, null, $this->params);
	}

	/**
	 * 获取单条店铺等级信息
	 * @api 接口访问地址: https://www.xxx.com/api/store/grade
	 */
	public function actionGrade()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(false)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['id']);

		$record = SgradeModel::find()->select('id,name,goods_limit,space_limit,charge,need_confirm,description')
			->where(['id' => $post->id])->asArray()->one();
		if (!$record) {
			return $respond->output(Respond::RECORD_NOTEXIST, Language::get('no_such_sgrade'));
		}

		return $respond->output(true, null, $record);
	}

	/**
	 * 获取店铺首单立减优惠信息
	 * @api 接口访问地址: https://www.xxx.com/api/store/exclusive
	 */
	public function actionExclusive()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(false)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['store_id']);

		// 验证是否为新客[下过单且状态不是取消的就算]
		if (!OrderModel::find()->where([
			'and',
			['buyer_id' => Yii::$app->user->id, 'seller_id' => $post->store_id],
			['!=', 'status', Def::ORDER_CANCELED]
		])->exists()) {

			// 店铺首单立减
			$client = Plugin::getInstance('promote')->build('exclusive');

			$result = [];
			if ($client->checkAvailable($post->store_id, false)) {
				$promote = $client->getSetting($post->store_id);
				if (isset($promote['status']) && $promote['status']) {
					$result['decrease'] = $promote['rules']['decrease'];
				}
			}
		}

		return $respond->output(true, null, $result);
	}

	/**
	 * 获取指定店铺的满优惠信息
	 * @api 接口访问地址: https://www.xxx.com/api/store/fullprefer
	 */
	public function actionFullprefer()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(false)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['store_id']);

		// 店铺满优惠
		$client = Plugin::getInstance('promote')->build('fullprefer');

		$result = [];
		if ($client->checkAvailable($post->store_id, false)) {
			$promote = $client->getSetting($post->store_id);
			if (isset($promote['status']) && $promote['status']) {
				$result['amount'] = $promote['rules']['amount'];
				if ($promote['rules']['type'] == 'discount') {
					$result['decrease'] = $promote['rules']['amount'] * ((10 - $promote['rules']['discount']) / 10);
				} else {
					$result['decrease'] = $promote['rules']['decrease'];
				}
			}
		}

		return $respond->output(true, null, $result);
	}

	/**
	 * 获取指定的店铺的满包邮信息
	 * @api 接口访问地址: https://www.xxx.com/api/store/fullfree
	 */
	public function actionFullfree()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(false)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['store_id']);

		// 店铺包邮
		$client = Plugin::getInstance('promote')->build('fullfree');

		$result = [];
		if ($client->checkAvailable($post->store_id, false)) {
			$promote = $client->getSetting($post->store_id);
			if (isset($promote['status']) && $promote['status']) {
				if ($promote['rules']['amount'] > 0 || $promote['rules']['quantity'] > 0) {
					$result = $promote['rules'];
				}
			}
		}

		return $respond->output(true, null, $result);
	}

	/**
	 * 获取店铺二维码
	 * @api 接口访问地址: https://www.xxx.com/api/store/qrcode
	 */
	public function actionQrcode()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(false)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['store_id', 'width']);
		if (!isset($post->width)) $post->width = 300;

		// 验证店铺是否存在
		if (!isset($post->store_id) || !($store = StoreModel::find()->select('store_id,store_logo')->where(['store_id' => $post->store_id])->one())) {
			return $respond->output(Respond::RECORD_NOTEXIST, Language::get('store_invalid'));
		}

		$path = UploadedFileModel::getSavePath('qrcode/', $post->store_id);
		list(, $background, $width) = Page::generateQRCode($path, ['text' => Basewind::mobileUrl(true) . '/pages/store/index?id=' . $post->store_id, 'size' => $post->width]);
		if (!$background) {
			return $respond->output(Respond::HANDLE_INVALID, Language::get('handle_exception'));
		}

		// 生成带LOGO的二维码
		$config = [
			'image' => [['url' => Formatter::path($store->store_logo, 'store'), 'left' => round(($width - 60) / 2), 'top' => round(($width - 60) / 2), 'width' => 60, 'height' => 60]],
			'background' => $background
		];

		try {
			$qrcode = Page::createPoster($config, $path . "store.png", true);
			$result = str_replace(Yii::getAlias('@public'), Basewind::baseUrl(), $qrcode);
			return $respond->output(true, null, $result);
		} catch (\Exception $e) {
			return $respond->output(Respond::HANDLE_INVALID, $e->getMessage());
		}
	}

	private function getConditions($query, $post)
	{
		// 关键词搜索
		if (isset($post->keyword) && $post->keyword) {
			$query->andWhere(['like', 's.store_name', $post->keyword]);
		}

		// 店铺分类
		if (isset($post->cate_id) && $post->cate_id) {
			$allId = ScategoryModel::getDescendantIds($post->cate_id);
			$query->andWhere(['in', 'cs.cate_id', $allId]);
		}
		// 主体类型
		if (in_array($post->stype, ['company', 'personal'])) {
			$query->andWhere(['s.stype' => $post->stype]);
		}
		// 店铺等级
		if (isset($post->sgrade) && $post->sgrade) {
			$query->andWhere(['s.sgrade' => $post->sgrade]);
		}
		// 好评率
		if ($post->praise_rate) {
			$query->andWhere(['>=', 'praise_rate', $post->praise_rate]);
		}
		// 地区
		if (isset($post->region_id) && $post->region_id) {
			$allId = RegionModel::getDescendantIds($post->region_id);
			$query->andWhere(['in', 'region_id', $allId]);
		}
		// 排序
		if (isset($post->orderby) && in_array($post->orderby, ['credit_value|desc', 'praise_rate|desc', 'add_time|desc'])) {
			$orderBy = Basewind::trimAll(explode('|', $post->orderby));
			$query->orderBy([$orderBy[0] => strtolower($orderBy[1]) == 'asc' ? SORT_ASC : SORT_DESC]);
		} else $query->orderBy(['credit_value' => SORT_DESC, 'stype' => SORT_ASC, 'praise_rate' => SORT_DESC]);

		return $query;
	}

	/**
	 * 根据坐标计算店铺距离，然后根据距离倒序显示数据
	 * @param int $distance  要检索的距离（米）
	 */
	private function getNearbyConditions($post, $query, $distance = 2000)
	{
		$latitude = $post->latitude;
		$longitude = $post->longitude;

		if (!$latitude || $latitude < 0 || !$longitude || $longitude < 0) {
			return $query;
		}

		// 距离半径判断（效率慢）
		//$fields = "sqrt( ( ((".$longitude."-longitude)*PI()*12656*cos(((".$latitude."+latitude)/2)*PI()/180)/180) * ((".$longitude."-longitude)*PI()*12656*cos (((".$latitude."+latitude)/2)*PI()/180)/180) ) + ( ((".$latitude."-latitude)*PI()*12656/180) * ((".$latitude."-latitude)*PI()*12656/180) ) )/2 as distance";

		// 这个效率待验证
		$fields = "(6371 * acos(cos(radians($latitude)) * cos(radians(latitude)) * cos(radians(longitude) - radians($longitude)) + sin(radians($latitude)) * sin(radians(latitude))))*1000 as distance";
		$query->addSelect($fields)->orderBy(['distance' => SORT_ASC]);

		// 如限定距离，加这行
		//$query->having(['<', 'distance', $distance]);

		return $query;
	}

	private function formatImagesUrl($record)
	{
		$fields = ['store_logo', 'banner', 'pcbanner', 'credit_value', 'business_license'];
		foreach ($fields as $field) {
			//if(isset($record[$field])) {
			if ($field == 'credit_value') {
				$record['credit_image'] = StoreModel::computeCredit($record[$field]);
			} else {
				$record[$field] = Formatter::path($record[$field], $field == 'store_logo' ? 'store' : '');
			}
			//}
		}
		return $record;
	}

	/**
	 * 如果是本地存储，存相对地址，如果是云存储，存完整地址
	 */
	private function getFileSavePath($image = '')
	{
		if (stripos($image, Def::fileSaveUrl()) !== false) {
			return str_replace(Def::fileSaveUrl() . '/', '', $image);
		}
		return $image;
	}
}
