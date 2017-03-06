# _*_ coding: utf-8 _*_
from MiddleWare import create_app
import sys

reload(sys)
sys.setdefaultencoding('utf-8')

app, kafka_producer, kafka_consumer, mysql_session = create_app()


@app.errorhandler(404)
def page_not_found(error):
    return 'API Not Found', 404


@app.errorhandler(500)
def page_not_found(error):
    return 'Server Exception', 500

if __name__ == '__main__':
    host = app.config.get('HOST')
    port = app.config.get('PORT')
    debug = app.config.get('DEBUG')
    app.run(host=host, port=port, debug=debug)
