<?php

namespace app\common\model;

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
 * 数据层模型
 */
class Webchat extends BaseModel
{
   
    /**
     * 新增聊天记录
     * @access public
     * @author csdeshang
     * @param type $msg
     * @return type
     */
    public function addChatmsg($msg)
    {
        $msg['f_ip'] = request()->ip();
        $msg['r_state'] = '2';//state:1--read ,2--unread
        $msg['chatmsg_addtime'] = TIMESTAMP;
        $m_id = Db::name('chatmsg')->insertGetId($msg);
        if ($m_id > 0) {
            $msg['m_id'] = $m_id;
            unset($msg['r_state']);
            unset($msg['chatmsg_addtime']);
            $msg['chatlog_addtime'] = TIMESTAMP;
            Db::name('chatlog')->insertGetId($msg);
            $msg['m_id'] = $m_id;
            $msg['add_time'] = date('Y-m-d H:i:s', $msg['chatlog_addtime']);
            $t_msg = $msg['t_msg'];
            $goods_id = 0;
            $goods_info = false;
            $pattern = '#' . HOME_SITE_URL . '/goods/index.html?goods_id=(\d+)$#';
            preg_match($pattern, $t_msg, $matches);
            if (isset($matches[1]) && intval($matches[1]) < 1) {//伪静态
                $pattern = '#' . HOME_SITE_URL . '/item-(\d+)\.html$#';
                preg_match($pattern, $t_msg, $matches);
            }
            $goods_id = isset($matches[1])?intval($matches[1]):0;
            if ($goods_id >= 1) {
                $goods_info = $this->getGoodsInfo($goods_id);
                $goods_id = intval($goods_info['goods_id']);
            }
            $msg['goods_id'] = $goods_id;
            $msg['goods_info'] = $goods_info;
            return $msg;
        }
        else {
            return 0;
        }
    }

    /**
     * 单个用户信息
     * @access public
     * @author csdeshang
     * @param type $member_id 会员id
     * @return boolean|array
     */
    public function getMember($member_id)
    {
        if (intval($member_id) < 1) {
            return false;
        }
        $member = $this->getMemberInfo(array('member_id' => $member_id));
        return $member;
    }


    /**
     * 获取聊天记录
     * @access public
     * @author csdeshang
     * @param type $condition 条件
     * @param type $pagesize 分页
     * @param type $order 排序
     * @return type
     */
    public function getChatlogList($condition = array(), $pagesize = 10, $order = 'm_id desc')
    {
        $list = Db::name('chatlog')->where($condition)->order($order)->paginate(['list_rows'=>$pagesize,'query' => request()->param()],false);
        if (!empty($list->items()) && is_array($list->items())) {
            foreach ($list as $k => $v) {
                $v['add_time'] = date("Y-m-d H:i:s", $v['chatlog_addtime']);
                $list[$k] = $v;
            }
        }
        $this->page_info=$list;
        return $list->items();
    }

    /**
     * 记录详细
     * @access public
     * @author csdeshang
     * @param type $condition 条件
     * @return type
     */
    public function getOneChatlog($condition)
    {
        return Db::name('chatlog')->where($condition)->find();
    }

    /**
     * 取得我的好友
     * @access public
     * @author csdeshang
     * @param type $condition 条件
     * @param type $limit 限制
     * @param type $member_list 会员列表
     * @return array
     */
    public function getFriendList($condition = array(), $limit = 50, $member_list = array())
    {
        $list = Db::name('snsfriend')->where($condition)->order('friend_addtime desc')->paginate(['list_rows'=>$limit,'query' => request()->param()],false);
        if ($list) {
            foreach ($list as $k => $v) {
                $member = array();
                $u_id = $v['friend_tomid'];
                $member['u_id'] = $u_id;
                $member['u_name'] = $v['friend_tomname'];
                $member['avatar'] = get_member_avatar_for_id($u_id);
                $member['friend'] = 1;
                $member_list[$u_id] = $member;
            }
        }
        $this->page_info=$list;
        return $member_list;
    }

