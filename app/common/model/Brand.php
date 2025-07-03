<?php

namespace app\common\model;

use think\facade\Db;

/**
 * 品牌
 */
class Brand extends BaseModel
{

    public $page_info;

    /**
     * 添加品牌
     * @access public
     * @author csdeshang 
     * @param array $data 参数内容
     * @return boolean
     */
    public function addBrand($data)
    {
        return Db::name('brand')->insertGetId($data);
    }

    /**
     * 编辑品牌
     * @access public
     * @author csdeshang 
     * @param array $condition 检索条件
     * @param array $update 更新数据
     * @return boolean
     */
    public function editBrand($condition, $data)
    {
        return Db::name('brand')->where($condition)->update($data);
    }

    /**
     * 删除品牌
     * @access public
     * @author csdeshang
     * @param array $condition 检索条件
     * @return boolean
     */
    public function delBrand($condition)
    {
        $brand_array = $this->getBrandList($condition, 'brand_id,brand_pic');
        $brandid_array = array();
        foreach ($brand_array as $value) {
            $brandid_array[] = $value['brand_id'];
            ds_del_pic(ATTACH_BRAND, $value['brand_pic']);
        }
        return Db::name('brand')->where(array(array('brand_id', 'in', $brandid_array)))->delete();
    }

    /**
     * 查询品牌数量
     * @access public
     * @author csdeshang
     * @param array $condition 检索条件
     * @return array
     */
    public function getBrandCount($condition)
    {
        return Db::name('brand')->where($condition)->count();
    }

    /**
     * 品牌列表
     * @access public
     * @author csdeshang 
     * @param array $condition 检索条件
     * @param str $field 字段
     * @param int $pagesize 分页信息
     * @param str $order 排序
     * @return array
     */
    public function getBrandList($condition, $field = '*', $pagesize = 0, $order = 'brand_sort asc, brand_id desc')
    {
        if ($pagesize) {
            $res = Db::name('brand')->where($condition)->field($field)->order($order)->paginate(['list_rows' => $pagesize, 'query' => request()->param()], false);
            $this->page_info = $res;
            return $res->items();
        } else {
            return Db::name('brand')->where($condition)->field($field)->order($order)->select()->toArray();
        }
    }

    /**
     * 通过的品牌列表
     * @access public
     * @author csdeshang 
     * @param array $condition 检索条件
     * @param str $field 字段
     * @param int $pagesize 分页信息
     * @param str $order 排序
     * @return array
     */
    public function getBrandPassedList($condition, $field = '*', $pagesize = 0, $order = 'brand_sort asc, brand_id desc')
    {
        $condition[] = array('brand_apply', '=', 1);
        return $this->getBrandList($condition, $field, $pagesize, $order);
    }

    /**
     * 未通过的品牌列表
     * @access public
     * @author csdeshang 
     * @param array $condition 检索条件
     * @param string $field 字段
     * @param string $pagesize 分页信息
     * @return array
     */
    public function getBrandNoPassedList($condition, $field = '*', $pagesize = 0)
    {
        $condition[] = array('brand_apply', '=', 0);
        return $this->getBrandList($condition, $field, $pagesize);
    }

    /**
     * 取单个品牌内容
     * @access public
     * @author csdeshang 
     * @param array $condition 检索条件
     * @param string $field 字段
     * @return array
     */
    public function getBrandInfo($condition, $field = '*')
    {
        return Db::name('brand')->field($field)->where($condition)->find();
    }
}
