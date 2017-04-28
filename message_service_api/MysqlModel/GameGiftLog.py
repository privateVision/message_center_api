# _*_ coding: utf8 _*_
from sqlalchemy import Column
from sqlalchemy import Integer
from sqlalchemy import String
from sqlalchemy.ext.declarative import declarative_base

Base = declarative_base()


class GameGiftLog(Base):
    __tablename__ = 'cms_gameGiftLog'

    id = Column(Integer, primary_key=True)
    gameId = Column(Integer)
    giftId = Column(Integer)
    platformId = Column(Integer)
    code = Column(String)
    uid = Column(Integer)
    username = Column(String)
    forTime = Column(Integer)
    forIp = Column(String)
    forMac = Column(String)
    type = Column(Integer, default=0)
