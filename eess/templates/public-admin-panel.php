<?php if (!defined('ABSPATH')) exit; ?>
<script>
/**
 * SCHOOL MANAGEMENT - CORE UI ENGINE (ULTRA HARDENED V5)
 * Standard linking and routing fix.
 */
(function(window) {
    const SM_UI = {
        showNotification: function(message, isError = false) {
            const toast = document.createElement('div');
            toast.className = 'sm-toast';
            toast.style.cssText = "position:fixed; top:20px; left:50%; transform:translateX(-50%); background:white; padding:15px 30px; border-radius:8px; box-shadow:0 4px 12px rgba(0,0,0,0.15); z-index:10001; display:flex; align-items:center; gap:10px; border-right:5px solid " + (isError ? '#e53e3e' : '#38a169');
            toast.innerHTML = `<strong>${isError ? '✖' : '✓'}</strong> <span>${message}</span>`;
            document.body.appendChild(toast);
            setTimeout(() => { toast.style.opacity = '0'; toast.style.transition = '0.5s'; setTimeout(() => toast.remove(), 500); }, 3000);
        },

        openInternalTab: function(tabId, element) {
            const target = document.getElementById(tabId);
            if (!target || !element) return;
            const container = target.parentElement;
            container.querySelectorAll('.sm-internal-tab').forEach(p => p.style.setProperty('display', 'none', 'important'));
            target.style.setProperty('display', 'block', 'important');
            element.parentElement.querySelectorAll('.sm-tab-btn').forEach(b => b.classList.remove('sm-active'));
            element.classList.add('sm-active');
        }
    };

    window.smShowNotification = SM_UI.showNotification;
    window.smOpenInternalTab = SM_UI.openInternalTab;

    // REAL-TIME COUNTERS
    function updateRealTimeCounters() {
        fetch('<?php echo admin_url('admin-ajax.php'); ?>?action=sm_get_counts_ajax')
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                const badgeReports = document.getElementById('pending-reports-badge');
                if (badgeReports) {
                    const count = parseInt(res.data.pending_reports);
                    badgeReports.innerText = count;
                    badgeReports.style.display = count > 0 ? 'block' : 'none';
                }
            }
        });
    }
    setInterval(updateRealTimeCounters, 10000); // Every 10 seconds
    window.addEventListener('load', updateRealTimeCounters);

    // MEDIA UPLOADER FOR LOGO
    window.smOpenMediaUploader = function(inputId) {
        const frame = wp.media({
            title: 'اختر شعار المدرسة',
            button: { text: 'استخدام هذا الشعار' },
            multiple: false
        });
        frame.on('select', function() {
            const attachment = frame.state().get('selection').first().toJSON();
            document.getElementById(inputId).value = attachment.url;
        });
        frame.open();
    };

    // GLOBAL EDIT HANDLERS
    window.editSmStudent = function(s) {
        document.getElementById('edit_stu_id').value = s.id;
        document.getElementById('edit_stu_name').value = s.name;
        document.getElementById('edit_stu_class').value = s.class_name || s.class;
        if (document.getElementById('edit_stu_section')) document.getElementById('edit_stu_section').value = s.section || '';
        document.getElementById('edit_stu_email').value = s.parent_email || '';
        document.getElementById('edit_stu_code').value = s.student_id || '';

        if (document.getElementById('edit_stu_phone')) document.getElementById('edit_stu_phone').value = s.guardian_phone || '';
        if (document.getElementById('edit_stu_nationality')) document.getElementById('edit_stu_nationality').value = s.nationality || '';
        if (document.getElementById('edit_stu_reg_date')) document.getElementById('edit_stu_reg_date').value = s.registration_date || '';

        if (document.getElementById('edit_stu_parent_user')) document.getElementById('edit_stu_parent_user').value = s.parent_id || '';
        document.getElementById('edit-student-modal').style.display = 'flex';
    };

    window.editSmTeacher = function(t) {
        document.getElementById('edit_t_id').value = t.id;
        document.getElementById('edit_t_name').value = t.name;
        document.getElementById('edit_t_code').value = t.teacher_id;
        document.getElementById('edit_t_job').value = t.job_title;
        document.getElementById('edit_t_phone').value = t.phone;
        document.getElementById('edit_t_email').value = t.email;
        document.getElementById('edit-teacher-modal').style.display = 'flex';
    };

    window.updateRecordStatus = function(id, status) {
        const formData = new FormData();
        formData.append('action', 'sm_update_record_status');
        formData.append('record_id', id);
        formData.append('status', status);
        formData.append('nonce', '<?php echo wp_create_nonce("sm_record_action"); ?>');

        fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                smShowNotification('تم تحديث حالة المخالفة');
                setTimeout(() => location.reload(), 500);
            }
        });
    };

    window.smDeleteAllLogs = function() {
        if (!confirm('هل أنت متأكد من مسح كافة سجلات النشاط؟ لا يمكن التراجع عن هذا الإجراء.')) return;

        const formData = new FormData();
        formData.append('action', 'sm_delete_all_logs_ajax');
        formData.append('nonce', '<?php echo wp_create_nonce("sm_admin_action"); ?>');

        fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                smShowNotification('تم مسح كافة النشاطات بنجاح');
                setTimeout(() => location.reload(), 500);
            }
        });
    };

    window.smDeleteLog = function(logId) {
        if (!confirm('هل أنت متأكد من حذف هذا السجل نهائياً؟')) return;

        const formData = new FormData();
        formData.append('action', 'sm_delete_log_ajax');
        formData.append('log_id', logId);
        formData.append('nonce', '<?php echo wp_create_nonce("sm_admin_action"); ?>');

        fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                smShowNotification('تم حذف السجل بنجاح');
                setTimeout(() => location.reload(), 500);
            }
        });
    };

    window.eessToggleUserOptionsDropdown = function(event) {
        event.stopPropagation();
        const dropdown = document.getElementById('eess-user-options-dropdown');
        if (dropdown) {
            dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
        }
    };

    window.eessToggleAbsenceDropdown = function(event) {
        event.stopPropagation();
        const dropdown = document.getElementById('eess-absence-dropdown');
        if (dropdown) {
            dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
        }
    };

    // Close dropdowns when clicking outside
    document.addEventListener('click', function() {
        const userDropdown = document.getElementById('eess-user-options-dropdown');
        if (userDropdown) userDropdown.style.display = 'none';
        const prepDropdown = document.getElementById('eess-prep-reports-dropdown');
        if (prepDropdown) prepDropdown.style.display = 'none';
        const absenceDropdown = document.getElementById('eess-absence-dropdown');
        if (absenceDropdown) absenceDropdown.style.display = 'none';
    });

    window.smOpenViolationModal = function() {
        document.getElementById('sm-global-violation-modal').style.display = 'flex';
    };

    window.smCloseViolationModal = function() {
        document.getElementById('sm-global-violation-modal').style.display = 'none';
    };

    window.smToggleUserDropdown = function() {
        const menu = document.getElementById('sm-user-dropdown-menu');
        if (menu.style.display === 'none') {
            menu.style.display = 'block';
            document.getElementById('sm-profile-view').style.display = 'block';
            document.getElementById('sm-profile-edit').style.display = 'none';
        } else {
            menu.style.display = 'none';
        }
    };

    window.smEditProfile = function() {
        document.getElementById('sm-profile-view').style.display = 'none';
        document.getElementById('sm-profile-edit').style.display = 'block';
    };

    window.smSaveProfile = function() {
        const name = document.getElementById('sm_edit_display_name').value;
        const email = document.getElementById('sm_edit_user_email').value;
        const pass = document.getElementById('sm_edit_user_pass').value;
        const nonce = '<?php echo wp_create_nonce("sm_profile_action"); ?>';

        const formData = new FormData();
        formData.append('action', 'sm_update_profile_ajax');
        formData.append('display_name', name);
        formData.append('user_email', email);
        formData.append('user_pass', pass);
        formData.append('nonce', nonce);

        fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                smShowNotification('تم تحديث الملف الشخصي بنجاح');
                setTimeout(() => location.reload(), 500);
            } else {
                smShowNotification('خطأ: ' + res.data, true);
            }
        });
    };

    document.addEventListener('click', function(e) {
        const dropdown = document.querySelector('.sm-user-dropdown');
        const menu = document.getElementById('sm-user-dropdown-menu');
        if (dropdown && !dropdown.contains(e.target)) {
            if (menu) menu.style.display = 'none';
        }
    });

    window.smBulkDelete = function(type) {
        if (!confirm('هل أنت متأكد من مسح كافة البيانات المحددة؟ لا يمكن التراجع عن هذا الإجراء.')) return;

        const formData = new FormData();
        formData.append('action', 'sm_bulk_delete_ajax');
        formData.append('delete_type', type);
        formData.append('nonce', '<?php echo wp_create_nonce("sm_admin_action"); ?>');

        fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                smShowNotification('تم مسح البيانات بنجاح');
                setTimeout(() => location.reload(), 1000);
            } else {
                smShowNotification('خطأ: ' + res.data, true);
            }
        });
    };

    window.smRollbackLog = function(logId) {
        if (!confirm('هل أنت متأكد من استعادة هذه البيانات المحذوفة؟')) return;

        const formData = new FormData();
        formData.append('action', 'sm_rollback_log_ajax');
        formData.append('log_id', logId);
        formData.append('nonce', '<?php echo wp_create_nonce("sm_admin_action"); ?>');

        fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                smShowNotification('تمت الاستعادة بنجاح');
                setTimeout(() => location.reload(), 1000);
            } else {
                smShowNotification('خطأ: ' + res.data, true);
            }
        });
    };

    window.smInitializeSystem = function() {
        const code = prompt('لتأكيد تهيأة النظام بالكامل، يرجى إدخال كود التأكيد (1011996):');
        if (!code) return;

        const formData = new FormData();
        formData.append('action', 'sm_initialize_system_ajax');
        formData.append('confirm_code', code);
        formData.append('nonce', '<?php echo wp_create_nonce("sm_admin_action"); ?>');

        fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                smShowNotification('تمت تهيأة النظام بالكامل بنجاح');
                setTimeout(() => location.reload(), 1000);
            } else {
                smShowNotification('خطأ: ' + res.data, true);
            }
        });
    };

    let smIsRefreshing = false;
    window.smRefreshSystem = function() {
        if (smIsRefreshing) return;
        smIsRefreshing = true;

        // Show loading indicator
        const overlay = document.createElement('div');
        overlay.id = 'sm-refresh-loading-overlay';
        overlay.style.cssText = 'position:fixed; inset:0; background:rgba(255,255,255,0.7); backdrop-filter:blur(2px); z-index:99999; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:15px; font-family:"Cairo", sans-serif;';
        overlay.innerHTML = `
            <div style="width: 40px; height: 40px; border: 3px solid #cbd5e1; border-top-color: #334155; border-radius: 50%; animation: smSpin 0.8s linear infinite;"></div>
            <div style="font-weight:700; color:#1e293b; font-size:14px;">جاري تحديث وإعادة تهيئة النظام...</div>
            <style>
                @keyframes smSpin { to { transform: rotate(360deg); } }
            </style>
        `;
        document.body.appendChild(overlay);

        const btn = document.getElementById('sm-system-refresh-btn');
        if (btn) btn.disabled = true;

        fetch('<?php echo admin_url('admin-ajax.php'); ?>?action=sm_refresh_system_cache_ajax')
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                fetch(window.location.href)
                .then(response => response.text())
                .then(html => {
                    const parserInstance = new DOMParser();
                    const doc = parserInstance.parseFromString(html, 'text/html');
                    const newPanel = doc.querySelector('.sm-admin-dashboard');
                    const oldPanel = document.querySelector('.sm-admin-dashboard');
                    if (newPanel && oldPanel) {
                        oldPanel.innerHTML = newPanel.innerHTML;
                    }
                    const overlayEl = document.getElementById('sm-refresh-loading-overlay');
                    if (overlayEl) overlayEl.remove();
                    if (btn) btn.disabled = false;
                    smIsRefreshing = false;
                    smShowNotification(res.data.message);
                });
            } else {
                const overlayEl = document.getElementById('sm-refresh-loading-overlay');
                if (overlayEl) overlayEl.remove();
                if (btn) btn.disabled = false;
                smIsRefreshing = false;
                smShowNotification('خطأ أثناء تحديث النظام', true);
            }
        })
        .catch(err => {
            const overlayEl = document.getElementById('sm-refresh-loading-overlay');
            if (overlayEl) overlayEl.remove();
            if (btn) btn.disabled = false;
            smIsRefreshing = false;
            smShowNotification('فشل الاتصال بالخادم', true);
        });
    };
})(window);
</script>

<?php 
$user = wp_get_current_user();
$roles = (array)$user->roles;
$is_admin = in_array('administrator', $roles) || current_user_can('manage_options');
$is_sys_admin = in_array('sm_system_admin', $roles);
$is_principal = in_array('sm_principal', $roles);
$is_supervisor = in_array('sm_supervisor', $roles);
$is_coordinator = in_array('sm_coordinator', $roles);
$is_teacher = in_array('sm_teacher', $roles);
$is_student = in_array('sm_student', $roles);
$is_parent = in_array('sm_parent', $roles);
$is_clinic = in_array('sm_clinic', $roles);

$active_tab = isset($_GET['sm_tab']) ? sanitize_text_field($_GET['sm_tab']) : 'summary';
$school = SM_Settings::get_school_info();
$stats = array();
$sidebar_visibility = SM_Settings::get_sidebar_visibility();
$my_role = !empty($user->roles) ? $user->roles[0] : '';
$my_visibility = $sidebar_visibility[$my_role] ?? array();

// Ensure site administrators always see everything regardless of role-based settings
$is_wp_admin = current_user_can('manage_options');

if ($active_tab === 'summary') {
    $stats = SM_DB::get_statistics();

    // For parents, filter stats to their student
    if ($is_parent) {
        $student = SM_DB::get_student_by_parent(get_current_user_id());
        if ($student) {
            $stats = SM_DB::get_student_stats($student->id);
        }
    }
}

// Dynamic Greeting logic
$hour = (int)current_time('G');
$greeting = ($hour >= 5 && $hour < 12) ? 'صباح الخير' : 'مساء الخير';
?>

