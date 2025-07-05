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
							<el-breadcrumb-item>商品</el-breadcrumb-item>
							<el-breadcrumb-item>商品管理</el-breadcrumb-item>
						</el-breadcrumb>
					</div>
				</div>
				<div class="round-edge pl20 pr20 pt20 pb20 bgf mt20">
					<el-form :inline="true" class="mb10">
						<el-form-item label="商品类目">
							<multiselector api="category/list" idField="cate_id" nameField="cate_name"
								parentField="parent_id" :placeholder="true" @callback="callback">
							</multiselector>
						</el-form-item>
						<el-form-item label="推荐状态">
							<el-select v-model="form.recommended" @change="queryClick" placeholder="不限制" clearable>
								<el-option label="已推荐的商品" value="1" />
								<el-option label="未推荐的商品" value="0" />
							</el-select>
						</el-form-item>
						<el-form-item label="上架状态">
							<el-select v-model="form.if_show" @change="queryClick" placeholder="不限制" clearable>
								<el-option label="上架中" value="1" />
								<el-option label="已下架" value="0" />
							</el-select>
						</el-form-item>
						<el-form-item label="禁售状态">
							<el-select v-model="form.closed" @change="queryClick" placeholder="不限制" clearable>
								<el-option label="正常" value="0" />
								<el-option label="禁售中" value="1" />
							</el-select>
						</el-form-item>
						<el-form-item label="商品排序">
							<el-select v-model="form.orderby" @change="queryClick" placeholder="不限制" clearable>
								<el-option label="销量从高到低" value="sales|desc" />
								<el-option label="点击量从高到低" value="views|desc" />
								<el-option label="价格从高到低" value="price|desc" />
								<el-option label="价格从低到高" value="price|asc" />
								<el-option label="上架时间从近到远" value="add_time|desc" />
								<el-option label="上架时间从远到近" value="add_time|asc" />
								<el-option label="评论数从多到少" value="comments|desc" />
							</el-select>
						</el-form-item>
						<el-form-item label="商品名称">
							<el-input v-model="form.keyword" clearable />
						</el-form-item>
						<div class="block">
							<el-form-item label="商品品牌">
								<el-input v-model="form.brand" clearable />
							</el-form-item>
							<el-form-item>
								<el-button @click="queryClick" type="primary" class="f-13">查询</el-button>
							</el-form-item>
						</div>
					</el-form>
					<el-table :data="gallery" :border="false" :stripe="false" size="large" v-loading="loading"
						:header-cell-style="{ 'background': '#f3f8fe' }">
						<el-table-column type="selection" />
						<el-table-column width="70" label="图片" align="center" fixed="left">
							<template #default="scope">
								<router-link :to="'/goods/detail/' + scope.row.goods_id" class="rlink">
									<img :src="scope.row.default_image" width="50" height="50" />
								</router-link>
							</template>
						</el-table-column>
						<el-table-column label="商品名称" width="300">
							<template #default="scope">
								<p class="l-h17"><router-link :to="'/goods/detail/' + scope.row.goods_id"
										class="rlink">{{
			(scope.row.goods_name) }}</router-link></p>
							</template>
						</el-table-column>
						<el-table-column prop="category" label="所在分类" width="200">
							<template #default="scope">
								<p class="l-h17" v-if="scope.row.category">{{ (scope.row.category).join(' / ') }}</p>
							</template>
						</el-table-column>
						<el-table-column prop="price" label="价格" width="150" align="center">
							<template #default="scope">
								<strong class="f-yahei">{{ currency(scope.row.price) }}</strong>
							</template>
						</el-table-column>

						<el-table-column prop="stocks" label="库存" width="100" sortable />
						<el-table-column prop="if_show" label="上架" width="60" align="center">
							<template #default="scope">
								<div class="flex-center">
									<el-icon
										@click="updateClick(scope.$index, 'if_show', scope.row.if_show == 1 ? 0 : 1)"
										v-if="scope.row.if_show == 1" class="f-green pointer" :size="16">
										<SuccessFilled />
									</el-icon>
									<el-icon
										@click="updateClick(scope.$index, 'if_show', scope.row.if_show == 1 ? 0 : 1)"
										v-else class="f-gray pointer" :size="16">
										<SuccessFilled />
									</el-icon>
								</div>
							</template>
						</el-table-column>

						<el-table-column prop="recommended" label="推荐" width="100" align="center">
							<template #default="scope">
								<div class="flex-center">
									<el-icon
										@click="updateClick(scope.$index, 'recommended', scope.row.recommended == 1 ? 0 : 1)"
										v-if="scope.row.recommended == 1" class="f-green pointer" :size="16">
										<SuccessFilled />
									</el-icon>
									<el-icon
										@click="updateClick(scope.$index, 'recommended', scope.row.recommended == 1 ? 0 : 1)"
										v-else class="f-gray pointer" :size="16">
										<SuccessFilled />
									</el-icon>
								</div>
							</template>
						</el-table-column>
						<el-table-column prop="closed" label="禁售" width="100" align="center">
							<template #default="scope">
								<el-icon v-if="scope.row.closed == 1" class="f-c60" :size="16">
									<SuccessFilled />
								</el-icon>
								<span v-else></span>
							</template>
						</el-table-column>
						<el-table-column prop="isnew" label="新品" width="60" align="center">
							<template #default="scope">
								<div class="flex-center">
									<el-icon @click="updateClick(scope.$index, 'isnew', scope.row.isnew == 1 ? 0 : 1)"
										v-if="scope.row.isnew == 1" class="f-green pointer" :size="16">
										<SuccessFilled />
									</el-icon>
									<el-icon @click="updateClick(scope.$index, 'isnew', scope.row.isnew == 1 ? 0 : 1)"
										v-else class="f-gray pointer" :size="16">
										<SuccessFilled />
									</el-icon>
								</div>
							</template>
						</el-table-column>

						<el-table-column prop="brand" label="品牌" width="100" sortable />
						<el-table-column prop="sales" label="销量" width="100" align="center" sortable />
						<el-table-column prop="collects" label="收藏" width="100" align="center" sortable />
						<el-table-column prop="views" label="人气" width="100" align="center" sortable />
						<el-table-column prop="add_time" label="上架时间" width="120" sortable />
						<el-table-column fixed="right" label="操作" width="200" align="center">
							<template #default="scope">
								<el-button type="danger" size="small" @click="priceClick(scope.$index)" plain>改价
								</el-button>
								<el-button type="primary" size="small"
									@click="redirect('/seller/goods/build/' + scope.row.goods_id)" plain>编辑
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
	<modifyprice v-if="gallery.length > 0" title="改价" :visible="dialogPriceVisible" :data="gallery[modifyIndex]"
		@close="dialogClose"></modifyprice>
	<myfoot></myfoot>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { ElMessageBox, ElMessage } from 'element-plus'
