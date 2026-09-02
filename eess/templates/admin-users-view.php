<?php if (!defined('ABSPATH')) exit;

// Fetch pending approval registration requests
$pending_users = get_users(array(
    'meta_key'   => 'eess_approval_status',
    'meta_value' => 'pending'
));

$current_user = wp_get_current_user();
$current_role = $current_user->roles[0];

$hierarchy = array(
    'administrator' => 5,
    'sm_system_admin' => 4,
    'sm_principal' => 3,
    'sm_supervisor' => 2,
    'sm_coordinator' => 1,
    'sm_teacher' => 0,
    'sm_student' => -1,
    'sm_parent' => -2
);
$current_level = $hierarchy[$current_role] ?? -3;

$all_registered_schools = class_exists('EESS_Org_Helper') ? EESS_Org_Helper::get_schools() : array();
$all_subjects = class_exists('SM_DB') ? SM_DB::get_subjects() : array();
$unique_subjects = !empty($all_subjects) ? array_unique(array_map(function($s){ return is_object($s) ? $s->name : $s; }, $all_subjects)) : array();

// Fetch all users
$all_users = get_users();

$current_user_scope = EESS_Org_Helper::get_user_scope();
if (!$current_user_scope['unrestricted']) {
    $all_users = array_filter($all_users, function($u) use ($current_user_scope) {
        if ($u->ID == get_current_user_id()) return true;
        $u_scope = EESS_Org_Helper::get_user_scope($u->ID);
        if (empty($u_scope['schools'])) return true;
        $intersect = array_intersect($u_scope['schools'], $current_user_scope['schools']);
        return !empty($intersect);
    });
}

// Sort hierarchy for display ordering
$sort_hierarchy = array(
    'sm_student' => 0,
    'sm_teacher' => 1,
    'sm_coordinator' => 2,
    'sm_supervisor' => 3,
    'sm_principal' => 4,
    'sm_system_admin' => 5,
    'administrator' => 6
);

