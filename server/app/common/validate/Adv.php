<?php

namespace app\common\validate;

use think\Validate;

/**
 * 广告
 */
class Adv extends Validate
{
    protected $rule = [
        'ap_name' => 'require|length:0,50',
        'ap_width' => 'require|number|length:0,8',
        'ap_height' => 'require|number|length:0,8',
        'adv_title' => 'require|length:0,50',
        'ap_id' => 'require',
        'adv_startdate' => 'require',
        'adv_enddate' => 'require',
        'adv_typedate' => 'length:1,255'
    ];
    protected $message = [
        'ap_name.require' => '广告位名称不能为空',
        'ap_name.length' => '广告位名称长度不能超过50个字符',
        'ap_width.require' => '广告位宽度为必填',
        'ap_width.number' => '广告位宽度应为不小于0的整数',
        'ap_width.length' => '广告位宽度应该小于9位数',
        'ap_height.require' => '广告位高度只能为数字形式',
        'ap_height.number' => '广告位高度应为不小于0的整数',
        'ap_height.length' => '广告位高度应该小于9位数',
        'adv_title.require' => '广告名称不能为空',
        'adv_title.length' => '广告名称长度不能超过50个字符',
        'ap_id.require' => '必须选择一个广告位',
        'adv_startdate.require' => '必须选择开始时间',
        'adv_enddate.require' => '必须选择结束时间',
        'adv_typedate.length' => '操作值必须小于255个字符'
    ];
    protected $scene = [
        'ap_add' => ['ap_name', 'ap_width', 'ap_height'],
        'ap_edit' => ['ap_name', 'ap_width', 'ap_height'],
        'adv_add' => ['adv_title', 'ap_id', 'adv_startdate', 'adv_enddate'],
        'adv_edit' => ['adv_title', 'adv_startdate', 'adv_enddate'],
        'app_ap_add' => ['ap_name', 'ap_width', 'ap_height'],
        'app_ap_edit' => ['ap_name', 'ap_width', 'ap_height'],
        'app_adv_add' => ['adv_title', 'ap_id', 'adv_startdate', 'adv_enddate', 'adv_typedate'],
        'app_adv_edit' => ['adv_title', 'adv_startdate', 'adv_enddate', 'adv_typedate'],
    ];
}
