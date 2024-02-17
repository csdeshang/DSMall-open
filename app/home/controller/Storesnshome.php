<?php

namespace app\home\controller;

use think\facade\View;
use think\facade\Lang;
use think\facade\Db;

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
class Storesnshome extends BaseStoreSns {

    public function initialize() {
        parent::initialize();
        Lang::load(base_path() . 'home/lang/' . config('lang.default_lang') . '/sellersns.lang.php');
    }

    /**
     * 查看店铺动态
     */
    public function index() {
        //获得店铺ID
        $sid = intval(input('param.sid'));
        $this->getStoreInfo($sid);
        // where 条件
        $where = array();
        $where[] = array('stracelog_state', '=', 1);
        $where[] = array('stracelog_storeid', '=', $sid);
        $type = input('type');
        if ($type != '') {
            switch (trim($type)) {
                case 'promotion':
                    $where[] = array('stracelog_type', 'in', array(4, 5, 6, 7, 8));
                    break;
                case 'new':
                    $where[] = array('stracelog_type', '=', 3);
                    break;
                case 'hotsell':
                    $where[] = array('stracelog_type', '=', 10);
                    break;
                case 'recommend':
                    $where[] = array('stracelog_type', '=', 9);
                    break;
            }
        }
        $storesnstracelog_model = model('storesnstracelog');
        $strace_array = $storesnstracelog_model->getStoresnstracelogList($where, '*', 'stracelog_id desc', 0, 40);
        // 整理
        if (!empty($strace_array) && is_array($strace_array)) {
            foreach ($strace_array as $key => $val) {
                switch ($val['stracelog_type']) {
                    case 4:
                    case 5:
                    case 6:
                    case 7:
                    case 8:
                        $strace_array[$key]['stracelog_style'] = 'promotion';
                        $strace_array[$key]['stracelog_lang'] = lang('store_sns_sales_promotion');
                        break;
                    case 3:
                        $strace_array[$key]['stracelog_style'] = 'new';
                        $strace_array[$key]['stracelog_lang'] = lang('store_sns_new_goods');
                        break;
                    case 10:
                        $strace_array[$key]['stracelog_style'] = 'hotsell';
                        $strace_array[$key]['stracelog_lang'] = lang('store_sns_hot_sale');
                        break;
                    case 9:
                        $strace_array[$key]['stracelog_style'] = 'recommend';
                        $strace_array[$key]['stracelog_lang'] = lang('store_sns_recommended');
                        break;
                    case 2:
                        $strace_array[$key]['stracelog_style'] = 'normal';
                        $strace_array[$key]['stracelog_lang'] = lang('store_sns_normal');
                        break;
                }
                if ($val['stracelog_content'] == '') {
                    $val['stracelog_goodsdata'] = json_decode(stripslashes($val['stracelog_goodsdata']), true);
                    $content = $storesnstracelog_model->spellingStyle($val['stracelog_type'], $val['stracelog_goodsdata']);
                    $strace_array[$key]['stracelog_content'] = str_replace("%siteurl%", HOME_SITE_URL . DIRECTORY_SEPARATOR, $content);
                }
            }
        }
        View::assign('strace_array', $strace_array);

        //允许插入新记录的最大条数
        View::assign('max_recordnum', self::MAX_RECORDNUM);
        View::assign('show_page', $storesnstracelog_model->page_info->render());

        // 最多收藏的会员
        $favorites = model('favorites')->getStoreFavoritesList(array(array('fav_id' ,'=', $sid)), '*', 0, 'fav_time desc', 8);
        if (!empty($favorites)) {
            $memberid_array = array();
            foreach ($favorites as $val) {
                $memberid_array[] = $val['member_id'];
            }
            $favorites_list = model('member')->getMemberList(array(array('member_id', 'in', $memberid_array)), 'member_id,member_name,member_avatar');
            View::assign('favorites_list', $favorites_list);
        }
        return View::fetch($this->template_dir . 'store_snshome');
    }

