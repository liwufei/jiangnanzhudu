<?php

namespace app\common\validate;

use think\Validate;

/**
 * 积分
 */
class Point extends Validate
{
    protected $rule = [
        'member_name' => 'require',
        'points_num' => 'number|min:1',
        'pgoods_name' => 'require|length:0,50',
        'pgoods_price' => 'require',
        'pgoods_points' => 'require|number',
        'pgoods_serial' => 'require',
        'pgoods_storage' => 'require|number',
        'pgoods_sort' => 'require|number|between:0,255',
    ];
    protected $message = [
        'member_name.require' => '会员信息错误，请重新填写会员名',
        'points_num.number' => '积分值必须为数字',
        'points_num.min' => '积分值必须大于0',
        'pgoods_name.require' => '请添加礼品名称',
        'pgoods_name.length' => '礼品名称长度不能大于200',
        'pgoods_price.require' => '礼品原价必须为数字且大于等于0',
        'pgoods_points.require' => '兑换积分为整数且大于等于0',
        'pgoods_points.number' => '兑换积分为整数且大于等于0',
        'pgoods_serial.require' => '请添加礼品编号',
        'pgoods_storage.require' => '礼品库存必须为整数且大于等于0',
        'pgoods_storage.number' => '礼品库存必须为整数且大于等于0',
        'pgoods_sort.require' => '礼品排序必须为0-255间数字',
        'pgoods_sort.number' => '礼品排序必须为0-255间数字',
        'pgoods_sort.between' => '礼品排序必须为0-255间数字',
    ];
    protected $scene = [
        'edit_points' => ['member_name', 'points_num'],
        'prod_add' => ['pgoods_name', 'pgoods_price', 'pgoods_points', 'pgoods_serial', 'pgoods_storage', 'pgoods_sort'],
        'prod_edit' => ['pgoods_name', 'pgoods_price', 'pgoods_points', 'pgoods_serial', 'pgoods_storage', 'pgoods_sort'],
    ];
}
