<?php defined('ABSPATH') || exit; ?>

<?php
$support = RFC_Engine::instance() ? RFC_Engine::instance()->module('support') : null;
$system_info = $support ? $support->getSystemInfo() : [];
$license_email = $settings->get('license_email', '');
$default_email = !empty($license_email) ? $license_email : get_option('admin_email');
?>

<div class="rfc-settings-page">

    <div class="rfc-dashboard-grid" style="grid-template-columns: 1fr 1fr; gap: 20px;">

        <div class="rfc-card">
            <h3><?php esc_html_e('My Tickets', 'rocketfuel-cache'); ?></h3>

            <div id="rfc-tickets-list">
                <div style="text-align: center; padding: 20px;">
                    <span class="rfc-spinner"></span>
                    <p><?php esc_html_e('Loading tickets...', 'rocketfuel-cache'); ?></p>
                </div>
            </div>
        </div>

        <div class="rfc-card">
            <h3><?php esc_html_e('Create New Ticket', 'rocketfuel-cache'); ?></h3>

            <div id="rfc-ticket-form">
                <table class="form-table">
                    <tr>
                        <th><label for="rfc-ticket-email"><?php esc_html_e('Email', 'rocketfuel-cache'); ?></label></th>
                        <td>
                            <input type="email" id="rfc-ticket-email" class="regular-text" value="<?php echo esc_attr($default_email); ?>">
                        </td>
                    </tr>
                    <tr>
                        <th><label for="rfc-ticket-subject"><?php esc_html_e('Subject', 'rocketfuel-cache'); ?></label></th>
                        <td>
                            <input type="text" id="rfc-ticket-subject" class="regular-text" placeholder="<?php esc_attr_e('Brief description of your issue', 'rocketfuel-cache'); ?>">
                        </td>
                    </tr>
                    <tr>
                        <th><label for="rfc-ticket-priority"><?php esc_html_e('Priority', 'rocketfuel-cache'); ?></label></th>
                        <td>
                            <select id="rfc-ticket-priority">
                                <option value="low"><?php esc_html_e('Low', 'rocketfuel-cache'); ?></option>
                                <option value="normal" selected><?php esc_html_e('Normal', 'rocketfuel-cache'); ?></option>
                                <option value="high"><?php esc_html_e('High', 'rocketfuel-cache'); ?></option>
                                <option value="urgent"><?php esc_html_e('Urgent', 'rocketfuel-cache'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="rfc-ticket-message"><?php esc_html_e('Message', 'rocketfuel-cache'); ?></label></th>
                        <td>
                            <textarea id="rfc-ticket-message" rows="8" class="large-text" placeholder="<?php esc_attr_e('Describe your issue in detail...', 'rocketfuel-cache'); ?>"></textarea>
                        </td>
                    </tr>
                </table>

                <div style="margin-top: 10px; margin-bottom: 15px;">
                    <p style="color: #666; font-size: 13px;">
                        <span class="dashicons dashicons-info" style="font-size: 14px;"></span>
                        <?php esc_html_e('System info will be automatically attached to your ticket.', 'rocketfuel-cache'); ?>
                        <a href="#" id="rfc-toggle-sysinfo" style="margin-left: 5px;"><?php esc_html_e('Preview', 'rocketfuel-cache'); ?></a>
                    </p>
                    <div id="rfc-sysinfo-preview" style="display: none; background: #f9f9f9; padding: 15px; border-radius: 4px; margin-top: 10px; font-size: 12px; font-family: monospace;">
                        <?php foreach ($system_info as $key => $value) : ?>
                            <div style="margin-bottom: 4px;">
                                <strong><?php echo esc_html($key); ?>:</strong>
                                <?php
                                if (is_array($value)) {
                                    echo esc_html(implode(', ', array_map(function ($v) {
                                        return is_bool($v) ? ($v ? 'true' : 'false') : (string) $v;
                                    }, $value)));
                                } elseif (is_bool($value)) {
                                    echo $value ? 'true' : 'false';
                                } else {
                                    echo esc_html((string) $value);
                                }
                                ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <button type="button" class="rfc-btn rfc-btn-primary" id="rfc-submit-ticket">
                    <?php esc_html_e('Submit Ticket', 'rocketfuel-cache'); ?>
                </button>
                <span id="rfc-ticket-status" style="margin-left: 10px;"></span>
            </div>
        </div>

    </div>

    <div id="rfc-ticket-detail" class="rfc-card" style="display: none;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
            <h3 id="rfc-ticket-detail-title" style="margin: 0;"></h3>
            <button type="button" class="rfc-btn" id="rfc-close-ticket-detail"><?php esc_html_e('Back to List', 'rocketfuel-cache'); ?></button>
        </div>
        <div id="rfc-ticket-thread" style="max-height: 500px; overflow-y: auto; padding: 10px;"></div>
        <div style="margin-top: 15px; border-top: 1px solid #eee; padding-top: 15px;">
            <textarea id="rfc-reply-message" rows="4" class="large-text" placeholder="<?php esc_attr_e('Type your reply...', 'rocketfuel-cache'); ?>"></textarea>
            <button type="button" class="rfc-btn rfc-btn-primary" id="rfc-send-reply" style="margin-top: 10px;">
                <?php esc_html_e('Send Reply', 'rocketfuel-cache'); ?>
            </button>
        </div>
    </div>

</div>

<script>
jQuery(function($) {
    var currentTicketId = null;

    function loadTickets() {
        $.get(rfcAdmin.ajaxurl, {
            action: 'rfc_get_tickets',
            nonce: rfcAdmin.nonce
        }, function(res) {
            var $list = $('#rfc-tickets-list');
            if (!res.success || !res.data || !res.data.tickets || res.data.tickets.length === 0) {
                $list.html('<p style="text-align: center; color: #999;"><?php echo esc_js(__('No tickets yet.', 'rocketfuel-cache')); ?></p>');
                return;
            }

            var statusColors = {
                'open': '#3b82f6',
                'pending': '#f59e0b',
                'resolved': '#10b981',
                'closed': '#6b7280'
            };

            var priorityColors = {
                'low': '#6b7280',
                'normal': '#3b82f6',
                'high': '#f59e0b',
                'urgent': '#dc2626'
            };

            var html = '<table class="widefat striped"><thead><tr>';
            html += '<th><?php echo esc_js(__('Ticket ID', 'rocketfuel-cache')); ?></th>';
            html += '<th><?php echo esc_js(__('Subject', 'rocketfuel-cache')); ?></th>';
            html += '<th><?php echo esc_js(__('Status', 'rocketfuel-cache')); ?></th>';
            html += '<th><?php echo esc_js(__('Priority', 'rocketfuel-cache')); ?></th>';
            html += '<th><?php echo esc_js(__('Created', 'rocketfuel-cache')); ?></th>';
            html += '<th><?php echo esc_js(__('Last Reply', 'rocketfuel-cache')); ?></th>';
            html += '</tr></thead><tbody>';

            $.each(res.data.tickets, function(i, ticket) {
                var sc = statusColors[ticket.status] || '#6b7280';
                var pc = priorityColors[ticket.priority] || '#6b7280';
                html += '<tr class="rfc-ticket-row" data-ticket="' + ticket.ticket_id + '" style="cursor: pointer;">';
                html += '<td><strong>' + ticket.ticket_id + '</strong></td>';
                html += '<td>' + ticket.subject + '</td>';
                html += '<td><span style="background: ' + sc + '; color: #fff; padding: 2px 8px; border-radius: 10px; font-size: 11px;">' + ticket.status + '</span></td>';
                html += '<td><span style="background: ' + pc + '; color: #fff; padding: 2px 8px; border-radius: 10px; font-size: 11px;">' + ticket.priority + '</span></td>';
                html += '<td>' + (ticket.created_at || '') + '</td>';
                html += '<td>' + (ticket.last_reply || '') + '</td>';
                html += '</tr>';
            });

            html += '</tbody></table>';
            $list.html(html);
        });
    }

    loadTickets();

    $(document).on('click', '.rfc-ticket-row', function() {
        var ticketId = $(this).data('ticket');
        currentTicketId = ticketId;
        $('#rfc-ticket-detail-title').text('<?php echo esc_js(__('Ticket: ', 'rocketfuel-cache')); ?>' + ticketId);
        $('#rfc-ticket-thread').html('<div style="text-align: center;"><span class="rfc-spinner"></span></div>');
        $('#rfc-ticket-detail').show();

        $.get(rfcAdmin.ajaxurl, {
            action: 'rfc_get_ticket',
            nonce: rfcAdmin.nonce,
            ticket_id: ticketId
        }, function(res) {
            if (!res.success || !res.data || !res.data.replies) {
                $('#rfc-ticket-thread').html('<p><?php echo esc_js(__('Could not load ticket details.', 'rocketfuel-cache')); ?></p>');
                return;
            }

            var html = '';
            $.each(res.data.replies, function(i, reply) {
                var isAdmin = reply.from === 'admin';
                var align = isAdmin ? 'left' : 'right';
                var bg = isAdmin ? '#f3f4f6' : '#eff6ff';
                var label = isAdmin ? '<?php echo esc_js(__('Support', 'rocketfuel-cache')); ?>' : '<?php echo esc_js(__('You', 'rocketfuel-cache')); ?>';

                html += '<div style="text-align: ' + align + '; margin-bottom: 12px;">';
                html += '<div style="display: inline-block; background: ' + bg + '; padding: 12px 16px; border-radius: 8px; max-width: 80%; text-align: left;">';
                html += '<div style="font-size: 11px; color: #666; margin-bottom: 4px;"><strong>' + label + '</strong> &middot; ' + (reply.date || '') + '</div>';
                html += '<div>' + reply.message + '</div>';
                html += '</div></div>';
            });

            $('#rfc-ticket-thread').html(html);
            $('#rfc-ticket-thread').scrollTop($('#rfc-ticket-thread')[0].scrollHeight);
        });
    });

    $('#rfc-close-ticket-detail').on('click', function() {
        $('#rfc-ticket-detail').hide();
        currentTicketId = null;
    });

    $('#rfc-send-reply').on('click', function() {
        var message = $('#rfc-reply-message').val().trim();
        if (!message || !currentTicketId) return;

        var $btn = $(this);
        $btn.prop('disabled', true).text('<?php echo esc_js(__('Sending...', 'rocketfuel-cache')); ?>');

        $.post(rfcAdmin.ajaxurl, {
            action: 'rfc_reply_ticket',
            nonce: rfcAdmin.nonce,
            ticket_id: currentTicketId,
            message: message
        }, function(res) {
            if (res.success) {
                $('#rfc-reply-message').val('');
                $('.rfc-ticket-row[data-ticket="' + currentTicketId + '"]').trigger('click');
            } else {
                alert(res.data || '<?php echo esc_js(__('Failed to send reply.', 'rocketfuel-cache')); ?>');
            }
            $btn.prop('disabled', false).text('<?php echo esc_js(__('Send Reply', 'rocketfuel-cache')); ?>');
        });
    });

    $('#rfc-toggle-sysinfo').on('click', function(e) {
        e.preventDefault();
        $('#rfc-sysinfo-preview').toggle();
    });

    $('#rfc-submit-ticket').on('click', function() {
        var email = $('#rfc-ticket-email').val().trim();
        var subject = $('#rfc-ticket-subject').val().trim();
        var priority = $('#rfc-ticket-priority').val();
        var message = $('#rfc-ticket-message').val().trim();

        if (!email || !subject || !message) {
            alert('<?php echo esc_js(__('Please fill in all required fields.', 'rocketfuel-cache')); ?>');
            return;
        }

        var $btn = $(this);
        $btn.prop('disabled', true).text('<?php echo esc_js(__('Submitting...', 'rocketfuel-cache')); ?>');
        $('#rfc-ticket-status').text('');

        $.post(rfcAdmin.ajaxurl, {
            action: 'rfc_create_ticket',
            nonce: rfcAdmin.nonce,
            email: email,
            subject: subject,
            priority: priority,
            message: message
        }, function(res) {
            if (res.success && res.data && res.data.ticket_id) {
                $('#rfc-ticket-status').html(
                    '<span style="color: #16a34a; font-weight: 600;">' +
                    '<?php echo esc_js(__('Ticket created: ', 'rocketfuel-cache')); ?>' + res.data.ticket_id +
                    '</span>'
                );
                $('#rfc-ticket-subject').val('');
                $('#rfc-ticket-message').val('');
                loadTickets();
            } else {
                $('#rfc-ticket-status').html(
                    '<span style="color: #dc2626;">' + (res.data || '<?php echo esc_js(__('Failed to create ticket.', 'rocketfuel-cache')); ?>') + '</span>'
                );
            }
            $btn.prop('disabled', false).text('<?php echo esc_js(__('Submit Ticket', 'rocketfuel-cache')); ?>');
        }).fail(function() {
            $('#rfc-ticket-status').html('<span style="color: #dc2626;"><?php echo esc_js(__('Request failed.', 'rocketfuel-cache')); ?></span>');
            $btn.prop('disabled', false).text('<?php echo esc_js(__('Submit Ticket', 'rocketfuel-cache')); ?>');
        });
    });
});
</script>
