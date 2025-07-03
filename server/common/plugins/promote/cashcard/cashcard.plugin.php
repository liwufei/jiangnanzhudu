<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace common\plugins\promote\cashcard;

use yii;

use common\models\DepositTradeModel;
use common\models\CashcardModel;

use common\library\Timezone;
use common\library\Page;
use common\plugins\BasePromote;

/**
 * @Id cashcard.plugin.php 2018.6.5 $
 * @author mosir
 */

class Cashcard extends BasePromote
{
	/**
	 * 插件实例
	 * @var string $code
	 */
	protected $code = 'cashcard';

	/**
	 * 获取充值卡列表
	 */
	public function getList($post = null, $params = [])
	{
		$query = CashcardModel::find()
			->select('cardNo,id,name,money,add_time,active_time,expire_time')->orderBy(['active_time' => SORT_DESC, 'id' => SORT_DESC])
			->where($params);

		if ($post->cardNo) {
			$query->andWhere(['cardNo' => $post->cardNo]);
		}
		if ($post->keyword) {
			$query->andWhere(['like', 'name', $post->keyword]);
		}

		$page = Page::getPage($query->count(), $post->page_size, false, $post->page);
		$list = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
		foreach ($list as $key => $record) {
			$recordlist[$key]['valid'] = 1;

			if ($record['expire_time'] > 0) {
				if (Timezone::gmtime() > $record['expire_time']) {
					$recordlist[$key]['valid'] = 0;
				}
			}

			if ($tradeNo = DepositTradeModel::find()->select('tradeNo')->where(['bizOrderId' => $record['cardNo']])->scalar()) {
				$list[$key]['tradeNo'] = $tradeNo;
			}

			$list[$key]['add_time'] = Timezone::localDate('Y-m-d H:i:s', $record['add_time']);
			$record['active_time'] > 0 && $list[$key]['active_time'] = Timezone::localDate('Y-m-d H:i:s', $record['active_time']);
			$record['expire_time'] > 0 && $list[$key]['expire_time'] = Timezone::localDate('Y-m-d H:i:s', $record['expire_time']);
		}

		return ['list' => $list, 'pagination' => Page::formatPage($page, false)];
	}
}
