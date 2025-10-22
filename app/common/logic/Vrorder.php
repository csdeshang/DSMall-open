<?php

namespace app\common\logic;
use think\facade\Db;
use app\common\model\Storemoneylog;

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
 * 逻辑层模型
 */
class Vrorder
{
    /**
     * 取消订单
     * @param array $order_info
     * @param string $role 操作角色 buyer、seller、admin、system 分别代表买家、商家、管理员、系统
     * @param string $msg 操作备注
     * @param boolean $if_queue 是否使用队列
     * @return array
     */
    public function changeOrderStateCancel($order_info, $role, $msg, $if_queue = true)
    {

        Db::startTrans();
        try {

            $vrorder_model = model('vrorder');
            
            
            $ppintuanorder_model=model('ppintuanorder');
            if($order_info['order_promotion_type']==2){
                $condition=array();
                $condition[]=array('order_id','=',$order_info['order_id']);
                $condition[]=array('pintuanorder_type','=',1);
                $condition[]=array('pintuanorder_state','=',1);
                $ppintuanorder_model->editPpintuanorder($condition, array('pintuanorder_state'=>0));
            }
            //库存、销量变更
            model('goods')->cancelOrderUpdateStorage(array($order_info['goods_id'] => $order_info['goods_num']),$order_info['virtual_type']>1?true:false);


            
            $predeposit_model = model('predeposit');

            //解冻充值卡
            $rcb_amount = floatval($order_info['rcb_amount']);
            $data_rcb = array();
            $data_rcb['member_id'] = $order_info['buyer_id'];
            $data_rcb['member_name'] = $order_info['buyer_name'];
            $data_rcb['amount'] = $rcb_amount;
            $data_rcb['order_sn'] = $order_info['order_sn'];


            //解冻预存款
            $pd_amount = floatval($order_info['pd_amount']);
            $data_pd = array();
            $data_pd['member_id'] = $order_info['buyer_id'];
            $data_pd['member_name'] = $order_info['buyer_name'];
            $data_pd['amount'] = $pd_amount;
            $data_pd['order_sn'] = $order_info['order_sn'];


            if ($order_info['order_state'] == ORDER_STATE_NEW) {
                if ($rcb_amount > 0) {
                    $predeposit_model->changeRcb('order_cancel', $data_rcb);
                }
                if ($pd_amount > 0) {
                    $predeposit_model->changePd('order_cancel', $data_pd);
                }
            }
            if ($order_info['order_state'] == ORDER_STATE_PAY) {
                $refundreturn_model=model('refundreturn');
                $refundreturn_model->refundAmount($order_info, $order_info['order_amount'],'vrorder');

                if($order_info['order_promotion_type']==2){//如果是拼团
                    $ppintuangroup_info=Db::name('ppintuangroup')->where('pintuangroup_id', $order_info['promotions_id'])->find();
                    if ($ppintuangroup_info && $ppintuangroup_info['pintuangroup_state'] == 1) {
                        if ($ppintuangroup_info['pintuangroup_joined'] > 0) {
                            Db::name('ppintuangroup')->where('pintuangroup_id', $order_info['promotions_id'])->dec('pintuangroup_joined')->update();
                            if ($ppintuangroup_info['pintuangroup_joined'] == 1) {
                                //拼团统计开团数量
                                $condition = array();
                                $condition[] = array('pintuan_id', '=', $ppintuangroup_info['pintuan_id']);
                                $condition[] = array('pintuan_count', '>', 0);
                                Db::name('ppintuan')->where($condition)->dec('pintuan_count')->update();
                            }
                        }
                    }
                }
            }
            //更新订单信息
            $update_order = array(
                'order_state' => ORDER_STATE_CANCEL, 'pd_amount' => 0, 'close_time' => TIMESTAMP, 'close_reason' => $msg
            );
            $update = $vrorder_model->editVrorder($update_order, array('order_id' => $order_info['order_id']));
            if (!$update) {
                throw new \think\Exception('保存失败', 10006);
            }
            //分销佣金取消
            $orderinviter_model = model('orderinviter');
            $orderinviter_model->cancelOrderinviterMoney($order_info['order_id'],1);

            //添加订单日志
            $data = array();
            $data['order_id'] = $order_info['order_id'];
            $data['log_role'] = $role;
            $data['log_msg'] = '取消了订单';
            $data['log_user'] = '';
            if ($msg) {
                $data['log_msg'] .= ' ( ' . $msg . ' )';
            }
            model('orderlog')->addVrOrderlog($data);
            
            Db::commit();
            return ds_callback(true, '更新成功');

        } catch (\Exception $e) {
            Db::rollback();
            return ds_callback(false, $e->getMessage());
        }
    }

