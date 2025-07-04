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
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;
use yii\helpers\HtmlPurifier;

use common\models\UserModel;

use common\library\Timezone;
use common\library\Plugin;
use common\library\Mailer;

/**
 * @Id Basewind.php 2018.3.2 $
 * @author mosir
 */

class Basewind
{
	/**
	 * 获取当前系统版本
	 * @return string 
	 */
	public static function getVersion()
	{
		return '5.0';
	}

	/**
	 * 环境检测/环境初始化/安装检测等，如果没有安装，跳转到安装界面
	 * @param bool $toInstall 如果没有安装，是否跳转至安装程序
	 */
	public static function environment($toInstall = true)
	{
		// 安装检测
		if (!self::isInstall() && $toInstall) {
			header('location:' . Basewind::baseUrl() . '/install');
			exit();
		}
		// ...
	}

	/**
	 * 安装检测
	 * @return bool	 
	 */
	public static function isInstall()
	{
		$file = Yii::getAlias('@public') . '/data/install.lock';

		// 已经安装了网站
		if (file_exists($file)) {
			return true;
		}
		return false;
	}

	/**
	 * 获取当前运行的应用程序名
	 * 如果找不到匹配的，默认电脑端
	 * @return string
	 */
	public static function getApp()
	{
		// 当前应用程序名
		$app = substr(Yii::getAlias('@app'), strripos(Yii::getAlias('@app'), DIRECTORY_SEPARATOR) + 1);

		// 允许的应用程序列表
		$list = ['backend' => 'admin', 'home' => 'pc', 'mob' => 'h5', 'api' => 'api'];
		foreach ($list as $key => $value) {
			if ($key == $app) return $value;
		}

		return 'pc';
	}

	/**
	 * 检测用户名是否存在
	 * @param string $username 要检测的用户名
	 * @param int $userid 是否排除指定用户
	 */
	public static function checkUser($username = '', $userid = 0)
	{
		if (!$username) return false;
		$query = UserModel::find()->where(['username' => $username]);
		if ($userid) $query->andWhere(['<>', 'userid', $userid]);
		if ($query->exists()) {
			return false;
		}
		return true;
	}

	/**
	 * 判断是否为正确的电子邮件地址
	 * @param string $email
	 */
	public static function isEmail($email = '')
	{
		if (!$email) return false;
		$chars = "/^([a-z0-9+_]|\\-|\\.)+@(([a-z0-9_]|\\-)+\\.)+[a-z]{2,5}\$/i";
		if (strpos($email, '@') !== false && strpos($email, '.') !== false) {
			if (preg_match($chars, $email)) {
				return true;
			}
			return false;
		}
		return false;
	}

	/**
	 * 检测电子邮件地址是否存在
	 * @param string $email 要检测的电子邮件地址
	 * @param int $userid 是否排除指定用户
	 */
	public static function checkEmail($email = '', $userid = 0)
	{
		if (!$email) return false;
		$query = UserModel::find()->where(['email' => $email]);
		if ($userid) $query->andWhere(['<>', 'userid', $userid]);
		if ($query->exists()) {
			return false;
		}
		return true;
	}

	/**
	 * 判断是否为正确的手机号
	 * @param string $phone
	 */
	public static function isPhone($phone = '')
	{
		if (!$phone) return false;
		if (preg_match("/^[1][3456789][0-9]{9}$/", $phone)) {
			return true;
		}
		return false;
	}

	/**
	 * 检测手机号是否可用，已存在返回不可用
	 * @param string $phone 要检测的手机号
	 * @param int $userid 是否排除指定用户
	 */
	public static function checkPhone($phone = '', $userid = 0)
	{
		if (!$phone) return false;
		$query = UserModel::find()->where(['phone_mob' => $phone]);
		if ($userid) $query->andWhere(['<>', 'userid', $userid]);
		if ($query->exists()) {
			return false;
		}
		return true;
	}

	/**
	 * 仅用于AJAX提交的验证码验证，请不要用于FORM提交的验证码验证 
	 * @param string $captcha
	 */
	public static function checkCaptcha($captcha = '')
	{
		$code = Yii::$app->controller->createAction('captcha')->getVerifyCode();
		if (strcasecmp($captcha, $code) === 0) { // 忽略大小写
			return true;
		}
		return false;
	}

