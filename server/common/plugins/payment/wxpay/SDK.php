<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace common\plugins\payment\wxpay;

use yii;
use yii\helpers\Url;

use common\library\Basewind;
use common\library\Language;
use common\library\Def;

use WeChatPay\Builder;
use WeChatPay\Crypto\Rsa;
use WeChatPay\Crypto\AesGcm;
use WeChatPay\Util\PemUtil;
use WeChatPay\Formatter;

/**
 * @Id SDK.php 2018.7.19 $
 * @author mosir
 * 
 * docs: JSAPI支付：https://pay.weixin.qq.com/wiki/doc/apiv3/apis/chapter3_1_1.shtml
 * docs: Native支付（扫码）：https://pay.weixin.qq.com/wiki/doc/apiv3/apis/chapter3_4_1.shtml
 * docs: APP支付：https://pay.weixin.qq.com/wiki/doc/apiv3/apis/chapter3_2_1.shtml
 * docs: 小程序支付：https://pay.weixin.qq.com/wiki/doc/apiv3/apis/chapter3_5_1.shtml
 * docs: H5支付：https://pay.weixin.qq.com/wiki/doc/apiv3/apis/chapter3_3_1.shtml
 * docs: 申请退款：https://pay.weixin.qq.com/wiki/doc/apiv3/apis/chapter3_1_9.shtml
 * docs: 商家转账[旧]：https://pay.weixin.qq.com/wiki/doc/apiv3/apis/chapter4_3_1.shtml
 * docs: 商家转账[新]：https://pay.weixin.qq.com/doc/v3/merchant/4012711988
 * docs：不使用SDK签名/验签参考：https://blog.csdn.net/qq_32550561/article/details/117751562
 */

class SDK
{
	/**
	 * 网关地址
	 * @var string $gateway
	 */
	public $gateway = 'https://api.mch.weixin.qq.com';

	/**
	 * 应用ID
	 * @var string $appId
	 */
	public $appId;

	/**
	 * 应用秘钥
	 * @var string $appSecret
	 */
	public $appSecret;

	/**
	 * 直连商户号
	 * @var string $mchId
	 */
	public $mchId;

	/**
	 * 商户apiv3秘钥
	 * @var string $mchKey
	 */
	public $mchKey;

	/**
	 * 商户证书序列号
	 * @var string $serialNo
	 */
	public $serialNo;

	/**
	 * 商户证书文件
	 * @var string $clientKey
	 */
	public $clientKey;

	/**
	 * 微信证书文件 & 微信公钥文件
	 * @var string $wechatKey
	 */
	public $wechatKey;

	/**
	 * 微信公钥ID
	 */
	public $publicKeyId;

	/**
	 * 支付交易号
	 * @var string $payTradeNo
	 */
	public $payTradeNo;

	/**
	 * 通知地址
	 * @var string $notifyUrl
	 */
	public $notifyUrl;

	/**
	 * 返回地址
	 * @var string $returnUrl
	 */
	public $returnUrl;

	/**
	 * 微信授权CODE
	 * @var string $code
	 */
	public $code;

	/**
	 * 支付终端
	 * @var string $terminal
	 * @return string APP|WAP|PC|MP
	 */
	public $terminal;

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

