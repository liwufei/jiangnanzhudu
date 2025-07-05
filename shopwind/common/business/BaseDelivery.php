<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace common\business;

use yii;

/**
 * @Id BaseDelivery.php 2018.4.12 $
 * @author mosir
 */

class BaseDelivery
{
	/**
	 * 发货类型
	 * @var string $dtype
	 */
	protected $dtype;

	/**
	 * 页面提交参数
	 * @var object $post
	 */
	public $post;

	/**
	 * 其他额外参数
	 * @var array $params
	 */
	public $params;

	/**
	 * 错误捕捉
	 * @var object $errors
	 */
	public $errors;

	public function __construct($dtype, $post = null, $params = [])
	{
		$this->dtype 	= $dtype;
		$this->post 	= $post;
		$this->params 	= $params;
	}

	public function getCode()
	{
		return $this->dtype;
	}

	/**
	 * 获取送货时间
	 */
	public function getDeliveryTime($store_id)
	{
		return '';
	}

	/**
	 * 验证收货地址是否在配送范围
	 */
	public function validDeliveryArea($list = [], $consignee_info = [])
	{
		return true;
	}

	/**
	 * 验证是否有足够余额预约第三方平台派发骑手运单
	 */
	public function validMerMoney($store_id, $freight)
	{
		return true;
	}
}
