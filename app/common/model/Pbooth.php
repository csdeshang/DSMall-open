<?php
/**
 * 推荐展位管理
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
class Pbooth extends BaseModel
{

    const STATE1 = 1;       // 开启
    const STATE0 = 0;       // 关闭
    public $page_info;

    /**
     * 展位套餐列表
     * @access public
     * @author csdeshang
     * @param array $condition 条件
     * @param string $field 字段
     * @param int $pagesize 分页
     * @param string $order 排序
     * @return array
     */
    public function getBoothquotaList($condition, $field = '*', $pagesize = 0, $order = 'boothquota_id desc')
    {
        if($pagesize){
        $res= Db::name('pboothquota')->field($field)->where($condition)->order($order)->paginate(['list_rows'=>$pagesize,'query' => request()->param()],false);
        $this->page_info=$res;
        return $res->items();
        }else{
            return Db::name('pboothquota')->field($field)->where($condition)->order($order)->select()->toArray();
        }
    }

    /**
     * 展位套餐详细信息
     * @access public
     * @author csdeshang
     * @param array $condition 条件
     * @param string $field 字段
     * @return array
     */
    public function getBoothquotaInfo($condition, $field = '*')
    {
        return Db::name('pboothquota')->field($field)->where($condition)->find();
    }

    /**
     * 展位套餐详细信息
     * @access public
     * @author csdeshang
     * @param int $store_id 店铺ID
     * @param string $field 字段
     * @return array
     */
    public function getBoothquotaInfoCurrent($store_id)
    {
        $condition = array();
        $condition[] = array('store_id','=',$store_id);
        $condition[] = array('boothquota_endtime','>',TIMESTAMP);
        $condition[] = array('boothquota_state','=',1);
        return $this->getBoothquotaInfo($condition);
    }

    /**
     * 保存推荐展位套餐
     * @access public
     * @author csdeshang
     * @param array $data 参数内容
     * @return boolean
     */
    public function addBoothquota($data)
    {
        return Db::name('pboothquota')->insertGetId($data);
    }

    /**
     * 表示推荐展位套餐
     * @access public
     * @author csdeshang
     * @param array $update 更新数据
     * @param array $condition 条件
     * @return array
     */
    public function editBoothquota($update, $condition)
    {
        return Db::name('pboothquota')->where($condition)->update($update);
    }

    /**
     * 编辑推荐展位套餐
     * @access public
     * @author csdeshang
     * @param array $update  更新数据
     * @param array $condition 条件
     * @return array
     */
    public function editBoothquotaOpen($update, $condition)
    {
        $update['boothquota_state'] = self::STATE1;
        return Db::name('pboothquota')->where($condition)->update($update);
    }

    /**
     * 商品列表
     * @access public
     * @author csdeshang
     * @access public
     * @author csdeshang
     * @param array $condition 条件
     * @param string $field 字段
     * @param int $pagesize 分页
     * @param int $limit 限制
     * @param string $order 排序
     * @return array
     */
    public function getBoothgoodsList($condition, $field = '*', $pagesize = 0, $limit = 0, $order = 'boothgoods_id asc') {
//        $condition = $this->_getRecursiveClass($condition);
        if ($pagesize) {
            $res = Db::name('pboothgoods')->field($field)->where($condition)->order($order)->paginate(['list_rows'=>$pagesize,'query' => request()->param()],false);
            $this->page_info = $res;
            return $res->items();
        } else {
            return Db::name('pboothgoods')->field($field)->where($condition)->limit($limit)->order($order)->select()->toArray();
        }
    }

    /**
     * 保存套餐商品信息
     * @access public
     * @author csdeshang
     * @param array $data 数据
     * @return boolean
     */
    public function addBoothgoods($data)
    {
        return Db::name('pboothgoods')->insertGetId($data);
    }

    /**
     * 编辑套餐商品信息
     * @access public
     * @author csdeshang
     * @param array $update 更新数据
     * @param array $condition 更新条件
     */
    public function editBooth($update, $condition)
    {
        return Db::name('pboothgoods')->where($condition)->update($update);
    }

    /**
     * 更新套餐为关闭状态
     * @access public
     * @author csdeshang
     * @param array $condition 条件
     * @return boolean
     */
    public function editBoothClose($condition)
    {
        $quota_list = $this->getBoothquotaList($condition);
        if (empty($quota_list)) {
            return true;
        }
        $storeid_array = array();
        foreach ($quota_list as $val) {
            $storeid_array[] = $val['store_id'];
        }
        $where = array(array('store_id','in', $storeid_array));
        $update = array('boothquota_state' => self::STATE0);
        $this->editBoothquota($update, $where);
        
        $update = array('boothgoods_state' => self::STATE0);
        $this->editBooth($update, $where);
        return true;
    }

    /**
     * 删除套餐商品
     * @access public
     * @author csdeshang
     * @param array $condition 条件
     * @return boolean
     */
    public function delBoothgoods($condition)
    {
        return Db::name('pboothgoods')->where($condition)->delete();
    }

    /**
     * 获得商品子分类的ID
     * @access public
     * @author csdeshang
     * @param array $condition 查询条件
     * @return array
     */
    public function _getRecursiveClass($condition,$gc_id)
    {
        if (!is_array($gc_id)) {
            $gc_list = model('goodsclass')->getGoodsclassForCacheModel();
            if (isset($gc_list[$gc_id])) {
                $all_gc_id[] = $gc_id;
                $gcchild_id = empty($gc_list[$gc_id]['child']) ? array() : explode(',', $gc_list[$gc_id]['child']);
                $gcchildchild_id = empty($gc_list[$gc_id]['childchild']) ? array() : explode(',', $gc_list[$gc_id]['childchild']);
                $all_gc_id = array_merge($all_gc_id, $gcchild_id, $gcchildchild_id);
                $condition[] = array('gc_id','in', $all_gc_id);
            }
        }
        return $condition;
    }
}
