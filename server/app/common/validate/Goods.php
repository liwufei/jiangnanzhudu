<?php

namespace app\common\validate;

use think\Validate;

/**
 * 商品
 */
class  Goods extends Validate
{
    protected $rule = [
        'goods_name' => 'require|max:200',
        'goods_price' => 'require',
    ];
    protected $message = [
        'goods_name.require' => '商品名称不能为空',
        'goods_name.max' => '商品名称不能超过200个字符',
        'goods_price.require' => '商品价格不能为空',
    ];
    protected $scene = [
        'edit_save_goods' => ['goods_name', 'goods_price'],
    ];
}
