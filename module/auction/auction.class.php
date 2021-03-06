<?php 
defined('IN_DESTOON') or exit('Access Denied');
class auction {
	var $moduleid;
	var $itemid;
	var $db;
	var $table;
	var $table_data;
	var $split;
	var $errmsg = errmsg;

    function auction($moduleid) {
		global $db, $table, $table_data, $MOD;
		$this->moduleid = $moduleid;
		$this->table = $table;
		$this->table_data = $table_data;
		$this->split = $MOD['split'];
		$this->db = &$db;
		$this->fields = array('catid','mycatid','areaid','level','title','style','fee','introduce','marketprice','price','savemoney','discount','minamount','amount','logistic','thumb','tag','status','hits','orders','username','fromtime','totime','editor','addtime','edittime','ip','template','linkurl','filepath','note','company','truename','telephone','mobile','address','email','msn','qq','ali','skype','thumb1','thumb2','thumb3','thumb4','thumb5','thumb6','thumb7');
    }

	function pass($post) {
		global $DT_TIME, $MOD;
		if(!is_array($post)) return false;
		if(!$post['catid']) return $this->_(lang('message->pass_cate'));
		if(strlen($post['title']) < 3) return $this->_(lang('message->pass_title'));
		if(strlen($post['thumb']) < 15) return $this->_(lang('message->pass_thumb'));
		if(dround($post['price']) < 0.1) return $this->_('请填写起拍价');
		if(dround($post['marketprice'])){
		if(dround($post['marketprice']) < 0.1) return $this->_(lang('message->pass_auction_mprice'));
		if(dround($post['marketprice']) <= dround($post['price'])) return $this->_(lang('一口价不得低于起拍价'));
		}else{
		$post['marketprice']=99999999;
		}
		
		////////////////
		if(!$post['fromtime']) return $this->_(lang('请选择起拍时间'));
		//if(!$post['fromtime'] || !is_date($post['fromtime'])) return $this->_(lang('message->pass_exhibit_fromdate'));
		if(!$post['totime']) return $this->_('请选择拍卖结束日期');
		//if(!$post['totime'] || !is_date($post['totime'])) return $this->_(lang('message->pass_exhibit_todate'));
		if(strtotime($post['fromtime']) > strtotime($post['totime'])) return $this->_(lang('message->pass_exhibit_baddate'));
		/////////////////
		
		
		if($post['totime']) {
			//if(!is_date($post['totime'])) return $this->_(lang('message->pass_date'));日期格式错误
			if(strtotime($post['totime']) < $DT_TIME) return $this->_(lang('message->pass_todate'));
			//if(strtotime($post['totime'].' 23:59:59') < $DT_TIME) return $this->_(lang('message->pass_todate'));
		}
		return true;
	}

