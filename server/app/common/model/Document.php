<?php

namespace app\common\model;

use think\facade\Db;

/**
 * 系统文章
 */
class Document extends BaseModel
{

    /**
     * 查询所有系统文章
     * @access public
     * @author csdeshang 
     * @return type
     */
    public function getDocumentList()
    {
        return Db::name('document')->select()->toArray();
    }

    /**
     * 根据编号查询一条
     * @access public
     * @author csdeshang 
     * @param int $id 文章id
     * @return array
     */
    public function getOneDocumentById($id)
    {
        $condition = array();
        $condition[] = array('document_id', '=', $id);
        return Db::name('document')->where($condition)->find();
    }

    /**
     * 根据标识码查询一条
     * @access public
     * @author csdeshang
     * @param type $code 标识码
     * @return type
     */
    public function getOneDocumentByCode($code)
    {
        $condition = array();
        $condition[] = array('document_code', '=', $code);
        return Db::name('document')->where($condition)->find();
    }

    /**
     * 更新
     * @access public
     * @author csdeshang
     * @param array $data 更新数据
     * @return bool
     */
    public function editDocument($data, $condition)
    {
        return Db::name('document')->where($condition)->update($data);
    }
}
