<?php
if (!defined('ABSPATH')) exit;

// Ensure variables are initialized to avoid critical error if not passed from controller
if (!isset($attendance_date)) {
    $attendance_date = isset($_GET['attendance_date']) ? sanitize_text_field($_GET['attendance_date']) : current_time('Y-m-d');
}
if (!isset($attendance_summary)) {
    $attendance_summary = SM_DB::get_attendance_summary($attendance_date);
}
?>
<div class="sm-attendance-page" dir="rtl" style="font-family: 'Cairo', sans-serif !important;">

    <!-- Single Main Banner Header (Matching Teacher Term & Annual Plans) -->
    <div style="background: #ffffff; padding: 20px 24px; border-radius: 20px; border: 1px solid #e2e8f0; margin-bottom: 18px; box-shadow: 0 4px 18px rgba(0,0,0,0.02); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
        <div style="display: flex; align-items: center; gap: 14px;">
            <div style="width: 48px; height: 48px; background: #fef2f2; border-radius: 14px; display: flex; align-items: center; justify-content: center; color: #881337; border: 1px solid #fecdd3; flex-shrink: 0;">
                <span class="dashicons dashicons-clock" style="font-size: 24px; width: 24px; height: 24px;"></span>
            </div>
            <div>
                <h2 style="margin: 0 0 4px 0; font-size: 20px; font-weight: 800; color: #0f172a;">سجل الحضور والغياب</h2>
                <p style="margin: 0; font-size: 12.5px; color: #64748b; font-weight: 500;">تسجيل ورصد الحضور والغياب اليومي للطلاب ومتابعة الإحصائيات العامة المعتمدة</p>
            </div>
        </div>

        <div style="display: flex; gap: 10px; align-items: center; flex-wrap: nowrap;">
            <a href="<?php echo home_url('/attendance/'); ?>" class="sm-btn" style="background: #881337; color: #ffffff !important; height: 38px; border-radius: 9999px !important; padding: 0 20px; font-weight: 800; font-size: 12.5px; border: none; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; white-space: nowrap;">
                <span class="dashicons dashicons-edit" style="font-size: 15px; width: 15px; height: 15px; color: #fff;"></span>
                <span>تسجيل الحضور</span>
            </a>

            <!-- Absence Reports Dropdown -->
            <div style="position: relative; display: inline-block;">
                <button type="button" onclick="eessToggleAbsenceDropdown(event)" class="sm-btn sm-btn-outline" style="height: 38px; display: inline-flex; align-items: center; gap: 6px; border-radius: 9999px !important; cursor: pointer; background: #ffffff; color: #334155; border: 1px solid #cbd5e1; font-weight: 800; font-size: 12.5px; padding: 0 16px; white-space: nowrap;">
                    <span class="dashicons dashicons-analytics" style="font-size: 16px; width: 16px; height: 16px; margin: 0; color: #475569;"></span>
                    <span>سجل الغيابات</span>
                    <span class="dashicons dashicons-arrow-down-alt2" style="font-size: 10px; width: 10px; height: 10px; margin: 0;"></span>
                </button>
                <div id="eess-absence-dropdown" style="display: none; position: absolute; left: 0; top: 115%; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 14px; width: 220px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); z-index: 99999; padding: 6px 0; text-align: right;">
                    <a href="javascript:void(0)" onclick="printAbsenceReport('daily'); document.getElementById('eess-absence-dropdown').style.display='none';" style="display: block; padding: 10px 16px; color: #334155; font-size: 12px; text-decoration: none; border-bottom: 1px solid #f1f5f9; font-weight: 700;">📊 غيابات اليوم</a>
                    <a href="javascript:void(0)" onclick="printAbsenceReport('term'); document.getElementById('eess-absence-dropdown').style.display='none';" style="display: block; padding: 10px 16px; color: #334155; font-size: 12px; text-decoration: none; font-weight: 700;">📈 الأكثر غياباً (الفصل)</a>
                </div>
            </div>

            <div style="display: flex; align-items: center; gap: 6px;">
                <label for="attendance-filter-date" style="font-size: 12px; font-weight: 700; color: #475569; white-space: nowrap;">التاريخ:</label>
                <input type="date" id="attendance-filter-date" class="sm-input" value="<?php echo esc_attr($attendance_date); ?>" onchange="window.location.href='<?php echo add_query_arg('attendance_date', '', $_SERVER['REQUEST_URI']); ?>' + this.value" style="height: 38px; border-radius: 9999px !important; padding: 0 14px; font-size: 12px; font-family: 'Cairo'; border: 1px solid #cbd5e1; outline: none;">
            </div>
        </div>
    </div>

    <!-- Stats Summary -->
    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 40px;">
        <?php
        $total_students = 0;
        $total_present = 0;
        $total_absent = 0;
        $total_late = 0;
        if (is_array($attendance_summary)) {
            foreach ($attendance_summary as $card) {
                $total_students += $card['student_count'];
                $total_present += $card['stats']['present'];
                $total_absent += $card['stats']['absent'];
                $total_late += $card['stats']['late'];
            }
        }
        ?>
        <div class="sm-stat-card" style="padding: 15px; background: #f8fafc; border: 1px solid #e2e8f0;">
            <div style="font-size: 11px; color: var(--sm-text-gray); font-weight: 700;">إجمالي الطلاب</div>
            <div style="font-size: 1.8em; font-weight: 900; color: var(--sm-dark-color);"><?php echo $total_students; ?></div>
        </div>
        <div class="sm-stat-card" style="padding: 15px; border: 1px solid #c6f6d5; background: #f0fff4;">
            <div style="font-size: 11px; color: #2f855a; font-weight: 700;">حضور</div>
            <div style="font-size: 1.8em; font-weight: 900; color: #38a169;"><?php echo $total_present; ?></div>
        </div>
        <div class="sm-stat-card" style="padding: 15px; border: 1px solid #fed7d7; background: #fff5f5;">
            <div style="font-size: 11px; color: #c53030; font-weight: 700;">غياب</div>
            <div style="font-size: 1.8em; font-weight: 900; color: #e53e3e;"><?php echo $total_absent; ?></div>
        </div>
        <div class="sm-stat-card" style="padding: 15px; border: 1px solid #feebc8; background: #fffaf0;">
            <div style="font-size: 11px; color: #c05621; font-weight: 700;">تأخير</div>
            <div style="font-size: 1.8em; font-weight: 900; color: #ecc94b;"><?php echo $total_late; ?></div>
        </div>
    </div>

    <!-- Filters -->
    <div style="background: #fff; padding: 20px; border-radius: 12px; border: 1px solid var(--sm-border-color); margin-bottom: 30px; display: flex; gap: 15px;">
        <input type="text" id="card-search" class="sm-input" placeholder="بحث عن صف أو شعبة..." onkeyup="filterAttendanceCards()">
        <select id="card-status-filter" class="sm-select" onchange="filterAttendanceCards()" style="width: 200px;">
            <option value="all">كل الحالات</option>
            <option value="complete">مكتمل</option>
            <option value="incomplete">غير مكتمل</option>
            <option value="absences">يوجد غيابات</option>
        </select>
    </div>

    <!-- Cards Grid -->
    <div id="attendance-cards-grid">
        <?php
        // Group by Grade
        $grouped_cards = array();
        if (is_array($attendance_summary)) {
            foreach ($attendance_summary as $card) {
                $grouped_cards[$card['class_name']][] = $card;
            }
        }

        foreach ($grouped_cards as $grade_name => $cards): ?>
            <div class="attendance-grade-section" style="margin-bottom: 40px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; border-bottom: 2px solid var(--sm-border-color); padding-bottom: 10px;">
                    <h3 style="margin: 0; font-weight: 900; color: var(--sm-primary-color);"><?php echo esc_html($grade_name); ?></h3>
                    <button onclick="printAttendance('grade', '<?php echo esc_js($grade_name); ?>')" class="sm-btn sm-btn-outline" style="font-size: 11px; padding: 4px 10px;">
                        <span class="dashicons dashicons-printer" style="font-size: 14px;"></span> طباعة الصف
                    </button>
                </div>
                <div style="display: grid; grid-template-columns: 1fr; gap: 15px;">
                    <?php foreach ($cards as $card):
                        $status_color = '#e53e3e'; // Red (Default Incomplete)
            $status_text = 'غير مكتمل';

            if ($card['is_complete']) {
                if ($card['has_absences']) {
                    $status_color = '#ecc94b'; // Yellow (Complete but with absences)
                    $status_text = 'يوجد غيابات/تأخير';
                } else {
                    $status_color = '#38a169'; // Green (Full attendance)
                    $status_text = 'حضور كامل';
                }
            }
        ?>
        <div class="sm-attendance-card"
             data-grade="<?php echo esc_attr($card['class_name']); ?>"
             data-section="<?php echo esc_attr($card['section']); ?>"
             data-complete="<?php echo $card['is_complete'] ? 'yes' : 'no'; ?>"
             data-absences="<?php echo $card['has_absences'] ? 'yes' : 'no'; ?>"
             style="background: #fff; border: 1px solid var(--sm-border-color); border-radius: 15px; padding: 20px 30px; transition: all 0.3s ease; position: relative; border-right: 6px solid <?php echo $status_color; ?>; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); display: flex; align-items: center; justify-content: space-between; gap: 30px; margin-bottom: 10px;">

            <div style="flex: 2; display: flex; align-items: center; gap: 25px; cursor: pointer;" onclick="openAttendanceModal('<?php echo esc_js($card['class_name']); ?>', '<?php echo esc_js($card['section']); ?>')">
                <div style="width: 50px; height: 50px; background: #f1f5f9; border-radius: 12px; display: flex; align-items: center; justify-content: center; color: var(--sm-primary-color); border: 1px solid #e2e8f0;">
                    <span class="dashicons dashicons-groups" style="font-size: 28px; width: 28px; height: 28px;"></span>
                </div>
                <div>
                    <h4 style="margin: 0; font-weight: 900; color: var(--sm-dark-color); font-size: 1.1em;"><?php echo esc_html($card['class_name']); ?> - شعبة <?php echo esc_html($card['section']); ?></h4>
                    <div style="font-size: 12px; color: var(--sm-text-gray); font-weight: 700; margin-top: 4px;">
                        إجمالي الطلاب: <?php echo $card['student_count']; ?>
                    </div>
                </div>
            </div>

            <div style="flex: 1; display: flex; flex-direction: column; gap: 5px;">
                <div style="font-size: 12px; color: <?php echo $status_color; ?>; font-weight: 700; display: flex; align-items: center; gap: 5px;">
                    <span class="dashicons dashicons-marker" style="font-size: 14px; width: 14px; height: 14px;"></span>
                    <?php echo $status_text; ?>
                </div>
                <div style="display: flex; gap: 8px; font-size: 11px; font-weight: 700;">
                    <span style="color: #38a169;">حاضر: <?php echo $card['stats']['present']; ?></span>
                    <span style="color: #e53e3e;">غائب: <?php echo $card['stats']['absent']; ?></span>
                    <span style="color: #ecc94b;">تأخير: <?php echo $card['stats']['late']; ?></span>
                </div>
            </div>

            <div style="flex: 1; display: flex; align-items: center; gap: 15px; border-right: 1px dashed #e2e8f0; padding-right: 15px;">
                <div>
                    <div style="font-size: 10px; color: var(--sm-text-gray); font-weight: 700; margin-bottom: 2px;">كود الأمان:</div>
                    <div id="code-<?php echo sanitize_title($card['class_name'] . '-' . $card['section']); ?>" style="font-family: monospace; font-size: 18px; font-weight: 900; color: var(--sm-dark-color); letter-spacing: 2px;">
                        <?php echo SM_Settings::get_class_security_code($card['class_name'], $card['section']); ?>
                    </div>
                </div>
                <button onclick="resetClassCode('<?php echo esc_js($card['class_name']); ?>', '<?php echo esc_js($card['section']); ?>', this)" class="sm-btn sm-btn-outline" style="padding: 4px 8px; font-size: 10px; height: auto;" title="إعادة تعيين الكود">
                    <span class="dashicons dashicons-randomize"></span>
                </button>
            </div>

            <div style="display: flex; gap: 10px;">
                <button onclick="printAttendance('section', '<?php echo esc_js($card['class_name']); ?>', '<?php echo esc_js($card['section']); ?>')" class="sm-btn sm-btn-outline" style="width: 38px; height: 38px; padding: 0;" title="طباعة الكشف">
                    <span class="dashicons dashicons-printer"></span>
                </button>
                <button onclick="openAttendanceModal('<?php echo esc_js($card['class_name']); ?>', '<?php echo esc_js($card['section']); ?>')" class="sm-btn" style="height: 38px; font-size: 11px;">تحديث</button>
            </div>
        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Attendance Marking Modal -->
