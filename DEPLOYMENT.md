# Dream Homes Realty - Deployment Guide

## Development vs Production Workflow

### 📁 File Organization

**Source Files (what you edit locally):**
- `assets/css/site.css` - Main stylesheet (readable, commented)
- `assets/css/admin.css` - Admin stylesheet (readable, commented)

**Production Files (auto-generated, uploaded to server):**
- `assets/css/site.min.css` - Minified main stylesheet (~30% smaller)
- `assets/css/admin.min.css` - Minified admin stylesheet (~30% smaller)

**Important:** Never edit `.min.css` files directly! They are auto-generated.

---

## 🚀 Deployment Workflow

### Step 1: Make Your Changes Locally
Edit your source CSS files normally:
- `assets/css/site.css`
- `assets/css/admin.css`

Keep them readable with comments and proper formatting.

### Step 2: Build Production Assets

**Option A - Using the build script (recommended):**
```bash
bash build.sh
```

**Option B - Manual minification:**
```bash
npx cssnano assets/css/site.css assets/css/site.min.css
npx cssnano assets/css/admin.css assets/css/admin.min.css
```

This creates the `.min.css` files in the same directory.

### Step 3: Upload to Server

Upload these files to your production server:
- `assets/css/site.min.css` ✅
- `assets/css/admin.min.css` ✅
- Any PHP files you changed ✅

**Do NOT upload the source `.css` files to production** (unless you want them available for debugging).

### Step 4: Set Production Environment

On your **production server**, add this to your `.htaccess` or server config:
```apache
SetEnv ENVIRONMENT production
```

Or create a `.env` file on the server:
```
ENVIRONMENT=production
```

This tells your site to automatically use `.min.css` files.

---

## 🔧 How It Works

### Local Development (Your Computer)
- `ENVIRONMENT=development` (default)
- Site uses `site.css` (readable source files)
- Make changes, refresh browser, see updates immediately
- No build step needed during active development

### Production Server (Live Website)
- `ENVIRONMENT=production` (set via .htaccess or .env)
- Site automatically uses `site.min.css` (optimized files)
- Faster load times for visitors
- Reduced bandwidth usage

---

## 📝 Updating Your PHP Files (Optional)

To use the automatic minification switching, update your PHP files:

**Before:**
```php
<link rel="stylesheet" href="assets/css/site.css">
```

**After:**
```php
<?php require_once __DIR__ . '/config.php'; ?>
<link rel="stylesheet" href="<?php echo get_css_path('assets/css/site.css'); ?>">
```

This automatically switches between `site.css` (dev) and `site.min.css` (prod).

---

## 🎯 Quick Reference

### When Working Locally:
1. Edit `site.css` directly
2. Refresh browser to see changes
3. No minification needed during development

### When Deploying to Production:
1. Run `bash build.sh`
2. Upload `*.min.css` files to server
3. Upload any changed PHP files
4. Clear browser cache and test

### Git Version Control:
- **Commit:** Source CSS files (`site.css`)
- **Ignore:** Minified files (`site.min.css`) - listed in `.gitignore`
- **Reason:** Minified files are build artifacts, regenerated from source

---

## 🌟 Benefits of This Workflow

✅ **Faster Website:** 25-30% smaller CSS files
✅ **Better SEO:** Improved page load speed
✅ **Readable Code:** Source files stay organized and commented
✅ **Version Control:** Only track source files, not generated files
✅ **Automatic:** Set environment once, switching is automatic

---

## 🛠️ Advanced: Adding More Build Steps

In the future, you could add:
- **Autoprefixer** - Add vendor prefixes automatically
- **PurgeCSS** - Remove unused CSS (can reduce size by 70%+)
- **Image optimization** - Compress images automatically
- **JS minification** - Minify JavaScript files
- **SCSS/SASS** - Use CSS preprocessors

For now, simple CSS minification gives you 80% of the benefits with minimal setup!
