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

/**
 * @Id RechargeSettingModel.php 2018.8.22 $
 * @author mosir
 */

class RechargeSettingModel extends ActiveRecord
{
	/**
	 * @inheritdoc
	 */
	public static function tableName()
	{
		return '{{%recharge_setting}}';
	}
}
