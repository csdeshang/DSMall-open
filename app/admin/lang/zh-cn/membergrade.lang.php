<?php

$lang['mg_addtime'] = '添加时间';

$lang['exp_rule'] = '经验值获取规则';
$lang['exp_login'] = '会员每天第一次登录';
$lang['exp_comments'] = '订单商品评论';
$lang['exp_orderrate'] = '消费额与赠送经验值比例';
$lang['exp_orderrate_tips'] = '该值为大于0的数， 例:设置为10，表明消费10单位货币赠送1经验值';

$lang['exp_ordermax'] = '每订单最多赠送经验值';
$lang['exp_ordermax_tips'] = '该值为大于等于0的数，填写为0表明不限制最多经验值，例:设置为100，表明每订单赠送经验值最多为100经验值';


$lang['explog_membername'] = '会员名称';
$lang['exp_value'] = '经验值';
$lang['explog_addtime'] = '添加时间';
$lang['explog_stage'] = '操作阶段';
$lang['explog_desc'] = '操作描述';
$lang['ds_exppoints_manage'] = '经验值管理';
$lang['ds_exppoints_setting'] = '规则设置';
$lang['ds_exppoints_edit'] = '经验值调整';
$lang['ds_member_grade'] = '会员等级';

$lang['exppoints_index_help1'] = '经验值管理，展示了会员经验值增减情况的详细情况，经验值前无符号表示增加，有符号“-”表示减少';

$lang['exppoints_greater_than'] = '经验值应大于';



/**
 * 经验值编辑
 */
$lang['admin_exppoints_userrecord_error'] = '会员信息错误';
$lang['admin_exppoints_membername'] = '会员名称';
$lang['admin_exppoints_operatetype'] = '增减类型';
$lang['admin_exppoints_operatetype_add'] = '增加';
$lang['admin_exppoints_operatetype_reduce'] = '减少';
$lang['admin_exppoints_pointsnum'] = '经验值值';
$lang['admin_exppoints_pointsdesc'] = '描述';
$lang['admin_exppoints_pointsdesc_notice'] = '描述信息将显示在经验值明细相关页，会员和管理员都可见';
$lang['admin_exppoints_member_error_again'] = '会员信息错误，请重新填写会员名';
$lang['admin_exppoints_null_error'] = '请添加经验值值';
$lang['admin_exppoints_min_error'] = '经验值值必须大于0';
$lang['admin_exppoints_short_error'] = '经验值不足，会员当前经验值数为';
$lang['admin_exppoints_addmembername_error'] = '请输入会员名';
$lang['admin_exppoints_member_tip'] = '会员';
$lang['admin_exppoints_member_tip_2'] = ', 当前经验值数为';


/*经验值规则设置*/
$lang['membergrade_index_help1'] = '当会员符合某个级别后将自动升至该级别，请谨慎设置会员级别';
$lang['membergrade_index_help2'] = '建议：一、级别应该是逐层递增，例如“级别2”所需经验值要高于“级别1”；二、设置的第一个级别所需经验值应为0；三、请填写完整的级别信息';
$lang['membergrade_level_name']= '级别名称';
$lang['membergrade_exppoints'] = '经验值';
$lang['membergrade_add'] = '新增等级';
$lang['membergrade_remove'] = '移除';

$lang['please_complete_info'] = '请将信息填写完整';
$lang['should_be_integer'] = '应为整数';

$lang['membergrade_exppoints_list'] = '经验值明细';

return $lang;