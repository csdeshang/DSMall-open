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
class Activity extends AdminControl {

    public function initialize() {
        parent::initialize();
        Lang::load(base_path() . 'admin/lang/'.config('lang.default_lang').'/activity.lang.php');
    }

    /**
     * 活动列表/删除活动
     */
    public function index() {
        $activity_model = model('activity');
        //条件
        $condition = array();
        $condition[] = array('activity_type','=','1'); //只显示商品活动
        //状态
        if ((input('param.searchstate'))) {
            $state = intval(input('param.searchstate')) - 1;
            $condition[] = array('activity_state','=',"$state");
        }
        //标题
        if ((input('param.searchtitle'))) {
            $condition[]=array('activity_title','like', "%" . input('param.searchtitle') . "%");
        }
        //有效期范围
        if ((input('param.searchstartdate')) && (input('param.searchenddate'))) {
            $startdate = strtotime(input('param.searchstartdate'));
            $enddate = strtotime(input('param.searchenddate'));
            if ($enddate > 0) {
                $enddate += 86400;
            }
            $condition[]=array('activity_enddate','>=',$startdate);
            $condition[]=array('activity_startdate','<=',$enddate);
        }
        //活动列表
        $activity_list = $activity_model->getActivityList($condition, 10 , 'activity_sort asc');
        //输出
        View::assign('show_page', $activity_model->page_info->render());
        View::assign('activity_list', $activity_list);
        View::assign('filtered', $condition ? 1 : 0); //是否有查询条件
        $this->setAdminCurItem('index');
        return View::fetch();
    }

    /**
     * 新建活动/保存新建活动
     */
    public function add() {
        if (request()->isPost()) {
            //提交表单
            $data = [
                'activity_title' => input('post.activity_title'),
                'activity_startdate' => strtotime(input('post.activity_startdate')),
                'activity_enddate' => strtotime(input('post.activity_enddate')),
                'activity_type' => input('post.activity_type'),
                'activity_banner' => $_FILES['activity_banner']['name'],
                'activity_banner_mobile' => $_FILES['activity_banner_mobile']['name'],
                'activity_sort' => intval(input('post.activity_sort'))
            ];
            $activity_validate = ds_validate('activity');
            if (!$activity_validate->scene('add')->check($data)) {
                $this->error($activity_validate->getError());
            }

            $file_name = '';
            if (!empty($_FILES['activity_banner']['name'])) {
                $res=ds_upload_pic(ATTACH_ACTIVITY,'activity_banner');
                if($res['code']){
                    $file_name=$res['data']['file_name'];
                }else{
                    $this->error($res['msg']);
                }
            }
            //保存
            $data['activity_banner'] = $file_name;
            
            $file_name_mobile = '';
            if (!empty($_FILES['activity_banner_mobile']['name'])) {
                $res=ds_upload_pic(ATTACH_ACTIVITY,'activity_banner_mobile');
                if($res['code']){
                    $file_name_mobile=$res['data']['file_name'];
                }else{
                    $this->error($res['msg']);
                }
            }
            //保存
            $data['activity_banner_mobile'] = $file_name_mobile;
            $data['activity_desc'] = trim(input('post.activity_desc'));
            $data['activity_state'] = intval(input('post.activity_state'));

            $activity_model = model('activity');
            $result = $activity_model->addActivity($data);
            if ($result) {
                $this->log(lang('ds_add') . lang('activity_index') . '[' . input('post.activity_title') . ']', null);
                dsLayerOpenSuccess(lang('ds_common_op_succ'));
            } else {
                //添加失败则删除刚刚上传的图片,节省空间资源
                @unlink($upload_file . DIRECTORY_SEPARATOR . $file_name);
                @unlink($upload_file . DIRECTORY_SEPARATOR . $file_name_mobile);
                $this->error(lang('ds_common_op_fail'));
            }
        } else {
            $activity = array(
                'activity_type' => '1',
                'activity_startdate' => TIMESTAMP,
                'activity_enddate' => TIMESTAMP,
                'activity_banner' => '',
                'activity_desc' => '',
                'activity_state' => '1',
            );
            View::assign('activity', $activity);
            return View::fetch('form');
        }
    }

