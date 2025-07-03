<?php

namespace app\common\validate;

use think\Validate;

/**
 * 消息
 */
class InstantMessage extends Validate
{
    protected $rule = [
        'instant_message_from_id' => 'require',
        'instant_message_from_type' => 'require',
        'instant_message_to_id' => 'require',
        'instant_message_to_type' => 'require',
        'instant_message_type' => 'require',
        'instant_message' => 'require',
    ];
    protected $message = [
        'instant_message_from_id.require' => '发送ID不能为空',
        'instant_message_from_type.require' => '发送类型不能为空',
        'instant_message_to_id.require' => '接收ID不能为空',
        'instant_message_to_type.require' => '接收类型不能为空',
        'instant_message_type.require' => '消息类型不能为空',
        'instant_message.require' => '消息内容不能为空',
    ];
    protected $scene = [
        'instant_message_save' => ['instant_message_from_id', 'instant_message_from_type', 'instant_message_to_id', 'instant_message_to_type', 'instant_message_type', 'instant_message'],
    ];
}
