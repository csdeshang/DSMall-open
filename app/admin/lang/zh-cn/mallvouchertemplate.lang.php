<?php
//菜单
$lang['admin_mallvouchertemplate_manage']= '平台代金券列表';
$lang['admin_mallvouchertemplate_add']		= '添加代金券';
$lang['admin_mallvouchertemplate_view']		= '查看代金券';
$lang['admin_mallvouchertemplate_edit']     = '编辑代金券';

/**
 * 代金券编辑
 */
$lang['admin_mallvouchertemplate_gc_error']   		= '请选择所属商品分类';
$lang['admin_mallvouchertemplate_price']    		= '代金券面额';
$lang['admin_mallvouchertemplate_price_error']   		= '代金券面额应为大于0的整数';
$lang['admin_mallvouchertemplate_title']    	= '代金券标题';
$lang['admin_mallvouchertemplate_title_error'] = '代金券标题不能为空';
$lang['admin_mallvouchertemplate_title_lengtherror'] = '代金券标题不能为空且不能大于255个字符';
$lang['admin_mallvouchertemplate_points'] = '领取所需积分';
$lang['admin_mallvouchertemplate_quantity'] = '发放数量';
$lang['admin_mallvouchertemplate_quantity_error'] = '发放数量不能为空';
$lang['admin_mallvouchertemplate_eachlimit'] = '每人限领';
$lang['admin_mallvouchertemplate_eachlimit_error'] = '每人限领不能为空';
$lang['admin_mallvouchertemplate_giveout'] = '已领取';
$lang['admin_mallvouchertemplate_used'] = '已使用';


$lang['admin_mallvouchertemplate_startdate']    	= '开始时间';
$lang['admin_mallvouchertemplate_enddate']    	= '结束时间';
$lang['admin_mallvouchertemplate_limit']    		= '消费金额';
$lang['admin_mallvouchertemplate_limit_error']		= "消费金额不能为空且必须为整数，且面额不能大于代金券面额";
$lang['admin_mallvoucher_drop']		= '删除代金券';
$lang['admin_mallvoucheruser_activedate'] = '代金券发放时间';
$lang['admin_mallvouchertemplate_goodsclass'] = '所属商品分类';


/**
 * 平台代金券领取
 */
$lang['mallvoucheruser_code']    	= '代金券编码';
$lang['mallvoucheruser_activedate']    	= '领取时间';
$lang['mallvoucheruser_ownername']    	= '领取会员名';
$lang['mallvoucheruser_state']    	= '使用状态';
$lang['mallvouchertemplate_points']    	= '领取所需积分';


return $lang;