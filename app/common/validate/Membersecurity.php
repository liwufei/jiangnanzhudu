<?php

namespace app\common\validate;

use think\Validate;

class Membersecurity extends Validate
{
    protected $rule = [
        'password' => 'require',
        'confirm_password' => 'require',
        'member_mobile' => 'require|mobile',
        'verify_code' => 'require|length:6,6',
    ];
    protected $message = [
        'password.require' => '请正确输入密码',
        'confirm_password.require' => '请正确输入确认密码',
        'member_mobile.require' => '请填写手机号',
        'member_mobile.mobile' => '请正确填写手机号',
        'verify_code.require' => '请正确填写手机验证码',
        'verify_code.length' => '验证码的长度为6位',
    ];
    protected $scene = [
        'modify_pwd' => ['password', 'confirm_password'],
        'modify_paypwd' => ['password', 'confirm_password'],
        'modify_mobile' => ['member_mobile', 'verify_code'],
    ];
}
