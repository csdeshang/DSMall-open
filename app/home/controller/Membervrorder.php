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
class Membervrorder extends BaseMember {

    public function initialize() {
        parent::initialize(); // TODO: Change the autogenerated stub
        Lang::load(base_path() . 'home/lang/'.config('lang.default_lang').'/memberorder.lang.php');
        Lang::load(base_path() . 'home/lang/'.config('lang.default_lang').'/sellerorder.lang.php');
    }

    /**
     * 买家我的订单
     *
     */
    public function index() {
        $vrorder_model = model('vrorder');
        //搜索
        $condition = array();
        $condition[] = array('buyer_id','=',session('member_id'));
        if (input('param.order_sn') != '') {
            $condition[] = array('order_sn','=',input('param.order_sn'));
        }
        $if_start_date = preg_match('/^20\d{2}-\d{2}-\d{2}$/', input('param.query_start_date'));
        $if_end_date = preg_match('/^20\d{2}-\d{2}-\d{2}$/', input('param.query_end_date'));
        $start_unixtime = $if_start_date ? strtotime(input('param.query_start_date')) : null;
        $end_unixtime = $if_end_date ? strtotime(input('param.query_end_date')) : null;
        if ($start_unixtime) {
            $condition[] = array('add_time','>=',$start_unixtime);
        }
        if ($end_unixtime) {
            $end_unixtime=$end_unixtime+86399;
            $condition[] = array('add_time','<=',$end_unixtime);
        }
        if (input('param.state_type') != '') {
            $order_state = str_replace(
                    array(
                'state_new', 'state_pay', 'state_success', 'state_cancel'
                    ), array(
                ORDER_STATE_NEW, ORDER_STATE_PAY, ORDER_STATE_SUCCESS, ORDER_STATE_CANCEL
                    ), input('param.state_type'));
            $condition[] = array('order_state','=',$order_state);
        }

        $order_list = $vrorder_model->getVrorderList($condition, 20, '*', 'order_id desc');
        //没有使用的兑换码列表
        $order_list = $vrorder_model->getCodeRefundList($order_list);

        foreach ($order_list as $key => $order) {

            //显示取消订单
            $order_list[$key]['if_cancel'] = $vrorder_model->getVrorderOperateState('buyer_cancel', $order);

            //显示支付
            $order_list[$key]['if_pay'] = $vrorder_model->getVrorderOperateState('payment', $order);

            //显示删除订单(放入回收站)
            $order_list[$key]['if_delete'] = $vrorder_model->getVrorderOperateState('delete', $order);

            //显示永久删除
            $order_list[$key]['if_drop'] = $vrorder_model->getVrorderOperateState('drop', $order);

            //显示还原订单
            $order_list[$key]['if_restore'] = $vrorder_model->getVrorderOperateState('restore', $order);

            //显示退款
            $order_list[$key]['if_refund'] = $vrorder_model->getVrorderOperateState('refund', $order);

            //显示评价
            $order_list[$key]['if_evaluation'] = $vrorder_model->getVrorderOperateState('evaluation', $order);

            //显示商家信息(QQ,WW)
            $order_list[$key]['extend_store'] = model('store')->getStoreInfoByID($order['store_id']);
        }

        View::assign('order_list', $order_list);
        View::assign('show_page', $vrorder_model->page_info->render());
        $this->setMemberCurMenu('member_vr_order');
        $this->setMemberCurItem(input('param.recycle') ? 'member_order_recycle' : 'member_order');
        return View::fetch($this->template_dir . 'index');
    }

