<?php

namespace app\common\validate;


use think\Validate;
/**
 * ============================================================================
 * DSMall多用户商城
 * ============================================================================
 * 版权所有 2014-2028 长沙德尚网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.csdeshang.com
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用 .
 * 不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * 验证器
 */
class  Presell extends Validate
{
    protected $rule = [
        'presell_type'=>'require|in:1,2',
        'goods_id'=>'require',
        'presell_deposit_amount'=>'requireIf:presell_type,2|float',
        'presell_price'=>'require|float|gt:0',
        'presell_start_time'=>'require',
        'presell_end_time'=>'require',
        'presell_shipping_time'=>'requireIf:presell_type,1',
    ];
    protected $message  =   [
        'presell_type.require' => '请选择预售类型',
        'presell_type.in' => '预售类型错误',
        'goods_id.require' => '请选择预售商品',
        'presell_deposit_amount.requireIf' => '请填写定金',
        'presell_deposit_amount.float' => '定金错误',
        'presell_price.require' => '请填写预售价',
        'presell_price.float' => '预售价错误',
        'presell_price.gt' => '预售价错误',
        'presell_start_time.require' => '请选择开始时间',
        'presell_end_time.require' => '请选择结束时间',
        'presell_shipping_time.requireIf' => '请选择发货时间',
    ];
    protected $scene = [
        'presell_add' => ['presell_type', 'goods_id', 'presell_deposit_amount', 'presell_price', 'presell_start_time', 'presell_end_time', 'presell_shipping_time'],
        'presell_edit' => ['presell_deposit_amount', 'presell_price', 'presell_shipping_time'],
    ];

}