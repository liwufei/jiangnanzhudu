<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace frontend\api\controllers\seller;

use Yii;

use common\models\GoodsModel;
use common\models\GoodsStatisticsModel;
use common\models\DistributeSettingModel;

use common\library\Basewind;
use common\library\Page;

use frontend\api\library\Respond;
use frontend\api\library\Formatter;

/**
 * @Id DistributeController.php 2019.12.8 $
 * @author yxyc
 */

class DistributeController extends \common\base\BaseApiController
{
	/**
	 * 获取分销商品列表
	 * @api 接口访问地址: https://www.xxx.com/api/seller/distribute/list
	 */
    public function actionList()
    {
		// 验证签名
		$respond = new Respond();
		if(!$respond->verify(true)) {
			return $respond->output(false);
		}
		
		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['page', 'page_size']);

		// 未设置分销
		if($post->type == 'pending') {
			$query = GoodsModel::find()->alias('g')
				->select('g.goods_id,g.goods_name,g.price,g.default_image as goods_image')
				->where(['g.store_id' => Yii::$app->user->id])
				->andWhere(['not in', 'g.goods_id', DistributeSettingModel::find()->alias('ds')->select('g.goods_id')->joinWith('goods g', false)->where(['ds.type' => 'goods', 'store_id' => Yii::$app->user->id])->column()]);

		}
		// 已设置分销
		else {
			$query = DistributeSettingModel::find()->alias('ds')
				->select('ds.ratio1,ds.ratio2,ds.ratio3,ds.enabled,g.goods_id,g.goods_name,g.price,g.default_image as goods_image')
				->joinWith('goods g', false, 'INNER JOIN')
				->where(['ds.type' => 'goods', 'g.store_id' => Yii::$app->user->id]);
		}
		
		if($post->keyword) {
			$query->andWhere(['like', 'g.goods_name', $post->keyword]);
		}

		$page = Page::getPage($query->count(), $post->page_size, false, $post->page);
		$list = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
		foreach($list as $key => $value) {
			$list[$key]['goods_image'] = Formatter::path($value['goods_image'], 'goods');
			$list[$key]['sales'] = GoodsStatisticsModel::find()->select('sales')->where(['goods_id' => $value['goods_id']])->scalar();
		}

		$this->params = ['list' => $list, 'pagination' => Page::formatPage($page, false)];
		return $respond->output(true, null, $this->params);
	}
}