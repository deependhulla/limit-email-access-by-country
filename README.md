# limit-email-access-by-country
Limit IMAP4 , POP3, SMTP services access for users based on country. (To reduce hack attack)

## Challenges
Daily we have seen on Mail-server Login-attempt being made from the unknown location for existing users and common-email ids.
Blocking all IPs based on fail2ban software was turning out to be huge list and also there was Load on MYSQL as all attacker was trying to access 2 attempts from random new IPs.
IT-Team was blocking access; many time for country-specific IP-Range in iptables. which was also turning out to be a problem. as the list was growing bigger and not possible when you offer support for multiple-clients on multiple-server using open-source.

 
## Solution
We had being knowing about [ip2location](https://www.ip2location.com) website/services, on finding they offer [Lite version](https://lite.ip2location.com/database/ip-country) & [PHP development library](https://www.ip2location.com/development-libraries/ip2location/php), we thought of using it.
we were knowing about Nginx offering mail-proxy for load-balance and custom auth. we went with PHP as we thought it would be easier for developers latter to upgrade code with more feature faster. (like admin panel and using SQLite/MySQL for web-based front-end or have city/region option in future too)

## Idea
We started to write this project a few months back with the vision to latter open-source it, 
once a major country block is ready for all 3 services and was tested with SSL load.
We wrote from angle to use in front of any existing mail-server offering IMAP/POP/SMTP services, be it same-host or different-VM/server.


## Quick Details for Understanding for implementation
- We have set up Postfix / Dovecot with Vmail Mailserver on Debian 9.x 64bit Linux using PostfixAdmin.
- For easy understanding have used dummy name  mail.deependhulla.com in conf files for reference.
- We have installed Apache2 + LetsEncrypt SSL &  GroupOffice for web-based access with two-factor Auth from any location.
- With only 110/143/25 Port configured only for localhost use.
- We used our existing MX infra for filtering Mails and to deliver to this server.

### Now  we installed Nginx on Debian OS by stopping apache2 servies as nginx while installation try to use port 80.
### And latter disabled binding of port 80/443 services by removing file from site-enable for nginx.

```
service apache2 stop
apt-get install nginx-full
service nginx stop
rm /etc/nginx/sites-enabled/default
service apache2 start
```

### After basic installation of packages, We added below mail section in nginx you can check in [/etc/nginx/nginx.conf](../master/rootdir/etc/nginx/nginx.conf)

#### nginx.conf update for configuring Nginx as mail-proxy for 
- IMAP4-ssl port 993 
- POP3-ssl port 995 
- SMTP-ssl port 465
- SUBMISSION-TLS port 587 


```
 mail {
    ### Servername used over internet for this server for ssl and sl certificate is ready
    server_name mail.deependhulla.com;
    auth_http   localhost/mailauth/nginxmailauth.php;
    proxy_pass_error_message on;

   server {
        listen     465;
        protocol   smtp;
        smtp_auth  login plain ;
        proxy_pass_error_message on;
        proxy on;
        xclient off;
	ssl                 on;
    	ssl_certificate     /etc/letsencrypt/live/mail.deependhulla.com/fullchain.pem;
    	ssl_certificate_key /etc/letsencrypt/live/mail.deependhulla.com/privkey.pem;

    }

    server {
        listen    995;
        protocol  pop3;
        pop3_auth plain apop cram-md5;
	ssl                 on;
	ssl_certificate     /etc/letsencrypt/live/mail.deependhulla.com/fullchain.pem;
	ssl_certificate_key /etc/letsencrypt/live/mail.deependhulla.com/privkey.pem;

	}

     server {
        listen   993;
        protocol imap;
	ssl                 on;
	ssl_certificate     /etc/letsencrypt/live/mail.deependhulla.com/fullchain.pem;
	ssl_certificate_key /etc/letsencrypt/live/mail.deependhulla.com/privkey.pem;

	    }

      server {
	starttls on;
	ssl_certificate     /etc/letsencrypt/live/mail.deependhulla.com/fullchain.pem;
	ssl_certificate_key /etc/letsencrypt/live/mail.deependhulla.com/privkey.pem;
        listen     587;
        protocol   smtp;
        smtp_auth  login plain ;
        proxy_pass_error_message on;
        proxy on;
        xclient off;
	 }

}
```

### Now comes the main code which is under apache2/php: [/var/www/html/mailauth/nginxmailauth.php](../master/rootdir/var/www/html/mailauth/nginxmailauth.php)

```php
<?php

## ref : https://www.php.net/manual/en/function.imap-open.php
$gimapserver="{127.0.0.1:143/notls/norsh/novalidate-cert}";

## Default country code to allow access to all.
$dcc=array();

## example allow from India & US by default all users.
$dcc[0]='IN';
$dcc[1]='US';

## Write to syslog
openlog("NGINXMAILAUTH", LOG_PID | LOG_PERROR, LOG_LOCAL0);

## Load IP2Location php library & IP-Contry Database
require_once './ip2loc/IP2Location.php';
##   Cache the database into memory to accelerate lookup speed
##   WARNING: Please make sure your system have sufficient RAM to enable this feature
## $db = new \IP2Location\Database('./ip2loc/IP2LOCATION-LITE-DB1.BIN', \IP2Location\Database::MEMORY_CACHE);
##        Default file I/O lookup
$db = new \IP2Location\Database('./ip2loc/IP2LOCATION-LITE-DB1.BIN', \IP2Location\Database::FILE_IO);

## Get basic variable ready.
$gloginuser=$_SERVER['HTTP_AUTH_USER'];
$gloginpass=$_SERVER['HTTP_AUTH_PASS'];
$glogintype=$_SERVER['HTTP_AUTH_METHOD'];
$gloginpro=$_SERVER['HTTP_AUTH_PROTOCOL'];
$gloginip=$_SERVER['HTTP_CLIENT_IP'];
## keep auth & login part to false (0)
$goforauth=0;
$loginok=0;
## read the file for extra allow per user Contry Code or IP
$filex=file_get_contents('user-allowed.php');
$filey=explode("\n",$filex);

## Get Country Code of IP
$records = $db->lookup($gloginip, \IP2Location\Database::ALL);
$glogincc=$records['countryCode'];
$glogincn=$records['countryName'];
$ipdetaillog="".$gloginip."|".$glogincc."|".$glogincn."";

## Check if country code is allowed in our default
for($e=0;$e<sizeof($dcc);$e++)
{
if($dcc[$e]==$glogincc){$goforauth=1;}
}

for($e=0;$e<sizeof($filey);$e++)
{
$uchk=$filey[$e];
### remove unwanted charector & space forcefully before compare
$uchk=str_replace("\n","",$uchk);$uchk=str_replace("\r","",$uchk);
$uchk=str_replace("\t","",$uchk);$uchk=str_replace("\0","",$uchk);
$uchk=str_replace(" ","",$uchk);$uchk=str_replace("","",$uchk);
$uchk=str_replace("","",$uchk);$uchk=str_replace("","",$uchk);

if($uchk!=""){
$uall=array();$uall=explode("|",$uchk);
### allow if country code match for whitelist/extra llow
if($uall[0]!="" && $uall[1]!="" && $uall[0]==$gloginuser && $uall[1]==$glogincc )
{
$goforauth=1;
}
### allow if IP code match for whitelist/extra llow
if($uall[0]!="" && $uall[1]!="" && $uall[0]==$gloginuser && $uall[1]==$gloginip )
{
$goforauth=1;
}

}
}

## fixes few URL decoding.
$gloginpass = str_replace('%20',' ', $gloginpass);
$gloginpass = str_replace('%25','%', $gloginpass);

## check for IMAP auth for password is OK or not
if($goforauth==1){
if($mbox=imap_open($gimapserver, $gloginuser,$gloginpass)){$loginok=1;}
imap_close($mbox);
}

## If login failed check for two option.
if($loginok==0)
{
## syslog it : failed if country not matched 
if($goforauth==0){syslog(LOG_INFO, "$gloginuser : COUNTRYFAILED|$gloginpro $ipdetaillog ");}

## syslog it : failed if country matched but password not correct , also log password to study if its randow , or dic attack. 
if($goforauth==1){syslog(LOG_INFO, "$gloginuser : AUTHFAILED|$gloginpro $ipdetaillog PASSATTACK:$gloginpass");}

## use this one, if password is not need to be logged during fail. ..depends on contry/company law.
//if($goforauth==1){syslog(LOG_INFO, "$gloginuser : AUTHFAILED|$gloginpro $ipdetaillog ");}

closelog();
## pass the error msg to email-client
header('Auth-Status: Invalid Login or Password or Not Allowed : '.$ipdetaillog.' ');
}

if($loginok==1){
## syslog it ..for success.
syslog(LOG_INFO, "$gloginuser : AUTHPASS|$gloginpro $ipdetaillog ");
closelog();
## pass the email-client conntection on success.
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
```

### This site or product includes IP2Location LITE data available from http://www.ip2location.com.

### This Code is working model and used in production by us.

