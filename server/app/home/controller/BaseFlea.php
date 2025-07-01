<?php

namespace app\home\controller;

class BaseFlea extends BaseMall
{
    public function initialize()
    {
        parent::initialize();
        if (config('ds_config.flea_isuse') != 1) {
            header('location: ' . HOME_SITE_URL);
            die;
        }
    }
}