<div id="sm-attendance-marking-modal" class="sm-modal-overlay">
    <div class="sm-modal-content" style="max-width: 700px;">
        <div class="sm-modal-header">
            <div>
                <h3 id="modal-attendance-title" style="margin: 0;">تسجيل الحضور</h3>
                <div id="modal-attendance-subtitle" style="font-size: 13px; color: var(--sm-text-gray); margin-top: 5px;"></div>
            </div>
            <button class="sm-modal-close" onclick="closeAttendanceModal()">&times;</button>
        </div>

        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; background: #f8fafc; padding: 10px; border-radius: 8px;">
            <div style="font-weight: 700; font-size: 13px;">تغيير سريع للكل:</div>
            <div style="display: flex; gap: 8px;">
                <button onclick="setAllAttendance('present')" class="sm-btn" style="background: #38a169; font-size: 11px; padding: 5px 12px;">حضور للكل</button>
                <button onclick="setAllAttendance('absent')" class="sm-btn" style="background: #e53e3e; font-size: 11px; padding: 5px 12px;">غياب للكل</button>
            </div>
        </div>

        <div id="attendance-students-list" style="max-height: 400px; overflow-y: auto;">
            <!-- Loaded via AJAX -->
            <div style="text-align: center; padding: 40px; color: var(--sm-text-gray);">جاري تحميل قائمة الطلاب...</div>
        </div>

        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid var(--sm-border-color); display: flex; justify-content: flex-end;">
            <button onclick="closeAttendanceModal()" class="sm-btn" style="background: var(--sm-dark-color);">إغلاق وحفظ</button>
        </div>
    </div>
