<?php

class School_Management {
    protected $loader;
    protected $plugin_name;
    protected $version;

    public function __construct() {
        $this->plugin_name = 'school-management';
        $this->version = SM_VERSION;
        $this->load_dependencies();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    private function load_dependencies() {
        require_once SM_PLUGIN_DIR . 'includes/class-sm-loader.php';
        require_once SM_PLUGIN_DIR . 'includes/class-sm-db.php';
        require_once SM_PLUGIN_DIR . 'includes/class-sm-settings.php';
        require_once SM_PLUGIN_DIR . 'includes/class-sm-logger.php';
        require_once SM_PLUGIN_DIR . 'includes/class-sm-notifications.php';
        require_once SM_PLUGIN_DIR . 'admin/class-sm-admin.php';
        require_once SM_PLUGIN_DIR . 'public/class-sm-public.php';
        $this->loader = new SM_Loader();
    }

    private function define_admin_hooks() {
        $plugin_admin = new SM_Admin($this->get_plugin_name(), $this->get_version());
        $this->loader->add_action('admin_menu', $plugin_admin, 'add_menu_pages');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
    }

    private function define_public_hooks() {
        $plugin_public = new SM_Public($this->get_plugin_name(), $this->get_version());
        $this->loader->add_filter('show_admin_bar', $plugin_public, 'hide_admin_bar_for_non_admins');
        $this->loader->add_action('delete_user', $plugin_public, 'prevent_system_admin_deletion');
        $this->loader->add_filter('get_avatar', $plugin_public, 'custom_user_avatar', 10, 3);
        $this->loader->add_action('admin_init', $plugin_public, 'restrict_admin_access');
        $this->loader->add_action('admin_init', $plugin_public, 'intercept_ajax_requests');
        $this->loader->add_action('admin_init', $plugin_public, 'handle_form_submission');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('init', $plugin_public, 'register_shortcodes');
        $this->loader->add_action('template_redirect', $plugin_public, 'handle_form_submission');
        $this->loader->add_action('wp_login_failed', $plugin_public, 'login_failed');
        $this->loader->add_action('wp_login', $plugin_public, 'log_successful_login', 10, 2);
        $this->loader->add_filter('wp_authenticate_user', $plugin_public, 'block_pending_users_login', 10, 2);
        $this->loader->add_action('wp_ajax_sm_get_student', $plugin_public, 'ajax_get_student');
        $this->loader->add_action('wp_ajax_sm_search_students', $plugin_public, 'ajax_search_students');
        $this->loader->add_action('wp_ajax_sm_get_student_intelligence', $plugin_public, 'ajax_get_student_intelligence');
        $this->loader->add_action('wp_ajax_sm_refresh_dashboard', $plugin_public, 'ajax_refresh_dashboard');
        $this->loader->add_action('wp_ajax_sm_save_record_ajax', $plugin_public, 'ajax_save_record');
        $this->loader->add_action('wp_ajax_sm_update_student_photo', $plugin_public, 'ajax_update_student_photo');
        $this->loader->add_action('wp_ajax_sm_update_record_status', $plugin_public, 'ajax_update_record_status');
        $this->loader->add_action('wp_ajax_sm_add_student_ajax', $plugin_public, 'ajax_add_student');
        $this->loader->add_action('wp_ajax_sm_update_student_ajax', $plugin_public, 'ajax_update_student');
        $this->loader->add_action('wp_ajax_sm_delete_student_ajax', $plugin_public, 'ajax_delete_student');
        $this->loader->add_action('wp_ajax_sm_delete_record_ajax', $plugin_public, 'ajax_delete_record');
        $this->loader->add_action('wp_ajax_sm_get_counts_ajax', $plugin_public, 'ajax_get_counts');
        $this->loader->add_action('wp_ajax_sm_add_user_ajax', $plugin_public, 'ajax_add_user');
        $this->loader->add_action('wp_ajax_sm_update_generic_user_ajax', $plugin_public, 'ajax_update_generic_user');
        $this->loader->add_action('wp_ajax_sm_add_teacher_ajax', $plugin_public, 'ajax_add_teacher');
        $this->loader->add_action('wp_ajax_sm_update_teacher_ajax', $plugin_public, 'ajax_update_teacher');
        $this->loader->add_action('wp_ajax_sm_add_parent_ajax', $plugin_public, 'ajax_add_parent');
        $this->loader->add_action('wp_ajax_sm_update_profile_ajax', $plugin_public, 'ajax_update_profile');
        $this->loader->add_action('wp_ajax_sm_bulk_delete_ajax', $plugin_public, 'ajax_bulk_delete');
        $this->loader->add_action('wp_ajax_sm_initialize_system_ajax', $plugin_public, 'ajax_initialize_system');
        $this->loader->add_action('wp_ajax_sm_rollback_log_ajax', $plugin_public, 'ajax_rollback_log');
        $this->loader->add_action('wp_ajax_sm_delete_log_ajax', $plugin_public, 'ajax_delete_log');
        $this->loader->add_action('wp_ajax_sm_save_regulation_settings_ajax', $plugin_public, 'ajax_save_regulation_settings');
        $this->loader->add_action('wp_ajax_sm_save_hierarchical_violations_ajax', $plugin_public, 'ajax_save_hierarchical_violations');
        $this->loader->add_action('wp_ajax_sm_delete_all_logs_ajax', $plugin_public, 'ajax_delete_all_logs');
        $this->loader->add_action('wp_ajax_sm_get_students_attendance_ajax', $plugin_public, 'ajax_get_students_attendance');
        $this->loader->add_action('wp_ajax_nopriv_sm_get_students_attendance_ajax', $plugin_public, 'ajax_get_students_attendance');
        $this->loader->add_action('wp_ajax_sm_save_attendance_ajax', $plugin_public, 'ajax_save_attendance');
        $this->loader->add_action('wp_ajax_nopriv_sm_save_attendance_ajax', $plugin_public, 'ajax_save_attendance');
        $this->loader->add_action('wp_ajax_sm_save_attendance_batch_ajax', $plugin_public, 'ajax_save_attendance_batch');
        $this->loader->add_action('wp_ajax_nopriv_sm_save_attendance_batch_ajax', $plugin_public, 'ajax_save_attendance_batch');
        $this->loader->add_action('wp_ajax_sm_upload_import_csv', $plugin_public, 'ajax_upload_import_csv');
        $this->loader->add_action('wp_ajax_sm_process_import_chunk', $plugin_public, 'ajax_process_import_chunk');
        $this->loader->add_action('wp_ajax_sm_export_students_csv', $plugin_public, 'ajax_export_students_csv');
        $this->loader->add_action('wp_ajax_sm_download_student_import_template', $plugin_public, 'ajax_download_student_import_template');
        $this->loader->add_action('wp_ajax_sm_print_student_full_report', $plugin_public, 'ajax_print_student_full_report');
        $this->loader->add_action('wp_ajax_sm_reset_class_code_ajax', $plugin_public, 'ajax_reset_class_code');
        $this->loader->add_action('wp_ajax_sm_toggle_attendance_status_ajax', $plugin_public, 'ajax_toggle_attendance_status');
        $this->loader->add_action('wp_ajax_sm_filter_violations', $plugin_public, 'ajax_filter_violations');
        $this->loader->add_action('wp_ajax_sm_mark_contacted', $plugin_public, 'ajax_mark_contacted');
        $this->loader->add_action('wp_ajax_sm_add_document_ajax', $plugin_public, 'ajax_add_document');
        $this->loader->add_action('wp_ajax_sm_update_document_ajax', $plugin_public, 'ajax_update_document');
        $this->loader->add_action('wp_ajax_sm_delete_document_ajax', $plugin_public, 'ajax_delete_document');
        $this->loader->add_action('wp_ajax_sm_add_assignment_ajax', $plugin_public, 'ajax_add_assignment');
        $this->loader->add_action('wp_ajax_sm_approve_plan_ajax', $plugin_public, 'ajax_approve_plan');
        $this->loader->add_action('wp_ajax_sm_bulk_delete_users_ajax', $plugin_public, 'ajax_bulk_delete_users');
        $this->loader->add_action('wp_ajax_sm_add_clinic_referral', $plugin_public, 'ajax_add_clinic_referral');
        $this->loader->add_action('wp_ajax_sm_confirm_clinic_arrival', $plugin_public, 'ajax_confirm_clinic_arrival');
        $this->loader->add_action('wp_ajax_sm_update_clinic_record', $plugin_public, 'ajax_update_clinic_record');
        $this->loader->add_action('wp_ajax_sm_get_clinic_reports', $plugin_public, 'ajax_get_clinic_reports');
        $this->loader->add_action('wp_ajax_sm_export_violations_csv', $plugin_public, 'ajax_export_violations_csv');
        $this->loader->add_action('wp_ajax_sm_export_users_csv', $plugin_public, 'ajax_export_users_csv');
        $this->loader->add_action('wp_ajax_sm_save_grade_ajax', $plugin_public, 'ajax_save_grade_ajax');
        $this->loader->add_action('wp_ajax_eess_import_grades_ajax', $plugin_public, 'ajax_import_grades');
        $this->loader->add_action('wp_ajax_sm_get_student_grades_ajax', $plugin_public, 'ajax_get_student_grades_ajax');
        $this->loader->add_action('wp_ajax_sm_delete_grade_ajax', $plugin_public, 'ajax_delete_grade_ajax');
        $this->loader->add_action('wp_ajax_sm_add_subject', $plugin_public, 'ajax_add_subject');
        $this->loader->add_action('wp_ajax_sm_delete_subject', $plugin_public, 'ajax_delete_subject');
        $this->loader->add_action('wp_ajax_sm_get_subjects', $plugin_public, 'ajax_get_subjects');
        $this->loader->add_action('wp_ajax_sm_save_class_grades', $plugin_public, 'ajax_save_class_grades');
        $this->loader->add_action('wp_ajax_sm_export_grades_csv', $plugin_public, 'ajax_export_grades_csv');
        $this->loader->add_action('wp_ajax_sm_import_grades_csv', $plugin_public, 'ajax_import_grades_csv');
        $this->loader->add_action('wp_ajax_sm_bulk_delete_students_ajax', $plugin_public, 'ajax_bulk_delete_students');
        $this->loader->add_action('wp_ajax_sm_download_plans_zip', $plugin_public, 'ajax_download_plans_zip');
        $this->loader->add_action('wp_ajax_sm_refresh_system_cache_ajax', $plugin_public, 'ajax_refresh_system');
        $this->loader->add_action('wp_ajax_eess_hr_add_employee', $plugin_public, 'ajax_hr_add_employee');
        $this->loader->add_action('wp_ajax_eess_bulk_import_employees_ajax', $plugin_public, 'ajax_bulk_import_employees');

        // Forgot password AJAX actions (Multi-step Verification Without OTP)
        $this->loader->add_action('wp_ajax_nopriv_eess_forgot_verify_identity', $plugin_public, 'ajax_forgot_verify_identity');
        $this->loader->add_action('wp_ajax_eess_forgot_verify_identity', $plugin_public, 'ajax_forgot_verify_identity');
        $this->loader->add_action('wp_ajax_nopriv_eess_forgot_set_password', $plugin_public, 'ajax_forgot_set_password');
        $this->loader->add_action('wp_ajax_eess_forgot_set_password', $plugin_public, 'ajax_forgot_set_password');

        // Registration wizard AJAX action (Without OTP)
        $this->loader->add_action('wp_ajax_nopriv_eess_register_submit', $plugin_public, 'ajax_register_submit');
        $this->loader->add_action('wp_ajax_eess_register_submit', $plugin_public, 'ajax_register_submit');

        // Mobile Unauthenticated Lesson Prep Employee Verification & Submission
        $this->loader->add_action('wp_ajax_nopriv_sm_verify_employee_id', $plugin_public, 'ajax_verify_employee_id');
        $this->loader->add_action('wp_ajax_sm_verify_employee_id', $plugin_public, 'ajax_verify_employee_id');
        $this->loader->add_action('wp_ajax_nopriv_sm_submit_mobile_lesson', $plugin_public, 'ajax_submit_mobile_lesson');
        $this->loader->add_action('wp_ajax_sm_submit_mobile_lesson', $plugin_public, 'ajax_submit_mobile_lesson');

        // System Announcements & Notifications AJAX actions
        $this->loader->add_action('wp_ajax_sm_create_system_announcement', $plugin_public, 'ajax_create_system_announcement');
        $this->loader->add_action('wp_ajax_sm_get_pending_announcements', $plugin_public, 'ajax_get_pending_announcements');
        $this->loader->add_action('wp_ajax_nopriv_sm_get_pending_announcements', $plugin_public, 'ajax_get_pending_announcements');
        $this->loader->add_action('wp_ajax_sm_mark_announcement_viewed', $plugin_public, 'ajax_mark_announcement_viewed');
        $this->loader->add_action('wp_ajax_sm_mark_announcement_closed', $plugin_public, 'ajax_mark_announcement_closed');
        $this->loader->add_action('wp_ajax_sm_reset_user_announcement', $plugin_public, 'ajax_reset_user_announcement');
        $this->loader->add_action('wp_ajax_sm_disable_system_announcement', $plugin_public, 'ajax_disable_system_announcement');
        $this->loader->add_action('wp_ajax_sm_delete_system_announcement', $plugin_public, 'ajax_delete_system_announcement');
        $this->loader->add_action('wp_ajax_sm_delete_user_announcement_log', $plugin_public, 'ajax_delete_user_announcement_log');

        // Term & Annual Plans AJAX actions
        $this->loader->add_action('wp_ajax_sm_save_term_plan', $plugin_public, 'ajax_save_term_plan');
        $this->loader->add_action('wp_ajax_sm_review_term_plan', $plugin_public, 'ajax_review_term_plan');
        $this->loader->add_action('wp_ajax_sm_delete_term_plan', $plugin_public, 'ajax_delete_term_plan');

        // Print / PDF action
        $this->loader->add_action('wp_ajax_sm_save_asset_inventory', $plugin_public, 'ajax_sm_save_asset_inventory');
        $this->loader->add_action('wp_ajax_sm_save_asset_request', $plugin_public, 'ajax_sm_save_asset_request');
        $this->loader->add_action('wp_ajax_sm_mark_teacher_contacted', $plugin_public, 'ajax_sm_mark_teacher_contacted');
        $this->loader->add_action('wp_ajax_sm_assign_term_plan', $plugin_public, 'ajax_sm_assign_term_plan');
        $this->loader->add_action('wp_ajax_sm_assign_lesson_prep', $plugin_public, 'ajax_sm_assign_lesson_prep');
        $this->loader->add_action('wp_ajax_sm_copy_record', $plugin_public, 'ajax_sm_copy_record');
        $this->loader->add_action('wp_ajax_sm_print', $plugin_public, 'ajax_sm_print');

        // Admin approval actions
        $this->loader->add_action('wp_ajax_eess_approve_user', $plugin_public, 'ajax_approve_user');
        $this->loader->add_action('wp_ajax_eess_reject_user', $plugin_public, 'ajax_reject_user');
        $this->loader->add_action('wp_ajax_eess_save_user_notes', $plugin_public, 'ajax_save_user_notes');
        $this->loader->add_action('wp_ajax_eess_get_user_assignments', $plugin_public, 'ajax_get_user_assignments');

        // Unified User & Employee Modal AJAX Handlers
        $this->loader->add_action('wp_ajax_eess_check_user_uniqueness', $plugin_public, 'ajax_check_user_uniqueness');
        $this->loader->add_action('wp_ajax_eess_get_user_unified', $plugin_public, 'ajax_get_user_unified');
        $this->loader->add_action('wp_ajax_eess_save_user_unified', $plugin_public, 'ajax_save_user_unified');

        // Lesson Prep Quick Actions & Bulk Operations
        $this->loader->add_action('wp_ajax_eess_quick_approve_prep', $plugin_public, 'ajax_quick_approve_prep');
        $this->loader->add_action('wp_ajax_eess_reject_lesson_prep', $plugin_public, 'ajax_reject_lesson_prep');
        $this->loader->add_action('wp_ajax_eess_bulk_lesson_action', $plugin_public, 'ajax_bulk_lesson_action');

        // Technical Support & Quick Parent Note AJAX Handlers
        $this->loader->add_action('wp_ajax_eess_submit_support_request', $plugin_public, 'ajax_submit_support_request');
        $this->loader->add_action('wp_ajax_eess_update_support_status', $plugin_public, 'ajax_update_support_status');
        $this->loader->add_action('wp_ajax_eess_delete_support_request', $plugin_public, 'ajax_delete_support_request');
        $this->loader->add_action('wp_ajax_eess_send_quick_parent_note', $plugin_public, 'ajax_send_quick_parent_note');
        $this->loader->add_action('wp_ajax_sm_get_educational_suggestions', $plugin_public, 'ajax_get_educational_suggestions');
        $this->loader->add_action('wp_ajax_sm_save_educational_input', $plugin_public, 'ajax_save_educational_input');
        $this->loader->add_action('wp_ajax_sm_submit_behavior_referral', $plugin_public, 'ajax_submit_behavior_referral');
    }

    public function run() {
        $this->check_version_updates();
        $this->loader->run();
    }

    private function check_version_updates() {
        $db_version = get_option('sm_plugin_version', '1.0.0');
        if (version_compare($db_version, SM_VERSION, '<')) {
            require_once SM_PLUGIN_DIR . 'includes/class-sm-activator.php';
            SM_Activator::activate(); // Run full activation logic including dbDelta
            update_option('sm_plugin_version', SM_VERSION);
        }
    }

    public function get_plugin_name() {
        return $this->plugin_name;
    }

    public function get_version() {
        return $this->version;
    }
}
