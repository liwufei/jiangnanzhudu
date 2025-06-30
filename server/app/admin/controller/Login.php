<?php

namespace app\admin\controller;

use think\facade\View;
use think\facade\Lang;
use think\captcha\facade\Captcha;

/**
 * 登录
 */
class Login extends AdminControl
{

    public function initialize()
    {
        parent::initialize();
        Lang::load(base_path() . 'admin/lang/' . config('lang.default_lang') . '/login.lang.php');
    }

    public function index()
    {
        if (session('admin_id')) {
            $this->success(lang('already_logged'), 'Index/index');
        }
        if (request()->isPost()) {
            $admin_name = input('post.admin_name');
            $admin_password = input('post.admin_password');
            $captcha = input('post.captcha');

            $data = array(
                'admin_name' => $admin_name,
                'admin_password' => $admin_password,
                'captcha' => $captcha,
            );

            //验证器
            $this->validate($data, 'app\common\validate\Admin.index');

            if (!captcha_check(input('post.captcha'))) {
                //验证失败
                ds_json_encode(10001, lang('wrong_checkcode'));
            }
            $condition = array();
            $condition[] = array('admin_name', '=', $admin_name);
            $condition[] = array('admin_password', '=', md5($admin_password));
            $admin_mod = model('admin');
            $admin_info = $admin_mod->getOneAdmin($condition);

            if (is_array($admin_info) and !empty($admin_info)) {
                //更新 admin 最新信息
                $update_info = array(
                    'admin_login_num' => ($admin_info['admin_login_num'] + 1),
                    'admin_login_time' => TIMESTAMP
                );
                $admin_mod->editAdmin($update_info, $admin_info['admin_id']);

                //设置 session
                session('admin_id', $admin_info['admin_id']);
                session('admin_name', $admin_info['admin_name']);
                session('admin_gid', $admin_info['admin_gid']);
                session('admin_is_super', $admin_info['admin_is_super']);
                ds_json_encode(10000, lang('login_succ'), '', '', false);
            } else {
                ds_json_encode(10001, lang('login_error'));
            }
        } else {
            return View::fetch();
        }
    }

    public function logout()
    {
        //设置 session
        session(null);
        ds_json_encode(10000, lang('logout_succ'), '', '', false);
    }

    /**
     * 产生验证码
     */
    public function makecode()
    {
        $config = [
            'fontSize' => 20, // // 验证码字体大小
            'length' => 4, // 验证码位数
            'useNoise' => false, //是否添加杂点
            'useCurve' => true,
            'imageH' => 50, //高度
            'imageW' => 150,
        ];
        config($config, 'captcha');
        $captcha = Captcha::create();
        return $captcha;
    }
}
