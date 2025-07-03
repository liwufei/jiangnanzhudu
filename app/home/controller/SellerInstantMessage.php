<?php

namespace app\home\controller;

use think\facade\View;
use think\facade\Lang;
use think\facade\Db;

class SellerInstantMessage extends BaseSeller
{

    public function initialize()
    {
        parent::initialize();
        Lang::load(base_path() . 'home/lang/' . config('lang.default_lang') . '/seller_instant_message.lang.php');
    }

    public function index()
    {
        $instant_message_model = model('instant_message');
        $f_name = trim(input('param.f_name'));
        $t_name = trim(input('param.t_name'));
        $time_add_from = input('param.add_time_from') ? strtotime(input('param.add_time_from')) : '';
        $time_add_to = input('param.add_time_to') ? strtotime(input('param.add_time_to')) : '';

        $condition = array();
        if ($f_name) {
            $condition[] = array('instant_message_from_name', 'like', '%' . $f_name . '%');
        }
        if ($t_name) {
            $condition[] = array('instant_message_to_name', 'like', '%' . $t_name . '%');
        }
        if ($time_add_from) {
            $condition[] = array('instant_message_add_time', '>=', $time_add_from);
        }
        if ($time_add_to) {
            $condition[] = array('instant_message_add_time', '>=', $time_add_to);
        }
        $instant_message_open = input('param.instant_message_open');
        if (in_array($instant_message_open, array('0', '1', '2'))) {
            $condition[] = array('instant_message_open', '=', $instant_message_open);
        }
        $member_id = $this->store_info['member_id'];
        $result = Db::name('InstantMessage')->where(function ($query) use ($member_id) {
            $query->where(function ($query) use ($member_id) {
                $query->where(array(array('instant_message_from_id', '=', $member_id), array('instant_message_from_type', '=', 0)));
            })->whereOr(function ($query) use ($member_id) {
                $query->where(array(array('instant_message_to_id', '=', $member_id), array('instant_message_to_type', '=', 0)));
            });
        })->where($condition)->order('instant_message_id desc')->paginate(['list_rows' => 10, 'query' => request()->param()], false);
        $instant_message_list = $result->items();
        foreach ($instant_message_list as $key => $val) {
            $instant_message_list[$key] = $instant_message_model->formatInstantMessage($val);
        }
        View::assign('instant_message_list', $instant_message_list);
        View::assign('show_page', $result->render());
        View::assign('search', $condition);

        /* 设置卖家当前菜单 */
        $this->setSellerCurMenu('seller_instant_message');
        /* 设置卖家当前栏目 */
        $this->setSellerCurItem('instant_message_list');
        return View::fetch($this->template_dir . 'index');
    }


    /**
     * 栏目菜单
     */
    function getSellerItemList()
    {
        $menu_array[] = array(
            'name' => 'instant_message_list',
            'text' => lang('chat_query'),
            'url' => url('seller_instant_message/index'),
        );

        return $menu_array;
    }
}
