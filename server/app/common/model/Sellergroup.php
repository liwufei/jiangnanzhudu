<?php

namespace app\common\model;

use think\facade\Db;

/**
 * 卖家分组
 */
class Sellergroup extends BaseModel
{

    /**
     * 读取列表
     * @access public
     * @author csdeshang
     * @param type $condition 条件
     * @return type
     */
    public function getSellergroupList($condition)
    {
        $result = Db::name('sellergroup')->where($condition)->select()->toArray();
        return $result;
    }

    /**
     * 读取单条记录
     * @access public
     * @author csdeshang
     * @param type $condition 条件
     * @return type
     */
    public function getSellergroupInfo($condition)
    {
        $result = Db::name('sellergroup')->where($condition)->find();
        return $result;
    }

    /**
     * 判断是否存在
     * @access public
     * @author csdeshang
     * @param type $condition 条件
     * @return boolean
     */
    public function isSellergroupExist($condition)
    {
        $result = Db::name('sellergroup')->where($condition)->find();
        if (empty($result)) {
            return FALSE;
        } else {
            return TRUE;
        }
    }

    /**
     * 增加 
     * @access public
     * @author csdeshang
     * @param array $data 参数内容
     * @return bool
     */
    public function addSellergroup($data)
    {
        return Db::name('sellergroup')->insertGetId($data);
    }

    /**
     * 更新
     * @access public
     * @author csdeshang
     * @param array $update 数据
     * @param array $condition 条件
     * @return bool
     */
    public function editSellergroup($update, $condition)
    {
        return Db::name('sellergroup')->where($condition)->update($update);
    }

    /**
     * 删除
     * @access public
     * @author csdeshang
     * @param array $condition 条件
     * @return bool
     */
    public function delSellergroup($condition)
    {
        return Db::name('sellergroup')->where($condition)->delete();
    }
}
