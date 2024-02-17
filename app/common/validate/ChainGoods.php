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
class  ChainGoods extends Validate {

    protected $rule = [
        'goods_storage' => 'require|integer|egt:0',
        'goods_id' => 'require',
    ];
    protected $message = [
        'goods_id.require' => '缺少商品ID',
        'goods_storage.require' => '库存必填',
        'goods_storage.integer' => '库存设置错误',
        'goods_storage.egt' => '库存设置错误',
    ];
    protected $scene = [
        'chain_goods_add' => ['goods_storage','goods_id'],
        'chain_goods_edit' => ['goods_storage','goods_id'],
    ];

}
