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
							<el-breadcrumb-item>运费设置</el-breadcrumb-item>
						</el-breadcrumb>
					</div>
				</div>
				<div class="round-edge pd10 bgf mt20">
                    <el-form class="pd10 mt20">
						<el-form-item label="模板名称" :label-width="100" class="required">
							<el-input v-model="delivery.name" maxlength="10" show-word-limit clearable />
						</el-form-item>
						<el-form-item label="计费方式" :label-width="100">
							<el-checkbox value="weight" checked>按重量计费</el-checkbox>
							<el-checkbox value="quantity" disabled>按件计费</el-checkbox>
						</el-form-item>
						<el-form-item label="配送区域及运费" :label-width="100" class="required" style="margin-right: 0;">
							<div v-if="template = delivery.rules.arrived" class="mb10 w-full">
								<el-table :data="template" :border="true" :stripe="false" v-loading="loading"
									class="f-13" :header-cell-style="{ 'background': '#f3f8fe' }">
									<el-table-column prop="dests" label="配送地区" width="240">
										<template #default="scope">
											<el-cascader v-if="scope.$index > 0" v-model="scope.row.dests"
												:options="cascader.options" :props="cascader.props" />
											<div v-else-if="route.params.type == 'express'" class="f-12">
												<text class="bold">默认运费</text>
												<span class="f-gray">（不设置指定区域运费时生效）</span>
											</div>
											<div v-else class="f-12">
												<text class="bold">基础配送费</text>
											</div>
										</template>
									</el-table-column>

									<el-table-column prop="start" label="首重(kg)">
										<template #default="scope">
											<el-input v-model="scope.row.start"
												oninput="value=value.replace(/[^\d^\.]+/g,'')" clearable />
										</template>
									</el-table-column>
									<el-table-column prop="postage" label="首费(元)">
										<template #default="scope">
											<el-input v-model="scope.row.postage"
												oninput="value=value.replace(/[^\d^\.]+/g,'')" clearable />
										</template>
									</el-table-column>
									<el-table-column prop="plus" label="续重(kg)">
										<template #default="scope">
											<el-input v-model="scope.row.plus"
												oninput="value=value.replace(/[^\d^\.]+/g,'')" clearable />
										</template>
									</el-table-column>
									<el-table-column prop="postageplus" label="续费(元)">
										<template #default="scope">
											<el-input v-model="scope.row.postageplus"
												oninput="value=value.replace(/[^\d^\.]+/g,'')" clearable />
										</template>
									</el-table-column>
									<el-table-column fixed="right" label="操作" width="100" align="center">
										<template #default="scope">
											<el-button type="warning" size="small"
												@click="deleteClick(template, scope.$index)" plain>删除
											</el-button>
										</template>
									</el-table-column>
								</el-table>
							</div>
							<div class="f-blue flex-middle">
								<el-icon :size="16">
									<CirclePlus />
								</el-icon>
								<span @click="addArea(template)" class="ml5 pointer">添加指定地区运费</span>
							</div>
						</el-form-item>
						<el-form-item v-if="route.params.type == 'locality'" label="起送金额" :label-width="100"
							class="required">
							<el-input v-model.number="delivery.basemoney" oninput="value=value.replace(/[^\d]/g,'')"
								style="width:100px" clearable />
							<p class="f-gray f-13 ml5">未达起送金额不予配送</p>
						</el-form-item>
						<el-form-item label="是否默认" :label-width="100">
							<el-switch v-model="delivery.enabled" :active-value="1" :inactive-value="0" />
						</el-form-item>
						<el-form-item label=" " :label-width="100">
							<el-button type="primary" @click="submit" :loading="loading">提交</el-button>
						</el-form-item>
					</el-form>
				</div>
			</el-col>
		</el-row>
	</div>

	<myfoot></myfoot>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useRoute } from 'vue-router'
import { ElNotification } from 'element-plus'
import { isEmpty, redirect } from '@/common/util.js'
import { deliveryRead, deliveryUpdate } from '@/api/delivery.js'
import { regionTree } from '@/api/region.js'

import myhead from '@/pages/layout/header/seller.vue'
import myfoot from '@/pages/layout/footer/user.vue'
import menus from '@/pages/layout/menus/seller.vue'

const route = useRoute()
const loading = ref(false)
const cascader = reactive({ selected: {}, options: [], props: { value: 'id', label: 'value', multiple: true, checkStrictly: true } })
const delivery = ref({ name: '默认运费', basemoney: 29, type: route.params.type, enabled: 1, rules: { arrived: [{ dests: 0 }] } })

onMounted(() => {
	deliveryRead({ id: parseInt(route.params.id) }, (data) => {
		if (data) delivery.value = data
	})

	regionTree({ layer: 4 }, (data) => {
		cascader.options = data || []
	})
})

const addArea = (template) => {
	template.push({ dests: [] })
}

const deleteClick = (template, value) => {
	template.splice(value, 1)
}

const submit = () => {
	deliveryUpdate(delivery.value, (data) => {
		ElNotification({
			title: '提示',
			message: '运费模板设置成功！',
			type: 'success',
			position: 'bottom-left',
			duration: 2000,
			onClose: () => {
				redirect('/seller/delivery/' + route.params.type + '/list')
			}
		})
	}, loading)
}

</script>
<style scoped>
.el-form .el-form-item {
	margin-right: 50%;
}

.el-form .border {
	border: 1px #eee solid;
}

.el-form .border-b0 {
	border-bottom: 0;
}

.el-form .el-input.small {
	width: 100px;
}

.el-form .tips {
	width: 240px;
	background-color: rgb(235, 248, 252);
}

:deep() .el-form .required label:before {
	content: "*";
	color: var(--el-color-danger);
	margin-right: 4px;
}
</style>