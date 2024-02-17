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
class Buy
{
    /**
     * 会员信息
     * @var array
     */
    private $_member_info = array();

    /**
     * 下单数据
     * @var array
     */
    private $_order_data = array();

    /**
     * 表单数据
     * @var array
     */
    private $_post_data = array();

    /**
     * buy_1.logic 对象
     * @var obj
     */
    private $_logic_buy_1;

    public function __construct()
    {
        $this->_logic_buy_1 = model('buy_1','logic');
    }

    /**
     * 购买第一步
     * @param type $cart_id
     * @param type $ifcart
     * @param type $member_id
     * @param type $store_id
     * @param type $extra  额外特殊判断处理数据，比如拼团功能  
     * @return type
     */
    public function buyStep1($cart_id, $ifcart, $member_id, $store_id,$extra=array())
    {

        //得到购买商品信息
        if ($ifcart) {
            $result = $this->getCartList($cart_id, $member_id);
        }
        else {
            $result = $this->getGoodsList($cart_id, $member_id, $store_id,$extra);
        }

        if (!$result['code']) {
            return $result;
        }
        //得到页面所需要数据：收货地址、发票、代金券、预存款、商品列表等信息
        $result = $this->getBuyStep1Data($member_id, $result['data']);
        return $result;
    }

    /**
     * 第一步：处理购物车
     * @param type $cart_id 购物车
     * @param type $member_id 会员编号
     * @param type $extra 额外特殊判断处理数据，比如拼团功能  
     * @return type
     */
    public function getCartList($cart_id, $member_id,$extra=array())
    {
        $cart_model = model('cart');

        //取得POST ID和购买数量
        $buy_items = $this->_parseItems($cart_id);
        if (empty($buy_items)) {
            return ds_callback(false, '所购商品无效');
        }

        if (count($buy_items) > 50) {
            return ds_callback(false, '一次最多只可购买50种商品');
        }

        //购物车列表
        $condition = array();
        $condition[] = array('cart_id','in', array_keys($buy_items));
        $condition[] = array('buyer_id','=', $member_id);
        $cart_list = $cart_model->getCartList('db', $condition);

        //购物车列表 [得到最新商品属性及促销信息]
        $cart_list = $this->_logic_buy_1->getGoodsCartList($cart_list);

        //商品列表 [优惠套装子商品与普通商品同级罗列]
        $goods_list = $this->_getGoodsList($cart_list);

        //以店铺下标归类
        $store_cart_list = $this->_getStoreCartList($cart_list);

        return ds_callback(true, '', array('goods_list' => $goods_list, 'store_cart_list' => $store_cart_list));

    }
    /**
     * 第一步：处理立即购买
     * @param type $cart_id 购物车
     * @param type $member_id 会员编号
     * @param type $store_id 店铺编号
     * @param type $extra 额外特殊判断处理数据，比如拼团功能  
     * @return type
     */
    public function getGoodsList($cart_id, $member_id, $store_id,$extra=array())
    {
        //取得POST ID和购买数量
        $buy_items = $this->_parseItems($cart_id);
        if (empty($buy_items)) {
            return ds_callback(false, '所购商品无效');
        }

        $goods_id = key($buy_items);
        $quantity = current($buy_items);

        //商品信息[得到最新商品属性及促销信息]
        $goods_info = $this->_logic_buy_1->getGoodsOnlineInfo($goods_id, intval($quantity),$extra,$member_id);
        if (empty($goods_info)) {
            return ds_callback(false, '商品已下架或不存在');
        }

        //不能购买自己店铺的商品
        if ($goods_info['store_id'] == $store_id) {
            return ds_callback(false, '不能购买自己店铺的商品');
        }

        //进一步处理数组
        $store_cart_list = array();
        $goods_list = array();
        $goods_info['chain_id'] = 0;
        $goods_list[0] = $store_cart_list[$goods_info['store_id']][0] = $goods_info;

        return ds_callback(true, '', array('goods_list' => $goods_list, 'store_cart_list' => $store_cart_list));
    }

    /**
     * 购买第一步：返回商品、促销、地址、发票等信息，然后交前台抛出
     * @param unknown $member_id
     * @param unknown $data 商品信息
     * @return
     */
    public function getBuyStep1Data($member_id, $data)
    {
        //list($goods_list, $store_cart_list) = $data;
        $goods_list = $data['goods_list'];
        $store_cart_list = $data['store_cart_list'];

        //定义返回数组
        $result = array();

        //商品金额计算(分别对每个商品/优惠套装小计、每个店铺小计)
        list($store_cart_list, $store_goods_total, $store_goods_original_total, $store_goods_discount_total) = $this->_logic_buy_1->calcCartList($store_cart_list);
        $result['store_cart_list'] = $store_cart_list;
        $result['store_goods_total'] = $store_goods_total;
        $result['store_goods_original_total'] = $store_goods_original_total;
        $result['store_goods_discount_total'] = $store_goods_discount_total;

        //取得店铺优惠 - 满即送(赠品列表，店铺满送规则列表)
        list($store_premiums_list, $store_mansong_rule_list) = $this->_logic_buy_1->getMansongruleCartListByTotal($store_goods_total);
        $result['store_premiums_list'] = $store_premiums_list;
        $result['store_mansong_rule_list'] = $store_mansong_rule_list;

        //重新计算优惠后(满即送)的店铺实际商品总金额
        $store_goods_total = $this->_logic_buy_1->reCalcGoodsTotal($store_goods_total, $store_mansong_rule_list, 'mansong');

        //返回店铺可用的代金券
        $store_voucher_list = $this->_logic_buy_1->getStoreAvailableVoucherList($store_goods_total, $member_id);
        $result['store_voucher_list'] = $store_voucher_list;
        
        //返回店铺可用的平台代金券
        $mall_voucher_list = $this->_logic_buy_1->getAvailableMallVoucherUserList($goods_list, $member_id);
        
        $result['mall_voucher_list'] = $mall_voucher_list;

        
        //返回需要计算运费的店铺ID数组 和 不需要计算运费(满免运费活动的)店铺ID及描述
        list($need_calc_sid_list, $cancel_calc_sid_list) = $this->_logic_buy_1->getStoreFreightDescList($store_goods_total);
        $result['need_calc_sid_list'] = $need_calc_sid_list;
        $result['cancel_calc_sid_list'] = $cancel_calc_sid_list;

        //将商品ID、数量、售卖区域、运费序列化，加密，输出到模板，选择地区AJAX计算运费时作为参数使用
        $freight_list = $this->_logic_buy_1->getStoreFreightList($goods_list, array_keys($cancel_calc_sid_list));
        $result['freight_list'] = $this->buyEncrypt($freight_list, $member_id);
        
        //处理加密店铺商品 用于获取门店用
        $chaingoods_list = array();
        $chain_goods = array();
        foreach($store_cart_list as $key => $val){
            $chaingoods_list[$key] = $this->buyEncrypt($val,$member_id);
        }
        $result['chaingoods_list'] = $chaingoods_list;

        //输出用户默认收货地址
        $result['address_info'] = model('address')->getDefaultAddressInfo(array('member_id' => $member_id));

        //输出有货到付款时，在线支付和货到付款及每种支付下商品数量和详细列表
        $pay_goods_list = $this->_logic_buy_1->getOfflineGoodsPay($goods_list);
        if (!empty($pay_goods_list['offline'])) {
            $result['pay_goods_list'] = $pay_goods_list;
            $result['ifshow_offpay'] = true;
        }
        else {
            //如果所购商品只支持线上支付，支付方式不允许修改
            $result['deny_edit_payment'] = true;
            $result['pay_goods_list'] = $pay_goods_list;
            $result['ifshow_offpay'] = FALSE;
        }

        //是否是预售订单
        $if_presell=0;
        $presell_deposit_amount=0;
            if(count($goods_list)==1){
                $current_goods=current($goods_list);
                if(!empty($current_goods['presell_info']) && $current_goods['presell_info']['presell_type']==2){
                    $if_presell=1;
                    $presell_deposit_amount=$current_goods['presell_info']['presell_deposit_amount'];
                    $result['deny_edit_payment'] = true;
                    $result['ifshow_offpay'] = FALSE;
                }
            }
        $result['if_presell'] = $if_presell;
        $result['presell_deposit_amount'] = $presell_deposit_amount;
        
        //发票 :只有所有商品都支持增值税发票才提供增值税发票
        $vat_deny=false;
        foreach ($goods_list as $goods) {
            if (!intval($goods['goods_vat'])) {
                $vat_deny = true;
                break;
            }
        }
        //不提供增值税发票时抛出true(模板使用)
        $result['vat_deny'] = $vat_deny;
        $result['vat_hash'] = $this->buyEncrypt($result['vat_deny'] ? 'deny_vat' : 'allow_vat', $member_id);

        //输出默认使用的发票信息
        $inv_info = model('invoice')->getDefaultInvoiceInfo(array('member_id' => $member_id));
        if ($inv_info['invoice_state'] == '2' && !$vat_deny) {
            $inv_info['content'] = '增值税发票 ' . $inv_info['invoice_company'] . ' ' . $inv_info['invoice_company_code'] . ' ' . $inv_info['invoice_reg_addr'];
        }
        elseif ($inv_info['invoice_state'] == '2' && $vat_deny) {
            $inv_info = array();
            $inv_info['content'] = '不需要发票';
        }
        elseif (!empty($inv_info)) {
            $inv_info['content'] = '普通发票 ' . $inv_info['invoice_title'] . ' ' . $inv_info['invoice_code']. ' ' . $inv_info['invoice_content'];
        }
        else {
            $inv_info = array();
            $inv_info['content'] = '不需要发票';
        }
        $result['inv_info'] = $inv_info;

        $buyer_info = model('member')->getMemberInfoByID($member_id);
        if (floatval($buyer_info['available_predeposit']) > 0) {
            $result['available_predeposit'] = $buyer_info['available_predeposit'];
        }
        if (floatval($buyer_info['available_rc_balance']) > 0) {
            $result['available_rc_balance'] = $buyer_info['available_rc_balance'];
        }
        $result['member_paypwd'] = $buyer_info['member_paypwd'] ? true : false;

        return ds_callback(true, '', $result);
    }

