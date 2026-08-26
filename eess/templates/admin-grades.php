<?php
if (!defined('ABSPATH')) exit;

$user = wp_get_current_user();
$roles = (array) $user->roles;
$is_teacher = in_array('sm_teacher', $roles);
$can_manage = current_user_can('manage_grades') || current_user_can('manage_options') || $is_teacher || in_array('sm_coordinator', $roles) || in_array('sm_hod', $roles) || in_array('sm_activities_supervisor', $roles);

if (!$can_manage) {
    echo '<p>غير مسموح لك بالوصول لهذه الصفحة.</p>';
    return;
}

$students = SM_DB::get_students();
?>

<div class="sm-grades-management" dir="rtl" style="font-family: 'Cairo', sans-serif !important;">

    <!-- Single Main Banner Header (Grades & Results) -->
    <div style="background: #ffffff; padding: 20px 24px; border-radius: 20px; border: 1px solid #e2e8f0; margin-bottom: 18px; box-shadow: 0 4px 18px rgba(0,0,0,0.02); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
        <div style="display: flex; align-items: center; gap: 14px;">
            <div style="width: 48px; height: 48px; background: #fef2f2; border-radius: 14px; display: flex; align-items: center; justify-content: center; color: #881337; border: 1px solid #fecdd3; flex-shrink: 0;">
                <span class="dashicons dashicons-welcome-write-blog" style="font-size: 24px; width: 24px; height: 24px;"></span>
            </div>
            <div>
                <h2 style="margin: 0 0 4px 0; font-size: 20px; font-weight: 800; color: #0f172a;">سجل الدرجات والنتائج الأكاديمية</h2>
                <p style="margin: 0; font-size: 12.5px; color: #64748b; font-weight: 500;">رصد درجات الطلاب الفردية والجماعية، إدارة المواد والمجموعات الدراسية واستخراج التقارير</p>
            </div>
        </div>

        <!-- Primary Header Actions -->
        <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
            <button type="button" onclick="document.getElementById('add-grade-modal').style.display='flex'" class="sm-btn" style="background: #881337; color: #ffffff !important; height: 38px; border-radius: 9999px !important; padding: 0 20px; font-weight: 800; font-size: 12.5px; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
                <span class="dashicons dashicons-plus-alt2" style="font-size: 15px; width: 15px; height: 15px; color: #fff;"></span>
                <span>رصد درجة جديدة</span>
            </button>
        </div>
    </div>

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <div class="sm-tabs-wrapper" style="display: flex; gap: 10px; border-bottom: 2px solid #eee; padding-bottom: 10px; flex: 1;">
            <button class="sm-tab-btn sm-active" onclick="smOpenInternalTab('individual-grading', this)">رصد فردي</button>
            <button class="sm-tab-btn" onclick="smOpenInternalTab('class-grading', this)">رصد جماعي (حسب الصف)</button>
            <?php if (current_user_can('إدارة_النظام')): ?>
                <button class="sm-tab-btn" onclick="smOpenInternalTab('subject-mgmt', this)">إدارة المواد</button>
            <?php endif; ?>
        </div>
    </div>

    <script>
    function eessSubmitImportGrades(e) {
        e.preventDefault();
        const form = document.getElementById('eess-import-grades-form');
        const formData = new FormData(form);
        formData.append('action', 'sm_import_grades_csv');

        jQuery.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(res) {
                if (res.success) {
                    alert(res.data.message || 'تم استيراد الدرجات بنجاح!');
                    location.reload();
                } else {
                    alert(res.data || 'حدث خطأ أثناء الاستيراد.');
                }
            }
        });
    }
    </script>

    <div id="individual-grading" class="sm-internal-tab">
        <div style="background: #fff; padding: 25px; border-radius: 12px; border: 1px solid var(--sm-border-color); margin-bottom: 30px;">
            <div style="display: grid; grid-template-columns: 2fr 1fr 1fr 1fr auto; gap: 15px; align-items: end;">
                <div class="sm-form-group" style="margin-bottom:0;">
                    <label class="sm-label">اختر الطالب:</label>
                    <select id="grade-student-id" class="sm-select">
                        <option value="">-- اختر طالب --</option>
                        <?php foreach ($students as $s): ?>
                            <option value="<?php echo $s->id; ?>"><?php echo esc_html($s->name); ?> (<?php echo $s->class_name; ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="sm-form-group" style="margin-bottom:0;">
                    <label class="sm-label">المادة:</label>
                    <select id="grade-subject" class="sm-select">
                        <option value="">-- اختر المادة --</option>
                        <?php
                        $subjects_all = SM_DB::get_subjects();
                        $unique_subjects = array_unique(array_column($subjects_all, 'name'));
                        foreach ($unique_subjects as $subname) echo '<option value="'.$subname.'">'.$subname.'</option>';
                        ?>
                    </select>
                </div>
                <div class="sm-form-group" style="margin-bottom:0;">
                    <label class="sm-label">الفصل:</label>
                    <select id="grade-term" class="sm-select">
                        <option value="الفصل الأول">الفصل الأول</option>
                        <option value="الفصل الثاني">الفصل الثاني</option>
                        <option value="الفصل الثالث">الفصل الثالث</option>
                    </select>
                </div>
                <div class="sm-form-group" style="margin-bottom:0;">
                    <label class="sm-label">الدرجة:</label>
                    <input type="text" id="grade-val" class="sm-input" placeholder="100/95">
                </div>
                <button onclick="saveStudentGrade()" class="sm-btn" style="height: 45px; background: var(--sm-primary-color);">رصد الدرجة</button>
            </div>
        </div>

        <div id="grades-table-container">
            <div style="padding: 40px; text-align: center; background: #f8fafc; border-radius: 12px; color: var(--sm-text-gray);">يرجى اختيار طالب لعرض درجاته.</div>
        </div>
    </div>

    <div id="class-grading" class="sm-internal-tab" style="display:none;">
        <div style="background: #fff; padding: 25px; border-radius: 12px; border: 1px solid var(--sm-border-color); margin-bottom: 30px;">
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr auto; gap: 15px; align-items: end;">
                <div class="sm-form-group" style="margin-bottom:0;">
                    <label class="sm-label">الصف:</label>
                    <select id="batch-class" class="sm-select" onchange="loadBatchStudents()">
                        <option value="">-- اختر الصف --</option>
                        <?php
                        global $wpdb;
                        $classes = $wpdb->get_results("SELECT DISTINCT class_name FROM {$wpdb->prefix}sm_students ORDER BY class_name ASC");
                        foreach ($classes as $c) echo '<option value="'.$c->class_name.'">'.$c->class_name.'</option>';
                        ?>
                    </select>
                </div>
                <div class="sm-form-group" style="margin-bottom:0;">
                    <label class="sm-label">المادة:</label>
                    <select id="batch-subject" class="sm-select">
                        <option value="">-- اختر المادة --</option>
                        <?php
                        foreach ($unique_subjects as $subname) echo '<option value="'.$subname.'">'.$subname.'</option>';
                        ?>
                    </select>
                </div>
                <div class="sm-form-group" style="margin-bottom:0;">
                    <label class="sm-label">الفصل:</label>
                    <select id="batch-term" class="sm-select">
                        <option value="الفصل الأول">الفصل الأول</option>
                        <option value="الفصل الثاني">الفصل الثاني</option>
                        <option value="الفصل الثالث">الفصل الثالث</option>
                    </select>
                </div>
                <button onclick="saveBatchGrades()" class="sm-btn" style="height: 45px; background: var(--sm-accent-color);">حفظ درجات الصف</button>
            </div>
        </div>
        <div id="batch-students-container"></div>
    </div>

    <?php if (current_user_can('إدارة_النظام')): ?>
    <div id="subject-mgmt" class="sm-internal-tab" style="display:none;">
        <div style="display: grid; grid-template-columns: 300px 1fr; gap: 30px;">
            <div style="background: #f8fafc; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0;">
                <h4 style="margin-top:0;">إضافة مادة جديدة</h4>
                <div class="sm-form-group">
                    <label class="sm-label">اسم المادة:</label>
                    <input type="text" id="new-subject-name" class="sm-input">
                </div>
                <div class="sm-form-group">
                    <label class="sm-label">تطبيق على الصفوف (متعدد):</label>
                    <div style="background: #fff; padding: 10px; border: 1px solid #e2e8f0; border-radius: 8px; max-height: 200px; overflow-y: auto; display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                        <?php for($i=1; $i<=12; $i++): ?>
                            <label style="font-size: 12px; display: flex; align-items: center; gap: 5px; cursor: pointer;">
                                <input type="checkbox" class="new-subject-grade-check" value="<?php echo $i; ?>"> صف <?php echo $i; ?>
                            </label>
                        <?php endfor; ?>
                    </div>
                </div>
                <button onclick="addSubject()" class="sm-btn" style="width:100%;">إضافة المادة</button>
            </div>
            <div id="subjects-list-container">
                <!-- Loaded via JS -->
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
document.getElementById('grade-student-id').addEventListener('change', function() {
    loadStudentGrades(this.value);
});

function loadStudentGrades(studentId) {
    if (!studentId) {
        document.getElementById('grades-table-container').innerHTML = '<div style="padding: 40px; text-align: center; background: #f8fafc; border-radius: 12px; color: var(--sm-text-gray);">يرجى اختيار طالب لعرض درجاته.</div>';
        return;
    }

    const formData = new FormData();
    formData.append('action', 'sm_get_student_grades_ajax');
    formData.append('student_id', studentId);
    formData.append('nonce', '<?php echo wp_create_nonce("sm_grade_action"); ?>');

    fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            renderGradesTable(res.data);
        }
    });
}

