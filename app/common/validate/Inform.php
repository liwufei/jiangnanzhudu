<?php

namespace app\common\validate;

use think\Validate;

/**
 * 举报
 */
class Inform extends Validate
{
    protected $rule = [
        'informsubject_content' => 'require|length:0,50',
        'inform_handle_message' => 'require|length:0,100',
        'inform_content' => 'require|length:0,100',
    ];
    protected $message = [
        'informsubject_content.require' => '举报内容不能为空且不能大于50个字符',
        'informsubject_content.length' => '举报内容不能为空且不能大于50个字符',
        'inform_handle_message.require' => '处理信息不能为空且不能大于100个字符',
        'inform_handle_message.length' => '处理信息不能为空且不能大于100个字符',
        'inform_content.require' => '举报内容不能为空且不能大于100个字符',
        'inform_content.length' => '举报内容不能为空且不能大于100个字符',
    ];
    protected $scene = [
        'inform_handle' => ['inform_handle_message'],
        'add' => ['inform_content', 'informsubject_content'],
    ];
}