	function set($post) {
		global $MOD, $DT_TIME, $DT_IP, $_username, $_userid;
		$post['editor'] = $_username;
		$post['addtime'] = (isset($post['addtime']) && $post['addtime']) ? strtotime($post['addtime']) : $DT_TIME;
		$post['edittime'] = $DT_TIME;
		//$post['totime'] = $post['totime'] ? strtotime($post['totime'].' 23:59:59') : 0;
		//$post['fromtime'] = $post['fromtime'] ? strtotime($post['fromtime'].' 23:59:59') : 0;
		$post['totime'] = $post['totime'] ? strtotime($post['totime']) : 0;
		$post['fromtime'] = $post['fromtime'] ? strtotime($post['fromtime']) : 0;
		$post['discount'] = dround($post['price']*10/$post['marketprice'], 1);
		$post['savemoney'] = dround($post['marketprice'] - $post['price']);
		$post['fee'] = dround($post['fee']);
		$post['price'] = dround($post['price']);
		$post['marketprice'] = dround($post['marketprice']);
		$post['minamount'] = dround($post['minamount']);
		$post['amount'] = dround($post['amount']);
		$post['title'] = trim($post['title']);
		$post['content'] = stripslashes($post['content']);
		$post['content'] = save_local($post['content']);
		if($MOD['clear_link']) $post['content'] = clear_link($post['content']);
		if($MOD['save_remotepic']) $post['content'] = save_remote($post['content']);
		if($post['content'] && !$post['introduce'] && $post['introduce_length']) $post['introduce'] = addslashes(get_intro($post['content'], $MOD['introduce_length']));
		if($this->itemid) {
			$new = $post['content'];
			if($post['thumb']) $new .= '<img src="'.$post['thumb'].'">';
			if($post['thumb1']) $new .= '<img src="'.$post['thumb1'].'">';
			if($post['thumb2']) $new .= '<img src="'.$post['thumb2'].'">';
			if($post['thumb3']) $new .= '<img src="'.$post['thumb3'].'">';
			if($post['thumb4']) $new .= '<img src="'.$post['thumb4'].'">';
			if($post['thumb5']) $new .= '<img src="'.$post['thumb5'].'">';
			if($post['thumb6']) $new .= '<img src="'.$post['thumb6'].'">';
			if($post['thumb7']) $new .= '<img src="'.$post['thumb7'].'">';
			$r = $this->get_one();
			$old = $r['content'];
			if($r['thumb']) $old .= '<img src="'.$r['thumb'].'">';
			if($r['thumb1']) $old .= '<img src="'.$r['thumb1'].'">';
			if($r['thumb2']) $old .= '<img src="'.$r['thumb2'].'">';
			if($r['thumb3']) $old .= '<img src="'.$r['thumb3'].'">';
			if($r['thumb4']) $old .= '<img src="'.$r['thumb4'].'">';
			if($r['thumb5']) $old .= '<img src="'.$r['thumb5'].'">';
			if($r['thumb6']) $old .= '<img src="'.$r['thumb6'].'">';
			if($r['thumb7']) $old .= '<img src="'.$r['thumb7'].'">';
			delete_diff($new, $old);
		} else {
			$post['ip'] = $DT_IP;
		}
		if(!defined('DT_ADMIN')) {
			$content = $post['content'];
			unset($post['content']);
			$post = dhtmlspecialchars($post);
			$post['content'] = dsafe($content);
		}
		$post['content'] = addslashes($post['content']);
		return array_map("trim", $post);
	}

	function get_one() {
		$content_table = content_table($this->moduleid, $this->itemid, $this->split, $this->table_data);
        return $this->db->get_one("SELECT * FROM {$this->table} a,{$content_table} c WHERE a.itemid=c.itemid and a.itemid=$this->itemid");
	}

	function get_list($condition = 'status=3', $order = 'edittime DESC', $cache = '') {
		global $MOD, $pages, $page, $pagesize, $offset, $items, $sum;
		if($page > 1 && $sum) {
			$items = $sum;
		} else {
			$r = $this->db->get_one("SELECT COUNT(*) AS num FROM {$this->table} WHERE $condition", $cache);
			$items = $r['num'];
		}
		$pages = defined('CATID') ? listpages(1, CATID, $items, $page, $pagesize, 10, $MOD['linkurl']) : pages($items, $page, $pagesize);
		$lists = $catids = $CATS = array();
		$result = $this->db->query("SELECT * FROM {$this->table} WHERE $condition ORDER BY $order LIMIT $offset,$pagesize", $cache);
		while($r = $this->db->fetch_array($result)) {
			$r['alt'] = $r['title'];
			$r['title'] = set_style($r['title'], $r['style']);
			$r['userurl'] = userurl($r['username']);
			$r['linkurl'] = $MOD['linkurl'].$r['linkurl'];
			$catids[$r['catid']] = $r['catid'];
			$lists[] = $r;
		}
		if($catids) {
			$result = $this->db->query("SELECT catid,catname,linkurl FROM {$this->db->pre}category WHERE catid IN (".implode(',', $catids).")");
			while($r = $this->db->fetch_array($result)) {
				$CATS[$r['catid']] = $r;
			}
			if($CATS) {
				foreach($lists as $k=>$v) {
					$lists[$k]['catname'] = $v['catid'] ? $CATS[$v['catid']]['catname'] : '';
					$lists[$k]['caturl'] = $v['catid'] ? $MOD['linkurl'].$CATS[$v['catid']]['linkurl'] : '';
				}
			}
		}
		return $lists;
	}
	
