<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace common\library;

use yii;
use yii\helpers\ArrayHelper;

use common\library\Language;
use common\library\Arrayfile;

/**
 * @Id Setting.php 2018.9.3 $
 * @author mosir
 */

class Setting extends Arrayfile
{
    var $filename = null;

    public function __construct()
    {
        $this->filename = Yii::getAlias('@public') . '/data/setting.php';
    }

    public static function getInstance()
    {
        return new Setting();
    }

    public function getAll($setting = array())
    {
        $default = self::getDefault();
        $setting = parent::getAll();

        return ArrayHelper::merge($default, $setting);
    }

    public static function getDefault()
    {
        return [
            'price_format'      => '￥%s',
            'site_name'         => Language::get('default_site_name'),
            'site_title'        => Language::get('default_site_title'),
            'site_keywords'     => Language::get('default_site_keywords'),
            'site_description'  => Language::get('default_site_description'),
            'site_status'       => '1',
            'hot_keywords'      => Language::get('default_hot_keywords'),
            'captcha_status'    => [],
            'store_allow'       => '1',
            'upgrade_required'     => '10',
            'default_goods_image'  => 'static/system/default_goods_image.jpg',
            'default_store_logo'   => 'static/system/default_store_logo.gif',
            'default_user_portrait' => 'static/system/default_user_portrait.gif',
            'site_logo'            => 'static/system/site_logo.png',
            'default_image'        => 'static/system/default_image.png',
            'androidqrcode' => 'static/system/default_androidqrcode.png',
            'iosqrcode'    => 'static/system/default_iosqrcode.png',
        ];
    }
}
