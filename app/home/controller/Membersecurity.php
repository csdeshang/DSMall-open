<?php

namespace app\home\controller;
use think\facade\View;
use think\facade\Lang;

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
class Membersecurity extends BaseMember {

    public function initialize() {
        parent::initialize();
        Lang::load(base_path() . 'home/lang/' . config('lang.default_lang') . '/memberpoints.lang.php');
    }

    public function index() {
        $member_info = $this->member_info;
        $member_info['security_level'] = model('member')->getMemberSecurityLevel($member_info);
        View::assign('member_info', $member_info);
        /* 设置买家当前菜单 */
        $this->setMemberCurMenu('member_security');
        /* 设置买家当前栏目 */
        $this->setMemberCurItem('index');
        return View::fetch($this->template_dir . 'index');
    }

    /**
     * 绑定邮箱 - 发送邮件
     */
    public function send_bind_email() {
        $member_email = input('param.email');

        $this->validate(array('member_email' => $member_email), 'app\common\validate\Singlefield.member_email');

        $member_model = model('member');
        $condition = array();
        $condition[]=array('member_email','=',$member_email);
        $condition[] = array('member_id','<>', session('member_id'));
        $member_info = $member_model->getMemberInfo($condition, 'member_id');
        if ($member_info) {
            ds_json_encode(10001, lang('mailbox_has_been_used'));
        }


        //验证发送频率
        $verify_code_model = model('verify_code');
        $result = $verify_code_model->isVerifyCodeFrequant(5, 1);
        if (!$result['code']) {
            ds_json_encode(10001, $result['msg']);
        }

        $verify_code = $verify_code_model->genVerifyCode(5, 1);
        if (!$verify_code) {
            ds_json_encode(10001, lang('system_error'));
        }

        $uid = base64_encode(ds_encrypt(session('member_id') . ' ' . $member_email));
        $verify_url = HOME_SITE_URL . '/Login/bind_email.html?uid=' . $uid . '&hash=' . md5($verify_code);

        $mailtemplates_model = model('mailtemplates');
        $tpl_info = $mailtemplates_model->getTplInfo(array('mailmt_code' => 'bind_email'));
        $param = array();
        $param['site_name'] = config('ds_config.site_name');
        $param['user_name'] = session('member_name');
        $param['verify_url'] = $verify_url;
        $subject = ds_replace_text($tpl_info['mailmt_title'], $param);
        $message = ds_replace_text($tpl_info['mailmt_content'], $param);
        $message = htmlspecialchars_decode($message);
        $ob_email = new \sendmsg\Email();
        $result = $ob_email->send_sys_email($member_email, $subject, $message);
        if ($result) {
            $ip = request()->ip();
            $flag = $verify_code_model->addVerifyCode(array(
                'verify_code_type' => 5,
                'verify_code' => $verify_code,
                'verify_code_user_type' => 1,
                'verify_code_user_id' => session('member_id'),
                'verify_code_user_name' => session('member_name'),
                'verify_code_add_time' => TIMESTAMP,
                'verify_code_ip' => $ip,
            ));
            if (!$flag) {
                ds_json_encode(10001, lang('system_error'));
            }


            $data = array();
            $data['member_email'] = $member_email;
            $data['member_emailbind'] = 0;
            $member_model->editMember(array('member_id' => session('member_id')), $data,session('member_id'));
            ds_json_encode(10000, lang('verify_mail_been_sent_mailbox'));
        } else {
            ds_json_encode(10001, lang('system_error'));
        }
    }

