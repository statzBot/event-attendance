<?php
/**
 * Öffentliche Ansicht für Event-Liste
 *
 * @link       https://example.com
 * @since      1.0.0
 *
 * @package    Event_Attendance
 * @subpackage Event_Attendance/public/partials
 */

// Direkter Zugriff verhindern
if (!defined('WPINC')) {
    die;
}
?>

<div class="event-attendance-container">
    <div class="event-attendance-list">
        <h2><?php _e('Upcoming Events', 'event-attendance'); ?></h2>
        
        <?php foreach ($events as $index => $event) : 
            $event_date = new DateTime($event->date);
            $current_status = $event->status ? $event->status->status : '';
        ?>
            <div class="event-item">
                <div class="event-item-header">
                    <h3 class="event-title">
                        <a href="<?php echo esc_url(add_query_arg('event_id', $event->id)); ?>">
                            <?php echo esc_html($event->title); ?>
                        </a>
                    </h3>
                    <div class="event-meta">
                        <span class="event-date"><?php echo $event_date->format('d.m.Y H:i'); ?></span>
                        <span class="event-location"><?php echo esc_html($event->location); ?></span>
                    </div>
                </div>
                
                <div class="event-status">
                    <?php if ($participant_id) : ?>
                        <?php if ($current_status) : ?>
                            <div class="status-badge status-<?php echo esc_attr($current_status); ?>">
                                <?php 
                                switch ($current_status) {
                                    case 'attending':
                                        _e('You are attending', 'event-attendance');
                                        break;
                                    case 'declined_sick':
                                        _e('You declined (Sick)', 'event-attendance');
                                        break;
                                    case 'declined_vacation':
                                        _e('You declined (Vacation)', 'event-attendance');
                                        break;
                                    case 'declined_business':
                                        _e('You declined (Business Trip)', 'event-attendance');
                                        break;
                                    default:
                                        echo esc_html($current_status);
                                }
                                ?>
                            </div>
                        <?php else : ?>
                            <div class="status-badge status-none">
                                <?php _e('No response yet', 'event-attendance'); ?>
                            </div>
                        <?php endif; ?>
                        
                        <a href="<?php echo esc_url(add_query_arg('event_id', $event->id)); ?>" class="event-details-link">
                            <?php _e('Manage attendance', 'event-attendance'); ?>
                        </a>
                    <?php else : ?>
                        <p class="login-required">
                            <?php _e('Please log in to confirm attendance', 'event-attendance'); ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<style type="text/css">
    .event-attendance-container {
        margin: 20px 0;
    }
    
    .event-attendance-list {
        margin-bottom: 20px;
    }
    
    .event-item {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        align-items: center;
        padding: 15px;
        margin-bottom: 10px;
        background: #f9f9f9;
        border: 1px solid #e5e5e5;
        border-radius: 3px;
    }
    
    .event-item-header {
        flex: 1 1 60%;
        min-width: 250px;
        padding-right: 15px;
    }
    
    .event-title {
        margin: 0 0 5px 0;
    }
    
    .event-title a {
        text-decoration: none;
        color: #2271b1;
    }
    
    .event-title a:hover {
        color: #135e96;
        text-decoration: underline;
    }
    
    .event-meta {
        color: #666;
        font-size: 0.9em;
    }
    
    .event-meta .event-date {
        margin-right: 15px;
    }
    
    .event-status {
        flex: 1 0 30%;
        min-width: 200px;
        text-align: right;
    }
    
    .status-badge {
        display: inline-block;
        padding: 5px 10px;
        border-radius: 3px;
        font-size: 0.9em;
        margin-bottom: 8px;
    }
    
    .status-attending {
        background: #dff0d8;
        color: #3c763d;
        border: 1px solid #d6e9c6;
    }
    
    .status-declined_sick,
    .status-declined_vacation,
    .status-declined_business {
        background: #f2dede;
        color: #a94442;
        border: 1px solid #ebccd1;
    }
    
    .status-none {
        background: #fcf8e3;
        color: #8a6d3b;
        border: 1px solid #faebcc;
    }
    
    .event-details-link {
        display: inline-block;
        text-decoration: none;
        font-size: 0.9em;
    }
    
    .login-required {
        font-style: italic;
        color: #888;
        font-size: 0.9em;
        margin: 0;
    }
    
    @media (max-width: 600px) {
        .event-item {
            flex-direction: column;
            align-items: flex-start;
        }
        
        .event-item-header {
            padding-right: 0;
            margin-bottom: 10px;
            width: 100%;
        }
        
        .event-status {
            text-align: left;
            width: 100%;
        }
    }
</style>