function loadBatchStudents() {
    const className = document.getElementById('batch-class').value;
    if (!className) return;

    const container = document.getElementById('batch-students-container');
    container.innerHTML = 'جاري التحميل...';

    // Reuse search logic to get class students
    fetch('<?php echo admin_url('admin-ajax.php'); ?>?action=sm_search_students&query=' + encodeURIComponent(className))
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            let html = '<table class="sm-table"><thead><tr><th>الطالب</th><th>الدرجة</th></tr></thead><tbody>';
            res.data.forEach(s => {
                html += `<tr><td>${s.name}</td><td><input type="text" class="sm-input batch-grade-input" data-student-id="${s.id}" style="width:100px;"></td></tr>`;
            });
            html += '</tbody></table>';
            container.innerHTML = html;
        }
    });
}

function saveBatchGrades() {
    const subject = document.getElementById('batch-subject').value;
    const term = document.getElementById('batch-term').value;
    if (!subject) { alert('يرجى تحديد المادة'); return; }

    const grades = {};
    document.querySelectorAll('.batch-grade-input').forEach(input => {
        grades[input.dataset.studentId] = input.value;
    });

    const formData = new FormData();
    formData.append('action', 'sm_save_class_grades');
    formData.append('subject', subject);
    formData.append('term', term);
    formData.append('grades', JSON.stringify(grades));
    formData.append('nonce', '<?php echo wp_create_nonce("sm_grade_action"); ?>');

    fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            smShowNotification(`تم حفظ ${res.data} درجة بنجاح`);
        }
    });
}