	function get_auction_list1($username) {
		global $MOD, $pages, $page, $pagesize, $offset, $items, $sum;
		$condition = "auction_user='".$username."' AND status=3 AND auction_status=1";
		if($page > 1 && $sum) {
			$items = $sum;
		} else {
			$r = $this->db->get_one("SELECT COUNT(*) AS num FROM {$this->table} WHERE $condition");
			$items = $r['num'];
		}
		//print_r ($items);
			
		$pages = defined('ITEMID') ? listpages(1, ITEMID, $items, $page, $pagesize, 10, $MOD['linkurl']) : pages($items, $page, $pagesize);
		$lists = $itemids = $ITEMS = $catids = $CATS = array();
		$result = $this->db->query("SELECT * FROM {$this->table} WHERE $condition ORDER BY itemid DESC LIMIT $offset,$pagesize");
		while($r = $this->db->fetch_array($result)) {
			$itemids[$r['itemid']] = $r['itemid'];
			$lists[] = $r;
		}
		$fields = 'itemid,title,linkurl,thumb,auction_price,marketprice,hits,process,catid';
		if($itemids) {
			$result = $this->db->query("SELECT {$fields} FROM {$this->table} WHERE itemid IN (".implode(',', $itemids).")");
			while($r = $this->db->fetch_array($result)) {
				$ITEMS[$r['itemid']] = $r;
			}
			if($ITEMS) {
				foreach($lists as $k=>$v) {
					$lists[$k]['title'] 		= $v['itemid'] ? $ITEMS[$v['itemid']]['title'] : '';
					$lists[$k]['thumb'] 		= $ITEMS[$v['itemid']]['thumb'];
					$lists[$k]['auction_price']	= $ITEMS[$v['itemid']]['auction_price'];
					$lists[$k]['marketprice']	= $ITEMS[$v['itemid']]['marketprice'];
					$lists[$k]['hits']			= $ITEMS[$v['itemid']]['hits'];
					$lists[$k]['process']		= $ITEMS[$v['itemid']]['process'];
					$lists[$k]['catid']			= $ITEMS[$v['itemid']]['catid'];
					$catids[]	= $ITEMS[$v['itemid']]['catid'];
				}
			}
		}

		if ($catids) {
			$result = $this->db->query("SELECT catid,catname,linkurl FROM {$this->db->pre}category WHERE catid IN (".implode(',', $catids).")");
			while($r = $this->db->fetch_array($result)) {
				$CATS[$r['catid']] = $r;
			}
			if($CATS) {
				foreach($lists as $k=>$v) {
					$lists[$k]['catname']   = $CATS[$v['catid']]['catname'];
					$lists[$k]['caturl'] 	= $MOD['linkurl'].$CATS[$v['catid']]['linkurl'];
				}
			}
		}
		return $lists;
	}

