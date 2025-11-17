<?php
/**
 * Profile Management Page
 * Professional user profile management with Apple-inspired design
 * 
 * Usage: [lop_profile_management]
 */

function lop_profile_management_shortcode() {
    // Check if user is logged in
    if ( ! is_user_logged_in() ) {
        return '<div class="lop-login-prompt" style="text-align: center; padding: 3rem; color: #86868b; font-family: -apple-system, BlinkMacSystemFont, sans-serif; background: #f5f5f7; border-radius: 12px; margin: 1rem 0;">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin-bottom: 1rem; opacity: 0.5;">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                <circle cx="12" cy="7" r="4"></circle>
            </svg>
            <h3 style="margin-bottom: 1rem; color: #1d1d1f;">Please log in to manage your profile</h3>
            <p style="margin-bottom: 2rem;">Access your account settings and personal information.</p>
            <a href="' . wp_login_url( get_permalink() ) . '" style="background: #007AFF; color: white; padding: 0.75rem 2rem; border-radius: 8px; text-decoration: none; font-weight: 600;">Log In</a>
        </div>';
    }

    $user_id = get_current_user_id();
    $user = get_userdata( $user_id );
    $user_avatar = get_avatar_url( $user_id, array( 'size' => 120 ) );
    
    // Handle form submissions
    $success_message = '';
    $error_message = '';
    
    if ( $_SERVER['REQUEST_METHOD'] === 'POST' && wp_verify_nonce( $_POST['profile_nonce'], 'update_profile_' . $user_id ) ) {
        
        // Handle personal details update
        if ( isset( $_POST['action'] ) && $_POST['action'] === 'update_personal_details' ) {
            $update_data = array();
            $update_data['ID'] = $user_id;
            
            // Validate and sanitize inputs
            if ( ! empty( $_POST['first_name'] ) ) {
                $update_data['first_name'] = sanitize_text_field( $_POST['first_name'] );
            }
            
            if ( ! empty( $_POST['last_name'] ) ) {
                $update_data['last_name'] = sanitize_text_field( $_POST['last_name'] );
            }
            
            if ( ! empty( $_POST['display_name'] ) ) {
                $update_data['display_name'] = sanitize_text_field( $_POST['display_name'] );
            }
            
            if ( ! empty( $_POST['user_email'] ) && is_email( $_POST['user_email'] ) ) {
                $update_data['user_email'] = sanitize_email( $_POST['user_email'] );
            } else if ( ! empty( $_POST['user_email'] ) ) {
                $error_message = 'Please enter a valid email address.';
            }
            
            if ( ! empty( $_POST['description'] ) ) {
                $update_data['description'] = sanitize_textarea_field( $_POST['description'] );
            }
            
            // Update user data
            if ( empty( $error_message ) ) {
                $result = wp_update_user( $update_data );
                if ( ! is_wp_error( $result ) ) {
                    // Try to sync with Memberium/Infusionsoft
                    if ( function_exists( 'memb_updateContact' ) ) {
                        try {
                            $contact_id = get_user_meta( $user_id, 'memb_contact_id', true );
                            if ( $contact_id ) {
                                $infusionsoft_data = array();
                                if ( ! empty( $_POST['first_name'] ) ) {
                                    $infusionsoft_data['FirstName'] = sanitize_text_field( $_POST['first_name'] );
                                }
                                if ( ! empty( $_POST['last_name'] ) ) {
                                    $infusionsoft_data['LastName'] = sanitize_text_field( $_POST['last_name'] );
                                }
                                if ( ! empty( $_POST['user_email'] ) && is_email( $_POST['user_email'] ) ) {
                                    $infusionsoft_data['Email'] = sanitize_email( $_POST['user_email'] );
                                }
                                
                                if ( ! empty( $infusionsoft_data ) ) {
                                    memb_updateContact( $contact_id, $infusionsoft_data );
                                    $success_message = 'Profile updated successfully and synced with Infusionsoft!';
                                } else {
                                    $success_message = 'Profile updated successfully!';
                                }
                            } else {
                                $success_message = 'Profile updated successfully!';
                            }
                        } catch ( Exception $e ) {
                            // Log error but still show success for WordPress update
                            error_log( 'Memberium profile sync failed: ' . $e->getMessage() );
                            $success_message = 'Profile updated successfully! (Note: Infusionsoft sync unavailable)';
                        }
                    } else {
                        $success_message = 'Profile updated successfully!';
                    }
                    
                    // Refresh user data
                    $user = get_userdata( $user_id );
                } else {
                    $error_message = 'Failed to update profile: ' . $result->get_error_message();
                }
            }
        }
        
        // Handle password change
        if ( isset( $_POST['action'] ) && $_POST['action'] === 'change_password' ) {
            $current_password = $_POST['current_password'];
            $new_password = $_POST['new_password'];
            $confirm_password = $_POST['confirm_password'];
            
            // Validate current password
            if ( ! wp_check_password( $current_password, $user->user_pass, $user_id ) ) {
                $error_message = 'Current password is incorrect.';
            } else if ( strlen( $new_password ) < 8 ) {
                $error_message = 'New password must be at least 8 characters long.';
            } else if ( $new_password !== $confirm_password ) {
                $error_message = 'New passwords do not match.';
            } else {
                // Update password
                wp_set_password( $new_password, $user_id );
                
                // Try to update password in Memberium/Infusionsoft if available
                if ( function_exists( 'memb_updateContact' ) ) {
                    try {
                        // Get Infusionsoft contact ID
                        $contact_id = get_user_meta( $user_id, 'memb_contact_id', true );
                        if ( $contact_id ) {
                            memb_updateContact( $contact_id, array( 'Password' => $new_password ) );
                        }
                    } catch ( Exception $e ) {
                        // Log error but don't show to user
                        error_log( 'Memberium password sync failed: ' . $e->getMessage() );
                    }
                }
                
                $success_message = 'Password changed successfully!';
            }
        }
    }
    
    // Get additional profile data
    $memberium_data = array();
    if ( function_exists( 'memb_getContactData' ) ) {
        $contact_id = get_user_meta( $user_id, 'memb_contact_id', true );
        if ( $contact_id ) {
            try {
                $memberium_data = memb_getContactData( $contact_id );
            } catch ( Exception $e ) {
                // Memberium data not available
            }
        }
    }
    
    // Get LearnDash data
    $learndash_data = array();
    if ( function_exists( 'learndash_user_get_enrolled_courses' ) ) {
        $enrolled_courses = learndash_user_get_enrolled_courses( $user_id );
        $learndash_data['total_courses'] = count( $enrolled_courses );
        
        // Calculate completed courses
        $completed_courses = 0;
        foreach ( $enrolled_courses as $course_id ) {
            if ( function_exists( 'learndash_course_completed' ) && learndash_course_completed( $user_id, $course_id ) ) {
                $completed_courses++;
            }
        }
        $learndash_data['completed_courses'] = $completed_courses;
    }
    
    // Get GamiPress data
    $gamipress_data = array();
    if ( function_exists( 'gamipress_get_user_points' ) && function_exists( 'gamipress_get_points_types' ) ) {
        $total_points = 0;
        $all_point_types = gamipress_get_points_types();
        
        foreach ( $all_point_types as $point_type_slug => $point_type ) {
            $points = gamipress_get_user_points( $user_id, $point_type_slug );
            if ( $points > 0 ) {
                $total_points += $points;
            }
        }
        
        $gamipress_data['total_points'] = $total_points;
        
        // Get achievements count
        if ( function_exists( 'gamipress_get_user_achievements' ) ) {
            $achievements = gamipress_get_user_achievements( array(
                'user_id' => $user_id,
                'achievement_type' => '',
                'orderby' => 'date_earned',
                'order' => 'DESC'
            ) );
            $gamipress_data['total_achievements'] = count( $achievements );
        }
    }

    // Enqueue CSS from child theme directory (works with Code Snippets)
    // Upload lop-dashboard-styles.css to: /wp-content/themes/YOUR-CHILD-THEME/lop-dashboard-styles.css
    wp_enqueue_style( 
        'lop-dashboard-styles', 
        get_stylesheet_directory_uri() . '/lop-dashboard-styles.css', 
        array(), 
        '1.0.1' // Increment when CSS changes to bust cache
    );
    
    ob_start();
    ?>

    <div class="lop-profile-page">
        <!-- Hero Section -->
        <div class="lop-hero-section">
            <h1 class="lop-hero-title">Profile Management</h1>
            <p class="lop-hero-subtitle">Manage your account settings, personal information, and preferences</p>
            
            <!-- Profile Header -->
            <div class="lop-profile-header">
                <img src="<?php echo esc_url( $user_avatar ); ?>" alt="Profile Avatar" class="lop-profile-avatar">
                <div class="lop-profile-info">
                    <h1><?php echo esc_html( $user->display_name ); ?></h1>
                    <div class="lop-profile-email"><?php echo esc_html( $user->user_email ); ?></div>
                    <div class="lop-profile-stats">
                        <?php if ( ! empty( $learndash_data ) ) : ?>
                        <div class="lop-profile-stat">
                            <div class="lop-profile-stat-number"><?php echo intval( $learndash_data['completed_courses'] ?? 0 ); ?></div>
                            <div class="lop-profile-stat-label">Completed</div>
                        </div>
                        <div class="lop-profile-stat">
                            <div class="lop-profile-stat-number"><?php echo intval( $learndash_data['total_courses'] ?? 0 ); ?></div>
                            <div class="lop-profile-stat-label">Enrolled</div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ( ! empty( $gamipress_data['total_points'] ) ) : ?>
                        <div class="lop-profile-stat">
                            <div class="lop-profile-stat-number"><?php echo intval( $gamipress_data['total_points'] ); ?></div>
                            <div class="lop-profile-stat-label">Points</div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ( ! empty( $gamipress_data['total_achievements'] ) ) : ?>
                        <div class="lop-profile-stat">
                            <div class="lop-profile-stat-number"><?php echo intval( $gamipress_data['total_achievements'] ); ?></div>
                            <div class="lop-profile-stat-label">Achievements</div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <?php if ( $success_message ) : ?>
            <div class="lop-alert lop-alert-success">
                <strong>Success!</strong> <?php echo esc_html( $success_message ); ?>
            </div>
        <?php endif; ?>

        <?php if ( $error_message ) : ?>
            <div class="lop-alert lop-alert-error">
                <strong>Error!</strong> <?php echo esc_html( $error_message ); ?>
            </div>
        <?php endif; ?>

                <!-- Two Column Layout -->
        <div class="lop-two-column-layout">
            <!-- Left Column: Forms -->
            <div class="lop-left-column">
                <!-- Personal Details Section -->
                <div class="lop-section">
                    <h2 class="lop-section-title">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                        Personal Details
                    </h2>
            
            <form method="post" action="">
                <?php wp_nonce_field( 'update_profile_' . $user_id, 'profile_nonce' ); ?>
                <input type="hidden" name="action" value="update_personal_details">
                
                <div class="lop-form-grid">
                    <div class="lop-form-group">
                        <label class="lop-form-label" for="first_name">First Name</label>
                        <input type="text" id="first_name" name="first_name" class="lop-form-input" 
                               value="<?php echo esc_attr( $user->first_name ); ?>">
                    </div>
                    
                    <div class="lop-form-group">
                        <label class="lop-form-label" for="last_name">Last Name</label>
                        <input type="text" id="last_name" name="last_name" class="lop-form-input" 
                               value="<?php echo esc_attr( $user->last_name ); ?>">
                    </div>
                    
                    <div class="lop-form-group">
                        <label class="lop-form-label" for="display_name">Display Name</label>
                        <input type="text" id="display_name" name="display_name" class="lop-form-input" 
                               value="<?php echo esc_attr( $user->display_name ); ?>">
                    </div>
                    
                    <div class="lop-form-group">
                        <label class="lop-form-label" for="user_email">Email Address</label>
                        <input type="email" id="user_email" name="user_email" class="lop-form-input" 
                               value="<?php echo esc_attr( $user->user_email ); ?>">
                    </div>
                </div>
                
                <div class="lop-form-group">
                    <label class="lop-form-label" for="description">Bio</label>
                    <textarea id="description" name="description" class="lop-form-textarea" 
                              placeholder="Tell us a bit about yourself..."><?php echo esc_textarea( $user->description ); ?></textarea>
                </div>
                
                <button type="submit" class="lop-btn lop-btn-primary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                        <polyline points="17,21 17,13 7,13 7,21"></polyline>
                        <polyline points="7,3 7,8 15,8"></polyline>
                    </svg>
                    Save Changes
                </button>
            </form>
                </div>

                <!-- Account Security Section -->
                <div class="lop-section">
            <h2 class="lop-section-title">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                    <circle cx="12" cy="16" r="1"></circle>
                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                </svg>
                Account Security
            </h2>
            
            <form method="post" action="">
                <?php wp_nonce_field( 'update_profile_' . $user_id, 'profile_nonce' ); ?>
                <input type="hidden" name="action" value="change_password">
                
                <div class="lop-form-grid">
                    <div class="lop-form-group">
                        <label class="lop-form-label" for="current_password">Current Password</label>
                        <input type="password" id="current_password" name="current_password" class="lop-form-input" required>
                    </div>
                    
                    <div class="lop-form-group">
                        <label class="lop-form-label" for="new_password">New Password</label>
                        <input type="password" id="new_password" name="new_password" class="lop-form-input" 
                               minlength="8" required>
                        <small style="color: var(--lop-gray-500); font-size: 0.875rem;">Minimum 8 characters</small>
                    </div>
                    
                    <div class="lop-form-group">
                        <label class="lop-form-label" for="confirm_password">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="lop-form-input" 
                               minlength="8" required>
                    </div>
                </div>
                
                <button type="submit" class="lop-btn lop-btn-primary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                    </svg>
                    Update Password
                </button>
                
                <?php if ( function_exists( 'memb_updateContact' ) ) : ?>
                <p style="color: var(--lop-gray-500); font-size: 0.875rem; margin-top: var(--lop-space-md);">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-right: 4px;">
                        <circle cx="12" cy="12" r="10"></circle>
                        <path d="M12 6v6l4 2"></path>
                    </svg>
                    Your password will be automatically synced with Infusionsoft.
                </p>
                <?php endif; ?>
                </form>
                </div>
            </div>
            
            <!-- Right Column: Information & Stats -->
            <div class="lop-right-column">
                <!-- Account Information Section -->
                <div class="lop-section-compact">
                    <h2 class="lop-section-title">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="3"></circle>
                            <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1 1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path>
                        </svg>
                        Account Information
                    </h2>
                    
                    <div class="lop-info-card">
                        <div class="lop-info-card-label">Username</div>
                        <div class="lop-info-card-value"><?php echo esc_html( $user->user_login ); ?></div>
                        <div class="lop-info-card-note">Username cannot be changed</div>
                    </div>
                    
                    <div class="lop-info-card">
                        <div class="lop-info-card-label">Member Since</div>
                        <div class="lop-info-card-value"><?php echo date( 'F j, Y', strtotime( $user->user_registered ) ); ?></div>
                    </div>
                    
                    <?php if ( ! empty( $memberium_data ) && isset( $memberium_data['ContactId'] ) ) : ?>
                    <div class="lop-info-card">
                        <div class="lop-info-card-label">Infusionsoft Contact ID</div>
                        <div class="lop-info-card-value"><?php echo esc_html( $memberium_data['ContactId'] ); ?></div>
                        <div class="lop-info-card-note">Synced with Infusionsoft CRM</div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="lop-info-card">
                        <div class="lop-info-card-label">User ID</div>
                        <div class="lop-info-card-value"><?php echo esc_html( $user_id ); ?></div>
                    </div>
                </div>
                
                <!-- Learning Progress Section -->
                <?php if ( ! empty( $learndash_data ) || ! empty( $gamipress_data ) ) : ?>
                <div class="lop-section-compact">
                    <h2 class="lop-section-title">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path>
                            <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path>
                        </svg>
                        Learning Progress
                    </h2>
                    
                    <?php if ( ! empty( $learndash_data ) ) : ?>
                    <div class="lop-info-card">
                        <div class="lop-info-card-label">Courses Completed</div>
                        <div class="lop-info-card-value"><?php echo intval( $learndash_data['completed_courses'] ?? 0 ); ?> of <?php echo intval( $learndash_data['total_courses'] ?? 0 ); ?></div>
                        <?php 
                        $completion_rate = $learndash_data['total_courses'] > 0 ? round(($learndash_data['completed_courses'] / $learndash_data['total_courses']) * 100) : 0;
                        ?>
                        <div class="lop-info-card-note"><?php echo $completion_rate; ?>% completion rate</div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ( ! empty( $gamipress_data['total_points'] ) ) : ?>
                    <div class="lop-info-card">
                        <div class="lop-info-card-label">Total Points Earned</div>
                        <div class="lop-info-card-value"><?php echo number_format( intval( $gamipress_data['total_points'] ) ); ?></div>
                        <div class="lop-info-card-note">Across all point types</div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ( ! empty( $gamipress_data['total_achievements'] ) ) : ?>
                    <div class="lop-info-card">
                        <div class="lop-info-card-label">Achievements Unlocked</div>
                        <div class="lop-info-card-value"><?php echo intval( $gamipress_data['total_achievements'] ); ?></div>
                        <div class="lop-info-card-note">
                            <a href="#" style="color: var(--lop-primary); text-decoration: none;">View All Achievements →</a>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
                <!-- Memberium Integration Status -->
                <?php if ( function_exists( 'memb_updateContact' ) ) : ?>
                <div class="lop-section-compact">
                    <h2 class="lop-section-title">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path>
                            <rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect>
                            <path d="M9 14l2 2 4-4"></path>
                        </svg>
                        Integration Status
                    </h2>
                    
                    <div class="lop-info-card">
                        <div class="lop-info-card-label">Memberium Integration</div>
                        <div class="lop-info-card-value" style="color: var(--lop-success);">✓ Active</div>
                        <div class="lop-info-card-note">Profile changes sync with Infusionsoft automatically</div>
                    </div>
                    
                    <?php if ( function_exists( 'learndash_user_get_enrolled_courses' ) ) : ?>
                    <div class="lop-info-card">
                        <div class="lop-info-card-label">LearnDash Integration</div>
                        <div class="lop-info-card-value" style="color: var(--lop-success);">✓ Active</div>
                        <div class="lop-info-card-note">Course progress tracking enabled</div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ( function_exists( 'gamipress_get_user_points' ) ) : ?>
                    <div class="lop-info-card">
                        <div class="lop-info-card-label">GamiPress Integration</div>
                        <div class="lop-info-card-value" style="color: var(--lop-success);">✓ Active</div>
                        <div class="lop-info-card-note">Points and achievements tracking enabled</div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
    // Password confirmation validation
    document.addEventListener('DOMContentLoaded', function() {
        const newPasswordField = document.getElementById('new_password');
        const confirmPasswordField = document.getElementById('confirm_password');
        
        function validatePasswords() {
            const newPassword = newPasswordField.value;
            const confirmPassword = confirmPasswordField.value;
            
            if (newPassword && confirmPassword) {
                if (newPassword !== confirmPassword) {
                    confirmPasswordField.setCustomValidity('Passwords do not match');
                } else {
                    confirmPasswordField.setCustomValidity('');
                }
            }
        }
        
        if (newPasswordField && confirmPasswordField) {
            newPasswordField.addEventListener('input', validatePasswords);
            confirmPasswordField.addEventListener('input', validatePasswords);
        }
    });
    </script>

    <?php
    return ob_get_clean();
}

// Register the shortcode
add_shortcode( 'lop_profile_management', 'lop_profile_management_shortcode' );

// Add custom CSS to admin if needed
function lop_profile_management_admin_styles() {
    if ( is_admin() ) {
        echo '<style>
            .lop-profile-management-shortcode-info {
                background: #f0f8ff;
                border: 1px solid #007cba;
                border-radius: 4px;
                padding: 12px;
                margin: 12px 0;
            }
            .lop-profile-management-shortcode-info h4 {
                margin: 0 0 8px 0;
                color: #007cba;
            }
            .lop-profile-management-shortcode-info code {
                background: rgba(0, 124, 186, 0.1);
                padding: 2px 6px;
                border-radius: 3px;
            }
        </style>';
    }
}
add_action( 'admin_head', 'lop_profile_management_admin_styles' );

?>