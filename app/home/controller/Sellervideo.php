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
class Sellervideo extends BaseSeller {

    public function initialize() {
        parent::initialize();
        Lang::load(base_path() . 'home/lang/' . config('lang.default_lang') . '/sellervideo.lang.php');
    }

    public function index() {
        $goods_model = model('goods');
        $video_list = $goods_model->getGoodsVideoList(array(array('store_id', '=', session('store_id'))), '*', 'goodsvideo_id desc', 0, 16);
        foreach ($video_list as $key => $val) {
            $video_list[$key]['goodsvideo_url'] = goods_video($val['goodsvideo_name']);
        }
        View::assign('video_list', $video_list);
        View::assign('show_page', $goods_model->page_info->render());
        /* 设置卖家当前菜单 */
        $this->setSellerCurMenu('sellervideo');
        /* 设置卖家当前栏目 */
        $this->setSellerCurItem('index');
        return View::fetch($this->template_dir . 'index');
    }

    /**
     * 视频列表，外部调用
     */
    public function video_list() {
        $goods_model = model('goods');
        $video_list = $goods_model->getGoodsVideoList(array(array('store_id', '=', session('store_id'))), '*', 'goodsvideo_id desc', 0, 3);
        foreach ($video_list as $key => $val) {
            $video_list[$key]['goodsvideo_url'] = goods_video($val['goodsvideo_name']);
        }
        View::assign('video_list', $video_list);
        View::assign('show_page', $goods_model->page_info->render());
        echo View::fetch($this->template_dir . 'video_list');
    }

    /**
     * 视频删除
     */
    public function del_video() {
        $return_json = input('param.return_json'); //是否为json 返回
        $ids = input('param.id/a');
        if (empty($ids)) {
            $this->error(lang('param_error'));
        }
        $goods_model = model('goods');
        //删除视频
        $condition = array();
        $condition[] = array('goodsvideo_id', 'in', $ids);
        $condition[] = array('store_id', '=', session('store_id'));
        $return = $goods_model->delGoodsVideo($condition);
        if ($return) {
            if ($return_json) {
                ds_json_encode(10000, lang('ds_common_op_succ'));
            } else {
                $this->success(lang('ds_common_op_succ'));
            }
        } else {
            if ($return_json) {
                ds_json_encode(10000, lang('ds_common_op_fail'));
            } else {
                $this->error(lang('ds_common_op_fail'));
            }
        }
    }

    /**
     * 上传视频
     */
    public function video_upload() {
        $store_id = session('store_id');
        $save_name = $store_id . '_' . date('YmdHis') . rand(10000, 99999) . '.mp4';

        $file_name = input('post.name');
        $upload_path = ATTACH_GOODS . DIRECTORY_SEPARATOR . $store_id;

        $res = ds_upload_pic($upload_path, $file_name, $save_name, 'mp4');
        if ($res['code']) {
            $save_name = $res['data']['file_name'];
            $data = array();
            $data ['url'] = goods_video($save_name);
            $data ['name'] = $save_name;

            $goods_model = model('goods');
            $goods_model->addGoodsVideo(array(
                'store_id' => $store_id,
                'store_name' => session('store_name'),
                'goodsvideo_name' => $save_name,
                'goodsvideo_add_time' => TIMESTAMP
            ));
            // 整理为json格式
            $output = json_encode($data);
            echo $output;
            exit();
        } else {
            echo json_encode(array('error' => $res['msg']));
            exit();
        }
    }

    /**
     * 用户中心右边，小导航
     *
     * @param string	$menu_type	导航类型
     * @param string 	$menu_key	当前导航的menu_key
     * @return
     */
    function getSellerItemList() {
        $item_list = array(
            array(
                'name' => 'index',
                'text' => lang('seller_goodsvideo'),
                'url' => (string) url('Sellervideo/index'),
            )
        );

        return $item_list;
    }

}

?>
