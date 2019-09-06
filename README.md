# limit-email-access-by-country
Limit IMAP4 , POP3, SMTP services access for users based on country. (To reduce hack attack)

## Challenges
aaaa
## Solution
bbb
## Ideal Setup for This project.
ccc
## Used Setup details.
ddd
## Quick Details for Understanding for implementation
- We have setup Postifx / Dovevot Mailserver on Debian 9.x 64bit Linux.
- We have installed Apache2 +LetsEncrypt SSL &  GroupOffice for WebBased access.
- With only 110/143/25 Port configured only for localhost use only.

### Now  we installed Nginx on Debian OS by stopping apache2 servies as nginx while installation try to use port 80.
### And latter to disable/binding port 80/443 Services by removing file from site-enable

,,,
service apache2 stop
apt-get install nginx-full
service nginx stop
rm /etc/nginx/sites-enabled/default
service apache2 start
,,,


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
