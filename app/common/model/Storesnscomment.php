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
class Storesnscomment extends BaseModel
{
    public $page_info;
    /**
     * 店铺动态评论列表
     * @access public
     * @author csdeshang
     * @param array $condition 条件
     * @param string $field 字段 
     * @param string $order 排序
     * @param int $limit 限制
     * @param int $pagesize 分页
     * @return array
     */
    public function getStoresnscommentList($condition, $field = '*', $order = 'storesnscomm_id desc', $limit = 0, $pagesize = 0) {
        if($pagesize){
        $res= Db::name('storesnscomment')->where($condition)->field($field)->order($order)->paginate(['list_rows'=>$pagesize,'query' => request()->param()],false);
        $this->page_info=$res;
        return $res->items();
    }else{
            return Db::name('storesnscomment')->where($condition)->field($field)->order($order)->select()->toArray();
        }
    }

    /**
     * 店铺评论数量
     * @access public
     * @author csdeshang
     * @param type $condition 条件
     * @return type
     */
    public function getStoresnscommentCount($condition) {
        return Db::name('storesnscomment')->where($condition)->count();
    }

    /**
     * 获取单条评论
     * @access public
     * @author csdeshang
     * @param array $condition 条件
     * @param string $field 字段
     * @return array
     */
    public function getStoresnscommentInfo($condition, $field = '*') {
        return Db::name('storesnscomment')->where($condition)->field($field)->find();
    }

    /**
     * 保存店铺评论
     * @access public
     * @author csdeshang
     * @param array $data 数据
     * @return boolean
     */
    public function addStoresnscomment($data) {
        return Db::name('storesnscomment')->insertGetId($data);
    }
    
    /**
     * 更新店铺评论
     * @access public
     * @author csdeshang
     * @param type $update 更新数据
     * @param type $condition 条件
     * @return type
     */
    public function editStoresnscomment($update, $condition) {
        return Db::name('storesnscomment')->where($condition)->update($update);
    }

    /**
     * 删除店铺动态评论
     * @access public
     * @author csdeshang
     * @param array $condition 条件
     * @return boolean
     */
    public function delStoresnscomment($condition) {
        return Db::name('storesnscomment')->where($condition)->delete();
    }
}