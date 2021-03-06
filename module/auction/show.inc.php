<?php 
defined('IN_DESTOON') or exit('Access Denied');
$itemid or dheader($MOD['linkurl']);

if(!check_group($_auctionid, $MOD['auction_show'])) include load('403.inc');
require DT_ROOT.'/module/'.$module.'/common.inc.php';
$item 	= $db->get_one("SELECT * FROM {$table} WHERE itemid=$itemid");

/* 竞拍名单列表 start */
$page 		= 1;
if (isset($_GET['vals'])) {
	$page = $_GET['vals'];
}

$condition 	= 'itemid = '.$item['itemid'];
$items 		= $db->count("{$DT_PRE}auction_list",$condition);
$pagesize 	= '5';
$offset 	= ($page-1)*$pagesize;
$pages      = ceil($items/$pagesize);

$result 	= $db->query("SELECT * FROM {$table}_list WHERE {$condition} ORDER BY time DESC,id DESC LIMIT {$offset},{$pagesize}");
while($r = $db->fetch_array($result)) {
		$r['time'] = timetodate($r['time'], 5);
		$tags[] = $r;
	}
$db->free_result($result);
if (isset($_GET['vals'])) {
	echo "<table style=\"width:100%\">";
	echo "<tr>
							<th height=\"33\">参与用户</th>
							<th>出价</th>
							<th>状态</th>
							<th>竞拍时间</th>
							<th>IP地址</th>
						</tr>";
	foreach ($tags as $key => $value) {
		echo "<tr>";
		if($value['niming']==1){
		echo "<td height=\"33\">匿名</td>";
		}else{
		echo "<td height=\"33\">".$value['auction_user']."</td>";
		}
		echo "<td>￥".$value['price']."</td>";
		if($key==0){echo "<td><span>领先</span></td>"; }else{echo "<td>出局 </td>";};
		echo "<td>".$value['time']."</td>";
		echo "<td>".$value['ip']."</td>";
		echo "</tr>";
	}
	echo "</table>";
	return false;
}



// $pagesize = '5';
// $offset = ($page-1)*$pagesize;
// $pages = listpages($CAT, $items, $page, $pagesize);
// $tags = array();
// $offset = ($page-1)*$pagesize;
// $result = $db->query("SELECT * FROM {$DT_PRE}auction_list WHERE {$condition} ORDER BY ".$MOD['order']." LIMIT {$offset},{$pagesize}", ($CFG['db_expires'] && $page == 1) ? 'CACHE' : '', $CFG['db_expires']);
// 	while($r = $db->fetch_array($result)) {
// 		$r['adddate'] 	= timetodate($r['addtime'], 5);
// 		$r['editdate'] 	= timetodate($r['edittime'], 5);
// 		if($lazy && isset($r['thumb']) && $r['thumb']) $r['thumb'] = DT_SKIN.'image/lazy.gif" original="'.$r['thumb'];
// 		$r['alt'] 		= $r['title'];
// 		$r['title'] 	= set_style($r['title'], $r['style']);
// 		$r['linkurl'] 	= $MOD['linkurl'].$r['linkurl'];
// 		$tags[] 		= $r;
// 	}
// $db->free_result($result);


/* 竞拍名单列表 end */

if($item && $item['status'] > 2) {
	if($MOD['show_html'] && is_file(DT_ROOT.'/'.$MOD['moduledir'].'/'.$item['linkurl'])) d301($MOD['linkurl'].$item['linkurl']);
	extract($item);
} else { include load('404.inc'); }

$CAT = get_cat($catid);
if(!check_group($_auctionid, $CAT['auction_show'])) include load('403.inc');
$content_table = content_table($moduleid, $itemid, $MOD['split'], $table_data);
$t = $db->get_one("SELECT content FROM {$content_table} WHERE itemid=$itemid");
$content = $t['content'];
if($lazy) $content = img_lazy($content);
if($MOD['keylink']) $content = keylink($content, $moduleid);
$CP = $MOD['cat_property'] && $CAT['property'];
if($CP) {
	require DT_ROOT.'/include/property.func.php';
	$options = property_option($catid);
	$values = property_value($moduleid, $itemid);
}
$adddate = timetodate($addtime, 3);
$editdate = timetodate($edittime, 3);
$todate = $totime ? timetodate($totime, 3) : 0;
$expired = $totime && $totime < $DT_TIME ? true : false;
$linkurl = $MOD['linkurl'].$linkurl;
$jsdate = $totime ? timetodate($totime, 'Y,').(timetodate($totime, 'n')-1).timetodate($totime, ',j,H,i,s') : '';
$thumbs = get_albums($item);
$albums =  get_albums($item, 1);