    /**
     * 购买第二步
     * @param array $post
     * @param int $member_id
     * @param string $member_name
     * @param string $member_email
     * @return array
     */
    public function buyStep2($post, $member_id, $member_name, $member_email)
    {

        $this->_member_info['member_id'] = $member_id;
        $this->_member_info['member_name'] = $member_name;
        $this->_member_info['member_email'] = $member_email;
        $this->_post_data = $post;

        try {

            $order_model = model('order');
            Db::startTrans();
            $this->_logic_buy_1->lock=true;
            //第1步 表单验证
            $this->_createOrderStep1();

            //第2步 得到购买商品信息
            $this->_createOrderStep2();

            //第3步 得到购买相关金额计算等信息
            $this->_createOrderStep3();

            //第4步 生成订单
            $this->_createOrderStep4();

            //第6步 订单后续处理
            $this->_createOrderStep6();
            
            Db::commit();
            return ds_callback(true, '', $this->_order_data);

        } catch (\Exception $e) {
            Db::rollback();
            return ds_callback(false, $e->getMessage());
        }

    }
    /**
     * 生成推广记录
     * @param array $order_list
     */
    public function addOrderInviter($order_list = array()) {
        if(!config('ds_config.inviter_open')){
            return;
        }
        if (empty($order_list) || !is_array($order_list))
            return;
        $inviter_ratio_1=config('ds_config.inviter_ratio_1');
        $inviter_ratio_2=config('ds_config.inviter_ratio_2');
        $inviter_ratio_3=config('ds_config.inviter_ratio_3');
        $orderinviter_model = model('orderinviter');
        foreach ($order_list as $order_id => $order) {
            //如果是线下支付因为不会生成结算单所以不生成推广记录
            if($order['payment_code']=='offline'){
                continue;
            }
            foreach($order['order_goods'] as $goods){
                //查询商品的分销信息
                $goods_common_info=Db::name('goodscommon')->alias('gc')->join('goods g','g.goods_commonid=gc.goods_commonid')->where('g.goods_id='.$goods['goods_id'])->field('gc.goods_commonid,gc.inviter_open,gc.inviter_ratio')->find();
                if(!$goods_common_info['inviter_open']){
                    continue;
                }
            $goods_amount=$goods['goods_pay_price']*$goods_common_info['inviter_ratio']/100;
            $inviter_ratios=array(
                $inviter_ratio_1,
                $inviter_ratio_2,
                $inviter_ratio_3,
            );
            //判断买家是否是分销员
            if(config('ds_config.inviter_return')){
                if(Db::name('inviter')->where('inviter_state=1 AND inviter_id='.$order['buyer_id'])->value('inviter_id')){
                    if (isset($inviter_ratios[0]) && floatval($inviter_ratios[0]) > 0) {
                        $ratio=round($inviter_ratios[0]*$goods_common_info['inviter_ratio']/100,2);
                            $money_1 = round($inviter_ratios[0] / 100 * $goods_amount, 2);
                            if ($money_1 > 0) {
                 
                                    //生成推广记录
                                    Db::name('orderinviter')->insert(array(
                                        'orderinviter_addtime' => TIMESTAMP,
                                        'orderinviter_store_name' => $order['store_name'],
                                        'orderinviter_goods_amount' => $goods['goods_pay_price'],
                                        'orderinviter_goods_quantity' => $goods['goods_num'],
                                        'orderinviter_order_type' => 0,
                                        'orderinviter_store_id' => $goods['store_id'],
                                        'orderinviter_goods_commonid' => $goods_common_info['goods_commonid'],
                                        'orderinviter_goods_id' => $goods['goods_id'],
                                        'orderinviter_level' => 1,
                                        'orderinviter_ratio' => $ratio,
                                        'orderinviter_goods_name' => $goods['goods_name'],
                                        'orderinviter_order_id' => $order_id,
                                        'orderinviter_order_sn' => $order['order_sn'],
                                        'orderinviter_member_id' => $order['buyer_id'],
                                        'orderinviter_member_name' => $order['buyer_name'],
                                        'orderinviter_money' => $money_1,
                                        'orderinviter_remark' => '获得分销员返佣，佣金比例' . $ratio . '%，订单号' . $order['order_sn'],
                                    ));
               
                            }
                        }
                    }
            }
            //一级推荐人
            $inviter_1_id=Db::name('member')->where('member_id',$order['buyer_id'])->value('inviter_id');
            if(!$inviter_1_id || !Db::name('inviter')->where('inviter_state=1 AND inviter_id='.$inviter_1_id)->value('inviter_id')){
                continue;
            }
            
            
            $inviter_1=Db::name('member')->where('member_id',$inviter_1_id)->field('inviter_id,member_id,member_name')->find();
            if($inviter_1 && isset($inviter_ratios[0]) && floatval($inviter_ratios[0])>0){
                $ratio=round($inviter_ratios[0]*$goods_common_info['inviter_ratio']/100,2);
                $money_1=round($inviter_ratios[0]/100*$goods_amount,2);
                if($money_1>0){
              
                        //生成推广记录
                        Db::name('orderinviter')->insert(array(
                            'orderinviter_addtime' => TIMESTAMP,
                            'orderinviter_store_name' => $order['store_name'],
                            'orderinviter_goods_amount'=>$goods['goods_pay_price'],
                            'orderinviter_goods_quantity'=>$goods['goods_num'],
                            'orderinviter_order_type'=>0,
                            'orderinviter_store_id'=>$goods['store_id'],
                            'orderinviter_goods_commonid'=>$goods_common_info['goods_commonid'],
                            'orderinviter_goods_id'=>$goods['goods_id'],
                            'orderinviter_level'=>1,
                            'orderinviter_ratio' => $ratio,
                            'orderinviter_goods_name'=>$goods['goods_name'],
                            'orderinviter_order_id'=>$order_id,
                            'orderinviter_order_sn'=>$order['order_sn'],
                            'orderinviter_member_id'=>$inviter_1['member_id'],
                            'orderinviter_member_name'=>$inviter_1['member_name'],
                            'orderinviter_money'=>$money_1,
                            'orderinviter_remark'=>'获得一级推荐佣金，佣金比例'.$ratio.'%，推荐关系'.$inviter_1['member_name'].'->'.$order['buyer_name'].'，订单号'.$order['order_sn'],
                        ));
            
                }
            }
            if(config('ds_config.inviter_level')<=1){
                continue;
            }
            //二级推荐人
            $inviter_2_id=Db::name('member')->where('member_id',$inviter_1_id)->value('inviter_id');
            if(!$inviter_2_id || !Db::name('inviter')->where('inviter_state=1 AND inviter_id='.$inviter_2_id)->value('inviter_id')){
                continue;
            }
            $inviter_2=Db::name('member')->where('member_id',$inviter_2_id)->field('inviter_id,member_id,member_name')->find();
            if($inviter_2 && isset($inviter_ratios[1]) && floatval($inviter_ratios[1])>0){
                $ratio=round($inviter_ratios[1]*$goods_common_info['inviter_ratio']/100,2);
                $money_2=round($inviter_ratios[1]/100*$goods_amount,2);
                if($money_2>0){
               
                        //生成推广记录
                        Db::name('orderinviter')->insert(array(
                            'orderinviter_addtime' => TIMESTAMP,
                            'orderinviter_store_name' => $order['store_name'],
                            'orderinviter_goods_amount'=>$goods['goods_pay_price'],
                            'orderinviter_goods_quantity'=>$goods['goods_num'],
                            'orderinviter_order_type'=>0,
                            'orderinviter_store_id'=>$goods['store_id'],
                            'orderinviter_goods_commonid'=>$goods_common_info['goods_commonid'],
                            'orderinviter_goods_id'=>$goods['goods_id'],
                            'orderinviter_level'=>2,
                            'orderinviter_ratio' => $ratio,
                            'orderinviter_goods_name'=>$goods['goods_name'],
                            'orderinviter_order_id'=>$order_id,
                            'orderinviter_order_sn'=>$order['order_sn'],
                            'orderinviter_member_id'=>$inviter_2['member_id'],
                            'orderinviter_member_name'=>$inviter_2['member_name'],
                            'orderinviter_money'=>$money_2,
                            'orderinviter_remark'=>'获得二级推荐佣金，佣金比例'.$ratio.'%，推荐关系'.$inviter_2['member_name'].'->'.$inviter_1['member_name'].'->'.$order['buyer_name'].'，订单号'.$order['order_sn'],
                        ));
           
                }
            }
            if(config('ds_config.inviter_level')<=2){
                continue;
            }
            //三级推荐人
            $inviter_3_id=Db::name('member')->where('member_id',$inviter_2_id)->value('inviter_id');
            if(!$inviter_3_id || !Db::name('inviter')->where('inviter_state=1 AND inviter_id='.$inviter_3_id)->value('inviter_id')){
                continue;
            }
            $inviter_3=Db::name('member')->where('member_id',$inviter_3_id)->field('inviter_id,member_id,member_name')->find();
            if($inviter_3 && isset($inviter_ratios[2]) && floatval($inviter_ratios[2])>0){
                $ratio=round($inviter_ratios[2]*$goods_common_info['inviter_ratio']/100,2);
                $money_3=round($inviter_ratios[2]/100*$goods_amount,2);
                if($money_3>0){
    
                        //生成推广记录
                        Db::name('orderinviter')->insert(array(
                            'orderinviter_addtime' => TIMESTAMP,
                            'orderinviter_store_name' => $order['store_name'],
                            'orderinviter_goods_amount'=>$goods['goods_pay_price'],
                            'orderinviter_goods_quantity'=>$goods['goods_num'],
                            'orderinviter_order_type'=>0,
                            'orderinviter_store_id'=>$goods['store_id'],
                            'orderinviter_goods_commonid'=>$goods_common_info['goods_commonid'],
                            'orderinviter_goods_id'=>$goods['goods_id'],
                            'orderinviter_level'=>3,
                            'orderinviter_ratio' => $ratio,
                            'orderinviter_goods_name'=>$goods['goods_name'],
                            'orderinviter_order_id'=>$order_id,
                            'orderinviter_order_sn'=>$order['order_sn'],
                            'orderinviter_member_id'=>$inviter_3['member_id'],
                            'orderinviter_member_name'=>$inviter_3['member_name'],
                            'orderinviter_money'=>$money_3,
                            'orderinviter_remark'=>'获得三级推荐佣金，佣金比例'.$ratio.'%，推荐关系'.$inviter_3['member_name'].'->'.$inviter_2['member_name'].'->'.$inviter_1['member_name'].'->'.$order['buyer_name'].'，订单号'.$order['order_sn'],
                        ));
               
                }
            }
        }
        }
    }
    /**
     * 删除购物车商品
     * @param unknown $ifcart
     * @param unknown $cart_ids
     */
    public function delCart($ifcart, $member_id, $cart_ids)
    {
        if (!$ifcart || !is_array($cart_ids))
            return;
        $cart_id_str = implode(',', $cart_ids);
        if (preg_match('/^[\d,]+$/', $cart_id_str)) {
            $condition = array();
            $condition[] = array('buyer_id','=',$member_id);
            $condition[] = array('cart_id','in',$cart_ids);
            model('cart')->delCart('db', $condition,$member_id);
        }
    }

