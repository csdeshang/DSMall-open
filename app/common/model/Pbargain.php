<?php

/**
 * 砍价活动模型 
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
class Pbargain extends BaseModel {

    public $page_info;

    const PINTUAN_STATE_CANCEL = 0;
    const PINTUAN_STATE_TO_BEGIN = 1;
    const PINTUAN_STATE_NORMAL = 2;
    const PINTUAN_STATE_END = 3;

    private $bargain_state_array = array(
        self::PINTUAN_STATE_CANCEL => '已取消',
        self::PINTUAN_STATE_TO_BEGIN => '待开始',
        self::PINTUAN_STATE_NORMAL => '进行中',
        self::PINTUAN_STATE_END => '已结束'
    );

    /**
     * 读取砍价列表
     * @access public
     * @author csdeshang
     * @param array $condition 查询条件
     * @param int $pagesize 分页数
     * @param string $order 排序
     * @param string $field 所需字段
     * @return array 砍价列表
     */
    public function getBargainList($condition, $pagesize = null, $order = 'bargain_id desc', $field = '*') {
        if($pagesize){
            $res = Db::name('pbargain')->field($field)->where($condition)->order($order)->paginate(['list_rows'=>$pagesize,'query' => request()->param()],false);
            $bargain_list = $res->items();
            $this->page_info = $res;
            return $bargain_list;
        }else{
            return Db::name('pbargain')->field($field)->where($condition)->order($order)->select()->toArray();
        }
    }
    /**
     * 读取砍价列表
     * @access public
     * @author csdeshang
     * @param array $condition 查询条件
     * @param int $pagesize 分页数
     * @param string $order 排序
     * @param string $field 所需字段
     * @return array 砍价列表
     */
    public function getOnlineBargainList($condition, $pagesize = null, $order = 'bargain_id desc', $field = '*') {
        $condition[]=array('bargain_state','=',self::PINTUAN_STATE_NORMAL);
        $condition[]=array('bargain_endtime','>',TIMESTAMP);
        $bargain_list = $this->getBargainList($condition, $pagesize, $order, $field);
        return $bargain_list;
    }

    /**
     * 根据条件读取砍价信息
     * @access public
     * @author csdeshang
     * @param array $condition 查询条件
     * @return array 砍价信息
     */
    public function getBargainInfo($condition) {
        $bargain_info = Db::name('pbargain')->where($condition)->find();
        return $bargain_info;
    }

    /**
     * 根据砍价编号读取砍价信息
     * @access public
     * @author csdeshang
     * @param array $bargain_id 砍价活动编号
     * @param int $store_id 如果提供店铺编号，判断是否为该店铺活动，如果不是返回null
     * @return array 砍价信息
     */
    public function getBargainInfoByID($bargain_id, $store_id = 0) {
        if (intval($bargain_id) <= 0) {
            return null;
        }

        $condition = array();
        $condition[] = array('bargain_id','=',$bargain_id);
        $bargain_info = $this->getBargainInfo($condition);
        if ($store_id > 0 && $bargain_info['store_id'] != $store_id) {
            return null;
        } else {
            return $bargain_info;
        }
    }
    
    public function getOnlineBargainInfoByID($bargain_id){
        if (intval($bargain_id) <= 0) {
            return null;
        }
        $condition = array();
        $condition[] = array('bargain_id','=',$bargain_id);
        $condition[] = array('bargain_state','=',self::PINTUAN_STATE_NORMAL);
        $condition[] = array('bargain_endtime','>',TIMESTAMP);
        $bargain_info = $this->getBargainInfo($condition);
        return $bargain_info;
    }

    /**
     * 砍价状态数组
     * @access public
     * @author csdeshang
     * @return type
     */
    public function getBargainStateArray() {
        return $this->bargain_state_array;
    }

    /**
     * 增加
     * @access public
     * @author csdeshang
     * @param array $data 数据
     * @return type
     */
    public function addBargain($data) {
        $flag= Db::name('pbargain')->insertGetId($data);
        if($flag){
            // 发布砍价锁定商品
            $this->_lockGoods($data['bargain_goods_commonid'],$data['bargain_goods_id']);
        }
        return $flag;
    }

    /**
     * 编辑更新
     * @param type $update 更新数据
     * @param type $condition 条件
     * @return type
     */
    public function editBargain($update, $condition) {
        $goods_ids=Db::name('pbargain')->where($condition)->column('bargain_goods_id');
        foreach($goods_ids as $goods_id){
            $this->_dGoodsBargainCache($goods_id);
        }
        return Db::name('pbargain')->where($condition)->update($update);
    }

    /**
     * 指定砍价活动结束,参团成功的继续参团,不成功的保持默认.
     * @access public
     * @author csdeshang
     * @param type $condition
     * @return type
     */
    public function endBargain($condition=array()) {
        $condition[]=array('bargain_state','=',self::PINTUAN_STATE_NORMAL);
        $goods_commonid=Db::name('pbargain')->where($condition)->column('bargain_goods_commonid');
        $goods_id=Db::name('pbargain')->where($condition)->column('bargain_goods_id');
        $data['bargain_state'] = self::PINTUAN_STATE_END;
        $flag= Db::name('pbargain')->where($condition)->update($data);
        if($flag){
            if(!empty($goods_commonid)){
                $this->_unlockGoods($goods_commonid,$goods_id);
            }
        }
        return $flag;
    }
    /**
     * 删除砍价活动
     * @access public
     * @author csdeshang
     * @param array $condition 检索条件
     * @return boolean
     */
    public function delBargain($condition) {
        return Db::name('pbargain')->where($condition)->delete();
    }
    /**
     * 取消砍价活动
     * @access public
     * @author csdeshang
     * @param type $condition 条件
     * @return type
     */
    public function cancelBargain($condition) {
        $goods_commonid = Db::name('pbargain')->where($condition)->column('bargain_goods_commonid');
        $goods_id = Db::name('pbargain')->where($condition)->column('bargain_goods_id');
        $update = array();
        $update['bargain_state'] = self::PINTUAN_STATE_CANCEL;
        $flag= $this->editBargain($update, $condition);
        if($flag){
            if(!empty($goods_commonid)){
                $this->_unlockGoods($goods_commonid,$goods_id);
            }
        }
        return $flag;
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
     * 获取砍价是否可编辑状态
     * @access public
     * @author csdeshang
     * @param type $bargain_info 砍价信息
     * @return boolean
     */
    public function getBargainBtn($bargain_info) {
        if (!$bargain_info) {
            return false;
        }
        if ($bargain_info['bargain_state'] == self::PINTUAN_STATE_TO_BEGIN && $bargain_info['bargain_begintime'] > TIMESTAMP) {
            $bargain_info['editable'] = true;
        } else {
            $bargain_info['editable'] = false;
        }

        return $bargain_info;
    }

    /**
     * 获取状态文字
     * @access public
     * @author csdeshang
     * @param type $bargain_info 砍价信息
     * @return boolean
     */
    public function getBargainStateText($bargain_info) {
        if (!$bargain_info) {
            return false;
        }
        $bargain_state_text = $this->bargain_state_array[$bargain_info['bargain_state']];
        return $bargain_state_text;
    }

    /**
     * 根据商品编号查询是否有可用砍价活动，如果有返回抢购信息，没有返回null
     * @param type $goods_id 商品id
     * @return array
     */
    public function getBargainInfoByGoodsID($goods_id) {
        $info = $this->_rGoodsBargainCache($goods_id);
        if (empty($info)) {
            $condition = array();
            $condition[] = array('bargain_goods_id','=',$goods_id);
            $condition[] = array('bargain_state','=',self::PINTUAN_STATE_NORMAL);
            $condition[] = array('bargain_endtime','>',TIMESTAMP);
            $bargain_info = $this->getBargainInfo($condition);


            //序列化存储到缓存
            $info['info'] = serialize($bargain_info);
            $this->_wGoodsBargainCache($goods_id, $info);
        }
        $bargain_goods_info = unserialize($info['info']);
        if (!empty($bargain_goods_info) && ($bargain_goods_info['bargain_state']!=2 || $bargain_goods_info['bargain_begintime'] > TIMESTAMP || $bargain_goods_info['bargain_endtime'] < TIMESTAMP)) {
            $bargain_goods_info = array();
        }
        return $bargain_goods_info;
    }

    /**
     * 读取商品抢购缓存
     * @access public
     * @author csdeshang
     * @param type $goods_id 商品id
     * @return type
     */
    private function _rGoodsBargainCache($goods_id) {
        return rcache($goods_id, 'goods_bargain');
    }

    /**
     * 写入商品抢购缓存
     * @access public
     * @author csdeshang
     * @param type $goods_id ID
     * @param type $info 信息
     * @return type
     */
    private function _wGoodsBargainCache($goods_id, $info) {
        return wcache($goods_id, $info, 'goods_bargain');
    }

    /**
     * 删除商品抢购缓存
     * @access public
     * @author csdeshang
     * @param int $goods_id 商品ID
     * @return boolean
     */
    public function _dGoodsBargainCache($goods_id) {
        return dcache($goods_id, 'goods_bargain');
    }

}
