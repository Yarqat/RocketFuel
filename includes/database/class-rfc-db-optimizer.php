<?php
defined('ABSPATH') || exit;

final class RFC_DB_Optimizer {

    private $settings;
    private $results = [];

    public function __construct(RFC_Settings $settings) {
        $this->settings = $settings;
    }

    public function deleteRevisions($keep = 0) {
        global $wpdb;

        $keep = max(0, (int) $keep);

        if ($keep > 0) {
            $sub = $wpdb->prepare(
                "SELECT r.ID FROM {$wpdb->posts} r
                 INNER JOIN (
                     SELECT post_parent, MAX(post_date) as latest
                     FROM {$wpdb->posts}
                     WHERE post_type = 'revision' AND post_parent > 0
                     GROUP BY post_parent
                 ) keep ON r.post_parent = keep.post_parent
                 WHERE r.post_type = 'revision'
                 ORDER BY r.post_date DESC",
                []
            );

            $parents = $wpdb->get_col("SELECT DISTINCT post_parent FROM {$wpdb->posts} WHERE post_type = 'revision'");
            $protected = [];

            foreach ($parents as $parent_id) {
                $keeper_ids = $wpdb->get_col($wpdb->prepare(
                    "SELECT ID FROM {$wpdb->posts}
                     WHERE post_type = 'revision' AND post_parent = %d
                     ORDER BY post_date DESC LIMIT %d",
                    (int) $parent_id,
                    $keep
                ));
                $protected = array_merge($protected, $keeper_ids);
            }

            if (!empty($protected)) {
                $placeholders = implode(',', array_fill(0, count($protected), '%d'));
                $wpdb->query($wpdb->prepare(
                    "DELETE FROM {$wpdb->postmeta} WHERE post_id IN (
                        SELECT ID FROM {$wpdb->posts}
                        WHERE post_type = 'revision' AND ID NOT IN ({$placeholders})
                    )",
                    ...$protected
                ));
                $deleted = $wpdb->query($wpdb->prepare(
                    "DELETE FROM {$wpdb->posts} WHERE post_type = 'revision' AND ID NOT IN ({$placeholders})",
                    ...$protected
                ));
            } else {
                $deleted = 0;
            }
        } else {
            $wpdb->query(
                "DELETE pm FROM {$wpdb->postmeta} pm
                 INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
                 WHERE p.post_type = 'revision'"
            );
            $deleted = $wpdb->query("DELETE FROM {$wpdb->posts} WHERE post_type = 'revision'");
        }

