<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace common\plugins\datapicker\leapi;

use yii;
use yii\helpers\ArrayHelper;

use common\models\GoodsModel;
use common\models\GoodsImageModel;
use common\models\GoodsSpecModel;
use common\library\Basewind;
use common\library\Language;

/**
 * @Id SDK.php 2018.6.5 $
 * @author mosir
 */

class SDK
{
	/**
	 * 网关地址
	 * @var string $gateway
	 */
	public $gateway = 'https://api09.99api.com';

	/**
	 * 秘钥
	 * @var string $apikey
	 */
	public $apikey;

	/**
	 * 抓取错误
	 */
	public $errors;

	/**
	 * 构造函数
	 */
	public function __construct(array $config)
	{
		foreach ($config as $key => $value) {
			$this->$key = $value;
		}
	}

	public function detail($id)
	{
		$url = $this->gateway . '?apikey=' . $this->apikey . '&itemid=' . $id;
		if (!($response = Basewind::curl($url))) {
			$this->errors = Language::get('no_data');
			return false;
		}

		$result = json_decode($response);
		if ($result->retcode != '0000') {
			$this->errors = $result->data;
			return false;
		}

		return $this->format($result->data);
	}

	/**
	 * 由于本系统限制,规格图片必须是放第一种规格下
	 */
	public function translate($result)
	{
		$tmp = $result['spec_name_2'];
		$result['spec_name_2'] = $result['spec_name_1'];
		$result['spec_name_1'] = $tmp;

		foreach ($result['specs'] as $key => $value) {
			$tmp = $value['spec_2'];
			$result['specs'][$key]['spec_2'] = $value['spec_1'];
			$result['specs'][$key]['spec_1'] = $tmp;
		}

		return $result;
	}

	/**
	 * 从链接提取商品ID
	 * 京东：https://item.jd.com/2349418.html
	 * 阿里巴巴：https://detail.1688.com/offer/1251853611.html
	 */
	public function getItemId($url)
	{
		if (!empty($url) && stripos($url, '.html') !== false) {
			list($host) = explode('.html', $url);
			$params = explode('/', $host);
			return $params[count($params) - 1];
		}

		return 0;
	}

	public function getDescInfo($item)
	{
		$html = '';
		if (isset($item->desc) && !empty($item->desc)) {
			$html = $item->desc;
		} elseif (isset($item->descImgs) && is_array($item->descImgs)) {
			foreach ($item->descImgs as $key => $value) {
				$html .= '<img src="' + $value + '">';
			}
		} elseif (isset($item->descUrl) && !empty($item->descUrl)) {
			$html = '<iframe frameborder="0" width="100%" height="100%" scrolling="no" src="' . $item->descUrl . '"></iframe>';
		}

		return str_replace('data-lazyload=', 'src=', $html);
	}

	public function getGoodsImages($data)
	{
		$list = [];
		foreach ($data as $value) {
			$list[] = ['image_url' => $value, 'thumbnail' => $value];
		}

		return $list;
	}

	public function format($data)
	{
		return $data;
	}
}

class Taobao extends SDK
{
	public function __construct(array $config)
	{
		$this->gateway .= '/taobao/detail';
		parent::__construct($config);
	}

	public function format($data)
	{
		$item = $data->item;
		$result = [
			'goods_name' => $item->title,
			'tags'		=> $item->subTitle,
			'goods_images' => $this->getGoodsImages($item->images),
			'description' => $this->getDescInfo($item),
			'spec_qty'	=> 0
		];

		// 由于本系统限制，最多支持两种规格
		$defaultSpec = [];
		foreach ($item->sku as $key => $value) {
			if ($value->skuId == 0) {
				$defaultSpec = [
					'price' => floatval($value->price),
					'stock' => intval($value->quantity)
				];
				continue;
			}

			$spec = [
				'sku' => $value->skuId,
				'price' => floatval($value->price),
				'mkprice' => $value->price * 1.5,
				'stock' => intval($value->quantity),
				'sort_order' => $key
			];

			$hasImg = 1;
			$propPath = explode(';', $value->propPath);
			foreach ($propPath as $k => $v) {
				if ($k > 1) {
					break;
				}
				$result['spec_qty']	= $k + 1;

				list($pid, $vid) = explode(':', $v);
				foreach ($item->props as $prop) {
					if ($prop->pid == $pid) {
						$result['spec_name_' . ($k + 1)] = $prop->name;
						foreach ($prop->values as $val) {
							if ($vid == $val->vid) {
								if ($val->image) {
									$spec['image'] = $val->image;
									$hasImg = $k + 1;
								}
								$spec['spec_' . ($k + 1)] = $val->name;
								break;
							}
						}
					}
				}
			}

			$result['specs'][] = $spec;
		}

		if (empty($result['specs'])) {
			$result['specs'][] = $defaultSpec;
		}

		return $hasImg > 1 ? $this->translate($result) : $result;
	}

