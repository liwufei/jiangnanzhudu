<?php

namespace app\common\validate;

use think\Validate;

/**
 * 店铺版式
 */
class Storeplate extends Validate
{
    protected $rule = [
        'storeplate_name' => 'require|length:0,10',
        'storeplate_content' => 'require',
    ];
    protected $message = [
        'storeplate_name.require' => '请填写版式名称',
        'storeplate_name.length' => '版式名称长度不能大于10',
        'storeplate_content.require' => '请填写版式内容',
    ];
    protected $scene = [
        'add' => ['storeplate_name', 'storeplate_content'],
        'edit' => ['storeplate_name', 'storeplate_content'],
    ];
}
