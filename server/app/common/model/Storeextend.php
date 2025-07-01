<?php

namespace app\common\model;

use think\facade\Db;

/**
 * 店铺扩展信息
 */
class Storeextend extends BaseModel
{

    /**
     * 查询店铺扩展信息
     * @access public
     * @author csdeshang
     * @param array $condition 店铺编号
     * @param string $field 查询字段
     * @return array
     */
    public function getStoreextendInfo($condition, $field = '*')
    {
        return Db::name('storeextend')->field($field)->where($condition)->find();
    }

    /**
     * 编辑店铺扩展信息
     * @access public
     * @author csdeshang
     * @param type $update 更新数据
     * @param type $condition 条件
     * @return type
     */
    public function editStoreextend($data, $condition)
    {
        if (empty($condition)) {
            return false;
        }
        if (is_array($data)) {
            $result = Db::name('storeextend')->where($condition)->update($data);
            return $result;
        } else {
            return false;
        }
    }

    /**
     * 删除店铺扩展信息
     * @access public
     * @author csdeshang
     * @param type $condition 条件
     * @return type
     */
    public function delStoreextend($condition)
    {
        return Db::name('storeextend')->where($condition)->delete();
    }

    /**
     * 添加店铺扩展信息
     * @access public
     * @author csdeshang
     * @param type $condition 条件
     * @return type
     */
    public function addStoreextend($data)
    {
        return Db::name('storeextend')->insertGetId($data);
    }
}
