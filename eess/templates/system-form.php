<?php if (!defined('ABSPATH')) exit; ?>
<div class="sm-form-container" dir="rtl" style="font-family: 'Cairo', sans-serif;">
    <form method="post" id="violation-form">
        <?php wp_nonce_field('sm_record_action', 'sm_nonce'); ?>
        <input type="hidden" name="record_id" id="edit_record_id" value="0">

        <!-- Top Header & Date -->
        <div style="display: flex; justify-content: space-between; align-items: center; background: #f8fafc; padding: 10px 14px; border-radius: 8px; border: 1px solid #e2e8f0; margin-bottom: 12px;">
            <div style="font-size: 13px; font-weight: 800; color: #1e293b; display: flex; align-items: center; gap: 6px;">
                <span class="dashicons dashicons-warning" style="color: #d97706; font-size: 18px;"></span>
                <span>تسجيل وإدارة مخالفات الطلاب السلوكية</span>
            </div>
            <div style="display: flex; align-items: center; gap: 10px;">
                <div style="display: flex; align-items: center; gap: 6px; background: #fff; padding: 3px 8px; border-radius: 6px; border: 1px solid #cbd5e1;">
                    <label style="font-size: 11px; font-weight: 700; color: #475569; margin: 0;">تاريخ المخالفة:</label>
                    <input type="date" name="custom_date" id="violation_custom_date" class="sm-input" value="<?php echo date('Y-m-d'); ?>" required style="height: 26px; font-size: 11px; border: none; padding: 0; background: transparent;">
                </div>
                <button id="start-scanner" type="button" class="sm-btn" style="height: 28px; padding: 0 10px; font-size: 11px; background: #334155; color: white !important; border-radius: 6px; cursor: pointer; display: flex; align-items: center; gap: 4px;">
                    <span class="dashicons dashicons-barcode" style="font-size: 14px; width: 14px; height: 14px; margin: 0;"></span>
                    <span>الماسح الضوئي</span>
                </button>
            </div>
        </div>

        <div id="reader" style="width: 100%; max-width: 350px; margin: 0 auto 12px auto; display: none; border-radius: 8px; overflow: hidden; border: 2px solid var(--sm-primary-color);"></div>

        <!-- Section 1: Student Selection & Live Confirmation -->
        <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; margin-bottom: 12px;">
            <div class="sm-form-group" style="position:relative; margin-bottom: 8px;">
                <label class="sm-label" style="font-weight: 700; font-size: 11px; margin-bottom: 4px; display: block;">البحث عن الطالب (يمكن اختيار أكثر من طالب للمخالفات الجماعية): <span style="color:#ef4444;">*</span></label>
                <input type="text" id="student_unified_search" class="sm-input" placeholder="اكتب اسم الطالب أو الكود للبحث المباشر..." autocomplete="off" style="height: 34px; font-size: 12px;">
                <div id="search_results_dropdown" style="display:none; position:absolute; top:100%; left:0; right:0; background:white; border:1px solid #cbd5e1; border-radius:0 0 8px 8px; z-index:1000; box-shadow:0 10px 15px -3px rgba(0,0,0,0.1); max-height:200px; overflow-y:auto;">
                    <!-- Results via AJAX -->
                </div>
                <div id="selected_students_container" style="display:flex; flex-wrap:wrap; gap:6px; margin-top:6px;">
                    <!-- Selected students tags -->
                </div>
                <input type="hidden" name="student_ids" id="selected_student_ids" required>
                <span class="eess-field-error" id="err_student_ids" style="display:none; color:#dc2626; font-size:11px; font-weight:bold; margin-top:3px;">يرجى اختيار طالب واحد على الأقل.</span>
            </div>

            <!-- Student Intelligence Confirmation Card -->
            <div id="student-intelligence-panel" style="display:none; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px; padding: 10px; border-right: 4px solid var(--sm-primary-color); font-size: 11px;">
                <div id="intel-content" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 8px; align-items: center;">
                    <!-- Content loaded via AJAX -->
                </div>
                <div id="intel-history" style="margin-top: 6px; font-size: 10px; color: #64748b; border-top: 1px dashed #cbd5e1; padding-top: 4px;">
                    <!-- Latest violations -->
                </div>
            </div>
        </div>

        <!-- Section 2: Violation Classification (Grid Layout) -->
        <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; margin-bottom: 12px;">
            <h4 style="margin: 0 0 10px 0; font-size: 12px; font-weight: 800; color: #1e293b; border-bottom: 1px solid #f1f5f9; padding-bottom: 4px;">تصنيف وتبويب المخالفة السلوكية</h4>

            <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 10px; margin-bottom: 10px;">
                <div class="sm-form-group" style="margin-bottom:0;">
                    <label class="sm-label" style="font-weight: 700; font-size: 11px;">درجة المخالفة (المستوى) <span style="color:#ef4444;">*</span></label>
                    <select name="degree" id="violation_degree" class="sm-select" onchange="updateHierarchicalViolations()" required style="height: 34px; font-size: 12px;">
                        <option value="">-- اختر الدرجة --</option>
                        <option value="1">المستوى الأول (بسيطة)</option>
                        <option value="2">المستوى الثاني (متوسطة)</option>
                        <option value="3">المستوى الثالث (جسيمة)</option>
                        <option value="4">المستوى الرابع (شديدة الخطورة)</option>
                    </select>
                    <span class="eess-field-error" id="err_violation_degree" style="display:none; color:#dc2626; font-size:11px; font-weight:bold;">يرجى تحديد درجة المخالفة.</span>
                </div>

                <div class="sm-form-group" style="margin-bottom:0;">
                    <label class="sm-label" style="font-weight: 700; font-size: 11px;">البند القانوني / نوع المخالفة <span style="color:#ef4444;">*</span></label>
                    <select name="violation_code" id="violation_code_select" class="sm-select" onchange="onViolationSelected()" required disabled style="height: 34px; font-size: 12px;">
                        <option value="">-- اختر البند القانوني --</option>
                    </select>
                    <span class="eess-field-error" id="err_violation_code" style="display:none; color:#dc2626; font-size:11px; font-weight:bold;">يرجى اختيار البند القانوني للمخالفة.</span>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px;">
                <div class="sm-form-group" style="margin-bottom:0;">
                    <label class="sm-label" style="font-weight: 700; font-size: 11px;">تصنيف موقع الموقف:</label>
                    <select name="classification" id="violation_classification" class="sm-select" style="height: 34px; font-size: 12px;">
                        <option value="general">عام</option>
                        <option value="inside_class">داخل الفصل الدراسي</option>
                        <option value="yard">في الساحة / الطابور</option>
                        <option value="labs">في المختبرات والمرافق</option>
                        <option value="bus">في الحافلة المدرسية</option>
                    </select>
                </div>

                <div class="sm-form-group" style="margin-bottom:0;">
                    <label class="sm-label" style="font-weight: 700; font-size: 11px;">النقاط المستحقة:</label>
                    <input type="number" name="points" id="violation_points" class="sm-input" value="0" style="height: 34px; font-size: 12px;">
                </div>

                <div class="sm-form-group" style="margin-bottom:0;">
                    <label class="sm-label" style="font-weight: 700; font-size: 11px;">الحدة التقديرية:</label>
                    <select name="severity" id="violation_severity" class="sm-select" style="height: 34px; font-size: 12px;">
                        <option value="low">منخفضة</option>
                        <option value="medium">متوسطة</option>
                        <option value="high">عالية</option>
                    </select>
                </div>

                <input type="hidden" name="type" id="hidden_violation_type">
            </div>
        </div>

        <!-- Section 3: Disciplinary Action & Incident Details -->
        <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; margin-bottom: 12px;">
            <h4 style="margin: 0 0 10px 0; font-size: 12px; font-weight: 800; color: #1e293b; border-bottom: 1px solid #f1f5f9; padding-bottom: 4px;">الإجراء المتخذ وتفاصيل الموقف</h4>

            <div class="sm-form-group" style="margin-bottom: 10px;">
                <label class="sm-label" style="font-weight: 700; font-size: 11px;">الإجراء التعديلي / التربوي المتخذ <span style="color:#ef4444;">*</span></label>
                <select name="action_taken" id="action_taken" class="sm-select" required style="height: 34px; font-size: 12px;">
                    <option value="">-- اختر الإجراء المتخذ --</option>
                    <?php foreach (SM_Settings::get_disciplinary_actions() as $level => $act): ?>
                        <option value="<?php echo esc_attr($act); ?>" data-level="<?php echo $level; ?>"><?php echo $level . '. ' . esc_html($act); ?></option>
                    <?php endforeach; ?>
                </select>
                <span class="eess-field-error" id="err_action_taken" style="display:none; color:#dc2626; font-size:11px; font-weight:bold;">يرجى اختيار الإجراء المتخذ.</span>

                <div id="action-progression-warning" style="display:none; margin-top:5px; padding:6px 10px; background:#fffbeb; border:1px solid #fef3c7; border-radius:6px; font-size:11px; color:#b45309;">
                    <span class="dashicons dashicons-info" style="font-size:13px; width:13px; height:13px; margin-left:4px; vertical-align:middle;"></span>
                    تنبيه: الطالب تلقى إجراءات سابقة. تم اقتراح الإجراء الموصى به تلقائياً.
                </div>
            </div>

            <div class="sm-form-group" style="margin-bottom: 10px;">
                <label class="sm-label" style="font-weight: 700; font-size: 11px;">شرح وتفاصيل الموقف السلوكي <span style="color:#ef4444;">*</span></label>
                <textarea name="details" id="violation_details" class="sm-input" placeholder="اشرح تفاصيل الموقف السلوكي بدقة والأشخاص المتواجدين..." style="height: 55px; font-size: 12px; padding: 6px;" required></textarea>
                <span class="eess-field-error" id="err_details" style="display:none; color:#dc2626; font-size:11px; font-weight:bold;">يرجى إدخال تفاصيل الموقف السلوكي.</span>
            </div>

            <div class="sm-form-group" style="margin-bottom: 0;">
                <label class="sm-label" style="font-weight: 700; font-size: 11px;">إرفاق مستند أو دليل مؤيد (اختياري):</label>
                <input type="text" name="reward_penalty" id="violation_evidence_link" class="sm-input" placeholder="أدخل رابط مستند أو ملاحظة إضافية..." style="height: 32px; font-size: 11px;">
            </div>
        </div>

        <!-- Section 4: Form Controls & Action Buttons -->
        <div style="display: flex; gap: 10px; justify-content: flex-end; align-items: center; border-top: 1px solid #e2e8f0; padding-top: 10px;">
            <button type="button" onclick="smCloseViolationModal()" class="sm-btn sm-btn-outline" style="height: 36px; padding: 0 16px; font-size: 12px;">إلغاء</button>
            <button type="submit" id="submit-btn" class="sm-btn" style="height: 36px; padding: 0 22px; font-weight: 800; font-size: 12px; background: var(--sm-primary-color); color: white !important;">حفظ وتسجيل المخالفة الآن</button>
        </div>
    </form>
