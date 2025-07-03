<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace frontend\mob\widgets\limitbuy;

use Yii;

use common\models\LimitbuyModel;

use common\library\Basewind;
use common\library\Timezone;
use common\library\Plugin;

use common\base\BaseWidget;

/**
 * @Id main.widget.php 2018.9.13 $
 * @author mosir
 */

class LimitbuyWidget extends BaseWidget
{
    var $name = 'limitbuy';

    public function getData()
    {
        // 初始值
        $this->options = array_merge(
            [
                'source' => '',
                'quantity' => 3,
            ],
            $this->options
        );

        $query = LimitbuyModel::find()->alias('lb')->select('g.goods_id,g.goods_name,g.default_image,g.price,g.default_spec as spec_id')
            ->joinWith('goods g', false, 'INNER JOIN')
            ->joinWith('store s', false)
            ->where(['and', ['s.state' => 1, 'g.if_show' => 1, 'g.closed' => 0], ['<=', 'lb.start_time', Timezone::gmtime()], ['>=', 'lb.end_time', Timezone::gmtime()]])
            ->orderBy(['id' => SORT_DESC]);

        if ($this->options['source'] == 'choice') {
            $query->andWhere(['in', 'g.goods_id', explode(',', $this->options['items'])]);
        } else {
            $query->limit($this->options['quantity'] > 0 ? $this->options['quantity'] : 3);
        }

        if (empty($list = $query->asArray()->all())) {
            $list = array([], [], []);
        }

        $client = Plugin::getInstance('promote')->build();
        foreach ($list as $key => $value) {
            if (!empty($value)) {
                $list[$key]['promotion'] = $client->getProInfo($value['goods_id'], $value['spec_id']);
            }
        }

        return array_merge(['list' => $list], $this->options);
    }

    public function parseConfig($input)
    {
        return $input;
    }
}