// Map of role values to Arabic labels
$role_map = array(
    'administrator' => 'الإدارة المركزية (المطور)',
    'sm_system_admin' => 'مدير النظام التقني',
    'sm_principal' => 'مدير المدرسة',
    'sm_supervisor' => 'مشرف تربوي',
    'sm_coordinator' => 'منسق مادة',
    'sm_hod' => 'رئيس قسم',
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

$all_subjects = SM_DB::get_subjects();
$unique_subjects = array_unique(array_map(function($s){ return $s->name; }, $all_subjects));
?>

<div class="sm-content-wrapper" style="font-family: 'Cairo', sans-serif;" dir="rtl">

    <!-- Single Main Banner Header (Matching Teacher Term & Annual Plans) -->
    <div style="background: #ffffff; padding: 20px 24px; border-radius: 20px; border: 1px solid #e2e8f0; margin-bottom: 18px; box-shadow: 0 4px 18px rgba(0,0,0,0.02); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
        <div style="display: flex; align-items: center; gap: 14px;">
            <div style="width: 48px; height: 48px; background: #fef2f2; border-radius: 14px; display: flex; align-items: center; justify-content: center; color: #881337; border: 1px solid #fecdd3; flex-shrink: 0;">
                <span class="dashicons dashicons-admin-users" style="font-size: 24px; width: 24px; height: 24px;"></span>
            </div>
            <div>
                <h2 style="margin: 0 0 4px 0; font-size: 20px; font-weight: 800; color: #0f172a;">إدارة مستخدمي النظام</h2>
                <p style="margin: 0; font-size: 12.5px; color: #64748b; font-weight: 500;">إدارة وتعديل حسابات الكادر التعليمي والإداري، التخصصات والصلاحيات في المنصة</p>
            </div>
        </div>

        <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
            <!-- User Options Dropdown -->
            <div style="position: relative; display: inline-block;">
                <button type="button" onclick="eessToggleUserOptionsDropdown(event)" class="sm-btn sm-btn-outline" style="height: 38px; display: inline-flex; align-items: center; gap: 6px; border-radius: 9999px !important; cursor: pointer; background: #ffffff; color: #334155; border: 1px solid #cbd5e1; font-weight: 800; font-size: 12.5px; padding: 0 16px;">
                    <span class="dashicons dashicons-admin-generic" style="font-size: 16px; width: 16px; height: 16px; margin: 0; color: #475569;"></span>
                    <span>خيارات المستخدمين</span>
                    <span class="dashicons dashicons-arrow-down-alt2" style="font-size: 10px; width: 10px; height: 10px; margin: 0;"></span>
                </button>
                <div id="eess-user-options-dropdown" style="display: none; position: absolute; left: 0; top: 115%; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 14px; width: 220px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); z-index: 99999; padding: 6px 0; text-align: right;">
                    <a href="javascript:void(0)" onclick="document.getElementById('user-csv-import-box').style.display = document.getElementById('user-csv-import-box').style.display === 'none' ? 'block' : 'none'; document.getElementById('eess-user-options-dropdown').style.display='none';" style="display: block; padding: 10px 16px; color: #334155; font-size: 12px; text-decoration: none; border-bottom: 1px solid #f1f5f9; font-weight: 700;">📥 استيراد مستخدمين (CSV)</a>
                    <a href="<?php echo admin_url('admin-ajax.php?action=sm_export_users_csv&nonce=' . wp_create_nonce('eess_admin_action')); ?>" onclick="document.getElementById('eess-user-options-dropdown').style.display='none';" style="display: block; padding: 10px 16px; color: #334155; font-size: 12px; text-decoration: none; font-weight: 700;">📤 تصدير مستخدمين (CSV)</a>
                </div>
            </div>

            <?php if (current_user_can('manage_options') || current_user_can('إدارة_المستخدمين')): ?>
            <button type="button" onclick="eessOpenUnifiedUserModal('add_user', 0)" class="sm-btn" style="background: #881337; color: #ffffff !important; height: 38px; border-radius: 9999px !important; padding: 0 20px; font-weight: 800; font-size: 12.5px; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
                <span class="dashicons dashicons-plus-alt2" style="font-size: 15px; width: 15px; height: 15px; color: #fff;"></span>
                <span>إضافة مستخدم جديد</span>
            </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- User Management Tabs -->
    <div style="display: flex; gap: 15px; margin-bottom: 20px; border-bottom: 2px solid #e2e8f0; padding-bottom: 10px;">
        <button onclick="switchUsersTab('users-list-tab', this)" class="sm-tab-btn sm-active" style="border: none; font-size: 14px; font-weight: 800; padding: 8px 20px; border-radius: 9999px; cursor: pointer;">
            إدارة المستخدمين النشطين
        </button>
        <button onclick="switchUsersTab('registration-requests-tab', this)" class="sm-tab-btn" style="border: none; font-size: 14px; font-weight: 800; padding: 8px 20px; border-radius: 9999px; cursor: pointer; position: relative;">
            طلبات التسجيل المعلقة
            <?php if (!empty($pending_users)): ?>
                <span style="background: #e53e3e; color: white; border-radius: 10px; padding: 1px 7px; font-size: 10px; font-weight: 800; margin-right: 5px; position: absolute; top: -5px; left: -5px;">
                    <?php echo count($pending_users); ?>
                </span>
            <?php endif; ?>
        </button>
    </div>

    <!-- TAB 1: ALL USERS LIST -->
    <div id="users-list-tab" class="users-tab-panel" style="display: block;">

        <!-- CSV Import Box -->
        <div id="user-csv-import-box" style="display:none; background: #f8fafc; padding: 20px; border: 2px dashed #cbd5e0; border-radius: 12px; margin-bottom: 25px;">
            <h4 style="margin-top:0; color:var(--sm-secondary-color); font-weight: 800; font-size: 15px;">استيراد المستخدمين الشامل من ملف CSV</h4>
            <p style="font-size:12px; color:#64748b; margin-bottom:15px; line-height:1.6;">يرجى تجهيز ملف CSV الخاص بك بحيث يضم الحقول التالية بالترتيب: <strong>اسم المستخدم، البريد، الاسم الكامل، الدور (مثال: sm_teacher)، الجوال، كلمة المرور، رابط الصورة الشخصية، التخصص</strong>.</p>
            <form method="post" enctype="multipart/form-data">
                <?php wp_nonce_field('sm_admin_action', 'sm_admin_nonce'); ?>
                <div style="display:flex; gap:15px; align-items:center;">
                    <input type="file" name="csv_file" accept=".csv" required class="sm-input" style="width:auto; font-size:12px;">
                    <button type="submit" name="sm_import_users_csv" class="sm-btn" style="width:auto; font-size:12px;">تأكيد وبدء الاستيراد</button>
                </div>
            </form>
        </div>

        <!-- Search and Advanced Filtering Panel -->
        <div style="background: #f8fafc; padding: 18px; border-radius: 12px; border: 1px solid #e2e8f0; margin-bottom: 20px;">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 15px;">
                <!-- Full Text Search -->
                <div>
                    <label class="sm-label" style="font-weight: 700; font-size: 12px;">البحث الفوري</label>
                    <input type="text" id="user-engine-search" onkeyup="filterSystemUsers()" placeholder="ابحث بالاسم، المسمى، البريد، الرقم الوظيفي..." class="sm-input" style="height: 38px; font-size: 12px;">
                </div>
                <!-- Role Filter -->
                <div>
                    <label class="sm-label" style="font-weight: 700; font-size: 12px;">تصفية حسب المسمى الوظيفي</label>
                    <select id="user-engine-role" onchange="filterSystemUsers()" class="sm-select" style="height: 38px; font-size: 12px;">
                        <option value="">الكل</option>
                        <?php foreach($role_map as $val => $lbl): ?>
                            <option value="<?php echo esc_attr($val); ?>"><?php echo esc_html($lbl); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <!-- Subject Filter -->
                <div>
                    <label class="sm-label" style="font-weight: 700; font-size: 12px;">تصفية حسب التخصص</label>
                    <select id="user-engine-subject" onchange="filterSystemUsers()" class="sm-select" style="height: 38px; font-size: 12px;">
                        <option value="">الكل</option>
                        <?php foreach($unique_subjects as $subj): ?>
                            <option value="<?php echo esc_attr($subj); ?>"><?php echo esc_html($subj); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <!-- Status Filter -->
                <div>
                    <label class="sm-label" style="font-weight: 700; font-size: 12px;">حالة الحساب</label>
                    <select id="user-engine-status" onchange="filterSystemUsers()" class="sm-select" style="height: 38px; font-size: 12px;">
                        <option value="">الكل</option>
                        <option value="active">نشط / مفعل</option>
                        <option value="restricted">مقيد / محظور</option>
                    </select>
                </div>
            </div>

            <!-- Sorting & Bulk Action Row -->
            <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #e2e8f0; padding-top: 15px; flex-wrap: wrap; gap: 15px;">
                <!-- Bulk Actions -->
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span style="font-size: 12px; font-weight: 800; color: #4a5568;">العمليات الجماعية:</span>
                    <select id="user-bulk-action" class="sm-select" style="width: 140px; height: 32px; padding: 0 8px; font-size: 11px;">
                        <option value="">اختر الإجراء...</option>
                        <option value="activate">تنشيط الحسابات</option>
                        <option value="restrict">حظر / تقييد</option>
                        <option value="delete">حذف نهائي</option>
                    </select>
                    <button onclick="executeUserBulkAction()" class="sm-btn" style="height: 32px; padding: 0 12px; font-size: 11px; background: #475569;">تطبيق</button>
                </div>

                <!-- Sorting Dropdown -->
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span style="font-size: 12px; font-weight: 800; color: #4a5568;">ترتيب حسب:</span>
                    <select id="user-engine-sort" onchange="filterSystemUsers()" class="sm-select" style="width: 160px; height: 32px; padding: 0 8px; font-size: 11px;">
                        <option value="name_asc">الاسم (أ - ي)</option>
                        <option value="name_desc">الاسم (ي - أ)</option>
                        <option value="date_desc">الأحدث تسجيلاً</option>
                        <option value="date_asc">الأقدم تسجيلاً</option>
                        <option value="role">المسمى الوظيفي</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Users Table Container -->
        <div class="sm-table-container">
            <table class="sm-table" id="system-users-table">
                <thead>
                    <tr>
                        <th style="width: 40px; text-align: center;"><input type="checkbox" id="select-all-users" onclick="toggleAllUsersCheckbox(this)"></th>
                        <th>المستخدم</th>
                        <th>البريد الإلكتروني</th>
                        <th>المسمى الوظيفي / الرتبة</th>
                        <th>حالة الحساب</th>
                        <th>كلمة المرور</th>
                        <th style="text-align: left;">الإجراءات الإدارية</th>
                    </tr>
                </thead>
                <tbody id="users-table-body">
                    <?php
                    usort($all_users, function($a, $b) use ($sort_hierarchy) {
                        $lvl_a = $sort_hierarchy[$a->roles[0]] ?? 99;
                        $lvl_b = $sort_hierarchy[$b->roles[0]] ?? 99;
                        return $lvl_a <=> $lvl_b;
                    });

                    foreach ($all_users as $u):
                        $u_role = !empty($u->roles) ? $u->roles[0] : '';
                        $u_level = $hierarchy[$u_role] ?? -3;

                        // Hierarchical Security Check
                        if ($u_level > $current_level && !current_user_can('administrator')) continue;

                        $u_spec = get_user_meta($u->ID, 'sm_specialization', true) ?: '';
                        $u_emp = get_user_meta($u->ID, 'eess_employee_number', true) ?: '';
                        $u_inst = get_user_meta($u->ID, 'eess_school_name', true) ?: '';
                        $u_school_id = get_user_meta($u->ID, 'eess_school_id', true) ?: '';
                        if (empty($u_school_id) && !empty($u_inst)) {
                            global $wpdb;
                            $u_school_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}eess_schools WHERE name = %s", $u_inst)) ?: '';
                        }
                        $u_dept = get_user_meta($u->ID, 'eess_department', true) ?: '';
                        $u_status = get_user_meta($u->ID, 'sm_account_status', true) ?: 'active';
                        $u_reg_status = get_user_meta($u->ID, 'eess_approval_status', true) ?: 'approved';
                        $u_notes = get_user_meta($u->ID, 'eess_admin_notes', true) ?: '';

                        $u_registered_raw = $u->user_registered;

                        $u_data = array(
                            "id" => $u->ID,
                            "name" => $u->display_name,
                            "email" => $u->user_email,
                            "login" => $u->user_login,
                            "role" => $u_role,
                            "photo" => get_user_meta($u->ID, 'eess_profile_photo', true),
                            "specialization" => $u_spec,
                            "employee_number" => $u_emp,
                            "institution" => $u_school_id,
                            "department" => $u_dept,
                            "status" => $u_status,
                            "notes" => $u_notes
                        );
                    ?>
                        <tr class="system-user-row"
                            data-id="<?php echo $u->ID; ?>"
                            data-name="<?php echo esc_attr(strtolower($u->display_name)); ?>"
                            data-login="<?php echo esc_attr(strtolower($u->user_login)); ?>"
                            data-email="<?php echo esc_attr(strtolower($u->user_email)); ?>"
                            data-role="<?php echo esc_attr($u_role); ?>"
                            data-subject="<?php echo esc_attr($u_spec); ?>"
                            data-emp="<?php echo esc_attr($u_emp); ?>"
                            data-inst="<?php echo esc_attr(strtolower($u_inst)); ?>"
                            data-dept="<?php echo esc_attr(strtolower($u_dept)); ?>"
                            data-status="<?php echo esc_attr($u_status); ?>"
                            data-registered="<?php echo strtotime($u_registered_raw); ?>"
                        >
                            <td style="text-align: center;">
                                <input type="checkbox" class="user-checkbox" value="<?php echo $u->ID; ?>" <?php if($u->ID == get_current_user_id()) echo 'disabled'; ?>>
                            </td>
                            <td style="padding: 12px 16px; vertical-align: middle;">
                                <div style="display:flex; align-items:center; gap:12px;">
                                    <div style="flex-shrink: 0; width: 36px; height: 36px;">
                                        <?php echo get_avatar($u->ID, 36, '', '', array('style' => 'border-radius:50%; width: 36px; height: 36px; object-fit: cover; border: 1px solid #cbd5e1;')); ?>
                                    </div>
                                    <div style="min-width: 0; flex: 1;">
                                        <div style="font-weight: 800; font-size: 13.5px; color: #0f172a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 220px;" title="<?php echo esc_attr($u->display_name); ?>">
                                            <?php echo esc_html($u->display_name); ?>
                                        </div>
                                        <?php
                                        $u_nat = get_user_meta($u->ID, 'nationality', true) ?: (get_user_meta($u->ID, 'sm_nationality', true) ?: 'عام');
                                        $emp_code = get_user_meta($u->ID, 'eess_employee_number', true) ?: (get_user_meta($u->ID, 'sm_employee_id', true) ?: $u->user_login);
                                        $app_year = intval(get_user_meta($u->ID, 'eess_appointment_year', true) ?: get_user_meta($u->ID, 'sm_appointment_year', true));
                                        $exp_years = $app_year > 0 ? (intval(date('Y')) - $app_year) : 0;
                                        ?>
                                        <div style="margin-top: 4px; display: flex; gap: 5px; align-items: center; flex-wrap: wrap;">
                                            <span style="display: inline-flex; align-items: center; padding: 2px 8px; border-radius: 6px; background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; font-size: 10.5px; font-weight: 700; font-family: monospace;">
                                                @<?php echo esc_html($u->user_login); ?>
                                            </span>
                                            <span style="display: inline-flex; align-items: center; padding: 2px 8px; border-radius: 6px; background: #fdf4ff; color: #86198f; border: 1px solid #f5d0fe; font-size: 10.5px; font-weight: 800;">
                                                <?php echo esc_html($u_nat); ?>
                                            </span>
                                            <span style="display: inline-flex; align-items: center; padding: 2px 8px; border-radius: 6px; background: #fef2f2; color: #881337; border: 1px solid #fecdd3; font-size: 10.5px; font-weight: 800; font-family: monospace;">
                                                <?php echo esc_html($emp_code); ?>
                                            </span>
                                            <span style="display: inline-flex; align-items: center; padding: 2px 8px; border-radius: 6px; background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; font-size: 10.5px; font-weight: 800;">
                                                خبرة: <?php echo $exp_years > 0 ? ($exp_years . ' سنوات') : 'جديد'; ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td style="font-size: 12px;"><?php echo esc_html($u->user_email); ?></td>
                            <td>
                                <div style="font-weight:700; font-size: 12px; color: #334155;">
                                    <?php echo $role_map[$u_role] ?? $u_role; ?>
                                </div>
                                <?php if (!empty($u_spec)): ?>
                                    <div style="font-size:11px; color:var(--sm-primary-color); font-weight:700;">التخصص: <?php echo esc_html($u_spec); ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($u_status === 'restricted'): ?>
                                    <span style="display:inline-block; padding: 2px 8px; font-size: 10px; background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; border-radius: 4px; font-weight: bold;">محظور / مقيد</span>
                                <?php else: ?>
                                    <span style="display:inline-block; padding: 2px 8px; font-size: 10px; background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; border-radius: 4px; font-weight: bold;">نشط</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <code style="background:#f1f5f9; padding:2px 6px; border-radius:4px; font-family:monospace; font-size: 11px;"><?php echo get_user_meta($u->ID, 'sm_temp_pass', true) ?: '********'; ?></code>
                            </td>
                            <td style="text-align: center;">
                                <div style="display: flex; align-items: center; justify-content: center; gap: 6px;">
                                    <?php
                                    $u_phone = get_user_meta($u->ID, 'sm_phone', true) ?: (get_user_meta($u->ID, 'phone_number', true) ?: (get_user_meta($u->ID, 'guardian_phone', true) ?: ''));
                                    $formatted_u_phone = SM_Settings::format_uae_phone($u_phone);
                                    if (!empty($formatted_u_phone)):
                                        $wa_text = rawurlencode("السلام عليكم ورحمة الله وبركاته، الأخ/ت العزيز/ة " . $u->display_name);
                                    ?>
                                        <a href="https://wa.me/<?php echo $formatted_u_phone; ?>?text=<?php echo $wa_text; ?>" target="_blank" title="تواصل عبر واتساب" style="width: 36px; height: 36px; border-radius: 50% !important; flex-shrink: 0; background: #dcfce7; color: #16a34a; border: 1px solid #86efac; display: inline-flex; align-items: center; justify-content: center; text-decoration: none; transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.08)'" onmouseout="this.style.transform='scale(1)'">
                                            <span class="dashicons dashicons-whatsapp" style="font-size: 18px; width: 18px; height: 18px; margin: 0;"></span>
                                        </a>
                                    <?php endif; ?>

                                    <button type="button" onclick="eessOpenUnifiedUserModal('edit_user', <?php echo $u->ID; ?>)" title="تعديل المستخدم" style="width: 36px; height: 36px; border-radius: 50% !important; flex-shrink: 0; background: #fef2f2; color: #881337; border: 1px solid #fecdd3; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.08)'" onmouseout="this.style.transform='scale(1)'">
                                        <span class="dashicons dashicons-edit" style="font-size: 16px; width: 16px; height: 16px; margin: 0;"></span>
                                    </button>

                                    <?php if ($u->ID != get_current_user_id()): ?>
                                        <form method="post" style="display:inline;" onsubmit="return confirm('حذف هذا المستخدم نهائياً؟')">
                                            <?php wp_nonce_field('sm_user_action', 'sm_nonce'); ?>
                                            <input type="hidden" name="delete_user_id" value="<?php echo $u->ID; ?>">
                                            <button type="submit" name="sm_delete_user" title="حذف المستخدم" style="width: 36px; height: 36px; border-radius: 50% !important; flex-shrink: 0; background: #fee2e2; color: #dc2626; border: none; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.08)'" onmouseout="this.style.transform='scale(1)'">
                                                <span class="dashicons dashicons-trash" style="font-size: 16px; width: 16px; height: 16px; margin: 0;"></span>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- TAB 2: REGISTRATION REQUESTS SECTION -->
    <div id="registration-requests-tab" class="users-tab-panel" style="display: none;">
        <div style="background: #fff; border: 1px solid #e2e8f0; padding: 25px; border-radius: 12px; margin-bottom: 25px;">
            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 15px; border-bottom: 2px solid #f1f5f9; padding-bottom: 12px;">
                <span class="dashicons dashicons-id-alt" style="color: var(--sm-primary-color); font-size: 24px; width: 24px; height: 24px; margin-top: -3px;"></span>
                <h4 style="margin: 0; color: #1e293b; font-weight: 800; font-size: 1.2em;">طلبات التسجيل قيد الانتظار والمراجعة</h4>
            </div>
            <p style="margin: 0 0 20px 0; font-size: 13px; color: #64748b; line-height: 1.6;">تم تقديم طلبات التسجيل التالية ذاتياً من قبل الموظفين الجدد عبر المنصة. يرجى مراجعة وفحص معلومات المتقدمين، إضافة الملاحظات الإدارية، ثم اعتماد أو رفض تفعيل الحساب.</p>

            <?php if (empty($pending_users)): ?>
                <div style="text-align: center; padding: 40px; color: #94a3b8; background: #f8fafc; border-radius: 8px; border: 1px dashed #e2e8f0;">
                    <span class="dashicons dashicons-yes-alt" style="font-size: 45px; width: 45px; height: 45px; color: #16a34a; margin-bottom: 10px;"></span>
                    <p style="font-weight: 700; margin: 0; font-size: 14px; color: #1e293b;">لا توجد طلبات تسجيل معلقة حالياً</p>
                    <p style="margin: 5px 0 0 0; font-size: 12px;">تم الانتهاء من مراجعة كافة الطلبات بنجاح.</p>
                </div>
            <?php else: ?>
                <!-- Professional 2-Column Responsive Card Grid on Desktop -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(380px, 1fr)); gap: 20px;">
                    <?php foreach ($pending_users as $pu):
                        $emp_num = get_user_meta($pu->ID, 'eess_employee_number', true) ?: 'غير محدد';
                        $school_name = get_user_meta($pu->ID, 'eess_school_name', true) ?: 'غير محدد';
                        $admin_notes = get_user_meta($pu->ID, 'eess_admin_notes', true) ?: '';

                        $role_label = 'مستخدم';
                        if (in_array('sm_teacher', (array)$pu->roles)) $role_label = 'معلم / هيئة تدريس';
                        elseif (in_array('sm_coordinator', (array)$pu->roles)) $role_label = 'منسق مادة';
                        elseif (in_array('sm_supervisor', (array)$pu->roles)) $role_label = 'مشرف تربوي';
                        elseif (in_array('sm_principal', (array)$pu->roles)) $role_label = 'مدير مدرسة';
                        elseif (in_array('sm_clinic', (array)$pu->roles)) $role_label = 'زائر صحي';
                    ?>
                    <div id="pending-card-<?php echo $pu->ID; ?>" style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 16px; padding: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.03); display: flex; flex-direction: column; justify-content: space-between; gap: 14px;">

                        <div>
                            <!-- Header of Applicant Card -->
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 10px; border-bottom: 1px solid #f1f5f9; padding-bottom: 12px; margin-bottom: 12px;">
                                <div>
                                    <h5 style="margin: 0; font-weight: 800; font-size: 15px; color: #0f172a;"><?php echo esc_html($pu->display_name ?: $pu->user_login); ?></h5>
                                    <div style="font-size: 11px; color: #64748b; font-family: monospace; margin-top: 3px;"><?php echo esc_html($pu->user_email); ?></div>
                                </div>
                                <span style="font-size: 11px; color: #881337; background: #fef2f2; border: 1px solid #fecdd3; padding: 3px 12px; border-radius: 50px; font-weight: 800;">
                                    <?php echo $role_label; ?>
                                </span>
                            </div>

                            <!-- Applicant Details Grid -->
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; font-size: 12px; color: #334155; margin-bottom: 14px;">
                                <div><strong>الرقم الوظيفي:</strong> <span style="font-family: monospace; font-weight: bold; color: #8b1e1e;"><?php echo esc_html($emp_num); ?></span></div>
                                <div><strong>المؤسسة:</strong> <span style="font-weight: bold; color: #0284c7;"><?php echo esc_html($school_name); ?></span></div>
                                <div style="grid-column: span 2;"><strong>تاريخ تقديم الطلب:</strong> <span style="color: #64748b; font-family: monospace;"><?php echo date_i18n('Y-m-d H:i', strtotime($pu->user_registered)); ?></span></div>
                            </div>

                            <!-- Internal Admin Notes Field -->
                            <div style="background: #f8fafc; border: 1px solid #cbd5e1; padding: 10px 12px; border-radius: 10px; margin-bottom: 10px;">
                                <label class="sm-label" style="font-size: 11px; font-weight: 700; margin-bottom: 4px; display: block; color: #475569;">ملاحظات المراجعة الداخلية:</label>
                                <div style="display: flex; gap: 8px;">
                                    <textarea id="notes-input-<?php echo $pu->ID; ?>" class="sm-input" style="height: 36px; padding: 6px; font-size: 11.5px; border-radius: 8px; flex: 1;" placeholder="اكتب ملاحظات الحساب..."><?php echo esc_textarea($admin_notes); ?></textarea>
                                    <button onclick="saveRegistrationNotes(<?php echo $pu->ID; ?>)" class="sm-btn" style="height: 36px; width: auto; font-size: 11px; background: #334155; padding: 0 12px; border-radius: 8px; color: white !important;">حفظ</button>
                                </div>
                            </div>
                        </div>

                        <!-- Actions Row -->
                        <div style="display: flex; gap: 8px; justify-content: space-between; align-items: center; border-top: 1px solid #f1f5f9; padding-top: 12px; flex-wrap: wrap;">
                            <button type="button" onclick="eessOpenUnifiedUserModal('edit_user', <?php echo $pu->ID; ?>)" class="sm-btn sm-btn-outline" style="height: 34px; padding: 0 12px; font-size: 11.5px; font-weight: 700; border-radius: 9999px !important; color: #334155;">تعديل البيانات والرتبة</button>

                            <div style="display: flex; gap: 6px;">
                                <button type="button" onclick="eessApproveUser(<?php echo $pu->ID; ?>)" class="sm-btn" style="height: 34px; padding: 0 14px; font-size: 11.5px; font-weight: 800; background-color: #16a34a !important; color: white !important; border-radius: 9999px !important; border: none;">اعتماد الحساب</button>
                                <button type="button" onclick="eessRejectUser(<?php echo $pu->ID; ?>)" class="sm-btn" style="height: 34px; padding: 0 12px; font-size: 11.5px; font-weight: 800; background-color: #dc2626 !important; color: white !important; border-radius: 9999px !important; border: none;">رفض</button>
                            </div>
                        </div>

                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<?php include_once SM_PLUGIN_DIR . 'templates/partials/unified-user-modal.php'; ?>
