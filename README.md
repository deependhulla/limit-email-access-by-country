# limit-email-access-by-country
Limit IMAP4 , POP3, SMTP services access for users based on country. (To reduce hack attack)


### nginx.conf update for configuring Nginx as mail-proxy for 
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
