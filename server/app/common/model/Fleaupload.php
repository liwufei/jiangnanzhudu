<?php

namespace app\common\model;

use think\facade\Db;

class Fleaupload extends BaseModel
{
    public $page_info;

    /**
     * 取单个内容
     * @access public
     * @author csdeshang
     * @param int $id 分类ID
     * @return array 数组类型的返回结果
     */
    public function getOneFleaupload($id)
    {
        $result = Db::name('fleaupload')->where(array('fleaupload_id' => intval($id)))->find();
        return $result;
    }

    /**
     * 新增
     * @access public
     * @author csdeshang
     * @param array $data 参数内容
     * @return bool 布尔类型的返回结果
     */
    public function addFleaupload($data)
    {
        if (empty($data)) {
            return false;
        }
        if (is_array($data)) {

            $result = Db::name('fleaupload')->insertGetId($data);
            return $result;
        } else {
            return false;
        }
    }

    /**
     * 更新信息
     * @access public
     * @author csdeshang
     * @param array $data 更新数据
     * @param array $condition 条件数组
     * @return bool 布尔类型的返回结果
     */
    public function editFleaupload($data, $condition)
    {
        $result = Db::name('fleaupload')->where($condition)->update($data);
        return $result;
    }

    /**
     * 删除图片信息，根据where
     * @access public
     * @author csdeshang
     * @param array $condition 条件数组
     * @return bool 布尔类型的返回结果
     */
    public function delFleaupload($condition, $store_id)
    {
        if (empty($condition)) {
            return false;
        }
        $image_more = Db::name('fleaupload')->where($condition)->field('fleafile_name')->select()->toArray();
        if (is_array($image_more) && !empty($image_more)) {
            foreach ($image_more as $v) {
                @unlink(BASE_UPLOAD_PATH . DIRECTORY_SEPARATOR . ATTACH_MFLEA . DIRECTORY_SEPARATOR . $store_id . DIRECTORY_SEPARATOR . $v['fleafile_name']);
            }
        }
        $state = Db::name('fleaupload')->where($condition)->delete();
        return $state;
    }
}
