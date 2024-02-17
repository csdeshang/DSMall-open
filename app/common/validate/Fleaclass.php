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
class  Fleaclass extends Validate
{
    protected $rule = [
        'fleaclass_name'=>'require',
        'fleaclass_sort'=>'require|number'
    ];
    protected $message = [
        'fleaclass_name.require'=>'分类名称不能为空',
        'fleaclass_sort.require'=>'分类排序仅能为数字',
        'fleaclass_sort.number'=>'分类排序仅能为数字'
    ];
    protected $scene = [
        'goods_class_add' => ['fleaclass_name', 'fleaclass_sort'],
        'goods_class_edit' => ['fleaclass_name', 'fleaclass_sort'],
    ];
}