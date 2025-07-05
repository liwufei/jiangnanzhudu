<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace common\plugins\map\tmap;

/**
 * @Id plugin.info.php 2018.6.3 $
 * @author mosir
 */

return array(
    'code' => 'tmap',
    'name' => '腾讯地图',
    'desc' => '腾讯地图，用于前端显示地图组件和设置位置坐标，地址解析，购买后需要分配【地址逆解析】额度，<a target="_blank" href="https://lbs.qq.com/dev/console/key/manage">申请秘钥</a>',
    'remark' => '',
    'author' => 'SHOPWIND',
    'website' => 'https://www.shopwind.net',
    'version' => '1.0',
    'config' => array(
        'key' => array(
            'type' => 'text',
            'text' => 'key'
        )
    )
);
