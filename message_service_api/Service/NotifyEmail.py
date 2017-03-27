# _*_ coding: utf8 _*_
import smtplib
from email.header import Header
from email.mime.multipart import MIMEMultipart
from email.mime.text import MIMEText


def send_mail(to, sub, content):
    mail_host = "smtp.163.com"
    mail_user = "yangchujie1@163.com"
    mail_pass = "ycj19910421"

    msg = MIMEMultipart()
    msg['Subject'] = Header(sub, 'utf-8')
    msg['From'] = 'yangchujie<yangchujie1@163.com>'
    msg['To'] = ";".join(to)

    message = MIMEText(content, 'plain', 'utf-8')

    s = smtplib.SMTP()
    s.connect(mail_host)
    s.login(mail_user, mail_pass)
    s.sendmail(mail_user, to, message.as_string())
    s.close()


def send_notify_email(time, service, content):
    mailto = ["14a1152bf3963d126735637d5e745ae5@mail.bearychat.com"]
    send_mail(mailto, service, content)


if __name__ == '__main__':
    send_notify_email(100210, '后台服务状态反馈邮件', 'sdk消息服务通知')