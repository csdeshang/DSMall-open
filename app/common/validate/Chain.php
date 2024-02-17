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
class  Chain extends Validate {

    protected $rule = [
        'chain_name' => 'require',
        'chain_passwd' => 'require',
        'chain_truename' => 'require',
        'chain_idcard' => 'require',
        'chain_mobile' => 'require',
        'chain_addressname' => 'require',
        'chain_area_3' => 'require',
        'chain_area_info' => 'require',
        'chain_address' => 'require',
        'chain_longitude' => 'require',
        'chain_latitude' => 'require',
    ];
    protected $message = [
        'chain_name.require' => '账户为必填',
        'chain_passwd.require' => '密码为必填',
        'chain_truename.require' => '真实姓名为必填',
        'chain_idcard.require' => '身份证为必填',
        'chain_mobile.require' => '手机号码为必填',
        'chain_addressname.require' => '服务站名称为必填',
        'chain_area_3.require' => '地区必须到3级',
        'chain_area_info.require' => '地区为必填',
        'chain_address.require' => '详细地址为必填',
        'chain_longitude.require' => '请给详细地址定位',
        'chain_latitude.require' => '请给详细地址定位',
    ];
    protected $scene = [
        'chain_add' => ['chain_name','chain_passwd','chain_truename','chain_mobile','chain_addressname','chain_area_3','chain_area_info','chain_address','chain_longitude','chain_latitude'],
        'chain_edit' => ['chain_truename','chain_mobile','chain_addressname','chain_area_3','chain_area_info','chain_address','chain_longitude','chain_latitude'],
        'chain_apply' => ['chain_name','chain_passwd','chain_truename','chain_idcard','chain_mobile','chain_addressname','chain_area_3','chain_area_info','chain_address',],
        'chain_apply_again' => ['chain_name','chain_truename','chain_idcard','chain_mobile','chain_addressname','chain_area_3','chain_area_info','chain_address',],
        'chain_setting' => ['chain_mobile','chain_addressname','chain_area_3','chain_area_info','chain_address',],
    ];

}
