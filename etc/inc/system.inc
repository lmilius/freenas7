<?php
/*
	system.inc
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
require_once("functions.inc");
require_once("util.inc");
require_once("rc.inc");

function system_reboot() {
	// Initiate halt. Everything will be done automatically
	// in /etc/rc.shutdown by executing rc-init scripts in
	// reverse order (the 'KEYWORD: shutdown' must be defined).
	mwexec("/sbin/shutdown -r now");
}

function system_halt() {
	// Initiate halt. Everything will be done automatically
	// in /etc/rc.shutdown by executing rc-init scripts in
	// reverse order (the 'KEYWORD: shutdown' must be defined).
	mwexec("/sbin/shutdown -p now");
}

/* Init language environment */
function system_language_load()
{
	global $config, $g_languages;

	/* Get the language configured. */
	$language = $config['system']['language'];
	$domain = strtolower(get_product_name());
	$codeset = $g_languages[$language]['codeset'];

	putenv("LANG=$language");
	setlocale(LC_MESSAGES, $language);
	bindtextdomain($domain, "/usr/local/share/locale");
	bind_textdomain_codeset($domain, $codeset);
	textdomain($domain);
}

/* Get the codeset of the current configured language. */
/* Return: String containing codeset of current laguage. */
function system_get_language_codeset() {
	global $config, $g_languages;

	$language = $config['system']['language'];
	$codeset = $g_languages[$language]['codeset'];

	if (empty($codeset))
		$codeset = "ISO-8859-1"; // Set default codeset.

	return $codeset;
}

// Get list of available groups from /etc/group.
// Result:
//	Array (
//		[wheel] => 0
//		[sshd] => 22
//		[www] => 80
//		[nobody] => 65534
//		[admin] => 1000
//		...
//	)
function system_get_group_list() {
	// List of groups to filter from result list.
	$filterlist = array("_dhcp", "_pflogd");

	$grouplist = array();

	preg_match_all("/(\S+):\*:(\d+):.*\n/", @file_get_contents("/etc/group"), $matches, PREG_SET_ORDER);

	if (is_array($matches)) {
		foreach ($matches as $group) {
			if (false === in_array($group[1], $filterlist)) {
				$grouplist[$group[1]] = $group[2];
			}
		}

		ksort($grouplist);
	}

	return $grouplist;
}

// Get list of available users from /etc/master.passwd.
// Result:
//  Array (
//    [test] => Array ( [name] => test
//                      [password] => $1$yuQLaTPN$lkwYlZEB7B8n85flXPkHd0
//                      [uid] => 1001
//                      [gid] => 1001
//                      [class] => 
//                      [change] => 0 
//                      [expire] => 0 
//                      [gecos] => test 
//                      [home_dir] => /mnt 
//                      [shell] => /usr/local/bin/scponly
//                    )
//    [root] => ...
//    [toor] => ..
//    [daemon] => ...
//    [operator] => ...
//    ...
//  )
function system_get_user_list() {
	$userlist = array();

	foreach (explode("\n", @file_get_contents("/etc/master.passwd")) as $userinfov) {
		if (empty($userinfov))
			continue;

		// Extract user information
		$userinfo = explode(":", $userinfov);

		$user = array();
		$user['name'] = $userinfo[0];
		$user['password'] = $userinfo[1];
		$user['uid'] = $userinfo[2];
		$user['gid'] = $userinfo[3];
		$user['class'] = $userinfo[4];
		$user['change'] = $userinfo[5];
		$user['expire'] = $userinfo[6];
		$user['gecos'] = $userinfo[7];
		$user['home_dir'] = $userinfo[8];
		$user['shell'] = $userinfo[9];
		
		$userlist[$user['name']] = $user;
	}

	return $userlist;
}

