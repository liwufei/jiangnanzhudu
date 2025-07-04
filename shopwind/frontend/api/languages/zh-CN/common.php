<?php
return array(
	'request_ok'			=> '请求成功！',
	'handle_fail'			=> '操作失败！',
	'handle_exception'		=> '业务处理异常',
	'handle_invalid'        => '非法操作',
	'appid_empty' 			=> 'appid不能为空',
	'appid_invalid' 		=> 'appid非法',
	'sign_empty'  			=> '签名（sign）值不能为空',
	'sign_invalid'			=> '签名（sign）不正确',
	'signtype_invalid' 		=> '目前只支持MD5签名',
	'timestamp_empty'   	=> '时间戳（timestamp）不能为空',
	'timestamp_invalid'		=> '请求已失效（timestamp已过期）',
	'user_invalid'			=> '请先登录',
	'login_please' 			=> '请先登录',
	'token_invalid'			=> 'TOKEN无效',
	'token_expire'			=> 'TOKEN已过期',
	'no_permission'			=> '没有权限',

	'item_invalid'			=> '数据项不能为空或非法',
	'no_such_user'			=> '用户不存在',
	'user_logoff'			=> '该用户已被注销',
	'username_existed'		=> '该用户名已存在',
	'phone_mob_required' 	=> '手机号码不能为空',
	'phone_mob_invalid' 	=> '请输入正确的手机号',
	'phone_mob_existed'		=> '该手机号已存在',
	'email_required' 		=> '邮箱不能为空',
	'email_invalid' 		=> '请输入正确的邮箱',
	'email_existed'			=> '该邮箱已存在',
	'no_such_item'			=> '查询的数据不存在',
	'no_data' 				=> '没有数据',
	'drop_fail'				=> '删除失败',
	'no_plugin' 			=> '插件未配置',
	'express'				=> '快递发货',
	'locality'				=> '同城配送',

	'stock'     			=> '库存',
	'defray'				=> '支付',
	'transfer'				=> '转账',
	'recharge'				=> '充值',

	// 支付类型
	'SHIELD'			=> '担保交易',
	'INSTANT'			=> '即时到账',
	'COD'				=> '货到付款',

	// 交易类型
	'PAY'				=> '在线支付',
	'TRANSFER'			=> '转账',
	'SERVICE'			=> '服务费',
	'WITHDRAW'			=> '提现',
	'RECHARGE'			=> '充值',
	'RECHARGECARD'		=> '充值卡',

	// 针对交易的状态
	'TRADE_PENDING'				=> '待付款',
	'TRADE_ACCEPTED'    		=> '待发货',
	'TRADE_SHIPPED'				=> '待收货',
	'TRADE_USING'				=> '待使用',
	'TRADE_SUCCESS'				=> '交易完成',
	'TRADE_CLOSED'				=> '交易关闭',
	'TRADE_VERIFY' 				=> '待审核',


	// 针对退款的状态
	'REFUND_SUCCESS'				=> '退款成功',
	'REFUND_CLOSED'					=> '退款关闭',
	'REFUND_WAIT_SELLER_AGREE'		=> '已申请退款，等待商家审核',
	'REFUND_SELLER_REFUSE_BUYER'	=> '商家拒绝退款，等待买家修改中',
	'REFUND_WAIT_SELLER_CONFIRM'	=> '退款申请等待商家确认中',

	// 针对订单状态
	'order_pending'     => '待付款',
	'order_teaming'		=> '待成团',
	'order_accepted'    => '待发货',
	'order_delivering'	=> '待配送',
	'order_canceled'    => '交易关闭',
	'order_shipped'     => '待收货',
	'order_using'		=> '待使用',
	'order_finished'    => '交易完成',
	'order_success'		=> '交易完成',
	'order_closed'		=> '交易关闭',
	'order_submited'	=> '已下单',
	//'order_ispayed'		=> '已付款',
	'order_received'	=> '已收货',

	'has_apply_refund'			=> '已申请退款',
	'party_apply_refund'		=> '对方已申请退款',
	'trade_refund_return'		=> '交易退款',
	'trade_refund_pay'			=> '交易付款',
	'chargeback'				=> '交易服务费',
	'drawalfee'					=> '提现手续费',
	'recharge_give'             => '充值返现',

	'deposit'				=> '余额支付',
	'alipay'				=> '支付宝',
	'tenpay' 				=> '财付通',
	'tenpay_wap' 			=> '手机财付通',
	'unionpay'  			=> '中国银联',
	'wxpay'  				=> '微信支付',
	'wxnativepay'  			=> '微信扫码支付',
	'wxh5pay' 				=> '微信H5支付',
	'wxapppay'             	=> '微信APP支付',
	'cod'					=> '货到付款',

	'sms_buy' 				=> '您店铺下了一个新订单，订单号为[%s]，请联系买家及时付款',
	'sms_send' 				=> '您的订单[%s]，商家[%s]已经发货，请及时查收！',
	'sms_check' 			=> '您的订单[%s]，买家[%s]已经确认！',
	'sms_pay' 				=> '您的订单[%s]，买家[%s]已经付款！',
	'msg_send_failure'		=> '短信发送失败',
	'send_msg_successed'	=> '短信发送成功',
	'refund_success'		=> '订单产生部分退款%s元',

	'phone_code_check_failed'   	=> '手机验证码错误或已失效',
	'phone_code_check_timeout' 		=> '短信验证码已经过期',
	'send_limit_frequency_minute' => '同手机号每分钟最多发送%s条短信',
	'send_limit_frequency_hour' => '同手机号每小时最多发送%s条短信',
	'send_limit_frequency_day' => '同手机号每天最多发送%s条短信',

	'buying_has_integral_logtext'  => '购物订单获积分，订单号[%s]',
	'selling_has_integral_logtext' => '买家使用积分抵扣货款，订单号[%s]',
	'return_integral_for_cancel_order' => '订单取消，退回冻结的积分',

	'app_disavailable'  => '该插件平台已禁用',
	'app_hasexpired'    => '该插件已失效',
	'app_hasnotbuy'     => '对不起，您需要先购买插件才可使用',
	'app_disabled'      => '该插件商家未启用',
);
