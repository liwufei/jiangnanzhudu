<template>
    <el-dialog v-model="dialogVisible" :title="title" :width="850" :center="true" :draggable="true"
        :destroy-on-close="true" :close-on-click-modal="false" :before-close="close">
        <el-form :inline="true">
            <el-form-item label="收货人" :label-width="85">
                <el-input v-model="address.consignee" />
            </el-form-item>
            <el-form-item label="所在地区" :label-width="85" class="uni-flex uni-row w-full">
                <multiselector api="region/list" idField="region_id" nameField="name" parentField="parent_id"
                    :original="[address.province, address.city, address.district]" @callback="callback">
                </multiselector>
            </el-form-item>
            <el-form-item label="详细地址" :label-width="85" style="margin-right: 40%">
                <el-input v-model="address.address" placeholder="街道/小区/楼栋/房号" />
            </el-form-item>
            <el-form-item v-if="mapKey" label="位置坐标" :label-width="85">
                <div class="map">
                    <!--https://didi.github.io/vue-tmap/guide-->
                    <tmap-map :mapKey="mapKey" zoom="15" :center="center" :events="events" :scrollable="false"
                        ref="map">
                        <tmap-multi-marker :geometries="geometries" :styles="styles" ref="markers" />
                    </tmap-map>
                </div>
            </el-form-item>
            <el-form-item label="手机号码" :label-width="85">
                <el-input v-model="address.phone_mob" />
            </el-form-item>
            <!-- <el-form-item label="固话号码" :label-width="85">
                <el-input v-model="address.phone_tel" />
            </el-form-item> -->
            <el-form-item label="设为默认" :label-width="85" class="w-full">
                <el-switch v-model="address.defaddr" :active-value="1" :inactive-value="0" />
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
import { ref, reactive, watch, shallowRef, getCurrentInstance, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { addressAdd, addressUpdate } from '@/api/address.js'
import { siteRead } from '@/api/site.js'

import multiselector from '@/components/selector/multiselector.vue'

const { proxy } = getCurrentInstance()
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
const address = reactive({})
const center = reactive({ lat: 39.908802, lng: 116.397502 })
const mapKey = ref()

onMounted(() => {
    siteRead(null, (data) => {
        mapKey.value = data.mapKey
    })
})

watch(() => props.visible, (value) => {
    dialogVisible.value = value
})
watch(() => props.data, (value) => {
    Object.assign(address, value, { defaddr: parseInt(value.defaddr) })
    if (value.latitude && value.longitude) {
        Object.assign(center, { lat: parseFloat(value.latitude), lng: parseFloat(value.longitude) })
        geometries.value = [{ id: 'center', position: center }]
    }
})

const emit = defineEmits(['close'])
const submit = () => {
    if (address.addr_id) {
        addressUpdate(address, (data) => {
            ElMessage.success('编辑成功')
            emit('close', data, 'update')
        }, loading)
    } else {
        addressAdd(address, (data) => {
            ElMessage.success('添加成功')
            emit('close', data)
        }, loading)
    }
}
const close = () => {
    emit('close', null)
}

const callback = (value) => {
    address.region_id = value.id
}
const pan = (event) => {
    geometries.value = [{ id: 'center', position: proxy.$refs.map.getCenter() }]
}
const panend = (event) => {
    let position = proxy.$refs.map.getCenter()
    address.latitude = position.lat
    address.longitude = position.lng
}
const events = ref({ pan: pan, panend: panend })
const geometries = ref([{ id: 'center', position: center, styleId: 'marker', content: '' }])
const styles = shallowRef({ 'marker': {} })
</script>
<style scoped>
.map {
    border: 1px #ddd solid;
    width: 640px;
    height: 300px;
    overflow: hidden;
}

.el-form {
    margin: 0 30px;
}
</style>