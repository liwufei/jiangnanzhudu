<?php

namespace app\common\validate;

use think\Validate;

/**
 * 门店
 */
class Chain extends Validate
{
    protected $rule = [
        'chain_name' => 'require|length:3,13|unique:chain',
        'chain_passwd' => 'require',
        'chain_truename' => 'require',
        'chain_idcard' => 'idCard',
        'chain_mobile' => 'mobile|unique:chain',
    ];
    protected $message = [
        'chain_name.require' => '账户为必填',
        'chain_name.length' => '账户长度在3到13位',
        'chain_name.unique' => '账户已存在',
        'chain_passwd.require' => '密码为必填',
        'chain_truename.require' => '真实姓名为必填',
        'chain_idcard.idCard' => '身份证号码错误',
        'chain_mobile.mobile' => '手机号码格式错误',
        'chain_mobile.unique' => '手机号码已存在',
    ];
    protected $scene = [
        'model_add' => ['chain_name', 'chain_passwd', 'chain_truename', 'chain_idcard', 'chain_mobile'],
        'model_edit' => ['chain_truename', 'chain_idcard', 'chain_mobile'],
    ];
}
