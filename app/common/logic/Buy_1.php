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
class Buy_1 {
    public $lock=false;//是否加锁
    /**
     * 取得商品最新的属性及促销[购物车]
     * @param unknown $cart_list
     */
    public function getGoodsCartList($cart_list) {

        $cart_list = $this->_getOnlineCartList($cart_list);

        //优惠套装
        $this->_getBundlingCartList($cart_list);
        //会员等级折扣
        $this->getMgdiscountCartList($cart_list);
        //抢购
        $this->getGroupbuyCartList($cart_list);
        //秒杀
        $this->getXianshiCartList($cart_list);
        //预售
        $this->getPresellCartList($cart_list);
        //批发
        $this->getWholesaleCartList($cart_list);
        //赠品
        $this->_getGiftCartList($cart_list);

        return $cart_list;
    }

    /**
     * 取得商品最新的属性及促销[立即购买]
     * @param type $goods_id
     * @param type $quantity
     * @param type $extra  
     * @return array
     */
    public function getGoodsOnlineInfo($goods_id, $quantity, $extra = array(), $member_id = 0) {
        $goods_info = $this->_getGoodsOnlineInfo($goods_id, $quantity);
        if (isset($extra['pintuan_id']) && intval($extra['pintuan_id']) > 0) {
            //如果是特定拼团商品，则只按照拼团的规则进行处理
            $this->getPintuanInfo($goods_info, $goods_info['goods_num'], $extra, $member_id);
        } else if (isset($extra['bargainorder_id']) && intval($extra['bargainorder_id']) > 0) {
            $this->getBargainInfo($goods_info, $goods_info['goods_num'], $extra);
        } else {
            //会员等级折扣
            $this->getMgdiscountInfo($goods_info);
            //抢购
            $this->getGroupbuyInfo($goods_info, $goods_info['goods_num']);
            //秒杀
            $this->getXianshiInfo($goods_info, $goods_info['goods_num']);
            //预售
            $this->getPresellInfo($goods_info, $goods_info['goods_num']);
            //批发
            $this->getWholesaleInfo($goods_info, $goods_info['goods_num']);
            //赠品
            $this->_getGoodsgiftList($goods_info);
        }
        return $goods_info;
    }

    /**
     * 商品金额计算(分别对每个商品/优惠套装小计、每个店铺小计)
     * @param unknown $store_cart_list 以店铺ID分组的购物车商品信息
     * @return array
     */
    public function calcCartList($store_cart_list) {
        if (empty($store_cart_list) || !is_array($store_cart_list))
            return array($store_cart_list, array(), 0);

        //存放每个店铺的商品总金额
        $store_goods_total = array();
        //存放本次下单所有店铺商品总金额
        $order_goods_total = 0;

        foreach ($store_cart_list as $store_id => $store_cart) {
            $tmp_amount = 0;
            $tmp_amount2 = 0;
            $tmp_amount3 = 0;
            foreach ($store_cart as $key => $cart_info) {
                $store_cart[$key]['goods_original_total'] = ds_price_format($cart_info['goods_original_price'] * $cart_info['goods_num']); //商品原价
                $store_cart[$key]['goods_total'] = ds_price_format($cart_info['goods_price'] * $cart_info['goods_num']);
                $store_cart[$key]['goods_discount_total'] = ds_price_format($store_cart[$key]['goods_original_total'] - $store_cart[$key]['goods_total']); //优惠金额
                $store_cart[$key]['goods_image_url'] = goods_cthumb($store_cart[$key]['goods_image']);
                $tmp_amount += $store_cart[$key]['goods_total'];
                $tmp_amount2 += $store_cart[$key]['goods_original_total'];
                $tmp_amount3 += $store_cart[$key]['goods_discount_total'];
            }
            $store_cart_list[$store_id] = $store_cart;
            $store_goods_total[$store_id] = ds_price_format($tmp_amount);
            $store_goods_original_total[$store_id] = ds_price_format($tmp_amount2);
            $store_goods_discount_total[$store_id] = ds_price_format($tmp_amount3);
        }
        return array($store_cart_list, $store_goods_total, $store_goods_original_total, $store_goods_discount_total);
    }

    /**
     * 取得店铺级优惠 - 跟据商品金额返回每个店铺当前符合的一条活动规则，如果有赠品，则自动追加到购买列表，价格为0
     * @param unknown $store_goods_total 每个店铺的商品金额小计，以店铺ID为下标
     * @return array($premiums_list,$mansong_rule_list) 分别为赠品列表[下标自增]，店铺满送规则列表[店铺ID为下标]
     */
    public function getMansongruleCartListByTotal($store_goods_total) {
        if (!config('ds_config.promotion_allow') || empty($store_goods_total) || !is_array($store_goods_total))
            return array(array(), array());

        $pmansong_model = model('pmansong');

        //定义赠品数组，下标为店铺ID
        $premiums_list = array();
        //定义满送活动数组，下标为店铺ID
        $mansong_rule_list = array();

        foreach ($store_goods_total as $store_id => $goods_total) {
            $rule_info = $pmansong_model->getMansongruleByStoreID($store_id, $goods_total);
            if (is_array($rule_info) && !empty($rule_info)) {
                //即不减金额，也找不到促销商品时(已下架),此规则无效
                if (empty($rule_info['mansongrule_discount']) && empty($rule_info['mansong_goods_name'])) {
                    continue;
                }
                $rule_info['desc'] = $this->_parseMansongruleDesc($rule_info);
                $rule_info['discount'] = ds_price_format($rule_info['mansongrule_discount']);
                $mansong_rule_list[$store_id] = $rule_info;
                //如果赠品在售,有库存,则追加到购买列表
                if (!empty($rule_info['mansong_goods_name']) && !empty($rule_info['goods_storage'])) {
                    $data = array();
                    $data['goods_id'] = $rule_info['goods_id'];
                    $data['goods_name'] = $rule_info['mansong_goods_name'];
                    $data['goods_num'] = 1;
                    $data['goods_price'] = 0.00;
                    $data['goods_image'] = $rule_info['goods_image'];
                    $data['goods_image_url'] = goods_cthumb($rule_info['goods_image']);
                    $data['goods_storage'] = $rule_info['goods_storage'];
                    $premiums_list[$store_id][] = $data;
                }
            }
        }
        return array($premiums_list, $mansong_rule_list);
    }

    /**
     * 重新计算每个店铺最终商品总金额(最初计算金额减去各种优惠/加运费)
     * @param array $store_goods_total 店铺商品总金额
     * @param array $preferential_array 店铺优惠活动内容
     * @param string $preferential_type 优惠类型，目前只有一个 'mansong'
     * @return array 返回扣除优惠后的店铺商品总金额
     */
    public function reCalcGoodsTotal($store_goods_total, $preferential_array, $preferential_type,$goods_list = array()) {
        $deny = empty($store_goods_total) || !is_array($store_goods_total) || empty($preferential_array) || !is_array($preferential_array);
        if ($deny)
            return $store_goods_total;
        switch ($preferential_type) {
            case 'mansong':
                if (!config('ds_config.promotion_allow'))
                    return $store_goods_total;
                foreach ($preferential_array as $store_id => $rule_info) {
                    if (is_array($rule_info) && $rule_info['discount'] > 0) {
                        $store_goods_total[$store_id] -= $rule_info['discount'];
                    }
                }
                break;

            case 'voucher':
                if (!config('ds_config.voucher_allow'))
                    return $store_goods_total;
                
                foreach ($preferential_array as $store_id => $voucher_info) {
                    $store_goods_total[$store_id] -= $voucher_info['voucher_price'];
                }
                break;
            case 'mallvoucher':
                if (!config('ds_config.voucher_allow'))
                    return $store_goods_total;
                $mallvouchergoodstotal = 0;
                //获取购物车里面的平台代金券商品
                $mallvouchergoodslist = array();
                foreach ($goods_list as $k => $v) {
                    $gc_id = ','.$v['gc_id'].',';
                    if(strpos($preferential_array['mallvouchertemplate_gcidarr'],$gc_id) !== false){ 
                      $mallvouchergoodslist[] = $v;
                    }
                };
                //获取代金券商品总价
                foreach ($mallvouchergoodslist as $k => $v) {
                    $mallvouchergoodstotal += $v['goods_price']* $v['goods_num'];
                }
                //获取店铺商品代金券占比
                $storearray = array();
                foreach ($mallvouchergoodslist as $k => $v) {
                    if(isset($storearray[$v['store_id']])){
                        $proportion = sprintf("%.2f", ($v['goods_price']* $v['goods_num'])/$mallvouchergoodstotal);
                        $storearray[$v['store_id']]['proportion'] = $storearray[$v['store_id']]['proportion'] + $proportion;
                    }else{
                        $storearray[$v['store_id']]['proportion'] = sprintf("%.2f", ($v['goods_price']* $v['goods_num'])/$mallvouchergoodstotal);
                    }
                }
                foreach($storearray as $key => $val){
                    $mallvoucherprice = $val['proportion'] * $preferential_array['mallvoucheruser_price'];
                    $store_goods_total[$key] =  $store_goods_total[$key] - $mallvoucherprice;
                }
                break;     
            case 'freight':
                foreach ($preferential_array as $store_id => $freight_total) {
                    $store_goods_total[$store_id] += $freight_total;
                }
                break;
        }
        return $store_goods_total;
    }

