#!/usr/bin/env python3
"""
Targofit Model — Food Image-to-Text Analyzer
Converts a food image to an Arabic description, then lets any text AI
estimate the nutritional values.

Analysis tiers (best → fallback):
  Tier 1 : BLIP      (transformers + torch)  — AI captioning
  Tier 2 : OpenCV    (cv2 + numpy)            — K-means dominant colors + texture
  Tier 3 : scikit-image (skimage + numpy)     — histogram + GLCM texture
  Tier 4 : Pillow    (Pillow only)            — basic pixel color scan
"""

import sys
import json
import base64
import io
import colorsys
import os

# ─────────────────────────── Library availability flags ──────────────────────

try:
    from PIL import Image
    HAS_PIL = True
except ImportError:
    HAS_PIL = False

try:
    import cv2
    import numpy as np
    HAS_CV2 = True
except ImportError:
    HAS_CV2 = False

try:
    import numpy as np                          # already imported if cv2 worked
    from skimage import color as skcolor
    from skimage import feature as skfeature
    HAS_SKIMAGE = True
except ImportError:
    HAS_SKIMAGE = False

# numpy is needed by both cv2 and skimage; import safely
if not HAS_CV2 and not HAS_SKIMAGE:
    try:
        import numpy as np
        HAS_NUMPY = True
    except ImportError:
        HAS_NUMPY = False
else:
    HAS_NUMPY = True


# ─────────────────────────── Color → food name map ───────────────────────────

COLOR_FOOD = {
    'dark_brown':  'لحوم مشوية داكنة أو شوكولاتة',
    'brown':       'لحوم مشوية أو خبز بني أو بيضة',
    'golden':      'دجاج مقلي أو فلافل أو بطاطس',
    'beige':       'أرز أو مكرونة أو خبز أبيض',
    'white':       'أرز مطبوخ أو دجاج أو جبنة أو لبن',
    'cream':       'كريمة أو جبنة قشطية أو بشاميل',
    'red':         'طماطم أو فلفل أحمر أو صلصة',
    'orange':      'جزر أو بطاطا حلوة أو حمضيات',
    'yellow':      'بيض مقلي أو ذرة أو موز',
    'green':       'خضراوات طازجة أو سلطة أو بروكلي',
    'dark_green':  'سبانخ أو ملوخية أو أعشاب',
    'pink':        'سمك سلمون أو لحم وردي',
    'purple':      'باذنجان أو توت بنفسجي',
    'gray':        'أسماك أو لحوم داكنة',
}

TEXTURE_LABEL = {
    'smooth':  'ملمس ناعم (شوربة / صلصة / يوغرت)',
    'medium':  'ملمس متوسط',
    'rough':   'ملمس خشن (سلطة / وجبة متنوعة / مشويات)',
}


# ─────────────────────────── Shared color naming ─────────────────────────────

def rgb_to_color_name(r, g, b):
    """Convert an RGB triplet to a food-relevant color name using HSV."""
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
        if s > 0.40 and v < 0.50:
            return 'dark_brown'
        if s > 0.30 and v < 0.72:
            return 'brown'
        if s > 0.22 and v >= 0.65:
            return 'golden'
        return 'beige'

    if h_deg < 12 or h_deg > 348:
        return 'red'
    if h_deg < 42:
        return 'orange'
    if h_deg < 72:
        return 'yellow'
    if h_deg < 85:
        return 'yellow_green'
    if h_deg < 150:
        return 'green'
    if h_deg < 178:
        return 'dark_green'
    if h_deg < 260:
        return 'blue'
    if h_deg < 300:
        return 'purple'
    if h_deg < 348:
        return 'pink'
    return 'beige'


def texture_label(density):
    """Map an edge-density float to a texture label."""
    if density < 0.06:
        return 'smooth'
    if density < 0.18:
        return 'medium'
    return 'rough'


def build_description(color_fractions, tex='medium', shape_hint=''):
    """
    Build an Arabic food description from a {color_name: fraction} dict
    plus a texture label and an optional shape hint.
    """
    skip = {'black', 'white', 'gray', 'blue', 'yellow_green'}
    meaningful = [
        (c, f) for c, f in sorted(color_fractions.items(), key=lambda x: -x[1])
        if c not in skip and f > 0.07
    ]
    if not meaningful:
        return f"طبق طعام متنوع {shape_hint}".strip()

    parts = []
    for color, frac in meaningful[:3]:
        if color in COLOR_FOOD:
            density = 'بكثافة' if frac > 0.38 else ('بشكل معتدل' if frac > 0.18 else 'بقدر قليل')
            parts.append(f"{COLOR_FOOD[color]} ({density})")

    if not parts:
        return f"طبق طعام {shape_hint}".strip()

    tex_str = TEXTURE_LABEL.get(tex, '')
    desc = f"صورة طعام {shape_hint} يحتوي على: {' و '.join(parts)}"
    if tex_str:
        desc += f" — {tex_str}"
    return desc.strip()