    /**
     * 选择不同地区时，异步处理并返回每个店铺总运费以及本地区是否能使用货到付款
     * 如果店铺统一设置了满免运费规则，则售卖区域无效
     * 如果店铺未设置满免规则，且使用售卖区域，按售卖区域计算，如果其中有商品使用相同的售卖区域，则两种商品数量相加后再应用该售卖区域计算（即作为一种商品算运费）
     * 如果未找到售卖区域，按免运费处理
     * 如果没有使用售卖区域，商品运费按快递价格计算，运费不随购买数量增加
     */
    public function changeAddr($freight_hash, $city_id, $area_id, $member_id,$goods = array())
    {
        //限制配送的商品
        $transport_model = model('transport');
        $goods_list = $this->buyDecrypt($goods, $member_id);
        $limitidarray = array();
        if(is_array($goods_list)){
            foreach($goods_list as $key => $val){
                $transport = $transport_model->getTransportInfo(array('transport_id' => $val['transport_id']));
                
                if($transport['transport_is_limited'] == 1){
                    $extend_list = $transport_model->getTransportextendList(array('transport_id' => $val['transport_id']));
                    if(!empty($extend_list)){
                        foreach($extend_list as $k => $v){
                            if($v['transportext_area_id'] != ''){
                                if (strpos($v['transportext_area_id'], "," . $city_id . ",") == false) {
                                    $limitidarray[] = $val['goods_id'];
                                }
                            } 
                        }
                    }
                    $limitidarray =  array_unique($limitidarray);
                }
            }
        }
        
        //$city_id计算售卖区域,$area_id计算货到付款
        $city_id = intval($city_id);
        $area_id = intval($area_id);
        if ($city_id <= 0 || $area_id <= 0)
            return null;

        //将hash解密，得到运费信息(店铺ID，运费,售卖区域ID,购买数量),hash内容有效期为1小时
        $freight_list = $this->buyDecrypt($freight_hash, $member_id);

        //算运费
        $store_freight_list = $this->_logic_buy_1->calcStoreFreight($freight_list, $city_id);

        $data = array();
        $data['state'] = empty($store_freight_list) ? 'fail' : 'success';
        $data['content'] = $store_freight_list;

        //是否能使用货到付款(只有包含平台店铺的商品才会判断)
        //$if_include_platform_store = array_key_exists(DEFAULT_PLATFORM_STORE_ID,$freight_list['iscalced']) || array_key_exists(DEFAULT_PLATFORM_STORE_ID,$freight_list['nocalced']);

        //$offline_store_id_array = model('store')->getOwnShopIds();
        $order_platform_store_ids = array();

        if (!empty($freight_list['iscalced']) && is_array($freight_list['iscalced']))
            foreach (array_keys($freight_list['iscalced']) as $k)
                $order_platform_store_ids[$k] = null;

        if (!empty($freight_list['nocalced']) && is_array($freight_list['nocalced']))
            foreach (array_keys($freight_list['nocalced']) as $k)
                //if (in_array($k, $offline_store_id_array))
                $order_platform_store_ids[$k] = null;

        //if ($order_platform_store_ids) {
        $allow_offpay_batch = model('offpayarea')->checkSupportOffpayBatch($area_id, array_keys($order_platform_store_ids));
        /*
                //JS验证使用
                $data['allow_offpay'] = array_filter($allow_offpay_batch) ? '1' : '0';
                $data['allow_offpay_batch'] = $allow_offpay_batch;
            } else {*/
        //JS验证使用
        $data['allow_offpay'] = array_filter($allow_offpay_batch) ? '1' : '0';
        $data['allow_offpay_batch'] = $allow_offpay_batch;
        //}

        //PHP验证使用
        $data['offpay_hash'] = $this->buyEncrypt($data['allow_offpay'] ? 'allow_offpay' : 'deny_offpay', $member_id);
        $data['offpay_hash_batch'] = $this->buyEncrypt($data['allow_offpay_batch'], $member_id);
        $data['limitidarray']  = $limitidarray;

        return $data;
    }
    
    /**
     * 自提门店
     * @param int $goods_commonid
     * @param string $fcode
     * @return array
     */
    public function changechain($goods, $area_id,$member_id)
    {
        $chain_model=model('chain');
        
        //将hash解密，得到商品信息
        $goods_list = $this->buyDecrypt($goods, $member_id);
        if(!empty($goods_list)){
            $store_id = $goods_list[0]['store_id'];
        }
        
        $allchain_list = array();
        $chain_idsarr = array();
        $onechain_id = array();
        foreach($goods_list as $key => $val){
            if($val['bl_id'] == 1){
                foreach($val['bl_goods_list'] as $k => $v){
                    $chain_ids=Db::name('chain_goods')->where(array(array('goods_id','=',$v['goods_id']),array('goods_storage','>=',1)))->column('chain_id');
                    if($key == 0){
                        $onechain_id = $chain_ids;
                        $chain_idsarr = array_intersect($onechain_id,$chain_ids);
                    }else{
                        $chain_idsarr = array_intersect($chain_idsarr,$chain_ids);
                    }
                }
            }else{
                $chain_ids=Db::name('chain_goods')->where(array(array('goods_id','=',$val['goods_id']),array('goods_storage','>=',$val['goods_num'])))->column('chain_id');
                if($key == 0){
                    $onechain_id = $chain_ids;
                    $chain_idsarr = array_intersect($onechain_id,$chain_ids);
                }else{
                    $chain_idsarr = array_intersect($chain_idsarr,$chain_ids);
                }
            }
        }
        foreach($goods_list as $key => $val){
            $condition=array();
            $condition[]=array('chain_if_pickup','=',1);
            $condition[]=array('chain_id','in',$chain_idsarr);
            if($area_id){
                $condition[]=array('chain_area_2|chain_area_3','=',$area_id);
            }
            $chain_list=$chain_model->getChainOpenList($condition);
            $allchain_list[] = $chain_list ;
        }
        
        $chain_list=array_values($chain_list);
        
        return $chain_list;
    }
    

