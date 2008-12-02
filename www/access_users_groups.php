#!/usr/local/bin/php
<?php
/*
	access_users.php
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

$pgtitle = array(gettext("Access"), gettext("Groups"));

if ($_POST) {
	$pconfig = $_POST;

	if ($_POST['apply']) {
		$retval = 0;
		if (!file_exists($d_sysrebootreqd_path)) {
			$retval |= ui_process_updatenotification("userdb_group", "userdbgroup_process_updatenotification");
			config_lock();
			$retval |= rc_exec_service("userdb");
			config_unlock();
		}
		$savemsg = get_std_save_message($retval);
		if ($retval == 0) {
			ui_cleanup_updatenotification("userdb_group");
		}
	}
}

if (!is_array($config['access']['group']))
	$config['access']['group'] = array();

array_sort_key($config['access']['group'], "name");
$a_group_conf = &$config['access']['group'];

$a_group = system_get_group_list();

if ($_GET['act'] === "del") {
	if ($a_group_conf[$_GET['id']]) {
		ui_set_updatenotification("userdb_group", UPDATENOTIFICATION_MODE_DIRTY, $a_group_conf[$_GET['id']]['uuid']);
		header("Location: access_users_groups.php");
		exit;
	}
}

function userdbgroup_process_updatenotification($mode, $data) {
	global $config;

	$retval = 0;

	switch ($mode) {
		case UPDATENOTIFICATION_MODE_NEW:
		case UPDATENOTIFICATION_MODE_MODIFIED:
			break;
		case UPDATENOTIFICATION_MODE_DIRTY:
			if (is_array($config['access']['group'])) {
				$index = array_search_ex($data, $config['access']['group'], "uuid");
				if (false !== $index) {
					unset($config['access']['group'][$index]);
					write_config();
				}
			}
			break;
	}

	return $retval;
}
?>
<?php include("fbegin.inc");?>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
	<tr>
		<td class="tabnavtbl">
			<ul id="tabnav">
				<li class="tabinact"><a href="access_users.php"><span><?=gettext("Users");?></span></a></li>
				<li class="tabact"><a href="access_users_groups.php" title="<?=gettext("Reload page");?>"><span><?=gettext("Groups");?></span></a></li>
			</ul>
		</td>
	</tr>
	<tr>
		<td class="tabcont">
			<form action="access_users_groups.php" method="post">
				<?php if ($savemsg) print_info_box($savemsg);?>
				<?php if (ui_exists_updatenotification("userdb_group")) print_config_change_box();?>
				<table width="100%" border="0" cellpadding="0" cellspacing="0">
					<tr>
						<td width="45%" class="listhdrr"><?=gettext("Group");?></td>
						<td width="5%" class="listhdrr"><?=gettext("GID");?></td>
						<td width="40%" class="listhdrr"><?=gettext("Description");?></td>
						<td width="10%" class="list"></td>
					</tr>
					<?php $i = 0; foreach ($a_group_conf as $groupv):?>
					<?php $notificationmode = ui_get_updatenotification_mode("userdb_group", $groupv['uuid']);?>
					<tr>
						<td class="listlr"><?=htmlspecialchars($groupv['name']);?>&nbsp;</td>
						<td class="listr"><?=htmlspecialchars($groupv['id']);?>&nbsp;</td>
						<td class="listr"><?=htmlspecialchars($groupv['desc']);?>&nbsp;</td>
						<?php if (UPDATENOTIFICATION_MODE_DIRTY != $notificationmode):?>
						<td valign="middle" nowrap class="list">
							<a href="access_users_groups_edit.php?id=<?=$i;?>"><img src="e.gif" title="<?=gettext("Edit group");?>" border="0"></a>&nbsp;
							<a href="access_users_groups.php?act=del&id=<?=$i;?>" onclick="return confirm('<?=gettext("Do you really want to delete this group?");?>')"><img src="x.gif" title="<?=gettext("Delete group");?>" border="0"></a>
						</td>
						<?php else:?>
						<td valign="middle" nowrap class="list">
							<img src="del.gif" border="0">
						</td>
						<?php endif;?>
					</tr>
					<?php $i++; endforeach;?>
					<?php foreach ($a_group as $groupk => $groupv):?>
					<?php if (false !== array_search_ex($groupv, $a_group_conf, "id")) continue; // Do not display user defined groups twice. ?>
					<tr>
						<td class="listlr"><?=$groupk;?>&nbsp;</td>
						<td class="listr"><?=htmlspecialchars($groupv);?>&nbsp;</td>
						<td class="listr"><?=gettext("System");?>&nbsp;</td>
					</tr>
					<?php endforeach;?>
					<tr>
						<td class="list" colspan="3"></td>
						<td class="list">
							<a href="access_users_groups_edit.php"><img src="plus.gif" title="<?=gettext("Add group");?>" border="0"></a>
						</td>
					</tr>
				</table>
			</form>
		</td>
	</tr>
</table>
<?php include("fend.inc");?>