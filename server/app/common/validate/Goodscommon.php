<?php

namespace app\common\validate;

use think\Validate;

/**
 * 商品公共内容
 */
class  Goodscommon extends Validate
{
    protected $rule = [
        'goods_name' => 'require|length:3,50',
        'goods_price' => 'require',
        'goods_storage_alarm' => 'between:0,255'
    ];
    protected $message = [
        'goods_name.require' => '商品名称不能为空',
        'goods_name.length' => '商品名称长度应该在3至50字符之间',
        'goods_price.require' => '商品价格不能为空',
        'goods_storage_alarm.between' => '库存预警值不能超过255',
    ];
    protected $scene = [
        'add' => ['goods_name', 'goods_price', 'goods_storage_alarm'],
        'edit' => ['goods_name', 'goods_price', 'goods_storage_alarm'],
    ];
}
