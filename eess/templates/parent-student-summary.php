<?php if (!defined('ABSPATH')) exit; ?>
<div style="background: #fff; border: 1px solid var(--sm-border-color); border-radius: 16px; overflow: hidden; margin-bottom: 30px; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
    <div style="background: linear-gradient(135deg, var(--sm-primary-color) 0%, #2c3e50 100%); padding: 40px; display: flex; gap: 30px; align-items: center; color: white;">
        <div style="flex-shrink: 0;">
            <div style="position: relative; cursor: pointer;" onclick="document.getElementById('student_photo_input').click()">
                <?php if ($student->photo_url): ?>
                    <img id="stu_main_photo" src="<?php echo esc_url($student->photo_url); ?>" style="width: 130px; height: 130px; border-radius: 50%; object-fit: cover; border: 4px solid rgba(255,255,255,0.3); box-shadow: 0 8px 16px rgba(0,0,0,0.2);">
                <?php else: ?>
                    <div id="stu_main_photo_placeholder" style="width: 130px; height: 130px; border-radius: 50%; background: rgba(255,255,255,0.2); display: flex; align-items: center; justify-content: center; font-size: 50px; border: 4px solid rgba(255,255,255,0.3);">👤</div>
                <?php endif; ?>
                <div style="position: absolute; bottom: 0; right: 0; background: var(--sm-primary-color); width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 2px solid white;">
                    <span class="dashicons dashicons-camera" style="font-size: 16px; color: white;"></span>
                </div>
                <input type="file" id="student_photo_input" style="display: none;" accept="image/*" onchange="uploadStudentPhoto(this, <?php echo $student->id; ?>)">
            </div>
        </div>
        <div style="flex: 1;">
            <h2 style="margin: 0 0 12px 0; border: none; padding: 0; color: white; font-size: 2em; font-weight: 800;">طالب: <?php echo esc_html($student->name); ?></h2>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; font-size: 0.95em; opacity: 0.9;">
                <span style="display: flex; align-items: center; gap: 8px;"><span class="dashicons dashicons-welcome-learn-more"></span> <?php echo SM_Settings::format_grade_name($student->class_name, $student->section); ?></span>
                <span style="display: flex; align-items: center; gap: 8px;"><span class="dashicons dashicons-id"></span> كود الطالب: <?php echo esc_html($student->student_code); ?></span>
                <span style="display: flex; align-items: center; gap: 8px;"><span class="dashicons dashicons-admin-site"></span> الجنسية: <?php echo esc_html($student->nationality ?: 'غير محدد'); ?></span>
                <span style="display: flex; align-items: center; gap: 8px;"><span class="dashicons dashicons-calendar-alt"></span> تاريخ التسجيل: <?php echo esc_html($student->registration_date); ?></span>
            </div>
        </div>
        <div style="text-align: left; display: flex; flex-direction: column; gap: 10px; align-items: flex-end;">
            <?php if ($stats['case_file']): ?>
                <div style="background: #e53e3e; color: white; padding: 5px 15px; border-radius: 50px; font-size: 12px; font-weight: 800; border: 2px solid rgba(255,255,255,0.4); animation: pulse 2s infinite;">
                    🔴 ملف متابعة سلوكية مفتوح
                </div>
            <?php endif; ?>
            <a href="<?php echo admin_url('admin-ajax.php?action=sm_print&print_type=disciplinary_report&student_id=' . $student->id); ?>" target="_blank" class="sm-btn" style="background: white !important; color: var(--sm-primary-color) !important; width: auto; font-size: 14px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">📂 تحميل الملف الشامل PDF</a>
        </div>
    </div>
</div>

<style>
@keyframes pulse {
    0% { transform: scale(1); opacity: 1; }
    50% { transform: scale(1.05); opacity: 0.8; }
    100% { transform: scale(1); opacity: 1; }
}
</style>


