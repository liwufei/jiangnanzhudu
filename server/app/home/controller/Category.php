<?php

namespace app\home\controller;

use think\facade\View;
use think\facade\Lang;

class Category extends BaseMall
{

    public function initialize()
    {
        parent::initialize();
        Lang::load(base_path() . 'home/lang/' . config('lang.default_lang') . '/category.lang.php');
    }

    /**
     * 显示商品分类列表
     */
    public function goods()
    {
        //获取全部的商品分类
        //导航
        $nav_link = array(
            '0' => array('title' => lang('homepage'), 'link' => HOME_SITE_URL),
            '1' => array('title' => lang('category_index_goods_class'))
        );
        View::assign('nav_link_list', $nav_link);

        View::assign('html_title', config('ds_config.site_name') . ' - ' . lang('category_index_goods_class'));
        return View::fetch($this->template_dir . 'goods_category');
    }

    /**
     * 显示店铺分类列表
     */
    public function store()
    {
        //导航
        $nav_link = array(
            '0' => array('title' => lang('homepage'), 'link' => HOME_SITE_URL),
            '1' => array('title' => lang('category_index_store_class'))
        );
        View::assign('nav_link_list', $nav_link);

        $sc_list = model('storeclass')->getStoreclassList();
        View::assign('sc_list', $sc_list);
        return View::fetch($this->template_dir . 'store_category');
    }
}
