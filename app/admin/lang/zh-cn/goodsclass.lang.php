<?php

/**
 * index
 */
$lang['goods_class_index_choose_edit'] = '请选择要编辑的内容';
$lang['goods_class_index_in_homepage'] = '首页内';
$lang['goods_class_index_display'] = '显示';
$lang['goods_class_index_hide'] = '隐藏';
$lang['goods_class_index_class'] = '商品分类';
$lang['goods_class_index_name'] = '分类名称';
//$lang['goods_class_index_display_in_homepage']	= '首页显示';
$lang['goods_class_index_ensure_del'] = '删除该分类将会同时删除该分类的所有下级分类，您确定要删除吗';
$lang['goods_class_index_help1'] = '店主添加商品的时候，可以选择商品的分类，用户可以根据商品的分类来查询商品列表';
$lang['goods_class_index_help2'] = '点击商品分类名前“+”符号，显示当前商品分类的下级分类';
$lang['goods_class_index_help3'] = '<a>对商品分类作任何更改后，都需要到 设置 -> 清理缓存，新的设置才会生效</a>';
/**
 * 批量编辑
 */
$lang['goods_class_batch_edit_succ'] = '批量编辑成功';
$lang['goods_class_batch_edit_wrong_content'] = '批量修改内容不正确';
$lang['goods_class_batch_edit_keep'] = '保持不变';
$lang['goods_class_batch_edit_ok'] = '编辑分类成功。';
$lang['goods_class_batch_edit_fail'] = '编辑分类失败。';
$lang['goods_class_batch_edit_paramerror'] = '参数非法';
/**
 * 添加分类
 */
$lang['goods_class_add_name_null'] = '分类名称不能为空';
$lang['goods_class_add_sort_int'] = '分类排序仅能为数字';
$lang['goods_class_add_commis_rate_error'] = '请正确填写分佣比例';
$lang['goods_class_add_name_exists'] = '该分类名称已经存在了，请您换一个';
$lang['goods_class_add_sup_class'] = '上级分类';
$lang['goods_class_add_sup_class_notice'] = '如果选择上级分类，那么新增的分类则为被选择上级分类的子分类';
$lang['goods_class_add_update_sort'] = '数字范围为0~255，数字越小越靠前';
$lang['goods_class_add_display_tip'] = '分类名称是否显示';
$lang['goods_class_add_type'] = '类型';
$lang['goods_class_add_commis_rate'] = '分佣比例';
$lang['goods_class_null_type'] = '无类型';
$lang['goods_class_add_type_desc_one'] = '如果当前下拉选项中没有适合的类型，可以去';
$lang['goods_class_add_type_desc_two'] = '功能中添加新的类型';
$lang['goods_class_edit_prompts_one'] = '商品类型关系到商品发布时，商品规格的添加，没有商品类型的商品分类将不能添加商品规格。';
$lang['goods_class_edit_prompts_two'] = '默认勾选"关联到子分类"将商品类型附加到该子分类，如子分类不同于上级分类的类型，可以取消勾选并单独对子分类的特定类型进行编辑选择。';
$lang['goods_class_edit_related_to_subclass'] = '关联到子分类';
$lang['goods_class_pic'] = '分类图片';
$lang['goods_class_pic_tips'] = '第一级图标显示在首页，建议用16px * 16px。二级分类图标显示在电脑端商品分类页，建议用70px * 70px。三级分类图标显示在手机端商品分类页，建议用100px * 100px';
$lang['gc_virtual'] = '发布虚拟商品';
$lang['gc_virtual_tips'] = '勾选允许发布虚拟商品后，在发布该分类的商品时可选择交易类型为“虚拟兑换码”形式。';
$lang['commis_rate']='分佣比例';
$lang['commis_rate_tips'] = '必须为0-100的整数';

/**
 * 分类导入
 */