    /**
     * 取得店铺可用的代金券
     * @param array $store_goods_total array(店铺ID=>商品总金额)
     * @return array
     */
    public function getStoreAvailableVoucherList($store_goods_total, $member_id) {
        if (!config('ds_config.voucher_allow'))
            return array();
        $voucher_list = array();
        $voucher_model = model('voucher');
        foreach ($store_goods_total as $store_id => $goods_total) {
            $condition = array();
            $condition[] = array('voucher_store_id', '=', $store_id);
            $condition[] = array('voucher_owner_id', '=', $member_id);
            $voucher_list[$store_id] = $voucher_model->getCurrentAvailableVoucher($condition, $goods_total);
        }
        return $voucher_list;
    }
    
    /**
     * 取得可用的平台代金券
     * @param array $store_goods_total array(店铺ID=>商品总金额)
     * @return array
     */
    public function getAvailableMallVoucherUserList($goods_list, $member_id)
    {
        //获取购物车里面的商品分类ID
        $goodsclassarray = array();
        foreach ($goods_list as $key => $value) {
            $goodsclassarray[]= ','.$value['gc_id'].',';
        }
        
        if (!config('ds_config.voucher_allow'))
            return array();
        
        $mallvoucher_list = array();
        //获取商品分类关联的平台代金券模板
        $mallvouchertemplate_list = array();
        $mallvouchertemplate_model = model('mallvouchertemplate');
        foreach($goodsclassarray as $key => $val){
            $gccondition = array();
            $gccondition[] = array('mallvouchertemplate_gcidarr','like',"%$val%");
            $mallvouchertemplatelist = $mallvouchertemplate_model->getMallvouchertemplateList($gccondition);
            $mallvouchertemplate_list = array_merge($mallvouchertemplate_list,$mallvouchertemplatelist);
        }
        if (!empty($mallvouchertemplate_list)) {
            //获取购物车商品相关平台代金券ID
            foreach ($mallvouchertemplate_list as $key => $value) {
                $mallvouchergoodstotal = 0;
                
                //获取代金券关联商品分类的商品
                $mallvouchergoodslist = array();
                foreach ($goods_list as $k => $v) {
                    $gc_id = ','.$v['gc_id'].',';
                    if(strpos($value['mallvouchertemplate_gcidarr'],$gc_id) !== false){ 
                      $mallvouchergoodslist[] = $v;
                    }
                };
                foreach ($mallvouchergoodslist as $k => $v) {
                    $mallvouchergoodstotal += $v['goods_price']* $v['goods_num'];
                }
                $mallvoucherusercondition   = array();
                $mallvoucherusercondition[] = array('mallvouchertemplate_id','=',$value['mallvouchertemplate_id']);
                $mallvoucherusercondition[] = array('mallvoucheruser_ownerid','=',$member_id);
                $mallvoucherarr_list =$mallvouchertemplate_model->getCurrentAvailableMallVoucherUser($mallvoucherusercondition, $mallvouchergoodstotal);
                if(!empty($mallvoucherarr_list)){
                    $mallvoucher_list= array_merge($mallvoucher_list,$mallvoucherarr_list);
                }
            }
        }
        $mallvoucher_list = ds_change_arraykey($mallvoucher_list,'mallvouchertemplate_id');
        return $mallvoucher_list;
    }
    
    /**
     * 验证传过来的代金券是否可用有效，如果无效，直接删除
     * @param array $input_mallvoucher_list 代金券列表
     * @param array $store_goods_total (店铺ID=>商品总金额)
     * @return array
     */
    public function reParseMallVoucherList($input_mallvoucher_list = array(), $goods_list = array(), $member_id)
    {
        if (empty($input_mallvoucher_list) || !is_array($input_mallvoucher_list))
            return array();
        $mall_voucher_list = $this->getAvailableMallVoucherUserList($goods_list, $member_id);
        foreach ($mall_voucher_list as $key => $value) {
            if($mall_voucher_list[$key]['mallvouchertemplate_id'] == $input_mallvoucher_list['mallvouchertemplate_id']){
                $input_mallvoucher_list['mallvoucheruser_price'] = $value['mallvoucheruser_price'];
                $input_mallvoucher_list['mallvoucheruser_id'] = $value['mallvoucheruser_id'];
                $input_mallvoucher_list['mallvoucheruser_ownerid'] =$value['mallvoucheruser_ownerid'];
                $input_mallvoucher_list['mallvouchertemplate_gcidarr'] =$value['mallvouchertemplate_gcidarr'];
                $input_mallvoucher_list['mallvoucheruser_code'] =$value['mallvoucheruser_code'];
            }
        }
        return $input_mallvoucher_list;
    }

    /**
     * 验证传过来的代金券是否可用有效，如果无效，直接删除
     * @param array $input_voucher_list 代金券列表
     * @param array $store_goods_total (店铺ID=>商品总金额)
     * @return array
     */
    public function reParseVoucherList($input_voucher_list = array(), $store_goods_total = array(), $member_id) {
        if (empty($input_voucher_list) || !is_array($input_voucher_list))
            return array();
        $store_voucher_list = $this->getStoreAvailableVoucherList($store_goods_total, $member_id);

        foreach ($input_voucher_list as $store_id => $voucher) {
            $tmp = $store_voucher_list[$store_id];
            if (is_array($tmp) && isset($tmp[$voucher['vouchertemplate_id']])) {
                $input_voucher_list[$store_id]['voucher_price'] = $tmp[$voucher['vouchertemplate_id']]['voucher_price'];
                $input_voucher_list[$store_id]['voucher_id'] = $tmp[$voucher['vouchertemplate_id']]['voucher_id'];
                $input_voucher_list[$store_id]['voucher_code'] = $tmp[$voucher['vouchertemplate_id']]['voucher_code'];
                $input_voucher_list[$store_id]['voucher_owner_id'] = $tmp[$voucher['vouchertemplate_id']]['voucher_owner_id'];
            } else {
                unset($input_voucher_list[$store_id]);
            }
        }
        return $input_voucher_list;
    }

    /**
     * 判断商品是不是秒杀中，如果购买数量若>=规定的下限，按折扣价格计算,否则按原价计算
     * @param array $goods_info
     * @param number $quantity 购买数量
     */
    public function getXianshiInfo(& $goods_info, $quantity) {
        if (empty($quantity))
            $quantity = 1;
        if (!config('ds_config.promotion_allow') || empty($goods_info['xianshi_info']))
            return;
        $goods_info['xianshi_info']['down_price'] = ds_price_format($goods_info['goods_price'] - $goods_info['xianshi_info']['xianshigoods_price']);
        if ($quantity >= $goods_info['xianshi_info']['xianshigoods_lower_limit']) {
            $goods_info['goods_price'] = $goods_info['xianshi_info']['xianshigoods_price'];
            $goods_info['promotions_id'] = $goods_info['xianshi_info']['xianshi_id'];
            $goods_info['ifxianshi'] = true;
        }
    }
    /**
     * 判断商品是不是预售
     * @param array $goods_info
     * @param number $quantity 购买数量
     */
    public function getPresellInfo(& $goods_info, $quantity) {
        if (empty($quantity))
            $quantity = 1;
        if (!config('ds_config.promotion_allow') || empty($goods_info['presell_info']))
            return;
        $goods_info['presell_info']['down_price'] = ds_price_format($goods_info['goods_price'] - $goods_info['presell_info']['presell_price']);
        $goods_info['goods_price'] = $goods_info['presell_info']['presell_price'];
        $goods_info['promotions_id'] = $goods_info['presell_info']['presell_id'];
        $goods_info['ifpresell'] = true;
    }
    /**
     * 判断商品是不是批发中，如果购买数量若>=规定的下限，按批发价格计算,否则按原价计算
     * @param array $goods_info
     * @param number $quantity 购买数量
     */
    public function getWholesaleInfo(& $goods_info, $quantity) {
        if (empty($quantity))
            $quantity = 1;
        if (!config('ds_config.promotion_allow') || empty($goods_info['wholesale_info']))
            return;
        $if_wholesale = false;
        foreach ($goods_info['wholesale_info']['wholesalegoods_price'] as $val) {
            if ($val['num'] <= $quantity) {
                $if_wholesale = true;
                $goods_info['wholesale_info']['down_price'] = ds_price_format($goods_info['goods_price'] - $val['price']);
                $goods_info['goods_price'] = $val['price'];
            }
        }
        if ($if_wholesale) {
            $goods_info['ifwholesale'] = true;
            $goods_info['promotions_id'] = $goods_info['wholesale_info']['wholesale_id'];
        }
    }