    /**
     * 异步修改
     */
    public function ajax() {
        if (in_array(input('param.branch'), array('activity_title', 'activity_sort'))) {
            $activity_model = model('activity');
            $update_array = array();
            switch (input('param.branch')) {
                /**
                 * 活动主题
                 */
                case 'activity_title':
                    if (trim(input('param.value')) == '')
                        exit;
                    break;
                /**
                 * 排序
                 */
                case 'activity_sort':
                    if (preg_match('/^\d+$/', trim(input('param.value'))) <= 0 or intval(trim(input('param.value'))) < 0 or intval(trim(input('param.value'))) > 255)
                        exit;
                    break;
                default:
                    exit;
            }
            $update_array[input('param.column')] = trim(input('param.value'));
            if ($activity_model->editActivity($update_array, intval(input('param.id'))))
                echo 'true';
        }elseif (in_array(input('param.branch'), array('activitydetail_sort'))) {
            $activitydetail_model = model('activitydetail');
            $update_array = array();
            switch (input('param.branch')) {
                /**
                 * 排序
                 */
                case 'activitydetail_sort':
                    if (preg_match('/^\d+$/', trim(input('param.value'))) <= 0 or intval(trim(input('param.value'))) < 0 or intval(trim(input('param.value'))) > 255)
                        exit;
                    break;
                default:
                    exit;
            }
            $update_array[input('param.column')] = trim(input('param.value'));
            if ($activitydetail_model->editActivitydetail($update_array, array(array('activitydetail_id','=',intval(input('param.id'))))))
                echo 'true';
        }
    }

    /**
     * 删除活动
     */
    public function del() {
        $id = intval(input('param.activity_id'));
        if ($id <= 0) {
            ds_json_encode(10001, lang('param_error'));
        }

        $activity_model = model('activity');
        $activitydetail_model = model('activitydetail');
        //获取可以删除的数据
        $activity_info = $activity_model->getOneActivityById($id);
        if (empty($activity_info) || ($activity_info['activity_state'] && $activity_info['activity_enddate']>TIMESTAMP)) {//没有符合条件的活动信息直接返回成功信息
            ds_json_encode(10001, lang('activity_index_help3'));
        }
        $id_arr = array($activity_info['activity_id']);
        $condition = array();
        $condition[] = array('activity_id','in',$id_arr);
        //只有关闭或者过期的活动，能删除
        if ($activitydetail_model->getActivitydetailList($condition)) {
            if (!$activitydetail_model->delActivitydetail($condition)) {
                ds_json_encode(10001, lang('activity_del_fail'));
            }
        }
        try {
            //删除数据先删除横幅图片，节省空间资源
            foreach ($id_arr as $v) {
                $this->delBanner(intval($v));
            }
        } catch (Exception $e) {
            ds_json_encode(10001, $e->getMessage());
        }
        if ($activity_model->delActivity($condition)) {
            $this->log(lang('ds_del') . lang('activity_index') . '[ID:' . $id . ']', null);
            ds_json_encode(10000, lang('ds_common_del_succ'));
        }
        ds_json_encode(10001, lang('activity_del_fail'));
    }

    /**
     * 编辑活动/保存编辑活动
     */
    public function edit() {
        $activity_id = intval(input('param.activity_id'));
        if ($activity_id<=0) {
            $this->error(lang('miss_argument'));
        }
        $activity_model = model('activity');
        $activity = $activity_model->getOneActivityById($activity_id);
        if (!request()->isPost()) {
            View::assign('activity', $activity);
            return View::fetch('form');
        } else {
            //提交表单
            $data = [
                'activity_title' => input('post.activity_title'),
                'activity_startdate' => strtotime(input('post.activity_startdate')),
                'activity_enddate' => strtotime(input('post.activity_enddate')),
                'activity_type' => input('post.activity_type'),
                'activity_sort' => intval(input('post.activity_sort'))
            ];
            $activity_validate = ds_validate('activity');
            if (!$activity_validate->scene('edit')->check($data)) {
                $this->error($activity_validate->getError());
            }
            //构造更新内容
            $file_name = '';
            if ($_FILES['activity_banner']['name'] != '') {
                $res=ds_upload_pic(ATTACH_ACTIVITY,'activity_banner');
                if($res['code']){
                    $file_name=$res['data']['file_name'];
					$data['activity_banner'] = $file_name;
                }else{
                    $this->error($res['msg']);
                }
                
            }
            $file_name_mobile = '';
            if ($_FILES['activity_banner_mobile']['name'] != '') {
                $res=ds_upload_pic(ATTACH_ACTIVITY,'activity_banner_mobile');
                if($res['code']){
                    $file_name_mobile=$res['data']['file_name'];
					$data['activity_banner_mobile'] = $file_name_mobile;
                }else{
                    $this->error($res['msg']);
                }
                
            }
            $data['activity_desc'] = trim(input('post.activity_desc'));
            $data['activity_state'] = intval(input('post.activity_state'));
            
            $result = $activity_model->editActivity($data, $activity_id);
            if ($result) {
                //删除图片
                @unlink($upload_file . DIRECTORY_SEPARATOR .$activity['activity_banner']);
                @unlink($upload_file . DIRECTORY_SEPARATOR .$activity['activity_banner_mobile']);
                $this->log(lang('ds_edit') . lang('activity_index') . '[ID:' . $activity_id . ']', null);
                dsLayerOpenSuccess(lang('ds_common_save_succ'));
            } else {
                if ($_FILES['activity_banner']['name'] != '') {
                    @unlink($upload_file . DIRECTORY_SEPARATOR .$file_name);
                }
                if ($_FILES['activity_banner_mobile']['name'] != '') {
                    @unlink($upload_file . DIRECTORY_SEPARATOR .$file_name_mobile);
                }
                $this->error(lang('ds_common_save_fail'));
            }
        }
    }

