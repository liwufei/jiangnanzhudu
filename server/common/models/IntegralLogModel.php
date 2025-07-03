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

use common\library\Timezone;

/**
 * @Id IntegralLogModel.php 2018.4.17 $
 * @author mosir
 */


class IntegralLogModel extends ActiveRecord
{
	/**
	 * @inheritdoc
	 */
	public static function tableName()
	{
		return '{{%integral_log}}';
	}

	public static function addLog($data = [])
	{
		extract($data);

		// 当前积分可兑换的金钱
		if (!isset($money)) {
			$rate = IntegralSettingModel::getSysSetting('rate');
			$money = abs($points) * floatval($rate);
		}

		$model = new IntegralLogModel();
		$model->userid = $userid;
		$model->order_id = isset($order_id) ? intval($order_id) : 0;
		$model->order_sn = isset($order_sn) ? $order_sn : '';
		$model->changes = (isset($flow) && $flow == 'minus') ? -$points : $points;
		$model->money = $money; // 当前积分数换算的金额
		$model->balance = $balance;
		$model->type = isset($type) ? $type : '';
		$model->state = isset($state) ? $state : 'finished';
		$model->flag = isset($flag) ? $flag : '';
		$model->add_time = Timezone::gmtime();

		return $model->save() ? true : false;
	}
}