    /**
     * 判断商品是不是砍价中，如果购买数量若>=规定的下限,则报错
     * @param array $goods_info
     * @param number $quantity 购买数量
     */
    public function getBargainInfo(& $goods_info, $quantity, $extra) {
        $pbargain_model = model('pbargain');
        $pbargainorder_model = model('pbargainorder');
        //是否正在进行的砍价活动
        $pbargainorder_info = $pbargainorder_model->getOnePbargainorder(array('bargainorder_id' => $extra['bargainorder_id'], 'bargainorder_state' => 2), true);
        if (!$pbargainorder_info) {
            return;
        }
        $bargain_info = $pbargain_model->getOnlineBargainInfoByID($pbargainorder_info['bargain_id']);
        if (!$bargain_info) {
            return;
        }
        //之前是否已添加过订单
        if (model('order')->getOrdergoodsInfo(array('goods_type' => 8, 'promotions_id' => $pbargainorder_info['bargainorder_id']))) {
            return;
        }
        //购买数量是否超过限制数量
        if ($quantity > $bargain_info['bargain_limit']) {
            return;
        }
        $goods_info['goods_price'] = $pbargainorder_info['bargainorder_current_price'];
        $goods_info['promotions_id'] = $pbargainorder_info['bargainorder_id'];
        $goods_info['ifbargain'] = true;
    }

    /**
     * 判断商品是不是拼团中，如果购买数量若>=规定的下限,则报错
     * @param array $goods_info
     * @param number $quantity 购买数量
     */
    public function getPintuanInfo(& $goods_info, $quantity, $extra, $member_id) {
        if (empty($quantity))
            $quantity = 1;

        //超过了购买限制按照原价
        if ($goods_info['pintuan_info'] && ($quantity > $goods_info['pintuan_info']['pintuan_limit_quantity'])) {
            return;
        }
        $pintuangroup_id = intval($extra['pintuangroup_id']);
        if ($pintuangroup_id > 0) {
            //判断非开团拼团订单,判断参团人数是否满足
            $pintuangroup = model('ppintuangroup')->getOnePpintuangroup(array('pintuangroup_id' => $pintuangroup_id));
            if (empty($pintuangroup) || $pintuangroup['pintuangroup_state'] != 1) {
                //不存在,或者拼团活动状态 不为1
                return;
            }
            //当开团拼团订单表中参团人数大于成团人团则按照正常价格购买
            if ($pintuangroup['pintuangroup_joined'] >= $pintuangroup['pintuangroup_limit_number']) {
                return;
            }
        }
        //不可以重复参加
        $order_id_list = Db::name('ppintuanorder')->where(array(array('pintuan_id', '=', $goods_info['pintuan_info']['pintuan_id']), array('pintuanorder_state', '<>', 0)))->column('order_id');
        if ($order_id_list) {
            if (!$goods_info['is_virtual']) {
                if (Db::name('order')->where('buyer_id', $member_id)->where('order_id', 'in', $order_id_list)->value('order_id')) {
                    return;
                }
            } else {
                if (Db::name('vrorder')->where('buyer_id', $member_id)->where('order_id', 'in', $order_id_list)->value('order_id')) {
                    return;
                }
            }
        }
        $goods_info['pintuan_info']['down_price'] = ds_price_format($goods_info['goods_price'] * (1 - $goods_info['pintuan_info']['pintuan_zhe']));
        $goods_info['goods_price'] = round(($goods_info['pintuan_info']['pintuan_zhe'] * $goods_info['goods_price']) / 10, 2);
        $goods_info['promotions_id'] = $goods_info['pintuan_info']['pintuan_id'];
        $goods_info['ifpintuan'] = true;
    }

    public function updatePintuan($post_data, $goods_info, $order, $order_type, $member_id) {
        $res = array();
        //拼团订单的特殊性,还需要额外的进行处理.
        $pintuangroup_id = intval($post_data['pintuangroup_id']);
        $pintuan_id = intval($post_data['pintuan_id']);
        $res['pintuangroup_id'] = $pintuangroup_id;
        $pintuanorder_isfirst = 0; #是否为首团订单
        if ($pintuangroup_id == 0) {
            //首团订单新增
            $data = array(
                'pintuan_id' => $pintuan_id,
                'pintuangroup_goods_id' => $goods_info['goods_id'],
                'pintuangroup_is_virtual' => $goods_info['is_virtual'],
                'pintuangroup_joined' => 0,
                'pintuangroup_limit_number' => $goods_info['pintuan_info']['pintuan_limit_number'],
                'pintuangroup_limit_hour' => $goods_info['pintuan_info']['pintuan_limit_hour'],
                'pintuangroup_headid' => $member_id,
                'pintuangroup_starttime' => TIMESTAMP,
            );
            $pintuangroup_id = model('ppintuangroup')->addPpintuangroup($data);
            $res['pintuangroup_id'] = $pintuangroup_id;
            $pintuanorder_isfirst = 1;
        }
        //新增订单
        $data = array(
            'pintuan_id' => $pintuan_id,
            'pintuangroup_id' => $pintuangroup_id,
            'order_id' => $order['order_id'],
            'order_sn' => $order['order_sn'],
            'pintuanorder_isfirst' => $pintuanorder_isfirst,
            'pintuanorder_type' => $order_type,
        );
        model('ppintuanorder')->addPpintuanorder($data);

        //下单后清除缓存
        model('ppintuan')->_dGoodsPintuanCache($goods_info['pintuan_info']['pintuan_goods_commonid']);
        return $res;
    }

    /**
     * 判断商品是不是享受会员等级折扣，
     * @param array $goods_info
     * @param number $level 会员等级
     */
    public function getMgdiscountInfo(& $goods_info) {
        $member_model=model('member');
        $member_info=$member_model->getMemberInfoByID(session('member_id'));
        if(!$member_info){
          return;
        }
        $member_gradeinfo = $member_model->getOneMemberGrade(intval($member_info['member_exppoints']));
        if(!$member_gradeinfo){
          return;
        }
        $level = intval($member_gradeinfo['level']);
        if (!config('ds_config.mgdiscount_allow') || empty($goods_info['mgdiscount_info']) || $level <= 0) {
            return;
        }
        $mgdiscount = $goods_info['mgdiscount_info'][$level];
        if (empty($mgdiscount)) {
            return;
        } else {
            if ($mgdiscount['level_discount'] < 10) {
                $goods_info['goods_price'] = round(($mgdiscount['level_discount'] * $goods_info['goods_price']) / 10, 2);
                $goods_info['mgdiscount_desc'] = '会员享受' . $mgdiscount['level_discount'] . '折';
                $goods_info['ifmgdiscount'] = true;
            }
        }
    }

    /**
     * 输出有货到付款时，在线支付和货到付款及每种支付下商品数量和详细列表
     * @param $buy_list 商品列表
     * @return 返回 以支付方式为下标分组的商品列表
     */
    public function getOfflineGoodsPay($buy_list) {
        //以支付方式为下标，存放购买商品
        $buy_goods_list = array();
        $offline_pay = model('payment')->getPaymentOpenInfo(array(array('payment_code', '=', 'offline')));
        if ($offline_pay) {
            //下单里包括平台自营商品并且平台已开启货到付款，则显示货到付款项及对应商品数量,取出支持货到付款的店铺ID组成的数组，目前就一个，DEFAULT_PLATFORM_STORE_ID
            foreach ($buy_list as $value) {
                $buy_goods_list['online'][] = $value;
            }
        }
        return $buy_goods_list;
    }

    /**
     * 计算每个店铺(所有店铺级优惠活动)总共优惠多少金额
     * @param array $store_goods_total 最初店铺商品总金额
     * @param array $store_final_goods_total 去除各种店铺级促销后，最终店铺商品总金额(不含运费)
     * @return array
     */
    public function getStorePromotionTotal($store_goods_total, $store_final_goods_total) {
        if (!is_array($store_goods_total) || !is_array($store_final_goods_total))
            return array();
        $store_promotion_total = array();
        foreach ($store_goods_total as $store_id => $goods_total) {
            $store_promotion_total[$store_id] = abs($goods_total - $store_final_goods_total[$store_id]);
        }
        return $store_promotion_total;
    }

    /**
     * 返回需要计算运费的店铺ID组成的数组 和 免运费店铺ID及免运费下限金额描述
     * @param array $store_goods_total 每个店铺的商品金额小计，以店铺ID为下标
     * @return array
     */
    public function getStoreFreightDescList($store_goods_total) {
        if (empty($store_goods_total) || !is_array($store_goods_total))
            return array(array(), array());

        //定义返回数组
        $need_calc_sid_array = array();
        $cancel_calc_sid_array = array();

        //如果商品金额未达到免运费设置下线，则需要计算运费
        $condition = array();
        $condition[] = array('store_id', 'in', array_keys($store_goods_total));
        $store_list = model('store')->getStoreOnlineList($condition, null, '', 'store_id,store_free_price');
        foreach ($store_list as $store_info) {
            $limit_price = floatval($store_info['store_free_price']);
            if ($limit_price == 0 || $limit_price > $store_goods_total[$store_info['store_id']]) {
                //需要计算运费
                $need_calc_sid_array[] = $store_info['store_id'];
            } else {
                //返回免运费金额下限
                $cancel_calc_sid_array[$store_info['store_id']]['free_price'] = $limit_price;
                $cancel_calc_sid_array[$store_info['store_id']]['desc'] = sprintf('满%s免运费', $limit_price);
            }
        }
        return array($need_calc_sid_array, $cancel_calc_sid_array);
    }

