<?php
if (!defined('ABSPATH')) exit;

$user = wp_get_current_user();
$roles = (array) $user->roles;
$is_staff_who_can_send = in_array('administrator', $roles) || current_user_can('manage_options') || in_array('sm_system_admin', $roles) || in_array('sm_principal', $roles) || in_array('sm_supervisor', $roles) || in_array('sm_teacher', $roles) || in_array('sm_activities_supervisor', $roles) || in_array('sm_discipline_supervisor', $roles);
$is_clinic_staff = in_array('sm_clinic', $roles) || in_array('administrator', $roles) || in_array('sm_system_admin', $roles) || in_array('sm_principal', $roles) || in_array('sm_supervisor', $roles);

global $wpdb;

// Fetch pending referrals (arrival not confirmed)
$pending_referrals = $wpdb->get_results("
    SELECT c.*, s.name as student_name, s.class_name, s.section, u.display_name as referrer_name
    FROM {$wpdb->prefix}sm_clinic c
    JOIN {$wpdb->prefix}sm_students s ON c.student_id = s.id
    JOIN {$wpdb->prefix}users u ON c.referrer_id = u.ID
    WHERE c.arrival_confirmed = 0
    ORDER BY c.created_at DESC
");

// Fetch history (arrival confirmed)
$history = $wpdb->get_results("
    SELECT c.*, s.name as student_name, s.class_name, s.section, u.display_name as referrer_name
    FROM {$wpdb->prefix}sm_clinic c
    JOIN {$wpdb->prefix}sm_students s ON c.student_id = s.id
    JOIN {$wpdb->prefix}users u ON c.referrer_id = u.ID
    WHERE c.arrival_confirmed = 1
    ORDER BY c.created_at DESC
    LIMIT 100
");
?>

<div class="sm-clinic-module" dir="rtl" style="font-family: 'Cairo', sans-serif !important;">

    <!-- Single Main Banner Header with Integrated Search Bar -->
    <div style="background: #ffffff; padding: 20px 24px; border-radius: 20px; border: 1px solid #e2e8f0; margin-bottom: 18px; box-shadow: 0 4px 18px rgba(0,0,0,0.02); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
        <div style="display: flex; align-items: center; gap: 14px; flex: 1; min-width: 280px;">
            <div style="width: 48px; height: 48px; background: #fef2f2; border-radius: 14px; display: flex; align-items: center; justify-content: center; color: #881337; border: 1px solid #fecdd3; flex-shrink: 0;">
                <span class="dashicons dashicons-heart" style="font-size: 24px; width: 24px; height: 24px;"></span>
            </div>
            <div style="flex: 1;">
                <h2 style="margin: 0 0 4px 0; font-size: 20px; font-weight: 800; color: #0f172a;">العيادة المدرسية</h2>
                <p style="margin: 0 0 8px 0; font-size: 12.5px; color: #64748b; font-weight: 500;">سجل الحالات والزيارات اليومية للعيادة المدرسية والتقارير الصحية والمراجعات الطبية للطلاب</p>
                <input type="text" id="eess-clinic-search" onkeyup="eessFilterClinic()" class="sm-input" placeholder="ابحث عن طالب بالاسم، الصف، أو المحول..." style="height: 36px; border-radius: 9999px !important; width: 100%; max-width: 380px; font-size: 12px; padding: 0 14px; border: 1px solid #cbd5e1;">
            </div>
        </div>

        <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
            <?php if ($is_staff_who_can_send): ?>
            <button type="button" onclick="document.getElementById('referral-modal').style.display='flex'" class="sm-btn" style="background: #881337; color: #ffffff !important; height: 38px; border-radius: 9999px !important; padding: 0 20px; font-weight: 800; font-size: 12.5px; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
                <span class="dashicons dashicons-plus-alt2" style="font-size: 15px; width: 15px; height: 15px; color: #fff;"></span>
                <span>تحويل جديد للعيادة</span>
            </button>
            <?php endif; ?>

            <?php if ($is_clinic_staff): $c_nonce = wp_create_nonce('sm_clinic_action'); ?>
            <div style="position: relative; display: inline-block;">
                <button type="button" onclick="jQuery('#eess-clinic-reports-dropdown').toggle(); event.stopPropagation();" class="sm-btn sm-btn-outline" style="height: 38px; display: inline-flex; align-items: center; gap: 6px; border-radius: 9999px !important; cursor: pointer; background: #ffffff; color: #334155; border: 1px solid #cbd5e1; font-weight: 800; font-size: 12.5px; padding: 0 16px;">
                    <span class="dashicons dashicons-download" style="font-size: 16px; width: 16px; height: 16px; margin: 0; color: #475569;"></span>
                    <span>تحميل التقارير</span>
                    <span class="dashicons dashicons-arrow-down-alt2" style="font-size: 10px; width: 10px; height: 10px; margin: 0;"></span>
                </button>
                <div id="eess-clinic-reports-dropdown" style="display: none; position: absolute; left: 0; top: 115%; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 14px; width: 180px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); z-index: 99999; padding: 6px 0; text-align: right;">
                    <a href="<?php echo admin_url('admin-ajax.php?action=sm_get_clinic_reports&report_type=day&nonce='.$c_nonce); ?>" style="display: block; padding: 10px 16px; color: #334155; font-size: 12px; text-decoration: none; border-bottom: 1px solid #f1f5f9; font-weight: 700;">تقرير اليوم</a>
                    <a href="<?php echo admin_url('admin-ajax.php?action=sm_get_clinic_reports&report_type=week&nonce='.$c_nonce); ?>" style="display: block; padding: 10px 16px; color: #334155; font-size: 12px; text-decoration: none; border-bottom: 1px solid #f1f5f9; font-weight: 700;">تقرير الأسبوع</a>
                    <a href="<?php echo admin_url('admin-ajax.php?action=sm_get_clinic_reports&report_type=month&nonce='.$c_nonce); ?>" style="display: block; padding: 10px 16px; color: #334155; font-size: 12px; text-decoration: none; border-bottom: 1px solid #f1f5f9; font-weight: 700;">تقرير الشهر</a>
                    <a href="<?php echo admin_url('admin-ajax.php?action=sm_get_clinic_reports&report_type=term&nonce='.$c_nonce); ?>" style="display: block; padding: 10px 16px; color: #334155; font-size: 12px; text-decoration: none; border-bottom: 1px solid #f1f5f9; font-weight: 700;">تقرير الفصل</a>
                    <a href="<?php echo admin_url('admin-ajax.php?action=sm_get_clinic_reports&report_type=year&nonce='.$c_nonce); ?>" style="display: block; padding: 10px 16px; color: #334155; font-size: 12px; text-decoration: none; font-weight: 700;">تقرير السنة</a>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- PENDING REFERRALS -->
    <div style="margin-bottom: 30px;">
        <?php if (empty($pending_referrals)): ?>
            <!-- Professional Centered Empty-State Card with Soft Pastel Medical Icon -->
            <div style="background: #ffffff; border: 1px solid #fecdd3; border-radius: 16px; padding: 35px 24px; text-align: center; box-shadow: 0 2px 10px rgba(0,0,0,0.02);">
                <div style="width: 64px; height: 64px; background: #fef2f2; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; color: #881337; margin-bottom: 12px; border: 1px solid #fecdd3;">
                    <span class="dashicons dashicons-heart" style="font-size: 32px; width: 32px; height: 32px;"></span>
                </div>
                <h3 style="margin: 0 0 6px 0; font-size: 16px; font-weight: 800; color: #0f172a;">لا توجد حالات صحية أو تحويلات قائمة</h3>
                <p style="margin: 0 auto; max-width: 460px; font-size: 12.5px; color: #64748b; line-height: 1.6;">تم تصميم قسم العيادة المدرسية لمتابعة حالات الطلاب الصحية والتحويلات الميدانية. لا توجد أي تحويلات معلقة أو حالات طارئة قائمة حالياً.</p>
            </div>
        <?php else: ?>
            <div class="sm-table-container">
                <table class="sm-table">
                    <thead>
                        <tr>
                            <th>الوقت</th>
                            <th>الطالب</th>
                            <th>المحول</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pending_referrals as $r): ?>
                            <tr>
                                <td><?php echo date('H:i', strtotime($r->created_at)); ?></td>
                                <td>
                                    <div style="font-weight: 800;"><?php echo esc_html($r->student_name); ?></div>
                                    <div style="font-size: 11px; color: var(--sm-text-gray);"><?php echo $r->class_name . ' ' . $r->section; ?></div>
                                </td>
                                <td style="font-weight: 700;"><?php echo esc_html($r->referrer_name); ?></td>
                                <td>
                                    <?php if ($is_clinic_staff): ?>
                                        <button onclick="confirmClinicArrival(<?php echo $r->id; ?>)" class="sm-btn" style="background: #38a169; font-size: 11px; padding: 5px 12px;">تأكيد الوصول</button>
                                    <?php else: ?>
                                        <span class="sm-badge" style="background: #edf2f7; color: #4a5568;">بانتظار الوصول</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- HISTORY -->
    <div>
        <?php if (empty($history)): ?>
            <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 30px; text-align: center;">
                <span class="dashicons dashicons-calendar-alt" style="font-size: 28px; width: 28px; height: 28px; color: #94a3b8; margin-bottom: 8px;"></span>
                <div style="font-size: 14px; font-weight: 800; color: #475569;">سجل الزيارات خالي حالياً</div>
            </div>
        <?php else: ?>
            <div class="sm-table-container">
                <table class="sm-table">
                    <thead>
                        <tr>
                            <th>الطالب</th>
                            <th>وقت الوصول</th>
                            <th>الحالة</th>
                            <th>الإجراء</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($history as $h): ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 800;"><?php echo esc_html($h->student_name); ?></div>
                                    <div style="font-size: 11px; color: var(--sm-text-gray);"><?php echo $h->class_name . ' ' . $h->section; ?></div>
                                </td>
                                <td><?php echo date('Y-m-d H:i', strtotime($h->arrival_at)); ?></td>
                                <td style="max-width: 200px; font-size: 12px;"><?php echo esc_html($h->health_condition); ?></td>
                                <td style="max-width: 200px; font-size: 12px;"><?php echo esc_html($h->action_taken); ?></td>
                                <td>
                                    <?php if ($is_clinic_staff): ?>
                                        <button onclick="openClinicEditModal(<?php echo htmlspecialchars(json_encode($h)); ?>)" class="sm-btn sm-btn-outline" style="padding: 5px;"><span class="dashicons dashicons-edit"></span></button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Referral Modal -->
<div id="referral-modal" class="sm-modal-overlay">
    <div class="sm-modal-content" style="max-width: 500px;">
        <div class="sm-modal-header">
            <h3>تحويل طالب للعيادة</h3>
            <button class="sm-modal-close" onclick="document.getElementById('referral-modal').style.display='none'">&times;</button>
        </div>
        <div class="sm-form-group">
            <label class="sm-label">البحث عن الطالب:</label>
            <input type="text" id="clinic-student-search" class="sm-input" placeholder="اكتب اسم الطالب أو كوده..." onkeyup="clinicSearchStudents(this.value)">
            <div id="clinic-search-results" style="background: #fff; border: 1px solid #ddd; border-top: none; max-height: 200px; overflow-y: auto; display: none;"></div>
        </div>
        <div id="selected-student-box" style="display: none; background: #f0fdf4; padding: 15px; border-radius: 8px; border: 1px solid #bbf7d0; margin-bottom: 20px;">
            <div style="font-weight: 800;" id="selected-student-name"></div>
            <div style="font-size: 12px; color: #166534;" id="selected-student-info"></div>
            <input type="hidden" id="selected-student-id">
        </div>
        <div style="display: flex; gap: 10px; justify-content: flex-end;">
            <button onclick="submitClinicReferral()" class="sm-btn" style="background: var(--sm-primary-color);">إرسال للعيادة</button>
            <button onclick="document.getElementById('referral-modal').style.display='none'" class="sm-btn sm-btn-outline">إلغاء</button>
        </div>
    </div>
</div>

<!-- Clinic Record Edit Modal -->
<div id="clinic-edit-modal" class="sm-modal-overlay">
    <div class="sm-modal-content" style="max-width: 550px;">
        <div class="sm-modal-header">
            <h3>تحديث السجل الصحي</h3>
            <button class="sm-modal-close" onclick="document.getElementById('clinic-edit-modal').style.display='none'">&times;</button>
        </div>
        <input type="hidden" id="edit-referral-id">
        <div class="sm-form-group">
            <label class="sm-label">الحالة الصحية / الشكوى:</label>
            <textarea id="edit-health-condition" class="sm-textarea" rows="3"></textarea>
        </div>
        <div class="sm-form-group">
            <label class="sm-label">الإجراء المتخذ / العلاج:</label>
            <textarea id="edit-action-taken" class="sm-textarea" rows="3"></textarea>
        </div>
        <div style="display: flex; gap: 10px; justify-content: flex-end;">
            <button onclick="submitClinicUpdate()" class="sm-btn" style="background: #38a169;">حفظ السجل</button>
            <button onclick="document.getElementById('clinic-edit-modal').style.display='none'" class="sm-btn sm-btn-outline">إلغاء</button>
        </div>
    </div>
</div>

<script>
function toggleClinicReportDropdown() {
    const menu = document.getElementById('clinic-report-menu');
    menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
}

function clinicSearchStudents(query) {
    if (query.length < 2) {
        document.getElementById('clinic-search-results').style.display = 'none';
        return;
    }

    const formData = new FormData();
    formData.append('action', 'sm_search_students');
    formData.append('query', query);

    fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
        if (res.success && res.data.length > 0) {
            let html = '';
            res.data.forEach(s => {
                html += `<div style="padding: 10px; border-bottom: 1px solid #eee; cursor: pointer;" onclick="selectClinicStudent(${s.id}, '${s.name}', '${s.class_name} ${s.section}')">
                            <strong>${s.name}</strong> (${s.student_code})<br><small>${s.class_name} - ${s.section}</small>
                         </div>`;
            });
            document.getElementById('clinic-search-results').innerHTML = html;
            document.getElementById('clinic-search-results').style.display = 'block';
        }
    });
}

