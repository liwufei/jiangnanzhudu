<?php

namespace app\common\validate;

use think\Validate;

/**
 * 客服咨询类型
 */
class Mallconsulttype extends Validate
{
    protected $rule = [
        'mallconsulttype_name' => 'require|length:0,50',
        'mallconsulttype_sort' => 'number|between:0,255',
    ];
    protected $message = [
        'mallconsulttype_name.require' => '请填写咨询类型名称',
        'mallconsulttype_name.length' => '咨询类型名称不能大于50个字符',
        'mallconsulttype_sort.number' => '排序必须为0-255间数字',
        'mallconsulttype_sort.between' => '排序必须为0-255间数字',
    ];
    protected $scene = [
        'add' => ['mallconsulttype_name', 'mallconsulttype_sort'],
        'edit' => ['mallconsulttype_name', 'mallconsulttype_sort'],
    ];
}