import { goodsSearch, goodsDelete, goodsUpdate } from '@/api/goods.js'
import { currency, redirect } from '@/common/util.js'

import myhead from '@/pages/layout/header/seller.vue'
import myfoot from '@/pages/layout/footer/user.vue'
import menus from '@/pages/layout/menus/seller.vue'
import modifyprice from '@/components/dialog/goods/price.vue'
import multiselector from '@/components/selector/multiselector.vue'

const loading = ref(false)
const gallery = ref([])
const pagination = ref({})
const form = reactive({ orderby: 'add_time|desc' })
const visitor = ref({})
const route = useRoute()
const dialogPriceVisible = ref(false)
const modifyIndex = ref(0)

onMounted(() => {
	visitor.value = JSON.parse(localStorage.getItem('visitor'))
	//getList()
})

const queryClick = () => {
	getList()
}

const callback = (value) => {
	form.cate_id = value.id
	getList(route.query)
}

const updateClick = (index, field, value) => {
	goodsUpdate({ goods_id: gallery.value[index].goods_id, [field]: value }, (data) => {
		gallery.value[index][field] = value
		//ElMessage.success('更新成功')
	})
}

const deleteClick = (value) => {
	ElMessageBox.confirm('您确定要删除该商品吗？', '提示', {
		confirmButtonText: '确定',
		type: 'warning'
	}).then(() => {
		goodsDelete({ goods_id: gallery.value[value].goods_id }, () => {
			ElMessage.success('删除成功！')
			gallery.value.splice(value, 1)
		})
	}).catch(() => { })
}

const priceClick = (value) => {
	dialogPriceVisible.value = true
	modifyIndex.value = value
}

const dialogClose = (value) => {
	dialogPriceVisible.value = false
	Object.assign(gallery.value[modifyIndex.value], value ? value : {})
}

const handleSizeChange = (value) => {
	getList({ page_size: value })
}
const handleCurrentChange = (value) => {
	getList({ page: value, page_size: pagination.value.page_size })
}

function getList(params) {
	goodsSearch(Object.assign(form, params, { store_id: visitor.value.store_id }), (data) => {
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

:deep() .el-table__header-wrapper .el-table-column--selection .el-checkbox {
	vertical-align: middle;
}
</style>
