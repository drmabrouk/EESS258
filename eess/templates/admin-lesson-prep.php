<?php
if (!defined('ABSPATH')) exit;

global $wpdb;
$user_id = get_current_user_id();

// Determine layout and view options
$user = wp_get_current_user();
$roles = (array) $user->roles;
$is_admin = in_array('administrator', $roles) || current_user_can('manage_options');
$is_sys_admin = in_array('sm_system_admin', $roles);
$is_principal = in_array('sm_principal', $roles);
$is_supervisor = in_array('sm_supervisor', $roles);
$is_coordinator = in_array('sm_coordinator', $roles);
$is_activities_sup = in_array('sm_activities_supervisor', $roles);
$is_teacher = in_array('sm_teacher', $roles);

$can_review = $is_admin || $is_sys_admin || $is_principal || $is_supervisor || $is_coordinator || $is_activities_sup;

// Auto-assign supervisor helper
if (!function_exists('eess_get_teacher_supervisor')) {
    function eess_get_teacher_supervisor($teacher_id) {
        $supervisors = get_users(array('role__in' => array('sm_supervisor', 'sm_principal', 'administrator')));
        if (!empty($supervisors)) {
            return $supervisors[0]->ID;
        }
        return 1;
    }
}

// Fetch general settings with additional parameters
$prep_settings = get_option('sm_lesson_prep_settings', array(
    'submission_frequency' => 'daily',
    'submission_deadline'  => '10:00',
    'working_days'         => array('sun', 'mon', 'tue', 'wed', 'thu'),
    'pe_monday_only'       => 'yes',
    'subject_exceptions'   => 'التربية البدنية والصحية',
    'reminder_intervals'   => '1hour',
    'notification_prefs'   => array('email', 'system'),
    'approval_workflow'    => 'single',
    'revision_limits'      => '0',
    'template_mgmt'        => 'default',
    'auto_status_updates'  => 'yes',
    'late_submission_rules'=> 'flag',
    'calendar_integration' => 'no'
));

$deadline_time = ($prep_settings['submission_deadline'] ?? '10:00') . ':00';

// Handle deleting a lesson prep
if (isset($_POST['eess_delete_lesson_prep']) && wp_verify_nonce($_POST['eess_lesson_prep_nonce'], 'eess_lesson_prep_action')) {
    $prep_id_to_delete = intval($_POST['delete_prep_id']);
    if ($prep_id_to_delete > 0) {
        $owner_id = $wpdb->get_var($wpdb->prepare("SELECT teacher_id FROM {$wpdb->prefix}sm_lesson_preps WHERE id = %d", $prep_id_to_delete));
        if ($owner_id == $user_id || $is_admin || $is_sys_admin) {
            $wpdb->delete("{$wpdb->prefix}sm_lesson_preps", array('id' => $prep_id_to_delete));
            $wpdb->delete("{$wpdb->prefix}sm_lesson_comments", array('prep_id' => $prep_id_to_delete));
            echo '<div style="background:#dcfce7; color:#15803d; padding:15px; border-radius:8px; border:1px solid #bbf7d0; font-weight:700; margin-bottom:20px; font-family:\'Cairo\'; text-align:right;">✅ تم حذف وثيقة التحضير والملاحظات التابعة لها بنجاح.</div>';
        }
    }
}

// Handle Form Submissions
if (isset($_POST['eess_save_lesson_prep']) && wp_verify_nonce($_POST['eess_lesson_prep_nonce'], 'eess_lesson_prep_action')) {
    $title         = sanitize_text_field($_POST['lesson_title']);
    $subject       = sanitize_text_field($_POST['lesson_subject']);
    $grade_level   = sanitize_text_field($_POST['lesson_grade']);
    $class_section = sanitize_text_field($_POST['lesson_section']);
    $lesson_date   = sanitize_text_field($_POST['lesson_date']);
    $status        = sanitize_text_field($_POST['lesson_status']); // draft or submitted or scheduled

    $resources_json = isset($_POST['selected_resources_json']) ? sanitize_text_field($_POST['selected_resources_json']) : '[]';
    $resources_array = json_decode(stripslashes($resources_json), true);

    $lesson_data = array(
        'objectives'     => sanitize_textarea_field($_POST['objectives']),
        'warmup'         => sanitize_textarea_field($_POST['warmup']),
        'activities'     => sanitize_textarea_field($_POST['activities']),
        'resources'      => is_array($resources_array) ? array_map('sanitize_text_field', $resources_array) : array(),
        'evaluation'     => sanitize_textarea_field($_POST['evaluation']),
        'homework'       => sanitize_textarea_field($_POST['homework']),
        'notes'          => sanitize_textarea_field($_POST['notes']),
        'scheduled_time' => isset($_POST['scheduled_time']) ? sanitize_text_field($_POST['scheduled_time']) : '',
    );

    // Compute Late Submission status if submitted
    $delay_seconds = 0;
    $final_status = $status;
    $submission_time = null;

    if ($status === 'submitted') {
        $submission_time = current_time('mysql');
        $submit_timestamp = strtotime($submission_time);
        $deadline_for_lesson = strtotime($lesson_date . ' ' . $deadline_time);

        // Exemption check for PE (English/Arabic matching)
        $is_pe = (strpos(strtolower($subject), 'رياضية') !== false || strpos(strtolower($subject), 'بدنية') !== false || strpos(strtolower($subject), 'pe') !== false || strpos(strtolower($subject), 'physical') !== false);
        $is_monday = (date('N', strtotime($lesson_date)) == 1);
        $exempt = false;

        if ($is_pe && ($prep_settings['pe_monday_only'] ?? 'yes') === 'yes' && !$is_monday) {
            $exempt = true;
        }

        if ($submit_timestamp > $deadline_for_lesson && !$exempt) {
            $delay_seconds = $submit_timestamp - $deadline_for_lesson;
            $final_status = 'late';
        } else {
            $final_status = 'submitted';
        }
    }

    $supervisor_id = eess_get_teacher_supervisor($user_id);

    if (isset($_POST['prep_id']) && !empty($_POST['prep_id'])) {
        $prep_id = intval($_POST['prep_id']);
        $existing_status = $wpdb->get_var($wpdb->prepare("SELECT status FROM {$wpdb->prefix}sm_lesson_preps WHERE id = %d", $prep_id));

        // Preserve history by incrementing version if resubmitting from revision_required
        $version = 1;
        $parent_id = 0;
        if ($existing_status === 'revision_required' && $status === 'submitted') {
            $version_data = $wpdb->get_row($wpdb->prepare("SELECT version, parent_id FROM {$wpdb->prefix}sm_lesson_preps WHERE id = %d", $prep_id));
            $version = intval($version_data->version) + 1;
            $parent_id = $version_data->parent_id == 0 ? $prep_id : $version_data->parent_id;

            // Create a new version record
            $wpdb->insert(
                "{$wpdb->prefix}sm_lesson_preps",
                array(
                    'teacher_id'      => $user_id,
                    'supervisor_id'   => $supervisor_id,
                    'title'           => $title,
                    'subject'         => $subject,
                    'grade_level'     => $grade_level,
                    'class_section'   => $class_section,
                    'lesson_date'     => $lesson_date,
                    'submission_time' => $submission_time,
                    'status'          => $final_status,
                    'delay_seconds'   => $delay_seconds,
                    'lesson_data'     => json_encode($lesson_data),
                    'version'         => $version,
                    'parent_id'       => $parent_id,
                    'created_at'      => current_time('mysql'),
                    'updated_at'      => current_time('mysql')
                )
            );
            $wpdb->update("{$wpdb->prefix}sm_lesson_preps", array('status' => 'resubmitted'), array('id' => $prep_id));
        } else {
            // Standard update
            $wpdb->update(
                "{$wpdb->prefix}sm_lesson_preps",
                array(
                    'title'           => $title,
                    'subject'         => $subject,
                    'grade_level'     => $grade_level,
                    'class_section'   => $class_section,
                    'lesson_date'     => $lesson_date,
                    'submission_time' => $submission_time,
                    'status'          => $final_status,
                    'delay_seconds'   => $delay_seconds,
                    'lesson_data'     => json_encode($lesson_data),
                    'updated_at'      => current_time('mysql')
                ),
                array('id' => $prep_id)
            );
        }
    } else {
        // Insert new prep
        $wpdb->insert(
            "{$wpdb->prefix}sm_lesson_preps",
            array(
                'teacher_id'      => $user_id,
                'supervisor_id'   => $supervisor_id,
                'title'           => $title,
                'subject'         => $subject,
                'grade_level'     => $grade_level,
                'class_section'   => $class_section,
                'lesson_date'     => $lesson_date,
                'submission_time' => $submission_time,
                'status'          => $final_status,
                'delay_seconds'   => $delay_seconds,
                'lesson_data'     => json_encode($lesson_data),
                'version'         => 1,
                'parent_id'       => 0,
                'created_at'      => current_time('mysql'),
                'updated_at'      => current_time('mysql')
            )
        );
    }
    echo '<div class="updated" style="background:#def7ec; color:#03543f; padding:12px; border-radius:8px; border:1px solid #bcf0da; margin-bottom:15px; font-weight:700; font-size:13px;">تم حفظ التحضير بنجاح.</div>';
}

// Handle Supervisor Actions
if (isset($_POST['eess_supervisor_action']) && wp_verify_nonce($_POST['eess_supervisor_nonce'], 'eess_supervisor_action_nonce')) {
    $prep_id = intval($_POST['prep_id']);
    $action  = sanitize_text_field($_POST['prep_status_action']);
    $comment = sanitize_textarea_field($_POST['supervisor_comment']);

    $wpdb->update(
        "{$wpdb->prefix}sm_lesson_preps",
        array('status' => $action, 'updated_at' => current_time('mysql')),
        array('id' => $prep_id)
    );

    if (!empty($comment)) {
        $wpdb->insert(
            "{$wpdb->prefix}sm_lesson_comments",
            array(
                'prep_id'      => $prep_id,
                'user_id'      => $user_id,
                'comment_text' => $comment,
                'created_at'   => current_time('mysql')
            )
        );
    }
    echo '<div class="updated" style="background:#def7ec; color:#03543f; padding:12px; border-radius:8px; border:1px solid #bcf0da; margin-bottom:15px; font-weight:700; font-size:13px;">تم تحديث حالة التحضير وإضافة الملاحظات بنجاح.</div>';
}

// Handle Settings Update (expanded fields)
if (isset($_POST['eess_save_prep_settings']) && wp_verify_nonce($_POST['eess_settings_nonce'], 'eess_settings_action')) {
    $new_settings = array(
        'submission_frequency' => sanitize_text_field($_POST['submission_frequency']),
        'submission_deadline'  => sanitize_text_field($_POST['submission_deadline']),
        'working_days'         => isset($_POST['working_days']) ? array_map('sanitize_text_field', $_POST['working_days']) : array(),
        'pe_monday_only'       => sanitize_text_field($_POST['pe_monday_only'] ?? 'no'),
        'subject_exceptions'   => sanitize_text_field($_POST['subject_exceptions'] ?? ''),
        'reminder_intervals'   => sanitize_text_field($_POST['reminder_intervals'] ?? ''),
        'notification_prefs'   => isset($_POST['notification_prefs']) ? array_map('sanitize_text_field', $_POST['notification_prefs']) : array(),
        'approval_workflow'    => sanitize_text_field($_POST['approval_workflow'] ?? 'single'),
        'revision_limits'      => sanitize_text_field($_POST['revision_limits'] ?? '0'),
        'template_mgmt'        => sanitize_text_field($_POST['template_mgmt'] ?? 'default'),
        'auto_status_updates'  => sanitize_text_field($_POST['auto_status_updates'] ?? 'no'),
        'late_submission_rules'=> sanitize_text_field($_POST['late_submission_rules'] ?? ''),
        'calendar_integration' => sanitize_text_field($_POST['calendar_integration'] ?? 'no')
    );
    update_option('sm_lesson_prep_settings', $new_settings);
    $prep_settings = $new_settings;
    $deadline_time = ($prep_settings['submission_deadline'] ?? '10:00') . ':00';
    echo '<div class="updated" style="background:#def7ec; color:#03543f; padding:12px; border-radius:8px; border:1px solid #bcf0da; margin-bottom:15px; font-weight:700; font-size:13px;">تم حفظ إعدادات منظومة التحضير بنجاح.</div>';
}

// Load edit prep details
$edit_prep = null;
if (isset($_GET['edit_prep_id'])) {
    $edit_prep = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sm_lesson_preps WHERE id = %d AND teacher_id = %d", intval($_GET['edit_prep_id']), $user_id));
}

// Load duplicate prep details
if (isset($_GET['duplicate_prep_id'])) {
    $dup_source = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sm_lesson_preps WHERE id = %d AND teacher_id = %d", intval($_GET['duplicate_prep_id']), $user_id));
    if ($dup_source) {
        $edit_prep = $dup_source;
        $edit_prep->id = 0;
        $edit_prep->title .= ' (نسخة)';
        $edit_prep->lesson_date = current_time('Y-m-d');
        $edit_prep->status = 'draft';
    }
}

$all_subjects = SM_DB::get_subjects();
$unique_subjects = array_unique(array_map(function($s){ return $s->name; }, $all_subjects));
?>

