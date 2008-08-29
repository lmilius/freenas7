#!/usr/local/bin/php
<?php
/*
	disks_manage_smart.php
	Copyright (C) 2006-2008 Volker Theile (votdev@gmx.de)
	All rights reserved.

	part of FreeNAS (http://www.freenas.org)
	Copyright (C) 2005-2008 Olivier Cochard-Labbe <olivier@freenas.org>.
	All rights reserved.

	Based on m0n0wall (http://m0n0.ch/wall)
	Copyright (C) 2003-2006 Manuel Kasper <mk@neon1.net>.
	All rights reserved.

	Redistribution and use in source and binary forms, with or without
	modification, are permitted provided that the following conditions are met:

	1. Redistributions of source code must retain the above copyright notice,
	   this list of conditions and the following disclaimer.

	2. Redistributions in binary form must reproduce the above copyright
	   notice, this list of conditions and the following disclaimer in the
	   documentation and/or other materials provided with the distribution.

	THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES,
	INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
	AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
	AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
	OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
	SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
	INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
	CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
	ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
	POSSIBILITY OF SUCH DAMAGE.
*/
require("guiconfig.inc");
require("email.inc");

$pgtitle = array(gettext("Disks"),gettext("Management"),gettext("S.M.A.R.T."));

if (!is_array($config['smartd']['selftest']))
	$config['smartd']['selftest'] = array();

$a_type = array( "S" => "Short Self-Test", "L" => "Long Self-Test", "C" => "Conveyance Self-Test", "O" => "Offline Immediate Test");
$a_selftest = &$config['smartd']['selftest'];

$pconfig['enable'] = isset($config['smartd']['enable']);
$pconfig['interval'] = $config['smartd']['interval'];
$pconfig['powermode'] = $config['smartd']['powermode'];
$pconfig['temp_diff'] = $config['smartd']['temp']['diff'];
$pconfig['temp_info'] = $config['smartd']['temp']['info'];
$pconfig['temp_crit'] = $config['smartd']['temp']['crit'];
$pconfig['email_enable'] = isset($config['smartd']['email']['enable']);
$pconfig['email_to'] = $config['smartd']['email']['to'];
$pconfig['email_subject'] = $config['smartd']['email']['subject'];

if ($_POST) {
	unset($input_errors);
	$pconfig = $_POST;

	$reqdfields = explode(" ", "interval powermode temp_diff temp_info temp_crit");
	$reqdfieldsn = array(gettext("Check interval"), gettext("Power mode"), gettext("Difference"), gettext("Informal"), gettext("Critical"));
	$reqdfieldst = explode(" ", "numericint string numericint numericint numericint");

	do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);
	do_input_validation_type($_POST, $reqdfields, $reqdfieldsn, $reqdfieldst, &$input_errors);

	if (10 > $_POST['interval']) {
		$input_errors[] = gettext("Interval must be greater or equal than 10 seconds.");
	}

	if (!$input_errors) {
		$config['smartd']['enable'] = $_POST['enable'] ? true : false;
		$config['smartd']['interval'] = $_POST['interval'];
		$config['smartd']['powermode'] = $_POST['powermode'];
		$config['smartd']['temp']['diff'] = $_POST['temp_diff'];
		$config['smartd']['temp']['info'] = $_POST['temp_info'];
		$config['smartd']['temp']['crit'] = $_POST['temp_crit'];
		$config['smartd']['email']['enable'] = $_POST['email_enable'] ? true : false;
		$config['smartd']['email']['to'] = $_POST['email_to'];
		$config['smartd']['email']['subject'] = $_POST['email_subject'];

		write_config();

		$retval = 0;
		if (!file_exists($d_sysrebootreqd_path)) {
			config_lock();
			$retval |= rc_update_service("smartd");
			config_unlock();
		}

		$savemsg = get_std_save_message($retval);

		if ($retval == 0) {
			if (file_exists($d_smartconfdirty_path))
				unlink($d_smartconfdirty_path);
		}
	}
}