	/**
	 * 清除不安全的JS标记（防止XSS攻击等）
	 * 数组转对象（并去掉字符串前后空格） 
	 * @param array|string|int $params
	 * @param bool $toObject 是否转成对象
	 * @param array $intvalFields 需要将$params中哪些字段的值转成整型
	 */
	public static function trimAll($params = null, $toObject = false, $intvalFields = array())
	{
		if (!is_array($params)) {
			if ($intvalFields === true) {
				return intval($params);
			} elseif (is_null($params) && $toObject == true) {
				return (object) $params;
			}
			return HtmlPurifier::process(trim($params));
		}

		foreach ($params as $k => $v) {
			if (is_string($v)) {
				$params[$k] = in_array($k, $intvalFields) ? intval($v) : HtmlPurifier::process(trim($v));
			} elseif (is_array($v) || is_object($v)) {
				$params[$k] = self::trimAll($v, $toObject);
			}
		}
		return $toObject ? (object)$params : $params;
	}

	/**
	 * 写入文件的安全过滤
	 * 插入数据库的不要加此过滤
	 */
	public static function filterAll($params = '')
	{
		if (!is_array($params)) {
			return htmlspecialchars($params, ENT_QUOTES, Yii::$app->charset); // 安全过滤
		}

		foreach ($params as $k => $v) {
			if (is_string($v)) {
				$params[$k] = htmlspecialchars($v, ENT_QUOTES, Yii::$app->charset); // 安全过滤
			} elseif (is_array($v)) {
				$params[$k] = self::filterAll($v);
			}
		}
		return $params;
	}

	/**
	 * 如果是数组，取第一条信息
	 * @param array|string $message
	 */
	public static function getFirstLine($message)
	{
		if (!is_array($message)) return $message;
		return self::getFirstLine(current($message));
	}

	/**
	 * 发送邮件需要用到邮件模板，模板中的变量需要兼容smarty解析，故先生成html视图，才能解析变量 
	 * @param string $view
	 * @param string $key
	 * @param string $template
	 */
	public static function createMailFile($view, $key, $template = 'mailtemplate')
	{
		$array = include(Yii::getAlias('@common') . '/mail/' . $template . '/' . $view . '.php');

		$path = dirname(Yii::getAlias('@runtime') . '/mail/' . $template . '/cache/' . $key);
		$file = $path . "/{$view}_{$key}.html";
		if (!file_exists($file)) {
			FileHelper::createDirectory($path);
			file_put_contents($file, $array[$key]);
		}
		return $file;
	}

	/**
	 * 获取站内信发送内容（暂时用Mail的模板） 
	 * @param string $view
	 * @param array $params
	 */
	public static function getPmer($view, $params = [])
	{
		$params = ArrayHelper::merge([
			'base_url' => self::baseUrl(),
			'site_url'  => self::homeUrl(),
			'site_name' => Yii::$app->params['site_name'],
			'send_time' => Timezone::localDate('Y-m-d H:i:s', Timezone::gmtime())
		], $params);

		$subject = Yii::$app->controller->renderFile(self::createMailFile($view, 'subject'), $params);
		$content = Yii::$app->controller->renderFile(self::createMailFile($view, 'content'), $params);

		$model = new \frontend\home\models\PmSendForm();
		$model->title = $subject;
		$model->content = $content;

		return $model;
	}

	/**
	 * 获取邮件发送对象 
	 * @param string $view
	 * @param array $params
	 */
	public static function getMailer($view, $params = [])
	{
		if (!isset(Yii::$app->params['mailer']) || empty(Yii::$app->params['mailer'])) {
			return false;
		}

		$params = ArrayHelper::merge([
			'base_url' => self::baseUrl(),
			'site_url'  => self::homeUrl(),
			'site_name' => Yii::$app->params['site_name'],
			'send_time' => Timezone::localDate('Y-m-d H:i:s', Timezone::gmtime())
		], $params);

		$subject = Yii::$app->controller->renderFile(self::createMailFile($view, 'subject'), $params);
		$content = Yii::$app->controller->renderFile(self::createMailFile($view, 'content'), $params);

		try {
			$mailer = new Mailer();
			return $mailer->compose()->setSubject($subject)->setHtmlBody($content);
		} catch (\Exception $e) {
			//print_r($e->getMessage());
			return false;
		}
	}