# ═════════════════════════════════════════════════════════════════════════════
# TIER 1 — BLIP (transformers + torch)
# ═════════════════════════════════════════════════════════════════════════════

def try_blip(img_pil):
    """
    Use Salesforce BLIP for image captioning.
    Only uses locally cached model — never auto-downloads on a web request.
    """
    try:
        from transformers import BlipProcessor, BlipForConditionalGeneration
        import torch

        cache_dir = os.path.join(os.path.dirname(os.path.abspath(__file__)),
                                 '.targofit_cache')
        processor = BlipProcessor.from_pretrained(
            'Salesforce/blip-image-captioning-base',
            cache_dir=cache_dir,
            local_files_only=True
        )
        model = BlipForConditionalGeneration.from_pretrained(
            'Salesforce/blip-image-captioning-base',
            cache_dir=cache_dir,
            local_files_only=True
        )
        model.eval()

        with torch.no_grad():
            inputs = processor(img_pil, return_tensors='pt')
            out = model.generate(**inputs, max_new_tokens=60)

        caption = processor.decode(out[0], skip_special_tokens=True)
        return {'success': True, 'method': 'blip', 'description': caption}

    except Exception:
        return None


# ═════════════════════════════════════════════════════════════════════════════
# TIER 2 — OpenCV  (cv2 + numpy)
# ═════════════════════════════════════════════════════════════════════════════

