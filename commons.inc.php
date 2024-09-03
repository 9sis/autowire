<?php  function get_runtime($start,$end='') {
	static $_ps_time = array();
	if(!empty($end)) {
		if(!isset($_ps_time[$end])) {
			$mtime = explode(' ', microtime());
		}
		return number_format(($mtime[1] + $mtime[0] - $_ps_time[$start]), 6);
	} else {
		$mtime = explode(' ', microtime());
		$_ps_time[$start] = $mtime[1] + $mtime[0];
	}
}
get_runtime('start');
session_start();
$C = $settings = $sysmsg = array();
if(strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
	define('LF',"\r\n");
} else {
	define('LF',"\n");
}
define('NOW_YEAR','2018');
define('PHPDISK_COOKIE','phpdisk_zcore_v2_info');
define('PHPDISK_ROOT', substr(dirname(__FILE__), 0, -8));
define('PD_PLUGINS_DIR',PHPDISK_ROOT.'plugins/');
define('IN_PHPDISK',TRUE);
define('SERVER_NAME',$_SERVER['SERVER_NAME']);
if(function_exists('date_default_timezone_set')) {
	@date_default_timezone_set('Asia/Shanghai');
}
$timestamp = time();
define('TS',$timestamp);
@set_magic_quotes_runtime(0);
$installed_file = PHPDISK_ROOT.'system/install.lock';
if(!file_exists($installed_file)) {
	header("Location: ./install/index.php");
	exit;
}
$config_file = PHPDISK_ROOT.'system/configs.inc.php';
if(!file_exists($config_file)) {
	header("Location: ./install/index.php");
	exit;
} else {
	require($config_file);
}
$tpf = $configs['tpf'];
$C['set']['debug'] = $configs['debug'];
define('DEBUG',$C['set']['debug'] ? true : false);
if(DEBUG) {
	error_reporting(E_ALL ^ E_NOTICE);
	@ini_set('display_errors', 'On');
} else {
	error_reporting(0);
	@ini_set('display_errors', 'Off');
}
$charset = $configs['charset'];
$charset_arr = array('gbk' => 'gbk','utf-8' => 'utf8');
$db_charset = $charset_arr[strtolower($configs['charset'])];
header("Content-Type: text/html; charset=$charset");
require_once PHPDISK_ROOT.'includes/core.phpdisk_v2.php';

