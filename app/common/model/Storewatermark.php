<?php

namespace app\common\model;

use think\facade\Db;

/**
 * 店铺水印
 */
class Storewatermark extends BaseModel
{

    /**
     * 根据店铺id获取水印
     * @access public
     * @author csdeshang
     * @param int $store_id 店铺ID
     * @return type
     */
    public function getOneStorewatermarkByStoreId($store_id)
    {
        $prefix = 'storewatermark_info';

        $cache_info = rcache($store_id, $prefix);
        if (empty($cache_info)) {
            $wm_arr = Db::name('storewatermark')->where('store_id', $store_id)->find();
            $cache = array();
            $cache['wm_arr'] = serialize($wm_arr);
            wcache($store_id, $cache, $prefix, 60 * 24);
        } else {
            $wm_arr = unserialize($cache_info['wm_arr']);
        }
        return $wm_arr;
    }

    /**
     * 新增水印
     * @access public
     * @author csdeshang
     * @param array $data 参数内容
     * @return bool 布尔类型的返回结果
     */
    public function addStorewatermark($data)
    {
        return Db::name('storewatermark')->insertGetId($data);
    }

    /**
     * 更新水印
     * @access public
     * @author csdeshang
     * @param array $data 更新数据
     * @return bool 布尔类型的返回结果
     */
    public function editStorewatermark($wm_id, $data)
    {
        $storewatermark_info = Db::name('storewatermark')->where('swm_id', $wm_id)->find();
        if ($storewatermark_info) {
            //清空缓存
            dcache($storewatermark_info['store_id'], 'storewatermark_info');
        }
        return Db::name('storewatermark')->where('swm_id', $wm_id)->update($data);
    }

    /**
     * 删除水印
     * @access public
     * @author csdeshang
     * @param int $id 记录ID
     * @return bool 布尔类型的返回结果
     */
    public function delStorewatermark($condition)
    {
        return Db::name('storewatermark')->where($condition)->delete();
    }
}
