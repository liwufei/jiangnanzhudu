<?php

namespace app\common\model;

use think\facade\Db;

/**
 * 邮件计划
 */
class Mailcron extends BaseModel
{

    /**
     * 新增商家消息任务计划
     * @access public
     * @author csdeshang
     * @param array $insert 插入数据
     */
    public function addMailCron($insert)
    {
        return Db::name('mailcron')->insertGetId($insert);
    }

    /**
     * 查看商家消息任务计划
     * @access public
     * @author csdeshang
     * @param array $condition 条件
     * @param int $limit 限制
     * @param string $order 排序
     * @return array
     */
    public function getMailCronList($condition, $limit = 0, $order = 'mailcron_id asc')
    {
        return Db::name('mailcron')->where($condition)->limit($limit)->order($order)->select()->toArray();
    }

    /**
     * 删除商家消息任务计划
     * @access public
     * @author csdeshang
     * @param array $condition 条件
     * @return bool
     */
    public function delMailCron($condition)
    {
        return Db::name('mailcron')->where($condition)->delete();
    }
}