function addSubject() {
    const name = document.getElementById('new-subject-name').value;
    const gradeIds = [];
    document.querySelectorAll('.new-subject-grade-check:checked').forEach(chk => gradeIds.push(chk.value));

    if (!name || gradeIds.length === 0) {
        alert('يرجى إدخال اسم المادة واختيار صف واحد على الأقل');
        return;
    }

    const formData = new FormData();
    formData.append('action', 'sm_add_subject');
    formData.append('name', name);
    gradeIds.forEach(id => formData.append('grade_ids[]', id));
    formData.append('nonce', '<?php echo wp_create_nonce("sm_admin_action"); ?>');

    fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            smShowNotification('تمت إضافة المادة بنجاح');
            loadSubjects();
            document.getElementById('new-subject-name').value = '';
            document.querySelectorAll('.new-subject-grade-check').forEach(chk => chk.checked = false);
        } else {
            smShowNotification('خطأ في الحفظ', true);
        }
    });
}

function loadSubjects() {
    const container = document.getElementById('subjects-list-container');
    if (!container) return;
    fetch('<?php echo admin_url('admin-ajax.php'); ?>?action=sm_get_subjects')
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            let html = '<table class="sm-table"><thead><tr><th>المادة</th><th>الصف</th><th>حذف</th></tr></thead><tbody>';
            res.data.forEach(s => {
                html += `<tr><td>${s.name}</td><td>الصف ${s.grade_id}</td><td><button onclick="deleteSubject(${s.id})" class="sm-btn sm-btn-outline" style="color:red;">×</button></td></tr>`;
            });
            html += '</tbody></table>';
            container.innerHTML = html;
        }
    });
}
document.addEventListener('DOMContentLoaded', loadSubjects);

