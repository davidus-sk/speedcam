#!/usr/bin/python3

import sys
import serial
import syslog
import time
import json
import os
import fcntl

# need to have serial port
if len(sys.argv) < 3:
	sys.exit()

# only run once
lock_file_pointer = os.open(f"/tmp/read_radar_{sys.argv[2]}.pid", os.O_WRONLY | os.O_CREAT)

try:
	fcntl.lockf(lock_file_pointer, fcntl.LOCK_EX | fcntl.LOCK_NB)
except IOError:
	sys.exit()

# start syslog
syslog.openlog(logoption=syslog.LOG_PID)

# read config
config_file = "/app/speed/config.json"
config = {}
camera = None
direction = None

if not os.path.isfile(config_file) or os.path.getsize(config_file) <= 0:
	syslog.syslog(syslog.LOG_ERR, f"Config file {config_file} does not exist. Quitting...")
	sys.exit()

with open(config_file) as f:
	config = json.load(f)

# find this radar in config
for v in config:
	if "radar" in config[v] and config[v]["radar"] == sys.argv[2]:
		camera = config[v]["camera"]
		direction = v

if camera is None or direction is None:
	syslog.syslog(syslog.LOG_ERR, f"No valid camera and direction found for radar {sys.argv[2]}. Quitting...")

# open port
ser = serial.Serial(sys.argv[1], 38400, timeout=1)

if not ser.is_open:
	syslog.syslog(syslog.LOG_ERR, f"Failed to open {sys.argv[1]}. Quitting...")
	sys.exit()

syslog.syslog(syslog.LOG_INFO, f"Port {sys.argv[1]} was opened for radar {sys.argv[2]}.")

# flush buffers
ser.flushInput()
ser.flushOutput()

while True:
	ser.write("$C01\r".encode())
	time.sleep(0.05)
	read_data = ser.readline()
	data = read_data.decode()
	items = data.split(";")

	if len(items) == 5:
		speed_away = int(items[0]) * 5120 / 256 / 44.7 / 1
		speed_towards = int(items[1]) * 5120 / 256 / 44.7 / 1

		# speeder detected
		# look at the radar that sees the car approaching
		if speed_towards >= config[direction]["speed_limit"]:
			syslog.syslog(syslog.LOG_INFO, f"Overspeed detected: {speed_towards} km/h.")

			# create new detection
			os.system(f"/app/speed/create_detection.py {sys.argv[2]} {speed_towards} > /dev/null 2>&1 &");

			# flashers
			os.system("/app/speed/flashers 8 > /dev/null 2>&1 &");

		print(f"To {speed_away} km/h :: From {speed_towards} km/h")

		with open(f"/dev/shm/{sys.argv[2]}.speed", 'wb') as f:
			f.write(str(speed_towards).encode())

	time.sleep(0.05)

ser.close()
