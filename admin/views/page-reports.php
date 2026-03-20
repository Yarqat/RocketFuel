<?php defined('ABSPATH') || exit; ?>

<?php
$speed_test = RFC_Engine::instance() ? RFC_Engine::instance()->module('speed_test') : null;
$suggestions_engine = RFC_Engine::instance() ? RFC_Engine::instance()->module('suggestions') : null;

$report = $speed_test ? $speed_test->getReport() : [];
$baseline = $report['baseline'] ?? [];
$current = $report['current'] ?? [];
$improvements = $report['improvements'] ?? [];
$history = get_option('rfc_report_history', []);

$mobile_score = $current['mobile']['performance'] ?? 0;
$desktop_score = $current['desktop']['performance'] ?? 0;
$mobile_color = $speed_test ? $speed_test->getScoreColor($mobile_score) : 'red';
$desktop_color = $speed_test ? $speed_test->getScoreColor($desktop_score) : 'red';

$suggestions = $suggestions_engine ? $suggestions_engine->analyze() : [];
$total_potential = $suggestions_engine ? $suggestions_engine->getTotalPotentialPoints() : 0;
$estimated_score = $suggestions_engine ? $suggestions_engine->getEstimatedScore($mobile_score) : $mobile_score;

$current_report = get_option('rfc_current_report', []);
$last_tested = isset($current_report['mobile']['tested_at']) ? $current_report['mobile']['tested_at'] : '';
?>

