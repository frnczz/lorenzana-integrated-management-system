# Quick Database Setup - Option 1

## Step-by-Step Instructions

### 1. Start XAMPP
- Open **XAMPP Control Panel**
- Click **Start** for **Apache**
- Click **Start** for **MySQL**
- Both should show green/running status

### 2. Open phpMyAdmin
- Open your web browser
- Go to: **http://localhost/phpmyadmin**

### 3. Import the SQL File
1. Click on the **"Import"** tab at the top of phpMyAdmin
2. Click the **"Choose File"** button
3. Navigate to: `C:\xampp\htdocs\lorinims\database_schema.sql`
4. Select the file `database_schema.sql`
5. Scroll down and click the **"Go"** button at the bottom

### 4. Wait for Completion
- You'll see a success message when done
- The old `lorinims_db` will be deleted automatically
- A fresh database with all tables will be created

### 5. Verify Setup
1. Look at the left sidebar - you should see **lorinims_db**
2. Click on **lorinims_db** to expand it
3. You should see all the tables:
   - users
   - products
   - raw_materials
   - finished_goods
   - production_batches
   - qc_records
   - suppliers
   - purchase_requests
   - customers
   - sales_orders
   - delivery_assignments
   - invoices
   - expenses
   - inventory_transactions
   - gps_tracking

### 6. Test Login
Go to: **http://localhost/lorinims/login.php**

Try logging in with:
- **Username:** `admin`
- **Password:** `admin123`

## That's It! ✅

Your database is now set up with the new schema and ready to use!
