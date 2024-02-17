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
class SellerService extends BaseSeller {

    public function initialize() {
        parent::initialize();
        Lang::load(base_path() . 'home/lang/' . config('lang.default_lang') . '/seller_service.lang.php');
    }

    public function index() {
        $store_service_model = model('store_service');
        $condition = array();
        $condition[] = array('store_id','=',session('store_id'));
        $store_service_list = $store_service_model->getStoreServiceList($condition, '*', 10);
        View::assign('store_service_list', $store_service_list);
        View::assign('show_page', $store_service_model->page_info->render());
        /* 设置卖家当前菜单 */
        $this->setSellerCurMenu('seller_service');
        /* 设置卖家当前栏目 */
        $this->setSellerCurItem('store_service_list');
        return View::fetch($this->template_dir . 'index');
    }

    public function add() {
        if (!request()->isPost()) {
            $this->setSellerCurMenu('seller_service');
            $this->setSellerCurItem('add');
            return View::fetch($this->template_dir . 'form');
        } else {
            $store_service_model = model('store_service');
            //不能添加超过20个
            if(Db::name('store_service')->where(array(array('store_id','=',session('store_id'))))->count()>=20){
                ds_json_encode(10001, lang('store_service_count_error'));
            }
            $data = $this->post_data();
            $data['store_id'] = session('store_id');
            $store_service_validate = ds_validate('store_service');
            if (!$store_service_validate->scene('add')->check($data)) {
                ds_json_encode(10001, $store_service_validate->getError());
            }
            $result = $store_service_model->addStoreService($data);
            if ($result) {
                $this->recordSellerlog(lang('ds_new') . lang('seller_service') . '[' . $data['store_service_title'] . ']', 1);
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
        $store_service_model = model('store_service');
        $store_service_info = $store_service_model->getStoreServiceInfo(array('store_service_id' => $id, 'store_id' => session('store_id')));
        if (!$store_service_info) {
            $this->error(lang('store_service_empty'));
        }
        if (!request()->isPost()) {
            View::assign('store_service_info', $store_service_info);
            $this->setSellerCurMenu('seller_service');
            $this->setSellerCurItem('edit');
            return View::fetch($this->template_dir . 'form');
        } else {
            $data = $this->post_data();
            $store_service_validate = ds_validate('store_service');
            if (!$store_service_validate->scene('edit')->check($data)) {
                ds_json_encode(10001, $store_service_validate->getError());
            }
            $result = $store_service_model->editStoreService($data, array('store_service_id' => $id, 'store_id' => session('store_id')));
            if ($result) {
                $this->recordSellerlog(lang('ds_edit') . lang('seller_service') . '[' . $store_service_info['store_service_title'] . ']', 1);
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
        $store_service_model = model('store_service');
        $store_service_info = $store_service_model->getStoreServiceInfo(array('store_service_id' => $id, 'store_id' => session('store_id')));
        if (!$store_service_info) {
            ds_json_encode(10001, lang('store_service_empty'));
        }
        $result = $store_service_model->delStoreService(array('store_service_id' => $id, 'store_id' => session('store_id')), array($store_service_info));
        if (!$result) {
            ds_json_encode(10001, lang('ds_common_del_fail'));
        } else {
            $this->recordSellerlog(lang('ds_del') . lang('seller_service') . '[' . $store_service_info['store_service_title'] . ']', 1);
            ds_json_encode(10000, lang('ds_common_del_succ'));
        }
    }

    public function post_data() {
        $data = array(
            'store_service_title' => input('post.store_service_title'),
            'store_service_desc' => input('post.store_service_desc'),
            'store_service_sort' => input('post.store_service_sort'),
        );
        return $data;
    }


    /**
     *    栏目菜单
     */
    function getSellerItemList() {
        $menu_array[] = array(
            'name' => 'store_service_list',
            'text' => lang('seller_service'),
            'url' => url('seller_service/index'),
        );

        return $menu_array;
    }

}
