<template>
    <el-dialog v-model="dialogVisible" :title="title" :width="750" :center="true" :draggable="true"
        :destroy-on-close="true" :close-on-click-modal="false" :before-close="close">
        <el-form>
            <el-form-item>
                <el-table ref="multipleTableRef" :data="specs" :border="true" :stripe="false" max-height="767"
                    size="small" :header-cell-style="{ 'background': '#f3f8fe' }">
                    <el-table-column v-if="goods.spec_qty == 0" label="规格" width="100">
                        <template #default="scope">
                            默认规格
                        </template>
                    </el-table-column>
                    <el-table-column v-if="goods.spec_name_1" :label="goods.spec_name_1" width="100">
                        <template #default="scope">
                            {{ scope.row['spec_1'] }}
                        </template>
                    </el-table-column>
                    <el-table-column v-if="goods.spec_name_2" :label="goods.spec_name_2" width="100">
                        <template #default="scope">
                            {{ scope.row['spec_2'] }}
                        </template>
                    </el-table-column>
                    <el-table-column label="划线价(元)" width="140">
                        <template #default="scope">
                            <el-input v-model="scope.row.mkprice" class="small" />
                        </template>
                    </el-table-column>
                    <el-table-column label="售价(元)" width="140">
                        <template #default="scope">
                            <el-input v-model="scope.row.price" class="small" />
                        </template>
                    </el-table-column>
                    <el-table-column prop="stock" label="库存" width="140">
                        <template #default="scope">
                            <el-input v-model="scope.row.stock" class="small" />
                        </template>
                    </el-table-column>
                    <el-table-column prop="weight" label="重量(kg)" width="140">
                        <template #default="scope">
                            <el-input v-model="scope.row.weight" class="small" />
                        </template>
                    </el-table-column>
                </el-table>
            </el-form-item>
        </el-form>
        <template #footer>
            <el-button @click="close">关闭</el-button>
            <el-button type="primary" @click="submit" :loading="loading">提交</el-button>
        </template>
    </el-dialog>
</template>
<script setup>

import { onMounted, ref, reactive, watch } from 'vue'
import { ElMessage } from 'element-plus'
import { goodsUpdate, goodsSpecs } from '@/api/goods.js'

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
const specs = ref([])
const goods = ref({})

onMounted(() => {
    getSpecs()
})

watch(() => props.visible, (value) => {
    dialogVisible.value = value
})
watch(() => props.data, (value) => {
    getSpecs()
})

const getSpecs = () => {
    goods.value = props.data
    goodsSpecs({ goods_id: goods.value.goods_id }, (data) => {
        specs.value = data.list || []
    })
}

const emit = defineEmits(['close'])
const submit = () => {
    goodsUpdate({ goods_id: goods.value.goods_id, spec_qty: goods.value.spec_qty, spec_name_1: goods.value.spec_name_1, spec_name_2: goods.value.spec_name_2, specs: specs.value }, (data) => {
        ElMessage.success('修改成功')
        let stocks = 0
        specs.value.forEach((item) => {
            stocks += Number(item.stock)
        })
        emit('close', { price: specs.value[0].price, stocks: stocks })
    }, loading)
}
const close = () => {
    emit('close', null)
}

</script>
<style scoped>
.el-form {
    margin: 0 20px;
}

:deep() .el-date-editor .el-input__inner {
    padding-left: 34px !important;
}
</style>