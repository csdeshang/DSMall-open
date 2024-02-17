<?php

namespace app\home\controller;
use think\facade\View;
use think\facade\Lang;
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
 * 控制器
 */
class Goods extends BaseGoods {

    public function initialize() {
        parent::initialize();
        Lang::load(base_path() . 'home/lang/'.config('lang.default_lang').'/goods.lang.php');
    }

    /**
     * 单个商品信息页
     */
    public function index() {
        $goods_id = intval(input('param.goods_id'));

        // 商品详细信息
        $goods_model = model('goods');
        $goods_detail = $goods_model->getGoodsDetail($goods_id);
        $goods_info = $goods_detail['goods_info'];
        if (empty($goods_info)) {
            $this->error(lang('goods_index_no_goods'),HOME_SITE_URL);
        }
        // 获取销量 BEGIN
        $rs = $goods_model->getGoodsList(array('goods_commonid' => $goods_info['goods_commonid']));
        $count = 0;
        foreach ($rs as $v) {
            $count += $v['goods_salenum'];
        }
        $goods_info['goods_salenum'] = $count;
        // 获取销量 END
        $this->getStoreInfo($goods_info['store_id']);

        // 看了又看（同分类本店随机商品）
        $goods_rand_list = model('goods')->getGoodsGcStoreRandList($goods_info['gc_id_1'], $goods_info['store_id'], $goods_info['goods_id'], 2);

        View::assign('goods_rand_list', $goods_rand_list);

        View::assign('spec_list', $goods_detail['spec_list']);
        View::assign('spec_image', $goods_detail['spec_image']);
        View::assign('goods_image', $goods_detail['goods_image']);
        View::assign('mansong_info', $goods_detail['mansong_info']);
        View::assign('gift_array', $goods_detail['gift_array']);

        View::assign('baidu_ak', config('ds_config.baidu_ak'));
        $chain_model=model('chain');
        $chain_ids=Db::name('chain_goods')->where(array(array('goods_id','=',$goods_id),array('goods_storage','>',0)))->column('chain_id');
        $chain_info=$chain_model->getChainOpenInfo(array(array('chain_if_pickup','=',1),array('chain_id','in',$chain_ids)));

        View::assign('if_chain', $chain_info?1:0);
        $inform_switch = true;
        // 检测商品是否下架,检查是否为店主本人
        if ($goods_info['goods_state'] != 1 || $goods_info['goods_verify'] != 1 || $goods_info['store_id'] == session('store_id')) {
            $inform_switch = false;
        }
        View::assign('inform_switch', $inform_switch);

        // 如果使用售卖区域
        if ($goods_info['transport_id'] > 0) {
            // 取得三种运送方式默认运费
            $transport_model = model('transport');
            $transport = $transport_model->getTransportextendList(array('transport_id' => $goods_info['transport_id'],'transportext_is_default'=>1));
            if (!empty($transport) && is_array($transport)) {
                foreach ($transport as $v) {
                    $goods_info["transport_price"] = $v['transportext_sprice'];
                }
            }
        }
        $inviter_model=model('inviter');
        $goods_info['inviter_money']=0;
        if(config('ds_config.inviter_show') && config('ds_config.inviter_open') && $goods_info['inviter_open'] && session('member_id') && $inviter_model->getInviterInfo('i.inviter_id='.session('member_id').' AND i.inviter_state=1')){
            $inviter_money=round($goods_info['inviter_ratio'] / 100 * $goods_info['goods_price'] * floatval(config('ds_config.inviter_ratio_1')) / 100, 2);
            if($inviter_money>0){
                $goods_info['inviter_money']=$inviter_money;
            }
        }
        $goods_info['goodsvideo_url']=goods_video($goods_info['goodsvideo_name']);
//        halt($goods_info);

        //抢购商品是否开始
        $IsHaveBuy = 0;
        if (session('member_id')) {
            $buyer_id = session('member_id');
            $promotion_type = isset($goods_info["promotion_type"]) ? $goods_info["promotion_type"] : '';
            if ($promotion_type == 'groupbuy') {
                //检测是否限购数量
                $upper_limit = $goods_info["upper_limit"];
                if ($upper_limit > 0) {
                    //查询些会员的订单中，是否已买过了
                    $order_model = model('order');
                    //取商品列表
                    $order_goods_list = $order_model->getOrdergoodsList(array('goods_id' => $goods_id, 'buyer_id' => $buyer_id, 'goods_type' => 2));
                    if ($order_goods_list) {
                        //取得上次购买的活动编号(防一个商品参加多次抢购活动的问题)
                        $promotions_id = $order_goods_list[0]["promotions_id"];
                        //用此编号取数据，检测是否这次活动的订单商品。
                        $groupbuy_model = model('groupbuy');
                        $groupbuy_info = $groupbuy_model->getGroupbuyInfo(array('groupbuy_id' => $promotions_id));
                        if ($groupbuy_info) {
                            $IsHaveBuy = 1;
                        } else {
                            $IsHaveBuy = 0;
                        }
                    }
                }
            }
            //判断商品是否有会员折扣
            if(isset($goods_info['goods_mgdiscount_arr'])){
                //获取当前会员折扣
                $level = session('level');
                $level_discount =  $goods_info['goods_mgdiscount_arr'][$level]['level_discount'] / 10;
                $goods_info['goods_price'] = $goods_info['goods_price'] * $level_discount;
            }
        }
        View::assign('goods', $goods_info);
        View::assign('IsHaveBuy', $IsHaveBuy);
        //end

        $storeplate_model = model('storeplate');
        // 顶部关联版式
        if ($goods_info['plateid_top'] > 0) {
            $plate_top = $storeplate_model->getStoreplateInfoByID($goods_info['plateid_top']);
            View::assign('plate_top', $plate_top);
        }
        // 底部关联版式
        if ($goods_info['plateid_bottom'] > 0) {
            $plate_bottom = $storeplate_model->getStoreplateInfoByID($goods_info['plateid_bottom']);
            View::assign('plate_bottom', $plate_bottom);
        }
        View::assign('store_id', $goods_info['store_id']);

        //推荐商品 
        $goods_commend_list = $goods_model->getGoodsOnlineList(array(array('store_id' ,'=', $goods_info['store_id']), array('goods_commend' ,'=', 1)), 'goods_id,goods_name,goods_advword,goods_image,store_id,goods_price', 0, '', 5, 'goods_commonid');
        View::assign('goods_commend', $goods_commend_list);


        // 当前位置导航
        $nav_link_list = model('goodsclass')->getGoodsclassnav($goods_info['gc_id'], 0);
        $nav_link_list[] = array('title' => $goods_info['goods_name']);
        View::assign('nav_link_list', $nav_link_list);

        //评价信息
        $goods_evaluate_info = model('evaluategoods')->getEvaluategoodsInfoByGoodsID($goods_id);
        View::assign('goods_evaluate_info', $goods_evaluate_info);

        //SEO 设置
        $seo_param = array();
        $seo_param['name'] = $goods_info['goods_name'];
        $seo_param['key'] = $goods_info['goods_name'];
        $seo_param['description'] = ds_substing(htmlspecialchars_decode($goods_info['goods_body']));
        $this->_assign_seo(model('seo')->type('product')->param($seo_param)->show());

        return View::fetch($this->template_dir . 'goods');
    }

