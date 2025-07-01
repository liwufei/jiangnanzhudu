<?php

namespace app\common\validate;

use think\Validate;

/**
 * 举报类型
 */
class Informsubjecttype extends Validate
{
    protected $rule = [
        'informtype_name' => 'require|length:0,50',
        'informtype_desc' => 'require|length:0,100',
    ];
    protected $message = [
        'informtype_name.require' => '举报类型不能为空且不能大于50个字符',
        'informtype_name.length' => '举报类型不能为空且不能大于50个字符',
        'informtype_desc.require' => '举报类型描述不能为空且不能大于100个字符',
        'informtype_desc.length' => '举报类型描述不能为空且不能大于100个字符',
    ];
    protected $scene = [
        'add' => ['informtype_name', 'informtype_desc'],
        'edit' => ['informtype_name', 'informtype_desc'],
    ];
}
