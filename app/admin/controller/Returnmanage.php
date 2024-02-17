<?php

namespace app\admin\controller;
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
class Returnmanage extends AdminControl {

    const EXPORT_SIZE = 1000;
    public function initialize() {
        parent::initialize();
        Lang::load(base_path() . 'admin/lang/'.config('lang.default_lang').'/returnmanage.lang.php');
        //向模板页面输出退款退货状态
        $this->getRefundStateArray();
    }

    function getRefundStateArray($type = 'all') {
        $state_array = array(
            '1' => lang('refund_state_confirm'),
            '2' => lang('refund_state_yes'),
            '3' => lang('refund_state_no')
        ); //卖家处理状态:1为待审核,2为同意,3为不同意
        View::assign('state_array', $state_array);

        $admin_array = array(
            '1' => lang('admin_state_1'),
            '2' => lang('admin_state_2'),
            '3' => lang('admin_state_3'), 
            '4' => lang('refund_state_no')
        ); //确认状态:1为买家或卖家处理中,2为待平台管理员处理,3为退款退货已完成
        View::assign('admin_array', $admin_array);

        $state_data = array(
            'seller' => $state_array,
            'admin' => $admin_array
        );
        if ($type == 'all') {
            return $state_data; //返回所有
        }
        return $state_data[$type];
    }

    /**
     * 待处理列表
     */
    public function return_manage() {
        $refundreturn_model = model('refundreturn');
        $condition = array();
        $condition[]=array('refund_state','=','2'); //状态:1为处理中,2为待管理员处理,3为已完成
        $keyword_type = array('order_sn', 'refund_sn', 'store_name', 'buyer_name', 'goods_name');

        $key = input('get.key');
        $type = input('get.type');
        if (trim($key) != '' && in_array($type, $keyword_type)) {
            $condition[]=array($type,'like', '%' . $key . '%');
        }
        $add_time_from = input('get.add_time_from');
        $add_time_to = input('get.add_time_to');
        if (trim($add_time_from) != '') {
            $add_time_from = strtotime(trim($add_time_from));
            if ($add_time_from !== false) {
                $condition[] = array('add_time','>=', $add_time_from);
            }
        }
        if (trim($add_time_to) != '') {
            $add_time_to = strtotime(trim($add_time_to))+86399;
            if ($add_time_to !== false) {
                $condition[] = array('add_time','<=', $add_time_to);
            }
        }
        $return_list = $refundreturn_model->getReturnList($condition, 10);

        View::assign('return_list', $return_list);
        View::assign('show_page', $refundreturn_model->page_info->render());
        
        View::assign('filtered', $condition ? 1 : 0); //是否有查询条件
        
        $this->setAdminCurItem('return_manage');
        return View::fetch('return_manage');
    }

    /**
     * 所有记录
     */
    public function return_all() {
        $refundreturn_model = model('refundreturn');
        $condition = array();

        $keyword_type = array('order_sn', 'refund_sn', 'store_name', 'buyer_name', 'goods_name');
        $key = input('get.key');
        $type = input('get.type');
        if (trim($key) != '' && in_array($type, $keyword_type)) {
            $condition[]=array($type,'like', '%' . $key . '%');
        }
        $add_time_from = input('get.add_time_from');
        $add_time_to = input('get.add_time_to');
        if (trim($add_time_from) != '') {
            $add_time_from = strtotime(trim($add_time_from));
            if ($add_time_from !== false) {
                $condition[] = array('add_time','>=', $add_time_from);
            }
        }
        if (trim($add_time_to) != '') {
            $add_time_to = strtotime(trim($add_time_to))+86399;
            if ($add_time_to !== false) {
                $condition[] = array('add_time','<=', $add_time_to);
            }
        }
        $return_list = $refundreturn_model->getReturnList($condition, 10);
        View::assign('return_list', $return_list);
        View::assign('show_page', $refundreturn_model->page_info->render());
        
        View::assign('filtered', $condition ? 1 : 0); //是否有查询条件
        $this->setAdminCurItem('return_all');
        return View::fetch('return_all');
    }

