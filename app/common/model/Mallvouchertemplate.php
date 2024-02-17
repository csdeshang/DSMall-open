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
class Mallvouchertemplate extends BaseModel
{

    const VOUCHER_STATE_UNUSED = 1;
    const VOUCHER_STATE_USED = 2;
    const VOUCHER_STATE_EXPIRE = 3;
    public $page_info;
    private $voucher_state_array = array(
        self::VOUCHER_STATE_UNUSED => '未使用', self::VOUCHER_STATE_USED => '已使用', self::VOUCHER_STATE_EXPIRE => '已过期',
    );


    private $templatestate_arr;

    /**
     * 构造函数
     * @access public
     * @author csdeshang
     */
    public function __construct()
    {
        parent::__construct();
    }


    /**
     * 返回当前可用的代金券列表,每种类型(模板)的代金券里取出一个代金券码(同一个模板所有码面额和到期时间都一样)
     * @access public
     * @author csdeshang
     * @param array $condition 条件
     * @param int $goods_total 商品总金额
     * @return string
     */
    public function getCurrentAvailableMallVoucherUser($condition = array(), $goods_total = 0)
    {
        $condition[] = array('mallvoucheruser_state', '=', 1);
        $condition[] = array('mallvoucheruser_enddate', '>', TIMESTAMP);
        $mallvoucheruser_list = Db::name('mallvoucheruser')->where($condition)->select()->toArray();
        if(!empty($mallvoucheruser_list)){
            $mallvoucheruser_list = ds_change_arraykey($mallvoucheruser_list,'mallvouchertemplate_id');
        }
        foreach ($mallvoucheruser_list as $key => $voucher) {
            if ($goods_total < $voucher['mallvoucheruser_limit']) {
                unset($mallvoucheruser_list[$key]);
            }
            else {
                $mallvoucheruser_list[$key]['desc'] = sprintf('%s 面额%s元 ',$voucher['mallvoucheruser_title'], $voucher['mallvoucheruser_price']);
                if ($voucher['mallvoucheruser_limit'] > 0) {
                    $mallvoucheruser_list[$key]['desc'] .= sprintf(' 消费满%s可用', $voucher['mallvoucheruser_limit']);
                }
            }
        }
        return $mallvoucheruser_list;
    }
    
    /**
     * 获得平台代金券列表
     * @access public
     * @author csdeshang
     * @return type
     */
    public function getMallvouchertemplateList($condition,$pagesize='',$order='mallvouchertemplate_price asc')
    {
        if($pagesize){
            $voucherprice_list = Db::name('mallvouchertemplate')->where($condition)->order($order)->paginate(['list_rows'=>10,'query' => request()->param()],false);
            $this->page_info = $voucherprice_list;
            return $voucherprice_list->items();
        } else {
            return Db::name('mallvouchertemplate')->where($condition)->order($order)->select()->toArray();
        }
    }
    
    /**
     * 获取单个代金券
     * @param type $where
     * @return type
     */
    public function getOneMallvouchertemplate($condition = array()){
        $malloucherinfotemplate = Db::name('mallvouchertemplate')->where($condition)->find();
        
        if ( $malloucherinfotemplate['mallvouchertemplate_enddate'] > TIMESTAMP) {
            $malloucherinfotemplate['editable'] = true;
        } else {
            $malloucherinfotemplate['editable'] = false;
        }
        return $malloucherinfotemplate;
    }
    
    /**
     * 增加代金券面额
     * @param type $data
     * @return type
     */
    public function addMallvouchertemplate($data){
        return Db::name('mallvouchertemplate')->insert($data);
    }
    
    /**
     * 修改代金券类型
     * @param type $where
     * @param type $data
     * @return type
     */
    public function editMallvouchertemplate($condition,$data){
        return Db::name('mallvouchertemplate')->where($condition)->update($data);
    }
    
