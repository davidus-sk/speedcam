#!/usr/bin/python3
import json
import time
import sys
import os
import syslog
import requests

# need to have input
if len(sys.argv) < 3:
	sys.exit()

# start syslog
syslog.openlog(logoption=syslog.LOG_PID)

# read config
config_file = "/app/speed/config.json"
config = {}
radar = sys.argv[1]
speed = sys.argv[2]
camera = None
direction = None

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

data = {"ts": time.time(), "radar":radar, "speed":speed, "direction":direction, "camera":camera}

# write to file
filename = "d_" + str(time.time()) + ".json"
syslog.syslog(syslog.LOG_ERR, f"Logging detection to file {filename}.")

with open("/dev/shm/" + filename, 'w', encoding='utf-8') as f:
	json.dump(data, f, ensure_ascii=False, indent=4)

# post detection to server
multipart_form_data = (
	('image1', ('custom_file_name.zip', open('myfile.zip', 'rb'))),
	('image2', ('custom_file_name.zip', open('myfile.zip', 'rb'))),
	('ts', (None, data["ts"])),
	('radar', (None, data["radar"])),
	('speed', (None, data["speed"])),
	('direction', (None, data["direction"])),
	('camera', (None, data["camera"])),
)

syslog.syslog(syslog.LOG_ERR, f"Logging detection to URL {config["settings"]["url"]}.")
response = requests.post(config["settings"]["url"], files=multipart_form_data)
