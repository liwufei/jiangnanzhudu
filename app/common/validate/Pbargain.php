<?php

namespace app\common\validate;

use think\Validate;

/**
 * 砍价
 */
class Pbargain extends Validate
{
    protected $rule = [
        'bargain_name' => 'require|max:50',
        'bargain_limit' => 'require|integer|egt:0',
        'bargain_time' => 'require|integer|egt:1|elt:48',
        'bargain_floorprice' => 'require|float|gt:0',
        'bargain_total' => 'require|integer|egt:1',
        'bargain_max' => 'require|float|gt:0',
        'bargain_remark' => 'max:50',
        'bargain_begintime' => 'require|date',
        'bargain_endtime' => 'require|date',
    ];
    protected $message  =   [
        'bargain_name.require' => '请填写砍价名称',
        'bargain_name.max' => '砍价名称不能超过50个字符',
        'bargain_limit.require' => '请填写砍价限购数量',
        'bargain_limit.integer' => '砍价限购数量必须是整数',
        'bargain_limit.egt' => '砍价限购数量需要大于等于0',
        'bargain_time.require' => '请填写砍价有效期',
        'bargain_time.integer' => '砍价有效期必须是整数',
        'bargain_time.egt' => '砍价有效期需要大于等于1',
        'bargain_time.elt' => '砍价有效期需要小于等于48',
        'bargain_floorprice.require' => '请填写商品底价',
        'bargain_floorprice.float' => '商品底价必须是数字',
        'bargain_floorprice.gt' => '商品底价需要大于0',
        'bargain_total.require' => '请填写底价砍价次数',
        'bargain_total.integer' => '底价砍价次数必须是整数',
        'bargain_total.egt' => '底价砍价次数需要大于等于1',
        'bargain_max.require' => '请填写每刀最多可砍金额',
        'bargain_max.float' => '每刀最多可砍金额必须是数字',
        'bargain_max.gt' => '每刀最多可砍金额需要大于0',
        'bargain_remark.max' => '分享描述不能超过50个字符',
        'bargain_begintime.require' => '请填写开始时间',
        'bargain_begintime.date' => '开始时间错误',
        'bargain_endtime.require' => '请填写结束时间',
        'bargain_endtime.date' => '结束时间错误',
    ];
    protected $scene = [
        'pbargin_save' => ['bargain_name', 'bargain_limit', 'bargain_time', 'bargain_floorprice', 'bargain_total', 'bargain_max', 'bargain_remark', 'bargain_begintime', 'bargain_endtime'],
    ];
}
