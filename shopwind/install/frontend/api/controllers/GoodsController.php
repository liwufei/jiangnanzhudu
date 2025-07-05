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
use common\models\GoodsModel;
use common\models\GoodsSpecModel;
use common\models\GoodsImageModel;
use common\models\OrderGoodsModel;
use common\models\GoodsQaModel;
use common\models\GoodsStatisticsModel;
use common\models\GcategoryModel;
use common\models\CollectModel;
use common\models\UploadedFileModel;
use common\models\CouponModel;
use common\models\TeambuyModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Timezone;
use common\library\Resource;
use common\library\Page;
use common\library\Def;
use common\library\Plugin;
use common\library\Weixin;

use frontend\api\library\Respond;
use frontend\api\library\Formatter;

/**
 * @Id GoodsController.php 2018.12.25 $
 * @author yxyc
 */

class GoodsController extends \common\base\BaseApiController
{
	/**
	 * 获取商品列表
	 * @api 接口访问地址: https://www.xxx.com/api/goods/list
	 */
	public function actionList()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(false)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['cate_id', 'store_id', 'page', 'page_size']);

		$query = GoodsModel::find()->alias('g')
			->select('g.goods_id,g.goods_name,g.tags,g.cate_id,g.brand,g.if_show,g.closed,g.add_time,g.default_spec,g.spec_qty,g.default_image,g.long_image,g.isnew,g.recommended,g.price,g.spec_qty,g.spec_name_1,g.spec_name_2,s.store_id,s.store_name,gs.mkprice,gs.stock,gs.weight,gst.views,gst.collects,gst.sales,gst.comments')
			->joinWith('store s', false)
			->joinWith('goodsStatistics gst', false)
			->joinWith('goodsDefaultSpec gs', false);

		if (isset($post->items) && !empty($post->items)) {
			$query->andWhere(['in', 'g.goods_id', explode(',', $post->items)]);
		}

		$model = new \frontend\api\models\GoodsForm();
		$query = $model->getBasicConditions($post, $query);

		// 获取距离
		if ($post->latitude && $post->longitude) {
			$query = $this->getNearbyConditions($post, $query);
		}

		$page = Page::getPage($query->count(), $post->page_size, false, $post->page);
		$list = $query->offset($page->offset)->limit($page->limit)->asArray()->all();

		$client = Plugin::getInstance('promote')->build();
		foreach ($list as $key => $value) {
			$list[$key]['default_image'] = Formatter::path($value['default_image'], 'goods');
			$list[$key]['long_image'] = Formatter::path($value['long_image']);
			$list[$key]['add_time'] = Timezone::localDate('Y-m-d H:i:s', $value['add_time']);
			$list[$key]['stocks'] = GoodsSpecModel::find()->where(['goods_id' => $value['goods_id']])->sum('stock');

			if ($post->queryspec == true) {
				$list[$key]['specs'] = GoodsSpecModel::find()->select('spec_id,stock,price')->where(['goods_id' => $value['goods_id']])->asArray()->all();
			}

			// 读取商品促销价格
			if (($promote = $client->getProInfo($value['goods_id'], $value['default_spec']))) {
				$list[$key]['promotion'] = $promote;
			}

			// 该商品是否参加拼团活动
			if (TeambuyModel::find()->where(['goods_id' => $value['goods_id'], 'status' => 1])->exists()) {
				$list[$key]['activity'][] = 'teambuy';
			}

			// 读取适用的优惠券
			$list[$key]['coupons'] = CouponModel::find()->select('id,name,money,amount')
				->where(['and', ['store_id' => $value['store_id']], ['available' => 1], ['<', 'start_time', Timezone::gmtime()], ['>', 'end_time', Timezone::gmtime()]])
				->andWhere(['<=', 'amount', $promote ? $promote['price'] : $value['price']])
				->limit(2)->orderBy(['money' => SORT_DESC])->asArray()->all();

			// 商品所在类目
			if ($value['cate_id'] && ($ancestor = GcategoryModel::getAncestor($value['cate_id']))) {
				$array = [];
				foreach ($ancestor as $k => $v) {
					$array[] = $v['cate_name'];
				}
				$list[$key]['category'] = $array;
			}
		}
		$this->params = array('list' => $list, 'pagination' => Page::formatPage($page, false));
		return $respond->output(true, Language::get('goods_list'), $this->params);
	}

	/**
	 * 搜索商品列表
	 * @api 接口访问地址: https://www.xxx.com/api/goods/search
	 */
	public function actionSearch()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(false)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['cate_id', 'store_id', 'region_id', 'page', 'page_size']);

		$query = GoodsModel::find()->alias('g')
			->select('g.goods_id,g.goods_name,g.cate_id,g.brand,g.if_show,g.closed,g.isnew,g.add_time,g.spec_qty,g.spec_name_1,g.spec_name_2,g.default_spec,g.default_image,g.long_image,g.recommended,g.price,s.store_id,s.store_name,gs.mkprice,gs.stock,gst.views,gst.collects,gst.sales,gst.comments')
			->joinWith('goodsStatistics gst', false)
			->where(['s.state' => 1]);

		$model = new \frontend\api\models\GoodsForm();
		$query = $model->getConditions($post, $query);

		$page = Page::getPage($query->count(), $post->page_size, false, $post->page);
		$list = $query->offset($page->offset)->limit($page->limit)->asArray()->all();

		$client = Plugin::getInstance('promote')->build();
		foreach ($list as $key => $value) {
			$list[$key]['default_image'] = Formatter::path($value['default_image'], 'goods');
			$list[$key]['long_image'] = Formatter::path($value['long_image']);
			$list[$key]['add_time'] = Timezone::localDate('Y-m-d H:i:s', $value['add_time']);
			$list[$key]['stocks'] = GoodsSpecModel::find()->where(['goods_id' => $value['goods_id']])->sum('stock');

			// 商品从图(不包含主图)
			$otherImages = GoodsImageModel::find()->select('thumbnail')->where(['goods_id' => $value['goods_id']])->andWhere(['!=', 'sort_order', 1])->orderBy(['sort_order' => SORT_ASC, 'image_id' => SORT_ASC])->column();
			foreach ($otherImages as $k => $image) {
				$otherImages[$k] = Formatter::path($image);
			}
			$list[$key]['other_image'] = $otherImages;

			// 读取商品促销价格
			if (($promote = $client->getProInfo($value['goods_id'], $value['default_spec']))) {
				$list[$key]['promotion'] = $promote;
			}

			// 该商品是否参加拼团活动
			if (TeambuyModel::find()->where(['goods_id' => $value['goods_id'], 'status' => 1])->exists()) {
				$list[$key]['activity'][] = 'teambuy';
			}

			// 读取适用的优惠券
			$list[$key]['coupons'] = CouponModel::find()->select('id,name,money,amount')
				->where(['and', ['store_id' => $value['store_id']], ['available' => 1], ['<', 'start_time', Timezone::gmtime()], ['>', 'end_time', Timezone::gmtime()]])
				->andWhere(['<=', 'amount', $promote ? $promote['price'] : $value['price']])
				->limit(2)->orderBy(['money' => SORT_DESC])->asArray()->all();

			// 商品所在类目
			if ($value['cate_id'] && ($ancestor = GcategoryModel::getAncestor($value['cate_id']))) {
				$array = [];
				foreach ($ancestor as $k => $v) {
					$array[] = $v['cate_name'];
				}
				$list[$key]['category'] = $array;
			}
		}
		$this->params = ['list' => $list, 'pagination' => Page::formatPage($page, false)];
		$this->params = array_merge($this->params, $model->getSelectors($post), $model->getFilters($post));

		return $respond->output(true, Language::get('goods_list'), $this->params);
	}

	/**
	 * 商品推送/发现好物数据（或感兴趣的商品）
	 * @api 接口访问地址: https://www.xxx.com/api/goods/push
	 * @var int $limit 需要获取的数量
	 * @var string|int $cate_id  用逗号隔开的多个id或一个id，如：cate_id=1 或 cate_id=1,2,3
	 * @var int $store_id
	 */
	public function actionPush()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(true)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['store_id', 'limit']);

		$list = $allId = [];

		// 必须指定分类
		if (!isset($post->cate_id) || !$post->cate_id) {
			return $respond->output(true);
		}

		$array = explode(',', $post->cate_id);
		foreach ($array as $key => $value) {
			if (($value = intval(trim($value))) && ($descendant = GcategoryModel::getDescendantIds($value))) {
				$allId = array_merge($allId, $descendant);
			}
		}
		if (!($allId = array_unique($allId))) {
			return $respond->output(true);
		}

		// 最多获取100个数据
		$query = GoodsModel::find()->select('goods_id,goods_name,default_image,long_image,price')->where(['in', 'cate_id', $allId])->andWhere(['if_show' => 1, 'closed' => 0])->limit(100);
		if (isset($post->store_id) && $post->store_id > 0) {
			$query->andWhere(['store_id' => $post->store_id]);
		}

		// 如果没有限制数量，默认从总数中随机取10个
		if (!isset($post->limit) || $post->limit <= 0) {
			$post->limit = 10;
		}
		if ($query->count() <= $post->limit) {
			$list = $query->asArray()->all();
		} else {

			// 取得随机数
			$array = range(0, $query->count() - 1);
			shuffle($array);
			$array = array_slice($array, -$post->limit);

			$all = $query->asArray()->all();
			foreach ($array as $key => $value) {
				$list[$value] = $all[$value];
			}
			array_values($list);
		}

		foreach ($list as $key => $value) {
			$list[$key]['default_image'] = Formatter::path($value['default_image'], 'goods');
			$list[$key]['long_image'] = Formatter::path($value['long_image']);
		}

		return $respond->output(true, null,  ['list' => $list]);
	}

	/**
	 * 获取商品单条信息
	 * @api 接口访问地址: https://www.xxx.com/api/goods/read
	 */
	public function actionRead()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(false)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['goods_id']);

		$model = new \frontend\api\models\GoodsForm();
		if ($record = $model->formData($post)) {
			GoodsStatisticsModel::updateStatistics($post->goods_id, 'views'); // 更新浏览量
		}

		return $respond->output(true, null, $record);
	}

	/**
	 * 获取商品描述信息
	 * @api 接口访问地址: https://www.xxx.com/api/goods/description
	 */
	public function actionDescription()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(false)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['goods_id']);

		$record = GoodsModel::find()->select('description')->where(['goods_id' => $post->goods_id])->asArray()->one();
		if ($record) {
			return $respond->output(Respond::RECORD_NOTEXIST, Language::get('no_such_goods'));
		}
		return $respond->output(true, null, $record);
	}

	/**
	 * 插入商品信息
	 * @api 接口访问地址: https://www.xxx.com/api/goods/add
	 */
	public function actionAdd()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(true)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['recommended', 'if_show', 'isnew', 'cate_id', 'spec_qty']);

		$model = new \frontend\home\models\GoodsForm(['store_id' => Yii::$app->user->id]);
		if (!$model->save($post, true)) {
			return $respond->output(Respond::HANDLE_INVALID, $model->errors);
		}

		return $respond->output(true);
	}

	/**
	 * 更新商品信息
	 * @api 接口访问地址: https://www.xxx.com/api/goods/update
	 */
	public function actionUpdate()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(true)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['goods_id', 'recommended', 'if_show', 'cate_id', 'spec_qty']);

		$model = new \frontend\home\models\GoodsForm(['store_id' => Yii::$app->user->id, 'goods_id' => $post->goods_id]);
		if (!$model->save($post, true)) {
			return $respond->output(Respond::HANDLE_INVALID, $model->errors);
		}

		return $respond->output(true);
	}

	/**
	 * 删除商品信息
	 * @api 接口访问地址: https://www.xxx.com/api/goods/delete
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

		if (!$post->goods_id || !(GoodsModel::find()->where(['goods_id' => $post->goods_id, 'store_id' => Yii::$app->user->id])->exists())) {
			return $respond->output(Respond::RECORD_NOTEXIST, Language::get('on_such_goods'));
		}

		$model = new \frontend\home\models\GoodsDeleteForm(['goods_id' => $post->goods_id, 'store_id' => Yii::$app->user->id]);
		if (!$model->delete($post)) {
			return $respond->output(Respond::HANDLE_INVALID, Language::get('handle_fail'));
		}

		return $respond->output(true);
	}

	/**
	 * 获取商品规格列表
	 * @api 接口访问地址: https://www.xxx.com/api/goods/specs
	 */
	public function actionSpecs()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(false)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['goods_id']);
		$query = GoodsSpecModel::find()->alias('gs')
			->select('gs.goods_id,gs.spec_id,gs.spec_1,gs.spec_2,gs.price,gs.mkprice,gs.stock,gs.weight,gs.image,gst.sales,g.goods_name,g.default_image,g.spec_qty,g.spec_name_1,g.spec_name_2')
			->joinWith('goods g', false)
			->joinWith('goodsStatistics gst', false)
			->where(['gs.goods_id' => $post->goods_id])
			->orderBy(['sort_order' => SORT_ASC, 'spec_id' => SORT_ASC, 'spec_1' => SORT_ASC, 'spec_2' => SORT_ASC]);

		// 如果筛选规格一
		if (isset($post->spec_1) && $post->spec_1) {
			$query->andWhere(['spec_1' => $post->spec_1]);
		}
		// 如果筛选规格二
		if (isset($post->spec_2) && $post->spec_2) {
			$query->andWhere(['spec_2' => $post->spec_2]);
		}
		$list = $query->asArray()->all();

		$client = Plugin::getInstance('promote')->build();
		foreach ($list as $key => $value) {
			$list[$key]['default_image'] = Formatter::path($value['default_image'], 'goods');

			// 确保有规格图的时候，相同规格也有图
			if (empty($value['image'])) {
				foreach ($list as $v) {
					if ($value['spec_1'] == $v['spec_1'] && $value['goods_id'] == $v['goods_id'] && $v['image']) {
						$value['image'] = $v['image'];
						break;
					}
				}
			}
			$list[$key]['image'] = Formatter::path($value['image']);

			// 读取商品促销价格
			if (($promote = $client->getProInfo($value['goods_id'], $value['spec_id']))) {
				$list[$key]['promotion'] = $promote;
			}
		}

		return $respond->output(true, null, ['list' => $list]);
	}

	/**
	 * 获取商品规格单条信息
	 * @api 接口访问地址: https://www.xxx.com/api/goods/spec
	 */
	public function actionSpec()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(false)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['spec_id']);

		$record = GoodsSpecModel::find()->select('spec_id,goods_id,spec_1,spec_2,price,mkprice,stock,weight,image')->where(['spec_id' => $post->spec_id])->asArray()->one();
		if (!$record) {
			return $respond->output(Respond::RECORD_NOTEXIST, Language::get('goods_spec_invalid'));
		}
		$record['image'] = Formatter::path($record['image']);

		return $respond->output(true, null, $record);
	}

	/**
	 * 获取商品价格单条信息（考虑促销价格）
	 * @api 接口访问地址: https://www.xxx.com/api/goods/price
	 */
	public function actionPrice()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(false)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['spec_id']);

		$record = GoodsSpecModel::find()->select('goods_id,spec_id,price,mkprice')->where(['spec_id' => $post->spec_id])->asArray()->one();
		if (!($record)) {
			return $respond->output(Respond::RECORD_NOTEXIST, Language::get('no_such_goods'));
		}

		// 读取商品促销价格
		$client = Plugin::getInstance('promote')->build();
		$promote = $client->getProInfo($record['goods_id'], $post->spec_id);
		$this->params = array_merge($record, ['promotion' => $promote]);

		return $respond->output(true, null, $this->params);
	}

	/**
	 * 获取商品上下架状态信息
	 * @api 接口访问地址: https://www.xxx.com/api/goods/shelfstate
	 */
	public function actionShelfstate()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(false)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['goods_id']);

		$query = GoodsModel::find()->select('if_show,closed')->where(['goods_id' => $post->goods_id])->one();
		if (!$query) {
			return $respond->output(Respond::RECORD_NOTEXIST, Language::get('goods_invalid'));
		}
		$record = ['goods_id' => $post->goods_id, 'state' => ($query->if_show && !$query->closed) ? 1 : 0];

		return $respond->output(true, null, $record);
	}

	/**
	 * 获取商品相册图片列表
	 * @api 接口访问地址: https://www.xxx.com/api/goods/images
	 */
	public function actionImages()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(false)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['goods_id']);

		$list = GoodsImageModel::find()->select('goods_id,image_id,image_url,thumbnail')->where(['goods_id' => $post->goods_id])->orderBy(['sort_order' => SORT_ASC, 'image_id' => SORT_ASC])->asArray()->all();
		foreach ($list as $key => $value) {
			$list[$key]['image_url'] = Formatter::path($value['image_url']);
			$list[$key]['thumbnail'] = Formatter::path($value['thumbnail']);
		}

		return $respond->output(true, null, ['list' => $list]);
	}

	/**
	 * 获取商品相册单张图片信息
	 * @api 接口访问地址: https://www.xxx.com/api/goods/image
	 */
	public function actionImage()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(false)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['image_id']);

		$record = GoodsImageModel::find()->where(['goods_id' => $post->goods_id])->orderBy(['sort_order' => SORT_ASC, 'image_id' => SORT_ASC])->asArray()->one();
		$record['image_url'] = Formatter::path($record['image_url']);
		$record['thumbnail'] = Formatter::path($record['thumbnail']);

		return $respond->output(true, null, $record);
	}

	/**
	 * 获取商品描述图片列表
	 * @api 接口访问地址: https://www.xxx.com/api/goods/descimages
	 */
	public function actionDescimages()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(false)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['goods_id']);

		$uploadedfiles = UploadedFileModel::find()->alias('f')->select('f.file_id,f.file_path,gi.goods_id')
			->joinWith('goodsImage gi', false)
			->where(['item_id' => $post->goods_id, 'belong' => 0])
			->orderBy(['sort_order' => SORT_ASC, 'file_id' => SORT_ASC])
			->asArray()->all();

		$list = [];
		if ($uploadedfiles) {
			foreach ($uploadedfiles as $key => $file) {
				if (!$file['goods_id']) {
					$list[] = Formatter::path($file['file_path']);
				}
			}
		}

		return $respond->output(true, null, ['list' => $list]);
	}

	/**
	 * 获取商品属性列表信息
	 * @api 接口访问地址: https://www.xxx.com/api/goods/attributes
	 */
	public function actionAttributes()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(false)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['goods_id']);

		$model = new \frontend\api\models\GoodsForm();
		if (!isset($post->goods_id) || !GoodsModel::find()->where(['goods_id' => $post->goods_id])->exists()) {
			return $respond->output(Respond::RECORD_NOTEXIST, Language::get('goods_invalid'));
		}

		return $respond->output(true, null, ['list' => $model->getGoodProps($post->goods_id)]);
	}

	/**
	 * 获取库存预警商品列表
	 * @api 接口访问地址: https://www.xxx.com/api/goods/stockwarn
	 */
	public function actionStockwarn()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(true)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['store_id', 'quantity', 'page_size', 'page']);
		$array = GoodsSpecModel::find()->select('goods_id')->where(['<', 'stock', $post->quantity ? $post->quantity : 100])->indexBy('goods_id')->asArray()->column();

		$query = GoodsModel::find()->select('goods_id,store_id,goods_name,brand,default_image as goods_image')
			->where(['in', 'goods_id', $array]);

		if ($post->store_id) {
			$query->andWhere(['store_id' => $post->store_id]);
		}
		if ($post->keyword) {
			$query->andWhere(['like', 'goods_name', $post->keyword]);
		}
		if ($post->brand) {
			$query->andWhere(['like', 'brand', $post->brand]);
		}

		$page = Page::getPage($query->count(), $post->page_size, false, $post->page);
		$list = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
		foreach ($list as $key => $value) {
			$list[$key]['goods_image'] = Formatter::path($value['goods_image'], 'goods');
			$list[$key]['stocks'] = GoodsModel::getStocks($value['goods_id']);
			$list[$key]['items'] = GoodsSpecModel::find()->select('spec_id,stock')->where(['goods_id' => $value['goods_id']])->asArray()->all();
		}

		return $respond->output(true, Language::get('goods_list'), ['list' => $list, 'pagination' => Page::formatPage($page, false)]);
	}

	/**
	 * 获取商品销售记录列表
	 * @api 接口访问地址: https://www.xxx.com/api/goods/salelogs
	 */
	public function actionSalelogs()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(false)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['goods_id', 'page', 'page_size']);

		if (!isset($post->goods_id) || !GoodsModel::find()->where(['goods_id' => $post->goods_id])->exists()) {
			return $respond->output(Respond::RECORD_NOTEXIST, Language::get('goods_invalid'));
		}

		$query = OrderGoodsModel::find()->alias('og')->select('og.id,og.spec_id,og.goods_id,og.goods_name,og.goods_image,og.specification,og.price,og.quantity,og.evaluation,o.evaluation_status,o.buyer_id,o.buyer_name,o.add_time,o.anonymous')
			->joinWith('order o', false)
			->where(['goods_id' => $post->goods_id, 'o.status' => Def::ORDER_FINISHED])
			->orderBy(['add_time' => SORT_DESC]);

		$page = Page::getPage($query->count(), $post->page_size, false, $post->page);
		$list = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
		foreach ($list as $key => $value) {
			$list[$key]['add_time'] = Timezone::localDate('Y-m-d H:i:s', $value['add_time']);
			$list[$key]['goods_image'] = Formatter::path($value['goods_image'], 'goods');
		}

		return $respond->output(true, null, ['list' => $list, 'pagination' => Page::formatPage($page, false)]);
	}

	/**
	 * 获取商品评价记录列表
	 * @api 接口访问地址: https://www.xxx.com/api/goods/comments
	 * @var boolean $commented 是否只读有评论内容的记录
	 */
	public function actionComments()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(false)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['goods_id', 'page', 'page_size']);

		$query = OrderGoodsModel::find()->alias('og')
			->select('og.id,og.spec_id,og.goods_id,og.goods_name,og.goods_image,og.specification,og.evaluation,og.comment,og.reply_comment,og.reply_time,og.images,o.buyer_id,o.buyer_name,o.evaluation_time')
			->joinWith('order o', false)
			->where(['o.evaluation_status' => 1])
			->orderBy(['o.evaluation_time' => SORT_DESC]);

		// 指定商品
		if ($post->goods_id) {
			if (!GoodsModel::find()->where(['goods_id' => $post->goods_id])->exists()) {
				return $respond->output(Respond::RECORD_NOTEXIST, Language::get('goods_invalid'));
			}
			$query->andWhere(['goods_id' => $post->goods_id]);
		}

		// 指定店铺
		if ($post->store_id) {
			$query->andWhere(['o.seller_id' => $post->store_id]);
		}

		// 只获取有评价内容的记录
		if (isset($post->commented) && $post->commented === true) {
			$query->andWhere(['>', 'comment', '']);
		}
		// 带图
		if ($post->hasimg) $query->andWhere(['>', 'images', '']);

		// 数据库字段记录的是5分制，3分为中评，小于3分是差评，大于3分是好评
		if (isset($post->level)) {
			if ($post->level == 'bad') $query->andWhere(['<', 'evaluation', 3]);
			if ($post->level == 'middle') $query->andWhere(['=', 'evaluation', 3]);
			if ($post->level == 'good') $query->andWhere(['>', 'evaluation', 3]);
		}

		$page = Page::getPage($query->count(), $post->page_size, false, $post->page);
		$list = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
		foreach ($list as $key => $value) {
			$list[$key]['reply_time'] = Timezone::localDate('Y-m-d', $value['reply_time']);
			$list[$key]['evaluation_time'] = Timezone::localDate('Y-m-d', $value['evaluation_time']);

			$user = UserModel::find()->select('portrait,nickname')->where(['userid' => $value['buyer_id']])->one();
			$list[$key]['buyer_portrait'] = Formatter::path($user->portrait, 'portrait');
			$list[$key]['buyer_nickname'] = $user->nickname;

			// 买家晒图
			if ($value['images']) {
				$list[$key]['images'] = json_decode($value['images']);
			}
		}

		$this->params = ['list' => $list, 'pagination' => Page::formatPage($page, false)];
		return $respond->output(true, null, array_merge($this->params, GoodsStatisticsModel::getCommectStatistics($post->goods_id)));
	}

	/**
	 * 获取商品问答列表
	 * @api 接口访问地址: https://www.xxx.com/api/goods/qas
	 * @var boolean $replied 是否只读有回复内容的记录
	 */
	public function actionQas()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(false)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['goods_id', 'page', 'page_size']);

		if (!isset($post->goods_id) || !GoodsModel::find()->where(['goods_id' => $post->goods_id])->exists()) {
			return $respond->output(Respond::RECORD_NOTEXIST, Language::get('goods_invalid'));
		}

		$query = GoodsQaModel::find()->alias('ga')->select('ga.id,ga.item_id,ga.item_name,ga.content,ga.reply_content,ga.add_time,ga.reply_time,ga.if_new,u.userid,u.username,u.nickname')->joinWith('user u', false)->where(['item_id' => $post->goods_id, 'type' => 'goods'])->orderBy(['add_time' => SORT_DESC]);

		// 只取有回复内容的问答
		if (isset($post->replied) && $post->replied === true) {
			$query->andWhere(['>', 'reply_content', '']);
		}
		$page = Page::getPage($query->count(), $post->page_size, false, $post->page);
		$list = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
		foreach ($list as $key => $value) {
			$list[$key]['add_time'] = Timezone::localDate('Y-m-d H:i:s', $value['add_time']);
			$list[$key]['reply_time'] = Timezone::localDate('Y-m-d H:i:s', $value['reply_time']);
		}

		return $respond->output(true, null, ['list' => $list, 'pagination' => Page::formatPage($page, false)]);
	}

	/**
	 * 插入商品问答信息
	 * @api 接口访问地址: https://www.xxx.com/api/goods/qaadd
	 * @var boolean $anonymous 是否匿名咨询
	 */
	public function actionQaadd()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(true)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['goods_id']);

		if (!isset($post->goods_id) || !($goods = GoodsModel::find()->select('store_id,goods_name')->where(['goods_id' => $post->goods_id])->one())) {
			return $respond->output(Respond::RECORD_NOTEXIST, Language::get('goods_invalid'));
		}

		if ($goods->store_id == Yii::$app->user->id) {
			return $respond->output(Respond::HANDLE_INVALID, Language::get('not_question_self'));
		}

		if (!isset($post->content) || empty($post->content)) {
			return $respond->output(Respond::PARAMS_INVALID, Language::get('content_invalid'));
		}

		$model = new GoodsQaModel();
		$model->content = $post->content;
		$model->type = 'goods';
		$model->item_id = $post->goods_id;
		$model->item_name = addslashes($goods->goods_name);
		$model->store_id = $goods->store_id;
		//$model->email = isset($post->email) ? $post->email : '';
		$model->userid = (isset($post->anonymous) && $post->anonymous === true) ? 0 : Yii::$app->user->id;
		$model->add_time = Timezone::gmtime();
		if (!$model->save()) {
			return $respond->output(Respond::CURD_FAIL, Language::get('content_fail'));
		}
		return $respond->output(true, null, ['id' => $model->id]);
	}

	/**
	 * 商品收藏
	 * @api 接口访问地址: https://www.xxx.com/api/goods/collect
	 * @var int $goods_id
	 * @var boolean $remove 取消收藏
	 */
	public function actionCollect()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(true)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['goods_id']);

		// 验证商品是否存在
		if (!isset($post->goods_id) || !GoodsModel::find()->where(['goods_id' => $post->goods_id])->exists()) {
			return $respond->output(Respond::RECORD_NOTEXIST, Language::get('goods_invalid'));
		}

		// 如果是取消收藏
		if (isset($post->remove) && ($post->remove === true)) {
			if (!CollectModel::deleteAll(['and', ['item_id' => $post->goods_id], ['type' => 'goods', 'userid' => Yii::$app->user->id]])) {
				return $respond->output(Respond::CURD_FAIL, Language::get('collect_goods_fail'));
			}
		} else {
			if (!($model = CollectModel::find()->where(['userid' => Yii::$app->user->id, 'type' => 'goods', 'item_id' => $post->goods_id])->one())) {
				$model = new CollectModel();
			}
			$model->userid = Yii::$app->user->id;
			$model->type = 'goods';
			$model->item_id = $post->goods_id;
			$model->add_time = Timezone::gmtime();
			if (!$model->save()) {
				return $respond->output(Respond::CURD_FAIL, Language::get('collect_goods_fail'));
			}
			// 更新被收藏总次数
			GoodsStatisticsModel::updateStatistics($post->goods_id, 'collects');
		}

		return $respond->output(true, null, ['goods_id' => $post->goods_id]);
	}

	/**
	 * 批量获取指定商品列表 （即将废弃，请改用goods/list接口）
	 * @api 接口访问地址: https://www.xxx.com/api/goods/query
	 * @var string|int $goods_id
	 * @example goods_id=1 或 goods_id=1,2,3
	 * @var string|int $spec_id
	 * @example spec_id=1 或 spec_id=1,2,3
	 */
	public function actionQuery()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(true)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true);

		if ((!isset($post->goods_id) || !$post->goods_id) && (!isset($post->spec_id) || !$post->spec_id)) {
			return $respond->output(true);
		}
		$allId = explode(',', $post->goods_id ? $post->goods_id : $post->spec_id);
		foreach ($allId as $key => $value) {
			$allId[$key] = intval(trim($value));
		}

		// 通过商品ID查数据
		if ($post->goods_id) {
			$query = GoodsModel::find()->alias('g')->select('g.default_spec as spec_id')->andWhere(['in', 'g.goods_id', $allId])->indexBy('goods_id');
		}
		// 通过商品SKU查数据
		else {
			$query = GoodsSpecModel::find()->alias('gs')->select('gs.spec_id')->joinWith('goods g', false)->andWhere(['in', 'gs.spec_id', $allId])->indexBy('spec_id');
		}
		$list = $query->addSelect('g.goods_id,g.goods_name,g.default_image,g.price,gst.sales')->joinWith('goodsStatistics gst', false)->asArray()->all();

		foreach ($list as $key => $value) {
			$list[$key]['default_image'] = Formatter::path($value['default_image'], 'goods');
		}

		$all = [];
		foreach ($allId as $id) {
			if (isset($list[$id])) {
				$all[] = $list[$id];
			}
		}

		return $respond->output(true, null, ['list' => $all]);
	}

	/**
	 * 获取商品推广海报
	 * @api 接口访问地址: https://www.xxx.com/api/goods/qrcode
	 */
	public function actionQrcode()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(false)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['goods_id']);

		// 验证商品是否存在
		if (!isset($post->goods_id) || !($goods = GoodsModel::find()->alias('g')->select('g.goods_id,g.goods_name,g.default_image,g.default_spec as spec_id,g.price,s.store_id,s.store_name,s.store_logo')->joinWith('store s', false)->where(['goods_id' => $post->goods_id])->asArray()->one())) {
			return $respond->output(Respond::RECORD_NOTEXIST, Language::get('goods_invalid'));
		}

		$path = UploadedFileModel::getSavePath('qrcode/goods/', 0, $post->goods_id);
		$wxacode = $path . "wxacode.png";
		if (!file_exists($wxacode)) {
			$response = Weixin::getInstance(null, 0, 'applet')->getWxaCode(['path' => $post->page, 'width' => 280], $wxacode);
			if ($response === false) {

				// 如果没有上线小程序，则无法获取小程序码，则使用H5页二维码取代
				list($wxacode) = Page::generateQRCode('qrcode/goods/', ['text' => Basewind::mobileUrl(true) . $post->page, 'size' => 280]);
				if (!$wxacode) {
					return $respond->output(Respond::HANDLE_INVALID, Language::get('handle_exception'));
				}
			}
		}
		$goods['default_image'] = Formatter::path($goods['default_image'], 'goods');

		// 读取促销价格
		$client = Plugin::getInstance('promote')->build();
		if (($promote = $client->getProInfo($goods['goods_id'], $goods['spec_id']))) {
			$goods['price'] = $promote['price'];
		}

		list($name, $logo, $background) = [$goods['store_name'], Formatter::path($goods['store_logo'], 'store'),  Resource::getResourceUrl('system/goodsQrcode.jpg')];

		// 生成海报
		$config = [
			'text' => [['text' => $name, 'left' => 280, 'top' => 400, 'fontSize' => 40], ['text' => mb_substr($goods['goods_name'], 0, 14, Yii::$app->charset), 'left' => 80, 'top' => 1440, 'fontSize' => 30], ['text' => mb_substr($goods['goods_name'], 14, 14, Yii::$app->charset), 'left' => 80, 'top' => 1500, 'fontSize' => 30], ['text' => '￥' . $goods['price'], 'left' => 80, 'top' => 1640, 'fontSize' => 50, 'fontColor' => '255,0,0']],
			'image' => [['url' => $logo, 'left' => 80, 'top' => 310, 'width' => 160, 'height' => 160], ['url' => $wxacode, 'left' => 670, 'top' => 1370, 'width' => 250, 'height' => 250], ['url' => $goods['default_image'], 'left' => 80, 'top' => 500, 'width' => 840, 'height' => 840]],
			'background' => $background
		];

		try {
			$qrcode = Page::createPoster($config, $path . "poster.png");
			$result['poster'] = str_replace(Yii::getAlias('@public'), Basewind::baseUrl(), $qrcode);
			return $respond->output(true, null, $result);
		} catch (\Exception $e) {
			return $respond->output(Respond::HANDLE_INVALID, $e->getMessage());
		}
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

		// 这个效率待验证
		$fields = "(6371 * acos(cos(radians($latitude)) * cos(radians(latitude)) * cos(radians(longitude) - radians($longitude)) + sin(radians($latitude)) * sin(radians(latitude))))*1000 as distance";
		$query->addSelect($fields)->addOrderBy(['distance' => SORT_ASC]);

		// 如限定距离，加这行
		//$query->having(['<', 'distance', $distance]);

		return $query;
	}
}
