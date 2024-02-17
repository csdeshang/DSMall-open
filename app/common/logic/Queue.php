<?php

namespace app\common\logic;
use think\facade\Db;
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
class Queue
{

    /**
     * 添加会员积分
     * @param unknown $member_info
     */
    public function addPoint($member_info)
    {
        $points_model = model('points');
        $points_model->savePointslog('login', array(
            'pl_memberid' => $member_info['member_id'], 'pl_membername' => $member_info['member_name']
        ), true);
        return ds_callback(true);
    }

    /**
     * 添加会员经验值
     * @param unknown $member_info
     */
    public function addExppoint($member_info)
    {
        $exppoints_model = model('exppoints');
        $exppoints_model->saveExppointslog('login', array(
            'explog_memberid' => $member_info['member_id'], 'explog_membername' => $member_info['member_name']
        ), true);
        return ds_callback(true);
    }

    /**
     * 更新抢购信息
     * @param unknown $groupbuy_info
     * @throws Exception
     */
    public function editGroupbuySaleCount($groupbuy_info)
    {
        $groupbuy_model = model('groupbuy');
        $data = array();
        $data['groupbuy_buyer_count'] = Db::raw('groupbuy_buyer_count+1');
        $data['groupbuy_buy_quantity'] = Db::raw('groupbuy_buy_quantity+'.$groupbuy_info['quantity']);
        $update = $groupbuy_model->editGroupbuy($data, array('groupbuy_id' => $groupbuy_info['groupbuy_id']));
        if (!$update) {
            return ds_callback(false, '更新抢购信息失败groupbuy_id:' . $groupbuy_info['groupbuy_id']);
        }
        else {
            return ds_callback(true);
        }
    }


    /**
     * 根据商品id更新促销价格
     *
     * @param int /array $goods_commonid
     * @return boolean
     */
    public function updateGoodsPromotionPriceByGoodsId($goods_id)
    {
        if(!is_array($goods_id)){
            $goods_id=(string)$goods_id;
        }
        $condition = array();
        $condition[] = array('goods_id','in', $goods_id);
        $update = model('goods')->editGoodsPromotionPrice($condition);
        if (!$update) {
            return ds_callback(false, '根据商品ID更新促销价格失败');
        }
        else {
            return ds_callback(true);
        }
    }

    /**
     * 根据商品公共id更新促销价格
     *
     * @param int /array $goods_commonid
     * @return boolean
     */
    public function updateGoodsPromotionPriceByGoodsCommonId($goods_commonid)
    {
        if(!is_array($goods_commonid)){
            $goods_commonid=(string)$goods_commonid;
        }
        $condition = array();
        $condition[] = array('goods_commonid','in', $goods_commonid);
        $update = model('goods')->editGoodsPromotionPrice($condition);
        if (!$update) {
            return ds_callback(false, '根据商品公共id更新促销价格失败');
        }
        else {
            return ds_callback(true);
        }
    }

    /**
     * 发送店铺消息
     */
    public function sendStoremsg($param)
    {
        $send = new \sendmsg\sendStoremsg();
        $send->set('code', $param['code']);
        $send->set('store_id', $param['store_id']);
        $send->send($param['param'],isset($param['weixin_param'])?$param['weixin_param']:array(),isset($param['ali_param'])?$param['ali_param']:array(),isset($param['ten_param'])?$param['ten_param']:array());
        return ds_callback(true);
    }

    /**
     * 发送会员消息
     */
    public function sendMemberMsg($param)
    {
        $send = new \sendmsg\sendMemberMsg();
        $send->set('code', $param['code']);
        $send->set('member_id', $param['member_id']);
        if (!empty($param['number']['mobile']))
            $send->set('mobile', $param['number']['mobile']);
        if (!empty($param['number']['email']))
            $send->set('email', $param['number']['email']);
        $send->send($param['param'],isset($param['weixin_param'])?$param['weixin_param']:array(),isset($param['ali_param'])?$param['ali_param']:array(),isset($param['ten_param'])?$param['ten_param']:array());
        return ds_callback(true);
    }



    /**
     * 清理特殊商品促销信息
     */
    public function clearSpecialGoodsPromotion($param)
    {
        // 抢购
        model('groupbuy')->delGroupbuy(array('goods_commonid' => $param['goods_commonid']));
        // 显示折扣
        $condition = array();
        $condition[] = array('goods_id','in', $param['goodsid_array']);
        model('pxianshigoods')->delXianshigoods($condition);
        // 优惠套装
        $condition = array();
        $condition[] = array('goods_id','in', $param['goodsid_array']);
        model('pbundling')->delBundlingGoods($condition);
        // 更新促销价格
        model('goods')->editGoods(array('goods_promotion_price' => Db::raw('goods_price'),'goods_promotion_type' => 0), array('goods_commonid' => $param['goods_commonid']));
        return ds_callback(true);
    }

