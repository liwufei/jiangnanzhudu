<?php

namespace app\common\model;

use think\facade\Db;

/**
 * 店铺消息接收设置
 */
class Storemsgsetting extends BaseModel
{

    public $page_info;

    /**
     * 店铺消息接收设置列表
     * @access public
     * @author csdeshang
     * @param type $condition 条件
     * @param type $field 字段
     * @param type $key 键值
     * @param type $pagesize 分页
     * @param type $order 排序
     * @return type
     */
    public function getStoremsgsettingList($condition, $field = '*', $key = '', $pagesize = 0, $order = 'storemt_code asc')
    {
        if ($pagesize) {
            $res = Db::name('storemsgsetting')->field($field)->where($condition)->order($order)->paginate(['list_rows' => $pagesize, 'query' => request()->param()], false);
            $this->page_info = $res;
            $result = $res->items();
        } else {
            $result = Db::name('storemsgsetting')->field($field)->where($condition)->order($order)->select()->toArray();
        }
        return ds_change_arraykey($result, $key);
    }

    /**
     * 店铺消息接收设置详细
     * @access public
     * @author csdeshang
     * @param type $condition 条件
     * @param type $field 字段
     * @return type
     */
    public function getStoremsgsettingInfo($condition, $field = '*')
    {
        return Db::name('storemsgsetting')->field($field)->where($condition)->find();
    }

    /**
     * 添加店铺模板接收设置
     * @access public
     * @author csdeshang
     * @param array $data 新增数据
     * @return bool
     */
    public function addStoremsgsetting($data)
    {
        return Db::name('storemsgsetting')->insert($data);
    }

    /**
     * 编辑店铺模板接收设置
     * @access public
     * @author csdeshang
     * @param array $data 更新数据
     * @return bool
     */
    public function editStoremsgsetting($data, $condition)
    {
        return Db::name('storemsgsetting')->where($condition)->update($data);
    }
}
