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
use yii\helpers\Url;
use yii\helpers\Json;

use common\models\GoodsModel;
use common\models\StoreModel;
use common\models\GcategoryModel;
use common\models\RecommendModel;
use common\models\BrandModel;
use common\models\GoodsImageModel;
use common\models\GoodsSpecModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Timezone;
use common\library\Message;
use common\library\Resource;
use common\library\Page;
use common\library\Plugin;

/**
 * @Id GoodsController.php 2018.8.8 $
 * @author mosir
 */

class GoodsController extends \common\base\BaseAdminController
{
	public function actionIndex()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['limit', 'page', 'cate_id']);

		if (!Yii::$app->request->isAjax) {
			$this->params['gcategories'] = GcategoryModel::getOptions(0, -1, null, 2);
			$this->params['filtered'] = $this->getConditions($post);

			$this->params['_head_tags'] = Resource::import(['style' => 'javascript/dialog/dialog.css']);
			$this->params['_foot_tags'] = Resource::import(['script' => 'javascript/jquery.ui/jquery.ui.js,javascript/dialog/dialog.js,javascript/inline_edit.js']);

			$this->params['page'] = Page::seo(['title' => Language::get('goods_list')]);
			return $this->render('../goods.index.html', $this->params);
		} else {
			$query = GoodsModel::find()->alias('g')->select('g.goods_id,goods_name,default_image,default_spec,store_id,cate_id,price,brand,if_show,closed,cate_name,add_time,views')
				->joinWith('goodsStatistics gst', false);
			$query->where(['closed' => 0]);
			$query = $this->getConditions($post, $query)->orderBy(['goods_id' => SORT_DESC]);

			$page = Page::getPage($query->count(), $post->limit ? $post->limit : 10);
			$list = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
			foreach ($list as $key => $value) {
				$list[$key]['default_image'] = Page::urlFormat($value['default_image'], 'goods');
				$list[$key]['cate_name'] = GcategoryModel::formatCateName($value['cate_name'], false, ' / ');
				$list[$key]['add_time'] = Timezone::localDate('Y-m-d H:i:s', $value['add_time']);
				$list[$key]['store_name'] = StoreModel::find()->select('store_name')->where(['store_id' => $value['store_id']])->scalar();
			}

			return Json::encode(['code' => 0, 'msg' => '', 'count' => $query->count(), 'data' => $list]);
		}
	}

	public function actionVerify()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['limit', 'page', 'cate_id']);

		if (!Yii::$app->request->isAjax) {
			$this->params['gcategories'] = GcategoryModel::getOptions(0, -1, null, 2);
			$this->params['filtered'] = $this->getConditions($post);

			$this->params['_head_tags'] = Resource::import(['style' => 'javascript/dialog/dialog.css']);
			$this->params['_foot_tags'] = Resource::import(['script' => 'javascript/jquery.ui/jquery.ui.js,javascript/dialog/dialog.js,javascript/inline_edit.js']);

			$this->params['page'] = Page::seo(['title' => Language::get('goods_list')]);
			return $this->render('../goods.verify.html', $this->params);
		} else {
			$query = GoodsModel::find()->alias('g')
				->select('g.goods_id,goods_name,default_image,store_id,cate_id,price,brand,if_show,closed,cate_name,add_time,views')
				->joinWith('goodsStatistics gst', false)
				->where(['closed' => 1]);
			$query = $this->getConditions($post, $query)->orderBy(['goods_id' => SORT_DESC]);

			$page = Page::getPage($query->count(), $post->limit ? $post->limit : 10);
			$list = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
			foreach ($list as $key => $value) {
				$list[$key]['default_image'] = Page::urlFormat($value['default_image'], 'goods');
				$list[$key]['cate_name'] = GcategoryModel::formatCateName($value['cate_name'], false, ' / ');
				$list[$key]['add_time'] = Timezone::localDate('Y-m-d H:i:s', $value['add_time']);
				$list[$key]['store_name'] = StoreModel::find()->select('store_name')->where(['store_id' => $value['store_id']])->scalar();
			}

			return Json::encode(['code' => 0, 'msg' => '', 'count' => $query->count(), 'data' => $list]);
		}
	}

	public function actionEdit()
	{
		if (!Yii::$app->request->isPost) {
			$this->params['gcategories'] = GcategoryModel::find()->select('cate_name')->where(['parent_id' => 0, 'store_id' => 0])->indexBy('cate_id')->orderBy(['sort_order' => SORT_ASC, 'cate_id' => SORT_ASC])->column();
			$this->params['brandList'] = BrandModel::find()->where(['if_show' => 1])->asArray()->all();

			if (($id = Yii::$app->request->get('id')) && is_numeric($id)) {
				$this->params['goods'] = GoodsModel::find()->select('cate_id,cate_name,brand,closed')->where(['goods_id' => $id])->asArray()->one();
			}

			$this->params['_foot_tags'] = Resource::import('javascript/mlselection.js');
			$this->params['page'] = Page::seo(['title' => Language::get('goods_edit')]);
			return $this->render('../goods.form.html', $this->params);
		} else {
			$post = Basewind::trimAll(Yii::$app->request->post(), true, ['cate_id', 'closed']);
			if (!$post->id) {
				return Message::warning(Language::get('no_such_goods'));
			}
			$model = new \backend\models\GoodsForm(['goods_id' => Yii::$app->request->get('id')]);
			if (!$model->save($post, true)) {
				return Message::warning($model->errors);
			}
			return Message::display(Language::get('edit_ok'), ['goods/index']);
		}
	}

	public function actionView()
	{
		if (!Yii::$app->request->isPost) {
			$post = Basewind::trimAll(Yii::$app->request->get(), true, ['id']);

			$goods = GoodsModel::find()->where(['goods_id' => $post->id])->asArray()->one();
			$goods['cate_name'] = GcategoryModel::formatCateName($goods['cate_name'], false, ' / ');
			$goods['images'] = GoodsImageModel::find()->select('thumbnail')->where(['goods_id' => $post->id])->orderBy(['sort_order' => SORT_ASC])->asArray()->all();
			$goods['specs'] = GoodsSpecModel::find()->where(['goods_id' => $post->id])->asArray()->all();
			$this->params['goods'] = $goods;
			$this->params['page'] = Page::seo(['title' => Language::get('goods_verify')]);
			return $this->render('../goods.view.html', $this->params);
		} else {
			$post = Basewind::trimAll(Yii::$app->request->post(), true, ['id', 'closed']);
			if (!$post->id || !($model = GoodsModel::find()->select('goods_id,closed')->where(['goods_id' => $post->id])->one())) {
				return Message::warning(Language::get('no_such_goods'));
			}
			$model->closed = intval($post->closed);
			if (!$model->save()) {
				return Message::warning($model->errors);
			}
			return Message::display(Language::get('handle_ok'), ['goods/verify']);
		}
	}

	public function actionDelete()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true);
		if (!$post->id) {
			return Message::warning(Language::get('no_such_goods'));
		}
		$model = new \backend\models\GoodsDeleteForm(['goods_id' => $post->id]);
		if (!$model->delete($post, true)) {
			return Message::warning($model->errors);
		}
		return Message::display(Language::get('drop_ok'), ['goods/index']);
	}

	public function actionRecommend()
	{
		if (!Yii::$app->request->isPost) {
			if (!($recommends = RecommendModel::find()->select('name')->where(['store_id' => 0])->indexBy('id')->column())) {
				return Message::warning(Language::get('set_recommend'), ['recommend/index']);
			}
			$this->params['recommends'] = $recommends;

			$this->params['page'] = Page::seo(['title' => Language::get('goods_recommend')]);
			return $this->render('../goods.recommend.html', $this->params);
		} else {
			$post = Basewind::trimAll(Yii::$app->request->post(), true, ['id']);
			if (!$post->goods_id) {
				return Message::popWarning(Language::get('no_such_goods'));
			}
			$model = new \backend\models\GoodsRecommendForm(['goods_id' => $post->goods_id]);
			if (!$model->save($post, true)) {
				return Message::popWarning($model->errors);
			}
			return Message::popSuccess(Language::get('edit_ok'), Url::toRoute(['recommend/index']));
		}
	}

	/**
	 * 刷单评价
	 */
	public function actionBrush()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['spec_id', 'store_id']);

		$specs = [];
		if ($post->spec_id) $specs = [$post->spec_id];

		if (!($client = Plugin::getInstance('other')->build('brush'))) {
			return Message::warning(Language::get('刷单插件未配置'));
		}
		if (!$client->create($post->store_id, $specs)) {
			return Message::warning($client->errors);
		}

		return Message::display(Language::get('刷单成功'));
	}

	/* 异步修改数据 */
	public function actionEditcol()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['id', 'if_show', 'closed']);
		if (in_array($post->column, ['goods_name', 'if_show', 'closed'])) {
			$model = new \backend\models\GoodsForm(['goods_id' => $post->id]);
			$query = GoodsModel::findOne($post->id);
			$query->{$post->column} = $post->value;
			if (!($brand = $model->save($query, true))) {
				return Message::warning($model->errors);
			}
			return Message::display(Language::get('edit_ok'));
		}
	}

	public function actionExport()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true);
		if ($post->id) $post->id = explode(',', $post->id);

		$query = GoodsModel::find()->alias('g')->select('g.goods_id,g.goods_name,g.cate_id,g.price,g.brand,g.if_show,g.closed,g.cate_name,s.store_name,gst.views')
			->joinWith('store s', false)
			->joinWith('goodsStatistics gst', false)
			->orderBy(['goods_id' => SORT_DESC]);

		if (!empty($post->id)) {
			$query->andWhere(['in', 'g.goods_id', $post->id]);
		} else {
			$query = $this->getConditions($post, $query)->limit(100);
		}
		if ($query->count() == 0) {
			return Message::warning(Language::get('no_data'));
		}
		return \backend\models\GoodsExportForm::download($query->asArray()->all());
	}

	private function getConditions($post, $query = null)
	{
		if ($query === null) {
			foreach (array_keys(ArrayHelper::toArray($post)) as $field) {
				if (in_array($field, ['goods_name', 'store_name', 'brand', 'cate_id', 'if_show'])) {
					return true;
				}
			}
			return false;
		}

		if ($post->goods_name) {
			$query->andWhere(['like', 'goods_name', $post->goods_name]);
		}
		if ($post->store_name) {
			$id = StoreModel::find()->select('store_id')->where(['store_name' => $post->store_name])->scalar();
			$query->andWhere(['store_id' => intval($id)]);
		}
		if ($post->brand) {
			$query->andWhere(['or', ['like', 'brand', $post->brand], ['like', 'goods_name', $post->brand]]);
		}

		if ($post->cate_id) {
			$childIds = GcategoryModel::getDescendantIds($post->cate_id, 0);
			$query->andWhere(['in', 'cate_id', $childIds]);
		}
		if ($post->if_show) {
			$query->andWhere(['if_show' => $post->if_show == 'yes' ? 1 : 0]);
		}
		return $query;
	}
}
