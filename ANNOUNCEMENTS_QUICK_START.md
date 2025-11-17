# 🎯 Quick Start - Announcements in 5 Minutes

## ⚡ Super Fast Setup

### 1️⃣ Add This Code (functions.php)

Copy/paste this entire block into your theme's `functions.php`:

```php
// Register Announcements Post Type
function lop_register_announcements_post_type() {
    register_post_type( 'dashboard_announcement', array(
        'labels' => array(
            'name' => 'Announcements',
            'singular_name' => 'Announcement',
            'add_new_item' => 'Add New Announcement',
        ),
        'public' => false,
        'show_ui' => true,
        'menu_icon' => 'dashicons-megaphone',
        'supports' => array( 'title', 'editor' ),
    ));
}
add_action( 'init', 'lop_register_announcements_post_type' );

// Add Settings Meta Box
function lop_add_announcement_meta_boxes() {
    add_meta_box('announcement_settings', 'Settings', 'lop_announcement_settings_cb', 'dashboard_announcement', 'side');
}
add_action( 'add_meta_boxes', 'lop_add_announcement_meta_boxes' );

function lop_announcement_settings_cb( $post ) {
    wp_nonce_field( 'ann_nonce', 'ann_nonce' );
    $type = get_post_meta( $post->ID, 'announcement_type', true ) ?: 'info';
    $cta_text = get_post_meta( $post->ID, 'cta_text', true );
    $cta_link = get_post_meta( $post->ID, 'cta_link', true );
    ?>
    <p><strong>Type:</strong><br>
    <select name="announcement_type" style="width:100%">
        <option value="info" <?php selected($type,'info'); ?>>📢 Info</option>
        <option value="event" <?php selected($type,'event'); ?>>📅 Event</option>
        <option value="offer" <?php selected($type,'offer'); ?>>🎉 Offer</option>
        <option value="alert" <?php selected($type,'alert'); ?>>⚠️ Alert</option>
    </select></p>
    <p><strong>CTA Button (optional):</strong><br>
    <input type="text" name="cta_text" value="<?php echo esc_attr($cta_text); ?>" placeholder="Register Now" style="width:100%"><br>
    <input type="url" name="cta_link" value="<?php echo esc_url($cta_link); ?>" placeholder="https://..." style="width:100%; margin-top:5px"></p>
    <?php
}

function lop_save_announcement_meta( $post_id ) {
    if ( !isset($_POST['ann_nonce']) || !wp_verify_nonce($_POST['ann_nonce'], 'ann_nonce') ) return;
    if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return;
    if ( !current_user_can('edit_post', $post_id) ) return;
    
    if (isset($_POST['announcement_type'])) update_post_meta($post_id, 'announcement_type', sanitize_text_field($_POST['announcement_type']));
    if (isset($_POST['cta_text'])) update_post_meta($post_id, 'cta_text', sanitize_text_field($_POST['cta_text']));
    if (isset($_POST['cta_link'])) update_post_meta($post_id, 'cta_link', esc_url_raw($_POST['cta_link']));
}
add_action( 'save_post_dashboard_announcement', 'lop_save_announcement_meta' );
```

**Save file. Done!**

---

### 2️⃣ Create Your First Announcement

1. WordPress Admin → **Announcements** → **Add New**
2. Title: `"Webinar This Friday"`
3. Content: `"Join us for special training..."`
4. Sidebar → **Settings**:
   - Type: 📅 Event
   - CTA: "Register" → https://zoom.us/...
5. Sidebar → **Memberium Protection**:
   - Check tags who should see it
6. Click **Publish**

---

### 3️⃣ Test It

1. View dashboard → See announcement at top!
2. No announcement? Check Memberium tags match

---

## 🎨 Announcement Types

```
📢 Info    → Blue    → Updates, reminders
📅 Event   → Purple  → Webinars, meetups  
🎉 Offer   → Orange  → Sales, promotions
⚠️ Alert   → Red     → Urgent messages
```

---

## 💡 Pro Tips

**Multiple Announcements**
- They stack automatically
- Most recent first
- All show at once

**Hide from Everyone**
- Set Memberium protection to non-existent tag
- Or save as Draft

**Show to Everyone**
- Don't set any Memberium protection
- All members see it

**Remove Announcement**
- Move to Trash
- Disappears immediately

---

## 📱 What It Looks Like

```
┌─────────────────────────────────────────┐
│ 📅 Webinar This Friday at 2 PM         │
│                                         │
│ Join us for an exclusive training on   │
│ advanced marketing strategies.          │
│                                         │
│                  [Register Now →]       │
└─────────────────────────────────────────┘
```

Clean, professional, mobile-responsive!

---

## ✅ Done!

That's it. Create announcements like blog posts, protect with Memberium, publish. Easy!
