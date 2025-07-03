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
use yii\web\Response;
use yii\helpers\FileHelper;
use yii\helpers\Url;

use common\library\Basewind;
use common\library\Setting;
use common\library\Def;

/**
 * @Id Page.php 2018.3.8 $
 * @author mosir
 */

class Page
{
	/* 页面SEO */
	public static function seo($seo = [])
	{
		$params = Yii::$app->params;
		if (Basewind::isInstall() != true) {
			$params = Setting::getDefault();
		}

		$page = [
			'menu'			=> $params['site_name'],
			'title' 		=> $params['site_title'],
			'keywords' 		=> $params['site_keywords'],
			'description' 	=> $params['site_description']
		];

		foreach ($seo as $k => $v) {
			if (isset($page[$k])) {
				$page[$k] = $v . ($k == 'title' ? ' - ' : ',') . $page[$k];
			}
			if ($k == 'title' && !empty($v)) {
				$menu = explode('-', $v);
				$page['menu'] = trim($menu[0]);
			}
		}
		$page['title'] .= str_replace(['\a', '\b', '\c', '\f', '\g', '\j', '\k', '\l', '\v'], '', ' - P\aow\ber\ced b\fy S\gh\jop\kW\li\vnd');

		return $page;
	}

	/**
	 * 获取当前主题目录
	 * PC及移动端主题[可拓展为动态获取]
	 * @param $client pc|h5
	 */
	public static function getTheme($client = '')
	{
		// ...

		return 'default';
	}

	/**
	 * 在ACTION执行前跳转 
	 * 跳转到登录页面后，如登录成功，跳回到 
	 * @param string $url
	 */
	public static function redirect($url = null)
	{
		// $loginUrl = Yii::$app->user->loginUrl;
		$loginUrl = Url::toRoute(['user/login', 'redirect' => $url]);

		if (Yii::$app->request->isAjax) {
			Yii::$app->getResponse()->format = Response::FORMAT_JSON;
			Yii::$app->getResponse()->data = ['done' => false, 'icon' => 'warning', 'msg' => Yii::t('yii', 'Login Required'), 'loginUrl' => $loginUrl];
			return false;
		}
		return Yii::$app->getResponse()->redirect($loginUrl);
	}

	/**
	 * 将相对地址修改为绝对地址，以适应不同的应用显示
	 * @desc 主要是处理图片路径，不要使用在JS文件路径（以免引起跨域问题）
	 */
	public static function urlFormat($url = '', $default = '')
	{
		if (empty($url)) $url = $default;

		if (!empty($url) && Url::isRelative($url)) {
			return Basewind::baseUrl() . '/' . $url;
		}
		return $url;
	}

	public static function getPage($totalCount = 0, $pageSize = 10, $isAJax = false, $curPage = false)
	{
		$pagination = new \yii\data\Pagination();
		$pagination->totalCount = $totalCount;
		$pagination->pageSize = abs(intval($pageSize)) ? intval($pageSize) : 10;
		$pagination->pageSizeParam = false;
		$pagination->validatePage = false;
		$pagination->isAjax = $isAJax;

		// 针对API接口，通过非GET形式实现的翻页
		// 该组件当前页是从0开始算的，所以减1
		if ($curPage !== false) {
			$pagination->setPage($curPage - 1, false);
		}

		return $pagination;
	}

	/**
	 * 返回分页数据
	 * 是否美化分页效果
	 * API接口返回的分页数据不需要美化效果
	 */
	public static function formatPage($page = null, $prettify = true, $style = 'default')
	{
		// for API
		if ($prettify == false) {
			return [
				'page' => $page->getPage() + 1,
				'page_size' => $page->getPageSize(),
				'page_count' => $page->getPageCount(),
				'total' => (int)$page->totalCount
			];
		}

		$config = [
			'pagination' 	=> $page,
			'nextPageLabel' => '下一页',
			'prevPageLabel' => '上一页',
			'firstPageLabel' => '首页',
			'lastPageLabel' => '尾页',
			//'totalPageLabel' => '共%s页',
			//'totalCountLabel' => '共%s条记录',
			// 分页样式
			'options' 	=> ['class' => 'pagination pagination-' . $style],
			// 不够两页，隐藏分页，默认true 
			'hideOnSinglePage' => false,
			// 设置要展示是页数
			'maxButtonCount' => 5
		];

		if (in_array($style, ['simple'])) {
			$config['nextPageLabel'] = '>';
			$config['prevPageLabel'] = '<';
			$config['firstPageLabel'] = false;
			$config['lastPageLabel'] = false;
			$config['options'] = ['class' => 'pagination pagination-sm pagination-' . $style];
			$config['maxButtonCount'] = 0;
		} elseif (in_array($style, ['basic'])) {
			$config['maxButtonCount'] = 3;
		} else {
			$config['totalCountLabel'] = '共%s条记录';
		}

		return \yii\widgets\LinkPager::widget($config);
	}

