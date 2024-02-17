<?php

namespace app\home\controller;

use think\facade\View;
use think\facade\Lang;
use think\facade\Db;
/**
 * ============================================================================
 * DSO2O多用户商城
 * ============================================================================
 * 版权所有 2014-2028 长沙德尚网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.csdeshang.com
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用 .
 * 不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * 控制器
 */
class SellerChain extends BaseSeller {

    public function initialize() {
        parent::initialize();
        Lang::load(base_path() . 'home/lang/' . config('lang.default_lang') . '/seller_chain.lang.php');
    }

    public function index() {
        $chain_model = model('chain');
        $condition = array();
        $search_field_value = input('search_field_value');
        $search_field_name = input('search_field_name');
        if ($search_field_value != '') {
            switch ($search_field_name) {
                case 'chain_name':
                    $condition[] = array('chain_name','like', '%' . trim($search_field_value) . '%');
                    break;
                case 'chain_truename':
                    $condition[] = array('chain_truename','like', '%' . trim($search_field_value) . '%');
                    break;
                case 'chain_mobile':
                    $condition[] = array('chain_mobile','like', '%' . trim($search_field_value) . '%');
                    break;
                case 'chain_addressname':
                    $condition[] = array('chain_addressname','like', '%' . trim($search_field_value) . '%');
                    break;
            }
        }
        $search_state = input('search_state');
        switch ($search_state) {
            case '1':
                $condition[] = array('chain_state','=','1');
                break;
            case '0':
                $condition[] = array('chain_state','=','0');
                break;
        }
        $filtered = 0;
        if ($condition) {
            $filtered = 1;
        }

        $condition[] = array('store_id','=',session('store_id'));
        $order='chain_addtime desc';

        $chain_list = $chain_model->getChainList($condition, 10, $order);
        View::assign('chain_list', $chain_list);
        View::assign('show_page', $chain_model->page_info->render());
        View::assign('search_field_name', trim($search_field_name));
        View::assign('search_field_value', trim($search_field_value));
        /* 设置卖家当前菜单 */
        $this->setSellerCurMenu('seller_chain');
        /* 设置卖家当前栏目 */
        $this->setSellerCurItem('chain_list');
        return View::fetch($this->template_dir . 'index');
    }

    public function add() {
        if (!request()->isPost()) {
            View::assign('baidu_ak', config('ds_config.baidu_ak'));
            $this->setSellerCurMenu('seller_chain');
            $this->setSellerCurItem('add');
            return View::fetch($this->template_dir . 'form');
        } else {
            $chain_model = model('chain');
            //不能添加超过20个
            if(Db::name('chain')->where(array(array('store_id','=',session('store_id'))))->count()>=20){
                ds_json_encode(10001, lang('chain_count_error'));
            }
            $data = $this->post_data();
            $data['store_id'] = session('store_id');
            $data['chain_name'] = input('post.chain_name');
            $data['chain_addtime'] = TIMESTAMP;
            $chain_validate = ds_validate('chain');
            if (!$chain_validate->scene('chain_add')->check($data)) {
                ds_json_encode(10001, $chain_validate->getError());
            }
            $condition = array();
            $condition[] = array('chain_name','=',$data['chain_name']);
            $result = $chain_model->getChainInfo($condition);
            if ($result) {
                ds_json_encode(10001, lang('chain_name_remote'));
            }
            $data['chain_passwd'] = md5($data['chain_passwd']);
            $result = $chain_model->addChain($data);
            if ($result) {
                $this->recordSellerlog(lang('ds_new') . lang('seller_chain') . '[' . $data['chain_name'] . ']', 1);
                ds_json_encode(10000, lang('ds_common_save_succ'));
            } else {
                ds_json_encode(10001, lang('ds_common_save_fail'));
            }
        }
    }