    /**
     * 取得店铺运费(使用运费模板的商品运费不会计算，但会返回模板信息)
     * 先将免运费的店铺运费置0，然后算出店铺里没使用运费模板的商品运费之和 ，存到iscalced下标中
     * 然后再计算使用运费模板的信息(array(店铺ID=>array(运费模板ID=>购买数量))，放到nocalced下标里
     * @param array $buy_list 购买商品列表
     * @param array $free_freight_sid_list 免运费的店铺ID数组
     */
    public function getStoreFreightList($buy_list = array(), $free_freight_sid_list) {
        //定义返回数组
        $return = array();
        //先将免运费的店铺运费置0(格式:店铺ID=>0)
        $freight_list = array();
        if (!empty($free_freight_sid_list) && is_array($free_freight_sid_list)) {
            foreach ($free_freight_sid_list as $store_id) {
                $freight_list[$store_id] = 0;
            }
        }

        //然后算出店铺里没使用运费模板(优惠套装商品除外)的商品运费之和(格式:店铺ID=>运费)
        //定义数组，存放店铺优惠套装商品运费总额 store_id=>运费
        $store_bl_goods_freight = array();
        foreach ($buy_list as $key => $goods_info) {
            //免运费店铺的商品不需要计算
            if (in_array($goods_info['store_id'], $free_freight_sid_list)) {
                unset($buy_list[$key]);
                continue;
            }
            //优惠套装商品运费另算
            if (intval($goods_info['bl_id'])) {
                unset($buy_list[$key]);
                $store_bl_goods_freight[$goods_info['store_id']] = $goods_info['bl_id'];
                continue;
            }
            if (!intval($goods_info['transport_id']) && !in_array($goods_info['store_id'], $free_freight_sid_list)) {
                if (!isset($freight_list[$goods_info['store_id']])) {
                    $freight_list[$goods_info['store_id']] = $goods_info['goods_freight'];
                } else {
                    $freight_list[$goods_info['store_id']] += $goods_info['goods_freight'];
                }
                unset($buy_list[$key]);
            }
        }
        //计算优惠套装商品运费
        if (!empty($store_bl_goods_freight)) {
            $pbundling_model = model('pbundling');
            foreach (array_unique($store_bl_goods_freight) as $store_id => $bl_id) {
                $bl_info = $pbundling_model->getBundlingInfo(array('bl_id' => $bl_id));
                if (!empty($bl_info)) {
                    if (!isset($freight_list[$store_id])) {
                        $freight_list[$store_id] = $bl_info['bl_freight'];
                    } else {
                        $freight_list[$store_id] += $bl_info['bl_freight'];
                    }
                }
            }
        }

        $return['iscalced'] = $freight_list;

        //最后再计算使用运费模板的信息(店铺ID，运费模板ID，购买数量),使用使用相同运费模板的商品数量累加
        $freight_list = array();
        foreach ($buy_list as $goods_info) {
            if (!isset($freight_list[$goods_info['store_id']])) {
                $freight_list[$goods_info['store_id']] = array();
            }
            if (!isset($freight_list[$goods_info['store_id']][$goods_info['transport_id']])) {
                $freight_list[$goods_info['store_id']][$goods_info['transport_id']] = array('count' => 0, 'weight' => 0);
            }
            $freight_list[$goods_info['store_id']][$goods_info['transport_id']]['count'] += $goods_info['goods_num'];
            $freight_list[$goods_info['store_id']][$goods_info['transport_id']]['weight'] += $goods_info['goods_num'] * $goods_info['goods_weight'];
        }
        $return['nocalced'] = $freight_list;

        return $return;
    }

    /**
     * 根据地区选择计算出所有店铺最终运费
     * @param array $freight_list 运费信息(店铺ID，运费，运费模板ID，购买数量)
     * @param int $city_id 市级ID
     * @return array 返回店铺ID=>运费
     */
    public function calcStoreFreight($freight_list, $city_id) {
        if (!is_array($freight_list) || empty($freight_list) || empty($city_id))
            return;
        //免费和固定运费计算结果
        $return_list = $freight_list['iscalced'];

        //使用运费模板的信息(array(店铺ID=>array(运费模板ID=>购买数量))
        $nocalced_list = $freight_list['nocalced'];

        //然后计算使用运费运费模板的在该$city_id时的运费值
        if (!empty($nocalced_list) && is_array($nocalced_list)) {
            //如果有商品使用的运费模板，先计算这些商品的运费总金额
            $transport_model = model('transport');
            foreach ($nocalced_list as $store_id => $value) {
                if (is_array($value)) {
                    foreach ($value as $transport_id => $v) {
                        $freight_total = $transport_model->calcTransport($transport_id, $city_id, $v['count'], $v['weight']);
                        if ($freight_total === false) {
                            return;
                        } else {
                            if (empty($return_list[$store_id])) {
                                $return_list[$store_id] = $freight_total;
                            } else {
                                $return_list[$store_id] += $freight_total;
                            }
                        }
                    }
                }
            }
        }
        return $return_list;
    }

    /**
     * 追加赠品到下单列表,并更新购买数量
     * @param array $store_cart_list 购买列表
     * @param array $store_premiums_list 赠品列表
     * @param array $store_mansong_rule_list 满即送规则
     */
    public function appendPremiumsToCartList($store_cart_list, $store_premiums_list = array(), $store_mansong_rule_list = array(), $member_id) {
        if (empty($store_cart_list))
            return array();

        //处理商品级赠品
        foreach ($store_cart_list as $store_id => $cart_list) {
            foreach ($cart_list as $cart_info) {
                if (empty($cart_info['gift_list']))
                    continue;
                if (!is_array($store_premiums_list))
                    $store_premiums_list = array();
                if (!array_key_exists($store_id, $store_premiums_list))
                    $store_premiums_list[$store_id] = array();
                $zenpin_info = array();
                foreach ($cart_info['gift_list'] as $gift_info) {
                    $zenpin_info['goods_id'] = $gift_info['gift_goodsid'];
                    $zenpin_info['goods_name'] = $gift_info['gift_goodsname'];
                    $zenpin_info['goods_image'] = $gift_info['gift_goodsimage'];
                    $zenpin_info['goods_storage'] = $gift_info['goods_storage'];
                    $zenpin_info['goods_num'] = $cart_info['goods_num'] * $gift_info['gift_amount'];
                    $store_premiums_list[$store_id][] = $zenpin_info;
                }
            }
        }

        //取得每种商品的库存[含赠品]
        $goods_storage_quantity = $this->_getEachGoodsStorageQuantity($store_cart_list, $store_premiums_list);

        //取得每种商品的购买量[不含赠品]
        $goods_buy_quantity = $this->_getEachGoodsBuyQuantity($store_cart_list);
        foreach ($goods_buy_quantity as $goods_id => $quantity) {
            $goods_storage_quantity[$goods_id] -= $quantity;
            if ($goods_storage_quantity[$goods_id] < 0) {
                //商品库存不足，请重购买
                return false;
            }
        }
        //将赠品追加到购买列表

        if (is_array($store_premiums_list)) {
            foreach ($store_premiums_list as $store_id => $goods_list) {
                $zp_list = array();
                $gift_desc = '';
                foreach ($goods_list as $goods_info) {
                    //如果没有库存了，则不再送赠品
                    if ($goods_storage_quantity[$goods_info['goods_id']] == 0) {
                        $gift_desc = '，赠品库存不足，未能全部送出 ';
                        continue;
                    }


                    $new_data = array();
                    $new_data['chain_id'] = 0;
                    $new_data['buyer_id'] = $member_id;
                    $new_data['store_id'] = $store_id;
                    $new_data['store_name'] = $store_cart_list[$store_id][0]['store_name'];
                    $new_data['goods_id'] = $goods_info['goods_id'];
                    $new_data['goods_name'] = $goods_info['goods_name'];
                    $new_data['goods_price'] = 0;
                    $new_data['goods_image'] = $goods_info['goods_image'];
                    $new_data['bl_id'] = 0;
                    $new_data['state'] = true;
                    $new_data['storage_state'] = true;
                    $new_data['gc_id'] = 0;
                    $new_data['goods_weight'] = 0;
                    $new_data['gc_id_1'] = 0;
                    $new_data['gc_id_2'] = 0;
                    $new_data['gc_id_3'] = 0;
                    $new_data['transport_id'] = 0;
                    $new_data['goods_freight'] = 0;
                    $new_data['goods_vat'] = 0;
                    $new_data['goods_total'] = 0;
                    $new_data['ifzengpin'] = true;

                    //计算赠送数量，有就赠，赠完为止
                    if ($goods_storage_quantity[$goods_info['goods_id']] - $goods_info['goods_num'] >= 0) {
                        if (!isset($goods_buy_quantity[$goods_info['goods_id']])) {
                            $goods_buy_quantity[$goods_info['goods_id']] = $goods_info['goods_num'];
                        } else {
                            $goods_buy_quantity[$goods_info['goods_id']] += $goods_info['goods_num'];
                        }
                        $goods_storage_quantity[$goods_info['goods_id']] -= $goods_info['goods_num'];
                        $new_data['goods_num'] = $goods_info['goods_num'];
                    } else {
                        $new_data['goods_num'] = $goods_storage_quantity[$goods_info['goods_id']];
                        $goods_buy_quantity[$goods_info['goods_id']] += $goods_storage_quantity[$goods_info['goods_id']];
                        $goods_storage_quantity[$goods_info['goods_id']] = 0;
                    }
                    if (array_key_exists($goods_info['goods_id'], $zp_list)) {
                        $zp_list[$goods_info['goods_id']]['goods_num'] += $new_data['goods_num'];
                    } else {
                        $zp_list[$goods_info['goods_id']] = $new_data;
                    }
                }
                sort($zp_list);
                $store_cart_list[$store_id] = array_merge($store_cart_list[$store_id], $zp_list);

                @$store_mansong_rule_list[$store_id]['desc'] .= $gift_desc;
                @$store_mansong_rule_list[$store_id]['desc'] = trim($store_mansong_rule_list[$store_id]['desc'], '，');
            }
        }
        return array($store_cart_list, $goods_buy_quantity, $store_mansong_rule_list);
    }