    /**
     * 删除(买/卖家)订单全部数量缓存
     * @param array $data 订单信息
     * @return boolean
     */
    public function delOrderCountCache($order_info)
    {
        if (empty($order_info))
            return ds_callback(true);
        $order_model = model('order');
        if (isset($order_info['order_id'])) {
            $order_info = $order_model->getOrderInfo(array('order_id' => $order_info['order_id']), array(), 'buyer_id,store_id');
        }
        if(isset($order_info['buyer_id'])) {
            $order_model->delOrderCountCache('buyer', $order_info['buyer_id']);
        }
        if (isset($order_info['store_id'])) {
            $order_model->delOrderCountCache('store', $order_info['store_id']);
        }
        return ds_callback(true);
    }



    /**
     * 发送提货码短信消息
     */
    public function sendPickupcode($param)
    {
        $order_common_info = model('order')->getOrdercommonInfo(array('order_id' => $param['order_id']),'reciver_info');
        if($order_common_info){
            $order_common_info['reciver_info'] = @unserialize($order_common_info['reciver_info']);
            $tpl_info = model('mailtemplates')->getTplInfo(array('mailmt_code' => 'send_pickup_code'));
            $data = array();
            $data['pickup_code'] = $param['pickup_code'];
            $ten_data=array($data['pickup_code']);
            $message = ds_replace_text($tpl_info['mailmt_content'], $data);
            $smslog_param=array(
                        'ali_template_code'=>$tpl_info['ali_template_code'],
                        'ali_template_param'=>$data,
                        'ten_template_code'=>$tpl_info['ten_template_code'],
                        'ten_template_param'=>$ten_data,
                        'message'=>$message,
                    );
            $result = model('smslog')->sendSms($order_common_info['reciver_info']['mob_phone'], $smslog_param);
            if (!$result) {
                return ds_callback(false, '发送提货码短信消息失败order_id:' . $param['order_id']);
            }
            else {
                return ds_callback(true);
            }
        }else{
            return ds_callback(false, '发送提货码短信消息失败order_id:' . $param['order_id']);
        }
    }



    /**
     * 生成卡密代金券
     */
    public function build_pwdvoucher($t_id)
    {
        $t_id = intval($t_id);
        if ($t_id <= 0) {
            return ds_callback(false, '参数错误');
        }
        $voucher_model = model('voucher');
        //查询代金券详情
        $where = array();
        $where[] = array('vouchertemplate_id','=',$t_id);
        $gettype_arr = $voucher_model->getVoucherGettypeArray();
        $where[] = array('vouchertemplate_gettype','=',$gettype_arr['pwd']['sign']);
        $where[] = array('vouchertemplate_isbuild','=',0);
        $where[] = array('vouchertemplate_state','=',1);
        $t_info = $voucher_model->getVouchertemplateInfo($where);
        $t_total = intval($t_info['vouchertemplate_total']);
        if ($t_total <= 0) {
            return ds_callback(false, '代金券模板信息错误');
        }
        while ($t_total > 0) {
            $is_succ = false;
            $insert_arr = array();
            $step = $t_total > 1000 ? 1000 : $t_total;
            for ($t = 0; $t < $step; $t++) {
                $voucher_code = $voucher_model->getVoucherCode(0);
                if (!$voucher_code) {
                    continue;
                }
                $voucher_pwd_arr = $voucher_model->createVoucherPwd($t_info['vouchertemplate_id']);
                if (!$voucher_pwd_arr) {
                    continue;
                }
                $tmp = array();
                $tmp['voucher_code'] = $voucher_code;
                $tmp['vouchertemplate_id'] = $t_info['vouchertemplate_id'];
                $tmp['voucher_title'] = $t_info['vouchertemplate_title'];
                $tmp['voucher_desc'] = $t_info['vouchertemplate_desc'];
                $tmp['voucher_startdate'] = $t_info['vouchertemplate_startdate'];
                $tmp['voucher_enddate'] = $t_info['vouchertemplate_enddate'];
                $tmp['voucher_price'] = $t_info['vouchertemplate_price'];
                $tmp['voucher_limit'] = $t_info['vouchertemplate_limit'];
                $tmp['voucher_store_id'] = $t_info['vouchertemplate_store_id'];
                $tmp['voucher_state'] = 1;
                $tmp['voucher_activedate'] = TIMESTAMP;
                $tmp['voucher_owner_id'] = 0;
                $tmp['voucher_owner_name'] = '';
                $tmp['voucher_order_id'] = 0;
                $tmp['voucher_pwd'] = $voucher_pwd_arr[0];//md5
                $tmp['voucher_pwd2'] = $voucher_pwd_arr[1];
                $insert_arr[] = $tmp;
                $t_total--;
            }

            $result = $voucher_model->addVoucherBatch($insert_arr);
            if ($result && $is_succ == false) {
                $is_succ = true;
            }
        }
        //更新代金券模板
        if ($is_succ) {
            $voucher_model->editVouchertemplate(array('vouchertemplate_id' => $t_info['vouchertemplate_id']), array('vouchertemplate_isbuild' => 1));
            return ds_callback(true);
        }
        else {
            return ds_callback(false);
        }
    }
    
    