			if (substr($value, -3) == 'pem') {
				$value = dirname(__FILE__) . DIRECTORY_SEPARATOR . $value;
			}
			$this->$key = $value;
		}
	}

	/**
	 * 预下单
	 */
	public function payData($orderInfo = [])
	{
		try {

			$extraParams = $this->getExtraParams();
			$post = [
				'json' => array_merge([
					'mchid'        => $this->mchId,
					'out_trade_no' => $this->payTradeNo,
					'appid'        => $this->appId,
					'description'  => mb_substr($orderInfo['title'], 0, 60, 'UTF-8'), // length <= 127
					'notify_url'   => $this->notifyUrl,
					'amount'       => [
						'total'    => intval($orderInfo['amount'] * 100),
						'currency' => 'CNY'
					]
				], $extraParams)
			];

			$response = $this->getInstance()->chain($this->getMethod())->post($post);

			$body = $response->getBody();
			if ($response->getStatusCode() != 200) {
				$this->errors = $body;
				return false;
			}
			return json_decode($body->getContents(), true);
		} catch (\Exception $e) {
			if ($e instanceof \GuzzleHttp\Exception\RequestException && $e->hasResponse()) {
				$this->errors = json_decode($e->getResponse()->getBody())->message;
			}
			if (!$this->errors) {
				$this->errors = $e->getMessage() . $e->getTraceAsString();
			}
			return false;
		}
	}

	/**
	 * 订单退款
	 * 由微信处理原路返回策略
	 * @desc 为了更好的处理业务，调用成功即认为微信退款成功（支付宝也即同步调用），暂不考虑异步通知的情况
	 */
	public function refundData($orderInfo = [])
	{
		try {

			$response = $this->getInstance()->chain($this->getMethod(true))->post([
				'json' => [
					'out_trade_no' => $this->payTradeNo,
					'out_refund_no' => $orderInfo['refund_sn'],
					//'notify_url'   => $this->notifyUrl,
					//'reason' => '',
					'amount'       => [
						'refund'   => intval($orderInfo['amount'] * 100),
						'total'    => intval($orderInfo['total'] * 100),
						'currency' => 'CNY'
					]
				]
			]);

			if ($response->getStatusCode() != 200) {
				$this->errors = $response->getBody();
				return false;
			}
			return true;
		} catch (\Exception $e) {
			if ($e instanceof \GuzzleHttp\Exception\RequestException && $e->hasResponse()) {
				$this->errors = json_decode($e->getResponse()->getBody())->message;
			}
			if (!$this->errors) {
				$this->errors = $e->getMessage() . $e->getTraceAsString();
			}
			return false;
		}
	}

	/**
	 * 商家转账（新版接口）
	 * 必须确保OPENID是在该APPID下获取的值
	 * 返回成功仅代表业务受理成功，严格来说，需要通过查询微信查询明细单 状态来确认是否转账成功
	 * 资金要求：单笔0.3-200，单日单用户 2000，单日5万
	 * 延伸阅读：https://zhuanlan.zhihu.com/p/5326308493
	 * 延伸阅读：https://developers.weixin.qq.com/community/pay/doc/000a060bb4c13095b6b27cc1b6ac01?page=1#comment-list
	 * 备注：该接口目前已经调通，并能返回结果，但由于还需要在前端，提现用户手动确认收款才能完成提现流程
	 * 且uniapp框架并没有提供小程序/H5及APP的对应api实现方法，故流程无法继续完成，暂且保留
	 */
	public function transferData($orderInfo = [])
	{
		// 微信商户平台提现金额限制
		if ($orderInfo['amount'] < 0.3 || $orderInfo['amount'] > 200) {
			$this->errors = Language::get('money limit 0.3 ~ 200');
			return false;
		}
		try {

			$platformCertificateFilePath = file_get_contents($this->wechatKey);
			$response = $this->getInstance()->chain($this->getMethod(false, true))->post([
				'json' => [
					'appid' => $this->appId,
					'out_bill_no' => $this->payTradeNo,
					'transfer_scene_id' => '1009', // 采购货款，向个人供应商支付货款、采购款、服务费等
					'openid' => $orderInfo['payee']['account'],
					'transfer_amount' => intval($orderInfo['amount'] * 100),
					'transfer_remark' => $orderInfo['title'],

					// 商家转账单据到终态后（转账完成或者转账失败，对应单据状态status的值为SUCCESS、CANCELLED和FAIL）
					// 微信支付会把单据的信息发送给商户，商户需要接收处理该消息，并返回应答
					'notify_url' => Url::toRoute(['paynotify/wxtransfer'], true),
					//'user_recv_perception' => '', // 向个人供应商支付货款、采购款、服务费等。默认货款
					'user_name' => $this->getEncrypt($orderInfo['payee']['name']),
					'transfer_scene_report_infos' => [
						[
							'info_type' => '采购商品名称',
							'info_content' => '手机壳'
						]
					]
				],
				'headers' => [
					'wechatpay-serial' => PemUtil::parseCertificateSerialNo($platformCertificateFilePath) // 获取微信支付平台证书序列号
				]
			]);

			$body = $response->getBody();
			if ($response->getStatusCode() != 200) {
				$this->errors = $body;
				return false;
			}
			return json_decode($body->getContents(), true);
		} catch (\Exception $e) {
			if ($e instanceof \GuzzleHttp\Exception\RequestException && $e->hasResponse()) {
				$this->errors = json_decode($e->getResponse()->getBody())->message;
			}
			if (!$this->errors) {
				$this->errors = $e->getMessage() . $e->getTraceAsString();
			}
			return false;
		}
	}

	/**
	 * 商家转账到零钱（该产品微信已关闭，即将废弃）
	 * 必须确保OPENID是在该APPID下获取的值
	 * 返回成功仅代表业务受理成功，严格来说，需要通过查询微信查询明细单 状态来确认是否转账成功
	 */
	public function transferData1($orderInfo = [])
	{
		// 微信商户平台提现金额限制
		if ($orderInfo['amount'] < 0.3 || $orderInfo['amount'] > 200) {
			$this->errors = Language::get('money limit 0.3 ~ 200');
			return false;
		}
		try {

			$platformCertificateFilePath = file_get_contents($this->wechatKey);
			$response = $this->getInstance()->chain($this->getMethod(false, true))->post([
				'json' => [
					'appid' => $this->appId,
					'out_batch_no' => $this->payTradeNo,
					'batch_name' => $orderInfo['title'],
					'batch_remark' => $orderInfo['title'],
					'total_amount' => intval($orderInfo['amount'] * 100),
					'total_num' => 1,
					'transfer_detail_list' => [
						array_merge([
							'out_detail_no' => $this->payTradeNo,
							'transfer_amount' => intval($orderInfo['amount'] * 100),
							'transfer_remark' => $orderInfo['title'],
							'openid' => $orderInfo['payee']['account'],
						], $orderInfo['amount'] >= 2000 ? ['user_name' => $this->getEncrypt($orderInfo['payee']['name'])] : [])
					]
				],
				'headers' => [
					'wechatpay-serial' => PemUtil::parseCertificateSerialNo($platformCertificateFilePath) // 获取微信支付平台证书序列号
				]
			]);

			$body = $response->getBody();
			if ($response->getStatusCode() != 200) {
				$this->errors = $body;
				return false;
			}
			return json_decode($body->getContents());
		} catch (\Exception $e) {
			if ($e instanceof \GuzzleHttp\Exception\RequestException && $e->hasResponse()) {
				$this->errors = json_decode($e->getResponse()->getBody())->message;
			}
			if (!$this->errors) {
				$this->errors = $e->getMessage() . $e->getTraceAsString();
			}
			return false;
		}
	}

	/**
	 * 查询明细单[商家转账到零钱]（新版）
	 * 只考虑一个批次一笔转账，如果微信提现有问题，可以通过查接口查询提现单提现失败原因
	 * https://pay.weixin.qq.com/wiki/doc/apiv3/apis/chapter4_3_6.shtml
	 */
	public function orderDetail()
	{
		try {

			$response = $this->getInstance()->chain($this->getMethod(false, false, true))->get();

			$body = $response->getBody();
			if ($response->getStatusCode() != 200) {
				$this->errors = $body;
				return false;
			}
			return json_decode($body->getContents());
		} catch (\Exception $e) {
			if ($e instanceof \GuzzleHttp\Exception\RequestException && $e->hasResponse()) {
				$this->errors = json_decode($e->getResponse()->getBody())->message;
			}
			if (!$this->errors) {
				$this->errors = $e->getMessage() . $e->getTraceAsString();
			}
			return false;
		}
	}

	/**
	 * 查询明细单[商家转账到零钱]（旧版，即将废弃）
	 * 只考虑一个批次一笔转账，如果微信提现有问题，可以通过查接口查询提现单提现失败原因
	 * https://pay.weixin.qq.com/wiki/doc/apiv3/apis/chapter4_3_6.shtml
	 */
	public function orderDetail1()
	{
		try {

			$response = $this->getInstance()->chain($this->getMethod(false, false, true))->get([
				'query' => [
					'need_query_detail' => true,
					'detail_status' => 'ALL'
				]
			]);

			$body = $response->getBody();
			if ($response->getStatusCode() != 200) {
				$this->errors = $body;
				return false;
			}
			return json_decode($body->getContents(), true);
		} catch (\Exception $e) {
			if ($e instanceof \GuzzleHttp\Exception\RequestException && $e->hasResponse()) {
				$this->errors = json_decode($e->getResponse()->getBody())->message;
			}
			if (!$this->errors) {
				$this->errors = $e->getMessage() . $e->getTraceAsString();
			}
			return false;
		}
	}

	/**
	 * 验证通知
	 */
	public function verifyNotify($orderInfo, $notify)
	{
		// 验证与本地信息是否匹配。这里不只是付款通知，有可能是发货通知，确认收货通知
		if ($orderInfo['payTradeNo'] != $notify['out_trade_no']) {
			// 通知中的订单与欲改变的订单不一致
			$this->errors = Language::get('order_inconsistent');
			return false;
		}

		// 必须加intval避免字符类型不一致导致比对有误
		if (intval($orderInfo['amount'] * 100) != intval($notify['amount']['total'])) {
			// 支付的金额与实际金额不一致
			$this->errors = Language::get('price_inconsistent');
			return false;
		}

		if ($notify['trade_state'] == 'SUCCESS') {
			$order_status = Def::ORDER_ACCEPTED;
		} else {
			$this->errors = Language::get('undefined_status');
			return false;
		}

		return array('target' => $order_status);
	}

	/**
	 * 验证签名
	 */
	public function verifySign($notify)
	{
		$headers = Yii::$app->request->headers;
		$inWechatpaySignature = $headers->get('wechatpay-signature');
		$inWechatpayTimestamp = $headers->get('wechatpay-timestamp');
		$inWechatpaySerial = $headers->get('wechatpay-serial');
		$inWechatpayNonce = $headers->get('wechatpay-nonce');
		$inBody = file_get_contents('php://input');

		// 根据通知的平台证书序列号，查询本地平台证书文件
		$platformPublicKeyInstance = Rsa::from(file_get_contents($this->wechatKey), Rsa::KEY_TYPE_PUBLIC);

		// 检查通知时间偏移量，允许5分钟之内的偏移
		$timeOffsetStatus = 300 >= abs(Formatter::timestamp() - (int)$inWechatpayTimestamp);
		$verifiedStatus = Rsa::verify(
			// 构造验签名串
			Formatter::joinedByLineFeed($inWechatpayTimestamp, $inWechatpayNonce, $inBody),
			$inWechatpaySignature,
			$platformPublicKeyInstance
		);
		if ($timeOffsetStatus && $verifiedStatus) {
			return true;
		}

		return false;
	}

	/**
	 * 应答微信通知
	 */
	public function verifyResult($target = false)
	{
		if ($target) {
			$result = ['code' => 'SUCCESS', 'message' => ''];
		} else {
			$result = ['code' => 'FAIL', 'message' => 'fail'];
		}
		echo json_encode($result);
	}

	/**
	 * 获取解密后的数据
	 */
	public function getNotify()
	{
		if (!$this->mchKey) {
			return false;
		}

		// 转换通知的JSON文本消息为PHP Array数组
		$inBodyObj = json_decode(file_get_contents('php://input'));
		// 加密文本消息解密
		$inBodyResource = AesGcm::decrypt($inBodyObj->resource->ciphertext, $this->mchKey, $inBodyObj->resource->nonce, $inBodyObj->resource->associated_data);
		// 把解密后的文本转换为PHP Array数组
		$inBodyResourceArray = (array)json_decode($inBodyResource, true);

		// 解密后的结果
		return $inBodyResourceArray;
	}

	/**
	 * 获取支付工厂类
	 */
	private function getInstance()
	{
		// 商户证书文件
		$merchantPrivateKeyFilePath = file_get_contents($this->clientKey);

		// 从本地文件中加载商户API私钥，商户API私钥会用来生成请求的签名
		$merchantPrivateKeyInstance = Rsa::from($merchantPrivateKeyFilePath, Rsa::KEY_TYPE_PRIVATE);

		// 从本地文件中加载微信证书文件 或 微信公钥文件
		$platformCertificateFilePath = file_get_contents($this->wechatKey);
		$platformPublicKeyInstance = Rsa::from($platformCertificateFilePath, Rsa::KEY_TYPE_PUBLIC);

		// 公钥模式
		if ($this->publicKeyId) {
			$platformPublicKeyId = $this->publicKeyId;
			$certs = [$platformPublicKeyId => $platformPublicKeyInstance];
		}
		// 证书模式
		else {
			$platformCertificateSerial = PemUtil::parseCertificateSerialNo($platformCertificateFilePath);
			$certs = [$platformCertificateSerial => $platformPublicKeyInstance];
		}

		$instance = Builder::factory([
			'mchid'      => $this->mchId,
			'serial'     => $this->serialNo, // 商户API证书序列号
			'privateKey' => $merchantPrivateKeyInstance,
			'certs'      => $certs
		]);

		return $instance;
	}

	/**
	 * 获取适用的接口接方法
	 * 因为有类继承，考虑全部终端
	 */
	public function getMethod($isrefund = false, $istransfer = false, $isdetail = false)
	{
		if ($isrefund) {
			return 'v3/refund/domestic/refunds';
		}
		if ($istransfer) {
			//return 'v3/transfer/batches'; // 旧
			return 'v3/fund-app/mch-transfer/transfer-bills';
		}
		if ($isdetail) { // 商家转账接口
			//return 'v3/transfer/batches/out-batch-no/' . $this->payTradeNo . '/details/out-detail-no/' . $this->payTradeNo; // 旧
			return 'v3/fund-app/mch-transfer/transfer-bills/out-bill-no/' . $this->payTradeNo;
		}

		if ($this->terminal == 'APP') {
			return 'v3/pay/transactions/app';
		}

		// H5
		if ($this->terminal == 'WAP' && !Basewind::isWeixin()) {
			return 'v3/pay/transactions/h5';
		}

		// 公众号&小程序
		if (Basewind::isWeixin()) {
			return 'v3/pay/transactions/jsapi';
		}

		// PC扫码
		return 'v3/pay/transactions/native';
	}

	/**
	 * 获取接口拓展参数
	 * 因为有类继承，考虑全部终端
	 */
	public function getExtraParams()
	{
		// APP
		if ($this->terminal == 'APP') {
			return [];
		}

		// H5
		if ($this->terminal == 'WAP' && !Basewind::isWeixin()) {
			$scene_info = ['scene_info' => ['payer_client_ip' => Yii::$app->request->userIP, 'h5_info' => ['type' => 'wap']]];
			return $scene_info;
		}

		// 公众号&&小程序
		if (Basewind::isWeixin()) {
			$payer = ['payer' => ['openid' => $this->getOpenId($this->code)]];
			return $payer;
		}

		return [];
	}

	/**
	 * 获取OPENID
	 */
	public function getOpenId($code = '')
	{
		if (!$code) {
			return false;
		}

		if (!($openid = $this->createOauthUrlForOpenid($code))) {
			return false;
		}

		return $openid;
	}

	/**
	 * 获取CODE Url
	 */
	public function createOauthUrlForCode()
	{
		$urlObj["appid"] =  $this->appId;
		$urlObj["redirect_uri"] = $this->returnUrl;
		$urlObj["response_type"] = "code";
		$urlObj["scope"] = "snsapi_base";
		$urlObj["state"] = "STATE#wechat_redirect";
		$bizString = $this->formatBizQueryParaMap($urlObj, false);
		return "https://open.weixin.qq.com/connect/oauth2/authorize?" . $bizString;
	}

	/**
	 * 获取公众号的OPENID
	 */
	public function createOauthUrlForOpenid($code)
	{
		$urlObj["appid"] = $this->appId;
		$urlObj["secret"] = $this->appSecret;
		$urlObj["code"] = $code;
		$urlObj["grant_type"] = "authorization_code";
		$bizString = $this->formatBizQueryParaMap($urlObj, false);
		$url = "https://api.weixin.qq.com/sns/oauth2/access_token?" . $bizString;

		$result = Basewind::curl($url);
		if (!$result) {
			return false;
		}
		$result = json_decode($result);
		if ($result->errcode || !$result->openid) {
			$this->errors = $result->errmsg;
			return false;
		}
		return $result->openid;
	}

	/**
	 * 公众号支付参数（小程序与此一致）
	 */
	public function getParameters($prepay_id)
	{
		$params["appId"] = $this->appId;
		$params["timeStamp"] = (string)Formatter::timestamp();
		$params["nonceStr"] = Formatter::nonce();
		$params["package"] = "prepay_id=$prepay_id";
		$params["paySign"] = $this->getSign($params);
		$params["signType"] = "RSA";
		return $params;
	}

	/**
	 * 获取微信加密后的字符串
	 * @link：https://pay.weixin.qq.com/wiki/doc/apiv3/apis/chapter4_3_1.shtml
	 */
	private function getEncrypt($str)
	{
		// 平台证书路径
		$public_key = file_get_contents($this->wechatKey);
		$encrypted = '';
		if (openssl_public_encrypt($str, $encrypted, $public_key, OPENSSL_PKCS1_OAEP_PADDING)) {
			return base64_encode($encrypted); //base64编码 
		}
		return false;
	}

	/**
	 * 获取签名
	 */
	public function getSign($params)
	{
		// 商户私钥
		$merchantPrivateKeyFilePath = file_get_contents($this->clientKey);
		$merchantPrivateKeyInstance = Rsa::from($merchantPrivateKeyFilePath);

		return Rsa::sign(Formatter::joinedByLineFeed(...array_values($params)), $merchantPrivateKeyInstance);
	}

	/**
	 * 	作用：格式化参数，签名过程需要使用
	 */
	public function formatBizQueryParaMap($paraMap, $urlencode)
	{
		$buff = "";
		ksort($paraMap);
		foreach ($paraMap as $k => $v) {
			if ($urlencode) {
				$v = urlencode($v);
			}
			//$buff .= strtolower($k) . "=" . $v . "&";
			$buff .= $k . "=" . $v . "&";
		}
		$reqPar = '';
		if (strlen($buff) > 0) {
			$reqPar = substr($buff, 0, strlen($buff) - 1);
		}
		return $reqPar;
	}
}
