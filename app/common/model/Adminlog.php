<?php

namespace app\common\model;

use think\facade\Db;

/**
 * 管理员日志
 */
class Adminlog extends BaseModel
{

    public $page_info;

    /**
     * 获取日志记录列表
     * @author csdeshang
     * @param type $condition 查询条件
     * @param type $pagesize      分页信息
     * @param type $order     排序
     * @return type
     */
    public function getAdminlogList($condition, $pagesize = '', $order)
    {
        if ($pagesize) {
            $result = Db::name('adminlog')->where($condition)->order($order)->paginate(['list_rows' => $pagesize, 'query' => request()->param()], false);
            $this->page_info = $result;
            return $result->items();
        } else {
            return Db::name('adminlog')->where($condition)->order($order)->select()->toArray();
        }
    }

    /**
     * 删除日志记录
     * @author csdeshang
     * @param type $condition 删除条件
     * @return type
     */
    public function delAdminlog($condition)
    {
        return Db::name('adminlog')->where($condition)->delete();
    }

    /**
     * 获取日志条数
     * @author csdeshang
     * @param type $condition 查询条件
     * @return type
     */
    public function getAdminlogCount($condition)
    {
        return Db::name('adminlog')->where($condition)->count();
    }

    /**
     * 增加日子
     * @author csdeshang
     * @param type $data
     * @return type
     */
    public function addAdminlog($data)
    {
        return Db::name('adminlog')->insertGetId($data);
    }
}