    /**
     * 上架
     *
     * @param int $goods_commonid
     */
    public function editProducesOnline($goods_commonid){
        $condition = array(array('goods_commonid','=',$goods_commonid));
        $update = model('goods')->editProducesOnline($condition);
        if (!$update){
            return ds_callback(false);
        }
        return ds_callback(true);
    }


    /**
     * 优惠套装过期
     *
     * @param int $store_id
     */
    public function editBundlingQuotaClose($store_id) {
        $pbundling_model=model('pbundling');
        if(intval(config('ds_config.promotion_bundling_price'))!=0){
            //如果没有购买过套餐，则将之前添加的优惠组合关闭
            Db::name('pbundling')->alias('pbundling')->join('pbundlingquota pbundlingquota', 'pbundling.store_id = pbundlingquota.store_id','LEFT')->where('pbundlingquota.store_id',null)->update(array('bl_state'=>$pbundling_model::STATE0));
        }
        $condition = array(array('store_id','=', $store_id));
        $update = $pbundling_model->editBundlingQuotaClose($condition);
        if (!$update) {
            return ds_callback(false);
        }
        return ds_callback(true);
    }

    /**
     * 推荐展位过期
     *
     * @param int $store_id
     */
    public function editBoothClose($store_id) {
        $pbooth_model=model('pbooth');
        if(intval(config('ds_config.promotion_bundling_price'))!=0){
            //如果没有购买过套餐，则将之前添加的优惠组合关闭
            Db::name('pboothgoods')->alias('pboothgoods')->join('pboothquota pboothquota', 'pboothgoods.store_id = pboothquota.store_id','LEFT')->where('pboothquota.store_id',null)->update(array('boothgoods_state'=>$pbooth_model::STATE0));
        }
        $condition = array(array('store_id','=', $store_id));
        $update = $pbooth_model->editBoothClose($condition);
        if (!$update) {
            return ds_callback(false);
        }
        return ds_callback(true);
    }

    /**
     * 抢购开始更新商品促销价格
     *
     * @param int $goods_commonid
     */
    public function editGoodsGroupbuyPrice($goods_commonid) {
        $condition = array();
        $condition[] = array('goods_commonid','=', $goods_commonid);
        $condition[] = array('groupbuy_starttime','<', TIMESTAMP);
        $condition[] = array('groupbuy_endtime','>', TIMESTAMP);
        $groupbuy = model('groupbuy')->getGroupbuyList($condition);
        $update=true;
        foreach ($groupbuy as $val) {
            $flag=model('goods')->editGoods(array('goods_promotion_price' => $val['groupbuy_price'], 'goods_promotion_type' => 1), array('goods_commonid' => $val['goods_commonid']));
            if(!$flag){
                $update=false;
            }
        }
        if (!$update) {
            return ds_callback(false);
        }
        return ds_callback(true);
    }

    /**
     * 抢购过期
     *
     * @param int $goods_commonid
     */
    public function editExpireGroupbuy($goods_commonid) {
        $condition = array(array('goods_commonid','=', $goods_commonid));
        //抢购活动过期
        $update = model('groupbuy')->editExpireGroupbuy($condition);
        if (!$update) {
            return ds_callback(false);
        }
        return ds_callback(true);
    }

    /**
     * 秒杀过期
     *
     * @param int $xianshi_id
     */
    public function editExpireXianshi($xianshi_id) {
        $condition = array(array('xianshi_id','=', $xianshi_id));
        //秒杀过期
        $update = model('pxianshi')->editExpireXianshi($condition);
        if (!$update) {
            return ds_callback(false);
        }
        return ds_callback(true);
    }

    /**
     * 批发过期
     *
     * @param int $wholesale_id
     */
    public function editExpireWholesale($wholesale_id) {
        $condition = array(array('wholesale_id','=', $wholesale_id));
        //秒杀过期
        $update = model('wholesale')->editExpireWholesale($condition);
        if (!$update) {
            return ds_callback(false);
        }
        return ds_callback(true);
    }
    
    /**
     * 更新使用的平台代金券状态
     * @param $input_voucher_list
     * @throws Exception
     */
    public function editMallVoucherState($mallvoucher_info)
    {
        $mallvoucheruser_model = model('mallvouchertemplate');
        $update = $mallvoucheruser_model->editMallVoucherUser(array('mallvoucheruser_state' => $mallvoucher_info['mallvoucheruser_state']), array('mallvoucheruser_id' => $mallvoucher_info['mallvoucheruser_id']), $mallvoucher_info['mallvoucheruser_ownerid']);
        return ds_callback(true);
    }

}