<?php
/*
	[Destoon B2B System] Copyright (c) 2008-2013 Destoon.COM
	This is NOT a freeware, use is subject to license.txt
*/
defined('IN_DESTOON') or exit('Access Denied');
if($action == 'ftp') {
	require DT_ROOT.'/include/ftp.class.php';
	if(strpos($ftp_pass, '***') !== false) $ftp_pass = $DT['ftp_pass'];
	$ftp = new dftp($ftp_host, $ftp_user, $ftp_pass, $ftp_port, $ftp_path, $ftp_pasv, $ftp_ssl);
	if(!$ftp->connected) dialog('FTP无法连接，请检查设置');
	if(!$ftp->dftp_chdir()) dialog('FTP无法进入远程存储目录，请检查远程存储目录');
	dialog('FTP设置正常,可以使用');
} else if ($action == 'mail') {
	define('TESTMAIL', true);
	if(strpos($smtp_pass, '***') !== false) $smtp_pass = $DT['smtp_pass'];
	$DT['mail_type'] = $mail_type;
	$DT['smtp_host'] = $smtp_host;
	$DT['smtp_port'] = $smtp_port;
	$DT['smtp_auth'] = $smtp_auth;
	$DT['smtp_user'] = $smtp_user;
	$DT['smtp_pass'] = $smtp_pass;
	$DT['mail_sender'] = $mail_sender;
	$DT['mail_name'] = $mail_name;
	$DT['mail_delimiter'] = $mail_delimiter;
	$DT['mail_sign'] = '<br/>------------------------------------<br><a href="http://www.jssdw.com/" target="_blank">Send By Jssdw Mail Tester</a>';
	if(send_mail($testemail, $DT['sitename'].'邮件发送测试', '<b>恭喜！您的站点['.$DT['sitename'].']邮件发送设置成功！</b>')) dialog('邮件已发送至'.$testemail.'，请注意查收', $mail_sender);
	dialog('邮件发送失败，请检查设置');
} else {
	$tab = isset($tab) ? intval($tab) : 0;
	$all = isset($all) ? intval($all) : 0;
	if($submit) {
		if(!preg_match("/^[0-9a-z]{10,}$/i", $config['authkey'])) $config['authkey'] = random(18);
		if($setting['safe_domain']) {
			$setting['safe_domain'] = str_replace('http://', '', $setting['safe_domain']);
			if(substr($setting['safe_domain'], 0, 4) == 'www.') $setting['safe_domain'] = substr($setting['safe_domain'], 4);
		}
		if(substr($config['url'], -1) != '/') $config['url'] = $config['url'].'/';
		if($config['cookie_domain'] && substr($config['cookie_domain'], 0, 1) != '.') $config['cookie_domain'] = '.'.$config['cookie_domain'];
		if($config['cookie_domain'] != $CFG['cookie_domain']) $config['cookie_pre'] = 'D'.random(2).'_';
		$setting['smtp_pass'] = pass_decode($setting['smtp_pass'], $DT['smtp_pass']);
		$setting['ftp_pass'] = pass_decode($setting['ftp_pass'], $DT['ftp_pass']);
		$setting['sms_key'] = pass_decode($setting['sms_key'], $DT['sms_key']);
		$setting['trade_pw'] = pass_decode($setting['trade_pw'], $DT['trade_pw']);
		$setting['admin_week'] = implode(',', $setting['admin_week']);
		$setting['check_week'] = implode(',', $setting['check_week']);		
		if($setting['logo'] != $DT['logo']) clear_upload($setting['logo']);
		if(!is_write(DT_ROOT.'/config.inc.php')) msg('根目录config.inc.php无法写入，请设置可写权限');
		$tmp = file_get(DT_ROOT.'/config.inc.php');
		foreach($config as $k=>$v) {
			$tmp = preg_replace("/[$]CFG\['$k'\]\s*\=\s*[\"'].*?[\"']/is", "\$CFG['$k'] = '$v'", $tmp);
		}
		file_put(DT_ROOT.'/config.inc.php', $tmp);
		update_setting($moduleid, $setting);
		cache_module(1);
		cache_module();
		file_put(DT_ROOT.'/file/avatar/remote.html', $setting['ftp_remote'] && $setting['remote_url'] ? $setting['remote_url'] : 'URL');
		$filename = DT_ROOT.'/'.$setting['index'].'.'.$setting['file_ext'];
		if(!$setting['index_html'] && $setting['file_ext'] != 'php') file_del($filename);
		$mdir = DT_ROOT.'/'.$MODULE[2]['moduledir'].'/';
		if($setting['file_register'] != $old_file_register) @rename($mdir.$old_file_register, $mdir.$setting['file_register']);
		if($setting['file_login'] != $old_file_login) @rename($mdir.$old_file_login, $mdir.$setting['file_login']);
		if($setting['file_my'] != $old_file_my) @rename($mdir.$old_file_my, $mdir.$setting['file_my']);
		dmsg('更新成功', '?moduleid='.$moduleid.'&file='.$file.'&tab='.$tab);
	} else {
		include DT_ROOT.'/config.inc.php';
		extract(dhtmlspecialchars($CFG));
		extract(dhtmlspecialchars($DT));
		$W = array('天', '一', '二', '三', '四', '五', '六');
		$smtp_pass = pass_encode($smtp_pass);
		$ftp_pass = pass_encode($ftp_pass);
		$sms_key = pass_encode($sms_key);
		$trade_pw = pass_encode($trade_pw);
		if($kw) {
			$all = 1;
			ob_start();
		}
		include tpl('setting', $module);
		if($kw) {
			$data = $content = ob_get_contents();
			ob_clean();
			$data = preg_replace('\'(?!((<.*?)|(<a.*?)|(<strong.*?)))('.$kw.')(?!(([^<>]*?)>)|([^>]*?</a>)|([^>]*?</strong>))\'si', '<span class=highlight>'.$kw.'</span>', $data);
			$data = preg_replace('/<span class=highlight>/', '<a name=high></a><span class=highlight>', $data, 1);
			echo $data ? $data : $content;
		}
	}
}
?>