<template>
    <el-dialog v-model="dialogVisible" :title="title" :width="800" :center="true" :draggable="true"
        :destroy-on-close="true" :close-on-click-modal="false" :before-close="close">
        <el-form :inline="false">
            <el-form-item label="优惠券名称" :label-width="85">
                <el-input v-model="coupon.name" style="width:220px" clearable />
            </el-form-item>
            <el-form-item label="优惠券数量" :label-width="85">
                <el-input v-model="coupon.total" :disabled="coupon.id" class="number" style="margin-right:300px;"
                    clearable />
            </el-form-item>
            <el-form-item label="优惠金额" :label-width="85">
                <el-input v-model="coupon.money" class="number" clearable />
                <span class="f-13 f-gray ml10">元</span>
            </el-form-item>
            <el-form-item label="购满金额" :label-width="85">
                <el-input v-model="coupon.amount" class="number" clearable />
                <span class="f-13 f-gray ml10">元，单笔订单可用商品购满多少金额可用</span>
            </el-form-item>
            <el-form-item label="有效期起" :label-width="85">
                <el-date-picker v-model="coupon.start_time" type="date" format="YYYY-MM-DD" value-format="YYYY-MM-DD" />
            </el-form-item>
            <el-form-item label="有效期至" :label-width="85">
                <el-date-picker v-model="coupon.end_time" type="date" format="YYYY-MM-DD" value-format="YYYY-MM-DD" />
            </el-form-item>
            <el-form-item label="获取方式" :label-width="85">
                <el-radio-group v-model="coupon.received">
                    <el-radio-button :label="1">点击领取</el-radio-button>
                    <el-radio-button :label="0">指定派发</el-radio-button>
                </el-radio-group>
            </el-form-item>
            <el-form-item label="指定商品" :label-width="85">
                <div class="bgp round-edge w-full">
                    <div class="pd10">
                        <el-input v-model="gallery.keyword" placeholder="商品名称" style="width: 240px;"
                            :suffix-icon="Search" @blur="search" />
                    </div>
                    <el-scrollbar v-if="gallery.list.length > 0" height="170px" class="bgp pl10 pt5 pb5 pr10">
                        <el-checkbox-group v-model="coupon.items">
                            <el-checkbox v-for="item in gallery.list" :key="item.goods_id" :label="item.goods_id"
                                class="block">{{ item.goods_name }}</el-checkbox>
                        </el-checkbox-group>
                    </el-scrollbar>
                </div>
            </el-form-item>
            <el-form-item label=" " :label-width="85">
                <p class="mt5 f-gray f-12">如果不勾选，则不限制</p>
            </el-form-item>
        </el-form>
        <template #footer>
            <div class="mb20">
                <el-button @click="close">关闭</el-button>
                <el-button type="primary" @click="submit" :loading="loading">提交</el-button>
            </div>
        </template>
    </el-dialog>
</template>
<script setup>
import { ref, reactive, watch, onMounted } from 'vue'
import { Search } from '@element-plus/icons-vue'
import { ElMessage } from 'element-plus'
import { couponAdd, couponUpdate } from '@/api/coupon.js'
import { goodsList } from '@/api/goods.js'

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
const coupon = ref({ items: [], received: 1 })
const gallery = reactive({ list: [], keyword: '' })
const visitor = ref({})

watch(() => props.visible, (value) => {
    dialogVisible.value = value
})
watch(() => props.data, (value) => {
    if (!value.items) value.items = []
    coupon.value = value
})

onMounted(() => {
    visitor.value = JSON.parse(localStorage.getItem('visitor')) || {}
    search()
})

const search = () => {
    goodsList({ store_id: visitor.value.store_id, keyword: gallery.keyword }, (data) => {
        gallery.list = data.list || []
    })
}

const emit = defineEmits(['close'])
const submit = () => {
    if (coupon.value.id) {
        couponUpdate(coupon.value, (data) => {
            ElMessage.success('编辑成功')
            emit('close', coupon.value)
        }, loading)
    } else {
        couponAdd(coupon.value, (data) => {
            ElMessage.success('添加成功')
            emit('close', Object.assign(coupon.value, { available: 1, surplus: coupon.value.total }))
        }, loading)
    }
}
const close = () => {
    emit('close', null)
}

</script>
<style scoped>
.el-form {
    margin: 0 30px;
}

.el-form .el-input.number {
    width: 100px;
}

:deep() .el-date-editor .el-input__inner {
    padding-left: 34px !important;
}
</style>
