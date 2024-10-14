#!/app/inference/bin/python3

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
from inference import get_model
from PIL import Image

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
model = get_model(model_id="license-plate-recognition-rxg4e/4")

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
best_image = None
best_score = 0
best_box = ()
found_plate = False
results = []

for name in glob.glob(f"/dev/shm/ffmpeg/{camera}_{ts}*"):
	if not found_plate or results["predictions"]:
		results = json.loads(model.infer(name)[0].json())

		for prediction in results["predictions"]:
			found_plate = True
			confidence = float(prediction["confidence"])
			x1 = int(prediction["x"]) - int(prediction["width"]) / 2
			y1 = int(prediction["y"]) - int(prediction["height"]) / 2
			x2 = int(prediction["x"]) + int(prediction["width"]) / 2
			y2 = int(prediction["y"]) + int(prediction["height"]) / 2

			if confidence > best_score:
				syslog.syslog(syslog.LOG_INFO, f'Found plate with confidence {confidence}.')
				best_score = confidence
				best_image = os.path.basename(name)
				best_box = (x1, y1, x2, y2)
				shutil.copy2(name, directory)

	# delete original file
	os.remove(name)

if found_plate:
	image = Image.open(f"{directory}/{best_image}")
	cropped_image = image.crop(best_box)
	cropped_image.save(f"{directory}/plate.jpg")

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
