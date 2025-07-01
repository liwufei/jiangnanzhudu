<?php

namespace app\common\model;

use think\facade\Db;

/**
 * 充值卡余额变更日志
 */
class Rcblog extends BaseModel
{

    public $page_info;

    /**
     * 获取列表
     * @access public
     * @author csdeshang
     * @param array $condition 条件
     * @param string $pagesize 分页
     * @param string $order 排序
     * @return array
     */
    public function getRechargecardBalanceLogList($condition, $pagesize, $order)
    {
        $res = Db::name('rcblog')->where($condition)->order($order)->paginate(['list_rows' => $pagesize, 'query' => request()->param()], false);
        $this->page_info = $res;
        return $res->items();
    }
}
