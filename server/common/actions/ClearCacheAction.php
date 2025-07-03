<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace common\actions;

use Yii;
use yii\base\Action;
use yii\web\Response;
use yii\helpers\FileHelper;

use common\models\UploadedFileModel;

use common\library\Message;
use common\library\Language;

/**
 * @Id ClearCacheAction.php 2018.8.29 $
 * @author mosir
 */

class ClearCacheAction extends Action
{
	/**
	 * Runs the action.
	 */
	public function run()
	{
		Yii::$app->response->format = Response::FORMAT_JSON;

		// 清空缓存（此处仅能清除后台的缓存，eg：当前应用为后台）
		//Yii::$app->cache->flush();

		$folders = ['cache', 'debug', 'logs', 'Smarty', 'mail'];
		foreach ($folders as $folder) {
			FileHelper::removeDirectory(Yii::getAlias('@backend') . '/runtime/' . $folder);
			FileHelper::removeDirectory(Yii::getAlias('@frontend') . '/home/runtime/' . $folder);
			FileHelper::removeDirectory(Yii::getAlias('@frontend') . '/mob/runtime/' . $folder);
			FileHelper::removeDirectory(Yii::getAlias('@frontend') . '/api/runtime/' . $folder);
			FileHelper::removeDirectory(Yii::getAlias('@install') . '/runtime/' . $folder);
			FileHelper::removeDirectory(Yii::getAlias('@console') . '/runtime/' . $folder);
		}

		// 删除二维码/海报图片（图片很多，可以定时删除，以免占用太多服务器资源）
		$folders = ['goods', 'poster', 'shop'];
		foreach ($folders as $folder) {
			FileHelper::removeDirectory(Yii::getAlias('@public') . '/data/files/mall/qrcode/' . $folder);
		}

		// 删除临时资源文件
		$this->clearResourceFiles();

		// 删除无用的上传文件【注意处理商品详情图片】
		//$this->clearUploadedFiles();

		return Message::display(Language::get('clear_cache_ok'));
	}

	/**
	 * 删除临时资源文件（文件夹大于20个的情况下才考虑清理）
	 * 通过此操作，可以使新的资源文件发布到web目录[del]
	 * 也可以删除多余无用的资源文件，释放空间，但请控制好删除条件
	 * 不建议每次更新缓存都清理资源文件，这样会极大消耗资源
	 * @param int $num 当临时资源文件夹大于该数量时，执行删除操作
	 */
	private function clearResourceFiles($num = 20)
	{
		$folders = [
			Yii::getAlias('@public') . '/admin/assets',
			Yii::getAlias('@public') . '/home/assets',
			Yii::getAlias('@public') . '/mob/assets',
			Yii::getAlias('@public') . '/install/assets',
		];

		foreach ($folders as $folder) {
			if (!is_dir($folder)) continue;
			$list = FileHelper::findDirectories($folder, ['recursive' => false]);
			if ($list && count($list) > $num) {
				FileHelper::removeDirectory($folder);
				FileHelper::createDirectory($folder);
			}
		}

		// 删除其他临时文件
		$folder = Yii::getAlias('@public') . '/data/files/mall/excel';
		FileHelper::removeDirectory($folder);
		FileHelper::createDirectory($folder);
	}

	/**
	 * 删除无用的上传文件
	 * @param int $limit 每次删除的文件数
	 */
	private function clearUploadedFiles($limit = 10)
	{
		$uploadedfiles = UploadedFileModel::find()->where(['item_id' => 0])
			->orderBy(['file_id' => SORT_ASC])->limit($limit)->asArray()->all();

		UploadedFileModel::deleteFileByQuery($uploadedfiles);
	}
}
