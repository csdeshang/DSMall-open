<?php

/**
 * 砍价订单辅助,用于判断砍价订单是归属于哪一个团长的 
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
class Pbargainorder extends BaseModel {

    public $page_info;
    public $lock=false;
    const PINTUANORDER_STATE_CLOSE = 0;
    const PINTUANORDER_STATE_NORMAL = 1;
    const PINTUANORDER_STATE_SUCCESS = 2;
    const PINTUANORDER_STATE_FAIL = 3;

    private $bargainorder_state_array = array(
        self::PINTUANORDER_STATE_CLOSE => '砍价取消',
        self::PINTUANORDER_STATE_NORMAL => '砍价中',
        self::PINTUANORDER_STATE_SUCCESS => '砍价成功',
        self::PINTUANORDER_STATE_FAIL => '砍价失败'
    );

    /**
     * 获取砍价订单表列表
     * @access public
     * @author csdeshang
     * @param type $condition 条件
     * @return type
     */
    public function getPbargainorderList($condition,$pagesize='') {
        $res = Db::name('pbargainorder')->where($condition)->order('bargainorder_id desc');
        if($this->lock){
            $res=$res->lock(true);
        }
        if($pagesize){
            $res=$res->paginate(['list_rows'=>$pagesize,'query' => request()->param()],false);
            $pbargainorder_list = $res->items();
            $this->page_info = $res;
        }else{
            $pbargainorder_list=$res->select()->toArray();
        }
        return $pbargainorder_list;
    }

    /**
     * 获取砍价订单表列表
     * @access public
     * @author csdeshang
     * @param type $condition 条件
     * @return type
     */
    public function getOnePbargainorder($condition,$lock=false) {
        return Db::name('pbargainorder')->where($condition)->lock($lock)->find();
    }
    
    /**
     * 获取砍价订单表数量
     * @access public
     * @author csdeshang
     * @param type $condition 条件
     * @return type
     */
    public function getPbargainorderCount($condition) {
        return Db::name('pbargainorder')->where($condition)->count();
    }
    /**
     * 增加砍价订单
     * @access public
     * @author csdeshang
     * @param type $data 参数内容
     * @return type
     */
    public function addPbargainorder($data) {
        return Db::name('pbargainorder')->insertGetId($data);
    }

    /**
     * 编辑砍价订单
     * @access public
     * @author csdeshang
     * @param type $condition 条件
     * @param type $data 数据
     * @return type
     */
    public function editPbargainorder($condition, $data) {
        return Db::name('pbargainorder')->where($condition)->update($data);
    }
    
    /**
     * 砍价状态数组
     * @access public
     * @author csdeshang
     * @return type
     */
    public function getBargainorderStateArray() {
        return $this->bargainorder_state_array;
    }
    

}
