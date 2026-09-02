<?php
if (!defined('ABSPATH')) exit;

class EESS_Org_Helper {

    /**
     * Seeds initial institutions and schools if none exist
     */
    public static function seed_default_structure() {
        global $wpdb;

        $inst_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}eess_institutions");
        if ($inst_count == 0) {
            // Seed default institution
            $wpdb->insert("{$wpdb->prefix}eess_institutions", array(
                'name' => 'مؤسسة الإمارات للتعليم المدرسي',
                'status' => 'active'
            ));
            $inst_id = $wpdb->insert_id;

            // Seed default schools
            $schools = array(
                'مدرسة الأمل للتعليم الأساسي والثانوي',
                'مدرسة النخبة النموذجية',
                'مدرسة الريادة للتعليم الثانوي'
            );

            foreach ($schools as $school_name) {
                $wpdb->insert("{$wpdb->prefix}eess_schools", array(
                    'institution_id' => $inst_id,
                    'name' => $school_name,
                    'status' => 'active'
                ));
                $school_id = $wpdb->insert_id;

                // Seed default Grade levels & Classes for each school
                for ($g = 1; $g <= 12; $g++) {
                    $wpdb->insert("{$wpdb->prefix}eess_grades", array(
                        'school_id' => $school_id,
                        'name' => 'الصف ' . $g
                    ));
                    $grade_id = $wpdb->insert_id;

                    // Seed default Classes/sections
                    $classes = array('أ', 'ب', 'ج');
                    foreach ($classes as $c_name) {
                        $wpdb->insert("{$wpdb->prefix}eess_classes", array(
                            'grade_id' => $grade_id,
                            'name' => $c_name
                        ));
                    }
                }

                // Seed default Departments
                $depts = array('العلوم العامة', 'الرياضيات والفيزياء', 'اللغات والآداب', 'التربية الرياضية');
                foreach ($depts as $d_name) {
                    $wpdb->insert("{$wpdb->prefix}eess_departments", array(
                        'school_id' => $school_id,
                        'name' => $d_name
                    ));
                }
            }
        }
    }

    /**
     * Retrieves the organizational scope for a given user
     */
    public static function get_user_scope($user_id = null) {
        if (!$user_id) $user_id = get_current_user_id();
        global $wpdb;

        $user = get_userdata($user_id);
        if (!$user) return array('unrestricted' => false, 'schools' => array(), 'grades' => array(), 'classes' => array(), 'subjects' => array(), 'departments' => array());

        $roles = (array) $user->roles;
        $is_admin = in_array('administrator', $roles) || in_array('sm_system_admin', $roles);

        if ($is_admin) {
            // Unrestricted access for System Admin
            $all_schools = $wpdb->get_col("SELECT id FROM {$wpdb->prefix}eess_schools WHERE status='active'");
            return array(
                'unrestricted' => true,
                'schools' => $all_schools,
                'grades' => array(),
                'classes' => array(),
                'subjects' => array(),
                'departments' => array()
            );
        }

        // Fetch user assignments
        $assignments = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}eess_user_assignments WHERE user_id = %d",
            $user_id
        ));

        $schools = array();
        $grades = array();
        $classes = array();
        $subjects = array();
        $departments = array();

        foreach ($assignments as $asn) {
            if ($asn->institution_id && (!$asn->school_id || $asn->school_id == 0)) {
                // Main Institution Scope -> Expand to all child schools under this institution
                $child_schools = $wpdb->get_col($wpdb->prepare("SELECT id FROM {$wpdb->prefix}eess_schools WHERE institution_id = %d AND status='active'", $asn->institution_id));
                if (!empty($child_schools)) {
                    foreach ($child_schools as $csid) $schools[] = intval($csid);
                }
            } elseif ($asn->school_id) {
                $schools[] = intval($asn->school_id);
            }
            if ($asn->grade_id) $grades[] = intval($asn->grade_id);
            if ($asn->class_id) $classes[] = intval($asn->class_id);
            if ($asn->subject_id) $subjects[] = intval($asn->subject_id);
            if ($asn->department_id) $departments[] = intval($asn->department_id);
        }

        // Fallback to user_meta 'eess_school_id' if assignments table is empty
        if (empty($schools)) {
            $meta_school_id = get_user_meta($user_id, 'eess_school_id', true);
            if ($meta_school_id) {
                $schools[] = intval($meta_school_id);
            }
        }

        return array(
            'unrestricted' => false,
            'schools' => array_unique($schools),
            'grades' => array_unique($grades),
            'classes' => array_unique($classes),
            'subjects' => array_unique($subjects),
            'departments' => array_unique($departments)
        );
    }

    /**
     * Centralized Assignment Saver
     */
    public static function save_user_assignments($user_id, $data) {
        global $wpdb;
        $wpdb->delete("{$wpdb->prefix}eess_user_assignments", array('user_id' => $user_id));

        $inst_ids = !empty($data['institutions']) ? array_map('intval', (array)$data['institutions']) : array();
        $school_ids = !empty($data['schools']) ? array_map('intval', (array)$data['schools']) : array();
        $grade_ids = !empty($data['grades']) ? array_map('intval', (array)$data['grades']) : array();
        $class_ids = !empty($data['classes']) ? array_map('intval', (array)$data['classes']) : array();
        $subject_ids = !empty($data['subjects']) ? array_map('intval', (array)$data['subjects']) : array();
        $dept_ids = !empty($data['departments']) ? array_map('intval', (array)$data['departments']) : array();

        $max_count = max(count($inst_ids), count($school_ids), count($grade_ids), count($class_ids), count($subject_ids), count($dept_ids), 1);

        for ($i = 0; $i < $max_count; $i++) {
            $wpdb->insert("{$wpdb->prefix}eess_user_assignments", array(
                'user_id' => $user_id,
                'institution_id' => $inst_ids[$i] ?? ($inst_ids[0] ?? null),
                'school_id' => $school_ids[$i] ?? ($school_ids[0] ?? null),
                'grade_id' => $grade_ids[$i] ?? ($grade_ids[0] ?? null),
                'class_id' => $class_ids[$i] ?? ($class_ids[0] ?? null),
                'subject_id' => $subject_ids[$i] ?? ($subject_ids[0] ?? null),
                'department_id' => $dept_ids[$i] ?? ($dept_ids[0] ?? null)
            ));
        }

        clean_user_cache($user_id);
        wp_cache_flush();
    }

    /**
     * Standardized SQL Filter Injector for any table querying students
     */
    public static function filter_students_query($query_alias = '') {
        global $wpdb;
        $scope = self::get_user_scope();
        if ($scope['unrestricted']) return " 1=1 ";

        $school_ids = !empty($scope['schools']) ? implode(',', array_map('intval', $scope['schools'])) : '0';
        $class_ids = !empty($scope['classes']) ? implode(',', array_map('intval', $scope['classes'])) : '0';

        $prefix = !empty($query_alias) ? $query_alias . '.' : '';

        // Principal / Supervisor can access all students in their assigned schools
        $user = wp_get_current_user();
        $roles = (array) $user->roles;
        $is_principal = in_array('sm_principal', $roles);
        $is_supervisor = in_array('sm_supervisor', $roles);
        $is_hr = in_array('sm_hr', $roles);

        if ($is_principal || $is_supervisor || $is_hr) {
            return " {$prefix}school_id IN ($school_ids) ";
        }

        // Teachers can only access their assigned classes/sections
        return " {$prefix}class_id IN ($class_ids) ";
    }

    public static function resolve_student_org_ids($student_id, $class_name, $section, $school_name = '') {
        global $wpdb;
        if (empty($school_name)) {
            $school_info = SM_Settings::get_school_info();
            $school_name = $school_info['school_name'] ?? 'مدرسة الأمل للتعليم الأساسي والثانوي';
        }

        // 1. Find or create School
        $school_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}eess_schools WHERE name = %s", $school_name));
        if (!$school_id) {
            $wpdb->insert("{$wpdb->prefix}eess_schools", array(
                'institution_id' => 1,
                'name' => $school_name,
                'status' => 'active'
            ));
            $school_id = $wpdb->insert_id;
        }

        // 2. Find or create Grade
        $grade_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}eess_grades WHERE school_id = %d AND name = %s", $school_id, $class_name));
        if (!$grade_id) {
            $wpdb->insert("{$wpdb->prefix}eess_grades", array(
                'school_id' => $school_id,
                'name' => $class_name
            ));
            $grade_id = $wpdb->insert_id;
        }

        // 3. Find or create Class
        $class_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}eess_classes WHERE grade_id = %d AND name = %s", $grade_id, $section));
        if (!$class_id) {
            $wpdb->insert("{$wpdb->prefix}eess_classes", array(
                'grade_id' => $grade_id,
                'name' => $section
            ));
            $class_id = $wpdb->insert_id;
        }

        // 4. Update the student table row
        $wpdb->update("{$wpdb->prefix}sm_students", array(
            'institution_id' => 1,
            'school_id' => $school_id,
            'grade_id' => $grade_id,
            'class_id' => $class_id
        ), array('id' => $student_id));

        return array(
            'school_id' => $school_id,
            'grade_id' => $grade_id,
            'class_id' => $class_id
        );
    }

    public static function ensure_all_students_resolved() {
        global $wpdb;
        $unresolved = $wpdb->get_results("SELECT id, class_name, section FROM {$wpdb->prefix}sm_students WHERE school_id IS NULL OR school_id = 0 OR class_id IS NULL OR class_id = 0");
        foreach ($unresolved as $row) {
            self::resolve_student_org_ids($row->id, $row->class_name, $row->section);
        }
    }

    /**
     * Ensures eess_institutions table has all single-level model columns
     */
    public static function ensure_institutions_columns_exist() {
        global $wpdb;
        $table = "{$wpdb->prefix}eess_institutions";

        $cols = array(
            'parent_id' => "BIGINT(20) DEFAULT NULL",
            'type' => "VARCHAR(100) DEFAULT 'مدرسة' NOT NULL",
            'logo_url' => "VARCHAR(255) DEFAULT '' NOT NULL",
            'country' => "VARCHAR(100) DEFAULT 'الإمارات العربية المتحدة' NOT NULL",
            'address' => "TEXT DEFAULT NULL",
            'phone' => "VARCHAR(50) DEFAULT '' NOT NULL",
            'manager_id' => "BIGINT(20) DEFAULT NULL",
            'director_name' => "VARCHAR(255) DEFAULT '' NOT NULL"
        );

        foreach ($cols as $col => $def) {
            $check = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '$table' AND COLUMN_NAME = '$col'");
            if (empty($check)) {
                $wpdb->query("ALTER TABLE $table ADD COLUMN $col $def");
            }
        }
    }

    // --- ORGANIZATIONAL CRUD METHODS ---
    public static function get_institutions() {
        global $wpdb;
        self::ensure_institutions_columns_exist();
        return $wpdb->get_results("SELECT i.*, u.display_name as manager_display_name FROM {$wpdb->prefix}eess_institutions i LEFT JOIN {$wpdb->users} u ON i.manager_id = u.ID ORDER BY i.name ASC");
    }

    public static function get_institution_by_id($id) {
        global $wpdb;
        self::ensure_institutions_columns_exist();
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}eess_institutions WHERE id = %d LIMIT 1", intval($id)));
    }

    public static function get_school_by_id($id) {
        global $wpdb;
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}eess_schools WHERE id = %d LIMIT 1", intval($id)));
    }

    public static function add_institution($data) {
        global $wpdb;
        self::ensure_institutions_columns_exist();
        if (is_string($data)) {
            $data = array('name' => $data);
        }
        $insert = array(
            'parent_id'     => !empty($data['parent_id']) ? intval($data['parent_id']) : null,
            'name'          => sanitize_text_field($data['name'] ?? ''),
            'type'          => sanitize_text_field($data['type'] ?? 'مدرسة'),
            'logo_url'      => esc_url_raw($data['logo_url'] ?? ''),
            'country'       => sanitize_text_field($data['country'] ?? 'الإمارات العربية المتحدة'),
            'address'       => sanitize_textarea_field($data['address'] ?? ''),
            'phone'         => sanitize_text_field($data['phone'] ?? ''),
            'manager_id'    => !empty($data['manager_id']) ? intval($data['manager_id']) : null,
            'director_name' => sanitize_text_field($data['director_name'] ?? ''),
            'status'        => 'active'
        );
        $wpdb->insert("{$wpdb->prefix}eess_institutions", $insert);
        return $wpdb->insert_id;
    }

    public static function update_institution($id, $data) {
        global $wpdb;
        self::ensure_institutions_columns_exist();
        if (is_string($data)) {
            $data = array('name' => $data);
        }
        $update = array(
            'parent_id'     => !empty($data['parent_id']) ? intval($data['parent_id']) : null,
            'name'          => sanitize_text_field($data['name'] ?? ''),
            'type'          => sanitize_text_field($data['type'] ?? 'مدرسة'),
            'logo_url'      => esc_url_raw($data['logo_url'] ?? ''),
            'country'       => sanitize_text_field($data['country'] ?? 'الإمارات العربية المتحدة'),
            'address'       => sanitize_textarea_field($data['address'] ?? ''),
            'phone'         => sanitize_text_field($data['phone'] ?? ''),
            'manager_id'    => !empty($data['manager_id']) ? intval($data['manager_id']) : null,
            'director_name' => sanitize_text_field($data['director_name'] ?? '')
        );
        return $wpdb->update("{$wpdb->prefix}eess_institutions", $update, array('id' => intval($id)));
    }

    public static function delete_institution($id) {
        global $wpdb;
        return $wpdb->delete("{$wpdb->prefix}eess_institutions", array('id' => $id));
    }

    public static function get_schools() {
        global $wpdb;
        return $wpdb->get_results("SELECT s.*, i.name as institution_name FROM {$wpdb->prefix}eess_schools s LEFT JOIN {$wpdb->prefix}eess_institutions i ON s.institution_id = i.id ORDER BY s.name ASC");
    }

    public static function get_all_schools() {
        return self::get_schools();
    }

    public static function add_school($inst_id, $name) {
        global $wpdb;
        return $wpdb->insert("{$wpdb->prefix}eess_schools", array('institution_id' => $inst_id, 'name' => $name, 'status' => 'active'));
    }

    public static function update_school($id, $name, $inst_id) {
        global $wpdb;
        return $wpdb->update("{$wpdb->prefix}eess_schools", array('name' => $name, 'institution_id' => $inst_id), array('id' => $id));
    }

    public static function delete_school($id) {
        global $wpdb;
        return $wpdb->delete("{$wpdb->prefix}eess_schools", array('id' => $id));
    }

    public static function get_grades($school_id = null) {
        global $wpdb;
        if ($school_id) {
            return $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}eess_grades WHERE school_id = %d ORDER BY name ASC", $school_id));
        }
        return $wpdb->get_results("SELECT g.*, s.name as school_name FROM {$wpdb->prefix}eess_grades g LEFT JOIN {$wpdb->prefix}eess_schools s ON g.school_id = s.id ORDER BY g.name ASC");
    }

    public static function add_grade($school_id, $name) {
        global $wpdb;
        return $wpdb->insert("{$wpdb->prefix}eess_grades", array('school_id' => $school_id, 'name' => $name));
    }

    public static function update_grade($id, $name, $school_id) {
        global $wpdb;
        return $wpdb->update("{$wpdb->prefix}eess_grades", array('name' => $name, 'school_id' => $school_id), array('id' => $id));
    }

    public static function delete_grade($id) {
        global $wpdb;
        return $wpdb->delete("{$wpdb->prefix}eess_grades", array('id' => $id));
    }

    public static function get_classes($grade_id = null) {
        global $wpdb;
        if ($grade_id) {
            return $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}eess_classes WHERE grade_id = %d ORDER BY name ASC", $grade_id));
        }
        return $wpdb->get_results("SELECT c.*, g.name as grade_name, s.name as school_name FROM {$wpdb->prefix}eess_classes c LEFT JOIN {$wpdb->prefix}eess_grades g ON c.grade_id = g.id LEFT JOIN {$wpdb->prefix}eess_schools s ON g.school_id = s.id ORDER BY c.name ASC");
    }

    public static function add_class($grade_id, $name) {
        global $wpdb;
        return $wpdb->insert("{$wpdb->prefix}eess_classes", array('grade_id' => $grade_id, 'name' => $name));
    }

    public static function update_class($id, $name, $grade_id) {
        global $wpdb;
        return $wpdb->update("{$wpdb->prefix}eess_classes", array('name' => $name, 'grade_id' => $grade_id), array('id' => $id));
    }

    public static function delete_class($id) {
        global $wpdb;
        return $wpdb->delete("{$wpdb->prefix}eess_classes", array('id' => $id));
    }

    public static function ensure_divisions_table_exists() {
        global $wpdb;
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$wpdb->prefix}eess_divisions'");
        if (!$table_exists) {
            $charset_collate = $wpdb->get_charset_collate();
            $sql = "CREATE TABLE {$wpdb->prefix}eess_divisions (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                school_id bigint(20) NOT NULL,
                name varchar(255) NOT NULL,
                status varchar(50) DEFAULT 'active' NOT NULL,
                created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
                PRIMARY KEY  (id)
            ) $charset_collate;";
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql);
        }

        // Add division_id column to eess_grades table if not exists
        $row = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '{$wpdb->prefix}eess_grades' AND COLUMN_NAME = 'division_id'");
        if (empty($row)) {
            $wpdb->query("ALTER TABLE {$wpdb->prefix}eess_grades ADD COLUMN division_id bigint(20) DEFAULT NULL");
        }

        // Add division_id column to eess_user_assignments table if not exists
        $row_assign = $wpdb->get_results("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '{$wpdb->prefix}eess_user_assignments' AND COLUMN_NAME = 'division_id'");
        if (empty($row_assign)) {
            $wpdb->query("ALTER TABLE {$wpdb->prefix}eess_user_assignments ADD COLUMN division_id bigint(20) DEFAULT NULL");
        }
    }

    public static function get_divisions() {
        global $wpdb;
        self::ensure_divisions_table_exists();
        return $wpdb->get_results("SELECT d.*, s.name as school_name FROM {$wpdb->prefix}eess_divisions d LEFT JOIN {$wpdb->prefix}eess_schools s ON d.school_id = s.id ORDER BY d.name ASC");
    }

    public static function add_division($school_id, $name) {
        global $wpdb;
        self::ensure_divisions_table_exists();
        return $wpdb->insert("{$wpdb->prefix}eess_divisions", array('school_id' => $school_id, 'name' => $name, 'status' => 'active'));
    }

    public static function update_division($id, $name, $school_id) {
        global $wpdb;
        self::ensure_divisions_table_exists();
        return $wpdb->update("{$wpdb->prefix}eess_divisions", array('name' => $name, 'school_id' => $school_id), array('id' => $id));
    }

    public static function delete_division($id) {
        global $wpdb;
        self::ensure_divisions_table_exists();
        return $wpdb->delete("{$wpdb->prefix}eess_divisions", array('id' => $id));
    }
}
