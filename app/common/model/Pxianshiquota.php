<?php

namespace app\common\model;

use think\facade\Db;

/**
 * 限时折扣套餐
 */
class Pxianshiquota extends BaseModel
{

    public $page_info;

    /**
     * 读取秒杀套餐列表
     * @access public
     * @author csdeshang
     * @param array $condition 条件
     * @param int $pagesize 分页
     * @param string $order 排序
     * @param string $field 字段
     * @return array 秒杀套餐列表
     */
    public function getXianshiquotaList($condition, $pagesize = null, $order = '', $field = '*')
    {
        if ($pagesize) {
            $res = Db::name('pxianshiquota')->field($field)->where($condition)->order($order)->paginate(['list_rows' => $pagesize, 'query' => request()->param()], false);
            $this->page_info = $res;
            $result = $res->items();
        } else {
            $result = Db::name('pxianshiquota')->field($field)->where($condition)->order($order)->select()->toArray();
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
    public function getXianshiquotaInfo($condition)
    {
        $result = Db::name('pxianshiquota')->where($condition)->find();
        return $result;
    }

    /**
     * 获取当前可用套餐
     * @access public
     * @author csdeshang
     * @param type $store_id 店铺ID
     * @return type
     */
    public function getXianshiquotaCurrent($store_id)
    {
        $condition = array();
        $condition[] = array('store_id', '=', $store_id);
        $condition[] = array('xianshiquota_endtime', '>', TIMESTAMP);
        return $this->getXianshiquotaInfo($condition);
    }

    /**
     * 增加
     * @access public
     * @author csdeshang
     * @param type $data 数据
     * @return bool
     */
    public function addXianshiquota($data)
    {
        return Db::name('pxianshiquota')->insertGetId($data);
    }

    /**
     * 更新
     * @access public
     * @author csdeshang
     * @param type $update 更新数据
     * @param type $condition 检索条件
     * @return bool
     */
    public function editXianshiquota($update, $condition)
    {
        return Db::name('pxianshiquota')->where($condition)->update($update);
    }

    /**
     * 删除
     * @access public
     * @author csdeshang
     * @param type $condition 条件
     * @return bool
     */
    public function delXianshiquota($condition)
    {
        return Db::name('pxianshiquota')->where($condition)->delete();
    }
}
