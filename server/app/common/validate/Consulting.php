<?php

namespace app\common\validate;

use think\Validate;

/**
 * 咨询
 */
class Consulting extends Validate
{
    protected $rule = array(
        'consulttype_name' => 'require',
        'consulttype_sort' => 'require|number',
    );
    protected $message = array(
        'consulttype_name.require' => '请填写咨询类型名称',
        'consulttype_sort.require' => '请正确填写咨询类型排序',
        'consulttype_sort.number' => '请正确填写咨询类型排序',
    );
    protected $scene = [
        'type_add' => ['consulttype_name', 'sort'],
        'type_edit' => ['consulttype_name', 'sort'],
    ];
}
