<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace frontend\home\widgets\notice;

use Yii;

use common\models\ArticleModel;

use common\library\Basewind;
use common\base\BaseWidget;

/**
 * @Id main.widget.php 2018.9.13 $
 * @author mosir
 */

class NoticeWidget extends BaseWidget
{
    var $name = 'notice';

    public function getData()
    {
        $query = ArticleModel::find()->select('id,title');

        if ($this->options['source'] == 'choice') {
            $query->andWhere(['in', 'id', explode(',', $this->options['items'])]);
        } else {
            if ($this->options['source'] == 'category') {
                $query->andWhere(['cate_id' => $this->options['cate_id']]);
            }
            $query->limit($this->options['quantity'] > 0 ? $this->options['quantity'] : 10);
        }
        $list = $query->orderBy(['id' => SORT_DESC])->asArray()->all();
        return array_merge(['list' => empty($list) ? [] : $list], $this->options);
    }

    public function parseConfig($input)
    {
        return $input;
    }
}
