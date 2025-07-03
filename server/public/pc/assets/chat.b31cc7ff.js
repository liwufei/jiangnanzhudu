import{E as b,u as w,r as l,y as g,d as y,G as x,e as u,k as s,o as f,h as k,i as _,g as C,a as m,c as B,t as I,l as p,n as L}from"./index.193ff21d.js";import{r,a as N}from"./blocks.a7684095.js";/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 * 
 * @Id webim.js 2022.11.25 $
 * @author mosir
 */function S(n,t,a){r("webim/list",n,e=>{e.code==0&&typeof t=="function"&&t(e.data)},a)}function D(n,t,a){r("webim/logs",n,e=>{e.code==0&&typeof t=="function"&&t(e.data)},a)}function G(n,t,a){r("webim/send",n,e=>{e.code==0?typeof t=="function"&&t(e.data):b.warning(e.message)},a)}function E(n,t,a){r("webim/unread",n,e=>{e.code==0&&typeof t=="function"&&t(e.data)},a)}const O={class:"relative"},R={key:0,class:"absolute unread f-10 f-white center"},V={setup(n){const t=w(),a=l({}),e=g({unread:0,lastid:0}),d=l(null);return y(()=>{a.value=JSON.parse(localStorage.getItem("visitor"))||{},a.value.userid&&t.path.indexOf("/webim/chat")<0&&(d.value=setInterval(()=>{E(null,c=>{c>e.unread&&(S(null,o=>{for(let i=0;i<o.length;i++)if(o[i].unreads>0&&o[i].to){e.lastid=o[i].to.userid;break}}),e.unread=c)})},5e3))}),x(()=>{clearInterval(d.value)}),(c,o)=>{const i=u("ChatLineRound"),v=u("el-icon"),h=u("el-backtop");return s(t).path.indexOf("/webim/chat")<0?(f(),k(h,{key:0,onClick:o[0]||(o[0]=M=>s(L)("/webim/chat"+(s(e).lastid>0?"/"+s(e).lastid:""))),right:10,bottom:150,"visibility-height":0},{default:_(()=>[C("div",O,[m(v,null,{default:_(()=>[m(i)]),_:1}),s(e).unread>0?(f(),B("span",R,I(s(e).unread),1)):p("",!0)])]),_:1})):p("",!0)}}};var J=N(V,[["__scopeId","data-v-6a2a05ba"]]);export{D as a,G as b,J as c,S as w};