$iprice = file_ext($price) == '00' ? intval($price) : $price;
if ($auction_price != '') {
	$iprice = file_ext($auction_price) == '00' ? intval($auction_price) : $auction_price;
}
$iprice_plus = $iprice + $item['minamount'];

$fee = get_fee($item['fee'], $MOD['fee_view']);
$update = '';
$left = $minamount ? $minamount - $orders : 1 - $orders;

if($expired) {
	if($process < 2) {
		//时间到了 TIME OVER
		$content = ob_template('email-ko', 'mail');
		send_message($item['auction_user'], "恭喜你，你的竞拍已经成功！", stripslashes($content));	
		$update .= ",process=2,endtime=$DT_TIME,auction_status=1";
		$item['process'] = $process = 2;
		$item['endtime'] = $endtime = $DT_TIME;
        /* 
		$sql = "SELECT auction_user FROM {$DT_PRE}auction_list WHERE auction_user !='".$item['auction_user']."' AND itemid='".$itemid."' GROUP BY auction_user";
		$rs  = $db->query($sql);
		while ($rr = $db->fetch_array($rs)) {
			$return_user[] = $rr['auction_user'];
		}
		foreach ($return_user as $key => $value) {
			//返还保证金
			money_add($value, $item['margins']);
			//发送竞价失败站内信
			$content = ob_template('email-false', 'mail');
							
		} */
		# 添加到审核通过之后执行
		$rs=$db->get_one("select * from {$DT_PRE}auction where itemid='".$itemid."'");	
		if(empty($rs)){
			$name=$rs['username'];
			$rsaddb=$db->get_one("select * from destoon_member where username='".$name."'");
			
			$bond = $rsaddb['bond'];
			$bondlocking = $rsaddb['bondlocking'];
			$bondlockingadd=$bondlocking-300;
			$bondadd=$bond+300;
			$db->query("update destoon_member set bondlocking='".$bondlockingadd."',bond='".$bondadd."' where username='".$name."'");						
			
			send_message($value, "您有一项竞拍商品竞价过期保证金已经归还。", stripslashes($content));	
		}
		#——————————————————————————————————	
		
	}
} else {
	if($process == 0) {
		if(($minamount > 0 && $orders >= $minamount) || ($minamount == 0 && $orders >= 1)) {
			$update .= ",process=1";
			$item['process'] = $process = 1;
		}
	} else if($process == 1) {
		if($amount && $amount <= $orders) {
			$content = ob_template('email-ko', 'mail');
			send_message($item['auction_user'], "恭喜你，你的竞拍已经成功！", stripslashes($content));		
			$update .= ",process=2,endtime=$DT_TIME,auction_status=1";
			$item['process'] = $process = 2;
			$item['endtime'] = $endtime = $DT_TIME;

			$sql = "SELECT auction_user FROM {$DT_PRE}auction_list WHERE auction_user !='".$item['auction_user']."' AND itemid='".$itemid."' GROUP BY auction_user";
			$rs  = $db->query($sql);
			while ($rr = $db->fetch_array($rs)) {
				$return_user[] = $rr['auction_user'];
			}
			foreach ($return_user as $key => $value) {
				money_add($value, $item['margins']);
				//发送竞价失败站内信
				$content = ob_template('email-false', 'mail');
				send_message($value, "您有一项竞拍商品竞价失败。", stripslashes($content));					
			}
		}
	}
}
if(check_group($_auctionid, $MOD['auction_contact'])) {
	if($fee) {
		$user_status = 4;
		$destoon_task = "moduleid=$moduleid&html=show&itemid=$itemid";
	} else {
		$user_status = 3;
		$member = $item['username'] ? userinfo($item['username']) : array();
		if($item['totime'] && $item['totime'] < $DT_TIME && $item['status'] == 3) $update .= ",status=4";
		if($member) {
			foreach(array('auctionid', 'vip','validated','company','areaid','truename','telephone','mobile','address','qq','msn','ali','skype') as $v) {
				if($item[$v] != $member[$v]) $update .= ",$v='".addslashes($member[$v])."'";
			}
			if($item['email'] != $member['mail']) $update .= ",email='$member[mail]'";
		}
	}
} else {
	$user_status = $_userid ? 1 : 0;
	if($_username && $item['username'] == $_username) {
		$member = userinfo($item['username']);
		$user_status = 3;
	}
}
include DT_ROOT.'/include/update.inc.php';
$seo_file = 'show';
include DT_ROOT.'/include/seo.inc.php';
if($EXT['wap_enable']) $head_mobile = $EXT['wap_url'].'index.php?moduleid='.$moduleid.'&itemid='.$itemid.($page > 1 ? '&page='.$page : '');
$template = $item['template'] ? $item['template'] : ($CAT['show_template'] ? $CAT['show_template'] : 'show');

include template($template, $module);
?>