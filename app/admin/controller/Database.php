<?php

namespace app\admin\controller;
use think\facade\View;
use think\facade\Db;
use think\facade\Lang;

//数据库备份根路径
define('DATA_BACKUP_PATH', 'uploads/sqldata/');
//数据库备份卷大小  20971520表示为 20M
//define('DATA_BACKUP_PART_SIZE', 20971520);
define('DATA_BACKUP_PART_SIZE', 1024*1024*10);
//数据库备份文件是否启用压缩
define('DATA_BACKUP_COMPRESS', 0);
//数据库备份文件压缩级别
define('DATA_BACKUP_COMPRESS_LEVEL', 9);

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
 * 控制器
 */
class Database extends AdminControl {

    public function initialize() {
        parent::initialize();
        Lang::load(base_path() . 'admin/lang/'.config('lang.default_lang').'/db.lang.php');
    }

    public function db() {
        $dbtable_list = Db::query('SHOW TABLE STATUS');
        $total = 0;
        foreach ($dbtable_list as $k => $v) {
            $dbtable_list[$k]['size'] = format_bytes($v['Data_length'] + $v['Index_length']);
            $total += $v['Data_length'] + $v['Index_length'];
        }
        View::assign('dbtable_list', $dbtable_list);
        View::assign('total', format_bytes($total));
        View::assign('tableNum', count($dbtable_list));
        $this->setAdminCurItem('db');
        return View::fetch();
    }

    public function export($tables = null, $id = null, $start = null) {
        //防止备份数据过程超时
        function_exists('set_time_limit') && set_time_limit(0);
        if (request()->isPost() && !empty($tables) && is_array($tables)) { //初始化
            $path = DATA_BACKUP_PATH;
            if (!is_dir($path)) {
                mkdir($path, 0755, true);
            }
            //读取备份配置
            $config = array(
                'path' => realpath($path) . DIRECTORY_SEPARATOR,
                'part' => DATA_BACKUP_PART_SIZE,
                'compress' => DATA_BACKUP_COMPRESS,
                'level' => DATA_BACKUP_COMPRESS_LEVEL,
            );
            //检查是否有正在执行的任务
            $lock = "{$config['path']}backup.lock";
            if (is_file($lock)) {
                return json(array('info' => lang('file_conflict'), 'status' => 0, 'url' => ''));
            } else {
                //创建锁文件
                file_put_contents($lock, TIMESTAMP);
            }

            //检查备份目录是否可写
            if (!is_writeable($config['path'])) {
                return json(array('info' => lang('file_cannot_write'), 'status' => 0, 'url' => ''));
            }
            session('backup_config', $config);

            //生成备份文件信息
            $file = array(
                'name' => date('Ymd-His', $_SERVER['REQUEST_TIME']),
                'part' => 1,
            );
            session('backup_file', $file);
            //缓存要备份的表
            session('backup_tables', $tables);
            //创建备份文件
            $Database = new \mall\Backup($file, $config);
            if (false !== $Database->create()) {
                $tab = array('id' => 0, 'start' => 0);
                return json(array('tables' => $tables, 'tab' => $tab, 'info' => lang('init_success'), 'status' => 1, 'url' => ''));
            } else {
                return json(array('info' => lang('init_error'), 'status' => 0, 'url' => ''));
            }
        } elseif (request()->isGet() && is_numeric($id) && is_numeric($start)) { //备份数据
            $tables = session('backup_tables');
            //备份指定表
            $Database = new \mall\Backup(session('backup_file'), session('backup_config'));
            $start = $Database->backup($tables[$id], $start);
            if (false === $start) { //出错
                return json(array('info' => lang('back_error'), 'status' => 0, 'url' => ''));
            } elseif (0 === $start) { //下一表
                if (isset($tables[++$id])) {
                    $tab = array('id' => $id, 'start' => 0);
                    return json(array('tab' => $tab, 'info' => lang('back_finish'), 'status' => 1, 'url' => ''));
                } else { //备份完成，清空缓存
                    unlink(session('backup_config.path') . 'backup.lock');
                    session('backup_tables', null);
                    session('backup_file', null);
                    session('backup_config', null);
                    return json(array('info' => lang('back_finish'), 'status' => 1, 'url' => ''));
                }
            } else {
                $tab = array('id' => $id, 'start' => $start[0]);
                $rate = floor(100 * ($start[0] / $start[1]));
                return json(array('tab' => $tab, 'info' => lang('backup_in_progress')."...({$rate}%)", 'status' => 1, 'url' => ''));
            }
        } else {
            //出错
            return json(array('info' => lang('param_error'), 'status' => 0, 'url' => ''));
        }
    }

    public function restore() {
        $path = DATA_BACKUP_PATH;
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
        $path = realpath($path);
        $flag = \FilesystemIterator::KEY_AS_FILENAME;
        $glob = new \FilesystemIterator($path, $flag);
        $restore_list = array();
        $filenum = $total = 0;
        foreach ($glob as $name => $file) {
            if (preg_match('/^\d{8,8}-\d{6,6}-\d+\.sql(?:\.gz)?$/', $name)) {
                $name = sscanf($name, '%4s%2s%2s-%2s%2s%2s-%d');
                $date = "{$name[0]}-{$name[1]}-{$name[2]}";
                $time = "{$name[3]}:{$name[4]}:{$name[5]}";
                $part = $name[6];
                $info = pathinfo($file);
                if (isset($restore_list["{$date} {$time}"])) {
                    $info = $restore_list["{$date} {$time}"];
                    $info['part'] = max($info['part'], $part);
                    $info['size'] = $info['size'] + $file->getSize();
                } else {
                    $info['part'] = $part;
                    $info['size'] = $file->getSize();
                }
                $info['compress'] = ($info['extension'] === 'sql') ? '-' : $info['extension'];
                $info['time'] = strtotime("{$date} {$time}");
                $filenum++;
                $total += $info['size'];
                $restore_list["{$date} {$time}"] = $info;
            }
        }
        View::assign('restore_list', $restore_list);
        View::assign('filenum', $filenum);
        View::assign('total', $total);
        $this->setAdminCurItem('restore');
        return View::fetch();
    }

