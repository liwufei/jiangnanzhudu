<template>
    <myhead></myhead>
    <div class="main w pt10">
        <el-row :gutter="12">
            <el-col :span="4">
                <menus></menus>
            </el-col>
            <el-col :span="20">
                <div class="wraper" v-loading="loading">
                    <div class="round-edge pd10 bgf">
                        <div class="pd10">
                            <el-breadcrumb separator="/">
                                <el-breadcrumb-item>订单</el-breadcrumb-item>
                                <el-breadcrumb-item>订单管理</el-breadcrumb-item>
                                <el-breadcrumb-item>订单详情</el-breadcrumb-item>
                            </el-breadcrumb>
                        </div>
                    </div>
                    <div class="round-edge pd10 bgf mt20">
                        <ordertimeline :data="order"></ordertimeline>
                    </div>
                    <div class="round-edge pd10 bgf mt20">
                        <div class="pl10 pr10 pt10">
                            <h3 class="f-14">订单信息</h3>
                            <el-row class="f-13 f-c55 mt20 mb10">
                                <el-col :span="12">订单编号：{{ order.order_sn }}</el-col>
                                <el-col :span="12">订单总价：<span class="f-red f-yahei">{{
            currency(order.order_amount)
        }}</span></el-col>
                                <el-col :span="12">买家账号：{{ order.buyer_name }}</el-col>
                                <el-col :span="12">配送费用：<span class="f-red f-yahei">{{
            currency(order.freight)
        }}</span></el-col>
                                <el-col :span="12">支付方式：{{ order.payment_name }}</el-col>
                                <el-col :span="12">交易流水：{{ order.tradeNo || '-' }}</el-col>
                                <el-col :span="12" v-if="order.ship_time">发货时间：{{ order.ship_time }}</el-col>
                                <el-col :span="12" v-if="orderextm">配送方式：{{ experss ? experss.company :
            orderextm.deliveryName }}</el-col>
                                <el-col :span="12" v-if="experss">物流单号：{{ experss.number }}</el-col>
                            </el-row>
                        </div>
                    </div>
                    <div class="round-edge pd10 bgf mt20">
                        <div class="pl10 pr10 pt10">
                            <h3 class="f-14">商品信息</h3>
                            <el-row class="f-13 f-c55 mt20">
                                <el-col :span="12" v-for="item in order.items" class="mb10">
                                    <el-col :span="4">
                                        <router-link :to="'/goods/detail/' + item.goods_id" class="rlink mr10">
                                            <img :src="item.goods_image" width="60" height="60"></router-link>
                                    </el-col>
                                    <el-col :span="20" class="l-h20">
                                        <router-link :to="'/goods/detail/' + item.goods_id"
                                            class="rlink line-clamp-2">{{
            item.goods_name }}</router-link>
                                        <p v-if="item.specification" class="f-gray">{{ item.specification }}</p>
                                        <p class="mt5 f-red">{{ currency(item.price) }} x {{ item.quantity }}</p>
                                    </el-col>
                                </el-col>
                            </el-row>
                        </div>
                    </div>
                    <div v-if="orderextm" class="round-edge pd10 bgf mt20">
                        <div class="pl10 pr10 pt10">
                            <h3 class="f-14">收货信息</h3>
                            <el-row class="f-13 f-c55 mt20 mb10">
                                <el-col :span="12">收<span style="padding: 0 6px;">货</span>人：{{ orderextm.consignee
                                    }}</el-col>
                                <el-col :span="12">收货地址：{{ orderextm.province + orderextm.city + (orderextm.district ||
                                    '') + orderextm.address
                                    }}</el-col>
                                <el-col :span="12">联系电话：{{ orderextm.phone_mob }}</el-col>
                            </el-row>
                        </div>
                    </div>
                </div>
            </el-col>
        </el-row>
    </div>
    <myfoot></myfoot>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { orderRead, orderExtm, orderGoods, orderExpress } from '@/api/order.js'
import { currency } from '@/common/util.js'

import myhead from '@/pages/layout/header/seller.vue'
import myfoot from '@/pages/layout/footer/user.vue'
import menus from '@/pages/layout/menus/seller.vue'
import ordertimeline from '@/components/datagrid/ordertimeline.vue'

const route = useRoute()
const loading = ref(false)
const order = ref({})
const orderextm = ref()
const experss = ref()

onMounted(() => {

    orderRead({ order_id: route.params.id }, (data) => {
        order.value = data
        orderGoods({ order_id: data.order_id }, (items) => {
            order.value.items = items.list
        })
        orderExpress({ order_id: data.order_id }, (value) => {
            experss.value = value
        })
        orderExtm({ order_id: route.params.id }, (extm) => {
            orderextm.value = extm
        })
    }, loading)
})
</script>

<style scoped>
.wraper .el-row {
    line-height: 30px;
}

.wraper .el-divider {
    margin: 16px 0;
}
</style>
