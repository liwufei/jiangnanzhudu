<?php

namespace app\common\validate;

use think\Validate;

/**
 * 文章
 */
class Article extends Validate
{
    protected $rule = [
        'article_sort' => 'number|between:0,255',
        'article_title' => 'require|length:0,50',
    ];
    protected $message = [
        'article_sort.number' => '排序只能为数字',
        'article_sort.between' => '排序必须为0-255间数字',
        'article_title.require' => '标题名称不能为空',
        'article_title.length' => '标题名称长度不能大于50',
    ];
    protected $scene = [
        'add' => ['article_sort', 'article_title'],
        'edit' => ['article_sort', 'article_title'],
    ];
}
