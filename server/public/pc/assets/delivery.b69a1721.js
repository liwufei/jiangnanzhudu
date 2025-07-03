import{r as d}from"./blocks.a7684095.js";import{E as n}from"./index.193ff21d.js";/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 * 
 * @Id delivery.js 2021.10.25 $
 * @author mosir
 */function a(t,i,f){d("delivery/list",t,e=>{e.code==0&&typeof i=="function"&&i(e.data)},f)}function r(t,i,f){d("delivery/read",t,e=>{e.code==0&&typeof i=="function"&&i(e.data)},f)}function u(t,i,f){d("delivery/company",t,e=>{e.code==0&&typeof i=="function"&&i(e.data)},f)}function p(t,i,f){d("delivery/types",t,e=>{e.code==0&&typeof i=="function"&&i(e.data)},f)}function v(t,i,f){d("delivery/template",t,e=>{e.code==0&&typeof i=="function"&&i(e.data)},f)}function m(t,i,f){d("delivery/update",t,e=>{e.code==0?typeof i=="function"&&i(e.data):n.warning(e.message)},f)}function s(t,i,f){d("delivery/delete",t,e=>{e.code==0?typeof i=="function"&&i(e.data):n.warning(e.message)},f)}function c(t,i,f){d("delivery/timer",t,e=>{e.code==0&&typeof i=="function"&&i(e.data)},f)}function g(t,i,f){d("delivery/timerupdate",t,e=>{e.code==0?typeof i=="function"&&i(e.data):n.warning(e.message)},f)}function l(t,i,f){d("delivery/freight",t,e=>{e.code==0&&typeof i=="function"&&i(e.data)},f)}export{v as a,a as b,m as c,u as d,s as e,p as f,r as g,c as h,g as i,l as j};
