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
							<el-breadcrumb-item>钱包</el-breadcrumb-item>
							<el-breadcrumb-item>在线充值</el-breadcrumb-item>
						</el-breadcrumb>
					</div>
				</div>
				<div class="round-edge pd10 bgf mt20">
					<div class="pl10 pt10">
						<h3>充值到余额</h3>
						<el-form>
							<el-form-item class="mt10" label="充值渠道" :label-width="150">
								<el-radio-group v-model="form.payment_code">
									<el-radio label="wxpay" size="large" border><i class="iconfont wxpay mr5"></i>微信支付
									</el-radio>
									<el-radio label="alipay" size="large" border><i class="iconfont alipay mr5"></i>支付宝
									</el-radio>
								</el-radio-group>
							</el-form-item>
							<el-form-item v-if="recharges.length > 0" class="mt10 recharges" label="充值金额"
								:label-width="150">
								<div @click="form.money = item.money" v-for="(item, index) in recharges" :key="index"
									:class="['item uni-flex uni-column mb20 mr20 center vertical-middle pointer', form.money == item.money ? 'selected' : '']">
									<p class="l-h20 f-16">{{ parseFloat(item.money) }}元</p>
									<span v-if="item.reward > 0" class="f-gray f-12 l-h20">送{{ parseFloat(item.reward ||
										0)
									}}元</span>
								</div>
							</el-form-item>
							<el-form-item v-else class="mt10" label="充值金额" :label-width="150">
								<el-input-number v-model="form.money" :min="0.01" :max="10000" /><span
									class=" ml10">元</span>

							</el-form-item>
							<el-form-item class="mt10" label="充值备注" :label-width="150">
								<el-input v-model="form.remark" :rows="2" type="textarea" style="width: 150px;" />
							</el-form-item>
							<el-form-item class="mt10" label=" " :label-width="150">
								<el-button type="primary" class="mt10" @click="submit" :loading="loading" :disabled="loading">
									下一步</el-button>
							</el-form-item>
						</el-form>
					</div>
				</div>
			</el-col>
		</el-row>
	</div>

	<el-dialog v-model="dialogVisible" title="微信支付" :width="300" :center="true" :draggable="true"
		:destroy-on-close="true" :close-on-click-modal="false" :before-close="close">
		<div class="center pb10 f-gray">
			<img class="mb10" :src="form.qrcode" width="200" height="200" />
			<p>使用微信扫一扫完成支付</p>
		</div>
	</el-dialog>

	<myfoot></myfoot>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { redirect } from '@/common/util.js'
import { depositRecharge } from '@/api/deposit.js'
import { rechargeSetting } from '@/api/recharge.js'
import { cashierCheckpay } from '@/api/cashier.js'

import myhead from '@/pages/layout/header/my.vue'
import myfoot from '@/pages/layout/footer/user.vue'
import menus from '@/pages/layout/menus/my.vue'

const loading = ref(false)
const form = reactive({ payment_code: 'wxpay', money: 100, fundtype: 'money' })
const recharges = ref([])
const dialogVisible = ref(false)

onMounted(() => {
	rechargeSetting(null, (data) => {
		recharges.value = data || []
		if (data.length > 0) form.money = data[0].money
	})
})

const submit = () => {
	loading.value = true

	depositRecharge(form, (data) => {
		if (form.payment_code == 'wxpay') {
			dialogVisible.value = true
			form.qrcode = data.qrcode

			let timer = setInterval(() => {
				cashierCheckpay(data, (result) => {
					if (result.ispay) {
						clearInterval(timer)
						//redirect('/deposit/trade/detail/' + result.tradeNo)
						redirect('/deposit/index')
					}
				})
			}, 1000)
		} else if (data.redirect) { // alipay
			location.href = data.redirect
			setTimeout(() => {
				loading.value = false
			}, 3000);
		}
	})
}

const close = (done) => {
	loading.value = false
	done()
}

</script>

<style scoped>
.el-form {
	width: 700px;
	margin-top: 60px;
}

.el-form .el-radio {
	min-width: 150px;
	margin-bottom: 10px;
	margin-right: 20px;
}

.el-form .recharges .item {
	width: 148px;
	height: 60px;
	border-radius: 4px;
	border: 1px #ddd solid;
}

.el-form .recharges .item.selected {
	border-color: var(--el-color-primary);
	color: var(--el-color-primary);
}

:deep() .el-form .el-radio__inner {
	display: none;
}
</style>