</div>

<script>
function filterAttendanceCards() {
    const search = document.getElementById('card-search').value.toLowerCase();
    const status = document.getElementById('card-status-filter').value;
    const cards = document.querySelectorAll('.sm-attendance-card');

    cards.forEach(card => {
        const grade = card.getAttribute('data-grade').toLowerCase();
        const section = card.getAttribute('data-section').toLowerCase();
        const isComplete = card.getAttribute('data-complete') === 'yes';
        const hasAbsences = card.getAttribute('data-absences') === 'yes';

        let show = true;
        if (search && !grade.includes(search) && !section.includes(search)) show = false;

        if (status === 'complete' && !isComplete) show = false;
        if (status === 'incomplete' && isComplete) show = false;
        if (status === 'absences' && !hasAbsences) show = false;

        card.style.display = show ? 'block' : 'none';
    });
}

function openAttendanceModal(className, section) {
    const date = document.getElementById('attendance-filter-date').value;
    document.getElementById('modal-attendance-title').innerText = 'تسجيل حضور: ' + className;
    document.getElementById('modal-attendance-subtitle').innerText = 'الشعبة: ' + section + ' | التاريخ: ' + date;
    document.getElementById('sm-attendance-marking-modal').style.display = 'flex';

    loadAttendanceStudents(className, section, date);
}