phpdisk_core::init_core();
$db = phpdisk_core::init_db_connect();
$setting_file = PHPDISK_ROOT.'system/settings.inc.php';
file_exists($setting_file) ? require_once $setting_file : settings_cache();
require_once PHPDISK_ROOT.'system/adminset.inc.php';
$file = PHPDISK_ROOT.'system/global/plugin_settings.inc.php';
file_exists($file) ? require_once $file : plugin_cache();
$file = PHPDISK_ROOT.'system/global/group_settings.inc.php';
file_exists($file) ? require_once $file : group_settings_cache();
$file = PHPDISK_ROOT.'system/global/stats.inc.php';
file_exists($file) ? require_once $file : stats_cache();
unset($file);
$settings[open_cache] = 1;
require_once(PHPDISK_ROOT.'includes/class/cache_file.class.php');
$C['gz']['open'] = $settings['gzipcompress'];
phpdisk_core::gzcompress_open();
$arr = phpdisk_core::init_lang_tpl();
$user_tpl_dir = $arr['user_tpl_dir'];
$admin_tpl_dir = $arr['admin_tpl_dir'];
$auth[is_fms] = $arr['fms'];
$auth[core] = $arr['core'];
$C['lang_type'] = $arr['lang_name'];
if($settings[open_switch_tpls]) {
	$tpl_sw = select_tpl();
}
if($settings[open_switch_langs]) {
	$langs_sw = select_lang();
	$plang = gpc('lang','C','');
	$C['lang_type'] = $plang ? (check_lang($plang) ? $plang: $C['lang_type']) : $C['lang_type'];
}
require PHPDISK_ROOT.'includes/lib/php-gettext/gettext.inc.php';
_setlocale(LC_MESSAGES, $C['lang_type']);
_bindtextdomain('phpdisk', 'languages');
_bind_textdomain_codeset('phpdisk', $charset);
_textdomain('phpdisk');
$ads_cache = PHPDISK_ROOT.'system/global/ads.cache.php';
if(file_exists($ads_cache)) {
	require_once($ads_cache);
} else {
	ads_cache();
}
load_active_plugins();
if(!@get_magic_quotes_gpc()) {
	$_GET = addslashes_array($_GET);
	$_POST = addslashes_array($_POST);
	$_COOKIE = addslashes_array($_COOKIE);
}
$onlineip = get_ip();
$str = strrchr($_SERVER['SCRIPT_NAME'],'/');
$curr_script = substr($str,1,strlen($str)-5);
if(in_array($curr_script,array('account','search','hotfile','public'))) {
	include_once(PHPDISK_ROOT.'includes/dosafe.php');
}
$my_sid = session_id();
$login_cookie_name = 'phpdisk_zcore_v2_info';
list($pd_uid,$pd_gid,$pd_username,$pd_pwd,$pd_email) = gpc(PHPDISK_COOKIE,'C','') ? explode("\t", pd_encode(gpc(PHPDISK_COOKIE,'C',''), 'DECODE')) : array('', '', '','','');
$pd_uid = (int)$pd_uid;
$pd_pwd = $db->escape($pd_pwd);
if(!$pd_uid || !$pd_pwd) {
	$pd_uid = 0;
} else {
	$userinfo = $db->fetch_one_array("select userid,u.gid,username,password,email,group_name from {$tpf}users u,{$tpf}groups g where u.userid='$pd_uid' and password='$pd_pwd' and u.gid=g.gid limit 1");
	if($userinfo) {
		$pd_username = $userinfo['username'];
		$pd_email = $userinfo['email'];
		$pd_gid = $userinfo['gid'];
		$pd_group_name = $userinfo['group_name'];
		check_share_login($pd_uid,$pd_gid);
	} else {
		$pd_uid = 0;
		$pd_pwd = '';
		pd_setcookie(PHPDISK_COOKIE, '',-3600);
	}
}
unset($userinfo);
if($pd_uid) {
	$myinfo = get_profile($pd_uid);
	$myinfo[wealth] = sprintf('%.2f',$myinfo[wealth]);
	if(date('Y-m-d')<>date('Y-m-d',$myinfo[last_login_time])) {
		$db->query_unbuffered("update {$tpf}users set last_login_ip='$onlineip',last_login_time='$timestamp' where userid='$pd_uid'");
		site_stat('login_user',1);
	}
}
if(display_plugin('api','open_uc_plugin',$settings['connect_uc'],0)) {
	define('P_W','admincp');
	$uc_conf = PD_PLUGINS_DIR.'api/uc_configs.inc.php';
	file_exists($uc_conf) ? require_once $uc_conf: exit(__('not_uc_conf').$uc_conf);
	$uc_client = $settings['connect_uc_type']=='phpwind' ? PD_PLUGINS_DIR.'api/pw_client/uc_client.php': PD_PLUGINS_DIR.'api/uc_client/client.php';
	file_exists($uc_client) ? require_once $uc_client: exit(__('not_uc_client').$uc_client);
}
$news_url = $auth['com_news_url'] ? $auth['com_news_url'] : 'http://www.phpdisk.com/m_news/m_idx.php';
$upgrade_url = $auth['com_upgrade_url'] ? $auth['com_upgrade_url'] : 'http://www.phpdisk.com/autoupdate/last_version_x2.php';
$pg = (int)gpc('pg','G',0);
!$pg && $pg = 1;
$perpage = $C['set']['perpage'] ? (int)$C['set']['perpage'] : 20;
$error = false;
$item = trim(gpc('item','GP','',1));
$app = trim(gpc('app','GP','',1));
$action = trim(gpc('action','GP','',1));
$task = trim(gpc('task','GP','',1));
$menu = trim(gpc('menu','GP','',1));
$p_formhash = trim(gpc('formhash','P',''));
$formhash = formhash();
if(!defined('ADMINCP')) {
	define('ADMINCP','admincp');
}
require_once(PHPDISK_ROOT.'includes/consts.inc.php');
if(display_plugin('phpdisk_ng_log','open_phpdisk_ng_log_plugin',$settings['open_phpdisk_ng_log'],0)) {
	if(function_exists('ng_log')) {
		ng_log();
	}
}
$sess_id = random(32);
$down_process = 'pc_'.date('YmdH');
$phone_down_process = 'ph_'.date('YmdH');
function sql_log($sql) {
	global $onlineip,$pd_username,$timestamp,$tpf,$adminset;
	$str = "<?php exit; ?>$onlineip\t$pd_username\t".date('Y-m-d H:i:s').LF;
	$str .= "SQL:".$sql.LF;
	$str .= "USER_AGENT:".$_SERVER['HTTP_USER_AGENT'].LF;
	$str .= "URI:".$_SERVER['REQUEST_URI'].LF;
	$str .= "POST:".LF;
	$str .= var_export($_POST,true).LF;
	$str .= "GET:".LF;
	$str .= var_export($_GET,true).LF;
	$str .= '|------------------------->>>'.LF;
	$dir = PHPDISK_ROOT.'system/sql_log/';
	make_dir($dir);
	$arr = explode(' ',trim($sql));
	$tag = trim($arr[0]);
	if($adminset[open_sql_log] && strpos($sql,'pd_users')!==false && strpos($sql,'pd_files')===false) {
		$f = date('YmdH').'_1.php';
		$f2 = date('YmdH').'_2.php';
		if(file_exists($dir.$f)) {
			write_file($dir.$f,$str,'ab');
			write_file($dir.$f2,'<?php exit; ?>'.$sql.LF,'ab');
		} else {
			write_file($dir.$f,$str);
			write_file($dir.$f2,'<?php exit; ?>'.$sql.LF);
		}
		foreach (glob($dir."*.php") as $filename) {
			if($timestamp-@filemtime($filename)> 86400) {
				@unlink($filename);
			}
		}
	}
}
?>