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
class Fleaconsult extends BaseModel {

    public $page_info;

    /**
     * 添加咨询
     * @access public
     * @author csdeshang
     * @param array $data 参数内容
     * @return bool
     */
    public function addFleaconsult($data) {
        if (empty($data)) {
            return false;
        }
        $consult = array();
        $consult['seller_id'] = $data['seller_id'];
        $consult['member_id'] = $data['member_id'];
        $consult['goods_id'] = $data['goods_id'];
        $consult['fleaconsult_email'] = trim($data['email']);
        $consult['fleaconsult_content'] = trim($data['consult_content']);
        $consult['fleaconsult_addtime'] = TIMESTAMP;
        $consult['fleaconsult_type'] = $data['type'];
        $result = Db::name('fleaconsult')->insertGetId($consult);
        if ($result) {
            return $result;
        } else {
            return false;
        }
    }

    /**
     * 查询咨询
     * @access public
     * @author csdeshang
     * @param type $condition 查询条件
     * @param type $pagesize 分页信息
     * @param type $type 查询类型
     * @param type $ctype 咨询类型
     * @param type $order 排序
     * @return type
     */
    public function getFleaconsultList($condition, $pagesize = '', $type = "simple", $ctype = 'goods',$order='fleaconsult.fleaconsult_id desc') {
        switch ($type) {
            case 'seller':
                $consult_list = Db::name('fleaconsult')->where($condition)->alias('fleaconsult')->field('fleaconsult.*,member.member_name,member.member_avatar,flea.goods_name')->order($order)->join('member member', 'fleaconsult.member_id = member.member_id')->join('flea flea', 'fleaconsult.goods_id = flea.goods_id');
                break;
            default:
                $consult_list = Db::name('fleaconsult')->where($condition)->order($order);
                break;
        }
        if($pagesize){
            $consult_list = $consult_list->paginate(['list_rows'=>$pagesize,'query' => request()->param()],false);
            $this->page_info = $consult_list;
            return $consult_list->items();
        }else{
            return $consult_list->select()->toArray();
        }

    }

    /**
     * 删除咨询
     * @access public
     * @author csdeshang
     * @param int $id ID编号
     * @return bool
     */
    public function delFleaconsult($id) {
        return Db::name('fleaconsult')->where("fleaconsult_id in ({$id})")->delete();
    }

    /**
     * 回复咨询
     * @access public
     * @author csdeshang
     * @param array $data 参数内容
     * @return type
     */
    public function replyFleaconsult($data) {
        $data['fleaconsult_reply_time'] = TIMESTAMP;
        return Db::name('fleaconsult')->where('fleaconsult_id',$data['consult_id'])->update($data);
    }

}
