#!/usr/bin/python3

import sys
import serial
import syslog
import time
import json
import os
import fcntl
from pathlib import Path

# need to have serial port and radar id
if len(sys.argv) < 3:
	sys.exit()

# start up variables
tty = sys.argv[1]
radar = sys.argv[2]
Path(f"/dev/shm/{radar}.top").touch()

# only run once
lock_file_pointer = os.open(f"/tmp/read_radar_{radar}.pid", os.O_WRONLY | os.O_CREAT)

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
	if "radar" in config[v] and config[v]["radar"] == radar:
		camera = config[v]["camera"]
		direction = v

if camera is None or direction is None:
	syslog.syslog(syslog.LOG_ERR, f"No valid camera and direction found for radar {radar}. Quitting...")

# open port
ser = serial.Serial(tty, 38400, timeout=1)

if not ser.is_open:
	syslog.syslog(syslog.LOG_ERR, f"Failed to open {tty} for radar {radar}. Quitting...")
	sys.exit()

syslog.syslog(syslog.LOG_INFO, f"Port {tty} was opened for radar {radar}.")

# flush buffers
ser.flushInput()
ser.flushOutput()

# get and set some radar settings
ser.write("$S0B00\r".encode())
read_data = ser.readline()
ser.write("$D0100\r".encode())
read_data = ser.readline()
ser.write("$S0407\r".encode())
read_data = ser.readline()

ser.write("$S04\r".encode())
read_data = ser.readline()
data = read_data.decode()

syslog.syslog(syslog.LOG_INFO, f"Radar {radar} sampling rate is set to {data}.")

# reset the radar
syslog.syslog(syslog.LOG_DEBUG, f"Radar {radar} is reset.")
ser.write("$W00\r".encode())

for x in range(10):
	ser.write("$R04\r".encode())
	read_data = ser.readline()
	data = read_data.decode()

	if data == "@R0402":
		syslog.syslog(syslog.LOG_DEBUG, f"Radar {radar} is back in run mode.")
		break

	time.sleep(1)

while True:
	# get speed command
	ser.write("$C01\r".encode())

	# rest a bit
	time.sleep(0.01)

	# get speed, direction, and magnitude
	read_data = ser.readline()
	data = read_data.decode()
	items = data.split(";")

	if len(items) == 5:
		speed_away = round(int(items[0]) * 8960 / 256 / 44.7 / 1, 2)
		speed_towards = round(int(items[1]) * 8960 / 256 / 44.7 / 1, 2)

		# speeder detected
		# look at the radar that sees the car approaching
		if speed_towards >= config[direction]["speed_limit"]:
			syslog.syslog(syslog.LOG_INFO, f"Overspeed {speed_towards} km/h detected on radar {radar}.")

			# create new detection
			os.system(f"/app/speed/create_detection.py {radar} {speed_towards} > /dev/null 2>&1 &");

			# flashers
			os.system("/app/speed/flashers 8 > /dev/null 2>&1 &");

		# debug
		print(f"To {speed_away} km/h :: From {speed_towards} km/h")

		# record current speed
		with open(f"/dev/shm/{radar}.speed", 'w') as f:
			f.write(str(speed_towards))

		# record top speed
		with open(f"/dev/shm/{radar}.top", 'r+') as f:
			speed_top = f.readline()

			if not speed_top.strip() or float(speed_top) < speed_towards:
				f.seek(0)
				f.write(str(speed_towards))
				f.truncate()

	# rest
	time.sleep(0.02)

ser.close()
