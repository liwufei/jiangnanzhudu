<template>
    <el-dialog v-model="dialogVisible" :title="title" :width="800" :center="true" :draggable="true"
        :destroy-on-close="true" :close-on-click-modal="false" :before-close="close">

        <div class="pb20 gallery">
            <div class="hd bold bgp pt10 pb10">
                <span class="item">用户</span>
                <span class="item">优惠券号码</span>
                <span class="item">获得时间</span>
                <!-- <span class="item">获得方式</span> -->
                <span class="item">状态</span>
            </div>
            <div v-if="gallery.list.length > 0" class="bd">
                <div v-for="item in gallery.list" class="pt10 pb10 bt">
                    <span class="item">{{ item.phone_mob || item.username }}</span>
                    <span class="item">{{ item.coupon_sn }}</span>
                    <span class="item">{{ item.created }}</span>
                    <!-- <span class="item">{{ item.received == 0 ? '主动领取' : '商家派发' }}</span> -->
                    <span class="item">{{ item.remain_times == 0 ? '已使用' : '未使用' }}</span>
                </div>
            </div>
            <div v-else>
                <div class="pd10 center f-12">暂无记录</div>
            </div>
            <div v-if="pagination.total > 0" class="mt20 mb20">
                <el-pagination v-model:currentPage="pagination.page" v-model:page-size="pagination.page_size"
                    :page-sizes="[10, 50, 100, 200]" :background="false" layout="total, sizes, prev, pager, next"
                    :total="pagination.total" @size-change="handleSizeChange" @current-change="handleCurrentChange"
                    :hide-on-single-page="false" class="center" />
            </div>
        </div>
    </el-dialog>
</template>
<script setup>
import { ref, reactive, watch, onMounted } from 'vue'
import { couponRecord } from '@/api/coupon.js'

const props = defineProps({
    title: { type: String, default: '' },
    visible: { type: Boolean, default: false },
    data: {
        type: [Object, Array],
        default: () => {
            return {}
        }
    }
})

const dialogVisible = ref(false)
const loading = ref(false)
const coupon = ref({})
const gallery = reactive({ list: [] })
const pagination = ref({})

watch(() => props.visible, (value) => {
    dialogVisible.value = value
})
watch(() => props.data, (value) => {
    coupon.value = value
    getList()
})

onMounted(() => {
    //getList()
})

const handleSizeChange = (value) => {
    getList({ page_size: value })
}
const handleCurrentChange = (value) => {
    getList({ page: value, page_size: pagination.value.page_size })
}

function getList(params) {
    couponRecord(Object.assign({ id: coupon.value.id }, params), (data) => {
        gallery.list = data.list || []
        pagination.value = data.pagination
    }, loading)
}

const emit = defineEmits(['close'])
const close = () => {
    emit('close', null)
}

</script>
<style scoped>
.gallery .item {
    width: 25%;
    display: inline-block;
    text-align: center;
    font-size: 13px;
}
</style>