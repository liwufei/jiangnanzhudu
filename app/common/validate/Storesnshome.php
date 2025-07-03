<?php

namespace app\common\validate;

use think\Validate;

/**
 * 店铺评论
 */
class Storesnshome extends Validate
{
    protected $rule = [
        'commentcontent' => 'require|length:0,140',
        'forwardcontent' => 'require|length:0,140',
    ];
    protected $message = [
        'commentcontent.require' => '需要评论点内容|不能超过140字',
        'commentcontent.length' => '需要评论点内容|不能超过140字',
        'forwardcontent.require' => '需要评论点内容|不能超过140字',
        'forwardcontent.length' => '需要评论点内容|不能超过140字',
    ];
    protected $scene = [
        'addcomment' => ['commentcontent'],
        'addforward' => ['forwardcontent'],
    ];
}
