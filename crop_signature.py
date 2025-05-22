import sys
from PIL import Image
import numpy as np

input_path = sys.argv[1]
output_path = sys.argv[2]

img = Image.open(input_path).convert("RGBA")
np_img = np.array(img)

alpha = np_img[:, :, 3]
coords = np.argwhere(alpha > 0)

if coords.size == 0:
    raise Exception("Image is fully transparent.")

top_left = coords.min(axis=0)
bottom_right = coords.max(axis=0) + 1

cropped = img.crop((top_left[1], top_left[0], bottom_right[1], bottom_right[0]))

# Resize to max height of 400px if needed
w, h = cropped.size
if h > 400:
    new_w = int((400 / h) * w)
    cropped = cropped.resize((new_w, 400), Image.LANCZOS)

cropped.save(output_path)