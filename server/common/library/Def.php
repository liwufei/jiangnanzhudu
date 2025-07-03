<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace common\library;

use yii;

use common\library\Language;

/**
 * @Id Def.php 2018.3.2 $
 * @author mosir
 */

class Def
{
	/* 店铺状态 */
	const STORE_APPLYING 		= 	0; // 申请中
	const STORE_OPEN 			= 	1; // 开启
	const STORE_CLOSED  		= 	2; // 关闭
	const STORE_NOPASS			=   3; // 审核不通过

	/* 订单状态 */
	const ORDER_PENDING			=	11;              // 待付款
	const ORDER_TEAMING			=   19;			     // 针对拼团订单，买家已付款，待成团
	const ORDER_ACCEPTED		=	20;              // 待发货（货到付款订单：未付款，非货到付款订单：已付款）
	const ORDER_DELIVERING		=   25;				// 待配送(针对同城配送订单)
	const ORDER_SHIPPED 		= 	30;              // 待收货
	const ORDER_USING           =   38;              // 待使用 & 待核销【暂留】
	const ORDER_FINISHED 		= 	40;              // 交易成功
	const ORDER_CANCELED 		= 	 0;              // 交易取消

	/* 商户业务类型代码 */
	const TRADE_ORDER 			= 	'ORDER';	 // 购物
	const TRADE_RECHARGE 		= 	'RECHARGE';	 // 充值到余额
	const TRADE_REGIVE			=	'REGIVE';	 // 充值返钱
	const TRADE_DRAW 			= 	'DRAW';	 	 // 提现
	const TRADE_CHARGE 			= 	'CHARGE';	 // 系统扣费
	const TRADE_BUYAPP 			= 	'BUYAPP';	 // 购买应用
	const TRADE_TRANS 			= 	'TRANS';	 // 余额转账
	const TRADE_FX 				= 	'FX';	 	 // 分销返佣

	/* 上传文件归属 */
	const BELONG_ARTICLE 		=	1;
	const BELONG_GOODS 			=  	2;
	const BELONG_APPMARKET		=  	6;

	/* 上传图片大小限制 */
	const IMAGE_FILE_SIZE		=   2097152;   	// 普通图片大小限制2MB = 2*1024*1024

	/* 上传文档的大小限制 */
	const ARCHIVE_FILE_SIZE		=   10485760; 	// 10M

	/* 文件类型 */
	const IMAGE_FILE_TYPE		=	'gif,jpg,jpeg,png,bmp'; // 图片类型
	const ARCHIVE_FILE_TYPE 	= 	'doc,docx,pdf,xls,xlsx'; // 文档类型
	const VIDEO_FILE_TYPE		=	'mp4'; // 视频类型

	/** 
	 * 上传文件保存的本地物理路径头
	 * 不管是前台上传，还是后台上传文件，都是保存到前台下
	 */
	public static function fileSavePath()
	{
		// 保存到本地
		return Yii::getAlias('@public');
	}

	/** 
	 * 保存到本地的上传文件的URL地址头
	 */
	public static function fileSaveUrl()
	{
		return Basewind::baseUrl();
	}

	/**
	 * 获取订单状态相应的文字表述
	 * @param int $status
	 */
	public static function getOrderStatus($status = null)
	{
		$lang_key = '';
		switch ($status) {
			case self::ORDER_PENDING:
				$lang_key = 'order_pending';
				break;
			case self::ORDER_TEAMING:
				$lang_key = 'order_teaming';
				break;
			case self::ORDER_ACCEPTED:
				$lang_key = 'order_accepted';
				break;
			case self::ORDER_SHIPPED:
				$lang_key = 'order_shipped';
				break;
			case self::ORDER_DELIVERING:
				$lang_key = 'order_delivering';
				break;
			case self::ORDER_USING:
				$lang_key = 'order_using';
				break;
			case self::ORDER_FINISHED:
				$lang_key = 'order_finished';
				break;
			case self::ORDER_CANCELED:
				$lang_key = 'order_canceled';
				break;
		}

		return $lang_key  ? Language::get($lang_key) : $lang_key;
	}

	/**
	 * 转换订单状态值
	 * @param string $string
	 */
	public static function getOrderStatusTranslator($string = '')
	{
		$status = -1;
		switch (strtolower($string)) {
			case 'canceled':    // 已取消的订单
				$status = self::ORDER_CANCELED;
				break;
			case 'pending':     // 待付款的订单
				$status = self::ORDER_PENDING;
				break;
			case 'teaming': // 待成团订单
				$status = self::ORDER_TEAMING;
				break;
			case 'accepted': 	// 待发货的订单
				$status = self::ORDER_ACCEPTED;
				break;
			case 'shipped':     // 待收货的订单
				$status = self::ORDER_SHIPPED;
				break;
			case 'delivering': // 待配送的订单
				$status = self::ORDER_DELIVERING;
				break;
			case 'using': // 待使用 & 待核销【暂留】
				$status = self::ORDER_USING;
				break;
			case 'finished':    // 已完成的订单
				$status = self::ORDER_FINISHED;
				break;
		}

		return $status;
	}

	/**
	 * 价格格式化
	 * @param float $price
	 * @param string $price_format
	 */
	public static function priceFormat($price, $price_format = NULL)
	{
		if (empty($price)) {
			$price = '0.00';
		}
		$price = number_format($price, 2);

		if ($price_format === NULL) {
			$price_format = Yii::$app->params['price_format'];
		}

		return sprintf($price_format, $price);
	}
}
