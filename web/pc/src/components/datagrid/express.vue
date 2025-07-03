<template>
    <div class="pd10" v-loading="loading">
        <div v-for="(logistic, index) in express">
            <el-row class="pl10 pr10 pt10 pb10">
                <el-col :span="2" v-if="express.length > 1">快件{{ index + 1 }}</el-col>
                <el-col :span="5">快递公司：{{ logistic.company }}</el-col>
                <el-col :span="17">物流单号：{{ logistic.number }}</el-col>
            </el-row>
            <el-row class="pl10 pr10 bt mt10">
                <el-timeline v-if="logistic.details && logistic.details.length > 0" class="mt20 pt10">
                    <el-timeline-item v-for="(item, index) in logistic.details" :key="index" :timestamp="item.time"
                        :color="index == 0 ? '#0bbd87' : ''">
                        <label v-if="index == 0" class="mb5 block bold">{{ logistic.label }}</label>
                        <p class="f-c55">{{ item.context }}</p>
                    </el-timeline-item>
                </el-timeline>
                <el-col v-else>
                    <p v-if="logistic.message" class="pt10 f-gray">物流信息获取失败：{{ logistic.message }}</p>
                    <p v-else class="pt10 f-gray">暂时没有物流信息</p>
                </el-col>
            </el-row>
        </div>
    </div>
</template>
<script setup>
import { ref, watch } from 'vue'
import { orderLogistic } from '@/api/order.js'

const props = defineProps({
    data: {
        type: Object,
        default: () => {
            return {}
        }
    }
})

const loading = ref(false)
const express = ref([])

orderLogistic({ order_id: props.data.id }, (data) => {
    express.value = data || []
}, loading)


</script>