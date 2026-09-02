<?php

class SM_Settings {
    public static function get_violation_types() {
        $default = array(
            'behavior' => 'سلوك',
            'lateness' => 'تأخر',
            'absence' => 'غياب',
            'other' => 'أخرى'
        );
        return get_option('sm_violation_types', $default);
    }

    public static function get_severities() {
        return array(
            'low' => 'منخفضة',
            'medium' => 'متوسطة',
            'high' => 'خطيرة'
        );
    }

    public static function save_violation_types($types) {
        update_option('sm_violation_types', $types);
    }

    public static function get_appearance() {
        $default = array(
            // Primary Brand Colors
            'primary_color' => '#8B0000',      // Primary Dark Red
            'primary_hover' => '#6F0000',      // Dark Red Hover
            'danger_color'  => '#C62828',      // Global Danger Red
            'danger_hover'  => '#A61B1B',
            'black_color'   => '#000000',
            'white_color'   => '#FFFFFF',

            // Complete Grayscale Palette
            'gray_50'  => '#F8F8F8',
            'gray_100' => '#F5F5F5',
            'gray_200' => '#EEEEEE',
            'gray_300' => '#E0E0E0',
            'gray_400' => '#BDBDBD',
            'gray_500' => '#9E9E9E',
            'gray_600' => '#757575',
            'gray_700' => '#424242',
            'gray_800' => '#212121',
            'gray_900' => '#000000',

            // Pastel Status Colors
            'pastel_red_bg'     => '#FDECEC',
            'pastel_red_text'   => '#C62828',
            'pastel_green_bg'   => '#EAF7EE',
            'pastel_green_text' => '#2E7D32',
            'pastel_blue_bg'    => '#EAF3FB',
            'pastel_blue_text'  => '#1565C0',
            'pastel_yellow_bg'  => '#FFF8E1',
            'pastel_yellow_text'=> '#B77900',
            'pastel_gray_bg'    => '#F5F5F5',
            'pastel_gray_text'  => '#616161',

            // Global Component Radii
            'button_radius' => '9999px',
            'card_radius'   => '20px',
            'field_radius'  => '9999px',
            'modal_radius'  => '20px',

            // Legacy Fallbacks
            'font_size'       => '15px',
            'secondary_color' => '#424242',
            'accent_color'    => '#8B0000',
            'dark_color'      => '#212121',
            'border_radius'   => '12px'
        );
        return wp_parse_args(get_option('sm_appearance', array()), $default);
    }

    public static function save_appearance($data) {
        update_option('sm_appearance', $data);
    }

    public static function get_notifications() {
        $default = array(
            'email_subject' => 'تنبيه بخصوص سلوك الطالب: {student_name}',
            'email_template' => "تم تسجيل ملاحظة بخصوص الطالب: {student_name}\nنوع المخالفة: {type}\nالحدة: {severity}\nالتفاصيل: {details}\nالإجراء المتخذ: {action_taken}",
            'whatsapp_template' => "تنبيه من المدرسة: تم تسجيل ملاحظة سلوكية بحق الطالب {student_name}. نوع الملاحظة: {type}. تفاصيل: {details}. الإجراء: {action_taken}",
            'internal_template' => "إشعار نظام: تم تسجيل مخالفة {type} للطالب {student_name}. الرجاء مراجعة سجل الطالب."
        );
        return get_option('sm_notification_settings', $default);
    }

    public static function save_notifications($data) {
        update_option('sm_notification_settings', $data);
    }

    public static function get_school_info() {
        $default = array(
            'school_name' => 'خدمات الأنظمة الإلكترونية التعليمية (EESS)',
            'school_principal_name' => 'أحمد علي',
            'school_logo' => '',
            'address' => 'الرياض، المملكة العربية السعودية',
            'email' => 'info@eess.online',
            'phone' => '0123456789',
            'working_schedule' => array(
                'staff' => array('mon', 'tue', 'wed', 'thu', 'fri'),
                'students' => array('mon', 'tue', 'wed', 'thu')
            ),
            'map_link' => '',
            'extra_details' => ''
        );
        return get_option('sm_school_info', $default);
    }

    public static function save_school_info($data) {
        update_option('sm_school_info', $data);
    }

