#!/usr/local/bin/php
<?php
/*
	services_rsyncd_client_edit.php
	part of FreeNAS (http://www.freenas.org)
	Copyright (C) 2005-2008 Olivier Cochard-Labbe <olivier@freenas.org>.
	Improved by Mat Murdock <mmurdock@kimballequipment.com>.
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

$id = $_GET['id'];
if (isset($_POST['id']))
	$id = $_POST['id'];

$pgtitle = array(gettext("Services"),gettext("RSYNC"),gettext("Client"),isset($id)?gettext("Edit"):gettext("Add"));

/* Global arrays. */
$a_months = explode(" ",gettext("January February March April May June July August September October November December"));
$a_weekdays = explode(" ",gettext("Sunday Monday Tuesday Wednesday Thursday Friday Saturday"));

if (!is_array($config['rsync']))
	$config['rsync'] = array();

if (!is_array($config['rsync']['rsyncclient']))
	$config['rsync']['rsyncclient'] = array();

$a_rsyncclient = &$config['rsync']['rsyncclient'];

if (isset($id) && $a_rsyncclient[$id]) {
	$pconfig['rsyncserverip'] = $a_rsyncclient[$id]['rsyncserverip'];
	$pconfig['localshare'] = $a_rsyncclient[$id]['localshare'];
	$pconfig['remoteshare'] = $a_rsyncclient[$id]['remoteshare'];
	$pconfig['minute'] = $a_rsyncclient[$id]['minute'];
	$pconfig['hour'] = $a_rsyncclient[$id]['hour'];
	$pconfig['day'] = $a_rsyncclient[$id]['day'];
	$pconfig['month'] = $a_rsyncclient[$id]['month'];
	$pconfig['weekday'] = $a_rsyncclient[$id]['weekday'];
	$pconfig['sharetosync'] = $a_rsyncclient[$id]['sharetosync'];
	$pconfig['all_mins'] = $a_rsyncclient[$id]['all_mins'];
	$pconfig['all_hours'] = $a_rsyncclient[$id]['all_hours'];
	$pconfig['all_days'] = $a_rsyncclient[$id]['all_days'];
	$pconfig['all_months'] = $a_rsyncclient[$id]['all_months'];
	$pconfig['all_weekdays'] = $a_rsyncclient[$id]['all_weekdays'];
	$pconfig['description'] = $a_rsyncclient[$id]['description'];
	$pconfig['recursive'] = isset($a_rsyncclient[$id]['options']['recursive']);
	$pconfig['times'] = isset($a_rsyncclient[$id]['options']['times']);
	$pconfig['compress'] = isset($a_rsyncclient[$id]['options']['compress']);
	$pconfig['archive'] = isset($a_rsyncclient[$id]['options']['archive']);
	$pconfig['delete'] = isset($a_rsyncclient[$id]['options']['delete']);
	$pconfig['delete_algorithm'] = $a_rsyncclient[$id]['options']['delete_algorithm'];
	$pconfig['quiet'] = isset($a_rsyncclient[$id]['options']['quiet']);
	$pconfig['perms'] = isset($a_rsyncclient[$id]['options']['perms']);
	$pconfig['xattrs'] = isset($a_rsyncclient[$id]['options']['xattrs']);
	$pconfig['extraoptions'] = $a_rsyncclient[$id]['options']['extraoptions'];
} else {
	$pconfig['recursive'] = true;
	$pconfig['times'] = true;
	$pconfig['compress'] = true;
	$pconfig['archive'] = false;
	$pconfig['delete'] = false;
	$pconfig['delete_algorithm'] = "default";
	$pconfig['quiet'] = false;
	$pconfig['perms'] = false;
	$pconfig['xattrs'] = false;
	$pconfig['extraoptions'] = "";
}

