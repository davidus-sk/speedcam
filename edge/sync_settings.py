#!/usr/bin/python3

"""
Speed camera system - sync settings

Triggered by cron
Used to obtain the latest user settings from the cloud

(C) LUCEON LLC 2025
"""

# libraries
import json
import time
import sys
import os
import syslog
import requests
import sqlite3


# start syslog
syslog.openlog(logoption=syslog.LOG_PID)

# read config
config_file = "/app/speed/config.json"
config = {}

if not os.path.isfile(config_file) or os.path.getsize(config_file) <= 0:
	syslog.syslog(syslog.LOG_ERR, f"Config file {config_file} does not exist. Quitting...")
	sys.exit()

with open(config_file) as f:
	config = json.load(f)

# get config from the cloud
data = {"location":config["settings"]["location"]}
response = requests.get(config["settings"]["api"]["post_url"], params=data)
response_json = response.content.decode('UTF-8')
syslog.syslog(syslog.LOG_DEBUG, f'Response from URL: {response_json}.')
response.close()

response_data = json.loads(response_json)

if response_data["status"] == "OK":
	# update config
	config["settings"]["flashers"] = response_data["flashers"]
	config["left"]["speed_limit"] = response_data["speedlimit"]
	config["right"]["speed_limit"] = response_data["speedlimit"]

	with open(config_file, 'w') as f:
		json.dump(config, f, indent=4)