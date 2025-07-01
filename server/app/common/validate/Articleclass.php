<?php

namespace app\common\validate;

use think\Validate;

/**
 * 文章分类
 */
class Articleclass extends Validate
{
    protected $rule = [
        'ac_name' => 'require|length:0,50',
        'ac_sort' => 'number|between:0,255'
    ];
    protected $message = [
        'ac_name.require' => '分类名称不能为空',
        'ac_name.length' => '分类名称长度不能大于50',
        'ac_sort.number' => '分类排序仅能为数字',
        'ac_sort.between' => '排序必须为0-255间数字',
    ];
    protected $scene = [
        'add' => ['ac_name', 'ac_sort'],
        'edit' => ['ac_name', 'ac_sort'],
    ];
}
