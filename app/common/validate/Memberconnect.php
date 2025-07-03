<?php

namespace app\common\validate;

use think\Validate;

/**
 * 重置密码
 */
class Memberconnect extends Validate
{
    protected $rule = [
        'new_password' => 'require|length:6,20',
        'confirm_password' => 'require|confirm:new_password',
    ];
    protected $message = [
        'new_password.require' => '新密码为必填|密码长度必须为6-20之间',
        'new_password.length' => '新密码为必填|密码长度必须为6-20之间',
        'confirm_password.require' => '新密码与确认密码不相同，请从重新输入',
        'confirm_password.confirm' => '新密码与确认密码不相同，请从重新输入',
    ];
    protected $scene = [
        'qqunbind' => ['new_password', 'confirm_password'],
        'sinaunbind' => ['new_password', 'confirm_password'],
        'weixinunbind' => ['new_password', 'confirm_password'],
    ];
}
