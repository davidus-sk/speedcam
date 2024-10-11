from inference import get_model

model = get_model(model_id="license-plate-recognition-rxg4e/4")

results = model.infer("https://source.roboflow.com/zD7y6XOoQnh7WC160Ae7/TwzYQXZhYdOCVWNXPF98/original.jpg")

print(results)