	/**
	 * 针对订单通知的邮件发送和短信发送 
	 * @param array $order 订单信息
	 * @param array $mail 邮件内容体
	 * @param array $sms 短信内容体
	 */
	public static function sendMailMsgNotify($order = [], $mail = [], $sms = [])
	{
		// 发送邮件提醒
		if ($mail) {
			$receiver = intval($mail['receiver']);
			$toEmail = UserModel::find()->select('email')->where(['userid' => $receiver])->scalar();

			try {
				if ($toEmail && ($mailer = self::getMailer($mail['key'], ['order' => $order]))) {
					$mailer->setTo($toEmail)->send();
				}
			} catch (\Exception $e) {
				//print_r($e->getMessage());
			}
		}

		// 发送短信提醒
		if ($sms) {
			try {
				if ($smser = Plugin::getInstance('sms')->autoBuild()) {
					$smser->sender =  intval($sms['sender']);
					$smser->scene = $sms['key'];

					// 获取手机号
					if (!Basewind::isPhone($sms['receiver'])) { //  receiver = phone || userid
						$smser->receiver = UserModel::find()->select('phone_mob')->where(['userid' => intval($sms['receiver'])])->scalar();
					}

					// 去掉Null值，防止短信接口（阿里云）解析JSON有误
					foreach ($order as $key => $value) {
						if (!$value) unset($order[$key]);
					}
					$smser->templateParams = $order;
					$smser->send();
				}
			} catch (\Exception $e) {
				//print_r($e->getMessage());
			}
		}
	}