    /**
     * 记录浏览历史
     */
    public function addbrowse() {
        $goods_id = intval(input('param.gid'));
        model('goodsbrowse')->addViewedGoods($goods_id, session('member_id'), session('store_id'));
        exit();
    }

    /**
     * 商品评论
     */
    public function comments() {
        $goods_id = intval(input('param.goods_id'));
        $type = input('param.type');
        $this->_get_comments($goods_id, $type, 10);
        echo View::fetch($this->template_dir . 'goods_comments');
    }

    /**
     * 商品评价详细页
     */
    public function comments_list() {
        $goods_id = intval(input('param.goods_id'));

        // 商品详细信息
        $goods_model = model('goods');
        $goods_info = $goods_model->getGoodsInfoByID($goods_id);
        // 验证商品是否存在
        if (empty($goods_info)) {
            $this->error(lang('goods_index_no_goods'));
        }
        View::assign('goods', $goods_info);

        $this->getStoreInfo($goods_info['store_id']);

        // 当前位置导航
        $nav_link_list = model('goodsclass')->getGoodsclassnav($goods_info['gc_id'], 0);
        $nav_link_list[] = array('title' => $goods_info['goods_name'], 'link' => (string)url('Goods/index', ['goods_id' => $goods_id]));
        $nav_link_list[] = array('title' => lang('goods_index_evaluation'));
        View::assign('nav_link_list', $nav_link_list);

        //评价信息
        $goods_evaluate_info = model('evaluategoods')->getEvaluategoodsInfoByGoodsID($goods_id);
        View::assign('goods_evaluate_info', $goods_evaluate_info);

        //SEO 设置
        $seo_param = array();
        $seo_param['name'] = $goods_info['goods_name'];
        $seo_param['description'] = ds_substing($goods_info['goods_name']);
        $this->_assign_seo(model('seo')->type('product')->param($seo_param)->show());

        $this->_get_comments($goods_id, input('param.type'), 20);

        return View::fetch($this->template_dir . 'comments_list');
    }