    /**
     * 评论前10条记录
     */
    public function commenttop() {
        $stid = intval(input('param.id'));
        if ($stid > 0) {
            $storesnscomment_model = model('storesnscomment');
            //查询评论总数

            $where = array(
                'stracelog_id' => $stid,
                'storesnscomm_state' => 1
            );
            $countnum = $storesnscomment_model->getStoresnscommentCount($where);
            //动态列表
            $commentlist = $storesnscomment_model->getStoresnscommentList($where, '*', 'storesnscomm_id desc', 10);
            // 更新评论数量
            model('storesnstracelog')->editStoresnstracelog(array('stracelog_comment' => $countnum), array('stracelog_id' => $stid));
        }
        $showmore = '0'; //是否展示更多的连接
        if ($countnum > count($commentlist)) {
            $showmore = '1';
        }
        View::assign('countnum', $countnum);
        View::assign('showmore', $showmore);
        View::assign('showtype', 1); //页面展示类型 0表示分页 1表示显示前几条
        View::assign('stid', $stid);

        //允许插入新记录的最大条数
        View::assign('max_recordnum', self::MAX_RECORDNUM);
        View::assign('commentlist', $commentlist);
        echo View::fetch($this->template_dir . 'store_snscommentlist');
        exit;
    }

    /**
     * 评论列表
     */
    public function commentlist() {
        $stid = intval(input('param.id'));
        if ($stid > 0) {
            $storesnscomment_model = model('storesnscomment');
            //查询评论总数
            $where = array(
                'stracelog_id' => $stid,
                'storesnscomm_state' => 1
            );
            $countnum = $storesnscomment_model->getStoresnscommentCount($where);

            //评价列表
            $commentlist = $storesnscomment_model->getStoresnscommentList($where, '*', 'storesnscomm_id desc', 0, 10);

            // 更新评论数量
            model('storesnstracelog')->editStoresnstracelog(array('stracelog_comment' => $countnum), array('stracelog_id' => $stid));
        }

        View::assign('commentlist', $commentlist);
        View::assign('show_page', $storesnscomment_model->page_info->render());
        View::assign('countnum', $countnum);
        View::assign('stid', $stid);
        View::assign('showtype', '0'); //页面展示类型 0表示分页 1表示显示前几条
        //允许插入新记录的最大条数
        View::assign('max_recordnum', self::MAX_RECORDNUM);
        echo View::fetch($this->template_dir . 'store_snscommentlist');
        exit;
    }

    /**
     * 添加评论(访客登录后操作)
     */
    public function addcomment() {
        // 验证用户是否登录
        $this->checkLoginStatus();

        $stid = intval(input('post.stid'));
        if ($stid <= 0) {
            ds_json_encode(10001, lang('param_error'));
        }

        $validate_arr = array(
            'commentcontent' => input('post.commentcontent'),
        );
        $storesnshome_validate = ds_validate('storesnshome');
        if (!$storesnshome_validate->scene('addcomment')->check($validate_arr)) {
            ds_json_encode(10001, $storesnshome_validate->getError());
        }
        //发帖数超过最大次数出现验证码
        if (intval(cookie('commentnum')) >= self::MAX_RECORDNUM) {
            if (!captcha_check(input('post.captcha'))) {
                ds_json_encode(10001, lang('wrong_checkcode'));
            }
        }



        //查询会员信息
        $member_info = Db::name('member')->where(array('member_state' => 1, 'member_id' => session('member_id')))->find();
        if (empty($member_info)) {
            ds_json_encode(10001, lang('sns_member_error'));
        }
        $insert_arr = array();
        $insert_arr['stracelog_id'] = $stid;
        $insert_arr['storesnscomm_content'] = input('post.commentcontent');
        $insert_arr['storesnscomm_memberid'] = $member_info['member_id'];
        $insert_arr['storesnscomm_membername'] = $member_info['member_name'];
        $insert_arr['storesnscomm_memberavatar'] = $member_info['member_avatar'];
        $insert_arr['storesnscomm_time'] = TIMESTAMP;
        $result = model('storesnscomment')->addStoresnscomment($insert_arr);
        if ($result) {
            // 原帖增加评论次数
            $where = array('stracelog_id' => $stid);
            $update = array('stracelog_comment' => Db::raw('stracelog_comment+1'));
            $rs = model('storesnstracelog')->editStoresnstracelog($update, $where);
            //建立cookie
            if (cookie('commentnum') != null && intval(cookie('commentnum')) > 0) {
                cookie('commentnum', intval(cookie('commentnum')) + 1, 2 * 3600); //保存2小时
            } else {
                cookie('commentnum', 1, 2 * 3600); //保存2小时
            }
            ds_json_encode(10000, lang('sns_comment_succ'), $stid);
        }
    }

