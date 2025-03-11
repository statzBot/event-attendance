<?php
/**
 * Admin-Ansicht für die Events-Verwaltung
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
        <!-- Formular zum Hinzufügen oder Bearbeiten eines Events -->
        <div class="event-form-container">
            <h2 id="event-form-title"><?php _e('Add New Event', 'event-attendance'); ?></h2>
            
            <form id="event-form" method="post">
                <input type="hidden" id="event-id" name="event_id" value="0">
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="event-title"><?php _e('Title', 'event-attendance'); ?> *</label>
                        </th>
                        <td>
                            <input type="text" id="event-title" name="title" class="regular-text" required>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="event-date"><?php _e('Date & Time', 'event-attendance'); ?> *</label>
                        </th>
                        <td>
                            <input type="datetime-local" id="event-date" name="date" required>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="event-location"><?php _e('Location', 'event-attendance'); ?> *</label>
                        </th>
                        <td>
                            <input type="text" id="event-location" name="location" class="regular-text" required>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="event-description"><?php _e('Description', 'event-attendance'); ?></label>
                        </th>
                        <td>
                            <textarea id="event-description" name="description" rows="5" class="large-text"></textarea>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <button type="submit" id="event-submit" class="button button-primary">
                        <?php _e('Save Event', 'event-attendance'); ?>
                    </button>
                    <button type="button" id="event-cancel" class="button button-secondary" style="display:none;">
                        <?php _e('Cancel', 'event-attendance'); ?>
                    </button>
                </p>
                
                <div id="event-message" class="notice" style="display:none;"></div>
            </form>
        </div>
        
        <!-- Tabelle mit vorhandenen Events -->
        <div class="event-list-container">
            <h2><?php _e('Existing Events', 'event-attendance'); ?></h2>
            
            <?php if (empty($events)) : ?>
                <p><?php _e('No events found.', 'event-attendance'); ?></p>
            <?php else : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th scope="col"><?php _e('Title', 'event-attendance'); ?></th>
                            <th scope="col"><?php _e('Date & Time', 'event-attendance'); ?></th>
                            <th scope="col"><?php _e('Location', 'event-attendance'); ?></th>
                            <th scope="col"><?php _e('Attendees', 'event-attendance'); ?></th>
                            <th scope="col"><?php _e('Actions', 'event-attendance'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="event-list">
                        <?php foreach ($events as $event) : 
                            $attendees_count = $this->get_attendees_count($event->id);
                            $event_date = new DateTime($event->date);
                        ?>
                            <tr data-id="<?php echo esc_attr($event->id); ?>">
                                <td>
                                    <?php echo esc_html($event->title); ?>
                                </td>
                                <td>
                                    <?php echo $event_date->format('d.m.Y H:i'); ?>
                                </td>
                                <td>
                                    <?php echo esc_html($event->location); ?>
                                </td>
                                <td>
                                    <?php echo $attendees_count; ?> <?php _e('confirmed', 'event-attendance'); ?>
                                </td>
                                <td>
                                    <button type="button" class="button button-small event-edit" 
                                        data-id="<?php echo esc_attr($event->id); ?>"
                                        data-title="<?php echo esc_attr($event->title); ?>"
                                        data-date="<?php echo esc_attr($event_date->format('Y-m-d\TH:i')); ?>"
                                        data-location="<?php echo esc_attr($event->location); ?>"
                                        data-description="<?php echo esc_attr($event->description); ?>">
                                        <?php _e('Edit', 'event-attendance'); ?>
                                    </button>
                                    <button type="button" class="button button-small event-view-attendees" 
                                        data-id="<?php echo esc_attr($event->id); ?>"
                                        data-title="<?php echo esc_attr($event->title); ?>">
                                        <?php _e('View Attendees', 'event-attendance'); ?>
                                    </button>
                                    <button type="button" class="button button-small button-link-delete event-delete" 
                                        data-id="<?php echo esc_attr($event->id); ?>"
                                        data-title="<?php echo esc_attr($event->title); ?>">
                                        <?php _e('Delete', 'event-attendance'); ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <!-- Modal für Teilnehmerliste -->
        <div id="attendees-modal" class="event-attendance-modal" style="display:none;">
            <div class="event-attendance-modal-content">
                <div class="event-attendance-modal-header">
                    <h2><?php _e('Attendees for', 'event-attendance'); ?> <span id="attendees-modal-title"></span></h2>
                    <span class="event-attendance-modal-close">&times;</span>
                </div>
                <div class="event-attendance-modal-body">
                    <div id="attendees-list">
                        <p><?php _e('Loading...', 'event-attendance'); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    jQuery(document).ready(function($) {
        // Event-Formular absenden
        $('#event-form').on('submit', function(e) {
            e.preventDefault();
            
            var eventId = $('#event-id').val();
            var title = $('#event-title').val();
            var date = $('#event-date').val();
            var location = $('#event-location').val();
            var description = $('#event-description').val();
            
            var action = eventId == '0' ? 'create_event' : 'update_event';
            
            $.ajax({
                url: event_attendance_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: action,
                    nonce: event_attendance_ajax.nonce,
                    event_id: eventId,
                    title: title,
                    date: date,
                    location: location,
                    description: description
                },
                success: function(response) {
                    if (response.success) {
                        $('#event-message')
                            .removeClass('notice-error')
                            .addClass('notice-success')
                            .html('<p>' + response.data.message + '</p>')
                            .show();
                        
                        // Formular zurücksetzen und Seite neu laden
                        resetForm();
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else {
                        $('#event-message')
                            .removeClass('notice-success')
                            .addClass('notice-error')
                            .html('<p>' + response.data.message + '</p>')
                            .show();
                    }
                },
                error: function() {
                    $('#event-message')
                        .removeClass('notice-success')
                        .addClass('notice-error')
                        .html('<p><?php _e('An error occurred. Please try again.', 'event-attendance'); ?></p>')
                        .show();
                }
            });
        });
        
        // Event bearbeiten
        $('.event-edit').on('click', function() {
            var eventId = $(this).data('id');
            var title = $(this).data('title');
            var date = $(this).data('date');
            var location = $(this).data('location');
            var description = $(this).data('description');
            
            $('#event-form-title').text('<?php _e('Edit Event', 'event-attendance'); ?>');
            $('#event-id').val(eventId);
            $('#event-title').val(title);
            $('#event-date').val(date);
            $('#event-location').val(location);
            $('#event-description').val(description);
            
            $('#event-submit').text('<?php _e('Update Event', 'event-attendance'); ?>');
            $('#event-cancel').show();
            
            // Zum Formular scrollen
            $('html, body').animate({
                scrollTop: $('#event-form-title').offset().top - 100
            }, 500);
        });
        
        // Abbrechen-Button
        $('#event-cancel').on('click', function() {
            resetForm();
        });
        
        // Event löschen
        $('.event-delete').on('click', function() {
            var eventId = $(this).data('id');
            var title = $(this).data('title');
            
            if (confirm('<?php _e('Are you sure you want to delete the event', 'event-attendance'); ?> "' + title + '"?')) {
                $.ajax({
                    url: event_attendance_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'delete_event',
                        nonce: event_attendance_ajax.nonce,
                        event_id: eventId
                    },
                    success: function(response) {
                        if (response.success) {
                            $('tr[data-id="' + eventId + '"]').fadeOut(500, function() {
                                $(this).remove();
                                if ($('#event-list tr').length === 0) {
                                    $('.event-list-container table').replaceWith('<p><?php _e('No events found.', 'event-attendance'); ?></p>');
                                }
                            });
                        } else {
                            alert(response.data.message);
                        }
                    },
                    error: function() {
                        alert('<?php _e('An error occurred. Please try again.', 'event-attendance'); ?>');
                    }
                });
            }
        });
        
        // Teilnehmer anzeigen
        $('.event-view-attendees').on('click', function() {
            var eventId = $(this).data('id');
            var title = $(this).data('title');
            
            $('#attendees-modal-title').text(title);
            $('#attendees-list').html('<p><?php _e('Loading...', 'event-attendance'); ?></p>');
            $('#attendees-modal').show();
            
            $.ajax({
                url: event_attendance_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'get_event_attendees',
                    nonce: event_attendance_ajax.nonce,
                    event_id: eventId
                },
                success: function(response) {
                    if (response.success) {
                        var attendees = response.data.attendees;
                        var html = '';
                        
                        if (attendees.length === 0) {
                            html = '<p><?php _e('No attendees for this event.', 'event-attendance'); ?></p>';
                        } else {
                            html = '<table class="wp-list-table widefat fixed striped">';
                            html += '<thead><tr>';
                            html += '<th><?php _e('Name', 'event-attendance'); ?></th>';
                            html += '<th><?php _e('Status', 'event-attendance'); ?></th>';
                            html += '<th><?php _e('Comment', 'event-attendance'); ?></th>';
                            html += '</tr></thead><tbody>';
                            
                            $.each(attendees, function(i, attendee) {
                                var statusText = '';
                                switch(attendee.status) {
                                    case 'attending':
                                        statusText = '<?php _e('Attending', 'event-attendance'); ?>';
                                        break;
                                    case 'declined_sick':
                                        statusText = '<?php _e('Declined (Sick)', 'event-attendance'); ?>';
                                        break;
                                    case 'declined_vacation':
                                        statusText = '<?php _e('Declined (Vacation)', 'event-attendance'); ?>';
                                        break;
                                    case 'declined_business':
                                        statusText = '<?php _e('Declined (Business Trip)', 'event-attendance'); ?>';
                                        break;
                                    default:
                                        statusText = attendee.status;
                                }
                                
                                html += '<tr>';
                                html += '<td>' + attendee.name + '</td>';
                                html += '<td>' + statusText + '</td>';
                                html += '<td>' + (attendee.comment || '-') + '</td>';
                                html += '</tr>';
                            });
                            
                            html += '</tbody></table>';
                        }
                        
                        $('#attendees-list').html(html);
                    } else {
                        $('#attendees-list').html('<p class="error">' + response.data.message + '</p>');
                    }
                },
                error: function() {
                    $('#attendees-list').html('<p class="error"><?php _e('An error occurred. Please try again.', 'event-attendance'); ?></p>');
                }
            });
        });
        
        // Modal schließen
        $('.event-attendance-modal-close').on('click', function() {
            $('#attendees-modal').hide();
        });
        
        // Auch außerhalb des Modals klicken schließt es
        $(window).on('click', function(e) {
            if ($(e.target).hasClass('event-attendance-modal')) {
                $('.event-attendance-modal').hide();
            }
        });
        
        // Hilfsfunktion: Formular zurücksetzen
        function resetForm() {
            $('#event-form-title').text('<?php _e('Add New Event', 'event-attendance'); ?>');
            $('#event-id').val('0');
            $('#event-form')[0].reset();
            $('#event-submit').text('<?php _e('Save Event', 'event-attendance'); ?>');
            $('#event-cancel').hide();
            $('#event-message').hide();
        }
    });
</script>

<style type="text/css">
    .event-attendance-admin {
        margin-top: 20px;
    }
    
    .event-form-container {
        background: #fff;
        padding: 20px;
        border: 1px solid #ccd0d4;
        box-shadow: 0 1px 1px rgba(0,0,0,.04);
        margin-bottom: 30px;
    }
    
    .event-list-container {
        margin-top: 30px;
    }
    
    /* Modal-Stil */
    .event-attendance-modal {
        display: none;
        position: fixed;
        z-index: 9999;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgba(0,0,0,0.4);
    }
    
    .event-attendance-modal-content {
        position: relative;
        background-color: #fefefe;
        margin: 5% auto;
        padding: 0;
        width: 80%;
        max-width: 800px;
        box-shadow: 0 4px 8px 0 rgba(0,0,0,0.2);
        animation-name: modalopen;
        animation-duration: 0.4s;
    }
    
    .event-attendance-modal-header {
        padding: 15px;
        background-color: #f5f5f5;
        border-bottom: 1px solid #ddd;
    }
    
    .event-attendance-modal-header h2 {
        margin: 0;
        padding: 0;
    }
    
    .event-attendance-modal-body {
        padding: 20px;
    }
    
    .event-attendance-modal-close {
        color: #aaa;
        float: right;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
        margin-top: -5px;
    }
    
    .event-attendance-modal-close:hover,
    .event-attendance-modal-close:focus {
        color: black;
        text-decoration: none;
        cursor: pointer;
    }
    
    @keyframes modalopen {
        from {opacity: 0; transform: translateY(-60px);}
        to {opacity: 1; transform: translateY(0);}
    }
</style><?php
    
    // Hilfsfunktionen wurden in die Hauptklasse Event_Attendance_Admin verschoben
?>