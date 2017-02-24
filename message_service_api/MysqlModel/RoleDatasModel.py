# _*_ coding: utf-8 _*_
from run import db


class RoleDatas(db.Model):
    ucid = db.Column(db.Integer)
    vid = db.Column(db.Integer)
    zoneId = db.Column(db.String())
    zoneName = db.Column(db.String())

    def __repr__(self):
        return '<RoleDatas %s-%s-%s>' % (self.ucid, self.vid, self.zoneName)