<?php
/**
 * Admin-Ansicht für die Teilnehmer-Verwaltung
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
        <!-- Formular zum Hinzufügen oder Bearbeiten eines Teilnehmers -->
        <div class="participant-form-container">
            <h2 id="participant-form-title"><?php _e('Add New Participant', 'event-attendance'); ?></h2>
            
            <form id="participant-form" method="post">
                <input type="hidden" id="participant-id" name="participant_id" value="0">
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="participant-name"><?php _e('Name', 'event-attendance'); ?> *</label>
                        </th>
                        <td>
                            <input type="text" id="participant-name" name="name" class="regular-text" required>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="participant-email"><?php _e('Email', 'event-attendance'); ?> *</label>
                        </th>
                        <td>
                            <input type="email" id="participant-email" name="email" class="regular-text" required>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="participant-user"><?php _e('WordPress User', 'event-attendance'); ?></label>
                        </th>
                        <td>
                            <select id="participant-user" name="user_id" class="regular-text">
                                <option value="0"><?php _e('-- None --', 'event-attendance'); ?></option>
                                <?php
                                $users = get_users(['orderby' => 'display_name']);
                                foreach ($users as $user) {
                                    echo '<option value="' . esc_attr($user->ID) . '">' . esc_html($user->display_name) . ' (' . esc_html($user->user_email) . ')</option>';
                                }
                                ?>
                            </select>
                            <p class="description"><?php _e('Link this participant to a WordPress user (optional)', 'event-attendance'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <button type="submit" id="participant-submit" class="button button-primary">
                        <?php _e('Save Participant', 'event-attendance'); ?>
                    </button>
                    <button type="button" id="participant-cancel" class="button button-secondary" style="display:none;">
                        <?php _e('Cancel', 'event-attendance'); ?>
                    </button>
                </p>
                
                <div id="participant-message" class="notice" style="display:none;"></div>
            </form>
        </div>
        
        <!-- Tabelle mit vorhandenen Teilnehmern -->
        <div class="participant-list-container">
            <h2><?php _e('Existing Participants', 'event-attendance'); ?></h2>
            
            <?php if (empty($participants)) : ?>
                <p><?php _e('No participants found.', 'event-attendance'); ?></p>
            <?php else : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th scope="col"><?php _e('Name', 'event-attendance'); ?></th>
                            <th scope="col"><?php _e('Email', 'event-attendance'); ?></th>
                            <th scope="col"><?php _e('WordPress User', 'event-attendance'); ?></th>
                            <th scope="col"><?php _e('Events Attending', 'event-attendance'); ?></th>
                            <th scope="col"><?php _e('Actions', 'event-attendance'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="participant-list">
                        <?php foreach ($participants as $participant) : 
                            $events_count = $this->get_participant_events_count($participant->id);
                            $user_info = '';
                            if (!empty($participant->user_id)) {
                                $user = get_userdata($participant->user_id);
                                if ($user) {
                                    $user_info = $user->display_name . ' (' . $user->user_login . ')';
                                }
                            }
                        ?>
                            <tr data-id="<?php echo esc_attr($participant->id); ?>">
                                <td>
                                    <?php echo esc_html($participant->name); ?>
                                </td>
                                <td>
                                    <?php echo esc_html($participant->email); ?>
                                </td>
                                <td>
                                    <?php echo $user_info ? esc_html($user_info) : __('Not linked', 'event-attendance'); ?>
                                </td>
                                <td>
                                    <?php echo $events_count; ?>
                                </td>
                                <td>
                                    <button type="button" class="button button-small participant-edit" 
                                        data-id="<?php echo esc_attr($participant->id); ?>"
                                        data-name="<?php echo esc_attr($participant->name); ?>"
                                        data-email="<?php echo esc_attr($participant->email); ?>"
                                        data-user-id="<?php echo esc_attr($participant->user_id); ?>">
                                        <?php _e('Edit', 'event-attendance'); ?>
                                    </button>
                                    <button type="button" class="button button-small button-link-delete participant-delete" 
                                        data-id="<?php echo esc_attr($participant->id); ?>"
                                        data-name="<?php echo esc_attr($participant->name); ?>">
                                        <?php _e('Delete', 'event-attendance'); ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<script type="text/javascript">
    jQuery(document).ready(function($) {
        // Teilnehmer-Formular absenden
        $('#participant-form').on('submit', function(e) {
            e.preventDefault();
            
            var participantId = $('#participant-id').val();
            var name = $('#participant-name').val();
            var email = $('#participant-email').val();
            var userId = $('#participant-user').val();
            
            var action = participantId == '0' ? 'create_participant' : 'update_participant';
            
            $.ajax({
                url: event_attendance_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: action,
                    nonce: event_attendance_ajax.nonce,
                    participant_id: participantId,
                    name: name,
                    email: email,
                    user_id: userId
                },
                success: function(response) {
                    if (response.success) {
                        $('#participant-message')
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
                        $('#participant-message')
                            .removeClass('notice-success')
                            .addClass('notice-error')
                            .html('<p>' + response.data.message + '</p>')
                            .show();
                    }
                },
                error: function() {
                    $('#participant-message')
                        .removeClass('notice-success')
                        .addClass('notice-error')
                        .html('<p><?php _e('An error occurred. Please try again.', 'event-attendance'); ?></p>')
                        .show();
                }
            });
        });
        
        // Teilnehmer bearbeiten
        $('.participant-edit').on('click', function() {
            var participantId = $(this).data('id');
            var name = $(this).data('name');
            var email = $(this).data('email');
            var userId = $(this).data('user-id');
            
            $('#participant-form-title').text('<?php _e('Edit Participant', 'event-attendance'); ?>');
            $('#participant-id').val(participantId);
            $('#participant-name').val(name);
            $('#participant-email').val(email);
            $('#participant-user').val(userId);
            
            $('#participant-submit').text('<?php _e('Update Participant', 'event-attendance'); ?>');
            $('#participant-cancel').show();
            
            // Zum Formular scrollen
            $('html, body').animate({
                scrollTop: $('#participant-form-title').offset().top - 100
            }, 500);
        });
        
        // Abbrechen-Button
        $('#participant-cancel').on('click', function() {
            resetForm();
        });
        
        // Teilnehmer löschen
        $('.participant-delete').on('click', function() {
            var participantId = $(this).data('id');
            var name = $(this).data('name');
            
            if (confirm('<?php _e('Are you sure you want to delete the participant', 'event-attendance'); ?> "' + name + '"?')) {
                $.ajax({
                    url: event_attendance_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'delete_participant',
                        nonce: event_attendance_ajax.nonce,
                        participant_id: participantId
                    },
                    success: function(response) {
                        if (response.success) {
                            $('tr[data-id="' + participantId + '"]').fadeOut(500, function() {
                                $(this).remove();
                                if ($('#participant-list tr').length === 0) {
                                    $('.participant-list-container table').replaceWith('<p><?php _e('No participants found.', 'event-attendance'); ?></p>');
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
        
        // Hilfsfunktion: Formular zurücksetzen
        function resetForm() {
            $('#participant-form-title').text('<?php _e('Add New Participant', 'event-attendance'); ?>');
            $('#participant-id').val('0');
            $('#participant-form')[0].reset();
            $('#participant-submit').text('<?php _e('Save Participant', 'event-attendance'); ?>');
            $('#participant-cancel').hide();
            $('#participant-message').hide();
        }
    });
</script>

<style type="text/css">
    .event-attendance-admin {
        margin-top: 20px;
    }
    
    .participant-form-container {
        background: #fff;
        padding: 20px;
        border: 1px solid #ccd0d4;
        box-shadow: 0 1px 1px rgba(0,0,0,.04);
        margin-bottom: 30px;
    }
    
    .participant-list-container {
        margin-top: 30px;
    }
</style>

<?php
    // Hilfsfunktionen wurden in die Hauptklasse Event_Attendance_Admin verschoben
?>