        $this->results['revisions'] = (int) $deleted;
        return (int) $deleted;
    }

    public function deleteAutoDrafts() {
        global $wpdb;

        $wpdb->query(
            "DELETE pm FROM {$wpdb->postmeta} pm
             INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
             WHERE p.post_status = 'auto-draft'"
        );

        $deleted = $wpdb->query("DELETE FROM {$wpdb->posts} WHERE post_status = 'auto-draft'");
        $this->results['auto_drafts'] = (int) $deleted;
        return (int) $deleted;
    }

    public function deleteTrashedPosts() {
        global $wpdb;

        $wpdb->query(
            "DELETE pm FROM {$wpdb->postmeta} pm
             INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
             WHERE p.post_status = 'trash'"
        );

        $deleted = $wpdb->query("DELETE FROM {$wpdb->posts} WHERE post_status = 'trash'");
        $this->results['trashed_posts'] = (int) $deleted;
        return (int) $deleted;
    }

    public function deleteSpamComments() {
        global $wpdb;

        $wpdb->query(
            "DELETE cm FROM {$wpdb->commentmeta} cm
             INNER JOIN {$wpdb->comments} c ON cm.comment_id = c.comment_ID
             WHERE c.comment_approved = 'spam'"
        );

        $deleted = $wpdb->query("DELETE FROM {$wpdb->comments} WHERE comment_approved = 'spam'");
        $this->results['spam_comments'] = (int) $deleted;
        return (int) $deleted;
    }

    public function deleteTrashedComments() {
        global $wpdb;

        $wpdb->query(
            "DELETE cm FROM {$wpdb->commentmeta} cm
             INNER JOIN {$wpdb->comments} c ON cm.comment_id = c.comment_ID
             WHERE c.comment_approved = 'trash'"
        );

        $deleted = $wpdb->query("DELETE FROM {$wpdb->comments} WHERE comment_approved = 'trash'");
        $this->results['trashed_comments'] = (int) $deleted;
        return (int) $deleted;
    }

    public function deleteExpiredTransients() {
        global $wpdb;

        $now = time();

        $expired = $wpdb->get_col($wpdb->prepare(
            "SELECT option_name FROM {$wpdb->options}
             WHERE option_name LIKE %s AND option_value < %d",
            $wpdb->esc_like('_transient_timeout_') . '%',
            $now
        ));

        $count = 0;
        foreach ($expired as $timeout_key) {
            $transient_key = str_replace('_transient_timeout_', '_transient_', $timeout_key);
            $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name = %s", $timeout_key));
            $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name = %s", $transient_key));
            $count++;
        }

        $site_expired = $wpdb->get_col($wpdb->prepare(
            "SELECT option_name FROM {$wpdb->options}
             WHERE option_name LIKE %s AND option_value < %d",
            $wpdb->esc_like('_site_transient_timeout_') . '%',
            $now
        ));

        foreach ($site_expired as $timeout_key) {
            $transient_key = str_replace('_site_transient_timeout_', '_site_transient_', $timeout_key);
            $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name = %s", $timeout_key));
            $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name = %s", $transient_key));
            $count++;
        }

        $this->results['expired_transients'] = $count;
        return $count;
    }

    public function deleteOrphanedMeta() {
        global $wpdb;

        $deleted = $wpdb->query(
            "DELETE pm FROM {$wpdb->postmeta} pm
             LEFT JOIN {$wpdb->posts} p ON pm.post_id = p.ID
             WHERE p.ID IS NULL"
        );

        $orphaned_comment_meta = $wpdb->query(
            "DELETE cm FROM {$wpdb->commentmeta} cm
             LEFT JOIN {$wpdb->comments} c ON cm.comment_id = c.comment_ID
             WHERE c.comment_ID IS NULL"
        );

        $total = (int) $deleted + (int) $orphaned_comment_meta;
        $this->results['orphaned_meta'] = $total;
        return $total;
    }

    public function optimizeTables() {
        global $wpdb;

        $tables = $wpdb->get_col("SHOW TABLES LIKE '{$wpdb->prefix}%'");
        $optimized = 0;

        foreach ($tables as $table) {
            $safe = esc_sql($table);
            $result = $wpdb->query("OPTIMIZE TABLE `{$safe}`");
            if ($result !== false) {
                $optimized++;
            }
        }

        $this->results['tables_optimized'] = $optimized;
        return $optimized;
    }

    public function runAll() {
        $this->results = [];

        if ($this->settings->get('db_revisions', true)) {
            $keep = $this->settings->get('limit_revisions', false)
                ? (int) $this->settings->get('revisions_count', 5)
                : 0;
            $this->deleteRevisions($keep);
        }

        if ($this->settings->get('db_auto_drafts', true)) {
            $this->deleteAutoDrafts();
        }

        if ($this->settings->get('db_trashed_posts', true)) {
            $this->deleteTrashedPosts();
        }

        if ($this->settings->get('db_spam_comments', true)) {
            $this->deleteSpamComments();
        }

        if ($this->settings->get('db_trashed_comments', true)) {
            $this->deleteTrashedComments();
        }

        if ($this->settings->get('db_expired_transients', true)) {
            $this->deleteExpiredTransients();
        }

        if ($this->settings->get('db_orphaned_meta', true)) {
            $this->deleteOrphanedMeta();
        }

        if ($this->settings->get('db_optimize_tables', true)) {
            $this->optimizeTables();
        }

        do_action('rfc_db_optimized', $this->results);

        return $this->results;
    }

    public function getStats() {
        global $wpdb;

        $stats = [];

        $stats['revisions'] = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'revision'"
        );

        $stats['auto_drafts'] = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = 'auto-draft'"
        );

        $stats['trashed_posts'] = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = 'trash'"
        );

        $stats['spam_comments'] = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->comments} WHERE comment_approved = 'spam'"
        );

        $stats['trashed_comments'] = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->comments} WHERE comment_approved = 'trash'"
        );

        $stats['expired_transients'] = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->options}
             WHERE option_name LIKE %s AND option_value < %d",
            $wpdb->esc_like('_transient_timeout_') . '%',
            time()
        ));

        $stats['orphaned_postmeta'] = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$wpdb->postmeta} pm
             LEFT JOIN {$wpdb->posts} p ON pm.post_id = p.ID
             WHERE p.ID IS NULL"
        );

        $stats['tables'] = count($wpdb->get_col("SHOW TABLES LIKE '{$wpdb->prefix}%'"));

        $size_result = $wpdb->get_row(
            "SELECT SUM(data_length + index_length) AS total_size,
                    SUM(data_free) AS overhead
             FROM information_schema.TABLES
             WHERE table_schema = DATABASE()
             AND table_name LIKE '{$wpdb->prefix}%'"
        );

        $stats['db_size'] = $size_result ? (int) $size_result->total_size : 0;
        $stats['db_overhead'] = $size_result ? (int) $size_result->overhead : 0;

        return $stats;
    }
}