function selectClinicStudent(id, name, info) {
    document.getElementById('selected-student-id').value = id;
    document.getElementById('selected-student-name').innerText = name;
    document.getElementById('selected-student-info').innerText = info;
    document.getElementById('selected-student-box').style.display = 'block';
    document.getElementById('clinic-search-results').style.display = 'none';
    document.getElementById('clinic-student-search').value = '';
}

function submitClinicReferral() {
    const id = document.getElementById('selected-student-id').value;
    if (!id) { alert('يرجى اختيار طالب أولاً'); return; }

    const formData = new FormData();
    formData.append('action', 'sm_add_clinic_referral');
    formData.append('student_id', id);
    formData.append('nonce', '<?php echo wp_create_nonce("sm_clinic_action"); ?>');

    fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            smShowNotification('تم التحويل للعيادة بنجاح');
            setTimeout(() => location.reload(), 500);
        }
    });
}

function confirmClinicArrival(referralId) {
    const formData = new FormData();
    formData.append('action', 'sm_confirm_clinic_arrival');
    formData.append('referral_id', referralId);
    formData.append('nonce', '<?php echo wp_create_nonce("sm_clinic_action"); ?>');

    fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            smShowNotification('تم تأكيد وصول الطالب');
            setTimeout(() => location.reload(), 500);
        }
    });
}

