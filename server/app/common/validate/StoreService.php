<?php

namespace app\common\validate;

use think\Validate;

/**
 * 店铺服务
 */
class StoreService extends Validate
{
    protected $rule = [
        'store_service_sort' => 'number',
        'store_service_title' => 'require',
    ];
    protected $message = [
        'store_service_sort.number' => '排序只能为数字',
        'store_service_title.require' => '标题名称不能为空',
    ];
    protected $scene = [
        'model_add' => ['store_service_sort', 'store_service_title'],
        'model_edit' => ['store_service_sort', 'store_service_title'],
    ];
}
