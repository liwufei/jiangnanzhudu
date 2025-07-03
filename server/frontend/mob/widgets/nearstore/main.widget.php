<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace frontend\mob\widgets\nearstore;

use Yii;

use common\models\StoreModel;
use common\models\ScategoryModel;

use common\base\BaseWidget;

/**
 * @Id main.widget.php 2018.9.13 $
 * @author mosir
 */

class NearstoreWidget extends BaseWidget
{
    var $name = 'nearstore';

    public function getData()
    {
        // 初始值
        $this->options = array_merge(
            [
                'source' => '',
                'paging' => 0,
                'quantity' => 1
            ],
            $this->options
        );

        $query = StoreModel::find()->alias('s')->select('s.store_id,store_name,store_logo')->joinWith('categoryStore cs', false)->where(['state' => 1]);
        if ($this->options['source'] == 'category') {
            $query->andWhere(['in', 'cate_id', ScategoryModel::getDescendantIds($this->options['cate_id'])]);
        }


         $query->limit($this->options['quantity'] > 0 ? $this->options['quantity'] : 4);
        if (empty($list = $query->asArray()->all())) {
            $list = array([]);
        } 

        return array_merge(['list' => $list], $this->options);
    }

    public function parseConfig($input)
    {
        return $input;
    }
}