function openClinicEditModal(data) {
    document.getElementById('edit-referral-id').value = data.id;
    document.getElementById('edit-health-condition').value = data.health_condition || '';
    document.getElementById('edit-action-taken').value = data.action_taken || '';
    document.getElementById('clinic-edit-modal').style.display = 'flex';
}

function submitClinicUpdate() {
    const id = document.getElementById('edit-referral-id').value;
    const cond = document.getElementById('edit-health-condition').value;
    const act = document.getElementById('edit-action-taken').value;

    const formData = new FormData();
    formData.append('action', 'sm_update_clinic_record');
    formData.append('referral_id', id);
    formData.append('health_condition', cond);
    formData.append('action_taken', act);
    formData.append('nonce', '<?php echo wp_create_nonce("sm_clinic_action"); ?>');

    fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            smShowNotification('تم تحديث السجل بنجاح');
            setTimeout(() => location.reload(), 500);
        }
    });
}

document.addEventListener('click', function(e) {
    const results = document.getElementById('clinic-search-results');
    if (results && !results.contains(e.target) && e.target.id !== 'clinic-student-search') {
        results.style.display = 'none';
    }
});

function eessFilterClinic() {
    const q = document.getElementById('eess-clinic-search').value.trim().toLowerCase();
    const rows = document.querySelectorAll('.sm-table tbody tr');

    rows.forEach(row => {
        if (row.cells.length < 2) return;
        const text = row.textContent.toLowerCase();
        if (text.includes(q)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}
</script>
