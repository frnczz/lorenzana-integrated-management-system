# Lorins Products Update

## Database Update Required

The database schema has been updated with all Lorins products. To apply these changes:

### Option 1: Update Existing Database (Recommended)
Run this SQL in phpMyAdmin to add the new products:

```sql
USE lorinims_db;

-- Delete old sample products
DELETE FROM products WHERE product_name IN ('Soy Sauce 1L', 'Soy Sauce 500ml', 'Vinegar 1L');

-- Insert all Lorins products
INSERT INTO products (product_name, description, unit) VALUES
-- Patis (Fish Sauce) Products
('Lorins Patis Flavor 150 mL pouch', 'Lorins Patis Flavor 150 mL pouch', 'pcs'),
('Lorins Patis Flavor 350 mL PET bottle', 'Lorins Patis Flavor 350 mL PET bottle', 'pcs'),
('Lorins Patis Flavor with Chili 350 mL PET bottle', 'Lorins Patis Flavor with Chili 350 mL PET bottle', 'pcs'),
('Lorins Patis Flavor 1 L', 'Lorins Patis Flavor 1 Liter', 'pcs'),
('Lorins Patis Flavor 1893 mL (Half Gallon)', 'Lorins Patis Flavor 1893 mL (Half Gallon)', 'pcs'),
('Lorins Patis Flavor 3785 mL (Gallon)', 'Lorins Patis Flavor 3785 mL (Gallon)', 'pcs'),
-- Soy Sauce Products
('Lorins Soy Sauce 350 mL PET bottle', 'Lorins Soy Sauce 350 mL PET bottle', 'pcs'),
('Lorins Soy Sauce 1 L', 'Lorins Soy Sauce 1 Liter', 'pcs'),
('Lorins Soy Sauce 3785 mL (Gallon)', 'Lorins Soy Sauce 3785 mL (Gallon)', 'pcs'),
-- Vinegar Products
('Lorins Coco Suka 150 mL', 'Lorins Coco Suka 150 mL', 'pcs'),
('Lorins Coco Suka 310 mL', 'Lorins Coco Suka 310 mL', 'pcs'),
('Lorins Coco Suka 800 mL', 'Lorins Coco Suka 800 mL', 'pcs'),
('Lorins Budget / Value Pack', 'Lorins Budget / Value Pack (Vinegar + Fish Sauce + Soy Sauce)', 'pcs'),
-- Alamang (Sauteed Shrimp Paste) Products
('Lorins Alamang Guisado Original 8 oz / 250 g', 'Lorins Alamang Guisado Original 8 oz / 250 g', 'pcs'),
('Lorins Alamang Guisado Sweet', 'Lorins Alamang Guisado Sweet', 'pcs'),
('Lorins Alamang Guisado Spicy', 'Lorins Alamang Guisado Spicy', 'pcs'),
-- Bagoong (Fermented Fish)
('Lorenzana Bagoong Isda Original 310 mL', 'Lorenzana Bagoong Isda Original 310 mL', 'pcs'),
-- Specialty Products
('Lorins Crab Paste 8 oz', 'Lorins Crab Paste 8 oz', 'pcs'),
('Lorins Coconut Milk 400 mL', 'Lorins Coconut Milk 400 mL tin', 'pcs'),
-- Premium Products
('Lorins Premium Extra-Virgin Anchovy Extract 200 mL', 'Lorins Premium Extra-Virgin Anchovy Extract 200 mL', 'pcs'),
-- Variants
('Lorins Fish Sauce 800 mL glass bottle', 'Lorins Fish Sauce 800 mL glass bottle', 'pcs'),
('Lorins Fish Sauce with Chili & Kalamansi 310 mL', 'Lorins Fish Sauce with Chili & Kalamansi 310 mL', 'pcs'),
('Lorins Coco Suka Spicy-Sweet 310 mL', 'Lorins Coco Suka Spicy-Sweet 310 mL', 'pcs');
```

### Option 2: Re-run Full Schema
If you prefer a fresh start, re-run the entire `database_schema.sql` file which now includes all products.

## Theme Updates

The system theme has been updated to match the Lorins brand:
- **Primary Color**: Orange (#FF6B35)
- **Secondary Color**: Light Orange (#FF8C5A)
- **Logo**: Added to all pages (login, dashboards, GPS page)
- **Branding**: Consistent orange theme throughout

## Logo Implementation

The Lorins logo has been added to:
- ✅ Login page
- ✅ Sidebar (responsive - adapts when collapsed)
- ✅ Header (desktop only)
- ✅ All dashboards (Admin, Production, Warehouse, QC, Sales, Accounting)
- ✅ GPS/Driver page

## Product Categories

All products are now organized by category:
1. **Patis (Fish Sauce)** - 6 products
2. **Soy Sauce** - 3 products
3. **Vinegar** - 4 products (including value pack)
4. **Alamang (Shrimp Paste)** - 3 products
5. **Bagoong** - 1 product
6. **Specialty** - 2 products
7. **Premium** - 1 product
8. **Variants** - 3 products

**Total: 23 Lorins Products**

## Next Steps

1. Run the SQL update to add products to your database
2. Test product selection in Production and Sales modules
3. Verify logo appears correctly on all pages
4. Check that orange theme is applied throughout
