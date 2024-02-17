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
class Sellerorder extends BaseSeller {

    public function initialize() {
        parent::initialize();
        Lang::load(base_path() . 'home/lang/' . config('lang.default_lang') . '/sellerorder.lang.php');
    }

    /**
     * 订单列表
     *
     */
    public function index() {
        $order_model = model('order');
        $condition = array();
        $condition[] = array('store_id', '=', session('store_id'));

        $order_sn = input('get.order_sn');
        if ($order_sn != '') {
            $condition[] = array('order_sn', '=', $order_sn);
        }
        $buyer_name = input('get.buyer_name');
        if ($buyer_name != '') {
            $condition[] = array('buyer_name', '=', $buyer_name);
        }
        $allow_state_array = array('state_new', 'state_pay', 'state_send', 'state_success', 'state_cancel');
        $state_type = input('param.state_type');
        if (in_array($state_type, $allow_state_array)) {
            $condition[] = array('order_state', '=', str_replace($allow_state_array, array(ORDER_STATE_NEW, ORDER_STATE_PAY, ORDER_STATE_SEND, ORDER_STATE_SUCCESS, ORDER_STATE_CANCEL), $state_type));
        } else {
            $state_type = 'store_order';
        }
        $if_start_date = preg_match('/^20\d{2}-\d{2}-\d{2}$/', input('get.query_start_date'));
        $if_end_date = preg_match('/^20\d{2}-\d{2}-\d{2}$/', input('get.query_end_date'));
        $start_unixtime = $if_start_date ? strtotime(input('get.query_start_date')) : null;
        $end_unixtime = $if_end_date ? (strtotime(input('get.query_end_date'))+86399) : null;
        if ($start_unixtime) {
            $condition[] = array('add_time', '>=', $start_unixtime);
        }
        if ($end_unixtime) {
            $condition[] = array('add_time', '<=', $end_unixtime);
        }

        $skip_off = input('get.buyer_name');
        if ($skip_off == 1) {
            $condition[] = array('order_state', '<>', ORDER_STATE_CANCEL);
        }

        $order_list = $order_model->getOrderList($condition, 10, '*', 'order_id desc', 0, array('order_goods', 'order_common', 'ppintuanorder', 'member'));
        View::assign('show_page', $order_model->page_info->render());
        View::assign('expresscf_kdn_if_open', $this->store_info['expresscf_kdn_if_open']);

        //页面中显示那些操作
        foreach ($order_list as $key => $order_info) {
            //显示取消订单
            $order_info['if_cancel'] = $order_model->getOrderOperateState('store_cancel', $order_info);
            //显示调整运费
            $order_info['if_modify_price'] = $order_model->getOrderOperateState('modify_price', $order_info);
            //显示修改价格
            $order_info['if_spay_price'] = $order_model->getOrderOperateState('spay_price', $order_info);
            //显示发货
            $order_info['if_send'] = $order_model->getOrderOperateState('send', $order_info);
            //显示锁定中
            $order_info['if_lock'] = $order_model->getOrderOperateState('lock', $order_info);
            //显示物流跟踪
            $order_info['if_deliver'] = $order_model->getOrderOperateState('deliver', $order_info);

            foreach ($order_info['extend_order_goods'] as $value) {
                $value['image_240_url'] = goods_cthumb($value['goods_image'], 240, $value['store_id']);
                $value['goods_type_cn'] = get_order_goodstype($value['goods_type']);
                $value['goods_url'] = (string) url('Goods/index', ['goods_id' => $value['goods_id']]);
                if ($value['goods_type'] == 5) {
                    $order_info['zengpin_list'][] = $value;
                } else {
                    $order_info['goods_list'][] = $value;
                }
            }

            if (empty($order_info['zengpin_list'])) {
                $order_info['goods_count'] = count($order_info['goods_list']);
            } else {
                $order_info['goods_count'] = count($order_info['goods_list']) + 1;
            }
            $order_list[$key] = $order_info;
        }

        View::assign('order_list', $order_list);


        /* 设置卖家当前菜单 */
        $this->setSellerCurMenu('sellerorder');
        /* 设置卖家当前栏目 */
        $this->setSellerCurItem($state_type);
        return View::fetch($this->template_dir . 'index');
    }

