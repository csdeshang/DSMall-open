<?php

/*
 * 虚拟订单
 */

namespace app\home\controller;

use think\facade\View;
use think\facade\Lang;

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
class Sellervrorder extends BaseSeller {

    public function initialize() {
        parent::initialize();
        Lang::load(base_path() . 'home/lang/' . config('lang.default_lang') . '/sellervrorder.lang.php');
    }

    /**
     * 虚拟订单列表
     *
     */
    public function index() {
        $vrorder_model = model('vrorder');

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
        $allow_state_array = array('state_new', 'state_pay', 'state_success', 'state_cancel');
        $state_type = input('param.state_type');
        if (in_array($state_type, $allow_state_array)) {
            $condition[] = array('order_state', '=', str_replace($allow_state_array, array(ORDER_STATE_NEW, ORDER_STATE_PAY, ORDER_STATE_SUCCESS, ORDER_STATE_CANCEL), $state_type));
        } else {
            $state_type = 'store_order';
        }
        $query_start_date = input('get.query_start_date');
        $query_end_date = input('get.query_end_date');
        $if_start_date = preg_match('/^20\d{2}-\d{2}-\d{2}$/', $query_end_date);
        $if_end_date = preg_match('/^20\d{2}-\d{2}-\d{2}$/', $query_start_date);
        $start_unixtime = $if_start_date ? strtotime($query_end_date) : null;
        $end_unixtime = $if_end_date ? (strtotime($query_start_date)+86399) : null;
        if ($start_unixtime) {
            $condition[] = array('add_time', '>=', $start_unixtime);
        }
        if ($end_unixtime) {
            $condition[] = array('add_time', '<=', $end_unixtime);
        }

        $skip_off = input('get.skip_off');
        if ($skip_off == 1) {
            $condition[] = array('order_state', '<>', ORDER_STATE_CANCEL);
        }

        $order_list = $vrorder_model->getVrorderList($condition, 20, '*', 'order_id desc');
        View::assign('show_page', $vrorder_model->page_info->render());

        foreach ($order_list as $key => $order) {
            //显示取消订单
            $order_list[$key]['if_cancel'] = $vrorder_model->getVrorderOperateState('buyer_cancel', $order);

            //追加返回买家信息
            $order_list[$key]['extend_member'] = model('member')->getMemberInfoByID($order['buyer_id']);
        }

        View::assign('order_list', $order_list);



        /* 设置卖家当前菜单 */
        $this->setSellerCurMenu('sellervrorder');
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
        $vrorder_model = model('vrorder');
        $condition = array();
        $condition[] = array('order_id','=',$order_id);
        $condition[] = array('store_id','=',session('store_id'));
        $order_info = $vrorder_model->getVrorderInfo($condition);
        if (empty($order_info)) {
            $this->error(lang('store_order_none_exist'));
        }

        //取兑换码列表
        $vr_code_list = $vrorder_model->getShowVrordercodeList(array('order_id' => $order_info['order_id']));
        $order_info['extend_vr_order_code'] = $vr_code_list;

        //显示取消订单
        $order_info['if_cancel'] = $vrorder_model->getVrorderOperateState('buyer_cancel', $order_info);

        //显示订单进行步骤
        $order_info['step_list'] = $vrorder_model->getVrorderStep($order_info);

        //显示系统自动取消订单日期
        if ($order_info['order_state'] == ORDER_STATE_NEW) {
            $order_info['order_cancel_day'] = $order_info['add_time'] + config('ds_config.order_auto_cancel_day') * 24 * 3600;
        }
        if($order_info['virtual_type']==1){
            $order_info['virtual_content']=explode('\r\n',$order_info['virtual_content']);
        }
        View::assign('order_info', $order_info);
        $this->setSellerCurMenu('sellervrorder');
        $this->setSellerCurItem('store_order');

        return View::fetch($this->template_dir . 'show_order');
    }

    /**
     * 卖家订单状态操作
     *
     */
    public function change_state() {
        $vrorder_model = model('vrorder');
        $condition = array();
        $condition[] = array('order_id','=',intval(input('param.order_id')));
        $condition[] = array('store_id','=',session('store_id'));
        $order_info = $vrorder_model->getVrorderInfo($condition);
        $state_type = input('param.state_type');
        if ($state_type == 'order_cancel') {
            $result = $this->_order_cancel($order_info, input('post.'));
        }
        if (!isset($result['code'])) {
            ds_json_encode(10001, lang('error'));
        } else {
            ds_json_encode(10000, $result['msg']);
        }
    }

