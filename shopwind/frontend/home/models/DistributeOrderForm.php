<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace frontend\home\models;

use Yii;
use yii\base\Model;

use common\models\DistributeOrderModel;
use common\models\OrderGoodsModel;
use common\models\OrderModel;

use common\library\Def;
use common\library\Page;

/**
 * @Id DistributeOrderForm.php 2018.9.19 $
 * @author mosir
 */
class DistributeOrderForm extends Model
{
	public $errors = null;

	/**
	 *  订单完成后分佣记录
	 */
	public function formData($post = null, $pageper = 4, $isAJax = false, $curPage = false)
	{
		$query = DistributeOrderModel::find()->alias('do')->select('do.order_sn,do.type,o.order_id,o.order_amount,o.seller_id,o.seller_name')
			->joinWith('order o', false)->where(['userid' => Yii::$app->user->id])
			->orderBy(['doid' => SORT_DESC]);
		$page = Page::getPage($query->count(), $pageper, $isAJax, $curPage);
		$list = $query->offset($page->offset)->limit($page->limit)->asArray()->all();

		if ($post->queryitem) {
			foreach ($list as $key => $value) {
				$goodslist = DistributeOrderModel::find()->select('do.tradeNo,do.money,do.layer,do.ratio,og.goods_id,og.goods_name,og.price,og.quantity,og.goods_image,og.specification')->alias('do')
					->joinWith('orderGoods og', false)
					->where(['order_sn' => $value['order_sn'], 'type' => $value['type']])
					->asArray()->all();

				$amount = 0;
				foreach ($goodslist as $k => $v) {
					$amount += $v['money'];
					$goodslist[$k]['ratio'] = ($v['ratio'] * 100) . '%';
					$list[$key]['tradeNo'][] = $v['tradeNo'];
				}
				$list[$key]['amount'] = $amount;
				$list[$key]['items'] = $goodslist;
				$list[$key]['tradeNo'] = implode(',', $list[$key]['tradeNo']);
				$list[$key]['status'] = Def::ORDER_FINISHED;
			}
		}

		return array($list, $page);
	}

	/**
	 * 分销订单【未使用，无法计算收益金额】
	 */
	public function orderData($post = null, $pageper = 4, $isAJax = false, $curPage = false)
	{
		$orders = OrderGoodsModel::find()->select('order_id')->where(['inviteUid' => Yii::$app->user->id])->column();
		$query = OrderModel::find()->select('order_id,order_sn,order_amount,seller_id,seller_name')
			->where(['in', 'order_id', $orders ? $orders : []])
			->orderBy(['order_id' => SORT_DESC]);

		$page = Page::getPage($query->count(), $pageper, $isAJax, $curPage);
		$list = $query->offset($page->offset)->limit($page->limit)->asArray()->all();

		if ($post->queryitem) {
			foreach ($list as $key => $value) {
				$goodslist = OrderGoodsModel::find()->select('goods_id,goods_name,quantity,price,goods_image,specification')->where(['order_id' => $value['order_id']])->asArray()->all();
				$list[$key]['items'] = $goodslist;
			}
		}

		return array($list, $page);
	}
}
