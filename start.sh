#! /bin/bash

start_host=('localhost:5000')
cd message_service_api
./restart_app_server.sh ${start_host[@]}