    /**
     * 验证F码
     * @param int $goods_commonid
     * @param string $fcode
     * @return array
     */
    public function checkFcode($goods_goodid, $fcode)
    {
        $fcode_info = model('goodsfcode')->getGoodsfcode(array(
                                                              'goods_commonid' => $goods_goodid, 'goodsfcode_code' => $fcode,
                                                              'goodsfcode_state' => 0
                                                          ));
        if ($fcode_info) {
            return ds_callback(true, '', $fcode_info);
        }
        else {
            return ds_callback(false, 'F码错误');
        }
    }

    /**
     * 订单生成前的表单验证与处理
     *
     */
    private function _createOrderStep1()
    {
        $post = $this->_post_data;

        //取得商品ID和购买数量
        $input_buy_items = $this->_parseItems($post['cart_id']);
        if (empty($input_buy_items)) {
            throw new \think\Exception('所购商品无效', 10006);
        }

        //验证收货地址
        $input_address_id = intval($post['address_id']);
        if ($input_address_id <= 0) {
            throw new \think\Exception('请选择收货地址', 10006);
        }
        else {
            $input_address_info = model('address')->getAddressInfo(array('address_id' => $input_address_id));
            if ($input_address_info['member_id'] != $this->_member_info['member_id']) {
                throw new \think\Exception('请选择收货地址', 10006);
            }
            if($input_address_info['chain_id']){
                //门店是否开启了代收
                if(!isset($input_address_info['chain_if_collect']) || $input_address_info['chain_if_collect']!=1){
                    throw new \think\Exception('代收点已关闭代收', 10006);
                }
            }
        }

        //收货地址城市编号
        $input_city_id = intval($input_address_info['city_id']);

        //是否开增值税发票
        $input_if_vat = $this->buyDecrypt($post['vat_hash'], $this->_member_info['member_id']);
        if (!in_array($input_if_vat, array('allow_vat', 'deny_vat'))) {
            throw new \think\Exception('订单保存出现异常[值税发票出现错误]，请重试', 10006);
        }
        $input_if_vat = ($input_if_vat == 'allow_vat') ? true : false;

        //是否支持货到付款
        $input_if_offpay = $this->buyDecrypt($post['offpay_hash'], $this->_member_info['member_id']);
        if (!in_array($input_if_offpay, array('allow_offpay', 'deny_offpay'))) {
            throw new \think\Exception('订单保存出现异常[货到付款验证错误]，请重试', 10006);
        }
        $input_if_offpay = ($input_if_offpay == 'allow_offpay') ? true : false;

        // 是否支持货到付款 具体到各个店铺
        $input_if_offpay_batch = $this->buyDecrypt($post['offpay_hash_batch'], $this->_member_info['member_id']);
        if (!is_array($input_if_offpay_batch)) {
            throw new \think\Exception('订单保存出现异常[部分店铺付款方式出现异常]，请重试', 10006);
        }

        //付款方式:在线支付/货到付款(online/offline)
        if (!in_array($post['pay_name'], array('online', 'offline'))) {
            throw new \think\Exception('付款方式错误，请重新选择', 10006);
        }
        $input_pay_name = $post['pay_name'];

        //验证发票信息
        $input_invoice_info=array();
        if (!empty($post['invoice_id'])) {
            $input_invoice_id = intval($post['invoice_id']);
            if ($input_invoice_id > 0) {
                $input_invoice_info = model('invoice')->getInvoiceInfo(array('invoice_id' => $input_invoice_id));
                if ($input_invoice_info['member_id'] != $this->_member_info['member_id']) {
                    throw new \think\Exception('请正确填写发票信息', 10006);
                }
            }
        }

        //验证店铺代金券
        $input_voucher_list = array();
        if (!empty($post['voucher']) && is_array($post['voucher'])) {
            foreach ($post['voucher'] as $store_id => $voucher) {
                if (preg_match_all('/^(\d+)\|(\d+)\|([\d.]+)$/', $voucher, $matchs)) {
                    if (floatval($matchs[3][0]) > 0) {
                        $input_voucher_list[$store_id]['vouchertemplate_id'] = $matchs[1][0];
                        $input_voucher_list[$store_id]['voucher_price'] = $matchs[3][0];
                    }
                }
            }
        }
        
        //验证平台代金券
        $input_mallvoucher_list = array();
        if(isset($post['mallvoucher'])){
            $mallvoucher = explode('|',$post['mallvoucher']);
            if(!empty($mallvoucher[0])){
                $input_mallvoucher_list['mallvoucher_price'] = substr($post['mallvoucher'],strripos($post['mallvoucher'],"|")+1);
                $input_mallvoucher_list['mallvouchertemplate_id'] = substr($post['mallvoucher'],0,strrpos($post['mallvoucher'],"|"));
            }
        }
        
        //处理自提门店
        $input_chain_list = array();
        if (!empty($post['chain_goods']) && is_array($post['chain_goods'])) {
            foreach ($post['chain_goods'] as $store_id => $chain_goods) {
               $input_chain_list[$store_id]['chain_id'] = $chain_goods;
            }
        }
        
        //保存数据
        $this->_order_data['input_buy_items'] = $input_buy_items;
        $this->_order_data['input_city_id'] = $input_city_id;
        $this->_order_data['input_pay_name'] = $input_pay_name;
        $this->_order_data['input_if_offpay'] = $input_if_offpay;
        $this->_order_data['input_if_offpay_batch'] = $input_if_offpay_batch;
        $this->_order_data['input_pay_message'] = $post['pay_message'];
        $this->_order_data['input_address_info'] = $input_address_info;
        $this->_order_data['input_invoice_info'] = $input_invoice_info;
        $this->_order_data['input_voucher_list'] = $input_voucher_list;
        $this->_order_data['input_chain_list'] = $input_chain_list;
        $this->_order_data['input_mallvoucher_list'] = $input_mallvoucher_list;
        $this->_order_data['order_from'] = $post['order_from'] == 2 ? 2 : 1;

    }

    /**
     * 得到购买商品信息
     *
     */
    private function _createOrderStep2()
    {
        $post = $this->_post_data;
        $input_buy_items = $this->_order_data['input_buy_items'];

        if ($post['ifcart']) {
            //购物车列表
            $cart_model = model('cart');
            $condition = array();
            $condition[] = array('cart_id','in', array_keys($input_buy_items));
            $condition[] = array('buyer_id','=', $this->_member_info['member_id']);
            $cart_list = $cart_model->getCartList('db', $condition);

            //购物车列表 [得到最新商品属性及促销信息]
            $cart_list = $this->_logic_buy_1->getGoodsCartList($cart_list);

            //商品列表 [优惠套装子商品与普通商品同级罗列]
            $goods_list = $this->_getGoodsList($cart_list);

            //以店铺下标归类
            $store_cart_list = $this->_getStoreCartList($cart_list);
        }
        else {

            //来源于直接购买
            $goods_id = key($input_buy_items);
            $quantity = current($input_buy_items);
            
            
            //额外数据用来处理拼团等其他活动
            $pintuan_id = isset($post['pintuan_id']) ? intval($post['pintuan_id']):0;
            $extra = array();
            if ($pintuan_id > 0) {
                $extra['pintuan_id'] = $pintuan_id; #拼团ID
                #是否为开团订单
                $extra['pintuangroup_id'] = empty(input('param.pintuangroup_id'))?0:intval(input('param.pintuangroup_id'));
            }
            $bargainorder_id = isset($post['bargainorder_id']) ? intval($post['bargainorder_id']):0;
            if ($bargainorder_id > 0) {
                $extra['bargainorder_id'] = $bargainorder_id; #砍价ID
            }
            
            //商品信息[得到最新商品属性及促销信息]
            $goods_info = $this->_logic_buy_1->getGoodsOnlineInfo($goods_id, intval($quantity),$extra,$this->_member_info['member_id']);
            if (empty($goods_info)) {
                throw new \think\Exception('商品已下架或不存在', 10006);
            }

            //进一步处理数组
            $store_cart_list = array();
            $goods_list = array();
            
            $goods_list[0] = $store_cart_list[$goods_info['store_id']][0] = $goods_info;

        }

        //F码验证
        $goodsfcode_id='';
        if(!empty($post['fcode'])) {
            $goodsfcode_id = $this->_checkFcode($goods_list, $post['fcode']);
            if (!$goodsfcode_id) {
                throw new \think\Exception('F码商品验证错误', 10006);
            }
        }
        $this->_order_data['goodsfcode_id'] = $goodsfcode_id;
        //保存数据
        $this->_order_data['goods_list'] = $goods_list;
        $this->_order_data['store_cart_list'] = $store_cart_list;
    }

