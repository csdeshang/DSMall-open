<?php

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
class Sellerreturn extends BaseSeller {

    public function initialize() {
        parent::initialize();
        Lang::load(base_path() . 'home/lang/' . config('lang.default_lang') . '/sellerreturn.lang.php');
    }

    /**
     * 退货记录列表页
     *
     */
    public function index() {
        $refundreturn_model = model('refundreturn');
        $condition = array();
        $condition[] = array('store_id', '=', session('store_id'));
        $condition[] = array('refund_type', '=', '2'); //类型:1为退款,2为退货
        $keyword_type = array('order_sn', 'refund_sn', 'buyer_name');

        $keyword = input('get.keyword');
        $type = input('get.type');
        if (trim($keyword) != '' && in_array($type, $keyword_type)) {
            $condition[] = array($type, 'like', '%' . $keyword . '%');
        }
        $add_time_from = input('get.add_time_from');
        $add_time_to = input('get.add_time_to');
        if (trim($add_time_from) != '') {
            $add_time_from=strtotime($add_time_from);
            if ($add_time_from !== false) {
                $condition[] = array('refundreturn_add_time', '>=', $add_time_from);
            }
        }
        if (trim($add_time_to) != '') {
            $add_time_to=strtotime($add_time_to)+86399;
            if ($add_time_to !== false) {
                $condition[] = array('refundreturn_add_time', '<=', $add_time_to);
            }
        }
        $refundreturn_seller_state = intval(input('get.state'));
        if ($refundreturn_seller_state > 0) {
            $condition[] = array('refundreturn_seller_state', '=', $refundreturn_seller_state);
        }
        $return_list = $refundreturn_model->getReturnList($condition, 10);

        View::assign('return_list', $return_list);
        //分页
        $show_page = $refundreturn_model->page_info->render();
        View::assign('show_page', $show_page);

        /* 设置卖家当前菜单 */
        $this->setSellerCurMenu('seller_refundreturn');
        /* 设置卖家当前栏目 */
        $this->setSellerCurItem('seller_return');

        return View::fetch($this->template_dir . 'index');
    }