if ($_POST) {
	unset($input_errors);
	unset($errormsg);

	$pconfig = $_POST;

	/* input validation */
	$reqdfields = explode(" ", "rsyncserverip localshare remoteshare");
	$reqdfieldsn = array(gettext("Remote RSYNC Server"),gettext("Local shares to be synchronized"),gettext("Remote module name"));
	do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);

	// Validate synchronization time
	do_input_validate_synctime($_POST, &$input_errors);

	if (!$input_errors) {
		$rsyncclient = array();

		$rsyncclient['rsyncserverip'] = $_POST['rsyncserverip'];
		$rsyncclient['minute'] = $_POST['minute'];
		$rsyncclient['hour'] = $_POST['hour'];
		$rsyncclient['day'] = $_POST['day'];
		$rsyncclient['month'] = $_POST['month'];
		$rsyncclient['weekday'] = $_POST['weekday'];
		$rsyncclient['localshare'] = $_POST['localshare'];
		$rsyncclient['remoteshare'] = $_POST['remoteshare'];
		$rsyncclient['all_mins'] = $_POST['all_mins'];
		$rsyncclient['all_hours'] = $_POST['all_hours'];
		$rsyncclient['all_days'] = $_POST['all_days'];
		$rsyncclient['all_months'] = $_POST['all_months'];
		$rsyncclient['all_weekdays'] = $_POST['all_weekdays'];
		$rsyncclient['description'] = $_POST['description'];
		$rsyncclient['options']['recursive'] = $_POST['recursive'] ? true : false;
		$rsyncclient['options']['times'] = $_POST['times'] ? true : false;
		$rsyncclient['options']['compress'] = $_POST['compress'] ? true : false;
		$rsyncclient['options']['archive'] = $_POST['archive'] ? true : false;
		$rsyncclient['options']['delete'] = $_POST['delete'] ? true : false;
		$rsyncclient['options']['delete_algorithm'] = $_POST['delete_algorithm'];
		$rsyncclient['options']['quiet'] = $_POST['quiet'] ? true : false;
		$rsyncclient['options']['perms'] = $_POST['perms'] ? true : false;
		$rsyncclient['options']['xattrs'] = $_POST['xattrs'] ? true : false;
		$rsyncclient['options']['extraoptions'] = $_POST['extraoptions'];

		if (isset($id) && $a_rsyncclient[$id])
			$a_rsyncclient[$id] = $rsyncclient;
		else
			$a_rsyncclient[] = $rsyncclient;

		touch($d_rsyncclientdirty_path);
		write_config();

		header("Location: services_rsyncd_client.php");
		exit;
	}
}
?>
<?php include("fbegin.inc");?>
<script language="JavaScript">
<!--
function set_selected(name) {
	document.getElementsByName(name)[1].checked = true;
}

