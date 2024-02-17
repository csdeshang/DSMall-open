<?php

/**
 * 预售活动模型 
 *
 */

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
class Presell extends BaseModel {

    public $page_info;

    const PRESELL_STATE_CANCEL = 0;
    const PRESELL_STATE_TO_BEGIN = 1;
    const PRESELL_STATE_NORMAL = 2;
    const PRESELL_STATE_END = 3;

    private $presell_state_array = array(
        self::PRESELL_STATE_CANCEL => '已取消',
        self::PRESELL_STATE_TO_BEGIN => '待开始',
        self::PRESELL_STATE_NORMAL => '进行中',
        self::PRESELL_STATE_END => '已结束'
    );

    /**
     * 读取预售列表
     * @access public
     * @author csdeshang
     * @param array $condition 查询条件
     * @param int $pagesize 分页数
     * @param string $order 排序
     * @param string $field 所需字段
     * @return array 预售列表
     */
    public function getPresellList($condition, $pagesize = null, $order = 'presell_id desc', $field = '*') {
        if($pagesize){
            $res = Db::name('presell')->field($field)->where($condition)->order($order)->paginate(['list_rows'=>$pagesize,'query' => request()->param()],false);
            $presell_list = $res->items();
            $this->page_info = $res;
            return $presell_list;
        }else{
            return Db::name('presell')->field($field)->where($condition)->order($order)->select()->toArray();
        }
    }
    /**
     * 读取预售列表
     * @access public
     * @author csdeshang
     * @param array $condition 查询条件
     * @param int $pagesize 分页数
     * @param string $order 排序
     * @param string $field 所需字段
     * @return array 预售列表
     */
    public function getOnlinePresellList($condition, $pagesize = null, $order = 'presell_id desc', $field = '*') {
        $condition[]=array('presell_state','=',self::PRESELL_STATE_NORMAL);
        $condition[]=array('presell_end_time','>',TIMESTAMP);
        $presell_list = $this->getPresellList($condition, $pagesize, $order, $field);
        return $presell_list;
    }

    /**
     * 根据条件读取预售信息
     * @access public
     * @author csdeshang
     * @param array $condition 查询条件
     * @return array 预售信息
     */
    public function getPresellInfo($condition) {
        $presell_info = Db::name('presell')->where($condition)->find();
        return $presell_info;
    }

    /**
     * 根据预售编号读取预售信息
     * @access public
     * @author csdeshang
     * @param array $presell_id 预售活动编号
     * @param int $store_id 如果提供店铺编号，判断是否为该店铺活动，如果不是返回null
     * @return array 预售信息
     */
    public function getPresellInfoByID($presell_id, $store_id = 0) {
        if (intval($presell_id) <= 0) {
            return null;
        }

        $condition = array();
        $condition[] = array('presell_id','=',$presell_id);
        $presell_info = $this->getPresellInfo($condition);
        if ($store_id > 0 && $presell_info['store_id'] != $store_id) {
            return null;
        } else {
            return $presell_info;
        }
    }
    
    public function getOnlinePresellInfoByID($presell_id){
        if (intval($presell_id) <= 0) {
            return null;
        }
        $condition = array();
        $condition[] = array('presell_id','=',$presell_id);
        $condition[] = array('presell_state','=',self::PRESELL_STATE_NORMAL);
        $condition[] = array('presell_end_time','>',TIMESTAMP);
        $presell_info = $this->getPresellInfo($condition);
        return $presell_info;
    }

    /**
     * 预售状态数组
     * @access public
     * @author csdeshang
     * @return type
     */
    public function getPresellStateArray() {
        return $this->presell_state_array;
    }

    /**
     * 增加
     * @access public
     * @author csdeshang
     * @param array $data 数据
     * @return type
     */
    public function addPresell($data) {
        $flag= Db::name('presell')->insertGetId($data);
        if($flag){
            // 发布预售锁定商品
            $this->_lockGoods($data['goods_commonid'],$data['goods_id']);
        }
        return $flag;
    }

    /**
     * 编辑更新
     * @param type $update 更新数据
     * @param type $condition 条件
     * @return type
     */
    public function editPresell($update, $condition) {
        $goods_ids=Db::name('presell')->where($condition)->column('goods_id');
        foreach($goods_ids as $goods_id){
            $this->_dGoodsPresellCache($goods_id);
        }
        return Db::name('presell')->where($condition)->update($update);
    }

    /**
     * 指定预售活动结束,参团成功的继续参团,不成功的保持默认.
     * @access public
     * @author csdeshang
     * @param type $condition
     * @return type
     */
    public function endPresell($condition=array()) {
        $condition[]=array('presell_state','=',self::PRESELL_STATE_NORMAL);
        $goods_commonid=Db::name('presell')->where($condition)->column('goods_commonid');
        $goods_id=Db::name('presell')->where($condition)->column('goods_id');
        $data['presell_state'] = self::PRESELL_STATE_END;
        $flag= Db::name('presell')->where($condition)->update($data);
        if($flag){
            if(!empty($goods_commonid)){
                $this->_unlockGoods($goods_commonid,$goods_id);
            }
        }
        return $flag;
    }