    /**
     * 退货审核页
     */
    public function edit() {
        $refundreturn_model = model('refundreturn');
        $condition = array();
        $condition[] = array('store_id','=',session('store_id'));
        $condition[] = array('refund_id','=',intval(input('param.return_id')));
        $return = $refundreturn_model->getRefundreturnInfo($condition);
        if (empty($return)) {
            $this->error(lang('param_error'));
        }
        if (!request()->isPost()) {
            View::assign('return', $return);
            $info['buyer'] = array();
            if (!empty($return['pic_info'])) {
                $info = unserialize($return['pic_info']);
            }
            View::assign('pic_list', $info['buyer']);
//            View::assign('pic_list', '');
            $member_model = model('member');
            $member = $member_model->getMemberInfoByID($return['buyer_id']);
            View::assign('member', $member);
            $condition = array();
            $condition[] = array('order_id','=',$return['order_id']);
            $order = $refundreturn_model->getRightOrderList($condition, $return['order_goods_id']);
            View::assign('order', $order);
            View::assign('store', $order['extend_store']);
            View::assign('order_common', $order['extend_order_common']);
            View::assign('goods_list', $order['goods_list']);

            /* 设置卖家当前菜单 */
            $this->setSellerCurMenu('seller_refundreturn');
            /* 设置卖家当前栏目 */
            $this->setSellerCurItem('seller_return');
            return View::fetch($this->template_dir . 'edit');
        } else {
            if ($return['refundreturn_seller_state'] != '1') {
                ds_json_encode(10001, lang('param_error'));
            }
            $order_id = $return['order_id'];
            $refund_array = array();
            $refund_array['refundreturn_seller_time'] = TIMESTAMP;
            $refund_array['refundreturn_seller_state'] = input('post.refundreturn_seller_state'); //卖家处理状态:1为待审核,2为同意,3为不同意
            $refund_array['refundreturn_seller_message'] = input('post.refundreturn_seller_message');

            if ($refund_array['refundreturn_seller_state'] == '2' && empty(input('post.return_type'))) {
                $refund_array['return_type'] = '2'; //退货类型:1为不用退货,2为需要退货
            } elseif ($refund_array['refundreturn_seller_state'] == '3') {
                $refund_array['refundreturn_admin_state'] = '3'; //状态:1为处理中,2为待管理员处理,3为已完成
            } else {
                $refund_array['refundreturn_seller_state'] = '2';
                $refund_array['refundreturn_admin_state'] = '2';
                $refund_array['return_type'] = '1'; //选择弃货
            }
            $state = $refundreturn_model->editRefundreturn($condition, $refund_array);
            if ($state) {
                if ($refund_array['refundreturn_seller_state'] == '3') {
                    $refundreturn_model->editOrderUnlock($order_id); //订单解锁
                }
                $this->recordSellerlog(lang('any_returns') . $return['refund_sn']);

                // 发送买家消息
                $param = array();
                $param['code'] = 'refund_return_notice';
                $param['member_id'] = $return['buyer_id'];
                $param['ali_param'] = array(
                    'refund_sn' => $return['refund_sn']
                );
                $param['ten_param'] = array(
                    $return['refund_sn']
                );
                $param['param'] = array_merge($param['ali_param'], array(
                    'refund_url' => HOME_SITE_URL .'/Memberreturn/view?return_id='.$return['refund_id'],
                ));
                //微信模板消息
                $param['weixin_param'] = array(
                    'url' => config('ds_config.h5_site_url') . '/pages/member/return/ReturnView?refund_id=' . $return['refund_id'],
                    'data' => array(
                        "keyword1" => array(
                            "value" => $return['order_sn'],
                            "color" => "#333"
                        ),
                        "keyword2" => array(
                            "value" => $return['refund_amount'],
                            "color" => "#333"
                        )
                    ),
                );
                model('cron')->addCron(array('cron_exetime'=>TIMESTAMP,'cron_type'=>'sendMemberMsg','cron_value'=>serialize($param)));

                ds_json_encode(10000, lang('ds_common_save_succ'));
            } else {
                ds_json_encode(10001, lang('ds_common_save_fail'));
            }
        }
    }

