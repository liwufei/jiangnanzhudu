<template>
    <myhead></myhead>
    <div class="main w pt10">
        <el-row :gutter="12">
            <el-col :span="4">
                <menus></menus>
            </el-col>
            <el-col :span="20">
                <div class="round-edge pd10 bgf">
                    <div class="pd10">
                        <el-breadcrumb separator="/">
                            <el-breadcrumb-item>配送</el-breadcrumb-item>
                            <el-breadcrumb-item>同城配送</el-breadcrumb-item>
                            <el-breadcrumb-item>门店配置</el-breadcrumb-item>
                        </el-breadcrumb>
                    </div>
                </div>
                <div class="round-edge pd10 bgf mt20">
                    <el-form :inline="true" class="pd10 mt20">
                        <el-form-item label="配送方式" :label-width="100" class="w-full">
                            <div v-if="localities.list.length > 0">
                                <el-checkbox-group v-model="localities.selected" @change="changeClick">
                                    <el-checkbox v-for="item in localities.list" :label="item.code">
                                        {{ item.name }}
                                    </el-checkbox>
                                </el-checkbox-group>
                            </div>
                            <div v-else class="f-red">同城配送插件未安装</div>
                        </el-form-item>
                        <el-form-item v-if="store.store_id" label="门店位置" :label-width="100">
                            <div v-if="store.store_id" class="uni-flex uni-row flex-middle">
                                <multiselector api="region/list" idField="region_id" nameField="name"
                                    parentField="parent_id"
                                    :original="[store.province || '', store.city || '', store.district || '']"
                                    @callback="(data) => { callback(data, 'region_id') }">
                                </multiselector>
                                <el-input v-model="store.address" placeholder="街道/小区/楼栋/房号" class="mr10" />
                            </div>
                        </el-form-item>
                        <el-form-item v-if="store.store_id" label="位置坐标" :label-width="100">
                            <div class="map flex-middle center">
                                <div v-if="mapKey">
                                    <!--https://didi.github.io/vue-tmap/guide-->
                                    <tmap-map :mapKey="mapKey" zoom="15" :center="center" :events="events"
                                        :scrollable="false" ref="map">
                                        <tmap-multi-marker :geometries="geometries" :styles="styles" ref="markers" />
                                    </tmap-map>
                                </div>
                                <div v-else class="f-gray center">地图插件未配置</div>
                            </div>
                        </el-form-item>
                        <el-form-item v-if="store.store_id" label=" " :label-width="100">
                            <div class="f-gray f-12">提示：如地图组件不显示，且浏览器报错不支持Webgl，则进入浏览器设置【about:config】启用Webgl</div>
                        </el-form-item>
                        <el-form-item label="支持配送距离" :label-width="100" class="w-full">
                            <div class="uni-flex uni-row">
                                <el-input v-model.number="store.radius" clearable />
                                <span class="ml10 f-12 f-gray nowrap">公里</span>
                            </div>
                        </el-form-item>
                        <el-form-item label=" " :label-width="100">
                            <el-button @click="update" type="primary" class="mt20">保存更新</el-button>
                        </el-form-item>
                    </el-form>
                </div>
            </el-col>
        </el-row>
    </div>
    <myfoot></myfoot>
</template>

<script setup>
import { onMounted, reactive, ref, shallowRef, getCurrentInstance } from 'vue'
import { ElMessage, ElNotification } from 'element-plus'
import { storeRead, storeUpdate } from '@/api/store.js'
import { deliveryTypes } from '@/api/delivery.js'
import { siteRead } from '@/api/site.js'

import multiselector from '@/components/selector/multiselector.vue'
import myhead from '@/pages/layout/header/seller.vue'
import myfoot from '@/pages/layout/footer/user.vue'
import menus from '@/pages/layout/menus/seller.vue'

const { proxy } = getCurrentInstance()
const visitor = ref({})
const store = reactive({})
const localities = reactive({ list: [], selected: [] })

/**
 * 如地图组件不显示，且浏览器报错不支持Webgl
 * 一、火狐浏览器地址栏中输入：about:config
 * 将webgl.force-enabled设置为true
 * 将webgl.disabled设置为false
 * 二、谷歌浏览器地址栏中输入：chrome://flags
 * 输入关键词：webgl，把列项开启
 */
const center = reactive({ lat: 39.908802, lng: 116.397502 })
const mapKey = ref()

onMounted(() => {
    visitor.value = JSON.parse(localStorage.getItem('visitor')) || {}

    siteRead(null, (data) => {
        mapKey.value = data.mapKey
    })

    storeRead({ store_id: visitor.value.store_id }, (data) => {
        Object.assign(store, data)
        if (data.latitude && data.longitude) {
            Object.assign(center, { lat: parseFloat(data.latitude), lng: parseFloat(data.longitude) })
        }

        deliveryTypes({ category: 'locality' }, (result) => {
            if (result) {
                localities.list = result
                localities.selected = [data.deliveryCode]
            }
        })
    })
})
const callback = (value) => {
    store.region_id = value.id
}

const changeClick = (value) => {
    if (localities.selected.length > 1) {
        localities.selected.splice(0, 1)
    }
    store.deliveryCode = localities.selected[0] || ''
}
const update = (event) => {
    let position = proxy.$refs.map.getCenter()
    storeUpdate({ latitude: position.lat, longitude: position.lng, region_id: store.region_id, address: store.address, deliveryCode: store.deliveryCode, radius: store.radius }, (data) => {
        ElMessage.success('门店更新成功！')
    })
}
const pan = (event) => {
    geometries.value = [{ id: 'center', position: proxy.$refs.map.getCenter() }]
}

const events = ref({ pan: pan })
const geometries = ref([{ id: 'center', position: center, styleId: 'marker', content: '' }])
const styles = shallowRef({ 'marker': {} })

</script>
<style scoped>
.map {
    border: 1px #ddd solid;
    width: 818px;
    height: 390px;
    overflow: hidden;
}
</style>