    public static function get_academic_structure() {
        $default = array(
            'academic_year' => '2027/2026',
            'terms_count' => 3,
            'term_dates' => array(
                'term1' => array('start' => '', 'end' => '', 'deadline' => ''),
                'term2' => array('start' => '', 'end' => '', 'deadline' => ''),
                'term3' => array('start' => '', 'end' => '', 'deadline' => '')
            ),
            'grades_count' => 12,
            'active_grades' => array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12),
            'grade_sections' => array(), // Per-grade sections: [grade_num => [count => 5, letters => "أ, ب..."]]
            'sections_count' => 5,
            'section_letters' => "أ, ب, ج, د, هـ",
            'academic_stages' => array(
                array('name' => 'المرحلة الابتدائية', 'start' => 1, 'end' => 4),
                array('name' => 'المرحلة المتوسطة', 'start' => 5, 'end' => 8),
                array('name' => 'المرحلة الثانوية', 'start' => 9, 'end' => 12)
            )
        );
        return wp_parse_args(get_option('sm_academic_structure', array()), $default);
    }

    public static function save_academic_structure($data) {
        update_option('sm_academic_structure', $data);
    }

    /**
     * Standardized Naming for Grades and Sections
     */
    public static function format_grade_name($grade, $section = '', $format = 'full') {
        if (empty($grade)) return '---';

        // Remove "الصف" prefix if it exists in data
        $grade_num = str_replace('الصف ', '', $grade);

        if ($format === 'short') {
            return trim($grade_num . ' ' . $section);
        }

        // Full format: "Grade + Number + Section"
        $output = 'الصف ' . $grade_num;
        if (!empty($section)) {
            $output .= ' شعبة ' . $section;
        }
        return $output;
    }

    public static function get_retention_settings() {
        $default = array(
            'message_retention_days' => 90
        );
        return get_option('sm_retention_settings', $default);
    }

    public static function save_retention_settings($data) {
        update_option('sm_retention_settings', $data);
    }

    public static function record_backup_download() {
        update_option('sm_last_backup_download', current_time('mysql'));
    }

    public static function record_backup_import() {
        update_option('sm_last_backup_import', current_time('mysql'));
    }

    public static function get_last_backup_info() {
        return array(
            'export' => get_option('sm_last_backup_download', 'لم يتم التصدير مسبقاً'),
            'import' => get_option('sm_last_backup_import', 'لم يتم الاستيراد مسبقاً')
        );
    }

    public static function get_subjects() {
        if (class_exists('SM_DB')) {
            $subjs = SM_DB::get_subjects();
            $out = array();
            if (!empty($subjs) && is_array($subjs)) {
                foreach ($subjs as $s) {
                    if (isset($s->id) && isset($s->name)) {
                        $out[$s->id] = $s->name;
                    }
                }
            }
            if (!empty($out)) return $out;
        }
        return array(
            'arabic' => 'اللغة العربية',
            'english' => 'اللغة الإنجليزية',
            'math' => 'الرياضيات',
            'science' => 'العلوم',
            'islamic' => 'التربية الإسلامية',
            'social' => 'الدراسات الاجتماعية',
            'pe' => 'التربية البدنية والرياضية',
            'art' => 'التربية الفنية',
            'music' => 'التربية الموسيقية',
            'computer' => 'الحاسوب والتكنولوجيا'
        );
    }

    public static function get_departments() {
        return array(
            'academic' => 'الشؤون الأكاديمية والتعليمية',
            'hr' => 'إدارة الموارد البشرية (HR)',
            'student_affairs' => 'شؤون الطلاب والانضباط',
            'activities' => 'الأنشطة المدرسية والفعاليات',
            'finance' => 'الشؤون المالية والمحاسبة',
            'services' => 'الخدمات المساندة والنقل',
            'medical' => 'العيادة والرعاية الصحية'
        );
    }

    public static function get_suggested_actions() {
        $default = array(
            'low' => "تنبيه شفوي\nتسجيل ملاحظة\nنصيحة تربوية",
            'medium' => "إنذار خطي\nاستدعاء ولي أمر\nحسم درجات سلوك",
            'high' => "فصل مؤقت\nمجلس انضباط\nتعهد خطي شديد"
        );
        return get_option('sm_suggested_actions', $default);
    }

    public static function save_suggested_actions($actions) {
        update_option('sm_suggested_actions', $actions);
    }

    public static function get_disciplinary_actions() {
        return array(
            1 => 'تنبيه شفوي',
            2 => 'إنذار خطي',
            3 => 'إخطار رسمي',
            4 => 'استدعاء ولي أمر',
            5 => 'تعهد ولي أمر',
            6 => 'حسم من درجات السلوك',
            7 => 'فصل مؤقت',
            8 => 'فصل نهائي'
        );
    }

    public static function get_hierarchical_violations() {
        $default = array(
            1 => array(
                '1.1' => array('name' => 'التأخر عن الطابور الصباحي', 'points' => 1, 'action' => 'تنبيه شفوي'),
                '1.2' => array('name' => 'التأخر عن بداية الحصة الدراسية', 'points' => 1, 'action' => 'تنبيه شفوي'),
                '1.3' => array('name' => 'عدم الالتزام بالزي المدرسي أو الرياضي', 'points' => 2, 'action' => 'تسجيل ملاحظة'),
                '1.4' => array('name' => 'مخالفة قصات الشعر أو المظهر العام', 'points' => 2, 'action' => 'تسجيل ملاحظة'),
                '1.5' => array('name' => 'عدم إحضار الكتب أو الأدوات المدرسية', 'points' => 1, 'action' => 'تنبيه شفوي'),
                '1.6' => array('name' => 'إثارة الفوضى داخل الفصل', 'points' => 2, 'action' => 'نصيحة تربوية'),
                '1.7' => array('name' => 'النوم أثناء الحصة الدراسية', 'points' => 1, 'action' => 'تنبيه شفوي'),
                '1.8' => array('name' => 'تناول الطعام أو العلكة أثناء الحصص', 'points' => 1, 'action' => 'تنبيه شفوي'),
                '1.9' => array('name' => 'سوء استخدام الأجهزة الإلكترونية الشخصية', 'points' => 3, 'action' => 'مصادرة المادة'),
                '1.10' => array('name' => 'إهمال الواجبات المدرسية المتكرر', 'points' => 2, 'action' => 'نصيحة تربوية'),
                '1.11' => array('name' => 'عدم اتباع تعليمات المناوبين في الساحة', 'points' => 2, 'action' => 'تنبيه شفوي'),
            ),
            2 => array(
                '2.1' => array('name' => 'الغياب عن المدرسة بدون عذر مقبول', 'points' => 4, 'action' => 'إنذار خطي واستدعاء ولي أمر'),
                '2.2' => array('name' => 'الدخول أو الخروج من الفصل بدون استئذان', 'points' => 3, 'action' => 'إنذار خطي'),
                '2.3' => array('name' => 'عدم حضور الأنشطة المدرسية الإلزامية', 'points' => 3, 'action' => 'إنذار خطي'),
                '2.4' => array('name' => 'التحريض على الشجار أو التخويف', 'points' => 5, 'action' => 'استدعاء ولي أمر وتعهد'),
                '2.5' => array('name' => 'مخالفة الزي التي تخدش قيم المدرسة', 'points' => 4, 'action' => 'إنذار خطي وتغيير الملابس'),
                '2.6' => array('name' => 'الكتابة على الجدران أو الأثاث المدرسي', 'points' => 5, 'action' => 'إصلاح الضرر وإنذار خطي'),
                '2.7' => array('name' => 'استخدام ألفاظ غير لائقة تجاه الزملاء', 'points' => 4, 'action' => 'اعتذار خطي وإنذار'),
            ),
            3 => array(
                '3.1' => array('name' => 'التنمر أو المضايقات الجسدية/اللفظية', 'points' => 10, 'action' => 'فصل مؤقت ومجلس انضباط'),
                '3.2' => array('name' => 'الغش في الامتحانات أو التزوير الأكاديمي', 'points' => 8, 'action' => 'إلغاء الدرجة وإنذار نهائي'),
                '3.3' => array('name' => 'الهروب من المدرسة أثناء الدوام الرسمي', 'points' => 12, 'action' => 'فصل مؤقت واستدعاء ولي أمر'),
                '3.6' => array('name' => 'العبث بممتلكات المدرسة أو تخريبها', 'points' => 15, 'action' => 'دفع قيمة التلفيات وفصل مؤقت'),
                '3.7' => array('name' => 'تعريض سلامة الطلاب أو الكادر للخطر', 'points' => 15, 'action' => 'مجلس انضباط وإيقاف عن الدراسة'),
                '3.8' => array('name' => 'التطاول اللفظي على أحد أعضاء الكادر', 'points' => 12, 'action' => 'اعتذار رسمي وفصل مؤقت'),
                '3.9' => array('name' => 'حيازة مواد ممنوعة (تبغ أو سجائر)', 'points' => 10, 'action' => 'مصادرة المادة وإنذار نهائي'),
                '3.10' => array('name' => 'التصوير داخل المدرسة بدون إذن', 'points' => 10, 'action' => 'حذف المحتوى ومصادرة الهاتف'),
                '3.11' => array('name' => 'التحريض على الهروب أو التغيب الجماعي', 'points' => 10, 'action' => 'استدعاء ولي أمر وفصل مؤقت'),
            ),
            4 => array(
                '4.1' => array('name' => 'الاستخدام غير القانوني لوسائل التواصل', 'points' => 20, 'action' => 'إيقاف فوري وتصعيد للجهات المختصة'),
                '4.2' => array('name' => 'حيازة أو استخدام الأسلحة أو الأدوات الحادة', 'points' => 25, 'action' => 'فصل نهائي وتصعيد أمني'),
                '4.3' => array('name' => 'السلوك الأخلاقي المشين أو التحرش', 'points' => 25, 'action' => 'فصل نهائي وتحقيق رسمي'),
                '4.4' => array('name' => 'السرقة أو الاستيلاء على ممتلكات الغير', 'points' => 20, 'action' => 'إعادة المسروقات وفصل نهائي'),
                '4.5' => array('name' => 'التخريب العمدي للمرافق الحيوية بالمدرسة', 'points' => 20, 'action' => 'تحميل التكاليف وفصل نهائي'),
                '4.6' => array('name' => 'الاعتداء الجسدي العنيف على الطلاب أو الكادر', 'points' => 25, 'action' => 'إيقاف عن الدراسة وتصعيد قانوني'),
                '4.7' => array('name' => 'ترويج أو تعاطي المخدرات والممنوعات', 'points' => 30, 'action' => 'فصل نهائي وتسليم للشرطة'),
                '4.10' => array('name' => 'الإساءة للرموز الوطنية أو الدينية', 'points' => 30, 'action' => 'فصل نهائي وتصعيد للجهات العليا'),
                '4.11' => array('name' => 'حيازة مواد مخلة بالآداب العامة', 'points' => 20, 'action' => 'فصل نهائي وتحقيق تربوي'),
                '4.12' => array('name' => 'انتحال صفة الغير في معاملات رسمية', 'points' => 15, 'action' => 'إيقاف عن الدراسة وتحقيق'),
                '4.13' => array('name' => 'التهديد المباشر بالقتل أو الأذى الجسيم', 'points' => 30, 'action' => 'فصل فوري وإبلاغ السلطات'),
                '4.14' => array('name' => 'إشعال الحرائق عمدًا داخل حرم المدرسة', 'points' => 30, 'action' => 'فصل نهائي وتحمل التبعات القانونية'),
            )
        );
        return get_option('sm_hierarchical_violations', $default);
    }

    public static function save_hierarchical_violations($data) {
        update_option('sm_hierarchical_violations', $data);
    }

    /**
     * Fetch Regulation Dynamic Info by Code
     */
    public static function get_regulation_by_code($code) {
        if (empty($code)) return false;
        $h_violations = self::get_hierarchical_violations();
        foreach ($h_violations as $level => $items) {
            if (isset($items[$code])) {
                return $items[$code];
            }
        }
        return false;
    }

    public static function get_class_security_codes() {
        $codes = get_option('sm_class_security_codes', array());
        return $codes;
    }

    public static function get_class_security_code($grade, $section) {
        $codes = self::get_class_security_codes();
        $key = $grade . '|' . $section;

        if (!isset($codes[$key])) {
            return self::reset_class_security_code($grade, $section);
        }

        return $codes[$key];
    }

    public static function reset_class_security_code($grade, $section) {
        $codes = self::get_class_security_codes();
        $key = $grade . '|' . $section;
        $new_code = str_pad(rand(0, 9999), 4, '0', STR_PAD_LEFT);
        $codes[$key] = $new_code;
        update_option('sm_class_security_codes', $codes);
        return $new_code;
    }

    public static function get_sections_from_db() {
        global $wpdb;
        $results = $wpdb->get_results("SELECT DISTINCT class_name, section FROM {$wpdb->prefix}sm_students WHERE section != '' ORDER BY class_name ASC, section ASC");

        $structure = array();
        foreach ($results as $row) {
            $grade_num = (int)str_replace('الصف ', '', $row->class_name);
            if (!isset($structure[$grade_num])) {
                $structure[$grade_num] = array();
            }
            if (!in_array($row->section, $structure[$grade_num])) {
                $structure[$grade_num][] = $row->section;
            }
        }

        // Sort sections alphabetically for each grade
        foreach ($structure as $grade => $sections) {
            sort($structure[$grade]);
        }

        return $structure;
    }

    public static function get_timetable_settings() {
        $default = array(
            'periods' => 8,
            'days' => array('sun', 'mon', 'tue', 'wed', 'thu')
        );
        return get_option('sm_timetable_settings', $default);
    }

    public static function save_timetable_settings($data) {
        update_option('sm_timetable_settings', $data);
    }

    public static function get_system_modules() {
        $user = wp_get_current_user();
        $roles = (array) $user->roles;

        // Educational staff role check: Teacher, Supervisor, Head of Department, Activities Supervisor
        $is_educational_role = in_array('sm_teacher', $roles) || in_array('sm_supervisor', $roles) || in_array('sm_hod', $roles) || in_array('sm_activities_supervisor', $roles);

        $modules = array(
            'summary' => array(
                'label' => 'لوحة المعلومات',
                'dashicon' => 'dashicons-dashboard',
                'tab' => 'summary',
                'default' => array(
                    'sm_system_admin' => true,
                    'sm_principal' => true,
                    'sm_supervisor' => true,
                    'sm_coordinator' => true,
                    'sm_hod' => true,
                    'sm_teacher' => true,
                    'sm_student' => true,
                    'sm_parent' => true,
                    'sm_discipline_supervisor' => true,
                    'sm_activities_supervisor' => true,
                    'sm_transportation_supervisor' => true,
                    'sm_bus_supervisor' => true,
                    'sm_hr' => true,
                )
            ),
            'lesson-plans' => array(
                'label' => 'تحضير الدروس',
                'dashicon' => 'dashicons-welcome-write-blog',
                'tab' => 'lesson-plans',
                'default' => array(
                    'sm_system_admin' => true,
                    'sm_principal' => true,
                    'sm_supervisor' => true,
                    'sm_coordinator' => true,
                    'sm_teacher' => true,
                    'sm_student' => false,
                    'sm_parent' => false,
                    'sm_discipline_supervisor' => false,
                    'sm_activities_supervisor' => true,
                    'sm_transportation_supervisor' => false,
                    'sm_bus_supervisor' => false,
                    'sm_hr' => false,
                )
            ),
            'term-plans' => array(
                'label' => 'الخطط الفصلية والسنوية',
                'dashicon' => 'dashicons-calendar-alt',
                'tab' => 'term-plans',
                'default' => array(
                    'sm_system_admin' => true,
                    'sm_principal' => true,
                    'sm_supervisor' => true,
                    'sm_coordinator' => true,
                    'sm_teacher' => true,
                    'sm_student' => false,
                    'sm_parent' => false,
                    'sm_discipline_supervisor' => true,
                    'sm_activities_supervisor' => true,
                    'sm_transportation_supervisor' => false,
                    'sm_bus_supervisor' => false,
                    'sm_hr' => false,
                )
            ),
            'assets' => array(
                'label' => 'إدارة العهد والمعدات',
                'dashicon' => 'dashicons-store',
                'tab' => 'assets',
                'default' => array(
                    'sm_system_admin' => true,
                    'sm_principal' => true,
                    'sm_supervisor' => true,
                    'sm_coordinator' => true,
                    'sm_hod' => true,
                    'sm_teacher' => true,
                    'sm_student' => false,
                    'sm_parent' => false,
                    'sm_discipline_supervisor' => false,
                    'sm_activities_supervisor' => true,
                    'sm_transportation_supervisor' => true,
                    'sm_bus_supervisor' => false,
                    'sm_hr' => false,
                )
            ),
            'assignments' => array(
                'label' => 'الواجبات المدرسية',
                'dashicon' => 'dashicons-portfolio',
                'tab' => 'assignments',
                'default' => array(
                    'sm_system_admin' => true,
                    'sm_principal' => false,
                    'sm_supervisor' => false,
                    'sm_coordinator' => false,
                    'sm_teacher' => true,
                    'sm_student' => true,
                    'sm_parent' => false,
                    'sm_discipline_supervisor' => false,
                    'sm_activities_supervisor' => false,
                    'sm_transportation_supervisor' => false,
                    'sm_bus_supervisor' => false,
                    'sm_hr' => false,
                )
            ),
            'attendance' => array(
                'label' => 'سجل الحضور والغياب',
                'dashicon' => 'dashicons-calendar-alt',
                'tab' => 'attendance',
                'default' => array(
                    'sm_system_admin' => true,
                    'sm_principal' => true,
                    'sm_supervisor' => true,
                    'sm_coordinator' => false,
                    'sm_teacher' => true,
                    'sm_student' => false,
                    'sm_parent' => false,
                    'sm_discipline_supervisor' => false,
                    'sm_activities_supervisor' => false,
                    'sm_transportation_supervisor' => false,
                    'sm_bus_supervisor' => false,
                    'sm_hr' => false,
                )
            ),
            'students' => array(
                'label' => 'شؤون الطلاب',
                'dashicon' => 'dashicons-groups',
                'tab' => 'students',
                'default' => array(
                    'sm_system_admin' => true,
                    'sm_principal' => true,
                    'sm_supervisor' => true,
                    'sm_coordinator' => false,
                    'sm_hod' => true,
                    'sm_teacher' => true,
                    'sm_student' => false,
                    'sm_parent' => false,
                    'sm_discipline_supervisor' => true,
                    'sm_activities_supervisor' => false,
                    'sm_transportation_supervisor' => false,
                    'sm_bus_supervisor' => false,
                    'sm_hr' => false,
                )
            ),
            'stats' => array(
                'label' => 'سجل سلوك الطلاب',
                'dashicon' => 'dashicons-list-view',
                'tab' => 'stats',
                'default' => array(
                    'sm_system_admin' => true,
                    'sm_principal' => true,
                    'sm_supervisor' => true,
                    'sm_coordinator' => false,
                    'sm_hod' => true,
                    'sm_teacher' => true,
                    'sm_student' => true,
                    'sm_parent' => true,
                    'sm_discipline_supervisor' => true,
                    'sm_activities_supervisor' => true,
                    'sm_transportation_supervisor' => true,
                    'sm_bus_supervisor' => true,
                    'sm_hr' => false,
                )
            ),
            'grades' => array(
                'label' => 'إدارة الدرجات والنتائج',
                'dashicon' => 'dashicons-welcome-learn-more',
                'tab' => 'grades',
                'default' => array(
                    'sm_system_admin' => true,
                    'sm_principal' => true,
                    'sm_supervisor' => true,
                    'sm_coordinator' => true,
                    'sm_hod' => true,
                    'sm_teacher' => true,
                    'sm_student' => true,
                    'sm_parent' => true,
                    'sm_discipline_supervisor' => false,
                    'sm_activities_supervisor' => false,
                    'sm_transportation_supervisor' => false,
                    'sm_bus_supervisor' => false,
                    'sm_hr' => false,
                )
            ),
            'teachers' => array(
                'label' => 'إدارة مستخدمي النظام',
                'dashicon' => 'dashicons-admin-users',
                'tab' => 'teachers',
                'default' => array(
                    'sm_system_admin' => true,
                    'sm_principal' => true,
                    'sm_supervisor' => true,
                    'sm_coordinator' => false,
                    'sm_hod' => false,
                    'sm_teacher' => true,
                    'sm_student' => false,
                    'sm_parent' => false,
                    'sm_discipline_supervisor' => false,
                    'sm_activities_supervisor' => false,
                    'sm_transportation_supervisor' => false,
                    'sm_bus_supervisor' => false,
                    'sm_hr' => false,
                )
            ),
            'parents' => array(
                'label' => 'إدارة أولياء الأمور',
                'dashicon' => 'dashicons-admin-users',
                'tab' => 'parents',
                'default' => array(
                    'sm_system_admin' => true,
                    'sm_principal' => true,
                    'sm_supervisor' => true,
                    'sm_coordinator' => false,
                    'sm_hod' => false,
                    'sm_teacher' => true,
                    'sm_student' => false,
                    'sm_parent' => false,
                    'sm_discipline_supervisor' => false,
                    'sm_activities_supervisor' => false,
                    'sm_transportation_supervisor' => false,
                    'sm_bus_supervisor' => false,
                    'sm_hr' => false,
                )
            ),
            'hr-evaluation' => array(
                'label' => 'تقييم الموظفين',
                'dashicon' => 'dashicons-awards',
                'tab' => 'hr-evaluation',
                'default' => array(
                    'sm_system_admin' => true,
                    'sm_principal' => true,
                    'sm_supervisor' => true,
                    'sm_coordinator' => true,
                    'sm_teacher' => false,
                    'sm_student' => false,
                    'sm_parent' => false,
                    'sm_discipline_supervisor' => false,
                    'sm_activities_supervisor' => false,
                    'sm_transportation_supervisor' => false,
                    'sm_bus_supervisor' => false,
                    'sm_hr' => true,
                )
            ),
            'hr-management' => array(
                'label' => 'إدارة الموارد البشرية',
                'dashicon' => 'dashicons-id-alt',
                'tab' => 'hr-management',
                'default' => array(
                    'sm_system_admin' => true,
                    'sm_principal' => false,
                    'sm_supervisor' => false,
                    'sm_coordinator' => false,
                    'sm_teacher' => false,
                    'sm_student' => false,
                    'sm_parent' => false,
                    'sm_discipline_supervisor' => false,
                    'sm_activities_supervisor' => false,
                    'sm_transportation_supervisor' => false,
                    'sm_bus_supervisor' => false,
                    'sm_hr' => true,
                )
            ),
            'documents' => array(
                'label' => 'مكتبة الوثائق والتقارير',
                'dashicon' => 'dashicons-media-document',
                'tab' => 'documents',
                'default' => array(
                    'sm_system_admin' => true,
                    'sm_principal' => true,
                    'sm_supervisor' => true,
                    'sm_coordinator' => true,
                    'sm_teacher' => true,
                    'sm_student' => false,
                    'sm_parent' => false,
                    'sm_discipline_supervisor' => true,
                    'sm_activities_supervisor' => true,
                    'sm_transportation_supervisor' => true,
                    'sm_bus_supervisor' => true,
                    'sm_hr' => false,
                )
            ),
            'clinic' => array(
                'label' => 'العيادة المدرسية',
                'dashicon' => 'dashicons-heart',
                'tab' => 'clinic',
                'default' => array(
                    'sm_system_admin' => true,
                    'sm_principal' => true,
                    'sm_supervisor' => true,
                    'sm_coordinator' => false,
                    'sm_teacher' => false,
                    'sm_student' => false,
                    'sm_parent' => false,
                    'sm_discipline_supervisor' => false,
                    'sm_activities_supervisor' => false,
                    'sm_transportation_supervisor' => false,
                    'sm_bus_supervisor' => false,
                    'sm_hr' => false,
                )
            ),
            'school-structure' => array(
                'label' => 'الهيكل التنظيمي والاداري',
                'dashicon' => 'dashicons-category',
                'tab' => 'school-structure',
                'default' => array(
                    'sm_system_admin' => true,
                    'sm_principal' => false,
                    'sm_supervisor' => false,
                    'sm_coordinator' => false,
                    'sm_teacher' => false,
                    'sm_student' => false,
                    'sm_parent' => false,
                    'sm_discipline_supervisor' => false,
                    'sm_activities_supervisor' => false,
                    'sm_transportation_supervisor' => false,
                    'sm_bus_supervisor' => false,
                    'sm_hr' => false,
                )
            ),
            'global-settings' => array(
                'label' => 'إعدادات النظام',
                'dashicon' => 'dashicons-admin-generic',
                'tab' => 'global-settings',
                'default' => array(
                    'sm_system_admin' => true,
                    'sm_principal' => false,
                    'sm_supervisor' => false,
                    'sm_coordinator' => false,
                    'sm_teacher' => false,
                    'sm_student' => false,
                    'sm_parent' => false,
                    'sm_discipline_supervisor' => false,
                    'sm_activities_supervisor' => false,
                    'sm_transportation_supervisor' => false,
                    'sm_bus_supervisor' => false,
                    'sm_hr' => false,
                )
            ),
            'employee-profile' => array(
                'label' => 'الملف الوظيفي',
                'dashicon' => 'dashicons-businessman',
                'tab' => 'employee-profile',
                'default' => array(
                    'sm_system_admin' => true,
                    'sm_principal' => true,
                    'sm_supervisor' => true,
                    'sm_coordinator' => true,
                    'sm_teacher' => true,
                    'sm_student' => false,
                    'sm_parent' => false,
                    'sm_discipline_supervisor' => true,
                    'sm_activities_supervisor' => true,
                    'sm_transportation_supervisor' => true,
                    'sm_bus_supervisor' => true,
                    'sm_hr' => true,
                )
            ),
        );

        return $modules;
    }

    public static function user_has_module_capability($key, $user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        if (!$user_id) {
            return false;
        }
        $user = get_userdata($user_id);
        if (!$user) {
            return false;
        }
        $roles = (array) $user->roles;
        $is_admin = in_array('administrator', $roles) || user_can($user_id, 'manage_options');
        $is_sys_admin = in_array('sm_system_admin', $roles);
        $is_principal = in_array('sm_principal', $roles);
        $is_supervisor = in_array('sm_supervisor', $roles);
        $is_coordinator = in_array('sm_coordinator', $roles);
        $is_teacher = in_array('sm_teacher', $roles);
        $is_student = in_array('sm_student', $roles);
        $is_parent = in_array('sm_parent', $roles);
        $is_clinic = in_array('sm_clinic', $roles);

        if ($is_admin || $is_sys_admin) {
            return true;
        }

        if ($key === 'school-structure' || $key === 'global-settings') {
            return false;
        }

        // The "Customize Sidebar Section Visibility by Role" settings are the central source of truth.
        return self::is_section_visible($key, $user_id);
    }

    public static function is_section_visible($section, $user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        if (!$user_id) {
            return false;
        }
        $user = get_userdata($user_id);
        if (!$user) {
            return false;
        }
        $roles = (array) $user->roles;
        if (in_array('administrator', $roles)) {
            return true;
        }

        $role = !empty($roles) ? $roles[0] : '';
        $visibility = self::get_sidebar_visibility();

        if (isset($visibility[$role][$section])) {
            return (bool) $visibility[$role][$section];
        }

        // Fallback to default
        $modules = self::get_system_modules();
        if (isset($modules[$section]['default'][$role])) {
            return (bool) $modules[$section]['default'][$role];
        }

        return false;
    }

    public static function is_ajax_action_allowed($action, $user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        // Strip prefix if any
        $clean_action = str_replace(array('nopriv_'), '', $action);

        // Public/nopriv actions allowed for non-logged-in users
        $public_actions = array(
            'sm_get_students_attendance_ajax',
            'sm_save_attendance_ajax',
            'sm_save_attendance_batch_ajax',
            'eess_forgot_verify_identity',
            'eess_forgot_set_password',
            'eess_register_submit',
            'sm_verify_employee_id',
            'sm_submit_mobile_lesson',
            'sm_get_pending_announcements'
        );

        if (!$user_id) {
            return in_array($clean_action, $public_actions);
        }

        $user = get_userdata($user_id);
        if (!$user) {
            return false;
        }
        $roles = (array) $user->roles;
        if (in_array('administrator', $roles) || in_array('sm_system_admin', $roles)) {
            return true;
        }

        // Actions allowed for all authenticated users
        $universal_authenticated_actions = array(
            'sm_get_pending_announcements',
            'sm_mark_announcement_viewed',
            'sm_mark_announcement_closed',
            'eess_submit_support_request',
            'eess_update_support_status',
            'eess_delete_support_request',
            'sm_update_profile_ajax',
            'sm_get_counts_ajax',
            'sm_refresh_dashboard'
        );
        if (in_array($clean_action, $universal_authenticated_actions)) {
            return true;
        }

        // Map AJAX actions to section keys
        $action_map = array(
            // Students
            'sm_get_student' => 'students',
            'sm_search_students' => 'students',
            'sm_get_student_intelligence' => 'students',
            'sm_add_student_ajax' => 'students',
            'sm_update_student_ajax' => 'students',
            'sm_delete_student_ajax' => 'students',
            'sm_bulk_delete_students_ajax' => 'students',
            'sm_upload_import_csv' => 'students',
            'sm_process_import_chunk' => 'students',
            'sm_export_students_csv' => 'students',
            'sm_download_student_import_template' => 'students',
            'sm_print_student_full_report' => 'students',
            'sm_update_student_photo' => 'students',

            // Behavior / Stats
            'sm_filter_violations' => 'stats',
            'sm_mark_contacted' => 'stats',
            'sm_export_violations_csv' => 'stats',
            'sm_save_record_ajax' => 'stats',
            'sm_update_record_status' => 'stats',
            'sm_delete_record_ajax' => 'stats',
            'sm_submit_behavior_referral' => 'stats',

            // Teachers / Users
            'sm_add_user_ajax' => 'teachers',
            'sm_update_generic_user_ajax' => 'teachers',
            'sm_add_teacher_ajax' => 'teachers',
            'sm_update_teacher_ajax' => 'teachers',
            'sm_bulk_delete_users_ajax' => 'teachers',
            'eess_approve_user' => 'teachers',
            'eess_reject_user' => 'teachers',
            'eess_save_user_notes' => 'teachers',
            'sm_export_users_csv' => 'teachers',
            'eess_check_user_uniqueness' => 'teachers',
            'eess_get_user_unified' => 'teachers',
            'eess_save_user_unified' => 'teachers',
            'eess_get_user_assignments' => 'teachers',

            // Parents
            'sm_add_parent_ajax' => 'parents',
            'eess_send_quick_parent_note' => 'parents',

            // Grades
            'sm_save_grade_ajax' => 'grades',
            'eess_import_grades_ajax' => 'grades',
            'sm_get_student_grades_ajax' => 'grades',
            'sm_delete_grade_ajax' => 'grades',
            'sm_add_subject' => 'grades',
            'sm_delete_subject' => 'grades',
            'sm_get_subjects' => 'grades',
            'sm_save_class_grades' => 'grades',
            'sm_export_grades_csv' => 'grades',
            'sm_import_grades_csv' => 'grades',

            // Attendance
            'sm_get_students_attendance_ajax' => 'attendance',
            'sm_save_attendance_ajax' => 'attendance',
            'sm_save_attendance_batch_ajax' => 'attendance',
            'sm_reset_class_code_ajax' => 'attendance',
            'sm_toggle_attendance_status_ajax' => 'attendance',

            // HR Management
            'eess_hr_add_employee' => 'hr-management',
            'eess_bulk_import_employees_ajax' => 'hr-management',
            'sm_verify_employee_id' => 'hr-management',

            // Lesson plans
            'sm_download_plans_zip' => 'lesson-plans',
            'eess_quick_approve_prep' => 'lesson-plans',
            'eess_bulk_lesson_action' => 'lesson-plans',
            'sm_submit_mobile_lesson' => 'lesson-plans',
            'sm_get_educational_suggestions' => 'lesson-plans',
            'sm_save_educational_input' => 'lesson-plans',

            // Term plans
            'sm_save_term_plan' => 'term-plans',
            'sm_review_term_plan' => 'term-plans',
            'sm_delete_term_plan' => 'term-plans',

            // Assignments
            'sm_add_assignment_ajax' => 'assignments',
            'sm_approve_plan_ajax' => 'assignments',

            // Documents
            'sm_add_document_ajax' => 'documents',
            'sm_update_document_ajax' => 'documents',
            'sm_delete_document_ajax' => 'documents',
            'sm_print' => 'documents',

            // Clinic
            'sm_add_clinic_referral' => 'clinic',
            'sm_confirm_clinic_arrival' => 'clinic',
            'sm_update_clinic_record' => 'clinic',
            'sm_get_clinic_reports' => 'clinic',

            // Global Settings
            'sm_save_regulation_settings_ajax' => 'global-settings',
            'sm_save_hierarchical_violations_ajax' => 'global-settings',
            'sm_delete_all_logs_ajax' => 'global-settings',
            'sm_delete_log_ajax' => 'global-settings',
            'sm_rollback_log_ajax' => 'global-settings',
            'sm_initialize_system_ajax' => 'global-settings',
            'sm_bulk_delete_ajax' => 'global-settings',
            'sm_refresh_system_cache_ajax' => 'global-settings',
            'sm_create_system_announcement' => 'global-settings',
            'sm_disable_system_announcement' => 'global-settings',
            'sm_delete_system_announcement' => 'global-settings',
            'sm_reset_user_announcement' => 'global-settings',
            'sm_delete_user_announcement_log' => 'global-settings',
        );

        if (isset($action_map[$clean_action])) {
            $section = $action_map[$clean_action];
            return self::is_section_visible($section, $user_id) && self::user_has_module_capability($section, $user_id);
        }

        return true; // Unmapped action, allow by default
    }

    public static function get_access_restricted_html() {
        ob_start();
        ?>
        <div class="sm-container" style="padding:60px 20px; text-align:center; max-width:550px; margin: 0 auto; font-family: 'Cairo', sans-serif;" dir="rtl">
            <div style="background:#ffffff; padding:45px 30px; border-radius:12px; border:1px solid #cbd5e1; box-shadow:0 10px 15px -3px rgba(0,0,0,0.05);">
                <div style="font-size:75px; color:#ea580c; line-height:1; margin-bottom:20px;">🔒</div>
                <h2 style="margin:0 0 10px 0; font-weight:800; color:#0f172a; font-size:1.6rem;">عفواً، الدخول غير مصرح به</h2>
                <p style="margin:0 0 30px 0; font-size:14px; color:#64748b; line-height:1.7;">يرجى العلم بأنك لا تملك الصلاحيات الكافية للوصول إلى هذا القسم. إذا كنت تعتقد أن هذا خطأ، يرجى التواصل مع إدارة النظام.</p>
                <a href="<?php echo home_url('/sm-admin'); ?>" class="sm-btn" style="width:100%; display:inline-flex; align-items:center; justify-content:center; text-decoration:none; font-weight:700; color:white !important; background-color:#000000 !important; border:1px solid #000000;">العودة للوحة الإدارة الرئيسية</a>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public static function get_sidebar_visibility() {
        $modules = self::get_system_modules();
        $roles = array(
            'sm_system_admin',
            'sm_principal',
            'sm_supervisor',
            'sm_coordinator',
            'sm_hod',
            'sm_teacher',
            'sm_student',
            'sm_parent',
            'sm_discipline_supervisor',
            'sm_activities_supervisor',
            'sm_transportation_supervisor',
            'sm_bus_supervisor',
            'sm_hr'
        );

        $default = array();
        foreach ($roles as $role) {
            $default[$role] = array();
            foreach ($modules as $sec_key => $sec_data) {
                $default[$role][$sec_key] = $sec_data['default'][$role] ?? false;
            }
        }

        $saved = get_option('sm_sidebar_visibility');
        if ($saved === false) {
            return $default;
        }

        // Merge saved settings role by role to ensure persistence
        foreach ($default as $role => $sections) {
            if (isset($saved[$role])) {
                $default[$role] = array_merge($sections, $saved[$role]);
            }
        }
        return $default;
    }

    public static function save_sidebar_visibility($data) {
        update_option('sm_sidebar_visibility', $data);
    }

    public static function change_user_role($user_id, $new_role, $additional_data = array()) {
        $user = new WP_User($user_id);
        if (!$user || empty($user->ID)) {
            return false;
        }

        // Permanent protections for the root System Administrator (info@eess.online)
        if ($user->user_email === 'info@eess.online') {
            return false;
        }

        // Restrict System Administrator role to info@eess.online only
        if ($new_role === 'sm_system_admin' || $new_role === 'administrator') {
            return false;
        }

        // 1. Set authoritative WordPress role (which strips old roles)
        $user->set_role($new_role);

        // 2. Map role back to the Arabic label
        $role_map = array(
            'administrator' => 'الإدارة المركزية (المطور)',
            'sm_system_admin' => 'مدير النظام',
            'sm_principal' => 'مدير المدرسة',
            'sm_supervisor' => 'مشرف تربوي',
            'sm_coordinator' => 'منسق مادة',
            'sm_hod' => 'رئيس قسم',
            'sm_teacher' => 'معلم',
            'sm_student' => 'طالب',
            'sm_parent' => 'ولي أمر',
            'sm_discipline_supervisor' => 'مشرف سلوك / انضباط',
            'sm_activities_supervisor' => 'مشرف أنشطة',
            'sm_transportation_supervisor' => 'مشرف نقل ومواصلات',
            'sm_bus_supervisor' => 'مشرف حافلة',
            'sm_clinic' => 'العيادة المدرسية',
            'sm_hr' => 'الموارد البشرية (HR)'
        );
        $role_label = $role_map[$new_role] ?? $new_role;

        // 3. Synchronize metadata and legacy role/job title fields
        update_user_meta($user_id, 'sm_job_title', $role_label);
        update_user_meta($user_id, 'sm_job_title_ar', $role_label);

        // Update user meta roles or statuses
        if ($new_role === 'sm_student') {
            update_user_meta($user_id, 'sm_account_status', 'active');
        } elseif ($new_role === 'sm_parent') {
            update_user_meta($user_id, 'sm_account_status', 'active');
        }

        // Handle subject assignment
        if (isset($additional_data['specialization'])) {
            update_user_meta($user_id, 'sm_specialization', sanitize_text_field($additional_data['specialization']));
        }

        // 4. In Employee Profile and Human Resources, synchronize immediately.
        $hr_status = get_user_meta($user_id, 'eess_hr_employment_status', true);
        if (empty($hr_status)) {
            update_user_meta($user_id, 'eess_hr_employment_status', 'active');
        }

        // Add a timeline event to the Employee Profile
        $timeline = get_user_meta($user_id, 'eess_hr_activity_timeline', true) ?: array();
        if (!is_array($timeline)) {
            $timeline = array();
        }
        $actor_user = wp_get_current_user();
        $actor = ($actor_user && $actor_user->display_name) ? $actor_user->display_name : 'النظام';
        array_unshift($timeline, array(
            'date' => current_time('Y-m-d H:i:s'),
            'action' => 'تغيير الرتبة / المسمى الوظيفي',
            'actor' => $actor,
            'details' => "تم تغيير رتبة المستخدم إلى: $role_label"
        ));
        update_user_meta($user_id, 'eess_hr_activity_timeline', $timeline);

        // 5. Invalidate caches immediately so that the new role, permissions, and sidebar appear instantly
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE '_transient_sm_%' OR option_name LIKE '_transient_timeout_sm_%'");
        $wpdb->query("DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE '_transient_eess_%' OR option_name LIKE '_transient_timeout_eess_%'");
        wp_cache_flush();
        clean_user_cache($user_id);

        return true;
    }

    /**
     * Enforce UAE Phone Format (+971)
     */
    public static function format_uae_phone($phone) {
        if (empty($phone)) return false;

        // 1. Strip all non-digits
        $digits = preg_replace('/[^0-9]/', '', $phone);

        // 2. Handle 050... -> 97150... (0 + 9 digits)
        if (strlen($digits) == 10 && strpos($digits, '0') === 0) {
            $digits = '971' . substr($digits, 1);
        }
        // 3. Handle 50... -> 97150... (9 digits)
        elseif (strlen($digits) == 9) {
            $digits = '971' . $digits;
        }
        // 4. Handle 97150... -> 97150... (12 digits)
        // Already handled if it was 12 digits.

        // Final Validation: Exactly 12 digits starting with 971
        if (strlen($digits) == 12 && strpos($digits, '971') === 0) {
            return $digits;
        }

        return false;
    }

    public static function get_subject_lesson_fields($subject) {
        $sub = mb_strtolower(trim($subject));

        if (strpos($sub, 'رياضية') !== false || strpos($sub, 'بدنية') !== false || strpos($sub, 'pe') !== false || strpos($sub, 'physical') !== false) {
            return array(
                'label1' => 'الإعداد البدني واللياقة العامة والخاصة (Physical Preparation)',
                'placeholder1' => 'تمارين الإحماء واكتساب عناصر اللياقة العامة والخاصة بالمهارة...',
                'label2' => 'الإعداد المهاري والخطوات الفنية (Skill Preparation)',
                'placeholder2' => 'شرح وعرض الخطوات التعليمية والفنية للمهارة الحركية...',
                'label3' => 'النشاط التطبيقي والتقسيمات (Main Practical Activity)',
                'placeholder3' => 'الألعاب والتقسيمات والتطبيقات والمنافسات الجماعية...',
                'label4' => 'الخاتمة والتهدئة وتجميع الطلاب (Cool-down & Closing)',
                'placeholder4' => 'تمارين التهدئة وتجميع الأدوات والتقييم الحركي الختامي...'
            );
        }

        if (strpos($sub, 'رياضيات') !== false || strpos($sub, 'math') !== false) {
            return array(
                'label1' => 'الأهداف والبراهين الرياضية والمفاهيم (Objectives & Concepts)',
                'placeholder1' => 'صياغة المفاهيم الرياضية، النظريات، والقوانين المقرر إثباتها...',
                'label2' => 'التهيئة والتمهيد ومراجعة المكتسبات (Warm-up & Prior Knowledge)',
                'placeholder2' => 'مراجعة المهارات السابقة والتمهيد للمسألة النموذجية...',
                'label3' => 'استراتيجيات الحل والأنشطة التطبيقية (Problem Solving & Activities)',
                'placeholder3' => 'خطوات حل المسائل والتطبيق التدريجي على البراهين والتمارين...',
                'label4' => 'التقويم الذاتي والتحقق من فهم المسائل (Assessment & Proof Check)',
                'placeholder4' => 'تمارين قياس مستوى الاستيعاب والتأكد من صحة الحلول...'
            );
        }

        if (strpos($sub, 'فيزياء') !== false || strpos($sub, 'كيمياء') !== false || strpos($sub, 'أحياء') !== false || strpos($sub, 'علوم') !== false || strpos($sub, 'physics') !== false || strpos($sub, 'chemistry') !== false || strpos($sub, 'biology') !== false || strpos($sub, 'science') !== false) {
            return array(
                'label1' => 'الأهداف والتجارب والتفسيرات العلمية (Scientific Objectives & Concepts)',
                'placeholder1' => 'الأهداف العلمية والتجارب المعملية والمفاهيم العلمية المستهدفة...',
                'label2' => 'التهيئة الاستكشافية والمشاهدة (Warm-up & Observation)',
                'placeholder2' => 'طرح التساؤل العلمي أو الظاهرة الاستكشافية قبل التجربة...',
                'label3' => 'التجربة العملية واستراتيجية الاستقصاء (Lab Experiment & Inquiry)',
                'placeholder3' => 'خطوات تنفيذ التجربة، السلامة المعملية، وجمع البيانات والتطبيقات...',
                'label4' => 'تحليل النتائج والتقويم العلمي (Data Analysis & Evaluation)',
                'placeholder4' => 'استنتاج نتائج التجربة والإجابة على أسئلة التقييم المعملي...'
            );
        }

        if (strpos($sub, 'إسلامية') !== false || strpos($sub, 'قرآن') !== false || strpos($sub, 'تربية إسلامية') !== false || strpos($sub, 'islamic') !== false) {
            return array(
                'label1' => 'الأهداف السلوكية والأحكام الشرعية والتلاوة (Objectives & Recitation)',
                'placeholder1' => 'الآيات والمفاهيم والأحكام الشرعية والقيم التربوية المستفادة...',
                'label2' => 'التمهيد وربط الآيات بالواقع (Warm-up & Reflection)',
                'placeholder2' => 'نشاط تمهيدي لربط الدرس بالقيم السلوكية والحياة اليومية...',
                'label3' => 'الشرح والتفسير والأنشطة الإيمانية (Tafseer & Learning Activities)',
                'placeholder3' => 'شرح المعاني، تطبيق قواعد التلاوة، والاستراتيجيات التفاعلية...',
                'label4' => 'التقويم السلوكي والتطبيق الحياتي (Behavioral Assessment & Closing)',
                'placeholder4' => 'قياس فهم الأحكام والآثار السلوكية المكتسبة...'
            );
        }

        return array(
            'label1' => 'الأهداف السلوكية والتعليمية (Objectives)',
            'placeholder1' => 'أدخل الأهداف السلوكية المحددة والواضحة للدرس...',
            'label2' => 'التمهيد والتهيئة الحافزة (Warm-up)',
            'placeholder2' => 'نشاط تمهيدي لجذب انتباه الطلاب للمفهوم الجديد...',
            'label3' => 'الاستراتيجيات والأنشطة والخطوات التعليمية (Learning Activities)',
            'placeholder3' => 'شرح طريقة عرض المفهوم والوسائل والأنشطة المتبعة...',
            'label4' => 'التقويم الصفي وأدوات القياس (Evaluation & Assessment)',
            'placeholder4' => 'أسئلة وأدوات تقييم فهم واستيعاب الطلاب خلال الحصة...'
        );
    }
}
