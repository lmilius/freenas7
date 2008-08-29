<?php
/*
	report.inc
	Copyright (c) 2007-2008 Volker Theile (votdev@gmx.de)
	All rights reserved.

	part of FreeNAS (http://www.freenas.org)
	Copyright (C) 2005-2008 Olivier Cochard-Labbé <olivier@freenas.org>.
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
require_once("config.inc");
require_once("util.inc");
require_once("disks.inc");
require_once("email.inc");

class StatusReportArticle
{
	var $_title = "";
	var $_text = "";

	function StatusReportArticle($title,$text) {
		$this->_title = $title;
		$this->_text = $text;
	}

	function GetTitle() {
		return $this->_title;
	}

	function GetText() {
		return $this->_text;
	}
}

class StatusReportArticleCmd extends StatusReportArticle
{
	function StatusReportArticleCmd($title,$command) {
		$this->_title = $title;
		$this->_text = "";

		if (!empty($command)) {
			exec($command . " 2>&1", $execOutput, $execStatus);
			for ($i = 0; isset($execOutput[$i]); $i++) {
				if ($i > 0) $this->_text .= "\n";
				$this->_text .= $execOutput[$i]; // Print command output.
			}
			$this->_text .= "\n";
		}
	}
}

class StatusReportRenderer
{
	function Generate($articles) {
		$report = "";
		$this->_RenderReport($report,$articles);
		return $report;
	}

	function _RenderReport(&$report,$articles) {
		for ($i = 0; is_object($articles[$i]); $i++) {
			$this->_RenderArticle($report,$articles[$i]);
		}
	}

	function _RenderArticle(&$report,$article) {
		// To be implemented by derived class.
	}
}

class StatusReportRendererText extends StatusReportRenderer
{
	function _RenderArticle(&$report,$article) {
		// Render article title.
		$title = $article->GetTitle();
		if (!empty($title)) {
			$nWidth = strlen($title) + 1;
			$report .= sprintf("%s:\n%'-{$nWidth}s\n",$title,""); // Print title and underline.
		}
		// Render article text.
		$report .= $article->GetText();
		$report .= "\n";
	}
}

class StatusReportRendererHTML extends StatusReportRenderer
{
	function _RenderReport(&$report,$articles) {
		exec("/bin/date", $dateOutput, $dateStatus);
		$currentDate = $dateOutput[0];

		$report = <<<EOD
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<title>Status Report</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<style type="text/css">
<!--
body, td, th {
	font-family: Tahoma, Verdana, Arial, Helvetica, sans-serif;
	font-size: 11px;
}
a {
	text-decoration: none;
}
.title {
	font-size: 18px;
	color: #777777;
	font-weight: bold;
}
.listtopic {
	border-right: 1px solid #999999;
	font-size: 11px;
	background-color: #435370;
	padding-right: 16px;
	padding-left: 6px;
	color: #FFFFFF;
	font-weight: bold;
	padding-top: 5px;
	padding-bottom: 5px;
}
.listlr {
	background-color: #FFFFFF;
	border-right: 1px solid #999999;
	border-bottom: 1px solid #999999;
	border-left: 1px solid #999999;
	font-size: 11px;
	padding-top: 4px;
	padding-bottom: 4px;
	padding-right: 6px;
	padding-left: 6px;
	text-align: left;
}
pre {
	margin: 0px;
	font-family: courier new, courier;
	font-weight: normal;
	font-size: 9pt;
}
table {
	width: 800px;
}
-->
</style>
</head>
<body link="#0000CC" vlink="#0000CC" alink="#0000CC">
<span class="title">Status Report</span><br>
<strong>{$currentDate}</strong><br>

EOD;

		// Render table of contents.
		$this->_RenderTableOfContents($report, $articles);

		// Render articles.
		for ($i = 0; is_object($articles[$i]); $i++) {
			$this->_RenderArticle($report,$articles[$i]);
		}

		$report .= <<<EOD
</body>
</html>

EOD;
	}

	function _RenderTableOfContents(&$report,$articles) {
		$report .= "<br>";
		$report .= "This status page includes the following information:\n";
		$report .= "<ul>\n";
		for ($i = 0; is_object($articles[$i]); $i++) {
			$report .= "<li><strong><a href=\"#" . $articles[$i]->GetTitle() . "\">" . $articles[$i]->GetTitle() . "</a></strong>\n";
		}
    $report .= "</ul>\n";
	}

	function _RenderArticle(&$report,$article) {
    $report .= "<a name=\"" . $article->GetTitle() . "\">\n";
    $report .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\">\n";
    $report .= "<tr><td class=\"listtopic\">" . $article->GetTitle() . "</td></tr>\n";
    $report .= "<tr><td class=\"listlr\"><pre>";
		$report .= htmlspecialchars($article->GetText(),ENT_NOQUOTES);
		$report .= "</pre></tr>\n";
    $report .= "</table>\n";
    $report .= "<br>\n";
	}
}

class StatusReport
{
	/**
	* Contains commands using to generate report.
	* @var array
	*/
	var $articles = array();

	/**
	* Sets the Content-type of the message.
	* @var bollean
	*/
  var $isHTML = false;

	/**
	* Sets message type to HTML.
	* @param bool $bool
	* @return void
	*/
	function IsHTML($bool) {
		$this->isHTML = $bool;
	}

	/**
	* Add article.
	* @return void
	*/
	function AddArticle($articles) {
		$this->articles[] = $articles;
	}

	/**
	* Generate report.
	* @return string
	*/
	function Generate() {
		$report = "";
		if (true === $this->isHTML) {
			$renderer = new StatusReportRendererHTML;
			$report = $renderer->Generate($this->articles);
		} else {
			$renderer = new StatusReportRendererText;
			$report = $renderer->Generate($this->articles);
		}

		return $report;
	}
}