<div class="sm-container" style="padding: 10px 0; font-family: 'Cairo', sans-serif !important; direction: rtl;">

    <!-- Single Main Banner Header (Matching Teacher Term & Annual Plans) -->
    <div style="background: #ffffff; padding: 20px 24px; border-radius: 20px; border: 1px solid #e2e8f0; margin-bottom: 18px; box-shadow: 0 4px 18px rgba(0,0,0,0.02); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
        <div style="display: flex; align-items: center; gap: 14px;">
            <div style="width: 48px; height: 48px; background: #fef2f2; border-radius: 14px; display: flex; align-items: center; justify-content: center; color: #881337; border: 1px solid #fecdd3; flex-shrink: 0;">
                <span class="dashicons dashicons-welcome-write-blog" style="font-size: 24px; width: 24px; height: 24px;"></span>
            </div>
            <div>
                <h2 style="margin: 0 0 4px 0; font-size: 20px; font-weight: 800; color: #0f172a;">تحضير الدروس</h2>
                <p style="margin: 0; font-size: 12.5px; color: #64748b; font-weight: 500;">متابعة وإعداد واعتماد التحضيرات والخطط الأكاديمية والتعليمية للكادر التدريسي والأكاديمي</p>
            </div>
        </div>

        <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
            <?php if ($is_teacher): ?>
            <button type="button" onclick="document.getElementById('prep-modal').style.display='flex'" class="sm-btn" style="background: #881337; color: #ffffff !important; height: 38px; border-radius: 9999px !important; padding: 0 20px; font-weight: 800; font-size: 12.5px; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
                <span class="dashicons dashicons-plus-alt2" style="font-size: 15px; width: 15px; height: 15px; color: #fff;"></span>
                <span>إضافة تحضير جديد</span>
            </button>
            <?php endif; ?>

            <?php if ($can_review): ?>
            <!-- Reports Dropdown Container -->
            <div style="position: relative; display: inline-block;">
                <button type="button" onclick="eessTogglePrepReportsDropdown(event)" class="sm-btn sm-btn-outline" style="height: 38px; display: inline-flex; align-items: center; gap: 6px; border-radius: 9999px !important; cursor: pointer; background: #ffffff; color: #334155; border: 1px solid #cbd5e1; font-weight: 800; font-size: 12.5px; padding: 0 16px;">
                    <span class="dashicons dashicons-analytics" style="font-size: 16px; width: 16px; height: 16px; margin: 0; color: #475569;"></span>
                    <span>تقارير التحضير</span>
                    <span class="dashicons dashicons-arrow-down-alt2" style="font-size: 10px; width: 10px; height: 10px; margin: 0;"></span>
                </button>
                <div id="eess-prep-reports-dropdown" style="display: none; position: absolute; left: 0; top: 115%; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 14px; width: 250px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); z-index: 99999; padding: 6px 0; text-align: right;">
                    <a href="javascript:void(0)" onclick="eessShowPrepReport('submitted')" style="display: block; padding: 10px 16px; color: #334155; font-size: 12px; text-decoration: none; border-bottom: 1px solid #f1f5f9; font-weight: 700;">📝 تقرير التحضيرات المقدمة</a>
                    <a href="javascript:void(0)" onclick="eessShowPrepReport('not_submitted')" style="display: block; padding: 10px 16px; color: #334155; font-size: 12px; text-decoration: none; border-bottom: 1px solid #f1f5f9; font-weight: 700;">❌ تقرير التحضيرات المتأخرة/غير المقدمة</a>
                    <a href="javascript:void(0)" onclick="eessShowPrepReport('by_institution')" style="display: block; padding: 10px 16px; color: #334155; font-size: 12px; text-decoration: none; border-bottom: 1px solid #f1f5f9; font-weight: 700;">🏫 الإحصائيات حسب المؤسسة</a>
                    <a href="javascript:void(0)" onclick="eessShowPrepReport('by_department')" style="display: block; padding: 10px 16px; color: #334155; font-size: 12px; text-decoration: none; border-bottom: 1px solid #f1f5f9; font-weight: 700;">📂 الإحصائيات حسب الأقسام</a>
                    <a href="javascript:void(0)" onclick="eessShowPrepReport('by_subject')" style="display: block; padding: 10px 16px; color: #334155; font-size: 12px; text-decoration: none; border-bottom: 1px solid #f1f5f9; font-weight: 700;">📚 الإحصائيات حسب المواد</a>
                    <a href="javascript:void(0)" onclick="eessShowPrepReport('periodical')" style="display: block; padding: 10px 16px; color: #334155; font-size: 12px; text-decoration: none; border-bottom: 1px solid #f1f5f9; font-weight: 700;">📅 تقرير دوري (يومي/أسبوعي/شهري)</a>
                    <a href="javascript:void(0)" onclick="eessShowPrepReport('ranking')" style="display: block; padding: 10px 16px; color: #334155; font-size: 12px; text-decoration: none; border-bottom: 1px solid #f1f5f9; font-weight: 700;">🏆 تصنيف المدارس والمعلمين</a>
                    <a href="javascript:void(0)" onclick="eessShowPrepReport('compliance')" style="display: block; padding: 10px 16px; color: #334155; font-size: 12px; text-decoration: none; border-bottom: 1px solid #f1f5f9; font-weight: 700;">📊 متوسطات الامتثال لنسب التقديم</a>
                    <a href="javascript:void(0)" onclick="eessShowPrepReport('late_stats')" style="display: block; padding: 10px 16px; color: #334155; font-size: 12px; text-decoration: none; border-bottom: 1px solid #f1f5f9; font-weight: 700;">⏱️ إحصائيات التأخر والمهل الزمنية</a>
                    <a href="javascript:void(0)" onclick="eessExportPrepReport()" style="display: block; padding: 10px 16px; color: #0d9488; font-size: 12px; font-weight: 800; text-decoration: none;">📥 تصدير التقرير الموحد (Excel/CSV)</a>
                </div>
            </div>

            <!-- Settings Gear Icon Button -->
            <button type="button" onclick="document.getElementById('prep-settings-modal').style.display='flex'" class="sm-btn sm-btn-outline" style="height: 38px; display: inline-flex; align-items: center; gap: 6px; border-radius: 9999px !important; cursor: pointer; background: #ffffff; color: #334155; border: 1px solid #cbd5e1; font-weight: 800; font-size: 12.5px; padding: 0 16px;">
                <span class="dashicons dashicons-admin-generic" style="font-size: 16px; width: 16px; height: 16px; margin: 0; color: #475569;"></span>
                <span>إعدادات التحضير</span>
            </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Administrative Statistics Dashboard (Compact layout) -->
    <?php if ($can_review):
        $today_date = current_time('Y-m-d');
        $stats_total_required = count(get_users(array('role' => 'sm_teacher')));
        $stats_submitted      = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}sm_lesson_preps WHERE (lesson_date = %s OR DATE(created_at) = %s) AND status IN ('submitted', 'approved', 'revision_required', 'rejected', 'late')", $today_date, $today_date));
        $stats_pending        = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sm_lesson_preps WHERE status = 'submitted'");
        $stats_approved       = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sm_lesson_preps WHERE status = 'approved'");
        $stats_rejected       = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sm_lesson_preps WHERE status = 'rejected'");
        $stats_revision       = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sm_lesson_preps WHERE status = 'revision_required'");
        $stats_late           = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sm_lesson_preps WHERE status = 'late'");

        $submission_pct = $stats_total_required > 0 ? round(($stats_submitted / $stats_total_required) * 100) : 0;
    ?>
    <div style="background: #fff; padding: 15px; border-radius: 8px; border: 1px solid #e2e8f0; margin-bottom: 15px;">
        <h3 style="margin: 0 0 12px 0; font-weight: 800; color: #1e293b; font-size: 13px; border-bottom: 2px solid #f1f5f9; padding-bottom: 6px;">إحصائيات الامتثال ومتابعة التحضير</h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 10px;">
            <div onclick="eessShowComplianceStatDetails('required')" class="sm-stat-card" style="border-top: 3px solid #334155; text-align: center; background: #f8fafc; padding: 10px; border-radius: 6px; border: 1px solid #e2e8f0; cursor: pointer; transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                <div style="font-size: 11px; color: #64748b; font-weight: 700; margin-bottom: 3px;">التحضيرات المطلوبة</div>
                <div style="font-size: 18px; font-weight: 800; color: #334155;"><?php echo $stats_total_required; ?></div>
            </div>
            <div onclick="eessShowComplianceStatDetails('submitted')" class="sm-stat-card" style="border-top: 3px solid #475569; text-align: center; background: #f8fafc; padding: 10px; border-radius: 6px; border: 1px solid #e2e8f0; cursor: pointer; transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                <div style="font-size: 11px; color: #64748b; font-weight: 700; margin-bottom: 3px;">التحضيرات المقدمة</div>
                <div style="font-size: 18px; font-weight: 800; color: #475569;"><?php echo $stats_submitted; ?></div>
            </div>
            <div onclick="eessShowComplianceStatDetails('pending')" class="sm-stat-card" style="border-top: 3px solid #eab308; text-align: center; background: #f8fafc; padding: 10px; border-radius: 6px; border: 1px solid #e2e8f0; cursor: pointer; transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                <div style="font-size: 11px; color: #64748b; font-weight: 700; margin-bottom: 3px;">قيد المراجعة</div>
                <div id="eess-pending-review-stat-counter" style="font-size: 18px; font-weight: 800; color: #eab308;"><?php echo $stats_pending; ?></div>
            </div>
            <div onclick="eessShowComplianceStatDetails('approved')" class="sm-stat-card" style="border-top: 3px solid #16a34a; text-align: center; background: #f8fafc; padding: 10px; border-radius: 6px; border: 1px solid #e2e8f0; cursor: pointer; transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                <div style="font-size: 11px; color: #64748b; font-weight: 700; margin-bottom: 3px;">التحضيرات المعتمدة</div>
                <div style="font-size: 18px; font-weight: 800; color: #16a34a;"><?php echo $stats_approved; ?></div>
            </div>
            <div onclick="eessShowComplianceStatDetails('revision_required')" class="sm-stat-card" style="border-top: 3px solid #ea580c; text-align: center; background: #f8fafc; padding: 10px; border-radius: 6px; border: 1px solid #e2e8f0; cursor: pointer; transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                <div style="font-size: 11px; color: #64748b; font-weight: 700; margin-bottom: 3px;">تعديل مطلوب</div>
                <div style="font-size: 18px; font-weight: 800; color: #ea580c;"><?php echo $stats_revision; ?></div>
            </div>
            <div onclick="eessShowComplianceStatDetails('late')" class="sm-stat-card" style="border-top: 3px solid #8b1e1e; text-align: center; background: #f8fafc; padding: 10px; border-radius: 6px; border: 1px solid #e2e8f0; cursor: pointer; transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                <div style="font-size: 11px; color: #64748b; font-weight: 700; margin-bottom: 3px;">تسليم متأخر</div>
                <div style="font-size: 18px; font-weight: 800; color: #8b1e1e;"><?php echo $stats_late; ?></div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Main Content Grid -->
    <div style="display: grid; grid-template-columns: 1fr; gap: 20px;">

        <!-- Step-by-Step Modal Wizard for Lesson Prep -->
        <?php if ($is_teacher): ?>
        <div id="prep-modal" class="sm-modal-overlay" style="display: <?php echo ($edit_prep && $edit_prep->id > 0) ? 'flex' : 'none'; ?>; position: fixed; inset: 0; width: 100vw; height: 100vh; background: rgba(15, 23, 42, 0.75); backdrop-filter: blur(5px); z-index: 999999; justify-content: center; align-items: center; padding: 20px; box-sizing: border-box; font-family: 'Cairo', sans-serif;">
            <div class="sm-modal-content" style="background: #ffffff; border-radius: 20px; max-width: 960px; width: 100%; max-height: 94vh; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.3); border: 1px solid #cbd5e1; display: flex; flex-direction: column;">

                <!-- Flush Full-Width Modal Header Banner with White Title and White Icon -->
                <div style="background: #0f172a; color: #ffffff; padding: 18px 24px; border-bottom: 1px solid #1e293b; display: flex; justify-content: space-between; align-items: center; width: 100%; box-sizing: border-box; margin: 0;">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <span class="dashicons dashicons-welcome-write-blog" style="color: #ffffff; font-size: 22px; width: 22px; height: 22px; margin: 0;"></span>
                        <div>
                            <h3 style="margin: 0; font-size: 16px; font-weight: 800; color: #ffffff; font-family: 'Cairo', sans-serif;">
                                <?php echo ($edit_prep && $edit_prep->id > 0) ? 'تعديل وثيقة تحضير درس' : 'إضافة تحضير جديد – المساعد الأكاديمي'; ?>
                            </h3>
                            <p style="margin: 3px 0 0 0; font-size: 11.5px; color: #94a3b8; font-weight: 600;">إعداد وتوثيق الخطة الدراسية خطوة بخطوة وفق الهيكل الأكاديمي</p>
                        </div>
                    </div>
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <span id="prep-autosave-badge" style="background: rgba(255,255,255,0.1); color: #cbd5e1; padding: 4px 12px; border-radius: 50px; font-size: 11px; font-weight: 700;">مسودة</span>
                        <button type="button" onclick="document.getElementById('prep-modal').style.display='none'" style="background: none; border: none; color: #ffffff; font-size: 26px; cursor: pointer; line-height: 1;">&times;</button>
                    </div>
                </div>

                <!-- Modal Body -->
                <div style="padding: 24px; box-sizing: border-box; overflow-y: auto; flex: 1;">
                    <?php
                    $assigned_subject = get_user_meta($user_id, 'sm_specialization', true) ?: '';
                    $is_locked = ($is_teacher && !empty($assigned_subject));
                    $current_subject = !empty($edit_prep->subject) ? $edit_prep->subject : $assigned_subject;

                    $subj_fields = SM_Settings::get_subject_lesson_fields($current_subject);
                    $data = $edit_prep ? json_decode($edit_prep->lesson_data, true) : array();
                    ?>

                    <form method="post" id="eess-lesson-prep-wizard-form" oninput="eessTriggerPrepAutoSave()">
                        <?php wp_nonce_field('eess_lesson_prep_action', 'eess_lesson_prep_nonce'); ?>
                        <input type="hidden" name="prep_id" id="eess_prep_db_id" value="<?php echo $edit_prep->id ?? 0; ?>">

                        <!-- Full-Width Balanced RTL Stepper Track -->
                        <div style="background: #f8fafc; padding: 14px 20px; border-radius: 14px; border: 1px solid #e2e8f0; margin-bottom: 22px; position: relative;">
                            <div style="position: absolute; top: 50%; left: 40px; right: 40px; height: 2px; background: #e2e8f0; transform: translateY(-50%); z-index: 1;"></div>
                            <div style="position: relative; z-index: 2; display: flex; justify-content: space-between; align-items: center; width: 100%;">
                                <div class="eess-prep-step-indicator active" id="eess-prep-ind-1" style="font-weight: 800; font-size: 11.5px; color: var(--sm-primary-color); display: flex; flex-direction: column; align-items: center; gap: 4px; background: #f8fafc; padding: 0 6px;">
                                    <span style="background: var(--sm-primary-color); color: white; width: 28px; height: 28px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 800; box-shadow: 0 0 0 4px #f8fafc;">1</span>
                                    <span>البيانات العامة</span>
                                </div>
                                <div class="eess-prep-step-indicator" id="eess-prep-ind-2" style="font-weight: 700; font-size: 11.5px; color: #94a3b8; display: flex; flex-direction: column; align-items: center; gap: 4px; background: #f8fafc; padding: 0 6px;">
                                    <span style="background: #e2e8f0; color: #475569; width: 28px; height: 28px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 800; box-shadow: 0 0 0 4px #f8fafc;">2</span>
                                    <span>الأهداف والتمهيد</span>
                                </div>
                                <div class="eess-prep-step-indicator" id="eess-prep-ind-3" style="font-weight: 700; font-size: 11.5px; color: #94a3b8; display: flex; flex-direction: column; align-items: center; gap: 4px; background: #f8fafc; padding: 0 6px;">
                                    <span style="background: #e2e8f0; color: #475569; width: 28px; height: 28px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 800; box-shadow: 0 0 0 4px #f8fafc;">3</span>
                                    <span>الأنشطة والتقويم</span>
                                </div>
                                <div class="eess-prep-step-indicator" id="eess-prep-ind-4" style="font-weight: 700; font-size: 11.5px; color: #94a3b8; display: flex; flex-direction: column; align-items: center; gap: 4px; background: #f8fafc; padding: 0 6px;">
                                    <span style="background: #e2e8f0; color: #475569; width: 28px; height: 28px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 800; box-shadow: 0 0 0 4px #f8fafc;">4</span>
                                    <span>الواجبات والملاحظات</span>
                                </div>
                                <div class="eess-prep-step-indicator" id="eess-prep-ind-5" style="font-weight: 700; font-size: 11.5px; color: #94a3b8; display: flex; flex-direction: column; align-items: center; gap: 4px; background: #f8fafc; padding: 0 6px;">
                                    <span style="background: #e2e8f0; color: #475569; width: 28px; height: 28px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 800; box-shadow: 0 0 0 4px #f8fafc;">5</span>
                                    <span>المراجعة والإرسال</span>
                                </div>
                            </div>
                        </div>

                        <!-- Stage 1: Basic Information (Only عنوان الدرس & تاريخ الدرس) -->
                        <div class="eess-prep-wizard-stage" id="eess-prep-stage-1" style="display: block;">
                            <?php
                                $teacher_school = get_user_meta($user_id, 'eess_school_name', true) ?: 'المدرسة الرئيسية';
                                $teacher_grade  = get_user_meta($user_id, 'sm_grade_level', true) ?: (get_user_meta($user_id, 'grade', true) ?: 'الصف العاشر');
                                $teacher_section= get_user_meta($user_id, 'sm_class_section', true) ?: (get_user_meta($user_id, 'section', true) ?: 'أ');
                            ?>
                            <!-- Personalized Step 1 Educational Instructional Note -->
                            <div style="background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 12px; padding: 14px 18px; margin-bottom: 20px;">
                                <div style="font-weight: 800; font-size: 13.5px; color: #166534; margin-bottom: 6px; display: flex; align-items: center; gap: 6px;">
                                    <span class="dashicons dashicons-info" style="font-size: 16px; width: 16px; height: 16px;"></span>
                                    <span>أهلاً بك أ. <?php echo esc_html($user->display_name); ?> - الإرشادات الأكاديمية للخطوة الأولى:</span>
                                </div>
                                <p style="margin: 0 0 10px 0; font-size: 12px; color: #15803d; line-height: 1.5;">تتيح لك هذه الخطوة إدخال عنوان الدرس الرئيسي وتحديد تاريخ إعطائه. تم استرجاع مادتك وتسكينك الأكاديمي تلقائياً لتسريع وتسهيل إدخال البيانات.</p>
                                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; font-size: 12px; color: #15803d; font-weight: 700; background: rgba(255,255,255,0.6); padding: 8px 12px; border-radius: 8px; border: 1px solid #bbf7d0;">
                                    <div>📚 <strong>المادة:</strong> <?php echo esc_html($current_subject); ?></div>
                                    <div>🏫 <strong>المدرسة:</strong> <?php echo esc_html($teacher_school); ?></div>
                                    <div>🎓 <strong>التسكين:</strong> <?php echo esc_html($teacher_grade . ' (' . $teacher_section . ')'); ?></div>
                                </div>
                            </div>

                            <input type="hidden" id="eess_lesson_subject" name="lesson_subject" value="<?php echo esc_attr($current_subject); ?>">
                            <input type="hidden" id="eess_lesson_grade" name="lesson_grade" value="<?php echo esc_attr($edit_prep->grade_level ?? $teacher_grade); ?>">
                            <input type="hidden" id="eess_lesson_section" name="lesson_section" value="<?php echo esc_attr($edit_prep->class_section ?? $teacher_section); ?>">

                            <h4 style="margin: 0 0 15px 0; font-size: 14px; font-weight: 800; color: #0f172a;">الخطوة الأولى: عنوان وتاريخ الدرس</h4>
                            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 16px; margin-bottom: 20px;">
                                <div>
                                    <label class="sm-label" style="font-weight: 700; font-size: 12.5px; color: #334155; margin-bottom: 6px; display: block;">عنوان الدرس <span style="color:#ef4444;">*</span></label>
                                    <input type="text" id="eess_lesson_title" name="lesson_title" value="<?php echo esc_attr($edit_prep->title ?? ''); ?>" class="sm-input" placeholder="أدخل عنوان الدرس الرئيسي..." style="height: 42px; font-size: 13px; border-radius: 9999px !important; border: 1px solid #cbd5e1; padding: 0 16px;" required>
                                </div>
                                <div>
                                    <label class="sm-label" style="font-weight: 700; font-size: 12.5px; color: #334155; margin-bottom: 6px; display: block;">تاريخ الدرس <span style="color:#ef4444;">*</span></label>
                                    <input type="date" id="eess_lesson_date" name="lesson_date" value="<?php echo esc_attr($edit_prep->lesson_date ?? current_time('Y-m-d')); ?>" class="sm-input" style="height: 42px; font-size: 12.5px; border-radius: 9999px !important; border: 1px solid #cbd5e1; padding: 0 14px;" required>
                                </div>
                            </div>
                        </div>

                        <!-- Stage 2: Specialized PE & Subject-Aware Objectives & Planning (Logical 9-Field Structure) -->
                        <div class="eess-prep-wizard-stage" id="eess-prep-stage-2" style="display: none;">
                            <h4 style="margin: 0 0 15px 0; font-size: 14px; font-weight: 800; color: #0f172a;">الخطوة الثانية: الربط والتفكير والإعداد البدني لدرس (<?php echo esc_html($current_subject); ?>)</h4>

                            <!-- Connection to Other Subjects (max 250 chars) -->
                            <div class="eess-float-group" style="margin-bottom: 22px;">
                                <textarea id="eess_cross_subject" name="cross_subject" maxlength="250" oninput="eessUpdateCharCount(this, 250, 'cnt_cross_subject')" class="sm-input eess-float-input" style="height: 80px; font-size: 12.5px; border-radius: 12px; border: 1px solid #cbd5e1; padding: 18px 16px 10px 16px;" placeholder=" "><?php echo esc_textarea($data['cross_subject'] ?? ''); ?></textarea>
                                <label for="eess_cross_subject" class="eess-float-label">2. الربط بالمواد الأخرى (الحد الأقصى: 250 حرف)</label>
                                <div style="text-align: left; font-size: 10.5px; color: #64748b; font-family: monospace; margin-top: 2px;" id="cnt_cross_subject">0 / 250</div>
                            </div>

                            <!-- Critical Thinking Questions (max 250 chars) -->
                            <div class="eess-float-group" style="margin-bottom: 22px;">
                                <textarea id="eess_critical_thinking" name="critical_thinking" maxlength="250" oninput="eessUpdateCharCount(this, 250, 'cnt_critical_thinking')" class="sm-input eess-float-input" style="height: 80px; font-size: 12.5px; border-radius: 12px; border: 1px solid #cbd5e1; padding: 18px 16px 10px 16px;" placeholder=" "><?php echo esc_textarea($data['critical_thinking'] ?? ''); ?></textarea>
                                <label for="eess_critical_thinking" class="eess-float-label">3. أسئلة التفكير الناقد والتحدي الحركي (الحد الأقصى: 250 حرف)</label>
                                <div style="text-align: left; font-size: 10.5px; color: #64748b; font-family: monospace; margin-top: 2px;" id="cnt_critical_thinking">0 / 250</div>
                            </div>

                            <!-- Warm-Up (5 min, max 250 chars) -->
                            <div class="eess-float-group" style="margin-bottom: 15px;">
                                <textarea id="eess_warmup" name="warmup" maxlength="250" oninput="eessUpdateCharCount(this, 250, 'cnt_warmup')" class="sm-input eess-float-input" style="height: 85px; font-size: 12.5px; border-radius: 12px; border: 1px solid #cbd5e1; padding: 18px 16px 10px 16px;" placeholder=" " required><?php echo esc_textarea($data['warmup'] ?? ''); ?></textarea>
                                <label for="eess_warmup" class="eess-float-label">4. الإحماء والتهيئة البدنية (Warm-Up - 5 دقائق) <span style="color:#ef4444;">*</span></label>
                                <div style="text-align: left; font-size: 10.5px; color: #64748b; font-family: monospace; margin-top: 2px;" id="cnt_warmup">0 / 250</div>
                            </div>

                            <script>
                            function eessUpdateCharCount(input, maxLen, badgeId) {
                                var len = input.value.length;
                                var badge = document.getElementById(badgeId);
                                if (badge) {
                                    badge.innerText = len + ' / ' + maxLen;
                                    if (len >= maxLen) {
                                        badge.style.color = '#dc2626';
                                    } else {
                                        badge.style.color = '#64748b';
                                    }
                                }
                            }
                            </script>
                        </div>

                        <!-- Stage 3: Activities & Learning Resources (Capsules Selection - Max 5 Choices) -->
                        <div class="eess-prep-wizard-stage" id="eess-prep-stage-3" style="display: none;">
                            <h4 style="margin: 0 0 15px 0; font-size: 14px; font-weight: 800; color: #0f172a;">الخطوة الثالثة: الأنشطة ومصادر التعلم</h4>

                            <!-- Activities Floating Textarea -->
                            <div style="position: relative; margin-bottom: 22px;">
                                <textarea id="eess_activities" name="activities" class="sm-input eess-floating-textarea" style="height: 95px; font-size: 12.5px; border-radius: 12px; border: 1px solid #cbd5e1; padding: 18px 16px 10px 16px; width: 100%; box-sizing: border-box;" placeholder=" " required><?php echo esc_textarea($data['activities'] ?? ''); ?></textarea>
                                <label for="eess_activities" style="position: absolute; right: 14px; top: -10px; background: #ffffff; padding: 0 6px; font-size: 11.5px; font-weight: 800; color: #881337; border-radius: 4px; pointer-events: none;">
                                    3. <?php echo esc_html($subj_fields['label3']); ?> <span style="color:#ef4444;">*</span>
                                </label>
                            </div>

                            <!-- Learning Resources 15 Capsules Grid -->
                            <div style="margin-bottom: 15px;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                    <label class="sm-label" style="font-weight: 800; font-size: 12.5px; color: #334155;">مصادر وسائل التعلم والتقنيات (اختر 5 كحد أقصى) <span style="color:#ef4444;">*</span></label>
                                    <span id="eess-resource-counter" style="font-size: 11px; font-weight: 700; color: #0284c7; background: #e0f2fe; padding: 2px 10px; border-radius: 50px;">0 / 5 مختارة</span>
                                </div>

                                <?php
                                $resource_options = array(
                                    'الكتاب المدرسي', 'السبورة التفاعلية', 'العارض الضوئي (Projector)',
                                    'أوراق عمل مطبوعة', 'بطاقات تعليمية', 'أجهزة اللوحية (Tablets)',
                                    'فيديوهات تعليمية', 'نماذج ومجسمات ثلاثية الأبعاد', 'عرض تقديمي (PowerPoint)',
                                    'مختبر العلوم / الحاسوب', 'أدوات ومعدات رياضية', 'منصة التيمز / التعلم الذكي',
                                    'خرائط ورسوم بيانية', 'قصص ومراجع خارجية', 'ألعاب تعليمية تفاعلية'
                                );
                                $saved_resources = isset($data['resources']) && is_array($data['resources']) ? $data['resources'] : array();
                                ?>

                                <input type="hidden" name="selected_resources_json" id="eess_selected_resources_json" value="<?php echo esc_attr(json_encode($saved_resources)); ?>">

                                <div style="display: flex; flex-wrap: wrap; gap: 8px; max-height: 140px; overflow-y: auto; padding: 8px; border: 1px solid #e2e8f0; border-radius: 12px; background: #f8fafc;">
                                    <?php foreach ($resource_options as $res_item):
                                        $is_selected = in_array($res_item, $saved_resources);
                                    ?>
                                        <div class="eess-resource-capsule <?php echo $is_selected ? 'selected' : ''; ?>"
                                             onclick="eessToggleResourceCapsule(this, '<?php echo esc_js($res_item); ?>')"
                                             style="padding: 6px 14px; border-radius: 50px; font-size: 11.5px; font-weight: 700; cursor: pointer; transition: all 0.2s; border: 1px solid <?php echo $is_selected ? '#881337' : '#cbd5e1'; ?>; background: <?php echo $is_selected ? '#881337' : '#ffffff'; ?>; color: <?php echo $is_selected ? '#ffffff' : '#475569'; ?>;">
                                            <?php echo esc_html($res_item); ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- Evaluation Floating Textarea -->
                            <div style="position: relative; margin-bottom: 15px;">
                                <textarea id="eess_evaluation" name="evaluation" class="sm-input eess-floating-textarea" style="height: 80px; font-size: 12.5px; border-radius: 12px; border: 1px solid #cbd5e1; padding: 18px 16px 10px 16px; width: 100%; box-sizing: border-box;" placeholder=" " required><?php echo esc_textarea($data['evaluation'] ?? ''); ?></textarea>
                                <label for="eess_evaluation" style="position: absolute; right: 14px; top: -10px; background: #ffffff; padding: 0 6px; font-size: 11.5px; font-weight: 800; color: #881337; border-radius: 4px; pointer-events: none;">
                                    4. <?php echo esc_html($subj_fields['label4']); ?> <span style="color:#ef4444;">*</span>
                                </label>
                            </div>
                        </div>

                        <script>
                            function eessToggleResourceCapsule(element, resourceName) {
                                const hiddenInput = document.getElementById('eess_selected_resources_json');
                                let currentSelected = [];
                                try {
                                    currentSelected = JSON.parse(hiddenInput.value) || [];
                                } catch(e) {
                                    currentSelected = [];
                                }

                                const index = currentSelected.indexOf(resourceName);
                                if (index > -1) {
                                    currentSelected.splice(index, 1);
                                    element.classList.remove('selected');
                                    element.style.background = '#ffffff';
                                    element.style.color = '#475569';
                                    element.style.borderColor = '#cbd5e1';
                                } else {
                                    if (currentSelected.length >= 5) {
                                        alert('يمكنك اختيار 5 وسائل تعليمية كحد أقصى للدرس الواحد.');
                                        return;
                                    }
                                    currentSelected.push(resourceName);
                                    element.classList.add('selected');
                                    element.style.background = '#881337';
                                    element.style.color = '#ffffff';
                                    element.style.borderColor = '#881337';
                                }

                                hiddenInput.value = JSON.stringify(currentSelected);
                                document.getElementById('eess-resource-counter').innerText = currentSelected.length + ' / 5 مختارة';
                                eessTriggerPrepAutoSave();
                            }
                        </script>

                        <!-- Stage 4: Homework & Notes -->
                        <div class="eess-prep-wizard-stage" id="eess-prep-stage-4" style="display: none;">
                            <h4 style="margin: 0 0 15px 0; font-size: 13px; font-weight: 800; color: var(--sm-primary-color);">الخطوة الرابعة: الواجبات والملاحظات التربوية</h4>
                            <div style="margin-bottom: 15px;">
                                <label class="sm-label" style="font-weight: 700; font-size: 12px;">5. الواجبات المنزلية والمهام الأكاديمية (Homework)</label>
                                <textarea id="eess_homework" name="homework" class="sm-input" style="height: 80px; font-size: 12px;" placeholder="حدد المهام أو الواجبات المطلوبة من الطلاب..."><?php echo esc_textarea($data['homework'] ?? ''); ?></textarea>
                            </div>
                            <div style="margin-bottom: 15px;">
                                <label class="sm-label" style="font-weight: 700; font-size: 12px;">6. ملاحظات وإرشادات وتأملات مهنية إضافية</label>
                                <textarea id="eess_notes" name="notes" class="sm-input" style="height: 80px; font-size: 12px;" placeholder="أي ملاحظات أو إرشادات تربوية إضافية..."><?php echo esc_textarea($data['notes'] ?? ''); ?></textarea>
                            </div>
                        </div>

                        <!-- Stage 5: Final Review & Confirmation -->
                        <div class="eess-prep-wizard-stage" id="eess-prep-stage-5" style="display: none;">
                            <h4 style="margin: 0 0 15px 0; font-size: 13px; font-weight: 800; color: var(--sm-primary-color);">الخطوة الخامسة: مراجعة وثيقة التحضير والتقديم للمراجعة</h4>
                            <div style="background: #f8fafc; padding: 20px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 12px; line-height: 1.6; margin-bottom: 20px;" id="eess-prep-review-live-summary">
                                <!-- Filled live via JavaScript -->
                            </div>
                        </div>

                        <!-- Wizard Step Action Controls -->
                        <div style="display: flex; gap: 12px; justify-content: space-between; border-top: 1px solid #e2e8f0; padding-top: 15px; margin-top: 15px;">
                            <div>
                                <button type="button" id="eess-prep-prev-btn" class="sm-btn sm-btn-outline" style="width: auto; height: 38px; padding: 0 18px; font-size: 12.5px; border-radius: 9999px !important; display: none; cursor:pointer;" onclick="eessGoToPrepStage(eessActivePrepStage - 1)">السابق</button>
                            </div>
                            <div style="display: flex; gap: 10px;">
                                <button type="button" id="eess-prep-next-btn" class="sm-btn" style="width: auto; height: 38px; padding: 0 22px; font-size: 12.5px; background: #881337; border: none; border-radius: 9999px !important; cursor:pointer; color: white !important; font-weight: 800;" onclick="eessGoToPrepStage(eessActivePrepStage + 1)">المتابعة للخطوة التالية</button>

                                <!-- Submit actions (Hidden until last stage) -->
                                <button type="submit" name="eess_save_lesson_prep" id="eess-prep-submit-btn" onclick="document.getElementById('lesson_status').value='submitted'; eessClearPrepDraftBackup();" class="sm-btn" style="width: auto; height: 38px; padding: 0 20px; font-size: 12.5px; background: #16a34a; border-radius: 9999px !important; border: none; color: white !important; font-weight: 800; display: none; cursor:pointer;">تم التقديم للمراجعة</button>
                                <button type="submit" name="eess_save_lesson_prep" id="eess-prep-draft-btn" onclick="document.getElementById('lesson_status').value='draft'" class="sm-btn sm-btn-secondary" style="width: auto; height: 38px; padding: 0 18px; font-size: 12.5px; background: #334155; color: white !important; border-radius: 9999px !important; border: none; font-weight: 700; display: none; cursor:pointer;">حفظ كمسودة</button>

                                <button type="button" onclick="document.getElementById('prep-modal').style.display='none'" class="sm-btn sm-btn-outline" style="width: auto; height: 38px; font-size: 12px; border-radius: 9999px !important;">إغلاق</button>
                            </div>
                        </div>

                        <input type="hidden" name="lesson_status" id="lesson_status" value="submitted">
                    </form>
                </div>
            </div>
        </div>

        <script>
            let eessAutoSaveTimeout = null;

            function eessTriggerPrepAutoSave() {
                // Save to local storage for offline recovery
                const draftData = {
                    title: document.getElementById('eess_lesson_title').value,
                    subject: document.getElementById('eess_lesson_subject') ? document.getElementById('eess_lesson_subject').value : '',
                    grade: document.getElementById('eess_lesson_grade').value,
                    section: document.getElementById('eess_lesson_section').value,
                    date: document.getElementById('eess_lesson_date').value,
                    objectives: document.getElementById('eess_objectives').value,
                    warmup: document.getElementById('eess_warmup').value,
                    activities: document.getElementById('eess_activities').value,
                    evaluation: document.getElementById('eess_evaluation').value,
                    homework: document.getElementById('eess_homework').value,
                    notes: document.getElementById('eess_notes').value
                };
                localStorage.setItem('eess_lesson_prep_draft_' + <?php echo $user_id; ?>, JSON.stringify(draftData));

                const badge = document.getElementById('prep-autosave-badge');
                if (badge) badge.innerText = 'مسودة (جاري الحفظ...)';

                clearTimeout(eessAutoSaveTimeout);
                eessAutoSaveTimeout = setTimeout(() => {
                    if (badge) badge.innerText = 'مسودة (تم الحفظ)';
                }, 1000);
            }

            function eessClearPrepDraftBackup() {
                localStorage.removeItem('eess_lesson_prep_draft_' + <?php echo $user_id; ?>);
            }

            // Check and restore draft if exists on modal open
            window.addEventListener('load', function() {
                const savedDraft = localStorage.getItem('eess_lesson_prep_draft_' + <?php echo $user_id; ?>);
                if (savedDraft) {
                    try {
                        const parsed = JSON.parse(savedDraft);
                        if (parsed.title && !document.getElementById('eess_lesson_title').value) {
                            if (confirm('توجد مسودة غير مكتملة محفوظة سابقاً. هل ترغب في استرجاعها لمتابعة تحضير الدرس؟')) {
                                document.getElementById('eess_lesson_title').value = parsed.title || '';
                                if (document.getElementById('eess_lesson_subject') && parsed.subject) {
                                    document.getElementById('eess_lesson_subject').value = parsed.subject;
                                }
                                document.getElementById('eess_lesson_grade').value = parsed.grade || '';
                                document.getElementById('eess_lesson_section').value = parsed.section || '';
                                document.getElementById('eess_lesson_date').value = parsed.date || '';
                                document.getElementById('eess_objectives').value = parsed.objectives || '';
                                document.getElementById('eess_warmup').value = parsed.warmup || '';
                                document.getElementById('eess_activities').value = parsed.activities || '';
                                document.getElementById('eess_evaluation').value = parsed.evaluation || '';
                                document.getElementById('eess_homework').value = parsed.homework || '';
                                document.getElementById('eess_notes').value = parsed.notes || '';
                            }
                        }
                    } catch(e) {}
                }
            });
        </script>
        <?php endif; ?>

        <!-- List Panel (Compacted & Cleaned Up) -->
        <div style="background: #fff; padding: 20px; border-radius: 8px; border: 1px solid #e2e8f0;">

            <!-- Search and Filter bar (Unified Wine-Red & Neutral Design System) -->
            <form method="get" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 12px; margin-bottom: 20px; background: #ffffff; padding: 18px; border-radius: 16px; border: 1px solid #cbd5e1; box-shadow: 0 2px 8px rgba(0,0,0,0.02);">
                <input type="hidden" name="page" value="<?php echo isset($_GET['page']) ? esc_attr($_GET['page']) : ''; ?>">

                <div>
                    <label style="font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 5px; display: block;">البحث الفوري</label>
                    <input type="text" name="s_query" value="<?php echo isset($_GET['s_query']) ? esc_attr($_GET['s_query']) : ''; ?>" placeholder="اسم المعلم، المادة، أو عنوان الدرس..." class="sm-input" style="height:38px; font-size:12.5px; border-radius: 9999px !important; border: 1px solid #cbd5e1; padding: 0 14px;">
                </div>

                <div>
                    <label style="font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 5px; display: block;">تاريخ الدرس</label>
                    <input type="date" name="filter_date" value="<?php echo isset($_GET['filter_date']) ? esc_attr($_GET['filter_date']) : ''; ?>" class="sm-input" style="height:38px; font-size:12.5px; border-radius: 9999px !important; border: 1px solid #cbd5e1; padding: 0 12px;">
                </div>

                <div>
                    <label style="font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 5px; display: block;">حالة التحضير</label>
                    <select name="filter_status" class="sm-input" style="height:38px; font-size:12.5px; border-radius: 9999px !important; border: 1px solid #cbd5e1; padding: 0 12px;">
                        <option value="">كافة الحالات</option>
                        <option value="draft" <?php selected(isset($_GET['filter_status']) && $_GET['filter_status'] == 'draft'); ?>>مسودة</option>
                        <option value="submitted" <?php selected(isset($_GET['filter_status']) && $_GET['filter_status'] == 'submitted'); ?>>مقدم للاعتماد</option>
                        <option value="approved" <?php selected(isset($_GET['filter_status']) && $_GET['filter_status'] == 'approved'); ?>>معتمد</option>
                        <option value="revision_required" <?php selected(isset($_GET['filter_status']) && $_GET['filter_status'] == 'revision_required'); ?>>تعديل مطلوب</option>
                        <option value="rejected" <?php selected(isset($_GET['filter_status']) && $_GET['filter_status'] == 'rejected'); ?>>مرفوض</option>
                        <option value="late" <?php selected(isset($_GET['filter_status']) && $_GET['filter_status'] == 'late'); ?>>تسليم متأخر</option>
                    </select>
                </div>

                <div style="display: flex; align-items: flex-end; gap: 8px;">
                    <button type="submit" class="sm-btn" style="height: 38px; font-size:12.5px; padding:0 20px; width:100%; background: #881337; border-radius: 9999px !important; color: white !important; font-weight: 800; border: none; cursor: pointer; white-space: nowrap !important;">تطبيق الفلترة</button>
                    <a href="<?php echo home_url('/lesson-prep'); ?>" class="sm-btn sm-btn-outline" style="height: 38px; font-size:12px; padding:0 16px; border-radius: 9999px !important; border: 1px solid #cbd5e1; color: #475569; display:flex; align-items:center; justify-content:center; text-decoration:none; font-weight:700; white-space: nowrap !important;">إعادة ضبط</a>
                </div>
            </form>

            <!-- Table of Submissions -->
            <div class="sm-table-container" style="overflow-x: auto;">
                <table class="sm-table" style="min-width: 800px;">
                    <thead>
                        <tr>
                            <th style="width: 30px; text-align: center;"><input type="checkbox" onclick="eessToggleAllPrepCheckboxes(this)" title="تحديد الكل"></th>
                            <th>التاريخ</th>
                            <?php if ($can_review): ?>
                                <th>المعلم</th>
                            <?php endif; ?>
                            <th>العنوان / المادة</th>
                            <th>الصف / الشعبة</th>
                            <th>النسخة</th>
                            <th>التأخير</th>
                            <th>الحالة</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $query = "SELECT p.*, u.display_name as teacher_name
                                  FROM {$wpdb->prefix}sm_lesson_preps p
                                  JOIN {$wpdb->prefix}users u ON p.teacher_id = u.ID";

                        $conditions = array();
                        $params = array();

                        if (!$can_review) {
                            $conditions[] = "p.teacher_id = %d";
                            $params[] = $user_id;
                        } elseif ($is_hod && !$is_admin && !$is_sys_admin) {
                            $hod_subject = get_user_meta($user_id, 'sm_specialization', true);
                            $hod_scope = EESS_Org_Helper::get_user_scope($user_id);
                            $hod_schools = !empty($hod_scope['schools']) ? $hod_scope['schools'] : array();

                            if (!empty($hod_subject)) {
                                $conditions[] = "p.subject = %s";
                                $params[] = $hod_subject;
                            }

                            if (!empty($hod_schools)) {
                                $placeholders = implode(',', array_fill(0, count($hod_schools), '%d'));
                                $conditions[] = "p.teacher_id IN (SELECT user_id FROM {$wpdb->prefix}eess_user_assignments WHERE school_id IN ($placeholders))";
                                foreach ($hod_schools as $sch_id) {
                                    $params[] = $sch_id;
                                }
                            }
                        }

                        if (isset($_GET['filter_date']) && !empty($_GET['filter_date'])) {
                            $conditions[] = "p.lesson_date = %s";
                            $params[] = sanitize_text_field($_GET['filter_date']);
                        }

                        if (isset($_GET['filter_status']) && !empty($_GET['filter_status'])) {
                            $conditions[] = "p.status = %s";
                            $params[] = sanitize_text_field($_GET['filter_status']);
                        }

                        if (isset($_GET['s_query']) && !empty($_GET['s_query'])) {
                            $conditions[] = "(p.title LIKE %s OR u.display_name LIKE %s OR p.subject LIKE %s)";
                            $like_param = '%' . $wpdb->esc_like(sanitize_text_field($_GET['s_query'])) . '%';
                            $params[] = $like_param;
                            $params[] = $like_param;
                            $params[] = $like_param;
                        }

                        if (!empty($conditions)) {
                            $query .= " WHERE " . implode(" AND ", $conditions);
                        }

                        $query .= " ORDER BY p.lesson_date DESC, p.created_at DESC";

                        if (!empty($params)) {
                            $submissions = $wpdb->get_results($wpdb->prepare($query, $params));
                        } else {
                            $submissions = $wpdb->get_results($query);
                        }

                        if (empty($submissions)):
                        ?>
                        <tr>
                            <td colspan="<?php echo $can_review ? 9 : 8; ?>" style="text-align: center; color: #94a3b8; padding: 25px; font-size: 13px;">لا توجد خطط تحضير مسجلة حالياً تطابق شروط التصفية.</td>
                        </tr>
                        <?php
                        else:
                            foreach ($submissions as $sub):
                                $delay_desc = 'في الموعد';
                                if ($sub->delay_seconds > 0) {
                                    $days = floor($sub->delay_seconds / 86400);
                                    $hours = floor(($sub->delay_seconds % 86400) / 3600);
                                    $minutes = floor(($sub->delay_seconds % 3600) / 60);

                                    $delay_parts = array();
                                    if ($days > 0) $delay_parts[] = $days . ' يوم';
                                    if ($hours > 0) $delay_parts[] = $hours . ' ساعة';
                                    if ($minutes > 0) $delay_parts[] = $minutes . ' دقيقة';
                                    $delay_desc = implode(' و', $delay_parts);
                                }
                        ?>
                        <tr style="font-size: 12px;" id="prep-row-<?php echo $sub->id; ?>">
                            <td style="text-align: center;"><input type="checkbox" class="eess-prep-cb" value="<?php echo $sub->id; ?>"></td>
                            <td style="font-weight: 700;">
                                <?php echo date_i18n('Y-m-d', strtotime($sub->lesson_date)); ?>
                                <?php
                                $weekday_names = array(
                                    'Sunday' => 'الأحد',
                                    'Monday' => 'الاثنين',
                                    'Tuesday' => 'الثلاثاء',
                                    'Wednesday' => 'الأربعاء',
                                    'Thursday' => 'الخميس',
                                    'Friday' => 'الجمعة',
                                    'Saturday' => 'السبت'
                                );
                                $eng_day = date('l', strtotime($sub->lesson_date));
                                $day_name = $weekday_names[$eng_day] ?? $eng_day;
                                ?>
                                <div style="font-size: 10px; color: #64748b; font-weight: normal; margin-top: 3px;"><?php echo esc_html($day_name); ?></div>
                            </td>
                            <?php if ($can_review): ?>
                                <td>
                                    <?php echo esc_html($sub->teacher_name); ?>
                                    <?php
                                    $teacher_emp_id = get_user_meta($sub->teacher_id, 'eess_employee_number', true);
                                    if (!empty($teacher_emp_id)): ?>
                                        <div style="font-size: 10px; color: #64748b; margin-top: 3px;">رقم الموظف: <?php echo esc_html($teacher_emp_id); ?></div>
                                    <?php endif; ?>
                                </td>
                            <?php endif; ?>
                            <td>
                                <div style="font-weight:700; color:var(--sm-dark-color);"><?php echo esc_html($sub->title); ?></div>
                                <div style="font-size:10px; color:#64748b;"><?php echo esc_html($sub->subject); ?></div>
                            </td>
                            <td>
                                <?php
                                $teacher_school = get_user_meta($sub->teacher_id, 'eess_school_name', true);
                                if (empty($teacher_school)) {
                                    $teacher_school = $school['school_name'] ?? '';
                                }
                                if (!empty($teacher_school)): ?>
                                    <div style="font-size: 10px; color: #0284c7; font-weight: bold; margin-bottom: 3px;"><?php echo esc_html($teacher_school); ?></div>
                                <?php endif; ?>
                                <?php echo esc_html($sub->grade_level . ' (' . $sub->class_section . ')'); ?>
                            </td>
                            <td><span style="font-weight:bold; color: #64748b;"><?php echo $sub->version; ?></span></td>
                            <td>
                                <?php if ($sub->delay_seconds > 0): ?>
                                    <span style="color: #dc2626; font-weight: 700; font-size: 10px;">⚠️ متأخر: <?php echo $delay_desc; ?></span>
                                <?php else: ?>
                                    <span style="color: #16a34a; font-weight: 700; font-size: 10px;">✓ في الموعد</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                $status_labels = array(
                                    'draft' => array('label' => 'مسودة', 'bg' => '#f1f5f9', 'color' => '#475569'),
                                    'submitted' => array('label' => 'بانتظار المراجعة', 'bg' => '#fef9c3', 'color' => '#a16207'),
                                    'approved' => array('label' => 'معتمد', 'bg' => '#dcfce7', 'color' => '#15803d'),
                                    'revision_required' => array('label' => 'طلب تعديل', 'bg' => '#ffedd5', 'color' => '#c2410c'),
                                    'rejected' => array('label' => 'مرفوض', 'bg' => '#fee2e2', 'color' => '#b91c1c'),
                                    'late' => array('label' => 'تسليم متأخر', 'bg' => '#ffedd5', 'color' => '#8b1e1e'),
                                    'resubmitted' => array('label' => 'معدل ومستلم', 'bg' => '#e0f2fe', 'color' => '#0369a1'),
                                );
                                $badge = $status_labels[$sub->status] ?? array('label' => $sub->status, 'bg' => '#f1f5f9', 'color' => '#475569');
                                ?>
                                <span style="display:inline-block; padding:2px 8px; border-radius:50px; font-size:10px; font-weight:bold; background:<?php echo $badge['bg']; ?>; color:<?php echo $badge['color']; ?>;">
                                    <?php echo $badge['label']; ?>
                                </span>
                            </td>
                            <td>
                                <div style="display:flex; gap:6px; align-items:center;">
                                    <!-- View Button (Pastel Slate) -->
                                    <button onclick="smOpenPrepViewer(<?php echo $sub->id; ?>)" class="sm-btn" title="عرض تفاصيل التحضير الكاملة" style="width: 32px; height: 32px; padding: 0; background: #f1f5f9; color: #475569 !important; border-radius: 8px; border: 1px solid #cbd5e1; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s;">
                                        <span class="dashicons dashicons-visibility" style="font-size: 16px; width: 16px; height: 16px; margin: 0; line-height: 1;"></span>
                                    </button>

                                    <!-- Print PDF Button (Pastel Sky Blue) -->
                                    <a href="<?php echo admin_url('admin-ajax.php?action=sm_print&print_type=lesson_prep&prep_id=' . $sub->id); ?>" target="_blank" class="sm-btn" title="طباعة أو تصدير وثيقة PDF المعتمدة" style="width: 32px; height: 32px; padding: 0; background: #e0f2fe; color: #0284c7 !important; border-radius: 8px; border: 1px solid #bae6fd; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; text-decoration: none; transition: all 0.2s;">
                                        <span class="dashicons dashicons-printer" style="font-size: 16px; width: 16px; height: 16px; margin: 0; line-height: 1;"></span>
                                    </a>

                                    <?php if ($can_review && ($sub->status === 'submitted' || $sub->status === 'late' || $sub->status === 'resubmitted')): ?>
                                        <!-- Approve Button (Pastel Emerald Green) -->
                                        <button id="btn-approve-<?php echo $sub->id; ?>" onclick="smQuickApprovePrep(<?php echo $sub->id; ?>)" class="sm-btn" title="اعتماد خطة الدرس فوراً" style="width: 32px; height: 32px; padding: 0; background: #dcfce7; color: #15803d !important; border-radius: 8px; border: 1px solid #bbf7d0; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s;">
                                            <span class="dashicons dashicons-yes-alt" style="font-size: 16px; width: 16px; height: 16px; margin: 0; line-height: 1;"></span>
                                        </button>
                                    <?php endif; ?>

                                    <?php if ($is_teacher && ($sub->status === 'draft' || $sub->status === 'revision_required')): ?>
                                        <!-- Edit Button (Pastel Amber) -->
                                        <a href="<?php echo add_query_arg('edit_prep_id', $sub->id, home_url('/lesson-prep')); ?>" class="sm-btn" title="تعديل وثيقة التحضير" style="width: 32px; height: 32px; padding: 0; background: #fef3c7; color: #b45309 !important; border-radius: 8px; border: 1px solid #fde68a; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; text-decoration: none; transition: all 0.2s;">
                                            <span class="dashicons dashicons-edit" style="font-size: 16px; width: 16px; height: 16px; margin: 0; line-height: 1;"></span>
                                        </a>
                                    <?php endif; ?>

                                    <!-- Delete Button (Pastel Red - Distinct Destructive Treatment) -->
                                    <button onclick="smOpenDeletePrepModal(<?php echo $sub->id; ?>, '<?php echo esc_js($sub->title); ?>')" class="sm-btn" title="حذف التحضير نهائياً" style="width: 32px; height: 32px; padding: 0; background: #fee2e2; color: #dc2626 !important; border-radius: 8px; border: 1px solid #fca5a5; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s;">
                                        <span class="dashicons dashicons-trash" style="font-size: 16px; width: 16px; height: 16px; margin: 0; line-height: 1; color: #dc2626;"></span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php
                            endforeach;
                        endif;
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <!-- Administrative Settings Overlay Modal -->
    <?php if ($is_admin || $is_sys_admin || $is_principal || $is_supervisor): ?>
    <div id="prep-settings-modal" class="sm-modal-overlay" style="display: none; position: fixed; inset: 0; width:100vw; height:100vh; background:rgba(0,0,0,0.5); z-index:999999; justify-content:center; align-items:center; backdrop-filter: blur(2px);">
        <div class="sm-modal-content" style="background:#fff; max-width: 650px; width:100%; border-radius:12px; padding:25px; box-shadow:0 10px 25px rgba(0,0,0,0.2); max-height: 90vh; overflow-y: auto;">
            <div class="sm-modal-header" style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #eee; padding-bottom:15px; margin-bottom:20px;">
                <h3 style="margin:0; font-weight:800; color:var(--sm-primary-color); display:flex; align-items:center; gap:8px; font-size: 15px;">
                    <span class="dashicons dashicons-admin-generic" style="font-size: 20px; width: 20px; height: 20px; margin: 0;"></span>
                    إعدادات وجدولة تسليم التحضيرات
                </h3>
                <button type="button" onclick="document.getElementById('prep-settings-modal').style.display='none'" class="sm-modal-close" style="width:32px; height:32px; border-radius:50%; border:none; cursor:pointer; background:#f1f5f9; display: flex; align-items: center; justify-content: center; font-size: 18px;">&times;</button>
            </div>
            <div class="sm-modal-body" style="text-align:right;">
                <form method="post">
                    <?php wp_nonce_field('eess_settings_action', 'eess_settings_nonce'); ?>

                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px; margin-bottom: 20px;">

                        <div>
                            <label class="sm-label" style="font-weight: 700; font-size: 12px;">دورية التسليم الرسمية</label>
                            <select name="submission_frequency" class="sm-select" style="height: 38px; font-size: 12px;">
                                <option value="daily" <?php selected(($prep_settings['submission_frequency'] ?? 'daily') === 'daily'); ?>>تسليم وثيقة تحضير يومية</option>
                                <option value="weekly" <?php selected(($prep_settings['submission_frequency'] ?? 'daily') === 'weekly'); ?>>تسليم وثيقة تحضير أسبوعية</option>
                            </select>
                        </div>

                        <div>
                            <label class="sm-label" style="font-weight: 700; font-size: 12px;">موعد الإغلاق اليومي واستحقاق التأخير</label>
                            <input type="time" name="submission_deadline" value="<?php echo esc_attr($prep_settings['submission_deadline'] ?? '10:00'); ?>" class="sm-input" style="height: 38px; font-size: 12px;">
                        </div>

                        <div>
                            <label class="sm-label" style="font-weight: 700; font-size: 12px;">استثناءات مادة التربية الرياضية</label>
                            <select name="pe_monday_only" class="sm-select" style="height: 38px; font-size: 12px;">
                                <option value="yes" <?php selected(($prep_settings['pe_monday_only'] ?? 'yes') === 'yes'); ?>>نعم - تحضير الاثنين فقط لمعلمي الرياضة</option>
                                <option value="no" <?php selected(($prep_settings['pe_monday_only'] ?? 'yes') === 'no'); ?>>لا - يعامل كباقي المواد</option>
                            </select>
                        </div>

                        <div>
                            <label class="sm-label" style="font-weight: 700; font-size: 12px;">مواد مستثناة أخرى (فصل بينها بفاصلة)</label>
                            <input type="text" name="subject_exceptions" value="<?php echo esc_attr($prep_settings['subject_exceptions'] ?? ''); ?>" class="sm-input" placeholder="مثال: الموسيقى، الفنون" style="height: 38px; font-size: 12px;">
                        </div>

                        <div>
                            <label class="sm-label" style="font-weight: 700; font-size: 12px;">فترة التذكير قبل الإغلاق</label>
                            <select name="reminder_intervals" class="sm-select" style="height: 38px; font-size: 12px;">
                                <option value="none" <?php selected(($prep_settings['reminder_intervals'] ?? '') === 'none'); ?>>إيقاف التذكير</option>
                                <option value="30min" <?php selected(($prep_settings['reminder_intervals'] ?? '') === '30min'); ?>>قبل نصف ساعة</option>
                                <option value="1hour" <?php selected(($prep_settings['reminder_intervals'] ?? '1hour') === '1hour'); ?>>قبل ساعة واحدة</option>
                                <option value="2hours" <?php selected(($prep_settings['reminder_intervals'] ?? '') === '2hours'); ?>>قبل ساعتين</option>
                            </select>
                        </div>

                        <div>
                            <label class="sm-label" style="font-weight: 700; font-size: 12px;">صلاحية التعديل القصيرة (عدد المرات)</label>
                            <select name="revision_limits" class="sm-select" style="height: 38px; font-size: 12px;">
                                <option value="0" <?php selected(($prep_settings['revision_limits'] ?? '0') === '0'); ?>>مفتوح (لا يوجد قيود)</option>
                                <option value="1" <?php selected(($prep_settings['revision_limits'] ?? '') === '1'); ?>>مرة واحدة كحد أقصى</option>
                                <option value="2" <?php selected(($prep_settings['revision_limits'] ?? '') === '2'); ?>>مرتين كحد أقصى</option>
                                <option value="3" <?php selected(($prep_settings['revision_limits'] ?? '') === '3'); ?>>3 مرات كحد أقصى</option>
                            </select>
                        </div>

                        <div>
                            <label class="sm-label" style="font-weight: 700; font-size: 12px;">منهجية مسار الاعتماد والمراجعة</label>
                            <select name="approval_workflow" class="sm-select" style="height: 38px; font-size: 12px;">
                                <option value="single" <?php selected(($prep_settings['approval_workflow'] ?? 'single') === 'single'); ?>>اعتماد بخطوة واحدة (المشرف المباشر)</option>
                                <option value="multi" <?php selected(($prep_settings['approval_workflow'] ?? '') === 'multi'); ?>>اعتماد متعدد الخطوات (المنسق ثم المشرف)</option>
                            </select>
                        </div>

                        <div>
                            <label class="sm-label" style="font-weight: 700; font-size: 12px;">تخصيص وإدارة القالب الافتراضي</label>
                            <select name="template_mgmt" class="sm-select" style="height: 38px; font-size: 12px;">
                                <option value="default" <?php selected(($prep_settings['template_mgmt'] ?? 'default') === 'default'); ?>>قالب تحضير مقسم (6 أقسام)</option>
                                <option value="compact" <?php selected(($prep_settings['template_mgmt'] ?? '') === 'compact'); ?>>قالب مختصر مبسط</option>
                                <option value="detailed" <?php selected(($prep_settings['template_mgmt'] ?? '') === 'detailed'); ?>>قالب متقدم مع مخرجات التعلم</option>
                            </select>
                        </div>

                        <div>
                            <label class="sm-label" style="font-weight: 700; font-size: 12px;">التحديث التلقائي للحالة بعد الإغلاق</label>
                            <select name="auto_status_updates" class="sm-select" style="height: 38px; font-size: 12px;">
                                <option value="yes" <?php selected(($prep_settings['auto_status_updates'] ?? 'yes') === 'yes'); ?>>نعم - وسم كمتأخر تلقائياً بعد الإغلاق</option>
                                <option value="no" <?php selected(($prep_settings['auto_status_updates'] ?? 'yes') === 'no'); ?>>لا - إبقاء الحالة دون تغيير تلقائي</option>
                            </select>
                        </div>

                        <div>
                            <label class="sm-label" style="font-weight: 700; font-size: 12px;">إجراءات وقواعد التسليمات المتأخرة</label>
                            <select name="late_submission_rules" class="sm-select" style="height: 38px; font-size: 12px;">
                                <option value="flag" <?php selected(($prep_settings['late_submission_rules'] ?? 'flag') === 'flag'); ?>>وضع علامة حمراء وتنبيه للمشرف</option>
                                <option value="deduct" <?php selected(($prep_settings['late_submission_rules'] ?? '') === 'deduct'); ?>>وضع علامة وخصم من درجات التقييم</option>
                                <option value="block" <?php selected(($prep_settings['late_submission_rules'] ?? '') === 'block'); ?>>منع وحظر التسليم المتأخر تماماً</option>
                            </select>
                        </div>

                        <div>
                            <label class="sm-label" style="font-weight: 700; font-size: 12px;">التزامن والتكامل مع التقويم الأكاديمي</label>
                            <select name="calendar_integration" class="sm-select" style="height: 38px; font-size: 12px;">
                                <option value="no" <?php selected(($prep_settings['calendar_integration'] ?? 'no') === 'no'); ?>>إيقاف المزامنة</option>
                                <option value="yes" <?php selected(($prep_settings['calendar_integration'] ?? 'no') === 'yes'); ?>>مزامنة تلقائية مع عطلات التقويم الرسمية</option>
                            </select>
                        </div>

                        <!-- Notification Preferences Checkboxes -->
                        <div>
                            <label class="sm-label" style="font-weight: 700; font-size: 12px;">قنوات استلام تنبيهات وإشعارات التحضير</label>
                            <div style="display:flex; flex-direction: column; gap:5px; background:#f8fafc; padding:8px; border-radius:6px; border:1px solid #cbd5e1; font-size: 11px;">
                                <label style="display:inline-flex; align-items:center; gap:5px; cursor:pointer;">
                                    <input type="checkbox" name="notification_prefs[]" value="email" <?php checked(in_array('email', $prep_settings['notification_prefs'] ?? array())); ?>> بريد إلكتروني رسمي
                                </label>
                                <label style="display:inline-flex; align-items:center; gap:5px; cursor:pointer;">
                                    <input type="checkbox" name="notification_prefs[]" value="system" <?php checked(in_array('system', $prep_settings['notification_prefs'] ?? array())); ?>> إشعار داخلي بالنظام
                                </label>
                                <label style="display:inline-flex; align-items:center; gap:5px; cursor:pointer;">
                                    <input type="checkbox" name="notification_prefs[]" value="whatsapp" <?php checked(in_array('whatsapp', $prep_settings['notification_prefs'] ?? array())); ?>> رسائل واتساب نصية
                                </label>
                            </div>
                        </div>

                        <div style="grid-column: span 2;">
                            <label class="sm-label" style="font-weight: 700; font-size: 12px;">أيام العمل والتحضير الأسبوعية المعتمدة</label>
                            <div style="display:flex; gap:12px; flex-wrap:wrap; background:#f8fafc; padding:10px; border-radius:6px; border:1px solid #cbd5e1;">
                                <?php
                                $days_list = array('sun' => 'الأحد', 'mon' => 'الاثنين', 'tue' => 'الثلاثاء', 'wed' => 'الأربعاء', 'thu' => 'الخميس');
                                foreach ($days_list as $key => $lbl): ?>
                                    <label style="font-size:11px; display:inline-flex; align-items:center; gap:5px; cursor:pointer;">
                                        <input type="checkbox" name="working_days[]" value="<?php echo $key; ?>" <?php checked(in_array($key, $prep_settings['working_days'] ?? array())); ?>> <?php echo $lbl; ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                    </div>
                    <div style="display: flex; gap: 10px; justify-content: flex-end;">
                        <button type="submit" name="eess_save_prep_settings" class="sm-btn" style="width: auto; background: var(--sm-primary-color); height: 36px; padding: 0 20px; font-weight: bold; font-size: 12px;">حفظ وتطبيق هذه الإعدادات</button>
                        <button type="button" onclick="document.getElementById('prep-settings-modal').style.display='none'" class="sm-btn sm-btn-outline" style="width: auto; height: 36px; padding: 0 15px; font-size: 12px;">إلغاء</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