<div class="sm-card-grid" style="margin-bottom: 40px;">
    <div class="sm-stat-card" style="border-right: 5px solid #F63049;">
        <div style="font-size: 0.85em; color: var(--sm-text-gray); margin-bottom: 10px; font-weight: 700;">إجمالي المخالفات</div>
        <div style="font-size: 2.5em; font-weight: 900; color: #F63049;"><?php echo $stats['total'] ?? 0; ?></div>
    </div>
    <div class="sm-stat-card" style="border-right: 5px solid #111F35;">
        <div style="font-size: 0.85em; color: var(--sm-text-gray); margin-bottom: 10px; font-weight: 700;">النقاط السلوكية المستحقة</div>
        <div style="font-size: 2.5em; font-weight: 900; color: #111F35;"><?php echo $stats['points'] ?? 0; ?></div>
    </div>
    <div class="sm-stat-card">
        <div style="font-size: 0.85em; color: var(--sm-text-gray); margin-bottom: 10px; font-weight: 700;">مخالفات خطيرة</div>
        <div style="font-size: 2.5em; font-weight: 900; color: #c0392b;"><?php echo $stats['high_severity_count'] ?? 0; ?></div>
    </div>
    <div class="sm-stat-card">
        <div style="font-size: 0.85em; color: var(--sm-text-gray); margin-bottom: 10px; font-weight: 700;">النوع الأكثر تكراراً</div>
        <div style="font-size: 1.2em; font-weight: 700; color: var(--sm-secondary-color); margin-top: 15px;">
            <?php 
            $types = SM_Settings::get_violation_types();
            echo isset($types[$stats['frequent_type']]) ? $types[$stats['frequent_type']] : 'لا يوجد'; 
            ?>
        </div>
    </div>
    <div class="sm-stat-card">
        <div style="font-size: 0.85em; color: var(--sm-text-gray); margin-bottom: 10px; font-weight: 700;">آخر إجراء متخذ</div>
        <div style="font-size: 1.1em; font-weight: 700; color: #27ae60; margin-top: 15px;"><?php echo $stats['last_action'] ?: 'لا يوجد'; ?></div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 40px;">
    <div style="background: #fff; padding: 30px; border-radius: 12px; border: 1px solid var(--sm-border-color);">
        <h3 style="margin-top:0; border-bottom: 2px solid var(--sm-primary-color); padding-bottom: 10px;">الواجبات المدرسية</h3>
        <?php if (empty($student_assignments)): ?>
            <p style="padding: 20px; text-align: center; color: var(--sm-text-gray);">لا يوجد واجبات حالياً.</p>
        <?php else: ?>
            <div style="max-height: 400px; overflow-y: auto;">
                <?php foreach ($student_assignments as $assign): ?>
                    <div style="padding: 15px; border: 1px solid #eee; border-radius: 8px; margin-bottom: 10px;">
                        <div style="font-weight: 800; color: var(--sm-dark-color);"><?php echo esc_html($assign->title); ?></div>
                        <div style="font-size: 11px; color: var(--sm-text-gray); margin: 5px 0;">
                            <?php echo esc_html($assign->sender_name); ?>
                            <?php if ($assign->specialization): ?>(<?php echo esc_html($assign->specialization); ?>)<?php endif; ?>
                            | <?php echo date('Y-m-d', strtotime($assign->created_at)); ?>
                        </div>
                        <div style="font-size: 12px;"><?php echo nl2br(esc_html($assign->description)); ?></div>
                        <?php if ($assign->file_url): ?>
                            <a href="<?php echo esc_url($assign->file_url); ?>" target="_blank" class="sm-btn" style="height: 28px; font-size: 10px; margin-top: 10px; width: auto;">📎 تحميل المرفق</a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div style="background: #fff; padding: 30px; border-radius: 12px; border: 1px solid var(--sm-border-color);">
        <h3 style="margin-top:0; border-bottom: 2px solid var(--sm-accent-color); padding-bottom: 10px;">نظام الاستشارات والاستفسارات</h3>
        <?php if (!$supervisor): ?>
            <p style="padding: 20px; text-align: center; color: var(--sm-text-gray);">لم يتم تعيين مشرف لهذا الصف بعد.</p>
        <?php else: ?>
            <div style="background: #f8fafc; padding: 15px; border-radius: 8px; margin-bottom: 20px; display: flex; align-items: center; gap: 15px;">
                <?php echo get_avatar($supervisor->ID, 40, '', '', array('style' => 'border-radius:50%;')); ?>
                <div>
                    <div style="font-weight: 800; font-size: 0.9em;">المشرف: <?php echo esc_html($supervisor->display_name); ?></div>
                    <div style="font-size: 11px; color: #38a169;">متاح لاستلام استفساراتك</div>
                </div>
            </div>
            <div class="sm-form-group">
                <label class="sm-label">موضوع الاستفسار:</label>
                <textarea id="student-inquiry-msg" class="sm-textarea" rows="4" placeholder="اكتب استفسارك هنا وسيتم إرساله للمشرف مباشرة..."></textarea>
            </div>
            <button onclick="sendStudentInquiry(<?php echo $supervisor->ID; ?>)" class="sm-btn" style="background: var(--sm-accent-color);">إرسال الاستفسار الآن</button>
        <?php endif; ?>
    </div>
</div>


<div style="background: #fff; padding: 30px; border-radius: 12px; border: 1px solid var(--sm-border-color); margin-bottom: 30px; grid-column: span 2;">
    <h3 style="margin-top:0; border-bottom: 2px solid var(--sm-secondary-color); padding-bottom: 10px;">المعلمون المكلفون بالصف</h3>
    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(230px, 1fr)); gap: 15px;">
        <?php
        $grade_num = preg_replace('/[^0-9]/', '', $student->class_name);
        $assigned_teachers = SM_DB::get_staff_by_section($grade_num, $student->section);
        if (empty($assigned_teachers)):
            echo '<p style="grid-column: 1/-1; text-align:center; color:#718096;">لا يوجد معلمون مكلفون حالياً.</p>';
        else:
            foreach ($assigned_teachers as $t):
                $spec = get_user_meta($t->ID, 'sm_specialization', true);
        ?>
        <div style="background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid #e2e8f0; display: flex; align-items: center; gap: 12px;">
            <?php echo get_avatar($t->ID, 40, '', '', array('style' => 'border-radius:50%; border: 2px solid #fff; box-shadow: 0 2px 4px rgba(0,0,0,0.05);')); ?>
            <div>
                <div style="font-weight: 800; font-size: 13px; color: var(--sm-dark-color);"><?php echo esc_html($t->display_name); ?></div>
                <div style="font-size: 11px; color: var(--sm-primary-color); font-weight: 600;"><?php echo esc_html($spec ?: 'معلم'); ?></div>
            </div>
        </div>
        <?php endforeach; endif; ?>
    </div>
