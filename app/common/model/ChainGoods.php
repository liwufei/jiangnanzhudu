<?php

namespace app\common\model;

use think\facade\Db;

/**
 * 门店商品
 */
class ChainGoods extends BaseModel
{

    public $page_info;

    /**
     * 插入数据
     * @access public
     * @author csdeshang 
     * @param array $data 数据
     * @return boolean
     */
    public function addChainGoods($data)
    {
        return Db::name('chain_goods')->insertGetId($data);
    }

    /**
     * 查询门店列表
     * @access public
     * @author csdeshang 
     * @param array $condition 检索条件
     * @param int $pagesize 分页信息
     * @param string $order 排序
     * @return array
     */
    public function getChainGoodsList($condition, $pagesize = 0, $order = 'chain_goods_id desc')
    {
        if ($pagesize) {
            $res = Db::name('chain_goods')->where($condition)->order($order)->paginate(['list_rows' => $pagesize, 'query' => request()->param()], false);
            $this->page_info = $res;
            return $res->items();
        } else {
            return Db::name('chain_goods')->where($condition)->order($order)->select()->toArray();
        }
    }

    /**
     * 取得门店详细信息
     * @access public
     * @author csdeshang 
     * @param array $condition 检索条件
     * @param string $field 字段
     * @return array
     */
    public function getChainGoodsInfo($condition, $field = '*')
    {
        return Db::name('chain_goods')->where($condition)->field($field)->find();
    }

    /**
     * 门店信息
     * @access public
     * @author csdeshang 
     * @param array $update 更新数据
     * @param array $condition 条件
     * @return bool
     */
    public function editChainGoods($update, $condition)
    {
        return Db::name('chain_goods')->where($condition)->update($update);
    }
}
