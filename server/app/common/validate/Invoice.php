<?php

namespace app\common\validate;

use think\Validate;

/**
 * 发票
 */
class Invoice extends Validate
{
    protected $rule = [
        'invoice_state' => 'require|in:1,2',
        'invoice_title' => 'require|max:50',
        'invoice_content' => 'require|max:10',
        'invoice_code' => 'require|max:20',
        'invoice_company' => 'require|max:50',
        'invoice_company_code' => 'require|max:20',
        'invoice_reg_addr' => 'require|max:50',
        'invoice_reg_phone' => 'require|max:30',
        'invoice_reg_bname' => 'require|max:30',
        'invoice_reg_baccount' => 'require|max:30',
    ];
    protected $message = [
        'invoice_state.require' => '发票类型必填',
        'invoice_state.in' => '发票类型错误',
        'invoice_title.require' => '发票抬头必填',
        'invoice_title.max' => '发票抬头长度必须小于50',
        'invoice_content.require' => '发票内容必填',
        'invoice_content.max' => '发票内容长度必须小于10',
        'invoice_code.require' => '纳税人识别号必填',
        'invoice_code.max' => '纳税人识别号长度必须小于20',
        'invoice_company.require' => '单位名称必填',
        'invoice_company.max' => '单位名称长度必须小于50',
        'invoice_company_code.require' => '纳税人识别号必填',
        'invoice_company_code.max' => '纳税人识别号长度必须小于20',
        'invoice_reg_addr.require' => '注册地址必填',
        'invoice_reg_addr.max' => '注册地址长度必须小于50',
        'invoice_reg_phone.require' => '注册电话必填',
        'invoice_reg_phone.max' => '注册电话长度必须小于30',
        'invoice_reg_bname.require' => '开户银行必填',
        'invoice_reg_bname.max' => '开户银行长度必须小于30',
        'invoice_reg_baccount.require' => '银行帐户必填',
        'invoice_reg_baccount.max' => '银行帐户长度必须小于30',
    ];
    protected $scene = [
        'invoice_1_update' => ['invoice_state', 'invoice_title', 'invoice_content', 'invoice_code'],
        'invoice_2_update' => ['invoice_company', 'invoice_company_code', 'invoice_reg_addr', 'invoice_reg_phone', 'invoice_reg_bname', 'invoice_reg_baccount'],
    ];
}
