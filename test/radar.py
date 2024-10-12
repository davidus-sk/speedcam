#!/usr/bin/python3

"""
Test data coming from the radar
"""

# import libraries
import sys
import serial
import time
import json
import os
import fcntl
from pathlib import Path

# need to have serial port and radar id
# arg 1 - serial port to connect to
# arg 2 - serial ID of the radar to work with
if len(sys.argv) < 3:
	sys.exit()

# start up variables
tty = sys.argv[1]
radar = sys.argv[2]

# read config
config_file = "/app/speed/config.json"
config = {}

if not os.path.isfile(config_file) or os.path.getsize(config_file) <= 0:
	print(f"Config file {config_file} does not exist. Quitting...\n")
	sys.exit()

with open(config_file) as f:
	config = json.load(f)

# open port
ser = serial.Serial(tty, 38400, timeout=1)

if not ser.is_open:
	print(f"Failed to open {tty} for radar {radar}. Quitting...\n")
	sys.exit()

# flush buffers
ser.flushInput()
ser.flushOutput()

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

		print(f"{radar} <> TO: {speed_towards:4.2f} <> FROM: {speed_away:4.2f}\n")

	# rest
	time.sleep(0.02)