</div>

<!-- Document Viewer Modal -->
<div id="prep-viewer-modal" class="sm-modal-overlay" style="display: none; position: fixed; inset:0; width:100vw; height:100vh; background:rgba(0,0,0,0.5); z-index:999999; justify-content:center; align-items:center; backdrop-filter: blur(2px);">
    <div class="sm-modal-content" style="background:#fff; max-width: 750px; width:100%; border-radius:12px; padding:25px; box-shadow:0 10px 25px rgba(0,0,0,0.2); max-height: 90vh; overflow-y: auto;">
        <div class="sm-modal-header" style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #eee; padding-bottom:15px; margin-bottom:20px;">
            <h3 id="view-modal-title" style="margin:0; font-weight:800; color:var(--sm-primary-color); font-size: 15px;">عنوان التحضير</h3>
            <button onclick="document.getElementById('prep-viewer-modal').style.display='none'" class="sm-modal-close" style="width:32px; height:32px; border-radius:50%; border:none; cursor:pointer; background:#f1f5f9; display: flex; align-items: center; justify-content: center; font-size: 18px;">&times;</button>
        </div>
        <div class="sm-modal-body" id="prep-viewer-body" style="line-height: 1.6; font-size:13px; text-align:right;">
            <!-- Rendered dynamically -->
        </div>
    </div>