    /**
     * 添加转发
     */
    public function addforward() {
        // 验证用户是否登录
        $this->checkLoginStatus();
        $stid = intval(input('param.stid'));
        if ($stid <= 0) {
            ds_json_encode(10001, lang('param_error'));
        }

        $validate_arr = array(
            'forwardcontent' => input('post.forwardcontent'),
        );

        $storesnshome_validate = ds_validate('storesnshome');
        if (!$storesnshome_validate->scene('addforward')->check($validate_arr)) {
            ds_json_encode(10001, $storesnshome_validate->getError());
        }
        //发帖数超过最大次数出现验证码
        if (intval(cookie('forwardnum')) >= self::MAX_RECORDNUM) {
            if (!captcha_check(input('post.captcha'))) {
                ds_json_encode(10001, lang('wrong_checkcode'));
            }
        }
        //查询会员信息
        $member_info = Db::name('member')->where(array('member_state' => 1, 'member_id' => session('member_id')))->find();
        if (empty($member_info)) {
            ds_json_encode(10001, lang('sns_member_error'));
        }
        //查询原帖信息
        $storesnstracelog_model = model('storesnstracelog');
        $stracelog_info = $storesnstracelog_model->getStoresnstracelogInfo(array('stracelog_id' => $stid));
        if (empty($stracelog_info)) {
            ds_json_encode(10001, lang('sns_forward_fail'));
        }
        if ($stracelog_info['stracelog_content'] == '') {
            $data = json_decode($stracelog_info['stracelog_goodsdata'], true);
            $stracelog_info['stracelog_content'] = $storesnstracelog_model->spellingStyle($stracelog_info['stracelog_type'], $data);
        }

        $insert_arr = array();
        $insert_arr['tracelog_originalid'] = 0;
        $insert_arr['tracelog_originalmemberid'] = 0;
        $insert_arr['tracelog_originalstate'] = 0;
        $insert_arr['tracelog_memberid'] = $member_info['member_id'];
        $insert_arr['tracelog_membername'] = $member_info['member_name'];
        $insert_arr['tracelog_memberavatar'] = $member_info['member_avatar'];
        $insert_arr['tracelog_title'] = input('post.forwardcontent') ? input('post.forwardcontent') : lang('sns_forward');
        $insert_arr['tracelog_content'] = "<dl class=\"fd-wrap\">
	<dt>
	<h3><a href=\"" . HOME_SITE_URL . "/Storesnshome/index.html?sid=" . $stracelog_info['stracelog_storeid'] . "\" target=\"_blank\">" . $stracelog_info['stracelog_storename'] . "</a>" . lang('ds_colon') . "
	" . $stracelog_info['stracelog_title'] . "</h3>
	</dt>
	<dd>" . $stracelog_info['stracelog_content'] . "</dd>
	<dl>";
        $insert_arr['tracelog_addtime'] = TIMESTAMP;
        $insert_arr['tracelog_state'] = 0;
        $insert_arr['tracelog_privacy'] = 0;
        $insert_arr['tracelog_commentcount'] = 0;
        $insert_arr['tracelog_copycount'] = 0;
        $insert_arr['tracelog_orgcommentcount'] = 0;
        $insert_arr['tracelog_orgcopycount'] = 0;
        $insert_arr['tracelog_from'] = 2;
        $result = Db::name('snstracelog')->insert($insert_arr);
        if ($result) {
            //更新动态转发次数
            $where = array('stracelog_id' => $stid);
            $update = array('stracelog_spread' => Db::raw('stracelog_spread+1'));
            model('storesnstracelog')->editStoresnstracelog($update, $where);
            ds_json_encode(10000, lang('sns_forward_succ'));
        } else {
            ds_json_encode(10001, lang('sns_forward_fail'));
        }
    }

