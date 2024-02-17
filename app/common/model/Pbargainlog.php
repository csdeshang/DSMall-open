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
class Pbargainlog extends BaseModel {

    public $page_info;

    /**
     * 获取开团表列表
     * @access public
     * @author csdeshang
     * @param type $condition 条件
     * @param type $pagesize 分页
     * @param type $order 排序
     * @return type
     */
    public function getPbargainlogList($condition, $pagesize = '',$order='pbargainlog_id desc') {
        if($pagesize){
            $res = Db::name('pbargainlog')->where($condition)->order($order)->paginate(['list_rows'=>$pagesize,'query' => request()->param()],false);
            $pbargainlog_list = $res->items();
            $this->page_info = $res;
        }else{
            $pbargainlog_list = Db::name('pbargainlog')->where($condition)->order($order)->select()->toArray();
        }
        return $pbargainlog_list;
    }
    /**
     * 获取单个单团信息
     * @access public
     * @author csdeshang
     * @param type $condition 条件
     * @return type
     */
    public function getOnePbargainlog($condition){
        return Db::name('pbargainlog')->where($condition)->find();
    }
    
    /**
     * 插入砍价开团表
     * @access public
     * @author csdeshang
     * @param type $data 参数数据
     * @return type
     */
    public function addPbargainlog($data)
    {
        return Db::name('pbargainlog')->insertGetId($data);
    }
 

  
    
}
