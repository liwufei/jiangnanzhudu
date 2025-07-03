<?php

namespace app\common\model;

use think\facade\Db;

/**
 * 店铺绑定类目
 */
class Storebindclass extends BaseModel
{
    private $page_info;

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
    public function getStorebindclassList($condition, $pagesize = '', $order = '', $field = '*')
    {
        if ($pagesize) {
            $result = Db::name('storebindclass')->field($field)->where($condition)->order($order)->paginate(['list_rows' => $pagesize, 'query' => request()->param()], false);
            $this->page_info = $result;
            return $result->items();
        } else {
            $result = Db::name('storebindclass')->field($field)->where($condition)->order($order)->select()->toArray();
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
    public function getStorebindclassInfo($condition)
    {
        $result = Db::name('storebindclass')->where($condition)->find();
        return $result;
    }

    /**
     * 增加
     * @access public
     * @author csdeshang
     * @param array $data 数据
     * @return bool
     */
    public function addStorebindclass($data)
    {
        return Db::name('storebindclass')->insertGetId($data);
    }

    /**
     * 增加
     * @access public
     * @author csdeshang
     * @param type $data 数据
     * @return type
     */
    public function addStorebindclassAll($data)
    {
        return Db::name('storebindclass')->insertAll($data);
    }

    /**
     * 更新
     * @access public
     * @author csdeshang
     * @param type $update 更新数据
     * @param type $condition 条件
     * @return type
     */
    public function editStorebindclass($update, $condition)
    {
        return Db::name('storebindclass')->where($condition)->update($update);
    }

    /**
     * 删除
     * @access public
     * @author csdeshang
     * @param array $condition 条件
     * @return bool
     */
    public function delStorebindclass($condition)
    {
        return Db::name('storebindclass')->where($condition)->delete();
    }

    /**
     * 总数量
     * @access public
     * @author csdeshang
     * @param array $condition 条件
     * @return int
     */
    public function getStorebindclassCount($condition = array())
    {
        return Db::name('storebindclass')->where($condition)->count();
    }

    /**
     * 取得店铺下商品分类佣金比例
     * @access public
     * @author csdeshang
     * @param array $goods_list 商品列表
     * @return array
     */
    public function getStoreGcidCommisRateList($goods_list)
    {
        if (empty($goods_list) || !is_array($goods_list))
            return array();

        //定义返回数组
        $store_gc_id_commis_rate = array();

        //取得每个店铺下有哪些商品分类
        $store_gc_id_list = array();
        foreach ($goods_list as $goods) {
            if (!intval($goods['gc_id']))
                continue;
            if (empty($store_gc_id_list) || empty($store_gc_id_list[$goods['store_id']]) || !in_array($goods['gc_id'], $store_gc_id_list[$goods['store_id']])) {
                $store_gc_id_list[$goods['store_id']][] = $goods;
            }
        }

        if (empty($store_gc_id_list))
            return $store_gc_id_commis_rate;

        $store_bind_class_list = array();
        foreach ($store_gc_id_list as $store_id => $gc_id_list) {
            foreach ($gc_id_list as $gc_id) {
                $key = $gc_id['gc_id_1'] . '|' . $gc_id['gc_id_2'] . '|' . $gc_id['gc_id_3'];
                if (!isset($store_bind_class_list[$key])) {
                    //如果class_1,2,3有一个字段值匹配，就有效
                    $condition = array();
                    $condition[] = array('store_id', '=', $store_id);
                    $condition[] = Db::raw('(class_1=0 AND class_2=0 AND class_3=0) OR (class_1=' . $gc_id['gc_id_1'] . ' AND class_2=0 AND class_3=0) OR (class_1=' . $gc_id['gc_id_1'] . ' AND class_2=' . $gc_id['gc_id_2'] . ' AND class_3=0) OR (class_1=' . $gc_id['gc_id_1'] . ' AND class_2=' . $gc_id['gc_id_2'] . ' AND class_3=' . $gc_id['gc_id_3'] . ')');
                    $bind_list = $this->getStorebindclassList($condition, 1, 'class_3 desc,class_2 desc,class_1 desc');
                    if (!empty($bind_list) && is_array($bind_list)) {
                        $store_bind_class_list[$key] = $bind_list[0];
                    } else {
                        $store_bind_class_list[$key] = false;
                    }
                }
                if ($store_bind_class_list[$key]) {
                    $store_gc_id_commis_rate[$store_id][$gc_id['gc_id']] = $store_bind_class_list[$key]['commis_rate'];
                }
            }
        }
        return $store_gc_id_commis_rate;
    }
}
