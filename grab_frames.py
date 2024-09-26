#!/usr/bin/python3
import sys
import os
import fcntl
import syslog
import json
import time
import urllib.request
import urllib.parse
import base64

def save_file_with_digest_auth(url, username, password, filename):
	"""Saves a file from the specified URL using digest authentication.

	Args:
	url: The URL of the file to download.
	username: The username for digest authentication.
	password: The password for digest authentication.
	filename: The name of the file to save.
	"""

	# Create a password manager
	password_mgr = urllib.request.HTTPPasswordMgrWithDefaultRealm()
	password_mgr.add_password(None, url, username, password)

	# Create a handler for digest authentication
	handler = urllib.request.HTTPDigestAuthHandler(password_mgr)
	opener = urllib.request.build_opener(handler)
	urllib.request.install_opener(opener)

	# Download the file
	with open(filename, 'wb') as f:
		response = urllib.request.urlopen(url, timeout=8)
		f.write(response.read())

# need to have serial port
if len(sys.argv) < 2:
	sys.exit()

direction = sys.argv[1]

# only run once
lock_file_pointer = os.open(f"/tmp/grab_frames_{direction}.pid", os.O_WRONLY | os.O_CREAT)

try:
        fcntl.lockf(lock_file_pointer, fcntl.LOCK_EX | fcntl.LOCK_NB)
except IOError:
        sys.exit()

# only run once
lock_file_pointer = os.open(f"/tmp/grab_frames_{direction}.pid", os.O_WRONLY | os.O_CREAT)

try:
	fcntl.lockf(lock_file_pointer, fcntl.LOCK_EX | fcntl.LOCK_NB)
except IOError:
	sys.exit()

# start syslog
syslog.openlog(logoption=syslog.LOG_PID)

# read config
config_file = "/app/speed/config.json"
config = {}
buffer = []
camera = None
url = None

if not os.path.isfile(config_file) or os.path.getsize(config_file) <= 0:
	syslog.syslog(syslog.LOG_ERR, f"Config file {config_file} does not exist. Quitting...")
	sys.exit()

with open(config_file) as f:
	config = json.load(f)

# create dir
directory = "/dev/shm/frames/"

if not os.path.isdir(directory):
	os.makedirs(directory)

# find this radar in config
camera = config[direction]["camera"]
url = config["camera"]["url"]

while True:
	print(f"Grabbing frame")
	filename = directory + str(camera) + "_" + str(time.time()) + ".jpg"
	save_file_with_digest_auth(url + str(camera), 'admin', '337caaf1d2', filename)

	buffer.append(filename)

	if len(buffer) > 10:
		file = buffer.pop(0)
		os.remove(file)