	function get_auction_list($username) {
		global $MOD, $pages, $page, $pagesize, $offset, $items, $sum;
		$condition = "auction_user='".$username."'";
		if($page > 1 && $sum) {
			$items = $sum;
		} else {
			$r = $this->db->get_one("SELECT COUNT(*) AS num FROM {$this->table}_list WHERE $condition");
			$items = $r['num'];
		}
			
		$pages = defined('ITEMID') ? listpages(1, ITEMID, $items, $page, $pagesize, 10, $MOD['linkurl']) : pages($items, $page, $pagesize);
		$lists = $itemids = $ITEMS = $catids = $CATS = array();
		$result = $this->db->query("SELECT * FROM {$this->table}_list WHERE $condition ORDER BY time DESC LIMIT $offset,$pagesize");
		while($r = $this->db->fetch_array($result)) {
			$itemids[$r['itemid']] = $r['itemid'];
			$lists[] = $r;
		}
		$fields = 'itemid,title,linkurl,thumb,auction_price,marketprice,hits,process,catid';
		if($itemids) {
			$result = $this->db->query("SELECT {$fields} FROM {$this->table} WHERE itemid IN (".implode(',', $itemids).")");
			while($r = $this->db->fetch_array($result)) {
				$ITEMS[$r['itemid']] = $r;
			}
			if($ITEMS) {
				foreach($lists as $k=>$v) {
					$lists[$k]['title'] 		= $v['itemid'] ? $ITEMS[$v['itemid']]['title'] : '';
					$lists[$k]['thumb'] 		= $ITEMS[$v['itemid']]['thumb'];
					$lists[$k]['auction_price']	= $ITEMS[$v['itemid']]['auction_price'];
					$lists[$k]['marketprice']	= $ITEMS[$v['itemid']]['marketprice'];
					$lists[$k]['hits']			= $ITEMS[$v['itemid']]['hits'];
					$lists[$k]['process']		= $ITEMS[$v['itemid']]['process'];
					$lists[$k]['catid']			= $ITEMS[$v['itemid']]['catid'];
					$catids[]	= $ITEMS[$v['itemid']]['catid'];
				}
			}
		}

		if ($catids) {
			$result = $this->db->query("SELECT catid,catname,linkurl FROM {$this->db->pre}category WHERE catid IN (".implode(',', $catids).")");
			while($r = $this->db->fetch_array($result)) {
				$CATS[$r['catid']] = $r;
			}
			if($CATS) {
				foreach($lists as $k=>$v) {
					$lists[$k]['catname']   = $CATS[$v['catid']]['catname'];
					$lists[$k]['caturl'] 	= $MOD['linkurl'].$CATS[$v['catid']]['linkurl'];
				}
			}
		}
		return $lists;
	}


	function add($post) {
		global $MOD;
		
		$post = $this->set($post);
		//print_r ($post);exit;
		$sqlk = $sqlv = '';
		foreach($post as $k=>$v) {
			if(in_array($k, $this->fields)) { $sqlk .= ','.$k; $sqlv .= ",'$v'"; }
		}
        $sqlk = substr($sqlk, 1);
        $sqlv = substr($sqlv, 1);
		$this->db->query("INSERT INTO {$this->table} ($sqlk) VALUES ($sqlv)");
		$this->itemid = $this->db->insert_id();
		$content_table = content_table($this->moduleid, $this->itemid, $this->split, $this->table_data);
		$this->db->query("INSERT INTO {$content_table} (itemid,content) VALUES ('$this->itemid', '$post[content]')");
		$this->update($this->itemid);
		if($post['status'] == 3 && $post['username'] && $MOD['credit_add']) {
			credit_add($post['username'], $MOD['credit_add']);
			credit_record($post['username'], $MOD['credit_add'], 'system', lang('my->credit_record_add', array($MOD['name'])), 'ID:'.$this->itemid);
		}
		clear_upload($post['content'].$post['thumb'].$post['thumb1'].$post['thumb2'].$post['thumb3'].$post['thumb4'].$post['thumb5'].$post['thumb6'].$post['thumb7'], $this->itemid);
		return $this->itemid;
	}