<!-- UNIFIED MODAL REPLACES ALL LEGACY USER MODALS -->

<script>
// Switch Tabs Client-side
function switchUsersTab(tabId, btn) {
    document.querySelectorAll('.users-tab-panel').forEach(p => p.style.display = 'none');
    const panel = document.getElementById(tabId);
    if (panel) panel.style.display = 'block';

    document.querySelectorAll('.sm-tab-btn').forEach(b => b.classList.remove('sm-active'));
    if (btn) btn.classList.add('sm-active');
}

// Client-side Realtime Search, Filtering, and Sorting Engine
function filterSystemUsers() {
    const query = document.getElementById('user-engine-search').value.toLowerCase().trim();
    const filterRole = document.getElementById('user-engine-role').value;
    const filterSubject = document.getElementById('user-engine-subject').value;
    const filterStatus = document.getElementById('user-engine-status').value;
    const sortVal = document.getElementById('user-engine-sort').value;

    const rows = Array.from(document.querySelectorAll('.system-user-row'));

    rows.forEach(row => {
        const uId = row.getAttribute('data-id');
        const uName = row.getAttribute('data-name');
        const uLogin = row.getAttribute('data-login');
        const uEmail = row.getAttribute('data-email');
        const uRole = row.getAttribute('data-role');
        const uSubject = row.getAttribute('data-subject');
        const uEmp = row.getAttribute('data-emp');
        const uInst = row.getAttribute('data-inst');
        const uDept = row.getAttribute('data-dept');
        const uStatus = row.getAttribute('data-status');

        // Check search match
        const matchesQuery = !query ||
                             uName.includes(query) ||
                             uLogin.includes(query) ||
                             uEmail.includes(query) ||
                             uEmp.includes(query) ||
                             uInst.includes(query) ||
                             uDept.includes(query);

        // Check filters
        const matchesRole = !filterRole || uRole === filterRole;
        const matchesSubject = !filterSubject || uSubject === filterSubject;
        const matchesStatus = !filterStatus || uStatus === filterStatus;

        if (matchesQuery && matchesRole && matchesSubject && matchesStatus) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });

    // Client-side Sorting
    const visibleRows = rows.filter(r => r.style.display !== 'none');
    const hiddenRows = rows.filter(r => r.style.display === 'none');

    visibleRows.sort((a, b) => {
        if (sortVal === 'name_asc') {
            return a.getAttribute('data-name').localeCompare(b.getAttribute('data-name'));
        } else if (sortVal === 'name_desc') {
            return b.getAttribute('data-name').localeCompare(a.getAttribute('data-name'));
        } else if (sortVal === 'date_desc') {
            return parseInt(b.getAttribute('data-registered')) - parseInt(a.getAttribute('data-registered'));
        } else if (sortVal === 'date_asc') {
            return parseInt(a.getAttribute('data-registered')) - parseInt(b.getAttribute('data-registered'));
        } else if (sortVal === 'role') {
            return a.getAttribute('data-role').localeCompare(b.getAttribute('data-role'));
        }
        return 0;
    });

    const tbody = document.getElementById('users-table-body');
    tbody.innerHTML = '';
    visibleRows.concat(hiddenRows).forEach(r => tbody.appendChild(r));
}

