<?php

namespace app\common\model;

use think\facade\Db;

/**
 * 提现账户
 */
class Memberbank extends BaseModel
{

    /**
     * 取得单条提现账户
     * @author csdeshang 
     * @param array $condition 条件
     * @param type $order 排序  
     * @return string
     */
    public function getMemberbankInfo($condition, $order = '')
    {
        $addr_info = Db::name('memberbank')->where($condition)->order($order)->find();
        return $addr_info;
    }

    /**
     * 读取提现账户列表
     * @author csdeshang
     * @param array $condition 查询条件
     * @param type $order 排序
     * @return array  数组格式的返回结果
     */
    public function getMemberbankList($condition, $order = 'memberbank_id desc')
    {
        $memberbank_list = Db::name('memberbank')->where($condition)->order($order)->select()->toArray();
        return $memberbank_list;
    }

    /**
     * 取数量
     * @author csdeshang
     * @param array $condition 条件
     * @return int
     */
    public function getMemberbankCount($condition = array())
    {
        return Db::name('memberbank')->where($condition)->count();
    }

    /**
     * 新增提现账户
     * @author csdeshang
     * @param array $data 参数内容
     * @return bool 布尔类型的返回结果
     */
    public function addMemberbank($data)
    {
        return Db::name('memberbank')->insertGetId($data);
    }

    /**
     * 取单个提现账户
     * @author csdeshang
     * @param int $id 提现账户ID
     * @return array 数组类型的返回结果
     */
    public function getOneMemberbank($memberbank_id)
    {
        $condition = array();
        $condition[] = array('memberbank_id', '=', $memberbank_id);
        $result = Db::name('memberbank')->where($condition)->find();
        return $result;
    }

    /**
     * 更新提现账户
     * @author csdeshang
     * @param array $update 更新数据
     * @param array $condition 更新条件
     * @return bool 布尔类型的返回结果
     */
    public function editMemberbank($data, $condition)
    {
        return Db::name('memberbank')->where($condition)->update($data);
    }

    /**
     * 删除提现账户
     * @author csdeshang
     * @param array $condition记录ID
     * @return bool 布尔类型的返回结果
     */
    public function delMemberbank($condition)
    {
        return Db::name('memberbank')->where($condition)->delete();
    }
}
