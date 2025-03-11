<?php
/**
 * Admin-Ansicht für wiederkehrende Termine
 *
 * @link       https://example.com
 * @since      1.0.0
 *
 * @package    Event_Attendance
 * @subpackage Event_Attendance/admin/partials
 */

// Direkter Zugriff verhindern
if (!defined('WPINC')) {
    die;
}
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="event-attendance-admin">
        <!-- Formular für wiederkehrende Termine -->
        <div class="recurring-form-container">
            <h2><?php _e('Create Recurring Events', 'event-attendance'); ?></h2>
            
            <p class="description">
                <?php _e('This tool allows you to create multiple events at once based on a recurring pattern.', 'event-attendance'); ?>
            </p>
            
            <form id="recurring-form" method="post">
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="recurring-title"><?php _e('Title', 'event-attendance'); ?> *</label>
                        </th>
                        <td>
                            <input type="text" id="recurring-title" name="title" class="regular-text" required>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="recurring-start-date"><?php _e('Start Date', 'event-attendance'); ?> *</label>
                        </th>
                        <td>
                            <input type="date" id="recurring-start-date" name="start_date" required>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="recurring-end-date"><?php _e('End Date', 'event-attendance'); ?> *</label>
                        </th>
                        <td>
                            <input type="date" id="recurring-end-date" name="end_date" required>
                            <p class="description"><?php _e('All events up to and including this date will be created.', 'event-attendance'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="recurring-time"><?php _e('Time', 'event-attendance'); ?> *</label>
                        </th>
                        <td>
                            <input type="time" id="recurring-time" name="time" required>
                            <p class="description"><?php _e('The time for all events in this series.', 'event-attendance'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="recurring-location"><?php _e('Location', 'event-attendance'); ?> *</label>
                        </th>
                        <td>
                            <input type="text" id="recurring-location" name="location" class="regular-text" required>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="recurring-description"><?php _e('Description', 'event-attendance'); ?></label>
                        </th>
                        <td>
                            <textarea id="recurring-description" name="description" rows="5" class="large-text"></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="recurring-interval"><?php _e('Recurring Pattern', 'event-attendance'); ?> *</label>
                        </th>
                        <td>
                            <select id="recurring-interval" name="interval" required>
                                <option value="1"><?php _e('Every week', 'event-attendance'); ?></option>
                                <option value="2"><?php _e('Every 2 weeks', 'event-attendance'); ?></option>
                                <option value="4"><?php _e('Every 4 weeks', 'event-attendance'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="recurring-day-of-week"><?php _e('Day of Week', 'event-attendance'); ?></label>
                        </th>
                        <td>
                            <select id="recurring-day-of-week" name="day_of_week">
                                <option value="-1"><?php _e('Start from specified date', 'event-attendance'); ?></option>
                                <option value="1"><?php _e('Monday', 'event-attendance'); ?></option>
                                <option value="2"><?php _e('Tuesday', 'event-attendance'); ?></option>
                                <option value="3"><?php _e('Wednesday', 'event-attendance'); ?></option>
                                <option value="4"><?php _e('Thursday', 'event-attendance'); ?></option>
                                <option value="5"><?php _e('Friday', 'event-attendance'); ?></option>
                                <option value="6"><?php _e('Saturday', 'event-attendance'); ?></option>
                                <option value="0"><?php _e('Sunday', 'event-attendance'); ?></option>
                            </select>
                            <p class="description"><?php _e('If selected, the first event will start on the first occurrence of this day after the start date.', 'event-attendance'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <button type="submit" id="recurring-submit" class="button button-primary">
                        <?php _e('Create Events', 'event-attendance'); ?>
                    </button>
                </p>
                
                <div id="recurring-message" class="notice" style="display:none;"></div>
                <div id="created-events-container" style="display:none;">
                    <h3><?php _e('Created Events', 'event-attendance'); ?></h3>
                    <ul id="created-events-list"></ul>
                </div>
            </form>
        </div>
    </div>
</div>

<script type="text/javascript">
    jQuery(document).ready(function($) {
        // Wiederkehrende Events erstellen
        $('#recurring-form').on('submit', function(e) {
            e.preventDefault();
            
            var title = $('#recurring-title').val();
            var startDate = $('#recurring-start-date').val();
            var endDate = $('#recurring-end-date').val();
            var time = $('#recurring-time').val();
            var location = $('#recurring-location').val();
            var description = $('#recurring-description').val();
            var interval = $('#recurring-interval').val();
            var dayOfWeek = $('#recurring-day-of-week').val();
            
            // Datum und Zeit kombinieren
            var startDateTime = startDate + ' ' + time;
            var endDateTime = endDate + ' 23:59:59';
            
            $('#recurring-submit').prop('disabled', true).text('<?php _e('Creating...', 'event-attendance'); ?>');
            $('#recurring-message').hide();
            $('#created-events-container').hide();
            
            $.ajax({
                url: event_attendance_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'create_recurring_events',
                    nonce: event_attendance_ajax.nonce,
                    title: title,
                    start_date: startDateTime,
                    end_date: endDateTime,
                    location: location,
                    description: description,
                    interval: interval,
                    day_of_week: dayOfWeek
                },
                success: function(response) {
                    $('#recurring-submit').prop('disabled', false).text('<?php _e('Create Events', 'event-attendance'); ?>');
                    
                    if (response.success) {
                        $('#recurring-message')
                            .removeClass('notice-error')
                            .addClass('notice-success')
                            .html('<p>' + response.data.message + '</p>')
                            .show();
                        
                        // Liste der erstellten Termine anzeigen
                        if (response.data.events && response.data.events.length > 0) {
                            var events = response.data.events;
                            var eventsHtml = '';
                            
                            for (var i = 0; i < events.length; i++) {
                                var eventDate = new Date(events[i].date);
                                var formattedDate = eventDate.toLocaleDateString() + ' ' + eventDate.toLocaleTimeString();
                                eventsHtml += '<li>' + title + ' - ' + formattedDate + '</li>';
                            }
                            
                            $('#created-events-list').html(eventsHtml);
                            $('#created-events-container').show();
                        }
                    } else {
                        $('#recurring-message')
                            .removeClass('notice-success')
                            .addClass('notice-error')
                            .html('<p>' + response.data.message + '</p>')
                            .show();
                    }
                },
                error: function() {
                    $('#recurring-submit').prop('disabled', false).text('<?php _e('Create Events', 'event-attendance'); ?>');
                    
                    $('#recurring-message')
                        .removeClass('notice-success')
                        .addClass('notice-error')
                        .html('<p><?php _e('An error occurred. Please try again.', 'event-attendance'); ?></p>')
                        .show();
                }
            });
        });
    });
</script>

<style type="text/css">
    .event-attendance-admin {
        margin-top: 20px;
    }
    
    .recurring-form-container {
        background: #fff;
        padding: 20px;
        border: 1px solid #ccd0d4;
        box-shadow: 0 1px 1px rgba(0,0,0,.04);
    }
    
    #created-events-container {
        margin-top: 20px;
        padding: 15px;
        background: #f7f7f7;
        border: 1px solid #ddd;
    }
    
    #created-events-list {
        margin: 10px 0 0 15px;
    }
</style>