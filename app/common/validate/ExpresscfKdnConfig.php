<?php

namespace app\common\validate;

use think\Validate;
/**
 * ============================================================================
 * DSO2O多用户商城
 * ============================================================================
 * 版权所有 2014-2028 长沙德尚网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.csdeshang.com
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用 .
 * 不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * 验证器
 */
class  ExpresscfKdnConfig extends Validate {

    protected $rule = [
        'express_code' => 'require',
        'expresscf_kdn_config_pay_type' => 'require',
    ];
    protected $message = [
        'express_code.require' => '快递公司编码必填',
        'expresscf_kdn_config_pay_type.require' => '运费支付方式必填',
    ];
    protected $scene = [
        'expresscf_kdn_config_add' => ['express_code','expresscf_kdn_config_pay_type'],
        'expresscf_kdn_config_edit' => ['expresscf_kdn_config_pay_type'],
    ];

}
