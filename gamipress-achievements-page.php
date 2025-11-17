<?php
/**
 * GamiPress Achievements Display Page
 * Clean professional page showing user achievements, points, and progress
 * 
 * Usage: [lop_achievements_page]
 */

function lop_achievements_page_shortcode() {
    // Check if user is logged in
    if ( ! is_user_logged_in() ) {
        return '<div class="lop-login-prompt" style="text-align: center; padding: 3rem; color: #86868b; font-family: -apple-system, BlinkMacSystemFont, sans-serif; background: #f5f5f7; border-radius: 12px; margin: 1rem 0;">
            <h3 style="margin-bottom: 1rem; color: #1d1d1f;">Please log in to view your achievements</h3>
            <p style="margin-bottom: 2rem;">Track your learning progress and celebrate your accomplishments!</p>
            <a href="' . wp_login_url( get_permalink() ) . '" style="background: #007AFF; color: white; padding: 0.75rem 2rem; border-radius: 8px; text-decoration: none; font-weight: 600;">Log In</a>
        </div>';
    }

    $user_id = get_current_user_id();
    
    // Get user's total points from all GamiPress point types
    $total_points = 0;
    $points_breakdown = array();
    
    if ( function_exists( 'gamipress_get_point_types' ) ) {
        $all_point_types = gamipress_get_point_types();
        
        foreach ( $all_point_types as $point_type_slug => $point_type ) {
            if ( function_exists( 'gamipress_get_user_points' ) ) {
                $points = gamipress_get_user_points( $user_id, $point_type_slug );
                if ( $points > 0 ) {
                    $points_breakdown[$point_type['plural_name']] = $points;
                    $total_points += $points;
                }
            }
        }
    }
    
    // Fallback using GamiPress meta keys
    if ( $total_points == 0 ) {
        global $wpdb;
        $results = $wpdb->get_results( $wpdb->prepare( 
            "SELECT meta_key, meta_value FROM {$wpdb->usermeta} 
             WHERE user_id = %d AND (meta_key LIKE '_gamipress_%%_points' OR meta_key LIKE '_gamipress_points' OR meta_key LIKE 'gamipress_%%_points')
             AND meta_value > 0 AND meta_value REGEXP '^[0-9]+$'",
            $user_id 
        ) );
        
        foreach ( $results as $result ) {
            $points = intval( $result->meta_value );
            if ( $points > 0 ) {
                $display_name = str_replace( array( '_gamipress_', '_points', 'gamipress_' ), '', $result->meta_key );
                $display_name = ucwords( str_replace( '_', ' ', $display_name ) );
                if ( empty( $display_name ) ) $display_name = 'Points';
                
                $points_breakdown[$display_name] = $points;
                $total_points += $points;
            }
        }
    }
    
    // Get user achievements using GamiPress functions (match dashboard query exactly)
    $user_achievements = array();
    if ( function_exists( 'gamipress_get_user_achievements' ) ) {
        $user_achievements = gamipress_get_user_achievements( array(
            'user_id' => $user_id,
            'achievement_type' => '', // Empty = all achievement types
            'orderby' => 'date_earned',
            'order' => 'DESC'
        ) );
    }
    
    // Fallback using GamiPress earnings table  
    if ( empty( $user_achievements ) ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'gamipress_user_earnings';
        if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" ) == $table_name ) {
            $earnings = $wpdb->get_results( $wpdb->prepare(
                "SELECT DISTINCT post_id FROM {$table_name} 
                 WHERE user_id = %d AND post_type IN ('achievement-type', 'badge', 'certificate')
                 ORDER BY date DESC",
                $user_id
            ) );
            
            foreach ( $earnings as $earning ) {
                $post = get_post( $earning->post_id );
                if ( $post && $post->post_status === 'publish' ) {
                    $user_achievements[] = $post;
                }
            }
        }
    }
    
    // Get user's rank using GamiPress rank functions
    $user_rank = '';
    $rank_requirements = array();
    $next_rank = '';
    $rank_ladder = array();
    $current_rank_type = '';
    
    if ( function_exists( 'gamipress_get_user_rank' ) && function_exists( 'gamipress_get_rank_types' ) ) {
        $rank_types = gamipress_get_rank_types();
        foreach ( $rank_types as $rank_type_slug => $rank_type ) {
            $rank = gamipress_get_user_rank( $user_id, $rank_type_slug );
            if ( $rank ) {
                $user_rank = $rank->post_title;
                $current_rank_type = $rank_type_slug;
                
                // Get all ranks for this type to show rank ladder
                $all_ranks = get_posts( array(
                    'post_type' => $rank_type_slug,
                    'post_status' => 'publish',
                    'numberposts' => -1,
                    'orderby' => 'menu_order',
                    'order' => 'ASC'
                ) );
                
                foreach ( $all_ranks as $rank_post ) {
                    $is_current = ( $rank_post->ID == $rank->ID );
                    $is_earned = false;
                    
                    // Check if user has earned this rank
                    if ( function_exists( 'gamipress_user_has_earned_rank' ) ) {
                        $is_earned = gamipress_user_has_earned_rank( $rank_post->ID, $user_id );
                    } else {
                        // Fallback: check if this rank is lower or equal to current rank
                        $is_earned = ( $rank_post->menu_order <= $rank->menu_order );
                    }
                    
                    $rank_ladder[] = array(
                        'id' => $rank_post->ID,
                        'title' => $rank_post->post_title,
                        'description' => wp_trim_words( $rank_post->post_content, 15 ),
                        'is_current' => $is_current,
                        'is_earned' => $is_earned,
                        'image' => get_the_post_thumbnail_url( $rank_post->ID, 'thumbnail' )
                    );
                }
                
                // Get next rank if available
                if ( function_exists( 'gamipress_get_next_user_rank' ) ) {
                    $next_rank_obj = gamipress_get_next_user_rank( $user_id, $rank_type_slug );
                    if ( $next_rank_obj ) {
                        $next_rank = $next_rank_obj->post_title;
                        // Get rank requirements
                        if ( function_exists( 'gamipress_get_rank_requirements' ) ) {
                            $rank_requirements = gamipress_get_rank_requirements( $next_rank_obj->ID );
                        }
                    }
                }
                break; // Use first rank type found
            }
        }
    }
    
    // Get all available achievements (not yet earned)
    $available_achievements = array();
    if ( function_exists( 'gamipress_get_achievement_types' ) ) {
        $achievement_types = gamipress_get_achievement_types();
        foreach ( $achievement_types as $achievement_type_slug => $achievement_type ) {
            $all_achievements = get_posts( array(
                'post_type' => $achievement_type_slug,
                'post_status' => 'publish',
                'numberposts' => -1,
                'orderby' => 'menu_order',
                'order' => 'ASC'
            ) );
            
            foreach ( $all_achievements as $achievement ) {
                // Check if user has already earned this achievement
                $already_earned = false;
                foreach ( $user_achievements as $earned_achievement ) {
                    $earned_id = is_object( $earned_achievement ) ? $earned_achievement->ID : $earned_achievement;
                    if ( $earned_id == $achievement->ID ) {
                        $already_earned = true;
                        break;
                    }
                }
                
                if ( ! $already_earned ) {
                    $available_achievements[] = $achievement;
                }
            }
        }
    }
    
    // Get total available achievements count
    $total_available = count( $available_achievements );
    $total_earned = count( $user_achievements );
    $completion_percentage = $total_earned > 0 && ( $total_earned + $total_available ) > 0 ? 
        round( ( $total_earned / ( $total_earned + $total_available ) ) * 100 ) : 0;

    
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
    
    <div class="lop-achievements-page">
        <!-- Hero Section -->
        <div class="lop-hero-section">
            <h1 class="lop-hero-title">🏆 Your Learning Journey</h1>
            <p class="lop-hero-subtitle">Track your progress, unlock achievements, and advance through the ranks!</p>
            
            <div class="lop-stats-grid">
                <div class="lop-hero-stat">
                    <span class="lop-hero-stat-icon">💎</span>
                    <div class="lop-hero-stat-number"><?php echo number_format( $total_points ); ?></div>
                    <div class="lop-hero-stat-label">Total Points</div>
                    <?php if ( ! empty( $points_breakdown ) ) : ?>
                        <div style="font-size: 0.75rem; color: var(--lop-gray-400); margin-top: 0.25rem; line-height: 1.2;">
                            <?php foreach ( $points_breakdown as $type => $points ) : ?>
                                <div><?php echo esc_html( $type . ': ' . number_format( $points ) ); ?></div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="lop-hero-stat">
                    <span class="lop-hero-stat-icon">🏅</span>
                    <div class="lop-hero-stat-number"><?php echo $total_earned; ?></div>
                    <div class="lop-hero-stat-label">Achievements Earned</div>
                </div>
                <div class="lop-hero-stat lop-completion-stat">
                    <span class="lop-hero-stat-icon">📊</span>
                    <div class="lop-hero-stat-number"><?php echo $completion_percentage; ?>%</div>
                    <div class="lop-hero-stat-label">Completion Rate</div>
                    <div class="lop-mini-progress">
                        <div class="lop-mini-progress-bar" style="width: <?php echo $completion_percentage; ?>%;"></div>
                    </div>
                </div>
                <?php if ( ! empty( $user_rank ) ) : ?>
                <div class="lop-hero-stat">
                    <span class="lop-hero-stat-icon">�</span>
                    <div class="lop-hero-stat-number" style="font-size: 1.5rem;"><?php echo esc_html( $user_rank ); ?></div>
                    <div class="lop-hero-stat-label">Current Rank</div>
                </div>
                <?php endif; ?>
            </div>

            <?php if ( ! empty( $next_rank ) && ! empty( $rank_requirements ) ) : ?>
            <!-- Rank Progress -->
            <div class="lop-level-progress" style="background: var(--lop-gray-50); border: 1px solid var(--lop-gray-100); border-radius: var(--lop-radius-lg); padding: var(--lop-space-lg);">
                <div class="lop-level-info" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--lop-space-md);">
                    <span class="lop-level-current" style="font-size: 1.25rem; font-weight: var(--lop-font-weight-bold); color: var(--lop-gray-900);"><?php echo esc_html( $user_rank ); ?></span>
                    <span class="lop-level-next" style="font-size: 0.875rem; color: var(--lop-gray-500);">Next: <?php echo esc_html( $next_rank ); ?></span>
                </div>
                <div style="font-size: 0.875rem; color: var(--lop-gray-600); text-align: center;">
                    <?php 
                    if ( ! empty( $rank_requirements ) ) {
                        echo 'Requirements to reach ' . esc_html( $next_rank ) . ':';
                        echo '<ul style="list-style: none; padding: 0; margin: 0.5rem 0;">';
                        foreach ( $rank_requirements as $requirement ) {
                            $req_text = isset( $requirement['title'] ) ? $requirement['title'] : 'Complete requirement';
                            echo '<li style="margin: 0.25rem 0;">• ' . esc_html( $req_text ) . '</li>';
                        }
                        echo '</ul>';
                    } else {
                        echo 'Keep earning points and achievements to advance!';
                    }
                    ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <?php if ( ! empty( $rank_ladder ) ) : ?>
        <!-- Rank Ladder Section -->
        <section class="lop-section">
            <h2 class="lop-section-title">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M16 16v4a2 2 0 0 1-2 2h-4a2 2 0 0 1-2-2v-4"></path>
                    <rect x="4" y="12" width="16" height="8" rx="2"></rect>
                    <path d="M9 8V6a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2"></path>
                    <rect x="8" y="4" width="8" height="8" rx="2"></rect>
                </svg>
                Rank Progression
            </h2>
            
            <div class="lop-rank-timeline">
                <?php 
                $total_ranks = count( $rank_ladder );
                $earned_ranks = 0;
                foreach ( $rank_ladder as $rank ) {
                    if ( $rank['is_earned'] || $rank['is_current'] ) $earned_ranks++;
                }
                $progress_percentage = $total_ranks > 0 ? round(($earned_ranks / $total_ranks) * 100) : 0;
                ?>
                
                <!-- Timeline Header -->
                <div class="lop-timeline-header">
                    <div class="lop-timeline-title">
                        <h3>Your Rank Journey</h3>
                        <p>Progress through your learning adventure</p>
                    </div>
                    <div class="lop-timeline-stats">
                        <div class="lop-timeline-stat">
                            <span class="lop-stat-number"><?php echo $earned_ranks; ?></span>
                            <span class="lop-stat-label">Earned</span>
                        </div>
                        <div class="lop-timeline-divider"></div>
                        <div class="lop-timeline-stat">
                            <span class="lop-stat-number"><?php echo $total_ranks - $earned_ranks; ?></span>
                            <span class="lop-stat-label">Remaining</span>
                        </div>
                    </div>
                </div>

                <!-- Timeline Progress Track -->
                <div class="lop-timeline-track">
                    <div class="lop-timeline-progress" style="width: <?php echo $progress_percentage; ?>%;"></div>
                    <div class="lop-timeline-dots">
                        <?php foreach ( $rank_ladder as $index => $rank ) : ?>
                        <div class="lop-timeline-dot <?php echo $rank['is_current'] ? 'current' : ( $rank['is_earned'] ? 'earned' : 'future' ); ?>" 
                             style="left: <?php echo $total_ranks > 1 ? ($index / ($total_ranks - 1)) * 100 : 50; ?>%;">
                            <div class="lop-dot-pulse <?php echo $rank['is_current'] ? 'active' : ''; ?>"></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Timeline Items -->
                <div class="lop-timeline-items">
                    <?php foreach ( $rank_ladder as $index => $rank ) : ?>
                    <div class="lop-timeline-item <?php echo $rank['is_current'] ? 'current' : ( $rank['is_earned'] ? 'earned' : 'future' ); ?>" 
                         data-rank="<?php echo $index + 1; ?>">
                        
                        <div class="lop-timeline-marker">
                            <div class="lop-marker-icon">
                                <?php if ( $rank['image'] ) : ?>
                                    <img src="<?php echo esc_url( $rank['image'] ); ?>" alt="<?php echo esc_attr( $rank['title'] ); ?>">
                                <?php else : ?>
                                    <?php if ( $rank['is_current'] ) : ?>
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                                        </svg>
                                    <?php elseif ( $rank['is_earned'] ) : ?>
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <polyline points="20,6 9,17 4,12"/>
                                        </svg>
                                    <?php else : ?>
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <circle cx="12" cy="12" r="10"/>
                                            <path d="M8 12h8"/>
                                        </svg>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                            <div class="lop-marker-line <?php echo $index < count($rank_ladder) - 1 ? 'visible' : 'hidden'; ?>"></div>
                        </div>

                        <div class="lop-timeline-content">
                            
                            <div class="lop-rank-card">
                                <div class="lop-rank-header">
                                    <div class="lop-rank-title-group">
                                        <h4 class="lop-rank-title"><?php echo esc_html( $rank['title'] ); ?></h4>
                                        <span class="lop-rank-number">Rank <?php echo $index + 1; ?></span>
                                    </div>
                                    <div class="lop-rank-status-badge">
                                        <?php if ( $rank['is_current'] ) : ?>
                                            <span class="lop-status-text">Current</span>
                                            <div class="lop-status-indicator current"></div>
                                        <?php elseif ( $rank['is_earned'] ) : ?>
                                            <span class="lop-status-text">Completed</span>
                                            <div class="lop-status-indicator earned"></div>
                                        <?php else : ?>
                                            <span class="lop-status-text">Upcoming</span>
                                            <div class="lop-status-indicator future"></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <?php if ( $rank['description'] ) : ?>
                                    <p class="lop-rank-description"><?php echo esc_html( $rank['description'] ); ?></p>
                                <?php endif; ?>
                                
                                <?php if ( $rank['is_current'] ) : ?>
                                    <div class="lop-current-rank-highlight">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/>
                                            <polyline points="3.27,6.96 12,12.01 20.73,6.96"/>
                                            <line x1="12" y1="22.08" x2="12" y2="12"/>
                                        </svg>
                                        <span>You are here - Keep up the great work!</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <!-- Achievements Section -->
        <section class="lop-section">
            <h2 class="lop-section-title">
                <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="8" r="7"></circle>
                    <polyline points="8.21,13.89 7,23 12,20 17,23 15.79,13.88"></polyline>
                </svg>
                Your Achievements
            </h2>

            <!-- Achievement View Switcher -->
            <div class="lop-achievement-switcher">
                <div class="lop-switcher-tabs">
                    <button class="lop-switcher-tab active" onclick="switchAchievementView('earned')" data-view="earned">
                        <span class="lop-tab-icon">✨</span>
                        <span class="lop-tab-label">Earned</span>
                        <span class="lop-tab-count"><?php echo $total_earned; ?></span>
                    </button>
                    <button class="lop-switcher-tab" onclick="switchAchievementView('available')" data-view="available">
                        <span class="lop-tab-icon">🎯</span>
                        <span class="lop-tab-label">Available</span>
                        <span class="lop-tab-count"><?php echo $total_available; ?></span>
                    </button>
                </div>
                
                <?php if ( $completion_percentage > 0 ) : ?>
                <div class="lop-achievement-progress">
                    <div class="lop-progress-label"><?php echo $completion_percentage; ?>% Complete</div>
                    <div class="lop-progress-bar">
                        <div class="lop-progress-fill" style="width: <?php echo $completion_percentage; ?>%;"></div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Achievement Type Filters -->
            <div class="lop-filter-tabs lop-type-filters">
                <button class="lop-filter-tab active" onclick="filterAchievements('all')">
                    <span>All Types</span>
                </button>
                <button class="lop-filter-tab" onclick="filterAchievements('course-completion')">
                    <span class="lop-filter-icon">🎓</span>
                    <span>Courses</span>
                </button>
                <button class="lop-filter-tab" onclick="filterAchievements('streak')">
                    <span class="lop-filter-icon">🔥</span>
                    <span>Streaks</span>
                </button>
                <button class="lop-filter-tab" onclick="filterAchievements('milestone')">
                    <span class="lop-filter-icon">🏆</span>
                    <span>Milestones</span>
                </button>
                <button class="lop-filter-tab" onclick="filterAchievements('badge')">
                    <span class="lop-filter-icon">⭐</span>
                    <span>Badges</span>
                </button>
            </div>

            <!-- Earned Achievements View -->
            <div id="earned-achievements" class="lop-achievement-view active">
                <?php if ( ! empty( $user_achievements ) ) : ?>
                    <div class="lop-achievements-grid" id="earned-grid">
                        <?php foreach ( $user_achievements as $achievement ) :
                            $achievement_id = is_object( $achievement ) ? $achievement->ID : $achievement;
                            $achievement_post = get_post( $achievement_id );
                            
                            if ( ! $achievement_post ) continue;
                            
                            $achievement_title = $achievement_post->post_title;
                            $achievement_content = $achievement_post->post_content;
                            $achievement_excerpt = wp_trim_words( $achievement_content, 15 );
                            $achievement_image = get_the_post_thumbnail_url( $achievement_id, 'medium' );
                            
                            // Get achievement type
                            $achievement_type = get_post_type( $achievement_id );
                            $achievement_type_obj = get_post_type_object( $achievement_type );
                            $type_name = $achievement_type_obj ? $achievement_type_obj->labels->singular_name : 'Achievement';
                            
                            // Get date earned from GamiPress earnings
                            $date_earned = '';
                            if ( function_exists( 'gamipress_get_user_last_activity' ) ) {
                                global $wpdb;
                                $table_name = $wpdb->prefix . 'gamipress_user_earnings';
                                if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" ) == $table_name ) {
                                    $earning = $wpdb->get_row( $wpdb->prepare(
                                        "SELECT date FROM {$table_name} WHERE user_id = %d AND post_id = %d ORDER BY date DESC LIMIT 1",
                                        $user_id, $achievement_id
                                    ) );
                                    if ( $earning ) {
                                        $date_earned = strtotime( $earning->date );
                                    }
                                }
                            }
                            
                            // Get points awarded for this achievement
                            $points_awarded = get_post_meta( $achievement_id, '_gamipress_points', true );
                            if ( ! $points_awarded ) {
                                $points_awarded = get_post_meta( $achievement_id, '_gamipress_achievement_points', true );
                            }
                            $points_awarded = intval( $points_awarded );
                            
                            // Get achievement type icon
                            $type_icon = '🏅';
                            switch ( $achievement_type ) {
                                case 'badge':
                                    $type_icon = '⭐';
                                    break;
                                case 'certificate':
                                    $type_icon = '🎓';
                                    break;
                                default:
                                    $type_icon = '🏅';
                            }
                        ?>
                            <div class="lop-achievement-card earned" data-type="<?php echo esc_attr( $achievement_type ); ?>">
                                <div class="lop-achievement-header">
                                    <?php if ( $achievement_image ) : ?>
                                        <img src="<?php echo esc_url( $achievement_image ); ?>" alt="<?php echo esc_attr( $achievement_title ); ?>" class="lop-achievement-icon" style="width: 48px; height: 48px; border-radius: 50%; object-fit: cover;">
                                    <?php else : ?>
                                        <div class="lop-achievement-icon" style="width: 48px; height: 48px; background: var(--lop-green-100); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
                                            <?php echo $type_icon; ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="lop-achievement-info">
                                        <h3 class="lop-achievement-title" style="color: var(--lop-gray-900); font-size: 1.125rem; font-weight: var(--lop-font-weight-semibold); margin-bottom: var(--lop-space-xs);"><?php echo esc_html( $achievement_title ); ?></h3>
                                        
                                        <div style="display: flex; gap: var(--lop-space-sm); flex-wrap: wrap;">
                                            <div class="lop-achievement-status earned" style="color: var(--lop-green-600); font-size: 0.75rem; font-weight: var(--lop-font-weight-semibold);">
                                                ✓ Earned
                                            </div>
                                            
                                            <?php if ( $points_awarded > 0 ) : ?>
                                                <div class="lop-achievement-points">
                                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                        <polygon points="12,2 15.09,8.26 22,9.27 17,14.14 18.18,21.02 12,17.77 5.82,21.02 7,14.14 2,9.27 8.91,8.26"></polygon>
                                                    </svg>
                                                    +<?php echo number_format( $points_awarded ); ?> points
                                                </div>
                                            <?php endif; ?>
                                            
                                            <div class="lop-achievement-type-badge">
                                                <?php echo $type_icon; ?> <?php echo esc_html( $type_name ); ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <?php if ( $achievement_excerpt ) : ?>
                                    <p class="lop-achievement-description" style="color: var(--lop-gray-600); font-size: 0.875rem; line-height: 1.4; margin: var(--lop-space-md) 0;">
                                        <?php echo esc_html( $achievement_excerpt ); ?>
                                    </p>
                                <?php endif; ?>
                                
                                <?php if ( $date_earned ) : ?>
                                    <div class="lop-achievement-date" style="color: var(--lop-gray-500); font-size: 0.75rem; display: flex; align-items: center; gap: 0.25rem;">
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <circle cx="12" cy="12" r="10"></circle>
                                            <polyline points="12,6 12,12 16,14"></polyline>
                                        </svg>
                                        Earned <?php echo human_time_diff( $date_earned, current_time( 'timestamp' ) ); ?> ago
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else : ?>
                    <div class="lop-empty-state">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                            <circle cx="12" cy="8" r="7"></circle>
                            <polyline points="8.21,13.89 7,23 12,20 17,23 15.79,13.88"></polyline>
                        </svg>
                        <h3>No Achievements Yet</h3>
                        <p>Start learning to unlock your first achievements!<br>Complete courses, maintain streaks, and reach milestones to earn rewards.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Available Achievements View -->
            <div id="available-achievements" class="lop-achievement-view">
                <?php if ( ! empty( $available_achievements ) ) : ?>
                    <div class="lop-achievements-grid" id="available-grid">
                        <?php foreach ( $available_achievements as $achievement ) :
                            $achievement_id = is_object( $achievement ) ? $achievement->ID : $achievement;
                            $achievement_post = get_post( $achievement_id );
                            
                            if ( ! $achievement_post ) continue;
                            
                            $achievement_title = $achievement_post->post_title;
                            $achievement_content = $achievement_post->post_content;
                            $achievement_excerpt = wp_trim_words( $achievement_content, 15 );
                            $achievement_image = get_the_post_thumbnail_url( $achievement_id, 'medium' );
                            
                            // Get achievement type
                            $achievement_type = get_post_type( $achievement_id );
                            $achievement_type_obj = get_post_type_object( $achievement_type );
                            $type_name = $achievement_type_obj ? $achievement_type_obj->labels->singular_name : 'Achievement';
                            
                            // Get points awarded for this achievement
                            $points_awarded = get_post_meta( $achievement_id, '_gamipress_points', true );
                            if ( ! $points_awarded ) {
                                $points_awarded = get_post_meta( $achievement_id, '_gamipress_achievement_points', true );
                            }
                            $points_awarded = intval( $points_awarded );
                            
                            // Get achievement type icon
                            $type_icon = '🔒';
                            switch ( $achievement_type ) {
                                case 'badge':
                                    $type_icon = '🔒';
                                    break;
                                case 'certificate':
                                    $type_icon = '🔒';
                                    break;
                                default:
                                    $type_icon = '🔒';
                            }
                        ?>
                            <div class="lop-achievement-card available" data-type="<?php echo esc_attr( $achievement_type ); ?>">
                                <div class="lop-achievement-header">
                                    <?php if ( $achievement_image ) : ?>
                                        <img src="<?php echo esc_url( $achievement_image ); ?>" alt="<?php echo esc_attr( $achievement_title ); ?>" class="lop-achievement-icon" style="width: 48px; height: 48px; border-radius: 50%; object-fit: cover; opacity: 0.5;">
                                    <?php else : ?>
                                        <div class="lop-achievement-icon" style="width: 48px; height: 48px; background: var(--lop-gray-100); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; opacity: 0.7;">
                                            <?php echo $type_icon; ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="lop-achievement-info">
                                        <h3 class="lop-achievement-title" style="color: var(--lop-gray-600); font-size: 1.125rem; font-weight: var(--lop-font-weight-semibold); margin-bottom: var(--lop-space-xs);"><?php echo esc_html( $achievement_title ); ?></h3>
                                        
                                        <div style="display: flex; gap: var(--lop-space-sm); flex-wrap: wrap;">
                                            <div class="lop-achievement-status available" style="color: var(--lop-gray-500); font-size: 0.75rem; font-weight: var(--lop-font-weight-semibold);">
                                                🔒 Available to Earn
                                            </div>
                                            
                                            <?php if ( $points_awarded > 0 ) : ?>
                                                <div class="lop-achievement-points" style="color: var(--lop-gray-500);">
                                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                        <polygon points="12,2 15.09,8.26 22,9.27 17,14.14 18.18,21.02 12,17.77 5.82,21.02 7,14.14 2,9.27 8.91,8.26"></polygon>
                                                    </svg>
                                                    +<?php echo number_format( $points_awarded ); ?> points
                                                </div>
                                            <?php endif; ?>
                                            
                                            <div class="lop-achievement-type-badge" style="color: var(--lop-gray-500);">
                                                🏅 <?php echo esc_html( $type_name ); ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <?php if ( $achievement_excerpt ) : ?>
                                    <p class="lop-achievement-description" style="color: var(--lop-gray-500); font-size: 0.875rem; line-height: 1.4; margin: var(--lop-space-md) 0;">
                                        <?php echo esc_html( $achievement_excerpt ); ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else : ?>
                    <div class="lop-empty-state">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1">
                            <circle cx="12" cy="8" r="7"></circle>
                            <polyline points="8.21,13.89 7,23 12,20 17,23 15.79,13.88"></polyline>
                        </svg>
                        <h3>All Achievements Earned!</h3>
                        <p>Congratulations! You've earned all available achievements.</p>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <script>
    (function() {
        'use strict';

        // Switch between earned and available achievements
        window.switchAchievementView = function(view) {
            const earnedView = document.getElementById('earned-achievements');
            const availableView = document.getElementById('available-achievements');
            const tabs = document.querySelectorAll('.lop-filter-tab');
            
            // Update active tab
            tabs.forEach(tab => tab.classList.remove('active'));
            event.target.classList.add('active');
            
            // Switch views
            if (view === 'earned') {
                earnedView.classList.add('active');
                availableView.classList.remove('active');
            } else if (view === 'available') {
                availableView.classList.add('active');
                earnedView.classList.remove('active');
            }
        };

        // Filter achievements by type
        window.filterAchievements = function(type) {
            const earnedGrid = document.getElementById('earned-grid');
            const availableGrid = document.getElementById('available-grid');
            const tabs = document.querySelectorAll('.lop-filter-tab');
            
            // Update active tab (only for filter tabs, not view tabs)
            const filterTabs = Array.from(tabs).filter(tab => 
                !tab.onclick || !tab.onclick.toString().includes('switchAchievementView')
            );
            filterTabs.forEach(tab => tab.classList.remove('active'));
            event.target.classList.add('active');
            
            // Filter cards in both grids
            [earnedGrid, availableGrid].forEach(grid => {
                if (!grid) return;
                const cards = grid.querySelectorAll('.lop-achievement-card');
                cards.forEach(card => {
                    if (type === 'all' || card.dataset.type === type) {
                        card.style.display = 'block';
                        setTimeout(() => {
                            card.style.opacity = '1';
                            card.style.transform = 'translateY(0)';
                        }, 50);
                    } else {
                        card.style.opacity = '0';
                        card.style.transform = 'translateY(20px)';
                        setTimeout(() => {
                            card.style.display = 'none';
                        }, 300);
                    }
                });
            });
        };

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            // Add entrance animations
            const cards = document.querySelectorAll('.lop-achievement-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });

            // Animate rank progress section if it exists
            const rankProgress = document.querySelector('.lop-level-progress');
            if (rankProgress) {
                rankProgress.style.opacity = '0';
                rankProgress.style.transform = 'translateY(10px)';
                setTimeout(() => {
                    rankProgress.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                    rankProgress.style.opacity = '1';
                    rankProgress.style.transform = 'translateY(0)';
                }, 200);
            }
        });
    })();
    </script>

    <?php
    return ob_get_clean();
}

// Register the shortcode
add_shortcode( 'lop_achievements_page', 'lop_achievements_page_shortcode' );


?>