</div>

    <div style="background: #fff; padding: 30px; border-radius: 12px; border: 1px solid var(--sm-border-color); grid-column: span 2; margin-bottom: 30px;">
        <h3 style="margin-top:0; border-bottom: 2px solid #3498db; padding-bottom: 10px;">النتائج الأكاديمية</h3>
        <div id="student-grades-display">
            <div style="text-align: center; padding: 20px; color: var(--sm-text-gray);">جاري تحميل النتائج...</div>
        </div>
    </div>

<div style="background: #fff; padding: 30px; border-radius: 12px; border: 1px solid var(--sm-border-color);">
    <h3 style="margin-top:0;">توزيع المخالفات حسب النوع</h3>
    <div style="max-width: 500px; margin: 0 auto;">
        <canvas id="parentStudentChart"></canvas>
    </div>
</div>

<script>
function sendStudentInquiry(supervisorId) {
    const msg = document.getElementById('student-inquiry-msg').value;
    if (!msg) { alert('يرجى كتابة نص الاستفسار'); return; }

    const formData = new FormData();
    formData.append('action', 'sm_send_message_ajax');
    formData.append('receiver_id', supervisorId);
    formData.append('message', "استفسار طالب: " + msg);
    formData.append('student_id', <?php echo $student->id; ?>);
    formData.append('sm_message_nonce', '<?php echo wp_create_nonce("sm_message_action"); ?>');

    fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            smShowNotification('تم إرسال استفسارك بنجاح');
            document.getElementById('student-inquiry-msg').value = '';
        }
    });
}

(function() {
    const initParentChart = function() {
        if (typeof Chart === 'undefined') {
            setTimeout(initParentChart, 200);
            return;
        }
        const ctx = document.getElementById('parentStudentChart');
        if (!ctx) return;
        
        new Chart(ctx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: [<?php foreach($stats['by_type'] as $st) echo "'" . (isset($types[$st->type]) ? $types[$st->type] : $st->type) . "',"; ?>],
                datasets: [{
                    data: [<?php foreach($stats['by_type'] as $st) echo $st->count . ","; ?>],
                    backgroundColor: ['#F63049', '#D02752', '#8A244B', '#111F35', '#718096']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    };
    const loadGradesForDashboard = function() {
        const container = document.getElementById('student-grades-display');
        if (!container) return;

        const formData = new FormData();
        formData.append('action', 'sm_get_student_grades_ajax');
        formData.append('student_id', <?php echo $student->id; ?>);

        fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                if (res.data.length === 0) {
                    container.innerHTML = '<p style="text-align:center; padding:20px; color:#718096;">لا يوجد نتائج معتمدة حالياً.</p>';
                } else {
                    let html = '<table class="sm-table" style="box-shadow:none; border:none;"><thead><tr><th>المادة</th><th>الفصل</th><th>الدرجة</th></tr></thead><tbody>';
                    res.data.forEach(g => {
                        html += `<tr><td style="font-weight:700;">${g.subject}</td><td>${g.term}</td><td><span class="sm-badge" style="background:var(--sm-bg-light); color:var(--sm-primary-color); font-size:1.1em;">${g.grade_val}</span></td></tr>`;
                    });
                    html += '</tbody></table>';
                    container.innerHTML = html;
                }
            }
        });
    };

    if (document.readyState === 'complete') { initParentChart(); loadGradesForDashboard(); }
    else {
        window.addEventListener('load', () => { initParentChart(); loadGradesForDashboard(); });
    }
})();

function uploadStudentPhoto(input, studentId) {
    if (!input.files || !input.files[0]) return;

    const formData = new FormData();
    formData.append('action', 'sm_update_student_photo');
    formData.append('student_id', studentId);
    formData.append('student_photo', input.files[0]);
    formData.append('sm_photo_nonce', '<?php echo wp_create_nonce("sm_photo_action"); ?>');

    smShowNotification('جاري رفع الصورة...');

    fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            smShowNotification('تم تحديث الصورة الشخصية');
            const img = document.getElementById('stu_main_photo');
            if (img) img.src = res.data.photo_url;
            else location.reload();
        } else {
            smShowNotification('خطأ: ' + res.data, true);
        }
    });
}
</script>
