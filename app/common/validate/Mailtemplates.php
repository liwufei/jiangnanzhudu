<?php

namespace app\common\validate;

use think\Validate;

/**
 * 邮件模板
 */
class Mailtemplates extends Validate
{
    protected $rule = [
        'mailmt_code' => 'require|length:0,30',
        'mailmt_title' => 'require|length:0,50',
        'mailmt_content' => 'require',
    ];
    protected $message = [
        'mailmt_code.require' => '编号不能为空',
        'mailmt_code.length' => '编号不能大于50个字符',
        'mailmt_title.require' => '标题不能为空',
        'mailmt_title.length' => '标题不能大于50个字符',
        'mailmt_content.require' => '正文不能为空',
    ];
    protected $scene = [
        'email_tpl_edit' => ['mailmt_code', 'mailmt_title', 'mailmt_content'],
    ];
}