    /**
     * 删除动态
     */
    public function deltrace() {
        // 验证用户是否登录
        $this->checkLoginStatus();

        $stid = intval(input('id'));
        if ($stid <= 0) {
            $this->error(lang('param_error'));
        }
        //删除动态
        $result = model('storesnstracelog')->delStoresnstracelog(array('stracelog_id' => $stid, 'stracelog_storeid' => session('store_id')));
        if ($result) {
            //删除对应的评论
            model('storesnscomment')->delStoresnscomment(array('stracelog_id' => $stid));
            ds_json_encode(10000, lang('ds_common_del_succ'));
        } else {
            ds_json_encode(10001, lang('ds_common_del_fail'));
        }
    }

    /**
     * 删除评论(访客登录后操作)
     */
    public function delcomment() {
        // 验证用户是否登录
        $this->checkLoginStatus();

        $scid = intval(input('param.scid'));
        $stid = intval(input('param.stid'));
        if ($scid <= 0 || $stid <= 0) {
            ds_json_encode(10001, lang('param_error'));
        }
        // 查询评论相关信息
        $storesnscomment_model = model('storesnscomment');
        $where = array('stracelog_id' => $stid, 'storesnscomm_id' => $scid, 'storesnscomm_memberid' => session('member_id')); // where条件
        $scomment_info = $storesnscomment_model->getStoresnscommentInfo($where);
        if (empty($scomment_info)) {
            ds_json_encode(10001, lang('param_error'));
        }

        // 删除评论
        $result = $storesnscomment_model->delStoresnscomment($where);
        if ($result) {
            // 更新动态统计信息
            $where = array('stracelog_id' => $scomment_info['stracelog_id']);
            $update = array('stracelog_comment' => Db::raw('stracelog_comment-1'));
            model('storesnstracelog')->editStoresnstracelog($update, $where);

//            $js ="$('.comment-list [ds_type=\"commentrow_" . $scid . "\"]').remove();";
            ds_json_encode(10000, lang('ds_common_del_succ'));
        } else {
            ds_json_encode(10001, lang('ds_common_del_fail'));
        }
    }

    /**
     * 一条SNS动态及其评论
     */
    public function straceinfo() {
        $stid = intval(input('get.st_id'));
        if ($stid <= 0) {
            $this->error(lang('param_error'));
        }
        $storesnstracelog_model = model('storesnstracelog');
        $strace_info = $storesnstracelog_model->getStoresnstracelogInfo(array('stracelog_id' => $stid));
        if (!empty($strace_info)) {
            if ($strace_info['stracelog_content'] == '') {
                $content = $storesnstracelog_model->spellingStyle($strace_info['stracelog_type'], json_decode(stripslashes($strace_info['stracelog_goodsdata']), true));
                $strace_info['stracelog_content'] = str_replace("%siteurl%", HOME_SITE_URL . DIRECTORY_SEPARATOR, $content);
            }
        }
        View::assign('strace_info', $strace_info);
        return View::fetch($this->template_dir . 'store_snstraceinfo');
    }

    /**
     * 验证用户是否登录
     */
    private function checkLoginStatus() {
        if (session('is_login') != 1) {
            @header("location: " . HOME_SITE_URL . "/Login/logon.html");
        }
    }

}

?>
