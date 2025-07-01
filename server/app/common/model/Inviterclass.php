<?php

namespace app\common\model;

use think\facade\Db;

/**
 * 分销员等级
 */
class Inviterclass extends BaseModel
{
    //获取分销员所对应的等级
    public function getInviterclass($inviterclass_amount)
    {
        $inviterclass_name = '';
        $inviterclass_list = Db::name('inviterclass')->order('inviterclass_amount asc')->select()->toArray();
        foreach ($inviterclass_list as $inviterclass) {
            if ($inviterclass_amount >= $inviterclass['inviterclass_amount']) {
                $inviterclass_name = $inviterclass['inviterclass_name'];
            }
        }
        return $inviterclass_name;
    }
}
