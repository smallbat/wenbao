<?php defined('IN_DESTOON') or exit('Access Denied');?><?php include template('header');?>
<div class="m">
<div class="left_box">
<div class="pos">当前位置: <a href="<?php echo $MODULE['1']['linkurl'];?>">首页</a> &raquo; <a href="<?php echo $MODULE['2']['linkurl'];?>">转到</a></div>
<div style="width:600px;margin:auto;padding:25px 30px 25px 0;">
<input type="text" size="50" id="url" value="<?php echo $url;?>" style="padding:3px;border:#7D96C4 1px solid;font-weight:bold;"/> <img src="<?php echo DT_STATIC;?><?php echo $MODULE['2']['moduledir'];?>/image/goto.gif" onclick="go_to();" align="absmiddle" class="c_p" title="转到"/>
</div>
<?php if($action) { ?>
<div style="width:600px;margin:auto;padding:10px 15px 10px 15px;background:#FFF5D8;line-height:200%;font-size:14px;">
一封验证邮件已经发送至您的电子邮箱 <strong><?php echo $email;?></strong>，请注意查收<br/>
</div>
<div style="width:600px;margin:auto;padding:20px 15px 10px 15px;line-height:200%;font-size:14px;">
<strong>没有收到邮件？</strong><br/>
- 尝试到广告邮件和垃圾邮件里找找看(被误屏蔽)<br/>
- 将 <span class="f_gray"><?php echo $DT['mail_sender'];?></span> 加入您的邮件白名单，然后重试<br/>
<?php if($action=='register') { ?>
- <a href="send.php?action=check">再次发送验证邮件</a><br/>
<?php } ?>
</div>
<?php } ?>
<br/><br/>
</div>
</div>
<script type="text/javascript">
function go_to() {
if(Dd('url').value.indexOf('://') == -1 || Dd('url').value.length < 12) {
alert('请填写正确的URL');
return false;
}
Go(Dd('url').value);
}
</script>
<?php include template('footer');?>