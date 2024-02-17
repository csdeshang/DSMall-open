<?php

namespace app\common\validate;

use think\Validate;
/**
 * ============================================================================
 * DSKMS多用户商城
 * ============================================================================
 * 版权所有 2014-2028 长沙德尚网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.csdeshang.com
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用 .
 * 不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * 验证器
 */
class  LiveApply extends Validate
{
    protected $rule = [
        'live_apply_name'=>'require',
        'live_apply_play_time'=>'require',
        'live_apply_remark'=>'require|max:255',
        'live_apply_push_state'=>'require|in:1,2',
    ];
    protected $message  =   [
        'live_apply_name.require' => '请填写直播标题',
        'live_apply_play_time.require' => '请填写直播时间',
        'live_apply_remark.require' => '请填写申请理由',
        'live_apply_remark.max' => '申请理由不能超过255字',
        'live_apply_push_state.require' => '缺少推流状态',
        'live_apply_push_state.in' => '推流状态错误',
    ];
    protected $scene = [
        'live_apply_save' => ['live_apply_name','live_apply_play_time', 'live_apply_remark'],
        'live_apply_change' => ['live_apply_push_state'],
    ];
}