function report_send_mail() {
	global $config;

	$statusreport = new StatusReport();

	if (is_array($config['statusreport']['report']) && in_array("systeminfo", $config['statusreport']['report'])) {
		$statusreport->AddArticle(new StatusReportArticleCmd("Version","cat /etc/prd.version"));
		$statusreport->AddArticle(new StatusReportArticleCmd("Revision","cat /etc/prd.revision"));
		$statusreport->AddArticle(new StatusReportArticleCmd("Platform","cat /etc/platform"));
		$statusreport->AddArticle(new StatusReportArticleCmd("System uptime","uptime"));
		$statusreport->AddArticle(new StatusReportArticleCmd("Interfaces","/sbin/ifconfig -a"));
		$statusreport->AddArticle(new StatusReportArticleCmd("Routing tables","netstat -nr"));
		$statusreport->AddArticle(new StatusReportArticleCmd("Processes","ps xauww"));
		$statusreport->AddArticle(new StatusReportArticleCmd("Memory","top -b 0 | grep Mem"));
		$statusreport->AddArticle(new StatusReportArticleCmd("Swap usage","/usr/sbin/swapinfo"));
		$statusreport->AddArticle(new StatusReportArticleCmd("ATA disk","/sbin/atacontrol list"));
		$statusreport->AddArticle(new StatusReportArticleCmd("SCSI disk","/sbin/camcontrol devlist"));
		$disklist = get_physical_disks_list();
		foreach ($disklist as $disknamek => $disknamev) {
			$statusreport->AddArticle(new StatusReportArticleCmd("S.M.A.R.T. [/dev/{$disknamek}]","/usr/local/sbin/smartctl -a /dev/{$disknamek}"));
		}
		$statusreport->AddArticle(new StatusReportArticleCmd("Geom Concat","/sbin/gconcat list"));
		$statusreport->AddArticle(new StatusReportArticleCmd("Geom Stripe","/sbin/gstripe list"));
		$statusreport->AddArticle(new StatusReportArticleCmd("Geom Mirror","/sbin/gmirror list"));
		$statusreport->AddArticle(new StatusReportArticleCmd("Geom RAID5","/sbin/graid5 list"));
		$statusreport->AddArticle(new StatusReportArticleCmd("Geom Vinum","/sbin/gvinum list"));
		$statusreport->AddArticle(new StatusReportArticleCmd("Mount point","/sbin/mount"));
		$statusreport->AddArticle(new StatusReportArticleCmd("Free disk space","/bin/df -h"));
		$statusreport->AddArticle(new StatusReportArticleCmd("Encrypted disks","/sbin/geli list"));
	}

	if (is_array($config['statusreport']['report']) && in_array("dmesg", $config['statusreport']['report'])) {
		$statusreport->AddArticle(new StatusReportArticleCmd("dmesg","/sbin/dmesg"));
	}

	$numentries = $config['syslogd']['nentries'];
	if (!$numentries) { $numentries = 50; } // Use default value if setting is not available.
	$reverse = isset($config['syslogd']['reverse']) ? "-r" : "";

	if (is_array($config['statusreport']['report']) && in_array("systemlog", $config['statusreport']['report'])) {
		$statusreport->AddArticle(new StatusReportArticleCmd("Last {$numentries} System log entries","/usr/sbin/clog /var/log/system.log 2>&1 | tail {$reverse} -n {$numentries}"));
	}
	if (is_array($config['statusreport']['report']) && in_array("ftplog", $config['statusreport']['report'])) {
		$statusreport->AddArticle(new StatusReportArticleCmd("Last {$numentries} FTP log entries","/usr/sbin/clog /var/log/ftp.log 2>&1 | tail {$reverse} -n {$numentries}"));
	}
	if (is_array($config['statusreport']['report']) && in_array("rsynclog", $config['statusreport']['report'])) {
		$statusreport->AddArticle(new StatusReportArticleCmd("Last {$numentries} RSYNCD log entries","/usr/sbin/clog /var/log/rsyncd.log 2>&1 | tail {$reverse} -n {$numentries}"));
	}
	if (is_array($config['statusreport']['report']) && in_array("sshdlog", $config['statusreport']['report'])) {
		$statusreport->AddArticle(new StatusReportArticleCmd("Last {$numentries} SSHD log entries","/usr/sbin/clog /var/log/sshd.log 2>&1 | tail {$reverse} -n {$numentries}"));
	}
	if (is_array($config['statusreport']['report']) && in_array("smartdlog", $config['statusreport']['report'])) {
		$statusreport->AddArticle(new StatusReportArticleCmd("Last {$numentries} SMARTD log entries","/usr/sbin/clog /var/log/smartd.log 2>&1 | tail {$reverse} -n {$numentries}"));
	}
	if (is_array($config['statusreport']['report']) && in_array("daemonlog", $config['statusreport']['report'])) {
		$statusreport->AddArticle(new StatusReportArticleCmd("Last {$numentries} Daemon log entries","/usr/sbin/clog /var/log/daemon.log 2>&1 | tail {$reverse} -n {$numentries}"));
	}

	// Send email and log result.
	$result = email_send($config['statusreport']['to'], $config['statusreport']['subject'], $statusreport->Generate(), $error);
	if (0 != $result) {
		write_log($error);
		return 1;
	}

	write_log("Status report successfully sent to {$config['statusreport']['to']}.");
	return 0;
}
?>