function closeAttendanceModal() {
    document.getElementById('sm-attendance-marking-modal').style.display = 'none';
    // Optionally reload to update cards
    location.reload();
}

function loadAttendanceStudents(className, section, date) {
    const listContainer = document.getElementById('attendance-students-list');
    listContainer.innerHTML = '<div style="text-align: center; padding: 40px; color: var(--sm-text-gray);">جاري التحميل...</div>';

    const formData = new FormData();
    formData.append('action', 'sm_get_students_attendance_ajax');
    formData.append('class_name', className);
    formData.append('section', section);
    formData.append('date', date);

    fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            renderStudentsList(res.data);
        } else {
            listContainer.innerHTML = '<div style="color: red; padding: 20px;">' + res.data + '</div>';
        }
    });
}

function renderStudentsList(students) {
    const listContainer = document.getElementById('attendance-students-list');
    if (students.length === 0) {
        listContainer.innerHTML = '<div style="padding: 20px; text-align: center;">لا يوجد طلاب في هذا الصف.</div>';
        return;
    }

    let html = '<table class="sm-table" style="box-shadow: none; border: none;"><tbody>';
    students.forEach(s => {
        const photo = s.photo_url ? `<img src="${s.photo_url}" style="width: 32px; height: 32px; border-radius: 50%; object-fit: cover;">` : `<div style="width: 32px; height: 32px; border-radius: 50%; background: #f1f5f9; display: flex; align-items: center; justify-content: center; font-size: 14px;">👤</div>`;

        html += `
            <tr data-student-id="${s.id}">
                <td style="width: 50px;">${photo}</td>
                <td>
                    <div style="font-weight: 700; font-size: 13px;">${s.name}</div>
                    <div style="font-size: 11px; color: var(--sm-text-gray);">${s.student_code}</div>
                </td>
                <td style="text-align: left;">
                    <div class="attendance-options" style="display: flex; gap: 5px; justify-content: flex-end;">
                        <button onclick="saveAttendance(${s.id}, 'present', this)" class="attendance-btn ${s.status === 'present' ? 'active' : ''}" data-status="present" title="حضور">ح</button>
                        <button onclick="saveAttendance(${s.id}, 'absent', this)" class="attendance-btn ${s.status === 'absent' ? 'active' : ''}" data-status="absent" title="غياب">غ</button>
                        <button onclick="saveAttendance(${s.id}, 'late', this)" class="attendance-btn ${s.status === 'late' ? 'active' : ''}" data-status="late" title="تأخير">ت</button>
                        <button onclick="saveAttendance(${s.id}, 'excused', this)" class="attendance-btn ${s.status === 'excused' ? 'active' : ''}" data-status="excused" title="بعذر">ع</button>
                    </div>
                </td>
            </tr>
        `;
    });
    html += '</tbody></table>';
    listContainer.innerHTML = html;
}