<div class="sm-admin-dashboard" dir="rtl" style="font-family: 'Cairo', 'Noto Kufi Arabic', sans-serif; background: #fff; border: 1px solid var(--sm-border-color); border-radius: 12px; overflow: hidden;">
    <!-- OFFICIAL SYSTEM HEADER -->
    <div class="sm-main-header" style="height: 52px; padding: 4px 16px; box-sizing: border-box; display: flex; align-items: center; justify-content: space-between;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <?php if (!empty($school['school_logo'])): ?>
                <img src="<?php echo esc_url($school['school_logo']); ?>" style="height: 32px; width: auto; border-radius: 6px; object-fit: contain; display: block;">
            <?php else: ?>
                <div style="background: #f1f5f9; border-radius: 6px; height: 32px; width: 32px; display: flex; align-items: center; justify-content: center; color: #94a3b8;">
                    <span class="dashicons dashicons-building" style="font-size: 16px; width: 16px; height: 16px;"></span>
                </div>
            <?php endif; ?>
            <div>
                <h1 style="margin:0; border: none; padding: 0; color: var(--sm-dark-color); font-weight: 800; font-size: 1.05em; text-decoration: none; line-height: 1;">
                    <?php echo esc_html($school['school_name']); ?>
                </h1>
                <div style="display: flex; align-items: center; flex-wrap: wrap; gap: 5px; margin-top: 4px;">
                    <!-- Enlarge Role Badge -->
                    <div style="display: inline-block; padding: 2px 10px; background: #fee2e2; color: #991b1b; border-radius: 50px; font-size: 11px; font-weight: 700; border: 1px solid #fca5a5; line-height: 1;">
                        <?php
                        $user_roles = (array) $user->roles;
                        $primary_role = reset($user_roles);
                        $role_labels = array(
                            'administrator' => 'مدير النظام المطور',
                            'sm_system_admin' => 'مدير النظام التقني',
                            'sm_principal' => 'مدير المدرسة',
                            'sm_supervisor' => 'مشرف تربوي',
                            'sm_coordinator' => 'منسق مادة',
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
                        echo esc_html($role_labels[$primary_role] ?? 'مستخدم النظام');
                        ?>
                    </div>

                    <!-- Subject Badge for Teacher/Coordinator -->
                    <?php
                    $my_subject = get_user_meta($user->ID, 'sm_specialization', true);
                    if (($is_teacher || $is_coordinator) && !empty($my_subject)): ?>
                        <div style="display: inline-block; padding: 2px 10px; background: #e0f2fe; color: #0369a1; border-radius: 50px; font-size: 11px; font-weight: 700; border: 1px solid #bae6fd; line-height: 1;">
                            <?php echo esc_html($my_subject); ?>
                        </div>
                    <?php endif; ?>

                    <!-- Assigned Institution/School Badge -->
                    <?php 
                    $user_scope = EESS_Org_Helper::get_user_scope($user->ID);
                    $assigned_school_name = '';
                    if (!empty($user_scope['schools'])) {
                        $first_school_id = reset($user_scope['schools']);
                        global $wpdb;
                        $assigned_school_name = $wpdb->get_var($wpdb->prepare("SELECT name FROM {$wpdb->prefix}eess_schools WHERE id = %d", $first_school_id));
                    }
                    if (empty($assigned_school_name)) {
                        $assigned_school_name = get_user_meta($user->ID, 'eess_school_name', true) ?: ($school['school_name'] ?? '');
                    }
                    if (!$is_student && !$is_parent && !empty($assigned_school_name)): ?>
                        <div style="display: inline-block; padding: 2px 10px; background: #f0fdf4; color: #166534; border-radius: 50px; font-size: 11px; font-weight: 700; border: 1px solid #bbf7d0; line-height: 1;">
                            <?php echo esc_html($assigned_school_name); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div style="display: flex; align-items: center; gap: 10px;">
            <?php if ($active_tab !== 'attendance'): ?>
                <?php if ($is_teacher || in_array('sm_activities_supervisor', $roles) || in_array('sm_hod', $roles)): ?>
                    <button type="button" onclick="eessOpenTeacherReferralModal()" class="sm-btn" style="background: var(--sm-primary-color); height: 32px; padding: 0 14px; font-size: 11.5px; font-weight: 700; color: white !important; border-radius: 9999px !important; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
                        <span class="dashicons dashicons-warning" style="font-size: 14px; width: 14px; height: 14px; color: white;"></span>
                        <span>تقديم حالة سلوكية</span>
                    </button>
                <?php elseif ($is_admin || current_user_can('تسجيل_مخالفة')): ?>
                    <button type="button" onclick="smOpenViolationModal()" class="sm-btn" style="background: var(--sm-primary-color); height: 32px; padding: 0 14px; font-size: 11.5px; font-weight: 700; color: white !important; border-radius: 9999px !important; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
                        <span class="dashicons dashicons-plus-alt" style="font-size: 14px; width: 14px; height: 14px; color: white;"></span>
                        <span>تسجيل مخالفة</span>
                    </button>
                <?php endif; ?>
            <?php endif; ?>

            <button type="button" onclick="eessOpenSupportHelpCapsule()" class="sm-btn" style="background-color: #ffffff !important; color: #dc2626 !important; border: 1.5px solid #ef4444 !important; height: 32px !important; border-radius: 9999px !important; padding: 0 14px !important; font-weight: 800 !important; font-size: 11.5px !important; cursor: pointer !important; display: inline-flex !important; align-items: center !important; gap: 6px !important; box-shadow: 0 2px 6px rgba(239,68,68,0.06) !important; transition: all 0.2s ease !important;">
                <span class="dashicons dashicons-sos" style="font-size: 15px !important; width: 15px !important; height: 15px !important; color: #dc2626 !important; margin: 0 !important;"></span>
                <span style="color: #dc2626 !important; font-weight: 800 !important;">الدعم والمساعدة</span>
            </button>

            <div class="sm-user-dropdown" style="position: relative;">
                <div class="sm-user-profile-nav" onclick="smToggleUserDropdown()" style="display: flex; align-items: center; gap: 8px; background: white; padding: 4px 10px; border-radius: 50px; border: 1px solid var(--sm-border-color); cursor: pointer;">
                    <div style="width: 28px; height: 28px; min-width: 28px; min-height: 28px; max-width: 28px; max-height: 28px; border-radius: 50%; overflow: hidden; border: 1.5px solid var(--sm-primary-color); display: flex; align-items: center; justify-content: center; flex-shrink: 0; box-sizing: border-box;">
                        <?php echo get_avatar($user->ID, 28, '', '', array('style' => 'width: 28px !important; height: 28px !important; max-width: 28px !important; max-height: 28px !important; border-radius: 50% !important; object-fit: cover !important; display: block !important; margin: 0 !important; padding: 0 !important;')); ?>
                    </div>
                    <div style="text-align: right;">
                        <div style="font-size: 0.8em; font-weight: 700; color: var(--sm-dark-color); line-height: 1.1;"><?php echo $greeting . '، ' . $user->display_name; ?></div>
                        <?php
                        $emp_id = get_user_meta($user->ID, 'eess_employee_number', true) ?: '';
                        ?>
                        <div style="font-size: 0.65em; color: #64748b; line-height: 1; font-weight: bold;"><?php echo esc_html($emp_id ? $emp_id : 'بدون رقم وظيفي'); ?> <span class="dashicons dashicons-arrow-down-alt2" style="font-size: 8px; width: 8px; height: 8px;"></span></div>
                    </div>
                </div>
                <div id="sm-user-dropdown-menu" style="display: none; position: absolute; top: 110%; left: 0; background: white; border: 1px solid var(--sm-border-color); border-radius: 8px; width: 260px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); z-index: 1000; animation: smFadeIn 0.2s ease-out; padding: 10px 0;">
                    <div id="sm-profile-view">
                        <div style="padding: 10px 20px; border-bottom: 1px solid #f0f0f0; margin-bottom: 5px; display: flex; align-items: center; gap: 10px;">
                            <div style="width: 36px; height: 36px; min-width: 36px; min-height: 36px; max-width: 36px; max-height: 36px; border-radius: 50%; overflow: hidden; border: 1.5px solid var(--sm-primary-color); display: flex; align-items: center; justify-content: center; flex-shrink: 0; box-sizing: border-box;">
                                <?php echo get_avatar($user->ID, 36, '', '', array('style' => 'width: 36px !important; height: 36px !important; max-width: 36px !important; max-height: 36px !important; border-radius: 50% !important; object-fit: cover !important; display: block !important; margin: 0 !important; padding: 0 !important;')); ?>
                            </div>
                            <div>
                                <div style="font-weight: 800; color: var(--sm-dark-color); font-size: 12px;"><?php echo $user->display_name; ?></div>
                                <div style="font-size: 11px; color: var(--sm-text-gray);"><?php echo $user->user_email; ?></div>
                            </div>
                        </div>
                        <?php if (!$is_student && !$is_parent): ?>
                            <a href="javascript:smEditProfile()" class="sm-dropdown-item"><span class="dashicons dashicons-edit"></span> تعديل البيانات الشخصية</a>
                        <?php endif; ?>
                        <?php if ($is_student || $is_parent): ?>
                            <a href="javascript:smEditProfile()" class="sm-dropdown-item"><span class="dashicons dashicons-lock"></span> تغيير كلمة المرور</a>
                        <?php endif; ?>
                        <?php if ($is_admin): ?>
                            <a href="<?php echo add_query_arg('sm_tab', 'global-settings'); ?>" class="sm-dropdown-item"><span class="dashicons dashicons-admin-generic"></span> إعدادات النظام</a>
                        <?php endif; ?>
                        <a href="javascript:location.reload()" class="sm-dropdown-item"><span class="dashicons dashicons-update"></span> تحديث الصفحة</a>
                    </div>

                    <div id="sm-profile-edit" style="display: none; padding: 15px;">
                        <div style="font-weight: 800; margin-bottom: 15px; font-size: 13px; border-bottom: 1px solid #eee; padding-bottom: 10px;">تعديل الملف الشخصي</div>
                        <div class="sm-form-group" style="margin-bottom: 10px;">
                            <label class="sm-label" style="font-size: 11px;">الاسم المفضل:</label>
                            <input type="text" id="sm_edit_display_name" class="sm-input" style="padding: 8px; font-size: 12px;" value="<?php echo esc_attr($user->display_name); ?>" <?php if ($is_student || $is_parent) echo 'disabled style="background:#f1f5f9; cursor:not-allowed;"'; ?>>
                        </div>
                        <div class="sm-form-group" style="margin-bottom: 10px;">
                            <label class="sm-label" style="font-size: 11px;">البريد الإلكتروني:</label>
                            <input type="email" id="sm_edit_user_email" class="sm-input" style="padding: 8px; font-size: 12px;" value="<?php echo esc_attr($user->user_email); ?>" <?php if ($is_student || $is_parent) echo 'disabled style="background:#f1f5f9; cursor:not-allowed;"'; ?>>
                        </div>
                        <div class="sm-form-group" style="margin-bottom: 15px;">
                            <label class="sm-label" style="font-size: 11px;">كلمة مرور جديدة (اختياري):</label>
                            <input type="password" id="sm_edit_user_pass" class="sm-input" style="padding: 8px; font-size: 12px;" placeholder="********">
                        </div>
                        <div style="display: flex; gap: 8px;">
                            <button onclick="smSaveProfile()" class="sm-btn" style="flex: 1; height: 32px; font-size: 11px; padding: 0;">حفظ</button>
                            <button onclick="document.getElementById('sm-profile-edit').style.display='none'; document.getElementById('sm-profile-view').style.display='block';" class="sm-btn sm-btn-outline" style="flex: 1; height: 32px; font-size: 11px; padding: 0;">إلغاء</button>
                        </div>
                    </div>

                    <hr style="margin: 5px 0; border: none; border-top: 1px solid #eee;">
                    <a href="<?php echo home_url('/sm-login?sm_action=logout'); ?>" class="sm-dropdown-item" style="color: #e53e3e;"><span class="dashicons dashicons-logout"></span> تسجيل الخروج</a>
                </div>
            </div>
        </div>
    </div>

    <div class="sm-admin-layout" style="display: flex; min-height: 800px;">
        <!-- SIDEBAR -->
        <div class="sm-sidebar" style="width: 220px; flex-shrink: 0; background: var(--sm-bg-light); border-left: 1px solid var(--sm-border-color); padding: 20px 0; display: flex; flex-direction: column; justify-content: space-between;">
            <ul style="list-style: none; padding: 0; margin: 0;">
                <?php foreach (SM_Settings::get_system_modules() as $key => $module):
                    // 1. Check if the module is visible for the role (or is super admin)
                    $is_visible = SM_Settings::is_section_visible($key);
                    if (!$is_visible) {
                        continue;
                    }

                    // Build link URLs
                    if ($key === 'stats') {
                        $link = remove_query_arg(['student_search', 'class_filter', 'section_filter', 'type_filter', 'start_date', 'end_date'], add_query_arg('sm_tab', 'stats'));
                    } elseif ($key === 'students') {
                        $link = remove_query_arg(['student_search', 'class_filter', 'section_filter', 'teacher_filter'], add_query_arg('sm_tab', 'students'));
                    } else {
                        $link = add_query_arg('sm_tab', $module['tab']);
                    }
                ?>
                    <li class="sm-sidebar-item <?php echo $active_tab == $module['tab'] ? 'sm-active' : ''; ?>">
                        <a href="<?php echo esc_url($link); ?>" class="sm-sidebar-link">
                            <span class="dashicons <?php echo esc_attr($module['dashicon']); ?>"></span>
                            <?php echo esc_html($module['label']); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>

        </div>

        <!-- CONTENT AREA -->
        <div class="sm-main-panel" style="flex: 1; min-width: 0; padding: 40px; background: #fff;">
            
            <?php if (isset($_GET['sm_admin_msg'])):
                $msg_type = sanitize_text_field($_GET['sm_admin_msg']);
                $messages_map = array(
                    'settings_saved' => 'تم حفظ الإعدادات والخيارات بنجاح ومزامنتها وتحديث القائمة فوراً مع كافة الأنظمة والرتب.',
                    'student_added' => 'تمت إضافة الطالب بنجاح بالنظام.',
                    'student_deleted' => 'تم حذف سجل الطالب وكافة بياناته بنجاح.',
                    'restored' => 'تمت استعادة النسخة الاحتياطية بنجاح.',
                    'demo_deleted' => 'تمت تهيئة النظام وحذف البيانات التجريبية بنجاح.',
                    'csv_imported' => 'تم استيراد البيانات من ملف Excel بنجاح.',
                    'error' => 'حدث خطأ غير متوقع. يرجى التحقق من البيانات وإعادة المحاولة.'
                );
                $msg_text = $messages_map[$msg_type] ?? '';
                if ($msg_text):
                    $is_error = ($msg_type === 'error');
            ?>
                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    if (typeof window.smShowNotification === 'function') {
                        window.smShowNotification(<?php echo json_encode($msg_text); ?>, <?php echo $is_error ? 'true' : 'false'; ?>);
                    }
                });
                </script>
            <?php endif; endif; ?>

            <?php
            // Unified Page Headers Map
            $header_map = array(
                'summary' => array(
                    'title' => 'لوحة المعلومات',
                    'desc' => 'متابعة إحصائيات النظام ومؤشرات الأداء العامة والأنشطة والعمليات الجارية في المنظومة.',
                    'button' => ''
                ),
                'students' => array(
                    'title' => 'إدارة شؤون الطلاب (Student Affairs)',
                    'desc' => 'المركز الرئيسي لإدارة بيانات الطلاب، الملفات الأكاديمية والشخصية، السجلات المدرسية، واستيراد وتصدير ملفات البيانات المعتمدة.',
                    'button' => ''
                ),
                'teachers' => array(
                    'title' => 'إدارة مستخدمي النظام',
                    'desc' => 'إدارة الحسابات، الأذونات والصلاحيات للمشرفين والمعلمين وكافة مستخدمي المنصة الإلكترونية.',
                    'button' => ''
                ),
                'parents' => array(
                    'title' => 'إدارة أولياء الأمور',
                    'desc' => 'إدارة سجلات وبيانات الاتصال لأولياء الأمور وربطهم بحسابات أبنائهم الطلاب المعتمدين.',
                    'button' => ''
                ),
                'grades' => array(
                    'title' => 'إدارة الدرجات والنتائج',
                    'desc' => 'رصد وتوثيق الدرجات الأكاديمية والشهادات والتقارير الدورية للفصول الدراسية.',
                    'button' => ''
                ),
                'attendance' => array(
                    'title' => 'سجل الحضور والغياب',
                    'desc' => 'تسجيل ورصد الحضور والغياب اليومي للطلاب ومتابعة الإحصائيات العامة المعتمدة.',
                    'button' => ''
                ),
                'employee-profile' => array(
                    'title' => 'الملف الوظيفي',
                    'desc' => 'الملف المهني والوظيفي المتكامل للموظف ومتابعة السجلات الإدارية والمالية والتقييمات السنوية.',
                    'button' => ''
                ),
                'hr-evaluation' => array(
                    'title' => 'تقييم الموظفين',
                    'desc' => 'المنظومة الاحترافية الشاملة لتقييم الأداء السنوي، الفصلي والدوري لمنتسبي الهيئة الأكاديمية والإدارية والقيادية.',
                    'button' => ''
                ),
                'hr-management' => array(
                    'title' => 'إدارة الموارد البشرية',
                    'desc' => 'إدارة شاملة لملفات العاملين، الرواتب، الترقيات، المستندات الرسمية والانضباط السلوكي والوظيفي.',
                    'button' => ''
                ),
                'lesson-plans' => array(
                    'title' => 'تحضير الدروس',
                    'desc' => 'متابعة وإعداد واعتماد التحضيرات والخطط الأكاديمية والتعليمية للكادر التدريسي والأكاديمي.',
                    'button' => ''
                ),
                'term-plans' => array(
                    'title' => 'الخطط الفصلية والسنوية',
                    'desc' => 'إعداد وتحديد الخطط الأكاديمية والتوزيع الأسبوعي للمناهج الدراسية بالفصول والاعتماد المباشر.',
                    'button' => ''
                ),
                'system-announcements' => array(
                    'title' => 'الإشعارات والإعلانات العامة',
                    'desc' => 'إدارة ونشر التنبيهات، التعاميم الإدارية، والإعلانات الشاملة لرتب مستخدمي النظام ومتابعة القراءة.',
                    'button' => ''
                ),
                'assignments' => array(
                    'title' => 'الواجبات المدرسية',
                    'desc' => 'إنشاء وتوزيع ومتابعة الواجبات المدرسية والمهام المنزلية المقررة على الطلاب لمتابعة الأداء الأكاديمي.',
                    'button' => ''
                ),
                'documents' => array(
                    'title' => 'مكتبة الوثائق والتقارير',
                    'desc' => 'مكتبة وأرشيف الوثائق والتقارير المدرسية الرسمية والقرارات والتعاميم المعتمدة للمؤسسة.',
                    'button' => ''
                ),
                'clinic' => array(
                    'title' => 'العيادة المدرسية',
                    'desc' => 'سجل الحالات والزيارات اليومية للعيادة المدرسية والتقارير الصحية والمراجعات الطبية للطلاب.',
                    'button' => ''
                ),
                'global-settings' => array(
                    'title' => 'إعدادات النظام',
                    'desc' => 'تخصيص اللوائح السلوكية، المظهر، صلاحيات القوائم وإعدادات الهيكل المدرسي العام والنسخ الاحتياطي.',
                    'button' => ''
                )
            );

            if ($active_tab === 'students') {
                $header_map['students']['button'] = '';
            }
            if ($active_tab === 'teachers') {
                $header_map['teachers']['button'] = '
                <div style="display: flex; align-items: center; gap: 10px;">
                    ' . ($is_admin ? '<button onclick="eessOpenUnifiedUserModal(\'add_user\', 0)" class="sm-btn" style="background:#000; border:1px solid #000; color:#fff; border-radius:8px; font-weight:700; height:38px; display:inline-flex; align-items:center; gap:8px; cursor:pointer;"><span class="dashicons dashicons-plus-alt"></span> إضافة مستخدم جديد</button>' : '') . '

                    <!-- User Options Dropdown -->
                    <div style="position: relative; display: inline-block;">
                        <button type="button" onclick="eessToggleUserOptionsDropdown(event)" class="sm-btn sm-btn-outline" style="height:38px; display:inline-flex; align-items:center; gap:5px; border-radius: 8px; cursor:pointer; background:#fff; color:#334155; border-color:#cbd5e1;">
                            <span class="dashicons dashicons-admin-generic" style="font-size: 16px; width: 16px; height: 16px; margin: 0; color: #475569;"></span>
                            <span>خيارات المستخدمين</span>
                            <span class="dashicons dashicons-arrow-down-alt2" style="font-size: 10px; width: 10px; height: 10px; margin: 0;"></span>
                        </button>
                        <div id="eess-user-options-dropdown" style="display: none; position: absolute; left: 0; top: 110%; background: #fff; border: 1px solid #cbd5e1; border-radius: 8px; width: 220px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); z-index: 99999; padding: 5px 0; text-align: right;">
                            <a href="javascript:void(0)" onclick="document.getElementById(\'user-csv-import-box\').style.display = document.getElementById(\'user-csv-import-box\').style.display === \'none\' ? \'block\' : \'none\'; document.getElementById(\'eess-user-options-dropdown\').style.display=\'none\';" style="display: block; padding: 10px 15px; color: #334155; font-size: 12px; text-decoration: none; border-bottom: 1px solid #f1f5f9; font-weight: 700;">📥 استيراد مستخدمين (CSV)</a>
                            <a href="' . admin_url('admin-ajax.php?action=sm_export_users_csv&nonce=' . wp_create_nonce('eess_admin_action')) . '" onclick="document.getElementById(\'eess-user-options-dropdown\').style.display=\'none\';" style="display: block; padding: 10px 15px; color: #334155; font-size: 12px; text-decoration: none; font-weight: 700;">📤 تصدير مستخدمين (CSV)</a>
                        </div>
                    </div>
                </div>';
            }
            if ($active_tab === 'parents' && ($is_admin || current_user_can('إدارة_أولياء_الأمور'))) {
                $header_map['parents']['button'] = '<button onclick="document.getElementById(\'add-parent-modal\').style.display=\'flex\'" class="sm-btn" style="background:#000; border:1px solid #000; color:#fff; border-radius:8px; font-weight:700; height:38px; display:inline-flex; align-items:center; gap:8px; cursor:pointer;"><span class="dashicons dashicons-plus-alt"></span> إضافة ولي أمر جديد</button>';
            }
            if ($active_tab === 'grades' && ($is_admin || $is_coordinator || $is_teacher)) {
                $header_map['grades']['button'] = '
                <div style="display: flex; align-items: center; gap: 10px;">
                    <button onclick="document.getElementById(\'add-grade-modal\').style.display=\'flex\'" class="sm-btn" style="background:#000; border:1px solid #000; color:#fff; border-radius:8px; font-weight:700; height:38px; display:inline-flex; align-items:center; gap:8px; cursor:pointer;"><span class="dashicons dashicons-plus-alt"></span> إضافة نتيجة أكاديمية</button>
                    <button onclick="document.getElementById(\'eess-grades-import-modal\').style.display=\'flex\'" class="sm-btn sm-btn-outline" style="height:38px; font-size:12px; cursor:pointer; display:inline-flex; align-items:center; gap:5px; border-radius: 8px;"><span class="dashicons dashicons-upload"></span> استيراد النتائج</button>
                </div>';
            }
            if ($active_tab === 'attendance') {
                $att_date = isset($_GET['attendance_date']) ? sanitize_text_field($_GET['attendance_date']) : current_time('Y-m-d');
                $header_map['attendance']['button'] = '
                <div style="display: flex; align-items: center; gap: 10px; flex-wrap: nowrap;">
                    <a href="' . home_url('/attendance/') . '" class="sm-btn" style="background:#000; border:1px solid #000; color:#fff !important; border-radius:8px; font-weight:700; height:38px; text-decoration:none; display:inline-flex; align-items:center; gap:8px;"><span class="dashicons dashicons-edit"></span> تسجيل الحضور</a>

                    <!-- Absence Reports Dropdown -->
                    <div style="position: relative; display: inline-block;">
                        <button type="button" onclick="eessToggleAbsenceDropdown(event)" class="sm-btn sm-btn-outline" style="height:38px; display:inline-flex; align-items:center; gap:5px; border-radius: 8px; cursor:pointer; background:#fff; color:#334155; border-color:#cbd5e1;">
                            <span class="dashicons dashicons-analytics" style="font-size: 16px; width: 16px; height: 16px; margin: 0; color: #475569;"></span>
                            <span>سجل الغيابات</span>
                            <span class="dashicons dashicons-arrow-down-alt2" style="font-size: 10px; width: 10px; height: 10px; margin: 0;"></span>
                        </button>
                        <div id="eess-absence-dropdown" style="display: none; position: absolute; left: 0; top: 110%; background: #fff; border: 1px solid #cbd5e1; border-radius: 8px; width: 220px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); z-index: 99999; padding: 5px 0; text-align: right;">
                            <a href="javascript:void(0)" onclick="printAbsenceReport(\'daily\'); document.getElementById(\'eess-absence-dropdown\').style.display=\'none\';" style="display: block; padding: 10px 15px; color: #334155; font-size: 12px; text-decoration: none; border-bottom: 1px solid #f1f5f9; font-weight: 700;">📊 غيابات اليوم</a>
                            <a href="javascript:void(0)" onclick="printAbsenceReport(\'term\'); document.getElementById(\'eess-absence-dropdown\').style.display=\'none\';" style="display: block; padding: 10px 15px; color: #334155; font-size: 12px; text-decoration: none; font-weight: 700;">📈 الأكثر غياباً (الفصل)</a>
                        </div>
                    </div>

                    <div class="sm-form-group" style="margin-bottom: 0; display:inline-block;">
                        <input type="date" id="attendance-filter-date" class="sm-input" value="' . esc_attr($att_date) . '" onchange="window.location.href=\'' . add_query_arg('attendance_date', '', $_SERVER['REQUEST_URI']) . '\' + this.value" style="height:38px; border-radius: 8px; padding:0 8px; font-family:\'Cairo\';">
                    </div>
                    <button onclick="location.reload()" class="sm-btn sm-btn-outline" title="تحديث" style="height:38px; border-radius: 8px; display:inline-flex; align-items:center; justify-content:center; width:38px; min-width:38px; padding:0; cursor:pointer;"><span class="dashicons dashicons-update" style="margin:0;"></span></button>
                </div>';
            }
            if ($active_tab === 'employee-profile') {
                $header_map['employee-profile']['button'] = '<button type="button" onclick="eessOpenProfileEditModal()" class="sm-btn" style="background: #000; border: 1px solid #000; color: #fff; border-radius: 8px; font-weight: 700; height: 38px; display: inline-flex; align-items: center; gap: 8px; cursor:pointer;"><span class="dashicons dashicons-edit"></span> تعديل وتزامن البيانات</button>';
            }
            if ($active_tab === 'hr-evaluation') {
                $header_map['hr-evaluation']['button'] = '<button onclick="jQuery(\'#eess-new-eval-container\').slideToggle();" class="sm-btn" style="background: #000; border: 1px solid #000; color: #fff; border-radius: 8px; font-weight: 700; height: 38px; display: inline-flex; align-items: center; gap: 8px; cursor:pointer;"><span class="dashicons dashicons-plus-alt"></span> إجراء تقييم جديد</button>';
            }
            if ($active_tab === 'lesson-plans') {
                $btn_html = '<div style="display: flex; align-items: center; gap: 10px;">';
                if ($is_teacher) {
                    $btn_html .= '<button onclick="document.getElementById(\'prep-modal\').style.display=\'flex\'" class="sm-btn" style="background:#000; border:1px solid #000; color:#fff; border-radius:8px; font-weight:700; height:38px; display:inline-flex; align-items:center; gap:8px; cursor:pointer;"><span class="dashicons dashicons-plus-alt"></span> إضافة تحضير جديد</button>';
                }
                if ($is_admin || $is_sys_admin || $is_principal || $is_supervisor) {
                    $btn_html .= '
                        <!-- Reports Dropdown Container -->
                        <div style="position: relative; display: inline-block;">
                            <button type="button" onclick="eessTogglePrepReportsDropdown(event)" class="sm-btn sm-btn-outline" style="width: auto; height: 38px; display: inline-flex; align-items: center; gap: 5px; border-color: #cbd5e1; cursor: pointer; padding: 0 10px; font-size: 12px; background: #fff; color: #334155; border-radius: 8px;">
                                <span class="dashicons dashicons-analytics" style="font-size: 16px; width: 16px; height: 16px; margin: 0; color: #475569;"></span>
                                <span>تقارير التحضير</span>
                                <span class="dashicons dashicons-arrow-down-alt2" style="font-size: 10px; width: 10px; height: 10px; margin: 0;"></span>
                            </button>
                            <div id="eess-prep-reports-dropdown" style="display: none; position: absolute; left: 0; top: 110%; background: #fff; border: 1px solid #cbd5e1; border-radius: 8px; width: 250px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); z-index: 99999; padding: 5px 0; text-align: right;">
                                <a href="javascript:void(0)" onclick="eessShowPrepReport(\'submitted\')" style="display: block; padding: 8px 15px; color: #334155; font-size: 12px; text-decoration: none; border-bottom: 1px solid #f1f5f9;">📝 تقرير التحضيرات المقدمة</a>
                                <a href="javascript:void(0)" onclick="eessShowPrepReport(\'not_submitted\')" style="display: block; padding: 8px 15px; color: #334155; font-size: 12px; text-decoration: none; border-bottom: 1px solid #f1f5f9;">❌ تقرير التحضيرات المتأخرة/غير المقدمة</a>
                                <a href="javascript:void(0)" onclick="eessShowPrepReport(\'by_institution\')" style="display: block; padding: 8px 15px; color: #334155; font-size: 12px; text-decoration: none; border-bottom: 1px solid #f1f5f9;">🏫 الإحصائيات حسب المؤسسة</a>
                                <a href="javascript:void(0)" onclick="eessShowPrepReport(\'by_department\')" style="display: block; padding: 8px 15px; color: #334155; font-size: 12px; text-decoration: none; border-bottom: 1px solid #f1f5f9;">📂 الإحصائيات حسب الأقسام</a>
                                <a href="javascript:void(0)" onclick="eessShowPrepReport(\'by_subject\')" style="display: block; padding: 8px 15px; color: #334155; font-size: 12px; text-decoration: none; border-bottom: 1px solid #f1f5f9;">📚 الإحصائيات حسب المواد</a>
                                <a href="javascript:void(0)" onclick="eessShowPrepReport(\'periodical\')" style="display: block; padding: 8px 15px; color: #334155; font-size: 12px; text-decoration: none; border-bottom: 1px solid #f1f5f9;">📅 تقرير دوري (يومي/أسبوعي/شهري)</a>
                                <a href="javascript:void(0)" onclick="eessShowPrepReport(\'ranking\')" style="display: block; padding: 8px 15px; color: #334155; font-size: 12px; text-decoration: none; border-bottom: 1px solid #f1f5f9;">🏆 تصنيف المدارس والمعلمين</a>
                                <a href="javascript:void(0)" onclick="eessShowPrepReport(\'compliance\')" style="display: block; padding: 8px 15px; color: #334155; font-size: 12px; text-decoration: none; border-bottom: 1px solid #f1f5f9;">📊 متوسطات الامتثال لنسب التقديم</a>
                                <a href="javascript:void(0)" onclick="eessShowPrepReport(\'late_stats\')" style="display: block; padding: 8px 15px; color: #334155; font-size: 12px; text-decoration: none; border-bottom: 1px solid #f1f5f9;">⏱️ إحصائيات التأخر والمهل الزمنية</a>
                                <a href="javascript:void(0)" onclick="eessExportPrepReport()" style="display: block; padding: 8px 15px; color: #0d9488; font-size: 12px; font-weight: bold; text-decoration: none;">📥 تصدير التقرير الموحد (Excel/CSV)</a>
                            </div>
                        </div>

                        <!-- Settings Gear Icon Button -->
                        <button type="button" onclick="document.getElementById(\'prep-settings-modal\').style.display=\'flex\'" class="sm-btn sm-btn-outline" style="width: auto; height: 38px; display: inline-flex; align-items: center; gap: 5px; border-color: #cbd5e1; cursor: pointer; padding: 0 10px; font-size: 12px; background: #fff; color: #334155; border-radius: 8px;">
                            <span class="dashicons dashicons-admin-generic" style="font-size: 16px; width: 16px; height: 16px; margin: 0;"></span>
                            <span>إعدادات التحضير</span>
                        </button>
                    ';
                }
                $btn_html .= '</div>';
                $header_map['lesson-plans']['button'] = $btn_html;
            }
            if ($active_tab === 'assignments' && $is_teacher) {
                $header_map['assignments']['button'] = '<button onclick="document.getElementById(\'add-assignment-modal\').style.display=\'flex\'" class="sm-btn" style="background:#000; border:1px solid #000; color:#fff; border-radius:8px; font-weight:700; height:38px; display:inline-flex; align-items:center; gap:8px; cursor:pointer;"><span class="dashicons dashicons-plus-alt"></span> إضافة واجب جديد</button>';
            }
            if ($active_tab === 'documents') {
                $header_map['documents']['button'] = '<button onclick="document.getElementById(\'add-doc-modal\').style.display=\'flex\'" class="sm-btn" style="background:#881337; border:none; color:#ffffff !important; border-radius:9999px !important; font-weight:800; height:38px; padding:0 20px; font-size:12.5px; display:inline-flex; align-items:center; gap:6px; cursor:pointer;"><span class="dashicons dashicons-plus-alt2" style="font-size:15px; width:15px; height:15px;"></span> إضافة مستند جديد</button>';
            }
            if ($active_tab === 'clinic') {
                $btn_html = '<div style="display: flex; align-items: center; gap: 10px;">';
                if ($is_admin || current_user_can('manage_options') || in_array('sm_system_admin', $roles) || in_array('sm_principal', $roles) || in_array('sm_supervisor', $roles)) {
                    $btn_html .= '<button onclick="document.getElementById(\'referral-modal\').style.display=\'flex\'" class="sm-btn" style="background:#000; border:1px solid #000; color:#fff; border-radius:8px; font-weight:700; height:38px; display:inline-flex; align-items:center; gap:8px; cursor:pointer;"><span class="dashicons dashicons-plus-alt"></span> تحويل جديد للعيادة</button>';
                }
                if ($is_clinic_staff) {
                    $c_nonce = wp_create_nonce('sm_clinic_action');
                    $btn_html .= '
                    <!-- Clinic Reports Dropdown -->
                    <div style="position: relative; display: inline-block;">
                        <button type="button" onclick="jQuery(\'#eess-clinic-reports-dropdown\').toggle(); event.stopPropagation();" class="sm-btn sm-btn-outline" style="width: auto; height: 38px; display: inline-flex; align-items: center; gap: 5px; border-color: #cbd5e1; cursor: pointer; padding: 0 10px; font-size: 12px; background: #fff; color: #334155; border-radius: 8px;">
                            <span class="dashicons dashicons-download" style="font-size: 16px; width: 16px; height: 16px; margin: 0; color: #475569;"></span>
                            <span>تحميل التقارير</span>
                            <span class="dashicons dashicons-arrow-down-alt2" style="font-size: 10px; width: 10px; height: 10px; margin: 0;"></span>
                        </button>
                        <div id="eess-clinic-reports-dropdown" style="display: none; position: absolute; left: 0; top: 110%; background: #fff; border: 1px solid #cbd5e1; border-radius: 8px; width: 180px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); z-index: 99999; padding: 5px 0; text-align: right;">
                            <a href="' . admin_url('admin-ajax.php?action=sm_get_clinic_reports&report_type=day&nonce='.$c_nonce) . '" class="sm-dropdown-item">تقرير اليوم</a>
                            <a href="' . admin_url('admin-ajax.php?action=sm_get_clinic_reports&report_type=week&nonce='.$c_nonce) . '" class="sm-dropdown-item">تقرير الأسبوع</a>
                            <a href="' . admin_url('admin-ajax.php?action=sm_get_clinic_reports&report_type=month&nonce='.$c_nonce) . '" class="sm-dropdown-item">تقرير الشهر</a>
                            <a href="' . admin_url('admin-ajax.php?action=sm_get_clinic_reports&report_type=term&nonce='.$c_nonce) . '" class="sm-dropdown-item">تقرير الفصل</a>
                            <a href="' . admin_url('admin-ajax.php?action=sm_get_clinic_reports&report_type=year&nonce='.$c_nonce) . '" class="sm-dropdown-item">تقرير السنة</a>
                        </div>
                    </div>
                    ';
                }
                $btn_html .= '</div>';
                $header_map['clinic']['button'] = $btn_html;
            }

            if ($active_tab === 'hr-management' && ($is_admin || $is_sys_admin || in_array('sm_hr', $roles) || current_user_can('manage_hr'))) {
                $header_map['hr-management']['button'] = '<button type="button" onclick="eessOpenUnifiedUserModal(\'add_employee\', 0)" class="sm-btn" style="background:#000; border:1px solid #000; color:#fff; border-radius:8px; font-weight:700; height:38px; display:inline-flex; align-items:center; gap:8px; cursor:pointer;"><span class="dashicons dashicons-plus-alt"></span> إضافة موظف جديد</button>';
            }

            $cur_header = $header_map[$active_tab] ?? null;
            $excluded_banner_tabs = array('summary', 'grades', 'employee-profile', 'term-plans', 'students', 'teachers', 'parents', 'hr-evaluation', 'hr-management', 'documents', 'clinic', 'lesson-plans', 'assignments', 'attendance');
            if ($cur_header && !in_array($active_tab, $excluded_banner_tabs) && !isset($_GET['manage_employee_id']) && !isset($_GET['eess_print_eval']) && !isset($_GET['eess_print_report'])):
            ?>
                <!-- Standardized Enterprise Page Header -->
                <div style="background: #fff; padding: 25px; border-radius: 12px; border: 1px solid #e2e8f0; margin-bottom: 25px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px; font-family: 'Cairo', sans-serif !important;">
                    <div>
                        <h1 style="font-weight: 900; font-size: 1.8rem; color: #1e293b; margin: 0 0 5px 0; display: flex; align-items: center; gap: 10px;">
                            <?php echo esc_html($cur_header['title']); ?>
                        </h1>
                        <p style="margin: 0; color: #64748b; font-size: 0.9rem; font-weight: 500;"><?php echo esc_html($cur_header['desc']); ?></p>
                    </div>

                    <?php if (!empty($cur_header['button'])): ?>
                        <div>
                            <?php echo $cur_header['button']; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php 
            switch ($active_tab) {
                case 'summary':
                    if ($is_parent) {
                        if (isset($student) && $student) include SM_PLUGIN_DIR . 'templates/parent-student-summary.php';
                        else echo '<p>لا يوجد بيانات لعرضها.</p>';
                    } else {
                        include SM_PLUGIN_DIR . 'templates/public-dashboard-summary.php'; 
                    }
                    break;

                case 'students':
                    if ($is_admin || current_user_can('إدارة_الطلاب')) {
                        include SM_PLUGIN_DIR . 'templates/admin-students.php';
                    }
                    break;

                case 'record':
                    // This tab is now handled by a global modal
                    echo '<script>window.location.href="' . remove_query_arg('sm_tab') . '";</script>';
                    break;

                case 'stats':
                    if ($is_admin || current_user_can('إدارة_المخالفات') || $is_parent) {
                        include SM_PLUGIN_DIR . 'templates/public-dashboard-stats.php'; 
                    }
                    break;

                case 'documents':
                    include SM_PLUGIN_DIR . 'templates/admin-documents.php';
                    break;

                case 'teachers':
                    if ($is_admin || current_user_can('إدارة_المعلمين') || current_user_can('إدارة_المستخدمين')) {
                        include SM_PLUGIN_DIR . 'templates/admin-users-view.php';
                    }
                    break;

                case 'parents':
                    if ($is_admin || current_user_can('إدارة_أولياء_الأمور')) {
                        include SM_PLUGIN_DIR . 'templates/admin-parents.php';
                    }
                    break;



                case 'employee-profile':
                    include SM_PLUGIN_DIR . 'templates/admin-employee-profile.php';
                    break;

                case 'hr-management':
                    include SM_PLUGIN_DIR . 'templates/admin-hr-management.php';
                    break;

                case 'hr-evaluation':
                    include SM_PLUGIN_DIR . 'templates/admin-hr-evaluation.php';
                    break;

                case 'attendance':
                    include SM_PLUGIN_DIR . 'templates/admin-attendance.php';
                    break;

                case 'lesson-plans':
                    include SM_PLUGIN_DIR . 'templates/admin-lesson-prep.php';
                    break;

                case 'term-plans':
                    include SM_PLUGIN_DIR . 'templates/admin-term-plans.php';
                    break;

                case 'assets':
                    include SM_PLUGIN_DIR . 'templates/admin-asset-management.php';
                    break;

                case 'system-announcements':
                    include SM_PLUGIN_DIR . 'templates/admin-system-announcements.php';
                    break;

                case 'assignments':
                    include SM_PLUGIN_DIR . 'templates/admin-assignments.php';
                    break;

                case 'clinic':
                    include SM_PLUGIN_DIR . 'templates/admin-clinic.php';
                    break;

                case 'grades':
                    include SM_PLUGIN_DIR . 'templates/admin-grades.php';
                    break;

                case 'school-structure':
                    if ($is_admin || current_user_can('إدارة_النظام')) {
                        $institutions = EESS_Org_Helper::get_institutions();
                        $all_users = get_users();
                        ?>
                        <div id="school-structure" class="sm-content-wrapper" style="font-family: 'Cairo', sans-serif;" dir="rtl">

                            <!-- Top Header Banner Card -->
                            <div style="background: #ffffff; padding: 20px 24px; border-radius: 20px; border: 1px solid #e2e8f0; margin-bottom: 20px; box-shadow: 0 4px 18px rgba(0,0,0,0.02); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
                                <div style="display: flex; align-items: center; gap: 14px;">
                                    <div style="width: 48px; height: 48px; background: #fef2f2; border-radius: 14px; display: flex; align-items: center; justify-content: center; color: #881337; border: 1px solid #fecdd3; flex-shrink: 0;">
                                        <span class="dashicons dashicons-bank" style="font-size: 24px; width: 24px; height: 24px;"></span>
                                    </div>
                                    <div>
                                        <h2 style="margin: 0 0 4px 0; font-size: 20px; font-weight: 800; color: #0f172a;">الهيكل التنظيمي والإداري</h2>
                                        <p style="margin: 0; font-size: 12.5px; color: #64748b; font-weight: 500;">إدارة المؤسسات التعليمية، مراكز التدريب، الكليات والجامعات المعتمدة مع التكليفات والكوادر مباشرة</p>
                                    </div>
                                </div>

                                <div>
                                    <button type="button" onclick="eessOpenAddInstitutionModal()" class="sm-btn" style="background: #881337; color: #ffffff !important; height: 38px; border-radius: 9999px !important; padding: 0 20px; font-weight: 800; font-size: 12.5px; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
                                        <span class="dashicons dashicons-plus-alt2" style="font-size: 15px; width: 15px; height: 15px; color: #fff;"></span>
                                        <span>إضافة مؤسسة جديدة</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Search & Filtering Card -->
                            <div style="background: #f8fafc; padding: 18px; border-radius: 16px; border: 1px solid #e2e8f0; margin-bottom: 24px;">
                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 15px;">
                                    <div>
                                        <label style="display: block; font-size: 12px; font-weight: 800; color: #334155; margin-bottom: 6px;">البحث الفوري</label>
                                        <input type="text" id="eess-inst-search-text" onkeyup="eessFilterInstitutionCards()" placeholder="ابحث باسم المؤسسة، المدير، العنوان..." class="sm-input" style="height: 38px; font-size: 12.5px;">
                                    </div>
                                    <div>
                                        <label style="display: block; font-size: 12px; font-weight: 800; color: #334155; margin-bottom: 6px;">نوع المؤسسة</label>
                                        <select id="eess-inst-search-type" onchange="eessFilterInstitutionCards()" class="sm-select" style="height: 38px; font-size: 12.5px;">
                                            <option value="">كافة الأنواع</option>
                                            <option value="مدرسة">مدرسة</option>
                                            <option value="مركز علمي">مركز علمي</option>
                                            <option value="مركز تدريب">مركز تدريب</option>
                                            <option value="جامعة">جامعة</option>
                                            <option value="كلية">كلية</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label style="display: block; font-size: 12px; font-weight: 800; color: #334155; margin-bottom: 6px;">الدولة / الموقع</label>
                                        <input type="text" id="eess-inst-search-country" onkeyup="eessFilterInstitutionCards()" placeholder="تصفية حسب الدولة..." class="sm-input" style="height: 38px; font-size: 12.5px;">
                                    </div>
                                </div>
                            </div>

                            <!-- Institutions Cards Grid Container -->
                            <div id="eess-institutions-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 20px;">
                                <?php if (empty($institutions)): ?>
                                    <div style="grid-column: 1 / -1; background: #ffffff; border-radius: 16px; border: 1px dashed #cbd5e1; padding: 40px; text-align: center; color: #64748b;">
                                        <span class="dashicons dashicons-bank" style="font-size: 40px; width: 40px; height: 40px; color: #cbd5e1; margin-bottom: 10px;"></span>
                                        <div style="font-size: 15px; font-weight: 800; color: #0f172a;">لا توجد مؤسسات مسجلة حالياً</div>
                                        <p style="font-size: 12.5px; margin-top: 4px;">انقر على زر "إضافة مؤسسة جديدة" في الأعلى للبدء.</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($institutions as $inst):
                                        $type_badge_bg = '#f1f5f9'; $type_badge_col = '#475569';
                                        if ($inst->type === 'جامعة' || $inst->type === 'كلية') { $type_badge_bg = '#e0f2fe'; $type_badge_col = '#0369a1'; }
                                        elseif ($inst->type === 'مركز علمي' || $inst->type === 'مركز تدريب') { $type_badge_bg = '#fef3c7'; $type_badge_col = '#b45309'; }
                                        elseif ($inst->type === 'مدرسة') { $type_badge_bg = '#fef2f2'; $type_badge_col = '#881337'; }

                                        $manager_name = $inst->manager_display_name ?: ($inst->director_name ?: 'غير محدد');

                                        // Fetch assigned staff count for this single-level institution
                                        global $wpdb;
                                        $assigned_staff_ids = $wpdb->get_col($wpdb->prepare(
                                            "SELECT DISTINCT user_id FROM {$wpdb->prefix}eess_user_assignments WHERE institution_id = %d",
                                            $inst->id
                                        ));
                                        $staff_count = count($assigned_staff_ids);
                                    ?>
                                    <div class="eess-institution-card"
                                         data-id="<?php echo $inst->id; ?>"
                                         data-name="<?php echo esc_attr(strtolower($inst->name)); ?>"
                                         data-type="<?php echo esc_attr($inst->type); ?>"
                                         data-country="<?php echo esc_attr(strtolower($inst->country)); ?>"
                                         data-manager="<?php echo esc_attr(strtolower($manager_name)); ?>"
                                         style="background: #ffffff; border-radius: 18px; border: 1px solid #e2e8f0; padding: 20px; box-shadow: 0 4px 12px rgba(0,0,0,0.02); display: flex; flex-direction: column; justify-content: space-between; gap: 16px; transition: transform 0.2s, box-shadow 0.2s;"
                                         onmouseover="this.style.transform='translateY(-3px)'; this.style.boxShadow='0 8px 20px rgba(0,0,0,0.06)';"
                                         onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.02)';"
                                    >
                                        <div>
                                            <!-- Top Card Bar -->
                                            <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; margin-bottom: 12px;">
                                                <div style="display: flex; align-items: center; gap: 12px;">
                                                    <?php if (!empty($inst->logo_url)): ?>
                                                        <img src="<?php echo esc_url($inst->logo_url); ?>" style="width: 46px; height: 48px; border-radius: 12px; object-fit: cover; border: 1px solid #e2e8f0; flex-shrink: 0;">
                                                    <?php else: ?>
                                                        <div style="width: 48px; height: 48px; border-radius: 12px; background: #f8fafc; border: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: center; color: #881337; flex-shrink: 0;">
                                                            <span class="dashicons dashicons-bank" style="font-size: 22px; width: 22px; height: 22px;"></span>
                                                        </div>
                                                    <?php endif; ?>
                                                    <div>
                                                        <h3 style="margin: 0 0 4px 0; font-size: 15px; font-weight: 800; color: #0f172a; line-height: 1.3;"><?php echo esc_html($inst->name); ?></h3>
                                                        <span style="display: inline-block; padding: 2px 10px; border-radius: 9999px; background: <?php echo $type_badge_bg; ?>; color: <?php echo $type_badge_col; ?>; font-size: 10.5px; font-weight: 800;">
                                                            <?php echo esc_html($inst->type); ?>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Metadata List -->
                                            <div style="background: #f8fafc; border-radius: 12px; padding: 12px; border: 1px solid #f1f5f9; display: flex; flex-direction: column; gap: 8px; font-size: 12px; color: #334155;">
                                                <div style="display: flex; align-items: center; gap: 6px;">
                                                    <span class="dashicons dashicons-admin-users" style="font-size: 15px; width: 15px; height: 15px; color: #881337;"></span>
                                                    <span><strong>المدير / المسؤول:</strong> <?php echo esc_html($manager_name); ?></span>
                                                </div>
                                                <div style="display: flex; align-items: center; gap: 6px;">
                                                    <span class="dashicons dashicons-location" style="font-size: 15px; width: 15px; height: 15px; color: #0284c7;"></span>
                                                    <span><strong>الدولة والجهة:</strong> <?php echo esc_html($inst->country ?: 'الإمارات'); ?><?php echo !empty($inst->address) ? ' - ' . esc_html($inst->address) : ''; ?></span>
                                                </div>
                                                <?php if (!empty($inst->phone)): ?>
                                                <div style="display: flex; align-items: center; gap: 6px;">
                                                    <span class="dashicons dashicons-phone" style="font-size: 15px; width: 15px; height: 15px; color: #16a34a;"></span>
                                                    <span><strong>الهاتف:</strong> <?php echo esc_html($inst->phone); ?></span>
                                                </div>
                                                <?php endif; ?>
                                                <div style="display: flex; align-items: center; gap: 6px;">
                                                    <span class="dashicons dashicons-groups" style="font-size: 15px; width: 15px; height: 15px; color: #475569;"></span>
                                                    <span><strong>الكادر والمنسقون المسندون:</strong> <strong style="color: #881337;"><?php echo $staff_count; ?> موظف/كادر</strong></span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Circular Action Buttons -->
                                        <div style="display: flex; justify-content: flex-end; align-items: center; gap: 8px; border-top: 1px dashed #e2e8f0; padding-top: 12px;">
                                            <button type="button" onclick='eessOpenEditInstitutionModal(<?php echo json_encode($inst); ?>, <?php echo json_encode($assigned_staff_ids); ?>)' title="تعديل المؤسسة وتكليفات الكادر" style="width: 36px; height: 36px; border-radius: 50% !important; flex-shrink: 0; background: #fef2f2; color: #881337; border: 1px solid #fecdd3; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.08)'" onmouseout="this.style.transform='scale(1)'">
                                                <span class="dashicons dashicons-edit" style="font-size: 16px; width: 16px; height: 16px; margin: 0;"></span>
                                            </button>

                                            <form method="post" style="display: inline;" onsubmit="return confirm('هل أنت متأكد من حذف هذه المؤسسة نهائياً بكل بياناتها؟')">
                                                <?php wp_nonce_field('sm_admin_action', 'sm_admin_nonce'); ?>
                                                <input type="hidden" name="eess_save_single_inst" value="1">
                                                <input type="hidden" name="inst_action" value="delete">
                                                <input type="hidden" name="inst_id" value="<?php echo $inst->id; ?>">
                                                <button type="submit" title="حذف المؤسسة" style="width: 36px; height: 36px; border-radius: 50% !important; flex-shrink: 0; background: #fee2e2; color: #dc2626; border: none; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.08)'" onmouseout="this.style.transform='scale(1)'">
                                                    <span class="dashicons dashicons-trash" style="font-size: 16px; width: 16px; height: 16px; margin: 0;"></span>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>

                        </div>

                        <!-- ADD / EDIT INSTITUTION IN-SYSTEM MODAL -->
                        <div id="eess-inst-modal" class="sm-modal-overlay" style="display: none; position: fixed; inset: 0; width: 100vw; height: 100vh; background: rgba(15, 23, 42, 0.75); backdrop-filter: blur(4px); z-index: 999999; justify-content: center; align-items: center; padding: 20px; box-sizing: border-box; font-family: 'Cairo', sans-serif;">
                            <div style="background: #ffffff; border-radius: 20px; max-width: 720px; width: 100%; border: 1px solid #cbd5e1; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); overflow: hidden; display: flex; flex-direction: column; max-height: 90vh;">

                                <!-- Flush Modal Dark Header -->
                                <div style="background: #0f172a; color: #ffffff; padding: 18px 24px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #1e293b;">
                                    <h3 id="inst-modal-title" style="margin: 0; font-size: 16px; font-weight: 800; color: #ffffff; display: flex; align-items: center; gap: 10px;">
                                        <span class="dashicons dashicons-bank" style="font-size: 20px; width: 20px; height: 20px; color: #f43f5e;"></span>
                                        <span>إضافة / تعديل بيانات المؤسسة التعليمية</span>
                                    </h3>
                                    <button type="button" onclick="document.getElementById('eess-inst-modal').style.display='none';" style="background: none; border: none; color: #ffffff; font-size: 24px; cursor: pointer; line-height: 1;">&times;</button>
                                </div>

                                <form method="post" id="eess-inst-form" style="display: flex; flex-direction: column; flex: 1; overflow: hidden;">
                                    <?php wp_nonce_field('sm_admin_action', 'sm_admin_nonce'); ?>
                                    <input type="hidden" name="eess_save_single_inst" value="1">
                                    <input type="hidden" name="inst_action" id="inst_form_action" value="add">
                                    <input type="hidden" name="inst_id" id="inst_form_id" value="0">

                                    <div style="padding: 24px; overflow-y: auto; flex: 1; display: flex; flex-direction: column; gap: 18px;">

                                        <!-- Profile Information Grid -->
                                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                                            <div style="grid-column: span 2;">
                                                <label style="display: block; font-size: 12px; font-weight: 800; color: #334155; margin-bottom: 6px;">اسم المؤسسة التعليمية <span style="color:#ef4444;">*</span></label>
                                                <input type="text" name="inst_name" id="inst_input_name" required placeholder="مثال: مدرسة الأمل النموذجية، جامعة الإمارات..." class="sm-input" style="width: 100%; height: 40px;">
                                            </div>

                                            <div>
                                                <label style="display: block; font-size: 12px; font-weight: 800; color: #334155; margin-bottom: 6px;">نوع المؤسسة <span style="color:#ef4444;">*</span></label>
                                                <select name="inst_type" id="inst_input_type" required class="sm-select" style="width: 100%; height: 40px;">
                                                    <option value="مدرسة">مدرسة</option>
                                                    <option value="مركز علمي">مركز علمي</option>
                                                    <option value="مركز تدريب">مركز تدريب</option>
                                                    <option value="جامعة">جامعة</option>
                                                    <option value="كلية">كلية</option>
                                                </select>
                                            </div>

                                            <div>
                                                <label style="display: block; font-size: 12px; font-weight: 800; color: #334155; margin-bottom: 6px;">الدولة / الموقع <span style="color:#ef4444;">*</span></label>
                                                <input type="text" name="inst_country" id="inst_input_country" value="الإمارات العربية المتحدة" required class="sm-input" style="width: 100%; height: 40px;">
                                            </div>

                                            <div>
                                                <label style="display: block; font-size: 12px; font-weight: 800; color: #334155; margin-bottom: 6px;">المدير / المسؤول المباشر</label>
                                                <select name="inst_manager_id" id="inst_input_manager_id" class="sm-select" style="width: 100%; height: 40px;">
                                                    <option value="">-- اختر مديراً من مستخدمي النظام --</option>
                                                    <?php foreach ($all_users as $u): ?>
                                                        <option value="<?php echo $u->ID; ?>"><?php echo esc_html($u->display_name); ?> (@<?php echo esc_html($u->user_login); ?>)</option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>

                                            <div>
                                                <label style="display: block; font-size: 12px; font-weight: 800; color: #334155; margin-bottom: 6px;">اسم المدير المستقل (نصي خارجي)</label>
                                                <input type="text" name="inst_director_name" id="inst_input_director_name" placeholder="مثال: د. أحمد المنصوري" class="sm-input" style="width: 100%; height: 40px;">
                                            </div>

                                            <div>
                                                <label style="display: block; font-size: 12px; font-weight: 800; color: #334155; margin-bottom: 6px;">رقم هاتف المؤسسة</label>
                                                <input type="text" name="inst_phone" id="inst_input_phone" placeholder="0500000000" class="sm-input" style="width: 100%; height: 40px;">
                                            </div>

                                            <div>
                                                <label style="display: block; font-size: 12px; font-weight: 800; color: #334155; margin-bottom: 6px;">رابط الشعار الرسمي (Official Logo URL)</label>
                                                <div style="display: flex; gap: 8px;">
                                                    <input type="text" name="inst_logo_url" id="inst_input_logo_url" placeholder="https://..." class="sm-input" style="width: 100%; height: 40px;">
                                                    <button type="button" onclick="smOpenMediaUploader('inst_input_logo_url')" class="sm-btn" style="background: #475569; height: 40px; padding: 0 12px; flex-shrink: 0;">رفع</button>
                                                </div>
                                            </div>

                                            <div style="grid-column: span 2;">
                                                <label style="display: block; font-size: 12px; font-weight: 800; color: #334155; margin-bottom: 6px;">العنوان التفصيلي</label>
                                                <textarea name="inst_address" id="inst_input_address" rows="2" placeholder="المنطقة، الشارع، المبنى..." class="sm-input" style="width: 100%; padding: 8px;"></textarea>
                                            </div>
                                        </div>

                                        <!-- Integrated Staff & Position Assignments Section -->
                                        <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 14px; padding: 16px;">
                                            <h4 style="margin: 0 0 8px 0; font-size: 13.5px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 8px;">
                                                <span class="dashicons dashicons-groups" style="color: #881337;"></span>
                                                <span>تكليف وتعيين الكادر الوظيفي للمؤسسة (Staff Assignments)</span>
                                            </h4>
                                            <p style="margin: 0 0 12px 0; font-size: 11.5px; color: #64748b; line-height: 1.5;">اختر الكوادر، المعلمين والموجهين التابعين لهذه المؤسسة للربط المباشر مع صلاحياتهم:</p>

                                            <div style="max-height: 140px; overflow-y: auto; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 10px; padding: 10px; display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 10px;">
                                                <?php foreach ($all_users as $u):
                                                    $u_role = !empty($u->roles) ? $u->roles[0] : '';
                                                ?>
                                                <label style="display: flex; align-items: center; gap: 8px; font-size: 12px; color: #1e293b; cursor: pointer; padding: 4px; border-radius: 6px;" onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='transparent'">
                                                    <input type="checkbox" name="assigned_staff_ids[]" value="<?php echo $u->ID; ?>" class="inst-staff-checkbox">
                                                    <span><strong><?php echo esc_html($u->display_name); ?></strong> <small style="color: #64748b;">(<?php echo esc_html($role_map[$u_role] ?? $u_role); ?>)</small></span>
                                                </label>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>

                                    </div>

                                    <!-- Footer Bar -->
                                    <div style="padding: 16px 24px; background: #f8fafc; border-top: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
                                        <button type="button" onclick="document.getElementById('eess-inst-modal').style.display='none';" class="sm-btn sm-btn-outline" style="height: 38px; font-weight: 700; font-size: 12px;">إلغاء</button>
                                        <button type="submit" class="sm-btn" style="background: #881337; color: #ffffff !important; height: 38px; padding: 0 24px; font-weight: 800; font-size: 12.5px; border-radius: 9999px !important;">حفظ وتثبيت البيانات</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <script>
                        function eessOpenAddInstitutionModal() {
                            document.getElementById('inst_form_action').value = 'add';
                            document.getElementById('inst_form_id').value = '0';
                            document.getElementById('inst-modal-title').innerText = 'إضافة مؤسسة تعليمية جديدة';
                            document.getElementById('eess-inst-form').reset();
                            document.querySelectorAll('.inst-staff-checkbox').forEach(cb => cb.checked = false);
                            document.getElementById('eess-inst-modal').style.display = 'flex';
                        }

                        function eessOpenEditInstitutionModal(inst, assignedStaff) {
                            document.getElementById('inst_form_action').value = 'edit';
                            document.getElementById('inst_form_id').value = inst.id;
                            document.getElementById('inst-modal-title').innerText = 'تعديل بيانات وتكليفات: ' + inst.name;

                            document.getElementById('inst_input_name').value = inst.name || '';
                            document.getElementById('inst_input_type').value = inst.type || 'مدرسة';
                            document.getElementById('inst_input_country').value = inst.country || 'الإمارات العربية المتحدة';
                            document.getElementById('inst_input_manager_id').value = inst.manager_id || '';
                            document.getElementById('inst_input_director_name').value = inst.director_name || '';
                            document.getElementById('inst_input_phone').value = inst.phone || '';
                            document.getElementById('inst_input_logo_url').value = inst.logo_url || '';
                            document.getElementById('inst_input_address').value = inst.address || '';

                            document.querySelectorAll('.inst-staff-checkbox').forEach(cb => {
                                const uid = parseInt(cb.value);
                                cb.checked = Array.isArray(assignedStaff) && assignedStaff.includes(uid);
                            });

                            document.getElementById('eess-inst-modal').style.display = 'flex';
                        }

                        function eessFilterInstitutionCards() {
                            const qText = document.getElementById('eess-inst-search-text').value.toLowerCase().trim();
                            const qType = document.getElementById('eess-inst-search-type').value;
                            const qCountry = document.getElementById('eess-inst-search-country').value.toLowerCase().trim();

                            document.querySelectorAll('.eess-institution-card').forEach(card => {
                                const cName = card.getAttribute('data-name') || '';
                                const cManager = card.getAttribute('data-manager') || '';
                                const cType = card.getAttribute('data-type') || '';
                                const cCountry = card.getAttribute('data-country') || '';

                                const matchText = !qText || cName.includes(qText) || cManager.includes(qText);
                                const matchType = !qType || cType === qType;
                                const matchCountry = !qCountry || cCountry.includes(qCountry);

                                if (matchText && matchType && matchCountry) {
                                    card.style.display = 'flex';
                                } else {
                                    card.style.display = 'none';
                                }
                            });
                        }
                        </script>
                        <?php
                    }
                    break;

                case 'global-settings':
                    if ($is_admin || current_user_can('إدارة_النظام')) {
                        ?>
                        <div class="sm-tabs-wrapper" style="display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 2px solid #eee; overflow-x: auto; white-space: nowrap; padding-bottom: 10px;">
                            <button class="sm-tab-btn sm-active" onclick="smOpenInternalTab('school-settings', this)">السلطة</button>
                            <button class="sm-tab-btn" onclick="smOpenInternalTab('design-settings', this)">تصميم النظام</button>
                            <button class="sm-tab-btn" onclick="smOpenInternalTab('sidebar-settings', this)">تخصيص القائمة</button>
                            <button class="sm-tab-btn" onclick="smOpenInternalTab('backup-settings', this)">مركز النسخ الاحتياطي</button>
                            <?php if ($is_admin): ?>
                                <button class="sm-tab-btn" onclick="smOpenInternalTab('announcements-settings', this)">الإشعارات والتعاميم العامة</button>
                                <button class="sm-tab-btn" onclick="smOpenInternalTab('activity-logs', this)">سجل النشاطات</button>
                            <?php endif; ?>
                        </div>
                        <div id="school-settings" class="sm-internal-tab">
                            <form method="post">
                                <?php wp_nonce_field('sm_admin_action', 'sm_admin_nonce'); ?>
                                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">
                                    <div class="sm-form-group" style="grid-column: span 2;"><label class="sm-label">اسم المدرسة:</label><input type="text" name="school_name" value="<?php echo esc_attr($school['school_name']); ?>" class="sm-input"></div>
                                    <div class="sm-form-group"><label class="sm-label">رقم الهاتف:</label><input type="text" name="school_phone" value="<?php echo esc_attr($school['phone']); ?>" class="sm-input"></div>
                                    <div class="sm-form-group"><label class="sm-label">البريد الإلكتروني:</label><input type="email" name="school_email" value="<?php echo esc_attr($school['email']); ?>" class="sm-input"></div>
                                    <div class="sm-form-group" style="grid-column: span 2;">
                                        <label class="sm-label">شعار المدرسة:</label>
                                        <div style="display:flex; gap:10px;">
                                            <input type="text" name="school_logo" id="sm_school_logo_url" value="<?php echo esc_attr($school['school_logo']); ?>" class="sm-input">
                                            <button type="button" onclick="smOpenMediaUploader('sm_school_logo_url')" class="sm-btn" style="width:auto; font-size:12px; background:var(--sm-secondary-color);">رفع/اختيار</button>
                                        </div>
                                    </div>
                                </div>
                                <button type="submit" name="sm_save_settings_unified" class="sm-btn" style="width:auto; margin-bottom: 25px;">حفظ الإعدادات</button>

                                <!-- Creator Entity & System Information Section (EESS) - Positioned at the bottom of the section -->
                                <div class="sm-form-group" style="background: #f8fafc; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; margin-top: 25px;">
                                    <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 10px;">
                                        <span class="dashicons dashicons-external" style="color: var(--sm-accent-color); font-size: 24px; width: 24px; height: 24px;"></span>
                                        <h4 style="margin: 0; color: var(--sm-dark-color); font-weight: 800; font-size: 1.1em;">الجهة المطورة: خدمات الأنظمة الإلكترونية التعليمية (EESS)</h4>
                                    </div>
                                    <p style="margin: 5px 0; font-size: 13px; color: var(--sm-text-gray); line-height: 1.6;">
                                        تم تصميم وتطوير لوحة الإدارة هذه بواسطة <strong>خدمات الأنظمة الإلكترونية التعليمية (Educational Electronic Systems Services - EESS)</strong> كجزء من الأنظمة التعليمية الإلكترونية المتكاملة التي تدعم المؤسسات التعليمية بفعالية واحترافية.
                                    </p>
                                    <div style="margin-top: 15px; display: flex; gap: 20px; font-size: 13px; font-weight: 700;">
                                        <div>الموقع الرسمي للجهة المطورة: <a href="https://eess.online" target="_blank" style="color: var(--sm-primary-color); text-decoration: none;">eess.online</a></div>
                                        <div>الدعم الفني والبريد الرسمي: <a href="mailto:info@eess.online" style="color: var(--sm-primary-color); text-decoration: none;">info@eess.online</a></div>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div id="design-settings" class="sm-internal-tab" style="display:none;">
                            <form method="post">
                                <?php wp_nonce_field('sm_admin_action', 'sm_admin_nonce'); $app = SM_Settings::get_appearance(); ?>
                                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #e2e8f0; padding-bottom: 12px; margin-bottom: 20px;">
                                    <h4 style="margin:0; font-weight: 800; font-size: 16px; color: #0f172a;">لوحة التحكم المركزية بالهوية البصرية وتصميم المنظومة</h4>
                                    <button type="submit" name="sm_reset_appearance" onclick="return confirm('هل أنت متأكد من إعادة ضبط مظهر النظام كاملاً للقيم الافتراضية الأصلية؟')" class="sm-btn" style="background: #f1f5f9; color: #dc2626 !important; border: 1px solid #fecdd3; font-size: 12px; height: 34px;">
                                        🔄 إعادة ضبط النظام المعتمد
                                    </button>
                                </div>

                                <div style="display: grid; grid-template-columns: 1.2fr 1fr; gap: 24px; align-items: start;">
                                    <div>
                                        <!-- Section 1: Primary Colors -->
                                        <h5 style="margin: 0 0 12px 0; font-size: 13.5px; font-weight: 800; color: #881337; border-bottom: 1px solid #fee2e2; padding-bottom: 6px;">1. ألوان الهوية الرئيسية والإجراءات (Primary Colors)</h5>
                                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 14px; margin-bottom: 20px;">
                                            <div class="sm-form-group"><label class="sm-label">اللون العنابي الأساسي (إضافة / نشط):</label><input type="color" name="primary_color" value="<?php echo esc_attr($app['primary_color']); ?>" class="sm-input" style="height: 38px;"></div>
                                            <div class="sm-form-group"><label class="sm-label">لون الحوام الداكن (Primary Hover):</label><input type="color" name="primary_hover" value="<?php echo esc_attr($app['primary_hover']); ?>" class="sm-input" style="height: 38px;"></div>
                                            <div class="sm-form-group"><label class="sm-label">لون الحذف والخطورة (Danger Red):</label><input type="color" name="danger_color" value="<?php echo esc_attr($app['danger_color']); ?>" class="sm-input" style="height: 38px;"></div>
                                            <div class="sm-form-group"><label class="sm-label">لون الحذف عند الحوام (Danger Hover):</label><input type="color" name="danger_hover" value="<?php echo esc_attr($app['danger_hover']); ?>" class="sm-input" style="height: 38px;"></div>
                                        </div>

                                        <!-- Section 2: Complete Grayscale System -->
                                        <h5 style="margin: 0 0 12px 0; font-size: 13.5px; font-weight: 800; color: #0f172a; border-bottom: 1px solid #e2e8f0; padding-bottom: 6px;">2. التدرج الرمادي الشامل (Grayscale System)</h5>
                                        <div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 10px; margin-bottom: 20px;">
                                            <div><label class="sm-label" style="font-size:10px;">Gray 50</label><input type="color" name="gray_50" value="<?php echo esc_attr($app['gray_50']); ?>" class="sm-input" style="height:32px; padding:0;"></div>
                                            <div><label class="sm-label" style="font-size:10px;">Gray 100</label><input type="color" name="gray_100" value="<?php echo esc_attr($app['gray_100']); ?>" class="sm-input" style="height:32px; padding:0;"></div>
                                            <div><label class="sm-label" style="font-size:10px;">Gray 200</label><input type="color" name="gray_200" value="<?php echo esc_attr($app['gray_200']); ?>" class="sm-input" style="height:32px; padding:0;"></div>
                                            <div><label class="sm-label" style="font-size:10px;">Gray 300</label><input type="color" name="gray_300" value="<?php echo esc_attr($app['gray_300']); ?>" class="sm-input" style="height:32px; padding:0;"></div>
                                            <div><label class="sm-label" style="font-size:10px;">Gray 400</label><input type="color" name="gray_400" value="<?php echo esc_attr($app['gray_400']); ?>" class="sm-input" style="height:32px; padding:0;"></div>
                                            <div><label class="sm-label" style="font-size:10px;">Gray 500</label><input type="color" name="gray_500" value="<?php echo esc_attr($app['gray_500']); ?>" class="sm-input" style="height:32px; padding:0;"></div>
                                            <div><label class="sm-label" style="font-size:10px;">Gray 600</label><input type="color" name="gray_600" value="<?php echo esc_attr($app['gray_600']); ?>" class="sm-input" style="height:32px; padding:0;"></div>
                                            <div><label class="sm-label" style="font-size:10px;">Gray 700</label><input type="color" name="gray_700" value="<?php echo esc_attr($app['gray_700']); ?>" class="sm-input" style="height:32px; padding:0;"></div>
                                            <div><label class="sm-label" style="font-size:10px;">Gray 800</label><input type="color" name="gray_800" value="<?php echo esc_attr($app['gray_800']); ?>" class="sm-input" style="height:32px; padding:0;"></div>
                                            <div><label class="sm-label" style="font-size:10px;">Gray 900</label><input type="color" name="gray_900" value="<?php echo esc_attr($app['gray_900']); ?>" class="sm-input" style="height:32px; padding:0;"></div>
                                        </div>

                                        <!-- Section 3: Pastel Status Colors -->
                                        <h5 style="margin: 0 0 12px 0; font-size: 13.5px; font-weight: 800; color: #0f172a; border-bottom: 1px solid #e2e8f0; padding-bottom: 6px;">3. ألوان الحالات الهادئة (Pastel Status Colors)</h5>
                                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin-bottom: 20px;">
                                            <div><label class="sm-label" style="font-size:11px;">خلفية خطأ (Pastel Red):</label><input type="color" name="pastel_red_bg" value="<?php echo esc_attr($app['pastel_red_bg']); ?>" class="sm-input" style="height:32px; padding:0;"></div>
                                            <div><label class="sm-label" style="font-size:11px;">نص خطأ (Red Text):</label><input type="color" name="pastel_red_text" value="<?php echo esc_attr($app['pastel_red_text']); ?>" class="sm-input" style="height:32px; padding:0;"></div>
                                            <div><label class="sm-label" style="font-size:11px;">خلفية نجاح (Pastel Green):</label><input type="color" name="pastel_green_bg" value="<?php echo esc_attr($app['pastel_green_bg']); ?>" class="sm-input" style="height:32px; padding:0;"></div>
                                            <div><label class="sm-label" style="font-size:11px;">نص نجاح (Green Text):</label><input type="color" name="pastel_green_text" value="<?php echo esc_attr($app['pastel_green_text']); ?>" class="sm-input" style="height:32px; padding:0;"></div>
                                        </div>

                                        <!-- Section 4: Component Radii -->
                                        <h5 style="margin: 0 0 12px 0; font-size: 13.5px; font-weight: 800; color: #0f172a; border-bottom: 1px solid #e2e8f0; padding-bottom: 6px;">4. انحناء الحواف المعتمد (UI Component Radii)</h5>
                                        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 14px; margin-bottom: 20px;">
                                            <div class="sm-form-group"><label class="sm-label">حواف الأزرار (Buttons):</label><input type="text" name="button_radius" value="<?php echo esc_attr($app['button_radius']); ?>" class="sm-input" placeholder="9999px"></div>
                                            <div class="sm-form-group"><label class="sm-label">حواف الكروت (Cards):</label><input type="text" name="card_radius" value="<?php echo esc_attr($app['card_radius']); ?>" class="sm-input" placeholder="20px"></div>
                                        </div>
                                    </div>

                                    <!-- Live Interactive Preview Box -->
                                    <div style="background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 16px; padding: 20px; position: sticky; top: 20px;">
                                        <h5 style="margin: 0 0 14px 0; font-weight: 800; font-size: 14px; color: #0f172a; border-bottom: 1px solid #cbd5e1; padding-bottom: 8px;">👁️ المعاينة الحية للهوية البصرية</h5>

                                        <div style="display: flex; flex-direction: column; gap: 14px;">
                                            <!-- Buttons Preview -->
                                            <div>
                                                <span style="font-size: 11px; font-weight: 700; color: #64748b; display: block; margin-bottom: 6px;">أزرار الإجراءات:</span>
                                                <div style="display: flex; gap: 8px; flex-wrap: wrap;">
                                                    <button type="button" class="sm-btn" style="background: <?php echo esc_attr($app['primary_color']); ?>; border-radius: 9999px !important; font-size: 11px; padding: 6px 14px; color: #fff;">+ إضافة طالب</button>
                                                    <button type="button" class="sm-btn" style="background: #000; border-radius: 9999px !important; font-size: 11px; padding: 6px 14px; color: #fff;">حفظ التغييرات</button>
                                                    <button type="button" class="sm-btn" style="background: <?php echo esc_attr($app['danger_color']); ?>; border-radius: 9999px !important; font-size: 11px; padding: 6px 14px; color: #fff;">حذف السجل</button>
                                                </div>
                                            </div>

                                            <!-- Badges Preview -->
                                            <div>
                                                <span style="font-size: 11px; font-weight: 700; color: #64748b; display: block; margin-bottom: 6px;">كبسولات الحالات (Badges):</span>
                                                <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                                                    <span style="padding: 3px 10px; border-radius: 12px; background: <?php echo esc_attr($app['pastel_green_bg']); ?>; color: <?php echo esc_attr($app['pastel_green_text']); ?>; font-weight: 800; font-size: 10.5px;">نشط / معتمد</span>
                                                    <span style="padding: 3px 10px; border-radius: 12px; background: <?php echo esc_attr($app['pastel_yellow_bg']); ?>; color: <?php echo esc_attr($app['pastel_yellow_text']); ?>; font-weight: 800; font-size: 10.5px;">معلق</span>
                                                    <span style="padding: 3px 10px; border-radius: 12px; background: <?php echo esc_attr($app['pastel_red_bg']); ?>; color: <?php echo esc_attr($app['pastel_red_text']); ?>; font-weight: 800; font-size: 10.5px;">مخالفة جسيمة</span>
                                                </div>
                                            </div>

                                            <!-- Table Preview -->
                                            <div style="border: 1px solid #e2e8f0; border-radius: 10px; overflow: hidden;">
                                                <table style="width: 100%; border-collapse: collapse; font-size: 11px;">
                                                    <thead>
                                                        <tr style="background: #212121; color: #ffffff;">
                                                            <th style="padding: 6px 10px; text-align: right;">الطالب</th>
                                                            <th style="padding: 6px 10px; text-align: center;">الحالة</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr style="background: #ffffff; border-bottom: 1px solid #eee;"><td style="padding: 6px 10px;">علي أحمد</td><td style="padding: 6px 10px; text-align: center;">منتظم</td></tr>
                                                        <tr style="background: #f8f8f8;"><td style="padding: 6px 10px;">محمد خليل</td><td style="padding: 6px 10px; text-align: center;">غائب</td></tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <button type="submit" name="sm_save_appearance" class="sm-btn" style="width:auto; background: #881337; margin-top: 20px;">حفظ وتطبيق تصميم المنظومة الآن</button>
                            </form>
                        </div>

                        <div id="sidebar-settings" class="sm-internal-tab" style="display:none;">
                            <form method="post">
                                <?php wp_nonce_field('sm_admin_action', 'sm_admin_nonce');
                                $visibility = SM_Settings::get_sidebar_visibility();
                                $roles_list = array(
                                    'sm_system_admin' => 'مدير النظام',
                                    'sm_principal' => 'مدير المدرسة',
                                    'sm_supervisor' => 'مشرف',
                                    'sm_coordinator' => 'منسق مادة',
                                    'sm_hod' => 'رئيس قسم',
                                    'sm_teacher' => 'معلم',
                                    'sm_student' => 'طالب',
                                    'sm_parent' => 'ولي أمر',
                                    'sm_discipline_supervisor' => 'مشرف سلوك / انضباط',
                                    'sm_activities_supervisor' => 'مشرف أنشطة',
                                    'sm_transportation_supervisor' => 'مشرف نقل ومواصلات',
                                    'sm_bus_supervisor' => 'مشرف حافلة',
                                    'sm_hr' => 'الموارد البشرية (HR)'
                                );
                                $sections = array();
                                foreach (SM_Settings::get_system_modules() as $k => $mod) {
                                    $sections[$k] = $mod['label'];
                                }
                                ?>
                                <h4 style="margin-top:0;">تخصيص ظهور أقسام القائمة الجانبية حسب الرتب</h4>
                                <p style="font-size:12px; color:#666; margin-bottom:20px;">حدد الأقسام التي تظهر لكل رتبة من رتب مستخدمي النظام.</p>

                                <div class="sm-table-container" style="overflow-x: auto;">
                                    <table class="sm-table">
                                        <thead>
                                            <tr>
                                                <th>القسم \ الرتبة</th>
                                                <?php foreach($roles_list as $role_label): ?>
                                                    <th style="font-size: 11px; text-align: center;"><?php echo $role_label; ?></th>
                                                <?php endforeach; ?>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($sections as $sec_key => $sec_label): ?>
                                                <tr>
                                                    <td style="font-weight: 700; font-size: 13px;"><?php echo $sec_label; ?></td>
                                                    <?php foreach($roles_list as $role_key => $role_label): ?>
                                                        <td style="text-align: center;">
                                                            <input type="checkbox" name="sidebar_visibility[<?php echo $role_key; ?>][<?php echo $sec_key; ?>]" value="1" <?php checked(!empty($visibility[$role_key][$sec_key])); ?>>
                                                        </td>
                                                    <?php endforeach; ?>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <button type="submit" name="sm_save_sidebar_visibility" class="sm-btn" style="width:auto; margin-top: 20px;">حفظ إعدادات القائمة</button>
                            </form>
                        </div>

                        <div id="backup-settings" class="sm-internal-tab" style="display:none;">
                            <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; padding:30px;">
                                <h4 style="margin-top:0;">مركز النسخ الاحتياطي وإدارة البيانات</h4>
                                <?php $backup_info = SM_Settings::get_last_backup_info(); ?>
                                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px; margin-bottom:30px;">
                                    <div style="background:white; padding:15px; border-radius:8px; border:1px solid #eee;">
                                        <div style="font-size:12px; color:#718096;">آخر تصدير ناجح:</div>
                                        <div style="font-weight:700; color:var(--sm-primary-color);"><?php echo $backup_info['export']; ?></div>
                                    </div>
                                    <div style="background:white; padding:15px; border-radius:8px; border:1px solid #eee;">
                                        <div style="font-size:12px; color:#718096;">آخر استيراد ناجح:</div>
                                        <div style="font-weight:700; color:var(--sm-secondary-color);"><?php echo $backup_info['import']; ?></div>
                                    </div>
                                </div>
                                <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap:20px;">
                                    <div style="background:white; padding:20px; border-radius:8px; border:1px solid #eee;">
                                        <h5 style="margin-top:0;">تصدير البيانات الشاملة</h5>
                                        <p style="font-size:12px; color:#666; margin-bottom:15px;">قم بتحميل نسخة كاملة من بيانات الطلاب والمخالفات بصيغة JSON.</p>
                                        <div style="display:flex; gap:10px;">
                                            <form method="post">
                                                <?php wp_nonce_field('sm_admin_action', 'sm_admin_nonce'); ?>
                                                <button type="submit" name="sm_download_backup" class="sm-btn" style="background:#27ae60; width:auto;">تصدير الآن (JSON)</button>
                                            </form>
                                            <form method="get" action="<?php echo admin_url('admin-ajax.php'); ?>">
                                                <input type="hidden" name="action" value="sm_export_violations_csv">
                                                <input type="hidden" name="range" value="all">
                                                <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('sm_export_action'); ?>">
                                                <button type="submit" class="sm-btn" style="background:#111F35; width:auto;">سجل الانضباط الشامل (CSV)</button>
                                            </form>
                                        </div>
                                    </div>
                                    <div style="background:white; padding:20px; border-radius:8px; border:1px solid #eee;">
                                        <h5 style="margin-top:0;">تصدير سجلات طالب محدد</h5>
                                        <p style="font-size:12px; color:#666; margin-bottom:15px;">تصدير كافة مخالفات طالب معين باستخدام الكود الخاص به.</p>
                                        <form method="get" action="<?php echo admin_url('admin-ajax.php'); ?>" target="_blank">
                                            <input type="hidden" name="action" value="sm_export_violations_csv">
                                            <input type="hidden" name="range" value="all">
                                            <?php $ex_nonce = wp_create_nonce('sm_export_action'); ?>
                                            <input type="hidden" name="nonce" value="<?php echo $ex_nonce; ?>">
                                            <div class="sm-form-group">
                                                <input type="text" name="student_code" class="sm-input" placeholder="أدخل كود الطالب (مثال: ST00001)" required style="font-size:11px;">
                                            </div>
                                            <button type="submit" class="sm-btn" style="background:#3182ce; width:auto; font-size:11px;">تصدير سجلات الطالب</button>
                                        </form>
                                    </div>
                                    <div style="background:white; padding:20px; border-radius:8px; border:1px solid #eee;">
                                        <h5 style="margin-top:0;">استيراد البيانات</h5>
                                        <p style="font-size:12px; color:#e53e3e; margin-bottom:15px;">تحذير: سيقوم الاستيراد بمسح البيانات الحالية واستبدالها بالنسخة المرفوعة.</p>
                                        <form method="post" enctype="multipart/form-data">
                                            <?php wp_nonce_field('sm_admin_action', 'sm_admin_nonce'); ?>
                                            <input type="file" name="backup_file" required style="margin-bottom:10px; font-size:11px;">
                                            <button type="submit" name="sm_restore_backup" class="sm-btn" style="background:#2980b9; width:auto;">بدء الاستيراد</button>
                                        </form>
                                    </div>
                                    <div style="background:white; padding:20px; border-radius:8px; border:1px solid #eee;">
                                        <h5 style="margin-top:0;">مسح البيانات المخصص</h5>
                                        <p style="font-size:12px; color:#666; margin-bottom:15px;">اختر القسم الذي تريد مسح كافة بياناته نهائياً:</p>
                                        <div style="display:flex; flex-wrap:wrap; gap:10px;">
                                            <button onclick="smBulkDelete('students')" class="sm-btn sm-btn-outline" style="font-size:11px; color:#e53e3e; border-color:#feb2b2;">مسح الطلاب</button>
                                            <button onclick="smBulkDelete('teachers')" class="sm-btn sm-btn-outline" style="font-size:11px; color:#e53e3e; border-color:#feb2b2;">مسح المعلمين</button>
                                            <button onclick="smBulkDelete('parents')" class="sm-btn sm-btn-outline" style="font-size:11px; color:#e53e3e; border-color:#feb2b2;">مسح أولياء الأمور</button>
                                            <button onclick="smBulkDelete('records')" class="sm-btn sm-btn-outline" style="font-size:11px; color:#e53e3e; border-color:#feb2b2;">مسح المخالفات</button>
                                        </div>
                                    </div>
                                    <div style="background:#fff5f5; padding:20px; border-radius:8px; border:2px dashed #feb2b2;">
                                        <h5 style="margin-top:0; color:#c53030;">تهيأة النظام (إعادة ضبط المصنع)</h5>
                                        <p style="font-size:12px; color:#666; margin-bottom:15px;">هذا الإجراء سيقوم بمسح **كافة** البيانات من جميع الأقسام بما في ذلك الإعدادات والمستخدمين والطلاب.</p>
                                        <button onclick="smInitializeSystem()" class="sm-btn" style="background:#c53030; width:auto;">تهيأة النظام بالكامل</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php if ($is_admin): ?>
                        <div id="announcements-settings" class="sm-internal-tab" style="display:none;">
                            <?php include SM_PLUGIN_DIR . 'templates/admin-system-announcements.php'; ?>
                        </div>
                        <div id="activity-logs" class="sm-internal-tab" style="display:none;">
                            <div style="background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:30px;">
                                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                                    <div>
                                        <h4 style="margin:0;">سجل نشاطات النظام الشامل</h4>
                                        <div style="font-size:12px; color:#718096; margin-top:5px;">يتم الاحتفاظ بآخر 200 نشاط فقط تلقائياً.</div>
                                    </div>
                                    <button onclick="smDeleteAllLogs()" class="sm-btn" style="background:#e53e3e; width:auto; font-size:12px;">مسح كافة النشاطات</button>
                                </div>
                                <div class="sm-table-container">
                                    <table class="sm-table">
                                        <thead>
                                            <tr>
                                                <th>الوقت</th>
                                                <th>المستخدم</th>
                                                <th>الإجراء</th>
                                                <th>التفاصيل</th>
                                                <th>الإجراءات</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $limit = 20;
                                            $page_num = isset($_GET['log_page']) ? max(1, intval($_GET['log_page'])) : 1;
                                            $offset = ($page_num - 1) * $limit;
                                            $all_logs = SM_Logger::get_logs($limit, $offset);
                                            $total_logs = SM_Logger::get_total_logs();
                                            $total_pages = ceil($total_logs / $limit);

                                            foreach ($all_logs as $log):
                                                $can_rollback = strpos($log->details, 'ROLLBACK_DATA:') === 0;
                                                $details_display = $can_rollback ? 'بيانات مستعادة' : esc_html($log->details);
                                            ?>
                                                <tr>
                                                    <td style="font-size: 0.85em; color: #718096;"><?php echo esc_html($log->created_at); ?></td>
                                                    <td style="font-weight: 600;">
                                                        <?php echo esc_html($log->display_name ?: 'مستخدم غير معروف'); ?>
                                                    </td>
                                                    <td style="font-weight:700; color:var(--sm-primary-color);"><?php echo esc_html($log->action); ?></td>
                                                    <td style="font-size:0.9em;"><?php echo $details_display; ?></td>
                                                    <td>
                                                        <div style="display:flex; gap:8px;">
                                                            <?php if ($can_rollback): ?>
                                                                <button onclick="smRollbackLog(<?php echo $log->id; ?>)" class="sm-btn" style="width:auto; height:28px; padding:0 12px; font-size:11px; background:#2d3748;">استعادة</button>
                                                            <?php endif; ?>
                                                            <button onclick="smDeleteLog(<?php echo $log->id; ?>)" class="sm-btn" style="width:auto; height:28px; padding:0 12px; font-size:11px; background:#e53e3e;">حذف</button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php if ($total_pages > 1): ?>
                                    <div style="display:flex; justify-content:center; gap:10px; margin-top:20px;">
                                        <?php if ($page_num > 1): ?>
                                            <a href="<?php echo add_query_arg('log_page', $page_num - 1); ?>" class="sm-btn sm-btn-outline" style="width:auto; padding:5px 15px; text-decoration:none;">السابق</a>
                                        <?php endif; ?>
                                        <span style="align-self:center; font-size:13px;">صفحة <?php echo $page_num; ?> من <?php echo $total_pages; ?></span>
                                        <?php if ($page_num < $total_pages): ?>
                                            <a href="<?php echo add_query_arg('log_page', $page_num + 1); ?>" class="sm-btn sm-btn-outline" style="width:auto; padding:5px 15px; text-decoration:none;">التالي</a>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php
                    }
                    break;

            }
            ?>

        </div>
    </div>