function renderGradesTable(grades) {
    const container = document.getElementById('grades-table-container');
    if (grades.length === 0) {
        container.innerHTML = '<div style="padding: 40px; text-align: center; background: #f8fafc; border-radius: 12px; color: var(--sm-text-gray);">لا يوجد درجات مسجلة لهذا الطالب.</div>';
        return;
    }

    let html = `
        <div class="sm-table-container">
            <table class="sm-table">
                <thead>
                    <tr>
                        <th>المادة</th>
                        <th>الفصل</th>
                        <th>الدرجة</th>
                        <th>تاريخ الرصد</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
    `;

    grades.forEach(g => {
        html += `
            <tr>
                <td style="font-weight:700;">${g.subject}</td>
                <td>${g.term}</td>
                <td><span class="sm-badge" style="background:var(--sm-bg-light); color:var(--sm-primary-color); font-size:1.1em;">${g.grade_val}</span></td>
                <td>${g.created_at}</td>
                <td>
                    <button onclick="deleteGrade(${g.id})" class="sm-btn sm-btn-outline" style="color:red; padding:5px;"><span class="dashicons dashicons-trash"></span></button>
                </td>
            </tr>
        `;
    });

    html += '</tbody></table></div>';
    container.innerHTML = html;
}

function saveStudentGrade() {
    const studentId = document.getElementById('grade-student-id').value;
    const subject = document.getElementById('grade-subject').value;
    const term = document.getElementById('grade-term').value;
    const gradeVal = document.getElementById('grade-val').value;

    if (!studentId || !subject || !gradeVal) {
        alert('يرجى إكمال كافة الحقول');
        return;
    }

    const formData = new FormData();
    formData.append('action', 'sm_save_grade_ajax');
    formData.append('student_id', studentId);
    formData.append('subject', subject);
    formData.append('term', term);
    formData.append('grade_val', gradeVal);
    formData.append('nonce', '<?php echo wp_create_nonce("sm_grade_action"); ?>');

    fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            smShowNotification('تم رصد الدرجة بنجاح');
            loadStudentGrades(studentId);
            document.getElementById('grade-subject').value = '';
            document.getElementById('grade-val').value = '';
        }
    });
}

function deleteGrade(gradeId) {
    if (!confirm('هل أنت متأكد من حذف هذه الدرجة؟')) return;

    const formData = new FormData();
    formData.append('action', 'sm_delete_grade_ajax');
    formData.append('grade_id', gradeId);
    formData.append('nonce', '<?php echo wp_create_nonce("sm_grade_action"); ?>');

    fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            smShowNotification('تم حذف الدرجة');
            if (document.getElementById('grade-student-id').value) {
                loadStudentGrades(document.getElementById('grade-student-id').value);
            } else {
                location.reload();
            }
        }
    });
}

function saveStudentGradeModal() {
    const studentId = document.getElementById('modal-grade-student-id').value;
    const subject = document.getElementById('modal-grade-subject').value;
    const term = document.getElementById('modal-grade-term').value;
    const gradeVal = document.getElementById('modal-grade-val').value;

    if (!studentId || !subject || !gradeVal) {
        alert('يرجى إكمال كافة الحقول');
        return;
    }

    const formData = new FormData();
    formData.append('action', 'sm_save_grade_ajax');
    formData.append('student_id', studentId);
    formData.append('subject', subject);
    formData.append('term', term);
    formData.append('grade_val', gradeVal);
    formData.append('nonce', '<?php echo wp_create_nonce("sm_grade_action"); ?>');

    fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            smShowNotification('تم رصد الدرجة بنجاح');
            document.getElementById('add-grade-modal').style.display = 'none';
            setTimeout(() => location.reload(), 1000);
        } else {
            alert('فشل في رصد الدرجة: ' + res.data);
        }
    });
}

