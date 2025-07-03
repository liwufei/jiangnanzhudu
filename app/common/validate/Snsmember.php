<?php

namespace app\common\validate;

use think\Validate;

class Snsmember extends Validate
{
    protected $rule = [
        'mtag_name' => 'require|length:0,20',
        'mtag_sort' => 'number|between:0,255',
        'mtag_desc' => 'length:0,50',
    ];
    protected $message = [
        'mtag_name.require' => '会员标签名称不能为空',
        'mtag_name.length' => '会员标签名称长度不能大于20',
        'mtag_sort.number' => '排序必须为0-255间数字',
        'mtag_sort.between' => '排序必须为0-255间数字',
        'mtag_desc.length' => '会员标签描述长度不能大于50',
    ];
    protected $scene = [
        'tag_add' => ['mtag_name', 'mtag_sort', 'mtag_desc'],
        'tag_edit' => ['mtag_name', 'mtag_sort', 'mtag_desc'],
    ];
}