</div>

<script>
const hViolations = <?php echo json_encode(SM_Settings::get_hierarchical_violations()); ?>;

function updateHierarchicalViolations() {
    const degree = document.getElementById('violation_degree').value;
    const select = document.getElementById('violation_code_select');

    select.innerHTML = '<option value="">-- اختر البند --</option>';
    if (!degree || !hViolations[degree]) {
        select.disabled = true;
        return;
    }

    Object.keys(hViolations[degree]).forEach(code => {
        const v = hViolations[degree][code];
        const opt = document.createElement('option');
        opt.value = code;
        opt.innerText = code + ' - ' + v.name;
        select.appendChild(opt);
    });
    select.disabled = false;
}

function onViolationSelected() {
    const degree = document.getElementById('violation_degree').value;
    const code = document.getElementById('violation_code_select').value;

    if (!degree || !code || !hViolations[degree][code]) return;

    const v = hViolations[degree][code];
    document.getElementById('violation_points').value = v.points;

    const actionSelect = document.getElementById('action_taken');
    if (actionSelect && v.action) {
        for (let i = 0; i < actionSelect.options.length; i++) {
            if (actionSelect.options[i].value === v.action) {
                actionSelect.selectedIndex = i;
                break;
            }
        }
    }

    document.getElementById('hidden_violation_type').value = v.name;

    const sev = document.getElementById('violation_severity');
    if (degree == 1) sev.value = 'low';
    else if (degree == 2) sev.value = 'medium';
    else sev.value = 'high';
}

