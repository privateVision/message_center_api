# _*_ coding: utf8 _*_

import smtplib
from email.mime.text import MIMEText


def send_mail(to_list, sub, content):
    mail_host = "smtp.163.com"
    mail_user = "yangchujie1@163.com"
    mail_pass = "ycj19910421"
    mail_postfix = "163.com"
    me = "<" + mail_user + ">"
    msg = MIMEText(content, _subtype='plain', _charset='utf8')
    msg['Subject'] = sub
    msg['From'] = me
    msg['To'] = ";".join(to_list)
    try:
        server = smtplib.SMTP()
        server.connect(mail_host)
        server.login(mail_user, mail_pass)
        server.sendmail(me, to_list, msg.as_string())
        server.close()
        return True
    except Exception, e:
        return False

def send_notify(content):
    mailto_list = ['14a1152bf3963d126735637d5e745ae5@mail.bearychat.com']
    send_mail(mailto_list, "sdk-api-message-service-exception", content)


if __name__ == '__main__':
    send_notify('测试异常通知')
