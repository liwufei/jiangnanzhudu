<template>
	<el-affix :offset="0" class="relative" style="z-index: 2001;">
		<div class="header pt10 pb10 mb5 f-white">
			<div class="uni-flex uni-row w pt5 pb5 width-between">
				<div class="uni-flex uni-row flex-middle ml5">
					<router-link to="/" class="rlink">
						<!--<img class="block" height="24" src="@/assets/images/logo.png">-->
						<img class="block" height="24" :src="site.logo">
					</router-link>
					<span class="ml10 mr10 f-20">·</span>
					<span class="f-15 line-clamp-1">商家中心</span>
				</div>
				<div class="uni-flex uni-row word-break-all">
					<div class="flex-middle mr20">
						<el-popover :width="300">
							<template #reference>
								<p class="vertical-middle">
									<i class="iconfont icon-app f-20 f-blue"></i>
									<span class="ml5 mr5">APP客户端</span>
									<el-icon>
										<arrow-down />
									</el-icon>
								</p>
							</template>
							<div class="uni-flex uni-row center">
								<p>
									<img :src="qrcode.ios" class="image" />
								<h3 class="f-14">iOS客户端</h3>
								</p>
								<p>
									<img :src="qrcode.android" class="image" />
								<h3 class="f-14">Android客户端</h3>
								</p>
							</div>
						</el-popover>
					</div>
					<div class="flex-middle mr20">
						<el-popover>
							<template #reference>
								<p class="vertical-middle">
									<i class="iconfont icon-xiaochengxu f-20 f-green"></i>
									<span class="ml5 mr5">小程序</span>
									<el-icon>
										<arrow-down />
									</el-icon>
								</p>
							</template>
							<div class="uni-flex uni-column center">
								<img v-if="qrcode.applet" :src="qrcode.applet" class="image" />
								<img v-else src="@/assets/images/appletcode.jpg">
							</div>
						</el-popover>
					</div>
					<div v-if="visitor.userid" class="flex-middle mr5 ml10">
						<el-popover>
							<template #reference>
								<p class="vertical-middle">
									<el-avatar :size="36" :src="visitor.portrait" class="mr5" />
									<span class="mr5">{{ visitor.nickname || visitor.username }}</span>
									<el-icon>
										<arrow-down />
									</el-icon>
								</p>
							</template>
							<div class="uni-flex uni-column center">
								<router-link to="/user/profile" class="rlink mb10">修改昵称</router-link>
								<router-link to="/user/profile" class="rlink mb10">修改头像</router-link>
								<router-link to="/user/password" class="rlink mb10">修改密码</router-link>
								<router-link to="/user/phone" class="rlink mb10">修改手机</router-link>
								<p @click="logout" class="rlink">安全退出</p>
							</div>
						</el-popover>
					</div>
				</div>
			</div>
		</div>
	</el-affix>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { redirect } from '@/common/util.js'
import { userLogout } from '@/api/user.js'
import { weixinQrcode } from '@/api/weixin.js'
import { siteRead } from '@/api/site.js'

const visitor = ref({})
const qrcode = reactive({ applet: '', ios: '', android: '' })
const site = ref({ logo: '' })

onMounted(() => {
	visitor.value = JSON.parse(localStorage.getItem('visitor')) || {}

	siteRead(null, (data) => {
		Object.assign(qrcode, data.qrcode)
		site.value.logo = data.site_logo
	})
	weixinQrcode({ code: 'merapplet' }, (data) => {
		if (data) qrcode.applet = data.codeurl
	})
})

const logout = () => {
	userLogout(() => {
		redirect('/seller/login')
	})
}
</script>

<style scoped>
.header {
	background-color: #505458;
}

.image {
	width: 145px;
	height: 145px;
}

:deep() .el-avatar {
	background: none;
}
</style>
