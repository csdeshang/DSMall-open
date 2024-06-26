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
class Membervrrefund extends BaseMember
{
    public function initialize()
    {
        parent::initialize(); // TODO: Change the autogenerated stub
        Lang::load(base_path().'home/lang/'.config('lang.default_lang').'/memberrefund.lang.php');
    }

    /**
     * 添加兑换码退款
     *
     */
    public function add_refund()
    {
        $vrrefund_model = model('vrrefund');
        $order_id = intval(input('param.order_id'));
        if ($order_id < 1) {//参数验证
            ds_json_encode(10001,lang('param_error'));
        }
        $condition = array();
        $condition[] = array('buyer_id','=',session('member_id'));
        $condition[] = array('order_id','=',$order_id);
        $order_info = $vrrefund_model->getRightVrorderList($condition,$order_id);
        View::assign('store',$order_info['store']);
        View::assign('code_list',$order_info['code_list']);
        $order=$order_info['order_info'];
        View::assign('order',$order);
        if (!$order['if_refund']) {//检查状态,防止页面刷新不及时造成数据错误
            ds_json_encode(10001,lang('param_error'));
        }
        if (request()->isPost() && $order['if_refund']) {
            $code_list = $order['code_list'];
            $refund_array = array();
            $goods_num = 0; //兑换码数量
            $refund_amount = 0; //退款金额
            $redeemcode_sn = '';
            $rec_id_array = input('post.rec_id/a');
            if (!empty($rec_id_array) && is_array($rec_id_array)) {//选择退款的兑换码
                foreach ($rec_id_array as $key => $value) {
                    $code = $code_list[$value];
                    if (!empty($code)) {
                        $goods_num += 1;
                        $refund_amount += $code['pay_price']; //实际支付金额
                        $redeemcode_sn .= $code['vr_code'] . ','; //兑换码编号
                    }
                }
            }
            if ($goods_num < 1) {
                ds_json_encode(10001,lang('param_error'));
            }
            $refund_array['redeemcode_sn'] = rtrim($redeemcode_sn, ',');
            $refund_array['admin_state'] = '1'; //状态:1为待审核,2为同意,3为不同意
            $refund_array['refund_amount'] = ds_price_format($refund_amount);
            $refund_array['goods_num'] = $goods_num;
            $refund_array['buyer_message'] = input('post.buyer_message');
            $refund_array['add_time'] = TIMESTAMP;
            $state = $vrrefund_model->addVrrefund($refund_array, $order);

            if ($state) {
                ds_json_encode(10000,lang('ds_common_save_succ'));
            } else {
                ds_json_encode(10001,lang('ds_common_save_fail'));
            }
        } else {
            $this->setMemberCurMenu('member_refund');
            $this->setMemberCurItem('add_refund');
            return View::fetch($this->template_dir . 'member_vr_refund_add');
        }
    }

    /**
     * 退款记录列表页
     *
     */
    public function index()
    {
        $condition = array();
        $condition[]=array('buyer_id','=',session('member_id'));

        $keyword_type = array('order_sn', 'refund_sn', 'goods_name');
        if (trim(input('param.key')) != '' && in_array(input('param.type'), $keyword_type)) {
            $type = input('param.type');
            $condition[] = array($type,'like', '%' . input('param.key') . '%');
        }
        if (trim(input('param.add_time_from')) != '') {
            $add_time_from = strtotime(input('param.add_time_from'));
            if ($add_time_from !== false) {
                $condition[] = array('add_time','>=', $add_time_from);
            }
        }
        if (trim(input('param.add_time_to')) != '') {
            $add_time_to = strtotime(input('param.add_time_to'));
            if ($add_time_to !== false) {
                $add_time_to=$add_time_to+86399;
                $condition[] = array('add_time','<=', $add_time_to);
            }
        }
        $vrrefund_model = model('vrrefund');
        $refund_list = $vrrefund_model->getVrrefundList($condition, 10);
        View::assign('refund_list', $refund_list);
        View::assign('show_page', $vrrefund_model->page_info->render());
        $store_list = $vrrefund_model->getVrrefundStoreList($refund_list);
        View::assign('store_list', $store_list);
        $this->setMemberCurItem('buyer_vr_refund');
        $this->setMemberCurMenu('membervrrefund');
       return View::fetch($this->template_dir.'member_vr_refund');
    }

    /**
     * 退款记录查看
     *
     */
    public function view()
    {
        $vrrefund_model = model('vrrefund');
        $condition = array();
        $condition[] = array('buyer_id','=',session('member_id'));
        $condition[] = array('refund_id','=',intval(input('param.refund_id')));
        $refund = $vrrefund_model->getOneVrrefund($condition);
        if(empty($refund)){
            $this->error(lang('param_error'));
        }
        
        View::assign('refund', $refund);
        $code_array = explode(',', $refund['redeemcode_sn']);
        View::assign('code_array', $code_array);
        $condition = array();
        $condition[] = array('order_id','=',$refund['order_id']);
        $order_info=$vrrefund_model->getRightVrorderList($condition,$refund['order_id']);
        View::assign('store',$order_info['store']);
        View::assign('code_list',$order_info['code_list']);
        View::assign('order',$order_info['order_info']);
        $this->setMemberCurMenu('membervrrefund');
        $this->setMemberCurItem('view');
       return View::fetch($this->template_dir.'member_vr_refund_view');
    }

    protected function getMemberItemList()
    {
        $menu_array = array(
            array(
                'name' => 'buyer_refund', 'text' => lang('ds_member_path_buyer_refund'),
                'url' => (string)url('Memberrefund/index')
            ), array(
                'name' => 'buyer_return', 'text' => lang('ds_member_path_buyer_return'),
                'url' => (string)url('Memberreturn/index')
            ), array(
                'name' => 'buyer_vr_refund', 'text' => lang('refund_virtual_currency_code'), 'url' => (string)url('Membervrrefund/index')
            )
        );
        return $menu_array;
    }

}