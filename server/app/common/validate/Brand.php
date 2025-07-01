<?php

namespace app\common\validate;

use think\Validate;

/**
 * 品牌
 */
class Brand extends Validate
{
    protected $rule = [
        'brand_name' => 'require|length:0,50',
        'brand_initial' => 'require',
        'brand_sort' => 'number|between:0,255'
    ];
    protected $message = [
        'brand_name.require' => '品牌名称不能为空',
        'brand_name.length' => '品牌名称长度不能大于50',
        'brand_initial.require' => '请填写首字母',
        'brand_sort.number' => '排序仅可以为数字',
        'brand_sort.between' => '排序必须为0-255间数字',
    ];
    protected $scene = [
        'brand_add' => ['brand_name', 'brand_initial', 'brand_sort'],
        'brand_edit' => ['brand_name', 'brand_initial', 'brand_sort'],
    ];
}
