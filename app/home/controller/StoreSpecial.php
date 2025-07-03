<?php

namespace app\home\controller;

use think\facade\View;
use think\facade\Lang;

class StoreSpecial extends BaseStore
{

    public function initialize()
    {
        parent::initialize();
        Lang::load(base_path() . 'home/lang/' . config('lang.default_lang') . '/seller_editable_page.lang.php');
    }

    public function index()
    {
        $editable_page_id = intval(input('param.special_id'));

        $editable_page_model = model('editable_page');
        $editable_page_info = $editable_page_model->getOneEditablePage(array('editable_page_id' => $editable_page_id));
        if (!$editable_page_info) {
            $this->error(lang('param_error'));
        }
        $editable_page_info['editable_page_theme_config'] = json_decode($editable_page_info['editable_page_theme_config'], true);
        View::assign('editable_page', $editable_page_info);
        $editable_page_config_model = model('editable_page_config');
        $editable_page_config_list = $editable_page_config_model->getEditablePageConfigList(array(array('editable_page_id', '=', $editable_page_id)));
        $config_list = array();
        foreach ($editable_page_config_list as $key => $val) {
            $config_info = json_decode($val['editable_page_config_content'], true);
            $model_id = $val['editable_page_model_id'];
            $var_html = array();
            if (!empty($config_info)) {
                require_once PLUGINS_PATH . '/editable_page_model/' . $model_id . '/config.php';
                $model_name = 'Model' . $model_id;
                $model = new $model_name();
                $res = $model->filterData($config_info);
                if ($res['code']) {
                    $res = $model->formatData(json_encode($res['data']), $this->store_info['store_id']);
                    if ($res['code']) {
                        $var_html['config_info'] = $res['data'];
                    }
                }
            }
            $html = View::fetch('../../../plugins/editable_page_model/' . $model_id . '/index', $var_html);
            $config_list[] = array(
                'val' => $val,
                'html' => $html,
            );
        }
        View::assign('config_list', $config_list);
        return View::fetch($this->template_dir . 'index');
    }
}