	/**
	 * 生成二维码图片
	 */
	public static function generateQRCode($path = 'qrcode/', $params = [])
	{
		$text = isset($params['text']) ? $params['text'] : 'TEXT';
		$size =  isset($params['size']) ? floatval($params['size']) : 100;
		$margin = isset($params['margin']) ? floatval($params['margin']) : 5;

		$outfile = Def::fileSavePath() . '/data/files/mall/' . $path;
		if (!is_dir($outfile)) {
			FileHelper::createDirectory($outfile);
		}

		$outfile .= md5((__METHOD__) . var_export(func_get_args(), true)) . '.png';
		if (!file_exists($outfile)) {
			$qrCode = (new \Da\QrCode\QrCode($text))->setSize($size)->setMargin($margin);
			$qrCode->writeFile($outfile);
		}

		$fileurl = str_replace(Def::fileSavePath(), Basewind::baseUrl(), $outfile);
		return array($fileurl, $outfile, $size + $margin * 2);
	}

	/**
	 * 生成宣传海报
	 * @param array  参数,包括图片和文字
	 * @param string  $filename 生成海报文件名,不传此参数则不生成文件,直接输出图片
	 * @param boolean $overlay 是否覆盖
	 * @return [type] [description]
	 */
	public static function createPoster($config = array(), $filename = "", $overlay = false)
	{
		if (file_exists($filename) && !$overlay) return $filename;

		//如果要看报什么错，可以先注释调这个header
		if (empty($filename)) header("content-type: image/png");
		$imageDefault = array(
			'left' => 0,
			'top' => 0,
			'right' => 0,
			'bottom' => 0,
			'width' => 100,
			'height' => 100,
			'opacity' => 100
		);
		$textDefault = array(
			'text' => '',
			'top' => 0,
			'fontPath' => Yii::getAlias('@common') . '/font/yahei.ttf',     //字体文件
			'fontSize' => 32,       //字号
			'fontColor' => '0,0,0', //字体颜色
			'angle' => 0,
		);
		$background = self::replaceHttps($config['background']); //海报最底层得背景
		//背景方法
		$backgroundInfo = getimagesize($background);
		$backgroundFun = 'imagecreatefrom' . image_type_to_extension($backgroundInfo[2], false);
		$background = $backgroundFun($background);
		$backgroundWidth = imagesx($background);  //背景宽度
		$backgroundHeight = imagesy($background);  //背景高度
		$imageRes = imageCreatetruecolor($backgroundWidth, $backgroundHeight);
		$color = imagecolorallocate($imageRes, 255, 255, 255);
		imagefill($imageRes, 0, 0, $color);
		// imageColorTransparent($imageRes, $color);  //颜色透明
		imagecopyresampled($imageRes, $background, 0, 0, 0, 0, imagesx($background), imagesy($background), imagesx($background), imagesy($background));

		//处理图片
		if (!empty($config['image'])) {
			foreach ($config['image'] as $key => $val) {
				$val = array_merge($imageDefault, $val);
				$val['url'] = self::replaceHttps($val['url']);
				$info = getimagesize($val['url']);
				$function = 'imagecreatefrom' . image_type_to_extension($info[2], false);
				if ($val['stream']) {   //如果传的是字符串图像流
					$info = getimagesizefromstring($val['url']);
					$function = 'imagecreatefromstring';
				}
				$res = $function($val['url']);
				$resWidth = $info[0];
				$resHeight = $info[1];
				//建立画板 ，缩放图片至指定尺寸
				$canvas = imagecreatetruecolor($val['width'], $val['height']);
				imagefill($canvas, 0, 0, $color);
				//关键函数，参数（目标资源，源，目标资源的开始坐标x,y, 源资源的开始坐标x,y,目标资源的宽高w,h,源资源的宽高w,h）
				imagecopyresampled($canvas, $res, 0, 0, 0, 0, $val['width'], $val['height'], $resWidth, $resHeight);
				$val['left'] = $val['left'] < 0 ? $backgroundWidth - abs($val['left']) - $val['width'] : $val['left'];
				$val['top'] = $val['top'] < 0 ? $backgroundHeight - abs($val['top']) - $val['height'] : $val['top'];
				//放置图像
				imagecopymerge($imageRes, $canvas, $val['left'], $val['top'], $val['right'], $val['bottom'], $val['width'], $val['height'], $val['opacity']); //左，上，右，下，宽度，高度，透明度
			}
		}
		//处理文字
		if (!empty($config['text'])) {
			foreach ($config['text'] as $key => $val) {
				$val = array_merge($textDefault, $val);
				list($R, $G, $B) = explode(',', $val['fontColor']);
				$fontColor = imagecolorallocate($imageRes, $R, $G, $B);

				if (isset($val['left'])) {
					$val['left'] = $val['left'] < 0 ? $backgroundWidth - abs($val['left']) : $val['left'];
				} else {
					$fontBox = imagettfbbox($val['fontSize'], $val['angle'], $val['fontPath'], $val['text']); //文字水平居中设置
					$val['left'] = ceil(($backgroundWidth - $fontBox[2]) / 2);
				}

				$val['top'] = $val['top'] < 0 ? $backgroundHeight - abs($val['top']) : $val['top'];
				imagettftext($imageRes, $val['fontSize'], $val['angle'], $val['left'], $val['top'], $fontColor, $val['fontPath'], $val['text']);
			}
		}

		//生成图片
		if (!empty($filename)) {
			$res = imagejpeg($imageRes, $filename, 90); //保存到本地
			imagedestroy($imageRes);
			if (!$res) return false;
			return $filename;
		} else {
			imagejpeg($imageRes);     //在浏览器上显示
			imagedestroy($imageRes);
		}
	}

