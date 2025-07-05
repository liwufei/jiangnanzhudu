<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace common\plugins\promote\mealbuy;

use yii;
use yii\helpers\ArrayHelper;

use common\models\GoodsModel;
use common\models\GoodsSpecModel;
use common\models\MealModel;
use common\models\MealGoodsModel;

use common\library\Timezone;
use common\library\Language;
use common\plugins\BasePromote;

/**
 * @Id mealbuy.plugin.php 2018.6.5 $
 * @author mosir
 */

class Mealbuy extends BasePromote
{
	/**
	 * 插件实例
	 * @var string $code
	 */
	protected $code = 'mealbuy';

	public function valid(&$post)
	{
		if (($message = $this->checkAvailable($this->params->store_id)) !== true) {
			$this->errors = $message;
			return false;
		}

		if (!$post->title) {
			$this->errors = Language::get('note_for_title');
			return false;
		}
		if (!$post->price || $post->price <= 0) {
			$this->errors = Language::get('meal_price_gt0');
			return false;
		}
		if (!isset($post->selected)) {
			$this->errors = Language::get('add_records');
			return false;
		}

		$selected = array_unique(ArrayHelper::toArray($post->selected));

		// 套餐商品的数量必须在2-10之间
		if (!is_array($selected) || count($selected) < 2 || count($selected) > 10) {
			$this->errors = Language::get('records_error');
			return false;
		}

		// 搭配宝贝是否属于本店的
		if (GoodsModel::find()->where(['!=', 'store_id', $this->params->store_id])->andWhere(['in', 'goods_id', $selected])->exists()) {
			$this->errors = Language::get('goods_not_on_sale');
			return false;
		}

		// 套餐中的宝贝是否处在禁售或者下架中
		if (GoodsModel::find()->where(['store_id' => $this->params->store_id])->andWhere(['or', ['if_show' => 0], ['closed' => 1]])->andWhere(['in', 'goods_id', $selected])->exists()) {
			$this->errors = Language::get('goods_not_on_sale');
			return false;
		}

		// 取最小的金额总和 或有多个规格的话，就是小于价格最小的总价
		$list = GoodsSpecModel::find()->select('goods_id,price')->where(['in', 'goods_id', $selected])->indexBy('goods_id')->orderBy(['price' => SORT_DESC])->all();
		$allPrice = 0;
		foreach ($list as $query) {
			$allPrice += $query->price;
		}
		$post->price = round($post->price, 2);
		if ($post->price - $allPrice > 0) {
			$this->errors = sprintf(Language::get('meal_price_error'), $allPrice);
			return false;
		}

		return true;
	}

	/**
	 * 商家新增/更新搭配购商品
	 */
	public function save($post, $valid = true)
	{
		if ($valid === true && !$this->valid($post)) {
			return false;
		}

		// 搭配购商品ID
		$selected = array_unique(ArrayHelper::toArray($post->selected));
		if (!$this->params->id || !($model = MealModel::find()->where(['meal_id' => $this->params->id, 'store_id' => $this->params->store_id])->one())) {
			$model = new MealModel();
			$model->created = Timezone::gmtime();
		}

		$model->store_id = $this->params->store_id;
		$model->title = $post->title;
		$model->price = $post->price;
		$model->keyword = $this->getKeywords($selected);
		$model->description = $post->description ? $post->description : '';
		$model->status = isset($post->status) ? intval($post->status) : 1;

		if (!$model->save()) {
			$this->errors = $model->errors;
			return false;
		}

		MealGoodsModel::deleteAll(['meal_id' => $model->meal_id]);
		foreach ($selected as $goods_id) {
			$query = new MealGoodsModel();
			$query->meal_id = $model->meal_id;
			$query->goods_id = $goods_id;
			$query->save();
		}

		return $model->meal_id;
	}

	/**
	 * 获取搭配购商品关键词，用于搜索用途
	 */
	private function getKeywords($selected = [])
	{
		$all = GoodsModel::find()->select('goods_name')->where(['in', 'goods_id', $selected])->column();
		return $all ? implode(',', $all) : '';
	}
}
