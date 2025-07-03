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

/**
 * @Id Timezone.php 2018.4.1 $
 * @author mosir
 */

class Timezone
{

	/**
	 * 获得当前世界标准时间戳 
	 */
	public static function gmtime()
	{
		return time();
	}

	/**
	 * 转换字符串形式的时间表达式为UTC时间戳 
	 */
	public static function gmstr2time($str = '')
	{
		return strtotime($str);
	}

	/**
	 * 从字符串获取指定日期的结束时间
	 * @desc 2020-1-1 xx:xx:xx format to 2020-1-1 23:59:59
	 */
	public static function gmstr2time_end($str = '')
	{
		return self::gmstr2time($str) + 86400 - 1;
	}

	/**
	 * 将GMT时间戳格式化为用户自定义时区日期 
	 */
	public static function localDate($format, $time = false)
	{
		// 取当前时间
		if ($time === true) {
			$time = self::gmtime();
		} elseif (!$time) {
			return '';
		}

		return date($format, $time);
	}

	/**
	 * 根据月份获取当月的开始日期和结束日期
	 * @var string $month: 2018-10
	 */
	public static function getMonthDay($month = 0)
	{
		if (!$month) return array();

		// 获取指定日期的开始时间戳
		$month_times = self::gmstr2time($month);

		// 指定的月份有多少天
		$monthdays 	= self::localDate("t", $month_times);

		// 请求的日期是该月的第几天
		$dayInMonth = self::localDate("j", $month_times);

		$beginMonth	= $month_times - ($dayInMonth - 1) * 24 * 3600;
		$endMonth	= $month_times + ($monthdays - $dayInMonth + 1) * 24 * 3600 - 1;

		return array($beginMonth, $endMonth);
	}

	/**
	 * 计算指定的时间戳距离现在的时间有多少天，小时，分，秒 
	 * @var int $begin 开始时间戳，如果不传取现在的时间
	 */
	public static function lefttime($time = 0, $begin = false)
	{
		if (!$begin) {
			$lefttime = $time - self::gmtime();
		} else {
			$lefttime = $time - $begin;
		}

		if (empty($time) || $lefttime <= 0) return array();

		$d = intval($lefttime / 86400);
		$lefttime -= $d * 86400;
		$h = intval($lefttime / 3600);
		$lefttime -= $h * 3600;
		$m = intval($lefttime / 60);
		$lefttime -= $m * 60;
		$s = $lefttime;

		return array('d' => $d, 'h' => $h, 'm' => $m, 's' => $s);
	}
}
