<?php

namespace app\common\model;

use think\facade\Db;

/**
 * 意见反馈
 */
class Feedback extends BaseModel
{
    public $page_info;

    /**
     * 列表
     * @access public
     * @author csdeshang
     * @param array $condition 查询条件
     * @param int $pagesize 分页数
     * @param string $order 排序
     * @return array
     */
    public function getFeedbackList($condition, $pagesize = 10, $order = 'fb_id desc')
    {
        $list = Db::name('feedback')->where($condition)->order($order)->paginate(['list_rows' => $pagesize, 'query' => request()->param()], false);
        $this->page_info = $list;
        return $list;
    }

    /**
     * 新增
     * @access public
     * @author csdeshang
     * @param array $data 参数内容
     * @return bool 布尔类型的返回结果
     */
    public function addFeedback($data)
    {
        return Db::name('feedback')->insertGetId($data);
    }

    /**
     * 删除
     * @access public
     * @author csdeshang
     * @param int $condition 条件
     * @return bool 布尔类型的返回结果
     */
    public function delFeedback($condition)
    {
        return Db::name('feedback')->where($condition)->delete();
    }
}
