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
							<el-breadcrumb-item>营销</el-breadcrumb-item>
							<el-breadcrumb-item>分销商品</el-breadcrumb-item>
						</el-breadcrumb>
					</div>
				</div>
				<div class="round-edge pd10 bgf mt20">
					<h3 class="pd10 mb20">分销商品设置</h3>

					<el-form :inline="true" class="pd10">
						<el-form-item label="选择商品" :label-width="100">
							<el-button :disabled="distribute.goods_id" @click="dialog.visible = true" type="primary"
								plain>选择商品</el-button>
						</el-form-item>
						<el-form-item v-if="goods.goods_id" label=" " :label-width="100">
							<div class="uni-flex uni-row">
								<p><img :src="goods.default_image" width="60" height="60" /></p>
								<p class="l-h20 width-surplus ml10">{{ goods.goods_name }}</p>
							</div>
						</el-form-item>
						<el-form-item label=" " :label-width="100" style="margin-right:0;">
							<p class="tips pl10 pr10 pt5 pb5 f-12 f-c60 flex-middle" style="width:100%">
								<el-icon :size="14" class="mr5">
									<WarningFilled />
								</el-icon> 分销比率适用于所有规格，暂不支持指定规格设置比率
							</p>
							<el-table :data="gallery.list" :border="true" :stripe="true" :header-cell-style="{ 'background': '#f3f8fe' }">
								<el-table-column
									:label="goods.spec_qty > 0 ? (goods.spec_name_1 + ' ' + goods.spec_name_2 || '') : '规格'">
									<template #default="scope">
										<text>{{ goods.spec_qty > 0 ? scope.row.specification : '默认规格' }}</text>
									</template>
								</el-table-column>
								<el-table-column prop="stock" label="库存" width="200" />
								<el-table-column label="价格" width="200">
									<template #default="scope">
										{{ scope.row.price }}
									</template>
								</el-table-column>
							</el-table>
						</el-form-item>
						<el-form-item label="佣金策略" :label-width="100">
							<div class="ml10 mr10">
								<p class="mb10">一级返佣 <el-input-number v-model="distribute.ratio1"
										controls-position="right" :step="0.01" :min="0.01" :max="0.99">
									</el-input-number>
								</p>
								<p class="mb10">二级返佣 <el-input-number v-model="distribute.ratio2"
										controls-position="right" :step="0.01" :min="0.01" :max="0.99">
									</el-input-number>
								</p>
								<p class="mb10">三级返佣 <el-input-number v-model="distribute.ratio3"
										controls-position="right" :step="0.01" :min="0.01" :max="0.99">
									</el-input-number>
								</p>
							</div>
						</el-form-item>
						<el-form-item label="启用" :label-width="100">
							<el-switch v-model="distribute.enabled" @change="changeClick" :active-value="1"
								:inactive-value="0" />
						</el-form-item>

						<el-form-item label=" " :label-width="100" class="mt10">
							<el-button type="primary" @click="submit" :loading="loading"><text
									class="ml20 mr20">提交</text>
							</el-button>
						</el-form-item>
					</el-form>
				</div>
			</el-col>
		</el-row>
	</div>

	<selector title="选择商品" :visible="dialog.visible" @close="dialogClose" :selected="dialog.selected" :limit="1">
	</selector>

	<myfoot></myfoot>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue'
import { ElMessage, ElNotification } from 'element-plus'
import { useRoute } from 'vue-router'
import { goodsRead, goodsSpecs } from '@/api/goods.js'
import { distributeRead, distributeUpdate } from '@/api/distribute.js'

import router from '@/router'
import selector from '@/components/dialog/goods/list.vue'
import myhead from '@/pages/layout/header/seller.vue'
import myfoot from '@/pages/layout/footer/user.vue'
import menus from '@/pages/layout/menus/seller.vue'

const route = useRoute()
const loading = ref(false)
const dialog = reactive({ visible: false, selected: [] })
const distribute = reactive({ enabled: 1, ratio1: 0.3, ratio2: 0.2, ratio3: 0.1 })
const gallery = reactive({ list: [] })
const goods = ref({})

onMounted(() => {
	if (route.params.goods_id) {
		distributeRead({ goods_id: route.params.goods_id }, (data) => {
			if (data) {
				Object.assign(distribute, data, { enabled: parseInt(data.enabled) })
				dialog.selected = [parseInt(distribute.goods_id)]
				query()
			}
		})
	}
})

const changeClick = (value) => {
	distribute.enabled = value
}

const submit = () => {

	if (gallery.list.length < 1) {
		return ElMessage.warning('请选择商品')
	}

	distributeUpdate(Object.assign(distribute, { goods_id: goods.value.goods_id }), (data) => {
		ElNotification({
			title: '提示',
			message: '分销商品设置成功！',
			type: 'success',
			position: 'bottom-left',
			duration: 2000,
			onClose: function () {
				router.replace('/seller/distribute/list')
			}
		})
	}, loading)
}

const dialogClose = (value) => {
	dialog.visible = false
	dialog.selected = value || []

	if (dialog.selected.length > 0) {
		query()
	}
}

function query() {
	goodsRead({ goods_id: dialog.selected[dialog.selected.length - 1] }, (data) => {
		goods.value = data

		goodsSpecs({ goods_id: data.goods_id }, (specs) => {
			for (let index in specs.list) {
				let item = specs.list[index]
				gallery.list[index] = {
					specification: item.spec_1 + ' ' + item.spec_2,
					price: item.price,
					stock: item.stock,
					image: item.image,
					spec_id: item.spec_id
				}
			}
		})
	})
}

</script>
<style scoped>
.el-form .el-form-item {
	margin-right: 40%;
}

.el-form .el-input {
	width: 220px;
}

.el-form .tips {
	width: 240px;
	background-color: rgb(235, 248, 252);
}
</style>