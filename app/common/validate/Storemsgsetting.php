<?php

namespace app\common\validate;

use think\Validate;

/**
 * 店铺消息接收设置
 */
class Storemsgsetting extends Validate
{
    protected $rule = [
        'storems_short_number' => 'mobile',
        'storems_mail_number' => 'email'
    ];
    protected $message = [
        'storems_short_number.mobile' => '请填写正确的手机号码',
        'storems_mail_number.email' => '请填写正确的邮箱'
    ];
    protected $scene = [
        'save' => ['storems_short_number', 'storems_mail_number'],
    ];
}