    public function auth() {
        $member_model = model('member');
        $type = input('param.type');
        if (!request()->isPost()) {
            if (!in_array($type, array('modify_pwd', 'modify_mobile', 'modify_email', 'modify_paypwd'))) {
                $this->redirect('Membersecurity/index');
            }
            //继承父类的member_info
            $member_info = $this->member_info;
            if (!$member_info) {
                $member_info = $member_model->getMemberInfo(array('member_id' => session('member_id')), 'member_email,member_emailbind,member_mobile,member_mobilebind');
            }
            //第一次绑定邮箱，不用发验证码，直接进下一步
            //第一次绑定手机，不用发验证码，直接进下一步
            if (($type == 'modify_email' && $member_info['member_emailbind'] == '0') || ($type == 'modify_mobile' && $member_info['member_mobilebind'] == '0')) {
                session('auth_' . $type, TIMESTAMP);
                /* 设置买家当前菜单 */
                $this->setMemberCurMenu('member_security');
                /* 设置买家当前栏目 */
                $this->setMemberCurItem($type);
                echo View::fetch($this->template_dir . $type);
                exit;
            }

            //修改密码、设置支付密码时，必须绑定邮箱或手机
            if (in_array($type, array('modify_pwd', 'modify_paypwd')) && $member_info['member_emailbind'] == '0' && $member_info['member_mobilebind'] == '0') {
                $this->error(lang('please_bind_email_phone_first'), 'membersecurity/index');
            }
            View::assign('member_info', $member_info);
            /* 设置买家当前菜单 */
            $this->setMemberCurMenu('member_security');
            /* 设置买家当前栏目 */
            $this->setMemberCurItem($type);
            return View::fetch($this->template_dir . 'auth');
        } else {
            if (!in_array($type, array('modify_pwd', 'modify_mobile', 'modify_email', 'modify_paypwd'))) {
                $this->redirect((string)url('Membersecurity/index'));
            }
            $verify_code = input('post.auth_code');
            
            $this->validate(array('verify_code'=>$verify_code), 'app\common\validate\Singlefield.verify_code');
            
            $verify_code_model = model('verify_code');
            if (!$verify_code_model->getVerifyCodeInfo(array(array('verify_code_type' ,'=', 6), array('verify_code_user_type' ,'=', 1), array('verify_code_user_id' ,'=', session('member_id')), array('verify_code' ,'=', $verify_code), array('verify_code_add_time','>', TIMESTAMP - VERIFY_CODE_INVALIDE_MINUTE * 60)))) {
                $this->error(lang('validation_fails'));
            }


            session('auth_' . $type, TIMESTAMP);

            /* 设置买家当前菜单 */
            $this->setMemberCurMenu('member_security');
            /* 设置买家当前栏目 */
            $this->setMemberCurItem($type);
            return View::fetch($this->template_dir . $type);
        }
    }

    /**
     * 统一发送身份验证码
     */
    public function send_auth_code() {
        $type = input('param.type');
        if (!in_array($type, array('email', 'mobile')))
            exit();

        $member_model = model('member');
        $member_info = $member_model->getMemberInfoByID(session('member_id'));



        //验证发送频率
        $verify_code_model = model('verify_code');
        $result = $verify_code_model->isVerifyCodeFrequant(6, 1);
        if (!$result['code']) {
            exit(json_encode(array('state' => 'false', 'msg' => $result['msg'])));
        }

        $verify_code = $verify_code_model->genVerifyCode(6, 1);
        if (!$verify_code) {
            exit(json_encode(array('state' => 'false', 'msg' => lang('system_error'))));
        }
        $mailtemplates_model = model('mailtemplates');
        $tpl_info = $mailtemplates_model->getTplInfo(array('mailmt_code' => 'authenticate'));

        $param = array();
        $param['code'] = $verify_code;
        $ten_param=array($verify_code);
        $subject = ds_replace_text($tpl_info['mailmt_title'], $param);
        $message = ds_replace_text($tpl_info['mailmt_content'], $param);
        if ($type == 'email') {
            $email = new \sendmsg\Email();
            $result['state'] = $email->send_sys_email($member_info["member_email"], $subject, $message);
        } elseif ($type == 'mobile') {
            $smslog_param=array(
                    'ali_template_code'=>$tpl_info['ali_template_code'],
                    'ali_template_param'=>$param,
                    'ten_template_code'=>$tpl_info['ten_template_code'],
                    'ten_template_param'=>$ten_param,
                    'message'=>$message,
                );
            $result = model('smslog')->sendSms($member_info["member_mobile"], $smslog_param,5,$verify_code);
        }
        if ($result['state']) {
            $ip = request()->ip();
            $flag = $verify_code_model->addVerifyCode(array(
                'verify_code_type' => 6,
                'verify_code' => $verify_code,
                'verify_code_user_type' => 1,
                'verify_code_user_id' => session('member_id'),
                'verify_code_user_name' => session('member_name'),
                'verify_code_add_time' => TIMESTAMP,
                'verify_code_ip' => $ip,
            ));
            if (!$flag) {
                exit(json_encode(array('state' => 'false', 'msg' => lang('system_error'))));
            }
            exit(json_encode(array('state' => 'true', 'msg' => lang('verification_code_has_been_sent'))));
        } else {
            exit(json_encode(array('state' => 'false', 'msg' => isset($result['message']) ? $result['message'] : lang('verification_code_sending_failed'))));
        }
    }

