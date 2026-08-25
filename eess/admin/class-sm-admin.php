<?php

class SM_Admin {
    private $plugin_name;
    private $version;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    public function add_menu_pages() {
        add_menu_page(
            'خدمات الأنظمة الإلكترونية التعليمية (EESS)',
            'خدمات الأنظمة الإلكترونية التعليمية (EESS)',
            'read', // Allow all roles to see top level
            'sm-dashboard',
            array($this, 'display_dashboard'),
            'dashicons-welcome-learn-more',
            6
        );

        add_submenu_page(
            'sm-dashboard',
            'لوحة التحكم',
            'لوحة التحكم',
            'read',
            'sm-dashboard',
            array($this, 'display_dashboard')
        );

        add_submenu_page(
            'sm-dashboard',
            'تسجيل مخالفة',
            'تسجيل مخالفة',
            'تسجيل_مخالفة',
            'sm-record-violation',
            array($this, 'display_record_violation')
        );

        add_submenu_page(
            'sm-dashboard',
            'شؤون الطلاب',
            'شؤون الطلاب',
            'إدارة_الطلاب',
            'sm-students',
            array($this, 'display_students')
        );

        add_submenu_page(
            'sm-dashboard',
            'المعلمون',
            'المعلمون',
            'إدارة_المعلمين',
            'sm-teachers',
            array($this, 'display_teachers_page')
        );

        add_submenu_page(
            'sm-dashboard',
            'إعدادات النظام',
            'إعدادات النظام',
            'إدارة_النظام',
            'sm-settings',
            array($this, 'display_settings')
        );
    }

    public function enqueue_styles() {
        wp_enqueue_style('google-font-cairo', 'https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800;900&family=Noto+Kufi+Arabic:wght@300;400;600;700;800&display=swap', array(), null);
        wp_enqueue_style($this->plugin_name, SM_PLUGIN_URL . 'assets/css/sm-admin.css', array(), $this->version, 'all');

        $appearance = SM_Settings::get_appearance();
        $custom_css = "
            :root {
                --sm-primary-color: {$appearance['primary_color']};
                --sm-secondary-color: {$appearance['secondary_color']};
                --sm-accent-color: {$appearance['accent_color']};
                --sm-dark-color: {$appearance['dark_color']};
                --sm-radius: {$appearance['border_radius']};
            }
            .sm-content-wrapper, .sm-admin-dashboard, .sm-container,
            .sm-content-wrapper *:not(.dashicons), .sm-admin-dashboard *:not(.dashicons), .sm-container *:not(.dashicons) {
                font-family: 'Cairo', 'Noto Kufi Arabic', sans-serif !important;
            }
            .sm-content-wrapper { font-size: calc({$appearance['font_size']} * 0.93); }
        ";
        wp_add_inline_style($this->plugin_name, $custom_css);
    }

    public function display_dashboard() {
        $_GET['sm_tab'] = 'summary';
        $this->display_settings();
    }

    public function display_record_violation() {
        $_GET['sm_tab'] = 'record';
        $this->display_settings();
    }

    public function display_settings() {
        if (isset($_POST['sm_save_settings_unified'])) {
            check_admin_referer('sm_admin_action', 'sm_admin_nonce');
            $existing = SM_Settings::get_school_info();
            SM_Settings::save_school_info(array(
                'school_name' => sanitize_text_field($_POST['school_name']),
                'school_principal_name' => isset($_POST['school_principal_name']) ? sanitize_text_field($_POST['school_principal_name']) : ($existing['school_principal_name'] ?? ''),
                'phone' => sanitize_text_field($_POST['school_phone']),
                'email' => sanitize_email($_POST['school_email']),
                'school_logo' => esc_url_raw($_POST['school_logo']),
                'address' => isset($_POST['school_address']) ? sanitize_text_field($_POST['school_address']) : ($existing['address'] ?? ''),
                'working_schedule' => $existing['working_schedule'] ?? array()
            ));
            echo '<div class="updated"><p>تم حفظ بيانات المدرسة بنجاح.</p></div>';
        }

        if (isset($_POST['sm_save_appearance'])) {
            check_admin_referer('sm_admin_action', 'sm_admin_nonce');
            SM_Settings::save_appearance(array(
                'primary_color' => sanitize_hex_color($_POST['primary_color']),
                'secondary_color' => sanitize_hex_color($_POST['secondary_color']),
                'accent_color' => sanitize_hex_color($_POST['accent_color']),
                'dark_color' => sanitize_hex_color($_POST['dark_color']),
                'font_size' => sanitize_text_field($_POST['font_size']),
                'border_radius' => sanitize_text_field($_POST['border_radius']),
                'table_style' => sanitize_text_field($_POST['table_style']),
                'button_style' => sanitize_text_field($_POST['button_style'])
            ));
            echo '<div class="updated"><p>تم حفظ إعدادات التصميم بنجاح.</p></div>';
        }

        if (isset($_POST['sm_save_violation_settings'])) {
            check_admin_referer('sm_admin_action', 'sm_admin_nonce');
            // Logic to save violation types and suggested actions
            $types_raw = explode("\n", str_replace("\r", "", $_POST['violation_types']));
            $types = array();
            foreach ($types_raw as $line) {
                if (strpos($line, '|') !== false) {
                    list($k, $v) = explode('|', $line);
                    $types[trim($k)] = trim($v);
                }
            }
            if (!empty($types)) SM_Settings::save_violation_types($types);

            SM_Settings::save_suggested_actions(array(
                'low' => sanitize_textarea_field($_POST['suggested_low']),
                'medium' => sanitize_textarea_field($_POST['suggested_medium']),
                'high' => sanitize_textarea_field($_POST['suggested_high'])
            ));
            echo '<div class="updated"><p>تم حفظ إعدادات المخالفات بنجاح.</p></div>';
        }

        $student_filters = array();
        $stats = SM_DB::get_statistics();
        $records = SM_DB::get_records();
        $students = SM_DB::get_students();
        include SM_PLUGIN_DIR . 'templates/public-admin-panel.php';
    }

    public function display_teachers_page() {
        $_GET['sm_tab'] = 'teachers';
        $this->display_settings();
    }

    public function display_records() {
        if (isset($_POST['sm_update_record'])) {
            check_admin_referer('sm_record_action', 'sm_nonce');
            if (current_user_can('إدارة_المخالفات')) {
                SM_DB::update_record(intval($_POST['record_id']), $_POST);
                echo '<div class="updated"><p>تم تحديث السجل بنجاح.</p></div>';
            }
        }

        $filters = array();
        if (isset($_GET['student_filter'])) $filters['student_id'] = intval($_GET['student_filter']);
        if (isset($_GET['start_date'])) $filters['start_date'] = sanitize_text_field($_GET['start_date']);
        if (isset($_GET['end_date'])) $filters['end_date'] = sanitize_text_field($_GET['end_date']);
        if (isset($_GET['type_filter'])) $filters['type'] = sanitize_text_field($_GET['type_filter']);

        // Teacher filter
        if (!current_user_can('إدارة_المستخدمين') && current_user_can('تسجيل_مخالفة')) {
            $filters['teacher_id'] = get_current_user_id();
        }

        $records = SM_DB::get_records($filters);
        include SM_PLUGIN_DIR . 'templates/public-dashboard-stats.php';
    }

    public function display_students() {
        $_GET['sm_tab'] = 'students';
        $this->display_settings();
    }

}