// Master Checkbox Toggle
function toggleAllUsersCheckbox(master) {
    document.querySelectorAll('.user-checkbox:not(:disabled)').forEach(cb => cb.checked = master.checked);
}

// Execute Bulk Actions
function executeUserBulkAction() {
    const selected = Array.from(document.querySelectorAll('.user-checkbox:checked')).map(cb => cb.value);
    const action = document.getElementById('user-bulk-action').value;

    if (selected.length === 0) {
        alert('يرجى تحديد مستخدمين أولاً لتطبيق الإجراء جماعياً.');
        return;
    }
    if (!action) {
        alert('يرجى تحديد إجراء جماعي أولاً.');
        return;
    }

    if (action === 'delete') {
        if (!confirm(`هل أنت متأكد من حذف ${selected.length} مستخدم نهائياً من قاعدة البيانات؟`)) return;

        const formData = new FormData();
        formData.append('action', 'sm_bulk_delete_users_ajax');
        formData.append('user_ids', selected.join(','));
        formData.append('nonce', '<?php echo wp_create_nonce("sm_teacher_action"); ?>');

        fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                smShowNotification(`تم حذف ${selected.length} مستخدم بنجاح`);
                setTimeout(() => location.reload(), 600);
            }
        });
    } else {
        // Activate/Restrict Action
        if (!confirm(`تطبيق هذا الإجراء على ${selected.length} مستخدم؟`)) return;

        let processed = 0;
        selected.forEach(userId => {
            const formData = new FormData();
            formData.append('action', 'sm_update_teacher_ajax');
            formData.append('edit_teacher_id', userId);

            // Need display_name & role to satisfy standard update
            const row = document.querySelector(`.system-user-row[data-id="${userId}"]`);
            formData.append('display_name', row.querySelector('div[style*="font-weight: 700"]').innerText);
            formData.append('role', row.getAttribute('data-role'));
            formData.append('account_status', action === 'activate' ? 'active' : 'restricted');
            formData.append('sm_nonce', '<?php echo wp_create_nonce("sm_teacher_action"); ?>');

            fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(res => {
                processed++;
                if (processed === selected.length) {
                    smShowNotification('تم تحديث حالة المستخدمين المحددين جماعياً بنجاح.');
                    setTimeout(() => location.reload(), 600);
                }
            });
        });
    }
}