    public function edit() {
        $id = intval(input('param.id'));
        if (!$id) {
            $this->error(lang('param_error'));
        }
        $chain_model = model('chain');
        $chain_array = $chain_model->getChainInfo(array('chain_id' => $id, 'store_id' => session('store_id')));
        if (!$chain_array) {
            $this->error(lang('chain_empty'));
        }
        if (!request()->isPost()) {
            View::assign('baidu_ak', config('ds_config.baidu_ak'));
            View::assign('chain_array', $chain_array);
            $this->setSellerCurMenu('seller_chain');
            $this->setSellerCurItem('edit');
            return View::fetch($this->template_dir . 'form');
        } else {

            $data = $this->post_data();


            $chain_validate = ds_validate('chain');
            if (!$chain_validate->scene('chain_edit')->check($data)) {
                ds_json_encode(10001, $chain_validate->getError());
            }
            if (isset($data['chain_passwd'])) {
                $data['chain_passwd'] = md5($data['chain_passwd']);
            }
            $result = $chain_model->editChain($data, array('chain_id' => $id, 'store_id' => session('store_id')));
            if ($result) {
                $this->recordSellerlog(lang('ds_edit') . lang('seller_chain') . '[' . $chain_array['chain_name'] . ']', 1);
                ds_json_encode(10000, lang('ds_common_save_succ'));
            } else {
                ds_json_encode(10001, lang('ds_common_save_fail'));
            }
        }
    }

    public function del() {
        $id = intval(input('param.id'));
        if (!$id) {
            ds_json_encode(10001, lang('param_error'));
        }
        $chain_model = model('chain');
        $chain_array = $chain_model->getChainInfo(array('chain_id' => $id, 'store_id' => session('store_id')));
        if (!$chain_array) {
            ds_json_encode(10001, lang('chain_empty'));
        }
        //如果有正在配送的订单则不能删除
        $chain_order_model=model('chain_order');
        if($chain_order_model->getChainOrderInfo(array(array('chain_id','=',$id),array('chain_order_state','not in',[ORDER_STATE_CANCEL,ORDER_STATE_SUCCESS])))){
            ds_json_encode(10001, lang('chain_drop_error'));
        }
        $result = $chain_model->delChain(array('chain_id' => $id, 'store_id' => session('store_id')), array($chain_array));
        if (!$result) {
            ds_json_encode(10001, lang('ds_common_del_fail'));
        } else {
            $this->recordSellerlog(lang('ds_del') . lang('seller_chain') . '[' . $chain_array['chain_name'] . ']', 1);
            ds_json_encode(10000, lang('ds_common_del_succ'));
        }
    }

    public function post_data() {
        $data = array(
            'chain_truename' => input('post.chain_truename'),
            'chain_mobile' => input('post.chain_mobile'),
            'chain_addressname' => input('post.chain_addressname'),
            'chain_telephony' => input('post.chain_telephony'),
            'chain_area_2' => input('post.chain_area_2'),
            'chain_area_3' => input('post.chain_area_3'),
            'chain_area_info' => input('post.chain_area_info'),
            'chain_state' => intval(input('post.chain_state')),
            'chain_address' => input('post.chain_address'),
            'chain_longitude' => input('post.chain_longitude'),
            'chain_latitude' => input('post.chain_latitude'),
            'chain_if_pickup' => input('post.chain_if_pickup'),
            'chain_if_collect' => input('post.chain_if_collect'),
        );
        
        if (input('post.chain_passwd')) {
            $data['chain_passwd'] = input('post.chain_passwd');
        }

        return $data;
    }

    public function ajax() {
        $chain_model = model('chain');
        switch (input('param.branch')) {
            /**
             * 品牌名称
             */
            case 'chain_name':
                /**
                 * 判断是否有重复
                 */
                $condition = array();
                $condition[] = array('chain_name','=',trim(input('param.value')));
                $condition[] = array('chain_id','<>',intval(input('param.id')));
                $result = $chain_model->getChainInfo($condition);
                if (empty($result)) {
                    echo 'true';
                    exit;
                } else {
                    echo 'false';
                    exit;
                }
                break;
        }
    }

    /**
     *    栏目菜单
     */
    function getSellerItemList() {
        $menu_array[] = array(
            'name' => 'chain_list',
            'text' => lang('seller_chain_list'),
            'url' => url('seller_chain/index'),
        );

        return $menu_array;
    }

}
