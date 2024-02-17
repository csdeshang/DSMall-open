<?php
$lang['admin_voucher_unavailable']    = '需开启 代金券、积分，正在跳转到设置页面 。。。';
$lang['admin_voucher_quotastate_activity']	= '正常';
$lang['admin_voucher_quotastate_cancel']    = '取消';
$lang['admin_voucher_quotastate_expire']    = '结束';
$lang['admin_voucher_templatestate_usable']	= '有效';
$lang['admin_voucher_templatestate_disabled']= '失效';
//菜单
$lang['admin_voucher_quota_manage']= '套餐管理';
$lang['admin_voucher_template_manage']= '店铺代金券';
$lang['admin_voucher_template_edit']= '编辑代金券';
$lang['admin_voucher_setting']		= '设置';
$lang['admin_voucher_pricemanage']		= '面额设置';
$lang['admin_voucher_priceadd']		= '添加面额';
$lang['admin_voucher_styletemplate']	= '样式模板';
/**
 * 设置
 */
$lang['admin_voucher_setting_price_error']		= '购买单价应为大于0的整数';
$lang['admin_voucher_setting_storetimes_error']	= '每月活动数量应为大于0的整数';
$lang['admin_voucher_setting_buyertimes_error']	= '最大领取数量应为大于0小于100的整数';
$lang['admin_voucher_setting_price']			= '购买单价（元/月）';
$lang['admin_voucher_setting_price_tip']		= '购买代金劵活动所需费用，购买后卖家可以在所购买周期内发布代金劵促销活动';
$lang['admin_voucher_setting_storetimes']		= '每月活动数量';
$lang['admin_voucher_setting_storetimes_tip']	= '每月最多可以发布的代金劵促销活动数量';
$lang['admin_voucher_setting_buyertimes']		= '买家最大领取数量';
$lang['admin_voucher_setting_buyertimes_tip']	= '买家最多只能拥有同一个店铺尚未消费抵用的店铺代金券最大数量';
/**
 * 代金券面额
 */
$lang['admin_voucher_price_error']   		= '代金券面额应为大于0的整数';
$lang['admin_voucher_price_describe_error'] = '描述不能为空';
$lang['admin_voucher_price_describe_lengtherror'] = '代金券描述不能为空且不能大于255个字符';
$lang['admin_voucher_price_points_error']   = '默认兑换积分数应为大于0的整数';
$lang['admin_voucher_price_exist']    		= '该代金券面额已经存在';
$lang['admin_voucher_price_title']    		= '代金券面额';
$lang['admin_voucher_price_describe']    	= '描述';
$lang['admin_voucher_price_points']    		= '兑换积分数';
$lang['admin_voucher_price_points_tip']    	= '当兑换代金券时，消耗的积分数';
$lang['admin_voucher_price_tip1']               = '管理员设置代金券面额，店铺发放代金券时面额从管理员设置的代金券的面额中选择';
/**
 * 代金券套餐
 */
$lang['admin_voucher_quota_tip1']    	= '取消操作后不可恢复，请慎重操作';

/**
 * 代金券
 */
$lang['admin_voucher_template_points_error']	= '兑换所需积分数应为大于0的整数';
$lang['admin_voucher_template_title']			= '代金券名称';
$lang['admin_voucher_template_enddate']			= '有效期';
$lang['admin_voucher_template_price']			= '面额';
$lang['admin_voucher_template_total']			= '可发放总数';
$lang['admin_voucher_template_eachlimit']		= '每人限领';
$lang['admin_voucher_template_orderpricelimit']	= '消费金额';
$lang['admin_voucher_template_describe']		= '代金券描述';
$lang['admin_voucher_template_image']			= '代金券图片';
$lang['admin_voucher_template_points']			= '兑换所需积分数';
$lang['admin_voucher_template_adddate']			= '添加时间';
$lang['admin_voucher_template_list_tip']		= '手工设置的代金券如果失效了,用户将不能再领取该代金券,但是已经领取的代金券仍然可以正常使用';
$lang['admin_voucher_template_giveoutnum']		= '已领取';
$lang['admin_voucher_template_usednum']			= '已使用';
$lang['admin_voucher_template_if_private']		= '是否私密';

return $lang;