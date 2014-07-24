<?php 
defined('IN_DESTOON') or exit('Access Denied');
login();
isset($MODULE[23]) or dheader($MODULE[2]['linkurl']);
require DT_ROOT.'/module/'.$module.'/common.inc.php';
require DT_ROOT.'/include/post.func.php';
$_status = $L['group_status'];
$dstatus = $L['group_dstatus'];
$step = isset($step) ? trim($step) : '';
$timenow = timetodate($DT_TIME, 3);
$memberurl = $MOD['linkurl'];
$myurl = userurl($_username);
$table = $DT_PRE.'auction_order';
if($action == 'update') {
	$itemid or message();
	$td = $db->get_one("SELECT * FROM {$table} WHERE itemid=$itemid");
	$td or message($L['group_msg_null']);
	if($td['buyer'] != $_username && $td['seller'] != $_username) message($L['group_msg_deny']);
	$td['adddate'] = timetodate($td['addtime'], 5);
	$td['updatedate'] = timetodate($td['updatetime'], 5);
	$td['linkurl'] = $EXT['linkurl'].'redirect.php?mid=23&itemid='.$td['gid'];
	$gid = $td['gid'];
	$nav = $_username == $td['buyer'] ? 'action_order' : 'action';
	switch($step) {
		case 'detail':
			$td['total'] = $td['amount'];
			$head_title = $L['group_detail_title'];
		break;
		case 'used':
			if($td['seller'] == $_username) {
				if($td['status'] != 0 || $td['logistic']) message();
				$db->query("UPDATE {$table} SET status=2,updatetime=$DT_TIME WHERE itemid=$itemid");
				dmsg($L['op_success'], '?page='.$page);
			} else {
				if($td['status'] != 2 || $td['logistic']) message();
				$db->query("UPDATE {$table} SET status=3,updatetime=$DT_TIME WHERE itemid=$itemid");
				money_add($td['seller'], $td['amount']);
				money_record($td['seller'], $td['amount'], '站内', 'system', '订单完成', '订单号:'.$td['itemid']);
				dmsg($L['op_trade_success'], '?action=order&page='.$page);
			}
		break;
		case 'receive':			
			
			
					
			$sql = "SELECT * FROM {$DT_PRE}auction WHERE itemid='".$td['gid']."'";			
			$rs22  = $db->query($sql);
			while ($rr22 = $db->fetch_array($rs22)) {
				$username=$rr22['username']; //卖家用户名
			}
			//查询卖家的bondlocking
			$sql3 = "SELECT * FROM {$DT_PRE}member WHERE username='".$username."'";
			
			
			$rs3  = $db->query($sql3);
			while ($rr3 = $db->fetch_array($rs3)) {
				$bondlockingsell = $rr3['bondlocking'];
				$bondsell = $rr3['bond'];
			}
			$bondlockingsellover=$bondlockingsell-300;
			$bondsellover=$bondsell+300;
			//解冻卖家的保证金
			$db->query("update destoon_member set bondlocking='".$bondlockingsellover."',bond='".$bondsellover."' where username='".$username."'");
					

			
			if($td['status'] != 1 || $td['buyer'] != $_username || !$td['logistic']) message();
			$db->query("UPDATE {$table} SET status=3,updatetime=$DT_TIME WHERE itemid='".$itemid."'");
			$addcredit=$td['amount']*0.1;//积分按照交易金额的10%计算
			$db->query("UPDATE `{$DT_PRE}member` SET `credit`=`credit`+$addcredit WHERE username='".$username."'");//交易成功之后给卖家添加积分
			money_add($td['seller'], $td['amount']);
			money_record($td['seller'], $td['amount'], '站内', 'system', '订单完成', '订单号:'.$td['itemid']);
			dmsg($L['op_trade_success'], '?action=order&page='.$page); 
		break;
		case 'send':
			if($td['status'] != 0 || $td['seller'] != $_username || !$td['logistic']) message();
			if($submit) {
				$yongjin=$td['amount']*0.035;
				$yongjin=number_format($yongjin,1);
				$yongjin=$yongjin>4000 ? $yongjin=4000 : $yongjin;
				money_add($_username, -$yongjin);
				money_record($_username, -$yongjin, $L['in_site'], 'system', "扣除佣金", '订单号ID('.$itemid.')');
				$send_type = htmlspecialchars($send_type);
				$send_no = htmlspecialchars($send_no);
				$send_time = htmlspecialchars($send_time);
				$db->query("UPDATE {$table} SET status=1,updatetime=$DT_TIME,send_type='$send_type',send_no='$send_no',send_time='$send_time' WHERE itemid=$itemid");
				dmsg($L['op_success'], '?page='.$page);
			} else {
				$amount1 = $td['amount']+($td['amount']*0.035);
				if($amount1 > $_money) message($L['need_charge'], '/member/charge.php?action=pay&amount='.($amount1-$_money));
				$head_title = $L['group_send_title'];
				$send_types = explode('|', trim($MOD['send_types']));
				$send_time = timetodate($DT_TIME, 3);
			}
		break;
	}
} else if($action == 'order') {
	$sfields = $L['group_order_sfields'];
	$dfields = array('title', 'title ', 'amount', 'password', 'seller', 'send_type', 'send_no', 'note');
	isset($fields) && isset($dfields[$fields]) or $fields = 0;
	$gid = isset($gid) ? intval($gid) : 0;
	(isset($seller) && check_name($seller)) or $seller = '';
	isset($fromtime) or $fromtime = '';
	isset($totime) or $totime = '';
	$status = isset($status) && isset($dstatus[$status]) ? intval($status) : '';
	$fields_select = dselect($sfields, 'fields', '', $fields);
	$status_select = dselect($dstatus, 'status', $L['status'], $status, '', 1, '', 1);
	$condition = "buyer='$_username'";
	if($keyword) $condition .= " AND $dfields[$fields] LIKE '%$keyword%'";
	if($fromtime) $condition .= " AND addtime>".(strtotime($fromtime.' 00:00:00'));
	if($totime) $condition .= " AND addtime<".(strtotime($totime.' 23:59:59'));
	if($status !== '') $condition .= " AND status='$status'";
	if($itemid) $condition .= " AND itemid='$itemid'";
	if($gid) $condition .= " AND gid='$gid'";
	if($seller) $condition .= " AND seller='$seller'";
	$r = $db->get_one("SELECT COUNT(*) AS num FROM {$DT_PRE}auction_order WHERE $condition");
	$pages = pages($r['num'], $page, $pagesize);		
	$lists = array();
	$result = $db->query("SELECT * FROM {$DT_PRE}auction_order WHERE $condition ORDER BY itemid DESC LIMIT $offset,$pagesize");
	$amount = $fee = $money = 0;
	while($r = $db->fetch_array($result)) {
		$r['addtime'] = str_replace(' ', '<br/>', timetodate($r['addtime'], 5));
		$r['updatetime'] = str_replace(' ', '<br/>', timetodate($r['updatetime'], 5));
		$r['linkurl'] = $EXT['linkurl'].'redirect.php?mid=23&itemid='.$r['gid'];
		$r['dstatus'] = $_status[$r['status']];
		$r['money'] = $r['amount'];
		$r['money'] = number_format($r['money'], 2, '.', '');
		$amount += $r['amount'];
		$lists[] = $r;
	}
	$money = $amount + $fee;
	$money = number_format($money, 2, '.', '');
	$forward = urlencode($DT_URL);
	//$head_title = $L['group_order_title'];
	$head_title = '发出的竞拍订单';
} else {
	$sfields = $L['group_sfields'];
	$dfields = array('title', 'title ', 'amount', 'password', 'buyer', 'buyer_name', 'buyer_address', 'buyer_postcode', 'buyer_mobile', 'buyer_phone', 'send_type', 'send_no', 'note');
	$gid = isset($gid) ? intval($gid) : 0;
	(isset($buyer) && check_name($buyer)) or $buyer = '';
	isset($fields) && isset($dfields[$fields]) or $fields = 0;
	isset($fromtime) or $fromtime = '';
	isset($totime) or $totime = '';
	$status = isset($status) && isset($dstatus[$status]) ? intval($status) : '';
	$fields_select = dselect($sfields, 'fields', '', $fields);
	$status_select = dselect($dstatus, 'status', $L['status'], $status, '', 1, '', 1);
	$condition = "seller='$_username'";
	if($keyword) $condition .= " AND $dfields[$fields] LIKE '%$keyword%'";
	if($fromtime) $condition .= " AND addtime>".(strtotime($fromtime.' 00:00:00'));
	if($totime) $condition .= " AND addtime<".(strtotime($totime.' 23:59:59'));
	if($status !== '') $condition .= " AND status='$status'";
	if($itemid) $condition .= " AND itemid=$itemid";
	if($gid) $condition .= " AND gid=$gid";
	if($buyer) $condition .= " AND buyer='$buyer'";
	$r = $db->get_one("SELECT COUNT(*) AS num FROM {$table} WHERE $condition");
	$pages = pages($r['num'], $page, $pagesize);		
	$groups = array();
	$result = $db->query("SELECT * FROM {$table} WHERE $condition ORDER BY itemid DESC LIMIT $offset,$pagesize");
	$amount = $fee = $money = 0;
	while($r = $db->fetch_array($result)) {
		$r['addtime'] = str_replace(' ', '<br/>', timetodate($r['addtime'], 5));
		$r['updatetime'] = str_replace(' ', '<br/>', timetodate($r['updatetime'], 5));
		$r['linkurl'] = $EXT['linkurl'].'redirect.php?mid=23&itemid='.$r['gid'];
		$r['dstatus'] = $_status[$r['status']];
		$r['money'] = $r['amount'];
		$r['money'] = number_format($r['money'], 2, '.', '');
		$amount += $r['amount'];
		$groups[] = $r;
	}
	$money = $amount + $fee;
	$money = number_format($money, 2, '.', '');
	$forward = urlencode($DT_URL);
	$head_title = "收到的竞拍订单";
}
include template('auction', $module);
?>