    /**
     * 取消订单
     * @param arrty $order_info
     * @param arrty $post
     * @throws Exception
     */
    private function _order_cancel($order_info, $post) {
        if (!request()->isPost()) {
            View::assign('order_id', $order_info['order_id']);
            View::assign('order_info', $order_info);
            echo View::fetch($this->template_dir . 'cancel');
            exit();
        } else {
            $vrorder_model = model('vrorder');
            $logic_vrorder = model('vrorder', 'logic');
            $if_allow = $vrorder_model->getVrorderOperateState('store_cancel', $order_info);
            if (!$if_allow) {
                return ds_callback(false, lang('have_right_operate'));
            }
            $msg = $post['state_info1'] != '' ? $post['state_info1'] : $post['state_info'];
            return $logic_vrorder->changeOrderStateCancel($order_info, 'seller', $msg);
        }
    }

    public function exchange() {
        $data = $this->_exchange();
        exit(json_encode($data));
    }

    /**
     * 兑换码消费
     */
    private function _exchange() {
        if (input('param.submit_exchange') == 'ok') {
            if (!preg_match('/^[a-zA-Z0-9]{15,18}$/', input('get.vr_code'))) {
                return array('error' => lang('exchange_code_format_error'));
            }
            $vrorder_model = model('vrorder');
            $vr_code_info = $vrorder_model->getVrordercodeInfo(array('vr_code' => input('get.vr_code')));
            if (empty($vr_code_info) || $vr_code_info['store_id'] != session('store_id')) {
                return array('error' => lang('exchange_code_not_exist'));
            }
            if ($vr_code_info['vr_state'] == '1') {
                return array('error' => lang('exchange_code_been_used'));
            }
            if ($vr_code_info['vr_indate'] < TIMESTAMP) {
                return array('error' => lang('exchange_code_expired') . date('Y-m-d H:i:s', $vr_code_info['vr_indate']));
            }
            if ($vr_code_info['refund_lock'] > 0) {//退款锁定状态:0为正常,1为锁定(待审核),2为同意
                return array('error' => lang('exchange_code_been_applied_refund'));
            }

            //更新兑换码状态
            $update = array();
            $update['vr_state'] = 1;
            $update['vr_usetime'] = TIMESTAMP;
            $update = $vrorder_model->editVrorderCode($update, array('vr_code' => input('get.vr_code')));

            //如果全部兑换完成，更新订单状态
            model('vrorder', 'logic')->changeOrderStateSuccess($vr_code_info['order_id']);

            if ($update) {
                //取得返回信息
                $order_info = $vrorder_model->getVrorderInfo(array('order_id' => $vr_code_info['order_id']));
                if ($order_info['use_state'] == '0') {
                    $vrorder_model->editVrorder(array('use_state' => 1), array('order_id' => $vr_code_info['order_id']));
                }
                $order_info['img_240'] = goods_thumb($order_info, 240);
                $order_info['goods_url'] = (string) url('Goods/index', ['goods_id' => $order_info['goods_id']]);
                $order_info['order_url'] = (string) url('Sellervrorder/show_order', ['order_id' => $order_info['order_id']]);
                return array('error' => '', 'data' => $order_info);
            }
        } else {
            $state_type = input('state_type');
            ;
            /* 设置卖家当前菜单 */
            $this->setSellerCurMenu('sellervrorder');
            /* 设置卖家当前栏目 */
            $this->setSellerCurItem($state_type);
            echo View::fetch($this->template_dir . 'exchange');
            exit;
        }
    }

    /**
     *    栏目菜单
     */
    function getSellerItemList() {
        $menu_array = array(
            array(
                'name' => 'store_order',
                'text' => lang('ds_member_path_all_order'),
                'url' => (string) url('Sellervrorder/index'),
            ),
            array(
                'name' => 'state_new',
                'text' => lang('ds_member_path_wait_pay'),
                'url' => (string) url('Sellervrorder/index', ['state_type' => 'state_new']),
            ),
            array(
                'name' => 'state_pay',
                'text' => lang('payment_been'),
                'url' => (string) url('Sellervrorder/index', ['state_type' => 'state_pay']),
            ),
            array(
                'name' => 'state_success',
                'text' => lang('ds_member_path_finished'),
                'url' => (string) url('Sellervrorder/index', ['state_type' => 'state_success']),
            ),
            array(
                'name' => 'state_cancel',
                'text' => lang('ds_member_path_canceled'),
                'url' => (string) url('Sellervrorder/index', ['state_type' => 'state_cancel']),
            ),
        );
        if (request()->action() === 'exchange') {
            $menu_array[] = array(
                'name' => 'exchange',
                'text' => lang('exchange_code'),
                'url' => (string) url('Sellervrorder/exchange'),
            );
        }
        return $menu_array;
    }

}
