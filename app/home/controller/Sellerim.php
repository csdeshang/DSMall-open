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
class Sellerim extends BaseSeller
{
    public function initialize()
    {
        parent::initialize();
        Lang::load(base_path() . 'home/lang/'.config('lang.default_lang').'/sellerim.lang.php');
        $add_time_to = date("Y-m-d",strtotime("+1 day"));
        $time_from = array();
        $time_from['7'] = strtotime($add_time_to) - 60 * 60 * 24 * 7;
        $time_from['60'] = strtotime($add_time_to) - 60 * 60 * 24 * 60;
        $add_time_from = date("Y-m-d", $time_from['60']);
        View::assign('minDate', $add_time_from);//只能查看2个月内数据
        View::assign('maxDate', $add_time_to);
        $timefrom = input('param.add_time_from');
        if (empty($timefrom) || $timefrom < $add_time_from) {
            $timefrom = date("Y-m-d", $time_from['7']);
        }
        $timeto =input('param.add_time_to');
        if (empty($timeto) || $timeto > $add_time_to) {
            $timeto = $add_time_to;
        }
    }

    /**
     * 查询页
     *
     */
    public function index()
    {
        $seller_model = model('seller');
        $condition = array();
        $condition[] = array('seller.store_id','=',session('store_id'));
        $seller_list = $seller_model->getSellerList($condition, 'seller_id asc');//账号列表
        View::assign('seller_list', $seller_list);

        $seller_id = session('seller_id');
        //halt($seller_id);
        View::assign('seller_id', $seller_id);
        $this->setSellerCurMenu('Sellerim');
        $this->setSellerCurItem('index');
       
       return View::fetch($this->template_dir.'index');
    }

    /**
     * 聊天记录查看页
     *
     */
    public function get_chat_log()
    {
        $seller_model = model('seller');
        $special_condition = array();
        $special_condition['store_id'] = session('store_id');
        $special_condition['seller_id'] = input('param.seller_id');
        $seller = $seller_model->getSellerInfo($special_condition);//账号
        View::assign('seller', $seller);
        if ($seller['member_id'] > 0) {//验证商家账号
            $webchat_model = model('webchat');
            $special_condition['add_time_from'] = trim(input('param.add_time_from'));
            $special_condition['add_time_to'] = trim(input('param.add_time_to'));
            $special_condition['f_id'] = intval($seller['member_id']);
            $special_condition['t_id'] = intval(input('param.t_id'));
            $special_condition['t_msg'] = trim(input('param.msg_key'));
            $webchat_list = $webchat_model->getChatlogFromList($special_condition, 15);
            foreach($webchat_list as $key => $val){
                $webchat_list[$key]['t_msg']=htmlspecialchars_decode($val['t_msg']);
            }
            $webchat_list = array_reverse($webchat_list);
            View::assign('webchat_list', $webchat_list);
            View::assign('show_page', $webchat_model->page_info->render());
        }
       echo View::fetch($this->template_dir.'chat_log');
    }

    /**
     * 最近联系人
     *
     */
    public function get_user_list()
    {
        $seller_model = model('seller');
        $condition = array();
        $condition[] = array('store_id','=',session('store_id'));
        $condition[] = array('seller_id','=',input('param.seller_id'));
        $seller = $seller_model->getSellerInfo($condition);//账号
        $member_list = array();
        if ($seller['member_id'] > 0) {//验证商家账号
            $webchat_model = model('webchat');
            $add_time_to = TIMESTAMP;
            $add_time_from = $add_time_to - 60 * 60 * 24 * 60;
            $add_time_to=$add_time_to+86399;
            $condition = array();
            $condition[] = array('chatmsg_addtime','between',array($add_time_from, $add_time_to));
            $condition[] = array('f_id','=',$seller['member_id']);
            $member_list = $webchat_model->getRecentList($condition, 100, $member_list);
            $condition = array();
            $condition[] = array('chatmsg_addtime','between',array($add_time_from, $add_time_to));
            $condition[] = array('t_id','=',$seller['member_id']);
            $member_list = $webchat_model->getRecentFromList($condition, 100, $member_list);
            View::assign('member_list', $member_list);
        }
       echo View::fetch($this->template_dir.'chat_user');exit;
    }

    /**
     * 小导航
     *
     * @param string $menu_type 导航类型
     * @param string $menu_key 当前导航的menu_key
     * @return
     */
    protected function getSellerItemList()
    {
        $menu_array = array(
            array(
                'name' => 'index', 'text' => lang('chat_query'), 'url' => (string)url('Sellerim/index'),
            ),
        );
        return $menu_array;
    }
}