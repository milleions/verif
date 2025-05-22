import sys
import cv2
import numpy as np
from PIL import Image, ImageDraw

# --- CONFIGURATION ---
DEBUG_OVERLAY = False
CANVAS_W, CANVAS_H = 1660, 2196
OVAL_W, OVAL_H = 1048, 1376
TOP_MARGIN = 141
OVAL_CENTER = (CANVAS_W // 2, TOP_MARGIN + OVAL_H // 2)

# Input/output paths
input_path = sys.argv[1]
output_path = sys.argv[2]

# Load RGBA image
img = Image.open(input_path).convert("RGBA")
np_img = np.array(img)
cv_img = cv2.cvtColor(np_img, cv2.COLOR_RGBA2BGRA)
gray = cv2.cvtColor(cv_img, cv2.COLOR_BGRA2GRAY)

# Face detection: get chin and center X
face_cascade = cv2.CascadeClassifier(cv2.data.haarcascades + 'haarcascade_frontalface_default.xml')
faces = face_cascade.detectMultiScale(gray, scaleFactor=1.1, minNeighbors=5)

if len(faces) == 0:
    print("No face detected.")
    sys.exit(1)

(x, y, w, h) = faces[0]
face_bottom = y + h  # chin
face_center_x = x + w // 2

# Find top of visible subject (hair/top of head)
alpha = np_img[:, :, 3]
ys, xs = np.where(alpha > 10)
if len(ys) == 0:
    print("No visible content found.")
    sys.exit(1)

visible_top = ys.min()  # top of hair

# Real face height = chin to top of head (hair included)
real_face_height = face_bottom - visible_top

# Scale factor to make that fit inside 375px oval
scale = OVAL_H / real_face_height

# Resize full image
scaled_img = img.resize((int(img.width * scale), int(img.height * scale)), Image.LANCZOS)

# Recalculate positions after scaling
scaled_face_center_x = int(face_center_x * scale)
scaled_visible_top = int(visible_top * scale)

# Align hair to top margin
offset_y = TOP_MARGIN - scaled_visible_top
offset_x = OVAL_CENTER[0] - scaled_face_center_x

# Create canvas and paste
canvas = Image.new("RGBA", (CANVAS_W, CANVAS_H), (0, 0, 0, 0))
canvas.paste(scaled_img, (offset_x, offset_y), scaled_img)

# Debug overlay
if DEBUG_OVERLAY:
    draw = ImageDraw.Draw(canvas)
    l = OVAL_CENTER[0] - OVAL_W // 2
    t = OVAL_CENTER[1] - OVAL_H // 2
    r = OVAL_CENTER[0] + OVAL_W // 2
    b = OVAL_CENTER[1] + OVAL_H // 2
    draw.ellipse([l, t, r, b], outline="red", width=3)
    draw.ellipse([OVAL_CENTER[0]-3, OVAL_CENTER[1]-3, OVAL_CENTER[0]+3, OVAL_CENTER[1]+3], fill="blue")

# Save result
canvas.save(output_path, dpi=(1200, 1200))