</div>

<!-- In-System Custom Confirmation Modal for Deleting Lesson Prep -->
<div id="eess-delete-prep-modal" class="sm-modal-overlay" style="display: none; position: fixed; inset: 0; width: 100vw; height: 100vh; background: rgba(15, 23, 42, 0.75); backdrop-filter: blur(5px); z-index: 999999; justify-content: center; align-items: center; padding: 20px; box-sizing: border-box; font-family: 'Cairo', sans-serif;">
    <div class="sm-modal-content" style="background: #ffffff; border-radius: 20px; max-width: 480px; width: 100%; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.3); border: 1px solid #cbd5e1; display: flex; flex-direction: column;">
        <div style="background: #0f172a; color: #ffffff; padding: 18px 22px; display: flex; justify-content: space-between; align-items: center; width: 100%; box-sizing: border-box;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <span class="dashicons dashicons-trash" style="color: #ef4444; font-size: 22px; width: 22px; height: 22px;"></span>
                <h3 style="margin: 0; font-size: 15px; font-weight: 800; color: #ffffff;">تأكيد حذف وثيقة تحضير الدرس</h3>
            </div>
            <button type="button" onclick="document.getElementById('eess-delete-prep-modal').style.display='none'" style="background: none; border: none; color: #ffffff; font-size: 24px; cursor: pointer; line-height: 1;">&times;</button>
        </div>
        <div style="padding: 22px; text-align: right;">
            <p style="margin: 0 0 12px 0; font-size: 13px; color: #334155; line-height: 1.6;">هل أنت متأكد من رغبتك في حذف وثيقة التحضير التالية نهائياً؟</p>
            <div style="background: #fef2f2; border: 1px solid #fecdd3; border-radius: 10px; padding: 12px 16px; margin-bottom: 20px; font-weight: 800; font-size: 13.5px; color: #991b1b;" id="eess_delete_prep_title_display">
                <!-- Title filled dynamically -->
            </div>
            <input type="hidden" id="eess_delete_prep_target_id" value="0">
            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <button type="button" onclick="document.getElementById('eess-delete-prep-modal').style.display='none'" class="sm-btn sm-btn-outline" style="height: 40px; padding: 0 20px; border-radius: 9999px !important; font-size: 12.5px; font-weight: 700; color: #475569;">إلغاء</button>
                <button type="button" id="eess-btn-confirm-delete-prep" onclick="eessExecuteConfirmDeletePrep()" class="sm-btn" style="height: 40px; padding: 0 24px; border-radius: 9999px !important; font-size: 12.5px; background: #dc2626; color: #ffffff !important; font-weight: 800; border: none; cursor: pointer;">تأكيد الحذف النهائي</button>
            </div>
        </div>
    </div>
