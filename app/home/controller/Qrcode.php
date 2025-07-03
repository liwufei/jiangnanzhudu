<?php

namespace app\home\controller;

class Qrcode extends BaseMall
{

    public function index()
    {
        include_once root_path() . 'extend/qrcode/phpqrcode.php';
        $value = strip_tags(htmlspecialchars_decode(input('get.url')));
        $errorCorrectionLevel = "L";
        $matrixPointSize = "4";
        \QRcode::png($value, false, $errorCorrectionLevel, $matrixPointSize, 2);
        exit;
    }
}
