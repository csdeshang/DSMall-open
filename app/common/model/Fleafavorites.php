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
class Fleafavorites extends BaseModel {

    public $page_info;



    /**
     * 取单个收藏的内容
     * @access public
     * @author csdeshang
     * @param type $fav_id 收藏ID
     * @param type $type   收藏类型
     * @param type $member_id 会员ID
     * @return boolean
     */
    public function getOneFleafavorites($fav_id, $type, $member_id) {
        if (intval($fav_id) > 0) {
            $result = Db::name('fleafavorites')->where('fleafav_id', intval($fav_id))->where('fleafav_type', $type)->where('member_id', $member_id)->field('fleafav_id,member_id,fleafav_type')->find();
            return $result;
        } else {
            return false;
        }
    }

    /**
     * 新增收藏
     * @access public
     * @author csdeshang
     * @param array $data 参数内容
     * @return bool 布尔类型的返回结果
     */
    public function addFleafavorites($data) {
        $result = Db::name('fleafavorites')->insert($data);
        return $result;
    }

    /**
     * 验证是否为当前用户收藏
     * @access public
     * @author csdeshang
     * @param type $fav_id 收藏ID
     * @param type $fav_type 收藏类型
     * @param type $member_id 会员ID
     * @return boolean
     */
    public function checkFleafavorites($fav_id, $fav_type, $member_id) {
        if (intval($fav_id) == 0 || empty($fav_type) || intval($member_id) == 0) {
            return true;
        }
        $result = self::getOneFleafavorites($fav_id, $fav_type, $member_id);
        if ($result['member_id'] == $member_id) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 删除
     * @access public
     * @author csdeshang
     * @param type $condition 
     * @return boolean
     */
    public function delFleafavorites($condition) {
        $result = Db::name('fleafavorites')->where($condition)->delete();
        return $result;
    }

}
