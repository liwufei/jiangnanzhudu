<?php

namespace app\home\controller;

/**
 * 商品
 */
class BaseGoods extends BaseStore
{

    protected $store_info;

    public function initialize()
    {
        parent::initialize();
        //输出会员信息
        $this->getMemberAndGradeInfo(false);
    }

    protected function getStoreInfo($store_id, $goods_info = null)
    {
        $store_model = model('store');
        $store_info = $store_model->getStoreOnlineInfoByID($store_id);
        if (empty($store_info)) {
            $this->error(lang('ds_store_close'));
        }
        if (cookie('dregion')) {
            $store_info['deliver_region'] = cookie('dregion');
        }

        if ($store_info['deliver_region'] && strpos($store_info['deliver_region'], '|')) {
            $store_info['deliver_region'] = explode('|', $store_info['deliver_region']);
            $store_info['deliver_region_ids'] = explode(' ', $store_info['deliver_region'][0]);
            $store_info['deliver_region_names'] = explode(' ', $store_info['deliver_region'][1]);
        }

        $storejoinin_model = model('storejoinin');
        $storejoinin_info = $storejoinin_model->getOneStorejoinin(array('member_id' => $store_info['member_id']));
        //营业执照
        if ($storejoinin_info) {
            $store_info['business_licence_number_electronic'] = ($storejoinin_info['business_licence_number_electronic'] && $storejoinin_info['store_type'] == 0) ? get_store_joinin_imageurl($storejoinin_info['business_licence_number_electronic']) : '';
        }
        $this->outputStoreInfo($store_info, $goods_info);
    }
}
