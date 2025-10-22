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
class Config extends Validate {

    protected $rule = [
        //promotion_booth
        'promotion_booth_price' => 'require|number|egt:0',
        'promotion_booth_goods_sum' => 'require|number',
        //promotion_bundling
        'promotion_bundling_price' => 'require|number|egt:0',
        'promotion_bundling_sum' => 'require|number',
        'promotion_bundling_goods_sum' => 'require|number',
        //promotion_voucher
        'promotion_voucher_price' => 'require|number',
        'promotion_voucher_storetimes_limit' => 'require|number',
        'promotion_voucher_buyertimes_limit' => 'require|number|elt:100',
    ];
    protected $message = [
        //promotion_booth
        'promotion_booth_price.require' => '不能为空，且不小于0的整数',
        'promotion_booth_price.number' => '不能为空，且不小于0的整数',
        'promotion_booth_price.egt' => '不能为空，且不小于0的整数',
        'promotion_booth_goods_sum.require' => '不能为空，且不小于1的整数',
        'promotion_booth_goods_sum.number' => '不能为空，且不小于1的整数',
        //promotion_bundling
        'promotion_bundling_price.require' => '不能为空，且不小于0的整数',
        'promotion_bundling_price.number' => '不能为空，且不小于0的整数',
        'promotion_bundling_price.egt' => '不能为空，且不小于0的整数',
        'promotion_bundling_sum.require' => '不能为空，且不小于0的整数',
        'promotion_bundling_sum.number' => '不能为空，且不小于0的整数',
        'promotion_bundling_goods_sum.require' => '不能为空，且为1到5之间的整数，包括1和5',
        'promotion_bundling_goods_sum.number' => '不能为空，且为1到5之间的整数，包括1和5',
        //promotion_voucher
        'promotion_voucher_price.require' => '购买单价应为大于0的整数',
        'promotion_voucher_price.number' => '购买单价应为大于0的整数',
        'promotion_voucher_storetimes_limit.require' => '每月活动数量应为大于0的整数',
        'promotion_voucher_storetimes_limit.number' => '每月活动数量应为大于0的整数',
        'promotion_voucher_buyertimes_limit.require' => '最大领取数量应为大于0小于100的整数',
        'promotion_voucher_buyertimes_limit.number' => '最大领取数量应为大于0小于100的整数',
        'promotion_voucher_buyertimes_limit.elt' => '最大领取数量应为大于0小于100的整数',
    ];
    protected $scene = [
        'promotion_booth_setting' => ['promotion_booth_price', 'promotion_booth_goods_sum'],
        'promotion_bundling_setting' => ['promotion_bundling_price', 'promotion_bundling_sum', 'promotion_bundling_goods_sum'],
        'promotion_voucher_setting' => ['promotion_voucher_price', 'promotion_voucher_storetimes_limit', 'promotion_voucher_buyertimes_limit'],
    ];
}
