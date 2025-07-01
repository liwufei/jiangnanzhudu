<?php

namespace app\common\model;

use think\facade\Db;

/**
 * 小程序直播商品
 */
class MiniproLiveGoods extends BaseModel
{

    public $page_info;

    public function getMiniproLiveGoodsList($condition, $field = '*', $pagesize = 10, $order = 'minipro_live_goods_id desc')
    {
        if ($pagesize) {
            $result = Db::name('minipro_live_goods')->field($field)->where($condition)->order($order)->paginate(['list_rows' => $pagesize, 'query' => request()->param()], false);
            $this->page_info = $result;
            return $result->items();
        } else {
            $result = Db::name('minipro_live_goods')->field($field)->where($condition)->order($order)->select()->toArray();
            return $result;
        }
    }

    /**
     * 取单个内容
     * @access public
     * @author csdeshang
     * @param int $id 分类ID
     * @return array 数组类型的返回结果
     */
    public function getMiniproLiveGoodsInfo($condition)
    {
        $result = Db::name('minipro_live_goods')->where($condition)->find();
        return $result;
    }

    /**
     * 新增
     * @access public
     * @author csdeshang
     * @param array $data 参数内容
     * @return bool 布尔类型的返回结果
     */
    public function addMiniproLiveGoods($data)
    {
        $result = Db::name('minipro_live_goods')->insertGetId($data);
        return $result;
    }

    /**
     * 更新信息
     * @access public
     * @author csdeshang
     * @param array $data 数据
     * @param array $condition 条件
     * @return bool
     */
    public function editMiniproLiveGoods($data, $condition)
    {
        $result = Db::name('minipro_live_goods')->where($condition)->update($data);
        return $result;
    }

    /**
     * 删除分类
     * @access public
     * @author csdeshang
     * @param int $condition 记录ID
     * @return bool 
     */
    public function delMiniproLiveGoods($condition)
    {
        return Db::name('minipro_live_goods')->where($condition)->delete();
    }
}
