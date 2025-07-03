<?php

namespace app\common\validate;

use think\Validate;

/**
 * 咨询
 */
class Consult extends Validate
{
    protected $rule = [
        'consult_content' => 'require|length:0,255',
        'consult_reply' => 'require|length:0,255',
    ];
    protected $message = [
        'consult_content.require' => '咨询内容不能为空',
        'consult_content.length' => '咨询内容名称长度不能大于255',
        'consult_reply.require' => '内容不能为空',
        'consult_reply.length' => '内容长度不能超过255字符',
    ];
    protected $scene = [
        'add' => ['consult_content'],
        'edit' => ['consult_reply'],
    ];
}
