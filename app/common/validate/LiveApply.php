<?php

namespace app\common\validate;

use think\Validate;

/**
 * 直播申请
 */
class  LiveApply extends Validate
{
    protected $rule = [
        'live_apply_name' => 'require',
        'live_apply_play_time' => 'require',
        'live_apply_remark' => 'require|max:255',
        'live_apply_push_state' => 'require|in:1,2',
    ];
    protected $message  =   [
        'live_apply_name.require' => '请填写直播标题',
        'live_apply_play_time.require' => '请填写直播时间',
        'live_apply_remark.require' => '请填写申请理由',
        'live_apply_remark.max' => '申请理由不能超过255字',
        'live_apply_push_state.require' => '缺少推流状态',
        'live_apply_push_state.in' => '推流状态错误',
    ];
    protected $scene = [
        'live_apply_save' => ['live_apply_name', 'live_apply_play_time', 'live_apply_remark'],
        'live_apply_change' => ['live_apply_push_state'],
    ];
}
