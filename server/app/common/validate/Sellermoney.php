<?php

namespace app\common\validate;

use think\Validate;

class Sellermoney extends Validate
{
    protected $rule = [
        'pdc_amount' => 'require|float|min:0.01'
    ];
    protected $message = [
        'pdc_amount.require' => '请填写取出金额|取出金额必须是数字|0.01',
        'pdc_amount.float' => '请填写取出金额|取出金额必须是数字|0.01',
        'pdc_amount.min' => '请填写取出金额|取出金额必须是数字|0.01'
    ];
    protected $scene = [
        'withdraw_add' => ['pdc_amount'],
    ];
}
