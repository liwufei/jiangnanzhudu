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
							<el-breadcrumb-item>资产</el-breadcrumb-item>
							<el-breadcrumb-item>优惠券列表</el-breadcrumb-item>
						</el-breadcrumb>
					</div>
				</div>
				<div class="round-edge pl20 pr20 pt20 pb10 bgf mt20">
					<el-form :inline="true">
						<el-form-item label="状态">
							<el-select v-model="form.available" @change="queryClick" placeholder="不限制" clearable>
								<el-option label="有效" value="1" />
								<el-option label="无效" value="0" />
							</el-select>
						</el-form-item>

						<el-form-item label="名称">
							<el-input v-model="form.keyword" clearable />
						</el-form-item>
						<el-form-item label="优惠券号码">
							<el-input v-model="form.coupon_sn" clearable />
						</el-form-item>
						<el-form-item>
							<el-button @click="queryClick" type="primary" class="f-13">查询</el-button>
						</el-form-item>
					</el-form>
				</div>
				<div class="round-edge pl20 pr20 pt20 pb20 bgf mt20">
					<el-table :data="gallery" v-loading="loading" size="large" :border="false" :stripe="false"
						:header-cell-style="{ 'background': '#f3f8fe' }">
						<el-table-column type="selection" />
						<el-table-column prop="name" width="150" label="名称" />
						<el-table-column label="优惠券号码" width="150">
							<template #default="scope">
								<strong class="f-price">{{ scope.row.coupon_sn }}</strong>
							</template>
						</el-table-column>
						<el-table-column label="优惠金额（元）" width="140">
							<template #default="scope">
								<strong class="f-price">{{ scope.row.money }}</strong>
							</template>
						</el-table-column>
						<el-table-column label="购满金额（元）" width="140">
							<template #default="scope">
								<strong class="f-price">{{ scope.row.amount }}</strong>
							</template>
						</el-table-column>
						<el-table-column prop="start_time" label="生效时间" width="110" sortable />
						<el-table-column prop="end_time" label="过期时间" width="110" sortable />
						<el-table-column prop="store_name" label="发放店铺" width="110" sortable />
						<el-table-column fixed="right" label="状态" width="100">
							<template #default="scope">
								<strong v-if="scope.row.available == 1" class="f-red">有效</strong>
								<strong v-else-if="scope.row.remain_times == 0" class="f-green">已使用</strong>
								<strong v-else>已失效</strong>
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

	<couponBuild title="新增优惠券" :visible="dialogVisible" @close="dialogClose"></couponBuild>

	<myfoot></myfoot>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { myCouponList } from '@/api/coupon.js'

import myhead from '@/pages/layout/header/my.vue'
import myfoot from '@/pages/layout/footer/user.vue'
import menus from '@/pages/layout/menus/my.vue'
import couponBuild from '@/components/dialog/coupon/build.vue'

const loading = ref(false)
const gallery = ref([])
const pagination = ref({})
const form = reactive({})
const dialogVisible = ref(false)

onMounted(() => {
	getList()
})

const queryClick = () => {
	getList()
}

const handleSizeChange = (value) => {
	getList({ page_size: value })
}
const handleCurrentChange = (value) => {
	getList({ page: value, page_size: pagination.value.page_size })
}
const dialogClose = (value) => {
	dialogVisible.value = false
	if (value) {
		getList()
	}
}
function getList(params) {
	myCouponList(Object.assign(form, params), (data) => {
		gallery.value = data.list
		pagination.value = data.pagination
	}, loading)
}
</script>

<style scoped>
.el-table,
.el-form-item {
	font-size: 13px;
}

.el-table__header-wrapper .el-table-column--selection .el-checkbox {
	vertical-align: middle;
}
</style>