<div class="rfc-settings-page">

    <div class="rfc-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="margin: 0;"><?php esc_html_e('Performance Score', 'rocketfuel-cache'); ?></h3>
            <div>
                <?php if ($last_tested) : ?>
                    <span style="color: #666; margin-right: 15px;">
                        <?php printf(esc_html__('Last tested: %s', 'rocketfuel-cache'), esc_html(human_time_diff(strtotime($last_tested), current_time('timestamp')) . ' ago')); ?>
                    </span>
                <?php endif; ?>
                <button type="button" class="rfc-btn rfc-btn-primary" id="rfc-run-test">
                    <span class="rfc-test-label"><?php esc_html_e('Run Test Now', 'rocketfuel-cache'); ?></span>
                    <span class="rfc-spinner" style="display: none;"></span>
                </button>
                <button type="button" class="rfc-btn" id="rfc-sync-report">
                    <?php esc_html_e('Sync to Dashboard', 'rocketfuel-cache'); ?>
                </button>
            </div>
        </div>

        <div class="rfc-dashboard-grid" style="grid-template-columns: 1fr 1fr; gap: 30px;">
            <div style="text-align: center;">
                <h4><?php esc_html_e('Mobile', 'rocketfuel-cache'); ?></h4>
                <div class="rfc-score-ring" data-score="<?php echo esc_attr($mobile_score); ?>" data-color="<?php echo esc_attr($mobile_color); ?>">
                    <svg viewBox="0 0 120 120" width="150" height="150">
                        <circle cx="60" cy="60" r="54" fill="none" stroke="#e0e0e0" stroke-width="8"/>
                        <circle cx="60" cy="60" r="54" fill="none"
                            stroke="<?php echo $mobile_color === 'green' ? '#0cce6b' : ($mobile_color === 'orange' ? '#ffa400' : '#ff4e42'); ?>"
                            stroke-width="8"
                            stroke-dasharray="<?php echo esc_attr(round(339.292 * $mobile_score / 100, 2)); ?> 339.292"
                            stroke-linecap="round"
                            transform="rotate(-90 60 60)"
                            style="transition: stroke-dasharray 1s ease;"/>
                        <text x="60" y="66" text-anchor="middle" font-size="28" font-weight="bold"
                            fill="<?php echo $mobile_color === 'green' ? '#0cce6b' : ($mobile_color === 'orange' ? '#ffa400' : '#ff4e42'); ?>">
                            <?php echo esc_html($mobile_score); ?>
                        </text>
                    </svg>
                </div>
            </div>
            <div style="text-align: center;">
                <h4><?php esc_html_e('Desktop', 'rocketfuel-cache'); ?></h4>
                <div class="rfc-score-ring" data-score="<?php echo esc_attr($desktop_score); ?>" data-color="<?php echo esc_attr($desktop_color); ?>">
                    <svg viewBox="0 0 120 120" width="150" height="150">
                        <circle cx="60" cy="60" r="54" fill="none" stroke="#e0e0e0" stroke-width="8"/>
                        <circle cx="60" cy="60" r="54" fill="none"
                            stroke="<?php echo $desktop_color === 'green' ? '#0cce6b' : ($desktop_color === 'orange' ? '#ffa400' : '#ff4e42'); ?>"
                            stroke-width="8"
                            stroke-dasharray="<?php echo esc_attr(round(339.292 * $desktop_score / 100, 2)); ?> 339.292"
                            stroke-linecap="round"
                            transform="rotate(-90 60 60)"
                            style="transition: stroke-dasharray 1s ease;"/>
                        <text x="60" y="66" text-anchor="middle" font-size="28" font-weight="bold"
                            fill="<?php echo $desktop_color === 'green' ? '#0cce6b' : ($desktop_color === 'orange' ? '#ffa400' : '#ff4e42'); ?>">
                            <?php echo esc_html($desktop_score); ?>
                        </text>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <?php if (!empty($baseline) && !empty($current)) : ?>
    <div class="rfc-card">
        <h3><?php esc_html_e('Before vs After', 'rocketfuel-cache'); ?></h3>
        <div class="rfc-dashboard-grid" style="grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="rfc-card" style="background: #f9f9f9;">
                <h4 style="text-align: center; color: #666;"><?php esc_html_e('When Activated', 'rocketfuel-cache'); ?></h4>
                <table class="widefat" style="border: none;">
                    <tbody>
                        <tr>
                            <td><?php esc_html_e('PageSpeed Score', 'rocketfuel-cache'); ?></td>
                            <td class="rfc-stat-value"><?php echo esc_html($baseline['mobile']['performance'] ?? '—'); ?></td>
                        </tr>
                        <tr>
                            <td><?php esc_html_e('LCP', 'rocketfuel-cache'); ?></td>
                            <td class="rfc-stat-value"><?php echo esc_html($baseline['mobile']['lcp']['display'] ?? '—'); ?></td>
                        </tr>
                        <tr>
                            <td><?php esc_html_e('CLS', 'rocketfuel-cache'); ?></td>
                            <td class="rfc-stat-value"><?php echo esc_html($baseline['mobile']['cls']['display'] ?? '—'); ?></td>
                        </tr>
                        <tr>
                            <td><?php esc_html_e('TTFB', 'rocketfuel-cache'); ?></td>
                            <td class="rfc-stat-value"><?php echo esc_html($baseline['mobile']['ttfb']['display'] ?? '—'); ?></td>
                        </tr>
                        <tr>
                            <td><?php esc_html_e('Page Size', 'rocketfuel-cache'); ?></td>
                            <td class="rfc-stat-value"><?php echo esc_html(isset($baseline['mobile']['total_byte_weight']) ? size_format($baseline['mobile']['total_byte_weight']) : '—'); ?></td>
                        </tr>
                        <tr>
                            <td><?php esc_html_e('Requests', 'rocketfuel-cache'); ?></td>
                            <td class="rfc-stat-value"><?php echo esc_html($baseline['mobile']['request_count'] ?? '—'); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="rfc-card" style="background: #f0fdf4;">
                <h4 style="text-align: center; color: #16a34a;"><?php esc_html_e('Current', 'rocketfuel-cache'); ?></h4>
                <table class="widefat" style="border: none;">
                    <tbody>
                        <?php
                        $metrics = [
                            'PageSpeed Score' => ['key' => 'performance', 'nested' => false],
                            'LCP'             => ['key' => 'lcp', 'nested' => true],
                            'CLS'             => ['key' => 'cls', 'nested' => true],
                            'TTFB'            => ['key' => 'ttfb', 'nested' => true],
                        ];
                        foreach ($metrics as $label => $meta) :
                            $b_val = $meta['nested']
                                ? ($baseline['mobile'][$meta['key']]['value'] ?? 0)
                                : ($baseline['mobile'][$meta['key']] ?? 0);
                            $c_val = $meta['nested']
                                ? ($current['mobile'][$meta['key']]['value'] ?? 0)
                                : ($current['mobile'][$meta['key']] ?? 0);
                            $display_val = $meta['nested']
                                ? ($current['mobile'][$meta['key']]['display'] ?? '—')
                                : ($current['mobile'][$meta['key']] ?? '—');

                            $improved = $meta['key'] === 'performance' ? ($c_val > $b_val) : ($c_val < $b_val);
                            $change_pct = $b_val > 0 ? abs(round((($c_val - $b_val) / $b_val) * 100, 1)) : 0;
                        ?>
                        <tr>
                            <td><?php echo esc_html($label); ?></td>
                            <td class="rfc-stat-value">
                                <?php echo esc_html($display_val); ?>
                                <?php if ($change_pct > 0) : ?>
                                    <span style="color: <?php echo $improved ? '#16a34a' : '#dc2626'; ?>; font-size: 12px; margin-left: 5px;">
                                        <?php echo $improved ? '&#9650;' : '&#9660;'; ?> <?php echo esc_html($change_pct); ?>%
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <tr>
                            <td><?php esc_html_e('Page Size', 'rocketfuel-cache'); ?></td>
                            <td class="rfc-stat-value">
                                <?php echo esc_html(isset($current['mobile']['total_byte_weight']) ? size_format($current['mobile']['total_byte_weight']) : '—'); ?>
                                <?php
                                $b_size = $baseline['mobile']['total_byte_weight'] ?? 0;
                                $c_size = $current['mobile']['total_byte_weight'] ?? 0;
                                if ($b_size > 0) :
                                    $size_change = abs(round((($c_size - $b_size) / $b_size) * 100, 1));
                                    $size_improved = $c_size < $b_size;
                                ?>
                                    <span style="color: <?php echo $size_improved ? '#16a34a' : '#dc2626'; ?>; font-size: 12px; margin-left: 5px;">
                                        <?php echo $size_improved ? '&#9650;' : '&#9660;'; ?> <?php echo esc_html($size_change); ?>%
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td><?php esc_html_e('Requests', 'rocketfuel-cache'); ?></td>
                            <td class="rfc-stat-value"><?php echo esc_html($current['mobile']['request_count'] ?? '—'); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="rfc-card">
        <h3><?php esc_html_e('Core Web Vitals', 'rocketfuel-cache'); ?></h3>
        <div class="rfc-dashboard-grid" style="grid-template-columns: 1fr 1fr 1fr; gap: 20px;">
            <?php
            $lcp_val = $current['mobile']['lcp']['value'] ?? 0;
            $lcp_ms = $lcp_val / 1000;
            if ($lcp_ms <= 2.5) {
                $lcp_status = 'Good';
                $lcp_color = '#0cce6b';
            } elseif ($lcp_ms <= 4.0) {
                $lcp_status = 'Needs Improvement';
                $lcp_color = '#ffa400';
            } else {
                $lcp_status = 'Poor';
                $lcp_color = '#ff4e42';
            }

            $inp_val = $current['mobile']['inp']['value'] ?? 0;
            if ($inp_val <= 200) {
                $inp_status = 'Good';
                $inp_color = '#0cce6b';
            } elseif ($inp_val <= 500) {
                $inp_status = 'Needs Improvement';
                $inp_color = '#ffa400';
            } else {
                $inp_status = 'Poor';
                $inp_color = '#ff4e42';
            }

            $cls_val = $current['mobile']['cls']['value'] ?? 0;
            if ($cls_val <= 0.1) {
                $cls_status = 'Good';
                $cls_color = '#0cce6b';
            } elseif ($cls_val <= 0.25) {
                $cls_status = 'Needs Improvement';
                $cls_color = '#ffa400';
            } else {
                $cls_status = 'Poor';
                $cls_color = '#ff4e42';
            }
            ?>
            <div class="rfc-card" style="text-align: center; border-left: 4px solid <?php echo esc_attr($lcp_color); ?>;">
                <h4><?php esc_html_e('LCP', 'rocketfuel-cache'); ?></h4>
                <div class="rfc-stat-value" style="font-size: 24px;">
                    <?php echo esc_html($current['mobile']['lcp']['display'] ?? '—'); ?>
                </div>
                <span style="color: <?php echo esc_attr($lcp_color); ?>; font-weight: 600;">
                    <?php echo esc_html($lcp_status); ?>
                </span>
            </div>
            <div class="rfc-card" style="text-align: center; border-left: 4px solid <?php echo esc_attr($inp_color); ?>;">
                <h4><?php esc_html_e('INP', 'rocketfuel-cache'); ?></h4>
                <div class="rfc-stat-value" style="font-size: 24px;">
                    <?php echo esc_html($current['mobile']['inp']['display'] ?? '—'); ?>
                </div>
                <span style="color: <?php echo esc_attr($inp_color); ?>; font-weight: 600;">
                    <?php echo esc_html($inp_status); ?>
                </span>
            </div>
            <div class="rfc-card" style="text-align: center; border-left: 4px solid <?php echo esc_attr($cls_color); ?>;">
                <h4><?php esc_html_e('CLS', 'rocketfuel-cache'); ?></h4>
                <div class="rfc-stat-value" style="font-size: 24px;">
                    <?php echo esc_html($current['mobile']['cls']['display'] ?? '—'); ?>
                </div>
                <span style="color: <?php echo esc_attr($cls_color); ?>; font-weight: 600;">
                    <?php echo esc_html($cls_status); ?>
                </span>
            </div>
        </div>
    </div>

    <?php if (!empty($suggestions)) : ?>
    <div class="rfc-card">
        <h3><?php esc_html_e('Optimization Suggestions', 'rocketfuel-cache'); ?></h3>

        <?php if ($total_potential > 0) : ?>
        <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 6px; padding: 15px; margin-bottom: 20px;">
            <strong><?php printf(esc_html__('You could gain +%d points by applying these changes.', 'rocketfuel-cache'), $total_potential); ?></strong>
            <span style="margin-left: 10px; color: #666;">
                <?php printf(esc_html__('Estimated score: %d/100', 'rocketfuel-cache'), $estimated_score); ?>
            </span>
            <div style="margin-top: 10px; background: #e0e0e0; border-radius: 4px; height: 8px; overflow: hidden;">
                <div style="background: #0cce6b; height: 100%; width: <?php echo esc_attr(min(100, $estimated_score)); ?>%; transition: width 0.5s;"></div>
            </div>
        </div>
        <?php endif; ?>

        <div class="rfc-suggestions-list">
            <?php foreach ($suggestions as $suggestion) :
                $is_pro = !empty($suggestion['pro_required']);
                $has_pro = RFC_Engine::instance() && RFC_Engine::instance()->hasPro();
                $impact_icon = $suggestion['impact'] === 'high' ? '&#9650;' : ($suggestion['impact'] === 'medium' ? '&#9644;' : '&#9660;');
                $impact_color = $suggestion['impact'] === 'high' ? '#dc2626' : ($suggestion['impact'] === 'medium' ? '#f59e0b' : '#6b7280');
            ?>
                <div class="rfc-suggestion-item" style="display: flex; align-items: center; padding: 15px; border-bottom: 1px solid #eee;" data-id="<?php echo esc_attr($suggestion['id']); ?>">
                    <span style="color: <?php echo esc_attr($impact_color); ?>; font-size: 16px; margin-right: 12px;" title="<?php echo esc_attr(ucfirst($suggestion['impact'])); ?> impact">
                        <?php echo $impact_icon; ?>
                    </span>
                    <div style="flex: 1;">
                        <strong>
                            <?php echo esc_html($suggestion['title']); ?>
                            <?php if ($is_pro && !$has_pro) : ?>
                                <span class="dashicons dashicons-lock" style="font-size: 14px; color: #9ca3af;"></span>
                            <?php endif; ?>
                        </strong>
                        <p style="margin: 4px 0 0; color: #666; font-size: 13px;"><?php echo esc_html($suggestion['description']); ?></p>
                    </div>
                    <span style="background: #f3f4f6; padding: 4px 10px; border-radius: 12px; font-size: 12px; font-weight: 600; margin-right: 12px; white-space: nowrap;">
                        +<?php echo esc_html($suggestion['points']); ?> <?php esc_html_e('pts', 'rocketfuel-cache'); ?>
                    </span>
                    <?php if ($is_pro && !$has_pro) : ?>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=rocketfuel-cache-license')); ?>" class="rfc-btn" style="font-size: 12px;">
                            <?php esc_html_e('Upgrade to Pro', 'rocketfuel-cache'); ?>
                        </a>
                    <?php elseif ($suggestion['status'] === 'actionable') : ?>
                        <button type="button" class="rfc-btn rfc-btn-primary rfc-apply-suggestion" data-suggestion="<?php echo esc_attr($suggestion['id']); ?>" style="font-size: 12px;">
                            <?php esc_html_e('Apply', 'rocketfuel-cache'); ?>
                        </button>
                    <?php else : ?>
                        <span style="color: #9ca3af; font-size: 12px;"><?php esc_html_e('Manual action needed', 'rocketfuel-cache'); ?></span>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="rfc-card">
        <h3><?php esc_html_e('Score History', 'rocketfuel-cache'); ?></h3>
        <div id="rfc-score-chart" style="min-height: 200px;">
            <?php if (empty($history)) : ?>
                <p style="text-align: center; color: #999;"><?php esc_html_e('Score history will appear after the first weekly test.', 'rocketfuel-cache'); ?></p>
            <?php else : ?>
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Date', 'rocketfuel-cache'); ?></th>
                            <th><?php esc_html_e('Mobile', 'rocketfuel-cache'); ?></th>
                            <th><?php esc_html_e('Desktop', 'rocketfuel-cache'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_reverse($history) as $entry) : ?>
                            <tr>
                                <td><?php echo esc_html($entry['date']); ?></td>
                                <td><?php echo esc_html($entry['mobile']); ?></td>
                                <td><?php echo esc_html($entry['desktop']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

</div>

<script>
jQuery(function($) {
    $('#rfc-run-test').on('click', function() {
        var $btn = $(this);
        $btn.prop('disabled', true);
        $btn.find('.rfc-test-label').text('<?php echo esc_js(__('Testing...', 'rocketfuel-cache')); ?>');
        $btn.find('.rfc-spinner').show();

        $.post(rfcAdmin.ajaxurl, {
            action: 'rfc_run_speed_test',
            nonce: rfcAdmin.nonce
        }, function(res) {
            if (res.success) {
                location.reload();
            } else {
                alert(res.data || '<?php echo esc_js(__('Test failed. Please try again.', 'rocketfuel-cache')); ?>');
                $btn.prop('disabled', false);
                $btn.find('.rfc-test-label').text('<?php echo esc_js(__('Run Test Now', 'rocketfuel-cache')); ?>');
                $btn.find('.rfc-spinner').hide();
            }
        }).fail(function() {
            alert('<?php echo esc_js(__('Request failed.', 'rocketfuel-cache')); ?>');
            $btn.prop('disabled', false);
            $btn.find('.rfc-test-label').text('<?php echo esc_js(__('Run Test Now', 'rocketfuel-cache')); ?>');
            $btn.find('.rfc-spinner').hide();
        });
    });

    $('#rfc-sync-report').on('click', function() {
        var $btn = $(this);
        $btn.prop('disabled', true).text('<?php echo esc_js(__('Syncing...', 'rocketfuel-cache')); ?>');

        $.post(rfcAdmin.ajaxurl, {
            action: 'rfc_sync_report',
            nonce: rfcAdmin.nonce
        }, function(res) {
            if (res.success) {
                $btn.text('<?php echo esc_js(__('Synced!', 'rocketfuel-cache')); ?>');
            } else {
                alert(res.data || '<?php echo esc_js(__('Sync failed.', 'rocketfuel-cache')); ?>');
                $btn.text('<?php echo esc_js(__('Sync to Dashboard', 'rocketfuel-cache')); ?>');
            }
            $btn.prop('disabled', false);
        });
    });

    $('.rfc-apply-suggestion').on('click', function() {
        var $btn = $(this);
        var id = $btn.data('suggestion');
        $btn.prop('disabled', true).text('<?php echo esc_js(__('Applying...', 'rocketfuel-cache')); ?>');

        $.post(rfcAdmin.ajaxurl, {
            action: 'rfc_apply_suggestion',
            nonce: rfcAdmin.nonce,
            suggestion_id: id
        }, function(res) {
            if (res.success) {
                $btn.text('<?php echo esc_js(__('Applied!', 'rocketfuel-cache')); ?>').css('background', '#0cce6b');
                $btn.closest('.rfc-suggestion-item').css('opacity', '0.6');
            } else {
                alert(res.data || '<?php echo esc_js(__('Failed to apply.', 'rocketfuel-cache')); ?>');
                $btn.prop('disabled', false).text('<?php echo esc_js(__('Apply', 'rocketfuel-cache')); ?>');
            }
        });
    });
});
</script>
