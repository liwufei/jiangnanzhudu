<?php

namespace app\home\controller;

use think\facade\View;
use think\facade\Lang;

class SellerResource extends BaseSeller
{

    public function initialize()
    {
        parent::initialize();
        Lang::load(base_path() . 'home/lang/' . config('lang.default_lang') . '/seller_resource.lang.php');
    }

    /**
     * 资源删除
     */
    public function del_resource()
    {
        $return_json = input('param.return_json'); //是否为json 返回
        $file_name = input('param.file_name');
        $ids = input('param.id/a');
        if (empty($file_name) && empty($ids)) {
            ds_json_encode(10001, lang('param_error'));
        }
        $goods_resource_model = model('goods_resource');
        //删除资源
        $condition = array();
        if ($ids) {
            $condition[] = array('goods_resource_id', 'in', $ids);
        }
        if ($file_name) {
            $condition[] = array('file_name', '=', $file_name);
        }
        $condition[] = array('store_id', '=', session('store_id'));
        $return = $goods_resource_model->delGoodsResource($condition, session('store_id'));
        if ($return) {
            ds_json_encode(10000, lang('ds_common_op_succ'));
        } else {
            ds_json_encode(10001, lang('ds_common_op_fail'));
        }
    }

    /**
     * 上传资源
     */
    public function resource_upload()
    {
        $store_id = session('store_id');
        $save_name = $store_id . '_' . date('YmdHis') . rand(10000, 99999) . '.zip';

        $file_name = 'file';
        $upload_path = ATTACH_GOODS_RESOURCE . DIRECTORY_SEPARATOR . $store_id;

        $res = ds_upload_pic($upload_path, $file_name, $save_name, 'zip');
        if ($res['code']) {
            $save_name = $res['data']['file_name'];
            $data = array();
            $data['url'] = goods_resource($save_name);
            $data['name'] = $save_name;

            $goods_resource_model = model('goods_resource');
            $goods_resource_model->addGoodsResource(array(
                'store_id' => $store_id,
                'store_name' => session('store_name'),
                'file_name' => $save_name,
                'file_size' => $_FILES[$file_name]['size'],
                'goods_resource_name' => $_FILES[$file_name]['name'],
                'goods_resource_add_time' => TIMESTAMP
            ));
            // 整理为json格式
            ds_json_encode(10000, lang('ds_common_op_succ'), $data);
        } else {
            ds_json_encode(10001, $res['msg']);
        }
    }
}