</div>

<!-- GLOBAL VIOLATION MODAL -->
<div id="sm-global-violation-modal" class="sm-modal-overlay" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.75); backdrop-filter: blur(6px); z-index: 999999; align-items: center; justify-content: center; padding: 15px;">
    <div class="sm-modal-content" style="max-width: 920px; width: 95%; background: #ffffff; border-radius: 20px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); overflow: hidden; border: 1px solid #e2e8f0; max-height: 92vh; display: flex; flex-direction: column; margin: auto;">
        <div id="sm-violation-modal-body" style="overflow-y: auto; padding: 0; margin: 0; width: 100%;">
            <?php include SM_PLUGIN_DIR . 'templates/system-form.php'; ?>
        </div>
    </div>
</div>

<style>
.sm-sidebar-item { border-bottom: 1px solid #e2e8f0; transition: 0.2s; }
.sm-sidebar-link { 
    padding: 6px 15px;
    cursor: pointer; font-weight: 600; color: #4a5568 !important;
    display: flex; align-items: center; gap: 10px;
    text-decoration: none !important;
    width: 100%;
}
.sm-sidebar-item:hover { background: #edf2f7; }

/* Perfect Circle Avatar Mask */
.sm-user-profile-nav img, .sm-user-profile-nav .avatar {
    border-radius: 50% !important;
    object-fit: cover !important;
}
.sm-sidebar-item.sm-active { 
    background: #fff !important; 
    border-right: 4px solid var(--sm-primary-color) !important; 
}
.sm-sidebar-item.sm-active .sm-sidebar-link {
    color: var(--sm-primary-color) !important;
}

.sm-sidebar-badge {
    position: absolute; left: 15px; top: 50%; transform: translateY(-50%);
    background: #e53e3e; color: white; border-radius: 20px; padding: 2px 8px; font-size: 10px; font-weight: 800;
}

.sm-dropdown-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 20px;
    text-decoration: none !important;
    color: var(--sm-dark-color) !important;
    font-size: 13px;
    font-weight: 600;
    transition: 0.2s;
}
.sm-dropdown-item:hover { background: var(--sm-bg-light); color: var(--sm-primary-color) !important; }

@keyframes smFadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

/* FORCE VISIBILITY FOR PANELS */
.sm-admin-dashboard .sm-main-tab-panel {
    width: 100% !important;
}
.sm-tab-btn { padding: 10px 20px; border: 1px solid #e2e8f0; background: #f8f9fa; cursor: pointer; border-radius: 5px 5px 0 0; }
.sm-tab-btn.sm-active { background: var(--sm-primary-color) !important; color: #fff !important; border-bottom: none; }
.sm-quick-btn { background: #48bb78 !important; color: white !important; padding: 8px 15px; border-radius: 6px; font-size: 13px; font-weight: 700; border: none; cursor: pointer; display: inline-block; }
.sm-refresh-btn { background: #718096; color: white; padding: 8px 15px; border-radius: 6px; font-size: 13px; border: none; cursor: pointer; }
.sm-logout-btn { background: #e53e3e; color: white; padding: 8px 15px; border-radius: 6px; font-size: 13px; text-decoration: none; font-weight: 700; display: inline-block; }

/* Unified Premium Enterprise Data Tables */
.sm-main-panel table.sm-table {
    width: 100% !important;
    border-collapse: separate !important;
    border-spacing: 0 !important;
    margin: 15px 0 !important;
    font-family: 'Cairo', sans-serif !important;
    font-size: 13px !important;
    border: 1px solid #cbd5e1 !important;
    border-radius: 12px !important;
    overflow: hidden !important;
    box-shadow: 0 1px 3px rgba(0,0,0,0.02) !important;
}
.sm-main-panel table.sm-table th {
    background-color: #334155 !important; /* Premium Slate Charcoal */
    color: #ffffff !important;
    font-weight: 800 !important;
    padding: 12px 15px !important;
    text-align: right !important;
    font-size: 12px !important;
    border-bottom: 1px solid #1e293b !important;
}
.sm-main-panel table.sm-table td {
    padding: 10px 15px !important;
    color: #334155 !important;
    border-bottom: 1px solid #f1f5f9 !important;
    transition: background-color 0.2s !important;
}
.sm-main-panel table.sm-table tr:last-child td {
    border-bottom: none !important;
}
.sm-main-panel table.sm-table tr:hover td {
    background-color: #f8fafc !important; /* Sleek Hover effect */
}

/* Standardized Premium Action Buttons (styled exactly like Lesson Prep's buttons) */
.sm-main-panel .sm-btn:not(.sm-btn-custom),
.sm-main-panel button[type="submit"]:not(.sm-btn-custom),
.sm-main-panel input[type="submit"]:not(.sm-btn-custom) {
    background-color: #334155 !important; /* Primary Monochromatic Slate */
    color: #ffffff !important;
    border: 1px solid #334155 !important;
    padding: 8px 18px !important;
    border-radius: 8px !important; /* Slightly increased border-radius */
    font-weight: 700 !important;
    font-size: 13px !important;
    font-family: 'Cairo', sans-serif !important;
    height: 38px !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 6px !important;
    cursor: pointer !important;
    transition: all 0.2s ease-in-out !important;
    box-shadow: 0 1px 2px rgba(0,0,0,0.05) !important;
    text-decoration: none !important;
}
.sm-main-panel .sm-btn:hover,
.sm-main-panel button[type="submit"]:hover {
    background-color: #1E293B !important;
    border-color: #1E293B !important;
    color: #ffffff !important;
}
.sm-main-panel .sm-btn-outline {
    background-color: #ffffff !important;
    color: #475569 !important;
    border: 1px solid #cbd5e1 !important;
    padding: 8px 18px !important;
    border-radius: 8px !important;
    font-weight: 700 !important;
    font-size: 13px !important;
    font-family: 'Cairo', sans-serif !important;
    height: 38px !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 6px !important;
    cursor: pointer !important;
    transition: all 0.2s ease-in-out !important;
}
.sm-main-panel .sm-btn-outline:hover {
    background-color: #f8fafc !important;
    border-color: #94a3b8 !important;
    color: #1e293b !important;
}

/* Inputs border radius and style */
.sm-main-panel .sm-input,
.sm-main-panel .sm-select,
.sm-main-panel input[type="text"],
.sm-main-panel input[type="email"],
.sm-main-panel input[type="password"],
.sm-main-panel input[type="number"],
.sm-main-panel input[type="tel"],
.sm-main-panel input[type="date"],
.sm-main-panel textarea,
.sm-main-panel select {
    border: 1px solid #cbd5e1 !important;
    border-radius: 8px !important; /* Slightly increased border-radius */
    padding: 8px 12px !important;
    font-size: 13px !important;
    font-family: 'Cairo', sans-serif !important;
    background-color: #ffffff !important;
    color: #1E293B !important;
    height: 40px !important;
    transition: border-color 0.2s !important;
}
.sm-main-panel .sm-input:focus,
.sm-main-panel select:focus {
    border-color: #64748B !important;
    outline: none !important;
}
</style>

<!-- Automatic Placeholder Standardizer Snippet -->
<script>
jQuery(document).ready(function($) {
    function eessStandardizePlaceholders() {
        $('form, .sm-container, .modal, .sm-content-wrapper').find('input[type="text"], input[type="email"], input[type="password"], input[type="number"], input[type="tel"], input[type="date"], textarea, select').each(function() {
            const input = $(this);
            let placeholder = input.attr('placeholder');

            // Try to find matching label
            let label = null;
            if (input.attr('id')) {
                label = $('label[for="' + input.attr('id') + '"]');
            }
            if (!label || label.length === 0) {
                label = input.closest('.sm-form-group').find('label');
            }
            if (!label || label.length === 0) {
                label = input.prev('label');
            }
            if (!label || label.length === 0) {
                label = input.parent().find('label');
            }

            if (label && label.length > 0) {
                let text = label.text().replace(/:/g, '').replace(/\*/g, '').trim();
                if (text && (!placeholder || placeholder === text)) {
                    if (input.is('select')) {
                        // For select elements, update the first default option text
                        const firstOpt = input.find('option').first();
                        if (firstOpt && (firstOpt.val() === '' || firstOpt.text().includes('--') || firstOpt.text().includes('اختر'))) {
                            firstOpt.text(text);
                        }
                    } else {
                        input.attr('placeholder', text);
                    }
                }

                // Hide label except for checkboxes/radios
                if (label.find('input[type="checkbox"], input[type="radio"]').length === 0) {
                    label.css({
                        'display': 'none',
                        'visibility': 'hidden',
                        'height': '0',
                        'margin': '0',
                        'padding': '0'
                    });
                }
            }
        });
    }

    // Run initially and on AJAX completions
    eessStandardizePlaceholders();
    $(document).ajaxComplete(function() {
        eessStandardizePlaceholders();
    });
});
</script>

<!-- SYSTEM ANNOUNCEMENTS POPUP QUEUE ENGINE -->
<div id="eess-announcement-modal" style="display: none; position: fixed; inset: 0; width: 100vw; height: 100vh; background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(8px); z-index: 999999; justify-content: center; align-items: center; padding: 20px; box-sizing: border-box; font-family: 'Cairo', sans-serif; direction: rtl;">
    <div style="background: #ffffff; border-radius: 20px; max-width: 480px; width: 100%; padding: 30px 25px; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.3); border: 1px solid #e2e8f0; text-align: center; position: relative;">
        <div id="anc-modal-icon-container" style="width: 70px; height: 70px; border-radius: 50%; background: #eff6ff; color: #2563eb; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px auto;">
            <span id="anc-modal-icon" class="dashicons dashicons-mega" style="font-size: 36px; width: 36px; height: 36px;"></span>
        </div>

        <div style="background: #f8fafc; border-radius: 50px; padding: 4px 14px; display: inline-block; font-size: 11px; font-weight: 800; color: #64748b; margin-bottom: 12px;" id="anc-modal-badge">
            تحديث جديد من النظام
        </div>

        <h3 id="anc-modal-title" style="margin: 0 0 12px 0; font-size: 18px; font-weight: 800; color: #0f172a; line-height: 1.4;">-</h3>
        <p id="anc-modal-details" style="margin: 0 0 25px 0; font-size: 13px; color: #475569; line-height: 1.7; white-space: pre-line;">-</p>

        <div style="display: flex; gap: 10px; margin-top: 20px;">
            <button type="button" onclick="eessCloseAnnouncementModal()" style="flex: 1; height: 44px; background: #0f172a; color: #ffffff; border: none; border-radius: 12px; font-weight: 800; font-size: 14px; cursor: pointer; transition: all 0.2s ease;">
                إغلاق
            </button>
            <button type="button" onclick="eessContinueAnnouncementModal()" id="anc-modal-continue-btn" style="flex: 1; height: 44px; background: #2563eb; color: #ffffff; border: none; border-radius: 12px; font-weight: 800; font-size: 14px; cursor: pointer; transition: all 0.2s ease; display: none;">
                متابعة
            </button>
        </div>
    </div>
</div>

<script>
(function() {
    let pendingQueue = [];
    let currentAnc = null;

    function initAnnouncementsEngine() {
        // Wait 5 seconds after page load
        setTimeout(function() {
            jQuery.post('<?php echo admin_url('admin-ajax.php'); ?>', {
                action: 'sm_get_pending_announcements'
            }, function(res) {
                if (res.success && res.data && res.data.length > 0) {
                    pendingQueue = res.data;
                    processNextAnnouncement();
                }
            });
        }, 5000);
    }

    function processNextAnnouncement() {
        if (pendingQueue.length === 0) {
            document.getElementById('eess-announcement-modal').style.display = 'none';
            return;
        }

        currentAnc = pendingQueue.shift();

        const titleEl = document.getElementById('anc-modal-title');
        const detailsEl = document.getElementById('anc-modal-details');
        const iconEl = document.getElementById('anc-modal-icon');
        const containerEl = document.getElementById('anc-modal-icon-container');
        const badgeEl = document.getElementById('anc-modal-badge');

        titleEl.innerText = currentAnc.title;
        detailsEl.innerText = currentAnc.details;

        if (currentAnc.type === 'warning') {
            iconEl.className = 'dashicons dashicons-warning';
            containerEl.style.background = '#fffbebfb';
            containerEl.style.color = '#d97706';
            badgeEl.innerText = 'تنبيه هام';
        } else if (currentAnc.type === 'urgent') {
            iconEl.className = 'dashicons dashicons-dismiss';
            containerEl.style.background = '#fef2f2';
            containerEl.style.color = '#dc2626';
            badgeEl.innerText = 'عاجل جداً';
        } else if (currentAnc.type === 'success') {
            iconEl.className = 'dashicons dashicons-yes-alt';
            containerEl.style.background = '#f0fdf4';
            containerEl.style.color = '#16a34a';
            badgeEl.innerText = 'رسالة ترحيبية';
        } else {
            iconEl.className = 'dashicons dashicons-mega';
            containerEl.style.background = '#eff6ff';
            containerEl.style.color = '#2563eb';
            badgeEl.innerText = 'تحديث إداري';
        }

        // Mark viewed in DB
        jQuery.post('<?php echo admin_url('admin-ajax.php'); ?>', {
            action: 'sm_mark_announcement_viewed',
            announcement_id: currentAnc.id
        });

        document.getElementById('eess-announcement-modal').style.display = 'flex';
    }

    window.eessContinueAnnouncementModal = function() {
        eessCloseAnnouncementModal();
    };

    window.eessCloseAnnouncementModal = function() {
        if (currentAnc) {
            jQuery.post('<?php echo admin_url('admin-ajax.php'); ?>', {
                action: 'sm_mark_announcement_closed',
                announcement_id: currentAnc.id
            });
        }

        document.getElementById('eess-announcement-modal').style.display = 'none';

        // Wait 1 second before showing next pending notification in queue
        if (pendingQueue.length > 0) {
            setTimeout(function() {
                processNextAnnouncement();
            }, 1000);
        }
    };

    document.addEventListener('DOMContentLoaded', initAnnouncementsEngine);
})();
</script>

<!-- Global In-System Confirmation Modal -->
<div id="sm-global-confirm-modal" class="sm-modal-overlay" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); z-index: 99999; align-items: center; justify-content: center;" dir="rtl">
    <div style="background: #ffffff; border-radius: 16px; max-width: 440px; width: 90%; padding: 24px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.2); font-family: 'Cairo', sans-serif; text-align: center; border: 1px solid #e2e8f0;">
        <div id="sm-confirm-icon-box" style="width: 56px; height: 56px; border-radius: 50%; background: #fef2f2; color: #dc2626; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px auto;">
            <span class="dashicons dashicons-warning" style="font-size: 28px; width: 28px; height: 28px;"></span>
        </div>
        <h3 id="sm-confirm-title" style="margin: 0 0 8px 0; font-size: 1.25rem; font-weight: 800; color: #0f172a;">تأكيد الإجراء</h3>
        <p id="sm-confirm-message" style="margin: 0 0 24px 0; font-size: 0.9rem; color: #64748b; line-height: 1.6;">هل أنت متأكد من تنفيذ هذا الإجراء؟</p>
        <div style="display: flex; gap: 12px; justify-content: center;">
            <button type="button" id="sm-confirm-ok-btn" class="sm-btn" style="background: #000000; color: #ffffff !important; border-radius: 9999px; height: 40px; padding: 0 24px; font-weight: 800; font-size: 13px; border: none; cursor: pointer;">تأكيد</button>
            <button type="button" id="sm-confirm-cancel-btn" class="sm-btn" style="background: #f1f5f9; color: #475569 !important; border-radius: 9999px; height: 40px; padding: 0 20px; font-weight: 700; font-size: 13px; border: 1px solid #cbd5e1; cursor: pointer;">إلغاء</button>
        </div>
    </div>
</div>

<script>
window.smConfirmAction = function(options) {
    return new Promise(function(resolve) {
        options = options || {};
        var modal = document.getElementById('sm-global-confirm-modal');
        var titleEl = document.getElementById('sm-confirm-title');
        var msgEl = document.getElementById('sm-confirm-message');
        var okBtn = document.getElementById('sm-confirm-ok-btn');
        var cancelBtn = document.getElementById('sm-confirm-cancel-btn');
        var iconBox = document.getElementById('sm-confirm-icon-box');

        if (!modal) {
            resolve(confirm(options.message || 'هل أنت متأكد؟'));
            return;
        }

        if (titleEl) titleEl.innerText = options.title || 'تأكيد الإجراء';
        if (msgEl) msgEl.innerText = options.message || 'هل أنت متأكد من تنفيذ هذا الإجراء؟';
        if (okBtn) {
            okBtn.innerText = options.confirmText || 'تأكيد';
            if (options.type === 'danger') {
                okBtn.style.background = '#dc2626';
                if (iconBox) { iconBox.style.background = '#fef2f2'; iconBox.style.color = '#dc2626'; }
            } else if (options.type === 'success') {
                okBtn.style.background = '#16a34a';
                if (iconBox) { iconBox.style.background = '#f0fdf4'; iconBox.style.color = '#16a34a'; }
            } else {
                okBtn.style.background = '#000000';
                if (iconBox) { iconBox.style.background = '#f8fafc'; iconBox.style.color = '#334155'; }
            }
        }

        modal.style.display = 'flex';

        function cleanup() {
            modal.style.display = 'none';
            okBtn.onclick = null;
            cancelBtn.onclick = null;
        }

        okBtn.onclick = function() {
            cleanup();
            resolve(true);
        };

        cancelBtn.onclick = function() {
            cleanup();
            resolve(false);
        };
    });
};
</script>

<!-- In-System Copy Record Modal (System Administrator Only) -->
<div id="eess-copy-record-modal" class="sm-modal-overlay" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.75); backdrop-filter: blur(5px); z-index: 999999; justify-content: center; align-items: center; padding: 20px; box-sizing: border-box; font-family: 'Cairo', sans-serif;" dir="rtl">
    <div style="background: #ffffff; border-radius: 20px; max-width: 520px; width: 100%; border: 1px solid #cbd5e1; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.3); overflow: hidden;">
        <div style="background: #0f172a; color: #ffffff; padding: 18px 24px; display: flex; justify-content: space-between; align-items: center;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <span class="dashicons dashicons-admin-page" style="font-size: 20px; width: 20px; height: 20px; color: #38bdf8;"></span>
                <h3 style="margin: 0; font-size: 16px; font-weight: 800; color: #ffffff;">نسخ السجل ونقله لمستخدم آخر</h3>
            </div>
            <button type="button" onclick="document.getElementById('eess-copy-record-modal').style.display='none'" style="background: none; border: none; color: #ffffff; font-size: 24px; cursor: pointer;">&times;</button>
        </div>
        <form id="eess-copy-record-form" onsubmit="eessExecuteCopyRecordSubmit(event)" style="padding: 24px;">
            <input type="hidden" id="copy_record_type" value="">
            <input type="hidden" id="copy_record_id" value="0">

            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px 16px; margin-bottom: 18px;">
                <div style="font-size: 11.5px; font-weight: 700; color: #64748b;">السجل المحدد للنسخ:</div>
                <div id="copy_record_title_preview" style="font-size: 14px; font-weight: 800; color: #0f172a; margin-top: 2px;">-</div>
            </div>

            <div style="margin-bottom: 18px;">
                <label style="font-size: 12.5px; font-weight: 800; color: #334155; margin-bottom: 6px; display: block;">اختر المستخدم / المعلم المستهدف <span style="color:#ef4444;">*</span></label>
                <?php
                $all_system_users = get_users(array('orderby' => 'display_name', 'order' => 'ASC', 'number' => 200));
                ?>
                <select id="copy_target_user_id" onchange="eessOnCopyTargetUserChanged(this)" class="sm-input" style="height: 42px; width: 100%; border-radius: 10px; border: 1px solid #cbd5e1; padding: 0 12px; font-weight: 700; font-size: 13px;" required>
                    <option value="">-- اختر المعلم المستهدف --</option>
                    <?php foreach ($all_system_users as $su): ?>
                        <option value="<?php echo $su->ID; ?>" data-email="<?php echo esc_attr($su->user_email); ?>" data-role="<?php echo esc_attr(implode(', ', $su->roles)); ?>">
                            <?php echo esc_html($su->display_name . ' (' . $su->user_login . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div id="copy_target_user_details" style="display: none; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 12px; padding: 12px 16px; margin-bottom: 20px;">
                <div style="font-size: 11.5px; font-weight: 700; color: #166534; margin-bottom: 4px;">تأكيد حساب المستهدف:</div>
                <div style="font-size: 12.5px; color: #15803d; font-weight: 700;">
                    البريد: <span id="copy_target_email_text">-</span> | الرتبة: <span id="copy_target_role_text">-</span>
                </div>
            </div>

            <div style="display: flex; gap: 12px; justify-content: flex-end;">
                <button type="submit" id="copy_submit_btn" class="sm-btn" style="background: #000000; color: #ffffff !important; height: 40px; padding: 0 22px; font-weight: 800; border-radius: 9999px !important; border: none; cursor: pointer;">تأكيد وإنشاء النسخة</button>
                <button type="button" onclick="document.getElementById('eess-copy-record-modal').style.display='none'" class="sm-btn sm-btn-outline" style="height: 40px; padding: 0 18px; border-radius: 9999px !important; border: 1px solid #cbd5e1; color: #475569; cursor: pointer;">إلغاء</button>
            </div>
        </form>
    </div>
</div>

<script>
window.eessOpenCopyRecordModal = function(recordType, recordId, recordTitle) {
    document.getElementById('copy_record_type').value = recordType;
    document.getElementById('copy_record_id').value = recordId;
    document.getElementById('copy_record_title_preview').innerText = recordTitle || 'سجل غير مسمى';
    document.getElementById('copy_target_user_id').selectedIndex = 0;
    document.getElementById('copy_target_user_details').style.display = 'none';
    document.getElementById('eess-copy-record-modal').style.display = 'flex';
};

window.eessOnCopyTargetUserChanged = function(sel) {
    var opt = sel.options[sel.selectedIndex];
    var details = document.getElementById('copy_target_user_details');
    if (opt && opt.value) {
        document.getElementById('copy_target_email_text').innerText = opt.getAttribute('data-email') || '-';
        document.getElementById('copy_target_role_text').innerText = opt.getAttribute('data-role') || '-';
        details.style.display = 'block';
    } else {
        details.style.display = 'none';
    }
};

window.eessExecuteCopyRecordSubmit = function(e) {
    e.preventDefault();
    var recordType = document.getElementById('copy_record_type').value;
    var recordId = document.getElementById('copy_record_id').value;
    var targetUid = document.getElementById('copy_target_user_id').value;

    if (!recordId || !targetUid) {
        alert('يرجى اختيار المستخدم المستهدف.');
        return;
    }

    var btn = document.getElementById('copy_submit_btn');
    btn.disabled = true;
    btn.innerText = 'جاري النسخ...';

    var formData = new FormData();
    formData.append('action', 'sm_copy_record');
    formData.append('record_type', recordType);
    formData.append('record_id', recordId);
    formData.append('target_user_id', targetUid);
    formData.append('nonce', '<?php echo wp_create_nonce("eess_admin_action"); ?>');

    fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
    .then(function(r) { return r.json(); })
    .then(function(res) {
        btn.disabled = false;
        btn.innerText = 'تأكيد وإنشاء النسخة';
        if (res.success) {
            if (typeof smShowNotification === 'function') {
                smShowNotification(res.data.message || 'تم نسخ السجل بنجاح');
            } else {
                alert(res.data.message || 'تم نسخ السجل بنجاح');
            }
            document.getElementById('eess-copy-record-modal').style.display = 'none';
            setTimeout(function() { location.reload(); }, 600);
        } else {
            alert('خطأ: ' + (res.data || 'تعذر نسخ السجل.'));
        }
    })
    .catch(function(err) {
        btn.disabled = false;
        btn.innerText = 'تأكيد وإنشاء النسخة';
        alert('حدث خطأ في الاتصال بالسيرفر.');
    });
};
</script>

<?php include_once SM_PLUGIN_DIR . 'templates/partials/teacher-behavior-referral-modal.php'; ?>
<!-- TECHNICAL SUPPORT & HELP CAPSULE MODAL -->
<div id="eess-support-capsule-modal" style="display: none; position: fixed; inset: 0; width: 100vw; height: 100vh; background: rgba(15, 23, 42, 0.75); backdrop-filter: blur(5px); z-index: 999999; justify-content: center; align-items: center; padding: 20px; box-sizing: border-box; font-family: 'Cairo', sans-serif; direction: rtl;">
    <div style="background: #ffffff; border-radius: 20px; max-width: 640px; width: 100%; border: 1px solid #e2e8f0; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); overflow: hidden; display: flex; flex-direction: column; padding: 28px; position: relative;">
        <!-- Circular Close Button -->
        <button type="button" onclick="eessCloseSupportHelpCapsule()" style="position: absolute; top: 18px; left: 18px; width: 34px; height: 34px; border-radius: 50%; background: #f1f5f9; border: 1px solid #cbd5e1; color: #475569; font-size: 20px; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.2s; z-index: 10;" onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">&times;</button>

        <!-- Clean White Header -->
        <div style="text-align: center; margin-bottom: 20px;">
            <div style="width: 52px; height: 52px; background: #fef2f2; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; color: #dc2626; margin-bottom: 10px; border: 1px solid #fecdd3;">
                <span class="dashicons dashicons-sos" style="font-size: 26px; width: 26px; height: 26px;"></span>
            </div>
            <h3 style="margin: 0 0 4px 0; font-size: 18px; font-weight: 800; color: #0f172a;">المساعدة والدعم الفني</h3>
            <p style="margin: 0; font-size: 12.5px; color: #64748b; font-weight: 600;">يرجى اختيار إحدى الخدمات المتاحة للتواصل مع فريق الدعم والتطوير</p>
        </div>

        <!-- Body -->
        <div style="box-sizing: border-box;">
            <div id="eess-capsule-msg" style="display: none; padding: 12px; border-radius: 8px; margin-bottom: 15px; font-size: 12px; font-weight: 700;"></div>

            <!-- Main Menu Options (3 Equal Square Cards on 1 Row) -->
            <div id="capsule-menu-view">

                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px;">
                    <!-- Card 1: Suggestion -->
                    <button type="button" onclick="eessSelectCapsuleOption('suggestion')" style="background: #f8fafc; border: 1.5px solid #cbd5e1; border-radius: 16px; padding: 20px 14px; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; cursor: pointer; transition: all 0.2s ease; aspect-ratio: 1 / 1;" onmouseover="this.style.borderColor='#881337'; this.style.transform='translateY(-3px)';" onmouseout="this.style.borderColor='#cbd5e1'; this.style.transform='translateY(0)';">
                        <span class="dashicons dashicons-lightbulb" style="font-size: 32px; width: 32px; height: 32px; color: #eab308; margin: 0 0 12px 0;"></span>
                        <div style="font-weight: 800; font-size: 14.5px; color: #0f172a; margin-bottom: 6px;">تقديم مقترح</div>
                        <div style="font-size: 11px; color: #64748b; line-height: 1.4;">مقترحات أفكار لتطوير المنظومة</div>
                    </button>

                    <!-- Card 2: Technical Issue -->
                    <button type="button" onclick="eessSelectCapsuleOption('technical_issue')" style="background: #f8fafc; border: 1.5px solid #cbd5e1; border-radius: 16px; padding: 20px 14px; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; cursor: pointer; transition: all 0.2s ease; aspect-ratio: 1 / 1;" onmouseover="this.style.borderColor='#881337'; this.style.transform='translateY(-3px)';" onmouseout="this.style.borderColor='#cbd5e1'; this.style.transform='translateY(0)';">
                        <span class="dashicons dashicons-warning" style="font-size: 32px; width: 32px; height: 32px; color: #dc2626; margin: 0 0 12px 0;"></span>
                        <div style="font-weight: 800; font-size: 14.5px; color: #0f172a; margin-bottom: 6px;">مشكلة فنية</div>
                        <div style="font-size: 11px; color: #64748b; line-height: 1.4;">الإبلاغ عن خلل مع لقطة شاشة</div>
                    </button>

                    <!-- Card 3: Rating / Thank Us -->
                    <button type="button" onclick="eessSelectCapsuleOption('rating')" style="background: #f8fafc; border: 1.5px solid #cbd5e1; border-radius: 16px; padding: 20px 14px; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; cursor: pointer; transition: all 0.2s ease; aspect-ratio: 1 / 1;" onmouseover="this.style.borderColor='#881337'; this.style.transform='translateY(-3px)';" onmouseout="this.style.borderColor='#cbd5e1'; this.style.transform='translateY(0)';">
                        <span class="dashicons dashicons-star-filled" style="font-size: 32px; width: 32px; height: 32px; color: #2563eb; margin: 0 0 12px 0;"></span>
                        <div style="font-weight: 800; font-size: 14.5px; color: #0f172a; margin-bottom: 6px;">شكرنا</div>
                        <div style="font-size: 11px; color: #64748b; line-height: 1.4;">تقييم الخدمة وإرسال كلمة شكر</div>
                    </button>
                </div>
            </div>

            <!-- Option 1: Suggestion Form -->
            <div id="capsule-suggestion-view" style="display: none;">
                <h4 style="margin: 0 0 15px 0; font-size: 14px; font-weight: 800; color: #0f172a; border-bottom: 2px solid #f1f5f9; padding-bottom: 8px;">تقديم مقترح وتطوير</h4>
                <form id="eess_capsule_sug_form" onsubmit="eessSubmitCapsuleSuggestion(event)">
                    <div style="margin-bottom: 12px;">
                        <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 4px;">عنوان المقترح <span style="color:#ef4444;">*</span></label>
                        <input type="text" id="sug_title" required placeholder="عنوان مختصر للمقترح" style="width: 100%; height: 40px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 10px; font-size: 13px; box-sizing: border-box;">
                    </div>

                    <div style="margin-bottom: 15px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                            <label style="font-size: 12px; font-weight: 700; color: #334155;">تفاصيل المقترح <span style="color:#ef4444;">*</span></label>
                            <span id="sug_counter" style="font-size: 11px; font-weight: 700; color: #64748b;">0 / 1000</span>
                        </div>
                        <textarea id="sug_details" required maxlength="1000" rows="4" oninput="document.getElementById('sug_counter').innerText = this.value.length + ' / 1000'" placeholder="اكتب تفاصيل مقترحك هنا بما يسهم في تحسين العمل..." style="width: 100%; border: 1px solid #cbd5e1; border-radius: 8px; padding: 8px 10px; font-size: 13px; box-sizing: border-box;"></textarea>
                    </div>

                    <div style="display: flex; gap: 10px; justify-content: space-between;">
                        <button type="button" onclick="eessBackToCapsuleMenu()" class="sm-btn sm-btn-outline" style="height: 38px; font-size: 12px;">← العودة للقائمة</button>
                        <button type="submit" id="btn_sug_submit" class="sm-btn" style="background: #2563eb; height: 38px; font-size: 12px; padding: 0 20px;">إرسال المقترح</button>
                    </div>
                </form>
            </div>

            <!-- Option 2: Technical Problem Wizard -->
            <div id="capsule-tech-view" style="display: none;">
                <!-- Step 1: Info -->
                <div id="tech-step-1" style="display: block;">
                    <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 12px; padding: 15px; margin-bottom: 15px;">
                        <h4 style="margin: 0 0 6px 0; color: #991b1b; font-size: 14px; font-weight: 800;">الإبلاغ عن مشكلة فنية</h4>
                        <p style="margin: 0; font-size: 12px; color: #b91c1c; line-height: 1.6;">أنت على وشك الإبلاغ عن مشكلة أو خلل تقني واجهته أثناء استخدام المنظومة. سيتم توجيه هذا البلاغ مباشرة للمهندسين المختصين ومتابعته.</p>
                    </div>
                    <div style="display: flex; gap: 10px; justify-content: space-between;">
                        <button type="button" onclick="eessBackToCapsuleMenu()" class="sm-btn sm-btn-outline" style="height: 38px; font-size: 12px;">← العودة للقائمة</button>
                        <button type="button" onclick="document.getElementById('tech-step-1').style.display='none'; document.getElementById('tech-step-2').style.display='block';" class="sm-btn" style="background: #2563eb; height: 38px; font-size: 12px; padding: 0 20px;">المتابعة للخطوة التالية →</button>
                    </div>
                </div>

                <!-- Step 2: Form -->
                <div id="tech-step-2" style="display: none;">
                    <form id="eess_capsule_tech_form" onsubmit="eessSubmitCapsuleTechnicalIssue(event)">
                        <div style="margin-bottom: 12px;">
                            <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 4px;">عنوان المشكلة <span style="color:#ef4444;">*</span></label>
                            <input type="text" id="tech_title" required placeholder="عنوان مختصر للمشكلة" style="width: 100%; height: 40px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 10px; font-size: 13px; box-sizing: border-box;">
                        </div>

                        <div style="margin-bottom: 12px;">
                            <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 4px;">تفاصيل المشكلة <span style="color:#ef4444;">*</span></label>
                            <textarea id="tech_details" required rows="3" placeholder="شرح المشكلة والخطوات التي أدت لظهورها..." style="width: 100%; border: 1px solid #cbd5e1; border-radius: 8px; padding: 8px 10px; font-size: 13px; box-sizing: border-box;"></textarea>
                        </div>

                        <div style="margin-bottom: 15px;">
                            <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 4px;">رفع لقطة شاشة (Screenshot)</label>
                            <input type="file" id="tech_file" accept="image/*" style="width: 100%; font-size: 12px; padding: 4px; border: 1px solid #cbd5e1; border-radius: 8px; box-sizing: border-box;">
                        </div>

                        <div style="display: flex; gap: 10px; justify-content: space-between;">
                            <button type="button" onclick="document.getElementById('tech-step-2').style.display='none'; document.getElementById('tech-step-1').style.display='block';" class="sm-btn sm-btn-outline" style="height: 38px; font-size: 12px;">← السابق</button>
                            <button type="submit" id="btn_tech_submit" class="sm-btn" style="background: #dc2626; height: 38px; font-size: 12px; padding: 0 20px;">إرسال المشكلة</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Option 3: Thank Us & Rating Form -->
            <div id="capsule-rating-view" style="display: none;">
                <h4 style="margin: 0 0 15px 0; font-size: 14px; font-weight: 800; color: #0f172a; border-bottom: 2px solid #f1f5f9; padding-bottom: 8px;">أشكرنا وتقييم الخدمة</h4>
                <form id="eess_capsule_rating_form" onsubmit="eessSubmitCapsuleRating(event)">
                    <!-- Star Rating Selector -->
                    <div style="text-align: center; margin-bottom: 15px;">
                        <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 8px;">اختر تقييمك للخدمة:</label>
                        <div id="capsule-star-rating" style="display: inline-flex; gap: 8px; font-size: 28px; cursor: pointer; color: #f59e0b;">
                            <span onclick="eessSetRatingStars(1)" id="star-1">★</span>
                            <span onclick="eessSetRatingStars(2)" id="star-2">★</span>
                            <span onclick="eessSetRatingStars(3)" id="star-3">★</span>
                            <span onclick="eessSetRatingStars(4)" id="star-4">★</span>
                            <span onclick="eessSetRatingStars(5)" id="star-5">★</span>
                        </div>
                        <input type="hidden" id="rating_stars_val" value="5">
                    </div>

                    <div style="margin-bottom: 15px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                            <label style="font-size: 12px; font-weight: 700; color: #334155;">تعليق أو رسالة شكر</label>
                            <span id="rating_counter" style="font-size: 11px; font-weight: 700; color: #64748b;">0 / 250</span>
                        </div>
                        <textarea id="rating_comment" maxlength="250" rows="3" oninput="document.getElementById('rating_counter').innerText = this.value.length + ' / 250'" placeholder="اكتب كلمة شكر أو انطباعك عن المنظومة..." style="width: 100%; border: 1px solid #cbd5e1; border-radius: 8px; padding: 8px 10px; font-size: 13px; box-sizing: border-box;"></textarea>
                    </div>

                    <div style="display: flex; gap: 10px; justify-content: space-between;">
                        <button type="button" onclick="eessBackToCapsuleMenu()" class="sm-btn sm-btn-outline" style="height: 38px; font-size: 12px;">← العودة للقائمة</button>
                        <button type="submit" id="btn_rating_submit" class="sm-btn" style="background: #16a34a; height: 38px; font-size: 12px; padding: 0 20px;">إرسال التقييم</button>
                    </div>
                </form>
            </div>

        </div>
    </div>
</div>

<script>
let eessSelectedStars = 5;

function eessOpenSupportHelpCapsule() {
    eessBackToCapsuleMenu();
    document.getElementById('eess-support-capsule-modal').style.display = 'flex';
}

function eessCloseSupportHelpCapsule() {
    document.getElementById('eess-support-capsule-modal').style.display = 'none';
}

function eessBackToCapsuleMenu() {
    document.getElementById('eess-capsule-msg').style.display = 'none';
    document.getElementById('capsule-menu-view').style.display = 'block';
    document.getElementById('capsule-suggestion-view').style.display = 'none';
    document.getElementById('capsule-tech-view').style.display = 'none';
    document.getElementById('capsule-rating-view').style.display = 'none';
    document.getElementById('tech-step-1').style.display = 'block';
    document.getElementById('tech-step-2').style.display = 'none';
}

function eessSelectCapsuleOption(option) {
    document.getElementById('capsule-menu-view').style.display = 'none';
    document.getElementById('eess-capsule-msg').style.display = 'none';

    if (option === 'suggestion') {
        document.getElementById('capsule-suggestion-view').style.display = 'block';
    } else if (option === 'technical_issue') {
        document.getElementById('capsule-tech-view').style.display = 'block';
    } else if (option === 'rating') {
        document.getElementById('capsule-rating-view').style.display = 'block';
        eessSetRatingStars(5);
    }
}

function eessSetRatingStars(count) {
    eessSelectedStars = count;
    document.getElementById('rating_stars_val').value = count;
    for (let i = 1; i <= 5; i++) {
        const star = document.getElementById('star-' + i);
        if (i <= count) {
            star.style.color = '#f59e0b';
        } else {
            star.style.color = '#cbd5e1';
        }
    }
}

function eessShowCapsuleMsg(msg, isError) {
    const box = document.getElementById('eess-capsule-msg');
    box.innerText = msg;
    box.style.display = 'block';
    if (isError) {
        box.style.background = '#fef2f2';
        box.style.color = '#991b1b';
        box.style.border = '1px solid #fecaca';
    } else {
        box.style.background = '#f0fdf4';
        box.style.color = '#166534';
        box.style.border = '1px solid #bbf7d0';
    }
}

function eessSubmitCapsuleSuggestion(e) {
    e.preventDefault();
    const btn = document.getElementById('btn_sug_submit');
    btn.disabled = true;
    btn.innerText = 'جاري الإرسال...';

    const formData = new FormData();
    formData.append('action', 'eess_submit_support_request');
    formData.append('category', 'suggestion');
    formData.append('title', document.getElementById('sug_title').value);
    formData.append('details', document.getElementById('sug_details').value);

    fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
        btn.disabled = false;
        btn.innerText = 'إرسال المقترح';
        if (res.success) {
            eessShowCapsuleMsg(res.data.message, false);
            document.getElementById('eess_capsule_sug_form').reset();
            document.getElementById('sug_counter').innerText = '0 / 1000';
        } else {
            eessShowCapsuleMsg(res.data, true);
        }
    });
}

function eessSubmitCapsuleTechnicalIssue(e) {
    e.preventDefault();
    const btn = document.getElementById('btn_tech_submit');
    btn.disabled = true;
    btn.innerText = 'جاري الإرسال...';

    const formData = new FormData();
    formData.append('action', 'eess_submit_support_request');
    formData.append('category', 'technical_issue');
    formData.append('title', document.getElementById('tech_title').value);
    formData.append('details', document.getElementById('tech_details').value);

    const fileInput = document.getElementById('tech_file');
    if (fileInput.files.length > 0) {
        formData.append('screenshot', fileInput.files[0]);
    }

    fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
        btn.disabled = false;
        btn.innerText = 'إرسال المشكلة';
        if (res.success) {
            eessShowCapsuleMsg(res.data.message, false);
            document.getElementById('eess_capsule_tech_form').reset();
        } else {
            eessShowCapsuleMsg(res.data, true);
        }
    });
}

function eessSubmitCapsuleRating(e) {
    e.preventDefault();
    const btn = document.getElementById('btn_rating_submit');
    btn.disabled = true;
    btn.innerText = 'جاري الإرسال...';

    const formData = new FormData();
    formData.append('action', 'eess_submit_support_request');
    formData.append('category', 'rating');
    formData.append('rating_stars', document.getElementById('rating_stars_val').value);
    formData.append('comment', document.getElementById('rating_comment').value);

    fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
        btn.disabled = false;
        btn.innerText = 'إرسال التقييم';
        if (res.success) {
            eessShowCapsuleMsg(res.data.message, false);
            document.getElementById('eess_capsule_rating_form').reset();
            document.getElementById('rating_counter').innerText = '0 / 250';
        } else {
            eessShowCapsuleMsg(res.data, true);
        }
    });
}
</script>
