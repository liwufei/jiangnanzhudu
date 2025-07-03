<?php

namespace app\admin\controller;

use think\facade\View;
use think\facade\Lang;

/**
 * 批发
 */
class Promotionwholesale extends AdminControl
{
    public function initialize()
    {
        parent::initialize();
        Lang::load(base_path() . 'admin/lang/' . config('lang.default_lang') . '/promotionwholesale.lang.php');
    }

    /**
     * 活动列表
     **/
    public function index()
    {
        //自动开启批发
        if (intval(input('param.promotion_allow')) === 1) {
            $config_model = model('config');
            $update_array = array();
            $update_array['promotion_allow'] = 1;
            $config_model->editConfig($update_array);
        }

        $wholesale_model = model('wholesale');
        $condition = array();
        if (!empty(input('param.goods_name'))) {
            $condition[] = array('goods_name', 'like', '%' . input('param.goods_name') . '%');
        }
        if (!empty(input('param.store_name'))) {
            $condition[] = array('store_name', 'like', '%' . input('param.store_name') . '%');
        }
        if (!empty(input('param.state'))) {
            $condition[] = array('wholesale_state', '=', intval(input('param.state')));
        }
        $wholesale_list = $wholesale_model->getWholesaleList($condition, 10, 'wholesale_state desc, wholesale_end_time desc');
        View::assign('wholesale_list', $wholesale_list);
        View::assign('show_page', $wholesale_model->page_info->render());
        View::assign('wholesale_state_array', $wholesale_model->getWholesaleStateArray());

        $this->setAdminCurItem('wholesale_list');
        // 输出自营店铺IDS

        View::assign('filtered', $condition ? 1 : 0); //是否有查询条件
        return View::fetch();
    }

    /**
     * 批发活动取消
     **/
    public function wholesale_cancel()
    {
        $wholesale_id = intval(input('param.wholesale_id'));
        $wholesale_model = model('wholesale');
        $result = $wholesale_model->cancelWholesale(array('wholesale_id' => $wholesale_id));
        if ($result) {
            $this->log(lang('ds_cancel') . lang('ds_promotion_wholesale') . 'ID:' . $wholesale_id);
            ds_json_encode(10000, lang('ds_common_op_succ'));
        } else {
            ds_json_encode(10001, lang('ds_common_op_fail'));
        }
    }

    /**
     * 批发活动删除
     **/
    public function wholesale_del()
    {
        $wholesale_model = model('wholesale');
        $wholesale_id = input('param.wholesale_id');
        $wholesale_id_array = ds_delete_param($wholesale_id);
        if ($wholesale_id_array === FALSE) {
            ds_json_encode(10001, lang('param_error'));
        }
        $condition = array(array('wholesale_id', 'in', $wholesale_id_array));
        $result = $wholesale_model->delWholesale($condition);
        if ($result) {
            $this->log(lang('ds_del') . lang('ds_promotion_wholesale') . 'ID:' . $wholesale_id);
            ds_json_encode(10000, lang('ds_common_op_succ'));
        } else {
            ds_json_encode(10001, lang('ds_common_op_fail'));
        }
    }

    /**
     * 活动详细信息
     **/
    public function wholesale_detail()
    {
        $wholesale_id = intval(input('param.wholesale_id'));

        $wholesale_model = model('wholesale');
        $wholesalegoods_model = model('wholesalegoods');

        $wholesale_info = $wholesale_model->getWholesaleInfoByID($wholesale_id);
        if (empty($wholesale_info)) {
            $this->error(lang('param_error'));
        }
        View::assign('wholesale_info', $wholesale_info);

        //获取批发商品列表
        $condition = array();
        $condition[] = array('wholesale_id', '=', $wholesale_id);
        $wholesalegoods_list = $wholesalegoods_model->getWholesalegoodsExtendList($condition, 5);
        View::assign('wholesalegoods_list', $wholesalegoods_list);
        View::assign('show_page', $wholesalegoods_model->page_info->render());
        return View::fetch();
    }

    /**
     * 套餐管理
     **/
    public function wholesale_quota()
    {
        $wholesalequota_model = model('wholesalequota');

        $condition = array();
        $condition[] = array('store_name', 'like', '%' . input('param.store_name') . '%');
        $wholesalequota_list = $wholesalequota_model->getWholesalequotaList($condition, 10, 'wholesalequota_endtime desc');
        View::assign('wholesalequota_list', $wholesalequota_list);
        View::assign('show_page', $wholesalequota_model->page_info->render());

        $this->setAdminCurItem('wholesale_quota');
        return View::fetch();
    }

    /**
     * 设置
     **/
    public function wholesale_setting()
    {
        if (!(request()->isPost())) {
            $setting = rkcache('config', true);
            View::assign('setting', $setting);
            return View::fetch();
        } else {
            $promotion_wholesale_price = intval(input('post.promotion_wholesale_price'));
            if ($promotion_wholesale_price < 0) {
                $this->error(lang('param_error'));
            }

            $config_model = model('config');
            $update_array = array();
            $update_array['promotion_wholesale_price'] = $promotion_wholesale_price;

            $result = $config_model->editConfig($update_array);
            if ($result) {
                $this->log('修改批发价格为' . $promotion_wholesale_price . '元');
                dsLayerOpenSuccess(lang('setting_save_success'));
            } else {
                $this->error(lang('setting_save_fail'));
            }
        }
    }

    /**
     * ajax修改抢购信息
     */
    public function ajax()
    {
        $result = true;
        $update_array = array();
        $condition = array();

        switch (input('param.branch')) {
            case 'recommend':
                $wholesalegoods_model = model('wholesalegoods');
                $update_array['wholesalegoods_recommend'] = input('param.value');
                $condition[] = array('wholesalegoods_id', '=', input('param.id'));
                $result = $wholesalegoods_model->editWholesalegoods($update_array, $condition);
                break;
        }

        if ($result) {
            echo 'true';
            exit;
        } else {
            echo 'false';
            exit;
        }
    }

    /**
     * 发送消息
     */
    private function send_message($member_id, $member_name, $message)
    {
        $param = array();
        $param['from_member_id'] = 0;
        $param['member_id'] = $member_id;
        $param['to_member_name'] = $member_name;
        $param['message_type'] = '1'; //表示为系统消息
        $param['msg_content'] = $message;
        $message_model = model('message');
        return $message_model->addMessage($param);
    }

    /**
     * 页面内导航菜单
     */
    protected function getAdminItemList()
    {
        $menu_array = array(
            array(
                'name' => 'wholesale_list',
                'text' => lang('wholesale_list'),
                'url' => (string)url('Promotionwholesale/index')
            ),
            array(
                'name' => 'wholesale_quota',
                'text' => lang('wholesale_quota'),
                'url' => (string)url('Promotionwholesale/wholesale_quota')
            ),
            array(
                'name' => 'wholesale_setting',
                'text' => lang('wholesale_setting'),
                'url' => "javascript:dsLayerOpen('" . (string)url('Promotionwholesale/wholesale_setting') . "','" . lang('wholesale_setting') . "')"
            ),
        );
        if (request()->action() == 'wholesale_detail')
            $menu_array[] = array(
                'name' => 'wholesale_detail',
                'text' => lang('wholesale_detail'),
                'url' => (string)url('Promotionwholesale/wholesale_detail')
            );
        return $menu_array;
    }
}