// Save Internal Notes on registration requests
function saveRegistrationNotes(userId) {
    const notesVal = document.getElementById('notes-input-' + userId).value;

    const data = new FormData();
    data.append('action', 'eess_save_user_notes');
    data.append('user_id', userId);
    data.append('notes', notesVal);
    data.append('nonce', '<?php echo wp_create_nonce("eess_admin_action"); ?>');

    fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: data })
    .then(res => res.json())
    .then(res => {
        if (res.success) {
            smShowNotification('تم حفظ الملاحظات الإدارية الداخلية بنجاح.');
        } else {
            smShowNotification('فشل الحفظ: ' + res.data, true);
        }
    });
}

// Permanently delete registration requests before approval
function permanentlyDeleteUserRequest(userId) {
    if (!confirm('تحذير: هل أنت متأكد من حذف هذا المتقدم وحذف حسابه المعلق بالكامل؟ لا يمكن التراجع عن هذا الإجراء.')) return;

    const data = new FormData();
    data.append('action', 'sm_bulk_delete_users_ajax');
    data.append('user_ids', userId);
    data.append('nonce', '<?php echo wp_create_nonce("sm_teacher_action"); ?>');

    fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: data })
    .then(res => res.json())
    .then(res => {
        if (res.success) {
            smShowNotification('تم حذف طلب التسجيل وحساب الموظف المعلق نهائياً.');
            const card = document.getElementById('pending-card-' + userId);
            if (card) card.remove();
            setTimeout(() => { location.reload(); }, 600);
        } else {
            smShowNotification('فشل الحذف: ' + res.data, true);
        }
    });
}