    /**
     * 商家客服
     * @access public
     * @author csdeshang
     * @param type $condition 条件
     * @param type $pagesize 分页
     * @param type $member_list 会员列表
     * @return type
     */
    public function getWebchatSellerList($condition = array(), $pagesize = 50, $member_list = array())
    {
        $seller_model = model('seller');
        $list = $seller_model->getSellerList($condition, 'seller_id desc');
        if (!empty($list) && is_array($list)) {
            $member_ids = array();//会员编号数组
            foreach ($list as $k => $v) {
                $member = array();
                $u_id = $v['member_id'];
                $member_ids[] = $u_id;
                $member['u_id'] = $u_id;
                $member['u_name'] = '';
                $member['seller_id'] = $v['seller_id'];
                $member['seller_name'] = $v['seller_name'];
                $member['avatar'] = get_member_avatar_for_id($u_id);
                $member['seller'] = 1;
                $member_list[$u_id] = $member;
            }
            $member_model = model('member');
            $condition = array();
            $condition[] = array('member_id','in', $member_ids);
            $m_list = $member_model->getMemberList($condition);
            if (!empty($m_list) && is_array($m_list)) {
                foreach ($m_list as $key => $value) {
                    $u_id = $value['member_id'];//会员编号
                    $member_list[$u_id]['u_name'] = $value['member_name'];
                }
            }
        }
        return $member_list;
    }

    /**
     * 取得接受消息列表
     * @access public
     * @author csdeshang
     * @param type $condition 条件
     * @param type $limit 限制
     * @param type $member_list 会员列表
     * @return type
     */
    public function getRecentList($condition = array(), $limit = 5, $member_list = array())
    {
        $list = $this->getMemberRecentList($condition, '', $limit);
        if (!empty($list) && is_array($list)) {
            foreach ($list as $k => $v) {
                $member = array();
                $u_id = $v['t_id'];
                $member['u_id'] = $u_id;
                $member['u_name'] = $v['t_name'];
                $member['avatar'] = get_member_avatar_for_id($u_id);
                $member['recent'] = 1;
                $member['time'] = date("Y-m-d H:i:s", $v['addtime']);
                if (empty($member_list[$u_id])) {
                    $member_list[$u_id] = $member;
                }
                else {
                    $member_list[$u_id]['recent'] = 1;
                    $member_list[$u_id]['time'] = date("Y-m-d H:i:s", $v['addtime']);
                }
            }
        }
        return $member_list;
    }

    /**
     * 获取消息列表
     * @access public
     * @author csdeshang
     * @param type $condition
     * @param type $limit
     * @param type $member_list
     * @return type
     */
    public function getRecentFromList($condition = array(), $limit = 5, $member_list = array())
    {
        $list = $this->getMemberFromList($condition, '', $limit);
        if (!empty($list) && is_array($list)) {
            foreach ($list as $k => $v) {
                $member = array();
                $u_id = $v['f_id'];
                $member['u_id'] = $u_id;
                $member['u_name'] = $v['f_name'];
                $member['avatar'] = get_member_avatar_for_id($u_id);
                $member['recent'] = 1;
                $member['time'] = date("Y-m-d H:i:s", $v['addtime']);
                $member['r_state'] = $v['r_state'];
                if (empty($member_list[$u_id])) {
                    $member_list[$u_id] = $member;
                }
                else {
                    $member_list[$u_id]['recent'] = 1;
                    $member_list[$u_id]['time'] = date("Y-m-d H:i:s", $v['addtime']);
                    $member_list[$u_id]['r_state'] = $v['r_state'];
                }
            }
        }
        return $member_list;
    }

    /**
     * 收到消息的会员记录
     * @access public
     * @author csdeshang
     * @param type $condition 条件
     * @param type $pagesize 分页
     * @param type $limit 限制
     * @return type
     */
    public function getMemberRecentList($condition = array(), $pagesize = '', $limit = 0)
    {
        $list = array();
//        $msg = Db::name('chatmsg')->field('count(DISTINCT t_id) as count')->where($condition)->find();
//        if ($msg['count'] > 0) {
//            $count = intval($msg['count']);
            $field = 't_id,t_name,max(chatmsg_addtime) as addtime';
            $list = Db::name('chatmsg')->field($field)->group('t_id')->where($condition)->limit($limit)->order('addtime desc')->select()->toArray();
//        }
        return $list;
    }

    /**
     * 发出消息的会员记录
     * @access public
     * @author csdeshang
     * @param type $condition 条件
     * @param type $pagesize 分页
     * @param type $limit 限制
     * @return type
     */
    public function getMemberFromList($condition = array(), $pagesize = '', $limit = 0)
    {
        $list = array();
//        $msg = Db::name('chatmsg')->field('count(DISTINCT f_id) as count')->where($condition)->find();
//        if ($msg['count'] > 0) {
//            $count = intval($msg['count']);
            $field = 'max(r_state) as r_state,f_id,f_name,max(chatmsg_addtime) as addtime';
            $list = Db::name('chatmsg')->field($field)->group('f_id')->where($condition)->limit($limit)->order('addtime desc')->select()->toArray();
//        }
        return $list;
    }

