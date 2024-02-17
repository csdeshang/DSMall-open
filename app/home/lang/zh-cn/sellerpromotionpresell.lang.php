<?php

$lang['promotion_unavailable'] = '商品预售功能尚未开启';

$lang['presell_add_success'] = '新增预售成功';
$lang['presell_quota_add_success'] = '购买预售套餐成功';


$lang['presell_add'] = '新增预售';
$lang['presell_edit'] = '编辑预售';
$lang['presell_active_list'] = '预售活动列表';
$lang['presell_goods'] = '预售商品';
$lang['presell_goods_explain'] = '选择参加预售对应的商品';
$lang['presell_manage'] = '预售管理';
$lang['presell_quota_add'] = '购买预售套餐';

$lang['ds_manage_presell'] = '预售详情';
$lang['ds_end_presell'] = '取消';

$lang['presell_add_start_time_explain'] = '开始时间不能为空且不能早于%s';
$lang['presell_add_end_time_explain'] = '结束时间不能为空且不能晚于%s';
$lang['time_error'] = '时间格式错误';
$lang['param_error'] = '参数错误';
$lang['greater_than_start_time'] = '结束时间必须大于开始时间';
$lang['greater_than_end_time'] = '发货时间必须大于结束时间';
/**
 * 购买活动
 */
$lang['presell_explain1'] = '1、点击购买套餐和套餐续费按钮可以购买或续费套餐';
$lang['presell_explain2'] = '2、点击添加活动按钮可以添加预售_活动，点击管理按钮可以对预售活动内的商品进行管理';
$lang['presell_explain3'] = '3、点击删除按钮可以删除预售_活动';
$lang['presell_manage_goods_explain1'] = '1、预售商品的时间段不能重叠';
$lang['presell_manage_goods_explain2'] = '2、点击添加商品按钮可以搜索并添加参加活动的商品，点击删除按钮可以删除该商品';

$lang['presell_quota_add_quantity'] = '套餐购买数量';
$lang['presell_price_explain1'] = '购买单位为月(30天)，一次最多购买12个月，购买后立即生效，即可发布预售套装活动。';
$lang['presell_price_explain2'] = '每月您需要支付%d元。';
$lang['presell_quota_price_fail'] = '参数错误，购买失败。';
$lang['presell_quota_price_succ'] = '购买成功。';
$lang['presell_quota_quantity_error'] = '不能为空，且必须为1~12之间的整数';
$lang['presell_quota_add_confirm'] = '确认购买?您总共需要支付';
$lang['presell_quota_success_glog_desc'] = '购买预售套装活动%d个月，单价%d元，总共花费%d元';
$lang['text_month'] = '月';

/* 预售相关 */
$lang['presell_id'] = '预售ID';
$lang['presell_time'] = '预售时间';
$lang['presell_start_time'] = '预售开始时间';
$lang['presell_end_time'] = '预售结束时间';
$lang['presell_shipping_time'] = '发货时间';

$lang['presell_goods_name'] = '预售商品名称';
$lang['presell_state'] = '预售状态';
$lang['presell_type'] = '预售类型';
$lang['presell_type_1'] = '全款预售';
$lang['presell_type_2'] = '定金预售';
$lang['presell_price'] = '预售价';
$lang['presell_price_error'] = '预售价必须小于原价';
$lang['presell_type_notice'] = '预售类型不可修改';
$lang['presell_deposit_amount'] = '定金';
$lang['presell_deposit_amount_explain'] = '定金不可超过预售价的20%';

//index
$lang['package_renewal'] = '套餐续费';
$lang['purchase_package'] = '购买套餐';
$lang['click_add_activity_button'] = '1、点击添加活动按钮可以添加预售活动，点击编辑按钮可以对未开始的预售活动进行编辑';
$lang['click_delete_button'] = '2、点击删除按钮可以删除预售活动';
$lang['overdue_package'] = '套餐过期时间';
$lang['please_package_first'] = '当前没有可用套餐，请先购买套餐';
$lang['period_settlement_deduct'] = '相关费用会在店铺的账期结算中扣除';

//presell_add
$lang['start_time_group_not_modified'] = '预售开始时间不可修改';
$lang['end_time_group_not_modifiable'] = '预售结束时间不可修改';
$lang['mall_price'] = '商城价';
$lang['select_goods'] = '选择商品';
$lang['search_store_goods'] = '第一步：搜索店内商品';
$lang['group_information1'] = '不输入名称直接搜索将显示店内所有普通商品，特殊商品不能参加。';


//presell_manage
$lang['people'] = '人';

//search_goods
$lang['sale_price'] = '销售价';
$lang['select_group_goods'] = '选择为预售商品';

//controller
$lang['please_buy_package_first'] = '没有可用预售套餐,请先购买套餐';
$lang['add_group_activities'] = '添加预售活动，活动名称：';
$lang['edit_group_activities'] = '编辑预售活动，活动名称：';
$lang['activity_number'] = '，活动编号：';
$lang['group_activities_end_early'] = '预售活动取消，活动名称：';
$lang['buy_spell_group'] = '购买预售';
$lang['buy'] = '购买';
$lang['combo_pack'] = '份预售套餐，单价';
$lang['goods_are_syndicate'] = '此商品在预售中';

$lang['ds_ensure_end'] = '您确认取消吗？';



$lang['sellerpromotionpresell_goods_not_allow']='所选商品已被设置为预售商品';

return $lang;