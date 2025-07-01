<?php

namespace app\common\validate;

use think\Validate;

/**
 * 发货地址
 */
class Daddress extends Validate
{
    protected $rule = [
        'seller_name' => 'require',
        'daddress_detail' => 'require',
        'daddress_telphone' => 'require',
    ];
    protected $message = [
        'seller_name.require' => '联系人必填',
        'daddress_detail.require' => '地址必填',
        'daddress_telphone.require' => '电话必填',
    ];
    protected $scene = [
        'add' => ['seller_name', 'daddress_detail', 'daddress_telphone'],
        'edit' => ['seller_name', 'daddress_detail', 'daddress_telphone'],
    ];
}