// Global parsed grades container
let eessParsedGrades = [];

document.getElementById('eess-grades-file-input').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (!file) return;

    const reader = new FileReader();
    reader.onload = function(evt) {
        const text = evt.target.result;
        const lines = text.split('\n');
        if (lines.length < 2) {
            alert('الملف فارغ أو غير صالح.');
            return;
        }

        // Parse CSV columns intelligently
        const headers = lines[0].split(',').map(h => h.trim().replace(/[\r\n"']/g, ''));
        let col_code = -1, col_subject = -1, col_term = -1, col_grade = -1;

        // Auto map columns
        headers.forEach((h, idx) => {
            const h_norm = h.toLowerCase();
            if (h_norm.includes('كود') || h_norm.includes('رقم') || h_norm.includes('code') || h_norm.includes('طالب')) col_code = idx;
            else if (h_norm.includes('مادة') || h_norm.includes('subject')) col_subject = idx;
            else if (h_norm.includes('فصل') || h_norm.includes('term')) col_term = idx;
            else if (h_norm.includes('درجة') || h_norm.includes('grade') || h_norm.includes('val')) col_grade = idx;
        });

        // Fallback default mapping
        if (col_code === -1) col_code = 0;
        if (col_subject === -1) col_subject = 1;
        if (col_term === -1) col_term = 2;
        if (col_grade === -1) col_grade = 3;

        eessParsedGrades = [];
        const tbody = document.querySelector('#eess-grades-preview-table tbody');
        tbody.innerHTML = '';

        for (let i = 1; i < lines.length; i++) {
            const line = lines[i].trim();
            if (!line) continue;

            const cols = line.split(',').map(c => c.trim().replace(/[\r\n"']/g, ''));
            if (cols.length < 2) continue;

            const code = cols[col_code] || '';
            const subject = cols[col_subject] || '';
            const term = cols[col_term] || 'الفصل الأول';
            const grade = cols[col_grade] || '';

            let statusHtml = '<span style="color:green; font-weight:bold;">جاهز للاستيراد</span>';
            let isValid = true;

            if (!code || !subject || !grade) {
                statusHtml = '<span style="color:red; font-weight:bold;">بيانات ناقصة</span>';
                isValid = false;
            }

            if (isValid) {
                eessParsedGrades.push({
                    student_code: code,
                    subject: subject,
                    term: term,
                    grade_val: grade
                });
            }

            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td style="text-align: right; padding-right: 15px; font-weight:bold;">${code}</td>
                <td>${subject}</td>
                <td>${term}</td>
                <td style="font-family:monospace; font-weight:bold; color:var(--sm-primary-color);">${grade}</td>
                <td>${statusHtml}</td>
            `;
            tbody.appendChild(tr);
        }

        document.getElementById('eess-grades-import-preview-section').style.display = 'block';
        if (eessParsedGrades.length > 0) {
            document.getElementById('eess-grades-confirm-import-btn').style.display = 'inline-block';
        } else {
            document.getElementById('eess-grades-confirm-import-btn').style.display = 'none';
        }
    };
    reader.readAsText(file);
});

function eessConfirmGradesImport() {
    if (eessParsedGrades.length === 0) {
        alert('لا توجد نتائج صالحة للاستيراد.');
        return;
    }

    const btn = document.getElementById('eess-grades-confirm-import-btn');
    btn.innerText = 'جاري الاستيراد...';
    btn.disabled = true;

    const formData = new FormData();
    formData.append('action', 'eess_import_grades_ajax');
    formData.append('records', JSON.stringify(eessParsedGrades));
    formData.append('nonce', '<?php echo wp_create_nonce("sm_grade_action"); ?>');

    fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            smShowNotification('تم استيراد ' + res.data.imported + ' نتيجة أكاديمية بنجاح!');
            document.getElementById('eess-grades-import-modal').style.display = 'none';
            setTimeout(() => location.reload(), 1500);
        } else {
            alert('خطأ أثناء الاستيراد: ' + res.data);
            btn.innerText = 'تأكيد واستيراد النتائج';
            btn.disabled = false;
        }
    });
}
</script>

<?php include SM_PLUGIN_DIR . 'templates/partials/grades-modals.php'; ?>
