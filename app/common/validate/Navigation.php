<?php

namespace app\common\validate;

use think\Validate;

/**
 * 页面导航
 */
class Navigation extends Validate
{
    protected $rule = [
        'nav_title' => 'require|length:0,50',
        'nav_sort' => 'number|between:0,255',
        'nav_url' => 'length:0,255',
    ];
    protected $message = [
        'nav_title.require' => '标题不能为空',
        'nav_title.length' => '标题名称长度不能大于50',
        'nav_sort.number' => '排序只能为数字',
        'nav_sort.between' => '排序必须为0-255间数字',
        'nav_url.length' => '链接长度不能大于255',
    ];
    protected $scene = [
        'add' => ['nav_title', 'nav_sort', 'nav_url',],
        'edit' => ['nav_title', 'nav_sort', 'nav_url',],
    ];
}
