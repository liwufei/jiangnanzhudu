<?php

namespace app\common\validate;

use think\Validate;

class Promotionbooth extends Validate
{
    protected $rule = [
        'promotion_booth_price' => 'require|number|egt:0',
        'promotion_booth_goods_sum' => 'require|number'
    ];
    protected $message = [
        'promotion_booth_price.require' => '不能为空，且不小于0的整数',
        'promotion_booth_price.number' => '不能为空，且不小于0的整数',
        'promotion_booth_price.egt' => '不能为空，且不小于0的整数',
        'promotion_booth_goods_sum.require' => '不能为空，且不小于1的整数',
        'promotion_booth_goods_sum.number' => '不能为空，且不小于1的整数'
    ];
    protected $scene = [
        'booth_setting' => ['promotion_booth_price', 'promotion_booth_goods_sum'],
    ];
}
