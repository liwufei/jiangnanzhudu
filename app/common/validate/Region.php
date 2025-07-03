<?php

namespace app\common\validate;

use think\Validate;

/**
 * 地区
 */
class Region extends Validate
{
    protected $rule = [
        'area_name' => 'require|length:0,50',
        'area_sort' => 'number|between:0,255',
        'area_region' => 'length:0,3',
        'area_initial' => 'length:0,1',
    ];
    protected $message = [
        'area_name.require' => '地区名称不能为空',
        'area_name.length' => '地区名称长度不能大于50',
        'area_sort.number' => '排序必须为0-255间数字',
        'area_sort.between' => '排序必须为0-255间数字',
        'area_region.length' => '大区名称必须小于三个字符',
        'area_initial.length' => '首字母只能有一个字符',
    ];
    protected $scene = [
        'add' => ['area_name', 'area_sort', 'area_region', 'area_initial'],
        'edit' => ['area_name', 'area_sort', 'area_region', 'area_initial'],
    ];
}