    /**
     * 卖家订单详情
     *
     */
    public function show_order() {
        $order_id = intval(input('param.order_id'));
        if ($order_id <= 0) {
            $this->error(lang('param_error'));
        }
        $order_model = model('order');
        $condition = array();
        $condition[] = array('order_id','=',$order_id);
        $condition[] = array('store_id','=',session('store_id'));
        $order_info = $order_model->getOrderInfo($condition, array('order_common', 'order_goods', 'member', 'ppintuanorder'));
        if (empty($order_info)) {
            $this->error(lang('store_order_none_exist'));
        }

        $refundreturn_model = model('refundreturn');
        $order_list = array();
        $order_list[$order_id] = $order_info;
        $order_list = $refundreturn_model->getGoodsRefundList($order_list, 1); //订单商品的退款退货显示
        $order_info = $order_list[$order_id];
        $refund_all = isset($order_info['refund_list'][0]) ? $order_info['refund_list'][0] : '';
        if (!empty($refund_all) && $refund_all['seller_state'] < 3) {//订单全部退款商家审核状态:1为待审核,2为同意,3为不同意
            View::assign('refund_all', $refund_all);
        }

        //显示锁定中
        $order_info['if_lock'] = $order_model->getOrderOperateState('lock', $order_info);

        //显示调整运费
        $order_info['if_modify_price'] = $order_model->getOrderOperateState('modify_price', $order_info);

        //显示调整价格
        $order_info['if_spay_price'] = $order_model->getOrderOperateState('spay_price', $order_info);

        //显示取消订单
        $order_info['if_cancel'] = $order_model->getOrderOperateState('store_cancel', $order_info);

        //显示发货
        $order_info['if_send'] = $order_model->getOrderOperateState('send', $order_info);

        //显示物流跟踪
        $order_info['if_deliver'] = $order_model->getOrderOperateState('deliver', $order_info);

        //显示系统自动取消订单日期
        if ($order_info['order_state'] == ORDER_STATE_NEW) {
            $order_info['order_cancel_day'] = $order_info['add_time'] + config('ds_config.order_auto_cancel_day') * 24 * 3600;
        }

        //显示快递信息
        $express = rkcache('express', true);
        if ($order_info['shipping_code'] != '' && isset($express[$order_info['extend_order_common']['shipping_express_id']])) {
            $order_info['express_info']['express_code'] = $express[$order_info['extend_order_common']['shipping_express_id']]['express_code'];
            $order_info['express_info']['express_name'] = $express[$order_info['extend_order_common']['shipping_express_id']]['express_name'];
            $order_info['express_info']['express_url'] = $express[$order_info['extend_order_common']['shipping_express_id']]['express_url'];
        } else {
            $order_info['express_info']['express_code'] = '';
            $order_info['express_info']['express_name'] = '';
            $order_info['express_info']['express_url'] = '';
        }

        //显示系统自动收获时间
        if ($order_info['order_state'] == ORDER_STATE_SEND) {
            $order_info['order_confirm_day'] = $order_info['delay_time'] + config('ds_config.order_auto_receive_day') * 24 * 3600;
        }

        //如果订单已取消，取得取消原因、时间，操作人
        if ($order_info['order_state'] == ORDER_STATE_CANCEL) {
            $order_info['close_info'] = $order_model->getOrderlogInfo(array('order_id' => $order_info['order_id']), 'log_id desc');
        }
        $order_info['chain_order_type'] = 0;
        //如果是待自提则获取提货码
        $chain_order_model = model('chain_order');
        $chain_order_info = $chain_order_model->getChainOrderInfo(array(array('order_id', '=', $order_info['order_id'])));
        if ($chain_order_info) {
            $order_info['chain_order_type'] = $chain_order_info['chain_order_type'];
        }
        foreach ($order_info['extend_order_goods'] as $value) {
            $value['image_240_url'] = goods_cthumb($value['goods_image'], 240, $value['store_id']);
            $value['goods_type_cn'] = get_order_goodstype($value['goods_type']);
            $value['goods_url'] = (string) url('Goods/index', ['goods_id' => $value['goods_id']]);
            if ($value['goods_type'] == 5) {
                $order_info['zengpin_list'][] = $value;
            } else {
                $order_info['goods_list'][] = $value;
            }
        }

        if (empty($order_info['zengpin_list'])) {
            $order_info['goods_count'] = count($order_info['goods_list']);
        } else {
            $order_info['goods_count'] = count($order_info['goods_list']) + 1;
        }

        View::assign('order_info', $order_info);

        //发货信息
        if (!empty($order_info['extend_order_common']['daddress_id'])) {
            $daddress_info = model('daddress')->getAddressInfo(array('daddress_id' => $order_info['extend_order_common']['daddress_id']));
            View::assign('daddress_info', $daddress_info);
        }

        /* 设置卖家当前菜单 */
        $this->setSellerCurMenu('sellerorder');
        /* 设置卖家当前栏目 */
        $this->setSellerCurItem();
        return View::fetch($this->template_dir . 'show_order');
    }

