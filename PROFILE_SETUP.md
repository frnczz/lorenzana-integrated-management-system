# Profile Page Setup Guide

This guide explains how to set up and use the user profile page in LORINIMS.

## Features

The profile page (`profile.php`) provides the following features for all users:

1. **Profile Overview**: Displays user information including:
   - Profile picture (initial-based avatar)
   - Full name
   - Username
   - Role
   - Email
   - Phone number (if set)
   - Address (if set)
   - Employee information (if linked to employee record)
   - Member since date

2. **Activity Statistics**: Shows user activity metrics:
   - Production batches created
   - Sales orders created
   - QC inspections done
   - Invoices created
   - Deliveries assigned (for delivery/driver roles)

3. **Update Profile**: Form to update:
   - Full name
   - Email address
   - Phone number
   - Address
   - Birth date

4. **Change Password**: Secure password change functionality with validation.

## Database Setup

### Option 1: New Database Installation

If you're creating a fresh database, the updated `database_schema.sql` already includes the new profile fields:
- `phone_number` VARCHAR(20)
- `address` TEXT
- `birth_date` DATE
- `profile_picture` VARCHAR(255)
- `last_login` TIMESTAMP

### Option 2: Update Existing Database

If you already have a database, run the `database_update_profile.sql` file to add the new columns:

```sql
-- Run these commands in MySQL
USE lorinims_db;

ALTER TABLE users ADD COLUMN phone_number VARCHAR(20) AFTER email;
ALTER TABLE users ADD COLUMN address TEXT AFTER phone_number;
ALTER TABLE users ADD COLUMN birth_date DATE AFTER address;
ALTER TABLE users ADD COLUMN profile_picture VARCHAR(255) AFTER birth_date;
ALTER TABLE users ADD COLUMN last_login TIMESTAMP NULL AFTER updated_at;
```

**Note**: If any column already exists, you'll get an error. You can ignore those errors or check first:

```sql
SHOW COLUMNS FROM users;
```

## Accessing the Profile Page

Users can access their profile page in two ways:

1. **From Header Dropdown**: Click on the user profile icon/name in the header, then select "👤 My Profile"
2. **From Sidebar**: Scroll to the bottom of the sidebar menu and click "👤 My Profile"

## Files Created/Modified

### New Files:
- `profile.php` - Main profile page
- `api/update_profile.php` - Handles profile updates
- `api/change_password.php` - Handles password changes
- `database_update_profile.sql` - Database migration script
- `PROFILE_SETUP.md` - This documentation file

### Modified Files:
- `database_schema.sql` - Updated users table schema
- `layouts/header.php` - Added profile link in dropdown
- `layouts/sidebar.php` - Added profile link in menu
- `login_process.php` - Added session variables and last_login tracking

## Security Features

1. **Authentication**: All profile endpoints require user authentication
2. **User Isolation**: Users can only update their own profile (not others')
3. **Password Validation**: 
   - Current password verification required
   - Minimum 6 characters for new password
   - Password confirmation match check
4. **Input Validation**:
   - Email format validation
   - Birth date cannot be in the future
   - Required fields validation

## Usage Instructions

### Updating Profile Information:

1. Navigate to the profile page
2. Scroll to the "Update Profile Information" section
3. Fill in the fields you want to update:
   - Full Name (required)
   - Email Address (optional)
   - Phone Number (optional)
   - Address (optional)
   - Birth Date (optional)
4. Click "Save Changes"
5. You'll see a success message if the update was successful

### Changing Password:

1. Navigate to the profile page
2. Scroll to the "Change Password" section
3. Enter your current password
4. Enter your new password (minimum 6 characters)
5. Confirm your new password
6. Click "Change Password"
7. You'll see a success message if the password was changed successfully

## Activity Statistics

The activity statistics automatically calculate based on:
- Production batches where `created_by` = user_id
- Sales orders where `created_by` = user_id
- QC records where `inspected_by` = user_id
- Invoices where `created_by` = user_id
- Delivery assignments where `driver_id` = user_id

These statistics update in real-time based on database records.

## Session Management

When a user logs in, the following information is stored in the session:
- `user_id` - User's ID
- `username` - Username
- `role` - User's role
- `full_name` - User's full name (or username if not set)
- `email` - User's email (if set)

When a user updates their profile, the session variables are automatically updated.

## Future Enhancements (Optional)

Potential future enhancements:
- Profile picture upload functionality
- Two-factor authentication
- Account activity log
- Password strength indicator
- Email verification
- Phone number verification

## Troubleshooting

### Profile page shows "Not set" for some fields:
- This is normal for optional fields that haven't been filled in yet
- Users can update these fields using the "Update Profile Information" form

### Can't update profile:
- Make sure you're logged in
- Check that required fields (Full Name) are filled in
- Verify email format is correct if email is provided
- Check for error messages displayed on the page

### Password change not working:
- Make sure current password is correct
- Ensure new password is at least 6 characters
- Verify password confirmation matches new password
- Check for error messages displayed on the page

## Support

For issues or questions, check:
- PHP error logs
- MySQL error logs
- Browser console for JavaScript errors
- Network tab in browser developer tools for API errors
