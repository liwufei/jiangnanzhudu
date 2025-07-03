<?php

namespace app\common\validate;

use think\Validate;

/**
 * 短消息
 */
class Message extends Validate
{
    protected $rule = [
        'to_member_name' => 'require',
        'msg_content' => 'require|length:0,255',
    ];
    protected $message = [
        'to_member_name.require' => '收件人不能为空',
        'msg_content.require' => '内容不能为空',
        'msg_content.length' => '内容长度不能大于50',
    ];
    protected $scene = [
        'savemsg' => ['to_member_name', 'msg_content'],
    ];
}