    /**
     * 订单详细
     *
     */
    public function show_order() {
        $order_id = intval(input('param.order_id'));
        if ($order_id <= 0) {
            $this->error(lang('member_order_none_exist'));
        }
        $vrorder_model = model('vrorder');
        $condition = array();
        $condition[] = array('order_id','=',$order_id);
        $condition[] = array('buyer_id','=',session('member_id'));
        $order_info = $vrorder_model->getVrorderInfo($condition);
        if (empty($order_info) || $order_info['delete_state'] == ORDER_DEL_STATE_DROP) {
            $this->error(lang('member_order_none_exist'));
        }
        $order_list = array();
        $order_list[$order_id] = $order_info;
        $order_list = $vrorder_model->getCodeRefundList($order_list); //没有使用的兑换码列表
        $order_info = $order_list[$order_id];

        $store_info = model('store')->getStoreInfo(array('store_id' => $order_info['store_id']));

        //取兑换码列表
        $vr_code_list = $vrorder_model->getShowVrordercodeList(array('order_id' => $order_info['order_id']));
        $order_info['extend_vr_order_code'] = $vr_code_list;

        //显示取消订单
        $order_info['if_cancel'] = $vrorder_model->getVrorderOperateState('buyer_cancel', $order_info);

        //显示订单进行步骤
        $order_info['step_list'] = $vrorder_model->getVrorderStep($order_info);

        //显示退款
        $order_info['if_refund'] = $vrorder_model->getVrorderOperateState('refund', $order_info);

        //显示评价
        $order_info['if_evaluation'] = $vrorder_model->getVrorderOperateState('evaluation', $order_info);

        //显示系统自动取消订单日期
        if ($order_info['order_state'] == ORDER_STATE_NEW) {
            $order_info['order_cancel_day'] = $order_info['add_time'] + config('ds_config.order_auto_cancel_day') * 24 * 3600;
        }
        if($order_info['virtual_type']==1){
            $order_info['virtual_content']=explode('\r\n',$order_info['virtual_content']);
        }
        View::assign('order_info', $order_info);
        View::assign('store_info', $store_info);
        $this->setMemberCurMenu('member_vr_order');
        $this->setMemberCurItem('show_order');
        return View::fetch($this->template_dir . 'show_order');
    }

    /**
     * 买家订单状态操作
     *
     */
    public function change_state() {
        $vrorder_model = model('vrorder');
        $condition = array();
        $condition[] = array('order_id','=',intval(input('param.order_id')));
        $condition[] = array('buyer_id','=',session('member_id'));
        $order_info = $vrorder_model->getVrorderInfo($condition);

        if (input('param.state_type') == 'order_cancel') {
            $result = $this->_order_cancel($order_info, input('post.'));
        }

        if (!isset($result['code'])) {
            ds_json_encode(10001,lang('no_permission_operation'));
        } else {
            ds_json_encode(10000,$result['msg']);
        }
    }

    /**
     * 取消订单
     */
    private function _order_cancel($order_info, $post) {
        if (!request()->isPost()) {
            View::assign('order_info', $order_info);
            echo View::fetch($this->template_dir . 'cancel');
            exit();
        } else {
            $vrorder_model = model('vrorder');
            $logic_vrorder = model('vrorder', 'logic');
            $if_allow = $vrorder_model->getVrorderOperateState('buyer_cancel', $order_info);
            if (!$if_allow) {
                return callback(false, lang('have_right_operate'));
            }

            $msg = $post['state_info1'] != '' ? $post['state_info1'] : $post['state_info'];
            return $logic_vrorder->changeOrderStateCancel($order_info, 'buyer', $msg);
        }
    }

    /**
     * 发送兑换码到手机
     */
    public function resend() {
        if (!request()->isPost()) {
            return View::fetch($this->template_dir . 'resend');
            exit();
        }
        if (!preg_match('/^[\d]{11}$/', input('post.buyer_phone'))) {
            ds_json_encode(10001,lang('please_fill_phone_number_correctly'));
        }
        $order_id = intval(input('post.order_id'));
        if ($order_id <= 0) {
            ds_json_encode(10001,lang('member_change_parameter_error'));
        }

        $vrorder_model = model('vrorder');

        $condition = array();
        $condition[] = array('order_id','=',$order_id);
        $condition[] = array('buyer_id','=',session('member_id'));
        $order_info = $vrorder_model->getVrorderInfo($condition);
        if (empty($order_info) && $order_info['order_state'] != ORDER_STATE_PAY) {
            ds_json_encode(10001,lang('error_order_information'));
        }
        if ($order_info['vr_send_times'] >= 5) {
            ds_json_encode(10001,lang('you_send_too_many_times'));
        }

        //发送兑换码到手机
        $param = array(
            'order_id' => $order_id, 'buyer_id' => session('member_id'), 'buyer_phone' => $order_info['buyer_phone']
        );
        $vrorder_model->sendVrCode($param);

        $vrorder_model->editVrorder(array('vr_send_times' => Db::raw('vr_send_times+1')), array('order_id' => $order_id));

        ds_json_encode(10000,lang('send_success'));
    }

    /**
     * 用户中心右边，小导航
     *
     * @param string $menu_type 导航类型
     * @param string $menu_key 当前导航的menu_key
     * @return
     */
    protected function getMemberItemList() {
        $menu_array = array(
            array(
                'name' => 'member_order', 'text' => lang('ds_member_path_order_list'),
                'url' => (string)url('Membervrorder/index')
            ),
        );
        if(request()->action()=='show_order'){
            $menu_array[] = array(
                'name' => 'show_order', 'text' => lang('member_show_order_desc'),
                'url' => 'javascript:void(0)'
            );
        }
        
        return $menu_array;
    }

}
