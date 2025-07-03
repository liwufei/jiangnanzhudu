<?php

namespace app\common\model;

use think\facade\Db;

/**
 * 卖家日志
 */
class Sellerlog extends BaseModel
{

    public $page_info;

    /**
     * 读取列表
     * @access public
     * @author csdeshang
     * @param array $condition 条件
     * @param int $pagesize 分页
     * @param string $order 排序
     * @param string $field 字段
     * @return array
     */
    public function getSellerlogList($condition, $pagesize = '', $order = '', $field = '*')
    {
        if ($pagesize) {
            $result = Db::name('sellerlog')->field($field)->where($condition)->order($order)->paginate(['list_rows' => $pagesize, 'query' => request()->param()], false);
            $this->page_info = $result;
            return $result->items();
        } else {
            $result = Db::name('sellerlog')->field($field)->where($condition)->order($order)->select()->toArray();
            return $result;
        }
    }

    /**
     * 读取单条记录
     * @access public
     * @author csdeshang
     * @param type $condition 条件
     * @return type
     */
    public function getSellerlogInfo($condition)
    {
        $result = Db::name('sellerlog')->where($condition)->find();
        return $result;
    }

    /**
     * 增加 
     * @access public
     * @author csdeshang
     * @param array $data 数据
     * @return bool
     */
    public function addSellerlog($data)
    {
        return Db::name('sellerlog')->insertGetId($data);
    }

    /**
     * 删除
     * @access public
     * @author csdeshang
     * @param array $condition 条件
     * @return bool
     */
    public function delSellerlog($condition)
    {
        return Db::name('sellerlog')->where($condition)->delete();
    }
}
