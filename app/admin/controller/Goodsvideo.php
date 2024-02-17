<?php

/*
 * 空间管理
 */

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
class Goodsvideo extends AdminControl {

    public function initialize() {
        parent::initialize();
        Lang::load(base_path() . 'admin/lang/'.config('lang.default_lang').'/goodsvideo.lang.php');
    }

    /**
     * 视频列表
     */
    public function index() {
        $goods_model=model('goods');
        $video_list=$goods_model->getGoodsVideoList(array(),'*','goodsvideo_id desc',0,16);
        foreach($video_list as $key => $val){
            $video_list[$key]['goodsvideo_url']=goods_video($val['goodsvideo_name']);
        }
        View::assign('video_list', $video_list);
        View::assign('show_page', $goods_model->page_info->render());
        $this->setAdminCurItem('index');
        return View::fetch();
    }

    /**
     * 删除视频
     *
     */
    public function del_video() {
        $goodsvideo_id = input('param.goodsvideo_id');
        $goodsvideo_id_array = ds_delete_param($goodsvideo_id);
        if ($goodsvideo_id_array === FALSE) {
            ds_json_encode('10001', lang('param_error'));
        }
        $condition=array();
        $condition[]=array('goodsvideo_id','in',$goodsvideo_id_array);
        $goods_model = model('goods');
        //批量删除视频
        $goods_model->delGoodsVideo($condition);
        $this->log(lang('ds_del') . lang('goodsvideo') . '[ID:' . $goodsvideo_id . ']', 1);
        ds_json_encode('10000', lang('ds_common_op_succ'));
    }


    protected function getAdminItemList() {
        $menu_array = array(
            array(
                'name' => 'index',
                'text' => lang('ds_list'),
                'url' => (string)url('Goodsvideo/index')
            )
        );
        return $menu_array;
    }

}

?>