	/**
	 * 判断是否为微信客户端
	 * @return bool
	 */
	public static function isWeixin()
	{
		if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false) {
			return true;
		}
		return false;
	}

	/**
	 * 判断是否为QQ内置浏览器
	 * @return bool
	 */
	public static function isQqbrowser()
	{
		if (strpos($_SERVER['HTTP_USER_AGENT'], 'QQ') !== false) {
			return true;
		}
		return false;
	}

	/**
	 * 判断是否为支付宝客户端
	 * @return bool
	 */
	public static function isAlipay()
	{
		if (strpos($_SERVER['HTTP_USER_AGENT'], 'AlipayClient') !== false) {
			return true;
		}
		return false;
	}

	/**
	 * 判断是否为头条小程序端
	 * @return bool
	 */
	public static function isToutiao()
	{
		if (strpos($_SERVER['HTTP_USER_AGENT'], 'ToutiaoMicroApp') !== false) {
			return true;
		}
		return false;
	}

	/**
	 * 判断是否为移动设备
	 * @return bool
	 */
	public static function isMobile()
	{
		// 如果有HTTP_X_WAP_PROFILE则一定是移动设备
		if (isset($_SERVER['HTTP_X_WAP_PROFILE'])) {
			return true;
		}
		// 如果via信息含有wap则一定是移动设备,部分服务商会屏蔽该信息
		if (isset($_SERVER['HTTP_VIA'])) {
			// 找不到为flase,否则为true
			return stristr($_SERVER['HTTP_VIA'], "wap") ? true : false;
		}
		// 脑残法，判断手机发送的客户端标志,兼容性有待提高
		if (isset($_SERVER['HTTP_USER_AGENT'])) {
			$clientkeywords = array('nokia', 'sony', 'ericsson', 'mot', 'samsung', 'htc', 'sgh', 'lg', 'sharp', 'sie-', 'philips', 'panasonic', 'alcatel', 'lenovo', 'iphone', 'ipod', 'blackberry', 'meizu', 'android', 'netfront', 'symbian', 'ucweb', 'windowsce', 'palm', 'operamini', 'operamobi', 'openwave', 'nexusone', 'cldc', 'midp', 'wap', 'mobile');
			// 从HTTP_USER_AGENT中查找手机浏览器的关键字
			if (preg_match("/(" . implode('|', $clientkeywords) . ")/i", strtolower($_SERVER['HTTP_USER_AGENT']))) {
				return true;
			}
		}
		// 协议法，因为有可能不准确，放到最后判断
		if (isset($_SERVER['HTTP_ACCEPT'])) {
			// 如果只支持wml并且不支持html那一定是移动设备
			// 如果支持wml和html但是wml在html之前则是移动设备
			if ((strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') !== false) && (strpos($_SERVER['HTTP_ACCEPT'], 'text/html') === false || (strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') < strpos($_SERVER['HTTP_ACCEPT'], 'text/html')))) {
				return true;
			}
		}
		return false;
	}

	/**
	 * 发送CURL请求
	 * @param string $url 发送地址
	 * @param string $method 提交方式 get|post|put
	 * @param string $post 提交参数
	 * @param string $cacert_url 证书地址
	 */
	public static function curl($url, $method = 'GET', $post = '', $cacert_url = '')
	{
		//初始化curl
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

		if ($cacert_url) {
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); //SSL证书认证
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2); //严格认证
			curl_setopt($ch, CURLOPT_CAINFO, $cacert_url); //证书地址
		}

		// 以JSON数组的形式提交
		if (!is_array($post) && $method != 'GET') {
			curl_setopt(
				$ch,
				CURLOPT_HTTPHEADER,
				array(
					"Content-Type: application/json; charset=utf-8",
					"Content-Length: " . strlen($post)
				)
			);
		}

		//设置超时
		//curl_setopt($ch, CURLOP_TIMEOUT, $this->curl_timeout);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		if (strtoupper($method) == 'POST') {
			curl_setopt($ch, CURLOPT_POST, 1); //post提交方式
			curl_setopt($ch, CURLOPT_POSTFIELDS, is_array($post) ? http_build_query($post) : $post);
		}
		if (strtoupper($method) == 'PUT') {
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
		}

		//运行curl，结果以jason形式返回
		$res = curl_exec($ch);
		//var_dump( curl_error($ch) );//如果执行curl过程中出现异常，可打开此开关，以便查看异常内容
		curl_close($ch);
		return $res;
	}

	/**
	 * 获得访问网站首页的URL地址
	 * @desc 如果配置了多个域名指向网站，获取的是实际访问的域名地址
	 * @err 获取有误时查看：https://xiaozhuanlan.com/topic/8720164395
	 * 解决：在 Nginx 的代理配置中加上 proxy_set_header X-Forwarded-Proto https;
	 * location / {
	 *  proxy_set_header Host $host;
	 *  proxy_set_header X-Real-Ip $remote_addr;
	 *  proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
	 *  proxy_set_header X-Forwarded-Proto https; # 加上这一句
	 *  proxy_redirect off;
	 *  proxy_pass http://yii2-app-upstream;
	 * }

	 * @return string
	 */
	public static function siteUrl()
	{
		$phpself = htmlentities(isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : $_SERVER['SCRIPT_NAME']);
		return Yii::$app->request->hostInfo . substr($phpself, 0, strrpos($phpself, '/'));
	}

	/**
	 * 获取安装时域名根地址
	 * @return string
	 */
	public static function baseUrl()
	{
		if (isset(Yii::$app->params['baseUrl']) && !empty(Yii::$app->params['baseUrl'])) {
			return Yii::$app->params['baseUrl'];
		}

		return dirname(self::siteUrl());
	}

	/**
	 * 获取安装时网站前台首页地址
	 * @return string
	 */
	public static function homeUrl()
	{
		if (isset(Yii::$app->params['homeUrl']) && !empty(Yii::$app->params['homeUrl'])) {
			return Yii::$app->params['homeUrl'];
		}

		return self::baseUrl() . '/home';
	}

	/**
	 * 获取安装时网站后台首页地址
	 * @return string
	 */
	public static function adminUrl()
	{
		if (isset(Yii::$app->params['adminUrl']) && !empty(Yii::$app->params['adminUrl'])) {
			return Yii::$app->params['adminUrl'];
		}

		return self::baseUrl() . '/admin';
	}

	/**
	 * 获取移动端的首页地址
	 * @return string
	 */
	public static function mobileUrl($vueurl = false)
	{
		if ($vueurl) {
			return Yii::$app->params['mobileUrl'];
		}

		// DIY编辑模式场景需要
		return self::baseUrl() . '/mob';
	}
}