    /**
     * 删除代金券
     * @param type $condition
     * @return type
     */
    public function delMallvouchertemplate($condition){
        return Db::name('mallvouchertemplate')->where($condition)->delete();
    }
    

    
    /**
     * 查询可兑换代金券模板详细信息，包括店铺信息
     * @access public
     * @author csdeshang
     * @param type $vid
     * @param type $member_id 会员id
     * @return type
     */
    public function getCanChangeTemplateInfo($vid, $member_id,$no_point=false)
    {
        echo $no_point;
        if ($vid <= 0 || $member_id <= 0) {
            return array('state' => false, 'msg' => '参数错误');
        }
        //查询可用代金券模板
        $where = array();
        $where[] = array('mallvouchertemplate_id','=',$vid);
        $where[] = array('mallvouchertemplate_startdate','<',TIMESTAMP);
        $where[] = array('mallvouchertemplate_enddate','>',TIMESTAMP);
        $template_info = $this->getOneMallvouchertemplate($where);
        if (empty($template_info) || $template_info['mallvouchertemplate_quantity'] <= $template_info['mallvouchertemplate_giveout']) {//代金券不存在或者已兑换完
            return array('state' => false, 'msg' => '代金券已兑换完');
        }
        $member_model = model('member');
        $member_info = $member_model->getMemberInfoByID($member_id);
        if (empty($member_info)) {
            return array('state' => false, 'msg' => '参数错误');
        }
        if(!$no_point){
            //验证会员积分是否足够
            if ( $template_info['mallvouchertemplate_points'] > 0) {
                if (intval($member_info['member_points']) < intval($template_info['mallvouchertemplate_points'])) {
                    return array('state' => false, 'msg' => '您的积分不足，暂时不能兑换该代金券');
                }
            }
        }
        
        //查询代金券列表
        $where = array();
        $where[] = array('mallvoucheruser_ownerid','=',$member_id);
    
        $mallvoucheruser_list = $this->getMallvoucheruserList($where);
        if (!empty($mallvoucheruser_list)) {
            if(!$no_point){
                $mallvoucher_count = 0; //兑换的代金券数量
                $mallvoucherone_count = 0; //该张代金券兑换的数量
                foreach ($mallvoucheruser_list as $k => $v) {
                    //如果代金券未用且未过期
                    if ($v['mallvoucheruser_state'] == 1 && $v['mallvoucheruser_enddate'] > TIMESTAMP) {
                        $mallvoucher_count += 1;
                    }
                    if ($v['mallvouchertemplate_id'] == $template_info['mallvouchertemplate_id']) {
                        $mallvoucherone_count += 1;
                    }
                }
    
                //买家最多只能拥有同一个店铺尚未消费抵用的店铺代金券最大数量的验证
                if ($mallvoucher_count >= intval(config('ds_config.voucher_buyertimes_limit'))) {
                    $message = sprintf('您的可用代金券已有%s张,不可再兑换了', config('ds_config.voucher_buyertimes_limit'));
                    return array('state' => false, 'msg' => $message);
                }
                //同一张代金券最多能兑换的次数
                if (!empty($template_info['mallvouchertemplate_eachlimit']) && $mallvoucherone_count >= $template_info['mallvouchertemplate_eachlimit']) {
                    $message = sprintf('该代金券您已兑换%s次，不可再兑换了', $template_info['mallvouchertemplate_eachlimit']);
                    return array('state' => false, 'msg' => $message);
                }
            }
        }
        return array('state' => true, 'info' => $template_info);
    }
    
