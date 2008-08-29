#!/usr/local/bin/php
<?php
/*
	services_iscsitarget_extent_edit.php
	Copyright © 2007-2008 Volker Theile (votdev@gmx.de)
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

$id = $_GET['id'];
if (isset($_POST['id']))
	$id = $_POST['id'];

$pgtitle = array(gettext("Services"),gettext("iSCSI Target"),gettext("Extent"),isset($id)?gettext("Edit"):gettext("Add"));

if (!is_array($config['iscsitarget']['extent']))
	$config['iscsitarget']['extent'] = array();

array_sort_key($config['iscsitarget']['extent'], "name");

$a_iscsitarget_extent = &$config['iscsitarget']['extent'];

if (isset($id) && $a_iscsitarget_extent[$id]) {
	$pconfig['name'] = $a_iscsitarget_extent[$id]['name'];
	$pconfig['path'] = $a_iscsitarget_extent[$id]['path'];
	$pconfig['size'] = $a_iscsitarget_extent[$id]['size'];

	// Check if a device is used as target.
	$pconfig['type'] = "device";
	if (!preg_match("/^\/dev\/(\S+)/", $pconfig['path']))
		$pconfig['type'] = "file";
} else {
	// Find next unused ID.
	$extentid = 0;
	$a_id = array();
	foreach($a_iscsitarget_extent as $extent)
		$a_id[] = (int)str_replace("extent", "", $extent['name']); // Extract ID.
	while (true === in_array($extentid, $a_id))
		$extentid += 1;

	$pconfig['name'] = "extent{$extentid}";
	$pconfig['path'] = "";
	$pconfig['size'] = "";
	$pconfig['type'] = "device";
}

if ($_POST) {
	unset($input_errors);
	unset($errormsg);

	$pconfig = $_POST;

	// Input validation.
	if ("device" === $_POST['type']) {
		$reqdfields = explode(" ", "device");
		$reqdfieldsn = array(gettext("Device"));
		$reqdfieldst = explode(" ", "string");
	} else {
		$reqdfields = explode(" ", "path size");
		$reqdfieldsn = array(gettext("Path"),gettext("File size"));
		$reqdfieldst = explode(" ", "string numeric");
	}

	do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);
	do_input_validation_type($_POST, $reqdfields, $reqdfieldsn, $reqdfieldst, &$input_errors);

	if (!$input_errors) {
		$iscsitarget_extent = array();
		$iscsitarget_extent['name'] = $_POST['name'];
		if ("device" === $_POST['type']) {
			$diskinfo = disks_get_diskinfo($_POST['device']);
			$iscsitarget_extent['path'] = $diskinfo['name'];
			$iscsitarget_extent['size'] = $diskinfo['mediasize_mbytes'] - 1; // Calculate target size.
		} else {
			$iscsitarget_extent['path'] = $_POST['path'];
			$iscsitarget_extent['size'] = $_POST['size'];
		}

		if (isset($id) && $a_iscsitarget_extent[$id])
			$a_iscsitarget_extent[$id] = $iscsitarget_extent;
		else
			$a_iscsitarget_extent[] = $iscsitarget_extent;

		touch($d_iscsitargetdirty_path);

		write_config();

		header("Location: services_iscsitarget.php");
		exit;
	}
}
?>
<?php include("fbegin.inc");?>
<script language="JavaScript">
<!--
function type_change() {
	switch (document.iform.type.value) {
	case "file":
		showElementById('path_tr','show');
		showElementById('size_tr','show');
		showElementById('device_tr','hide');
		break;
	
	case "device":
		showElementById('path_tr','hide');
		showElementById('size_tr','hide');
		showElementById('device_tr','show');
		break;
	}
}
// -->
</script>
<form action="services_iscsitarget_extent_edit.php" method="post" name="iform" id="iform">
	<table width="100%" border="0" cellpadding="0" cellspacing="0">
		<tr>
			<td class="tabcont">
				<?php if ($input_errors) print_input_errors($input_errors);?>
				<table width="100%" border="0" cellpadding="6" cellspacing="0">
					<?php html_inputbox("name", gettext("Extent name"), $pconfig['name'], "", true, 10, isset($id));?>
					<?php html_combobox("type", gettext("Type"), $pconfig['type'], array("file" => gettext("File"), "device" => gettext("Device")), "", true, false, "type_change()");?>
					<?php html_filechooser("path", "Path", $pconfig['path'], sprintf(gettext("File path (e.g. /mnt/sharename/extent/%s) or device name (e.g. /dev/ad1) used as extent."), $pconfig['name']), "/mnt", true);?>
					<?php $a_device = array(); $a_device[''] = gettext("Must choose one"); foreach (get_conf_all_disks_list_filtered() as $diskv) { if (0 == strcmp($diskv['size'], "NA")) continue; if (1 == disks_exists($diskv['devicespecialfile'])) continue; $diskinfo = disks_get_diskinfo($diskv['devicespecialfile']); $a_device[$diskv['devicespecialfile']] = htmlspecialchars("{$diskv['name']}: {$diskinfo['mediasize_mbytes']}MB ({$diskv['desc']})"); }?>
					<?php html_combobox("device", gettext("Device"), $pconfig['path'], $a_device, "", true);?>
					<?php html_inputbox("size", gettext("File size"), $pconfig['size'], gettext("Size in MB."), true, 10);?>
					<tr>
						<td width="22%" valign="top">&nbsp;</td>
						<td width="78%"><input name="Submit" type="submit" class="formbtn" value="<?=((isset($id) && $a_iscsitarget_extent[$id]))?gettext("Save"):gettext("Add")?>">
						<?php if (isset($id) && $a_iscsitarget_extent[$id]):?>
							<input name="id" type="hidden" value="<?=$id;?>">
						<?php endif;?>
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
</form>
<script language="JavaScript">
<!--
type_change(false);
//-->
</script>
<?php include("fend.inc");?>