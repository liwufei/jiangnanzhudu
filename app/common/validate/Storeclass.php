<?php

namespace app\common\validate;

use think\Validate;

/**
 * 店铺分类
 */
class Storeclass extends Validate
{
    protected $rule = [
        'storeclass_name' => 'require|length:0,50',
        'storeclass_sort' => 'number|between:0,255',
        'storeclass_bail' => 'number|length:0,8',
    ];
    protected $message = [
        'storeclass_name.require' => '分类名称必填',
        'storeclass_name.length' => '分类名称长度不能大于50',
        'storeclass_sort.number' => '排序必须为0-255间数字',
        'storeclass_sort.between' => '排序必须为0-255间数字',
        'storeclass_bail.number' => '保证金必须为整数',
        'storeclass_bail.length' => '保证金必须小于9位数',
    ];
    protected $scene = [
        'add' => ['storeclass_name', 'storeclass_sort', 'storeclass_bail'],
        'edit' => ['storeclass_name', 'storeclass_sort', 'storeclass_bail']
    ];
}
