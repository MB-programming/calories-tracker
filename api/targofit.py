#!/usr/bin/env python3
"""
Targofit Model - Food Image to Text Analyzer
Converts food images to Arabic text descriptions for text-based AI analysis.

Requirements (minimal): pip install Pillow
Optional (better accuracy): pip install transformers torch
"""

import sys
import json
import base64
import io
import colorsys
import os

try:
    from PIL import Image
    HAS_PIL = True
except ImportError:
    HAS_PIL = False


# ─── Color → Food description mapping ────────────────────────────────────────

COLOR_FOOD = {
    'brown':      'لحوم مشوية أو مقلية أو خبز بني',
    'golden':     'دجاج مقلي أو بطاطس أو فلافل',
    'beige':      'أرز أو مكرونة أو خبز أبيض',
    'white':      'أرز مطبوخ أو دجاج أو جبنة أو لبن',
    'green':      'خضراوات طازجة أو سلطة أو بروكلي أو فاصوليا',
    'dark_green': 'سبانخ أو ملوخية أو أعشاب',
    'red':        'طماطم أو فلفل أحمر أو صلصة أحمر',
    'orange':     'جزر أو بطاطا حلوة أو فاكهة حمضيات',
    'yellow':     'بيض مقلي أو ذرة أو موز أو جبنة صفراء',
    'pink':       'سمك سلمون أو لحم وردي',
    'purple':     'باذنجان أو توت بنفسجي أو ملفوف',
    'cream':      'كريمة أو جبنة قشطية أو شوربة',
    'dark_brown': 'شوكولاتة أو لحوم مشوية داكنة',
    'gray':       'أسماك أو لحوم داكنة',
}


# ─── Color classification ─────────────────────────────────────────────────────

def pixel_to_color(r, g, b):
    """Map an RGB pixel to a food-relevant color name."""
    h, s, v = colorsys.rgb_to_hsv(r / 255.0, g / 255.0, b / 255.0)
    h_deg = h * 360

    if v < 0.18:
        return 'black'
    if v > 0.88 and s < 0.12:
        return 'white'
    if s < 0.10:
        return 'gray'

    # Brown / golden / beige family
    if 18 <= h_deg <= 55:
        if s > 0.35 and v < 0.55:
            return 'dark_brown'
        if s > 0.30 and v < 0.75:
            return 'brown'
        if s > 0.25 and v >= 0.65:
            return 'golden'
        return 'beige'

    if h_deg < 12 or h_deg > 348:
        return 'red'
    if h_deg < 42:
        return 'orange'
    if h_deg < 72:
        return 'yellow'
    if h_deg < 100:
        return 'yellow_green'
    if h_deg < 150:
        return 'green'
    if h_deg < 175:
        return 'dark_green'
    if h_deg < 260:
        return 'blue'        # usually plate/background
    if h_deg < 300:
        return 'purple'
    if h_deg < 348:
        return 'pink'

    return 'beige'


# ─── Image color analysis ─────────────────────────────────────────────────────

def analyze_colors(img):
    """Return a dict of {color_name: fraction} for the image."""
    small = img.resize((120, 120))
    pixels = list(small.getdata())

    counts = {}
    skipped = 0
    for px in pixels:
        r, g, b = int(px[0]), int(px[1]), int(px[2])
        # Skip near-white (plate/background) and near-black borders
        if r > 225 and g > 225 and b > 225:
            skipped += 1
            continue
        if r < 25 and g < 25 and b < 25:
            skipped += 1
            continue
        c = pixel_to_color(r, g, b)
        counts[c] = counts.get(c, 0) + 1

    total = sum(counts.values()) or 1
    return {c: v / total for c, v in sorted(counts.items(), key=lambda x: -x[1])}


# ─── Build Arabic description from colors ─────────────────────────────────────

def build_description(color_dist, img_size):
    """Produce a food description string from dominant colors."""
    # Ignore background-like / non-food colors
    skip = {'black', 'white', 'gray', 'blue', 'yellow_green'}
    meaningful = [
        (c, v) for c, v in color_dist.items()
        if c not in skip and v > 0.07
    ]

    if not meaningful:
        return "طبق طعام متنوع"

    parts = []
    for color, frac in meaningful[:3]:
        if color in COLOR_FOOD:
            density = 'بكثافة' if frac > 0.35 else ('بشكل معتدل' if frac > 0.18 else 'بقدر قليل')
            parts.append(f"{COLOR_FOOD[color]} ({density})")

    if not parts:
        return "طبق طعام متنوع"

    # Shape hint from aspect ratio
    w, h = img_size
    shape = "في طبق مستدير" if abs(w - h) < min(w, h) * 0.3 else "في وعاء أو صحن"

    return f"صورة طعام {shape} يحتوي على: {' و '.join(parts)}"


# ─── Optional BLIP captioning ─────────────────────────────────────────────────

def try_blip(img):
    """
    Use Salesforce BLIP for image captioning if transformers+torch are installed
    and the model is already cached. Never downloads on first run automatically.
    """
    try:
        from transformers import BlipProcessor, BlipForConditionalGeneration
        import torch

        cache_dir = os.path.join(os.path.dirname(__file__), '.targofit_cache')

        processor = BlipProcessor.from_pretrained(
            "Salesforce/blip-image-captioning-base",
            cache_dir=cache_dir,
            local_files_only=True     # only use if already cached – never auto-download
        )
        model = BlipForConditionalGeneration.from_pretrained(
            "Salesforce/blip-image-captioning-base",
            cache_dir=cache_dir,
            local_files_only=True
        )
        model.eval()

        with torch.no_grad():
            inputs = processor(img, return_tensors="pt")
            out = model.generate(**inputs, max_new_tokens=60)

        return processor.decode(out[0], skip_special_tokens=True)

    except Exception:
        return None


# ─── Main analysis ────────────────────────────────────────────────────────────

def analyze(image_b64):
    if not HAS_PIL:
        return {
            "success": False,
            "error": "مكتبة Pillow غير مثبّتة - شغّل: pip install Pillow"
        }

    try:
        img_bytes = base64.b64decode(image_b64)
        img = Image.open(io.BytesIO(img_bytes)).convert('RGB')
    except Exception as e:
        return {"success": False, "error": f"فشل تحميل الصورة: {e}"}

    # Try BLIP first (more accurate, optional)
    blip = try_blip(img)
    if blip:
        color_dist = analyze_colors(img)
        arabic_hint = build_description(color_dist, img.size)
        return {
            "success": True,
            "method": "blip",
            "description": blip,
            "arabic_hint": arabic_hint
        }

    # Fallback: color-based analysis (always available with Pillow)
    color_dist = analyze_colors(img)
    description = build_description(color_dist, img.size)
    top_colors = {k: round(v, 2) for k, v in list(color_dist.items())[:6]}

    return {
        "success": True,
        "method": "color_analysis",
        "description": description,
        "colors": top_colors
    }


# ─── Entry point ──────────────────────────────────────────────────────────────

if __name__ == '__main__':
    try:
        raw = sys.stdin.read().strip()
        if not raw:
            print(json.dumps({"success": False, "error": "لا توجد بيانات في stdin"}))
            sys.exit(1)

        data = json.loads(raw)
        result = analyze(data.get('image', ''))
        print(json.dumps(result, ensure_ascii=False))

    except json.JSONDecodeError as e:
        print(json.dumps({"success": False, "error": f"JSON غير صحيح: {e}"}))
    except Exception as e:
        print(json.dumps({"success": False, "error": str(e)}))