    /**
     * 单个会员的消息记录
     * @access public
     * @author csdeshang
     * @param type $condition 条件
     * @param type $pagesize 分页
     * @param type $order 排序
     * @return type
     */
    public function getChatlogFromList($special_condition = array(), $pagesize = 10, $order = 'm_id desc')
    {
        $list = array();
        $f_id = intval($special_condition['f_id']);
        if ($f_id > 0) {
            $t_id = intval($special_condition['t_id']);
            if ($t_id > 0) {
                $condition_sql = " ((f_id = '" . $f_id . "' and t_id = '" . $t_id . "') or (f_id = '" . $t_id . "' and t_id = '" . $f_id . "'))";
            }
            else {
                $condition_sql = " (f_id = '" . $f_id . "' or t_id = '" . $f_id . "')";
            }
            $add_time_from = trim($special_condition['add_time_from']);
            if (!empty($add_time_from)) {
                $add_time_from = strtotime($add_time_from);
                $condition_sql .= " and chatlog_addtime >= '" . $add_time_from . "'";
            }
            $add_time_to = trim($special_condition['add_time_to']);
            if (!empty($add_time_to)) {
                $add_time_to = strtotime($add_time_to) + 60 * 60 * 24;
                $condition_sql .= " and chatlog_addtime <= '" . $add_time_to . "'";
            }
            $t_msg = isset($special_condition['t_msg'])?trim($special_condition['t_msg']):'';
            if (!empty($t_msg)) {
                $condition_sql .= " and t_msg like '%" . $t_msg . "%'";
            }
            $list = $this->getChatlogList($condition_sql, $pagesize, $order);
        }
        return $list;
    }

    /**
     * 会员相关的信息
     * @access public
     * @author csdeshang
     * @param type $condition 条件
     * @return type
     */
    public function getMemberInfo($condition)
    {
        $member_model = model('member');
        $member = $member_model->getMemberInfo($condition, 'member_id,member_name,member_avatar');
        if(empty($member)){
            return;
        }
        $member['store_id'] = '';
        $member['store_name'] = '';
        $member['store_avatar'] = '';
        $member['grade_id'] = '';
        $member['member_avatar'] = get_member_avatar_for_id($member['member_id']);
        $seller_model = model('seller');
        $seller = $seller_model->getSellerInfo(array('member_id' => $member['member_id']));
        if (!empty($seller) && $seller['store_id'] > 0) {
            $store_info = Db::name('store')->field('store_id,store_name,grade_id,store_avatar')->where(array('store_id' => $seller['store_id']))->find();
            if (is_array($store_info) && !empty($store_info)) {
                $member['store_id'] = $store_info['store_id'];
                $member['store_name'] = $store_info['store_name'];
                $member['seller_name'] = $seller['seller_name'];
                $member['grade_id'] = $store_info['grade_id'];
                $member['store_avatar'] = get_store_logo($store_info['store_avatar']);
            }
        }
        return $member;
    }

    /**
     * 商品相关的信息
     * @access public
     * @author csdeshang
     * @param type $goods_id 商品id
     * @return type
     */
    public function getGoodsInfo($goods_id)
    {
        $goods = array();
        $goods_model = model('goods');
        $goods_id = intval($goods_id);
        $goods = $goods_model->getGoodsInfoByID($goods_id);
        if (is_array($goods) && !empty($goods)) {
            $goods['url'] = (string)url('home/Goods/index',['goods_id'=>$goods['goods_id']]);
            $goods['goods_promotion_price'] = ds_price_format($goods['goods_promotion_price']);
            $goods['pic'] = goods_thumb($goods, 60);
            $goods['pic24'] = goods_thumb($goods, 240);
        }
        return $goods;
    }

    /**
     * 获取聊天记录数
     * @access public
     * @author csdeshang
     * @param type $condition 条件
     * @return type
     */
    public function getChatmsgCount($condition)
    {
        return (int)Db::name('chatmsg')->where($condition)->count();
    }
    
    /**
     * 删除聊天记录
     * @access public
     * @author csdeshang
     * @param array $condition 条件
     * @return bool
     */
    public function delChatmsg($condition)
    {
        if(empty($condition)){
            return;
        }
        return Db::name('chatmsg')->where($condition)->delete();
    }
    
}