<?php

namespace app\common\validate;

use think\Validate;

/**
 * 相册分类
 */
class Albumclass extends Validate
{
    protected $rule = [
        'aclass_name' => 'require|length:0,50',
        'aclass_des' => 'length:0,255',
        'aclass_sort' => 'between:0,255',
    ];
    protected $message = [
        'aclass_name.require' => '相册名称必填',
        'aclass_name.length' => '相册名称长度不能大于50',
        'aclass_des.length' => '相册描述内容长度不能大于255',
        'aclass_sort.between' => '排序必须为0-255间数字',
    ];
    protected $scene = [
        'add' => ['aclass_name', 'aclass_des', 'aclass_sort'],
        'edit' => ['aclass_name', 'aclass_des', 'aclass_sort']
    ];
}
