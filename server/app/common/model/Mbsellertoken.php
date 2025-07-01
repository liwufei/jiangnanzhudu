<?php

namespace app\common\model;

use think\facade\Db;

/**
 * 卖家令牌
 */
class Mbsellertoken extends BaseModel
{

    /**
     * 查询
     * @access public
     * @author csdeshang
     * @param array $condition 查询条件
     * @return array
     */
    public function getMbsellertokenInfo($condition)
    {
        return Db::name('mbsellertoken')->where($condition)->find();
    }

    /**
     * 获取卖家令牌
     * @access public
     * @author csdeshang
     * @param type $token 令牌
     * @return type
     */
    public function getMbsellertokenInfoByToken($token)
    {
        if (empty($token)) {
            return null;
        }
        return $this->getMbsellertokenInfo(array('seller_token' => $token));
    }

    /**
     * 新增
     * @access public
     * @author csdeshang
     * @param array $data 参数内容
     * @return bool 布尔类型的返回结果
     */
    public function addMbsellertoken($data)
    {
        return Db::name('mbsellertoken')->insertGetId($data);
    }

    /**
     * 删除
     * @access public
     * @author csdeshang
     * @param int $condition 条件
     * @return bool 布尔类型的返回结果
     */
    public function delMbsellertoken($condition)
    {
        return Db::name('mbsellertoken')->where($condition)->delete();
    }
}
