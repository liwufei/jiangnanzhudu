<?php

namespace app\home\controller;

use think\facade\View;
use think\facade\Lang;

class Document extends BaseMall
{

    public function initialize()
    {
        parent::initialize();
        Lang::load(base_path() . 'home/lang/' . config('lang.default_lang') . '/index.lang.php');
    }

    public function index()
    {
        $code = input('param.code');

        if ($code == '') {
            $this->error(lang('param_error')); //'缺少参数:文章标识'
        }
        $document_model = model('document');
        $doc = $document_model->getOneDocumentByCode($code);
        View::assign('doc', $doc);

        // 分类导航
        $nav_link = array(
            array(
                'title' => lang('homepage'),
                'link' => HOME_SITE_URL
            ),
            array(
                'title' => $doc['document_title']
            )
        );

        View::assign('nav_link_list', $nav_link);
        return View::fetch($this->template_dir . 'index');
    }
}
