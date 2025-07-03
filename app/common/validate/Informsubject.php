<?php

namespace app\common\validate;

use think\Validate;

/**
 * 举报主题
 */
class Informsubject extends Validate
{
    protected $rule = [
        'informsubject_type_name' => 'require|length:0,50',
        'informsubject_content' => 'require|length:0,50',
        'informsubject_type_id' => 'require',
    ];
    protected $message = [
        'informsubject_type_name.require' => '举报主题不能为空且不能大于50个字符',
        'informsubject_type_name.length' => '举报主题不能为空且不能大于50个字符',
        'informsubject_content.require' => '举报内容不能为空且不能大于50个字符',
        'informsubject_content.length' => '举报内容不能为空且不能大于50个字符',
        'informsubject_type_id.require' => '举报ID不能为空',
    ];
    protected $scene = [
        'add' => ['informsubject_type_name', 'informsubject_content', 'informsubject_type_id'],
    ];
}
