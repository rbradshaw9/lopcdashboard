# LOP Dashboard - Deployment Instructions

## Overview
You're using **WordPress Code Snippets plugin** to run these PHP shortcodes in Elementor pages. This requires a specific deployment process.

## CSS File Deployment (REQUIRED)

### Step 1: Upload CSS to Your Child Theme

1. **Via FTP or cPanel File Manager:**
   - Navigate to: `/wp-content/themes/YOUR-CHILD-THEME-NAME/`
   - Upload `lop-dashboard-styles.css` to this directory
   - Final path should be: `/wp-content/themes/YOUR-CHILD-THEME-NAME/lop-dashboard-styles.css`

2. **Via WordPress Theme Editor (Alternative):**
   - Go to: **Appearance → Theme File Editor**
   - Select your **Child Theme** (NOT the parent theme)
   - Click "Add New File"
   - Name it `lop-dashboard-styles.css`
   - Copy and paste the entire contents of your local `lop-dashboard-styles.css` file
   - Save

### Step 2: Verify CSS is Loading

After uploading, visit your dashboard page and check the browser console:
- CSS should load from: `https://members.liveonpurposecentral.com/wp-content/themes/YOUR-THEME/lop-dashboard-styles.css`
- No MIME type errors should appear

## PHP Shortcode Deployment

### For Dashboard (Main Page):

1. **WordPress Admin → Snippets → Add New**
2. **Title:** "LOP Ultimate Dashboard Shortcode"
3. **Code:** Copy entire contents of `function lop_ultimate_dashboard_shortcod.php`
4. **Type:** Select "PHP Snippet"
5. **Activate:** Turn on the snippet
6. **Shortcode created:** `[lop_apple_dashboard]`

### For Profile Management:

1. **WordPress Admin → Snippets → Add New**
2. **Title:** "LOP Profile Management Shortcode"
3. **Code:** Copy entire contents of `profile-management-page.php`
4. **Type:** Select "PHP Snippet"  
5. **Activate:** Turn on the snippet
6. **Shortcode created:** `[lop_profile_management]`

### For Achievements Page:

1. **WordPress Admin → Snippets → Add New**
2. **Title:** "LOP Achievements Page Shortcode"
3. **Code:** Copy entire contents of `gamipress-achievements-page.php`
4. **Type:** Select "PHP Snippet"
5. **Activate:** Turn on the snippet
6. **Shortcode created:** `[lop_achievements_page]`

## Using Shortcodes in Elementor

### In Any Elementor Page:

1. **Edit page with Elementor**
2. **Add "Shortcode" widget** (drag from left panel)
3. **Enter shortcode:**
   - For Dashboard: `[lop_apple_dashboard]`
   - For Profile: `[lop_profile_management]`
   - For Achievements: `[lop_achievements_page]`
4. **Publish/Update** the page

## Important Notes

### CSS File Location
- ✅ **Correct:** `/wp-content/themes/YOUR-CHILD-THEME/lop-dashboard-styles.css`
- ❌ **Wrong:** Plugin directory (won't work with Code Snippets)
- ❌ **Wrong:** Parent theme (gets overwritten on theme updates)

### Child Theme
- **Always use a child theme** to prevent CSS loss during theme updates
- If you don't have a child theme, create one first
- [How to create a child theme](https://developer.wordpress.org/themes/advanced-topics/child-themes/)

### Cache Busting
- After uploading CSS changes, increment the version number in all three PHP files
- Change `'1.0.1'` to `'1.0.2'` etc. to force browsers to reload CSS
- Or clear your site's cache (if using a caching plugin)

### Updating Code
When you make changes locally:
1. Update the local `.php` file
2. Copy the entire updated code
3. Edit the snippet in **WordPress Admin → Snippets**
4. Replace the code
5. Save the snippet

When you update CSS:
1. Update local `lop-dashboard-styles.css` 
2. Upload to child theme (replace existing file)
3. Increment version number in PHP files (`'1.0.1'` → `'1.0.2'`)
4. Update snippets with new version number
5. Clear site cache

## Troubleshooting

### CSS Not Loading (MIME Type Error)
**Problem:** Console shows "MIME type ('text/html') is not a supported stylesheet"  
**Solution:** CSS file is in wrong location or path is incorrect
- Verify file is in child theme directory
- Check that child theme is active
- Verify URL in browser: `https://yoursite.com/wp-content/themes/YOUR-CHILD-THEME/lop-dashboard-styles.css`

### Shortcode Not Working
**Problem:** Shortcode displays as text instead of rendering  
**Solution:** 
- Verify snippet is activated in Code Snippets
- Check for PHP errors in the snippet
- Make sure you're using the Elementor "Shortcode" widget, not text/code block

### Styles Look Wrong
**Problem:** Dashboard renders but styling is broken  
**Solution:**
- CSS file not loaded - check browser console
- Increment CSS version number to bust cache
- Clear WordPress cache, browser cache, and CDN cache

### PHP Errors
**Problem:** White screen or error messages  
**Solution:**
- Check PHP error logs in cPanel or hosting dashboard
- Verify all required plugins are active (LearnDash, Memberium, GamiPress)
- Make sure you copied the ENTIRE PHP file (including opening `<?php`)

## Site Specific Info

**Your Site:** members.liveonpurposecentral.com  
**Current Theme:** (Check in Appearance → Themes)  
**Child Theme Path:** `/wp-content/themes/YOUR-CHILD-THEME-NAME/`

**Required Plugins:**
- ✅ Code Snippets (for running PHP shortcodes)
- ✅ LearnDash (for course management)
- ✅ Memberium (for CRM integration)
- ✅ GamiPress (for achievements - optional)
- ✅ Elementor (for page building)

## Quick Checklist

Before going live:
- [ ] CSS file uploaded to child theme directory
- [ ] All three PHP snippets created and activated
- [ ] Shortcodes added to Elementor pages
- [ ] Browser console shows no CSS errors
- [ ] Test on desktop and mobile
- [ ] Clear all caches
- [ ] Test with actual user account

## Support

If you encounter issues:
1. Check browser console for errors (F12 → Console tab)
2. Verify all files are in correct locations
3. Ensure all required plugins are active
4. Check PHP error logs
5. Try incrementing CSS version number
6. Clear all caches
