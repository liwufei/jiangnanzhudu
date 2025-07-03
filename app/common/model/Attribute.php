<?php

namespace app\common\model;

use think\facade\Db;

/**
 * 属性
 */
class Attribute extends BaseModel
{

    const SHOW0 = 0;    // 不显示
    const SHOW1 = 1;    // 显示

    /**
     * 属性列表
     * @access public
     * @author csdeshang
     * @param array $condition 检索条件
     * @param string $field 字段
     * @return array
     */
    public function getAttributeList($condition, $field = '*')
    {
        return Db::name('attribute')->where($condition)->field($field)->order('attr_sort asc')->select()->toArray();
    }

    /**
     * 属性列表
     * @access public
     * @author csdeshang
     * @param array $condition 检索条件
     * @param string $field 字段
     * @return array
     */
    public function getAttributeShowList($condition, $field = '*')
    {
        $condition[] = array('attr_show', '=', self::SHOW1);
        return $this->getAttributeList($condition, $field);
    }

    /**
     * 属性值列表
     * @access public
     * @author csdeshang
     * @param array $condition 检索条件
     * @param string $field 字段
     * @return array
     */
    public function getAttributeValueList($condition, $field = '*')
    {
        return Db::name('attributevalue')->where($condition)->field($field)->order('attrvalue_sort asc,attrvalue_id asc')->select()->toArray();
    }

    /**
     * 保存属性值
     * @access public
     * @author csdeshang 
     * @param array $data 参数内容
     * @return boolean
     */
    public function addAttributeValueAll($data)
    {
        return Db::name('attributevalue')->insertAll($data);
    }

    /**
     * 保存属性值
     * @access public
     * @author csdeshang 
     * @param array $data 参数内容
     * @return boolean
     */
    public function addAttributeValue($data)
    {
        return Db::name('attributevalue')->insertGetId($data);
    }

    /**
     * 编辑属性值
     * @access public
     * @author csdeshang
     * @param array $update 更新数据
     * @param array $condition 条件
     * @return boolean
     */
    public function editAttributeValue($update, $condition)
    {
        return Db::name('attributevalue')->where($condition)->update($update);
    }
}