// Get current CPU usage.
// Return current CPU usage in percent.
function system_get_cpu_usage() {
	$cpuTicks1 = explode(" ", `/sbin/sysctl -n kern.cp_time`);
	sleep(2);
	$cpuTicks2 = explode(" ", `/sbin/sysctl -n kern.cp_time`);
	
	$diff = array();
	$diff['user'] = ($cpuTicks2[0] - $cpuTicks1[0]);
	$diff['nice'] = ($cpuTicks2[1] - $cpuTicks1[1]);
	$diff['sys'] = ($cpuTicks2[2] - $cpuTicks1[2]);
	$diff['intr'] = ($cpuTicks2[3] - $cpuTicks1[3]);
	$diff['idle'] = ($cpuTicks2[4] - $cpuTicks1[4]);
	
	$totalDiff = $diff['user'] + $diff['nice'] + $diff['sys'] + $diff['intr'] + $diff['idle'];
	$totalused = $diff['user'] + $diff['nice'] + $diff['sys'] + $diff['intr'];
	
	if (isset($totalused) && $totalused <= 0) {
		$totalused = 0.001;
	}
	
	$cpuUsage = floor(100 * ($totalused / $totalDiff));
	
	return $cpuUsage;
}

// Get uptime (how long system is running).
// Return current uptime as string.
function system_get_uptime() {
	exec("/sbin/sysctl -n kern.boottime", $boottime);
	preg_match("/sec = (\d+)/", $boottime[0], $matches);
	$boottime = $matches[1];
	$uptime = time() - $boottime;

	if ($uptime > 60) $uptime += 30;
	$updays = (int)($uptime / 86400);
	$uptime %= 86400;
	$uphours = (int)($uptime / 3600);
	$uptime %= 3600;
	$upmins = (int)($uptime / 60);

	$uptime = "";
	if ($updays > 1) $uptime .= "$updays ".gettext("days").", ";
	else if ($updays > 0) $uptime .= "1 ".gettext("day").", ";
	$uptime .= sprintf("%02d:%02d", $uphours, $upmins);

	return $uptime;
}

// Get the current RAM information.
// Returns an array listing the amount of memory installed in the hardware.
function system_get_ram_info() {
	exec("/sbin/sysctl -n vm.stats.vm.v_active_count vm.stats.vm.v_inactive_count vm.stats.vm.v_wire_count vm.stats.vm.v_cache_count vm.stats.vm.v_free_count hw.physmem", $memory);
	exec("/sbin/sysctl -n hw.realmem", $hwmemory);

	$raminfo = array();
	$raminfo['real'] = $hwmemory[0];
	$raminfo['physical'] = $memory[5];
	$raminfo['total'] = $memory[0] + $memory[1] + $memory[2] + $memory[3] + $memory[4];
	$raminfo['free'] = $memory[4] + $memory[1];
	$raminfo['used'] = $raminfo['total'] - $raminfo['free'];

	return $raminfo;
}

// Get the current swap information.
// Result:
//  Array (
//    [device] => "/dev/ad0s2b"
//    [total] => "20M"
//    [used] => "0B"
//    [avail] => "20M"
//    [capacity] => "0%"
//  )
function system_get_swap_info() {
	exec("/usr/sbin/swapinfo", $swap);

	// blocks
	$val = 512;
	if (preg_match("/(?:Device)(?:\s+)(\d+)(?:k-blocks|-blocks)(?:\s+)(?:Used)(?:\s+)(?:Avail)(?:\s)(?:Capacity)/", $swap[0], $matches)) {
		if ($matches[1] == 1) {
			$val = $matches[1] * 1024;
		} else {
			$val = $matches[1];
		}
	}

	array_shift($swap);
	$swapinfo = array();

	foreach ($swap as $swapv) {
		if (preg_match("/(.*)(?:\s+)(\d+)(?:\s+)(\d+)(?:\s+)(\d+)(?:\s)(.+)/", $swapv, $matches)) {
			$devswap = array();
			$devswap['device']  = trim($matches[1]);
			$devswap['total']  = decode_size(trim($matches[2])*$val);
			$devswap['used'] = decode_size(trim($matches[3])*$val);
			$devswap['avail'] = decode_size(trim($matches[4])*$val);
			$devswap['capacity'] = trim($matches[5]);

			$swapinfo[] = $devswap;
		}
	}

	return $swapinfo;
}
?>