<?php


$lang['db_data_backup_help1'] = '数据备份功能根据您选择备份全部数据或指定数据，导出的数据文件可用“数据恢复”功能或者使用 phpMyAdmin 导入';
$lang['db_data_backup_help2'] = '建议定期备份数据库，以避免数据丢失';

$lang['db_database_table_list'] = '数据库表列表';
$lang['db_total_num'] = '共';
$lang['db_tablenum_record'] = '张记录';
$lang['db_total_size'] = '共计';
$lang['db_database_table'] = '数据库表';
$lang['db_data_length'] = '记录条数';
$lang['db_occupy_space'] = '占用空间';
$lang['db_code'] = '编码';
$lang['db_createtime'] = '创建时间';
$lang['db_backup_state'] = '备份状态';
$lang['db_handle'] = '操作';
$lang['db_unbackup'] = '未备份';
$lang['db_magic'] = '优化';
$lang['db_repair'] = '修复';

$lang['db_backup_error'] = '请选中要备份的数据表';
$lang['db_backup_requer'] = '正在发送备份请求...';
$lang['db_backup_strat'] = '开始备份，请不要关闭本页面！';
$lang['db_backup_conduct'] = '正在备份数据库，请不要关闭！';
$lang['db_backup'] = '立即备份';
$lang['db_backup_state_strat'] = '开始备份...(0%)';
$lang['db_backup_restart'] = '备份完成，点击重新备份';

$lang['db_restore'] = '数据还原';
$lang['db_restore_help1'] = '点击操作下面的恢复选项进行数据导入.';
$lang['db_restore_help2'] = '导入的SQL文件语句必须按照MYSQL的语法来编写';
$lang['db_restore_file_list'] = 'sql文件列表';
$lang['db_backup_file_count'] = '备份文件数量';
$lang['db_backup_file_size'] = '占空间大小';
$lang['db_restore_refresh'] = '刷新数据';
$lang['db_restore'] = '数据还原';
$lang['db_restore_file_name'] = '文件名称';
$lang['db_restore_volume_num'] = '卷号';
$lang['db_restore_compress'] = '压缩';
$lang['db_restore_data_size'] = '数据大小';
$lang['db_restore_backup_time'] = '备份时间';
$lang['db_restore_state'] = '状态';
$lang['db_restore_restore'] = '恢复';


$lang['backup_in_progress'] = '正在备份';
$lang['restoring'] = '正在还原';
$lang['optimization_table_succ'] = '优化表成功';
$lang['optimization_repair_succ'] = '修复表成功';


$lang['file_not_exist'] = '该文件不存在，可能是被删除';

$lang['data_backup'] ='数据备份';
$lang['data_restoration'] ='数据还原';

$lang['back_file_drop_success'] ='备份文件删除成功！';
$lang['back_file_drop_fail'] ='备份文件删除失败，请检查权限！';
$lang['please_repire_table'] ='请选择修复的表';
$lang['please_select_repire_table'] ='请选择要优化的表';
$lang['recover_success'] ='还原完成！';
$lang['recover_error'] ='还原数据出错！';
$lang['file_break_please_check'] ='备份文件可能已经损坏，请检查！';

$lang['init_success'] ='初始化完成！';
$lang['back_finish'] ='备份完成！';
$lang['back_error'] ='备份出错！';
$lang['init_error'] ='初始化失败，备份文件创建失败！';
$lang['file_cannot_write'] ='备份目录不存在或不可写，请检查后重试！';
$lang['file_conflict'] ='检测到有一个备份任务正在执行，请稍后再试！';

return $lang;