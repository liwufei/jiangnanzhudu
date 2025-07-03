<?php

namespace app\common\validate;

use think\Validate;

/**
 * 店铺商品分类
 */
class Storegoodsclass extends Validate
{
    protected $rule = [
        'store_id' => 'require',
        'storegc_name' => 'require|length:0,50',
        'storegc_sort' => 'between:0,255',
    ];
    protected $message = [
        'store_id.require' => '店铺ID必填',
        'storegc_name.require' => '分类名称必填',
        'storegc_name.length' => '分类名称长度应该在1至50字符之间',
        'storegc_sort.between' => '排序应在0至255之间',
    ];
    protected $scene = [
        'save' => ['store_id', 'storegc_name', 'storegc_sort'],
    ];
}