	/**
	 * 从链接提取商品ID
	 * 淘宝天猫：https://detail.tmall.com/item.htm?id=563422855497
	 */
	public function getItemId($url)
	{
		if (!empty($url) && stripos($url, '?') !== false) {
			list($host, $query) = explode('?', $url);
			$query = str_replace('&amp;', '&', $query);
			parse_str($query, $params);
			return $params['id'];
		}

		return 0;
	}
}

class Alibaba extends SDK
{
	public function __construct(array $config)
	{
		$this->gateway .= '/alibaba/pro/detail';
		parent::__construct($config);
	}

	public function format($data)
	{
		$item = $data;
		$result = [
			'goods_name' => $item->title,
			'goods_images' => $this->getGoodsImages($item->images),
			'description' => $this->getDescInfo($item),
			'spec_qty'	=> 0
		];

		// 由于本系统限制，最多支持两种规格
		if (isset($item->skuProps) && count($item->skuProps) > 0) {
			$result['spec_qty'] = count($item->skuProps);

			$index = 0;
			foreach ($item->skuMap as $k => $v) {

				$spec = [
					'price' => floatval($v->discountPrice),
					'mkprice' => $v->price ? floatval($v->price) : $v->discountPrice * 1.5,
					'stock' => intval($v->canBookCount),
					'sort_order' => ++$index
				];

				$spec12 = explode('&gt;', $v->specAttrs);
				foreach ($item->skuProps as $k1 => $v1) {

					if ($k > 1) {
						break;
					}

					$result['spec_name_' . ($k1 + 1)] = $v1->prop;
					$spec['spec_' . ($k1 + 1)] = $spec12[$k1];
					foreach ($v1->value as $k2 => $v2) {
						if ($v2->name == $spec12[$k1]) {
							if ($v2->imageUrl) {
								$spec['image'] = $v2->imageUrl;
								break;
							}
						}
					}
				}

				$result['specs'][] = $spec;
			}
		}

		if (empty($result['specs']) && isset($item->showPriceRanges)) {
			$result['specs'][] = [
				'price' => floatval($item->showPriceRanges[0]->price),
				'stock' => 200
			];
		}

		return $result;
	}
}

class Jd extends SDK
{
	public function __construct(array $config)
	{
		$this->gateway .= '/jd/detail';
		parent::__construct($config);
	}

	public function format($data)
	{
		$item = $data->item;
		$result = [
			'goods_name' => $item->name,
			'brand' => $item->brandName,
			'goods_images' => $this->getGoodsImages($item->images),
			'description' => $this->getDescInfo($item),
			'spec_qty'	=> 0,
			'video' => $item->video
		];

		$saleProp = ArrayHelper::toArray($item->saleProp);
		foreach (ArrayHelper::toArray($item->sku) as $key => $value) {

			$price = $this->getPrice($item, $value, 'price');
			$mkprice = $this->getPrice($item, $value, 'originalPrice');

			$spec = [
				'sku' => $value['skuId'],
				'price' => $price,
				'mkprice' => $mkprice ? $mkprice : $price * 1.5,
				'stock' => intval($value['stockState']),
				'image' => $this->getSpecImage($item->clothesColor, $value),
				'spec_1' => $value[1],
				'spec_2' => $value[2],
				'sort_order' => $key
			];

			if (!isset($result['spec_name_1'])) {
				$result['spec_name_1'] = $value[1] ? $saleProp[1] : '';
				$result['spec_name_2'] = $value[2] ? $saleProp[2] : '';
				$result['spec_qty'] = ($value[2] && $value[1]) ? 2 : 1;
			}

			$result['specs'][] = $spec;
		}

		return $result;
	}