    private function _get_comments($goods_id, $type, $page) {
        $condition = array();
        $condition[]=array('geval_goodsid','=',$goods_id);
        switch ($type) {
            case '1':
                $condition[]=array('geval_scores','in', '5,4');
                View::assign('type', '1');
                break;
            case '2':
                $condition[]=array('geval_scores','in', '3,2');
                View::assign('type', '2');
                break;
            case '3':
                $condition[]=array('geval_scores','in', '1');
                View::assign('type', '3');
                break;
            default:
                View::assign('type','');
                break;
        }

        //查询商品评分信息
        $evaluategoods_model = model('evaluategoods');
        $goodsevallist = $evaluategoods_model->getEvaluategoodsList($condition, $page);
        View::assign('goodsevallist', $goodsevallist);
        View::assign('show_page', $evaluategoods_model->page_info->render());
    }

    /**
     * 销售记录
     */
    public function salelog() {
        $goods_id = intval(input('param.goods_id'));
        $vr = intval('param.vr');
        if ($vr) {
            $vrorder_model = model('vrorder');
            $sales = $vrorder_model->getVrorderAndOrderGoodsSalesRecordList(array(array('goods_id' ,'=', $goods_id)), '*', 10);
            View::assign('show_page', $vrorder_model->page_info->render());
        } else {
            $order_model = model('order');
            $sales = $order_model->getOrderAndOrderGoodsSalesRecordList(array(array('order_goods.goods_id' ,'=', $goods_id)), 'order_goods.*, order.buyer_name, order.add_time', 10);
            View::assign('show_page', $order_model->page_info->render());
        }
        View::assign('sales', $sales);
        View::assign('order_type', array(2 => lang('ds_xianshi_rob'), 3 => lang('ds_xianshi_flag'), '4' => lang('ds_xianshi_suit')));
        echo View::fetch($this->template_dir . 'goods_salelog');
    }

    /**
     * 产品咨询
     */
    public function consulting() {
        $goods_id = intval(input('param.goods_id'));
        if ($goods_id <= 0) {
            $this->error(lang('param_error'), '', 'html', 'error');
        }

        //得到商品咨询信息
        $consult_model = model('consult');
        $condition = array();
        $condition[] = array('goods_id','=',$goods_id);

        $ctid = intval(input('param.ctid'));
        if ($ctid > 0) {
            $condition[] = array('consulttype_id','=',$ctid);
        }
        $consult_list = $consult_model->getConsultList($condition, '*', '10');
        View::assign('consult_list', $consult_list);

        // 咨询类型
        $consult_type = rkcache('consulttype', true);
        View::assign('consult_type', $consult_type);

        View::assign('consult_able', $this->checkConsultAble());
        echo View::fetch($this->template_dir . 'goods_consulting');
    }

    /**
     * 产品咨询
     */
    public function consulting_list() {

        View::assign('hidden_nctoolbar', 1);
        $goods_id = intval(input('param.goods_id'));
        if ($goods_id <= 0) {
            $this->error(lang('param_error'));
        }

        // 商品详细信息
        $goods_model = model('goods');
        $goods_info = $goods_model->getGoodsInfoByID($goods_id);
        // 验证商品是否存在
        if (empty($goods_info)) {
            $this->error(lang('goods_index_no_goods'));
        }
        View::assign('goods', $goods_info);

        $this->getStoreInfo($goods_info['store_id']);

        // 当前位置导航
        $nav_link_list = model('goodsclass')->getGoodsclassnav($goods_info['gc_id'], 0);
        $nav_link_list[] = array('title' => $goods_info['goods_name'], 'link' => (string)url('Goods/index', ['goods_id' => $goods_id]));
        $nav_link_list[] = array('title' => lang('goods_commodity_consulting'));
        View::assign('nav_link_list', $nav_link_list);

        //得到商品咨询信息
        $consult_model = model('consult');
        $condition = array();
        $condition[] = array('goods_id','=',$goods_id);
        if (intval(input('param.ctid')) > 0) {
            $condition[] = array('consulttype_id','=',intval(input('param.ctid')));
        }
        $consult_list = $consult_model->getConsultList($condition, '*');
        View::assign('consult_list', $consult_list);
        View::assign('show_page', $consult_model->page_info->render());

        // 咨询类型
        $consult_type = rkcache('consulttype', true);
        View::assign('consult_type', $consult_type);

        //SEO 设置
        $seo_param = array ();
        $seo_param['name'] = $goods_info['goods_name'];
        $seo_param['description'] = ds_substing($goods_info['goods_name']);
        $this->_assign_seo(model('seo')->type('product')->param($seo_param)->show());

        View::assign('consult_able', $this->checkConsultAble($goods_info['store_id']));
        return View::fetch($this->template_dir . 'consulting_list');
    }

