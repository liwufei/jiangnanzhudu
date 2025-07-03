/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 * 
 * @Id server.js 2021.9.6 $
 * @author mosir
 */

import axios from 'axios'
import md5 from 'js-md5'
import { ElLoading, ElMessage } from 'element-plus'
import { siteUrl } from '@/common/util.js'

const APIURL = import.meta.env.VITE_API_URL ? import.meta.env.VITE_API_URL : siteUrl() + '/api'
const APPID = import.meta.env.VITE_API_APPID
const SECRET = import.meta.env.VITE_API_SECRET

/**
 * 请求数据
 * @param {String} api 
 * @param {Object} params 
 * @param {Function} callback 
 * @param {ElLoading} loading 
 */
export function request(api, params, callback, loading) {
	if (typeof loading != 'undefined' && loading != null) {
		loading.value = true
	}

	// 方式一：所有请求都需要TOKEN（安全高，效率稍低）
	// let token = localStorage.getItem('access_token') || ''
	// if (!token) {
	// 	http('auth/token', build(params), function (res) {
	// 		if (res.code == 0) {
	// 			localStorage.setItem('access_token', res.data.token)
	// 			http(api, build(params), callback, loading)
	// 		}
	// 	})
	// }

	// 方式二：访问用户级接口才需要TOKEN（安全稍低，效率高）
	http(api, build(params), callback, loading)
}

/**
 * 请求数据
 * @param {String} api 
 * @param {Object} params 
 * @param {Function} callback 
 * @param {ElLoading} loading 
 */
export function promise(api, params, callback, loading) {
	return new Promise((resolve, reject) => {
		request(api, params, (res) => {
			if (res.code == 0) {
				resolve(res.data)
				if (typeof callback == "function") {
					callback(res.data)
				}
			} else {
				console.log(res)
				//reject(new Error(res.message))
			}
		}, loading)
	})
}

/**
 * 上传文件
 * @param {String} api
 * @param {File} file
 * @param {Object} params 
 * @param {Function} callback 
 */
export function upload(api, file, params, callback, loading) {

	const formData = new FormData()
	formData.append(params.fileval ? params.fileval : 'file', file)

	var obj = build(params)
	for (var key in obj) {
		formData.append(key, obj[key])
	}

	http(api, formData, callback, loading)
}

/**
 * 发起请求
 * @param {String} api
 * @param {Object} params
 * @param {Function} callback
 * @param {ElLoading} loading
 */
function http(api, params, callback, loading) {

	if (typeof loading != 'undefined' && loading != null) {
		loading.value = true
	}
	axios.post(APIURL + '/' + api, params, { timeout: 10000, headers: { 'Content-Type': 'application/json' } }).then((res) => {

		// TOEKN过期或TOKEN非法，重新获取
		if (res.data.code == 4003 || res.data.code == 4002) {
			localStorage.removeItem('visitor')
			localStorage.removeItem('access_token')
			//location.reload()

			http('auth/token', build(params), function (res) {
				if (res.code == 0) {
					localStorage.setItem('access_token', res.data.token)
					http(api, build(params), callback, loading)
				}
			})

		}
		else {
			if (typeof callback == "function") {
				callback(res.data)
			}
			if (res.data.code > 0) {
				if (res.data.code <= 2000) {
					ElMessage.warning(res.data.message)
				} else {
					console.log(res.data)
				}
			}
		}

		if (typeof loading != 'undefined' && loading != null) {
			loading.value = false
		}
	}).catch((err) => {
		console.log(err)
		ElMessage.warning(err.message)
	})
}

/**
 * 创建请求BODY
 */
function build(params) {

	var obj = {}

	// 系统级参数
	obj.appid = APPID
	obj.version = '5.0'
	obj.sign_type = 'md5'
	obj.timestamp = formatTime(new Date())
	obj.format = 'json'

	// TOKEN
	obj.token = localStorage.getItem('access_token') || ''

	// 业务级参数，必须JSON，上传文件需要
	obj.params = JSON.stringify(params);

	// 排序
	obj = ksort(obj)

	let string = getEncryptionString(obj)
	obj.sign = md5(string + SECRET).toUpperCase()
	return obj
}

/**
 * 获取待加密的字符串
 */
function getEncryptionString(obj) {

	let string = ''
	for (var key in obj) {
		// 业务参数不参与签名
		if (key != 'params'  && key != 'sign') {
			obj[key] = encodeURIComponent(obj[key])
			string += key + "=" + character(obj[key]) + "&"
		}
	}

	return string ? string.substring(0, string.length - 1) : ''
}

/**
 * 数组排序
 */
function ksort(obj) {

	var keyArray = Object.keys(obj).sort()

	var newObj = {}
	for (var i = 0; i < keyArray.length; i++) {
		newObj[keyArray[i]] = obj[keyArray[i]];
	}
	return newObj
}

/**
 * 处理特殊字符
 * @param {Object} value
 */
function character(value) {
	if (typeof value === "string") {

		// 替换全部空格，使JSON编译后的字符串在前端和后端保持一致
		value = value.replace(/%20/g, '+')
	}
	return value
}

/**
 * 格式化时间（yyyy-mm-dd hh:ii:ss）
 * @param {Object} time
 */
function formatTime(time) {
	const year = time.getFullYear()
	const month = time.getMonth() + 1
	const day = time.getDate()
	const hour = time.getHours()
	const minute = time.getMinutes()
	const second = time.getSeconds()

	return [year, month, day].map(formatNumber).join('-') + ' ' + [hour, minute, second].map(formatNumber).join(':')
}

/**
 * @param {Object} n
 */
function formatNumber(n) {
	n = n.toString()
	return n[1] ? n : '0' + n
}