	/**
	 * 将图片访问地址https修改为http
	 * 部分php函数不支持https图片（如：getimagesize）
	 * 注意：（宝塔)Web环境不要开启强制https
	 */
	private static function replaceHttps($value)
	{
		if (substr($value, 0, 2) == '//') {
			return 'http:' . $value;
		}

		return str_replace("https://", "http://", $value);
	}

	/**
	 * 导出xlsx文件
	 */
	public static function export($config = [], $outfile = false)
	{
		$writer = new \XLSXWriter();

		$writer->setTempDir(Yii::getAlias('@public/data'));
		$writer->writeSheet($config['models']);

		if (!$outfile) {
			header('Content-disposition: attachment; filename="' . $writer->sanitize_filename($config['filename']) . '.xlsx"');
			header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
			header('Content-Transfer-Encoding: binary');
			header('Cache-Control: must-revalidate');
			header('Pragma: public');
			$writer->writeToStdOut();
			exit(0);
		}

		// 输出下载文件路径
		$path = Yii::getAlias('@public/data/files/mall/excel/');
		@mkdir($path, 0777);

		$file = $path . $config['filename'] . '.xlsx';
		$writer->writeToFile($file);

		return str_replace(Yii::getAlias('@public'), Basewind::baseUrl(), $file);
	}

	public static function writeLog($key = '', $word = '')
	{
		//$word = json_encode($word); // for AJAX debug
		$word = var_export($word, true);

		$path = dirname(Yii::getAlias('@public')) . "/logs/" . date('Ymd', time());
		if (!is_dir($path)) {
			\yii\helpers\FileHelper::createDirectory($path);
		}

		$fp = fopen($path . "/log.txt", "a");
		flock($fp, LOCK_EX);
		fwrite($fp, $key . " At:" . date("Y-m-d H:i:s", time()) . "[IP:" . Yii::$app->request->userIP . "]\n" . $word . "\n");
		flock($fp, LOCK_UN);
		fclose($fp);
	}
}
