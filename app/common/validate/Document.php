<?php

namespace app\common\validate;

use think\Validate;

/**
 * 系统文章
 */
class Document extends Validate
{
    protected $rule = [
        'document_title' => 'require|length:0,50',
        'document_content' => 'require'
    ];
    protected $message = [
        'document_title.require' => '文章标题不能为空',
        'document_title.length' => '文章标题长度不能大于50',
        'document_content.require' => '文章内容不能为空'
    ];
    protected $scene = [
        'edit' => ['document_title', 'document_content'],
    ];
}