    /**
     * 积分兑换代金券
     * @access public
     * @author csdeshang
     * @param type $template_info 信息模板
     * @param type $member_id 会员ID
     * @param type $member_name 会员名
     * @return type
     */
    public function exchangeMallvoucher($template_info, $member_id, $member_name = '',$no_point=false)
    {
        if (intval($member_id) <= 0 || empty($template_info)) {
            return array('state' => false, 'msg' => '参数错误');
        }
        //查询会员信息
        if (!$member_name) {
            $member_info = model('member')->getMemberInfoByID($member_id);
            if (empty($template_info)) {
                return array('state' => false, 'msg' => '参数错误');
            }
            $member_name = $member_info['member_name'];
        }
        //添加代金券信息
        $insert_arr = array();
        $insert_arr['mallvouchertemplate_id'] = $template_info['mallvouchertemplate_id'];
        $insert_arr['mallvoucheruser_code'] = $this->getMallvoucherCode($member_id);
        $insert_arr['mallvoucheruser_title'] = $template_info['mallvouchertemplate_title'];
        $insert_arr['mallvoucheruser_startdate'] = $template_info['mallvouchertemplate_startdate'];
        $insert_arr['mallvoucheruser_enddate'] = $template_info['mallvouchertemplate_enddate'];
        $insert_arr['mallvoucheruser_price'] = $template_info['mallvouchertemplate_price'];
        $insert_arr['mallvoucheruser_limit'] = $template_info['mallvouchertemplate_limit'];
        $insert_arr['mallvoucheruser_state'] = 1;
        $insert_arr['mallvoucheruser_activedate'] = TIMESTAMP;
        $insert_arr['mallvoucheruser_ownerid'] = $member_id;
        $insert_arr['mallvoucheruser_ownername'] = $member_name;
        $insert_arr['mallvouchertemplate_gcidarr'] = $template_info['mallvouchertemplate_gcidarr'];
        $insert_arr['mallvouchertemplate_gcid'] = $template_info['mallvouchertemplate_gcid'];
        $insert_arr['mallvouchertemplate_points'] = $template_info['mallvouchertemplate_points'];
        
        $result = Db::name('mallvoucheruser')->insertGetId($insert_arr);
        if (!$result) {
            return array('state' => false, 'msg' => '兑换失败');
        }
        if(!$no_point){
            //扣除会员积分
            if ($template_info['mallvouchertemplate_points'] > 0 ) {
                $points_arr['pl_memberid'] = $member_id;
                $points_arr['pl_membername'] = $member_name;
                $points_arr['pl_points'] = -$template_info['mallvouchertemplate_points'];
                $points_arr['pl_desc'] = lang('home_voucher') . lang('points_pointorderdesc');
                $result = model('points')->savePointslog('app', $points_arr, true);
                if (!$result) {
                    return array('state' => false, 'msg' => '兑换失败');
                }
            }
        }
        if ($result) {
            //代金券模板的兑换数增加
            $result = $this->editMallvouchertemplate(array('mallvouchertemplate_id' => $template_info['mallvouchertemplate_id']), array(
                'mallvouchertemplate_giveout' => Db::raw('mallvouchertemplate_giveout+1')
            ));
            if (!$result) {
                return array('state' => false, 'msg' => '兑换失败');
            }
            return array('state' => true, 'msg' => '兑换成功');
        }
        else {
            return array('state' => false, 'msg' => '兑换失败');
        }
    }
    
    /**
     * 获取买家代金券列表
     * @access public
     * @author csdeshang
     * @param int $member_id 用户编号
     * @param int $mallvoucher_state 代金券状态
     * @param int $pagesize 分页数
     * @return array
     */
    public function getMemberMallvoucherList($member_id, $mallvoucher_state, $pagesize = null)
    {
        if (empty($member_id)) {
            return false;
        }
    
        //更新过期代金券状态
        $this->_checkVoucherExpire($member_id);
    
        $where = array();
        $where[] = array('mallvoucheruser_ownerid','=',$member_id);
        $mallvoucher_state = intval($mallvoucher_state);
        if (intval($mallvoucher_state) > 0 && array_key_exists($mallvoucher_state, $this->voucher_state_array)) {
            $where[] = array('mallvoucheruser_state','=',$mallvoucher_state);
        }
    
        if($pagesize){
        $list = Db::name('mallvoucheruser')->where($where)->order('mallvoucheruser_id desc')->paginate(['list_rows'=>$pagesize,'query' => request()->param()],false);
        $this->page_info=$list;
        $list=$list->items();
        }else{
            $list=Db::name('mallvoucheruser')->where($where)->order('mallvoucheruser_id desc')->select()->toArray();
        }
        if (!empty($list) && is_array($list)) {
            foreach ($list as $key => $val) {
   
                //代金券状态文字
                $list[$key]['mallvoucheruser_state_text'] = $this->voucher_state_array[$val['mallvoucheruser_state']];
                $list[$key]['mallvoucheruser_end_date_text'] = $val['mallvoucheruser_enddate'] ? @date('Y.m.d', $val['mallvoucheruser_enddate']) : '';
            }
        }
        return $list;
    }
    
    
    /**
     * 获取代金券的领取列表
     * @access public
     * @author csdeshang
     * @param type $where 条件
     * @param type $field 字段
     * @return type
     */
    public function getMallvoucheruserList($condition,$pagesize='',$order='mallvoucheruser_activedate desc') {
        if($pagesize){
            $list = Db::name('mallvoucheruser')->where($condition)->order($order)->paginate(['list_rows'=>10,'query' => request()->param()],false);
            $this->page_info = $list;
            $mallvoucheruser_list = $list->items();
        } else {
            $mallvoucheruser_list = Db::name('mallvoucheruser')->where($condition)->order($order)->select()->toArray();
        }
        if (!empty($mallvoucheruser_list) && is_array($mallvoucheruser_list)) {
            foreach ($mallvoucheruser_list as $key => $val) {
                //代金券状态文字
                $mallvoucheruser_list[$key]['mallvoucheruser_state_text'] = $this->voucher_state_array[$val['mallvoucheruser_state']];
            }
        }
        return $mallvoucheruser_list;
    }
    
