<?php

namespace app\common\validate;

use think\Validate;

/**
 * 友情链接
 */
class Link extends Validate
{
    protected $rule = [
        'link_sort' => 'number|between:0,255',
        'link_title' => 'require|length:0,50',
    ];
    protected $message = [
        'link_sort.number' => '排序必须为0-255间数字',
        'link_sort.between' => '排序必须为0-255间数字',
        'link_title.require' => '链接名称不能为空',
        'link_title.length' => '链接名称不能为空且不能大于50个字符',
    ];
    protected $scene = [
        'add' => ['link_sort', 'link_title'],
        'edit' => ['link_sort', 'link_title'],
    ];
}