(function() {

let searchTimer;
document.addEventListener('click', function(e) {
    const searchInput = document.getElementById('student_unified_search');
    if (searchInput && !searchInput.contains(e.target)) {
        const dropdown = document.getElementById('search_results_dropdown');
        if (dropdown) dropdown.style.display = 'none';
    }
});

const searchInput = document.getElementById('student_unified_search');
if (searchInput) {
    searchInput.addEventListener('input', function() {
        const query = this.value;
        clearTimeout(searchTimer);
        if (query.length < 2) {
            document.getElementById('search_results_dropdown').style.display = 'none';
            return;
        }

        searchTimer = setTimeout(() => {
            const formData = new FormData();
            formData.append('action', 'sm_search_students');
            formData.append('query', query);

            fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    const results = res.data;
                    const dropdown = document.getElementById('search_results_dropdown');
                    dropdown.innerHTML = '';
                    if (results.length === 0) {
                        dropdown.innerHTML = '<div style="padding:10px; color:#666; text-align:center;">لم يتم العثور على نتائج.</div>';
                    } else {
                        results.forEach(s => {
                            const div = document.createElement('div');
                            div.className = 'sm-search-result-item';
                            div.style = "padding:10px 12px; border-bottom:1px solid #eee; cursor:pointer; display:flex; align-items:center; gap:10px; transition: background 0.2s;";
                            div.onmouseover = () => div.style.background = '#f8fafc';
                            div.onmouseout = () => div.style.background = '#fff';
                            div.innerHTML = `
                                ${s.photo_url ? `<img src="${s.photo_url}" style="width:28px; height:28px; border-radius:50%; object-fit:cover;">` : '<span class="dashicons dashicons-admin-users"></span>'}
                                <div>
                                    <div style="font-weight:700; font-size:12px;">${s.name}</div>
                                    <div style="font-size:10px; color:#666;">كود: ${s.student_code} | فصل: ${s.class_name} ${s.section || ''}</div>
                                </div>
                            `;
                            div.onclick = () => selectStudent(s);
                            dropdown.appendChild(div);
                        });
                    }
                    dropdown.style.display = 'block';
                }
            });
        }, 300);
    });
}

