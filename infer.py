from inference import get_model
from PIL import Image
import glob
import json
import sqlite3

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
