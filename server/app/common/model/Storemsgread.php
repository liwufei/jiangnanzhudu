<?php

namespace app\common\model;

use think\facade\Db;

/**
 * 店铺消息阅读
 */
class Storemsgread extends BaseModel
{

    /**
     * 新增店铺消息阅读
     * @access public
     * @author csdeshang
     * @param array $data 参数内容
     * @return bool
     */
    public function addStoremsgread($data)
    {
        $data['storemsg_readtime'] = TIMESTAMP;
        return Db::name('storemsgread')->insert($data);
    }

    /**
     * 查看店铺消息阅读详细
     * @access public
     * @author csdeshang
     * @param type $condition 条件
     * @param type $field 字段
     * @return type
     */
    public function getStoremsgreadInfo($condition, $field = '*')
    {
        return Db::name('storemsgread')->field($field)->where($condition)->find();
    }

    /**
     * 店铺消息阅读列表
     * @access public
     * @author csdeshang
     * @param array $condition 条件
     * @param string $field 字段
     * @param string $order 排序
     * @return array 
     */
    public function getStoremsgreadList($condition, $field = '*', $order = 'storemsg_readtime desc')
    {
        return Db::name('storemsgread')->field($field)->where($condition)->order($order)->select()->toArray();
    }

    /**
     * 删除店铺消息阅读记录
     * @access public
     * @author csdeshang
     * @param type $condition 条件
     * @return bool
     */
    public function delStoremsgread($condition)
    {
        Db::name('storemsgread')->where($condition)->delete();
    }
}
