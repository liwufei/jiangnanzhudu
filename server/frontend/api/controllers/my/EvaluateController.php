<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace frontend\api\controllers\my;

use Yii;

use common\models\OrderGoodsModel;

use common\library\Basewind;
use common\library\Timezone;
use common\library\Page;

use frontend\api\library\Respond;

/**
 * @Id EvaluateController.php 2018.12.25 $
 * @author yxyc
 */

class EvaluateController extends \common\base\BaseApiController
{
	/**
	 * 获取我的评价记录列表
	 * @api 接口访问地址: http://api.xxx.com/my/evaluate/list
	 */
	public function actionList()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(true)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['page', 'page_size']);

		$query = OrderGoodsModel::find()->alias('og')
			->select('og.id,og.spec_id,og.goods_id,og.order_id,og.goods_name,og.goods_image,og.specification,og.evaluation,og.comment,og.reply_comment,og.reply_time,og.images,o.buyer_id,o.evaluation_time')
			->joinWith('order o', false)
			->where(['buyer_id' => Yii::$app->user->id, 'o.evaluation_status' => 1])
			->orderBy(['id' => SORT_DESC]);

		$page = Page::getPage($query->count(), $post->page_size, false, $post->page);
		$list = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
		foreach ($list as $key => $value) {
			$list[$key]['reply_time'] = Timezone::localDate('Y-m-d', $value['reply_time']);
			$list[$key]['evaluation_time'] = Timezone::localDate('Y-m-d', $value['evaluation_time']);


			// 晒图
			if ($value['images']) {
				$list[$key]['images'] = json_decode($value['images']);
			}
		}

		return $respond->output(true, null, ['list' => $list, 'pagination' => Page::formatPage($page, false)]);
	}
}
