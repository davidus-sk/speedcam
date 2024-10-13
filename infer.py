from inference import get_model
from PIL import Image
import glob
import json

best_image = None
best_score = 0
best_box = ()

model = get_model(model_id="license-plate-recognition-rxg4e/4")

for name in glob.glob(f"/dev/shm/ffmpeg/0_1728833558.3241858_*"):
	results = json.loads(model.infer(name)[0].json())


	for prediction in results["predictions"]:

		if "confidence" in prediction:
			confidence = float(prediction["confidence"])
			x1 = int(prediction["x"]) - int(prediction["width"]) / 2
			y1 = int(prediction["y"]) - int(prediction["height"]) / 2
			x2 = int(prediction["x"]) + int(prediction["width"]) / 2
			y2 = int(prediction["y"]) + int(prediction["height"]) / 2

			if confidence > best_score:
				best_score = confidence
				best_image = name
				best_box = (x1, y1, x2, y2)

image = Image.open(best_image)
cropped_image = image.crop(best_box)
cropped_image.save("/tmp/plate.jpg")
