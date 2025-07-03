<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace common\plugins\promote\limitbuy;

use yii;

use common\models\LimitbuyModel;

use common\library\Language;
use common\library\Timezone;
use common\plugins\BasePromote;

/**
 * @Id limitbuy.plugin.php 2018.6.5 $
 * @author mosir
 */

class Limitbuy extends BasePromote
{
	/**
	 * 插件实例
	 * @var string $code
	 */
	protected $code = 'limitbuy';

	/**
	 * 获取商品促销价格信息
	 * @param boolean $force 强验证，验证促销价格时间
	 */
	public function getItemProInfo($goods_id = 0, $spec_id = 0, $force = true)
	{
		list($price, $id) = LimitbuyModel::getItemProPrice($goods_id, $spec_id, $force);
		if ($price !== false) {
			$limitbuy = LimitbuyModel::find()->select('start_time,end_time,title')->where(['id' => $id])->one();
			return [
				'price' => round($price, 2),
				'type' => $this->code,
				'name' => $limitbuy->title,
				'start_time' 	=> Timezone::localDate('Y-m-d H:i:s', $limitbuy->start_time),
				'end_time' 		=> Timezone::localDate('Y-m-d H:i:s', $limitbuy->end_time),
				'timestamp' 	=> $limitbuy->end_time - Timezone::gmtime(),
				'lefttime' 		=> Timezone::lefttime($limitbuy->end_time)
			];
		}

		return false;
	}
}
