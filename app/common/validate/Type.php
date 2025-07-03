<?php

namespace app\common\validate;

use think\Validate;

/**
 * 类型
 */
class Type extends Validate
{
    protected $rule = [
        'type_name' => 'require|length:0,50',
        'type_sort' => 'require|number|between:0,255',
        'class_id' => 'require|number',
        'attr_name' => 'require|length:0,50',
        'type_id' => 'require|number',
        'attr_show' => 'require|number',
        'attr_sort' => 'require|number|between:0,255',
    ];
    protected $message = [
        'type_name.require' => '类型名不能为空',
        'type_name.length' => '类型名长度不能大于50',
        'type_sort.require' => '请填写类型排序|类型排序必须为数字',
        'type_sort.number' => '类型排序必须为0-255间数字',
        'type_sort.between' => '类型排序必须为0-255间数字',
        'class_id.require' => '分类为必填',
        'class_id.number' => '分类ID必须为数字',
        'attr_name.require' => '属性名称为必填',
        'attr_name.length' => '属性名长度不能大于50',
        'type_id.require' => '类型ID为必填|类型ID必须为数字',
        'type_id.number' => '类型ID为必填|类型ID必须为数字',
        'attr_show.require' => '属性是否显示为必填',
        'attr_show.number' => '属性是否显示必须为数字',
        'attr_sort.require' => '属性排序为必填',
        'attr_sort.number' => '属性排序必须为0-255间数字',
        'attr_sort.between' => '属性排序必须为0-255间数字',
    ];
    protected $scene = [
        'type_add' => ['type_name', 'type_sort', 'class_id'],
        'type_edit' => ['type_name', 'type_sort', 'class_id'],
        'attr_edit' => ['attr_name', 'type_id', 'attr_show', 'attr_sort'],
    ];
}