</div>

<!-- Supervisor Review Action Modal -->
<div id="prep-review-modal" class="sm-modal-overlay" style="display: none; position: fixed; inset:0; width:100vw; height:100vh; background:rgba(0,0,0,0.5); z-index:999999; justify-content:center; align-items:center; backdrop-filter: blur(2px);">
    <div class="sm-modal-content" style="background:#fff; max-width: 550px; width:100%; border-radius:12px; padding:25px; box-shadow:0 10px 25px rgba(0,0,0,0.2);">
        <div class="sm-modal-header" style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #eee; padding-bottom:15px; margin-bottom:20px;">
            <h3 style="margin:0; font-weight:800; font-size: 15px;">مراجعة واعتماد وثيقة التحضير</h3>
            <button onclick="document.getElementById('prep-review-modal').style.display='none'" class="sm-modal-close" style="width:32px; height:32px; border-radius:50%; border:none; cursor:pointer; background:#f1f5f9; display: flex; align-items: center; justify-content: center; font-size: 18px;">&times;</button>
        </div>
        <div class="sm-modal-body" style="text-align:right;">
            <form method="post">
                <?php wp_nonce_field('eess_supervisor_action', 'eess_supervisor_nonce'); ?>
                <input type="hidden" name="prep_id" id="review-prep-id">

                <div style="margin-bottom: 12px;">
                    <label class="sm-label" style="font-weight: 700; font-size: 12px;">اسم التحضير المختار</label>
                    <input type="text" id="review-prep-title" class="sm-input" readonly style="background:#f1f5f9; color:#475569; height: 38px; font-size: 12px;">
                </div>

                <div style="margin-bottom: 12px;">
                    <label class="sm-label" style="font-weight: 700; font-size: 12px;">القرار النهائي والاعتماد</label>
                    <select name="prep_status_action" class="sm-select" required style="height: 38px; font-size: 12px;">
                        <option value="approved">✓ اعتماد وإجازة التحضير (معتمد)</option>
                        <option value="revision_required">⚠ طلب مراجعة وتعديل (تعديل مطلوب)</option>
                        <option value="rejected">✗ رفض وإلغاء وثيقة التحضير (مرفوض)</option>
                    </select>
                </div>

                <div style="margin-bottom: 15px;">
                    <label class="sm-label" style="font-weight: 700; font-size: 12px;">الملاحظات، التوصيات والتوجيهات الفنية</label>
                    <textarea name="supervisor_comment" class="sm-input" style="height: 80px; font-size: 12px;" placeholder="أدخل ملحوظاتك الفنية وتوجيهاتك للمعلم..."></textarea>
                </div>

                <button type="submit" name="eess_supervisor_action" class="sm-btn" style="background:#16a34a; width: 100%; height: 38px; font-weight: bold; font-size: 13px;">تطبيق القرار وحفظ الملاحظات</button>
            </form>
        </div>
    </div>
