<?php

# File: cron_agenda.php
# Description: Goes through the system directories and enumerates
#		all cron jobs on the system.  Gives you an agenda
#		for the day.
# Author: Brian Epstein
# Date: 2015-02-01
# Resources: I borrowed and tweaked the code from here:
#	http://stackoverflow.com/questions/321494/calculate-when-a-cron-job-will-be-executed-then-next-time/28242522

$cronsrc = array("/etc/cron.d","/var/spool/cron","/etc/crontab");

function list_crons($sources) {
# This should return an array of $frequency, $user, and script
# it expects an array ($sources) of directories and files to look into
	# $rc is our return array
	$rc = array();
	if (is_array($sources)) {
		foreach ($sources as $source) {
			if (is_dir($source)) {
				$handle=opendir($source);                    
				while (false !== ($entry = readdir($handle))) {
					if (is_file("$source/$entry")) {
						$lines = file("$source/$entry");
						foreach ($lines as $line) {
							$matches = preg_match_all("/^(([-0-9*\/]+\s+){3}(([-0-9*\/]+|jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec)\s+)(([-0-7*\/]|sun|mon|tue|wed|thu|fri|sat)))\s+([^\s]+)\s+([^\s].*)$/Ui",$line,$out,PREG_PATTERN_ORDER);
							if (false !== ($matches > 0)) {
								$frequency = $out[1][0];

								# replace any number of whitespaces with a single space
								$frequency=preg_replace('/\s\s*/', ' ', $frequency);

								if ($source == "/var/spool/cron") {
									# /var/spool/cron entries have no user, we get it from the filename
									$user = $entry;
									$script = $out[7][0] . " " . $out[8][0];
								} else {
									# all other cron entries include the user in the line
									$user = $out[7][0];
									$script = $out[8][0];
								}
								array_push($rc, array($frequency, $user, $script));
							}
						}
					}
				}
			} elseif (is_file($source)) {
				$lines = file("$source");
				foreach ($lines as $line) {
					$matches = preg_match_all("/^(([-0-9*\/]+\s+){3}(([-0-9*\/]+|jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec)\s+)(([-0-7*\/]|sun|mon|tue|wed|thu|fri|sat)))\s+([^\s]+)\s+([^\s].*)$/Ui",$line,$out,PREG_PATTERN_ORDER);
					if (false !== ($matches > 0)) {
						$frequency = $out[1][0];
						$user = $out[7][0];
						$script = $out[8][0];
						array_push($rc, array($frequency, $user, $script));
					}
				}
			}
		}
	} else {
		return false;
	}
	return($rc);
}

function parse_crontab($frequency='* * * * *', $time=false) {
	# Example of job definition:
	# .---------------- minute (0 - 59)
	# |  .------------- hour (0 - 23)
	# |  |  .---------- day of month (1 - 31)
	# |  |  |  .------- month (1 - 12) OR jan,feb,mar,apr ...
	# |  |  |  |  .---- day of week (0 - 6) (Sunday=0 or 7) OR sun,mon,tue,wed,thu,fri,sat
	# |  |  |  |  |
	# *  *  *  *  * user-name  command to be executed

	# replace month and day of week names with numbers
	$patterns = array('/jan/i','/feb/i','/mar/i','/apr/i','/may/i','/jun/i','/jul/i','/aug/i','/sep/i','/oct/i','/nov/i','/dec/i');
	$replace = array(1,2,3,4,5,6,7,8,9,10,11,12);
	$frequency = preg_replace($patterns,$replace,$frequency);
	$patterns = array('/sun/i','/mon/i','/tue/i','/wed/i','/thu/i','/fri/i','/sat/i');
	$replace = array(0,1,2,3,4,5,6);
	$frequency = preg_replace($patterns,$replace,$frequency);

	# make the time now, or the time input into the function
	$time = is_string($time) ? strtotime($time) : time();

	# pull out the values from our time
	$time = explode(' ', date('i G j n w', $time));

	# ensure that any 0-padded minutes are converted to integers
	$time[0] = $time[0] + 0;

	# pull out our job definition frequencies
	$crontab = explode(' ', $frequency);

	foreach ($crontab as $k => &$v) {
		$v = explode(',', $v);
		$regexps = array(
			'/^\*$/', # every 
			'/^\d+$/', # digit 
			'/^(\d+)\-(\d+)$/', # range
			'/^\*\/(\d+)$/' # every digit
		);
		$content = array(
			"true", # every
			"{$time[$k]} === $0", # digit
			"($1 <= {$time[$k]} && {$time[$k]} <= $2)", # range
			"{$time[$k]} % $1 === 0" # every digit
		);
		foreach ($v as &$v1)
			$v1 = preg_replace($regexps, $content, $v1);
			$v = '('.implode(' || ', $v).')';
	}
	$crontab = implode(' && ', $crontab);
	return eval("return {$crontab};");
}

# We don't really need to be root, but we need to add contingencies if we aren't
# For now, this is just a quick check.

if (posix_getuid() !== 0) {
	print "Sorry, you need to be root for now.\n";
	exit(1);
}

$crons = list_crons($cronsrc);
if (false !== $crons) {
	for($i=0; $i<24; $i++) {
		for($j=0; $j<60; $j++) {
			$date=sprintf("%d:%02d",$i,$j);
			foreach ($crons as $cron) {
				if (parse_crontab($cron[0],$date)) {
					if (preg_match_all("/run-parts\s+([^\s]+)/",$cron[2],$out)) {
						$source=$out[1][0];
						$numfiles = count(glob("$source/*",GLOB_BRACE));
						if ($numfiles > 0) {
							print "$date\t" . $cron[0] . "\t" . $cron[1] . "\t" . $cron[2] . "\n";
							if (is_dir($source)) {
								$handle=opendir($source);                    
								while (false !== ($entry = readdir($handle))) {
									if (is_file("$source/$entry")) {
										print "$date\t" . $cron[0] . "\t" . $cron[1] . "\t$source/$entry\n";
									}
								}
							}
						}
					} else {
						print "$date\t" . $cron[0] . "\t" . $cron[1] . "\t" . $cron[2] . "\n";
					}
				} else {
	#				print "$date no\n";
				}
			}
		}
	}
}

exit(0);

?>
