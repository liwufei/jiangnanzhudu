<?php

namespace app\common\validate;

use think\Validate;

/**
 * 反馈
 */
class Feedback extends Validate
{
    protected $rule = [
        'fb_content' => 'require|length:0,255',
    ];
    protected $message = [
        'fb_content.require' => '反馈内容不能为空',
        'fb_content.length' => '反馈内容长度不能大于255',
    ];
    protected $scene = [
        'add' => ['fb_content'],
        'edit' => ['fb_content'],
    ];
}