	private function getSpecImage($clothesColor, $sku)
	{
		/* 这里返回的图片太小，效果不好，不采纳
		if($clothesColor) {
			foreach($clothesColor as $key => $value) {
				if($value->sku == $sku['skuId']) {
					return $value->imagePath;
				}
			}
		}*/

		if (substr($sku['imagePath'], 0, 2) == '//' || substr($sku['imagePath'], 0, 4) == 'http') {
			return $sku['imagePath'];
		}

		return $sku['imagePath'] ? '//img10.360buyimg.com/imgzone/' . $sku['imagePath'] : '';
	}

	/**
	 * 由于接口返回的数据非标准化，需要特定处理
	 */
	private function getPrice($item, $value, $field = 'price')
	{
		if (isset($value[$field])) {
			return floatval($value[$field]);
		}
		if ($item->$field) {
			return floatval($item->$field);
		}

		return 0;
	}
}

class Pdd extends SDK
{
	public function __construct(array $config)
	{
		$this->gateway .= '/pdd/detail1705';
		parent::__construct($config);
	}

	public function format($data)
	{
		$item = $data->item;
		$result = [
			'goods_name' => $item->goodsName,
			'goods_images' => $this->getGoodsImages($item->banner), //array_reverse($item->banner),
			'description' => $this->getDescInfo($item),
			'spec_qty'	=> 0
		];

		foreach ($item->skus as $key => $value) {

			$spec = [
				'sku' => $value->skuID,
				'price' => floatval($value->groupPrice),
				'mkprice' => $value->normalPrice ? floatval($value->normalPrice) : $value->groupPrice * 1.5, // $item->marketPrice
				'stock' => intval($value->quantity),
				'image' => $value->thumbUrl,
				'sort_order' => $key + 1
			];


			foreach ($value->specs as $k => $v) {

				if ($k > 1) {
					break;
				}

				$result['spec_qty'] = $k + 1;
				if (!isset($result['spec_name_' . ($k + 1)])) {
					$result['spec_name_' . ($k + 1)] = $v->spec_key;
				}

				// 接口兼容处理
				$spec['spec_' . ($k + 1)] = isset($v->spec_values) ? $v->spec_values : $v->spec_value;
			}

			$result['specs'][] = $spec;
		}

		return $result;
	}

	/**
	 * 从链接提取商品ID
	 * 拼多多：http://mobile.yangkeduo.com/goods.html?goods_id=5914165983
	 */
	public function getItemId($url)
	{
		if (!empty($url) && stripos($url, '?') >= 0) {
			list($host, $query) = explode('?', $url);
			$query = str_replace('&amp;', '&', $query);
			parse_str($query, $params);
			return $params['goods_id'];
		}

		return 0;
	}

	public function getDescInfo($item)
	{
		$html = $item->goodsDesc;

		if (!isset($item->detail) || empty($item->detail)) {
			return $html;
		}

		foreach ($item->detail as $value) {
			$html .= '<img src="' . $value->url . '">';
		}

		return $html;
	}
}

class Local extends SDK
{
	/**
	 * 从链接提取商品ID
	 * 本站：http://www.xxx.com/goods/detail/5914165983
	 */
	public function getItemId($url)
	{
		if (!empty($url)) {
			list($host) = explode('?', $url);
			$params = explode('/', $host);
			return $params[count($params) - 1];
		}

		return 0;
	}

	public function detail($id)
	{
		$result = GoodsModel::find()->select('goods_name,brand,tags,description,spec_qty,spec_name_1,spec_name_2')->where(['goods_id' => $id])->asArray()->one();
		if ($result) {
			$result['goods_images'] = GoodsImageModel::find()->select('image_url,thumbnail,sort_order')->where(['goods_id' => $id])->orderBy(['sort_order' => SORT_ASC])->asArray()->all();
			$result['specs'] = GoodsSpecModel::find()->select('spec_1,spec_2,price,mkprice,stock,weight,image,sort_order')->where(['goods_id' => $id])->orderBy(['sort_order' => SORT_ASC])->asArray()->all();
		}
		return $result;
	}
}
