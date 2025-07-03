<?php

namespace app\common\validate;

use think\Validate;

/**
 * 咨询类型
 */
class Consulttype extends Validate
{
    protected $rule = array(
        'consulttype_name' => 'require|length:0,10',
        'consulttype_sort' => 'number|between:0,255',
    );
    protected $message = array(
        'consulttype_name.require' => '请填写咨询类型名称',
        'consulttype_name.length' => '咨询类型名称长度不能大于10',
        'consulttype_sort.number' => '请正确填写咨询类型排序',
        'consulttype_sort.between' => '咨询类型排序必须为0-255间数字',
    );
    protected $scene = [
        'add' => ['consulttype_name', 'consulttype_sort'],
        'edit' => ['consulttype_name', 'consulttype_sort'],
    ];
}
