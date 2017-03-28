# _*_ coding: utf8 _*_

import smtplib
from email.mime.text import MIMEText


def send_mail(to_list, sub, content):
    mail_host = "smtp.163.com"
    mail_user = "yangchujie1@163.com"
    mail_pass = "ycj19910421"
    mail_postfix = "163.com"
    me = "hello" + "<" + mail_user + "@" + mail_postfix + ">"
    msg = MIMEText(content, _subtype='plain')
    msg['Subject'] = sub
    msg['From'] = me
    msg['To'] = ";".join(to_list)  # 将收件人列表以‘；’分隔
    try:
        server = smtplib.SMTP()
        server.connect(mail_host)  # 连接服务器
        server.login(mail_user, mail_pass)  # 登录操作
        server.sendmail(me, to_list, msg.as_string())
        server.close()
        return True
    except Exception, e:
        print str(e)
        return False


if __name__ == '__main__':
    mailto_list = ['14a1152bf3963d126735637d5e745ae5@mail.bearychat.com']
    if send_mail(mailto_list, "消息服务反馈", "消息服务出现一场悉尼下"):
        print "done!"
    else:
        print "failed!"
