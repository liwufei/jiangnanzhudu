<template>
    <div v-loading="loading">
        <el-scrollbar :height="gallery.length > 4 ? '340px' : ''">
            <el-timeline class="pl10 pt10 pr10 mt10">
                <el-timeline-item v-for="(item, index) in gallery" :color="index == 0 ? '#0bbd87' : ''"
                    :timestamp="item.created">
                    <span :class="index == 0 ? 'bold' : ''">{{ item.status }}</span>
                    <p v-if="item.remark" class="f-gray mt5">{{ item.remark }}</p>
                </el-timeline-item>
            </el-timeline>
        </el-scrollbar>
    </div>
</template>
<script setup>
import { ref, watch } from 'vue'
import { orderTimeline } from '@/api/order.js';

const props = defineProps({
    data: {
        type: Object,
        default: () => {
            return {}
        }
    }
})

const loading = ref(true)
const gallery = ref([])

watch(() => props.data, (value) => {
    orderTimeline({ order_id: value.order_id }, (data) => {
        gallery.value = data
    }, loading)
})

</script>