def analyze_opencv(img_pil):
    """
    K-means dominant colour extraction + Canny edge density for texture.
    Returns a full result dict on success, None if cv2 is unavailable.
    """
    if not HAS_CV2:
        return None

    img_rgb = np.array(img_pil.resize((200, 200)))
    img_bgr = cv2.cvtColor(img_rgb, cv2.COLOR_RGB2BGR)

    # ── Background mask (remove near-white plate / near-black shadows) ──
    gray = cv2.cvtColor(img_bgr, cv2.COLOR_BGR2GRAY)
    mask = (gray > 22) & (gray < 232)
    food_pixels = img_rgb[mask]

    if len(food_pixels) < 150:
        food_pixels = img_rgb.reshape(-1, 3)

    # ── K-means dominant colours ──
    k = min(6, max(2, len(food_pixels) // 200))
    pixels_f = food_pixels.astype(np.float32)
    criteria = (cv2.TERM_CRITERIA_EPS + cv2.TERM_CRITERIA_MAX_ITER, 30, 1.0)
    _, labels, centers = cv2.kmeans(
        pixels_f, k, None, criteria, 10, cv2.KMEANS_RANDOM_CENTERS
    )
    counts = np.bincount(labels.flatten())
    total = len(labels)
    sorted_idx = np.argsort(-counts)

    color_fractions = {}
    for i in sorted_idx:
        r, g, b = int(centers[i][0]), int(centers[i][1]), int(centers[i][2])
        name = rgb_to_color_name(r, g, b)
        color_fractions[name] = color_fractions.get(name, 0) + counts[i] / total

    # ── Texture via Canny edge density ──
    edges = cv2.Canny(gray, 50, 150)
    tex = texture_label(np.sum(edges > 0) / edges.size)

    # ── Shape hint ──
    h, w = img_pil.size[1], img_pil.size[0]
    shape = 'في طبق مستدير' if abs(w - h) < min(w, h) * 0.3 else 'في وعاء أو صحن'

    desc = build_description(color_fractions, tex, shape)
    top_colors = {k: round(v, 2) for k, v in
                  sorted(color_fractions.items(), key=lambda x: -x[1])[:6]}

    return {
        'success': True,
        'method': 'opencv_kmeans',
        'description': desc,
        'colors': top_colors,
        'texture': tex,
    }


# ═════════════════════════════════════════════════════════════════════════════
# TIER 3 — scikit-image  (skimage + numpy)
# ═════════════════════════════════════════════════════════════════════════════

def analyze_skimage(img_pil):
    """
    Colour histogram in Lab space + GLCM texture via scikit-image.
    Returns a result dict on success, None if skimage is unavailable.
    """
    if not HAS_SKIMAGE:
        return None

    img_rgb = np.array(img_pil.resize((160, 160)), dtype=np.float64) / 255.0

    # ── Convert to Lab for perceptual colour accuracy ──
    img_lab = skcolor.rgb2lab(img_rgb)

    # ── Build colour distribution from RGB (Lab used only for texture) ──
    img_rgb8 = (img_rgb * 255).astype(np.uint8)
    color_fractions = {}
    for row in img_rgb8:
        for r, g, b in row:
            if r > 230 and g > 230 and b > 230:
                continue
            if r < 22 and g < 22 and b < 22:
                continue
            name = rgb_to_color_name(int(r), int(g), int(b))
            color_fractions[name] = color_fractions.get(name, 0) + 1

    total = sum(color_fractions.values()) or 1
    color_fractions = {c: v / total for c, v in color_fractions.items()}

    # ── GLCM texture ──
    gray_uint = (skcolor.rgb2gray(img_rgb) * 255).astype(np.uint8)
    try:
        glcm = skfeature.graycomatrix(
            gray_uint, distances=[1], angles=[0],
            levels=256, symmetric=True, normed=True
        )
        contrast    = skfeature.graycoprops(glcm, 'contrast')[0, 0]
        homogeneity = skfeature.graycoprops(glcm, 'homogeneity')[0, 0]
        # Map to texture label
        if homogeneity > 0.75:
            tex = 'smooth'
        elif contrast > 200:
            tex = 'rough'
        else:
            tex = 'medium'
    except Exception:
        tex = 'medium'

    h, w = img_pil.size[1], img_pil.size[0]
    shape = 'في طبق مستدير' if abs(w - h) < min(w, h) * 0.3 else 'في وعاء أو صحن'

    desc = build_description(color_fractions, tex, shape)
    top_colors = {k: round(v, 2) for k, v in
                  sorted(color_fractions.items(), key=lambda x: -x[1])[:6]}

    return {
        'success': True,
        'method': 'skimage_glcm',
        'description': desc,
        'colors': top_colors,
        'texture': tex,
    }


# ═════════════════════════════════════════════════════════════════════════════
# TIER 4 — Pillow  (basic pixel scan)
# ═════════════════════════════════════════════════════════════════════════════

def analyze_pillow(img_pil):
    """Simple pixel-by-pixel colour analysis using only Pillow."""
    small = img_pil.resize((120, 120))
    pixels = list(small.getdata())

    color_fractions = {}
    kept = 0
    for px in pixels:
        r, g, b = int(px[0]), int(px[1]), int(px[2])
        if r > 228 and g > 228 and b > 228:
            continue
        if r < 22 and g < 22 and b < 22:
            continue
        name = rgb_to_color_name(r, g, b)
        color_fractions[name] = color_fractions.get(name, 0) + 1
        kept += 1

    if kept:
        color_fractions = {c: v / kept for c, v in color_fractions.items()}

    h, w = img_pil.size[1], img_pil.size[0]
    shape = 'في طبق مستدير' if abs(w - h) < min(w, h) * 0.3 else 'في وعاء أو صحن'
    desc = build_description(color_fractions, 'medium', shape)
    top_colors = {k: round(v, 2) for k, v in
                  sorted(color_fractions.items(), key=lambda x: -x[1])[:6]}

    return {
        'success': True,
        'method': 'pillow_pixels',
        'description': desc,
        'colors': top_colors,
    }


# ─────────────────────────── Main dispatcher ─────────────────────────────────

def analyze(image_b64):
    if not HAS_PIL:
        return {
            'success': False,
            'error': 'Pillow غير مثبّت — شغّل: pip install Pillow'
        }

    try:
        img_bytes = base64.b64decode(image_b64)
        img = Image.open(io.BytesIO(img_bytes)).convert('RGB')
    except Exception as e:
        return {'success': False, 'error': f'فشل تحميل الصورة: {e}'}

    # Tier 1: BLIP
    result = try_blip(img)
    if result:
        # Enrich BLIP caption with color hint for better AI prompting
        color_hint = (analyze_opencv(img) or analyze_pillow(img))
        if color_hint and color_hint.get('success'):
            result['color_hint'] = color_hint.get('description', '')
        return result

    # Tier 2: OpenCV K-means
    result = analyze_opencv(img)
    if result:
        return result

    # Tier 3: scikit-image
    result = analyze_skimage(img)
    if result:
        return result

    # Tier 4: Pillow (always available if we got here)
    return analyze_pillow(img)


# ─────────────────────────── Entry point ─────────────────────────────────────

if __name__ == '__main__':
    try:
        raw = sys.stdin.read().strip()
        if not raw:
            print(json.dumps({'success': False, 'error': 'لا توجد بيانات في stdin'}))
            sys.exit(1)
        data   = json.loads(raw)
        result = analyze(data.get('image', ''))
        print(json.dumps(result, ensure_ascii=False))
    except json.JSONDecodeError as e:
        print(json.dumps({'success': False, 'error': f'JSON غير صحيح: {e}'}))
    except Exception as e:
        print(json.dumps({'success': False, 'error': str(e)}))