if ($_GET['act'] == "del") {
	if ($a_selftest[$_GET['id']]) {
		unset($a_selftest[$_GET['id']]);

		write_config();
		touch($d_smartconfdirty_path);

		header("Location: disks_manage_smart.php");
		exit;
	}
}
?>
<?php include("fbegin.inc");?>
<script language="JavaScript">
<!--
function enable_change(enable_change) {
	var endis = !(document.iform.enable.checked || enable_change);

	if (enable_change.name == "email_enable") {
		endis = !enable_change.checked;

		document.iform.email_to.disabled = endis;
		document.iform.email_subject.disabled = endis;
	} else {
		document.iform.interval.disabled = endis;
		document.iform.powermode.disabled = endis;
		document.iform.temp_diff.disabled = endis;
		document.iform.temp_info.disabled = endis;
		document.iform.temp_crit.disabled = endis;
		document.iform.email_enable.disabled = endis;

		if (document.iform.enable.checked == true) {
			endis = !(document.iform.email_enable.checked || enable_change);
		}

		document.iform.email_to.disabled = endis;
		document.iform.email_subject.disabled = endis;
	}
}
//-->
</script>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
	<tr>
    <td class="tabnavtbl">
      <ul id="tabnav">
      	<li class="tabinact"><a href="disks_manage.php"><span><?=gettext("Management");?></span></a></li>
				<li class="tabact"><a href="disks_manage_smart.php" title="<?=gettext("Reload page");?>"><span><?=gettext("S.M.A.R.T.");?></span></a></li>
				<li class="tabinact"><a href="disks_manage_iscsi.php"><span><?=gettext("iSCSI Initiator");?></span></a></li>
      </ul>
    </td>
  </tr>
  <tr>
    <td class="tabcont">
      <form action="disks_manage_smart.php" method="post" name="iform" id="iform">
				<?php if (isset($pconfig['email_enable']) && (0 !== email_validate_settings())) print_error_box(sprintf(gettext("Make sure you have already configured your <a href='%s'>Email</a> settings."), "system_email.php"));?>
				<?php if ($input_errors) print_input_errors($input_errors);?>
				<?php if ($savemsg) print_info_box($savemsg);?>
				<?php if (file_exists($d_smartconfdirty_path)) print_config_change_box();?>
				<table width="100%" border="0" cellpadding="6" cellspacing="0">
					<?php html_titleline_checkbox("enable", gettext("Self-Monitoring, Analysis and Reporting Technology"), $pconfig['enable'] ? true : false, gettext("Enable"), "enable_change(this)");?>
					<?php html_inputbox("interval", gettext("Check interval"), $pconfig['interval'], gettext("Sets the interval between disk checks to N seconds. The minimum allowed value is 10."), true, 5);?>
					<tr>
						<td width="22%" valign="top" class="vncellreq"><?=gettext("Power mode");?></td>
						<td width="78%" class="vtable">
							<select name="powermode" class="formfld" id="powermode">
								<?php $types = explode(" ", "Never Sleep Standby Idle"); $vals = explode(" ", "never sleep standby idle");?>
								<?php $j = 0; for ($j = 0; $j < count($vals); $j++):?>
								<option value="<?=$vals[$j];?>" <?php if ($vals[$j] == $pconfig['powermode']) echo "selected";?>><?=htmlspecialchars($types[$j]);?></option>
								<?php endfor;?>
							</select><br/>
							<span class="vexpl">
							<li><?=gettext("Never - Poll (check) the device regardless of its power mode. This may cause a disk which is spun-down to be spun-up when it is checked.");?></li>
							<li><?=gettext("Sleep - Check the device unless it is in SLEEP mode.");?></li>
							<li><?=gettext("Standby - Check the device unless it is in SLEEP or STANDBY mode. In these modes most disks are not spinning, so if you want to prevent a laptop disk from spinning up each poll, this is probably what you want.");?></li>
							<li><?=gettext("Idle - Check the device unless it is in SLEEP, STANDBY or IDLE mode. In the IDLE state, most disks are still spinning, so this is probably not what you want.");?></li>
							</span>
						</td>
					</tr>
					<?php html_separator();?>
					<?php html_titleline(gettext("Temperature monitoring"));?>
					<tr>
						<td width="22%" valign="top" class="vncellreq"><?=gettext("Difference");?></td>
						<td width="78%" class="vtable">
							<input name="temp_diff" type="text" class="formfld" id="temp_diff" size="5" value="<?=htmlspecialchars($pconfig['temp_diff']);?>">&nbsp;&deg;C<br/>
							<span class="vexpl"><?=gettext("Report if the temperature had changed by at least N degrees Celsius since last report. Set to 0 to disable this report.");?></span>
						</td>
					</tr>
					<tr>
						<td width="22%" valign="top" class="vncellreq"><?=gettext("Informal");?></td>
						<td width="78%" class="vtable">
							<input name="temp_info" type="text" class="formfld" id="temp_info" size="5" value="<?=htmlspecialchars($pconfig['temp_info']);?>">&nbsp;&deg;C<br/>
							<span class="vexpl"><?=gettext("Report if the temperature is greater or equal than N degrees Celsius. Set to 0 to disable this report.");?></span>
						</td>
					</tr>
					<tr>
						<td width="22%" valign="top" class="vncellreq"><?=gettext("Critical");?></td>
						<td width="78%" class="vtable">
							<input name="temp_crit" type="text" class="formfld" id="temp_crit" size="5" value="<?=htmlspecialchars($pconfig['temp_crit']);?>">&nbsp;&deg;C<br/>
							<span class="vexpl"><?=gettext("Report if the temperature is greater or equal than N degrees Celsius. Set to 0 to disable this report.");?></span>
						</td>
					</tr>
					<?php html_separator();?>
					<?php html_titleline(gettext("Scheduled self-tests"));?>
				  <tr>
			    	<td width="22%" valign="top" class="vncell"><?=gettext("Scheduled self-tests");?></td>
						<td width="78%" class="vtable">
				      <table width="100%" border="0" cellpadding="0" cellspacing="0">
				        <tr>
									<td width="20%" class="listhdrr"><?=gettext("Disk");?></td>
									<td width="30%" class="listhdrr"><?=gettext("Type");?></td>
									<td width="40%" class="listhdrr"><?=gettext("Description");?></td>
									<td width="10%" class="list"></td>
				        </tr>
							  <?php $i = 0; foreach($a_selftest as $selftest):?>
				        <tr>
				          <td class="listlr"><?=htmlspecialchars($selftest['devicespecialfile']);?>&nbsp;</td>
									<td class="listr"><?=htmlspecialchars(gettext($a_type[$selftest['type']]));?>&nbsp;</td>
									<td class="listr"><?=htmlspecialchars($selftest['desc']);?>&nbsp;</td>
				          <td valign="middle" nowrap class="list">
				          	<a href="disks_manage_smart_edit.php?id=<?=$i;?>"><img src="e.gif" title="<?=gettext("Edit self-test");?>" width="17" height="17" border="0"></a>
				            <a href="disks_manage_smart.php?act=del&id=<?=$i;?>" onclick="return confirm('<?=gettext("Do you really want to delete this scheduled self-test?");?>')"><img src="x.gif" title="<?=gettext("Delete self-test");?>" width="17" height="17" border="0"></a>
				          </td>
				        </tr>
				        <?php $i++; endforeach;?>
				        <tr>
				          <td class="list" colspan="3"></td>
				          <td class="list"><a href="disks_manage_smart_edit.php"><img src="plus.gif" title="<?=gettext("Add self-test");?>" width="17" height="17" border="0"></a></td>
						    </tr>
							</table>
							<span class="vexpl"><?=gettext("Add additional scheduled self-test.");?></span>
						</td>
					</tr>
					<?php html_separator();?>
					<?php html_titleline_checkbox("email_enable", gettext("Email report"), $pconfig['email_enable'] ? true : false, gettext("Activate"), "enable_change(this)");?>
					<?php html_inputbox("email_to", gettext("To email"), $pconfig['email_to'], sprintf("%s %s", gettext("Destination email address."), gettext("Separate email addresses by semi-colon.")), true, 40);?>
					<?php html_inputbox("email_subject", gettext("Subject"), $pconfig['email_subject'], gettext("The subject of the email."), true, 40);?>
					<tr>
						<td width="22%" valign="top">&nbsp;</td>
						<td width="78%">
							<input name="Submit" type="submit" class="formbtn" value="<?=gettext("Save and Restart");?>" onClick="enable_change(true)">
						</td>
					</tr>
					<tr>
						<td width="22%" valign="top">&nbsp;</td>
						<td width="78%"><span class="vexpl"><span class="red"><strong><?=gettext("Note");?>:</strong><br/>
						</span><?=gettext("Activate email report if you want to be notified if a failure or a new error has been detected, or if a S.M.A.R.T. command to a disk fails.");?></span>
						</td>
					</tr>
				</table>
			</form>
		</td>
	</tr>
</table>
<script language="JavaScript">
<!--
enable_change(false);
//-->
</script>
<?php include("fend.inc");?>