	function edit($post) {
		$this->delete($this->itemid, false);
		$post = $this->set($post);
		$sql = '';
		foreach($post as $k=>$v) {
			if(in_array($k, $this->fields)) $sql .= ",$k='$v'";
		}
        $sql = substr($sql, 1);
	    $this->db->query("UPDATE {$this->table} SET $sql WHERE itemid=$this->itemid");
		$content_table = content_table($this->moduleid, $this->itemid, $this->split, $this->table_data);
	    $this->db->query("UPDATE {$content_table} SET content='$post[content]' WHERE itemid=$this->itemid");
		$this->update($this->itemid);
		clear_upload($post['content'].$post['thumb'].$post['thumb1'].$post['thumb2'].$post['thumb3'].$post['thumb4'].$post['thumb5'].$post['thumb6'].$post['thumb7'], $this->itemid);
		if($post['status'] > 2) $this->tohtml($this->itemid, $post['catid']);
		return true;
	}

	function tohtml($itemid = 0, $catid = 0) {
		global $module, $MOD;
		if($MOD['show_html'] && $itemid) tohtml('show', $module, "itemid=$itemid");
	}

	function update($itemid) {
		$item = $this->db->get_one("SELECT * FROM {$this->table} WHERE itemid=$itemid");
		$update = '';
		$keyword = $item['title'].','.$item['company'].','.strip_tags(cat_pos(get_cat($item['catid']), ','));
		if($keyword != $item['keyword']) {
			$keyword = str_replace("//", '', addslashes($keyword));
			$update .= ",keyword='$keyword'";
		}
		$item['itemid'] = $itemid;
		$linkurl = itemurl($item);
		if($linkurl != $item['linkurl']) $update .= ",linkurl='$linkurl'";
		$member = $item['username'] ? userinfo($item['username']) : array();
		if($member) {
			foreach(array('auctionid','vip','validated','company','areaid','truename','telephone','mobile','address','qq','msn','ali','skype') as $v) {
				if($item[$v] != $member[$v]) $update .= ",$v='".addslashes($member[$v])."'";
			}
			if($item['email'] != $member['mail']) $update .= ",email='".addslashes($member['mail'])."'";
		}
		if($update) $this->db->query("UPDATE {$this->table} SET ".(substr($update, 1))." WHERE itemid=$itemid");
	}

	function recycle($itemid) {
		if(is_array($itemid)) {
			foreach($itemid as $v) { $this->recycle($v); }
		} else {
			$this->db->query("UPDATE {$this->table} SET status=0 WHERE itemid=$itemid");
			$this->delete($itemid, false);
			return true;
		}		
	}

	function restore($itemid) {
		global $module, $MOD;
		if(is_array($itemid)) {
			foreach($itemid as $v) { $this->restore($v); }
		} else {
			$this->db->query("UPDATE {$this->table} SET status=3 WHERE itemid=$itemid");
			if($MOD['show_html']) tohtml('show', $module, "itemid=$itemid");
			return true;
		}		
	}

	function delete($itemid, $all = true) {
		global $MOD;
		if(is_array($itemid)) {
			foreach($itemid as $v) {
				$this->delete($v, $all);
			}
		} else {
			# 添加到审核通过之后执行
			$rs=$this->db->get_one("select * from destoon_auction where itemid=$itemid");
			$name=$rs['username'];
			$rsaddb=$this->db->get_one("select * from destoon_member where username='".$name."'");	
			$bond = $rsaddb['bond'];
			$bondlocking = $rsaddb['bondlocking'];				
			$bondauctionadd=$bondlocking-300;
			$bondadd=$bond+300;
			$this->db->query("update destoon_member set bondlocking='".$bondauctionadd."',bond='".$bondadd."' where username='".$name."'");					
			#——————————————————————————————————	
			$this->itemid = $itemid;
			$r = $this->get_one();
			if($MOD['show_html']) {
				$_file = DT_ROOT.'/'.$MOD['moduledir'].'/'.$r['linkurl'];
				if(is_file($_file)) unlink($_file);
			}
			if($all) {
				$userid = get_user($r['username']);
				if($r['thumb']) delete_upload($r['thumb'], $userid);
				if($r['content']) delete_local($r['content'], $userid);
				$this->db->query("DELETE FROM {$this->table} WHERE itemid=$itemid");
				$content_table = content_table($this->moduleid, $this->itemid, $this->split, $this->table_data);
				$this->db->query("DELETE FROM {$content_table} WHERE itemid=$itemid");
				if($MOD['cat_property']) $this->db->query("DELETE FROM {$this->db->pre}category_value WHERE moduleid=$this->moduleid AND itemid=$itemid");
				if($r['username'] && $MOD['credit_del']) {
					credit_add($r['username'], -$MOD['credit_del']);
					credit_record($r['username'], -$MOD['credit_del'], 'system', lang('my->credit_record_del', array($MOD['name'])), 'ID:'.$this->itemid);
				}
			}
		}
	}

