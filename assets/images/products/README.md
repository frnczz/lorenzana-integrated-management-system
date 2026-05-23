# Product Images Directory

This directory is for storing product package images for the LORINIMS system.

## Purpose
To replace emoji icons with actual product package images that are more recognizable to users.

## How to Add Product Images

1. Save product images in this directory with descriptive filenames
2. Recommended naming: `product-name-size.jpg` (e.g., `soy-sauce-350ml.jpg`)
3. Recommended image size: 60x60px to 100x100px (square format works best)
4. Supported formats: JPG, PNG, WebP

## Product Image Mapping

The system will automatically look for images matching product names:
- `soy-sauce-350ml.jpg` for "Lorins Soy Sauce 350 mL PET bottle"
- `patis-150ml.jpg` for "Lorins Patis Flavor 150 mL pouch"
- `vinegar-150ml.jpg` for "Lorins Coco Suka 150 mL"
- etc.

## Current Status
The system currently uses emoji icons. To use actual images, update the `getProductIcon()` function in `includes/functions.php` to return image paths instead of emojis.
