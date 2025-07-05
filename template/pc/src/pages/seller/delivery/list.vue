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
							<el-breadcrumb-item>物流</el-breadcrumb-item>
							<el-breadcrumb-item>
								{{ route.params.type == 'express' ? '快递发货' : '同城配送' }}
							</el-breadcrumb-item>
						</el-breadcrumb>
					</div>
				</div>
				<div class="round-edge pl20 pr20 pt20 pb20 bgf mt20">
					<div class="mb20">
						<el-button type="primary"
							@click="redirect('/seller/delivery/' + route.params.type + '/build')">新增模板
						</el-button>
						<el-button v-if="route.params.type == 'locality'"
							@click="redirect('/seller/delivery/locality/location')">门店配置</el-button>
					</div>
					<el-table :data="gallery" :border="false" :stripe="false" size="large" v-loading="loading"
						:header-cell-style="{ 'background': '#f3f8fe' }">
						<el-table-column prop="name" label="名称" />
						<el-table-column prop="label" label="类型" />
						<el-table-column prop="created" label="创建时间" />
						<el-table-column prop="enabled" label="是否默认">
							<template #default="scope">
								<el-switch @change="(value) => { changeClick(scope.row, value) }"
									:value="parseInt(scope.row.enabled)" :active-value="1" :inactive-value="0" />
							</template>
						</el-table-column>
						<el-table-column fixed="right" label="操作" width="140" align="center">
							<template #default="scope">
								<el-button type="primary" size="small"
									@click="redirect('/seller/delivery/' + route.params.type + '/build/' + scope.row.id)"
									plain>编辑
								</el-button>
								<el-button type="warning" size="small" @click="deleteClick(scope.$index)" plain>删除
								</el-button>
							</template>
						</el-table-column>
					</el-table>
					<div v-if="pagination.total > 0" class="mt20 mb20">
						<el-pagination v-model:currentPage="pagination.page" v-model:page-size="pagination.page_size"
							:page-sizes="[10, 50, 100, 200]" :background="false"
							layout="total, sizes, prev, pager, next" :total="pagination.total"
							@size-change="handleSizeChange" @current-change="handleCurrentChange"
							:hide-on-single-page="false" class="center" />
					</div>
				</div>
			</el-col>
		</el-row>
	</div>
	<myfoot></myfoot>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useRoute } from 'vue-router'
import { ElMessageBox, ElMessage } from 'element-plus'
import { redirect } from '@/common/util.js'
import { deliveryList, deliveryDelete, deliveryUpdate } from '@/api/delivery.js'

import myhead from '@/pages/layout/header/seller.vue'
import myfoot from '@/pages/layout/footer/user.vue'
import menus from '@/pages/layout/menus/seller.vue'

const route = useRoute()
const loading = ref(false)
const gallery = ref([])
const pagination = ref({})
const visitor = ref({})

onMounted(() => {
	visitor.value = JSON.parse(localStorage.getItem('visitor')) || {}
	getList()
})

const handleSizeChange = (value) => {
	getList({ page_size: value })
}

const handleCurrentChange = (value) => {
    getList({ page: value, page_size: pagination.value.page_size })
}

const changeClick = (delivery, value) => {
	delivery.enabled = value
	deliveryUpdate(delivery, (data) => { })
}
const deleteClick = (value) => {
	ElMessageBox.confirm('您确定要删除该运费模板吗？', '提示', {
		confirmButtonText: '确定',
		type: 'warning'
	}).then(() => {
		deliveryDelete({ id: gallery.value[value].id }, () => {
			ElMessage.success('删除成功！')
			gallery.value.splice(value, 1)
		})
	}).catch(() => { })
}

function getList(params) {
	deliveryList(Object.assign({ store_id: visitor.value.userid, type: route.params.type }, params || {}), (data) => {
		gallery.value = data.list || []
		pagination.value = data.pagination
	}, loading)
}

</script>