<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace common\plugins\express\kuaidi100;

use yii;

use common\library\Basewind;
use common\library\Language;
use common\plugins\BaseExpress;

/**
 * @Id kuaidi100.plugin.php 2018.9.5 $
 * @author mosir
 */

class Kuaidi100 extends BaseExpress
{
	/**
	 * 网关地址
	 * @var string $gateway
	 */
	protected $gateway = 'http://poll.kuaidi100.com/poll/query.do';

	/**
	 * 插件实例
	 * @var string $code
	 */
	protected $code = 'kuaidi100';

	/**
	 * SDK实例
	 * @var object $client
	 */
	private $client = null;


	/**
	 * 对数据进行验证
	 */
	public function valid($post = null, $order = null)
	{
		if (empty($order->code) || empty($order->number)) {
			$this->errors = Language::get('invoice_or_company_empty');
			return false;
		}
		return true;
	}

	/**
	 * 发送请求获取数据
	 */
	public function submit($post = null, $order = null, $valid = true)
	{
		if ($valid === true && !($this->valid($post, $order))) {
			return false;
		}

		// 企业版优先
		if ($this->config['customer']) {
			$result = $this->queryPoll($post, $order);
		}

		// 免费版（JSON）
		// if (!$result['done']) {
		// 	$result = $this->queryApi($post, $order);
		// }

		// 免费版（URL）
		// if (!$result['done']) {
		// 	$result = $this->queryIframe($post, $order);
		// }

		if ($result) {
			$result = [
				'code' => $result['returnCode'],
				'message' => $result['message'],
				'result' => $result['result'],
				'label' => $result['label'],
				'company' 	=> $order->company,
				'number' 	=> $order->number,
				'details' 	=> $result['data']
			];
		}
		\common\library\Page::writeLog('kkkk', $result);
		return $result;
	}

	/**
	 * 企业版 返回JSON 稳定
	 * 顺丰、EMS，必须传phone
	 */
	private function queryPoll($post = null, $order = null)
	{
		$params['customer'] = $this->config['customer'];
		$params['param'] 	= json_encode(['com' => $order->code, 'num' => $order->number, 'phone' => $order->phone_mob]);
		$params['sign'] 	= strtoupper(md5($params['param'] . $this->config['key'] . $this->config['customer']));

		$result = Basewind::curl($this->gateway, 'post', $params);
		$result = json_decode(str_replace("\"", '"', $result), true);

		// 快递单当前签收状态，包括0在途中、1已揽收、2疑难、3已签收、4退签、5派件、6退回、7转投、8清关、14拒签等10个状态
		if (isset($result['state']) && in_array($result['state'], array(0, 1, 2, 3, 4, 5, 6, 7, 8, 14))) {
			$result['label'] = $this->getState($result['state']);
			//$result['status'] = 1; // 兼容免费版接口状态值（企业版：返回200，免费版返回1）
			//$result['done'] = true;
		}
		return $result;
	}

	/* 免费版（暂时保留）返回JSON 但是该网关不支持EMS、顺丰和申通 且不稳定 */
	private function queryApi($post = null, $order = null)
	{
		$this->gateway = 'http://api.kuaidi100.com/api';

		$params['id'] 	= $this->config['key'];
		$params['com'] 	= $order->code;
		$params['nu']	= $order->number;
		$params['show'] = 2;
		$params['muti'] = 1;
		$params['order'] = 'desc';

		$result = Basewind::curl($this->gateway . '?' . http_build_query($params));
		$result = json_decode($result, true);

		// status 查询的结果状态。0：运单暂无结果，1：查询成功，2：接口出现异常，408：验证码出错（仅适用于APICode url，可忽略)   
		if ($result && ($result['status'] == 0 || $result['status'] == 1)) {
			$result['done'] = true;
		}
		return $result;
	}

	/* 免费版（暂时先保留）返回固定格式的HTML（一个URL链接），并带广告 较稳定，体验不友好 */
	private function queryIframe($post = null, $order = null)
	{
		$this->gateway = 'http://www.kuaidi100.com/applyurl';

		$params['key']  = $this->config['key'];
		$params['com'] 	= $order->code;
		$params['nu']	= $order->number;

		$result = Basewind::curl($this->gateway . '?' . http_build_query($params));
		$array = json_decode($result, true);
		if (isset($array['status'])) {
			$result = $array;
		}

		return is_string($result) ? ['url' => $result] : $result;
	}

	/**
	 * 针对企业版，API接口
	 */
	private function getState($state)
	{
		if ($state == 0) return '运输中';
		if ($state == 1) return '已揽收';
		if ($state == 2) return '派件异常';
		if ($state == 3) return '已签收';
		if ($state == 4) return '已退签';
		if ($state == 5) return '正在派件';
		if ($state == 6) return '已退回';
		if ($state == 7) return '已转投';
		if ($state == 8) return '清关中';
		if ($state == 14) return '已拒签';
		return '';
	}

	/**
	 * 获取SDK实例
	 */
	public function getClient()
	{
		if ($this->client === null) {
			$this->client = new SDK($this->config);
		}
		return $this->client;
	}
}
