<template>
	<myhead :exclude="['category', 'imagead']"></myhead>
	<div class="main w mt20">
		<div v-for="item in categories" class="item mb20 mt20">
			<h2 class="">
				<router-link :to="'/search/goods/' + item.id" class="rlink f-17">
					{{ item.value }}
				</router-link>
			</h2>
			<div class="round-edge bgf mt20 pd10 uni-flex flex-wrap">
				<div v-for="child in Object.values(item.children)" class="pd10">
					<router-link :to="'/search/goods/' + child.id" class="rlink bold f-13 f-c55">
						{{ child.value }}</router-link>

					<!-- <el-row>
						<el-col :span="2">
							<router-link :to="'/search/goods/' + child.id" class="rlink bold f-13 f-c55">{{
								child.value
							}}</router-link>
						</el-col>
						<el-col :span="22">
							<router-link v-for="child2 in Object.values(child.children)" :to="'/search/goods/' + child2.id"
								class="mr20 rlink f-13 word-break-all l-h20">
								{{ child2.value }}
							</router-link>
						</el-col>
					</el-row> -->
				</div>

			</div>
		</div>
	</div>
	<myfoot></myfoot>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { categoryTree } from '@/api/category.js'

import myhead from '@/pages/layout/header/home.vue'
import myfoot from '@/pages/layout/footer/home.vue'

const categories = ref([])

onMounted(() => {
	let now = new Date()
	let storages = JSON.parse(localStorage.getItem('categories'))
	if (storages && storages.expired > now.getTime()) {
		categories.value = storages.value || []
	}
	else {
		categoryTree({ layer: 2 }, (data) => {
			categories.value = data
			localStorage.setItem('categories', JSON.stringify({ value: categories.value, expired: now.getTime() + 86400 }))
		})
	}
})

</script>

<style scoped>
.word-break-all {
	line-height: 25px;
}
</style>
