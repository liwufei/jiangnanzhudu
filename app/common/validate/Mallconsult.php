<?php

namespace app\common\validate;

use think\Validate;

/**
 * 客服咨询
 */
class Mallconsult extends Validate
{
    protected $rule = [
        'mallconsulttype_id' => 'require|number',
        'mallconsult_content' => 'require|length:0,255'
    ];
    protected $message = [
        'mallconsulttype_id.require' => '请选择咨询类型',
        'mallconsulttype_id.number' => '请选择咨询类型',
        'mallconsult_content.require' => '请填写咨询内容',
        'mallconsult_content.length' => '咨询内容不能大于255个字符',
    ];
    protected $scene = [
        'add' => ['mallconsulttype_id', 'mallconsult_content'],
        'edit' => ['mallconsult_content'],
    ];
}
