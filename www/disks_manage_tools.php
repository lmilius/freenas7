#!/usr/local/bin/php
<?php
/*
	disks_manage_tools.php
	Copyright � 2006 Volker Theile (votdev@gmx.de)
  All rights reserved.

	part of FreeNAS (http://www.freenas.org)
	Copyright (C) 2005-2007 Olivier Cochard-Labb� <olivier@freenas.org>.
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

$pgtitle = array(_DISKSPHP_NAME,_DISKSMANAGETOOLS_NAMEDESC);

if (!is_array($config['disks']['disk']))
	$config['disks']['disk'] = array();

disks_sort();

if (!is_array($config['gvinum']['vdisk']))
	$config['gvinum']['vdisk'] = array();

gvinum_sort();

if (!is_array($config['gmirror']['vdisk']))
	$config['gmirror']['vdisk'] = array();

gmirror_sort();

if (!is_array($config['gconcat']['vdisk']))
	$config['gconcat']['vdisk'] = array();

gconcat_sort();

if (!is_array($config['gstripe']['vdisk']))
	$config['gstripe']['vdisk'] = array();

gstripe_sort();

if (!is_array($config['graid5']['vdisk']))
	$config['graid5']['vdisk'] = array();

graid5_sort();

$a_disk = array_merge($config['disks']['disk'],$config['gvinum']['vdisk'],$config['gmirror']['vdisk'],$config['gconcat']['vdisk'],$config['gstripe']['vdisk'],$config['graid5']['vdisk']);


if ($_POST) {
	unset($input_errors);
	unset($do_action);

	/* input validation */
	$reqdfields = explode(" ", "disk action");
	$reqdfieldsn = explode(",", "Disk,Action");
	do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);

	if (!$input_errors)
	{
		$do_action = true;
		$disk = $_POST['disk'];
		$action = $_POST['action'];
		$partition = $_POST['partition'];
		$umount = $_POST['umount'];
	}
}

