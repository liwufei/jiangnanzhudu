<?php

namespace app\admin\controller;

use think\facade\View;
use think\facade\Lang;

/**
 * 到货通知
 */
class Arrivalnotice extends AdminControl
{

    public function initialize()
    {
        parent::initialize();
        Lang::load(base_path() . 'admin/lang/' . config('lang.default_lang') . '/arrivalnotice.lang.php');
    }

    /**
     * 到货通知列表
     */
    public function index()
    {
        $arrivalnotice_model = model('arrivalnotice');
        $condition = array();
        if (!empty(input('param.search_goods'))) {
            $condition[] = array('goods_name', 'like', '%' . input('param.search_goods') . '%');
        }
        if (!empty(input('param.search_state'))) {
            $condition[] = array('arrivalnotice_state', '=', input('param.search_state'));
        }
        $arrivalnotice_list = $arrivalnotice_model->getArrivalNoticeList($condition, '', '', '', 5);
        foreach ($arrivalnotice_list as $key => $value) {
            $arrivalnotice_list[$key]['member_name'] = model('member')->getMemberInfo(['member_id' => $value['member_id']], 'member_name')['member_name'];
        }

        View::assign('arrivalnotice_list', $arrivalnotice_list);
        View::assign('show_page', $arrivalnotice_model->page_info->render());
        $this->setAdminCurItem('index');
        View::assign('filtered', $condition ? 1 : 0); //是否有查询条件
        return View::fetch();
    }

    /**
     * 到货通知删除
     */
    public function arrivalnotice_del()
    {
        $arrivalnotice_id = input('param.arrivalnotice_id');
        $arrivalnotice_id_array = ds_delete_param($arrivalnotice_id);
        if ($arrivalnotice_id_array == FALSE) {
            ds_json_encode('10001', lang('param_error'));
        }
        $condition = array();
        $condition[] = array('arrivalnotice_id', 'in', $arrivalnotice_id_array);
        $arrivalnotice_model = model('arrivalnotice');
        //批量删除
        $result = $arrivalnotice_model->delArrivalNotice($condition);
        if ($result) {
            ds_json_encode(10000, lang('ds_common_del_succ'));
        } else {
            ds_json_encode(10001, lang('ds_common_del_fail'));
        }
    }
}
