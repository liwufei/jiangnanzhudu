<?php

namespace app\common\validate;

use think\Validate;

/**
 * 门店商品
 */
class ChainGoods extends Validate
{
    protected $rule = [
        'goods_storage' => 'require|integer|egt:0',
        'goods_id' => 'require',
    ];
    protected $message = [
        'goods_id.require' => '缺少商品ID',
        'goods_storage.require' => '库存必填',
        'goods_storage.integer' => '库存设置错误',
        'goods_storage.egt' => '库存设置错误',
    ];
    protected $scene = [
        'chain_goods_add' => ['goods_storage', 'goods_id'],
        'chain_goods_edit' => ['goods_storage', 'goods_id'],
    ];
}
