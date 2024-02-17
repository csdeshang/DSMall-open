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
class  Storesnshome extends Validate
{
    protected $rule = [
        'commentcontent'=>'require|length:0,140',
        'forwardcontent'=>'require|length:0,140',
    ];
    protected $message = [
        'commentcontent.require'=>'需要评论点内容|不能超过140字',
        'commentcontent.length'=>'需要评论点内容|不能超过140字',
        'forwardcontent.require'=>'需要评论点内容|不能超过140字',
        'forwardcontent.length'=>'需要评论点内容|不能超过140字',
    ];
    protected $scene = [
        'addcomment' => ['commentcontent'],
        'addforward' => ['forwardcontent'],
    ];
}