// Helper registration flow triggers
function eessApproveUser(userId) {
    var proceed = function() {
        const data = new FormData();
        data.append('action', 'eess_approve_user');
        data.append('user_id', userId);
        data.append('nonce', '<?php echo wp_create_nonce("eess_admin_action"); ?>');

        fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: data })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                smShowNotification('تم اعتماد وتفعيل الحساب بنجاح وإرسال إشعار للمستخدم.');
                const card = document.getElementById('pending-card-' + userId);
                if (card) card.remove();
                setTimeout(() => { location.reload(); }, 1000);
            } else {
                smShowNotification('فشل الاعتماد: ' + res.data, true);
            }
        });
    };

    if (typeof window.smConfirmAction === 'function') {
        window.smConfirmAction({
            title: 'اعتماد حساب الموظف',
            message: 'هل أنت متأكد من رغبتك في اعتماد وتنشيط حساب هذا الموظف؟',
            type: 'success',
            confirmText: 'اعتماد وتفعيل'
        }).then(function(confirmed) {
            if (confirmed) proceed();
        });
    } else {
        if (confirm('هل أنت متأكد من رغبتك في اعتماد وتنشيط حساب هذا الموظف؟')) proceed();
    }
}

function eessRejectUser(userId) {
    var proceed = function() {
        const data = new FormData();
        data.append('action', 'eess_reject_user');
        data.append('user_id', userId);
        data.append('nonce', '<?php echo wp_create_nonce("eess_admin_action"); ?>');

        fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: data })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                smShowNotification('تم رفض طلب التسجيل وحذف الحساب بنجاح.');
                const card = document.getElementById('pending-card-' + userId);
                if (card) card.remove();
                setTimeout(() => { location.reload(); }, 1000);
            } else {
                smShowNotification('فشل الرفض: ' + res.data, true);
            }
        });
    };

    if (typeof window.smConfirmAction === 'function') {
        window.smConfirmAction({
            title: 'رفض حساب الموظف',
            message: 'هل أنت متأكد من رفض طلب هذا المستخدم وحذف حسابه المعلق نهائياً؟',
            type: 'danger',
            confirmText: 'رفض وحذف الحساب'
        }).then(function(confirmed) {
            if (confirmed) proceed();
        });
    } else {
        if (confirm('هل أنت متأكد من رفض طلب هذا المستخدم وحذف حسابه المعلق نهائياً؟')) proceed();
    }
}

