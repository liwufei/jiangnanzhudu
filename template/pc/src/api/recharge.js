/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 * 
 * @Id deposit.js 2021.11.30 $
 * @author mosir
 */

import { request } from '@/common/server.js'
import { ElMessage } from 'element-plus'

/**
 * 充值配置
 * @param {Object} params 
 * @param {Function} callback 
 * @param {ElLoading} loading 
 */
export function rechargeSetting(params, callback, loading) {
    request('recharge/setting', params, (res) => {
        if (res.code == 0) {
            if (typeof callback == 'function') {
                callback(res.data)
            }
        }
    }, loading)
}