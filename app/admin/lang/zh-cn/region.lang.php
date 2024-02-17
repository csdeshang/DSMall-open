<?php


$lang['area_name'] = '地区名称';
$lang['area_region'] = '大区名称';
$lang['add_child'] = '新增下级';
$lang['area_sort'] = '排序';
$lang['area_parentid'] = '上级地区';
$lang['area_name_error'] = '请填写地区名称';
$lang['area_sort_error'] = '排序必须为0-255间数字';
$lang['area_region_error'] = '大区名称必须小于三个字符';
$lang['area_initial'] = '名称首字母';
$lang['area_initial_error'] = '请填写大写首字母';

$lang['region_index_help1'] = '全站所有地区均来源于此，建议对此处谨慎操作。';
$lang['region_index_help2'] = '所属大区为默认的全国性的几大区域，只有省级地区才需要填写大区域，目前全国几大区域有：华北、东北、华东、华南、华中、西南、西北、港澳台、海外';
$lang['region_index_help3'] = '所在层级为该地区的所在的层级深度，如北京&gt;北京市&gt;朝阳区,其中北京层级为1，北京市层级为2，朝阳区层级为3';
$lang['region_index_help4'] = '地区名前面有“+”符号的，表示该地区下还有下级地区，您可以点击“+”查看';
$lang['region_index_help5'] = '对地区作更改后，会直接更新地区的缓存';
$lang['region_index_help6'] = '在“地区管理”模块中删除数据时会同时将“商家配送范围”关联的数据删除';

$lang['area_parent_id'] = '上级地区';
$lang['area_empty'] = '没有该地区';
$lang['area_deep_error'] = '最多只能%s级';
$lang['area_parent_error'] = '地区的上级不能是自己以及自己的下级地区';

$lang['please_drop_child_region']='请先删除该分类下的子地区';

return $lang;