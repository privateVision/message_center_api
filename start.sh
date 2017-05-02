#! /bin/bash

#start_host=localhost:5000
start_ports=("5000" "5001" "5002" "5003" "5004" "5005" "5006" "5007" "5008" "5009" "5010" "5011" "5012" "5013" "5014")
cd message_service_api
#./restart_app_server.sh ${start_host}
./restart_app_server.sh "${start_ports[@]}"