if (!isset($do_action))
{
	$do_action = false;
	$disk = '';
	$action = '';
	$partition = '';
	$umount = false;
}
?>
<?php include("fbegin.inc"); ?>
<script language="JavaScript">
<!--
function disk_change() {
  var next = null;
  // Remove all entries from partition combobox.
  document.iform.partition.length = 0;
  // Insert entries for partition combobox.
  switch(document.iform.disk.value)
  {
    <?php foreach ($a_disk as $diskv): ?>
	<?php if (strcmp($diskv['fstype'],"softraid")==0): ?> 	  
    		<?php continue; ?>
    	<?php endif; ?>
		case "<?=$diskv['fullname'];?>":
		  <?php $partinfo = disks_get_partition_info($diskv['fullname']);?>
      <?php foreach($partinfo as $partinfon => $partinfov): ?>
        if(document.all) // MS IE workaround.
          next = document.iform.partition.length;
        document.iform.partition.add(new Option("<?=$partinfon;?>","s<?=$partinfon;?>",false,<?php if("s{$partinfon}"==$partition){echo "true";}else{echo "false";};?>), next);
      <?php endforeach; ?>
      break;
    <?php endforeach; ?>
  }
}
// -->
</script>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
  <tr>
    <td class="tabnavtbl">
      <ul id="tabnav">
      	<li class="tabinact"><a href="disks_manage.php"><?=_DISKSPHP_MANAGE;?></a></li>
      	<li class="tabinact"><a href="disks_manage_init.php"><?=_DISKSPHP_FORMAT;?></a></li>
      	<li class="tabact"><?=_DISKSPHP_TOOLS;?></li>
      	<li class="tabinact"><a href="disks_manage_iscsi.php"><?=_DISKSPHP_ISCSIINIT;?></a></li>
      </ul>
    </td>
  </tr>
  <tr>
    <td class="tabcont">
      <?php if ($input_errors) print_input_errors($input_errors); ?>
			<form action="disks_manage_tools.php" method="post" name="iform" id="iform">
			  <table width="100%" border="0" cellpadding="6" cellspacing="0">
          <tr>
            <td valign="top" class="vncellreq"><?=_DISK;?></td>
            <td class="vtable">
              <select name="disk" class="formfld" id="disk" onchange="disk_change()">
                <?php foreach ($a_disk as $diskn): ?>
			<?php if (strcmp($diskn['fstype'],"softraid")==0): ?> 	  
    				<?php continue; ?>
    			<?php endif; ?>
                <option value="<?=$diskn['fullname'];?>"<?php if ($diskn['fullname'] == $disk) echo "selected";?>>
                <?php echo htmlspecialchars($diskn['name'] . ": " .$diskn['size'] . " (" . $diskn['desc'] . ")");?>
                <?php endforeach; ?>
                </option>
              </select>
            </td>
      		</tr>
      		<tr> 
            <td valign="top" class="vncellreq"><?=_PARTITION;?></td>
            <td class="vtable"> 
            <select name="partition" class="formfld" id="partition"></select>
            </td>
          </tr>
          <tr>
            <td valign="top" class="vncellreq"><?=_DISKSMANAGETOOLS_COMMAND;?></td>
            <td class="vtable"> 
              <select name="action" class="formfld" id="action">
                <option value="fsck" <?php if ($action == "fsck") echo "selected"; ?>>fsck</option>
               </select>
            </td>
          </tr>
          <tr> 
            <td width="22%" valign="top" class="vncell"></td>
            <td width="78%" class="vtable"> 
              <input name="umount" type="checkbox" id="umount" value="yes" <?php if ($umount) echo "checked"; ?>>
              <strong><?=_DISKSMANAGETOOLS_UMOUNT;?></strong><span class="vexpl"><br>
              <?=_DISKSMANAGETOOLS_UMOUNTTEXT;?></span>
            </td>
          </tr>
  				<tr>
  				  <td width="22%" valign="top">&nbsp;</td>
  				  <td width="78%">
              <input name="Submit" type="submit" class="formbtn" value="<?=_DISKSMANAGETOOLS_SENDCMD;?>">
  				  </td>
  				</tr>
  				<tr>
    				<td valign="top" colspan="2">
    				<?php if($do_action)
    				{
    				  echo("<strong>" . _DISKSMANAGETOOLS_CMDINFO . "</strong><br>");
    					echo('<pre>');
    					ob_end_flush();

              switch($action)
              {
                case "fsck":
                  /* Get the id of the disk. */
		              $id = array_search_ex($disk, $a_disk, "name");
		              /* Get the filesystem type of the disk. */ 
		              $type = $a_disk[$id]['fstype'];
                  /* Check if disk is mounted. */
                  $ismounted = disks_check_mount($disk,$partition);

                  /* Umount disk if necessary. */
		              if($umount && $ismounted) {
		                echo("<strong class='red'>" . _NOTE . ":</strong> " . _DISKSMANAGETOOLS_MOUNTNOTE . "<br><br>");
		                disks_umount_ex($disk,$partition);
                  }

                  switch($type)
        					{
                    case "":
          					case "ufs":
          					case "ufs_no_su":
          					case "ufsgpt":
          					case "ufsgpt_no_su":
                      system("/sbin/fsck_ufs -y -f " . escapeshellarg($disk . $partition));
          						break;
          					case "gmirror":
          					case "gvinum":
          					case "graid5":
                      $infomsg = sprintf(_DISKSMANAGETOOLS_RAIDDISKNOTE, "disks_raid_{$type}_tools.php");
                      print_info_box_np($infomsg);
          						break;
          					case "msdos":
                      system("/sbin/fsck_msdosfs -y -f " . escapeshellarg($disk . $partition));
          						break;
        					}

                  /* Mount disk if necessary. */
        					if($umount && $ismounted) {
		                disks_mount_ex($disk,$partition);
                  }

                  break;
              }
    					echo('</pre>');
    				}
    				?>
    				</td>
  				</tr>
			 </table>
    </form>
  </td></tr>
</table>
<script language="JavaScript">
<!--
disk_change();
//-->
</script>
<?php include("fend.inc"); ?>
