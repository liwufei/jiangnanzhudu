<?php

namespace app\common\validate;

use think\Validate;

/**
 * 会员地址
 */
class Memberaddress extends Validate
{
    protected $rule = [
        'address_realname' => 'length:0,50',
        'city_id' => 'gt:0',
        'area_id' => 'gt:0',
        'area_info' => 'length:0,255',
        'address_detail' => 'length:0,255',
        'address_mob_phone' => 'mobile',
        'address_tel_phone' => 'length:0,255',
    ];
    protected $message = [
        'address_realname.length' => '姓名长度不能大于50',
        'city_id.gt' => '请选择地区',
        'area_id.gt' => '地区至少两级',
        'area_info.length' => '地区长度不能大于255',
        'address_detail.length' => '地址长度不能大于255',
        'address_mob_phone.mobile' => '手机号格式不正确',
        'address_tel_phone.mobile' => '座机号长度不能大于15',
    ];
    protected $scene = [
        'model_add' => ['address_realname', 'city_id', 'area_id', 'area_info', 'address_detail', 'address_mob_phone', 'address_tel_phone'],
        'model_edit' => ['address_realname', 'city_id', 'area_id', 'area_info', 'address_detail', 'address_mob_phone', 'address_tel_phone'],
    ];
}
