<?php

namespace app\common\model;

use think\facade\Db;

/**
 * 店铺动态设置
 */
class Storesnssetting extends BaseModel
{
    /**
     * 获取单条动态设置设置信息
     * @access public
     * @author csdeshang
     * @param array $condition 条件
     * @param string $field 字段
     * @return array
     */
    public function getStoresnssettingInfo($condition, $field = '*')
    {
        return Db::name('storesnssetting')->field($field)->where($condition)->find();
    }

    /**
     * 保存店铺动态设置
     * @access public
     * @author csdeshang
     * @param array $data 参数数据
     * @return boolean
     */
    public function addStoresnssetting($data)
    {
        return Db::name('storesnssetting')->insert($data);
    }

    /**
     * 保存店铺动态设置
     * @access public
     * @author csdeshang
     * @param type $update 更新数据
     * @param type $condition 条件
     * @return boolean
     */
    public function editStoresnssetting($update, $condition)
    {
        return Db::name('storesnssetting')->where($condition)->update($update);
    }
}