</div>

<script>
let eessActivePrepStage = 1;

function eessGoToPrepStage(stageNum) {
    if (stageNum < 1 || stageNum > 5) return;

    // Live validation before proceeding
    if (stageNum > eessActivePrepStage) {
        if (eessActivePrepStage === 1) {
            const title = document.getElementById('eess_lesson_title').value.trim();
            const subject = document.getElementById('eess_lesson_subject').value.trim();
            const grade = document.getElementById('eess_lesson_grade').value.trim();
            const section = document.getElementById('eess_lesson_section').value.trim();
            const date = document.getElementById('eess_lesson_date').value.trim();
            if (!title || !subject || !grade || !section || !date) {
                alert('يرجى إكمال كافة البيانات العامة المطلوبة المميزة بنجمة (*)');
                return;
            }
        } else if (eessActivePrepStage === 2) {
            const objectives = document.getElementById('eess_objectives').value.trim();
            const warmup = document.getElementById('eess_warmup').value.trim();
            if (!objectives || !warmup) {
                alert('الأهداف السلوكية والتمهيد حقول إلزامية للتحضير (*)');
                return;
            }
        } else if (eessActivePrepStage === 3) {
            const activities = document.getElementById('eess_activities').value.trim();
            const evaluation = document.getElementById('eess_evaluation').value.trim();
            if (!activities || !evaluation) {
                alert('الأنشطة والتقويم الصفي حقول إلزامية للتحضير (*)');
                return;
            }
        }
    }

    eessActivePrepStage = stageNum;

    // Show active stage, hide others
    document.querySelectorAll('.eess-prep-wizard-stage').forEach((el, idx) => {
        el.style.display = (idx + 1 === stageNum) ? 'block' : 'none';
    });

    // Update progress indicator styling
    document.querySelectorAll('.eess-prep-step-indicator').forEach((el, idx) => {
        const stepNum = idx + 1;
        const span = el.querySelector('span');
        if (stepNum === stageNum) {
            el.style.color = 'var(--sm-primary-color)';
            if (span) span.style.background = 'var(--sm-primary-color)';
            if (span) span.style.color = 'white';
        } else if (stepNum < stageNum) {
            el.style.color = '#15803d';
            if (span) span.style.background = '#dcfce7';
            if (span) span.style.color = '#15803d';
        } else {
            el.style.color = '#94a3b8';
            if (span) span.style.background = '#e2e8f0';
            if (span) span.style.color = '#475569';
        }
    });

    // Toggle Back/Next/Submit button displays
    document.getElementById('eess-prep-prev-btn').style.display = (stageNum > 1) ? 'inline-block' : 'none';
    if (stageNum === 5) {
        document.getElementById('eess-prep-next-btn').style.display = 'none';
        document.getElementById('eess-prep-submit-btn').style.display = 'inline-block';
        document.getElementById('eess-prep-draft-btn').style.display = 'inline-block';

        // Render live summary of all details
        const summaryEl = document.getElementById('eess-prep-review-live-summary');
        if (summaryEl) {
            const isPeSubject = <?php echo $is_pe_subject ? 'true' : 'false'; ?>;
            const label1 = isPeSubject ? 'الإعداد البدني (Physical Prep):' : 'الأهداف السلوكية والتعليمية:';
            const label2 = isPeSubject ? 'الإعداد المهاري (Skill Prep):' : 'التمهيد والتهيئة الحافزة:';
            const label3 = isPeSubject ? 'النشاط الرئيسي/العملي (Practical Activity):' : 'الاستراتيجيات والأنشطة والخطوات:';
            const label4 = isPeSubject ? 'الخاتمة والتهدئة (Cool-down & Closing):' : 'التقويم الصفي وأدوات القياس:';
            const label5 = isPeSubject ? 'الواجبات أو التكليفات البدنية:' : 'الواجبات المنزلية والمهام الأكاديمية:';
            const label6 = isPeSubject ? 'توجيهات الأمن والسلامة والملاحظات:' : 'ملاحظات وإرشادات وتأملات مهنية:';

            summaryEl.innerHTML = `
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:12px; border-bottom:1px solid #cbd5e1; padding-bottom:10px; margin-bottom:10px;">
                    <div><strong>عنوان الدرس:</strong> ${document.getElementById('eess_lesson_title').value}</div>
                    <div><strong>المادة:</strong> ${document.getElementById('eess_lesson_subject').value}</div>
                    <div><strong>الصف:</strong> ${document.getElementById('eess_lesson_grade').value}</div>
                    <div><strong>الشعبة:</strong> ${document.getElementById('eess_lesson_section').value}</div>
                    <div><strong>التاريخ:</strong> ${document.getElementById('eess_lesson_date').value}</div>
                </div>
                <div style="margin-bottom:10px;"><strong>1. ${label1}</strong><p style="margin:5px 0 0 0; color:#475569;">${document.getElementById('eess_objectives').value.replace(/\n/g, '<br>')}</p></div>
                <div style="margin-bottom:10px;"><strong>2. ${label2}</strong><p style="margin:5px 0 0 0; color:#475569;">${document.getElementById('eess_warmup').value.replace(/\n/g, '<br>')}</p></div>
                <div style="margin-bottom:10px;"><strong>3. ${label3}</strong><p style="margin:5px 0 0 0; color:#475569;">${document.getElementById('eess_activities').value.replace(/\n/g, '<br>')}</p></div>
                <div style="margin-bottom:10px;"><strong>4. ${label4}</strong><p style="margin:5px 0 0 0; color:#475569;">${document.getElementById('eess_evaluation').value.replace(/\n/g, '<br>')}</p></div>
                <div style="margin-bottom:10px;"><strong>5. ${label5}</strong><p style="margin:5px 0 0 0; color:#475569;">${document.getElementById('eess_homework').value.replace(/\n/g, '<br>')}</p></div>
                <div><strong>6. ${label6}</strong><p style="margin:5px 0 0 0; color:#475569;">${document.getElementById('eess_notes').value.replace(/\n/g, '<br>')}</p></div>
            `;
        }
    } else {
        document.getElementById('eess-prep-next-btn').style.display = 'inline-block';
        document.getElementById('eess-prep-submit-btn').style.display = 'none';
        document.getElementById('eess-prep-draft-btn').style.display = 'none';
    }
}