function delete_change() {
	switch(document.iform.delete.checked) {
		case false:
			showElementById('delete_algorithm_tr','hide');
			break;

		case true:
			showElementById('delete_algorithm_tr','show');
			break;
	}
}
// -->
</script>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
	<tr>
		<td class="tabnavtbl">
			<ul id="tabnav">
				<li class="tabinact"><a href="services_rsyncd.php"><span><?=gettext("Server");?></span></a></li>
				<li class="tabact"><a href="services_rsyncd_client.php" title="<?=gettext("Reload page");?>"><span><?=gettext("Client");?></span></a></li>
				<li class="tabinact"><a href="services_rsyncd_local.php"><span><?=gettext("Local");?></span></a></li>
			</ul>
		</td>
	</tr>
  <tr>
    <td class="tabcont">
			<form action="services_rsyncd_client_edit.php" method="post" name="iform" id="iform">
				<?php if ($input_errors) print_input_errors($input_errors);?>
				<table width="100%" border="0" cellpadding="6" cellspacing="0">
					<tr>
						<td width="22%" valign="top" class="vncellreq"><?=gettext("Local share");?></td>
						<td width="78%" class="vtable">
							<input name="localshare" type="text" class="formfld" id="localshare" size="60" value="<?=htmlspecialchars($pconfig['localshare']);?>">
							<input name="browse" type="button" class="formbtn" id="Browse" onClick='ifield = form.localshare; filechooser = window.open("filechooser.php?p="+escape(ifield.value)+"&sd=/mnt", "filechooser", "scrollbars=yes,toolbar=no,menubar=no,statusbar=no,width=550,height=300"); filechooser.ifield = ifield; window.ifield = ifield;' value="..." \><br/>
							<span class="vexpl"><?=gettext("Path to be shared.");?></span>
					  </td>
					</tr>
			    <tr>
						<td width="22%" valign="top" class="vncellreq"><strong><?=gettext("Remote RSYNC Server");?><strong></td>
						<td width="78%" class="vtable">
							<input name="rsyncserverip" id="rsyncserverip" type="text" class="formfld" size="20" value="<?=htmlspecialchars($pconfig['rsyncserverip']);?>">
							<br><?=gettext("IP or FQDN address of remote RSYNC server.");?><br>
						</td>
					</tr>
					<tr>
						<td width="22%" valign="top" class="vncellreq"><?=gettext("Remote module name");?></td>
			      <td width="78%" class="vtable">
			        <input name="remoteshare" type="text" class="formfld" id="remoteshare" size="20" value="<?=htmlspecialchars($pconfig['remoteshare']);?>">
			      </td>
			    </tr>
			    <tr>
						<td width="22%" valign="top" class="vncellreq"><?=gettext("Synchronization Time");?></td>
						<td width="78%" class="vtable">
							<table width=100% border cellpadding="6" cellspacing="0">
								<tr>
									<td class="optsect_t"><b class="optsect_s"><?=gettext("minutes");?></b></td>
									<td class="optsect_t"><b class="optsect_s"><?=gettext("hours");?></b></td>
									<td class="optsect_t"><b class="optsect_s"><?=gettext("days");?></b></td>
									<td class="optsect_t"><b class="optsect_s"><?=gettext("months");?></b></td>
									<td class="optsect_t"><b class="optsect_s"><?=gettext("week days");?></b></td>
								</tr>
								<tr bgcolor=#cccccc>
									<td valign=top>
										<input type="radio" name="all_mins" id="all_mins1" value="1" <?php if (1 == $pconfig['all_mins']) echo "checked";?>>
										<?=gettext("All");?><br>
										<input type="radio" name="all_mins" id="all_mins2" value="0" <?php if (1 != $pconfig['all_mins']) echo "checked";?>>
										<?=gettext("Selected");?> ..<br>
										<table>
											<tr>
												<td valign=top>
													<select multiple size="12" name="minute[]" id="minutes1" onchange="set_selected('all_mins')">
														<?php for ($i = 0; $i <= 11; $i++):?>
														<option value="<?=$i;?>" <?php if (is_array($pconfig['minute']) && in_array("$i", $pconfig['minute'])) echo "selected";?>><?=htmlspecialchars($i);?></option>
														<?php endfor;?>
													</select>
												</td>
												<td valign=top>
													<select multiple size="12" name="minute[]" id="minutes2" onchange="set_selected('all_mins')">
														<?php for ($i = 12; $i <= 23; $i++):?>
														<option value="<?=$i;?>" <?php if (is_array($pconfig['minute']) && in_array("$i", $pconfig['minute'])) echo "selected";?>><?=htmlspecialchars($i);?></option>
														<?php endfor;?>
													</select>
												</td>
												<td valign=top>
													<select multiple size="12" name="minute[]" id="minutes3" onchange="set_selected('all_mins')">
														<?php for ($i = 24; $i <= 35; $i++):?>
														<option value="<?=$i;?>" <?php if (is_array($pconfig['minute']) && in_array("$i", $pconfig['minute'])) echo "selected";?>><?=htmlspecialchars($i);?></option>
														<?php endfor;?>
													</select>
												</td>
												<td valign=top>
													<select multiple size="12" name="minute[]" id="minutes4" onchange="set_selected('all_mins')">
														<?php for ($i = 36; $i <= 47; $i++):?>
														<option value="<?=$i;?>" <?php if (is_array($pconfig['minute']) && in_array("$i", $pconfig['minute'])) echo "selected";?>><?=htmlspecialchars($i);?></option>
														<?php endfor;?>
													</select>
												</td>
												<td valign=top>
													<select multiple size="12" name="minute[]" id="minutes5" onchange="set_selected('all_mins')">
														<?php for ($i = 48; $i <= 59; $i++):?>
														<option value="<?=$i;?>" <?php if (is_array($pconfig['minute']) && in_array("$i", $pconfig['minute'])) echo "selected";?>><?=htmlspecialchars($i);?></option>
														<?php endfor;?>
													</select>
												</td>
											</tr>
										</table>
										<br>
									</td>
									<td valign=top>
										<input type="radio" name="all_hours" id="all_hours1" value="1" <?php if (1 == $pconfig['all_hours']) echo "checked";?>>
										<?=gettext("All");?><br>
										<input type="radio" name="all_hours" id="all_hours2" value="0" <?php if (1 != $pconfig['all_hours']) echo "checked";?>>
										<?=gettext("Selected");?> ..<br>
										<table>
											<tr>
												<td valign=top>
													<select multiple size="12" name="hour[]" id="hours1" onchange="set_selected('all_hours')">
														<?php for ($i = 0; $i <= 11; $i++):?>
														<option value="<?=$i;?>" <?php if (is_array($pconfig['hour']) && in_array("$i", $pconfig['hour'])) echo "selected";?>><?=htmlspecialchars($i);?></option>
														<?php endfor;?>
													</select>
												</td>
												<td valign=top>
													<select multiple size="12" name="hour[]" id="hours2" onchange="set_selected('all_hours')">
														<?php for ($i = 12; $i <= 23; $i++):?>
														<option value="<?=$i;?>" <?php if (is_array($pconfig['hour']) && in_array("$i", $pconfig['hour'])) echo "selected";?>><?=htmlspecialchars($i);?></option>
														<?php endfor;?>
													</select>
												</td>
											</tr>
										</table>
									</td>
									<td valign=top>
										<input type="radio" name="all_days" id="all_days1" value="1" <?php if (1 == $pconfig['all_days']) echo "checked";?>>
										<?=gettext("All");?><br>
										<input type="radio" name="all_days" id="all_days2" value="0" <?php if (1 != $pconfig['all_days']) echo "checked";?>>
										<?=gettext("Selected");?> ..<br>
										<table>
											<tr>
												<td valign=top>
													<select multiple size="12" name="day[]" id="days1" onchange="set_selected('all_days')">
														<?php for ($i = 1; $i <= 12; $i++):?>
														<option value="<?=$i;?>" <?php if (is_array($pconfig['day']) && in_array("$i", $pconfig['day'])) echo "selected";?>><?=htmlspecialchars($i);?></option>
														<?php endfor;?>
													</select>
												</td>
												<td valign=top>
													<select multiple size="12" name="day[]" id="days2" onchange="set_selected('all_days')">
														<?php for ($i = 13; $i <= 24; $i++):?>
														<option value="<?=$i;?>" <?php if (is_array($pconfig['day']) && in_array("$i", $pconfig['day'])) echo "selected";?>><?=htmlspecialchars($i);?></option>
														<?php endfor;?>
													</select>
												</td>
												<td valign=top>
													<select multiple size="7" name="day[]" id="days3" onchange="set_selected('all_days')">
														<?php for ($i = 25; $i <= 31; $i++):?>
														<option value="<?=$i;?>" <?php if (is_array($pconfig['day']) && in_array("$i", $pconfig['day'])) echo "selected";?>><?=htmlspecialchars($i);?></option>
														<?php endfor;?>
													</select>
												</td>
											</tr>
										</table>
									</td>
									<td valign=top>
										<input type="radio" name="all_months" id="all_months1" value="1" <?php if (1 == $pconfig['all_months']) echo "checked";?>>
										<?=gettext("All");?><br>
										<input type="radio" name="all_months" id="all_months2" value="0" <?php if (1 != $pconfig['all_months']) echo "checked";?>>
										<?=gettext("Selected");?> ..<br>
										<table>
											<tr>
												<td valign=top>
													<select multiple size="12" name="month[]" id="months" onchange="set_selected('all_months')">
														<?php $i = 1; foreach ($a_months as $month):?>
														<option value="<?=$i;?>" <?php if (isset($pconfig['month']) && in_array("$i", $pconfig['month'])) echo "selected";?>><?=htmlspecialchars($month);?></option>
														<?php $i++; endforeach;?>
													</select>
												</td>
											</tr>
										</table>
									</td>
									<td valign=top>
										<input type="radio" name="all_weekdays" id="all_weekdays1" value="1" <?php if (1 == $pconfig['all_weekdays']) echo "checked";?>>
										<?=gettext("All");?><br>
										<input type="radio" name="all_weekdays" id="all_weekdays2" value="0" <?php if (1 != $pconfig['all_weekdays']) echo "checked";?>>
										<?=gettext("Selected");?> ..<br>
										<table>
											<tr>
												<td valign=top>
													<select multiple size="7" name="weekday[]" id="weekdays" onchange="set_selected('all_weekdays')">
														<?php $i = 0; foreach ($a_weekdays as $day):?>
														<option value="<?=$i;?>" <?php if (isset($pconfig['weekday']) && in_array("$i", $pconfig['weekday'])) echo "selected";?>><?=$day;?></option>
														<?php $i++; endforeach;?>
													</select>
												</td>
											</tr>
										</table>
									</td>
								</tr>
								<tr bgcolor=#cccccc>
									<td colspan=5>
										<?=gettext("Note: Ctrl-click (or command-click on the Mac) to select and de-select minutes, hours, days and months.");?>
									</td>
								</tr>
							</table>
						</td>
					</tr>
					<tr>
						<td width="22%" valign="top" class="vncell"><?=gettext("Description");?></td>
						<td width="78%" class="vtable">
							<input name="description" type="text" class="formfld" id="description" size="40" value="<?=htmlspecialchars($pconfig['description']);?>">
						</td>
					</tr>
					<tr>
						<td colspan="2" class="list" height="12"></td>
					</tr>
					<tr>
						<td colspan="2" valign="top" class="listtopic"><?=gettext("Advanced Options");?></td>
					</tr>
					<?php html_checkbox("recursive", gettext("Recursive"), $pconfig['recursive'] ? true : false, gettext("Recurse into directories."), "", false);?>
					<?php html_checkbox("times", gettext("Times"), $pconfig['times'] ? true : false, gettext("Preserve modification times."), "", false);?>
					<?php html_checkbox("compress", gettext("Compress"), $pconfig['compress'] ? true : false, gettext("Compress file data during the transfer."), "", false);?>
					<?php html_checkbox("archive", gettext("Archive"), $pconfig['archive'] ? true : false, gettext("Archive mode."), "", false);?>
					<?php html_checkbox("delete", gettext("Delete"), $pconfig['delete'] ? true : false, gettext("Delete files on the receiving side that don't exist on sender."), "", false, "delete_change()");?>
					<?php html_combobox("delete_algorithm", gettext("Delete algorithm"), $pconfig['delete_algorithm'], array("default" => "Default", "before" => "Before", "during" => "During", "delay" => "Delay", "after" => "After"), gettext("<li>Default - Rsync will choose the 'during' algorithm when talking to rsync 3.0.0 or newer, and the 'before' algorithm when talking to an older rsync.</li><li>Before - File-deletions will be done before the transfer starts.</li><li>During - File-deletions will be done incrementally as the transfer happens.</li><li>Delay - File-deletions will be computed during the transfer, and then removed after the transfer completes.</li><li>After - File-deletions will be done after the transfer has completed.</li>"), false);?>
					<tr>
						<td width="22%" valign="top" class="vncell"><?=gettext("Quiet");?></td>
						<td width="78%" class="vtable">
							<input name="quiet" id="quiet" type="checkbox" value="yes" <?php if ($pconfig['quiet']) echo "checked"; ?>> <?=gettext("Suppress non-error messages."); ?><br>
						</td>
					</tr>
					<?php html_checkbox("perms", gettext("Preserve permissions"), $pconfig['perms'] ? true : false, gettext("This option causes the receiving rsync to set the destination permissions to be the same as the source permissions."), "", false);?>
					<?php html_checkbox("xattrs", gettext("Preserve extended attributes"), $pconfig['xattrs'] ? true : false, gettext("This option causes rsync to update the remote extended attributes to be the same as the local ones."), "", false);?>
					<?php html_inputbox("extraoptions", gettext("Extra options"), $pconfig['extraoptions'], gettext("Extra options to rsync (usually empty)."), false, 40);?>
					<tr>
	          <td width="22%" valign="top">&nbsp;</td>
	          <td width="78%">
	            <input name="Submit" type="submit" class="formbtn" value="<?=gettext("Save");?>">
							<?php if (isset($id) && $a_rsyncclient[$id]): ?>
							<input name="id" type="hidden" value="<?=$id;?>">
							<?php endif; ?>
	          </td>
	        </tr>
	      </table>
			</form>
		</td>
	</tr>
</table>
<script language="JavaScript">
<!--
delete_change();
//-->
</script>
<?php include("fend.inc");?>