    /**
     * 修改密码
     */
    public function modify_pwd() {
        $member_model = model('member');
        //身份验证后，需要在30分钟内完成修改密码操作
        if (TIMESTAMP - session('auth_modify_pwd') > 1800) {
            ds_json_encode(10001, lang('operation_timed_out'));
        }
        if (!request()->isPost()) {
            exit();
        }
        $data = array(
            'password' => input('post.password'),
            'confirm_password' => input('post.confirm_password'),
        );
        $this->validate($data, 'app\common\validate\Membersecurity.modify_pwd');
        
        if ($data['password'] != $data['confirm_password']) {
            ds_json_encode(10001, lang('two_password_inconsistencies'));
        }

        //判断当前的密码是否和原密码相同
        $member_info = $member_model->getMemberInfo(array('member_id' => session('member_id')));
        if ($member_info['member_password'] == md5($data['password'])) {
            ds_json_encode(10001, lang('new_password_same'));
        }


        $update = $member_model->editMember(array('member_id' => session('member_id')), array('member_password' => md5($data['password'])),session('member_id'));
        $message = $update ? lang('password_modify_successfully') : 'operation_timed_out';
        session('auth_modify_pwd', NULL);
        if ($update) {
            ds_json_encode(10000, $message);
        } else {
            ds_json_encode(10001, $message);
        }
    }

    /**
     * 设置支付密码
     */
    public function modify_paypwd() {
        $member_model = model('member');

        //身份验证后，需要在30分钟内完成修改密码操作
        if (TIMESTAMP - session('auth_modify_paypwd') > 1800) {
            $this->error(lang('operation_timed_out'), (string)url('Membersecurity/auth', ['type' => 'modify_paypwd']));
        }
        if (!request()->isPost())
            exit();
        $data = array(
            'password' => input('post.password'),
            'confirm_password' => input('post.confirm_password'),
        );
        $this->validate($data, 'app\common\validate\Membersecurity.modify_paypwd');

        if ($data['password'] != $data['confirm_password']) {
            ds_json_encode(10001, lang('two_password_inconsistencies'));
        }

        $update = $member_model->editMember(array('member_id' => session('member_id')), array('member_paypwd' => md5($data['password'])),session('member_id'));
        $message = $update ? lang('password_set_successfully') : lang('password_setting_failed');
        session('auth_modify_paypwd', NULL);
        if ($update) {
            ds_json_encode(10000, $message);
        } else {
            ds_json_encode(10001, $message);
        }
    }

    /**
     * 绑定手机
     */
    public function modify_mobile() {
        $member_model = model('member');
        $member_model->getMemberInfoByID(session('member_id'));
        if (request()->isPost()) {
            
            $member_mobile = input('post.mobile');
            $verify_code = input('post.vcode');
            
            $data = array(
                'member_mobile' => input('post.mobile'),
                'verify_code' => $verify_code,
            );
            $this->validate($data, 'app\common\validate\Membersecurity.modify_mobile');
            
            $verify_code_model = model('verify_code');
            if (!$verify_code_model->getVerifyCodeInfo(array(array('verify_code_type' ,'=', 4), array('verify_code_user_type' ,'=', 1), array('verify_code_user_id' ,'=', session('member_id')), array('verify_code' ,'=', $verify_code), array('verify_code_add_time','>', TIMESTAMP - VERIFY_CODE_INVALIDE_MINUTE * 60)))) {
                ds_json_encode(10001, lang('mobile_verification_code_error'));
            }

            $member_model->editMember(array('member_id' => session('member_id')), array('member_mobilebind' => 1),session('member_id'));
            ds_json_encode(10000, lang('phone_number_bound_successfully'));
        }
    }

