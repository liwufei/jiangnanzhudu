<?php

namespace app\home\controller;

use think\facade\View;
use think\facade\Lang;

class Showpintuan extends BaseMall
{

    public function initialize()
    {
        parent::initialize();
        Lang::load(base_path() . 'home/lang/' . config('lang.default_lang') . '/pintuan.lang.php');
    }

    /**
     * 拼团列表页
     */
    public function index()
    {
        $ppintuan_model = model('ppintuan');
        $condition = array(
            array('pintuan_state', '=', 1),
            array('pintuan_starttime', '<', TIMESTAMP),
            array('pintuan_end_time', '>', TIMESTAMP),
        );
        $cache_key = 'pintuan' . md5(serialize($condition)) . '-' . intval(input('param.page'));
        $result = rcache($cache_key);
        if (empty($result)) {
            $pintuan_list = $ppintuan_model->getPintuanList($condition, 10, 'pintuan_state desc, pintuan_end_time desc');
            foreach ($pintuan_list as $key => $pintuan) {
                $pintuan_list[$key]['pintuan_image'] = goods_cthumb($pintuan['pintuan_image'], 240);
                $pintuan_list[$key]['pintuan_zhe_price'] = round($pintuan['pintuan_goods_price'] * $pintuan['pintuan_zhe'] / 10, 2);
                $pintuan_list[$key]['pintuan_url'] = urlencode(config('ds_config.h5_site_url') . "/pages/home/goodsdetail/Goodsdetail?goods_id=" . $pintuan['pintuan_goods_id'] . "&pintuan_id=" . $pintuan['pintuan_id']);
            }
            $result['pintuan_list'] = $pintuan_list;
            $result['show_page'] = $ppintuan_model->page_info->render();
            wcache($cache_key, $result);
        }
        // halt($result['pintuan_list']);
        View::assign('pintuan_list', $result['pintuan_list']);
        View::assign('show_page', $result['show_page']);
        // 当前位置导航
        View::assign('nav_link_list', array(array('title' => lang('homepage'), 'link' => (string) url('home/Index/index')), array('title' => lang('pintuan_list'))));
        //SEO 设置
        $seo = array(
            'html_title' => config('ds_config.site_name') . '-' . lang('pintuan_list'),
            'seo_keywords' => lang('pintuan_list'),
            'seo_description' => lang('pintuan_list'),
        );
        $this->_assign_seo($seo);

        return View::fetch($this->template_dir . 'index');
    }
}
