<?php

namespace app\common\model;

use think\facade\Db;

/**
 * 导航
 */
class Navigation extends BaseModel
{

    public $page_info;

    /**
     * 获取导航列表
     * @access public
     * @author csdeshang
     * @param array $condition 条件
     * @param int $pagesize 分页
     * @param string $order 排序
     * @return array
     */
    public function getNavigationList($condition, $pagesize = '', $order = 'nav_sort desc')
    {
        if ($pagesize) {
            $nav_list = Db::name('navigation')->where($condition)->order($order)->paginate(['list_rows' => $pagesize, 'query' => request()->param()], false);
            $this->page_info = $nav_list;
            return $nav_list->items();
        } else {
            return Db::name('navigation')->where($condition)->order('nav_sort')->select()->toArray();
        }
    }

    /**
     * 新增导航
     * @access public
     * @author csdeshang
     * @param type $data 参数内容
     * @return bool
     */
    public function addNavigation($data)
    {
        $add_navigation = Db::name('navigation')->insert($data);
        return $add_navigation;
    }

    /**
     * 编辑导航
     * @access public
     * @author csdeshang
     * @param type $data 数据
     * @param type $condition 条件
     * @return bool
     */
    public function eidtNavigation($data, $condition)
    {
        return Db::name('navigation')->where($condition)->update($data);
    }

    /**
     * 获取单个导航
     * @access public
     * @author csdeshang
     * @param type $condition 条件
     * @return array
     */
    public function getOneNavigation($condition)
    {
        return Db::name('navigation')->where($condition)->find();
    }

    /**
     * 删除导航
     * @access public
     * @author csdeshang
     * @param type $condition 条件
     * @return bool
     */
    public function delNavigation($condition)
    {
        return Db::name('navigation')->where($condition)->delete();
    }
}
