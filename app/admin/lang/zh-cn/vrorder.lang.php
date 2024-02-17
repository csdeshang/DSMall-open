<?php

/**
 * 虚拟订单和实物订单
 */
$lang['refund_state'] = '退款状态';
$lang['refund_state_0'] = '无退款';
$lang['refund_state_1'] = '部分退款';
$lang['refund_state_2'] = '全部退款';
$lang['ob_no'] = '结算单号';
$lang['ob_no_state'] = '结算状态';
$lang['ob_no_state_0'] = '未结算';
$lang['ob_no_state_1'] = '已结算';

$lang['order_state'] = '订单状态';
$lang['order_state_new'] = '待付款';
$lang['order_state_deposit'] = '待付定金';
$lang['order_state_rest'] = '待付尾款';
$lang['order_state_pay'] = '待发货';
$lang['order_state_pickup'] = '待自提';
$lang['order_state_send'] = '待收货';
$lang['ds_processing'] = '处理中';
$lang['ds_processed'] = '待处理';
$lang['order_state_success'] = '已完成';
$lang['order_state_cancel'] = '已取消';
$lang['type'] = '类型';
$lang['order_time_from'] = '下单时间';
$lang['order_price_from'] = '订单金额';
$lang['cancel_search'] = '撤销检索';
$lang['order_time'] = '下单时间';
$lang['order_total_price'] = '订单总额';
$lang['order_total_transport'] = '运费';
$lang['miss_order_number'] = '缺少订单编号';

$lang['order_state_paid'] = '已付款';
$lang['order_admin_operator'] = '系统管理员';
$lang['order_handle_history'] = '操作历史';
$lang['order_confirm_cancel'] = '您确实要取消该订单吗？';
$lang['order_change_cancel'] = '取消';
$lang['order_change_received'] = '收到货款';

//订单详情
$lang['order_detail'] = '订单详情';
$lang['order_info'] = '订单信息';
$lang['payment_time'] = '支付时间';
$lang['ship_time'] = '发货时间';
$lang['complate_time'] = '完成时间';
$lang['buyer_message'] = '买家附言';
$lang['consignee_name'] = '收货人姓名';
$lang['region'] = '所在地区';
$lang['tel_phone'] = '电话号码';
$lang['mob_phone'] = '手机号码';
$lang['address'] = '详细地址';
$lang['ship_code'] = '发货单号';
$lang['product_info'] = '商品信息';
$lang['product_price'] = '单价';
$lang['product_num'] = '数量';
$lang['ds_promotion'] = '促销活动';



$lang['order_show_at'] = '于';


$lang['trade_no_tips'] = '支付宝等第三方支付平台交易号';

$lang['payment_time_required'] = '请填写付款准确时间';
$lang['payment_code_required'] = '请选择付款方式';
$lang['trade_no_required'] = '请填写第三方支付平台交易号';

$lang['receive_pay_confirm'] = '操作提醒：\n该操作不可撤销\n提交前请务必确认是否已收到付款\n继续操作吗?';
/**
 * 虚拟订单
 */
$lang['exp_od_no'] = '订单号';
$lang['exp_od_store'] = '店铺';
$lang['exp_od_buyer'] = '买家';
$lang['exp_od_xtimd'] = '下单时间';
$lang['exp_od_count'] = '订单总额';
$lang['exp_od_yfei'] = '运费';
$lang['exp_od_paytype'] = '支付方式';
$lang['exp_od_state'] = '订单状态';
$lang['exp_od_storeid'] = '店铺ID';
$lang['exp_od_selerid'] = '卖家ID';
$lang['exp_od_buyerid'] = '买家ID';
$lang['exp_od_bemail'] = '买家Email';
$lang['exp_od_sta_qx'] = '已取消';
$lang['exp_od_sta_dfk'] = '待付款';
$lang['exp_od_sta_dqr'] = '已付款、待确认';
$lang['exp_od_sta_yfk'] = '已付款';
$lang['exp_od_sta_yfh'] = '已发货';
$lang['exp_od_sta_yjs'] = '已结算';
$lang['exp_od_sta_dsh'] = '待审核';
$lang['exp_od_sta_yqr'] = '已确认';
$lang['exp_od_order'] = '订单';
$lang['vrorder_index_help1'] = '点击查看操作将显示订单，提示：包括电子兑换码，的详细信息';
$lang['vrorder_index_help2'] = '如果该订单是未付款的订单，您可以点击取消操作来取消该订单';
$lang['vrorder_index_help3'] = '如果平台已确认收到买家的付款，但系统支付状态并未变更，您可以点击收到货款操作，并填写相关信息后更改订单支付状态';

$lang['vrorder_pd_amount'] = '使用预存款支付';
$lang['vrorder_rcb_amount'] = '使用充值卡支付';
$lang['vrorder_commis_rate'] = '佣金比例';
$lang['vrorder_commis_price'] = '收取佣金';
$lang['vrorder_groupbuy'] = '抢购，';
$lang['vrorder_groupbuy_validity'] = '使用时效：即日起 至';
$lang['vrorder_expired_no_refund'] = '，过期不退款';

$lang['vr_code'] = '兑换码';
$lang['vr_code_not_exist'] = '未生成电子兑换码';


$lang['order_amount'] = '订单总金额';

$lang['consignee_info'] = '收货人信息';
$lang['daddress_info'] = '发货信息';
$lang['daddress_seller_name'] = '发货人';
$lang['daddress_address_name'] = '发货地';
$lang['invoice_info'] = '发票信息';
$lang['goods_pay_price'] = '实际支付额';
$lang['other_info'] = '其它信息';
$lang['voucher_use_info'] = '使用了面额为：%s元的代金券，编码：%s';
$lang['refund_info'] = '退款记录';
$lang['refund_detail_info'] = '发生时间：%s&emsp;&emsp;退款单号：%s&emsp;&emsp;退款金额：%s&emsp;&emsp;备注：%s';
$lang['return_info'] = '退货记录';
$lang['return_detail_info'] = '发生时间：%s&emsp;&emsp;退货单号：%s&emsp;&emsp;退款金额：%s&emsp;&emsp;备注：%s';

$lang['no_right_operate'] = '无权操作';
$lang['admin_cancel_vrorder'] = '管理员关闭虚拟订单';
$lang['order_not_exist'] = '订单不存在';
$lang['exp_od_mobile'] = '接收手机';

$lang['confirm_receive_pay'] = '确认收款';

return $lang;