    /**
     * 支付订单
     * @param array $order_info
     * @param string $role 操作角色 buyer、seller、admin、system 分别代表买家、商家、管理员、系统
     * @param string $post
     * @return array
     */
    public function changeOrderStatePay($order_info, $role, $post)
    {
        Db::startTrans();
        try {

            $vrorder_model = model('vrorder');

            $predeposit_model = model('predeposit');
            //下单，支付被冻结的充值卡
            $rcb_amount = floatval($order_info['rcb_amount']);
            if ($rcb_amount > 0) {
                $data_pd = array();
                $data_pd['member_id'] = $order_info['buyer_id'];
                $data_pd['member_name'] = $order_info['buyer_name'];
                $data_pd['amount'] = $rcb_amount;
                $data_pd['order_sn'] = $order_info['order_sn'];
                $predeposit_model->changeRcb('order_comb_pay', $data_pd);
            }

            //下单，支付被冻结的预存款
            $pd_amount = floatval($order_info['pd_amount']);
            if ($pd_amount > 0) {
                $data_pd = array();
                $data_pd['member_id'] = $order_info['buyer_id'];
                $data_pd['member_name'] = $order_info['buyer_name'];
                $data_pd['amount'] = $pd_amount;
                $data_pd['order_sn'] = $order_info['order_sn'];
                $predeposit_model->changePd('order_comb_pay', $data_pd);
            }

            //更新订单状态
            $update_order = array();
            $update_order['order_state'] = ORDER_STATE_PAY;
            $update_order['payment_time'] = isset($post['payment_time']) ? strtotime($post['payment_time']) : TIMESTAMP;
            $update_order['payment_code'] = $post['payment_code'];
            $update_order['trade_no'] = $post['trade_no'];
            $update = $vrorder_model->editVrorder($update_order, array('order_id' => $order_info['order_id']));
            if (!$update) {
                throw new \think\Exception(lang('ds_common_save_fail'), 10006);
            }
            
            //添加订单日志
            $data = array();
            $data['order_id'] = $order_info['order_id'];
            $data['log_role'] = $role;
            $data['log_user'] = '';
            $data['log_msg'] = '收到了货款 ' . (isset($post['trade_no']) ? ('( 支付平台交易号 : ' . $post['trade_no'] . ' )') : '');
            model('orderlog')->addVrOrderlog($data);
            
                //如果是拼团
                if ($order_info['order_promotion_type']==2) {
                    $ppintuangroup_model=model('ppintuangroup');
                    $ppintuangroup_info=Db::name('ppintuangroup')->where('pintuangroup_id', $order_info['promotions_id'])->find();
                    if($ppintuangroup_info && $ppintuangroup_info['pintuangroup_state']==1){
                        if($ppintuangroup_info['pintuangroup_joined']==0){
                            //拼团统计开团数量
                            $condition=array();
                            $condition[]=array('pintuan_id','=', $ppintuangroup_info['pintuan_id']);
                            Db::name('ppintuan')->where($condition)->inc('pintuan_count')->update();
                        }
                        //开团统计新增人数
                        Db::name('ppintuangroup')->where('pintuangroup_id', $order_info['promotions_id'])->inc('pintuangroup_joined')->update();
                        if(($ppintuangroup_info['pintuangroup_joined']+1)>=$ppintuangroup_info['pintuangroup_limit_number']){
                            $condition = array();
                            $condition[] = array('pintuangroup_id','=', $order_info['promotions_id']);
                            $ppintuangroup_model->successPpintuangroup($condition,$condition);
                            $this->addVrorderCode($order_info);
                            $condition=array();
                            $condition[]=array('pintuan_id','=', $ppintuangroup_info['pintuan_id']);
                            Db::name('ppintuan')->where($condition)->inc('pintuan_ok_count')->update();
                        }
                    }
                    
                }elseif($order_info['virtual_type']==0){//虚拟商品拼团等拼团成功再发兑换码
                $this->addVrorderCode($order_info);
            }else{
                    $result = $this->changeOrderStateSuccess($order_info['order_id']);
                    if (!$result['code']) {
                        return $result;
                    }
                }
                
            Db::commit();
            return ds_callback(true, '更新成功');

        } catch (\Exception $e) {
            Db::rollback();
            return ds_callback(false, $e->getMessage());
        }
    }
    
