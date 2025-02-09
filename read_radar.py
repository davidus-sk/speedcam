#!/usr/bin/python3

"""
Speed camera - Obtain and process speed

Script gets data from serial radars, checks if speed is above predetermined threshold,
turns on flashers, and creates a detection via a callback

(C) 2024 LUCEON LLC
"""

# import librarie
import sys
import serial
import syslog
import time
import json
import os
import fcntl
import datetime
import hashlib
from pathlib import Path

# only run once
lock_file_pointer = os.open(f"/tmp/read_radar_{sys.argv[2]}.pid", os.O_WRONLY | os.O_CREAT)

try:
	fcntl.lockf(lock_file_pointer, fcntl.LOCK_EX | fcntl.LOCK_NB)
except IOError:
	sys.exit()

# need to have serial port and radar id
# arg 1 - serial port to connect to
# arg 2 - serial ID of the radar to work with
if len(sys.argv) < 3:
	sys.exit()

# global variables
tty = sys.argv[1]
radar = sys.argv[2]
camera = None
direction = None
ts_detection = time.time()
config_hash = ""
script_dir = os.path.dirname(os.path.realpath(__file__))

# start syslog
syslog.openlog(logoption=syslog.LOG_PID)

# make sure flashers are off
os.system(f"{script_dir}/flashers_off > /dev/null 2>&1 &")

# read config
config_file = f"{script_dir}/config.json"
config = {}

if not os.path.isfile(config_file) or os.path.getsize(config_file) <= 0:
	syslog.syslog(syslog.LOG_ERR, f"Config file {config_file} does not exist. Quitting...")
	sys.exit()

with open(config_file) as f:
	config = json.load(f)
	data = f.read()
	config_hash = hashlib.md5(data.encode('utf-8')).hexdigest()

# find this radar in config
for v in config:
	if "radar" in config[v] and config[v]["radar"] == radar:
		camera = config[v]["camera"]
		direction = v

# sanity check
if camera is None or direction is None:
	syslog.syslog(syslog.LOG_ERR, f"No valid camera and direction found for radar {radar}. Quitting...")

# for frame capture
camera_url = config["settings"]["camera"]["video_url"].replace("#channel#", str(camera))

# open port
ser = serial.Serial(tty, 38400, timeout=1)

if not ser.is_open:
	syslog.syslog(syslog.LOG_ERR, f"Failed to open {tty} for radar {radar}. Quitting...")
	sys.exit()

syslog.syslog(syslog.LOG_INFO, f"Port {tty} was opened for radar {radar}.")

# flush buffers
ser.flushInput()
ser.flushOutput()

# set the radar to use onboard pot for sensitivity
# 00 - disable pot and use digital settings
# 01 - enable pot, pot is turned to max sensitivity
# sensitivy in 00 mode is set via $D01
ser.write("$S0B01\r".encode())
read_data = ser.readline()
#ser.write("$D0100\r".encode())
#read_data = ser.readline()

# set radar's sampling rate
# 07 - 8960 Hz and can measure up to 100km/h
ser.write("$S0407\r".encode())
read_data = ser.readline()

# read out the sampling rate
ser.write("$S04\r".encode())
read_data = ser.readline()
data = read_data.decode()

# log info
syslog.syslog(syslog.LOG_INFO, f"Radar {radar} sampling rate is set to {data}.")

# reset the radar
syslog.syslog(syslog.LOG_DEBUG, f"Radar {radar} is reset.")
ser.write("$W00\r".encode())
read_data = ser.readline()

# check if it came online after reset
for x in range(10):
	ser.write("$R04\r".encode())
	read_data = ser.readline()
	data = read_data.decode().rstrip()

	if data == "@R0402":
		syslog.syslog(syslog.LOG_DEBUG, f"Radar {radar} is back in run mode.")
		break

	time.sleep(1)

# main loop
# read speed and check against a treshold to create a detection
while True:
	# get speed command
	ser.write("$C01\r".encode())

	# rest a bit
	time.sleep(0.01)

	# get speed, direction, and magnitude
	read_data = ser.readline()
	data = read_data.decode()
	items = data.split(";")

	# radar returns semicolon delimited data
	# 000;000;000;000;
	# check that we have 5 componets, the last one will be empty
	if len(items) == 5:
		# the 8960 value is dependent on $S04 setting
		speed_away = round(int(items[1]) * 8960 / 256 / 44.7 / 1, 2)
		speed_towards = round(int(items[0]) * 8960 / 256 / 44.7 / 1, 2)

		# speeder detected
		# look at the radar that sees the car approaching
		# if we go above the threshold and
		# it has been at least a ts_spacing since the last detection
		# we log a valid speeder
		ts_spacing = (1 - (speed_towards / 100.2)) + 1

		if speed_towards >= config[direction]["speed_limit"] and (time.time() - ts_detection) > ts_spacing:
			ts_detection = time.time()
			ts_detection_str = '%.4f'%(ts_detection)

			# log event
			syslog.syslog(syslog.LOG_INFO, f"Overspeed {speed_towards} km/h detected on radar {radar}.")

			# start frame capture
			#os.system(f"/usr/bin/ffmpeg -hide_banner -rtsp_transport tcp -probesize 1000 -fflags nobuffer -fflags discardcorrupt -flags low_delay -r 15 -copyts -t 4 -i {camera_url} /data/ffmpeg/{camera}_{ts_detection_str}.mp4 > /dev/null 2>&1 &")

			# create new detection
			os.system(f"{script_dir}/create_detection.py {radar} {speed_towards} {ts_detection_str} > /dev/null 2>&1 &")

			# flashers
			if config["settings"]["flashers"]:
				os.system(f"{script_dir}/flashers 8 > /dev/null 2>&1 &")

		# debug
		with open(f"/tmp/{camera}.osd", 'w') as f:
			tme = time.time()
			dt = datetime.datetime.fromtimestamp(tme)
			tme_str = dt.isoformat()
			f.write(f"{tme_str} ({tme}) :: Camera {camera} :: Radar {radar} :: Direction {direction}")

	# check if config changed
	with open(config_file) as f:
		data = f.read()
		config_hash_new = hashlib.md5(data.encode('utf-8')).hexdigest()

		if config_hash_new != config_hash:
			syslog.syslog(syslog.LOG_INFO, f"Config file {config_file} has changed. Updating...")
			config = json.loads(data)
			config_hash = config_hash_new

	# rest
	time.sleep(0.02)

ser.close()