$lang['goods_class_import_csv_null'] = '导入的csv文件不能为空';
$lang['goods_class_import_choose_file'] = '请选择文件';
$lang['goods_class_import_file_tip'] = '如果导入速度较慢，建议您把文件拆分为几个小文件，然后分别导入';
$lang['goods_class_import_file_type'] = '文件格式';
$lang['goods_class_import_first_class'] = '一级分类';
$lang['goods_class_import_second_class'] = '二级分类';
$lang['goods_class_import_third_class'] = '三级分类';
$lang['goods_class_import_example_tip'] = '点击下载导入例子文件';
/**
 * 分类导出
 */
$lang['goods_class_export_if_trans'] = '导出您的商品分类数据';
$lang['goods_class_export_help1'] = '导出商品分类信息的.csv文件';
/**
 * TAG index
 */
$lang['goods_class_tag_name'] = 'TAG名称';
$lang['goods_class_tag_value'] = 'TAG值';
$lang['goods_class_tag_update'] = '更新TAG名称';
$lang['goods_class_tag_update_prompt'] = '更新TAG名称需要话费较长的时间，请耐心等待。';
$lang['goods_class_tag_reset'] = '导入/重置TAG';
$lang['goods_class_tag_reset_confirm'] = '您确定要重新导入TAG吗？重新导入将会重置所有TAG值信息。';
$lang['goods_class_tag_prompts_two'] = 'TAG值是分类搜索的关键字，请精确的填写TAG值。TAG值可以填写多个，每个值之间需要用,隔开。';
$lang['goods_class_tag_prompts_three'] = '导入、重置TAG功能可以根据商品分类重新更新TAG值，TAG值默认为各级商品分类的值。';
$lang['goods_class_tag_choose_data'] = '请选择要操作的数据项。';
/**
 * 重置TAG
 */
$lang['goods_class_reset_tag_fail_no_class'] = '重置TAG失败，没查找到任何分类信息。';
/**
 * 更新TAG名称
 */
$lang['goods_class_update_tag_fail_no_class'] = 'TAG名称更新失败，没查找到任何分类信息。';
/**
 * 删除TAG
 */
$lang['goods_class_tag_del_confirm'] = '你确定要删除商品分类TAG吗?';
$lang['type_add_brand_null_one'] = '还没有品牌，赶快去';
$lang['type_add_brand_null_two'] = '添加品牌吧！';

/**
 * 导航编辑
 */
$lang['type_common_checked_hide'] = '隐藏未选项';
$lang['goodscn_alias'] = '分类别名';
$lang['goodscn_alias_tips'] = '可选项。设置别名后，别名将会替代原分类名称展示在分类导航菜单列表中。';
$lang['recommend_goodsclass'] = '推荐分类';
$lang['recommend_goodsclass_tips'] = '推荐分类将在展开后的二、三级导航列表上方突出显示，建议根据分类名称长度控制选择数量不超过8个以确保展示效果。';
$lang['recommend_brand'] = '推荐品牌';
$lang['recommend_brand_tips'] = '推荐品牌将在展开后的二、三级导航列表右侧突出显示，建议选择数量为8个具有图片的品牌，超过将被隐藏。';
$lang['recommend_ad'] = '广告图';
$lang['recommend_ad_tips'] = '广告图片将展示在推荐品牌下方，请使用宽度190像素，高度150像素的jpg/gif/png格式图片作为分类导航广告上传，如需跳转请在后方添加以http://开头的链接地址。';

$lang['under_goodsclass'] = '分类下的三级分类';
$lang['under_brand'] = '分类下对应的品牌';

$lang['gc_parent_id_selected'] = '不更改所属分类（更改下拉）';
$lang['goods_class_edit_sup_class_notice'] = '注意：不要把顶级分类整体移动到其它分类下；';
$lang['display_no_options'] = '显示未选项';

$lang['parent_parent_goods_class_equal_self_error'] = '父分类的父分类不能等于自身';
$lang['parent_goods_class_equal_self_error'] = '父分类不能等于自身';

return $lang;
?>