    public function addVrorderCode($order_info) {
        $vrorder_model = model('vrorder');
        //发放兑换码
        $insert = $vrorder_model->addVrorderCode($order_info);
        if (!$insert) {
            throw new \think\Exception('兑换码发送失败', 10006);
        }

        // 支付成功发送买家消息
        $param = array();
        $param['code'] = 'order_payment_success';
        $param['member_id'] = $order_info['buyer_id'];
                //阿里短信参数
                $param['ali_param'] = array(
                    'order_sn' => $order_info['order_sn'],
                );
                $param['ten_param'] = array(
                    $order_info['order_sn'],
                );
        $param['param'] = array_merge($param['ali_param'],array(
            'order_url' => HOME_SITE_URL .'/Membervrorder/show_order?order_id='.$order_info['order_id']
        ));
        //微信模板消息
                $param['weixin_param'] = array(
                    'url' => config('ds_config.h5_site_url').'/pages/member/vrorder/OrderDetail?order_id='.$order_info['order_id'],
                    'data'=>array(
                        "keyword1" => array(
                            "value" => $order_info['order_sn'],
                            "color" => "#333"
                        ),
                        "keyword2" => array(
                            "value" => $order_info['goods_name'],
                            "color" => "#333"
                        ),
                        "keyword3" => array(
                            "value" => $order_info['order_amount'],
                            "color" => "#333"
                        ),
                        "keyword4" => array(
                            "value" => date('Y-m-d H:i',$order_info['add_time']),
                            "color" => "#333"
                        )
                    ),
                );
        model('cron')->addCron(array('cron_exetime'=>TIMESTAMP,'cron_type'=>'sendMemberMsg','cron_value'=>serialize($param)));

        // 支付成功发送店铺消息
        $param = array();
        $param['code'] = 'new_order';
        $param['store_id'] = $order_info['store_id'];
        $param['ali_param'] = array(
            'order_sn' => $order_info['order_sn']
        );
        $param['ten_param'] = array(
            $order_info['order_sn']
        );
        $param['param'] = $param['ali_param'];
        $param['weixin_param']=array(
                    'url' => config('ds_config.h5_store_site_url').'/pages/seller/vrorder/OrderDetail?order_id='.$order_info['order_id'],
                    'data'=>array(
                        "keyword1" => array(
                            "value" => $order_info['order_sn'],
                            "color" => "#333"
                        ),
                        "keyword2" => array(
                            "value" => $order_info['goods_name'],
                            "color" => "#333"
                        ),
                        "keyword3" => array(
                            "value" => $order_info['order_amount'],
                            "color" => "#333"
                        ),
                        "keyword4" => array(
                            "value" => date('Y-m-d H:i',$order_info['add_time']),
                            "color" => "#333"
                        )
                    ),
                );
        model('cron')->addCron(array('cron_exetime'=>TIMESTAMP,'cron_type'=>'sendStoremsg','cron_value'=>serialize($param)));

        //发送兑换码到手机 
        $param = array(
            'order_id' => $order_info['order_id'], 'buyer_id' => $order_info['buyer_id'],
            'buyer_phone' => $order_info['buyer_phone']
        );
        $vrorder_model->sendVrCode($param);
    }

    /**
     * 完成订单
     * @param int $order_id
     * @return array
     */
    public function changeOrderStateSuccess($order_id)
    {
        Db::startTrans();
        try {
            $vrorder_model = model('vrorder');
            $condition = array();
            $condition[] = array('vr_state', '=', 0);
            $condition[] = array('refund_lock', 'in', array(0, 1));
            $condition[] = array('order_id', '=', $order_id);
            $condition[] = array('vr_indate', '>', TIMESTAMP);
            $order_code_info = $vrorder_model->getVrordercodeInfo($condition, '*');
            if (empty($order_code_info)) {
                $update = $vrorder_model->editVrorder(array(
                    'order_state' => ORDER_STATE_SUCCESS, 'finnshed_time' => TIMESTAMP
                        ), array('order_id' => $order_id));
                if (!$update) {
                    throw new \think\Exception('更新失败', 10006);
                }
            }


            $order_info = $vrorder_model->getVrorderInfo(array('order_id' => $order_id));

            //虚拟订单结算
            $this->balanceVrOrderStateReceive($order_info);

            //添加会员积分
            if (config('ds_config.points_isuse') == 1) {
                model('points')->savePointslog('order', array(
                    'pl_memberid' => $order_info['buyer_id'], 'pl_membername' => $order_info['buyer_name'],
                    'orderprice' => $order_info['order_amount'], 'order_sn' => $order_info['order_sn'],
                    'order_id' => $order_info['order_id']
                        ), true);
            }

            //添加会员经验值
            model('exppoints')->saveExppointslog('order', array(
                'explog_memberid' => $order_info['buyer_id'], 'explog_membername' => $order_info['buyer_name'],
                'orderprice' => $order_info['order_amount'], 'order_sn' => $order_info['order_sn'],
                'order_id' => $order_info['order_id']
                    ), true);

            Db::commit();
            return ds_callback(true, '操作成功');
        } catch (\Exception $e) {
            Db::rollback();
            return ds_callback(false, $e->getMessage());
        }
    }
    
    
    //获取订单结算的数据,支付给店铺,平台,佣金,退款等
    public function getBalanceVrOrderInfo($order_info){
        $vrorder_info = Db::name('vrorder')->where('order_id', '=', $order_info['order_id'])->field('SUM(order_amount) AS vr_order_amount,SUM(ROUND(order_amount*commis_rate/100,2)) AS commis_totals,SUM(refund_amount) AS vr_refund_amount,SUM(ROUND(refund_amount*commis_rate/100,2)) AS vr_commis_return_totals')->find();
        
        //虚拟订单金额
        $result['order_amount'] = $vrorder_info['vr_order_amount'];
        
        //退款金额
        $result['refund_amount'] = $vrorder_info['vr_refund_amount'];
        
        //使用的平台代金券
//        $result['mall_voucher_totals'] = $ordercommon['mall_voucher_totals'];
        
        //平台的佣金
        $result['commis_totals'] = $vrorder_info['commis_totals'];
        
        //获取用户推荐佣金
        $orderinviter = Db::name('orderinviter')->where('orderinviter_order_id', '=',$order_info['order_id'])->where('orderinviter_order_type', 1)->field('SUM(orderinviter_money) AS vr_inviter_totals')->find();
        $result['inviter_totals'] = $orderinviter['vr_inviter_totals'];
        
        return $result;
    }
    
