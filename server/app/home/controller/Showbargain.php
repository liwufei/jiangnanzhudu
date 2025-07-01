<?php

namespace app\home\controller;

use think\facade\View;
use think\facade\Lang;

class Showbargain extends BaseMall
{
    public function initialize()
    {
        parent::initialize();
        Lang::load(base_path() . 'home/lang/' . config('lang.default_lang') . '/bargain.lang.php');
    }

    /**
     * 砍价列表页
     */
    public function index()
    {
        $pbargain_model = model('pbargain');
        $condition = array();
        $cache_key = 'bargain' . md5(serialize($condition)) . '-' . intval(input('param.page'));
        $result = rcache($cache_key);
        if (empty($result)) {
            $bargain_list = $pbargain_model->getOnlineBargainList($condition, 12);
            foreach ($bargain_list as $key => $bargain) {
                $bargain_list[$key]['bargain_goods_image_url'] = goods_cthumb($bargain['bargain_goods_image'], 480, $bargain['store_id']);
                $bargain_list[$key]['bargain_url'] = urlencode(config('ds_config.h5_site_url') . "/pages/home/goodsdetail/Goodsdetail?goods_id=" . $bargain['bargain_goods_id'] . "&bargain_id=" . $bargain['bargain_id']);
            }
            $result['bargain_list'] = $bargain_list;
            $result['show_page'] = $pbargain_model->page_info->render();
            wcache($cache_key, $result);
        }
        // halt($result['bargain_list']);
        View::assign('bargain_list', $result['bargain_list']);
        View::assign('show_page', $result['show_page']);
        // 当前位置导航
        View::assign('nav_link_list', array(array('title' => lang('homepage'), 'link' => (string)url('home/Index/index')), array('title' => lang('bargain_list'))));
        //SEO 设置
        $seo = array(
            'html_title' => config('ds_config.site_name') . '-' . lang('bargain_list'),
            'seo_keywords' => lang('bargain_list'),
            'seo_description' => lang('bargain_list'),
        );
        $this->_assign_seo($seo);

        return View::fetch($this->template_dir . 'index');
    }
}
