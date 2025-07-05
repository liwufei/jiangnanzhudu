<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace frontend\mob\widgets\storelist;

use Yii;

use common\models\StoreModel;
use common\models\GoodsModel;
use common\models\ScategoryModel;

use common\base\BaseWidget;

/**
 * @Id main.widget.php 2018.9.13 $
 * @author mosir
 */

class StorelistWidget extends BaseWidget
{
    var $name = 'storelist';

    public function getData()
    {
        // 初始值
        $this->options = array_merge(
            [
                'source' => '',
                'paging' => 0,
                'quantity' => 1,
                'orderby' => ''
            ],
            $this->options
        );

        $query = StoreModel::find()->alias('s')->select('s.store_id,store_name,store_logo')->joinWith('categoryStore cs', false)->where(['state' => 1]);
        if ($this->options['source'] == 'category') {
            $query->andWhere(['in', 'cate_id', ScategoryModel::getDescendantIds($this->options['cate_id'])]);
        }

        if ($this->options['paging'] == 1) {
            $query->limit($this->options['page_size'] > 0 ? $this->options['page_size'] : 4);
        } else {
            $query->limit($this->options['quantity'] > 0 ? $this->options['quantity'] : 4);
        }

        if (empty($list = $query->asArray()->all())) {
            $list = array([]);
        } else {
            foreach ($list as $key => $value) {

                $model = GoodsModel::find()->select('goods_id,goods_name,price,default_image,store_id')
                    ->where(['store_id' => $value['store_id'], 'if_show' => 1, 'closed' => 0])
                    ->limit(4);

                if ($this->options['orderby']) {
                    $orderBy = explode('|', $this->options['orderby']);
                    $model->orderBy([$orderBy[0]  => $orderBy[1] == 'desc' ? SORT_DESC : SORT_ASC]);
                } else $model->orderBy(['goods_id' => SORT_DESC]);

                if (($items = $model->asArray()->all())) {
                    $list[$key]['items'] = $items;
                }
            }
        }

        return array_merge(['list' => $list], $this->options);
    }

    public function parseConfig($input)
    {
        return $input;
    }
}
