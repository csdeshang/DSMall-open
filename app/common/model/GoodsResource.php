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
class GoodsResource extends BaseModel {

    public $page_info;

    /**
     * 查询列表
     * @access public
     * @author csdeshang 
     * @param array $condition 检索条件
     * @param int $pagesize 分页信息
     * @param string $order 排序
     * @return array
     */
    public function getGoodsResourceList($condition, $pagesize = 0, $order = 'goods_resource_id desc') {
        if ($pagesize) {
            $res = Db::name('goods_resource')->where($condition)->order($order)->paginate(['list_rows' => $pagesize, 'query' => request()->param()], false);
            $this->page_info = $res;
            return $res->items();
        } else {
            return Db::name('goods_resource')->where($condition)->order($order)->select()->toArray();
        }
    }

    /**
     * 取单个内容
     * @access public
     * @author csdeshang
     * @param int $id 分类ID
     * @return array 数组类型的返回结果
     */
    public function getGoodsResourceInfo($condition) {
        return Db::name('goods_resource')->where($condition)->find();
    }

    /**
     * 新增
     * @access public
     * @author csdeshang
     * @param array $data 参数内容
     * @return bool 布尔类型的返回结果
     */
    public function addGoodsResource($data) {
        return Db::name('goods_resource')->insertGetId($data);
    }

    /**
     * 更新信息
     * @access public
     * @author csdeshang
     * @param array $data 更新数据
     * @param array $condition 条件数组
     * @return bool 布尔类型的返回结果
     */
    public function editGoodsResource($data, $condition) {
        return Db::name('goods_resource')->where($condition)->update($data);
    }

    /**
     * 删除图片信息，根据where
     * @access public
     * @author csdeshang
     * @param array $condition 条件数组
     * @return bool 布尔类型的返回结果
     */
    public function delGoodsResource($condition, $store_id) {
        if (empty($condition)) {
            return false;
        }
        $image_more = Db::name('goods_resource')->where($condition)->field('file_name')->select()->toArray();
        if (is_array($image_more) && !empty($image_more)) {
            foreach ($image_more as $v) {
                @unlink(BASE_UPLOAD_PATH . DIRECTORY_SEPARATOR . ATTACH_GOODS_RESOURCE . DIRECTORY_SEPARATOR . $store_id . DIRECTORY_SEPARATOR . $v['file_name']);
            }
        }
        $state = Db::name('goods_resource')->where($condition)->delete();
        return $state;
    }

}