    /**
     * 充值卡支付,依次循环每个订单
     * 如果充值卡足够就单独支付了该订单，如果不足就暂时冻结，等API支付成功了再彻底扣除
     */
    public function rcbPay($order_list, $input, $buyer_info) {
        $member_id = $buyer_info['member_id'];
        $member_name = $buyer_info['member_name'];

        $available_rcb_amount = floatval($buyer_info['available_rc_balance']);
        if ($available_rcb_amount <= 0)
            return;

        $order_model = model('order');
        $logic_order = model('order', 'logic');
        $predeposit_model = model('predeposit');
        $canPay=false;
        foreach ($order_list as $key => $order_info) {

            //货到付款的订单跳过
            if ($order_info['payment_code'] == 'offline')
                continue;


            if (!isset($order_info['rcb_amount'])) {
                $order_list[$key]['rcb_amount'] = $order_info['rcb_amount'] = 0;
            }

            if (!isset($order_info['pd_amount'])) {
                $order_list[$key]['pd_amount'] = $order_info['pd_amount'] = 0;
            }
            $order_amount = round(($order_info['order_state'] == ORDER_STATE_DEPOSIT?$order_info['presell_deposit_amount']:($order_info['order_amount'] - $order_info['presell_deposit_amount'] + $order_info['presell_rcb_amount'] + $order_info['presell_pd_amount'])) - $order_info['rcb_amount'] - $order_info['pd_amount'], 2);
            $data_pd = array();
            $data_pd['member_id'] = $member_id;
            $data_pd['member_name'] = $member_name;

            $data_pd['order_sn'] = $order_info['order_sn'];


            //暂冻结充值卡,后面还需要 API彻底完成支付
            if ($available_rcb_amount > 0 && $order_amount>0) {
                if ($available_rcb_amount >= $order_amount) {
                    $available_rcb_amount -= $order_amount;
                    $data_pd['amount'] = $order_amount;
                    $canPay=true;
                } else {
                    $data_pd['amount'] = $available_rcb_amount;
                    $available_rcb_amount = 0;
                    $canPay=false;
                }

                $predeposit_model->changeRcb('order_freeze', $data_pd);
                //支付金额保存到订单
                $data_order = array();
                $order_list[$key]['rcb_amount'] = $data_order['rcb_amount'] = round($order_info['rcb_amount'] + $data_pd['amount'], 2);
                $result = $order_model->editOrder($data_order, array('order_id' => $order_info['order_id']));

                if (!$result) {
                    throw new \think\Exception('订单更新失败', 10006);
                }
            }
        }
                if ($canPay) {//收到货款
                    $logic_order->changeOrderReceivePay($order_list, 'buyer', $member_name,array('payment_code'=>'predeposit'));
                }
        return $order_list;
    }

    /**
     * 预存款支付,依次循环每个订单
     * 如果预存款足够就单独支付了该订单，如果不足就暂时冻结，等API支付成功了再彻底扣除
     */
    public function pdPay($order_list, $input, $buyer_info) {
        $member_id = $buyer_info['member_id'];
        $member_name = $buyer_info['member_name'];

//                 $payment_model = model('payment');
//                 $pd_payment_info = $payment_model->getPaymentOpenInfo(array('payment_code'=>'predeposit'));
//                 if (empty($pd_payment_info)) return;

        $available_pd_amount = floatval($buyer_info['available_predeposit']);
        if ($available_pd_amount <= 0)
            return;

        $order_model = model('order');
        $logic_order = model('order', 'logic');
        $predeposit_model = model('predeposit');
        $canPay=false;
        foreach ($order_list as $key => $order_info) {

            //货到付款的订单、已经充值卡支付的订单跳过
            if ($order_info['payment_code'] == 'offline')
                continue;
            if ($order_info['order_state'] == ORDER_STATE_PAY)
                continue;

            if (!isset($order_info['rcb_amount'])) {
                $order_list[$key]['rcb_amount'] = $order_info['rcb_amount'] = 0;
            }

            if (!isset($order_info['pd_amount'])) {
                $order_list[$key]['pd_amount'] = $order_info['pd_amount'] = 0;
            }
            $order_amount = round(($order_info['order_state'] == ORDER_STATE_DEPOSIT?$order_info['presell_deposit_amount']:($order_info['order_amount'] - $order_info['presell_deposit_amount'] + $order_info['presell_rcb_amount'] + $order_info['presell_pd_amount'])) - $order_info['rcb_amount'] - $order_info['pd_amount'], 2);

            $data_pd = array();
            $data_pd['member_id'] = $member_id;
            $data_pd['member_name'] = $member_name;
            $data_pd['order_sn'] = $order_info['order_sn'];

            //暂冻结预存款,后面还需要 API彻底完成支付
            
            if ($available_pd_amount > 0 && $order_amount>0) {
                if ($available_pd_amount >= $order_amount) {
                    $data_pd['amount'] = $order_amount;
                    $available_pd_amount -= $order_amount;
                    $canPay=true;
                } else {
                    $data_pd['amount'] = $available_pd_amount;
                    $available_pd_amount = 0;
                    $canPay=false;
                }
                $predeposit_model->changePd('order_freeze', $data_pd);
                //预存款支付金额保存到订单
                $data_order = array();
                $order_list[$key]['pd_amount'] = $data_order['pd_amount'] = round($order_info['pd_amount'] + $data_pd['amount'], 2);
                $result = $order_model->editOrder($data_order, array('order_id' => $order_info['order_id']));

                if (!$result) {
                    throw new \think\Exception('订单更新失败', 10006);
                }

            }
        }
                if ($canPay) {//收到货款
                    $logic_order->changeOrderReceivePay($order_list, 'buyer', $member_name,array('payment_code'=>'predeposit'));
                }
        return $order_list;
    }

    /**
     * 订单编号生成规则，n(n>=1)个订单表对应一个支付表，
     * 生成订单编号(年取1位 + $pay_id取13位 + 第N个子订单取2位)
     * 1000个会员同一微秒提订单，重复机率为1/100
     * @param $pay_id 支付表自增ID
     * @return string
     */
    public function makeOrderSn($pay_id) {
        //记录生成子订单的个数，如果生成多个子订单，该值会累加
        static $num;
        if (empty($num)) {
            $num = 1;
        } else {
            $num++;
        }
        return (date('y', TIMESTAMP) % 9 + 1) . sprintf('%013d', $pay_id) . sprintf('%02d', $num);
    }

    /**
     * 更新库存与销量
     *
     * @param array $buy_items 商品ID => 购买数量
     */
    public function editGoodsNum($buy_items) {
        foreach ($buy_items as $goods_id => $buy_num) {
            $data = array(
                'goods_storage' => Db::raw('goods_storage-' . $buy_num),
                'goods_salenum' => Db::raw('goods_salenum+' . $buy_num)
            );
            $result = model('goods')->editGoods($data, array('goods_id' => $goods_id));
            if (!$result)
                throw new \think\Exception(lang('cart_step2_submit_fail'), 10006);
        }
    }

    /**
     * 取得店铺级活动 - 每个店铺可用的满即送活动规则列表
     * @param unknown $store_id_array 店铺ID数组
     */
    public function getMansongruleList($store_id_array) {
        if (!config('ds_config.promotion_allow') || empty($store_id_array) || !is_array($store_id_array))
            return array();
        $pmansong_model = model('pmansong');
        $mansong_rule_list = array();
        foreach ($store_id_array as $store_id) {
            $store_mansong_rule = $pmansong_model->getMansongInfoByStoreID($store_id);
            if (!empty($store_mansong_rule['rules']) && is_array($store_mansong_rule['rules'])) {
                foreach ($store_mansong_rule['rules'] as $rule_info) {
                    //如果减金额 或 有赠品(在售且有库存)
                    if (!empty($rule_info['mansongrule_discount']) || (!empty($rule_info['mansong_goods_name']) && !empty($rule_info['goods_storage']))) {
                        $mansong_rule_list[$store_id][] = $this->_parseMansongruleDesc($rule_info);
                    }
                }
            }
        }
        return $mansong_rule_list;
    }

