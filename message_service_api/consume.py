# _*_ coding: utf-8 _*_
import json

from kafka import KafkaConsumer

if __name__ == '__main__':
    kafka_consumer = KafkaConsumer(bootstrap_servers='192.168.1.128:9092')
    kafka_consumer.subscribe(['message-service'])
    ts = kafka_consumer.topics()
    for t in ts:
        print t
    for msg in kafka_consumer:
        print json.dumps(msg)