    private function checkConsultAble($store_id = 0) {
        //检查是否为店主本身
        $store_self = false;
        if (session('store_id')) {
            if (($store_id == 0 && intval(input('param.store_id')) == session('store_id')) || ($store_id != 0 && $store_id == session('store_id'))) {
                $store_self = true;
            }
        }
        //查询会员信息
        $member_info = array();
        $member_model = model('member');
        if (session('member_id'))
            $member_info = $member_model->getMemberInfoByID(session('member_id'));
        //检查是否可以评论
        $consult_able = true;
        if ((!config('ds_config.guest_comment') && !session('member_id') ) || $store_self == true || (session('member_id') > 0 && $member_info['is_allowtalk'] == 0)) {
            $consult_able = false;
        }
        return $consult_able;
    }

    /**
     * 商品咨询添加
     */
    public function save_consult() {
        //检查是否可以评论
        if (!config('ds_config.guest_comment') && !session('member_id')) {
            ds_json_encode(10001,lang('goods_index_goods_noallow'));
        }
        $goods_id = intval(input('post.goods_id'));
        if ($goods_id <= 0) {
            ds_json_encode(10001,lang('param_error'));
        }
        //咨询内容的非空验证
        if (trim(input('post.goods_content')) == "") {
            ds_json_encode(10001,lang('goods_index_input_consult'));
        }
        //表单验证
        $data = [
            'goods_content' => input('post.goods_content')
        ];
        $res=word_filter($data['goods_content']);
        if(!$res['code']){
            ds_json_encode(10001,$res['msg']);
        }
        $data['goods_content']=$res['data']['text'];
        $goods_validate = ds_validate('goods');
        if (!$goods_validate->scene('save_consult')->check($data)) {
            ds_json_encode(10001,$goods_validate->getError());
        }

        if (session('member_id')) {
            //查询会员信息
            $member_model = model('member');
            $member_info = $member_model->getMemberInfo(array('member_id' => session('member_id')));
            if (empty($member_info) || $member_info['is_allowtalk'] == 0) {
                ds_json_encode(10001,lang('goods_index_goods_noallow'));
            }
        }
        //判断商品编号的存在性和合法性
        $goods = model('goods');
        $goods_info = $goods->getGoodsInfoByID($goods_id);
        if (empty($goods_info)) {
            ds_json_encode(10001,lang('goods_index_goods_not_exists'));
        }
        //判断是否是店主本人
        if (session('store_id') && $goods_info['store_id'] == session('store_id')) {
            ds_json_encode(10001,lang('goods_index_consult_store_error'));
        }
        //检查店铺状态
        $store_model = model('store');
        $store_info = $store_model->getStoreInfoByID($goods_info['store_id']);
        if ($store_info['store_state'] == '0' || intval($store_info['store_state']) == '2' || (intval($store_info['store_endtime']) != 0 && $store_info['store_endtime'] <= TIMESTAMP)) {
            ds_json_encode(10001,lang('goods_index_goods_store_closed'));
        }
        //接收数据并保存
        $input = array();
        $input['goods_id'] = $goods_id;
        $input['goods_name'] = $goods_info['goods_name'];
        $input['member_id'] = intval(session('member_id')) > 0 ? session('member_id') : 0;
        $input['member_name'] = session('member_name') ? session('member_name') : '';
        $input['store_id'] = $store_info['store_id'];
        $input['store_name'] = $store_info['store_name'];
        $input['consulttype_id'] = intval(input('post.consult_type_id',1));
        $input['consult_addtime'] = TIMESTAMP;
        $input['consult_content'] = $data['goods_content'];
        $input['consult_isanonymous'] = input('post.hide_name')=='hide'?1:0;
        $consult_model = model('consult');
        if ($consult_model->addConsult($input)) {
            ds_json_encode(10000,lang('goods_index_consult_success'));
        } else {
            ds_json_encode(10001,lang('goods_index_consult_fail'));
        }
    }

