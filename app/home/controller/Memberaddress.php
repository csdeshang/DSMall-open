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
class Memberaddress extends BaseMember {

    public function initialize() {
        parent::initialize();
        Lang::load(base_path() . 'home/lang/'.config('lang.default_lang').'/memberaddress.lang.php');
    }

    /*
     * 收货地址列表
     */

    public function index() {
        $address_model=model('address');
        $chain_model = model('chain');
        $address_list = $address_model->getAddressList(array('member_id'=>session('member_id')));
        foreach($address_list as $key => $val){
            $address_list[$key]['cityerror'] = '';
            if($val['chain_id'] > 0){
                $condition = array();
                $condition[] = array('chain_id','=',$val['chain_id']);
                $chain_info = $chain_model->getChainInfo($condition,'chain_area_2,chain_area_3');
                if($val['city_id'] !== $chain_info['chain_area_2'] || $val['area_id'] !== $chain_info['chain_area_3']){
                    $address_list[$key]['cityerror'] = lang('city_fail');
                }
            }
        }
        View::assign('address_list', $address_list);

        /* 设置买家当前菜单 */
        $this->setMemberCurMenu('member_address');
        /* 设置买家当前栏目 */
        $this->setMemberCurItem('my_address');
        return View::fetch($this->template_dir . 'index');
    }

    public function add() {
        if (!request()->isPost()) {
            $area_mod=model('area');
            $region_list = $area_mod->getAreaList(array('area_parent_id'=>'0'));
            View::assign('region_list', $region_list);
            $address = array(
                'address_realname' => '',
                'area_id' => '',
                'city_id' => '',
                'address_detail' => '',
                'address_tel_phone' => '',
                'address_mob_phone' => '',
                'address_is_default' => '',
                'area_info' => '',
                'address_longitude' => '',
                'address_latitude' => '',
            );
            View::assign('address', $address);
            /* 设置买家当前菜单 */
            $this->setMemberCurMenu('member_address');
            /* 设置买家当前栏目 */
            $this->setMemberCurItem('my_address_add');
            View::assign('baidu_ak', config('ds_config.baidu_ak'));
            return View::fetch($this->template_dir . 'form');
        } else {
            $address_is_default = input('post.is_default') == 1 ? 1 : 0;
            $data = array(
                'member_id' => session('member_id'),
                'address_realname' => input('post.true_name'),
                'area_id' => input('post.area_id'),
                'city_id' => input('post.city_id'),
                'address_detail' => input('post.address'),
                'address_longitude' => input('post.longitude'),
                'address_latitude' => input('post.latitude'),
                'address_tel_phone' => input('post.tel_phone'),
                'address_mob_phone' => input('post.mob_phone'),
                'address_is_default' => $address_is_default,
                'area_info' => input('post.area_info'),
            );
            $memberaddress_validate = ds_validate('memberaddress');
            if (!$memberaddress_validate->scene('add')->check($data)) {
                ds_json_encode(10001,$memberaddress_validate->getError());
            }

            //当默认地址为1时,把当前用户的地址设置为非默认地址
            if ($address_is_default == 1) {
                model('address')->editAddress(array('address_is_default' => 0), array('member_id' => session('member_id')));
            }
            
            $address_model=model('address');
            $result = $address_model->addAddress($data);
            if ($result) {
                ds_json_encode(10000,lang('ds_common_save_succ'));
            } else {
                ds_json_encode(10001,lang('ds_common_save_fail'));
            }
        }
    }

    public function edit() {

        $address_id = intval(input('param.address_id'));
        if (0 >= $address_id) {
            ds_json_encode(10001,lang('param_error'));
        }
        $address_model=model('address');
        $address = $address_model->getAddressInfo(array('member_id' => session('member_id'), 'address_id' => $address_id));
        if (empty($address)) {
            ds_json_encode(10001,lang('address_does_not_exist'));
        }
        if (!request()->isPost()) {
            $area_mod=model('area');
            $region_list = $area_mod->getAreaList(array('area_parent_id'=>'0'));
            View::assign('region_list', $region_list);
            View::assign('address', $address);
            /* 设置买家当前菜单 */
            $this->setMemberCurMenu('member_address');
            /* 设置买家当前栏目 */
            $this->setMemberCurItem('my_address_edit');
            View::assign('baidu_ak', config('ds_config.baidu_ak'));
            return View::fetch($this->template_dir . 'form');
        } else {
            $address_is_default = input('post.is_default') == 1 ? 1 : 0;
            $data = array(
                'address_realname' => input('post.true_name'),
                'area_id' => input('post.area_id'),
                'city_id' => input('post.city_id'),
                'address_detail' => input('post.address'),
                'address_longitude' => input('post.longitude'),
                'address_latitude' => input('post.latitude'),
                'address_tel_phone' => input('post.tel_phone'),
                'address_mob_phone' => input('post.mob_phone'),
                'address_is_default' => $address_is_default,
                'area_info' => input('post.area_info'),
            );
            $memberaddress_validate = ds_validate('memberaddress');
            if (!$memberaddress_validate->scene('edit')->check($data)) {
                ds_json_encode(10001,$memberaddress_validate->getError());
            }

            //当默认地址为1时,把当前用户的地址设置为非默认地址
            if ($address_is_default == 1) {
                model('address')->editAddress(array('address_is_default' => 0), array('member_id' => session('member_id')));
            }

            $result = $address_model->editAddress($data,array('member_id' => session('member_id'), 'address_id' => $address_id));
            if ($result) {
                ds_json_encode(10000,lang('ds_common_save_succ'));
            } else {
                ds_json_encode(10001,lang('ds_common_save_fail'));
            }
        }
    }