    //用户确认收货,与店铺进行结算.虚拟订单
    public function balanceVrOrderStateReceive($order_info) {
        $result = $this->getBalanceVrOrderInfo($order_info);

        //店铺去除费用后应该获得的资金
        $store_avaliable_money = $result['order_amount'] - $result['commis_totals'] - $result['inviter_totals'];
        
        $storemoneylog_desc = '虚拟订单'.$order_info['order_sn'].'用户确认收货。(订单金额：'.$result['order_amount'].')-(平台佣金'.$result['commis_totals'].')-(分销佣金'.$result['inviter_totals'].')';
        $storemoneylog_model = model('storemoneylog');
        //付款给店铺
        $data = array(
            'store_id' => $order_info['store_id'],
            'storemoneylog_type' => Storemoneylog::TYPE_ORDER_SUCCESS,
            'storemoneylog_state' => Storemoneylog::STATE_VALID,
            'storemoneylog_add_time' => TIMESTAMP,
            'storemoneylog_avaliable_money' => $store_avaliable_money,
            'storemoneylog_desc' => $storemoneylog_desc,
        );
        $storemoneylog_model->changeStoremoney($data);
        
        //付款给推荐人分销佣金[虚拟订单]
        $orderinviter_model = model('orderinviter');
        $orderinviter_model->giveMoney($order_info['order_id'], 1);
        
        //记录订单日志
        $data = array();
        $data['order_id'] = $order_info['order_id'];
        $data['log_role'] = 'system';
        $data['log_user'] = '';
        $data['log_msg'] = '确认收货,店铺收款'.$store_avaliable_money.'元。'. $storemoneylog_desc;
        model('orderlog')->addVrOrderlog($data);
        
    }
    
    
    //确认退款,对店铺的扣款
    public function balanceVrOrderStateRefund($order_info,$refund){
        
        //判断订单状态,店铺是否发货,未发货则店铺不需要进行扣款
        
        //用户申请的退款金额
        $refund_amount = $refund['refund_amount'];
        
        // 平台的佣金、推荐的分成 不做扣除
        $store_avaliable_money = $refund_amount;
        
        $storemoneylog_desc = '虚拟订单'.$order_info['order_sn'].'退款。';
        $storemoneylog_model = model('storemoneylog');
        //付款给店铺
        $data = array(
            'store_id' => $order_info['store_id'],
            'storemoneylog_type' => Storemoneylog::TYPE_ORDER_REFUND,
            'storemoneylog_state' => Storemoneylog::STATE_VALID,
            'storemoneylog_add_time' => TIMESTAMP,
            'storemoneylog_avaliable_money' => -$store_avaliable_money,
            'storemoneylog_desc' => $storemoneylog_desc,
        );
        $storemoneylog_model->changeStoremoney($data);
        
        //产生退款,修改推荐人分销佣金[虚拟订单]
        $orderinviter_model = model('orderinviter');
        $orderinviter_model->refundVrorderinviterMoney($order_info, $refund);
        
        //记录订单日志
        $data = array();
        $data['order_id'] = $order_info['order_id'];
        $data['log_role'] = 'system';
        $data['log_user'] = '';
        $data['log_msg'] = '用户退款,店铺扣除'.$store_avaliable_money.'元';
        model('orderlog')->addVrOrderlog($data);
        
    }
    
    
}