    /**
     * 取消预售活动
     * @access public
     * @author csdeshang
     * @param type $condition 条件
     * @return type
     */
    public function cancelPresell($condition) {
        $goods_commonid = Db::name('presell')->where($condition)->column('goods_commonid');
        $goods_id = Db::name('presell')->where($condition)->column('goods_id');
        $update = array();
        $update['presell_state'] = self::PRESELL_STATE_CANCEL;
        $flag= $this->editPresell($update, $condition);
        if($flag){
            if(!empty($goods_commonid)){
                $this->_unlockGoods($goods_commonid,$goods_id);
            }
        }
        return $flag;
    }
    /**
     * 删除预售活动
     * @access public
     * @author csdeshang
     * @param array $condition 检索条件
     * @return boolean
     */
    public function delPresell($condition) {
        return Db::name('presell')->where($condition)->delete();
    }
     /**
     * 锁定商品
     * @access private
     * @author csdeshang
     * @param type $goods_commonid 商品编号
     */
    private function _lockGoods($goods_commonid,$goods_id)
    {
        $condition = array();
        $condition[] = array('goods_commonid','=',$goods_commonid);

        $goods_model = model('goods');
        $goods_model->editGoodsCommonLock($condition);
        
        $condition = array();
        $condition[] = array('goods_id','=',$goods_id);
        $goods_model->editGoodsLock($condition);
    }

    /**
     * 解锁商品
     * @access private
     * @author csdeshang
     * @param type $goods_commonid 商品编号ID
     */
    private function _unlockGoods($goods_commonid,$goods_id)
    {
        $goods_model = model('goods');
        $goods_model->editGoodsUnlock(array(array('goods_id' ,'in', $goods_id)));
        $temp=Db::name('goods')->where(array(array('goods_id','in',$goods_id),array('goods_lock','=',1)))->column('goods_commonid');
        if(!empty($temp)){
            $goods_commonid=array_diff($goods_commonid,$temp);
        }
        if(!empty($goods_commonid)){
            $goods_model->editGoodsCommonUnlock(array(array('goods_commonid' ,'in', $goods_commonid)));
        }
    }
    
    /**
     * 获取预售是否可编辑状态
     * @access public
     * @author csdeshang
     * @param type $presell_info 预售信息
     * @return boolean
     */
    public function getPresellBtn($presell_info) {
        if (!$presell_info) {
            return false;
        }
        if ($presell_info['presell_state'] == self::PRESELL_STATE_TO_BEGIN && $presell_info['presell_start_time'] > TIMESTAMP) {
            $presell_info['editable'] = true;
        } else {
            $presell_info['editable'] = false;
        }

        return $presell_info;
    }

    /**
     * 获取状态文字
     * @access public
     * @author csdeshang
     * @param type $presell_info 预售信息
     * @return boolean
     */
    public function getPresellStateText($presell_info) {
        if (!$presell_info) {
            return false;
        }
        $presell_state_text = $this->presell_state_array[$presell_info['presell_state']];
        return $presell_state_text;
    }

    /**
     * 根据商品编号查询是否有可用预售活动，如果有返回预售信息，没有返回null
     * @param type $goods_id 商品id
     * @return array
     */
    public function getPresellInfoByGoodsID($goods_id) {
        $info = $this->_rGoodsPresellCache($goods_id);
        if (empty($info)) {
            $condition = array();
            $condition[] = array('goods_id','=',$goods_id);
            $condition[] = array('presell_state','=',self::PRESELL_STATE_NORMAL);
            $condition[] = array('presell_end_time','>',TIMESTAMP);
            $presell_info = $this->getPresellInfo($condition);


            //序列化存储到缓存
            $info['info'] = serialize($presell_info);
            $this->_wGoodsPresellCache($goods_id, $info);
        }
        $presell_goods_info = unserialize($info['info']);
        if (!empty($presell_goods_info) && ($presell_goods_info['presell_state']!=2 || $presell_goods_info['presell_start_time'] > TIMESTAMP || $presell_goods_info['presell_end_time'] < TIMESTAMP)) {
            $presell_goods_info = array();
        }
        return $presell_goods_info;
    }

    /**
     * 读取商品预售缓存
     * @access public
     * @author csdeshang
     * @param type $goods_id 商品id
     * @return type
     */
    private function _rGoodsPresellCache($goods_id) {
        return rcache($goods_id, 'goods_presell');
    }

    /**
     * 写入商品预售缓存
     * @access public
     * @author csdeshang
     * @param type $goods_id ID
     * @param type $info 信息
     * @return type
     */
    private function _wGoodsPresellCache($goods_id, $info) {
        return wcache($goods_id, $info, 'goods_presell');
    }

    /**
     * 删除商品预售缓存
     * @access public
     * @author csdeshang
     * @param int $goods_id 商品ID
     * @return boolean
     */
    public function _dGoodsPresellCache($goods_id) {
        return dcache($goods_id, 'goods_presell');
    }

}
