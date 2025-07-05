<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace common\plugins\locality\msd;

use yii;
use yii\helpers\Url;

use common\models\DeliveryTemplateModel;
use common\plugins\BaseLocality;

/**
 * @Id msd.plugin.php 2018.9.5 $
 * @author mosir
 */

class Msd extends BaseLocality
{
	/**
	 * 网关地址
	 * @var string $gateway
	 */
	protected $gateway = '';

	/**
	 * 插件实例
	 * @var string $code
	 */
	protected $code = 'msd';

	/**
	 * 查询运费
	 */
	public function queryFee($post = null)
	{
		$result = DeliveryTemplateModel::queryFee($post->store_id, $post->region_id, $post->weight, $this->instance);
		return $result ? $result['freight'] : false;
	}
}
