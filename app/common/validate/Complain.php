<?php

namespace app\common\validate;

use think\Validate;

/**
 * 投诉
 */
class Complain extends Validate
{
    protected $rule = [
        'final_handle_message' => 'require|length:0,255',
        'appeal_message' => 'require|length:0,255',
    ];
    protected $message = [
        'final_handle_message.require' => '处理意见不能为空',
        'final_handle_message.length' => '必须小于255个字符',
        'appeal_message.require' => '投诉内容不能为空且必须小于100个字符|投诉内容不能为空且必须小于100个字符',
        'appeal_message.length' => '投诉内容不能为空且必须小于100个字符|投诉内容不能为空且必须小于100个字符',
    ];
    protected $scene = [
        'complain_close' => ['final_handle_message'],
        'appeal_save' => ['appeal_message'],
    ];
}
