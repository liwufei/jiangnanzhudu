<?php

namespace app\common\validate;

use think\Validate;

/**
 * 快递鸟
 */
class  ExpresscfKdnConfig extends Validate
{
    protected $rule = [
        'express_code' => 'require',
        'expresscf_kdn_config_pay_type' => 'require',
    ];
    protected $message = [
        'express_code.require' => '快递公司编码必填',
        'expresscf_kdn_config_pay_type.require' => '运费支付方式必填',
    ];
    protected $scene = [
        'add' => ['express_code', 'expresscf_kdn_config_pay_type'],
        'edit' => ['expresscf_kdn_config_pay_type'],
    ];
}
