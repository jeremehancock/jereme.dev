#!/usr/bin/env python3

from PIL import Image, ImageDraw, ImageFont
import os

def create_placeholder(output_path="data/placeholder.jpg", width=300, height=450):
    """Create a placeholder image for missing posters"""
    
    # Create a new image with a dark background
    img = Image.new('RGB', (width, height), (42, 42, 42))
    draw = ImageDraw.Draw(img)
    
    # Draw a simple film reel icon using basic shapes
    center_x, center_y = width // 2, height // 2
    
    # Draw outer circle (film reel)
    circle_radius = min(width, height) // 4
    draw.ellipse([center_x - circle_radius, center_y - circle_radius,
                  center_x + circle_radius, center_y + circle_radius],
                 outline=(229, 160, 13), width=3)
    
    # Draw inner circles (holes in the film reel)
    hole_radius = circle_radius // 4
    for angle in [0, 60, 120, 180, 240, 300]:
        import math
        x = center_x + circle_radius * 0.6 * math.cos(math.radians(angle))
        y = center_y + circle_radius * 0.6 * math.sin(math.radians(angle))
        draw.ellipse([x - hole_radius, y - hole_radius,
                      x + hole_radius, y + hole_radius],
                     outline=(229, 160, 13), width=2)
    
    # Add text "No Image"
    try:
        # Try to use a default font, fall back to basic font if not available
        font = ImageFont.truetype("/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf", 32)
    except:
        font = ImageFont.load_default()
    
    text = "No Image"
    text_bbox = draw.textbbox((0, 0), text, font=font)
    text_width = text_bbox[2] - text_bbox[0]
    text_height = text_bbox[3] - text_bbox[1]
    text_x = (width - text_width) // 2
    text_y = center_y + circle_radius + 20
    
    draw.text((text_x, text_y), text, fill=(229, 160, 13), font=font)
    
    # Save the image
    os.makedirs(os.path.dirname(output_path), exist_ok=True)
    img.save(output_path, 'JPEG', quality=85)
    print(f"Placeholder image created at: {output_path}")

if __name__ == "__main__":
    create_placeholder()