    /**
     * 取得哪些店铺有满免运费活动
     * @param array $store_id_array 店铺ID数组
     * @return array
     */
    public function getFreeFreightActiveList($store_id_array) {
        if (empty($store_id_array) || !is_array($store_id_array))
            return array();

        //定义返回数组
        $store_free_freight_active = array();

        //如果商品金额未达到免运费设置下线，则需要计算运费
        $condition = array();
        $condition[] = array('store_id', 'in', $store_id_array);
        $store_list = model('store')->getStoreOnlineList($condition, null, '', 'store_id,store_free_price');
        foreach ($store_list as $store_info) {
            $limit_price = floatval($store_info['store_free_price']);
            if ($limit_price > 0) {
                $store_free_freight_active[$store_info['store_id']] = sprintf('满%s免运费', $limit_price);
            }
        }
        return $store_free_freight_active;
    }

    /**
     * 取得收货人地址信息
     * @param array $address_info
     * @return array
     */
    public function getReciverAddr($address_info = array()) {
        if (intval($address_info['chain_id'])) {
            $reciver_info['phone'] = trim($address_info['address_mob_phone'] . ($address_info['address_tel_phone'] ? ',' . $address_info['address_tel_phone'] : null), ',');
            $reciver_info['tel_phone'] = $address_info['address_tel_phone'];
            $reciver_info['mob_phone'] = $address_info['address_mob_phone'];
            $reciver_info['address'] = $address_info['chain_area_info'] . ' ' . $address_info['chain_address'];
            $reciver_info['area'] = $address_info['chain_area_info'];
            $reciver_info['street'] = $address_info['chain_address'];
            $reciver_info['chain'] = 1;
            $reciver_info = serialize($reciver_info);
            $reciver_name = $address_info['chain_addressname'];
        } else {
            $reciver_info['phone'] = trim($address_info['address_mob_phone'] . ($address_info['address_tel_phone'] ? ',' . $address_info['address_tel_phone'] : null), ',');
            $reciver_info['mob_phone'] = $address_info['address_mob_phone'];
            $reciver_info['tel_phone'] = $address_info['address_tel_phone'];
            $reciver_info['address'] = $address_info['area_info'] . ' ' . $address_info['address_detail'];
            $reciver_info['area'] = $address_info['area_info'];
            $reciver_info['street'] = $address_info['address_detail'];
            $reciver_info = serialize($reciver_info);
            $reciver_name = $address_info['address_realname'];
        }
        return array($reciver_info, $reciver_name);
    }

    /**
     * 整理发票信息
     * @param array $invoice_info 发票信息数组
     * @return string
     */
    public function createInvoiceData($invoice_info) {
        //发票信息
        $inv = array();
        if (isset($invoice_info['invoice_state']) && $invoice_info['invoice_state'] == 1) {
            $inv['类型'] = '普通发票 ';
            $inv['抬头'] = isset($invoice_info['invoice_title']) ? $invoice_info['invoice_title'] : '个人';
            $inv['内容'] = $invoice_info['invoice_content'];
            $inv['纳税人识别号'] = $invoice_info['invoice_code'];
        } elseif (!empty($invoice_info)) {
            $inv['单位名称'] = $invoice_info['invoice_company'];
            $inv['纳税人识别号'] = $invoice_info['invoice_company_code'];
            $inv['注册地址'] = $invoice_info['invoice_reg_addr'];
            $inv['注册电话'] = $invoice_info['invoice_reg_phone'];
            $inv['开户银行'] = $invoice_info['invoice_reg_bname'];
            $inv['银行账户'] = $invoice_info['invoice_reg_baccount'];
//            $inv['收票人姓名'] = $invoice_info['invoice_rec_name'];
//            $inv['收票人手机号'] = $invoice_info['invoice_rec_mobphone'];
//            $inv['收票人省份'] = $invoice_info['invoice_rec_province'];
//            $inv['送票地址'] = $invoice_info['invoice_goto_addr'];
        }
        return !empty($inv) ? serialize($inv) : serialize(array());
    }

    /**
     * 计算本次下单中每个店铺订单是货到付款还是线上支付,店铺ID=>付款方式[online在线支付offline货到付款]
     * @param array $store_id_array 店铺ID数组
     * @param boolean $if_offpay 是否支持货到付款 true/false
     * @param string $pay_name 付款方式 online/offline
     * @return array
     */
    public function getStorePayTypeList($store_id_array, $if_offpay, $pay_name) {
        $store_pay_type_list = array();
        if ($pay_name == 'online') {
            foreach ($store_id_array as $store_id) {
                $store_pay_type_list[$store_id] = 'online';
            }
        } else {
            $offline_pay = model('payment')->getPaymentOpenInfo(array(array('payment_code', '=', 'offline')));
            if ($offline_pay) {
                //下单里包括平台自营商品并且平台已开启货到付款
                foreach ($store_id_array as $store_id) {
                    $store_pay_type_list[$store_id] = 'offline';
                }
            }
        }
        return $store_pay_type_list;
    }

    /**
     * 直接购买时返回最新的在售商品信息（需要在售）
     *
     * @param int $goods_id 所购商品ID
     * @param int $quantity 购买数量
     * @return array
     */
    private function _getGoodsOnlineInfo($goods_id, $quantity) {
        //取目前在售商品
        $goods_model=model('goods');
        $goods_model->lock=$this->lock;
        $goods_info = $goods_model->getGoodsOnlineInfoAndPromotionById($goods_id);
        if (empty($goods_info)) {
            return null;
        }
        $new_array = array();
        $new_array['goods_num'] = $goods_info['is_goodsfcode'] ? 1 : $quantity;
        $new_array['goods_id'] = $goods_id;
        $new_array['goods_commonid'] = $goods_info['goods_commonid'];
        $new_array['is_virtual'] = $goods_info['is_virtual'];
        $new_array['gc_id'] = $goods_info['gc_id'];
        $new_array['goods_weight'] = $goods_info['goods_weight'];
        $new_array['gc_id_1'] = $goods_info['gc_id_1'];
        $new_array['gc_id_2'] = $goods_info['gc_id_2'];
        $new_array['gc_id_3'] = $goods_info['gc_id_3'];
        $new_array['store_id'] = $goods_info['store_id'];
        $new_array['goods_name'] = $goods_info['goods_name'];
        $new_array['goods_price'] = $goods_info['goods_price'];
        $new_array['goods_original_price'] = $goods_info['goods_original_price'];
        $new_array['store_name'] = $goods_info['store_name'];
        $new_array['goods_image'] = $goods_info['goods_image'];
        $new_array['transport_id'] = $goods_info['transport_id'];
        $new_array['goods_freight'] = $goods_info['goods_freight'];
        $new_array['goods_vat'] = $goods_info['goods_vat'];
        $new_array['goods_storage'] = $goods_info['goods_storage'];
        $new_array['goods_storage_alarm'] = $goods_info['goods_storage_alarm'];
        $new_array['is_goodsfcode'] = $goods_info['is_goodsfcode'];
        $new_array['is_have_gift'] = $goods_info['is_have_gift'];
        $new_array['state'] = true;
        $new_array['storage_state'] = intval($goods_info['goods_storage']) < intval($quantity) ? false : true;
        $new_array['groupbuy_info'] = $goods_info['groupbuy_info'];
        $new_array['xianshi_info'] = $goods_info['xianshi_info'];
        $new_array['presell_info'] = $goods_info['presell_info'];
        $new_array['wholesale_info'] = $goods_info['wholesale_info'];
        $new_array['pintuan_info'] = $goods_info['pintuan_info'];
        $new_array['bargain_info'] = $goods_info['bargain_info'];
        $new_array['mgdiscount_info'] = $goods_info['mgdiscount_info'];

        //填充必要下标，方便后面统一使用购物车方法与模板
        //cart_id=goods_id,优惠套装目前只能进购物车,不能立即购买
        $new_array['cart_id'] = $goods_id;
        $new_array['bl_id'] = 0;


        return $new_array;
    }

    /**
     * 直接购买时，判断商品是不是正在抢购中，如果是，按抢购价格计算，购买数量若超过抢购规定的上限，则按抢购上限计算
     * @param array $goods_info
     */
    public function getGroupbuyInfo(& $goods_info = array(), $quantity) {
        if (!config('ds_config.groupbuy_allow') || empty($goods_info['groupbuy_info']))
            return;
        $groupbuy_info = $goods_info['groupbuy_info'];
        $temp = array();
        $temp['goods_num'] = $quantity;
        $temp['goods_price'] = $groupbuy_info['groupbuy_price'];
        if ($groupbuy_info['groupbuy_upper_limit'] && $quantity > $groupbuy_info['groupbuy_upper_limit']) {
            $temp['goods_num'] = $groupbuy_info['groupbuy_upper_limit'];
        }
        $temp['upper_limit'] = $groupbuy_info['groupbuy_upper_limit'];
        $temp['promotions_id'] = $temp['groupbuy_id'] = $groupbuy_info['groupbuy_id'];
        $temp['ifgroupbuy'] = true;
        if ($groupbuy_info['groupbuy_upper_limit'] > 0) {//如果限购
            $ordergoods = Db::name('ordergoods')->where(array('buyer_id' => session('member_id'), 'goods_type' => 2, 'promotions_id' => $groupbuy_info['groupbuy_id']))->sum('goods_num');
            if (!empty($ordergoods) && intval($ordergoods) > 0) {
                $tnum = intval($groupbuy_info['groupbuy_upper_limit']) - intval($ordergoods); //-intval($goods_info['goods_num']);
                if ($tnum <= 0)
//                $goods_info = null;
                    return;
                else {
                    if ($temp['goods_num'] > $tnum) {
                        $temp['goods_num'] = $tnum;
                    }
                }
            }
        }
        $goods_info = array_merge($goods_info, $temp);
        //end
    }

