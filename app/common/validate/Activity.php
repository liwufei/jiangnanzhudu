<?php

namespace app\common\validate;

use think\Validate;

/**
 * 活动
 */
class Activity extends Validate
{
    protected $rule = [
        'activity_title' => 'require|length:1,50',
        'activity_startdate' => 'require',
        'activity_enddate' => 'require|checkEnddate:1',
        'activity_type' => 'require',
        'activity_sort' => 'require|between:0,255'
    ];
    protected $message = [
        'activity_title.require' => '活动标题不能为空',
        'activity_title.length' => '活动标题长度不能大于50个字符',
        'activity_startdate.require' => '开始时间不能为空',
        'activity_enddate.require' => '结束时间不能为空',
        'activity_enddate.checkEnddate' => '结束时间不能为空',
        'activity_type.require' => '必须选择活动类别',
        'activity_sort.require' => '排序为0~255的数字',
        'activity_sort.between' => '排序应在0至255之间',
    ];
    protected $scene = [
        'add'  => ['activity_title', 'activity_startdate', 'activity_enddate', 'activity_type', 'activity_sort'],
        'edit' => ['activity_title', 'activity_startdate', 'activity_enddate', 'activity_type', 'activity_sort'],
    ];

    protected function checkEnddate($value)
    {
        $activity_startdate = strtotime(input('post.activity_startdate'));
        if ($activity_startdate >= $value) {
            return '结束时间早于开始时间或相同时间';
        }
        return true;
    }
}
