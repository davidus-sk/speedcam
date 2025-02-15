#!/usr/bin/python3

"""
Speed camera system - detection creation

Triggered by high speed detection via read_radar.py
Pools data together, finds images, sends to upstream server

(C) LUCEON LLC 2024
"""

# libraries
import json
import time
import sys
import os
import syslog
import requests
import re
import sqlite3
import datetime
import shutil

# need to have input
if len(sys.argv) < 4:
	sys.exit()

# start syslog
syslog.openlog(logoption=syslog.LOG_PID)

# global vars
radar = sys.argv[1]
speed = float(sys.argv[2])
ts = float(sys.argv[3])
camera = None
direction = None
dt = datetime.datetime.fromtimestamp(ts)
total, used, free = shutil.disk_usage("/data")

# read config
config_file = "/app/speed/config.json"
config = {}

if not os.path.isfile(config_file) or os.path.getsize(config_file) <= 0:
	syslog.syslog(syslog.LOG_ERR, f"Config file {config_file} does not exist. Quitting...")
	sys.exit()

with open(config_file) as f:
	config = json.load(f)

# find this radar in config
for v in config:
	if "radar" in config[v] and config[v]["radar"] == radar:
		camera = config[v]["camera"]
		direction = v

# connecto to sqlite and create DB if it does not exist
con = sqlite3.connect("/data/speed.db")
cur = con.cursor()
cur.execute("CREATE TABLE IF NOT EXISTS detections (time TEXT, month INTEGER, day INTEGER, hour INTEGER, direction, camera INTEGER, radar, speed REAL, processed INTEGER DEFAULT 0, uploaded INTEGER DEFAULT 0)")
con.commit()

time.sleep(10)

# store in DB as well
syslog.syslog(syslog.LOG_INFO, f"Creating new detection {ts} for camera {camera}")
cur.execute(f'INSERT INTO detections VALUES ("{ts}", {dt.month}, {dt.day}, {dt.hour}, "{direction}", {camera}, "{radar}", {speed}, 0, 0)')
con.commit()
con.close()

# data to upload
data = {"ts":ts, "radar":radar, "speed":speed, "direction":direction, "location":config["settings"]["location"], "camera":camera, "storage":int((free/total)*100)}

syslog.syslog(syslog.LOG_INFO, f'Logging detection to URL {config["settings"]["api"]["post_url"]} for radar {radar}.')

for x in [1,2,3,4,5]:
	response = requests.post(config["settings"]["api"]["post_url"], data=data)
	response_json = response.content.decode('UTF-8')
	syslog.syslog(syslog.LOG_DEBUG, f'Response from URL: {response_json}.')
	response.close()

	response_data = json.loads(response_json)

	if response_data["status"] == "OK":
		# update config
		config["settings"]["flashers"] = response_data["flashers"]
		config[direction]["speed_limit"] = response_data["speedlimit"]

		with open(config_file, 'w') as f:
			json.dump(config, f, indent=4)

		break
