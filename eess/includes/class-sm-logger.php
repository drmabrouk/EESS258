<?php

class SM_Logger {
    public static function log($action, $details = '') {
        global $wpdb;
        $user_id = get_current_user_id();

        $wpdb->insert(
            "{$wpdb->prefix}sm_logs",
            array(
                'user_id' => $user_id,
                'action' => sanitize_text_field($action),
                'details' => sanitize_textarea_field($details),
                'created_at' => current_time('mysql')
            )
        );

        // Limit to 200 entries
        $count = (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sm_logs");
        if ($count > 200) {
            $limit = $count - 200;
            $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}sm_logs ORDER BY created_at ASC LIMIT %d", $limit));
        }
    }

    public static function get_logs($limit = 100, $offset = 0) {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            "SELECT l.*, u.display_name FROM {$wpdb->prefix}sm_logs l LEFT JOIN {$wpdb->base_prefix}users u ON l.user_id = u.ID ORDER BY l.created_at DESC LIMIT %d OFFSET %d",
            $limit,
            $offset
        ));
    }

    public static function get_total_logs() {
        global $wpdb;
        return (int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sm_logs");
    }
}