    /**
     * 退货处理页
     *
     */
    public function edit() {
        $refundreturn_model = model('refundreturn');
        $condition = array();
        $condition[] = array('refund_id','=',intval(input('param.refund_id')));
        $return = $refundreturn_model->getRefundreturnInfo($condition);
        if(empty($return)){
            $this->error(lang('param_error'));
        }
        //查询交易凭证
        $order_model = model('order');
        $order = $order_model->getOrderInfo(array('order_id' => $return['order_id']));
        if (request()->isPost()) {
            if(!in_array(input('post.refund_state'),[3,4])){
              $this->error(lang('refund_state_null'));
            }
            $check = request()->checkToken('__token__');
            if(false === $check) {
                $this->error('invalid token');
            }
            if ($return['refund_state'] != '2') {//检查状态,防止页面刷新不及时造成数据错误
                $this->error(lang('ds_common_save_fail'));
            }
            $order_id = $return['order_id'];
            $refund_array = array();
            $refund_array['admin_time'] = TIMESTAMP;
            $refund_array['refund_state'] = '4'; //状态:1为处理中,2为待管理员处理,3为已完成
            $refund_array['admin_message'] = input('post.admin_message');
            if (input('post.refund_state') == '3') {
                $trade_no=input('param.trade_no');
                if($trade_no && $trade_no!=$order['trade_no']){
                    $order_model->editOrder(array('trade_no'=>$trade_no), array(
                    'order_id' => $order['order_id']
                    ));
                    //添加订单日志
                    $data = array();
                    $data['order_id'] = $order['order_id'];
                    $data['log_role'] = 'system';
                    $data['log_user'] = $this->admin_info['admin_name'];
                    $data['log_msg'] = '修改支付平台交易号 : ' . $trade_no;
                    $data['log_orderstate'] = $order['order_state'];
                    $order_model->addOrderlog($data);
                }
                $refund_array['refund_state'] = '3';
                $res = $refundreturn_model->editOrderRefund($return);
                $state=$res['code'];
                if(!$state){
                    $this->error($res['msg']);
                }
            }else{
                if($return['order_lock'] == '2'){
                    $state = $refundreturn_model->editOrderUnlock($order_id); //订单解锁
                }else{
                    $state = true;
                }
            }
            
            if ($state) {
                $refundreturn_model->editRefundreturn($condition, $refund_array);
                $this->log('退货确认，退货编号' . $return['refund_sn']);

                // 发送买家消息
                $param = array();
                $param['code'] = 'refund_return_notice';
                $param['member_id'] = $return['buyer_id'];
                //阿里短信参数
                $param['ali_param'] = array(
                    'refund_sn' => $return['refund_sn']
                );
                $param['ten_param'] = array(
                    $return['refund_sn']
                );
                $param['param'] = array_merge($param['ali_param'],array(
                    'refund_url' => HOME_SITE_URL .'/memberreturn/view?return_id='.$return['refund_id'],
                ));
                //微信模板消息
                $param['weixin_param'] = array(
                    'url' => config('ds_config.h5_site_url').'/pages/member/return/ReturnView?refund_id='.$return['refund_id'],
                    'data'=>array(
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

                dsLayerOpenSuccess(lang('ds_common_save_succ'));
            } else {
                $this->error(lang('ds_common_save_fail'));
            }
        }
        View::assign('trade_no', $order['trade_no']);
        View::assign('return', $return);
        $info['buyer'] = array();
        if (!empty($return['pic_info'])) {
            $info = unserialize($return['pic_info']);
        }
        View::assign('pic_list', $info['buyer']);
        return View::fetch('edit');
    }

    /**
     * 退货记录查看页
     *
     */
    public function view() {
        $refundreturn_model = model('refundreturn');
        $condition = array();
        $condition[] = array('refund_id','=',intval(input('param.refund_id')));
        $return = $refundreturn_model->getRefundreturnInfo($condition);
        if(empty($return)){
            $this->error(lang('param_error'));
        }
        View::assign('return', $return);
        $info['buyer'] = array();
        if (!empty($return['pic_info'])) {
            $info = unserialize($return['pic_info']);
        }
        View::assign('pic_list', $info['buyer']);
        return View::fetch('view');
    }


    /**
     * 导出
     *
     */
    public function export_step1() {

        $refundreturn_model = model('refundreturn');
        $condition = array();

        $keyword_type = array('order_sn', 'refund_sn', 'store_name', 'buyer_name', 'goods_name');
        $key = input('get.key');
        $type = input('get.type');
        if (trim($key) != '' && in_array($type, $keyword_type)) {
            $condition[]=array($type,'like', '%' . $key . '%');
        }
        $add_time_from = input('get.add_time_from');
        $add_time_to = input('get.add_time_to');
        if (trim($add_time_from) != '') {
            $add_time_from = strtotime(trim($add_time_from));
            if ($add_time_from !== false) {
                $condition[] = array('add_time','>=', $add_time_from);
            }
        }
        if (trim($add_time_to) != '') {
            $add_time_to = strtotime(trim($add_time_to))+86399;
            if ($add_time_to !== false) {
                $condition[] = array('add_time','<=', $add_time_to);
            }
        }
        if (!is_numeric(input('param.page'))) {
            $count = $refundreturn_model->getReturnCount($condition);
            $export_list = array();
            if ($count > self::EXPORT_SIZE) { //显示下载链接
                $page = ceil($count / self::EXPORT_SIZE);
                for ($i = 1; $i <= $page; $i++) {
                    $limit1 = ($i - 1) * self::EXPORT_SIZE + 1;
                    $limit2 = $i * self::EXPORT_SIZE > $count ? $count : $i * self::EXPORT_SIZE;
                    $export_list[$i] = $limit1 . ' ~ ' . $limit2;
                }
                View::assign('export_list', $export_list);
                return View::fetch('/public/excel');
            } else { //如果数量小，直接下载
                $data = $refundreturn_model->getReturnList($condition, '', '*', 'refund_id desc', self::EXPORT_SIZE);
                $this->createExcel($data);
            }
        } else { //下载
            $limit1 = (input('param.page') - 1) * self::EXPORT_SIZE;
            $limit2 = self::EXPORT_SIZE;
            $data = $refundreturn_model->getReturnList($condition, $limit2, '*', 'refund_id desc');
            $this->createExcel($data);
        }
    }

    /**
     * 生成excel
     *
     * @param array $data
     */
    private function createExcel($data = array()) {
        Lang::load(base_path() .'admin/lang/'.config('lang.default_lang').'/export.lang.php');
        $excel_obj = new \excel\Excel();
        $excel_data = array();
        //设置样式
        $excel_obj->setStyle(array('id' => 's_title', 'Font' => array('FontName' => '宋体', 'Size' => '12', 'Bold' => '1')));
        //header
        $excel_data[0][] = array('styleid' => 's_title', 'data' => lang('ds_order_sn'));
        $excel_data[0][] = array('styleid' => 's_title', 'data' => lang('exp_th_order_returnsn'));
        $excel_data[0][] = array('styleid' => 's_title', 'data' => lang('ds_store_name'));
        $excel_data[0][] = array('styleid' => 's_title', 'data' => lang('ds_goods_name'));
        $excel_data[0][] = array('styleid' => 's_title', 'data' => lang('ds_buyer_name'));
        $excel_data[0][] = array('styleid' => 's_title', 'data' => lang('exp_th_add_time'));
        $excel_data[0][] = array('styleid' => 's_title', 'data' => lang('exp_th_refund_amount'));
        $excel_data[0][] = array('styleid' => 's_title', 'data' => lang('exp_th_goods_num'));
        //data
        foreach ((array) $data as $k => $v) {
            $tmp = array();
            $tmp[] = array('data' => 'DS' . $v['order_sn']);
            $tmp[] = array('data' => $v['refund_sn']);
            $tmp[] = array('data' => $v['store_name']);
            $tmp[] = array('data' => $v['goods_name']);
            $tmp[] = array('data' => $v['buyer_name']);
            $tmp[] = array('data' => date('Y-m-d H:i:s', $v['add_time']));
            $tmp[] = array('format' => 'Number', 'data' => ds_price_format($v['refund_amount']));
            $tmp[] = array('data' => $v['goods_num']);
            $excel_data[] = $tmp;
        }
        $excel_data = $excel_obj->charset($excel_data, CHARSET);
        $excel_obj->addArray($excel_data);
        $excel_obj->addWorksheet($excel_obj->charset(lang('exp_th_return'), CHARSET));
        $excel_obj->generateXML($excel_obj->charset(lang('exp_th_return'), CHARSET) . input('param.page') . '-' . date('Y-m-d-H', TIMESTAMP));
    }

    /**
     * 获取卖家栏目列表,针对控制器下的栏目
     */
    protected function getAdminItemList() {
        $menu_array = array(
            array(
                'name' => 'return_manage',
                'text' => lang('refund_state_confirm'),
                'url' => (string)url('Returnmanage/return_manage')
            ),
            array(
                'name' => 'return_all',
                'text' => lang('refund_all'),
                'url' => (string)url('Returnmanage/return_all')
            ),
        );
        if(request()->action() == 'edit') {
            $menu_array[] = array(
                'name' => 'edit', 'text' => lang('ds_verify'), 'url' => 'javascript:void(0)',
            );
        }
        return $menu_array;
    }

}

?>
