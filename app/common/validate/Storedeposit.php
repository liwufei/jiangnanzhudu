<?php

namespace app\common\validate;

use think\Validate;

/**
 * 店铺保证金
 */
class Storedeposit extends Validate
{
    protected $rule = [
        'store_id' => 'require|number',
        'amount' => 'require|float|between:0.01,1000000',
        'operatetype' => 'require',
    ];
    protected $message = [
        'store_id.require' => '请输入店主用户名',
        'store_id.number' => '店主信息错误',
        'amount.require' => '请添加金额',
        'amount.float' => '金额格式不正确',
        'amount.between' => '金额为0.01至100万之间',
        'operatetype.require' => '请输入增减类型',
    ];
    protected $scene = [
        'adjust' => ['store_id', 'amount', 'operatetype'],
    ];
}
