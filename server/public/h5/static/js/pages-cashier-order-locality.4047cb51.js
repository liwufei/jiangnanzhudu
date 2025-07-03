(window["webpackJsonp"]=window["webpackJsonp"]||[]).push([["pages-cashier-order-locality"],{"2de7":function(t,e,n){"use strict";n.r(e);var a=n("43f1"),i=n("bf8c");for(var r in i)["default"].indexOf(r)<0&&function(t){n.d(e,t,(function(){return i[t]}))}(r);var u=n("828b"),l=Object(u["a"])(i["default"],a["b"],a["c"],!1,null,null,null,!1,a["a"],void 0);e["default"]=l.exports},3879:function(t,e,n){"use strict";var a=n("f5bd").default,i=a(n("0ed1"));t.exports={locality:
/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 *
 * @Id locality.js 2019.10.26 $
 * @author winder
 */
function(t,e){i.default.request("order/timeline",{order_id:e},(function(e){0==e.code&&(t.locality=e.data||[],t.locality.length>0&&uni.setNavigationBarColor({backgroundColor:"#F1F1F1"}))}))}}},"43f1":function(t,e,n){"use strict";n.d(e,"b",(function(){return a})),n.d(e,"c",(function(){return i})),n.d(e,"a",(function(){}));var a=function(){var t=this,e=t.$createElement,n=t._self._c||e;return n("v-uni-view",[t.locality.length>0?n("v-uni-view",{staticClass:"pd10 round-edge bgf ml10 mr10 mt10"},t._l(t.locality,(function(e,a){return n("v-uni-view",{key:a,class:["pb10 pt10",a>0?"bt ":""]},[n("v-uni-label",{staticClass:"uni-bold"},[t._v(t._s(e.status))]),n("p",{staticClass:"f-gray"},[t._v(t._s(e.remark))]),n("p",{staticClass:"f-gray"},[t._v(t._s(e.created))])],1)})),1):n("v-uni-view",{staticClass:"mb10 pd10 bgr f-white"},[t._v("暂无配送信息")])],1)},i=[]},"674a":function(t,e,n){"use strict";n("6a54");var a=n("f5bd").default;Object.defineProperty(e,"__esModule",{value:!0}),e.default=void 0;var i=a(n("ac89")),r=a(n("3879")),u={data:function(){return{locality:[]}},onLoad:function(t){i.default.verifyLogin(!0,this.$mp.page.route)&&r.default.locality(this,t.id)}};e.default=u},bf8c:function(t,e,n){"use strict";n.r(e);var a=n("674a"),i=n.n(a);for(var r in a)["default"].indexOf(r)<0&&function(t){n.d(e,t,(function(){return a[t]}))}(r);e["default"]=i.a}}]);