<?php

namespace app\common\model;

use think\facade\Db;

/**
 * 会员等级折扣套餐
 */
class Pmgdiscountquota extends BaseModel
{

    public $page_info;

    /**
     * 获取会员等级折扣套餐列表
     * @access public
     * @author csdeshang
     * @param type $condition 条件
     * @param type $pagesize 分页
     * @param type $order 排序
     * @param type $field 字段
     * @return type
     */
    public function getMgdiscountquotaList($condition, $pagesize = null, $order = '', $field = '*')
    {
        if ($pagesize) {
            $res = Db::name('pmgdiscountquota')->field($field)->where($condition)->order($order)->paginate(['list_rows' => $pagesize, 'query' => request()->param()], false);
            $this->page_info = $res;
            $result = $res->items();
        } else {
            $result = Db::name('pmgdiscountquota')->field($field)->where($condition)->order($order)->select()->toArray();
        }
        return $result;
    }

    /**
     * 读取单条记录
     * @access public
     * @author csdeshang
     * @param type $condition 条件
     * @return type
     */
    public function getMgdiscountquotaInfo($condition)
    {
        $result = Db::name('pmgdiscountquota')->where($condition)->find();
        return $result;
    }

    /**
     * 获取当前可用套餐
     * @access public
     * @author csdeshang
     * @param type $store_id 店铺ID
     * @return type
     */
    public function getMgdiscountquotaCurrent($store_id)
    {
        $condition = array();
        $condition[] = array('store_id', '=', $store_id);
        $condition[] = array('mgdiscountquota_endtime', '>', TIMESTAMP);
        return $this->getMgdiscountquotaInfo($condition);
    }

    /**
     * 增加
     * @access public
     * @author csdeshang
     * @param type $data 数据
     * @return type
     */
    public function addMgdiscountquota($data)
    {
        return Db::name('pmgdiscountquota')->insertGetId($data);
    }

    /**
     * 更新
     * @access public
     * @author csdeshang
     * @param type $update 更新数据
     * @param type $condition 条件
     * @return type
     */
    public function editMgdiscountquota($update, $condition)
    {
        return Db::name('pmgdiscountquota')->where($condition)->update($update);
    }

    /**
     * 删除
     * @access public
     * @author csdeshang
     * @param type $condition 条件
     * @return type
     */
    public function delMgdiscountquota($condition)
    {
        return Db::name('pmgdiscountquota')->where($condition)->delete();
    }
}