    /**
     * 卖家订单状态操作
     *
     */
    public function change_state() {
        $state_type = input('param.state_type');
        $order_id = intval(input('param.order_id'));

        $order_model = model('order');
        $condition = array();
        $condition[] = array('order_id','=',$order_id);
        $condition[] = array('store_id','=',session('store_id'));
        $order_info = $order_model->getOrderInfo($condition);
        if ($state_type == 'order_cancel') {
            $result = $this->_order_cancel($order_info, input('post.'));
        } elseif ($state_type == 'modify_price') {
            $result = $this->_order_ship_price($order_info, input('post.'));
        } elseif ($state_type == 'spay_price') {
            $result = $this->_order_spay_price($order_info, input('post.'));
        }
        if (!$result['code']) {
            ds_json_encode(10001, $result['msg']);
        } else {
            ds_json_encode(10000, $result['msg']);
        }
    }
    
    /**
     * 打印订单
     * 
     */
    public function print_order() {
        $order_id = ds_delete_param(input('param.order_id'));
        if (empty($order_id)) {
            $this->error(lang('param_error'));
        }
        $order_model = model('order');
        $condition = array();
        $condition[] = array('order_id','in',$order_id);
        $condition[] = array('store_id','=',session('store_id'));
        $order_list = $order_model->getOrderList($condition, '', '*', 'order_id desc', 0, array('order_common', 'order_goods'));
        if (empty($order_list)) {
            $this->error(lang('member_printorder_ordererror'));
        }
        

        //卖家信息
        $store_model = model('store');
        $store_info = $store_model->getStoreInfoByID(session('store_id'));
        if (!empty($store_info['store_avatar'])) {
            $store_info['store_avatar'] = ds_get_pic( ATTACH_STORE . DIRECTORY_SEPARATOR .$store_info['store_id'],$store_info['store_avatar']);
        }
        if (!empty($store_info['store_seal'])) {
            $store_info['store_seal'] = ds_get_pic( ATTACH_STORE , $store_info['store_seal']);
        }
        View::assign('store_info', $store_info);

        //订单商品
        foreach($order_list as $key =>$order_info){
            $goods_all_num = 0;
            $goods_total_price = 0;
            if (isset($order_info['extend_order_goods']) && !empty($order_info['extend_order_goods'])) {
                foreach ($order_info['extend_order_goods'] as $k => $v) {
                    $v['goods_name'] = str_cut($v['goods_name'], 100);
                    $goods_all_num += $v['goods_num'];
                    $v['goods_all_price'] = ds_price_format($v['goods_num'] * $v['goods_price']);
                    $goods_total_price += $v['goods_all_price'];
                    $order_list[$key]['extend_order_goods'][$k]=$v;
                }
                //优惠金额
                $order_list[$key]['promotion_amount'] = $goods_total_price - $order_info['goods_amount'];
                $order_list[$key]['goods_all_num'] = $goods_all_num;
                $order_list[$key]['goods_total_price'] = ds_price_format($goods_total_price);
                $order_list[$key]['total_page'] = ceil(count($order_info['extend_order_goods']) / 15);
            }
            
        }
        View::assign('order_list', $order_list);
        return View::fetch($this->template_dir.'print_order');
    }
    /**
     * 打印订单
     * 
     */
    public function print_eorder() {
        $order_id = ds_delete_param(input('param.order_id'));
        if (empty($order_id)) {
            $this->error(lang('param_error'));
        }
        $order_model = model('order');
        $condition = array();
        $condition[] = array('order_id','in',$order_id);
        $condition[] = array('store_id','=',session('store_id'));
        $order_list = $order_model->getOrderList($condition);
        if (empty($order_list)) {
            $this->error(lang('member_printorder_ordererror'));
        }
        
        $requestData=array();
        foreach($order_list as $key =>$order_info){
            $requestData[]=array(
                'OrderCode'=>$order_info['order_sn'],
                'PortName'=>$this->store_info['expresscf_kdn_printer'],
            );
        }
        $expresscf_kdn_config_model = model('expresscf_kdn_config');
        echo $expresscf_kdn_config_model->printExpresscfKdnOrder($requestData, $this->store_info['expresscf_kdn_id'], $this->store_info['expresscf_kdn_key']);
    }

