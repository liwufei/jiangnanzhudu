<template>
    <el-dialog v-model="dialogVisible" :title="title" :width="800" :center="true" :draggable="true"
        :destroy-on-close="true" :close-on-click-modal="false" :before-close="close">
        <el-form :inline="false">
            <el-form-item label="优惠券名称" :label-width="85">
                <p>{{ coupon.name }}</p>
            </el-form-item>
            <el-form-item label="发放给用户" :label-width="85">
                <div class="bgp round-edge w-full">
                    <div class="pd10">
                        <el-input v-model="gallery.keyword" placeholder="用户手机号" style="width: 240px;"
                            :suffix-icon="Search" @blur="search" />
                    </div>
                    <el-scrollbar v-if="gallery.list.length > 0" height="170px" class="pl10 pb10 pr10">
                        <el-checkbox-group v-model="coupon.users">
                            <el-checkbox v-for="item in gallery.list" :key="item.userid" :label="item.userid"
                                class="block vertical-middle">
                                <div class="vertical-middle pt10 pb10">
                                    <img :src="item.portrait" width="20" height="20" class="image" />
                                    <span class="ml10">
                                        {{ (item.nickname || item.username) + '（手机号：' + (item.phone_mob || '无') + '）' }}
                                    </span>
                                </div>
                            </el-checkbox>
                        </el-checkbox-group>
                    </el-scrollbar>
                </div>
            </el-form-item>
            <el-form-item label="发放数量" :label-width="85">
                <el-input-number v-model="coupon.quantity" min="1" max="100" />
                <p class="f-gray ml10 f-12">单个用户发放数量</p>
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
import { couponSend } from '@/api/coupon.js'
import { userList } from '@/api/user.js'

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
const coupon = ref({ users: [] })
const gallery = reactive({ list: [], keyword: '' })

watch(() => props.visible, (value) => {
    dialogVisible.value = value
})
watch(() => props.data, (value) => {
    coupon.value = value
    coupon.value.quantity = 1
})

onMounted(() => {
    search()
})

const search = () => {
    userList({ phone_mob: gallery.keyword }, (data) => {
        gallery.list = data.list || []
    })
}

const emit = defineEmits(['close'])
const submit = () => {
    couponSend(coupon.value, (data) => {
        coupon.users = []
        ElMessage.success('发放成功')
        emit('close', Object.assign(coupon.value, data || {}))
    }, loading)
}
const close = () => {
    emit('close', null)
}

</script>
<style scoped>
.el-form {
    margin: 0 30px;
}

.el-form .image {
    border-radius: 100%;
}
</style>