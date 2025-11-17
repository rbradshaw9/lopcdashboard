# 📢 Dashboard Announcements System - Complete Setup Guide

## 🎯 Overview

The announcements system allows admins to display targeted messages at the top of the student dashboard. It uses:
- **WordPress Custom Post Type** for easy management
- **Memberium Protection** for audience targeting (who sees what)
- **Custom Fields** for announcement types and CTAs
- **Automatic Display** based on user access

---

## 📋 Setup Instructions

### **Step 1: Register the Custom Post Type**

Add this code to your theme's `functions.php` or a custom plugin:

```php
/**
 * Register Dashboard Announcements Custom Post Type
 */
function lop_register_announcements_post_type() {
    $labels = array(
        'name'                  => 'Dashboard Announcements',
        'singular_name'         => 'Announcement',
        'menu_name'             => 'Announcements',
        'add_new'               => 'Add New',
        'add_new_item'          => 'Add New Announcement',
        'edit_item'             => 'Edit Announcement',
        'new_item'              => 'New Announcement',
        'view_item'             => 'View Announcement',
        'search_items'          => 'Search Announcements',
        'not_found'             => 'No announcements found',
        'not_found_in_trash'    => 'No announcements found in Trash',
        'all_items'             => 'All Announcements',
    );

    $args = array(
        'labels'                => $labels,
        'public'                => false, // Not shown on front-end
        'show_ui'               => true,
        'show_in_menu'          => true,
        'menu_icon'             => 'dashicons-megaphone',
        'menu_position'         => 25,
        'capability_type'       => 'post',
        'hierarchical'          => false,
        'supports'              => array( 'title', 'editor', 'author', 'revisions' ),
        'has_archive'           => false,
        'rewrite'               => false,
    );

    register_post_type( 'dashboard_announcement', $args );
}
add_action( 'init', 'lop_register_announcements_post_type' );
```

**Save and refresh WordPress admin** - you should now see "Announcements" in the sidebar!

---

### **Step 2: Add Custom Fields (Meta Boxes)**

Add this code after Step 1:

```php
/**
 * Add Meta Boxes for Announcement Settings
 */
function lop_add_announcement_meta_boxes() {
    add_meta_box(
        'announcement_settings',
        'Announcement Settings',
        'lop_announcement_settings_callback',
        'dashboard_announcement',
        'side',
        'default'
    );
}
add_action( 'add_meta_boxes', 'lop_add_announcement_meta_boxes' );

/**
 * Meta Box Callback - Announcement Type and CTA
 */
function lop_announcement_settings_callback( $post ) {
    wp_nonce_field( 'announcement_settings_nonce', 'announcement_settings_nonce' );
    
    $type = get_post_meta( $post->ID, 'announcement_type', true );
    $cta_text = get_post_meta( $post->ID, 'cta_text', true );
    $cta_link = get_post_meta( $post->ID, 'cta_link', true );
    ?>
    
    <p>
        <label for="announcement_type"><strong>Announcement Type:</strong></label><br>
        <select name="announcement_type" id="announcement_type" style="width: 100%; margin-top: 5px;">
            <option value="info" <?php selected( $type, 'info' ); ?>>📢 Info</option>
            <option value="event" <?php selected( $type, 'event' ); ?>>📅 Event</option>
            <option value="offer" <?php selected( $type, 'offer' ); ?>>🎉 Special Offer</option>
            <option value="alert" <?php selected( $type, 'alert' ); ?>>⚠️ Alert</option>
        </select>
    </p>

    <hr>

    <p>
        <label for="cta_text"><strong>Call to Action (Optional):</strong></label><br>
        <input type="text" name="cta_text" id="cta_text" value="<?php echo esc_attr( $cta_text ); ?>" placeholder="e.g., Register Now" style="width: 100%; margin-top: 5px;">
    </p>

    <p>
        <label for="cta_link"><strong>CTA Link:</strong></label><br>
        <input type="url" name="cta_link" id="cta_link" value="<?php echo esc_url( $cta_link ); ?>" placeholder="https://..." style="width: 100%; margin-top: 5px;">
    </p>

    <p style="color: #666; font-size: 12px; margin-top: 15px;">
        <strong>Tip:</strong> Use Memberium protection settings (below) to control who sees this announcement.
    </p>
    <?php
}

/**
 * Save Meta Box Data
 */
function lop_save_announcement_meta( $post_id ) {
    // Security checks
    if ( ! isset( $_POST['announcement_settings_nonce'] ) ) {
        return;
    }
    if ( ! wp_verify_nonce( $_POST['announcement_settings_nonce'], 'announcement_settings_nonce' ) ) {
        return;
    }
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    // Save fields
    if ( isset( $_POST['announcement_type'] ) ) {
        update_post_meta( $post_id, 'announcement_type', sanitize_text_field( $_POST['announcement_type'] ) );
    }
    
    if ( isset( $_POST['cta_text'] ) ) {
        update_post_meta( $post_id, 'cta_text', sanitize_text_field( $_POST['cta_text'] ) );
    }
    
    if ( isset( $_POST['cta_link'] ) ) {
        update_post_meta( $post_id, 'cta_link', esc_url_raw( $_POST['cta_link'] ) );
    }
}
add_action( 'save_post_dashboard_announcement', 'lop_save_announcement_meta' );
```