    /**
     * 收货
     *
     */
    public function receive() {
        $refundreturn_model = model('refundreturn');
        $trade_model = model('trade');
        $condition = array();
        $condition[] = array('store_id','=',session('store_id'));
        $condition[] = array('refund_id','=',intval(input('param.return_id')));
        $return = $refundreturn_model->getRefundreturnInfo($condition);
        if (empty($return)) {
            $this->error(lang('param_error'));
        }
        View::assign('return', $return);
        $return_delay = $trade_model->getMaxDay('return_delay'); //发货默认5天后才能选择没收到
        $refundreturn_delay_time = TIMESTAMP - $return['refundreturn_delay_time'] - 60 * 60 * 24 * $return_delay;
        View::assign('return_delay', $return_delay);
        View::assign('return_confirm', $trade_model->getMaxDay('return_confirm')); //卖家不处理收货时按同意并弃货处理
        View::assign('refundreturn_delay_time', $refundreturn_delay_time);
        if (!request()->isPost()) {
            $express_list = rkcache('express', true);
            if ($return['express_id'] > 0 && !empty($return['invoice_no'])) {
                View::assign('express_name', isset($express_list[$return['express_id']])?$express_list[$return['express_id']]['express_name']:'');
                View::assign('express_code', isset($express_list[$return['express_id']])?$express_list[$return['express_id']]['express_code']:'');
            }
            return View::fetch($this->template_dir . 'receive');
        } else {

            if ($return['refundreturn_seller_state'] != '2' || $return['refundreturn_goods_state'] != '2') {//检查状态,防止页面刷新不及时造成数据错误
                ds_json_encode(10001, lang('param_error'));
            }
            $refund_array = array();
            if (input('post.return_type') == '3' && $refundreturn_delay_time > 0) {
                $refund_array['refundreturn_goods_state'] = '3';
            } else {
                $refund_array['refundreturn_receive_time'] = TIMESTAMP;
                $refund_array['refundreturn_receive_message'] = lang('confirm_receipt_goods_completed');
                $refund_array['refundreturn_admin_state'] = '2'; //状态:1为处理中,2为待管理员处理,3为已完成
                $refund_array['refundreturn_goods_state'] = '4';
            }
            $state = $refundreturn_model->editRefundreturn($condition, $refund_array);
            if ($state) {
                $this->recordSellerlog(lang('confirm_receipt_goods_returned') . $return['refund_sn']);

                // 发送买家消息
                $param = array();
                $param['code'] = 'refund_return_notice';
                $param['member_id'] = $return['buyer_id'];
                $param['ali_param'] = array(
                    'refund_sn' => $return['refund_sn']
                );
                $param['ten_param'] = array(
                    $return['refund_sn']
                );
                $param['param'] = array_merge($param['ali_param'], array(
                    'refund_url' => HOME_SITE_URL .'/Memberreturn/view?return_id='.$return['refund_id'],
                ));
                //微信模板消息
                $param['weixin_param'] = array(
                    'url' => config('ds_config.h5_site_url') . '/pages/member/return/ReturnView?refund_id=' . $return['refund_id'],
                    'data' => array(
                        "keyword1" => array(
                            "value" => $return['order_sn'],
                            "color" => "#333"
                        ),
                        "keyword2" => array(
                            "value" => $return['refund_amount'],
                            "color" => "#333"
                        )
                    ),
                );
                model('cron')->addCron(array('cron_exetime'=>TIMESTAMP,'cron_type'=>'sendMemberMsg','cron_value'=>serialize($param)));
                ds_json_encode(10000, lang('ds_common_save_succ'));
            } else {
                ds_json_encode(10001, lang('ds_common_save_fail'));
            }
        }
    }

    /**
     * 退货记录查看页
     *
     */
    public function view() {
        $refundreturn_model = model('refundreturn');
        $condition = array();
        $condition[] = array('store_id','=',session('store_id'));
        $condition[] = array('refund_id','=',intval(input('param.return_id')));
        $return = $refundreturn_model->getRefundreturnInfo($condition);
        if (empty($return)) {
            $this->error(lang('param_error'));
        }
        View::assign('return', $return);
        $express_list = rkcache('express', true);
        if ($return['express_id'] > 0 && !empty($return['invoice_no'])) {
            View::assign('express_name', isset($express_list[$return['express_id']])?$express_list[$return['express_id']]['express_name']:'');
            View::assign('express_code', isset($express_list[$return['express_id']])?$express_list[$return['express_id']]['express_code']:'');
        }
        $info['buyer'] = array();
        if (!empty($return['pic_info'])) {
            $info = unserialize($return['pic_info']);
        }
        View::assign('pic_list', $info['buyer']);
//        View::assign('pic_list', '');
        $member_model = model('member');
        $member = $member_model->getMemberInfoByID($return['buyer_id']);
        View::assign('member', $member);
        $condition = array();
        $condition[] =array('order_id','=',$return['order_id']);
        $order = $refundreturn_model->getRightOrderList($condition, $return['order_goods_id']);
        View::assign('order', $order);
        View::assign('store', $order['extend_store']);
        View::assign('order_common', $order['extend_order_common']);
        View::assign('goods_list', $order['goods_list']);
        /* 设置卖家当前菜单 */
        $this->setSellerCurMenu('seller_refundreturn');
        /* 设置卖家当前栏目 */
        $this->setSellerCurItem('seller_return');
        return View::fetch($this->template_dir . 'view');
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
                'name' => 'seller_refund',
                'text' => '退款',
                'url' => (string) url('Sellerrefund/index')
            ),
            array(
                'name' => 'seller_return',
                'text' => '退货',
                'url' => (string) url('Sellerreturn/index')
            ),
        );
        return $menu_array;
    }

}