const eessSubmissions = <?php
    $preps_for_js = array();
    if (!empty($submissions)) {
        foreach ($submissions as $sub) {
            $parsed_data = json_decode($sub->lesson_data, true) ?: array();
            $comments = $wpdb->get_results($wpdb->prepare("SELECT c.*, u.display_name FROM {$wpdb->prefix}sm_lesson_comments c JOIN {$wpdb->prefix}users u ON c.user_id = u.ID WHERE c.prep_id = %d ORDER BY c.created_at ASC", $sub->id));
            $comments_array = array();
            if (!empty($comments)) {
                foreach ($comments as $com) {
                    $comments_array[] = array(
                        'author' => $com->display_name,
                        'text' => $com->comment_text,
                        'date' => date_i18n('Y-m-d H:i', strtotime($com->created_at))
                    );
                }
            }

            $preps_for_js[$sub->id] = array(
                'title' => $sub->title,
                'subject' => $sub->subject,
                'grade' => $sub->grade_level,
                'section' => $sub->class_section,
                'date' => $sub->lesson_date,
                'objectives' => $parsed_data['objectives'] ?? '',
                'warmup' => $parsed_data['warmup'] ?? '',
                'activities' => $parsed_data['activities'] ?? '',
                'evaluation' => $parsed_data['evaluation'] ?? '',
                'homework' => $parsed_data['homework'] ?? '',
                'notes' => $parsed_data['notes'] ?? '',
                'comments' => $comments_array
            );
        }
    }
    echo json_encode($preps_for_js);
?>;

function smOpenPrepViewer(id) {
    const data = eessSubmissions[id];
    if (!data) return;

    document.getElementById('view-modal-title').innerText = data.title;

    const subLower = data.subject.toLowerCase();
    const isPe = (subLower.indexOf('رياضية') !== -1 || subLower.indexOf('بدنية') !== -1 || subLower.indexOf('pe') !== -1 || subLower.indexOf('physical') !== -1 || subLower.indexOf('health') !== -1);

    const label1 = isPe ? 'الإعداد البدني (Physical Prep)' : 'الأهداف السلوكية والتعليمية';
    const label2 = isPe ? 'الإعداد المهاري (Skill Prep)' : 'التمهيد والتهيئة الحافزة';
    const label3 = isPe ? 'النشاط الرئيسي/العملي (Main/Practical Activity)' : 'الاستراتيجيات والأنشطة والخطوات التعليمية الاستراتيجية';
    const label4 = isPe ? 'الخاتمة والتهدئة (Cool-down & Closing)' : 'التقويم الصفي وأدوات القياس';
    const label5 = isPe ? 'الواجبات أو التكليفات البدنية المقررة' : 'الواجبات المنزلية والمهام الأكاديمية';
    const label6 = isPe ? 'توجيهات الأمن والسلامة والملاحظات' : 'ملاحظات تربوية وتأملات إضافية';

    let html = `
        <div style="background:#f8fafc; padding: 12px; border-radius: 8px; border:1px solid #e2e8f0; margin-bottom:15px; display:grid; grid-template-columns: 1fr 1fr; gap:10px; font-size: 12px;">
            <div><strong>المادة:</strong> ${data.subject}</div>
            <div><strong>الصف الدراسي:</strong> ${data.grade} (${data.section})</div>
            <div><strong>تاريخ الدرس:</strong> ${data.date}</div>
        </div>
        <div style="margin-bottom: 12px; border-right: 3px solid var(--sm-primary-color); padding-right:10px;">
            <h4 style="margin:0 0 3px 0; color:var(--sm-primary-color); font-size:12px; font-weight:800;">${label1}</h4>
            <p style="margin:0; font-size:12px;">${data.objectives.replace(/\n/g, '<br>')}</p>
        </div>
        <div style="margin-bottom: 12px; border-right: 3px solid var(--sm-secondary-color); padding-right:10px;">
            <h4 style="margin:0 0 3px 0; color:var(--sm-secondary-color); font-size:12px; font-weight:800;">${label2}</h4>
            <p style="margin:0; font-size:12px;">${data.warmup.replace(/\n/g, '<br>')}</p>
        </div>
        <div style="margin-bottom: 12px; border-right: 3px solid var(--sm-accent-color); padding-right:10px;">
            <h4 style="margin:0 0 3px 0; color:var(--sm-accent-color); font-size:12px; font-weight:800;">${label3}</h4>
            <p style="margin:0; font-size:12px;">${data.activities.replace(/\n/g, '<br>')}</p>
        </div>
        <div style="margin-bottom: 12px; border-right: 3px solid var(--sm-dark-color); padding-right:10px;">
            <h4 style="margin:0 0 3px 0; color:var(--sm-dark-color); font-size:12px; font-weight:800;">${label4}</h4>
            <p style="margin:0; font-size:12px;">${data.evaluation.replace(/\n/g, '<br>')}</p>
        </div>
        <div style="margin-bottom: 12px; border-right: 3px solid #8b1e1e; padding-right:10px;">
            <h4 style="margin:0 0 3px 0; color:#8b1e1e; font-size:12px; font-weight:800;">${label5}</h4>
            <p style="margin:0; font-size:12px;">${data.homework ? data.homework.replace(/\n/g, '<br>') : 'لا يوجد واجب صفي مقرر'}</p>
        </div>
        <div style="margin-bottom: 12px; border-right: 3px solid #64748b; padding-right:10px;">
            <h4 style="margin:0 0 3px 0; color:#64748b; font-size:12px; font-weight:800;">${label6}</h4>
            <p style="margin:0; font-size:12px;">${data.notes ? data.notes.replace(/\n/g, '<br>') : 'لا توجد ملاحظات إضافية'}</p>
        </div>
    `;

    if (data.comments && data.comments.length > 0) {
        html += `
            <div style="margin-top: 20px; padding-top: 12px; border-top: 2px dashed #e2e8f0;">
                <h4 style="margin: 0 0 10px 0; color:#dc2626; font-size:12px; font-weight:800;">سجل التوجيهات والملاحظات من المشرفين</h4>
                <div style="display:flex; flex-direction:column; gap:8px;">
                    ${data.comments.map(c => `
                        <div style="background:#fff5f5; border:1px solid #fca5a5; padding:10px; border-radius:6px;">
                            <div style="display:flex; justify-content:space-between; font-size:10px; color:#c53030; font-weight:800; margin-bottom:3px;">
                                <span>المشرف الفني: ${c.author}</span>
                                <span>${c.date}</span>
                            </div>
                            <p style="margin:0; font-size:11px; color:#991b1b; line-height:1.5;">${c.text.replace(/\n/g, '<br>')}</p>
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
    }

    document.getElementById('prep-viewer-body').innerHTML = html;
    document.getElementById('prep-viewer-modal').style.display = 'flex';
}

function smOpenReviewModal(id, title) {
    document.getElementById('review-prep-id').value = id;
    document.getElementById('review-prep-title').value = title;
    document.getElementById('prep-review-modal').style.display = 'flex';
}

// Reports Dropdown and Viewer Logic
function eessTogglePrepReportsDropdown(event) {
    event.stopPropagation();
    const dropdown = document.getElementById('eess-prep-reports-dropdown');
    dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
}

// Close dropdown when clicking outside
window.addEventListener('click', function() {
    const dropdown = document.getElementById('eess-prep-reports-dropdown');
    if (dropdown) {
        dropdown.style.display = 'none';
    }
});

    window.eessShowComplianceStatDetails = function(statKey) {
        let typeMapping = {
            'required': 'submitted',
            'submitted': 'submitted',
            'pending': 'submitted',
            'approved': 'ranking',
            'revision_required': 'not_submitted',
            'late': 'late_stats'
        };
        const mappedType = typeMapping[statKey] || 'submitted';
        eessShowPrepReport(mappedType);
    };

function eessShowPrepReport(type) {
    // Hide all report sections inside modal
    document.querySelectorAll('.eess-report-section').forEach(el => el.style.display = 'none');

    // Show active report section
    const targetSection = document.getElementById('rep-' + type);
    if (targetSection) {
        targetSection.style.display = 'block';
    }

    // Open the report viewer modal
    document.getElementById('eess-prep-report-modal').style.display = 'flex';
}

function eessExportPrepReport() {
    let csvContent = "data:text/csv;charset=utf-8,\uFEFF";
    const activeSection = document.querySelector('.eess-report-section[style*="display: block"]');
    if (!activeSection) {
        alert("يرجى عرض تقرير أولاً قبل الضغط على التصدير.");
        return;
    }
    const table = activeSection.querySelector('table');
    if (!table) {
        alert("هذا التقرير لا يحتوي على جدول بيانات لتصديره.");
        return;
    }

    const rows = table.querySelectorAll('tr');
    rows.forEach(function(row) {
        const cols = row.querySelectorAll('th, td');
        const rowData = [];
        cols.forEach(function(col) {
            rowData.push('"' + col.innerText.replace(/"/g, '""') + '"');
        });
        csvContent += rowData.join(",") + "\r\n";
    });

    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", "report_" + typeOfActiveReport() + "_" + new Date().toISOString().slice(0,10) + ".csv");
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

function typeOfActiveReport() {
    const activeSection = document.querySelector('.eess-report-section[style*="display: block"]');
    return activeSection ? activeSection.id : 'lesson_prep';
}

window.smQuickApprovePrep = function(prepId) {
    if (!prepId) return;
    var btn = document.getElementById('btn-approve-' + prepId);
    if (btn) btn.disabled = true;

    var formData = new FormData();
    formData.append('action', 'eess_quick_approve_prep');
    formData.append('prep_id', prepId);
    formData.append('sm_nonce', '<?php echo wp_create_nonce("eess_lesson_prep_action"); ?>');

    fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            if (typeof smShowNotification === 'function') {
                smShowNotification('تم اعتماد خطة الدرس بنجاح');
            } else {
                alert('تم اعتماد خطة الدرس بنجاح');
            }
            var row = document.getElementById('prep-row-' + prepId);
            if (row) {
                var badgeCell = row.cells[7];
                if (badgeCell) {
                    badgeCell.innerHTML = '<span style="display:inline-block; padding:2px 8px; border-radius:50px; font-size:10px; font-weight:bold; background:#dcfce7; color:#15803d;">معتمد</span>';
                }
            }
            if (btn) btn.style.display = 'none';

            // Decrement Pending Review counter in real time
            var statCounter = document.getElementById('eess-pending-review-stat-counter');
            if (statCounter) {
                var cur = parseInt(statCounter.innerText) || 0;
                statCounter.innerText = Math.max(0, cur - 1);
            }
        } else {
            alert('خطأ: ' + (res.data || 'فشل اعتماد التحضير.'));
            if (btn) btn.disabled = false;
        }
    })
    .catch(err => {
        alert('حدث خطأ في الاتصال بالخادم.');
        if (btn) btn.disabled = false;
    });
};

window.smOpenDeletePrepModal = function(prepId, title) {
    if (!prepId) return;
    document.getElementById('eess_delete_prep_target_id').value = prepId;
    document.getElementById('eess_delete_prep_title_display').innerText = title || 'وثيقة التحضير';
    document.getElementById('eess-delete-prep-modal').style.display = 'flex';
};

window.eessExecuteConfirmDeletePrep = function() {
    var prepId = document.getElementById('eess_delete_prep_target_id').value;
    if (!prepId || prepId === '0') return;

    var btn = document.getElementById('eess-btn-confirm-delete-prep');
    btn.disabled = true;
    btn.innerText = 'جاري الحذف...';

    var formData = new FormData();
    formData.append('action', 'eess_bulk_lesson_action');
    formData.append('bulk_action', 'delete');
    formData.append('prep_ids[]', prepId);
    formData.append('sm_nonce', '<?php echo wp_create_nonce("eess_lesson_prep_action"); ?>');

    fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
        btn.disabled = false;
        btn.innerText = 'تأكيد الحذف النهائي';
        document.getElementById('eess-delete-prep-modal').style.display = 'none';

        if (res.success) {
            if (typeof smShowNotification === 'function') {
                smShowNotification('تم حذف وثيقة التحضير بنجاح');
            } else {
                alert('تم حذف وثيقة التحضير بنجاح');
            }
            var row = document.getElementById('prep-row-' + prepId);
            if (row) row.remove();
        } else {
            alert('خطأ: ' + (res.data || 'فشل حذف التحضير.'));
        }
    });
};

window.eessToggleAllPrepCheckboxes = function(master) {
    var checkboxes = document.querySelectorAll('.eess-prep-cb');
    checkboxes.forEach(function(cb) {
        cb.checked = master.checked;
    });
};

window.eessExecutePrepBulkAction = function() {
    var actionSelect = document.getElementById('eess-prep-bulk-action');
    var action = actionSelect ? actionSelect.value : '';
    if (!action) {
        alert('يرجى اختيار الإجراء الجماعي المطلوب.');
        return;
    }

    var selectedCbs = document.querySelectorAll('.eess-prep-cb:checked');
    if (selectedCbs.length === 0) {
        alert('يرجى تحديد تحضير واحد على الأقل من الجدول.');
        return;
    }

    if (action === 'delete' && !confirm('هل أنت متأكد من رغبتك في حذف جميع التحضيرات المحددة نهائياً؟')) {
        return;
    }

    var formData = new FormData();
    formData.append('action', 'eess_bulk_lesson_action');
    formData.append('bulk_action', action);
    formData.append('sm_nonce', '<?php echo wp_create_nonce("eess_lesson_prep_action"); ?>');

    selectedCbs.forEach(function(cb) {
        formData.append('prep_ids[]', cb.value);
    });

    fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            alert('✅ ' + res.data.message);
            location.reload();
        } else {
            alert('❌ خطأ: ' + res.data);
        }
    });
};
</script>

<!-- Dynamic Lesson Preparation Reporting & Compliance Modal -->
<?php
if ($is_hod && !$is_admin && !$is_sys_admin) {
    $hod_subject = get_user_meta($user_id, 'sm_specialization', true);
    $prep_report_teachers = get_users(array(
        'role'       => 'sm_teacher',
        'meta_key'   => 'sm_specialization',
        'meta_value' => $hod_subject
    ));
    $prep_report_submitted = $wpdb->get_results($wpdb->prepare(
        "SELECT p.*, u.display_name as teacher_name FROM {$wpdb->prefix}sm_lesson_preps p LEFT JOIN {$wpdb->users} u ON p.teacher_id = u.ID WHERE p.subject = %s AND p.status IN ('submitted', 'approved', 'late') ORDER BY p.id DESC LIMIT 30",
        $hod_subject
    ));
} else {
    $prep_report_teachers = get_users(array('role' => 'sm_teacher'));
    $prep_report_submitted = $wpdb->get_results("SELECT p.*, u.display_name as teacher_name FROM {$wpdb->prefix}sm_lesson_preps p LEFT JOIN {$wpdb->users} u ON p.teacher_id = u.ID WHERE p.status IN ('submitted', 'approved', 'late') ORDER BY p.id DESC LIMIT 30");
}

$prep_report_inst = $wpdb->get_results("SELECT COALESCE((SELECT meta_value FROM {$wpdb->usermeta} WHERE user_id = p.teacher_id AND meta_key = 'eess_school_name'), 'خدمات الأنظمة الإلكترونية التعليمية') as inst, COUNT(*) as cnt FROM {$wpdb->prefix}sm_lesson_preps p GROUP BY inst ORDER BY cnt DESC");
$prep_report_dept = $wpdb->get_results("SELECT COALESCE((SELECT meta_value FROM {$wpdb->usermeta} WHERE user_id = p.teacher_id AND meta_key = 'eess_department'), 'غير محدد') as dept, COUNT(*) as cnt FROM {$wpdb->prefix}sm_lesson_preps p GROUP BY dept ORDER BY cnt DESC");
$prep_report_subject = $wpdb->get_results("SELECT subject as name, COUNT(*) as cnt FROM {$wpdb->prefix}sm_lesson_preps GROUP BY subject ORDER BY cnt DESC");