    /**
     * 取得某商品赠品列表信息
     * @param array $goods_info
     */
    private function _getGoodsgiftList(& $goods_info) {
        if (!isset($goods_info['is_have_gift']))
            return;
        $gift_list = model('goodsgift')->getGoodsgiftListByGoodsId($goods_info['goods_id']);
        //取得赠品当前信息，如果未在售踢除，如果在售取出库存
        if (empty($gift_list))
            return array();
        $goods_model = model('goods');
        foreach ($gift_list as $k => $v) {
            $goods_online_info = $goods_model->getGoodsOnlineInfoByID($v['gift_goodsid']);
            if (empty($goods_online_info)) {
                unset($gift_list[$k]);
            } else {
                $gift_list[$k]['goods_storage'] = $goods_online_info['goods_storage'];
            }
        }
        $goods_info['gift_list'] = $gift_list;
    }

    /**
     * 取商品最新的在售信息
     * @param unknown $cart_list
     * @return array
     */
    private function _getOnlineCartList($cart_list) {
        if (empty($cart_list) || !is_array($cart_list))
            return $cart_list;
        //验证商品是否有效
        $goods_id_array = array();
        foreach ($cart_list as $key => $cart_info) {
            if (!intval($cart_info['bl_id'])) {
                $goods_id_array[] = $cart_info['goods_id'];
            }
        }
        $goods_model = model('goods');
        $goods_model->lock=$this->lock;
        $goods_online_list = $goods_model->getGoodsOnlineListAndPromotionByIdArray($goods_id_array);
        $goods_online_array = array();
        foreach ($goods_online_list as $goods) {
            $goods_online_array[$goods['goods_id']] = $goods;
        }

        foreach ((array) $cart_list as $key => $cart_info) {
            if (intval($cart_info['bl_id']))
                continue;
            $cart_list[$key]['state'] = true;
            $cart_list[$key]['storage_state'] = true;
            $cart_list[$key]['chain_id'] = 0;
            if (in_array($cart_info['goods_id'], array_keys($goods_online_array))) {
                $goods_online_info = $goods_online_array[$cart_info['goods_id']];
                $cart_list[$key]['goods_commonid'] = $goods_online_info['goods_commonid'];
                $cart_list[$key]['goods_name'] = $goods_online_info['goods_name'];
                $cart_list[$key]['gc_id'] = $goods_online_info['gc_id'];
                $cart_list[$key]['goods_weight'] = $goods_online_info['goods_weight'];
                $cart_list[$key]['gc_id_1'] = $goods_online_info['gc_id_1'];
                $cart_list[$key]['gc_id_2'] = $goods_online_info['gc_id_2'];
                $cart_list[$key]['gc_id_3'] = $goods_online_info['gc_id_3'];
                $cart_list[$key]['goods_image'] = $goods_online_info['goods_image'];
                $cart_list[$key]['goods_price'] = $goods_online_info['goods_price'];
                $cart_list[$key]['goods_original_price'] = $goods_online_info['goods_original_price'];
                $cart_list[$key]['transport_id'] = $goods_online_info['transport_id'];
                $cart_list[$key]['goods_freight'] = $goods_online_info['goods_freight'];
                $cart_list[$key]['goods_vat'] = $goods_online_info['goods_vat'];
                $cart_list[$key]['goods_storage'] = $goods_online_info['goods_storage'];
                $cart_list[$key]['goods_storage_alarm'] = $goods_online_info['goods_storage_alarm'];
                $cart_list[$key]['is_goodsfcode'] = $goods_online_info['is_goodsfcode'];
                $cart_list[$key]['is_have_gift'] = $goods_online_info['is_have_gift'];
                if ($cart_info['goods_num'] > $goods_online_info['goods_storage']) {
                    $cart_list[$key]['storage_state'] = false;
                }
                $cart_list[$key]['groupbuy_info'] = $goods_online_info['groupbuy_info'];
                $cart_list[$key]['mgdiscount_info'] = $goods_online_info['mgdiscount_info'];
                $cart_list[$key]['xianshi_info'] = $goods_online_info['xianshi_info'];
                $cart_list[$key]['presell_info'] = $goods_online_info['presell_info'];
                $cart_list[$key]['wholesale_info'] = $goods_online_info['wholesale_info'];
            } else {
                //如果商品下架
                $cart_list[$key]['state'] = false;
                $cart_list[$key]['storage_state'] = false;
            }
        }

        return $cart_list;
    }

    /**
     *  直接购买时，判断商品是不是正在抢购中，如果是，按抢购价格计算，购买数量若超过抢购规定的上限，则按抢购上限计算
     * @param array $cart_list
     */
    public function getGroupbuyCartList(& $cart_list) {
        if (!config('ds_config.promotion_allow') || empty($cart_list))
            return;
        foreach ($cart_list as $key => $cart_info) {
            if ((isset($cart_info['bl_id']) && $cart_info['bl_id'] === '1') || empty($cart_info['groupbuy_info']))
                continue;
            $this->getGroupbuyInfo($cart_info, $cart_info['goods_num']);
            if ($cart_info) {
                $cart_list[$key] = $cart_info;
            } else {
                unset($cart_list[$key]);
            }
        }
    }

    /**
     * 批量判断购物车内的商品是不是秒杀中，如果购买数量若>=规定的下限，按折扣价格计算,否则按原价计算
     * 并标识该商品为秒杀商品
     * @param array $cart_list
     */
    public function getXianshiCartList(& $cart_list) {
        if (!config('ds_config.promotion_allow') || empty($cart_list))
            return;
        foreach ($cart_list as $key => $cart_info) {
            if ((isset($cart_info['bl_id']) && $cart_info['bl_id'] === '1') || empty($cart_info['xianshi_info']))
                continue;
            $this->getXianshiInfo($cart_info, $cart_info['goods_num']);
            $cart_list[$key] = $cart_info;
        }
    }
    /**
     * 批量判断购物车内的商品是不是预售
     * 并标识该商品为秒杀商品
     * @param array $cart_list
     */
    public function getPresellCartList(& $cart_list) {
        if (!config('ds_config.promotion_allow') || empty($cart_list))
            return;
        
        foreach ($cart_list as $key => $cart_info) {
            if ((isset($cart_info['bl_id']) && $cart_info['bl_id'] === '1') || empty($cart_info['presell_info']))
                continue;
            $this->getPresellInfo($cart_info, $cart_info['goods_num']);
            $cart_list[$key] = $cart_info;
            if(count($cart_list)>1){
                $cart_list[$key]['state'] = false;//预售商品只能直接购买
            }
        }
    }
    /**
     * 批量判断购物车内的商品是不是批发中，如果购买数量若>=规定的下限，按批发价格计算,否则按原价计算
     * 并标识该商品为秒杀商品
     * @param array $cart_list
     */
    public function getWholesaleCartList(& $cart_list) {
        if (!config('ds_config.promotion_allow') || empty($cart_list))
            return;
        foreach ($cart_list as $key => $cart_info) {
            if ((isset($cart_info['bl_id']) && $cart_info['bl_id'] === '1') || empty($cart_info['wholesale_info']))
                continue;
            $this->getWholesaleInfo($cart_info, $cart_info['goods_num']);
            $cart_list[$key] = $cart_info;
        }
    }

    /**
     * 批量判断购物车内的商品是不是会员等级折扣中，如果平台开启会员折扣、商家开启会员折扣并设置，则按折扣价格计算,否则按原价计算
     * 并标识该商品为会员等级折扣
     * @param type $cart_list
     * @return type
     */
    public function getMgdiscountCartList(& $cart_list) {
        if (!config('ds_config.mgdiscount_allow') || empty($cart_list))
            return;

        foreach ($cart_list as $key => $cart_info) {
            if (empty($cart_info['mgdiscount_info']))
                continue;
            $this->getMgdiscountInfo($cart_info);
            $cart_list[$key] = $cart_info;
        }
    }

    /**
     * 取得购物车商品的赠品列表[商品级赠品]
     *
     * @param array $cart_list
     */
    private function _getGiftCartList(& $cart_list) {
        foreach ($cart_list as $k => $cart_info) {
            if ($cart_info['bl_id'])
                continue;
            $this->_getGoodsgiftList($cart_info);
            $cart_list[$k] = $cart_info;
        }
    }

