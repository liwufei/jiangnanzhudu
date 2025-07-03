<?php

namespace app\common\validate;

use think\Validate;

/**
 * 投诉主题
 */
class Complainsubject extends Validate
{
    protected $rule = [
        'complainsubject_content' => 'require|length:0,50',
        'complainsubject_desc' => 'require|length:0,100',
    ];
    protected $message = [
        'complainsubject_content.require' => '投诉主题不能为空',
        'complainsubject_content.length' => '投诉主题必须小于50个字符',
        'complainsubject_desc.require' => '投诉主题描述不能为空',
        'complainsubject_desc.length' => '投诉主题描述必须小于100个字符',
    ];
    protected $scene = [
        'add' => ['complainsubject_content', 'complainsubject_desc'],
    ];
}
