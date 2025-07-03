import{r as s}from"./blocks.a7684095.js";import{E as d}from"./index.193ff21d.js";/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 * 
 * @Id distribute.js 2021.12.25 $
 * @author mosir
 */function u(e,i,f){s("seller/distribute/list",e,t=>{t.code==0&&typeof i=="function"&&i(t.data)},f)}function a(e,i,f){s("distribute/read",e,t=>{t.code==0&&typeof i=="function"&&i(t.data)},f)}function r(e,i,f){s("distribute/update",e,t=>{t.code==0?typeof i=="function"&&i(t.data):d.warning(t.message)},f)}function p(e,i,f){s("distribute/delete",e,t=>{t.code==0?typeof i=="function"&&i(t.data):d.warning(t.message)},f)}export{a,r as b,p as d,u as s};