$prep_report_daily = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sm_lesson_preps WHERE DATE(lesson_date) = CURDATE()") ?: 0;
$prep_report_weekly = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sm_lesson_preps WHERE YEARWEEK(lesson_date, 1) = YEARWEEK(CURDATE(), 1)") ?: 0;
$prep_report_monthly = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sm_lesson_preps WHERE MONTH(lesson_date) = MONTH(CURDATE()) AND YEAR(lesson_date) = YEAR(CURDATE())") ?: 0;

$prep_report_ranking = $wpdb->get_results("SELECT p.teacher_id, u.display_name, COUNT(*) as total, SUM(CASE WHEN p.status = 'approved' THEN 1 ELSE 0 END) as approved_count FROM {$wpdb->prefix}sm_lesson_preps p JOIN {$wpdb->users} u ON p.teacher_id = u.ID GROUP BY p.teacher_id ORDER BY approved_count DESC, total DESC LIMIT 10");
$prep_report_avg_late = $wpdb->get_var("SELECT AVG(delay_seconds / 60) FROM {$wpdb->prefix}sm_lesson_preps WHERE delay_seconds > 0") ?: 0;
$prep_report_total_late = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sm_lesson_preps WHERE status = 'late'") ?: 0;
?>

<div id="eess-prep-report-modal" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 99999; justify-content: center; align-items: center; padding: 20px; backdrop-filter: blur(2px); direction: rtl;">
    <div style="background: #fff; width: 100%; max-width: 850px; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); overflow: hidden; display: flex; flex-direction: column; max-height: 85vh; font-family: 'Cairo', sans-serif;">
        <!-- Modal Header -->
        <div style="background: #1e293b; color: white; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center;">
            <h3 id="eess-report-modal-title" style="margin: 0; font-size: 1.1rem; font-weight: 800; display: flex; align-items: center; gap: 8px;">
                <span class="dashicons dashicons-analytics"></span> تقارير تحضير الدروس والامتثال الأكاديمي
            </h3>
            <div style="display: flex; gap: 10px; align-items: center;">
                <button onclick="window.print()" class="sm-btn" style="background: #475569; color: white; border: none; font-size: 11px; padding: 4px 12px; height: auto; cursor:pointer;">🖨️ طباعة التقرير</button>
                <button type="button" onclick="document.getElementById('eess-prep-report-modal').style.display='none'" style="background: none; border: none; color: white; font-size: 24px; cursor: pointer; line-height: 1;">&times;</button>
            </div>
        </div>

        <!-- Modal Body -->
        <div style="padding: 20px; overflow-y: auto; flex: 1;">

            <!-- Report 1: Submitted -->
            <div id="rep-submitted" class="eess-report-section" style="display: none;">
                <h4 style="margin: 0 0 15px 0; color: #1e293b; font-weight: 800; border-bottom: 2px solid #f1f5f9; padding-bottom: 8px;">📝 التحضيرات المقدمة والمعتمدة مؤخراً</h4>
                <div class="sm-table-container">
                    <table class="sm-table" id="table-rep-submitted" style="width: 100%;">
                        <thead>
                            <tr><th>المعلم</th><th>عنوان التحضير</th><th>المادة</th><th>الصف والفرقة</th><th>تاريخ الدرس</th><th>حالة الاعتماد</th></tr>
                        </thead>
                        <tbody>
                            <?php if (empty($prep_report_submitted)): ?>
                                <tr><td colspan="6" style="text-align: center; color: #94a3b8;">لا توجد تحضيرات مقدمة حتى الآن.</td></tr>
                            <?php else: ?>
                                <?php foreach ($prep_report_submitted as $p): ?>
                                    <tr>
                                        <td style="font-weight: 700;"><?php echo esc_html($p->teacher_name); ?></td>
                                        <td><?php echo esc_html($p->title); ?></td>
                                        <td><?php echo esc_html($p->subject); ?></td>
                                        <td><?php echo esc_html($p->grade_level); ?> (<?php echo esc_html($p->class_section); ?>)</td>
                                        <td style="font-weight: bold;"><?php echo esc_html($p->lesson_date); ?></td>
                                        <td><span style="background: #dcfce7; color: #15803d; padding: 2px 6px; border-radius: 4px; font-weight: bold; font-size: 11px;"><?php echo esc_html($p->status); ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Report 2: Not Submitted -->
            <div id="rep-not_submitted" class="eess-report-section" style="display: none;">
                <h4 style="margin: 0 0 15px 0; color: #1e293b; font-weight: 800; border-bottom: 2px solid #f1f5f9; padding-bottom: 8px;">❌ المعلمون المتأخرون عن التحضير والمستثنين اليوم</h4>
                <div class="sm-table-container">
                    <table class="sm-table" id="table-rep-not-submitted" style="width: 100%;">
                        <thead>
                            <tr><th>المعلم</th><th>المادة/التخصص</th><th>القسم</th><th>الحالة العامة</th></tr>
                        </thead>
                        <tbody>
                            <?php $has_late_teachers = false;
                            foreach ($prep_report_teachers as $t):
                                $has_prep = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}sm_lesson_preps WHERE teacher_id = %d AND DATE(lesson_date) = CURDATE()", $t->ID));
                                if (!$has_prep):
                                    $has_late_teachers = true;
                            ?>
                                <tr>
                                    <td style="font-weight: 700; color: #dc2626;"><?php echo esc_html($t->display_name); ?></td>
                                    <td><?php echo esc_html(get_user_meta($t->ID, 'sm_specialization', true) ?: 'غير محدد'); ?></td>
                                    <td><?php echo esc_html(get_user_meta($t->ID, 'eess_department', true) ?: 'غير محدد'); ?></td>
                                    <td><span style="background: #fee2e2; color: #991b1b; padding: 2px 6px; border-radius: 4px; font-weight: bold; font-size: 11px;">لم يقدّم اليوم</span></td>
                                </tr>
                            <?php endif; endforeach;
                            if (!$has_late_teachers): ?>
                                <tr><td colspan="4" style="text-align: center; color: #16a34a; font-weight: bold;">جميع المعلمين قاموا بالتحضير اليوم بنجاح! 🎉</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Report 3: By Institution -->
            <div id="rep-by_institution" class="eess-report-section" style="display: none;">
                <h4 style="margin: 0 0 15px 0; color: #1e293b; font-weight: 800; border-bottom: 2px solid #f1f5f9; padding-bottom: 8px;">🏫 إحصائيات التحضيرات حسب المؤسسة التعليمية</h4>
                <div class="sm-table-container">
                    <table class="sm-table" id="table-rep-by-institution" style="width: 100%;">
                        <thead>
                            <tr><th>اسم المؤسسة / المدرسة</th><th>عدد التحضيرات المرفوعة</th></tr>
                        </thead>
                        <tbody>
                            <?php if (empty($prep_report_inst)): ?>
                                <tr><td colspan="2" style="text-align: center; color: #94a3b8;">لا توجد بيانات متاحة.</td></tr>
                            <?php else: ?>
                                <?php foreach ($prep_report_inst as $inst): ?>
                                    <tr><td style="font-weight: 700;"><?php echo esc_html($inst->inst); ?></td><td style="font-weight: bold; font-family: monospace; color: var(--sm-primary-color);"><?php echo $inst->cnt; ?> تحضير</td></tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Report 4: By Department -->
            <div id="rep-by_department" class="eess-report-section" style="display: none;">
                <h4 style="margin: 0 0 15px 0; color: #1e293b; font-weight: 800; border-bottom: 2px solid #f1f5f9; padding-bottom: 8px;">📂 إحصائيات التحضيرات حسب الأقسام التعليمية</h4>
                <div class="sm-table-container">
                    <table class="sm-table" id="table-rep-by-department" style="width: 100%;">
                        <thead>
                            <tr><th>القسم / الإدارة</th><th>عدد التحضيرات المرفوعة</th></tr>
                        </thead>
                        <tbody>
                            <?php if (empty($prep_report_dept)): ?>
                                <tr><td colspan="2" style="text-align: center; color: #94a3b8;">لا توجد بيانات متاحة.</td></tr>
                            <?php else: ?>
                                <?php foreach ($prep_report_dept as $dept): ?>
                                    <tr><td style="font-weight: 700;"><?php echo esc_html($dept->dept); ?></td><td style="font-weight: bold; font-family: monospace; color: var(--sm-primary-color);"><?php echo $dept->cnt; ?> تحضير</td></tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Report 5: By Subject -->
            <div id="rep-by_subject" class="eess-report-section" style="display: none;">
                <h4 style="margin: 0 0 15px 0; color: #1e293b; font-weight: 800; border-bottom: 2px solid #f1f5f9; padding-bottom: 8px;">📚 إحصائيات التحضيرات حسب المواد الدراسية</h4>
                <div class="sm-table-container">
                    <table class="sm-table" id="table-rep-by-subject" style="width: 100%;">
                        <thead>
                            <tr><th>المادة الدراسية</th><th>عدد التحضيرات المرفوعة</th></tr>
                        </thead>
                        <tbody>
                            <?php if (empty($prep_report_subject)): ?>
                                <tr><td colspan="2" style="text-align: center; color: #94a3b8;">لا توجد بيانات متاحة للمواد الدراسية.</td></tr>
                            <?php else: ?>
                                <?php foreach ($prep_report_subject as $sub): ?>
                                    <tr><td style="font-weight: 700; color: var(--sm-primary-color);"><?php echo esc_html($sub->name); ?></td><td style="font-weight: bold; font-family: monospace;"><?php echo $sub->cnt; ?> تحضير</td></tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Report 6: Periodical -->
            <div id="rep-periodical" class="eess-report-section" style="display: none;">
                <h4 style="margin: 0 0 15px 0; color: #1e293b; font-weight: 800; border-bottom: 2px solid #f1f5f9; padding-bottom: 8px;">📅 التقرير الدوري والمؤشرات الموقوتة</h4>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px;">
                    <div style="background: #f8fafc; padding: 20px; border-radius: 8px; border: 1px solid #e2e8f0; text-align: center;">
                        <span style="font-size: 13px; color: #64748b; font-weight: bold; display: block; margin-bottom: 5px;">التحضيرات المرفوعة اليوم</span>
                        <strong style="font-size: 28px; color: #1e293b; font-family: monospace;"><?php echo $prep_report_daily; ?></strong>
                    </div>
                    <div style="background: #f8fafc; padding: 20px; border-radius: 8px; border: 1px solid #e2e8f0; text-align: center;">
                        <span style="font-size: 13px; color: #64748b; font-weight: bold; display: block; margin-bottom: 5px;">التحضيرات هذا الأسبوع</span>
                        <strong style="font-size: 28px; color: #1e293b; font-family: monospace;"><?php echo $prep_report_weekly; ?></strong>
                    </div>
                    <div style="background: #f8fafc; padding: 20px; border-radius: 8px; border: 1px solid #e2e8f0; text-align: center;">
                        <span style="font-size: 13px; color: #64748b; font-weight: bold; display: block; margin-bottom: 5px;">التحضيرات هذا الشهر</span>
                        <strong style="font-size: 28px; color: #1e293b; font-family: monospace;"><?php echo $prep_report_monthly; ?></strong>
                    </div>
                </div>
            </div>

            <!-- Report 7: Ranking -->
            <div id="rep-ranking" class="eess-report-section" style="display: none;">
                <h4 style="margin: 0 0 15px 0; color: #1e293b; font-weight: 800; border-bottom: 2px solid #f1f5f9; padding-bottom: 8px;">🏆 تصنيف المدارس والمعلمين المتميزين (الأكثر التزاماً بالمنظومة)</h4>
                <div class="sm-table-container">
                    <table class="sm-table" id="table-rep-ranking" style="width: 100%;">
                        <thead>
                            <tr><th>تصنيف التميز</th><th>المعلم المتميز</th><th>إجمالي التحضيرات المعتمدة</th></tr>
                        </thead>
                        <tbody>
                            <?php if (empty($prep_report_ranking)): ?>
                                <tr><td colspan="3" style="text-align: center; color: #94a3b8;">لا توجد تحضيرات معتمدة بعد لتصنيفها.</td></tr>
                            <?php else: ?>
                                <?php $rank = 1; foreach ($prep_report_ranking as $teacher): ?>
                                    <tr>
                                        <td style="font-weight: 800; color: #b7791f;">⭐ المرتبة <?php echo $rank++; ?></td>
                                        <td style="font-weight: 700;"><?php echo esc_html($teacher->display_name); ?></td>
                                        <td style="font-weight: bold; font-family: monospace; color: #16a34a;"><?php echo $teacher->approved_count; ?> معتمد من <?php echo $teacher->total; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Report 8: Compliance -->
            <div id="rep-compliance" class="eess-report-section" style="display: none;">
                <h4 style="margin: 0 0 15px 0; color: #1e293b; font-weight: 800; border-bottom: 2px solid #f1f5f9; padding-bottom: 8px;">📊 متوسطات الامتثال لنسب التقديم السنوية والدورية</h4>
                <div style="background: #f8fafc; padding: 30px; border-radius: 12px; border: 1px solid #cbd5e1; text-align: center; max-width: 500px; margin: 0 auto;">
                    <span style="font-size: 15px; color: #475569; font-weight: bold; display: block; margin-bottom: 10px;">📊 متوسط امتثال المعلمين والمؤسسات العام</span>
                    <strong style="font-size: 3.5rem; color: #16a34a; font-family: monospace;"><?php echo $submission_pct; ?>%</strong>
                    <p style="margin: 15px 0 0 0; font-size: 13px; color: #64748b; line-height: 1.6;">تُقاس هذه النسبة بناءً على عدد التحضيرات المقدمة مقارنةً بإجمالي التحضيرات المترقبة من الكادر الأكاديمي والتعليمي النشط بالمنظومة.</p>
                </div>
            </div>

            <!-- Report 9: Late Statistics -->
            <div id="rep-late_stats" class="eess-report-section" style="display: none;">
                <h4 style="margin: 0 0 15px 0; color: #1e293b; font-weight: 800; border-bottom: 2px solid #f1f5f9; padding-bottom: 8px;">⏱️ إحصائيات التأخر ومهل التسليم للتحضيرات</h4>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px;">
                    <div style="background: #f8fafc; padding: 25px; border-radius: 8px; border: 1px solid #cbd5e1; text-align: center;">
                        <span style="font-size: 13px; color: #64748b; font-weight: bold; display: block; margin-bottom: 5px;">متوسط زمن تأخير التسليم</span>
                        <strong style="font-size: 26px; color: #dc2626; font-family: monospace;"><?php echo round($prep_report_avg_late); ?> دقيقة</strong>
                    </div>
                    <div style="background: #f8fafc; padding: 25px; border-radius: 8px; border: 1px solid #cbd5e1; text-align: center;">
                        <span style="font-size: 13px; color: #64748b; font-weight: bold; display: block; margin-bottom: 5px;">التحضيرات المتأخرة المرفوعة</span>
                        <strong style="font-size: 26px; color: #dc2626; font-family: monospace;"><?php echo $prep_report_total_late; ?> تحضير</strong>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
