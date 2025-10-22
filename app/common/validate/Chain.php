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
class Chain extends Validate {

    protected $rule = [
        'chain_name' => 'require|length:3,13|unique:chain',
        'chain_passwd' => 'require',
        'chain_truename' => 'require',
        'chain_idcard' => 'idCard',
        'chain_mobile' => 'mobile|unique:chain',
    ];
    protected $message = [
        'chain_name.require' => '账户为必填',
        'chain_name.length' => '账户长度在3到13位',
        'chain_name.unique' => '账户已存在',
        'chain_passwd.require' => '密码为必填',
        'chain_truename.require' => '真实姓名为必填',
        'chain_idcard.idCard' => '身份证号码错误',
        'chain_mobile.mobile' => '手机号码格式错误',
        'chain_mobile.unique' => '手机号码已存在',
    ];
    protected $scene = [
        'model_add' => ['chain_name', 'chain_passwd', 'chain_truename','chain_idcard', 'chain_mobile'],
        'model_edit' => ['chain_truename','chain_idcard', 'chain_mobile'],
    ];
}