let selectedStudents = [];

window.selectStudent = function(s) {
    if (selectedStudents.find(x => x.id === s.id)) return;
    
    selectedStudents.push(s);
    renderSelectedStudents();
    document.getElementById('student_unified_search').value = '';
    document.getElementById('search_results_dropdown').style.display = 'none';
    
    if (selectedStudents.length === 1) {
        fetchIntelligence(s.id);
    } else {
        document.getElementById('student-intelligence-panel').style.display = 'none';
    }
};

function renderSelectedStudents() {
    const container = document.getElementById('selected_students_container');
    container.innerHTML = '';
    const ids = [];
    
    selectedStudents.forEach(s => {
        ids.push(s.id);
        const tag = document.createElement('div');
        tag.style = "background:#f0f7ff; padding:4px 10px; border-radius:20px; border:1px solid #c3dafe; display:flex; align-items:center; gap:6px; font-size:12px; font-weight:600; color:var(--sm-primary-color);";
        tag.innerHTML = `
            <span>${s.name} (${s.class_name || ''})</span>
            <span onclick="removeStudent(${s.id})" style="cursor:pointer; color:#e53e3e; font-weight:bold;">✖</span>
        `;
        container.appendChild(tag);
    });
    
    document.getElementById('selected_student_ids').value = ids.join(',');
    if (ids.length > 0) {
        document.getElementById('err_student_ids').style.display = 'none';
    }
}

window.removeStudent = function(id) {
    selectedStudents = selectedStudents.filter(x => x.id !== id);
    renderSelectedStudents();
    if (selectedStudents.length === 1) fetchIntelligence(selectedStudents[0].id);
    else document.getElementById('student-intelligence-panel').style.display = 'none';
};

window.clearStudentSelection = function() {
    selectedStudents = [];
    renderSelectedStudents();
    document.getElementById('student-intelligence-panel').style.display = 'none';
};

const scannerBtn = document.getElementById('start-scanner');
if (scannerBtn) {
    scannerBtn.addEventListener('click', function() {
        const reader = document.getElementById('reader');
        reader.style.display = 'block';
        const html5QrCode = new Html5Qrcode("reader");
        html5QrCode.start({ facingMode: "environment" }, { fps: 15, qrbox: 250 }, onScanSuccess);

        function onScanSuccess(decodedText) {
            html5QrCode.stop().then(() => {
                reader.style.display = 'none';

                const formData = new FormData();
                formData.append('action', 'sm_get_student');
                formData.append('code', decodedText);

                fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        selectStudent(res.data);
                    } else {
                        alert('عذراً، كود غير معروف: ' + decodedText);
                    }
                });
            });
        }
    });
}

