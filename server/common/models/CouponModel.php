<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace common\models;

use Yii;
use yii\db\ActiveRecord;

use common\models\CouponsnModel;

use common\library\Timezone;

/**
 * @Id CouponModel.php 2018.5.20 $
 * @author mosir
 */

class CouponModel extends ActiveRecord
{
	/**
	 * @inheritdoc
	 */
	public static function tableName()
	{
		return '{{%coupon}}';
	}

	// 关联表
	public function getStore()
	{
		return parent::hasOne(StoreModel::className(), ['store_id' => 'store_id']);
	}

	/* 获取订单可用的优惠券 */
	public static function getAvailableByOrder($order = [])
	{
		$time = Timezone::gmtime();
		$list = CouponsnModel::find()->alias('sn')
			->select('sn.coupon_sn,c.amount,c.money,c.name,c.items')
			//->select('sn.coupon_sn as number,c.money as value')
			->joinWith('coupon c', false)
			->where(['c.store_id' => $order['store_id'], 'sn.userid' => Yii::$app->user->id])
			->andWhere(['>=', 'sn.remain_times', 1])
			->andWhere(['<=', 'c.start_time', $time])
			->andWhere(['>=', 'c.end_time', $time])
			->andWhere(['c.available' => 1])
			->andWhere(['<=', 'c.amount', $order['amount']])
			->orderBy(['c.money' => SORT_DESC])
			//->indexBy('number')
			->asArray()->all();

		// 如果是指定商品优惠券，则把不满足金额的优惠券去掉
		foreach ($list as $key => $value) {
			if ($value['items'] && ($items = explode(',', $value['items']))) {
				$amount = 0;
				foreach ($order['items'] as $goods) {
					if (in_array($goods['goods_id'], array_map('intval', $items))) {
						$amount += floatval($goods['subtotal']);
					}
				}

				// 不满足金额
				if ($amount < floatval($value['amount'])) {
					unset($list[$key]);
				}
			}
		}

		return array_values($list);
	}
}
