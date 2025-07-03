<?php

namespace app\common\validate;

use think\Validate;

/**
 * 会员账户
 */
class Memberbank extends Validate
{
    protected $rule = [
        'memberbank_type' => 'require',
        'memberbank_truename' => 'require|length:2,5',
        'memberbank_no' => 'require|length:5,30',
    ];
    protected $message = [
        'memberbank_type.require' => '账户类型不能为空',
        'memberbank_truename.require' => '开户名不能为空',
        'memberbank_truename.length' => '开户名长度应为2至5之间',
        'memberbank_no.require' => '账号不能为空',
        'memberbank_no.length' => '账号长度不正确',
    ];
    protected $scene = [
        'add' => ['memberbank_type', 'memberbank_truename', 'memberbank_no'],
        'edit' => ['memberbank_type', 'memberbank_truename', 'memberbank_no'],
    ];
}
