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

use common\models\OrderModel;
use common\models\GoodsQaModel;
use common\models\RefundModel;

use common\library\Def;

/**
 * @Id UserForm.php 2018.3.25 $
 * @author mosir
 */
class UserForm extends Model
{
	public $userid = 0;
	public $errors = null;

	public function valid($post)
	{
		return true;
	}

	/**
	 * 买家提醒：待付款、待确认、待评价等
	 * @desc API接口也使用到该数据	 
	 */
	public function getBuyerStat()
	{
		return array_merge(
			$this->getStatistics('buyer_id'),
			[
				'evaluation' => OrderModel::find()->where(['buyer_id' => Yii::$app->user->id, 'status' => Def::ORDER_FINISHED, 'evaluation_status' => 0])->count(),
				'question' => GoodsQaModel::find()->where(['userid' => Yii::$app->user->id, 'if_new' => 1])->andWhere(['!=', 'reply_content', ''])->count()
			]
		);
	}

	/**
	 * 卖家提醒：已提交、待发货、待回复等
	 * @desc API接口也使用到该数据
	 */
	public function getSellerStat()
	{
		return array_merge(
			$this->getStatistics('seller_id'),
			['replied' => GoodsQaModel::find()->where(['store_id' => Yii::$app->user->id])->andWhere(['=', 'reply_content', ''])->count()]
		);
	}

	/**
	 * 获取各状态订单数据
	 */
	public function getStatistics($field = 'buyer_id')
	{
		$array = [
			'all' => OrderModel::find()->where([$field => Yii::$app->user->id])->count(),
			'canceled' => OrderModel::find()->where([$field => Yii::$app->user->id, 'status' => Def::ORDER_CANCELED])->count(),
			'pending' => OrderModel::find()->where([$field => Yii::$app->user->id, 'status' => Def::ORDER_PENDING])->count(),
			'teaming' => OrderModel::find()->where([$field => Yii::$app->user->id, 'status' => Def::ORDER_TEAMING])->count(),
			'delivering' => OrderModel::find()->where([$field => Yii::$app->user->id, 'status' => Def::ORDER_DELIVERING])->count(),
			'accepted' => OrderModel::find()->where([$field => Yii::$app->user->id, 'status' => Def::ORDER_ACCEPTED])->count(),
			'shipped' => OrderModel::find()->where([$field => Yii::$app->user->id])->andWhere(['in', 'status', [Def::ORDER_SHIPPED, Def::ORDER_USING]])->count(),
			'finished' => OrderModel::find()->where([$field => Yii::$app->user->id, 'status' => Def::ORDER_FINISHED])->count(),
			'refund' => RefundModel::find()->where(['and', [$field => Yii::$app->user->id], ['not in', 'status', ['SUCCESS', 'CLOSED']]])->count(),
		];
		return $array;
	}
}