    /**
     * 得到购买相关金额计算等信息
     *
     */
    private function _createOrderStep3()
    {
        $goods_list = $this->_order_data['goods_list'];
        $store_cart_list = $this->_order_data['store_cart_list'];
        $input_voucher_list = $this->_order_data['input_voucher_list'];
        $input_mallvoucher_list = $this->_order_data['input_mallvoucher_list'];

        $input_city_id = $this->_order_data['input_city_id'];

        //商品金额计算(分别对每个商品/优惠套装小计、每个店铺小计)
        list($store_cart_list, $store_goods_total, $store_goods_original_total, $store_goods_discount_total) = $this->_logic_buy_1->calcCartList($store_cart_list);

        //取得店铺优惠 - 满即送(赠品列表，店铺满送规则列表)
        list($store_premiums_list, $store_mansong_rule_list) = $this->_logic_buy_1->getMansongruleCartListByTotal($store_goods_total);

        //重新计算店铺扣除满即送后商品实际支付金额
        $store_final_goods_total = $this->_logic_buy_1->reCalcGoodsTotal($store_goods_total, $store_mansong_rule_list, 'mansong');
        
        //得到有效的店铺代金券
        $input_voucher_list = $this->_logic_buy_1->reParseVoucherList($input_voucher_list, $store_goods_total, $this->_member_info['member_id']);

        //重新计算店铺扣除优惠券送商品实际支付金额
        $store_final_goods_total = $this->_logic_buy_1->reCalcGoodsTotal($store_final_goods_total, $input_voucher_list, 'voucher');

        //得到有效的平台代金券
        $input_mallvoucher_list = $this->_logic_buy_1->reParseMallVoucherList($input_mallvoucher_list, $goods_list, $this->_member_info['member_id']);

        //平台优惠券前的金额
        $store_final_goods_total_before = $store_final_goods_total;

        //计算店铺平台优惠券优惠到的金额
        $store_mallvoucher_goods_total = $this->_logic_buy_1->reCalcGoodsTotal($store_final_goods_total, $input_mallvoucher_list, 'mallvoucher',$goods_list);

        //平台优惠券后的折扣金额
        $store_final_goods_total_after = array();
        foreach ($store_final_goods_total as $key => $value) {
            $store_final_goods_total_after[$key] = $store_final_goods_total_before[$key]-$store_mallvoucher_goods_total[$key];
        }

        //计算每个店铺(所有店铺级优惠活动)总共优惠多少
        $store_promotion_total = $this->_logic_buy_1->getStorePromotionTotal($store_goods_total, $store_final_goods_total);
        //计算每个店铺运费
        list($need_calc_sid_list, $cancel_calc_sid_list) = $this->_logic_buy_1->getStoreFreightDescList($store_final_goods_total);
        $freight_list = $this->_logic_buy_1->getStoreFreightList($goods_list, array_keys($cancel_calc_sid_list));
        $store_freight_total = $this->_logic_buy_1->calcStoreFreight($freight_list, $input_city_id);
        if(empty($store_freight_total)){
            throw new \think\Exception('抱歉，商品在所在地区无货', 10006);
        }
        //计算店铺最终订单实际支付金额(加上运费)
        $store_final_order_total = $this->_logic_buy_1->reCalcGoodsTotal($store_final_goods_total, $store_freight_total, 'freight');

        //计算店铺分类佣金[改由任务计划]
        $store_gc_id_commis_rate_list = model('storebindclass')->getStoreGcidCommisRateList($goods_list);

        //将赠品追加到购买列表(如果库存0，则不送赠品)
        $append_premiums_to_cart_list = $this->_logic_buy_1->appendPremiumsToCartList($store_cart_list, $store_premiums_list, $store_mansong_rule_list, $this->_member_info['member_id']);
        if ($append_premiums_to_cart_list === false) {
            throw new \think\Exception('抱歉，您购买的商品库存不足，请重购买', 10006);
        }
        else {
            list($store_cart_list, $goods_buy_quantity, $store_mansong_rule_list) = $append_premiums_to_cart_list;
        }

        //保存数据
        $this->_order_data['store_goods_total'] = $store_goods_total;
        $this->_order_data['store_final_order_total'] = $store_final_order_total;
        $this->_order_data['store_final_goods_total_after'] = $store_final_goods_total_after;
        $this->_order_data['store_freight_total'] = $store_freight_total;
        $this->_order_data['store_promotion_total'] = $store_promotion_total;
        
        $this->_order_data['store_gc_id_commis_rate_list'] = $store_gc_id_commis_rate_list;
        $this->_order_data['store_mansong_rule_list'] = $store_mansong_rule_list;
        $this->_order_data['store_cart_list'] = $store_cart_list;
        $this->_order_data['goods_buy_quantity'] = $goods_buy_quantity;
        $this->_order_data['input_voucher_list'] = $input_voucher_list;
        $this->_order_data['input_mallvoucher_list'] = $input_mallvoucher_list;

    }