    public function drop() {
        $address_id = intval(input('param.address_id'));
        if (0 >= $address_id) {
            ds_json_encode(10001,lang('empty_error'));
        }
        $address_model=model('address');
        $condition = array();
        $condition[] = array('address_id','=',$address_id);
        $condition[] = array('member_id','=',session('member_id'));
        $result = $address_model->delAddress($condition);
        if ($result) {
            ds_json_encode(10000,lang('ds_common_del_succ'));
        } else {
            ds_json_encode(10001,lang('ds_common_del_fail'));
        }
    }

    /**
     * 添加门店收货地址
     */
    public function chain_add() {
        if (request()->isPost()) {
            $info = model('chain')->getChainOpenInfo(array(array('chain_id' ,'=', intval(input('param.chain_id')))));
            if (empty($info)) {
                ds_json_encode(10001,lang('pick_up_point_exist'));
            }
            $data = array();
            $data['member_id'] = session('member_id');
            $data['address_realname'] = input('param.true_name');
            $data['area_id'] = $info['chain_area_3'];
            $data['city_id'] = $info['chain_area_2'];
            $data['area_info'] = $info['chain_area_info'];
            $data['address_detail'] = $info['chain_address'];
            $data['address_tel_phone'] = input('param.tel_phone');
            $data['address_mob_phone'] = input('param.mob_phone');
            $data['chain_id'] = $info['chain_id'];
            $data['address_is_default'] = 0;
            if (intval(input('param.address_id'))) {
                $result = model('address')->editAddress($data, array('address_id' => intval(input('param.address_id'))));
            } else {
                $count = model('address')->getAddressCount(array('member_id' => session('member_id')));
                if ($count >= 20) {
                    ds_json_encode(10001,lang('valid_addresses_allowed'));
                }
                $result = model('address')->addAddress($data);
            }
            if (!$result) {
                ds_json_encode(10001,lang('ds_common_save_fail'));
            }
            ds_json_encode(10000,lang('ds_common_save_succ'));
        } else {
            if (intval(input('param.address_id')) > 0) {
                $address_model = model('address');
                $condition = array('address_id' => intval(input('param.address_id')), 'member_id' => session('member_id'));
                $address_info = $address_model->getAddressInfo($condition);
                //取出省级ID
                $area_info = model('area')->getAreaInfo(array('area_id' => $address_info['city_id']));
                $address_info['province_id'] = $area_info['area_parent_id'];
                View::assign('address_info', $address_info);
            }
            /* 设置买家当前菜单 */
            $this->setMemberCurMenu('member_address');
            /* 设置买家当前栏目 */
            $this->setMemberCurItem('chain_add');
            return View::fetch($this->template_dir . 'chain_add');
        }
    }

    /**
     * 展示门店列表
     */
    public function chain_list() {
        $chain_model = model('chain');
        $condition = array();
        $condition[]=array('chain_area_3','=',intval(input('param.area_id')));
        $condition[]=array('chain_if_collect','=',1);
        $chain_list = $chain_model->getChainOpenList($condition, 5);
        View::assign('show_page', $chain_model->page_info->render());
        View::assign('chain_list', $chain_list);
        /* 设置买家当前菜单 */
        $this->setMemberCurMenu('member_address');
        /* 设置买家当前栏目 */
        $this->setMemberCurItem('chain_list');
        echo View::fetch($this->template_dir . 'chain_list');exit;
    }

    /**
     *    栏目菜单
     */
    function getMemberItemList() {
        $item_list = array(
            array(
                'name' => 'my_address',
                'text' => lang('my_address'),
                'url' => (string)url('Memberaddress/index'),
            ),
            array(
                'name' => 'my_address_add',
                'text' => lang('new_address'),
                'url' => (string)url('Memberaddress/add'),
            ),
        );
        if (request()->action() == 'edit') {
            $item_list[] = array(
                'name' => 'my_address_edit',
                'text' => lang('edit_address'),
                'url' => "javascript:void(0)",
            );
        }
        if (request()->action() == 'chain_add') {
            $item_list[] = array(
                'name' => 'chain_add',
                'text' => lang('to_set'),
                'url' => "javascript:void(0)",
            );
        }
        return $item_list;
    }

}

?>
