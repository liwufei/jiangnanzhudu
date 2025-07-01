<?php

namespace app\common\model;

use think\facade\Db;

/**
 * 买家令牌
 */
class Mbusertoken extends BaseModel
{

    /**
     * 查询
     * @access public
     * @author csdeshang
     * @param array $condition 查询条件
     * @return array
     */
    public function getMbusertokenInfo($condition)
    {
        $mbusertoken = Db::name('mbusertoken')->where($condition)->find();
        if (!empty($mbusertoken)) {
            $mbusertoken['member_logintime_desc'] = date('Y-m-d H:i:s', $mbusertoken['member_logintime']);
            $mbusertoken['member_operationtime_desc'] = date('Y-m-d H:i:s', $mbusertoken['member_operationtime']);
            //更新最近活跃时间
            if (TIMESTAMP - $mbusertoken['member_operationtime'] > 60 * 60) {
                Db::name('mbusertoken')->where($condition)->update(array('member_operationtime' => TIMESTAMP));
            }
        }
        return $mbusertoken;
    }

    /**
     * 查询
     * @access public
     * @author csdeshang
     * @param type $token 令牌
     * @return type
     */
    public function getMbusertokenInfoByToken($token)
    {
        if (empty($token)) {
            return null;
        }
        return $this->getMbusertokenInfo(array('member_token' => $token));
    }

    /**
     * 编辑
     * @access public
     * @author csdeshang
     * @param type $token 令牌
     * @param type $openId ID
     * @return type
     */
    public function editMemberOpenId($token, $openId)
    {
        return Db::name('mbusertoken')->where(array('member_token' => $token,))->update(array('member_openid' => $openId,));
    }

    /**
     * 新增
     * @access public
     * @author csdeshang
     * @param array $data 参数内容
     * @return bool 布尔类型的返回结果
     */
    public function addMbusertoken($data)
    {
        return Db::name('mbusertoken')->insertGetId($data);
    }

    /**
     * 删除
     * @access public
     * @author csdeshang
     * @param int $condition 条件
     * @return bool 布尔类型的返回结果
     */
    public function delMbusertoken($condition)
    {
        return Db::name('mbusertoken')->where($condition)->delete();
    }

    public function getMbusertokenList($condition)
    {
        $mbusertoken_list = Db::name('mbusertoken')->where($condition)->select()->toArray();
        foreach ($mbusertoken_list as $key => $mbusertoken) {
            $mbusertoken_list[$key]['member_logintime_desc'] = date('Y-m-d H:i:s', $mbusertoken['member_logintime']);
            $mbusertoken_list[$key]['member_operationtime_desc'] = date('Y-m-d H:i:s', $mbusertoken['member_operationtime']);
        }
        return $mbusertoken_list;
    }
}