    /**
     * 取得购买车内组合销售信息以及包含的商品及有效状态
     * @param array $cart_list
     */
    private function _getBundlingCartList(& $cart_list) {
        if (!config('ds_config.promotion_allow') || empty($cart_list))
            return;
        $pbundling_model = model('pbundling');
        $goods_model = model('goods');
        foreach ($cart_list as $key => $cart_info) {
            if (!intval($cart_info['bl_id']))
                continue;
            $cart_list[$key]['state'] = true;
            $cart_list[$key]['storage_state'] = true;
            $bl_info = $pbundling_model->getBundlingInfo(array('bl_id' => $cart_info['bl_id']));

            //标志优惠套装是否处于有效状态
            if (empty($bl_info) || !intval($bl_info['bl_state'])) {
                $cart_list[$key]['state'] = false;
            }

            //取得优惠套装商品列表
            $cart_list[$key]['bl_goods_list'] = $pbundling_model->getBundlingGoodsList(array('bl_id' => $cart_info['bl_id']));

            //取最新在售商品信息
            $goods_id_array = array();
            foreach ($cart_list[$key]['bl_goods_list'] as $goods_info) {
                $goods_id_array[] = $goods_info['goods_id'];
            }
            $goods_model->lock=$this->lock;
            $goods_list = $goods_model->getGoodsOnlineListAndPromotionByIdArray($goods_id_array);
            $goods_online_list = array();
            foreach ($goods_list as $goods_info) {
                $goods_online_list[$goods_info['goods_id']] = $goods_info;
            }
            unset($goods_list);

            //使用最新的商品名称、图片,如果一旦有商品下架，则整个套装置置为无效状态
            $total_down_price = 0;
            $tmp_amount = 0;
            foreach ($cart_list[$key]['bl_goods_list'] as $k => $goods_info) {
                if (array_key_exists($goods_info['goods_id'], $goods_online_list)) {
                    $goods_online_info = $goods_online_list[$goods_info['goods_id']];
                    //如果库存不足，标识false
                    if ($cart_info['goods_num'] > $goods_online_info['goods_storage']) {
                        $cart_list[$key]['storage_state'] = false;
                    }
                    $cart_list[$key]['bl_goods_list'][$k]['chain_id'] = 0;
                    $cart_list[$key]['bl_goods_list'][$k]['goods_id'] = $goods_online_info['goods_id'];
                    $cart_list[$key]['bl_goods_list'][$k]['goods_commonid'] = $goods_online_info['goods_commonid'];
                    $cart_list[$key]['bl_goods_list'][$k]['goods_original_price'] = $goods_online_info['goods_original_price'];
                    $cart_list[$key]['bl_goods_list'][$k]['store_id'] = $goods_online_info['store_id'];
                    $cart_list[$key]['bl_goods_list'][$k]['store_name'] = $goods_online_info['store_name'];
                    $cart_list[$key]['bl_goods_list'][$k]['goods_name'] = $goods_online_info['goods_name'];
                    $cart_list[$key]['bl_goods_list'][$k]['goods_image'] = $goods_online_info['goods_image'];
                    $cart_list[$key]['bl_goods_list'][$k]['transport_id'] = $goods_online_info['transport_id'];
                    $cart_list[$key]['bl_goods_list'][$k]['goods_freight'] = $goods_online_info['goods_freight'];
                    $cart_list[$key]['bl_goods_list'][$k]['goods_vat'] = $goods_online_info['goods_vat'];
                    $cart_list[$key]['bl_goods_list'][$k]['goods_storage'] = $goods_online_info['goods_storage'];
                    $cart_list[$key]['bl_goods_list'][$k]['goods_storage_alarm'] = $goods_online_info['goods_storage_alarm'];
                    $cart_list[$key]['bl_goods_list'][$k]['gc_id'] = $goods_online_info['gc_id'];
                    $cart_list[$key]['bl_goods_list'][$k]['goods_weight'] = $goods_online_info['goods_weight'];
                    $cart_list[$key]['bl_goods_list'][$k]['gc_id_1'] = $goods_online_info['gc_id_1'];
                    $cart_list[$key]['bl_goods_list'][$k]['gc_id_2'] = $goods_online_info['gc_id_2'];
                    $cart_list[$key]['bl_goods_list'][$k]['gc_id_3'] = $goods_online_info['gc_id_3'];
                    //每个商品直降多少
                    $total_down_price += $cart_list[$key]['bl_goods_list'][$k]['down_price'] = ds_price_format($goods_online_info['goods_price'] - $goods_info['blgoods_price']);
                    $tmp_amount += $goods_online_info['goods_original_price'];
                } else {
                    //商品已经下架
                    $cart_list[$key]['state'] = false;
                    $cart_list[$key]['storage_state'] = false;
                }
            }
            $cart_list[$key]['down_price'] = ds_price_format($total_down_price);
            $cart_list[$key]['goods_original_price'] = ds_price_format($tmp_amount);
        }
    }

    /**
     * 取得每种商品的库存
     * @param array $store_cart_list 购买列表
     * @param array $store_premiums_list 赠品列表
     * @return array 商品ID=>库存
     */
    private function _getEachGoodsStorageQuantity($store_cart_list, $store_premiums_list = array()) {
        if (empty($store_cart_list) || !is_array($store_cart_list))
            return array();
        $goods_storage_quangity = array();
        foreach ($store_cart_list as $store_cart) {
            foreach ($store_cart as $cart_info) {
                if (!intval($cart_info['bl_id'])) {
                    //正常商品
                    $goods_storage_quangity[$cart_info['goods_id']] = $cart_info['goods_storage'];
                } elseif (!empty($cart_info['bl_goods_list']) && is_array($cart_info['bl_goods_list'])) {
                    //优惠套装
                    foreach ($cart_info['bl_goods_list'] as $goods_info) {
                        $goods_storage_quangity[$goods_info['goods_id']] = $goods_info['goods_storage'];
                    }
                }
            }
        }
        //取得赠品商品的库存
        if (is_array($store_premiums_list)) {
            foreach ($store_premiums_list as $store_id => $goods_list) {
                foreach ($goods_list as $goods_info) {
                    if (!isset($goods_storage_quangity[$goods_info['goods_id']])) {
                        $goods_storage_quangity[$goods_info['goods_id']] = $goods_info['goods_storage'];
                    }
                }
            }
        }
        return $goods_storage_quangity;
    }

    /**
     * 取得每种商品的购买量
     * @param array $store_cart_list 购买列表
     * @return array 商品ID=>购买数量
     */
    private function _getEachGoodsBuyQuantity($store_cart_list) {
        if (empty($store_cart_list) || !is_array($store_cart_list))
            return array();
        $goods_buy_quangity = array();
        foreach ($store_cart_list as $store_cart) {
            foreach ($store_cart as $cart_info) {
                if (!intval($cart_info['bl_id'])) {
                    //正常商品
                    if (!isset($goods_buy_quangity[$cart_info['goods_id']])) {
                        $goods_buy_quangity[$cart_info['goods_id']] = $cart_info['goods_num'];
                    } else {
                        $goods_buy_quangity[$cart_info['goods_id']] += $cart_info['goods_num'];
                    }
                } elseif (!empty($cart_info['bl_goods_list']) && is_array($cart_info['bl_goods_list'])) {
                    //优惠套装
                    foreach ($cart_info['bl_goods_list'] as $goods_info) {
                        if (!isset($goods_buy_quangity[$goods_info['goods_id']])) {
                            $goods_buy_quangity[$goods_info['goods_id']] = $cart_info['goods_num'];
                        } else {
                            $goods_buy_quangity[$goods_info['goods_id']] += $cart_info['goods_num'];
                        }
                    }
                }
            }
        }
        return $goods_buy_quangity;
    }

    /**
     * 得到所购买的id和数量
     *
     */
    private function _parseItems($cart_id) {
        //存放所购商品ID和数量组成的键值对
        $buy_items = array();
        if (is_array($cart_id)) {
            foreach ($cart_id as $value) {
                if (preg_match_all('/^(\d{1,10})\|(\d{1,6})$/', $value, $match)) {
                    $buy_items[$match[1][0]] = $match[2][0];
                }
            }
        }
        return $buy_items;
    }

    /**
     * 拼装单条满即送规则页面描述信息
     * @param array $rule_info 满即送单条规则信息
     * @return string
     */
    private function _parseMansongruleDesc($rule_info) {
        if (empty($rule_info) || !is_array($rule_info))
            return;
        $discount_desc = !empty($rule_info['mansongrule_discount']) ? '减' . $rule_info['mansongrule_discount'] : '';
        $goods_desc = (!empty($rule_info['mansong_goods_name']) && !empty($rule_info['goods_storage'])) ? " 送<a href='" . (string) url('home/Goods/index', ['goods_id' => $rule_info['goods_id']]) . "' title='{$rule_info['mansong_goods_name']}' target='_blank'>[赠品]</a>" : '';
        return sprintf('满%s%s%s', $rule_info['mansongrule_price'], $discount_desc, $goods_desc);
    }

}
