import{r as a}from"./blocks.a7684095.js";import{E as i}from"./index.193ff21d.js";/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 * 
 * @Id address.js 2021.10.30 $
 * @author mosir
 */function o(s,d,t){a("address/list",s,e=>{e.code==0&&typeof d=="function"&&d(e.data)},t)}function r(s,d,t){a("address/add",s,e=>{e.code==0?typeof d=="function"&&d(e.data):i.warning(e.message)},t)}function u(s,d,t){a("address/update",s,e=>{e.code==0?typeof d=="function"&&d(e.data):i.warning(e.message)},t)}function p(s,d,t){a("address/delete",s,e=>{e.code==0?typeof d=="function"&&d(e.data):i.warning(e.message)},t)}export{u as a,r as b,o as c,p as d};