function fetchIntelligence(studentId) {
    if (!studentId) {
        document.getElementById('student-intelligence-panel').style.display = 'none';
        return;
    }

    const formData = new FormData();
    formData.append('action', 'sm_get_student_intelligence');
    formData.append('student_id', studentId);

    fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            const data = res.data;
            document.getElementById('student-intelligence-panel').style.display = 'block';
            
            let photoHtml = data.photo_url ? `<img src="${data.photo_url}" style="width:40px; height:40px; border-radius:50%; object-fit:cover; border:2px solid var(--sm-primary-color);">` : '<span class="dashicons dashicons-admin-users" style="font-size:28px; width:28px; height:28px;"></span>';

            let intelHtml = `
                <div style="display:flex; align-items:center; gap:10px;">
                    ${photoHtml}
                    <div>
                        <strong style="font-size:12px; color:#0f172a;">${selectedStudents[0].name}</strong>
                        <div style="font-size:10px; color:#64748b;">كود: ${selectedStudents[0].student_code || ''} | الصف: ${selectedStudents[0].class_name || ''} ${selectedStudents[0].section || ''}</div>
                    </div>
                </div>
                <div><strong>إجمالي المخالفات:</strong> <span style="color:#dc2626; font-weight:bold;">${data.stats.total}</span></div>
                <div><strong>الأكثر تكراراً:</strong> <span>${data.labels[data.stats.frequent_type] || 'لا يوجد'}</span></div>
                <div><strong>آخر إجراء:</strong> <span>${data.stats.last_action || 'لا يوجد'}</span></div>
            `;
            document.getElementById('intel-content').innerHTML = intelHtml;

            let historyHtml = '<strong>آخر الملاحظات السابقة:</strong> ';
            if (data.recent.length === 0) historyHtml += 'لا يوجد سجل سابق.';
            data.recent.forEach(r => {
                historyHtml += `<span style="margin-left:10px;">• ${r.created_at.split(' ')[0]}: ${data.labels[r.type]}</span>`;
            });
            document.getElementById('intel-history').innerHTML = historyHtml;

            const actionSelect = document.getElementById('action_taken');
            const warningBox = document.getElementById('action-progression-warning');

            if (actionSelect) {
                const nextIndex = data.last_action_index + 1;

                for (let i = 0; i < actionSelect.options.length; i++) {
                    const opt = actionSelect.options[i];
                    const level = parseInt(opt.getAttribute('data-level') || 0);

                    if (level > 0) {
                        opt.disabled = false;
                        opt.text = opt.text.replace('(سابق) ', '').replace('(تخطي) ', '');

                        if (level === nextIndex) {
                            opt.text = '⭐ ' + opt.text + ' (مقترح)';
                        }
                    }
                }

                if (nextIndex <= 8) {
                    for (let i = 0; i < actionSelect.options.length; i++) {
                        if (parseInt(actionSelect.options[i].getAttribute('data-level')) === nextIndex) {
                            actionSelect.selectedIndex = i;
                            break;
                        }
                    }
                }

                if (data.last_action_index > 0) warningBox.style.display = 'block';
                else warningBox.style.display = 'none';
            }
        }
    });
}

// Handle Form Submission via AJAX with Real-time Validation
const vForm = document.getElementById('violation-form');
if (vForm) {
    vForm.addEventListener('submit', function(e) {
        e.preventDefault();

        // Validation checks
        let hasErrors = false;
        document.querySelectorAll('.eess-field-error').forEach(el => el.style.display = 'none');

        const studentIds = document.getElementById('selected_student_ids').value;
        const degree = document.getElementById('violation_degree').value;
        const code = document.getElementById('violation_code_select').value;
        const action = document.getElementById('action_taken').value;
        const details = document.getElementById('violation_details').value.trim();

        if (!studentIds) {
            document.getElementById('err_student_ids').style.display = 'block';
            hasErrors = true;
        }
        if (!degree) {
            document.getElementById('err_violation_degree').style.display = 'block';
            hasErrors = true;
        }
        if (!code) {
            document.getElementById('err_violation_code').style.display = 'block';
            hasErrors = true;
        }
        if (!action) {
            document.getElementById('err_action_taken').style.display = 'block';
            hasErrors = true;
        }
        if (!details) {
            document.getElementById('err_details').style.display = 'block';
            hasErrors = true;
        }

        if (hasErrors) {
            return;
        }

        const btn = document.getElementById('submit-btn');
        btn.innerText = 'جاري الحفظ...';
        btn.disabled = true;

        const formData = new FormData(this);
        formData.append('action', 'sm_save_record_ajax');

        fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                alert('تم تسجيل المخالفة بنجاح.');
                if (typeof smCloseViolationModal === 'function') {
                    smCloseViolationModal();
                } else if (document.getElementById('sm-global-violation-modal')) {
                    document.getElementById('sm-global-violation-modal').style.display = 'none';
                }
                location.reload();
            } else {
                alert('خطأ: ' + (res.data || 'فشل في حفظ السجل'));
                btn.innerText = 'حفظ وتسجيل المخالفة الآن';
                btn.disabled = false;
            }
        });
    });
}
})();
</script>
