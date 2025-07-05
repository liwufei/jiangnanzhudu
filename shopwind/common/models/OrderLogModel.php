<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace common\models;

use Yii;
use yii\db\ActiveRecord;

use common\library\Language;
use common\library\Timezone;
use common\library\Def;

/**
 * @Id OrderLogModel.php 2018.4.23 $
 * @author mosir
 */


class OrderLogModel extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%order_log}}';
    }

    /**
     * 插入订单新日志
     */
    public static function create($order_id, $status, $remark = '', $operator = '')
    {
        $operator = $operator ? $operator : Language::get('system');
        $status = is_int($status) ? Def::getOrderStatus($status) : $status;

        // 同城配送下，考虑物流状态回转
        $model = new OrderLogModel();
        $model->order_id = $order_id;
        $model->operator = $operator;
        $model->status = $status;
        $model->remark = $remark;
        $model->created = Timezone::gmtime();

        return $model->save();
    }

    /**
     * 不插入新记录，而是将上一次的订单日志修改为当前状态值
     * 如：待付款-》已付款
     */
    public static function change($order_id, $status, $remark = '')
    {
        if (is_int($status)) $status = Def::getOrderStatus($status);
        if ($model = parent::find()->where(['order_id' => $order_id])->orderBy(['id' => SORT_DESC])->one()) {
            $model->status = $status;
            $model->remark = $remark;
            $model->created = Timezone::gmtime();
            return $model->save();
        }
        return false;
    }
}