function saveAttendance(studentId, status, btn) {
    const date = document.getElementById('attendance-filter-date').value;
    const row = btn.closest('tr');

    // UI Update
    row.querySelectorAll('.attendance-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');

    const formData = new FormData();
    formData.append('action', 'sm_save_attendance_ajax');
    formData.append('student_id', studentId);
    formData.append('status', status);
    formData.append('date', date);
    formData.append('nonce', '<?php echo wp_create_nonce("sm_attendance_action"); ?>');

    fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
        if (!res.success) {
            smShowNotification('خطأ في حفظ الحضور: ' + res.data, true);
            btn.classList.remove('active');
        }
    });
}

function setAllAttendance(status) {
    const buttons = document.querySelectorAll(`.attendance-btn[data-status="${status}"]`);
    buttons.forEach(btn => btn.click());
}

function resetClassCode(grade, section, btn) {
    if (!confirm('هل أنت متأكد من إعادة تعيين كود الأمان لهذا الفصل؟')) return;

    btn.disabled = true;
    const formData = new FormData();
    formData.append('action', 'sm_reset_class_code_ajax');
    formData.append('grade', grade);
    formData.append('section', section);
    formData.append('nonce', '<?php echo wp_create_nonce("sm_attendance_action"); ?>');

    fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            const codeId = 'code-' + (grade + '-' + section).toLowerCase().replace(/ /g, '-');
            const el = document.getElementById(codeId);
            if (el) el.innerText = res.data;
            smShowNotification('تم تغيير الكود بنجاح');
        }
        btn.disabled = false;
    });
}

function printAttendance(type, grade = '', section = '') {
    const date = document.getElementById('attendance-filter-date').value;
    let url = '<?php echo admin_url('admin-ajax.php?action=sm_print&print_type=attendance_sheet'); ?>';
    url += '&date=' + date;
    url += '&scope=' + type;
    if (grade) url += '&grade=' + encodeURIComponent(grade);
    if (section) url += '&section=' + encodeURIComponent(section);

    window.open(url, '_blank');
}

function printAbsenceReport(type) {
    const date = document.getElementById('attendance-filter-date').value;
    let url = '<?php echo admin_url('admin-ajax.php?action=sm_print&print_type=absence_report'); ?>';
    url += '&type=' + type + '&date=' + date;
    window.open(url, '_blank');
}

</script>

<style>
#attendance-cards-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 15px;
}

.sm-attendance-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);
}
.attendance-btn {
    width: 32px;
    height: 32px;
    border-radius: 6px;
    border: 1px solid #e2e8f0;
    background: #fff;
    cursor: pointer;
    font-weight: 800;
    font-size: 12px;
    transition: 0.2s;
    color: var(--sm-text-gray);
}
.attendance-btn[data-status="present"]:hover, .attendance-btn[data-status="present"].active { background: #38a169; color: #fff; border-color: #38a169; }
.attendance-btn[data-status="absent"]:hover, .attendance-btn[data-status="absent"].active { background: #e53e3e; color: #fff; border-color: #e53e3e; }
.attendance-btn[data-status="late"]:hover, .attendance-btn[data-status="late"].active { background: #ecc94b; color: #fff; border-color: #ecc94b; }
.attendance-btn[data-status="excused"]:hover, .attendance-btn[data-status="excused"].active { background: #4299e1; color: #fff; border-color: #4299e1; }
</style>