    /**
     * 执行还原数据库操作
     * @param int $time
     * @param null $part
     * @param null $start
     */
    public function import($time = 0, $part = null, $start = null) {
        function_exists('set_time_limit') && set_time_limit(0);

        if (is_numeric($time) && is_null($part) && is_null($start)) { //初始化
            //获取备份文件信息
            $name = date('Ymd-His', $time) . '-*.sql*';
            $path = realpath(DATA_BACKUP_PATH) . DIRECTORY_SEPARATOR . $name;
            $files = glob($path);
            $list = array();
            foreach ($files as $name) {
                $basename = basename($name);
                $match = sscanf($basename, '%4s%2s%2s-%2s%2s%2s-%d');
                $gz = preg_match('/^\d{8,8}-\d{6,6}-\d+\.sql.gz$/', $basename);
                $list[$match[6]] = array($match[6], $name, $gz);
            }
            ksort($list);

            //检测文件正确性
            $last = end($list);
            if (count($list) === $last[0]) {
                session('backup_list', $list); //缓存备份列表
                $this->success(lang('init_success'), NULL ,['part'=>1,'start'=>0]);
            } else {
                $this->error(lang('file_break_please_check'));
            }
        } elseif (is_numeric($part) && is_numeric($start)) {
            $list = session('backup_list');
            $db = new \mall\Backup($list[$part], array(
                'path' => realpath(DATA_BACKUP_PATH) . DIRECTORY_SEPARATOR,
                'compress' => $list[$part][2])
            );
            $start = $db->import($start);
            if (false === $start) {
                $this->error(lang('recover_error'));
            } elseif (0 === $start) { //下一卷
                if (isset($list[++$part])) {
                    $data = array('part' => $part, 'start' => 0);
                    $this->success(lang('restoring')."...#{$part}", null, $data);
                } else {
                    session('backup_list', null);
                    $this->success(lang('recover_success'));
                }
            } else {
                $data = array('part' => $part, 'start' => $start[0]);
                if ($start[1]) {
                    $rate = floor(100 * ($start[0] / $start[1]));
                    $this->success(lang('restoring')."...#{$part} ({$rate}%)", null, $data);
                } else {
                    $data['gz'] = 1;
                    $this->success(lang('restoring')."...#{$part}", null, $data);
                }
            }
        } else {
            $this->error(lang('param_error'));
        }
    }

    /**
     * 优化
     */
    public function optimize() {
        $batchFlag = intval(input('param.batchFlag'));
        //批量删除
        if ($batchFlag) {
            $table = input('param.key');
        } else {
            $table[] = input('param.tablename');
        }
        if (empty($table)) {
            $this->error(lang('please_select_repire_table'));
        }

        $strTable = implode(',', $table);

        if (!Db::query("OPTIMIZE TABLE {$strTable} ")) {
            $strTable = '';
        }
        $this->success(lang('optimization_table_succ') . $strTable, (string)url('Database/db'));
    }

    /**
     * 修复
     */
    public function repair() {
        $batchFlag = intval(input('param.batchFlag'));
        //批量删除
        if ($batchFlag) {
            $table = I('key', array());
        } else {
            $table[] = input('param.tablename');
        }

        if (empty($table)) {
            $this->error(lang('please_repire_table'));
        }

        $strTable = implode(',', $table);
        if (!Db::query("REPAIR TABLE {$strTable} ")) {
            $strTable = '';
        }

        $this->success(lang('optimization_repair_succ') . $strTable, (string)url('Database/db'));
    }

    /**
     * 下载
     * @param int $time
     */
    public function downFile($time = 0) {
        $name = date('Ymd-His', $time) . '-*.sql*';
        $path = realpath(DATA_BACKUP_PATH) . DIRECTORY_SEPARATOR . $name;
        $files = glob($path);
        if (is_array($files)) {
            foreach ($files as $filePath) {
                if (!file_exists($filePath)) {
                    $this->error(lang('file_not_exist'));
                } else {
                    $filename = basename($filePath);
                    header("Content-type: application/octet-stream");
                    header('Content-Disposition: attachment; filename="' . $filename . '"');
                    header("Content-Length: " . filesize($filePath));
                    readfile($filePath);
                }
            }
        }
    }

    /**
     * 删除备份文件
     * @param  Integer $time 备份时间
     */
    public function del($time = 0) {
        if ($time) {
            $name = date('Ymd-His', $time) . '-*.sql*';
            $path = realpath(DATA_BACKUP_PATH) . DIRECTORY_SEPARATOR . $name;
            array_map("unlink", glob($path));
            if (count(glob($path))) {
                $this->error(lang('back_file_drop_fail'));
            } else {
                $this->success(lang('back_file_drop_success'));
            }
        } else {
            $this->error(lang('param_error'));
        }
    }

    /**
     * 获取卖家栏目列表,针对控制器下的栏目
     */
    protected function getAdminItemList() {
        $menu_array = array(
            array(
                'name' => 'db',
                'text' => lang('data_backup'),
                'url' => (string)url('Database/db')
            ),
            array(
                'name' => 'restore',
                'text' => lang('data_restoration'),
                'url' => (string)url('Database/restore')
            ),
        );

        return $menu_array;
    }

}

?>
