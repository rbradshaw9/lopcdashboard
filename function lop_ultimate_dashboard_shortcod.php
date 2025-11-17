    <?php
    /**
     * Apple-Inspired LearnDash Student Dashboard
     * Professional, mobile-responsive dashboard with advanced features
     * 
     * VERSION: 3.0 - Complete Overhaul (STABLE)
     * UPDATED: October 2025
     * 
     * FEATURES:
     * - Accurate course progress tracking (FIXED)
     * - Smart resume to last lesson/topic accessed
     * - GamiPress integration with points and streak display
     * - Correct course pricing display
     * - All enrolled courses shown in My Courses
     * - Dynamic button text (Start/Continue based on progress)
     * - Professional Apple-inspired design
     * - Mobile-responsive with dark mode support
     * - Announcements system with membership targeting
     */

    function lop_apple_dashboard_shortcode() {
        // Check if user is logged in
        if ( ! is_user_logged_in() ) {
            return '<div class="lop-login-prompt" style="text-align: center; padding: 3rem; color: #86868b; font-family: -apple-system, BlinkMacSystemFont, sans-serif; background: #f5f5f7; border-radius: 12px; margin: 1rem 0;">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin-bottom: 1rem; opacity: 0.5;">
                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                    <circle cx="12" cy="7" r="4"></circle>
                </svg>
                <p style="margin: 0; font-size: 1.1rem;">Please log in to access your learning dashboard</p>
            </div>';
        }

        $user        = wp_get_current_user();
        $user_id     = $user->ID;
        $user_name   = $user->display_name;
        $user_avatar = get_avatar_url( $user_id, array( 'size' => 120 ) );

        // Get announcements that user has access to (Memberium-protected)
        $user_announcements = array();
        $all_announcements = get_posts( array(
            'post_type'      => 'dash_announcement',
            'posts_per_page' => 10,
            'post_status'    => 'publish',
            'orderby'        => 'date',
            'order'          => 'DESC'
        ) );

        // Get user's permanently dismissed announcements
        $dismissed_forever = get_user_meta( $user_id, 'lop_dismissed_announcements', true );
        if ( ! is_array( $dismissed_forever ) ) {
            $dismissed_forever = array();
        }

        // Filter by Memberium access and dismissed status
        if ( ! empty( $all_announcements ) && function_exists( 'memb_hasPostAccess' ) ) {
            foreach ( $all_announcements as $announcement ) {
                // Skip if permanently dismissed
                if ( in_array( $announcement->ID, $dismissed_forever ) ) {
                    continue;
                }
                
                // Check Memberium access - Enhanced check
                $has_access = false;
                
                // Try multiple Memberium functions to get proper access check
                if ( function_exists( 'memb_hasPostAccess' ) ) {
                    $has_access = memb_hasPostAccess( $announcement->ID );
                }
                
                // Alternative: Check if post has any Memberium protection set
                if ( $has_access && function_exists( 'memb_getPostMemberships' ) ) {
                    $post_memberships = memb_getPostMemberships( $announcement->ID );
                    
                    // If memberships are explicitly set but user doesn't have them, deny access
                    if ( ! empty( $post_memberships ) ) {
                        $user_has_membership = false;
                        foreach ( $post_memberships as $membership_id ) {
                            if ( function_exists( 'memb_hasMembership' ) && memb_hasMembership( $membership_id ) ) {
                                $user_has_membership = true;
                                break;
                            }
                        }
                        $has_access = $user_has_membership;
                    }
                }
                
                // DEBUG: Enhanced logging (uncomment to debug)
                /*
                $post_memberships = function_exists( 'memb_getPostMemberships' ) ? memb_getPostMemberships( $announcement->ID ) : 'N/A';
                error_log( sprintf( 
                    'Announcement ID %d: memb_hasPostAccess=%s, post_memberships=%s, final_access=%s', 
                    $announcement->ID, 
                    function_exists( 'memb_hasPostAccess' ) ? ( memb_hasPostAccess( $announcement->ID ) ? 'TRUE' : 'FALSE' ) : 'N/A',
                    is_array( $post_memberships ) ? implode( ',', $post_memberships ) : $post_memberships,
                    $has_access ? 'TRUE' : 'FALSE'
                ) );
                */
                
                // Check if announcement has scheduling and if it's currently active
                if ( $has_access ) {
                    $is_scheduled_active = true;
                    
                    // Get scheduling fields (ACF or post meta)
                    if ( function_exists( 'get_field' ) ) {
                        $start_date = get_field( 'announcement_start_date', $announcement->ID );
                        $end_date = get_field( 'announcement_end_date', $announcement->ID );
                    } else {
                        $start_date = get_post_meta( $announcement->ID, 'announcement_start_date', true );
                        $end_date = get_post_meta( $announcement->ID, 'announcement_end_date', true );
                    }
                    
                    $current_time = current_time( 'timestamp' );
                    
                    // Check start date
                    if ( ! empty( $start_date ) ) {
                        $start_timestamp = is_numeric( $start_date ) ? $start_date : strtotime( $start_date );
                        if ( $start_timestamp && $current_time < $start_timestamp ) {
                            $is_scheduled_active = false; // Not started yet
                        }
                    }
                    
                    // Check end date
                    if ( ! empty( $end_date ) && $is_scheduled_active ) {
                        $end_timestamp = is_numeric( $end_date ) ? $end_date : strtotime( $end_date );
                        if ( $end_timestamp && $current_time > $end_timestamp ) {
                            $is_scheduled_active = false; // Already ended
                        }
                    }
                    
                    // DEBUG: Scheduling debug (uncomment to debug)
                    /*
                    error_log( sprintf( 
                        'Announcement ID %d: start_date=%s, end_date=%s, current_time=%s, is_active=%s', 
                        $announcement->ID,
                        $start_date ? date( 'Y-m-d H:i:s', is_numeric( $start_date ) ? $start_date : strtotime( $start_date ) ) : 'none',
                        $end_date ? date( 'Y-m-d H:i:s', is_numeric( $end_date ) ? $end_date : strtotime( $end_date ) ) : 'none',
                        date( 'Y-m-d H:i:s', $current_time ),
                        $is_scheduled_active ? 'TRUE' : 'FALSE'
                    ) );
                    */
                    
                    if ( $is_scheduled_active ) {
                        $user_announcements[] = $announcement;
                    }
                }
            }
        } elseif ( ! empty( $all_announcements ) ) {
            // If Memberium not active, show all announcements (except dismissed and scheduled)
            foreach ( $all_announcements as $announcement ) {
                if ( in_array( $announcement->ID, $dismissed_forever ) ) {
                    continue;
                }
                
                // Check scheduling for non-Memberium sites
                $is_scheduled_active = true;
                
                // Get scheduling fields (ACF or post meta)
                if ( function_exists( 'get_field' ) ) {
                    $start_date = get_field( 'announcement_start_date', $announcement->ID );
                    $end_date = get_field( 'announcement_end_date', $announcement->ID );
                } else {
                    $start_date = get_post_meta( $announcement->ID, 'announcement_start_date', true );
                    $end_date = get_post_meta( $announcement->ID, 'announcement_end_date', true );
                }
                
                $current_time = current_time( 'timestamp' );
                
                // Check start date
                if ( ! empty( $start_date ) ) {
                    $start_timestamp = is_numeric( $start_date ) ? $start_date : strtotime( $start_date );
                    if ( $start_timestamp && $current_time < $start_timestamp ) {
                        $is_scheduled_active = false; // Not started yet
                    }
                }
                
                // Check end date
                if ( ! empty( $end_date ) && $is_scheduled_active ) {
                    $end_timestamp = is_numeric( $end_date ) ? $end_date : strtotime( $end_date );
                    if ( $end_timestamp && $current_time > $end_timestamp ) {
                        $is_scheduled_active = false; // Already ended
                    }
                }
                
                if ( $is_scheduled_active ) {
                    $user_announcements[] = $announcement;
                }
            }
        }

        // Get enrolled courses
        $enrolled_courses = function_exists( 'learndash_user_get_enrolled_courses' ) 
            ? learndash_user_get_enrolled_courses( $user_id ) 
            : [];

        // Calculate progress for all courses - FIXED METHOD
        $course_progress_data = array();
        $completed_count = 0;
        $total_progress_sum = 0;

        if ( ! empty( $enrolled_courses ) ) {
            foreach ( $enrolled_courses as $course_id ) {
                $percentage = 0;
                $completed_steps = 0;
                $total_steps = 0;

                // Use the working method from reference code - NO 'co' parameter!
                if ( function_exists( 'learndash_user_get_course_progress' ) ) {
                    $course_progress = learndash_user_get_course_progress( $user_id, $course_id );
                    
                    if ( ! empty( $course_progress ) ) {
                        // Check for percentage key first
                        if ( isset( $course_progress['percentage'] ) ) {
                            $percentage = round( $course_progress['percentage'] );
                        } elseif ( isset( $course_progress['completed'], $course_progress['total'] ) && $course_progress['total'] > 0 ) {
                            $percentage = round( ( $course_progress['completed'] / $course_progress['total'] ) * 100 );
                        }
                        
                        $completed_steps = isset( $course_progress['completed'] ) ? absint( $course_progress['completed'] ) : 0;
                        $total_steps = isset( $course_progress['total'] ) ? absint( $course_progress['total'] ) : 0;
                    }
                }

                // Get last activity timestamp
                $last_activity = get_user_meta( $user_id, 'course_last_activity_' . $course_id, true );
                if ( ! $last_activity ) {
                    $last_activity = time();
                }

                $course_progress_data[$course_id] = array(
                    'percentage'      => $percentage,
                    'completed_steps' => $completed_steps,
                    'total_steps'     => $total_steps,
                    'last_activity'   => $last_activity
                );

                $total_progress_sum += $percentage;
                
                if ( $percentage >= 100 ) {
                    $completed_count++;
                }
            }

            // Sort: In-progress courses first (by completion %), then completed courses at the end
            uasort( $course_progress_data, function( $a, $b ) {
                $a_percentage = isset( $a['percentage'] ) ? $a['percentage'] : 0;
                $b_percentage = isset( $b['percentage'] ) ? $b['percentage'] : 0;
                $a_completed = ( $a_percentage >= 100 );
                $b_completed = ( $b_percentage >= 100 );
                
                // Completed courses go to the end
                if ( $a_completed && ! $b_completed ) {
                    return 1; // $a goes after $b
                } elseif ( ! $a_completed && $b_completed ) {
                    return -1; // $a goes before $b
                }
                
                // Both completed - sort by recent activity (most recent first)
                if ( $a_completed && $b_completed ) {
                    $a_activity = isset( $a['last_activity'] ) ? $a['last_activity'] : 0;
                    $b_activity = isset( $b['last_activity'] ) ? $b['last_activity'] : 0;
                    return $b_activity - $a_activity;
                }
                
                // Both in-progress - sort by completion percentage (highest first)
                return $b_percentage - $a_percentage;
            });
        }

        $total_courses = count( $enrolled_courses );
        $overall_progress = $total_courses > 0 ? round( $total_progress_sum / $total_courses ) : 0;

        // GamiPress Integration - Get TOTAL points across ALL point types
        $total_points = 0;
        $points_breakdown = array();
        
        // Get all available point types
        if ( function_exists( 'gamipress_get_points_types' ) ) {
            $all_point_types = gamipress_get_points_types();
            error_log( 'Available GamiPress Point Types: ' . print_r( array_keys( $all_point_types ), true ) );
            
            // Sum up points from ALL point types
            foreach ( $all_point_types as $point_type_slug => $point_type ) {
                if ( function_exists( 'gamipress_get_user_points' ) ) {
                    $points = gamipress_get_user_points( $user_id, $point_type_slug );
                    if ( $points > 0 ) {
                        $total_points += $points;
                        $points_breakdown[$point_type_slug] = $points;
                        error_log( 'Point Type: ' . $point_type_slug . ' = ' . $points );
                    }
                }
            }
            
            error_log( 'Total Points Breakdown: ' . print_r( $points_breakdown, true ) );
            error_log( 'TOTAL GAMIPRESS POINTS: ' . $total_points );
        }
        
        // Fallback: If no points found, try direct meta queries for common point types
        if ( $total_points == 0 ) {
            error_log( 'No points found via functions, trying meta queries...' );
            global $wpdb;
            $meta_keys_query = $wpdb->get_results( 
                $wpdb->prepare(
                    "SELECT meta_key, meta_value FROM {$wpdb->usermeta} 
                    WHERE user_id = %d 
                    AND meta_key LIKE '%%gamipress%%points%%'
                    AND meta_key NOT LIKE '%%rank%%'
                    AND meta_key NOT LIKE '%%achievement%%'",
                    $user_id
                )
            );
            
            foreach ( $meta_keys_query as $meta ) {
                error_log( 'Found meta: ' . $meta->meta_key . ' = ' . $meta->meta_value );
                if ( is_numeric( $meta->meta_value ) && $meta->meta_value > 0 ) {
                    $total_points += absint( $meta->meta_value );
                }
            }
            
            error_log( 'Total from meta queries: ' . $total_points );
        }
        
        $positivity_points_display = $total_points;
        error_log( 'FINAL TOTAL POINTS DISPLAY: ' . $positivity_points_display );

        // GamiPress Streak - Enhanced tracking using WordPress and LearnDash data
        $today = date( 'Y-m-d' );
        $yesterday = date( 'Y-m-d', strtotime( '-1 day' ) );
        
        // Check multiple data sources for login/activity tracking
        $wp_last_login = get_user_meta( $user_id, 'wp_last_login', true ); // WordPress login tracking plugin data
        $ld_last_activity = get_user_meta( $user_id, 'learndash_last_known_activity', true ); // LearnDash activity
        $session_tokens = get_user_meta( $user_id, 'session_tokens', true ); // WordPress session data
        $custom_last_login = get_user_meta( $user_id, 'last_login_date', true ); // Our custom tracking
        
        // GamiPress login tracking - since you're getting login points, let's use GamiPress data
        $gamipress_logs = array();
        if ( function_exists( 'gamipress_get_user_log_entries' ) ) {
            // Get recent login entries from GamiPress logs
            $gamipress_logs = gamipress_get_user_log_entries( array(
                'user_id' => $user_id,
                'type' => 'login',
                'since' => strtotime( '-7 days' ), // Check last 7 days
                'orderby' => 'date',
                'order' => 'DESC'
            ) );
        }
        
        // Also check for any daily login awards
        if ( function_exists( 'gamipress_get_user_earnings' ) ) {
            $daily_earnings = gamipress_get_user_earnings( array(
                'user_id' => $user_id,
                'achievement_type' => 'daily-login', // Common GamiPress daily login achievement type
                'since' => strtotime( '-3 days' ),
                'orderby' => 'date',
                'order' => 'DESC'
            ) );
            
            if ( ! empty( $daily_earnings ) ) {
                foreach ( $daily_earnings as $earning ) {
                    $earning_date = date( 'Y-m-d', strtotime( $earning->date ) );
                    error_log( 'STREAK DEBUG - GamiPress daily login earning found: ' . $earning_date );
                }
            }
        }
        
        // Get the most recent login date from available sources
        $login_dates = array();
        
        // Parse WordPress session tokens for last activity
        if ( is_array( $session_tokens ) && ! empty( $session_tokens ) ) {
            $latest_session = 0;
            foreach ( $session_tokens as $token_data ) {
                if ( isset( $token_data['login'] ) && $token_data['login'] > $latest_session ) {
                    $latest_session = $token_data['login'];
                }
            }
            if ( $latest_session > 0 ) {
                $login_dates[] = date( 'Y-m-d', $latest_session );
            }
        }
        
        // Add GamiPress login data
        if ( ! empty( $gamipress_logs ) ) {
            foreach ( $gamipress_logs as $log_entry ) {
                if ( isset( $log_entry->date ) ) {
                    $login_dates[] = date( 'Y-m-d', strtotime( $log_entry->date ) );
                }
            }
        }
        
        // Add other sources
        if ( $wp_last_login ) $login_dates[] = date( 'Y-m-d', strtotime( $wp_last_login ) );
        if ( $ld_last_activity ) $login_dates[] = date( 'Y-m-d', $ld_last_activity );
        if ( $custom_last_login ) $login_dates[] = $custom_last_login;
        
        // Get the most recent date
        $login_dates = array_unique( $login_dates );
        rsort( $login_dates ); // Sort newest first
        $last_activity_date = ! empty( $login_dates ) ? $login_dates[0] : '';
        
        error_log( 'STREAK DEBUG - User: ' . $user_id . ', Login sources found: ' . implode( ', ', $login_dates ) . ', Most recent: ' . $last_activity_date );
        
        // Check LearnDash specific activity
        $ld_progress_data = array();
        if ( function_exists( 'learndash_user_get_course_progress' ) ) {
            $user_courses = learndash_user_get_enrolled_courses( $user_id );
            foreach ( $user_courses as $course_id ) {
                $progress = learndash_user_get_course_progress( $user_id, $course_id );
                if ( ! empty( $progress['last_activity'] ) ) {
                    $activity_date = date( 'Y-m-d', $progress['last_activity'] );
                    $ld_progress_data[] = $activity_date;
                }
            }
        }
        
        if ( ! empty( $ld_progress_data ) ) {
            rsort( $ld_progress_data );
            $ld_last_date = $ld_progress_data[0];
            error_log( 'STREAK DEBUG - LearnDash last activity: ' . $ld_last_date );
            
            // Use LearnDash date if it's more recent
            if ( empty( $last_activity_date ) || $ld_last_date > $last_activity_date ) {
                $last_activity_date = $ld_last_date;
            }
        }
        
        // Update streak logic based on most reliable data
        $current_streak_before = get_user_meta( $user_id, 'learning_streak', true ) ?: 0;
        
        // Get our last recorded streak update date
        $last_streak_update = get_user_meta( $user_id, 'last_streak_update_date', true );
        
        error_log( 'STREAK DEBUG - Last streak update: ' . $last_streak_update . ', Today: ' . $today . ', Yesterday: ' . $yesterday );
        
        if ( $last_streak_update !== $today ) {
            // Haven't updated streak today yet
            
            if ( $last_streak_update === $yesterday ) {
                // CONTINUE STREAK: User was active yesterday and is active today
                $new_streak = $current_streak_before + 1;
                update_user_meta( $user_id, 'learning_streak', $new_streak );
                error_log( 'STREAK DEBUG - CONTINUING streak: ' . $current_streak_before . ' -> ' . $new_streak . ' (consecutive days)' );
                
            } elseif ( empty( $last_streak_update ) ) {
                // FIRST TIME: No previous streak data, start at 1
                update_user_meta( $user_id, 'learning_streak', 1 );
                error_log( 'STREAK DEBUG - FIRST TIME streak, setting to 1' );
                
            } else {
                // MISSED DAYS: Gap between last update and today - RESET STREAK
                // If last_streak_update is older than yesterday, they missed at least one day
                $days_since_last = (strtotime($today) - strtotime($last_streak_update)) / (60 * 60 * 24);
                update_user_meta( $user_id, 'learning_streak', 1 );
                error_log( 'STREAK DEBUG - STREAK RESET to 1 (missed ' . $days_since_last . ' days since ' . $last_streak_update . ')' );
            }
            
            // Update streak tracking date to today
            update_user_meta( $user_id, 'last_streak_update_date', $today );
            error_log( 'STREAK DEBUG - Updated last_streak_update_date to: ' . $today );
            
        } else {
            error_log( 'STREAK DEBUG - Already updated streak today, no change needed' );
        }
        
        $streak_display = get_user_meta( $user_id, 'learning_streak', true ) ?: 0;
        error_log( 'STREAK DEBUG - Final streak: ' . $streak_display . ' (Last activity: ' . $last_activity_date . ', Last update: ' . $last_streak_update . ')' );

        // Smart recent course selection
        $recent_course_id = null;
        $recent_progress_data = null;

        foreach ( $course_progress_data as $course_id => $data ) {
            if ( $data['percentage'] < 100 && $data['percentage'] > 0 ) {
                $recent_course_id = $course_id;
                $recent_progress_data = $data;
                break;
            }
        }

        // If no in-progress course, get most recent
        if ( ! $recent_course_id && ! empty( $course_progress_data ) ) {
            $recent_course_id = array_keys( $course_progress_data )[0];
            $recent_progress_data = $course_progress_data[$recent_course_id];
        }

        // Get resume URL for recent course - properly get last accessed lesson/topic
        $resume_url = '';
        if ( $recent_course_id ) {
            // Method 1: Check user activity meta for last accessed step
            global $wpdb;
            $last_activity = $wpdb->get_row( $wpdb->prepare(
                "SELECT post_id, activity_type 
                FROM {$wpdb->prefix}learndash_user_activity 
                WHERE user_id = %d 
                AND course_id = %d 
                AND activity_type IN ('lesson', 'topic')
                AND activity_completed = 0
                ORDER BY activity_updated DESC 
                LIMIT 1",
                $user_id,
                $recent_course_id
            ) );
            
            if ( $last_activity && $last_activity->post_id ) {
                $resume_url = get_permalink( $last_activity->post_id );
            }
            
            // Method 2: If no incomplete step, get the last completed step
            if ( ! $resume_url ) {
                $last_completed = $wpdb->get_var( $wpdb->prepare(
                    "SELECT post_id 
                    FROM {$wpdb->prefix}learndash_user_activity 
                    WHERE user_id = %d 
                    AND course_id = %d 
                    AND activity_type IN ('lesson', 'topic')
                    ORDER BY activity_updated DESC 
                    LIMIT 1",
                    $user_id,
                    $recent_course_id
                ) );
                
                if ( $last_completed ) {
                    $resume_url = get_permalink( $last_completed );
                }
            }
            
            // Final fallback: course page
            if ( ! $resume_url ) {
                $resume_url = get_permalink( $recent_course_id );
            }
        }

        // CSS Loading via jsDelivr CDN (GitHub) with cache-busting
        // This loads CSS from your GitHub repository - fast, cached, and reliable
        // Make sure your GitHub repo is public and contains lop-dashboard-styles.css
        $css_version = '1.0.3-' . time(); // Cache busting to force fresh load
        wp_enqueue_style( 'lop-dashboard-styles', 'https://cdn.jsdelivr.net/gh/rbradshaw9/lopcdashboard@main/lop-dashboard-styles.css', array(), $css_version );
        
        // Alternative: If repo name or branch is different, update the URL:
        // Format: https://cdn.jsdelivr.net/gh/USERNAME/REPO@BRANCH/lop-dashboard-styles.css
        
        ob_start();
        ?>
        
        <div class="lop-dashboard-wrapper">
            <!-- Hero Header with Enhanced Visual Hierarchy -->
            <div class="lop-hero-section">
                <div class="lop-hero-content">
                    <div class="lop-user-welcome">
                        <img src="<?php echo esc_url( $user_avatar ); ?>" alt="<?php echo esc_attr( $user_name ); ?>'s profile" class="lop-user-avatar">
                        <div class="lop-welcome-text">
                            <h1 class="lop-hero-title">Welcome back, <?php echo esc_html( $user_name ); ?>! 👋</h1>
                            <p class="lop-hero-subtitle">
                                <?php 
                                // Dynamic motivational message based on progress
                                if ( $completed_count === 0 ) {
                                    echo "Let's start your learning journey";
                                } elseif ( $overall_progress < 25 ) {
                                    echo "Great start! Keep building momentum";
                                } elseif ( $overall_progress < 50 ) {
                                    echo "You're making excellent progress";
                                } elseif ( $overall_progress < 75 ) {
                                    echo "Fantastic work! You're more than halfway there";
                                } elseif ( $overall_progress < 100 ) {
                                    echo "Almost there! You're crushing it";
                                } else {
                                    echo "Outstanding! All courses completed";
                                }
                                ?>
                            </p>
                        </div>
                    </div>
                    
                    <?php if ( ! empty( $resume_url ) ) : ?>
                        <a href="<?php echo esc_url( $resume_url ); ?>" class="lop-button lop-button-primary lop-hero-cta">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polygon points="5,3 19,12 5,21"></polygon>
                            </svg>
                            <?php echo $recent_progress_data['percentage'] > 0 ? 'Continue Learning' : 'Start Learning'; ?>
                        </a>
                    <?php endif; ?>
                </div>
                
                <!-- Quick Stats Bar (Inline with Hero for better scanability) -->
                <div class="lop-quick-stats">
                    <div class="lop-quick-stat">
                        <div class="lop-quick-stat-value"><?php echo $overall_progress; ?>%</div>
                        <div class="lop-quick-stat-label">Overall Progress</div>
                    </div>
                    <div class="lop-quick-stat-divider"></div>
                    <div class="lop-quick-stat">
                        <div class="lop-quick-stat-value"><?php echo $completed_count; ?>/<?php echo $total_courses; ?></div>
                        <div class="lop-quick-stat-label">Completed</div>
                    </div>
                    <div class="lop-quick-stat-divider"></div>
                    <div class="lop-quick-stat">
                        <div class="lop-quick-stat-value"><?php echo max(0, $total_courses - $completed_count); ?></div>
                        <div class="lop-quick-stat-label">In Progress</div>
                    </div>
                </div>
            </div>
            
            <?php
            // Only process and display announcements if user has any accessible ones
            if ( ! empty( $user_announcements ) ) :
                // Separate announcements into banners and regular cards
                $banner_announcements = array();
                $regular_announcements = array();
                $seen_announcements = get_user_meta( $user_id, 'lop_seen_announcements', true );
                if ( ! is_array( $seen_announcements ) ) {
                    $seen_announcements = array();
                }
                
                foreach ( $user_announcements as $announcement ) {
                    // Get announcement type (ACF or post meta)
                    if ( function_exists( 'get_field' ) ) {
                        $announcement_display = get_field( 'announcement_display_type', $announcement->ID );
                    } else {
                        $announcement_display = get_post_meta( $announcement->ID, 'announcement_display_type', true );
                    }
                    
                    $is_banner = ( strpos( strtolower( $announcement_display ), 'banner' ) !== false );
                    
                    if ( $is_banner ) {
                        $banner_announcements[] = $announcement;
                    } else {
                        $regular_announcements[] = $announcement;
                    }
                }
                
                // Count unread regular announcements (banners are always visible)
                $unread_count = 0;
                foreach ( $regular_announcements as $announcement ) {
                    if ( ! in_array( $announcement->ID, $seen_announcements ) ) {
                        $unread_count++;
                    }
                }
            ?>
            
            <!-- Banner Announcements (Full Width Above Card) -->
            <?php if ( ! empty( $banner_announcements ) ) : ?>
                    <div class="lop-banner-announcements">
                        <?php foreach ( $banner_announcements as $banner ) :
                            // Get banner fields
                            if ( function_exists( 'get_field' ) ) {
                                $cta_link = get_field( 'cta_link', $banner->ID );
                                $cta_target = get_field( 'cta_target', $banner->ID );
                            } else {
                                $cta_link = get_post_meta( $banner->ID, 'cta_link', true );
                                $cta_target = get_post_meta( $banner->ID, 'cta_target', true );
                            }
                            if ( empty( $cta_target ) ) {
                                $cta_target = '_blank';
                            }
                            $banner_image = get_the_post_thumbnail_url( $banner->ID, 'large' );
                        ?>
                            <?php
                            // Get responsive banner images
                            if ( function_exists( 'get_field' ) ) {
                                $desktop_banner = get_field( 'desktop_banner_image', $banner->ID );
                                $mobile_banner = get_field( 'mobile_banner_image', $banner->ID );
                            } else {
                                $desktop_banner = get_post_meta( $banner->ID, 'desktop_banner_image', true );
                                $mobile_banner = get_post_meta( $banner->ID, 'mobile_banner_image', true );
                            }
                            
                            // Fallback to featured image if custom fields not set
                            if ( empty( $desktop_banner ) ) {
                                $desktop_banner = $banner_image;
                            }
                            if ( empty( $mobile_banner ) ) {
                                $mobile_banner = $desktop_banner; // Use desktop as fallback
                            }
                            
                            // Ensure we have URLs if these are attachment IDs
                            if ( is_numeric( $desktop_banner ) ) {
                                $desktop_banner = wp_get_attachment_url( $desktop_banner );
                            }
                            if ( is_numeric( $mobile_banner ) ) {
                                $mobile_banner = wp_get_attachment_url( $mobile_banner );
                            }
                            ?>
                            <div class="lop-banner-announcement" data-announcement-id="<?php echo esc_attr( $banner->ID ); ?>">
                                <button class="lop-banner-dismiss lop-announcement-dismiss" data-announcement-id="<?php echo esc_attr( $banner->ID ); ?>" aria-label="Dismiss banner">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <line x1="18" y1="6" x2="6" y2="18"></line>
                                        <line x1="6" y1="6" x2="18" y2="18"></line>
                                    </svg>
                                </button>
                                <?php if ( ! empty( $cta_link ) ) : ?>
                                    <a href="<?php echo esc_url( $cta_link ); ?>" target="<?php echo esc_attr( $cta_target ); ?>" class="lop-banner-link">
                                        <picture>
                                            <source media="(max-width: 768px)" srcset="<?php echo esc_url( $mobile_banner ); ?>">
                                            <img src="<?php echo esc_url( $desktop_banner ); ?>" alt="<?php echo esc_attr( $banner->post_title ); ?>" />
                                        </picture>
                                    </a>
                                <?php else : ?>
                                    <picture>
                                        <source media="(max-width: 768px)" srcset="<?php echo esc_url( $mobile_banner ); ?>">
                                        <img src="<?php echo esc_url( $desktop_banner ); ?>" alt="<?php echo esc_attr( $banner->post_title ); ?>" />
                                    </picture>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <!-- Regular Announcements Card (Only show if there are non-banner announcements) -->
                <?php if ( ! empty( $regular_announcements ) ) : ?>
                <section class="lop-announcements-wrapper" aria-label="Announcements">
                    <div class="lop-announcements-card">
                        <div class="lop-announcements-header">
                            <button class="lop-announcements-toggle" aria-expanded="<?php echo $unread_count > 0 ? 'true' : 'false'; ?>" data-unread-count="<?php echo $unread_count; ?>">
                                <div class="lop-announcements-toggle-content">
                                    <h2>
                                        📢 Announcements
                                        <?php if ( $unread_count > 0 ) : ?>
                                            <span class="lop-unread-badge"><?php echo $unread_count; ?></span>
                                        <?php endif; ?>
                                    </h2>
                                    <svg class="lop-announcements-chevron" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="6,9 12,15 18,9"></polyline>
                                    </svg>
                                </div>
                            </button>
                        </div>
                        <div class="lop-announcements" style="<?php echo $unread_count > 0 ? '' : 'display: none;'; ?>">
                    <?php foreach ( $regular_announcements as $announcement ) :
                        // Get announcement type for styling (use ACF function if available)
                        if ( function_exists( 'get_field' ) ) {
                            $announcement_type = get_field( 'announcement_type', $announcement->ID );
                        } else {
                            $announcement_type = get_post_meta( $announcement->ID, 'announcement_type', true );
                        }
                        if ( empty( $announcement_type ) ) {
                            $announcement_type = 'info'; // Default
                        }
                        
                        // DEBUG: Check what announcement type is being retrieved (remove in production)
                        error_log( sprintf( 'Announcement ID %d: announcement_type = "%s"', $announcement->ID, $announcement_type ) );
                        
                        // Get optional CTA (use ACF function if available)
                        if ( function_exists( 'get_field' ) ) {
                            $cta_text = get_field( 'cta_text', $announcement->ID );
                            $cta_link = get_field( 'cta_link', $announcement->ID );
                            $cta_target = get_field( 'cta_target', $announcement->ID );
                            $show_timestamp = get_field( 'show_timestamp', $announcement->ID );
                        } else {
                            $cta_text = get_post_meta( $announcement->ID, 'cta_text', true );
                            $cta_link = get_post_meta( $announcement->ID, 'cta_link', true );
                            $cta_target = get_post_meta( $announcement->ID, 'cta_target', true );
                            $show_timestamp = get_post_meta( $announcement->ID, 'show_timestamp', true );
                        }
                        
                        // Ensure values are properly retrieved (fix for first-load issue)
                        $cta_text = ! empty( $cta_text ) ? $cta_text : '';
                        $cta_link = ! empty( $cta_link ) ? $cta_link : '';
                        
                        if ( empty( $cta_target ) ) {
                            $cta_target = '_blank'; // Default to new window
                        }
                        // Default to showing timestamp if not explicitly set to false
                        if ( ! isset( $show_timestamp ) || $show_timestamp === '' ) {
                            $show_timestamp = true;
                        }
                        
                        // Get scheduling information
                        if ( function_exists( 'get_field' ) ) {
                            $start_date = get_field( 'announcement_start_date', $announcement->ID );
                            $end_date = get_field( 'announcement_end_date', $announcement->ID );
                        } else {
                            $start_date = get_post_meta( $announcement->ID, 'announcement_start_date', true );
                            $end_date = get_post_meta( $announcement->ID, 'announcement_end_date', true );
                        }
                        
                        // Get featured image
                        $featured_image = get_the_post_thumbnail_url( $announcement->ID, 'large' );
                        
                        // Get icon based on type
                        $icons = array(
                            'info'  => '📢',
                            'event' => '📅',
                            'offer' => '🎉',
                            'alert' => '⚠️',
                            'banner' => '🖼️',
                            'banner image' => '🖼️'
                        );
                        
                        // Handle banner type variations
                        if ( strpos( strtolower( $announcement_type ), 'banner' ) !== false ) {
                            $icon = '🖼️';
                        } else {
                            $icon = isset( $icons[$announcement_type] ) ? $icons[$announcement_type] : $icons['info'];
                        }
                    ?>
                        <!-- Regular Announcement Card -->
                        <?php 
                        // Normalize banner type for CSS classes
                        $css_type = ( strpos( strtolower( $announcement_type ), 'banner' ) !== false ) ? 'banner' : $announcement_type;
                        ?>
                        <div class="lop-announcement-card type-<?php echo esc_attr( $css_type ); ?><?php echo $featured_image ? ' has-image' : ''; ?>" role="article" data-announcement-id="<?php echo esc_attr( $announcement->ID ); ?>">
                            <button class="lop-announcement-dismiss" data-announcement-id="<?php echo esc_attr( $announcement->ID ); ?>" aria-label="Dismiss announcement">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <line x1="18" y1="6" x2="6" y2="18"></line>
                                    <line x1="6" y1="6" x2="18" y2="18"></line>
                                </svg>
                            </button>
                            <?php if ( $featured_image ) : ?>
                                <div class="lop-announcement-image">
                                    <img src="<?php echo esc_url( $featured_image ); ?>" alt="<?php echo esc_attr( $announcement->post_title ); ?>" />
                                </div>
                            <?php endif; ?>
                            
                            <div class="lop-announcement-icon">
                                <?php echo $icon; ?>
                            </div>
                            <div class="lop-announcement-content">
                                <h3><?php echo esc_html( $announcement->post_title ); ?></h3>
                                <?php if ( ! empty( $announcement->post_content ) ) : ?>
                                    <div><?php echo wpautop( $announcement->post_content ); ?></div>
                                <?php endif; ?>
                                
                                <?php if ( ! empty( $cta_text ) && ! empty( $cta_link ) ) : ?>
                                    <a href="<?php echo esc_url( $cta_link ); ?>" target="<?php echo esc_attr( $cta_target ); ?>" class="lop-announcement-cta">
                                        <?php echo esc_html( $cta_text ); ?>
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <line x1="5" y1="12" x2="19" y2="12"></line>
                                            <polyline points="12,5 19,12 12,19"></polyline>
                                        </svg>
                                    </a>
                                <?php endif; ?>
                                
                                <?php if ( $show_timestamp ) : ?>
                                    <div class="lop-announcement-date">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <circle cx="12" cy="12" r="10"></circle>
                                            <polyline points="12,6 12,12 16,14"></polyline>
                                        </svg>
                                        <?php echo human_time_diff( strtotime( $announcement->post_date ), current_time( 'timestamp' ) ); ?> ago
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ( ! empty( $end_date ) ) : 
                                    $end_timestamp = is_numeric( $end_date ) ? $end_date : strtotime( $end_date );
                                    $time_until_end = $end_timestamp - current_time( 'timestamp' );
                                    
                                    // Only show if ending within 7 days or less
                                    if ( $time_until_end > 0 && $time_until_end <= ( 7 * 24 * 60 * 60 ) ) :
                                        $is_urgent = $time_until_end <= ( 24 * 60 * 60 ); // Less than 24 hours
                                    ?>
                                        <div class="lop-announcement-expires <?php echo $is_urgent ? 'urgent' : ''; ?>">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <circle cx="12" cy="12" r="10"></circle>
                                                <polyline points="12,6 12,12 16,14"></polyline>
                                            </svg>
                                            <?php if ( $is_urgent ) : ?>
                                                ⏰ Expires in <?php echo human_time_diff( current_time( 'timestamp' ), $end_timestamp ); ?>
                                            <?php else : ?>
                                                📅 Expires <?php echo human_time_diff( current_time( 'timestamp' ), $end_timestamp ); ?> from now
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                        </div>
                    </div>
                </section>
            <?php endif; ?>
            
            <?php endif; // End if ( ! empty( $user_announcements ) ) ?>

            <!-- My Courses Section -->
            <section class="lop-section" aria-label="My Courses">
                <div class="lop-courses-header">
                    <h2 class="lop-section-title">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path>
                            <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path>
                        </svg>
                        My Courses
                    </h2>
                    
                    <?php if ( ! empty( $enrolled_courses ) ) : ?>
                    <div class="lop-search-filter-container">
                        <div class="lop-search-box">
                            <input 
                                type="text" 
                                id="lop-my-courses-search" 
                                class="lop-search-input" 
                                placeholder="Search my courses..." 
                                aria-label="Search my courses"
                            >
                        </div>
                        <select id="lop-my-courses-sort" class="lop-filter-select" aria-label="Sort my courses">
                            <option value="recent">Most Recent</option>
                            <option value="alphabetical">Alphabetical</option>
                            <option value="progress">Progress %</option>
                            <option value="status">Completion Status</option>
                        </select>
                        <select id="lop-my-courses-filter" class="lop-filter-select" aria-label="Filter my courses by status">
                            <option value="all">All Courses</option>
                            <option value="not-started">Not Started</option>
                            <option value="in-progress">In Progress</option>
                            <option value="completed">Completed</option>
                        </select>
                    </div>
                    <?php endif; ?>
                </div>

                <?php if ( ! empty( $enrolled_courses ) ) : ?>
                    <div class="lop-course-grid lop-my-courses-grid" role="list">
                        <?php
                        // Get last accessed timestamps from LearnDash activity data
                        global $wpdb;
                        $last_accessed_query = $wpdb->get_results( $wpdb->prepare(
                            "SELECT course_id, MAX(activity_updated) as last_accessed
                            FROM {$wpdb->prefix}learndash_user_activity 
                            WHERE user_id = %d 
                            AND course_id IN (" . implode(',', array_map('intval', $enrolled_courses)) . ")
                            GROUP BY course_id",
                            $user_id
                        ) );
                        
                        $last_accessed_courses = array();
                        foreach ( $last_accessed_query as $activity ) {
                            $last_accessed_courses[$activity->course_id] = strtotime( $activity->last_accessed );
                        }
                        
                        // SHOW ALL COURSES - Using sorted course_progress_data for proper ordering
                        // Debug: Log course order for verification
                        $debug_order = array();
                        foreach ( $course_progress_data as $debug_course_id => $debug_data ) {
                            $debug_course_title = get_the_title( $debug_course_id );
                            $debug_percentage = isset( $debug_data['percentage'] ) ? $debug_data['percentage'] : 0;
                            $debug_order[] = $debug_course_title . ' (' . $debug_percentage . '%)';
                        }
                        error_log( 'COURSE SORT ORDER: ' . implode( ' | ', $debug_order ) );
                        
                        $course_index = 0;
                        foreach ( $course_progress_data as $course_id => $progress_data ) :
                            $course_index++;
                            $course = get_post( $course_id );
                            if ( ! $course ) continue;
                            $pct = $progress_data['percentage'];
                            
                            // Dynamic status and button text based on progress
                            $status = ( $pct == 0 ) ? 'Not Started' : ( ( $pct < 100 ) ? 'In Progress' : 'Completed' );
                            $button_text = ( $pct == 0 ) ? 'Start Course' : ( ( $pct < 100 ) ? 'Continue Course' : 'View Course' );
                            
                            // Get proper resume URL - goes to exact last lesson/topic
                            $course_url = get_permalink( $course_id );
                            if ( $pct > 0 && $pct < 100 ) {
                                // Query LearnDash activity table for last accessed step
                                global $wpdb;
                                $last_activity = $wpdb->get_row( $wpdb->prepare(
                                    "SELECT post_id 
                                    FROM {$wpdb->prefix}learndash_user_activity 
                                    WHERE user_id = %d 
                                    AND course_id = %d 
                                    AND activity_type IN ('lesson', 'topic')
                                    AND activity_completed = 0
                                    ORDER BY activity_updated DESC 
                                    LIMIT 1",
                                    $user_id,
                                    $course_id
                                ) );
                                
                                if ( $last_activity && $last_activity->post_id ) {
                                    $course_url = get_permalink( $last_activity->post_id );
                                } else {
                                    // Fallback: get last completed step
                                    $last_completed = $wpdb->get_var( $wpdb->prepare(
                                        "SELECT post_id 
                                        FROM {$wpdb->prefix}learndash_user_activity 
                                        WHERE user_id = %d 
                                        AND course_id = %d 
                                        AND activity_type IN ('lesson', 'topic')
                                        ORDER BY activity_updated DESC 
                                        LIMIT 1",
                                        $user_id,
                                        $course_id
                                    ) );
                                    
                                    if ( $last_completed ) {
                                        $course_url = get_permalink( $last_completed );
                                    }
                                }
                            }                        $thumbnail = get_the_post_thumbnail_url( $course_id, 'medium' ) ?: 'data:image/svg+xml;base64,' . base64_encode('<svg width="400" height="200" xmlns="http://www.w3.org/2000/svg"><rect width="100%" height="100%" fill="#f3f4f6"/><text x="50%" y="50%" text-anchor="middle" dy=".3em" fill="#9ca3af" font-family="Arial, sans-serif" font-size="16">Course</text></svg>');
                            
                            // Get last accessed timestamp for sorting
                            $last_accessed = isset( $last_accessed_courses[$course_id] ) ? $last_accessed_courses[$course_id] : 0;
                            
                            // Determine if this should be initially hidden (show first 6 courses)
                            $initial_class = ( $course_index > 6 ) ? ' lop-course-hidden' : '';
                        ?>
                            <article class="lop-course-card<?php echo $initial_class; ?><?php echo $pct >= 100 ? ' completed' : ''; ?>" role="listitem" onclick="window.location.href='<?php echo esc_js( esc_url( $course_url ) ); ?>'" style="cursor: pointer;" tabindex="0" onkeypress="if(event.key==='Enter')window.location.href='<?php echo esc_js( esc_url( $course_url ) ); ?>'" 
                                data-title="<?php echo esc_attr( strtolower( $course->post_title ) ); ?>" 
                                data-status="<?php echo esc_attr( strtolower( str_replace(' ', '-', $status) ) ); ?>"
                                data-progress="<?php echo esc_attr( $pct ); ?>"
                                data-last-accessed="<?php echo esc_attr( $last_accessed ); ?>"
                                data-course-id="<?php echo esc_attr( $course_id ); ?>">
                                <?php
                                // Check if course has "Bonus" category
                                $has_bonus_category = has_term( 'bonus', 'ld_course_category', $course_id );
                                
                                // Show completion ribbon for completed courses
                                if ( $pct >= 100 ) : ?>
                                    <div class="lop-completion-ribbon">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <polyline points="20,6 9,17 4,12"></polyline>
                                        </svg>
                                        Complete
                                    </div>
                                <?php endif; ?>
                                
                                <?php // Show bonus badge if course has "bonus" category
                                if ( $has_bonus_category ) : ?>
                                    <div class="lop-bonus-badge">
                                        <svg viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                                        </svg>
                                        Bonus
                                    </div>
                                <?php endif; ?>
                                
                                <img src="<?php echo esc_url( $thumbnail ); ?>" alt="" class="lop-card-thumb" loading="lazy">
                                <div class="lop-card-content">
                                    <h4><?php echo esc_html( $course->post_title ); ?></h4>
                                    <div class="lop-progress-container">
                                        <div class="lop-progress-bar" role="progressbar" aria-valuenow="<?php echo $pct; ?>" aria-valuemin="0" aria-valuemax="100" aria-label="<?php echo esc_attr( $course->post_title ); ?> progress">
                                            <div class="lop-progress-fill" style="width: <?php echo $pct; ?>%"></div>
                                        </div>
                                        <span class="lop-progress-text">
                                            <?php echo $pct; ?>% — <?php echo $status; ?>
                                        </span>
                                    </div>
                                    <a href="<?php echo esc_url( $course_url ); ?>" class="lop-button lop-button-primary" style="margin-top: auto;" aria-label="<?php echo esc_attr( $button_text . ' ' . $course->post_title ); ?>">
                                        <?php if ( $pct > 0 && $pct < 100 ) : ?>
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <polygon points="5,3 19,12 5,21"></polygon>
                                            </svg>
                                        <?php elseif ( $pct === 100 ) : ?>
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M9 12l2 2 4-4"></path>
                                                <circle cx="12" cy="12" r="10"></circle>
                                            </svg>
                                        <?php else : ?>
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <circle cx="12" cy="12" r="10"></circle>
                                                <polygon points="10,8 16,12 10,16"></polygon>
                                            </svg>
                                        <?php endif; ?>
                                        <?php echo esc_html( $button_text ); ?>
                                    </a>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                    
                    <?php if ( count( $enrolled_courses ) > 6 ) : ?>
                        <div class="lop-load-more-container" style="text-align: center; margin-top: var(--lop-space-xl);">
                            <button id="lop-load-more-courses" class="lop-button lop-button-secondary" aria-label="Load more courses">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="6,9 12,15 18,9"></polyline>
                                </svg>
                                Load More Courses
                            </button>
                        </div>
                    <?php endif; ?>
                <?php else : ?>
                    <div style="text-align: center; padding: var(--lop-space-3xl); color: var(--lop-gray-500);">
                        <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin-bottom: var(--lop-space-lg); opacity: 0.5;">
                            <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"></path>
                            <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"></path>
                        </svg>
                        <h3 style="margin: 0 0 var(--lop-space-md) 0; color: var(--lop-gray-700);">No Courses Yet</h3>
                        <p style="margin: 0; font-size: 1.125rem;">You haven't enrolled in any courses yet. Browse our catalog to get started!</p>
                    </div>
                <?php endif; ?>
            </section>

            <!-- Discover More Courses Section -->
            <?php
            // Get all published courses that user is NOT enrolled in
            $all_courses_query = get_posts( array(
                'post_type'      => 'sfwd-courses',
                'posts_per_page' => -1,
                'post_status'    => 'publish',
                'post__not_in'   => $enrolled_courses,
            ) );

            // Filter courses by Memberium access control
            $accessible_courses = array();
            foreach ( $all_courses_query as $course ) {
                // Use Memberium's official API function: memb_hasPostAccess()
                // This respects ALL Memberium protection settings, tags, and membership levels
                $show_course = true;
                
                if ( function_exists( 'memb_hasPostAccess' ) ) {
                    // memb_hasPostAccess() returns true if user CAN access the post/course
                    // Returns false if user CANNOT access it (lacks required tags/membership)
                    $show_course = memb_hasPostAccess( $course->ID );
                }
                
                // Only show course if Memberium allows access
                if ( $show_course ) {
                    $accessible_courses[] = $course;
                }
            }

            // Limit to 6 courses for display
            $all_courses = array_slice( $accessible_courses, 0, 6 );

            if ( ! empty( $all_courses ) ) : ?>
                <section class="lop-section" aria-label="Discover More Courses">
                    <div class="lop-courses-header">
                        <h2 class="lop-section-title">
                            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="11" cy="11" r="8"></circle>
                                <path d="M21 21l-4.35-4.35"></path>
                            </svg>
                            Discover More Courses
                        </h2>
                        
                        <div class="lop-search-filter-container">
                            <div class="lop-search-box">
                                <input 
                                    type="text" 
                                    id="lop-discover-search" 
                                    class="lop-search-input" 
                                    placeholder="Search available courses..." 
                                    aria-label="Search available courses"
                                >
                            </div>
                        </div>
                    </div>
                    <div class="lop-course-grid lop-discover-grid" role="list">
                        <?php foreach ( $all_courses as $course ) :
                            $course_url = get_permalink( $course->ID );
                            $excerpt = wp_trim_words( $course->post_excerpt ?: $course->post_content, 20 );
                            $thumbnail = get_the_post_thumbnail_url( $course->ID, 'medium' ) ?: 'data:image/svg+xml;base64,' . base64_encode('<svg width="400" height="200" xmlns="http://www.w3.org/2000/svg"><rect width="100%" height="100%" fill="#f3f4f6"/><text x="50%" y="50%" text-anchor="middle" dy=".3em" fill="#9ca3af" font-family="Arial, sans-serif" font-size="16">Course</text></svg>');
                            
                            // Get course pricing - Handle all scenarios: pre-formatted, numeric, closed, free
                            $price_display = 'Free';
                            
                            if ( function_exists( 'learndash_get_course_price' ) ) {
                                $price = learndash_get_course_price( $course->ID );
                                error_log( '=== Course ID ' . $course->ID . ' (' . $course->post_title . ') ===' );
                                error_log( 'Full Price Data: ' . print_r( $price, true ) );
                                
                                // Get course price type (open, closed, free, paynow, subscribe, etc.)
                                $price_type = isset( $price['type'] ) ? $price['type'] : 'open';
                                error_log( 'Price Type: ' . $price_type );
                                
                                // Check if price key exists
                                if ( isset( $price['price'] ) && $price['price'] !== '' && $price['price'] !== null ) {
                                    $raw_price = trim( $price['price'] );
                                    error_log( 'Raw price value: "' . $raw_price . '"' );
                                    
                                    // Check if price is already formatted (contains $ or text)
                                    if ( strpos( $raw_price, '$' ) !== false || ! is_numeric( $raw_price ) ) {
                                        // Price is already formatted (e.g., "$47" or "$47 (Save 75%)")
                                        $price_display = $raw_price;
                                        error_log( 'Using pre-formatted price: ' . $price_display );
                                    } else {
                                        // Price is numeric, format it
                                        $price_display = '$' . number_format( floatval( $raw_price ), 2 );
                                        error_log( 'Formatted numeric price: ' . $price_display );
                                    }
                                } else {
                                    // Price is empty - check course status
                                    error_log( 'Price is empty - checking course status' );
                                    
                                    if ( $price_type === 'closed' ) {
                                        $price_display = 'Enrollment Closed';
                                        error_log( 'Course is closed' );
                                    } elseif ( $price_type === 'free' || $price_type === 'open' ) {
                                        $price_display = 'Free';
                                        error_log( 'Course is free/open' );
                                    } else {
                                        // Try alternative: Check course meta directly
                                        $meta_price = get_post_meta( $course->ID, '_sfwd-courses', true );
                                        if ( isset( $meta_price['sfwd-courses_course_price'] ) && ! empty( $meta_price['sfwd-courses_course_price'] ) ) {
                                            $raw_meta_price = trim( $meta_price['sfwd-courses_course_price'] );
                                            error_log( 'Found price in course meta: "' . $raw_meta_price . '"' );
                                            if ( strpos( $raw_meta_price, '$' ) !== false || ! is_numeric( $raw_meta_price ) ) {
                                                $price_display = $raw_meta_price;
                                            } else {
                                                $price_display = '$' . number_format( floatval( $raw_meta_price ), 2 );
                                            }
                                        } else {
                                            error_log( 'No price in meta - defaulting based on type: ' . $price_type );
                                            $price_display = 'Free';
                                        }
                                    }
                                }
                            }
                            
                            error_log( 'Final Price Display: ' . $price_display );
                            error_log( '======================================' );
                            // Check if this is a bonus course
                            $is_bonus = has_term( 'bonus', 'ld_course_category', $course->ID );
                        ?>
                            <article class="lop-course-card" role="listitem" data-title="<?php echo esc_attr( strtolower( $course->post_title ) ); ?>" style="cursor: pointer;" onclick="window.location.href='<?php echo esc_url( $course_url ); ?>'" tabindex="0" onkeypress="if(event.key==='Enter') window.location.href='<?php echo esc_url( $course_url ); ?>'">
                                <img src="<?php echo esc_url( $thumbnail ); ?>" alt="" class="lop-card-thumb" loading="lazy">
                                
                                <?php if ( $is_bonus ) : ?>
                                    <div class="lop-bonus-badge" title="Bonus Course">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                                        </svg>
                                        Bonus
                                    </div>
                                <?php endif; ?>
                                
                                <div class="lop-card-content">
                                    <h4><?php echo esc_html( $course->post_title ); ?></h4>
                                    <?php if ( $excerpt ) : ?>
                                        <p class="lop-course-excerpt"><?php echo esc_html( $excerpt ); ?></p>
                                    <?php endif; ?>
                                    <div class="lop-discover-footer">
                                        <div class="lop-price-badge"><?php echo esc_html( $price_display ); ?></div>
                                        <a href="<?php echo esc_url( $course_url ); ?>" class="lop-discover-cta" onclick="event.stopPropagation();">
                                            Learn More
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                                <line x1="5" y1="12" x2="19" y2="12"></line>
                                                <polyline points="12,5 19,12 12,19"></polyline>
                                            </svg>
                                        </a>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php else : ?>
                <section class="lop-section" aria-label="Discover More Courses">
                    <h2 class="lop-section-title">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="M21 21l-4.35-4.35"></path>
                        </svg>
                        Discover More Courses
                    </h2>
                    <div class="lop-empty-state">
                        <svg class="lop-empty-state-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M9 12l2 2 4-4"></path>
                            <circle cx="12" cy="12" r="10"></circle>
                        </svg>
                        <h3>You're All Caught Up!</h3>
                        <p>Congratulations! You're enrolled in all available courses. Keep up the great work and complete your current courses to earn more certificates!</p>
                        <a href="#my-courses" class="lop-button lop-button-primary" onclick="event.preventDefault(); document.querySelector('.lop-section[aria-label=\"My Courses\"]')?.scrollIntoView({behavior: 'smooth'});">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polygon points="5,3 19,12 5,21"></polygon>
                            </svg>
                            Continue Learning
                        </a>
                    </div>
                </section>
            <?php endif; ?>
        </div>

        <script>
        (function() {
            'use strict';

            // DEBUG: Log dashboard data to console
            console.log('=== LOP Dashboard Debug Info ===');
            console.log('User ID:', <?php echo absint( $user_id ); ?>);
            console.log('Total Courses:', <?php echo absint( $total_courses ); ?>);
            console.log('GamiPress Function Exists:', <?php echo function_exists( 'gamipress_get_user_points' ) ? 'true' : 'false'; ?>);

            // ===== COLLAPSIBLE ANNOUNCEMENTS =====
            function initCollapsibleAnnouncements() {
                const toggle = document.querySelector('.lop-announcements-toggle');
                const announcementsSection = document.querySelector('.lop-announcements');
                
                if (!toggle || !announcementsSection) return;
                
                toggle.addEventListener('click', function() {
                    const isExpanded = this.getAttribute('aria-expanded') === 'true';
                    const unreadCount = parseInt(this.dataset.unreadCount) || 0;
                    
                    if (isExpanded) {
                        // Collapse
                        this.setAttribute('aria-expanded', 'false');
                        announcementsSection.style.display = 'none';
                    } else {
                        // Expand
                        this.setAttribute('aria-expanded', 'true');
                        announcementsSection.style.display = 'block';
                        
                        // Mark announcements as seen when expanded
                        if (unreadCount > 0) {
                            markAnnouncementsAsSeen();
                        }
                    }
                });
                
                // Auto-mark as seen after a few seconds if expanded
                const initialUnreadCount = parseInt(toggle.dataset.unreadCount) || 0;
                if (initialUnreadCount > 0 && toggle.getAttribute('aria-expanded') === 'true') {
                    setTimeout(() => {
                        markAnnouncementsAsSeen();
                    }, 3000); // Mark as seen after 3 seconds
                }
            }
            
            function markAnnouncementsAsSeen() {
                const announcementIds = [];
                document.querySelectorAll('[data-announcement-id]').forEach(el => {
                    announcementIds.push(el.dataset.announcementId);
                });
                
                if (announcementIds.length === 0) return;
                
                // Save to database via AJAX
                fetch('<?php echo admin_url("admin-ajax.php"); ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=lop_mark_announcements_seen&announcement_ids=${announcementIds.join(',')}&nonce=<?php echo wp_create_nonce("lop_mark_announcements_seen"); ?>`
                }).then(response => response.json()).then(data => {
                    if (data.success) {
                        // Remove unread badge
                        const badge = document.querySelector('.lop-unread-badge');
                        if (badge) {
                            badge.style.opacity = '0';
                            setTimeout(() => badge.remove(), 200);
                        }
                        
                        // Update toggle dataset
                        const toggle = document.querySelector('.lop-announcements-toggle');
                        if (toggle) {
                            toggle.dataset.unreadCount = '0';
                        }
                    }
                }).catch(err => {
                    console.error('Error marking announcements as seen:', err);
                });
            }

            // ===== DISMISSIBLE ANNOUNCEMENTS =====
            function initDismissibleAnnouncements() {
                const dismissButtons = document.querySelectorAll('.lop-announcement-dismiss, .lop-banner-dismiss');
                
                // Check for dismissed announcements on page load
                const dismissedToday = getDismissedToday();
                const dismissedForever = getDismissedForever();
                
                // Hide already dismissed announcements
                [...dismissedToday, ...dismissedForever].forEach(announcementId => {
                    const announcement = document.querySelector(`[data-announcement-id="${announcementId}"]`);
                    if (announcement) {
                        announcement.style.display = 'none';
                    }
                });
                
                // Add click handlers to dismiss buttons
                dismissButtons.forEach(button => {
                    button.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        
                        const announcementId = this.dataset.announcementId;
                        const announcement = this.closest('[data-announcement-id]');
                        
                        // Show dismiss options modal
                        showDismissModal(announcementId, announcement);
                    });
                });
            }
            
            function showDismissModal(announcementId, announcementElement) {
                // Create modal
                const modal = document.createElement('div');
                modal.style.cssText = `
                    position: fixed;
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    background: rgba(0, 0, 0, 0.5);
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    z-index: 10000;
                    backdrop-filter: blur(4px);
                `;
                
                const modalContent = document.createElement('div');
                modalContent.style.cssText = `
                    background: white;
                    padding: 2rem;
                    border-radius: 12px;
                    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
                    max-width: 400px;
                    width: 90%;
                `;
                
                modalContent.innerHTML = `
                    <h3 style="margin: 0 0 1rem 0; font-size: 1.25rem; color: #1a1a1a;">Dismiss Announcement</h3>
                    <p style="margin: 0 0 1.5rem 0; color: #666; line-height: 1.6;">How long would you like to dismiss this announcement?</p>
                    <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                        <button class="dismiss-24h" style="
                            padding: 0.75rem 1.5rem;
                            background: #007AFF;
                            color: white;
                            border: none;
                            border-radius: 8px;
                            cursor: pointer;
                            font-weight: 600;
                            transition: all 0.2s;
                        ">Remind me tomorrow</button>
                        <button class="dismiss-forever" style="
                            padding: 0.75rem 1.5rem;
                            background: #FF453A;
                            color: white;
                            border: none;
                            border-radius: 8px;
                            cursor: pointer;
                            font-weight: 600;
                            transition: all 0.2s;
                        ">Don't show again</button>
                        <button class="dismiss-cancel" style="
                            padding: 0.75rem 1.5rem;
                            background: #f5f5f7;
                            color: #1a1a1a;
                            border: none;
                            border-radius: 8px;
                            cursor: pointer;
                            font-weight: 600;
                            transition: all 0.2s;
                        ">Cancel</button>
                    </div>
                `;
                
                modal.appendChild(modalContent);
                document.body.appendChild(modal);
                
                // Add hover effects
                const buttons = modalContent.querySelectorAll('button');
                buttons.forEach(btn => {
                    btn.addEventListener('mouseenter', function() {
                        this.style.transform = 'translateY(-2px)';
                        this.style.boxShadow = '0 4px 12px rgba(0, 0, 0, 0.15)';
                    });
                    btn.addEventListener('mouseleave', function() {
                        this.style.transform = 'translateY(0)';
                        this.style.boxShadow = 'none';
                    });
                });
                
                // Handle dismiss for 24 hours
                modalContent.querySelector('.dismiss-24h').addEventListener('click', function() {
                    dismissForToday(announcementId);
                    dismissAnnouncement(announcementElement);
                    document.body.removeChild(modal);
                });
                
                // Handle dismiss forever
                modalContent.querySelector('.dismiss-forever').addEventListener('click', function() {
                    dismissForever(announcementId);
                    document.body.removeChild(modal);
                    
                    // Save to database via AJAX
                    fetch('<?php echo admin_url("admin-ajax.php"); ?>', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `action=lop_dismiss_announcement&announcement_id=${announcementId}&nonce=<?php echo wp_create_nonce("lop_dismiss_announcement"); ?>`
                    }).then(() => {
                        dismissAnnouncement(announcementElement);
                    }).catch(err => {
                        console.error('Error saving dismiss preference:', err);
                        dismissAnnouncement(announcementElement);
                    });
                });
                
                // Handle cancel
                modalContent.querySelector('.dismiss-cancel').addEventListener('click', function() {
                    document.body.removeChild(modal);
                });
                
                // Handle click outside
                modal.addEventListener('click', function(e) {
                    if (e.target === modal) {
                        document.body.removeChild(modal);
                    }
                });
            }
            
            function dismissAnnouncement(element) {
                element.classList.add('dismissing');
                
                const isBanner = element.classList.contains('lop-banner-announcement');
                const animationDuration = isBanner ? 400 : 300;
                
                setTimeout(() => {
                    if (isBanner) {
                        // Remove banner completely from DOM
                        element.remove();
                    } else {
                        // Hide regular announcement
                        element.style.display = 'none';
                    }
                    
                    // Check if all announcements are dismissed
                    const remainingAnnouncements = document.querySelectorAll(
                        '.lop-announcement-card:not([style*="display: none"]), .lop-banner-announcement'
                    );
                    
                    if (remainingAnnouncements.length === 0) {
                        const announcementsWrapper = document.querySelector('.lop-announcements-wrapper');
                        const bannerSection = document.querySelector('.lop-banner-announcements');
                        
                        if (announcementsWrapper) {
                            announcementsWrapper.style.display = 'none';
                        }
                        if (bannerSection && bannerSection.children.length === 0) {
                            bannerSection.style.display = 'none';
                        }
                    }
                }, animationDuration);
            }
            
            function dismissForToday(announcementId) {
                const dismissed = getDismissedToday();
                if (!dismissed.includes(announcementId)) {
                    dismissed.push(announcementId);
                }
                const expiry = new Date();
                expiry.setHours(23, 59, 59, 999); // Expire at end of day
                localStorage.setItem('lop_dismissed_today', JSON.stringify({
                    ids: dismissed,
                    expiry: expiry.getTime()
                }));
            }
            
            function dismissForever(announcementId) {
                const dismissed = getDismissedForever();
                if (!dismissed.includes(announcementId)) {
                    dismissed.push(announcementId);
                }
                localStorage.setItem('lop_dismissed_forever', JSON.stringify(dismissed));
            }
            
            function getDismissedToday() {
                try {
                    const data = JSON.parse(localStorage.getItem('lop_dismissed_today') || '{"ids":[],"expiry":0}');
                    const now = new Date().getTime();
                    
                    // Check if expired (past end of day)
                    if (data.expiry && now > data.expiry) {
                        localStorage.removeItem('lop_dismissed_today');
                        return [];
                    }
                    
                    return data.ids || [];
                } catch (e) {
                    return [];
                }
            }
            
            function getDismissedForever() {
                try {
                    return JSON.parse(localStorage.getItem('lop_dismissed_forever') || '[]');
                } catch (e) {
                    return [];
                }
            }

            // Enhanced progress bar animations
            function animateProgressBars() {
                const bars = document.querySelectorAll('.lop-progress-fill');
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            const bar = entry.target;
                            const width = bar.dataset.width || bar.style.width;
                            bar.style.width = '0%';
                            setTimeout(() => {
                                bar.style.width = width;
                            }, 200);
                            observer.unobserve(bar);
                        }
                    });
                }, { threshold: 0.1 });

                bars.forEach(bar => {
                    bar.dataset.width = bar.style.width;
                    observer.observe(bar);
                });
            }

            // Improve accessibility for keyboard navigation
            function enhanceAccessibility() {
                const cards = document.querySelectorAll('.lop-course-card[onclick]');
                cards.forEach(card => {
                    card.setAttribute('role', 'button');
                    card.setAttribute('tabindex', '0');

                    card.addEventListener('keydown', function(e) {
                        if (e.key === 'Enter' || e.key === ' ') {
                            e.preventDefault();
                            this.click();
                        }
                    });
                });
            }

            // Add loading states for better UX
            function addLoadingStates() {
                const buttons = document.querySelectorAll('.lop-button');
                buttons.forEach(button => {
                    button.addEventListener('click', function() {
                        if (this.href) {
                            this.style.opacity = '0.7';
                            this.style.pointerEvents = 'none';
                            setTimeout(() => {
                                this.style.opacity = '';
                                this.style.pointerEvents = '';
                            }, 2000);
                        }
                    });
                });
            }

            // Smooth scroll for better navigation
            function enableSmoothScrolling() {
                document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                    anchor.addEventListener('click', function (e) {
                        e.preventDefault();
                        const target = document.querySelector(this.getAttribute('href'));
                        if (target) {
                            target.scrollIntoView({
                                behavior: 'smooth',
                                block: 'start'
                            });
                        }
                    });
                });
            }

            // Search and Filter Functionality - My Courses
            function initMyCoursesSearch() {
                const searchInput = document.getElementById('lop-my-courses-search');
                const filterSelect = document.getElementById('lop-my-courses-filter');
                const courseGrid = document.querySelector('.lop-my-courses-grid');
                
                if (!searchInput || !filterSelect || !courseGrid) {
                    return;
                }
                
                const courseCards = courseGrid.querySelectorAll('.lop-course-card');

                function filterCourses() {
                    const searchTerm = searchInput.value.toLowerCase();
                    const filterValue = filterSelect.value;
                    let visibleCount = 0;

                    courseCards.forEach(function(card) {
                        const title = card.getAttribute('data-title') || '';
                        const status = card.getAttribute('data-status') || '';
                        
                        const matchesSearch = title.includes(searchTerm);
                        const matchesFilter = filterValue === 'all' || status === filterValue;
                        
                        if (matchesSearch && matchesFilter) {
                            card.style.display = '';
                            card.style.animation = 'fadeIn 0.3s ease-in';
                            visibleCount++;
                        } else {
                            card.style.display = 'none';
                        }
                    });

                    // Show "no results" message
                    let noResults = courseGrid.querySelector('.lop-no-results-my');
                    
                    if (visibleCount === 0 && !noResults) {
                        noResults = document.createElement('div');
                        noResults.className = 'lop-no-results-my';
                        noResults.style.cssText = 'grid-column: 1 / -1; text-align: center; padding: 3rem; color: #86868b;';
                        noResults.innerHTML = '<p style="margin: 0; font-size: 1.125rem;">🔍 No courses found</p><p style="margin: 0.5rem 0 0; font-size: 0.9375rem;">Try adjusting your search or filter</p>';
                        courseGrid.appendChild(noResults);
                    } else if (visibleCount > 0 && noResults) {
                        noResults.remove();
                    }
                }

                searchInput.addEventListener('input', filterCourses);
                filterSelect.addEventListener('change', filterCourses);
            }
            
            // Course Sorting and Load More Functionality
            function initCourseSorting() {
                const sortSelect = document.getElementById('lop-my-courses-sort');
                const courseGrid = document.querySelector('.lop-my-courses-grid');
                const loadMoreBtn = document.getElementById('lop-load-more-courses');
                const loadMoreContainer = document.querySelector('.lop-load-more-container');
                
                if (!sortSelect || !courseGrid) {
                    return;
                }
                
                // Load saved sort preference
                const savedSort = localStorage.getItem('lopCourseSort') || 'recent';
                sortSelect.value = savedSort;
                
                function sortCourses(sortType) {
                    const courseCards = Array.from(courseGrid.querySelectorAll('.lop-course-card'));
                    
                    courseCards.sort((a, b) => {
                        switch (sortType) {
                            case 'recent':
                                const aAccessed = parseInt(a.getAttribute('data-last-accessed')) || 0;
                                const bAccessed = parseInt(b.getAttribute('data-last-accessed')) || 0;
                                return bAccessed - aAccessed; // Most recent first
                                
                            case 'alphabetical':
                                const aTitle = a.getAttribute('data-title') || '';
                                const bTitle = b.getAttribute('data-title') || '';
                                return aTitle.localeCompare(bTitle);
                                
                            case 'progress':
                                const aProgress = parseInt(a.getAttribute('data-progress')) || 0;
                                const bProgress = parseInt(b.getAttribute('data-progress')) || 0;
                                return bProgress - aProgress; // Highest progress first
                                
                            case 'status':
                                const statusOrder = { 'in-progress': 0, 'not-started': 1, 'completed': 2 };
                                const aStatus = a.getAttribute('data-status') || '';
                                const bStatus = b.getAttribute('data-status') || '';
                                return (statusOrder[aStatus] || 3) - (statusOrder[bStatus] || 3);
                                
                            default:
                                return 0;
                        }
                    });
                    
                    // Re-append sorted cards and reset visibility
                    courseCards.forEach((card, index) => {
                        courseGrid.appendChild(card);
                        // Reset load more visibility (show first 6)
                        if (index < 6) {
                            card.classList.remove('lop-course-hidden');
                            card.classList.add('lop-course-show');
                        } else {
                            card.classList.add('lop-course-hidden');
                            card.classList.remove('lop-course-show');
                        }
                    });
                    
                    // Show/hide load more button
                    if (loadMoreContainer) {
                        const hiddenCards = courseGrid.querySelectorAll('.lop-course-hidden').length;
                        if (hiddenCards > 0) {
                            loadMoreContainer.classList.remove('hidden');
                            loadMoreBtn.innerHTML = `
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="6,9 12,15 18,9"></polyline>
                                </svg>
                                Load More Courses (${hiddenCards} remaining)
                            `;
                        } else {
                            loadMoreContainer.classList.add('hidden');
                        }
                    }
                }
                
                // Sort change handler
                sortSelect.addEventListener('change', function() {
                    const sortType = this.value;
                    localStorage.setItem('lopCourseSort', sortType);
                    sortCourses(sortType);
                });
                
                // Load more handler
                if (loadMoreBtn) {
                    loadMoreBtn.addEventListener('click', function() {
                        const hiddenCards = courseGrid.querySelectorAll('.lop-course-hidden');
                        const showCount = Math.min(6, hiddenCards.length); // Show 6 more at a time
                        
                        for (let i = 0; i < showCount; i++) {
                            const card = hiddenCards[i];
                            card.classList.remove('lop-course-hidden');
                            card.classList.add('lop-course-show');
                            
                            // Stagger the animation slightly
                            setTimeout(() => {
                                card.style.display = 'block';
                            }, i * 50);
                        }
                        
                        // Update button text or hide if no more cards
                        const remainingHidden = courseGrid.querySelectorAll('.lop-course-hidden').length;
                        if (remainingHidden === 0) {
                            loadMoreContainer.classList.add('hidden');
                        } else {
                            this.innerHTML = `
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polyline points="6,9 12,15 18,9"></polyline>
                                </svg>
                                Load More Courses (${remainingHidden} remaining)
                            `;
                        }
                    });
                }
                
                // Initial sort
                sortCourses(savedSort);
            }
            

            
            // Search Functionality - Discover More Courses
            function initDiscoverSearch() {
                const searchInput = document.getElementById('lop-discover-search');
                const courseGrid = document.querySelector('.lop-discover-grid');
                
                if (!searchInput || !courseGrid) {
                    return;
                }
                
                const courseCards = courseGrid.querySelectorAll('.lop-course-card');

                function filterCourses() {
                    const searchTerm = searchInput.value.toLowerCase();
                    let visibleCount = 0;

                    courseCards.forEach(function(card) {
                        const title = card.getAttribute('data-title') || '';
                        
                        if (title.includes(searchTerm)) {
                            card.style.display = '';
                            card.style.animation = 'fadeIn 0.3s ease-in';
                            visibleCount++;
                        } else {
                            card.style.display = 'none';
                        }
                    });

                    // Show "no results" message
                    let noResults = courseGrid.querySelector('.lop-no-results-discover');
                    
                    if (visibleCount === 0 && !noResults) {
                        noResults = document.createElement('div');
                        noResults.className = 'lop-no-results-discover';
                        noResults.style.cssText = 'grid-column: 1 / -1; text-align: center; padding: 3rem; color: #86868b;';
                        noResults.innerHTML = '<p style="margin: 0; font-size: 1.125rem;">🔍 No courses found</p><p style="margin: 0.5rem 0 0; font-size: 0.9375rem;">Try a different search term</p>';
                        courseGrid.appendChild(noResults);
                    } else if (visibleCount > 0 && noResults) {
                        noResults.remove();
                    }
                }

                searchInput.addEventListener('input', filterCourses);
            }

            // Initialize everything when DOM is ready
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', function() {
                    animateProgressBars();
                    enhanceAccessibility();
                    addLoadingStates();
                    enableSmoothScrolling();
                    initMyCoursesSearch();
                    initDiscoverSearch();
                    initCourseSorting();
                    initDismissibleAnnouncements();
                    initCollapsibleAnnouncements();
                });
            } else {
                animateProgressBars();
                enhanceAccessibility();
                addLoadingStates();
                enableSmoothScrolling();
                initMyCoursesSearch();
                initDiscoverSearch();
                initCourseSorting();
                initDismissibleAnnouncements();
                initCollapsibleAnnouncements();
            }


            // Update last activity timestamp
            if (window.lopUpdateActivity) {
                window.lopUpdateActivity();
            }
        })();
        </script>
        <?php
        return ob_get_clean();
    }

    // Register the shortcode
    add_shortcode( 'lop_dashboard', 'lop_apple_dashboard_shortcode' );

    // Helper function to update user activity (call this when user accesses courses)
    if ( ! function_exists( 'lop_update_user_activity' ) ) {
        function lop_update_user_activity( $user_id, $course_id ) {
            update_user_meta( $user_id, 'course_last_activity_' . $course_id, time() );

            // Update learning streak
            $last_activity_date = get_user_meta( $user_id, 'last_learning_date', true );
            $today = date( 'Y-m-d' );
            $yesterday = date( 'Y-m-d', strtotime( '-1 day' ) );

            if ( $last_activity_date === $yesterday ) {
                // Continue streak
                $current_streak = get_user_meta( $user_id, 'learning_streak', true ) ?: 0;
                update_user_meta( $user_id, 'learning_streak', $current_streak + 1 );
            } elseif ( $last_activity_date !== $today ) {
                // Reset streak
                update_user_meta( $user_id, 'learning_streak', 1 );
            }

            update_user_meta( $user_id, 'last_learning_date', $today );
        }
    }

    // Track when users access course pages to update "Continue Learning" section
    add_action( 'template_redirect', function() {
        if ( ! is_user_logged_in() ) {
            return;
        }

        $user_id = get_current_user_id();
        
        // Check if viewing a LearnDash course
        if ( is_singular( 'sfwd-courses' ) ) {
            $course_id = get_the_ID();
            lop_update_user_activity( $user_id, $course_id );
        }
        // Check if viewing a lesson (also track parent course)
        elseif ( is_singular( 'sfwd-lessons' ) && function_exists( 'learndash_get_course_id' ) ) {
            $lesson_id = get_the_ID();
            $course_id = learndash_get_course_id( $lesson_id );
            if ( $course_id ) {
                lop_update_user_activity( $user_id, $course_id );
            }
        }
        // Check if viewing a topic (also track parent course)
        elseif ( is_singular( 'sfwd-topic' ) && function_exists( 'learndash_get_course_id' ) ) {
            $topic_id = get_the_ID();
            $course_id = learndash_get_course_id( $topic_id );
            if ( $course_id ) {
                lop_update_user_activity( $user_id, $course_id );
            }
        }
    } );

    // Hook into LearnDash course access
    add_action( 'learndash_course_completed', function( $data ) {
        if ( isset( $data['user'] ) && isset( $data['course'] ) ) {
            $user_id = is_object( $data['user'] ) ? $data['user']->ID : $data['user'];
            $course_id = is_object( $data['course'] ) ? $data['course']->ID : $data['course'];
            lop_update_user_activity( $user_id, $course_id );

            // Award bonus points for completion
            $current_points = get_user_meta( $user_id, 'positivity_points', true ) ?: 0;
            update_user_meta( $user_id, 'positivity_points', $current_points + 100 );
        }
    } );

    // Hook into lesson completion for activity tracking
    add_action( 'learndash_lesson_completed', function( $data ) {
        if ( isset( $data['user'] ) && isset( $data['course'] ) ) {
            $user_id = is_object( $data['user'] ) ? $data['user']->ID : $data['user'];
            $course_id = is_object( $data['course'] ) ? $data['course']->ID : $data['course'];
            lop_update_user_activity( $user_id, $course_id );

            // Award points for lesson completion
            $current_points = get_user_meta( $user_id, 'positivity_points', true ) ?: 0;
            update_user_meta( $user_id, 'positivity_points', $current_points + 10 );
        }
    } );

    // AJAX handler for permanently dismissing announcements
    add_action( 'wp_ajax_lop_dismiss_announcement', 'lop_dismiss_announcement_handler' );
    function lop_dismiss_announcement_handler() {
        // Verify nonce
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'lop_dismiss_announcement' ) ) {
            wp_send_json_error( 'Invalid nonce' );
            return;
        }
        
        // Check user is logged in
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( 'User not logged in' );
            return;
        }
        
        $user_id = get_current_user_id();
        $announcement_id = isset( $_POST['announcement_id'] ) ? absint( $_POST['announcement_id'] ) : 0;
        
        if ( ! $announcement_id ) {
            wp_send_json_error( 'Invalid announcement ID' );
            return;
        }
        
        // Get current dismissed announcements
        $dismissed = get_user_meta( $user_id, 'lop_dismissed_announcements', true );
        if ( ! is_array( $dismissed ) ) {
            $dismissed = array();
        }
        
        // Add this announcement to dismissed list
        if ( ! in_array( $announcement_id, $dismissed ) ) {
            $dismissed[] = $announcement_id;
            update_user_meta( $user_id, 'lop_dismissed_announcements', $dismissed );
        }
        
        wp_send_json_success( 'Announcement dismissed permanently' );
    }

    // AJAX handler for marking announcements as seen
    add_action( 'wp_ajax_lop_mark_announcements_seen', 'lop_mark_announcements_seen_handler' );
    function lop_mark_announcements_seen_handler() {
        // Verify nonce
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'lop_mark_announcements_seen' ) ) {
            wp_send_json_error( 'Invalid nonce' );
            return;
        }
        
        // Check user is logged in
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( 'User not logged in' );
            return;
        }
        
        $user_id = get_current_user_id();
        $announcement_ids = isset( $_POST['announcement_ids'] ) ? sanitize_text_field( $_POST['announcement_ids'] ) : '';
        
        if ( empty( $announcement_ids ) ) {
            wp_send_json_error( 'No announcement IDs provided' );
            return;
        }
        
        // Convert comma-separated string to array of integers
        $ids = array_map( 'absint', explode( ',', $announcement_ids ) );
        $ids = array_filter( $ids ); // Remove any zero values
        
        if ( empty( $ids ) ) {
            wp_send_json_error( 'Invalid announcement IDs' );
            return;
        }
        
        // Get current seen announcements
        $seen = get_user_meta( $user_id, 'lop_seen_announcements', true );
        if ( ! is_array( $seen ) ) {
            $seen = array();
        }
        
        // Add new IDs to seen list
        $seen = array_unique( array_merge( $seen, $ids ) );
        update_user_meta( $user_id, 'lop_seen_announcements', $seen );
        
        wp_send_json_success( 'Announcements marked as seen' );
    }

    // AJAX handler for updating streak after page load
    add_action( 'wp_ajax_lop_update_streak', 'lop_update_streak_handler' );
    function lop_update_streak_handler() {
        // Verify nonce
        if ( ! wp_verify_nonce( $_POST['nonce'], 'lop_streak_nonce' ) ) {
            wp_send_json_error( 'Invalid nonce' );
            return;
        }
        
        $user_id = absint( $_POST['user_id'] );
        if ( $user_id !== get_current_user_id() ) {
            wp_send_json_error( 'Invalid user' );
            return;
        }
        
        $today = date( 'Y-m-d' );
        $yesterday = date( 'Y-m-d', strtotime( '-1 day' ) );
        
        // Check WordPress login data sources
        $login_sources = array();
        
        // 1. WordPress session tokens (most reliable)
        $session_tokens = get_user_meta( $user_id, 'session_tokens', true );
        if ( is_array( $session_tokens ) && ! empty( $session_tokens ) ) {
            foreach ( $session_tokens as $token_data ) {
                if ( isset( $token_data['login'] ) ) {
                    $login_date = date( 'Y-m-d', $token_data['login'] );
                    $login_sources[] = $login_date;
                }
            }
        }
        
        // 2. Memberium login tracking (if available)
        $memberium_last_login = get_user_meta( $user_id, 'memberium_last_login', true );
        if ( $memberium_last_login ) {
            $login_sources[] = date( 'Y-m-d', $memberium_last_login );
        }
        
        // 3. WordPress core user_activation_key updates (happens on login)
        $user_data = get_userdata( $user_id );
        if ( $user_data && $user_data->user_activation_key ) {
            // Check if activation key was updated recently (WordPress updates this on login)
            $key_time = get_user_meta( $user_id, 'wp_user_level', true ); // This gets updated on login
        }
        
        // 4. Check for recent database activity indicating login
        global $wpdb;
        $recent_meta_updates = $wpdb->get_results( $wpdb->prepare( "
            SELECT DISTINCT DATE(FROM_UNIXTIME(UNIX_TIMESTAMP())) as activity_date 
            FROM {$wpdb->usermeta} 
            WHERE user_id = %d 
            AND meta_key IN ('session_tokens', 'wp_capabilities', 'last_activity')
            AND DATE(FROM_UNIXTIME(UNIX_TIMESTAMP())) >= DATE_SUB(CURDATE(), INTERVAL 7 DAYS)
            ORDER BY activity_date DESC
        ", $user_id ) );
        
        foreach ( $recent_meta_updates as $update ) {
            $login_sources[] = $update->activity_date;
        }
        
        // 5. LearnDash activity as login indicator
        $ld_courses = learndash_user_get_enrolled_courses( $user_id );
        foreach ( $ld_courses as $course_id ) {
            $activity_meta = get_user_meta( $user_id, 'course_last_activity_' . $course_id, true );
            if ( $activity_meta ) {
                $activity_date = date( 'Y-m-d', $activity_meta );
                $login_sources[] = $activity_date;
            }
        }
        
        // Remove duplicates and sort
        $login_sources = array_unique( $login_sources );
        rsort( $login_sources ); // Most recent first
        
        error_log( 'AJAX STREAK - Login sources found: ' . implode( ', ', $login_sources ) );
        
        // Count consecutive days from today backwards
        $consecutive_days = 0;
        $check_date = $today;
        
        while ( in_array( $check_date, $login_sources ) ) {
            $consecutive_days++;
            $check_date = date( 'Y-m-d', strtotime( $check_date . ' -1 day' ) );
            
            // Safety check - don't go back more than 365 days
            if ( $consecutive_days > 365 ) break;
        }
        
        // If no activity today but there was yesterday, and we're checking right after login
        if ( $consecutive_days === 0 && in_array( $yesterday, $login_sources ) ) {
            // User was active yesterday, assume today's activity hasn't been recorded yet
            $consecutive_days = 1;
            error_log( 'AJAX STREAK - No today activity recorded yet, but yesterday found. Setting to 1.' );
        }
        
        // Minimum streak is 1 if any recent activity
        if ( $consecutive_days === 0 && ! empty( $login_sources ) ) {
            $consecutive_days = 1;
        }
        
        // Update the streak
        update_user_meta( $user_id, 'learning_streak', $consecutive_days );
        update_user_meta( $user_id, 'last_streak_update_date', $today );
        
        error_log( 'AJAX STREAK UPDATE - Calculated consecutive days: ' . $consecutive_days );
        
        wp_send_json_success( array( 
            'streak' => $consecutive_days,
            'sources' => $login_sources,
            'debug' => 'Found ' . count( $login_sources ) . ' login dates'
        ) );
    }