    /**
     * 生成订单
     * @param array $input
     * @throws Exception
     * @return array array(支付单sn,订单列表)
     */
    private function _createOrderStep4()
    {

        extract($this->_order_data);

        $member_id = $this->_member_info['member_id'];
        $member_name = $this->_member_info['member_name'];
        $member_email = $this->_member_info['member_email'];

        $order_model = model('order');

        //存储生成的订单数据
        $order_list = array();
        //存储通知信息
        $notice_list = array();

        //每个店铺订单是货到付款还是线上支付,店铺ID=>付款方式[在线支付/货到付款]
        $store_pay_type_list = $this->_logic_buy_1->getStorePayTypeList(array_keys($store_cart_list), $input_if_offpay, $input_pay_name);

        foreach ($store_pay_type_list as $k => & $v) {
            if (empty($input_if_offpay_batch[$k]))
                $v = 'online';
        }

        $pay_sn = makePaySn($member_id);
        $order_pay = array();
        $order_pay['pay_sn'] = $pay_sn;
        $order_pay['buyer_id'] = $member_id;
        $order_pay_id = $order_model->addOrderpay($order_pay);
        if (!$order_pay_id) {
            throw new \think\Exception('订单保存失败[未生成支付单]', 10006);
        }
        //收货人信息
        list($reciver_info, $reciver_name) = $this->_logic_buy_1->getReciverAddr($input_address_info);

        foreach ($store_cart_list as $store_id => $goods_list) {

            //取得本店优惠额度(后面用来计算每件商品实际支付金额，结算需要)
            $promotion_total = !empty($store_promotion_total[$store_id]) ? $store_promotion_total[$store_id] : 0;
            //本店总的优惠比例,保留3位小数
            $should_goods_total = $store_final_order_total[$store_id] - $store_freight_total[$store_id] + $promotion_total;
            $promotion_rate = abs(number_format($promotion_total / $should_goods_total, 5, '.', ''));
            if ($promotion_rate <= 1) {
                $promotion_rate = floatval(substr($promotion_rate, 0, 5));
            }
            else {
                $promotion_rate = 0;
            }

            //每种商品的优惠金额累加保存入 $promotion_sum
            $promotion_sum = 0;

            $order = array();
            $order_common = array();
            $order_goods = array();

            $order['order_sn'] = $this->_logic_buy_1->makeOrderSn($order_pay_id);
            $order['pay_sn'] = $pay_sn;
            $order['store_id'] = $store_id;
            $order['store_name'] = $goods_list[0]['store_name'];
            $order['buyer_id'] = $member_id;
            $order['buyer_name'] = $member_name;
            $order['buyer_email'] = $member_email;
            $order['add_time'] = TIMESTAMP;
            $order['payment_code'] = $store_pay_type_list[$store_id];
            if(!empty($goods_list[0]['presell_info']) && $goods_list[0]['presell_info']['presell_type']==2){
                $order['presell_deposit_amount'] = round($goods_list[0]['presell_info']['presell_deposit_amount']*$goods_list[0]['goods_num'],2);
                $order['presell_end_time'] = $goods_list[0]['presell_info']['presell_end_time'];
            }
            $order['order_state'] = $store_pay_type_list[$store_id] == 'online' ? (isset($this->_post_data['presell_pay']) && $this->_post_data['presell_pay']==2?ORDER_STATE_DEPOSIT:ORDER_STATE_NEW) : ORDER_STATE_PAY;
            
            //判断是否自提
            if (isset($input_chain_list[$store_id]) && !empty($input_chain_list[$store_id])) {
                $order['chain_id'] = $input_chain_list[$store_id]['chain_id']; 
                //选择自提 不计算运费
                $order['shipping_fee'] = 0;
                $store_final_order_total[$store_id] = $store_final_order_total[$store_id] - $store_freight_total[$store_id]; 
            }else{
                $order['chain_id'] = 0;
                $order['shipping_fee'] = $store_freight_total[$store_id];
            }
            
            if (isset($input_mallvoucher_list) && !empty($input_mallvoucher_list)) {
                $order['order_amount'] = $store_final_order_total[$store_id] - $store_final_goods_total_after[$store_id]; 
            }else{
                $order['order_amount'] = $store_final_order_total[$store_id];
            }
            $order['goods_amount'] = $order['order_amount'] - $order['shipping_fee'];
            $order['order_from'] = $order_from;
            //如果支持方式为空时，默认为货到付款 
            if ($order['payment_code'] == "") {
                $order['payment_code'] = "offline";
            }
            if($order['payment_code'] == "offline" && $input_address_info['chain_id']){
                throw new \think\Exception('代收点不可以使用货到付款[未生成订单数据]', 10006);
            }
            $order_id = $order_model->addOrder($order);
            if (!$order_id) {
                throw new \think\Exception('订单保存失败[未生成订单数据]', 10006);
            }
            $order['order_id'] = $order_id;
            $order_list[$order_id] = $order;

            $order_common['order_id'] = $order_id;
            $order_common['store_id'] = $store_id;
            $order_common['order_message'] = $input_pay_message[$store_id];

            //店铺代金券
            if (isset($input_voucher_list[$store_id])) {
                $order_common['voucher_price'] = $input_voucher_list[$store_id]['voucher_price'];
                $order_common['voucher_code'] = $input_voucher_list[$store_id]['voucher_code'];
            }
            
            //平台代金券
            $mallvouchertotal = 0;
            if (isset($input_mallvoucher_list) && !empty($input_mallvoucher_list)) {
                $input_mallvoucher_list['mallvoucheruser_state'] = 1;
                $order_common['mallvoucher_price'] = $store_final_goods_total_after[$store_id];
                $order_common['mallvoucher_code'] = $input_mallvoucher_list['mallvoucheruser_code'];
                foreach ($goods_list as $goods_info) {
                    $gc_id = ','.$goods_info['gc_id'].',';
                    if(strpos($input_mallvoucher_list['mallvouchertemplate_gcidarr'],$gc_id) !== false){ 
                       $mallvouchertotal += $goods_info['goods_price'] * $goods_info['goods_num'];
                    }
                }
            }
            $order_common['reciver_info'] = $reciver_info;
            $order_common['reciver_name'] = $reciver_name;
            $order_common['reciver_city_id'] = $input_city_id;

            //发票信息
            $order_common['invoice_info'] = $this->_logic_buy_1->createInvoiceData($input_invoice_info);

            //保存促销信息
            if (isset($store_mansong_rule_list[$store_id])) {
                $order_common['promotion_info'] = addslashes($store_mansong_rule_list[$store_id]['desc']);
            }

            $order_id = $order_model->addOrdercommon($order_common);
            if (!$order_id) {
                throw new \think\Exception('订单保存失败[未生成订单扩展数据]', 10006);
            }
            $order_list[$order_id]['order_common']=$order_common;
            //生成order_goods订单商品数据
            $i = 0;

            foreach ($goods_list as $goods_info) {
                if (!$goods_info['state'] || !$goods_info['storage_state']) {
                    throw new \think\Exception('部分商品已经下架或库存不足，请重新选择', 10006);
                }
                if (!intval($goods_info['bl_id'])) {
                    //如果不是优惠套装
                    $order_goods[$i]['order_id'] = $order_id;
                    $order_goods[$i]['goods_id'] = $goods_info['goods_id'];
                    $order_goods[$i]['store_id'] = $store_id;
                    $order_goods[$i]['goods_name'] = $goods_info['goods_name'];
                    $order_goods[$i]['goods_price'] = $goods_info['goods_price'];
                    $order_goods[$i]['goods_num'] = $goods_info['goods_num'];
                    $order_goods[$i]['goods_image'] = $goods_info['goods_image'];
                    $order_goods[$i]['buyer_id'] = $member_id;
                    $ifgroupbuy = false;
                    if (isset($goods_info['ifgroupbuy'])) {
                        $ifgroupbuy = true;
                        $order_goods[$i]['goods_type'] = 2;
                    }
                    elseif (isset($goods_info['ifxianshi'])) {
                        $order_goods[$i]['goods_type'] = 3;
                    }
                    elseif (isset($goods_info['ifpresell'])) {
                        $order_goods[$i]['goods_type'] = 10;
                    }
                    elseif (isset($goods_info['ifwholesale'])) {
                        $order_goods[$i]['goods_type'] = 9;
                    }
                    elseif (isset($goods_info['ifzengpin'])) {
                        $order_goods[$i]['goods_type'] = 5;
                    }
                    elseif (isset($goods_info['ifpintuan']) && intval($this->_post_data['pintuan_id'])>0) {
                        //拼团订单
                        /**
                         * $goods_info['ifpintuan'] , $goods_info['pintuan_id']  此数据是通过商品ID 获取到是否为拼团订单
                         * $this->_post_data['pintuan_id']   $this->_post_data['pintuangroup_id'] 此数据是通过post 过来的数据，用来判断是否为首个拼团订单:0首个订单 其他为所属订单
                         */
                        $order_goods[$i]['goods_type'] = 6;
                        $res=$this->_logic_buy_1->updatePintuan($this->_post_data,$goods_info,$order,0,$member_id);
                        $goods_info['promotions_id'] = $res['pintuangroup_id'];
                    }elseif(isset($goods_info['ifbargain']) && $goods_info['ifbargain']){
                        //砍价订单
                        $order_goods[$i]['goods_type'] = 8;
                        if(!model('pbargainorder')->editPbargainorder(array('bargainorder_id'=>$goods_info['promotions_id']),array('order_id'=>$order_id))){
                            throw new \think\Exception('砍价活动更新失败', 10006);
                        }
                    }
                    elseif (isset($goods_info['ifmgdiscount'])) {
                        $order_goods[$i]['goods_type'] = 7;
                    }
                    else {
                        $order_goods[$i]['goods_type'] = 1;
                    }
                    $order_goods[$i]['promotions_id'] = isset($goods_info['promotions_id']) ? $goods_info['promotions_id'] : 0;

                    $order_goods[$i]['commis_rate'] = floatval(@$store_gc_id_commis_rate_list[$store_id][$goods_info['gc_id']]);

                    $order_goods[$i]['gc_id'] = $goods_info['gc_id'];
                    //计算商品金额
                    $goods_total = $goods_info['goods_price'] * $goods_info['goods_num'];
                    
                    //计算商品平台优惠券优惠金额
                    $goodsmallvoucher = 0;
                    
                    if (isset($input_mallvoucher_list) && !empty($input_mallvoucher_list)) {
                        $gc_id = ','.$goods_info['gc_id'].',';
                        if(strpos($input_mallvoucher_list['mallvouchertemplate_gcidarr'],$gc_id) !== false){ 
                           $proportion = sprintf("%.2f", $goods_total/$mallvouchertotal);
                           $goodsmallvoucher = sprintf("%.2f",$store_final_goods_total_after[$store_id] * $proportion);
                        }
                    }
                    
                    //计算本件商品优惠金额
                    $promotion_value = floor($goods_total * ($promotion_rate));
                    $order_goods[$i]['goods_pay_price'] = $goods_total - $promotion_value - $goodsmallvoucher ;
                    $promotion_sum += $promotion_value;
                    $i++;

                    //存储库存报警数据
                    if (isset($goods_info['goods_storage_alarm'])&&$goods_info['goods_storage_alarm'] >= ($goods_info['goods_storage'] - $goods_info['goods_num'])) {
                        $param = array();
                        $param['common_id'] = $goods_info['goods_commonid'];
                        $param['sku_id'] = $goods_info['goods_id'];
                        $ten_param=array($param['common_id'],$param['sku_id']);
                        $weixin_param=array(
                            'url' => config('ds_config.h5_store_site_url').'/pages/seller/goods/GoodsForm2?commonid='.$goods_info['goods_commonid'].'&class_id='.$goods_info['gc_id'],
                            'data'=>array(
                                "keyword1" => array(
                                    "value" => $goods_info['goods_storage'] - $goods_info['goods_num'],
                                    "color" => "#333"
                                ),
                                "keyword2" => array(
                                    "value" => date('Y-m-d H:i'),
                                    "color" => "#333"
                                )
                            ),
                        );
                        
                        $notice_list['goods_storage_alarm'][$goods_info['store_id']] = array('param'=>$param,'ali_param'=>$param,'ten_param'=>$ten_param,'weixin_param'=>$weixin_param);
                    }

                }
                elseif (!empty($goods_info['bl_goods_list']) && is_array($goods_info['bl_goods_list'])) {
                    $ifgroupbuy = false;
                    //优惠套装
                    foreach ($goods_info['bl_goods_list'] as $bl_goods_info) {
                        $order_goods[$i]['order_id'] = $order_id;
                        $order_goods[$i]['goods_id'] = $bl_goods_info['goods_id'];
                        $order_goods[$i]['store_id'] = $store_id;
                        $order_goods[$i]['goods_name'] = $bl_goods_info['goods_name'];
                        $order_goods[$i]['goods_price'] = $bl_goods_info['blgoods_price'];
                        $order_goods[$i]['goods_num'] = $goods_info['goods_num'];
                        $order_goods[$i]['goods_image'] = $bl_goods_info['goods_image'];
                        $order_goods[$i]['buyer_id'] = $member_id;
                        $order_goods[$i]['goods_type'] = 4;
                        $order_goods[$i]['promotions_id'] = $bl_goods_info['bl_id'];
                        $order_goods[$i]['commis_rate'] = floatval(@$store_gc_id_commis_rate_list[$store_id][$goods_info['gc_id']]);
                        $order_goods[$i]['gc_id'] = $bl_goods_info['gc_id'];

                        //计算商品实际支付金额(goods_price减去分摊优惠金额后的值)
                        $goods_total = $bl_goods_info['blgoods_price'] * $goods_info['goods_num'];
                        
                        //计算商品平台优惠券优惠金额
                        $goodsmallvoucher = 0;
                        if (isset($input_mallvoucher_list) && !empty($input_mallvoucher_list)) {
                            $gc_id = ','.$goods_info['gc_id'].',';
                            if(strpos($input_mallvoucher_list['mallvouchertemplate_gcidarr'],$gc_id) !== false){ 
                               $proportion = sprintf("%.2f", $goods_total/$mallvouchertotal);
                               $goodsmallvoucher = sprintf("%.2f",$store_final_goods_total_after[$store_id] * $proportion);
                            }
                        }
                        
                        //计算本件商品优惠金额
                        $promotion_value = floor($goods_total * ($promotion_rate));
                        $order_goods[$i]['goods_pay_price'] = $goods_total - $promotion_value - $goodsmallvoucher;
                        $promotion_sum += $promotion_value;
                        $i++;

                        //存储库存报警数据
                        if ($bl_goods_info['goods_storage_alarm'] >= ($bl_goods_info['goods_storage'] - $goods_info['goods_num'])) {
                            $param = array();
                            $param['common_id'] = $bl_goods_info['goods_commonid'];
                            $param['sku_id'] = $bl_goods_info['goods_id'];
                            $ten_param=array($param['common_id'],$param['sku_id']);
                            $weixin_param=array(
                                'url' => config('ds_config.h5_store_site_url').'/pages/seller/goods/GoodsForm2?commonid='.$goods_info['goods_commonid'].'&class_id='.$goods_info['gc_id'],
                                'data'=>array(
                                    "keyword1" => array(
                                        "value" => $goods_info['goods_storage'] - $goods_info['goods_num'],
                                        "color" => "#333"
                                    ),
                                    "keyword2" => array(
                                        "value" => date('Y-m-d H:i'),
                                        "color" => "#333"
                                    )
                                ),
                            );
                            $notice_list['goods_storage_alarm'][$bl_goods_info['store_id']] = array('param'=>$param,'ali_param'=>$param,'ten_param'=>$ten_param,'weixin_param'=>$weixin_param);
                        }
                    }
                }
            }
            //将因舍出小数部分出现的差值补到最后一个商品的实际成交价中(商品goods_price=0时不给补，可能是赠品)
            if ($promotion_total > $promotion_sum) {
                $i--;
                for ($i; $i >= 0; $i--) {
                    if (floatval($order_goods[$i]['goods_price']) > 0) {
                        $order_goods[$i]['goods_pay_price'] -= $promotion_total - $promotion_sum;
                        break;
                    }
                }
            }

            $insert = $order_model->addOrdergoods($order_goods);
            if (!$insert) {
                throw new \think\Exception('订单保存失败[未生成商品数据]', 10006);
            }
            //自提订单
            $chain_order_data = Db::name('order')->where(array(array('order_id','=',$order_id),array('chain_id','>',0)))->find();
            $chain_list=array();
            $chain_model=model('chain');
            $chain_order_model=model('chain_order');
            
            if(!empty($chain_order_data)){
                if(!isset($chain_list[$chain_order_data['chain_id']])){
                    $chain_list[$chain_order_data['chain_id']]=$chain_model->getChainOpenInfo(array(array('chain_id','=',$chain_order_data['chain_id'])));
                }
                if(!$chain_list[$chain_order_data['chain_id']]){
                    throw new \think\Exception('自提点不存在[未生成自提数据]', 10006);
                }
                if($chain_list[$chain_order_data['chain_id']]['chain_if_pickup']!=1){
                    throw new \think\Exception('自提点已关闭自提[未生成自提数据]', 10006);
                }
                $chain_order_model->addChainOrder(array(
                    'order_id'=>$chain_order_data['order_id'],
                    'order_goods_id'=>0,
                    'chain_order_type'=>2,
                    'chain_order_pickup_code'=>rand(1, 9) . rand(0, 9) . rand(0, 9) . rand(0, 9),
                    'payment_code'=>$order_list[$order_id]['payment_code'],
                    'chain_order_add_time'=>TIMESTAMP,
                    'order_sn'=>$order_list[$order_id]['order_sn'],
                    'chain_id'=>$chain_order_data['chain_id'],
                    'store_id'=>$chain_order_data['store_id'],
                ));
                //减库存
                foreach($order_goods as $key => $val){
                    Db::name('chain_goods')->where(array(array('chain_id','=',$chain_order_data['chain_id']),array('goods_id','=',$val['goods_id'])))->dec('goods_storage',$val['goods_num'])->update();
                }
            }
                
            $order_list[$order_id]['order_goods']=$order_goods;
            //存储商家发货提醒数据
            if ($order['order_state'] == ORDER_STATE_PAY) {
                //更改自提点的订单状态
                $chain_order_model->editChainOrderPay($order_id);
                
                $weixin_param=array(
                    'url' => config('ds_config.h5_store_site_url').'/pages/seller/order/OrderDetail?order_id='.$order_id,
                    'data'=>array(
                        "keyword1" => array(
                            "value" => $order['order_sn'],
                            "color" => "#333"
                        ),
                        "keyword2" => array(
                            "value" => $order_goods[0]['goods_name'].(count($order_goods)>1? sprintf(lang('order_goods_more_than_one'), count($order_goods)):''),
                            "color" => "#333"
                        ),
                        "keyword3" => array(
                            "value" => $order['order_amount'],
                            "color" => "#333"
                        ),
                        "keyword4" => array(
                            "value" => date('Y-m-d H:i',$order['add_time']),
                            "color" => "#333"
                        )
                    ),
                );
                $notice_list['new_order'][$order['store_id']] = array('param'=>array('order_sn' => $order['order_sn']),'ali_param'=>array('order_sn' => $order['order_sn']),'ten_param'=>array($order['order_sn']),'weixin_param'=>$weixin_param);
            }
        }

        //保存数据
        $this->_order_data['pay_sn'] = $pay_sn;
        $this->_order_data['order_list'] = $order_list;
        $this->_order_data['notice_list'] = $notice_list;
        $this->_order_data['ifgroupbuy'] = $ifgroupbuy;
        $this->_order_data['input_mallvoucher_list'] = $input_mallvoucher_list;
    }


