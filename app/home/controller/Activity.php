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
class Activity extends BaseMall {

    public function initialize() {
        parent::initialize();
        Lang::load(base_path() . 'home/lang/'.config('lang.default_lang').'/activity.lang.php');
    }
    
    /*
     * 显示所有活动列表
     */
    function index()
    {
        $condition = array();
        $activity_model = model('activity');
        $condition[]=array('activity_type','=',1);
        $condition[]=array('activity_startdate','<=',TIMESTAMP);
        $condition[]=array('activity_enddate','>=',TIMESTAMP);
        $condition[]=array('activity_state','=',1);
        
        $activity_list = $activity_model->getActivityList($condition, 10);
        View::assign('activity_list', $activity_list);
        View::assign('show_page', $activity_model->page_info->render());
        View::assign('html_title', config('ds_config.site_name') . ' - '.lang('activity_list'));
        return View::fetch($this->template_dir.'activity_index');
    }
    

    /**
     * 单个活动信息页
     */
    public function detail() {
        //得到导航ID
        $nav_id = intval(input('param.nav_id'));
        View::assign('index_sign', $nav_id);
        //查询活动信息
        $activity_id = intval(input('param.activity_id'));
        if ($activity_id <= 0) {
            $this->error(lang('param_error')); //'缺少参数:活动编号'
        }
        $activity = model('activity')->getOneActivityById($activity_id);
        if (empty($activity) || $activity['activity_type'] != '1' || $activity['activity_state'] != 1 || $activity['activity_startdate'] > TIMESTAMP || $activity['activity_enddate'] < TIMESTAMP) {
            $this->error(lang('activity_index_activity_not_exists')); //'指定活动并不存在'
        }
        View::assign('activity', $activity);
        $editable_page_model = model('editable_page');
        $editable_page_info = $editable_page_model->getOneEditablePage(array('editable_page_path' => 'activity/detail','editable_page_client'=>'pc','editable_page_item_id'=>$activity_id));
        if ($editable_page_info) {
            $editable_page_info['editable_page_theme_config'] = json_decode($editable_page_info['editable_page_theme_config'], true);
            View::assign('editable_page', $editable_page_info);
            $editable_page_config_model = model('editable_page_config');
            $editable_page_config_list=$editable_page_config_model->getEditablePageConfigList(array(array('editable_page_id', '=', $editable_page_info['editable_page_id'])));
            $config_list=array();
            foreach($editable_page_config_list as $key => $val){
                $config_info=json_decode($val['editable_page_config_content'], true);
                $model_id=$val['editable_page_model_id'];
                $var_html=array();
            if(!empty($config_info)){
                require_once PLUGINS_PATH.'/editable_page_model/'.$model_id.'/config.php';
                $model_name='Model'.$model_id;
                $model=new $model_name();
                $res=$model->filterData($config_info);
                if($res['code']){
                    $res=$model->formatData(json_encode($res['data']));
                    if($res['code']){
                        $var_html['config_info']=$res['data'];
                    }
                }
                
            }
            $html=View::fetch('../../../plugins/editable_page_model/'.$model_id.'/index',$var_html);
                $config_list[]=array(
                    'val'=>$val,
                    'html'=>$html,
                );
            }
            View::assign('config_list', $config_list);
            View::assign('editable_page', $editable_page_info);
        }else{
        //查询活动内容信息
        $condition = array();
        $condition[] = array('activitydetail.activity_id','=',$activity_id);
        $condition[] = array('activitydetail.activitydetail_state','=','1');
        $activitydetail_model=model('activitydetail');
        $activitydetail_list = $activitydetail_model->getGoodsJoinList($condition,100,'activitydetail_sort asc','goods.goods_commonid');
        View::assign('show_page', $activitydetail_model->page_info->render());
        View::assign('activitydetail_list', $activitydetail_list);
        }
        View::assign('html_title', config('ds_config.site_name') . ' - ' . $activity['activity_title']);
        return View::fetch($this->template_dir.'activity_show');
    }

}

?>