    /**
     * 修改手机号 - 发送验证码
     */
    public function send_modify_mobile() {
        $member_mobile = input('param.mobile');
        $singlefield_validate = ds_validate('singlefield');
        if (!$singlefield_validate->scene('member_mobile')->check(array('member_mobile' => $member_mobile))) {
            exit(json_encode(array('state' => 'false', 'msg' => $singlefield_validate->getError())));
        }

        $member_model = model('member');
        $condition = array();
        $condition[]=array('member_mobile','=',$member_mobile);
        $condition[] = array('member_id','<>', session('member_id'));
        $member_info = $member_model->getMemberInfo($condition, 'member_id');
        if ($member_info) {
            exit(json_encode(array('state' => 'false', 'msg' => lang('please_change_another_phone_number'))));
        }

        //验证发送频率
        $verify_code_model = model('verify_code');
        $result = $verify_code_model->isVerifyCodeFrequant(4, 1);
        if (!$result['code']) {
            exit(json_encode(array('state' => 'false', 'msg' => $result['msg'])));
        }

        $verify_code = $verify_code_model->genVerifyCode(4, 1);
        if (!$verify_code) {
            exit(json_encode(array('state' => 'false', 'msg' => lang('system_error'))));
        }


        $mailtemplates_model = model('mailtemplates');
        $tpl_info = $mailtemplates_model->getTplInfo(array('mailmt_code' => 'modify_mobile'));
        $param = array();
        $param['code'] = $verify_code;
        $ten_param=array($verify_code);
        $message = ds_replace_text($tpl_info['mailmt_content'], $param);
        $smslog_param=array(
                    'ali_template_code'=>$tpl_info['ali_template_code'],
                    'ali_template_param'=>$param,
                    'ten_template_code'=>$tpl_info['ten_template_code'],
                    'ten_template_param'=>$ten_param,
                    'message'=>$message,
                );
        $result = model('smslog')->sendSms($member_mobile, $smslog_param,4,$verify_code);

        if (!$result['state']) {
            exit(json_encode(array('state' => 'false', 'msg' => $result['message'])));
        }
        $ip = request()->ip();
        $flag = $verify_code_model->addVerifyCode(array(
            'verify_code_type' => 4,
            'verify_code' => $verify_code,
            'verify_code_user_type' => 1,
            'verify_code_user_id' => session('member_id'),
            'verify_code_user_name' => session('member_name'),
            'verify_code_add_time' => TIMESTAMP,
            'verify_code_ip' => $ip,
        ));
        if (!$flag) {
            exit(json_encode(array('state' => 'false', 'msg' => lang('system_error'))));
        }
        $update = $member_model->editMember(array('member_id' => session('member_id')), array('member_mobile' => $member_mobile),session('member_id'));
        if (!$update) {
            exit(json_encode(array('state' => 'false', 'msg' => lang('modified_phone_same_original_one'))));
        } else {
            exit(json_encode(array('state' => 'true', 'msg' => lang('send_success'))));
        }
    }

    /**
     * 用户中心右边，小导航
     *
     * @param string $menu_type 导航类型
     * @param string $menu_key 当前导航的menu_key
     * @return
     */
    protected function getMemberItemList() {
        $menu_name = request()->action();
        switch ($menu_name) {
            case 'index':
                $menu_array = array(
                    array(
                        'name' => 'index', 'text' => lang('account_security'),
                        'url' => (string)url('Membersecurity/index')
                    )
                );
                return $menu_array;
                break;
            case 'modify_pwd':
                $menu_array = array(
                    array(
                        'name' => 'index', 'text' => lang('account_security'),
                        'url' => (string)url('Membersecurity/index')
                    ), array(
                        'name' => 'modify_pwd', 'text' => lang('change_login_password'),
                        'url' => (string)url('Membersecurity/auth', ['type' => 'modify_pwd'])
                    ),
                );
                return $menu_array;
                break;
            case 'modify_email':
                $menu_array = array(
                    array(
                        'name' => 'index', 'text' => lang('account_security'),
                        'url' => (string)url('Membersecurity/index')
                    ), array(
                        'name' => 'modify_email', 'text' => lang('email_address_verification'),
                        'url' => (string)url('Membersecurity/auth', ['type' => 'modify_email'])
                    ),
                );
                return $menu_array;
                break;
            case 'modify_mobile':
                $menu_array = array(
                    array(
                        'name' => 'index', 'text' => lang('account_security'),
                        'url' => (string)url('Membersecurity/index')
                    ), array(
                        'name' => 'modify_mobile', 'text' => lang('phone_verification'),
                        'url' => (string)url('Membersecurity/auth', ['type' => 'modify_mobile'])
                    ),
                );
                return $menu_array;
                break;
            case 'modify_paypwd':
                $menu_array = array(
                    array(
                        'name' => 'index', 'text' => lang('account_security'),
                        'url' => (string)url('Membersecurity/index')
                    ), array(
                        'name' => 'modify_paypwd', 'text' => lang('set_payment_password'),
                        'url' => (string)url('Membersecurity/auth', ['type' => 'modify_paypwd'])
                    ),
                );
                return $menu_array;
                break;
        }
    }

}

?>