    /**
     * 订单后续其它处理
     */
    private function _createOrderStep6()
    {
        $ifcart = $this->_post_data['ifcart'];
        $goods_buy_quantity = $this->_order_data['goods_buy_quantity'];
        $input_voucher_list = $this->_order_data['input_voucher_list'];
        $input_mallvoucher_list = $this->_order_data['input_mallvoucher_list'];
        $store_cart_list = $this->_order_data['store_cart_list'];
        $input_buy_items = $this->_order_data['input_buy_items'];
        $order_list = $this->_order_data['order_list'];
        $input_address_info = $this->_order_data['input_address_info'];
        $notice_list = $this->_order_data['notice_list'];
        $goodsfcode_id = $this->_order_data['goodsfcode_id'];
        $ifgroupbuy = $this->_order_data['ifgroupbuy'];

        //变更库存和销量
        $res=model('goods')->createOrderUpdateStorage($goods_buy_quantity);
        if(!$res['code']){
            throw new \think\Exception($res['msg'], 10006);
        }
        
        //更新使用的店铺代金券状态
        if (!empty($input_voucher_list) && is_array($input_voucher_list)) {
            model('voucher')->editVoucherState($input_voucher_list);
        }
        
        //更新使用的平台代金券状态
        if (!empty($input_mallvoucher_list) && is_array($input_mallvoucher_list)) {
            model('Mallvouchertemplate')->editMallvoucherState($input_mallvoucher_list);
        }

        //更新F码使用状态
        if ($goodsfcode_id) {
            model('goodsfcode')->updateGoodsfcode($goodsfcode_id);
        }

        //更新抢购购买人数和数量
        if ($ifgroupbuy) {
            foreach ($store_cart_list as $goods_list) {
                foreach ($goods_list as $goods_info) {
                    if (isset($goods_info['ifgroupbuy']) && isset($goods_info['groupbuy_id'])) {
                        $groupbuy_info = array();
                        $groupbuy_info['groupbuy_id'] = $goods_info['groupbuy_id'];
                        $groupbuy_info['quantity'] = $goods_info['goods_num'];
                        model('cron')->addCron(array('cron_exetime'=>TIMESTAMP,'cron_type'=>'editGroupbuySaleCount','cron_value'=>serialize($groupbuy_info)));
                    }
                }
            }
        }

        //删除购物车中的商品
        $this->delCart($ifcart, $this->_member_info['member_id'], array_keys($input_buy_items));
        cookie('cart_goods_num', '', -3600);


        //保存订单代收信息
        if (config('ds_config.chain_isuse') && intval($input_address_info['chain_id'])) {
            $data = array();
            $data['chain_id'] = $input_address_info['chain_id'];
            foreach ($order_list as $v) {
                if(!$v['chain_id']){
                    $data['order_sn_list'][$v['order_id']]['store_id'] = $v['store_id'];
                    $data['order_sn_list'][$v['order_id']]['order_sn'] = $v['order_sn'];
                    $data['order_sn_list'][$v['order_id']]['add_time'] = $v['add_time'];
                }
            }
            if(isset($data['order_sn_list'])){
                model('chain_order')->saveChainOrder($data);
            }
        }
        //生成推广记录
        $this->addOrderInviter($order_list);
        //发送提醒类信息
        if (!empty($notice_list)) {
            foreach ($notice_list as $code => $value) {
                $temp=current($value);
                model('cron')->addCron(array('cron_exetime'=>TIMESTAMP,'cron_type'=>'sendStoremsg','cron_value'=>serialize(array(
                    'code' => $code, 'store_id' => key($value),'param' => $temp['param'], 'weixin_param' => $temp['weixin_param'], 'ali_param' => $temp['ali_param'], 'ten_param' => $temp['ten_param'])), 
                ));
            }
        }

    }

