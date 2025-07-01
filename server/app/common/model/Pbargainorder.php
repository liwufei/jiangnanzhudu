<?php

namespace app\common\model;

use think\facade\Db;

/**
 * 砍价订单
 */
class Pbargainorder extends BaseModel
{

    public $page_info;
    public $lock = false;
    const PINTUANORDER_STATE_CLOSE = 0;
    const PINTUANORDER_STATE_NORMAL = 1;
    const PINTUANORDER_STATE_SUCCESS = 2;
    const PINTUANORDER_STATE_FAIL = 3;

    private $bargainorder_state_array = array(
        self::PINTUANORDER_STATE_CLOSE => '砍价取消',
        self::PINTUANORDER_STATE_NORMAL => '砍价中',
        self::PINTUANORDER_STATE_SUCCESS => '砍价成功',
        self::PINTUANORDER_STATE_FAIL => '砍价失败'
    );

    /**
     * 获取砍价订单表列表
     * @access public
     * @author csdeshang
     * @param type $condition 条件
     * @return type
     */
    public function getPbargainorderList($condition, $pagesize = '')
    {
        $res = Db::name('pbargainorder')->where($condition)->order('bargainorder_id desc');
        if ($pagesize) {
            $res = $res->paginate(['list_rows' => $pagesize, 'query' => request()->param()], false);
            $pbargainorder_list = $res->items();
            $this->page_info = $res;
        } else {
            $pbargainorder_list = $res->select()->toArray();
        }
        return $pbargainorder_list;
    }

    /**
     * 获取砍价订单表列表
     * @access public
     * @author csdeshang
     * @param type $condition 条件
     * @return type
     */
    public function getOnePbargainorder($condition)
    {
        return Db::name('pbargainorder')->where($condition)->find();
    }

    /**
     * 获取砍价订单表数量
     * @access public
     * @author csdeshang
     * @param type $condition 条件
     * @return type
     */
    public function getPbargainorderCount($condition)
    {
        return Db::name('pbargainorder')->where($condition)->count();
    }

    /**
     * 增加砍价订单
     * @access public
     * @author csdeshang
     * @param type $data 参数内容
     * @return type
     */
    public function addPbargainorder($data)
    {
        return Db::name('pbargainorder')->insertGetId($data);
    }

    /**
     * 编辑砍价订单
     * @access public
     * @author csdeshang
     * @param type $condition 条件
     * @param type $data 数据
     * @return type
     */
    public function editPbargainorder($condition, $data)
    {
        return Db::name('pbargainorder')->where($condition)->update($data);
    }

    /**
     * 砍价状态数组
     * @access public
     * @author csdeshang
     * @return type
     */
    public function getBargainorderStateArray()
    {
        return $this->bargainorder_state_array;
    }
}
