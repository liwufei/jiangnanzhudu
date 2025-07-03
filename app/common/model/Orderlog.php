<?php

namespace app\common\model;

use think\facade\Db;

/**
 * 订单日志
 */
class Orderlog extends BaseModel
{

    /**
     * 添加订单日志
     * @access public
     * @author csdeshang
     * @param type $data 数据信息
     * @return type
     */
    public function addOrderlog($data)
    {
        $data['log_type'] = 'order';
        $data['log_role'] = str_replace(array('buyer', 'seller', 'system', 'admin'), array('买家', '商家', '系统', '管理员'), $data['log_role']);
        $data['log_time'] = TIMESTAMP;
        return Db::name('orderlog')->insertGetId($data);
    }

    //添加跑腿订单日志
    public function addErrandOrderlog($data)
    {
        $data['log_type'] = 'errand';
        $data['log_role'] = str_replace(array('buyer', 'seller', 'system', 'admin'), array('买家', '商家', '系统', '管理员'), $data['log_role']);
        $data['log_time'] = TIMESTAMP;
        return Db::name('orderlog')->insertGetId($data);
    }

    //添加虚拟订单日志
    public function addVrOrderlog($data)
    {
        $data['log_type'] = 'vrorder';
        $data['log_role'] = str_replace(array('buyer', 'seller', 'system', 'admin'), array('买家', '商家', '系统', '管理员'), $data['log_role']);
        $data['log_time'] = TIMESTAMP;
        return Db::name('orderlog')->insertGetId($data);
    }

    //添加虚拟订单日志
    public function addFuwuOrderlog($data)
    {
        $data['log_type'] = 'fuwu';
        $data['log_role'] = str_replace(array('buyer', 'seller', 'system', 'admin'), array('买家', '商家', '系统', '管理员'), $data['log_role']);
        $data['log_time'] = TIMESTAMP;
        return Db::name('orderlog')->insertGetId($data);
    }

    /**
     * 订单操作历史列表
     * @access public
     * @author csdeshang
     * @param type $condition 条件
     * @return Ambigous <multitype:, unknown>
     */
    public function getOrderlogList($condition)
    {
        return Db::name('orderlog')->where($condition)->select()->toArray();
    }

    /**
     * 取得单条订单操作记录
     * @access public
     * @author csdeshang
     * @param array $condition 条件     
     * @param string $order 排序
     * @return array
     */
    public function getOrderlogInfo($condition = array(), $order = '')
    {
        return Db::name('orderlog')->where($condition)->order($order)->find();
    }
}
