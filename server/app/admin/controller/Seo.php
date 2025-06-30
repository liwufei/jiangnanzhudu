<?php

namespace app\admin\controller;

use think\facade\View;
use think\facade\Lang;
use think\facade\Db;

/**
 * SEO
 */
class Seo extends AdminControl
{

    public function initialize()
    {
        parent::initialize();
        Lang::load(base_path() . 'admin/lang/' . config('lang.default_lang') . '/seo.lang.php');
    }

    function index()
    {
        if (!request()->isPost()) {
            //读取SEO信息
            $list = Db::name('seo')->select()->toArray();
            $seo = array();
            foreach ((array) $list as $value) {
                $seo[$value['seo_type']] = $value;
            }
            View::assign('seo', $seo);
            // $category = model('goodsclass')->getGoodsclassForCacheModel();
            // View::assign('category', $category);
            return View::fetch('index');
        } else {
            $update = array();
            $seo = input('post.SEO/a'); #获取数组
            if (is_array($seo)) {
                foreach ($seo as $key => $value) {
                    Db::name('seo')->where(array('seo_type' => $key))->update($value);
                }
                dkcache('seo');
                ds_json_encode('10000', lang('ds_common_save_succ'));
            }
        }
    }
}
