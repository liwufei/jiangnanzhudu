<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace common\base;

use yii;


/**
 * @Id BaseModel.php 2018.9.3 $
 * @author mosir
 */

class BaseModel
{
    public static function getInstance($name = null)
    {
        if ($name) {
            $class_name = sprintf("common\models\%sModel", $name);
            return new $class_name();
        }

        return null;
    }
}
