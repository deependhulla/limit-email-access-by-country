<?php

/*
Author : deepen@deependhulla.com/deepen@technoinfotech.com


*/


## ref : https://www.php.net/manual/en/function.imap-open.php
$gimapserver="{127.0.0.1:143/notls/norsh/novalidate-cert}";

## Default country code to allow access to all.
$dcc=array();

## example allow from India & US by default all users.
$dcc[0]='IN';
$dcc[1]='US';


## Write to syslog
openlog("NGINXMAILAUTH", LOG_PID | LOG_PERROR, LOG_LOCAL0);

require_once './ip2loc/IP2Location.php';
##   Cache the database into memory to accelerate lookup speed
##   WARNING: Please make sure your system have sufficient RAM to enable this feature
## $db = new \IP2Location\Database('./ip2loc/IP2LOCATION-LITE-DB1.BIN', \IP2Location\Database::MEMORY_CACHE);

##        Default file I/O lookup
$db = new \IP2Location\Database('./ip2loc/IP2LOCATION-LITE-DB1.BIN', \IP2Location\Database::FILE_IO);



$gloginuser=$_SERVER['HTTP_AUTH_USER'];
$gloginpass=$_SERVER['HTTP_AUTH_PASS'];
$glogintype=$_SERVER['HTTP_AUTH_METHOD'];
$gloginpro=$_SERVER['HTTP_AUTH_PROTOCOL'];
$gloginip=$_SERVER['HTTP_CLIENT_IP'];
$goforauth=0;
$loginok=0;
$filex=file_get_contents('user-allowed.php');
$filey=explode("\n",$filex);

$records = $db->lookup($gloginip, \IP2Location\Database::ALL);
$glogincc=$records['countryCode'];
$glogincn=$records['countryName'];
$ipdetaillog="".$gloginip."|".$glogincc."|".$glogincn."";

for($e=0;$e<sizeof($dcc);$e++)
{
if($dcc[$e]==$glogincc){$goforauth=1;}
}

for($e=0;$e<sizeof($filey);$e++)
{
$uchk=$filey[$e];
### remove unwanted charector & space forcefully before compare
$uchk=str_replace("\n","",$uchk);
$uchk=str_replace("\r","",$uchk);
$uchk=str_replace("\t","",$uchk);
$uchk=str_replace("\0","",$uchk);
$uchk=str_replace(" ","",$uchk);
$uchk=str_replace("","",$uchk);
$uchk=str_replace("","",$uchk);
$uchk=str_replace("","",$uchk);
if($uchk!=""){
$uall=array();$uall=explode("|",$uchk);
if($uall[0]!="" && $uall[1]!="" && $uall[0]==$gloginuser && $uall[1]==$glogincc )
{
$goforauth=1;
}
}
}

$gloginpass = str_replace('%20',' ', $gloginpass);
$gloginpass = str_replace('%25','%', $gloginpass);

if($goforauth==1){
if($mbox=imap_open($gimapserver, $gloginuser,$gloginpass)){$loginok=1;}
imap_close($mbox);
}



if($loginok==0)
{
if($goforauth==0){syslog(LOG_INFO, "$gloginuser : COUNTRYFAILED|$gloginpro $ipdetaillog ");}

###use this one, if password is not need to be logged during fail. ..depends on contry/company law.
//if($goforauth==1){syslog(LOG_INFO, "$gloginuser : AUTHFAILED|$gloginpro $ipdetaillog ");}

if($goforauth==1){syslog(LOG_INFO, "$gloginuser : AUTHFAILED|$gloginpro $ipdetaillog PASSATTACK:$gloginpass");}
closelog();
header('Auth-Status: Invalid Login or Password or Not Allowed : '.$ipdetaillog.' ');
}

if($loginok==1){
syslog(LOG_INFO, "$gloginuser : AUTHPASS|$gloginpro $ipdetaillog ");
closelog();
header('Auth-User: '.$gloginuser);
header('Auth-Pass: '.$gloginpass);
header('Auth-Status: OK');
header('Auth-Method: plain');
header('Auth-Server: 127.0.0.1');
if($gloginpro=="smtp"){header('Auth-Port: 25');}
if($gloginpro=="imap"){header('Auth-Port: 143');}
if($gloginpro=="pop3"){header('Auth-Port: 110');}
}

?>