    /**
     * 取消订单
     * @param unknown $order_info
     */
    private function _order_cancel($order_info, $post) {
        $order_model = model('order');
        $logic_order = model('order', 'logic');

        if (!request()->isPost()) {
            View::assign('order_info', $order_info);
            View::assign('order_id', $order_info['order_id']);
            echo View::fetch($this->template_dir . 'cancel');
            exit();
        } else {
            $if_allow = $order_model->getOrderOperateState('store_cancel', $order_info);
            if (!$if_allow) {
                return ds_callback(false, lang('have_no_legalpower'));
            }
            $msg = $post['state_info1'] != '' ? $post['state_info1'] : $post['state_info'];
            try{
                Db::startTrans();
                $logic_order->changeOrderStateCancel($order_info, 'seller', session('member_name'), $msg);
            } catch (\Exception $e) {
                Db::rollback();
                return ds_callback(false, $e->getMessage());
            }
            Db::commit();    
            return ds_callback(true, lang('ds_common_op_succ'));
        }
    }

    /**
     * 修改运费
     * @param unknown $order_info
     */
    private function _order_ship_price($order_info, $post) {
        $order_model = model('order');
        $logic_order = model('order', 'logic');
        if (!request()->isPost()) {
            View::assign('order_info', $order_info);
            View::assign('order_id', $order_info['order_id']);
            echo View::fetch($this->template_dir . 'edit_price');
            exit();
        } else {
            $if_allow = $order_model->getOrderOperateState('modify_price', $order_info);
            if (!$if_allow) {
                return ds_callback(false, lang('have_no_legalpower'));
            }
            return $logic_order->changeOrderShipPrice($order_info, 'seller', session('member_name'), $post['shipping_fee']);
        }
    }

    /**
     * 修改商品价格
     * @param unknown $order_info
     */
    private function _order_spay_price($order_info, $post) {
        $order_model = model('order');
        $logic_order = model('order', 'logic');
        if (!request()->isPost()) {
            View::assign('order_info', $order_info);
            View::assign('order_id', $order_info['order_id']);
            echo View::fetch($this->template_dir . 'edit_spay_price');
            exit();
        } else {
            $if_allow = $order_model->getOrderOperateState('spay_price', $order_info);
            if (!$if_allow) {
                return ds_callback(false, lang('have_no_legalpower'));
            }
            return $logic_order->changeOrderSpayPrice($order_info, 'seller', session('member_name'), $post['goods_amount']);
        }
    }

    /**
     * 用户中心右边，小导航
     *
     * @param string $menu_type 导航类型
     * @param string $menu_key 当前导航的menu_key
     * @return
     */
    function getSellerItemList() {
        $menu_array = array(
            array(
                'name' => 'store_order',
                'text' => lang('ds_member_path_all_order'),
                'url' => (string) url('Sellerorder/index')
            ),
            array(
                'name' => 'state_new',
                'text' => lang('ds_member_path_wait_pay'),
                'url' => (string) url('Sellerorder/index', ['state_type' => 'state_new'])
            ),
            array(
                'name' => 'state_pay',
                'text' => lang('ds_member_path_wait_send'),
                'url' => (string) url('Sellerorder/index', ['state_type' => 'state_pay'])
            ),
            array(
                'name' => 'state_send',
                'text' => lang('ds_member_path_sent'),
                'url' => (string) url('Sellerorder/index', ['state_type' => 'state_send'])
            ),
            array(
                'name' => 'state_success',
                'text' => lang('ds_member_path_finished'),
                'url' => (string) url('Sellerorder/index', ['state_type' => 'state_success'])
            ),
            array(
                'name' => 'state_cancel',
                'text' => lang('ds_member_path_canceled'),
                'url' => (string) url('Sellerorder/index', ['state_type' => 'state_cancel'])
            ),
        );
        return $menu_array;
    }

}

?>
