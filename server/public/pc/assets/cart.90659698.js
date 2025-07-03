import{r as n}from"./blocks.a7684095.js";import{n as f,a1 as c,E as i}from"./index.193ff21d.js";/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 * 
 * @Id cart.js 2022.5.30 $
 * @author mosir
 */function r(o,t,a){n("cart/list",o,e=>{e.code==0&&typeof t=="function"&&t(e.data)},a)}function u(o,t,a){n("cart/add",o,e=>{e.code==0?typeof t=="function"&&t(e.data):e.code==4004?f("/user/login?redirect="+encodeURIComponent(c())):i.warning(e.message)},a)}function g(o,t,a){n("cart/update",o,e=>{e.code==0?typeof t=="function"&&t(e.data):i.warning(e.message)},a)}function m(o,t,a){n("cart/remove",o,e=>{e.code==0?typeof t=="function"&&t(e.data):i.warning(e.message)},a)}function p(o,t,a){n("cart/chose",o,e=>{e.code==0?typeof t=="function"&&t(e.data):i.warning(e.message)},a)}export{r as a,g as b,u as c,m as d,p as e};
