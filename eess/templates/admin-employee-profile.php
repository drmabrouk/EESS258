<?php
if (!defined('ABSPATH')) exit;

$current_user = wp_get_current_user();
$roles = (array) $current_user->roles;
$is_admin = in_array('administrator', $roles) || current_user_can('manage_options');
$is_sys_admin = in_array('sm_system_admin', $roles);
$is_hr = in_array('sm_hr', $roles) || current_user_can('manage_hr');

// Determine the target employee
$target_user_id = $current_user->ID;
if (($is_admin || $is_sys_admin || $is_hr) && isset($_GET['employee_id'])) {
    $target_user_id = intval($_GET['employee_id']);
}

// Handle profile photo upload with HR approval workflow
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_FILES['profile_photo_upload'])) {
    if (isset($_POST['eess_photo_nonce']) && wp_verify_nonce($_POST['eess_photo_nonce'], 'eess_profile_photo_upload')) {
        if (!empty($_FILES['profile_photo_upload']['name'])) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');

            $attachment_id = media_handle_upload('profile_photo_upload', 0);
            if (!is_wp_error($attachment_id)) {
                $photo_url = wp_get_attachment_url($attachment_id);
                update_user_meta($target_user_id, 'eess_pending_profile_photo', $photo_url);
                echo '<div style="background:#fffbeb; color:#b45309; padding:15px; border-radius:8px; border:1px solid #fef3c7; font-weight:700; margin-bottom:20px; font-family:\'Cairo\', sans-serif; text-align:right;">⚠️ تم رفع الصورة الشخصية بنجاح وهي معلقة بانتظار موافقة واعتماد قسم الموارد البشرية (HR).</div>';
            } else {
                echo '<div style="background:#fee2e2; color:#991b1b; padding:15px; border-radius:8px; border:1px solid #fca5a5; font-weight:700; margin-bottom:20px; font-family:\'Cairo\', sans-serif; text-align:right;">❌ خطأ في رفع الصورة: ' . esc_html($attachment_id->get_error_message()) . '</div>';
            }
        }
    }
}

$u = get_userdata($target_user_id);
if (!$u) {
    echo '<div class="error" style="background:#fee2e2; color:#991b1b; padding:15px; border-radius:8px; border:1px solid #fca5a5; font-weight:700; font-family:\'Cairo\', sans-serif;">خطأ: لم يتم العثور على الموظف المطلوب.</div>';
    return;
}

// Security: Employees can only access their own profile
if ($target_user_id !== $current_user->ID && !($is_admin || $is_sys_admin || $is_hr)) {
    $target_user_id = $current_user->ID;
    $u = $current_user;
}

// Arabic role translation map
$role_map = array(
    'administrator' => 'الإدارة المركزية (المطور)',
    'sm_system_admin' => 'مدير النظام التقني',
    'sm_principal' => 'مدير المدرسة',
    'sm_supervisor' => 'مشرف تربوي',
    'sm_hod' => 'رئيس قسم',
    'sm_coordinator' => 'منسق مادة',
    'sm_teacher' => 'معلم',
    'sm_discipline_supervisor' => 'مشرف سلوك / انضباط',
    'sm_activities_supervisor' => 'مشرف أنشطة',
    'sm_transportation_supervisor' => 'مشرف نقل ومواصلات',
    'sm_bus_supervisor' => 'مشرف حافلة',
    'sm_clinic' => 'العيادة المدرسية',
    'sm_hr' => 'الموارد البشرية (HR)',
    'sm_student' => 'طالب',
    'sm_parent' => 'ولي أمر'
);

