<?php

namespace app\common\model;

use think\facade\Db;

/**
 * 发票
 */
class Invoice extends BaseModel
{

    /**
     * 取得买家默认发票
     * @access public
     * @author csdeshang
     * @param array $condition 条件数组
     * @return array
     */
    public function getDefaultInvoiceInfo($condition = array())
    {
        return Db::name('invoice')->where($condition)->order('invoice_state asc')->find();
    }

    /**
     * 取得单条发票信息
     * @access public
     * @author csdeshang
     * @param array $condition 查询条件
     */
    public function getInvoiceInfo($condition = array())
    {
        return Db::name('invoice')->where($condition)->find();
    }

    /**
     * 取得发票列表
     * @access public
     * @author csdeshang
     * @param type $condition 条件
     * @param type $limit 限制
     * @param type $field 字段
     * @return type
     */
    public function getInvoiceList($condition, $limit = 0, $field = '*')
    {
        return Db::name('invoice')->field($field)->where($condition)->limit($limit)->select()->toArray();
    }

    /**
     * 删除发票信息
     * @access public
     * @author csdeshang
     * @param array $condition 条件
     * @return bool
     */
    public function delInvoice($condition)
    {
        return Db::name('invoice')->where($condition)->delete();
    }

    /**
     * 新增发票信息
     * @access public
     * @author csdeshang
     * @param array $data 参数内容
     * @return bool
     */
    public function addInvoice($data)
    {
        return Db::name('invoice')->insertGetId($data);
    }

    /**
     * 编辑发票
     * @param type $data
     * @param type $condition
     * @return type
     */
    public function editInvoice($data, $condition)
    {
        return Db::name('invoice')->where($condition)->update($data);
    }
}
