<?php
/**
 * Öffentliche Ansicht für Event-Details
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

// Datum formatieren
$event_date = new DateTime($event->date);
?>

<div class="event-attendance-container">
    <div class="event-detail">
        <h2 class="event-title"><?php echo esc_html($event->title); ?></h2>
        
        <div class="event-meta">
            <p class="event-date">
                <strong><?php _e('Date:', 'event-attendance'); ?></strong> 
                <?php echo $event_date->format('d.m.Y H:i'); ?>
            </p>
            <p class="event-location">
                <strong><?php _e('Location:', 'event-attendance'); ?></strong> 
                <?php echo esc_html($event->location); ?>
            </p>
        </div>
        
        <?php if (!empty($event->description)) : ?>
            <div class="event-description">
                <h3><?php _e('Description', 'event-attendance'); ?></h3>
                <?php echo wpautop(esc_html($event->description)); ?>
            </div>
        <?php endif; ?>
        
        <div class="event-attendance-form">
            <h3><?php _e('Your Attendance', 'event-attendance'); ?></h3>
            
            <?php if ($participant_id) : ?>
                <div class="attendance-buttons" data-event-id="<?php echo esc_attr($event->id); ?>" data-participant-id="<?php echo esc_attr($participant_id); ?>">
                    <?php
                    // Aktueller Status
                    $current_status = $status ? $status->status : '';
                    $current_comment = $status ? $status->comment : '';
                    
                    // Button für Zusage
                    $attending_class = ($current_status === 'attending') ? 'active' : '';
                    echo '<button type="button" class="attendance-button ' . $attending_class . '" data-status="attending">' . __('Attending', 'event-attendance') . '</button>';
                    
                    // Button für Absage (krank)
                    $sick_class = ($current_status === 'declined_sick') ? 'active' : '';
                    echo '<button type="button" class="attendance-button ' . $sick_class . '" data-status="declined_sick">' . __('Sick', 'event-attendance') . '</button>';
                    
                    // Button für Absage (Urlaub)
                    $vacation_class = ($current_status === 'declined_vacation') ? 'active' : '';
                    echo '<button type="button" class="attendance-button ' . $vacation_class . '" data-status="declined_vacation">' . __('Vacation', 'event-attendance') . '</button>';
                    
                    // Button für Absage (Dienstreise)
                    $business_class = ($current_status === 'declined_business') ? 'active' : '';
                    echo '<button type="button" class="attendance-button ' . $business_class . '" data-status="declined_business">' . __('Business Trip', 'event-attendance') . '</button>';
                    
                    // Kommentarfeld
                    echo '<div class="attendance-comment">';
                    echo '<p><label for="attendance-comment-' . $event->id . '">' . __('Comment (optional):', 'event-attendance') . '</label></p>';
                    echo '<textarea id="attendance-comment-' . $event->id . '">' . esc_textarea($current_comment) . '</textarea>';
                    echo '</div>';
                    
                    // Feedback-Nachricht
                    echo '<div class="attendance-feedback"></div>';
                    ?>
                </div>
            <?php else : ?>
                <p class="error-message"><?php _e('You need to be logged in to confirm attendance.', 'event-attendance'); ?></p>
            <?php endif; ?>
        </div>
        
        <div class="event-attendees">
            <h3><?php _e('Attendees', 'event-attendance'); ?></h3>
            
            <?php if (empty($attendees)) : ?>
                <p><?php _e('No confirmed attendees yet.', 'event-attendance'); ?></p>
            <?php else : ?>
                <table class="attendees-table">
                    <thead>
                        <tr>
                            <th><?php _e('Name', 'event-attendance'); ?></th>
                            <th><?php _e('Status', 'event-attendance'); ?></th>
                            <th><?php _e('Comment', 'event-attendance'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($attendees as $attendee) : ?>
                            <tr>
                                <td><?php echo esc_html($attendee->name); ?></td>
                                <td>
                                    <?php
                                    switch ($attendee->status) {
                                        case 'attending':
                                            _e('Attending', 'event-attendance');
                                            break;
                                        case 'declined_sick':
                                            _e('Declined (Sick)', 'event-attendance');
                                            break;
                                        case 'declined_vacation':
                                            _e('Declined (Vacation)', 'event-attendance');
                                            break;
                                        case 'declined_business':
                                            _e('Declined (Business Trip)', 'event-attendance');
                                            break;
                                        default:
                                            echo esc_html($attendee->status);
                                    }
                                    ?>
                                </td>
                                <td><?php echo !empty($attendee->comment) ? esc_html($attendee->comment) : '-'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <p class="event-back-link">
            <a href="<?php echo esc_url(remove_query_arg('event_id')); ?>"><?php _e('← Back to Events List', 'event-attendance'); ?></a>
        </p>
    </div>
</div>

<script type="text/javascript">
    jQuery(document).ready(function($) {
        $('.attendance-button').on('click', function() {
            var button = $(this);
            var buttonContainer = button.parent();
            var eventId = buttonContainer.data('event-id');
            var participantId = buttonContainer.data('participant-id');
            var status = button.data('status');
            var comment = buttonContainer.find('textarea').val();
            var feedback = buttonContainer.find('.attendance-feedback');
            
            // Setze alle Buttons zurück
            buttonContainer.find('.attendance-button').removeClass('active');
            
            // Markiere ausgewählten Button
            button.addClass('active');
            
            // AJAX-Request
            $.ajax({
                url: event_attendance_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'update_attendance',
                    nonce: event_attendance_ajax.nonce,
                    event_id: eventId,
                    participant_id: participantId,
                    status: status,
                    comment: comment
                },
                beforeSend: function() {
                    feedback.html('<span class="loading"><?php echo esc_html__('Updating...', 'event-attendance'); ?></span>');
                },
                success: function(response) {
                    if (response.success) {
                        feedback.html('<span class="success">' + response.data.message + '</span>');
                        
                        // Seite nach kurzem Delay neu laden, um Teilnehmerliste zu aktualisieren
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        feedback.html('<span class="error">' + response.data.message + '</span>');
                    }
                },
                error: function() {
                    feedback.html('<span class="error"><?php echo esc_html__('Error updating attendance', 'event-attendance'); ?></span>');
                }
            });
        });
    });
</script>

<style type="text/css">
    .event-attendance-container {
        margin: 20px 0;
    }
    
    .event-detail {
        background: #f9f9f9;
        border: 1px solid #e5e5e5;
        border-radius: 3px;
        padding: 20px;
    }
    
    .event-title {
        margin-top: 0;
        margin-bottom: 15px;
        color: #23282d;
    }
    
    .event-meta {
        margin-bottom: 20px;
    }
    
    .event-meta p {
        margin: 5px 0;
    }
    
    .event-description {
        margin-bottom: 20px;
        padding-bottom: 20px;
        border-bottom: 1px solid #e5e5e5;
    }
    
    .event-attendance-form {
        margin-bottom: 20px;
        padding-bottom: 20px;
        border-bottom: 1px solid #e5e5e5;
    }
    
    .attendance-buttons {
        margin: 15px 0;
    }
    
    .attendance-button {
        display: inline-block;
        margin: 0 5px 5px 0;
        padding: 8px 15px;
        background: #f7f7f7;
        border: 1px solid #ccc;
        border-radius: 3px;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    
    .attendance-button:hover {
        background: #e7e7e7;
    }
    
    .attendance-button.active {
        background: #2271b1;
        color: #fff;
        border-color: #2271b1;
    }
    
    .attendance-comment {
        margin: 15px 0;
    }
    
    .attendance-comment textarea {
        width: 100%;
        height: 80px;
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 3px;
    }
    
    .attendance-feedback {
        min-height: 24px;
        margin: 10px 0;
        font-weight: bold;
    }
    
    .attendance-feedback .success {
        color: green;
    }
    
    .attendance-feedback .error {
        color: red;
    }
    
    .attendance-feedback .loading {
        color: #666;
    }
    
    .attendees-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
    }
    
    .attendees-table th, .attendees-table td {
        padding: 8px;
        text-align: left;
        border-bottom: 1px solid #e5e5e5;
    }
    
    .attendees-table th {
        background: #f1f1f1;
        font-weight: bold;
    }
    
    .event-back-link {
        margin-top: 20px;
    }
</style>