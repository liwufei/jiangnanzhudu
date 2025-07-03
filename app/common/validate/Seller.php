<?php

namespace app\common\validate;

use think\Validate;

/**
 * 卖家
 */
class Seller extends Validate
{
    protected $rule = [
        'seller_name' => 'require|alphaDash|length:3,20',
        'password' => 'require|length:6,20',
    ];
    protected $message = [
        'seller_name.require' => '卖家用户名必填',
        'seller_name.alphaDash' => '卖家用户名只能为字母、数字、下划线、破折号',
        'seller_name.length' => '用户名长度在3到20位',
        'password.require' => '密码为必填',
        'password.length' => '密码长度必须为6-20之间',
    ];
    protected $scene = [
        'login' => ['seller_name', 'password'],
    ];
}
