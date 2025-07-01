<?php

namespace app\common\validate;

use think\Validate;

/**
 * 规格
 */
class Spec extends Validate
{
    protected $rule = [
        'sp_name' => 'require|length:0,50',
        'sp_sort' => 'require|number|between:0,255',
        'gc_id' => 'require|number',
        'gc_name' => 'length:0,50',
    ];
    protected $message = [
        'sp_name.require' => '规格名称为必填',
        'sp_name.length' => '规格名称长度不能大于50',
        'sp_sort.require' => '规格排序为必填',
        'sp_sort.number' => '排序必须为0-255间数字',
        'sp_sort.between' => '排序必须为0-255间数字',
        'gc_id.require' => '分类为必填',
        'gc_id.number' => '分类ID必须为数字',
        'gc_name.length' => '所属商品分类名称长度不能大于50',
    ];
    protected $scene = [
        'spec_add' => ['sp_name', 'sp_sort', 'gc_id', 'gc_name'],
        'spec_edit' => ['sp_name', 'sp_sort', 'gc_id', 'gc_name'],
    ];
}
