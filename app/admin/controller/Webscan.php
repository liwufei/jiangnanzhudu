<?php

namespace app\admin\controller;

use think\facade\Lang;
use think\facade\Db;

/**
 * 系统安全检测
 */
class Webscan extends AdminControl
{
    public function initialize()
    {
        parent::initialize();
        $this->_prefix = config('database.connections.mysql.prefix');
        Lang::load(base_path() . 'admin/lang/' . config('lang.default_lang') . '/webscan.lang.php');
    }

    public function index()
    {
        $this->scan_member();
    }

    public function scan_member()
    {
        $output = array();
        //检测Member数据表中是否有重复的 用户名  邮箱  手机号
        $result = Db::query("select member_name,count(*) as count from {$this->_prefix}member group by member_name having count>1;");
    }
}