    /**
     * 更新过期代金券状态
     * @access public
     * @author csdeshang
     * @param type $member_id 会员ID
     * @return type 
     */
    private function _checkVoucherExpire($member_id)
    {
        $condition = array();
        $condition[] = array('mallvoucheruser_ownerid','=',$member_id);
        $condition[] = array('mallvoucheruser_state','=',self::VOUCHER_STATE_UNUSED);
        $condition[] = array('mallvoucheruser_enddate','<', TIMESTAMP);
    
        Db::name('mallvoucheruser')->where($condition)->update(array('mallvoucheruser_state' => self::VOUCHER_STATE_EXPIRE));
    }
    
    /**
     * 返回代金券状态数组
     * @access public
     * @author csdeshang
     * @return array
     */
    public function getVoucherStateArray()
    {
        return $this->voucher_state_array;
    }
    
    /**
     * 更新平台代金券信息
     * @param type $data 数据
     * @param type $condition 条件
     * @param type $member_id 会员id
     * @return type
     */
    public function editMallVoucherUser($data, $condition, $member_id = 0)
    {
        $result = Db::name('mallvoucheruser')->where($condition)->update($data);
        if ($result && $member_id > 0) {
            wcache($member_id, array('mallvoucher_count' => null), 'm_mallvoucher');
        }
        return $result;
    }
    
    /**
     * 更新使用的代金券状态
     * @param $input_voucher_list
     * @throws Exception
     */
    public function editMallvoucherState($mallvoucher_list)
    {
        $update = $this->editMallVoucherUser(array('mallvoucheruser_state' => 2), array('mallvoucheruser_id' => $mallvoucher_list['mallvoucheruser_id']), $mallvoucher_list['mallvoucheruser_ownerid']);
        if ($update) {
            $this->editMallvouchertemplate(array('mallvouchertemplate_id' => $mallvoucher_list['mallvouchertemplate_id']),array('mallvouchertemplate_used'=>Db::raw('mallvouchertemplate_used+1')));
        }
        else {
            return ds_callback(false, '更新平台代金券状态失败');
        }
        
        return ds_callback(true);
    }
    
    /**
     * 获取代金券编码
     * @access public
     * @author csdeshang
     * @staticvar int $num
     * @param type $member_id 会员id
     * @return type
     */
    public function getMallvoucherCode($member_id = 0)
    {
        static $num = 1;
        $sign_arr = array();
        $sign_arr[] = sprintf('%02d', mt_rand(10, 99));
        $sign_arr[] = sprintf('%03d', (float)microtime() * 1000);
        $sign_arr[] = sprintf('%010d', TIMESTAMP - 946656000);
        if ($member_id) {
            $sign_arr[] = sprintf('%03d', (int)$member_id % 1000);
        }
        else {
            //自增变量
            $tmpnum = 0;
            if ($num > 99) {
                $tmpnum = substr($num, -1, 2);
            }
            else {
                $tmpnum = $num;
            }
            $sign_arr[] = sprintf('%02d', $tmpnum);
            $sign_arr[] = mt_rand(1, 9);
        }
        $code = implode('', $sign_arr);
        $num += 1;
        return $code;
    }
    
    
    
}

?>
