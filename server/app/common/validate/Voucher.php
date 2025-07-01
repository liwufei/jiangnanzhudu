<?php

namespace app\common\validate;

use think\Validate;

/**
 * 代金券
 */
class Voucher extends Validate
{
    protected $rule = [
        'voucherprice' => 'require|number|length:0,11',
        'voucherprice_describe' => 'require|length:0,255',
        'voucherprice_defaultpoints' => 'require|number|length:0,11',
    ];
    protected $message = [
        'voucherprice.require' => '代金券面额应为大于0的整数',
        'voucherprice.number' => '代金券面额应为大于0的整数',
        'voucherprice.length' => '代金券面额长度应该小于11位',
        'voucherprice_describe.require' => '描述不能为空',
        'voucherprice_describe.length' => '描述长度应该小于255',
        'voucherprice_defaultpoints.require' => '默认兑换积分数应为大于0的整数',
        'voucherprice_defaultpoints.number' => '默认兑换积分数应为大于0的整数',
        'voucherprice_defaultpoints.length' => '默认兑换积分数长度应该小于11位',
    ];
    protected $scene = [
        'priceadd' => ['voucherprice', 'voucherprice_describe', 'voucherprice_defaultpoints'],
        'priceedit' => ['voucherprice', 'voucherprice_describe', 'voucherprice_defaultpoints'],
    ];
}
