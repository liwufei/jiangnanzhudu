(window["webpackJsonp"]=window["webpackJsonp"]||[]).push([["pages-article-detail"],{4672:function(t,e,a){"use strict";var i=a("9c39"),n=a.n(i);n.a},"4b8b":function(t,e,a){"use strict";a.r(e);var i=a("542f"),n=a("beae");for(var r in n)["default"].indexOf(r)<0&&function(t){a.d(e,t,(function(){return n[t]}))}(r);a("4672");var u=a("828b"),d=Object(u["a"])(n["default"],i["b"],i["c"],!1,null,"21bd0124",null,!1,i["a"],void 0);e["default"]=d.exports},5374:function(t,e,a){"use strict";a("6a54");var i=a("f5bd").default;Object.defineProperty(e,"__esModule",{value:!0}),e.default=void 0;var n=i(a("e1e4")),r={data:function(){return{id:0,article:{}}},onLoad:function(t){this.id=t.id,n.default.article(this)}};e.default=r},"542f":function(t,e,a){"use strict";a.d(e,"b",(function(){return n})),a.d(e,"c",(function(){return r})),a.d(e,"a",(function(){return i}));var i={uParse:a("8882").default},n=function(){var t=this.$createElement,e=this._self._c||t;return e("v-uni-view",[this.article.id?e("v-uni-view",{staticClass:"pd10 bgf"},[e("u-parse",{staticClass:"detail-info pd10",attrs:{content:this.article.description||" "}})],1):this._e()],1)},r=[]},5710:function(t,e,a){var i=a("c86c");e=i(!1),e.push([t.i,"uni-page-body[data-v-21bd0124]{background-color:#fff}body.?%PAGE?%[data-v-21bd0124]{background-color:#fff}\n\n/* 消除描述中图片上下间距 */[data-v-21bd0124] .detail-info img{display:block}",""]),t.exports=e},"9c39":function(t,e,a){var i=a("5710");i.__esModule&&(i=i.default),"string"===typeof i&&(i=[[t.i,i,""]]),i.locals&&(t.exports=i.locals);var n=a("967d").default;n("6e520e57",i,!0,{sourceMap:!1,shadowMode:!1})},beae:function(t,e,a){"use strict";a.r(e);var i=a("5374"),n=a.n(i);for(var r in i)["default"].indexOf(r)<0&&function(t){a.d(e,t,(function(){return i[t]}))}(r);e["default"]=n.a},e1e4:function(t,e,a){"use strict";var i=a("f5bd").default,n=i(a("0ed1"));t.exports={article:
/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 *
 * @Id detail.js 2019.11.20 $
 * @author winder
 */
function(t){n.default.request("article/read",{id:t.id},(function(e){0==e.code&&(t.article=e.data||{},uni.setNavigationBarTitle({title:t.article.title}))}))}}}}]);