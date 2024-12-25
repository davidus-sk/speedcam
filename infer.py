#!/app/inference/bin/python3

from inference import get_model
from PIL import Image
import glob
import json
import sqlite3
import os
import time
import sys
import shutil
import syslog
import fcntl
import subprocess

# only run once
lock_file_pointer = os.open(f"/tmp/infer.pid", os.O_WRONLY | os.O_CREAT)

try:
	fcntl.lockf(lock_file_pointer, fcntl.LOCK_EX | fcntl.LOCK_NB)
except IOError:
	sys.exit()

# start syslog
syslog.openlog(logoption=syslog.LOG_PID)

# load model
model = get_model(model_id="license-plate-recognition-rxg4e/4", api_key="BCAzwxkM57CDYeVdQXr3")

# load DB connection
con = sqlite3.connect("/data/speed.db")
cur = con.cursor()

while True:

	for row in cur.execute("SELECT time, camera FROM detections WHERE processed = 0"):
		ts = row[0]
		camera = row[1]

		# log
		syslog.syslog(syslog.LOG_INFO, f"Processing item {ts} on camera {camera}.")
		print(f"Processing item {ts} on camera {camera}.")

		# create detection
		directory = f"/data/{camera}_{ts}"

		# find images
		best_image = None
		best_score = 0
		best_box = ()
		found_plate = False
		results = []
		files = glob.glob(f"/data/ffmpeg/{camera}_{ts}*")

		print(f"Found {len(files)} images to process for {ts} on camera {camera} in /data/ffmpeg/{camera}_{ts}*")

		result = subprocess.run(['/usr/bin/pgrep', '-f', f'"[f]fmpeg/{camera}_{ts}_"'], capture_output=True, text=True)
		print(result)
		if result.stdout:
			print(f"Still acquiring images. Quitting...")
			sys.exit()

		for name in glob.glob(f"/data/ffmpeg/{camera}_{ts}*"):
			if not found_plate or results["predictions"]:
				try:
					results = json.loads(model.infer(name)[0].json())
				except:
					os.remove(name)
					continue

				for prediction in results["predictions"]:
					print(results["predictions"])

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

		cur.execute(f"UPDATE detections SET processed = 1 WHERE camera = {camera} AND time = '{ts}'")
		con.commit()

	time.sleep(2)