---

## 📝 How to Create an Announcement (Admin Workflow)

### **Step 1: Create the Announcement**

1. Go to **WordPress Admin** → **Announcements** → **Add New**
2. Enter **Title**: "Webinar This Friday - Advanced Marketing"
3. Enter **Content**: Full announcement message
4. Choose **Type**: Event (📅)
5. Add **CTA** (optional):
   - Text: "Register Now"
   - Link: https://yoursite.com/register

### **Step 2: Set Memberium Protection**

Scroll down to **Memberium Content Protection** meta box:

```
┌─────────────────────────────────────────┐
│ Memberium Content Protection            │
├─────────────────────────────────────────┤
│ Protect this post with:                 │
│                                         │
│ Required Tags:                          │
│ ☑ bronze-member                         │
│ ☑ silver-member                         │
│ ☐ gold-member                           │
│ ☐ platinum-member                       │
│                                         │
│ Or Required Membership Level:           │
│ [ ] Level 1                             │
│ [ ] Level 2                             │
└─────────────────────────────────────────┘
```

**Select who can see this:**
- Bronze + Silver members = Check those tags
- All members = Leave empty or check all
- Gold only = Check gold tag

### **Step 3: Publish**

Click **Publish** - Done! 

The announcement will immediately show for users with matching tags.

---

## 🎨 Announcement Types & Styling

### **Info (📢)**
- Blue accent
- General information
- Updates, reminders

### **Event (📅)**
- Purple accent
- Webinars, meetups
- Training sessions

### **Offer (🎉)**
- Orange accent
- Sales, promotions
- Limited-time deals

### **Alert (⚠️)**
- Red accent
- Urgent messages
- System updates

---

## 🔧 Advanced Features

### **Schedule Announcements**

Use WordPress's built-in scheduling:
1. Click "Edit" next to "Publish immediately"
2. Set future date/time
3. Click "Schedule"

### **Priority Ordering**

To control order of announcements:
1. Install **Post Types Order** plugin (free)
2. Drag announcements to reorder
3. Top = shows first

**Or manually:**
- Edit announcement
- Screen Options → Enable "Page Attributes"
- Set "Order" number (lower = higher priority)

### **Temporary Announcements**

For time-limited messages:
1. Create announcement
2. Set publish date = start date
3. After end date, move to trash

Or use **Post Expirator** plugin for automatic removal.

---

## 👥 Example Use Cases

### **Membership-Specific Offers**

```
Title: "Exclusive Gold Member Webinar"
Content: "Join our private mastermind session..."
Type: Event
Memberium: Require "gold-member" tag
CTA: "RSVP Now" → https://zoom.us/...
```

### **Site-Wide Announcements**

```
Title: "New Courses Added!"
Content: "We just released 3 new courses..."
Type: Info
Memberium: No protection (all members see it)
CTA: "Explore Courses" → /courses
```

### **Urgent Alerts**

```
Title: "System Maintenance Tonight"
Content: "The platform will be offline from 2-4 AM..."
Type: Alert
Memberium: No protection
CTA: None
```

---

## 🎯 What Students See

### **If they have access:**

```
┌────────────────────────────────────────────────────┐
│ 📅 Upcoming Webinar - This Friday at 2 PM         │
│                                                    │
│ Join us for an exclusive training on advanced     │
│ marketing strategies. Limited seats available!    │
│                                                    │
│                          [Register Now →]         │
│                                                    │
│ 🕐 2 hours ago                                    │
└────────────────────────────────────────────────────┘
```

### **If NO announcements match:**

Nothing displays - clean dashboard!

---

## ✅ Testing Checklist

1. **Create test announcement** with "test-tag" requirement
2. **View dashboard** without tag → Should NOT see it
3. **Add tag** to your test user in Memberium
4. **Refresh dashboard** → Should see announcement
5. **Test CTA button** → Should go to correct URL
6. **Test multiple announcements** → Should stack properly
7. **Trash announcement** → Should disappear immediately

---

## 🆘 Troubleshooting

### **"Announcements menu not showing"**
- Make sure you added code to `functions.php`
- Refresh admin page (Ctrl+F5)
- Check for PHP errors in debug log

### **"Announcement shows to wrong users"**
- Verify Memberium protection settings
- Test `memb_hasPostAccess()` function exists
- Check user actually has required tags in Keap/Memberium

### **"CTA button not showing"**
- Make sure BOTH CTA text and link are filled
- Check for typos in meta field names

### **"Styling looks broken"**
- Clear browser cache
- Check theme doesn't override `.lop-announcement-*` classes
- Verify dashboard CSS is loading

---

## 📊 Summary

**What You Get:**
- ✅ Easy-to-use announcement management
- ✅ Memberium-powered targeting
- ✅ Beautiful, modern design
- ✅ Optional call-to-action buttons
- ✅ Automatic display based on access
- ✅ Clean dashboard when no announcements

**Admin Experience:**
1. Create like a blog post
2. Set Memberium protection
3. Publish
4. Done!

**Student Experience:**
- See only relevant announcements
- Clean, non-intrusive cards
- Clear call-to-actions
- Mobile-responsive

---

## 🚀 You're Ready!

Add the code, create your first announcement, and watch it appear on the dashboard. Any questions? The system is now fully integrated with your existing Memberium setup!
