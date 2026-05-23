# Database Setup Guide for LORINIMS

## Method 1: Using phpMyAdmin (Easiest)

### Steps:
1. **Start XAMPP**
   - Open XAMPP Control Panel
   - Start **Apache** and **MySQL** services

2. **Open phpMyAdmin**
   - Open your web browser
   - Go to: `http://localhost/phpmyadmin`

3. **Import the SQL file**
   - Click on **"Import"** tab at the top
   - Click **"Choose File"** button
   - Navigate to: `C:\xampp\htdocs\lorinims\database_schema.sql`
   - Select the file
   - Click **"Go"** button at the bottom

4. **Verify**
   - Check that `lorinims_db` database appears in the left sidebar
   - Click on it to see all the tables

## Method 2: Using MySQL Command Line

### Steps:
1. **Open Command Prompt**
   - Press `Win + R`
   - Type `cmd` and press Enter

2. **Navigate to MySQL bin directory**
   ```cmd
   cd C:\xampp\mysql\bin
   ```

3. **Login to MySQL**
   ```cmd
   mysql -u root -p
   ```
   - Press Enter (default password is empty, or enter your MySQL password if you set one)

4. **Run the SQL file**
   ```sql
   source C:/xampp/htdocs/lorinims/database_schema.sql
   ```
   OR
   ```cmd
   mysql -u root < C:\xampp\htdocs\lorinims\database_schema.sql
   ```

## Method 3: Copy-Paste in phpMyAdmin

### Steps:
1. **Open phpMyAdmin**: `http://localhost/phpmyadmin`

2. **Click on "SQL" tab**

3. **Open the SQL file**
   - Open `database_schema.sql` in a text editor (Notepad++)
   - Copy all the contents (Ctrl+A, Ctrl+C)

4. **Paste into phpMyAdmin**
   - Paste into the SQL text area (Ctrl+V)
   - Click **"Go"** button

## Verification

After running the SQL file, verify the setup:

1. **Check Database**
   ```sql
   SHOW DATABASES;
   ```
   Should see `lorinims_db` in the list

2. **Check Tables**
   ```sql
   USE lorinims_db;
   SHOW TABLES;
   ```
   Should see all tables: users, products, raw_materials, etc.

3. **Check Sample Data**
   ```sql
   SELECT * FROM users;
   SELECT * FROM products;
   SELECT * FROM raw_materials;
   ```
   Should see sample records

## Troubleshooting

### Error: "Database already exists"
- If `lorinims_db` already exists, you can either:
  - Drop it first: `DROP DATABASE lorinims_db;` then run the schema again
  - Or modify the SQL file to remove the `CREATE DATABASE` line

### Error: "Table already exists"
- The SQL uses `CREATE TABLE IF NOT EXISTS` so it should be safe
- If you get errors, drop the existing database and recreate it

### Error: "Access denied"
- Make sure MySQL is running in XAMPP
- Check that you're using the correct username (usually `root`) and password

## Default Login Credentials

After running the schema, you can login with:

| Username | Password | Role |
|----------|----------|------|
| admin | admin123 | admin |
| production | prod123 | production |
| warehouse | ware123 | warehouse |
| qc | qc123 | qc |
| accounting | acc123 | accounting |
| sales | sales123 | sales |
| delivery | del123 | delivery |

## Next Steps

1. ✅ Database setup complete
2. ✅ Test login with default credentials
3. ✅ Start using the system!
