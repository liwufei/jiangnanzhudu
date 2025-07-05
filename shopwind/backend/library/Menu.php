<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace backend\library;

use yii;
use yii\helpers\Url;
use yii\helpers\FileHelper;

use common\library\Language;
use common\library\Plugin;

/**
 * @Id Menu.php 2018.7.26 $
 * @author mosir
 */

class Menu
{
	/* 获取全局菜单列表 */
	public static function getMenus()
	{
		$menu = array(
			'dashboard' => array(
				'text'      => Language::get('dashboard'),
				'ico'		=> 'icon-home',
				'children'  => array(
					'overview'   => array(
						'text'  => Language::get('overview'),
						'url'   => Url::toRoute('default/index')
					)
				)
			),
			// 设置
			'setting'   => array(
				'text'      => Language::get('setting'),
				'ico' 	=> 'icon-wangzhanshezhi',
				'children'  => array(
					'baseinfo'  => array(
						'text'  => Language::get('base_setting'),
						'url'   => Url::toRoute('setting/index'),
						'priv'  => ['key' => 'setting|index']
					),
					'region' => array(
						'text'  => Language::get('region_setting'),
						'url'   => Url::toRoute('region/index'),
						'priv'  => ['key' => 'region|all']
					),
					'api' => array(
						'text'  => Language::get('api_setting'),
						'url'   => Url::toRoute('setting/api'),
						'priv'  => ['key' => 'setting|api']
					),
					'upload' => array(
						'text'  => Language::get('upload_setting'),
						'url'   => Url::toRoute('setting/upload'),
						'priv'  => ['key' => 'setting|upload']
					),
					'smtp' => array(
						'text'  => Language::get('smtp_setting'),
						'url'   => Url::toRoute('setting/email'),
						'priv'  => ['key' => 'setting|email']
					),
					'verifycode' => array(
						'text'  => Language::get('captcha'),
						'url'   => Url::toRoute('setting/verifycode'),
						'priv'  => ['key' => 'setting|verifycode']
					)
				)
			),
			// 用户
			'user' => array(
				'text'      => Language::get('user'),
				'ico' => 'icon-huiyuan',
				'children'  => array(
					'list' => array(
						'text'  => Language::get('user_list'),
						'url'   => Url::toRoute('user/index'),
						'priv'  => ['key' => 'user|all', 'label' => Language::get('user_manage')]
					),
					'admin' => array(
						'text' => Language::get('admin_list'),
						'url'   => Url::toRoute('manager/index'),
						'priv'  => ['key' => 'manager|all', 'label' => Language::get('admin_manage')]
					),
					'integral' => array(
						'text' => Language::get('integral_manage'),
						'url'  => Url::toRoute('integral/index'),
						'priv' => ['key' => 'integral|all']
					),
					'setting' => array(
						'text' => Language::get('integral_setting'),
						'url'  => Url::toRoute('integral/setting')
					)
				)
			),
			// 商品
			'goods' => array(
				'text'      => Language::get('goods'),
				'ico' => 'icon-shangpin',
				'children'  => array(
					'list' => array(
						'text'  => Language::get('goods_list'),
						'url'   => Url::toRoute('goods/index'),
						'priv' => ['key' => 'goods|all', 'depends' => 'upload|all', 'label' => Language::get('goods_manage')]
					),
					'gcategory' => array(
						'text'  => Language::get('gcategory_manage'),
						'url'   => Url::toRoute('gcategory/index'),
						'priv' => ['key' => 'gcategory|all']
					),
					'verify' => array(
						'text'  => Language::get('goods_verify'),
						'url'   => Url::toRoute('goods/verify'),
					),
					'brand' => array(
						'text'  => Language::get('brand_manage'),
						'url'   => Url::toRoute('brand/index'),
						'priv'  => ['key' => 'brand|all']
					),
					'props' => array(
						'text' => Language::get('goods_props'),
						'url'  => Url::toRoute('goodsprop/index'),
						'priv' => ['key' => 'goodsprop|all']
					),
					'recommended' => array(
						'text'  => Language::get('recommend_manage'),
						'url'   => Url::toRoute('recommend/index'),
						'priv'  => ['key' => 'recommend|all']
					)
				)
			),
			// 店铺
			'store'     => array(
				'text'      => Language::get('store'),
				'ico' => 'icon-mendian',
				'children'  => array(
					'list'  => array(
						'text'  => Language::get('store_list'),
						'url'   => Url::toRoute('store/index'),
						'priv'  => ['key' => 'store|all', 'depends' => 'mlselection|all', 'label' => Language::get('store_manage')]
					),
					'verify'  => array(
						'text'  => Language::get('store_verify'),
						'url'   => Url::toRoute('store/verify'),
						'priv'  => ['key' => 'store|verify']
					),
					'sgrade' => array(
						'text'  => Language::get('sgrade'),
						'url'   => Url::toRoute('sgrade/index'),
						'priv'  => ['key' => 'sgrade|all']
					),
					'scategory' => array(
						'text'  => Language::get('scategory'),
						'url'   => Url::toRoute('scategory/index'),
						'priv'  => ['key' => 'scategory|all']
					),
					'apply'  => array(
						'text'  => Language::get('store_setting'),
						'url'   => Url::toRoute('setting/store'),
						'priv'  => ['key' => 'setting|store']
					)
				)
			),
			// 订单
			'order' => array(
				'text'      => Language::get('order'),
				'ico' => 'icon-order',
				'children'  => array(
					'order' => array(
						'text'  => Language::get('order_list'),
						'url'   => Url::toRoute('order/index'),
						'priv'  => ['key' => 'order|all', 'label' => Language::get('order_manage')]
					),
					'timeline' => array(
						'text'  => Language::get('order_timeline'),
						'url'   => Url::toRoute('order/timeline'),
					),
					'refund' => array(
						'text' => Language::get('refund_list'),
						'url'  => Url::toRoute('refund/index'),
						'priv' => ['key' => 'refund|all']
					)
				)
			),
			// 分销
			'distribute' => array(
				'text'      => Language::get('distribute'),
				'ico' => 'icon-jiagoufenxiao',
				'children'  => array(
					'list' => array(
						'text'  => Language::get('distribute_merchant'),
						'url'   => Url::toRoute('distribute/merchant'),
						'priv'  => ['key' => 'distribute|all', 'label' => Language::get('distribute_manage')]
					),
					'verify' => array(
						'text'  => Language::get('distribute_verify'),
						'url'   => Url::toRoute('distribute/verify'),
						'priv'  => ['key' => 'distribute|verify']
					)
				)
			),
			// 资产
			'deposit' => array(
				'text'      => Language::get('myfund'),
				'ico' => 'icon-yue',
				'children'  => array(
					'account' => array(
						'text' => Language::get('deposit_account'),
						'url'  => Url::toRoute('deposit/index'),
						'priv' => ['key' => 'deposit|account', 'depends' => 'deposit|edit,deposit|editcol,deposit|index,deposit|delete,deposit|export,deposit|monthbill,deposit|downloadbill']
					),
					'trade' => array(
						'text' => Language::get('trade_manage'),
						'url'  => Url::toRoute('deposit/tradelist'),
						'priv' => ['key' => 'deposit|tradelist', 'depends' => 'deposit|export']
					),
					'recharge' => array(
						'text' => Language::get('recharge_manage'),
						'url'  => Url::toRoute('deposit/rechargelist'),
						'priv' => ['key' => 'deposit|rechargelist', 'depends' => 'deposit|recharge,deposit|export']
					),
					'drawal' => array(
						'text' => Language::get('drawal_manage'),
						'url'  => Url::toRoute('deposit/drawlist'),
						'priv' => ['key' => 'deposit|drawlist', 'depends' => 'deposit|drawverify,deposit|drawrefuse,deposit|export']
					),
					'cashcard' => array(
						'text' => Language::get('cashcard_manage'),
						'url'  => Url::toRoute('cashcard/index'),
						'priv' => ['key' => 'cashcard|all']
					),
					'setting' => array(
						'text' => Language::get('deposit_setting'),
						'url'  => Url::toRoute('deposit/setting'),
						'priv' => ['key' => 'deposit|setting']
					)
				)
			),
			// 插件
			'plugin' => array(
				'text'  => Language::get('plugin'),
				'ico' => 'icon-app',
				'children' => Plugin::getInstance('plugin')->build()->getList()
			),
			// 网站
			'website' => array(
				'text'      => Language::get('website'),
				'ico' => 'icon-website_setup',
				'children'  => array(
					'template' => array(
						'text'  => Language::get('template_diy'),
						'url'   => Url::toRoute('template/index'),
						'priv'  => ['key' => 'template|all', 'depends' => 'gselector|all']
					)
				)
			),
			// 文章
			'article' => array(
				'text'      => Language::get('article'),
				'ico' => 'icon-wenzhang',
				'children'  => array(
					'list' => array(
						'text'  => Language::get('article_list'),
						'url'   => Url::toRoute('article/index'),
						'priv'  => ['key' => 'article|all', 'depends' => 'upload|all', 'label' => Language::get('article_manage')]
					),
					'acategory' => array(
						'text'  => Language::get('acategory'),
						'url'   => Url::toRoute('acategory/index'),
						'priv'  => ['key' => 'acategory|all']
					)
				)
			),
			// 数据库
			'db' => array(
				'text'      => Language::get('db'),
				'ico' => 'icon-shujuku',
				'children'  => array(
					'backup' => array(
						'text'  => Language::get('db_backup'),
						'url'   => Url::toRoute('db/backup'),
						'priv'  => ['key' => 'db|backup']
					),
					'recover' => array(
						'text'  => Language::get('db_recover'),
						'url'   => Url::toRoute('db/recover'),
						'priv'  => ['key' => 'db|recover']
					),
					'slave' => array(
						'text'  => Language::get('db_slave'),
						'url'   => Url::toRoute('db/index'),
						'priv'  => ['key' => 'db|index', 'depends' => 'db|slave']
					),
				)
			),
			// 缓存
			'cache'   => array(
				'text'      => Language::get('cache'),
				'ico' => 'icon-cache',
				'children'  => array(
					'redis' => array(
						'text'  => Language::get('Redis'),
						'url'   => Url::toRoute('cache/redis'),
						'priv'  => ['key' => 'cache|redis']
					),
					'cache' => array(
						'text'  => Language::get('cache_file'),
						'url'   => Url::toRoute('cache/index'),
						'priv'  => ['key' => 'cache|index']
					),
					'memcache' => array(
						'text'  => Language::get('Memcache'),
						'url'   => Url::toRoute('cache/memcache'),
						'priv'  => ['key' => 'cache|memcache']
					),
				)
			)
		);

		return $menu;
	}
}