    /**
     * 异步显示优惠套装/推荐组合
     */
    public function get_bundling() {
        $goods_id = intval(input('param.goods_id'));
        if ($goods_id <= 0) {
            exit();
        }
        $goods_model = model('goods');
        $goods_info = $goods_model->getGoodsOnlineInfoByID($goods_id);
        if (empty($goods_info)) {
            exit();
        }

        // 优惠套装
        $array = model('pbundling')->getBundlingCacheByGoodsId($goods_id);
        if (!empty($array)) {
            View::assign('bundling_array', unserialize($array['bundling_array']));
            View::assign('b_goods_array', unserialize($array['b_goods_array']));
        }

        // 推荐组合
        if (!empty($goods_info) && $goods_model->checkIsGeneral($goods_info)) {
            $array = model('goodscombo')->getGoodscomboCacheByGoodsId($goods_id);
            View::assign('goods_info', $goods_info);
            View::assign('gcombo_list', unserialize($array['gcombo_list']));
        }

        echo View::fetch($this->template_dir . 'goods_bundling');
    }

    /**
     * 商品详细页运费显示
     *
     * @return unknown
     */
    public function calc() {
        if (!is_numeric(input('param.area_id')) || !is_numeric(input('param.tid')))
            return false;
        $freight_total = model('transport')->calcTransport(intval(input('param.tid')), intval(input('param.area_id')));
        if ($freight_total > 0) {
            if (input('param.myf') > 0) {
                if ($freight_total >= input('param.myf')) {
                    $freight_total = lang('free_shipping');
                } else {
                    $freight_total = lang('freight').'：' . $freight_total . lang('shop_with') . input('param.myf') . lang('ds_yuan'). ' '. lang('free_shipping');
                }
            } else {
                $freight_total =lang('freight').'：' . $freight_total . lang('ds_yuan');
            }
        } else {
            if ($freight_total !== false) {
                $freight_total = lang('free_shipping');
            }
        }
        echo input('param.callback') . '(' . json_encode(array('total' => $freight_total)) . ')';
    }

    /**
     * 到货通知
     */
    public function arrival_notice() {
        if (!session('is_login')) {
            $this->error(lang('param_error'));
        }
        $member_info = model('member')->getMemberInfoByID(session('member_id'));
        View::assign('member_info', $member_info);

        return View::fetch($this->template_dir . 'arrival_notice_submit');
    }

    /**
     * 到货通知表单
     */
    public function arrival_notice_submit() {
        $type = intval(input('post.type')) == 2 ? 2 : 1;
        $goods_id = intval(input('post.goods_id'));
        if ($goods_id <= 0) {
            ds_json_encode(10001,lang('param_error'));
        }
        // 验证商品数是否充足
        $goods_info = model('goods')->getGoodsInfoByID($goods_id);
        if (empty($goods_info) || ($goods_info['goods_storage'] > 0 && $goods_info['goods_state'] == 1)) {
            ds_json_encode(10001,lang('param_error'));
        }

        $arrivalnotice_model = model('arrivalnotice');
        // 验证会员是否已经添加到货通知
        $condition = array();
        $condition[] = array('goods_id','=',$goods_info['goods_id']);
        $condition[] = array('member_id','=',session('member_id'));
        $condition[] = array('arrivalnotice_type','=',$type);
        $notice_info = $arrivalnotice_model->getArrivalNoticeInfo($condition);
        if (!empty($notice_info)) {
            if ($type == 1) {
                ds_json_encode(10001,lang('goods_no_repeat_add'));
            } else {
                ds_json_encode(10001,lang('goods_no_repeat_appointment'));
            }
        }

        $mobile=input('post.mobile');
        $member_info = model('member')->getMemberInfoByID(session('member_id'));
        if($mobile==encrypt_show($member_info['member_mobile'],4,4)){
            $mobile=$member_info['member_mobile'];
        }
        $insert = array();
        $insert['goods_id'] = $goods_info['goods_id'];
        $insert['goods_name'] = $goods_info['goods_name'];
        $insert['member_id'] = session('member_id');
        $insert['arrivalnotice_mobile'] = $mobile;
        $insert['arrivalnotice_email'] = input('post.email');
        $insert['arrivalnotice_type'] = $type;
        $arrivalnotice_model->addArrivalNotice($insert);
        ds_json_encode(10000,lang('successful_booking_goods'));
    }


    public function json_area() {
        echo input('param.callback') . '(' . json_encode(model('area')->getAreaArrayForJson()) . ')';
    }
    
    
    public function show_chain(){
        $goods_id=input('param.goods_id');
        $chain_model=model('chain');
        $chain_ids=Db::name('chain_goods')->where(array(array('goods_id','=',$goods_id),array('goods_storage','>',0)))->column('chain_id');
        $chain_list=$chain_model->getChainOpenList(array(array('chain_if_pickup','=',1),array('chain_id','in',$chain_ids)));
        View::assign('chain_list', $chain_list);
        return View::fetch($this->template_dir . 'show_chain');
    }

}

?>
