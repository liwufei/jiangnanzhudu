<template>
    <el-dialog v-model="dialogVisible" :title="title" :width="500" :center="true" :draggable="true"
        :destroy-on-close="true" :close-on-click-modal="false" :before-close="close">
        <el-form>
            <el-form-item label="备注">
                <el-input v-model="form.remark" :clearable="true" />
                <p class="f-gray mt5 f-13">非商家自配订单无需提交</p>
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
import { ElMessage, ElMessageBox } from 'element-plus'
import { orderUpdate } from '@/api/order.js'

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
const form = reactive({ remark: '订单已送达，配送完成' })

watch(() => props.visible, (value) => {
    dialogVisible.value = value
})

const emit = defineEmits(['close'])
const submit = () => {
    ElMessageBox.confirm('商户已完成配送并交付物品给用户，该操作无法回退，请确认', '提示', {
        confirmButtonText: '确定',
        type: 'warning'
    }).then(() => {
        orderUpdate(Object.assign(form, { order_id: props.data.order_id, status: 30 }), (data) => {
            ElMessage.success('已完成配送')
            emit('close', data)
        }, loading)
    }).catch(() => { })
}

const close = () => {
    emit('close', null)
}

</script>
<style scoped>
.el-form {
    margin: 0 100px;
}

.el-form .el-select {
    width: 240px;
}
</style>