<?php defined('ABSPATH') || exit; ?>

<?php
global $wpdb;

$counts = [
    'revisions'          => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'revision'"),
    'auto_drafts'        => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = 'auto-draft'"),
    'trashed_posts'      => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = 'trash'"),
    'spam_comments'      => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->comments} WHERE comment_approved = 'spam'"),
    'trashed_comments'   => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->comments} WHERE comment_approved = 'trash'"),
    'expired_transients' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_%' AND option_value < UNIX_TIMESTAMP()"),
    'orphaned_postmeta'  => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->postmeta} pm LEFT JOIN {$wpdb->posts} p ON pm.post_id = p.ID WHERE p.ID IS NULL"),
    'orphaned_commentmeta' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->commentmeta} cm LEFT JOIN {$wpdb->comments} c ON cm.comment_id = c.comment_ID WHERE c.comment_ID IS NULL"),
];

$total = array_sum($counts);
$labels = [
    'revisions'            => __('Post Revisions', 'rocketfuel-cache'),
    'auto_drafts'          => __('Auto Drafts', 'rocketfuel-cache'),
    'trashed_posts'        => __('Trashed Posts', 'rocketfuel-cache'),
    'spam_comments'        => __('Spam Comments', 'rocketfuel-cache'),
    'trashed_comments'     => __('Trashed Comments', 'rocketfuel-cache'),
    'expired_transients'   => __('Expired Transients', 'rocketfuel-cache'),
    'orphaned_postmeta'    => __('Orphaned Post Meta', 'rocketfuel-cache'),
    'orphaned_commentmeta' => __('Orphaned Comment Meta', 'rocketfuel-cache'),
];
?>

<div class="rfc-settings-page">

    <div class="rfc-card">
        <h3><?php esc_html_e('Database Cleanup', 'rocketfuel-cache'); ?></h3>
        <p class="rfc-card-desc">
            <?php printf(esc_html__('Found %d items that can be cleaned up.', 'rocketfuel-cache'), $total); ?>
        </p>

        <form id="rfc-db-cleanup-form">
            <?php wp_nonce_field('rfc_db_cleanup', '_rfc_db_nonce'); ?>

            <table class="rfc-db-table">
                <thead>
                    <tr>
                        <th class="rfc-db-check"><input type="checkbox" id="rfc-db-select-all"></th>
                        <th><?php esc_html_e('Type', 'rocketfuel-cache'); ?></th>
                        <th><?php esc_html_e('Items', 'rocketfuel-cache'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($counts as $key => $count) : ?>
                        <tr>
                            <td class="rfc-db-check">
                                <input type="checkbox" name="rfc_db_tasks[]" value="<?php echo esc_attr($key); ?>" <?php echo $count > 0 ? '' : 'disabled'; ?>>
                            </td>
                            <td><?php echo esc_html($labels[$key]); ?></td>
                            <td><span class="rfc-db-count <?php echo $count > 0 ? 'rfc-db-has-items' : ''; ?>"><?php echo esc_html(number_format_i18n($count)); ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="rfc-db-actions">
                <button type="button" id="rfc-db-clean-btn" class="rfc-btn rfc-btn-primary"><?php esc_html_e('Clean Selected', 'rocketfuel-cache'); ?></button>
            </div>
        </form>

        <div id="rfc-db-results" class="rfc-db-results" style="display:none;">
            <h4><?php esc_html_e('Results', 'rocketfuel-cache'); ?></h4>
            <div class="rfc-progress-bar">
                <div class="rfc-progress-fill" id="rfc-db-progress"></div>
            </div>
            <ul id="rfc-db-result-list"></ul>
        </div>
    </div>

    <div class="rfc-card">
        <h3><?php esc_html_e('Optimize Tables', 'rocketfuel-cache'); ?></h3>
        <p class="rfc-card-desc"><?php esc_html_e('Run the MySQL OPTIMIZE TABLE command to reclaim unused space.', 'rocketfuel-cache'); ?></p>
        <button type="button" id="rfc-db-optimize-btn" class="rfc-btn"><?php esc_html_e('Optimize Tables', 'rocketfuel-cache'); ?></button>
    </div>

    <div class="rfc-card rfc-pro-section">
        <h3><?php esc_html_e('Scheduled Cleanup', 'rocketfuel-cache'); ?> <span class="rfc-pro-badge">PRO</span></h3>

        <div class="rfc-field">
            <label class="rfc-toggle rfc-toggle-disabled">
                <input type="checkbox" disabled>
                <span class="rfc-toggle-slider"></span>
            </label>
            <span class="rfc-field-label"><?php esc_html_e('Enable Automatic Cleanup', 'rocketfuel-cache'); ?></span>
        </div>

        <div class="rfc-field">
            <label class="rfc-field-label"><?php esc_html_e('Frequency', 'rocketfuel-cache'); ?></label>
            <select disabled class="rfc-select">
                <option><?php esc_html_e('Daily', 'rocketfuel-cache'); ?></option>
                <option><?php esc_html_e('Weekly', 'rocketfuel-cache'); ?></option>
                <option><?php esc_html_e('Monthly', 'rocketfuel-cache'); ?></option>
            </select>
        </div>

        <div class="rfc-field">
            <label class="rfc-toggle rfc-toggle-disabled">
                <input type="checkbox" disabled>
                <span class="rfc-toggle-slider"></span>
            </label>
            <span class="rfc-field-label"><?php esc_html_e('Email Report After Cleanup', 'rocketfuel-cache'); ?></span>
        </div>
    </div>

</div>
