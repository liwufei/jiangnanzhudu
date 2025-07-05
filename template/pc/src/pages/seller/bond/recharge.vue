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
                            <el-breadcrumb-item>店铺</el-breadcrumb-item>
                            <el-breadcrumb-item>保证金</el-breadcrumb-item>
                            <el-breadcrumb-item>在线缴纳</el-breadcrumb-item>
                        </el-breadcrumb>
                    </div>
                </div>
                <div class="round-edge pd10 bgf mt20">
                    <div class="pl10 pt10">
                        <h3>缴纳保证金</h3>
                        <el-form :inline="true">
                            <el-form-item class="mt10" label="支付方式" :label-width="150">
                                <el-radio-group v-model="form.payment_code">
                                    <el-radio label="wxpay" size="large" border><i class="iconfont wxpay mr5"></i>微信支付
                                    </el-radio>
                                    <el-radio label="alipay" size="large" border><i class="iconfont alipay mr5"></i>支付宝
									</el-radio>
                                </el-radio-group>
                            </el-form-item>
                            <el-form-item class="mt10" label="缴纳金额" :label-width="150" style="width: 100%;">
                                <!-- <el-input-number v-model="form.money" :min="form.money" :max="10000" /> -->
                                <text class="f-red">{{ form.money }}</text>
                                <span class="ml10">元</span>
                            </el-form-item>
                            <el-form-item class="mt10" label="备注" :label-width="150">
                                <el-input v-model="form.remark" :rows="2" type="textarea" />
                            </el-form-item>
                            <el-form-item class="mt10" label=" " :label-width="150">
                                <el-button type="primary" @click="submit" :loading="loading" :disabled="loading">
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
import { cashierCheckpay } from '@/api/cashier.js'
import { storeRead, storeGrade } from '@/api/store.js'

import myhead from '@/pages/layout/header/seller.vue'
import myfoot from '@/pages/layout/footer/user.vue'
import menus from '@/pages/layout/menus/seller.vue'

const loading = ref(false)
const form = reactive({ payment_code: 'wxpay', money: 100, fundtype: 'bond' })
const dialogVisible = ref(false)

onMounted(() => {
    let visitor = JSON.parse(localStorage.getItem('visitor')) || {}
    storeRead({ store_id: visitor.store_id }, (data) => {
        storeGrade({ id: data.sgrade }, (result) => {
            if (result.charge > 0) form.money = result.charge
        })
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
                        redirect('/seller/bond/index')
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
    width: 500px;
    margin-top: 60px;
}

:deep() .el-form .el-radio__inner {
    display: none;
}
</style>
