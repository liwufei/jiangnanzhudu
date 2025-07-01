<?php

namespace app\common\model;

use think\facade\Db;

/**
 * 邮件模板
 */
class Mailtemplates extends BaseModel
{

    /**
     * 取单条信息
     * @access public
     * @author csdeshang
     * @param array $condition 条件
     * @param string $fields 字段
     */
    public function getTplInfo($condition = array(), $fields = '*')
    {
        return Db::name('mailmsgtemlates')->where($condition)->field($fields)->find();
    }

    /**
     * 模板列表
     * @access public
     * @author csdeshang
     * @param array $condition 检索条件
     * @return array 数组形式的返回结果
     */
    public function getTplList($condition = array())
    {
        return Db::name('mailmsgtemlates')->where($condition)->select()->toArray();
    }

    /**
     * 编辑模板
     * @access public
     * @author csdeshang
     * @param type $data 参数内容
     * @param type $condition 条件
     * @return type
     */
    public function editTpl($data = array(), $condition = array())
    {
        return Db::name('mailmsgtemlates')->where($condition)->update($data);
    }
}