// Form submission handler for saving/synchronizing account and profile details
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['eess_save_profile_action'])) {
    // CSRF Check
    if (!isset($_POST['eess_profile_nonce']) || !wp_verify_nonce($_POST['eess_profile_nonce'], 'eess_save_profile')) {
        wp_die('عذراً، انتهت صلاحية الجلسة. يرجى المحاولة مجدداً.');
    }

    // Permission Check: only Admin, HR, or the employee himself can save personal info
    if ($target_user_id === $current_user->ID || $is_admin || $is_sys_admin || $is_hr) {
        $first_name = sanitize_text_field($_POST['first_name'] ?? '');
        $last_name  = sanitize_text_field($_POST['last_name'] ?? '');
        $email      = sanitize_email($_POST['email'] ?? '');
        $phone      = sanitize_text_field($_POST['phone'] ?? '');

        // Update core user record
        $user_data = array(
            'ID' => $target_user_id,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'display_name' => $first_name . ' ' . $last_name,
        );
        if (!empty($email) && $email !== $u->user_email) {
            $user_data['user_email'] = $email;
        }
        wp_update_user($user_data);

        // Update user meta
        update_user_meta($target_user_id, 'first_name', $first_name);
        update_user_meta($target_user_id, 'last_name', $last_name);
        update_user_meta($target_user_id, 'sm_phone', $phone);

        // HR/Admin only fields (Employment info, position, etc.)
        if ($is_admin || $is_sys_admin || $is_hr) {
            $emp_num    = sanitize_text_field($_POST['employee_number'] ?? '');
            $school     = sanitize_text_field($_POST['school_name'] ?? '');
            $dept       = sanitize_text_field($_POST['department'] ?? '');
            $spec       = sanitize_text_field($_POST['specialization'] ?? '');
            $emp_date   = sanitize_text_field($_POST['employment_date'] ?? '');
            $emp_status = sanitize_text_field($_POST['employment_status'] ?? 'active');

            update_user_meta($target_user_id, 'eess_employee_number', $emp_num);
            update_user_meta($target_user_id, 'eess_school_name', $school);
            update_user_meta($target_user_id, 'eess_department', $dept);
            update_user_meta($target_user_id, 'sm_specialization', $spec);
            update_user_meta($target_user_id, 'eess_hr_employment_date', $emp_date);
            update_user_meta($target_user_id, 'eess_hr_employment_status', $emp_status);
        }

        // Log action in activity timeline
        $timeline = get_user_meta($target_user_id, 'eess_hr_activity_timeline', true) ?: array();
        if (!is_array($timeline)) $timeline = array();
        array_unshift($timeline, array(
            'date' => current_time('Y-m-d H:i:s'),
            'action' => 'تحديث الملف الشخصي',
            'actor' => $current_user->display_name,
            'details' => 'تم تحديث بيانات الملف التعريفي والاتصال بنجاح.'
        ));
        update_user_meta($target_user_id, 'eess_hr_activity_timeline', $timeline);

        // Clean user cache
        clean_user_cache($target_user_id);
        wp_cache_flush();

        // Refresh user data reference
        $u = get_userdata($target_user_id);

        echo '<div style="background:#dcfce7; color:#15803d; padding:15px; border-radius:8px; border:1px solid #bbf7d0; font-weight:700; margin-bottom:20px; font-family:\'Cairo\', sans-serif;">✅ تم تحديث وتزامن بيانات الموظف بنجاح في قاعدة البيانات والأنظمة المرتبطة.</div>';
    }
}

// Fetch HR fields from metadata
$first_name = get_user_meta($target_user_id, 'first_name', true) ?: $u->first_name;
$last_name = get_user_meta($target_user_id, 'last_name', true) ?: $u->last_name;
$emp_num = get_user_meta($target_user_id, 'eess_employee_number', true) ?: '';
$school_name = get_user_meta($target_user_id, 'eess_school_name', true) ?: 'خدمات الأنظمة الإلكترونية التعليمية (EESS)';
$dept = get_user_meta($target_user_id, 'eess_department', true) ?: 'غير محدد';
$specialization = get_user_meta($target_user_id, 'sm_specialization', true) ?: 'غير محدد';
$employment_date = get_user_meta($target_user_id, 'eess_hr_employment_date', true) ?: '';
$employment_status = get_user_meta($target_user_id, 'eess_hr_employment_status', true) ?: 'active';
$phone = get_user_meta($target_user_id, 'sm_phone', true) ?: '';

// Load list metadata structures
$salary_records = get_user_meta($target_user_id, 'eess_hr_salary_records', true) ?: array();
if (!is_array($salary_records)) $salary_records = json_decode($salary_records, true) ?: array();

$disciplinary_records = get_user_meta($target_user_id, 'eess_hr_disciplinary_records', true) ?: array();
if (!is_array($disciplinary_records)) $disciplinary_records = json_decode($disciplinary_records, true) ?: array();

$warning_notices = get_user_meta($target_user_id, 'eess_hr_warning_notices', true) ?: array();
if (!is_array($warning_notices)) $warning_notices = json_decode($warning_notices, true) ?: array();

$admin_actions = get_user_meta($target_user_id, 'eess_hr_admin_actions', true) ?: array();
if (!is_array($admin_actions)) $admin_actions = json_decode($admin_actions, true) ?: array();

$hr_documents = get_user_meta($target_user_id, 'eess_hr_documents', true) ?: array();
if (!is_array($hr_documents)) $hr_documents = json_decode($hr_documents, true) ?: array();

$employment_history = get_user_meta($target_user_id, 'eess_hr_employment_history', true) ?: array();
if (!is_array($employment_history)) $employment_history = json_decode($employment_history, true) ?: array();

$evaluations = get_user_meta($target_user_id, 'eess_hr_evaluations', true) ?: array();
if (!is_array($evaluations)) $evaluations = json_decode($evaluations, true) ?: array();

$leaves = get_user_meta($target_user_id, 'eess_hr_leaves', true) ?: array();
if (!is_array($leaves)) $leaves = json_decode($leaves, true) ?: array();

$timeline = get_user_meta($target_user_id, 'eess_hr_activity_timeline', true) ?: array();
if (!is_array($timeline)) $timeline = json_decode($timeline, true) ?: array();
?>

