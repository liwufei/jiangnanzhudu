<?php

namespace frontend\home\assets;

use yii\web\AssetBundle;

/**
 * Main frontend application asset bundle.
 */
class AppAsset extends AssetBundle
{
	// 当配置$sourcePath后，执行\frontend\home\assets\AppAsset::register($this->view); 会复制资源文件到@web目录
	//public $sourcePath = '@public/static/javascript';
    public $basePath = '@webroot';
    public $baseUrl = '@web';
    public $css=[
		//'css/site.css',
	];
	public $js = [
    ];
	public $depends=[
		//'yii\ewb\YiiAsset',
		//'yii\bootstrap\BootstrapAsset',
	];
}
