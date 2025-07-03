<?php

namespace app\common\validate;

use think\Validate;

/**
 * 商品分类
 */
class Goodsclass extends Validate
{
    protected $rule = [
        'gc_name' => 'length:0,50',
        'type_name' => 'length:0,50',
        'gc_title' => 'length:0,50',
        'gc_sort' => 'number|between:0,255',
        'gc_keywords' => 'length:0,255',
        'gc_description' => 'length:0,255',
    ];
    protected $message = [
        'gc_name.length' => '分类标题长度不能大于50',
        'type_name.length' => '类型长度不能大于50',
        'gc_title.length' => '标题长度不能大于50',
        'gc_sort.number' => '排序应该在0至255之间',
        'gc_sort.between' => '排序应该在0至255之间',
        'gc_keywords.length' => '商品分类关键词长度不能大于255',
        'gc_description.length' => '商品分类描述长度不能大于255',
    ];
    protected $scene = [
        'add' => ['gc_name', 'type_name', 'gc_title', 'gc_sort', 'gc_keywords', 'gc_description'],
        'edit' => ['gc_name', 'type_name', 'gc_title', 'gc_sort', 'gc_keywords', 'gc_description'],
    ];
}