<div class="sm-container" style="padding: 10px 0; font-family: 'Cairo', sans-serif !important; direction: rtl;">

    <!-- Single Main Banner Header (Work Profile) -->
    <div style="background: #ffffff; padding: 20px 24px; border-radius: 20px; border: 1px solid #e2e8f0; margin-bottom: 18px; box-shadow: 0 4px 18px rgba(0,0,0,0.02); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
        <div style="display: flex; align-items: center; gap: 14px;">
            <div style="width: 48px; height: 48px; background: #fef2f2; border-radius: 14px; display: flex; align-items: center; justify-content: center; color: #881337; border: 1px solid #fecdd3; flex-shrink: 0;">
                <span class="dashicons dashicons-id-alt" style="font-size: 24px; width: 24px; height: 24px;"></span>
            </div>
            <div>
                <h2 style="margin: 0 0 4px 0; font-size: 20px; font-weight: 800; color: #0f172a;">الملف التعريفي والمهني للموظف</h2>
                <p style="margin: 0; font-size: 12.5px; color: #64748b; font-weight: 500;">استعراض البيانات الشخصية، السجلات الوظيفية، التقييمات والوثائق الرسمية</p>
            </div>
        </div>

        <!-- Primary Header Actions -->
        <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
            <?php if ($target_user_id === $current_user->ID || $is_admin || $is_sys_admin || $is_hr): ?>
            <button type="button" onclick="eessOpenProfileEditModal()" class="sm-btn" style="background: #881337; color: #ffffff !important; height: 38px; border-radius: 9999px !important; padding: 0 20px; font-weight: 800; font-size: 12.5px; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
                <span class="dashicons dashicons-edit" style="font-size: 15px; width: 15px; height: 15px; color: #fff;"></span>
                <span>تعديل الملف المهني</span>
            </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Main Workspace with Navigation List on Right and Selected Tab Content on Left (RTL Compliant) -->
    <div style="background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: var(--sm-shadow); padding: 25px; min-height: 500px; display: flex; gap: 25px; direction: rtl; align-items: flex-start;">

        <!-- Right Side: Vertical Navigation Tabs List (width: 250px) -->
        <div style="width: 250px; flex-shrink: 0; border-left: 1px solid #cbd5e0; padding-left: 20px; display: flex; flex-direction: column; gap: 8px;">
            <button type="button" onclick="switchEmployeeProfileTab('wp-personal', this)" class="sm-tab-btn sm-active" style="text-align: right; width: 100%; border: none; font-size: 13px; font-weight: 700; padding: 10px 15px; border-radius: 9999px !important; cursor: pointer; transition: all 0.2s; background: #881337; color: white; display: flex; align-items: center; gap: 8px;">
                <span class="dashicons dashicons-admin-users" style="font-size: 16px; width: 16px; height: 16px;"></span>
                <span>المعلومات الشاملة</span>
            </button>
            <button type="button" onclick="switchEmployeeProfileTab('wp-salaries', this)" class="sm-tab-btn" style="text-align: right; width: 100%; border: none; font-size: 13px; font-weight: 700; padding: 10px 15px; border-radius: 9999px !important; cursor: pointer; transition: all 0.2s; background: #f8fafc; color: #475569; display: flex; align-items: center; gap: 8px;">
                <span class="dashicons dashicons-money-alt" style="font-size: 16px; width: 16px; height: 16px;"></span>
                <span>معلومات الرواتب</span>
            </button>
            <button type="button" onclick="switchEmployeeProfileTab('wp-disciplinary', this)" class="sm-tab-btn" style="text-align: right; width: 100%; border: none; font-size: 13px; font-weight: 700; padding: 10px 15px; border-radius: 9999px !important; cursor: pointer; transition: all 0.2s; background: #f8fafc; color: #475569; display: flex; align-items: center; gap: 8px;">
                <span class="dashicons dashicons-warning" style="font-size: 16px; width: 16px; height: 16px;"></span>
                <span>السجلات التأديبية</span>
            </button>
            <button type="button" onclick="switchEmployeeProfileTab('wp-evaluations', this)" class="sm-tab-btn" style="text-align: right; width: 100%; border: none; font-size: 13px; font-weight: 700; padding: 10px 15px; border-radius: 9999px !important; cursor: pointer; transition: all 0.2s; background: #f8fafc; color: #475569; display: flex; align-items: center; gap: 8px;">
                <span class="dashicons dashicons-chart-line" style="font-size: 16px; width: 16px; height: 16px;"></span>
                <span>تقييم الأداء</span>
            </button>
            <button type="button" onclick="switchEmployeeProfileTab('wp-docs', this)" class="sm-tab-btn" style="text-align: right; width: 100%; border: none; font-size: 13px; font-weight: 700; padding: 10px 15px; border-radius: 9999px !important; cursor: pointer; transition: all 0.2s; background: #f8fafc; color: #475569; display: flex; align-items: center; gap: 8px;">
                <span class="dashicons dashicons-media-document" style="font-size: 16px; width: 16px; height: 16px;"></span>
                <span>الوثائق الرسمية</span>
            </button>
            <button type="button" onclick="switchEmployeeProfileTab('wp-leaves', this)" class="sm-tab-btn" style="text-align: right; width: 100%; border: none; font-size: 13px; font-weight: 700; padding: 10px 15px; border-radius: 9999px !important; cursor: pointer; transition: all 0.2s; background: #f8fafc; color: #475569; display: flex; align-items: center; gap: 8px;">
                <span class="dashicons dashicons-calendar-alt" style="font-size: 16px; width: 16px; height: 16px;"></span>
                <span>سجل الإجازات</span>
            </button>
            <button type="button" onclick="switchEmployeeProfileTab('wp-notes', this)" class="sm-tab-btn" style="text-align: right; width: 100%; border: none; font-size: 13px; font-weight: 700; padding: 10px 15px; border-radius: 9999px !important; cursor: pointer; transition: all 0.2s; background: #f8fafc; color: #475569; display: flex; align-items: center; gap: 8px;">
                <span class="dashicons dashicons-edit" style="font-size: 16px; width: 16px; height: 16px;"></span>
                <span>الملاحظات الإدارية</span>
            </button>
            <button type="button" onclick="switchEmployeeProfileTab('wp-timeline', this)" class="sm-tab-btn" style="text-align: right; width: 100%; border: none; font-size: 13px; font-weight: 700; padding: 10px 15px; border-radius: 9999px !important; cursor: pointer; transition: all 0.2s; background: #f8fafc; color: #475569; display: flex; align-items: center; gap: 8px;">
                <span class="dashicons dashicons-clock" style="font-size: 16px; width: 16px; height: 16px;"></span>
                <span>سجل الأنشطة</span>
            </button>
        </div>

        <!-- Left Side: Content Panels for Selected Tab (flex: 1) -->
        <div style="flex: 1; min-width: 0;">
            <!-- Section 1: Personal Information -->
            <div id="wp-personal" class="wp-tab-content" style="display: block;">

                <!-- Employee Overview Top Header (Moved to first tab: Personal Information) -->
                <div style="background: #f8fafc; padding: 25px; border-radius: 12px; border: 1px solid #e2e8f0; margin-bottom: 25px; box-shadow: var(--sm-shadow);">
                    <div style="display: flex; gap: 24px; align-items: center; flex-wrap: wrap;">
                        <div style="text-align: center;">
                            <?php $pending_photo = get_user_meta($target_user_id, 'eess_pending_profile_photo', true); ?>
                            <form method="POST" enctype="multipart/form-data" action="" id="eess_avatar_upload_form">
                                <?php wp_nonce_field('eess_profile_photo_upload', 'eess_photo_nonce'); ?>
                                <label style="position: relative; display: inline-block; cursor: pointer; border-radius: 50%; overflow: hidden; border: 4px solid #cbd5e1; width: 110px; height: 110px;" title="انقر لتغيير الصورة الشخصية">
                                    <?php echo get_avatar($target_user_id, 110, '', '', array('style' => 'width: 100%; height: 100%; object-fit: cover; display: block; margin: 0;')); ?>
                                    <div style="position: absolute; inset: 0; background: rgba(0,0,0,0.4); color: white; display: flex; flex-direction: column; align-items: center; justify-content: center; opacity: 0; transition: opacity 0.2s;" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0'">
                                        <span class="dashicons dashicons-camera" style="font-size: 24px; width: 24px; height: 24px;"></span>
                                        <span style="font-size: 10px; font-weight: bold; margin-top: 2px;">تغيير</span>
                                    </div>
                                    <input type="file" name="profile_photo_upload" onchange="document.getElementById('eess_avatar_upload_form').submit()" style="display: none;">
                                </label>
                            </form>
                            <?php if ($pending_photo): ?>
                                <div style="margin-top: 5px; font-size: 10px; color: #d97706; font-weight: bold; background: #fffbeb; border: 1px solid #fef3c7; padding: 4px; border-radius: 4px; text-align: center; max-width: 180px; margin-left: auto; margin-right: auto;">
                                    ⚠️ الصورة الجديدة قيد الاعتماد من قبل HR
                                </div>
                            <?php endif; ?>
                        </div>

                        <div style="flex: 1; min-width: 280px; text-align: right;">
                            <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                                <h2 style="margin: 0; font-weight: 800; color: #1e293b; font-size: 1.6rem;"><?php echo esc_html($u->display_name); ?></h2>
                                <span style="background: #334155; color: #f8fafc; padding: 2px 10px; border-radius: 6px; font-size: 11px; font-weight: 700;">
                                    <?php echo $role_map[$u->roles[0]] ?? $u->roles[0]; ?>
                                </span>
                                <?php if ($employment_status === 'active'): ?>
                                    <span style="background: #dcfce7; color: #15803d; padding: 2px 10px; border-radius: 6px; font-size: 11px; font-weight: 700; border: 1px solid #bbf7d0;">نشط بالخدمة</span>
                                <?php elseif ($employment_status === 'restricted'): ?>
                                    <span style="background: #fee2e2; color: #991b1b; padding: 2px 10px; border-radius: 6px; font-size: 11px; font-weight: 700; border: 1px solid #fca5a5;">مقيد الدخول للمنصة</span>
                                <?php else: ?>
                                    <span style="background: #f1f5f9; color: #475569; padding: 2px 10px; border-radius: 6px; font-size: 11px; font-weight: 700; border: 1px solid #cbd5e1;">غير نشط / إجازة</span>
                                <?php endif; ?>
                            </div>
                            <p style="margin: 6px 0; font-size: 0.85rem; color: #64748b; font-family: monospace;">@<?php echo esc_html($u->user_login); ?> | <?php echo esc_html($u->user_email); ?></p>

                            <div style="display: flex; gap: 15px; margin-top: 12px; flex-wrap: wrap; font-size: 12px;">
                                <span style="color: #475569;">
                                    <strong>رقم الموظف:</strong> <code style="background: #f1f5f9; padding: 2px 6px; border-radius: 4px;"><?php echo esc_html($emp_num ?: 'غير محدد'); ?></code>
                                </span>
                                <span style="color: #475569;">
                                    <strong>القسم:</strong> <?php echo esc_html($dept); ?>
                                </span>
                                <span style="color: #475569;">
                                    <strong>المادة/التخصص:</strong> <span style="color: #64748b; font-weight: 700;"><?php echo esc_html($specialization); ?></span>
                                </span>
                            </div>
                        </div>

                    </div>
                </div>

                <h4 style="margin: 0 0 20px 0; font-weight: 800; color: #1e293b; font-size: 1.1rem; border-bottom: 2px solid #f1f5f9; padding-bottom: 10px;">البيانات الشخصية وتفاصيل الاتصال</h4>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px;">
                <div style="background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid #f1f5f9;">
                    <span style="color: #64748b; font-size: 12px; display: block;">الاسم الأول:</span>
                    <strong style="font-size: 14px; color: #1e293b;"><?php echo esc_html($first_name ?: 'غير محدد'); ?></strong>
                </div>
                <div style="background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid #f1f5f9;">
                    <span style="color: #64748b; font-size: 12px; display: block;">اسم العائلة / اللقب:</span>
                    <strong style="font-size: 14px; color: #1e293b;"><?php echo esc_html($last_name ?: 'غير محدد'); ?></strong>
                </div>
                <div style="background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid #f1f5f9;">
                    <span style="color: #64748b; font-size: 12px; display: block;">الاسم الكامل للموظف:</span>
                    <strong style="font-size: 14px; color: #1e293b;"><?php echo esc_html($u->display_name); ?></strong>
                </div>
                <div style="background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid #f1f5f9;">
                    <span style="color: #64748b; font-size: 12px; display: block;">البريد الإلكتروني المعتمد:</span>
                    <strong style="font-size: 14px; color: #1e293b; font-family: monospace;"><?php echo esc_html($u->user_email); ?></strong>
                </div>
                <div style="background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid #f1f5f9;">
                    <span style="color: #64748b; font-size: 12px; display: block;">رقم الهاتف المتحرك:</span>
                    <strong style="font-size: 14px; color: #1e293b; font-family: monospace;"><?php echo esc_html($phone ?: 'غير محدد'); ?></strong>
                </div>
            </div>
        </div>

        <!-- Section 2: Employment Information -->
        <div id="wp-employment" class="wp-tab-content" style="display: none;">
            <h4 style="margin: 0 0 20px 0; font-weight: 800; color: #1e293b; font-size: 1.1rem; border-bottom: 2px solid #f1f5f9; padding-bottom: 10px;">💼 بيانات المباشرة والتعيين الرسمي</h4>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px;">
                <div style="background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid #f1f5f9;">
                    <span style="color: #64748b; font-size: 12px; display: block;">تاريخ مباشرة العمل:</span>
                    <strong style="font-size: 14px; color: #1e293b;"><?php echo esc_html($employment_date ?: 'غير مسجل'); ?></strong>
                </div>
                <div style="background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid #f1f5f9;">
                    <span style="color: #64748b; font-size: 12px; display: block;">المؤسسة التعليمية التابع لها:</span>
                    <strong style="font-size: 14px; color: #1e293b;"><?php echo esc_html($school_name); ?></strong>
                </div>
                <div style="background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid #f1f5f9;">
                    <span style="color: #64748b; font-size: 12px; display: block;">نوع التوظيف:</span>
                    <strong style="font-size: 14px; color: #1e293b;">دائم / دوام كامل</strong>
                </div>
                <div style="background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid #f1f5f9;">
                    <span style="color: #64748b; font-size: 12px; display: block;">تاريخ انتهاء التعاقد المخطط:</span>
                    <strong style="font-size: 14px; color: #1e293b;">مستمر</strong>
                </div>
            </div>
        </div>

        <!-- Section 3: Position Details -->
        <div id="wp-position" class="wp-tab-content" style="display: none;">
            <h4 style="margin: 0 0 20px 0; font-weight: 800; color: #1e293b; font-size: 1.1rem; border-bottom: 2px solid #f1f5f9; padding-bottom: 10px;">⚙️ تفاصيل المنصب الحالي والمسؤوليات والمهام المسندة</h4>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px;">
                <div style="background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid #f1f5f9;">
                    <span style="color: #64748b; font-size: 12px; display: block;">المنصب الحالي في النظام:</span>
                    <strong style="font-size: 14px; color: var(--sm-primary-color);"><?php echo $role_map[$u->roles[0]] ?? $u->roles[0]; ?></strong>
                </div>
                <div style="background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid #f1f5f9;">
                    <span style="color: #64748b; font-size: 12px; display: block;">الإدارة / القسم التنظيمي:</span>
                    <strong style="font-size: 14px; color: #1e293b;"><?php echo esc_html($dept); ?></strong>
                </div>
                <div style="background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid #f1f5f9;">
                    <span style="color: #64748b; font-size: 12px; display: block;">التخصص التدريسي أو الأكاديمي:</span>
                    <strong style="font-size: 14px; color: #1e293b;"><?php echo esc_html($specialization); ?></strong>
                </div>
                <div style="background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid #f1f5f9;">
                    <span style="color: #64748b; font-size: 12px; display: block;">سلطة المباشرة:</span>
                    <strong style="font-size: 14px; color: #1e293b;">مدير المدرسة المباشر</strong>
                </div>
            </div>
        </div>

        <!-- Section 4: Salary Information -->
        <div id="wp-salaries" class="wp-tab-content" style="display: none;">
            <h4 style="margin: 0 0 20px 0; font-weight: 800; color: #1e293b; font-size: 1.1rem; border-bottom: 2px solid #f1f5f9; padding-bottom: 10px;">📊 معلومات الرواتب والتعويضات والبدلات</h4>
            <div class="sm-table-container">
                <table class="sm-table" style="margin: 0; width: 100%;">
                    <thead>
                        <tr>
                            <th>الشهر / تاريخ الاستحقاق</th>
                            <th>الراتب الأساسي</th>
                            <th>بدل السكن</th>
                            <th>بدل الانتقال</th>
                            <th>خصومات واستقطاعات</th>
                            <th>الصافي المستلم</th>
                            <th>تفاصيل وملاحظات الصرف</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($salary_records)): ?>
                            <tr><td colspan="7" style="text-align: center; color: #94a3b8; padding: 30px;">لا توجد أي سجلات مالية أو رواتب مسجلة لهذا الموظف حالياً.</td></tr>
                        <?php else: ?>
                            <?php foreach ($salary_records as $rec): ?>
                                <tr>
                                    <td style="font-weight: 800; color: #1e293b;"><?php echo esc_html($rec['date']); ?></td>
                                    <td><?php echo number_format($rec['basic'] ?? 0, 2); ?> د.إ</td>
                                    <td><?php echo number_format($rec['housing'] ?? 0, 2); ?> د.إ</td>
                                    <td><?php echo number_format($rec['transport'] ?? 0, 2); ?> د.إ</td>
                                    <td style="color: #dc2626; font-weight: bold;">-<?php echo number_format($rec['deductions'] ?? 0, 2); ?> د.إ</td>
                                    <td style="color: #16a34a; font-weight: 800; font-size: 14px;"><?php echo number_format($rec['net'] ?? 0, 2); ?> د.إ</td>
                                    <td style="font-style: italic; font-size: 12px; color: #64748b;"><?php echo esc_html($rec['notes'] ?? ''); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Section 5: Disciplinary Records (Single Unified Un-nested Table) -->
        <div id="wp-disciplinary" class="wp-tab-content" style="display: none;">
            <h4 style="margin: 0 0 20px 0; font-weight: 800; color: #0f172a; font-size: 1.1rem; border-bottom: 2px solid #f1f5f9; padding-bottom: 10px;">السجلات التأديبية ومحاضر الانضباط والقرارات</h4>

            <div style="border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden;">
                <table style="width: 100%; border-collapse: collapse; text-align: right;">
                    <thead>
                        <tr style="background: #212121; color: #ffffff;">
                            <th style="padding: 12px 16px; font-size: 12.5px; font-weight: 800;">التاريخ</th>
                            <th style="padding: 12px 16px; font-size: 12.5px; font-weight: 800;">نوع الإنذار / المخالفة</th>
                            <th style="padding: 12px 16px; font-size: 12.5px; font-weight: 800;">التفاصيل والواقعة</th>
                            <th style="padding: 12px 16px; font-size: 12.5px; font-weight: 800; text-align: center;">الإجراء / حالة الملف</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $all_disc_items = array_merge($warning_notices, $disciplinary_records);
                        if (empty($all_disc_items)): ?>
                            <tr><td colspan="4" style="text-align: center; color: #94a3b8; padding: 40px; font-weight: 700;">السجل المعتمد خالي من أي إنذارات أو قرارات انضباطية.</td></tr>
                        <?php else: ?>
                            <?php foreach ($all_disc_items as $item): ?>
                                <tr style="border-bottom: 1px solid #f1f5f9;">
                                    <td style="padding: 12px 16px; font-weight: 800; color: #0f172a;"><?php echo esc_html($item['date'] ?? '---'); ?></td>
                                    <td style="padding: 12px 16px; font-weight: 700; color: #dc2626;"><?php echo esc_html($item['subject'] ?? ($item['incident'] ?? 'إشعار انضباطي')); ?></td>
                                    <td style="padding: 12px 16px; font-size: 12.5px; color: #334155;"><?php echo esc_html($item['details'] ?? ($item['incident'] ?? '---')); ?></td>
                                    <td style="padding: 12px 16px; text-align: center;">
                                        <span style="display: inline-block; padding: 3px 10px; border-radius: 12px; background: #fee2e2; color: #b91c1c; font-weight: 800; font-size: 11px;">
                                            <?php echo esc_html($item['action'] ?? ($item['status'] ?? 'إنذار رسمي')); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Section 6: Performance Evaluations -->
        <div id="wp-evaluations" class="wp-tab-content" style="display: none;">
            <h4 style="margin: 0 0 20px 0; font-weight: 800; color: #1e293b; font-size: 1.1rem; border-bottom: 2px solid #f1f5f9; padding-bottom: 10px;">📈 تقييم الأداء والتقارير والتقييمات السنوية</h4>
            <div class="sm-table-container">
                <table class="sm-table" style="margin: 0; width: 100%;">
                    <thead>
                        <tr>
                            <th>فترة التقييم</th>
                            <th>الدرجة النهائية (%)</th>
                            <th>التقدير العام</th>
                            <th>توصيات وملاحظات رئيس المباشرة</th>
                            <th>المقيّم المعتمد</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($evaluations)): ?>
                            <tr><td colspan="5" style="text-align: center; color: #94a3b8; padding: 30px;">لا توجد تقييمات أداء مسجلة لهذا الموظف حتى الآن.</td></tr>
                        <?php else: ?>
                            <?php foreach ($evaluations as $eval): ?>
                                <tr>
                                    <td style="font-weight: 800;"><?php echo esc_html($eval['period'] ?? $eval['date'] ?? 'غير محدد'); ?></td>
                                    <td style="font-weight: bold; font-family: monospace; font-size: 14px; color: var(--sm-primary-color);"><?php echo esc_html($eval['score'] ?? '0'); ?>%</td>
                                    <td>
                                        <span style="background: #f1f5f9; padding: 4px 8px; border-radius: 4px; font-weight: bold; font-size: 12px;">
                                            <?php echo esc_html($eval['grade'] ?? 'غير محدد'); ?>
                                        </span>
                                    </td>
                                    <td style="font-size: 12px; color: #475569;"><?php echo esc_html($eval['notes'] ?? $eval['comments'] ?? ''); ?></td>
                                    <td><?php echo esc_html($eval['evaluator'] ?? 'الإدارة'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Section 7: Official Documents -->
        <div id="wp-docs" class="wp-tab-content" style="display: none;">
            <h4 style="margin: 0 0 20px 0; font-weight: 800; color: #1e293b; font-size: 1.1rem; border-bottom: 2px solid #f1f5f9; padding-bottom: 10px;">📄 الوثائق الرسمية، الهويات، المؤهلات والشهادات المؤرشفة</h4>
            <div class="sm-table-container">
                <table class="sm-table" style="margin: 0; width: 100%;">
                    <thead>
                        <tr>
                            <th>تاريخ المرفق</th>
                            <th>اسم وثيقة الثبوتية</th>
                            <th>رابط المستند المؤرشف</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($hr_documents)): ?>
                            <tr><td colspan="3" style="text-align: center; color: #94a3b8; padding: 30px;">لم يتم رفع أي مستندات رسمية أو ثبوتية للموظف.</td></tr>
                        <?php else: ?>
                            <?php foreach ($hr_documents as $doc): ?>
                                <tr>
                                    <td><?php echo esc_html($doc['date']); ?></td>
                                    <td style="font-weight: 700;"><?php echo esc_html($doc['name']); ?></td>
                                    <td>
                                        <a href="<?php echo esc_url($doc['file_url']); ?>" target="_blank" class="sm-btn" style="padding: 4px 12px; height: 28px; width: auto; font-size: 11px; background: #000; color: white !important; text-decoration: none; display: inline-flex; align-items: center; gap: 5px;">
                                            📥 معاينة وتحميل الملف
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Section 8: Employment History -->
        <div id="wp-history" class="wp-tab-content" style="display: none;">
            <h4 style="margin: 0 0 20px 0; font-weight: 800; color: #1e293b; font-size: 1.1rem; border-bottom: 2px solid #f1f5f9; padding-bottom: 10px;">⏳ السجل التاريخي والترقيات والخبرات السابقة</h4>
            <div class="sm-table-container">
                <table class="sm-table" style="margin: 0; width: 100%;">
                    <thead>
                        <tr>
                            <th>تاريخ وتوقيت التحديث</th>
                            <th>المسمى الوظيفي / دور العمل</th>
                            <th>المدرسة / المؤسسة المشغلة</th>
                            <th>ملاحظات وتفاصيل الترقية</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($employment_history)): ?>
                            <tr><td colspan="4" style="text-align: center; color: #94a3b8; padding: 30px;">لا توجد ترقيات أو سجل خبرة تاريخي مسجل مسبقاً.</td></tr>
                        <?php else: ?>
                            <?php foreach ($employment_history as $hist): ?>
                                <tr>
                                    <td style="font-weight: bold;"><?php echo esc_html($hist['date']); ?></td>
                                    <td style="font-weight: 700; color: var(--sm-primary-color);"><?php echo esc_html($hist['role']); ?></td>
                                    <td><?php echo esc_html($hist['organization']); ?></td>
                                    <td style="font-size: 12px; color: #475569;"><?php echo esc_html($hist['notes']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Section 9: Leave Records -->
        <div id="wp-leaves" class="wp-tab-content" style="display: none;">
            <h4 style="margin: 0 0 20px 0; font-weight: 800; color: #1e293b; font-size: 1.1rem; border-bottom: 2px solid #f1f5f9; padding-bottom: 10px;">📅 سجل الإجازات الرسمية، العارضة والمغادرات اليومية</h4>
            <div class="sm-table-container">
                <table class="sm-table" style="margin: 0; width: 100%;">
                    <thead>
                        <tr>
                            <th>من تاريخ</th>
                            <th>إلى تاريخ</th>
                            <th>نوع الإجازة / الطلب</th>
                            <th>عدد الأيام الفعلي</th>
                            <th>ملاحظات معتمدة</th>
                            <th>حالة الطلب والموافقة</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($leaves)): ?>
                            <tr><td colspan="6" style="text-align: center; color: #94a3b8; padding: 30px;">لم يتم رصد أو تسجيل أي إجازات أو طلبات مغادرة حتى الآن.</td></tr>
                        <?php else: ?>
                            <?php foreach ($leaves as $lv): ?>
                                <tr>
                                    <td style="font-weight: bold;"><?php echo esc_html($lv['start_date']); ?></td>
                                    <td style="font-weight: bold;"><?php echo esc_html($lv['end_date']); ?></td>
                                    <td><?php echo esc_html($lv['type']); ?></td>
                                    <td><?php echo esc_html($lv['days']); ?> أيام</td>
                                    <td style="font-size:12px; color:#475569;"><?php echo esc_html($lv['notes'] ?? ''); ?></td>
                                    <td>
                                        <span style="background:#dcfce7; color:#15803d; padding:2px 8px; border-radius:4px; font-weight:bold; font-size:11px;">
                                            <?php echo esc_html($lv['status'] ?? 'موافق عليه'); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Section 10: Administrative Notes -->
        <div id="wp-notes" class="wp-tab-content" style="display: none;">
            <h4 style="margin: 0 0 20px 0; font-weight: 800; color: #1e293b; font-size: 1.1rem; border-bottom: 2px solid #f1f5f9; padding-bottom: 10px;">📝 الملاحظات الإدارية المعتمدة والتوصيات المباشرة</h4>
            <div class="sm-table-container">
                <table class="sm-table" style="margin: 0; width: 100%;">
                    <thead>
                        <tr>
                            <th>التاريخ</th>
                            <th>القرار أو الإشعار الإداري</th>
                            <th>تفاصيل وملاحظات القسم المسؤول</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($admin_actions)): ?>
                            <tr><td colspan="3" style="text-align: center; color: #94a3b8; padding: 30px;">لا توجد أي ملاحظات أو توجيهات إدارية مرصودة في سجل الملف حالياً.</td></tr>
                        <?php else: ?>
                            <?php foreach ($admin_actions as $act): ?>
                                <tr>
                                    <td style="font-weight: bold;"><?php echo esc_html($act['date']); ?></td>
                                    <td style="font-weight: 700; color: #1e293b;"><?php echo esc_html($act['action']); ?></td>
                                    <td style="font-size: 12px; color: #475569;"><?php echo esc_html($act['notes']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Section 11: Activity Timeline -->
        <div id="wp-timeline" class="wp-tab-content" style="display: none;">
            <h4 style="margin: 0 0 20px 0; font-weight: 800; color: #1e293b; font-size: 1.1rem; border-bottom: 2px solid #f1f5f9; padding-bottom: 10px;">سجل الأنشطة والعمليات التاريخية</h4>
            <div style="padding: 10px 15px;">
                <?php if (empty($timeline)): ?>
                    <p style="color: #94a3b8; text-align: center; padding: 20px;">لا يوجد سجل أنشطة أو عمليات تاريخية مسجلة في هذا الملف بعد.</p>
                <?php else: ?>
                    <div style="position: relative; border-right: 2px solid #cbd5e1; padding-right: 20px; margin-right: 10px;">
                        <?php foreach ($timeline as $tl): ?>
                            <div style="position: relative; margin-bottom: 20px;">
                                <!-- Dot indicator -->
                                <span style="position: absolute; right: -26px; top: 4px; width: 10px; height: 10px; border-radius: 50%; background: var(--sm-primary-color); border: 2px solid white;"></span>
                                <div style="font-size: 11px; color: #64748b; font-weight: bold;"><?php echo esc_html($tl['date']); ?></div>
                                <div style="font-weight: bold; color: #1e293b; font-size: 13px; margin: 2px 0;"><?php echo esc_html($tl['action']); ?> <span style="font-weight:normal; font-size:11px; color:#64748b;">(بواسطة: <?php echo esc_html($tl['actor'] ?? 'النظام'); ?>)</span></div>
                                <div style="font-size: 12px; color: #475569;"><?php echo esc_html($tl['details']); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        </div>


    </div>

</div>

<?php include_once SM_PLUGIN_DIR . 'templates/partials/unified-user-modal.php'; ?>

<script>
var eessIsAdmin = <?php echo ($is_admin || $is_sys_admin) ? 'true' : 'false'; ?>;

// Tab Switching logic for 11 distinct sections inside Employee Profile
function switchEmployeeProfileTab(tabId, btn) {
    document.querySelectorAll('.wp-tab-content').forEach(el => el.style.display = 'none');
    const tabEl = document.getElementById(tabId);
    if (tabEl) tabEl.style.display = 'block';

    btn.parentElement.querySelectorAll('.sm-tab-btn').forEach(b => b.classList.remove('sm-active'));
    btn.classList.add('sm-active');
}

// Modal Toggle Utilities invoking Unified Modal directly
function eessOpenProfileEditModal() {
    eessOpenUnifiedUserModal('edit_employee_profile', <?php echo $target_user_id; ?>);
}
function eessCloseProfileEditModal() {
    eessCloseUnifiedUserModal();
}
</script>
