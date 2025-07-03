<?php

namespace app\common\model;

use think\facade\Db;

/**
 * 商品分类导航
 */
class Goodsclassnav extends BaseModel
{

    /**
     * 根据商品分类id取得数据
     * @access public
     * @author csdeshang
     * @param num $gc_id 分类ID
     * @return array
     */
    public function getGoodsclassnavInfoByGcId($gc_id)
    {
        return Db::name('goodsclassnav')->where(array('gc_id' => $gc_id))->find();
    }

    /**
     * 保存分类导航设置
     * @access public
     * @author csdeshang
     * @param type $data 更新数据
     * @return type
     */
    public function addGoodsclassnav($data)
    {
        return Db::name('goodsclassnav')->insert($data);
    }

    /**
     * 编辑存分类导航设置
     * @access public
     * @author csdeshang
     * @param array $update 更新数据
     * @param int $gc_id 分类id
     * @return boolean
     */
    public function editGoodsclassnav($update, $gc_id)
    {
        return Db::name('goodsclassnav')->where(array('gc_id' => $gc_id))->update($update);
    }
}