    /**
     * 加密
     * @param array /string $string
     * @param int $member_id
     * @return mixed arrray/string
     */
    public function buyEncrypt($string, $member_id)
    {
        $buy_key = sha1(md5($member_id . '&' . md5(config('ds_config.setup_date'))));
        if (is_array($string)) {
            $string = serialize($string);
        }
        else {
            $string = strval($string);
        }
        return ds_encrypt(base64_encode($string), $buy_key);
    }

    /**
     * 解密
     * @param string $string
     * @param int $member_id
     * @param number $ttl
     */
    public function buyDecrypt($string, $member_id, $ttl = 0)
    {
        $buy_key = sha1(md5($member_id . '&' . md5(config('ds_config.setup_date'))));
        if (empty($string))
            return;
        $string = base64_decode(ds_decrypt(strval($string), $buy_key, $ttl));
        return ($tmp = @unserialize($string)) !== false ? $tmp : $string;
    }

    /**
     * 得到所购买的id和数量
     *
     */
    private function _parseItems($cart_id)
    {
        //存放所购商品ID和数量组成的键值对
        $buy_items = array();
        if (is_array($cart_id)) {
            foreach ($cart_id as $value) {
                if (preg_match_all('/^(\d{1,10})\|(\d{1,6})$/', $value, $match)) {
                    if (intval($match[2][0]) > 0) {
                        $buy_items[$match[1][0]] = $match[2][0];
                    }
                }
            }
        }
        return $buy_items;
    }

    /**
     * 从购物车数组中得到商品列表
     * @param unknown $cart_list
     */
    private function _getGoodsList($cart_list)
    {
        if (empty($cart_list) || !is_array($cart_list))
            return $cart_list;
        $goods_list = array();
        $i = 0;
        foreach ($cart_list as $key => $cart) {
            if (!$cart['state'] || !$cart['storage_state'])
                continue;
            //购买数量
            $quantity = $cart['goods_num'];
            if (!intval($cart['bl_id'])) {
                //如果是普通商品
                $goods_list[$i]['goods_num'] = $quantity;
                $goods_list[$i]['goods_id'] = $cart['goods_id'];
                $goods_list[$i]['store_id'] = $cart['store_id'];
                $goods_list[$i]['gc_id'] = $cart['gc_id'];
                $goods_list[$i]['goods_weight'] = $cart['goods_weight'];
                $goods_list[$i]['gc_id_1'] = $cart['gc_id_1'];
                $goods_list[$i]['gc_id_2'] = $cart['gc_id_2'];
                $goods_list[$i]['gc_id_3'] = $cart['gc_id_3'];
                $goods_list[$i]['goods_name'] = $cart['goods_name'];
                $goods_list[$i]['goods_price'] = $cart['goods_price'];
                $goods_list[$i]['goods_original_price'] = $cart['goods_original_price'];
                $goods_list[$i]['store_name'] = $cart['store_name'];
                $goods_list[$i]['goods_image'] = $cart['goods_image'];
                $goods_list[$i]['transport_id'] = $cart['transport_id'];
                $goods_list[$i]['goods_freight'] = $cart['goods_freight'];
                $goods_list[$i]['goods_vat'] = $cart['goods_vat'];
                $goods_list[$i]['is_goodsfcode'] = $cart['is_goodsfcode'];
                $goods_list[$i]['bl_id'] = 0;
                $i++;
            }
            else {
                //如果是优惠套装商品
                foreach ($cart['bl_goods_list'] as $bl_goods) {
                    $goods_list[$i]['goods_num'] = $quantity;
                    $goods_list[$i]['goods_id'] = $bl_goods['goods_id'];
                    $goods_list[$i]['store_id'] = $cart['store_id'];
                    $goods_list[$i]['gc_id'] = $bl_goods['gc_id'];
                    $goods_list[$i]['goods_weight'] = $bl_goods['goods_weight'];
                    $goods_list[$i]['gc_id_1'] = $bl_goods['gc_id_1'];
                    $goods_list[$i]['gc_id_2'] = $bl_goods['gc_id_2'];
                    $goods_list[$i]['gc_id_3'] = $bl_goods['gc_id_3'];
                    $goods_list[$i]['goods_name'] = $bl_goods['goods_name'];
                    $goods_list[$i]['goods_price'] = $bl_goods['blgoods_price'];
                    $goods_list[$i]['goods_original_price'] = $bl_goods['goods_original_price'];
                    $goods_list[$i]['store_name'] = $bl_goods['store_name'];
                    $goods_list[$i]['goods_image'] = $bl_goods['goods_image'];
                    $goods_list[$i]['transport_id'] = $bl_goods['transport_id'];
                    $goods_list[$i]['goods_freight'] = $bl_goods['goods_freight'];
                    $goods_list[$i]['goods_vat'] = $bl_goods['goods_vat'];
                    $goods_list[$i]['bl_id'] = $cart['bl_id'];
                    $i++;
                }
            }
        }
        return $goods_list;
    }

    /**
     * 将下单商品列表转换为以店铺ID为下标的数组
     *
     * @param array $cart_list
     * @return array
     */
    private function _getStoreCartList($cart_list)
    {
        if (empty($cart_list) || !is_array($cart_list))
            return $cart_list;
        $new_array = array();
        foreach ($cart_list as $cart) {
            $new_array[$cart['store_id']][] = $cart;
        }
        return $new_array;
    }

    /**
     * 本次下单是否需要码及F码合法性
     * 无需使用F码，返回 true
     * 需要使用F码，返回($goodsfcode_id/false)
     */
    private function _checkFcode($goods_list, $fcode)
    {
        $is_goodsfcode = false;
        foreach ($goods_list as $k => $v) {
            if ($v['is_goodsfcode'] == 1) {
                $is_goodsfcode = true;
                break;
            }
        }
        if (!$is_goodsfcode)
            return true;
        if (empty($fcode) || count($goods_list) > 1) {
            return false;
        }
        $goods_info = $goods_list[0];
        $fcode_info = $this->checkFcode($goods_info['goods_commonid'], $fcode);
        if ($fcode_info['code'] && !$fcode_info['data']['goodsfcode_state']) {
            return intval($fcode_info['data']['goodsfcode_id']);
        }
        else {
            return false;
        }
    }
}