	function check($itemid) {
		global $_username, $DT_TIME, $MOD;
		if(is_array($itemid)) {
			foreach($itemid as $v) { $this->check($v); }
		} else {
			$this->itemid = $itemid;
			$item = $this->get_one();
			if($MOD['credit_add'] && $item['username'] && $item['hits'] < 1) {
				credit_add($item['username'], $MOD['credit_add']);
				credit_record($item['username'], $MOD['credit_add'], 'system', lang('my->credit_record_add', array($MOD['name'])), 'ID:'.$this->itemid);
			}
			$this->db->query("UPDATE {$this->table} SET status=3,hits=hits+1,editor='$_username',edittime=$DT_TIME WHERE itemid=$itemid");
			$this->tohtml($itemid);
			return true;
		}
	}

	function reject($itemid) {
		global $_username, $DT_TIME;
		if(is_array($itemid)) {
			foreach($itemid as $v) { $this->reject($v); }
		} else {
			$this->db->query("UPDATE {$this->table} SET status=1,editor='$_username' WHERE itemid=$itemid");
				# 添加到审核通过之后执行
				$rs=$this->db->get_one("select * from destoon_auction where itemid=$itemid");								
				$name=$rs['username'];
				$rsaddb=$this->db->get_one("select * from destoon_member where username='".$name."'");	
				$bond = $rsaddb['bond'];
				$bondlocking = $rsaddb['bondlocking'];				
				$bondauctionadd=$bondlocking-300;
				$bondadd=$bond+300;
				$this->db->query("update destoon_member set bondlocking='".$bondauctionadd."',bond='".$bondadd."' where username='".$name."'");								
				#——————————————————————————————————	
			return true;
		}
	}

	function expire($condition = '') {
		global $DT_TIME;
		$this->db->query("UPDATE {$this->table} SET status=4 WHERE status=3 AND totime>0 AND totime<$DT_TIME $condition");
	}

	function clear($condition = 'status=0') {		
		$result = $this->db->query("SELECT itemid FROM {$this->table} WHERE $condition ");
		while($r = $this->db->fetch_array($result)) {
			$this->delete($r['itemid']);
		}
	}

	function level($itemid, $level) {
		$itemids = is_array($itemid) ? implode(',', $itemid) : $itemid;
		$this->db->query("UPDATE {$this->table} SET level=$level WHERE itemid IN ($itemids)");
	}

	function type($itemid, $typeid) {
		$itemids = is_array($itemid) ? implode(',', $itemid) : $itemid;
		$this->db->query("UPDATE {$this->table} SET typeid=$typeid WHERE itemid IN ($itemids)");
	}

	function refresh($itemid) {
		global $DT_TIME;
		$itemids = is_array($itemid) ? implode(',', $itemid) : $itemid;
		$this->db->query("UPDATE {$this->table} SET edittime='$DT_TIME' WHERE itemid IN ($itemids)");
	}

	function _update($username) {
		global $DT_TIME;
		$this->db->query("UPDATE {$this->table} SET status=4 WHERE status=3 AND totime>0 AND totime<$DT_TIME AND username='$username'");
	}

	function _($e) {
		$this->errmsg = $e;
		return false;
	}
}
?>