    /**
     * 活动细节列表
     */
    public function detail() {
        $activity_id = intval(input('param.id'));
        if ($activity_id <= 0) {
            $this->error(lang('miss_argument'));
        }
        //条件
        $condition_arr = array();
        $condition_arr[] = array('activity_id','=',$activity_id);
        //审核状态
        if ((input('param.searchstate'))) {
            $state = intval(input('param.searchstate')) - 1;
            $condition_arr[] = array('activitydetail_state','=',"$state");
        }
        //店铺名称
        if ((input('param.searchstore'))) {
            $condition_arr[] = array('store_name','like', "%" . input('param.searchstore') . "%");
        }
        //商品名称
        if ((input('param.searchgoods'))) {
            $condition_arr[] = array('item_name','like', "%" . input('param.searchgoods') . "%");
        }

        $activitydetail_model = model('activitydetail');
        $activitydetail_list = $activitydetail_model->getActivitydetailList($condition_arr, 10);
        //输出到模板
        View::assign('show_page', $activitydetail_model->page_info->render());
        View::assign('activitydetail_list', $activitydetail_list);
        $this->setAdminCurItem('detail');
        return View::fetch();
    }

    /**
     * 活动内容处理
     */
    public function deal() {
        $activitydetail_id = input('param.activitydetail_id');
        $activitydetail_id_array = ds_delete_param($activitydetail_id);
        if ($activitydetail_id_array == FALSE) {
            ds_json_encode('10001', lang('param_error'));
        }
        $condition = array();
        $condition[] = array('activitydetail_id','in',$activitydetail_id_array);

        //创建活动内容对象
        $activitydetail_state = intval(input('param.state'));
        $result = model('activitydetail')->editActivitydetail(array('activitydetail_state' => $activitydetail_state),$condition);
        if ($result>=0) {
            $this->log(lang('ds_edit') . lang('activity_index') . '[ID:' . $activitydetail_id . ']', null);
            if (input('param.ajax')) {
                ds_json_encode(10000,lang('ds_common_op_succ'));
            }else{
                $this->success(lang('ds_common_op_succ'));
            }
            
        } else {
            if (input('param.ajax')) {
                ds_json_encode(10001,lang('ds_common_op_fail'));
            }else{
                $this->error(lang('ds_common_op_fail'));
            }
            
        }
    }
    /**
     * 删除活动内容
     */
    public function del_detail() {
        $activitydetail_id = input('param.activitydetail_id');
        $activitydetail_id_array = ds_delete_param($activitydetail_id);
        if ($activitydetail_id_array == FALSE) {
            ds_json_encode('10001', lang('param_error'));
        }

        $activitydetail_model = model('activitydetail');
        //条件
        $condition_arr = array();
        $condition_arr[] =array('activitydetail_id','in',$activitydetail_id_array);
        $condition_arr[] = array('activitydetail_state','in',array('0','2'));//未审核和已拒绝
        if ($activitydetail_model->delActivitydetail($condition_arr)) {
            $this->log(lang('ds_del') . lang('activity_index_content') . '[ID:' . implode(',', $activitydetail_id_array) . ']', null);
            ds_json_encode(10000, lang('ds_common_del_succ'));
        } else {
            ds_json_encode(10001, lang('ds_common_del_fail'));
        }
    }

    /**
     * 根据活动编号删除横幅图片
     *
     * @param int $id
     */
    private function delBanner($id) {
        $activity_model = model('activity');
        $row = $activity_model->getOneActivityById($id);
        //删除图片文件
        @unlink(BASE_UPLOAD_PATH . DIRECTORY_SEPARATOR . ATTACH_ACTIVITY . DIRECTORY_SEPARATOR . $row['activity_banner']);
    }

    /**
     * 获取卖家栏目列表,针对控制器下的栏目
     */
    protected function getAdminItemList() {
        $menu_array = array(
            array(
                'name' => 'index', 'text' => lang('ds_manage'), 'url' => (string)url('Activity/index')
            ), array(
                'name' => 'add',
                'text' => lang('ds_new'),
                'url' => "javascript:dsLayerOpen('".(string)url('Activity/add')."','".lang('ds_new')."')"
            ),
        );
        if (request()->action() == 'detail') {
            $menu_array[] = array(
                'name' => 'detail', 'text' => lang('processing_application'), 'url' => 'javascript:void(0)'
            );
        }
        return $menu_array;
    }

}
