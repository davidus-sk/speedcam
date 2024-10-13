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
import glob
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
con = sqlite3.connect("/dev/shm/speed.db")
cur = con.cursor()
cur.execute("CREATE TABLE IF NOT EXISTS detections (time, month, day, hour, direction, camera, radar, speed)")

# create detection
data = {"ts": ts, "radar":radar, "speed":speed, "direction":direction, "camera":camera}
directory = f"/dev/shm/{camera}_{ts}"
os.mkdir(directory)

# store in DB as well
cur.execute(f'INSERT INTO detections VALUES ({ts}, {dt.month}, {dt.day}, {dt.hour}, "{direction}", {camera}, "{radar}", {speed})')
con.commit()
con.close()

# give time for the car to pass
time.sleep(10)

# find images
for name in glob.glob(f"/dev/shm/ffmpeg/{camera}_*"):
	mtime_seconds = os.path.getmtime(name)
	mtime_diff = mtime_seconds - ts

	if mtime_diff > 9 and mtime_diff <= 11:
		shutil.copy2(name, directory)

# post detection to server
"""
multipart_form_data = (
	('image1', ('custom_file_name.zip', open('myfile.zip', 'rb'))),
	('image2', ('custom_file_name.zip', open('myfile.zip', 'rb'))),
	('ts', (None, data["ts"])),
	('radar', (None, data["radar"])),
	('speed', (None, data["speed"])),
	('direction', (None, data["direction"])),
	('camera', (None, data["camera"])),
)

syslog.syslog(syslog.LOG_INFO, f'Logging detection to URL {config["settings"]["api"]["post_url"]} for radar {radar}.')
response = requests.post(config["settings"]["api"]["post_url"], files=multipart_form_data)
"""
