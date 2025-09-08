# HTTPS/Mixed Content Fixes

## Issue
Your frontend (https://mu-connect-zeta.vercel.app) was trying to load HTTP resources from your backend, causing mixed content errors that browsers block for security reasons.

## Root Cause
- Asset URLs were being generated with HTTP instead of HTTPS
- Some models lacked proper URL accessors that handle HTTPS correctly
- No global HTTPS enforcement in production

## Fixed Files

### 1. **app/Models/User.php**
- ✅ Updated `getAvatarUrlAttribute()` to use `secure_asset()` in production
- ✅ Now generates HTTPS URLs for all avatar images

### 2. **app/Models/Event.php** 
- ✅ Added `getImageUrlAttribute()` accessor with HTTPS support
- ✅ Added `image_url` to `$appends` array for automatic inclusion in JSON

### 3. **app/Models/Club.php**
- ✅ Added `getLogoUrlAttribute()` accessor with HTTPS support  
- ✅ Added `logo_url` to `$appends` array for automatic inclusion in JSON

### 4. **app/Models/Attachment.php**
- ✅ Enhanced `getUrlAttribute()` to force HTTPS in production
- ✅ Handles both Storage URLs and external URLs properly

### 5. **app/Http/Controllers/NotificationController.php**
- ✅ Updated fallback avatar URL to use `secure_asset()` in production

### 6. **app/Providers/AppServiceProvider.php** 
- ✅ Added global HTTPS URL forcing with `URL::forceScheme('https')` in production
- ✅ This ensures all `asset()` calls generate HTTPS URLs in production

## How It Works

### Before (HTTP URLs)
```
http://mu-connect.onrender.com/storage/avatars/default.png
```

### After (HTTPS URLs)
```
https://mu-connect-backend.onrender.com/storage/avatars/default.png  
```

### Dynamic Detection
```php
// Automatically detects environment and uses appropriate protocol
if (app()->environment('production')) {
    return secure_asset($path);  // HTTPS
}
return asset($path);  // HTTP for local development
```

## Frontend Benefits
- ✅ No more mixed content warnings
- ✅ All images load properly over HTTPS
- ✅ Maintains HTTP for local development
- ✅ Automatic HTTPS enforcement in production

## Testing
1. Deploy these changes to your backend
2. Test avatar images in your frontend
3. Check browser console - no more mixed content errors
4. All images should now load over HTTPS

The fix is comprehensive and handles all asset URL generation throughout your application!
