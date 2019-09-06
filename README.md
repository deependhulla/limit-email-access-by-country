# limit-email-access-by-country
Limit IMAP4 , POP3, SMTP services access for users based on country. (To reduce hack attack)

## Challenges
Daily we have seen on Mail-server Login-attempt being made from unknown location for exsiting users and common-email ids.
Blocking all ips based on fail2ban software was turning out to be huge list and also there was Load on MYSQL as all attacker where trying to access 2 attempt from random new IPs.
IT-Team  was blocking access; many time for country specific IP-Range in iptables. which was also turning out to be problem. as list was growing bigger and not possible when you offer support for multiple-clients on muiltiple-server using open-source.
 
## Solution
We had beeing knowing about ip2location website/services, on finding they offer Lite version we thought of using it.
we where knowing about Nginx offering mail-proxy for load-balance and custom auth. we went with PHP as we thought it would be easier for developers  latter to upgrade code with more feature faster. (like admin panel and using sqlite/MySQL for web-based front-end or have city/regin option in future too)

## Ideal Setup for This project.
We started to wrote this project few month back with vision to latter open-source it 
once majour country block is ready for all 3 services and was tested with SSL load.
We wrote from angle to use in front of any existing mail-server offering imap/pop/smtp services, be  it same-host or different-vm/server.


## Quick Details for Understanding for implementation
- We have setup Postifx / Dovevot with Vmail Mailserver on Debian 9.x 64bit Linux using postfixadmin 
- For easy understanding have used dummy name  mail.deependhulla.com in conf files for reference. 
- We have installed Apache2 +LetsEncrypt SSL &  GroupOffice for WebBased access with two-factor Auth from any location.
- With only 110/143/25 Port configured only for localhost use only.
- We used our existing MX infra for filtering Mails and to deliver to this server.

### Now  we installed Nginx on Debian OS by stopping apache2 servies as nginx while installation try to use port 80.
### And latter disabled binding of port 80/443 services by removing file from site-enable.

,,,
service apache2 stop
apt-get install nginx-full
service nginx stop
rm /etc/nginx/sites-enabled/default
service apache2 start
,,,

## We added below mail section in nginx you can check in (rootdir/etc/nginx/nginx.conf)

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

## How comes the code which was under apache2/php (/var/www/html/mailauth/nginxmailauth.php) 