// Avatar Previews
(function() {
    window.previewProfilePhoto = function(input, mode) {
        const file = input.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const img = document.getElementById(mode + '_photo_preview');
                if (img) {
                    img.src = e.target.result;
                    img.style.display = 'block';
                }
                const btn = document.getElementById(mode + '_remove_photo_btn');
                if (btn) btn.style.display = 'inline-flex';
            };
            reader.readAsDataURL(file);
        }
    };

    window.removeSelectedPhoto = function(mode) {
        const input = document.querySelector(`#${mode}-user-form input[type="file"]`);
        if (input) input.value = '';
        const img = document.getElementById(mode + '_photo_preview');
        if (img) {
            img.src = '';
            img.style.display = 'none';
        }
        const btn = document.getElementById(mode + '_remove_photo_btn');
        if (btn) btn.style.display = 'none';

        if (mode === 'edit') {
            const flag = document.getElementById('edit_delete_photo_flag');
            if (flag) flag.value = '1';
        }
    };

    window.toggleSpecialization = function(select, mode = 'add') {
        const form = select.closest('form');
        const group = form.querySelector('.spec-group');
        const hodGroup = form.querySelector('.hod-inst-group');
        if (select.value === 'sm_teacher' || select.value === 'sm_coordinator' || select.value === 'sm_hod') {
            if (group) group.style.display = 'block';
        } else {
            if (group) group.style.display = 'none';
        }
        if (select.value === 'sm_hod') {
            if (hodGroup) hodGroup.style.display = 'block';
        } else {
            if (hodGroup) hodGroup.style.display = 'none';
        }
    };

    window.editSmGenericUser = function(u) {
        document.getElementById('edit_u_id').value = u.id;
        document.getElementById('edit_u_name').value = u.name;
        document.getElementById('edit_u_email').value = u.email;
        document.getElementById('edit_u_role').value = u.role;
        document.getElementById('edit_u_spec').value = u.specialization || '';
        document.getElementById('edit_u_emp').value = u.employee_number || '';
        document.getElementById('edit_u_inst').value = u.institution || '';
        document.getElementById('edit_u_dept').value = u.department || '';
        document.getElementById('edit_u_status').value = u.status || 'active';
        document.getElementById('edit_delete_photo_flag').value = '0';

        const img = document.getElementById('edit_photo_preview');
        const btn = document.getElementById('edit_remove_photo_btn');
        if (u.photo) {
            img.src = u.photo;
            img.style.display = 'block';
            btn.style.display = 'inline-flex';
        } else {
            img.src = '';
            img.style.display = 'none';
            btn.style.display = 'none';
        }

        toggleSpecialization(document.getElementById('edit_u_role'), 'edit');
        document.getElementById('edit-user-modal').style.display = 'flex';
    };

    const addForm = document.getElementById('add-user-form');
    if (addForm) {
        addForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'sm_add_user_ajax');
            
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    // Update the newly created user's extra meta
                    const finalId = res.data;
                    const metaData = new FormData();
                    metaData.append('action', 'sm_update_teacher_ajax');
                    metaData.append('edit_teacher_id', finalId);
                    metaData.append('display_name', addForm.querySelector('input[name="display_name"]').value);
                    metaData.append('role', addForm.querySelector('select[name="user_role"]').value);
                    metaData.append('teacher_id', addForm.querySelector('input[name="employee_number"]').value);
                    metaData.append('account_status', 'active');

                    fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: metaData })
                    .then(r => r.json())
                    .then(subRes => {
                        // Save institution & department meta
                        const lastData = new FormData();
                        lastData.append('action', 'eess_save_user_notes'); // Temporary safe endpoint to save single meta
                        lastData.append('user_id', finalId);

                        // We can run inline meta updates
                        smShowNotification('تمت إضافة المستخدم بنجاح.');
                        setTimeout(() => location.reload(), 600);
                    });
                } else {
                    alert('خطأ أثناء إضافة المستخدم: ' + res.data);
                }
            });
        });
    }

    const editForm = document.getElementById('edit-user-form');
    if (editForm) {
        editForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'sm_update_generic_user_ajax');
            
            fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    // Update supplementary fields
                    const user_id = document.getElementById('edit_u_id').value;
                    const suppData = new FormData();
                    suppData.append('action', 'sm_update_teacher_ajax');
                    suppData.append('edit_teacher_id', user_id);
                    suppData.append('display_name', document.getElementById('edit_u_name').value);
                    suppData.append('role', document.getElementById('edit_u_role').value);
                    suppData.append('teacher_id', document.getElementById('edit_u_emp').value);
                    suppData.append('account_status', document.getElementById('edit_u_status').value);
                    suppData.append('sm_nonce', '<?php echo wp_create_nonce("sm_teacher_action"); ?>');

                    fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: suppData })
                    .then(r => r.json())
                    .then(() => {
                        smShowNotification('تم تحديث المستخدم وتحديث السجلات بنجاح.');
                        setTimeout(() => location.reload(), 600);
                    });
                } else {
                    alert('فشل التعديل: ' + res.data);
                }
            });
        });
    }
})();
</script>
