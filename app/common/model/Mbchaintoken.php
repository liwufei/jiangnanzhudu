<?php

namespace app\common\model;

use think\facade\Db;

/**
 * 提货站令牌
 */
class Mbchaintoken extends BaseModel
{

    /**
     * 查询
     * @access public
     * @author csdeshang
     * @param array $condition 查询条件
     * @return array
     */
    public function getMbchaintokenInfo($condition)
    {
        return Db::name('mbchaintoken')->where($condition)->find();
    }

    /**
     * 获取提货站令牌
     * @access public
     * @author csdeshang
     * @param type $token 令牌
     * @return type
     */
    public function getMbchaintokenInfoByToken($token)
    {
        if (empty($token)) {
            return null;
        }
        return $this->getMbchaintokenInfo(array('chain_token' => $token));
    }

    /**
     * 新增
     * @access public
     * @author csdeshang
     * @param array $data 参数内容
     * @return bool 布尔类型的返回结果
     */
    public function addMbchaintoken($data)
    {
        return Db::name('mbchaintoken')->insertGetId($data);
    }

    /**
     * 删除
     * @access public
     * @author csdeshang
     * @param int $condition 条件
     * @return bool 布尔类型的返回结果
     */
    public function delMbchaintoken($condition)
    {
        return Db::name('mbchaintoken')->where($condition)->delete();
    }
}
