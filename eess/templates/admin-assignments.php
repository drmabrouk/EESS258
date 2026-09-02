<?php if (!defined('ABSPATH')) exit; ?>
<div class="sm-assignments-container" dir="rtl" style="font-family: 'Cairo', sans-serif;">

    <!-- Single Main Banner Header (Matching Teacher Term & Annual Plans) -->
    <div style="background: #ffffff; padding: 20px 24px; border-radius: 20px; border: 1px solid #e2e8f0; margin-bottom: 18px; box-shadow: 0 4px 18px rgba(0,0,0,0.02); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
        <div style="display: flex; align-items: center; gap: 14px;">
            <div style="width: 48px; height: 48px; background: #fef2f2; border-radius: 14px; display: flex; align-items: center; justify-content: center; color: #881337; border: 1px solid #fecdd3; flex-shrink: 0;">
                <span class="dashicons dashicons-edit" style="font-size: 24px; width: 24px; height: 24px;"></span>
            </div>
            <div>
                <h2 style="margin: 0 0 4px 0; font-size: 20px; font-weight: 800; color: #0f172a;">الواجبات المدرسية</h2>
                <p style="margin: 0; font-size: 12.5px; color: #64748b; font-weight: 500;">إنشاء وتوزيع ومتابعة الواجبات المدرسية والمهام المنزلية المقررة على الطلاب لمتابعة الأداء الأكاديمي</p>
            </div>
        </div>

        <?php if ($is_teacher): ?>
        <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
            <button type="button" onclick="document.getElementById('add-assignment-modal').style.display='flex'" class="sm-btn" style="background: #881337; color: #ffffff !important; height: 38px; border-radius: 9999px !important; padding: 0 20px; font-weight: 800; font-size: 12.5px; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
                <span class="dashicons dashicons-plus-alt2" style="font-size: 15px; width: 15px; height: 15px; color: #fff;"></span>
                <span>إضافة واجب جديد</span>
            </button>
        </div>
        <?php endif; ?>
    </div>

    <!-- Homework Multi-Filter Search Card -->
    <div style="background: #ffffff; padding: 18px 22px; border-radius: 16px; border: 1px solid #cbd5e1; box-shadow: 0 2px 8px rgba(0,0,0,0.02); margin-bottom: 20px;">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 12px; align-items: end;">
            <div>
                <label style="font-size: 11.5px; font-weight: 700; color: #475569; margin-bottom: 4px; display: block;">البحث المباشر</label>
                <input type="text" id="eess-homework-search" onkeyup="eessFilterHomework()" class="sm-input" placeholder="عنوان الواجب، المدرس، المادة، الطالب..." style="height: 38px; border-radius: 9999px !important; border: 1px solid #cbd5e1; width: 100%; font-size: 12.5px; padding: 0 14px;">
            </div>

            <div>
                <label style="font-size: 11.5px; font-weight: 700; color: #475569; margin-bottom: 4px; display: block;">المادة الدراسية</label>
                <select id="eess-hw-filter-subject" onchange="eessFilterHomework()" class="sm-input" style="height: 38px; border-radius: 9999px !important; border: 1px solid #cbd5e1; width: 100%; font-size: 12.5px; padding: 0 12px;">
                    <option value="">كافة المواد</option>
                    <?php
                    $all_subjects = SM_DB::get_subjects() ?: array();
                    $unique_subjs = array_unique(array_filter(array_map(function($s){ return is_object($s) ? $s->name : (is_array($s) ? ($s['name'] ?? '') : (string)$s); }, (array)$all_subjects)));
                    foreach ($unique_subjs as $sub_item) {
                        echo '<option value="' . esc_attr($sub_item) . '">' . esc_html($sub_item) . '</option>';
                    }
                    ?>
                </select>
            </div>

            <div>
                <label style="font-size: 11.5px; font-weight: 700; color: #475569; margin-bottom: 4px; display: block;">التاريخ</label>
                <input type="date" id="eess-hw-filter-date" onchange="eessFilterHomework()" class="sm-input" style="height: 38px; border-radius: 9999px !important; border: 1px solid #cbd5e1; width: 100%; font-size: 12px; padding: 0 10px;">
            </div>

            <div style="display: flex; align-items: flex-end; gap: 8px;">
                <button type="button" onclick="eessFilterHomework()" class="sm-btn" style="height: 38px; font-size: 12px; padding: 0 18px; background: #881337; color: white !important; border-radius: 9999px !important; font-weight: 800; border: none; cursor: pointer; width: 100%;">تصفية النتائج</button>
                <button type="button" onclick="eessResetHomeworkFilter()" class="sm-btn sm-btn-outline" style="height: 38px; font-size: 12px; padding: 0 14px; border-radius: 9999px !important; border: 1px solid #cbd5e1; color: #475569; font-weight: 700; cursor: pointer;">إعادة ضبط</button>
            </div>
        </div>
    </div>

    <div class="sm-tabs-wrapper" style="display: flex; gap: 12px; margin-bottom: 20px; padding-bottom: 0;">
        <button class="sm-tab-btn sm-active" onclick="smOpenInternalTab('received-assignments', this)" style="padding: 10px 20px; font-weight: 800; font-size: 13px; border: none; background: transparent; color: #881337; border-bottom: 3px solid #881337; margin-bottom: -2px; cursor: pointer; transition: all 0.2s;">الواجبات المستلمة</button>
        <button class="sm-tab-btn" onclick="smOpenInternalTab('sent-assignments', this)" style="padding: 10px 20px; font-weight: 800; font-size: 13px; border: none; background: transparent; color: #64748b; border-bottom: 3px solid transparent; margin-bottom: -2px; cursor: pointer; transition: all 0.2s;">الواجبات المرسلة</button>
    </div>

    <script>
    function smOpenInternalTab(tabId, btn) {
        document.querySelectorAll('.sm-internal-tab').forEach(el => el.style.display = 'none');
        document.getElementById(tabId).style.display = 'block';
        btn.parentElement.querySelectorAll('.sm-tab-btn').forEach(b => {
            b.style.color = '#64748b';
            b.style.borderBottomColor = 'transparent';
            b.classList.remove('sm-active');
        });
        btn.style.color = '#881337';
        btn.style.borderBottomColor = '#881337';
        btn.classList.add('sm-active');
    }
    </script>

    <!-- TAB 1: RECEIVED ASSIGNMENTS -->
    <div id="received-assignments" class="sm-internal-tab">
        <div class="sm-table-container" style="box-shadow: none !important; border-radius: 0 !important; border: none !important; background: transparent !important;">
            <table class="sm-table">
                <thead>
                    <tr>
                        <th>التاريخ</th>
                        <th>من</th>
                        <th>العنوان والمادة</th>
                        <th>المرفقات</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $received = SM_DB::get_assignments($user->ID, 'assignment');
                    if (empty($received)): ?>
                        <tr><td colspan="5" style="text-align: center; padding: 35px; color: #94a3b8;">لا توجد أي واجبات مستلمة حتى الآن.</td></tr>
                    <?php else: foreach($received as $a): ?>
                        <tr>
                            <td style="font-size: 12px; font-weight: 600;"><?php echo date('Y-m-d', strtotime($a->created_at)); ?></td>
                            <td style="font-weight: 700; color: #334155;"><?php echo esc_html($a->sender_name); ?></td>
                            <td style="font-weight: 700; color: #0f172a;"><?php echo esc_html($a->title); ?></td>
                            <td>
                                <?php if ($a->file_url): ?>
                                    <a href="<?php echo esc_url($a->file_url); ?>" target="_blank" class="sm-btn sm-btn-outline" style="font-size: 11px; padding: 4px 10px; border-radius: 6px;">📄 فتح المستند</a>
                                <?php else: ?>
                                    <span style="color: #cbd5e1;">---</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button onclick='viewAssignment(<?php echo json_encode($a); ?>)' class="sm-btn sm-btn-outline" style="font-size: 11px; padding: 4px 12px; border-radius: 6px;">عرض التفاصيل</button>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- TAB 2: SENT ASSIGNMENTS -->
    <div id="sent-assignments" class="sm-internal-tab" style="display: none;">
        <div class="sm-table-container" style="box-shadow: none !important; border-radius: 0 !important; border: none !important; background: transparent !important;">
            <table class="sm-table">
                <thead>
                    <tr>
                        <th>التاريخ</th>
                        <th>إلى (المستلم)</th>
                        <th>العنوان والمادة</th>
                        <th>المرفقات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $sent = SM_DB::get_sent_assignments($user->ID);
                    if (empty($sent)): ?>
                        <tr><td colspan="4" style="text-align: center; padding: 35px; color: #94a3b8;">لم تقم بإرسال أي واجبات مسجلة بعد.</td></tr>
                    <?php else: foreach($sent as $a): ?>
                        <tr>
                            <td style="font-size: 12px; font-weight: 600;"><?php echo date('Y-m-d', strtotime($a->created_at)); ?></td>
                            <td style="font-weight: 700; color: #334155;"><?php echo esc_html($a->receiver_name); ?></td>
                            <td style="font-weight: 700; color: #0f172a;"><?php echo esc_html($a->title); ?></td>
                            <td>
                                <?php if ($a->file_url): ?>
                                    <a href="<?php echo esc_url($a->file_url); ?>" target="_blank" class="sm-btn sm-btn-outline" style="font-size: 11px; padding: 4px 10px; border-radius: 6px;">📄 فتح المستند</a>
                                <?php else: ?>
                                    <span style="color: #cbd5e1;">---</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- UPGRADED MULTI-STUDENT HOMEWORK MODAL -->
<div id="add-assignment-modal" class="sm-modal-overlay" style="display: none; align-items: center; justify-content: center; z-index: 99999; background: rgba(15,23,42,0.7); backdrop-filter: blur(4px);">
    <div class="sm-modal-content" style="max-width: 680px; width: 100%; border-radius: 12px; padding: 20px; font-family: 'Cairo', sans-serif;">
        <div class="sm-modal-header" style="border-bottom: 1px solid #f1f5f9; padding-bottom: 12px; margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0; font-size: 15px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 8px;">
                <span class="dashicons dashicons-clipboard" style="color: var(--sm-primary-color);"></span>
                <span>إضافة وإسناد واجب مدرسي جديد</span>
            </h3>
            <button type="button" class="sm-modal-close" onclick="document.getElementById('add-assignment-modal').style.display='none'" style="background: none; border: none; font-size: 22px; cursor: pointer;">&times;</button>
        </div>

        <form id="add-assignment-form">
            <?php wp_nonce_field('sm_assignment_action', 'sm_nonce'); ?>

            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 12px; margin-bottom: 12px;">
                <div class="sm-form-group" style="margin-bottom:0;">
                    <label class="sm-label" style="font-weight: 700; font-size: 11px;">عنوان الواجب المدرسي <span style="color:#ef4444;">*</span></label>
                    <input type="text" name="title" class="sm-input" placeholder="مثال: واجب الدرس الأول - حل تدريبات الصفحة 24" required style="height: 36px; font-size: 12px;">
                </div>
                <div class="sm-form-group" style="margin-bottom:0;">
                    <label class="sm-label" style="font-weight: 700; font-size: 11px;">تاريخ التسليم المتوقع</label>
                    <input type="date" name="due_date" class="sm-input" value="<?php echo date('Y-m-d', strtotime('+2 days')); ?>" style="height: 36px; font-size: 12px;">
                </div>
            </div>

            <!-- Student Search Engine & Multi-Student Selection -->
            <div class="sm-form-group" style="position: relative; margin-bottom: 12px; background: #f8fafc; padding: 12px; border-radius: 8px; border: 1px solid #e2e8f0;">
                <label class="sm-label" style="font-weight: 700; font-size: 11px; margin-bottom: 5px; display: block;">تحديد الطلاب المستهدفين بالواجب (محرك البحث المباشر): <span style="color:#ef4444;">*</span></label>
                <input type="text" id="hw_student_search_input" class="sm-input" placeholder="ابحث باسم الطالب أو رقم الكود لإضافته..." autocomplete="off" style="height: 36px; font-size: 12px;">
                <div id="hw_search_dropdown" style="display:none; position:absolute; top:100%; left:12px; right:12px; background:white; border:1px solid #cbd5e1; border-radius:0 0 8px 8px; z-index:1000; box-shadow:0 10px 15px -3px rgba(0,0,0,0.1); max-height:200px; overflow-y:auto;">
                    <!-- Dropdown items via AJAX -->
                </div>
                <div id="hw_selected_students_container" style="display:flex; flex-wrap:wrap; gap:6px; margin-top:8px;">
                    <!-- Selected student tag badges -->
                </div>
                <input type="hidden" name="receiver_ids" id="hw_receiver_ids" required>
                <span class="eess-field-error" id="err_hw_receivers" style="display:none; color:#dc2626; font-size:10px; font-weight:bold; margin-top:3px;">يرجى اختيار طالب واحد على الأقل لإرسال الواجب.</span>
            </div>

            <div class="sm-form-group" style="margin-bottom: 12px;">
                <label class="sm-label" style="font-weight: 700; font-size: 11px;">شرح وتفاصيل الواجب والتعليمات:</label>
                <textarea name="description" class="sm-input" placeholder="اكتب التعليمات والتوجيهات المطلوبة من الطلاب بالتفصيل..." style="height: 70px; font-size: 12px; padding: 8px;"></textarea>
            </div>

            <div class="sm-form-group" style="margin-bottom: 15px;">
                <label class="sm-label" style="font-weight: 700; font-size: 11px;">إرفاق مستند أو ورقة عمل (PDF / صورة / رابط):</label>
                <div style="display:flex; gap:8px;">
                    <input type="text" name="file_url" id="assignment_file_url" class="sm-input" placeholder="أدخل رابط المستند أو اضغط رفع مرفق..." style="height: 36px; font-size: 12px; flex:1;">
                    <button type="button" onclick="smOpenMediaUploader('assignment_file_url')" class="sm-btn" style="width:auto; font-size:12px; font-weight:700; background:#334155; color: white !important;">رفع ملف</button>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 8px; border-top: 1px solid #f1f5f9; padding-top: 12px;">
                <button type="button" onclick="document.getElementById('add-assignment-modal').style.display='none'" class="sm-btn sm-btn-outline" style="height: 36px; padding: 0 16px; font-size: 12px;">إلغاء</button>
                <button type="submit" id="hw_submit_btn" class="sm-btn" style="height: 36px; padding: 0 24px; font-weight: 800; font-size: 12px; background: var(--sm-primary-color); color: white !important;">إرسال الواجب الآن ➔</button>
            </div>
        </form>
    </div>
</div>

<script>
let hwSelectedStudents = [];

(function() {
    let hwSearchTimer;
    const searchInput = document.getElementById('hw_student_search_input');

    document.addEventListener('click', function(e) {
        if (searchInput && !searchInput.contains(e.target)) {
            const dropdown = document.getElementById('hw_search_dropdown');
            if (dropdown) dropdown.style.display = 'none';
        }
    });

    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const query = this.value.trim();
            clearTimeout(hwSearchTimer);
            if (query.length < 2) {
                document.getElementById('hw_search_dropdown').style.display = 'none';
                return;
            }

            hwSearchTimer = setTimeout(() => {
                const formData = new FormData();
                formData.append('action', 'sm_search_students');
                formData.append('query', query);

                fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        const results = res.data;
                        const dropdown = document.getElementById('hw_search_dropdown');
                        dropdown.innerHTML = '';
                        if (results.length === 0) {
                            dropdown.innerHTML = '<div style="padding:10px; color:#666; text-align:center;">لم يتم العثور على طلاب مطابقتين.</div>';
                        } else {
                            results.forEach(s => {
                                const div = document.createElement('div');
                                div.style = "padding:8px 12px; border-bottom:1px solid #f1f5f9; cursor:pointer; display:flex; align-items:center; gap:8px; font-size:12px;";
                                div.onmouseover = () => div.style.background = '#f8fafc';
                                div.onmouseout = () => div.style.background = '#fff';
                                div.innerHTML = `
                                    <strong style="color:#0f172a;">${s.name}</strong>
                                    <span style="font-size:10px; color:#64748b;">(الصف: ${s.class_name || ''} ${s.section || ''})</span>
                                `;
                                div.onclick = () => selectHwStudent(s);
                                dropdown.appendChild(div);
                            });
                        }
                        dropdown.style.display = 'block';
                    }
                });
            }, 300);
        });
    }

    const form = document.getElementById('add-assignment-form');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();

            if (hwSelectedStudents.length === 0) {
                document.getElementById('err_hw_receivers').style.display = 'block';
                return;
            } else {
                document.getElementById('err_hw_receivers').style.display = 'none';
            }

            const btn = document.getElementById('hw_submit_btn');
            btn.innerText = 'جاري إرسال الواجب...';
            btn.disabled = true;

            const formData = new FormData(this);
            formData.append('action', 'sm_add_assignment_ajax');

            fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    alert('✅ ' + (res.data.message || 'تم إرسال الواجب بنجاح.'));
                    location.reload();
                } else {
                    alert('❌ خطأ: ' + (res.data || 'فشل في إرسال الواجب.'));
                    btn.innerText = 'إرسال الواجب الآن ➔';
                    btn.disabled = false;
                }
            });
        });
    }
})();

function selectHwStudent(s) {
    if (hwSelectedStudents.find(x => x.id === s.id)) return;
    hwSelectedStudents.push(s);
    renderHwSelectedStudents();
    document.getElementById('hw_student_search_input').value = '';
    document.getElementById('hw_search_dropdown').style.display = 'none';
}

function renderHwSelectedStudents() {
    const container = document.getElementById('hw_selected_students_container');
    container.innerHTML = '';
    const ids = [];

    hwSelectedStudents.forEach(s => {
        // Use parent_user_id or user ID
        const targetId = s.parent_user_id || s.id;
        ids.push(targetId);

        const tag = document.createElement('div');
        tag.style = "background:#e0f2fe; padding:3px 10px; border-radius:20px; border:1px solid #bae6fd; display:flex; align-items:center; gap:6px; font-size:11px; font-weight:700; color:#0369a1;";
        tag.innerHTML = `
            <span>${s.name} (${s.class_name || ''})</span>
            <span onclick="removeHwStudent(${s.id})" style="cursor:pointer; color:#e53e3e; font-weight:bold;">✖</span>
        `;
        container.appendChild(tag);
    });

    document.getElementById('hw_receiver_ids').value = ids.join(',');
    if (ids.length > 0) {
        document.getElementById('err_hw_receivers').style.display = 'none';
    }
}

function removeHwStudent(id) {
    hwSelectedStudents = hwSelectedStudents.filter(x => x.id !== id);
    renderHwSelectedStudents();
}

function viewAssignment(a) {
    alert("تفاصيل الواجب:\n\n" + (a.description || 'لا توجد تعليمات إضافية.'));
}

function eessFilterHomework() {
    const q = document.getElementById('eess-homework-search').value.trim().toLowerCase();
    const subj = document.getElementById('eess-hw-filter-subject') ? document.getElementById('eess-hw-filter-subject').value.trim().toLowerCase() : '';
    const dateVal = document.getElementById('eess-hw-filter-date') ? document.getElementById('eess-hw-filter-date').value.trim() : '';

    const rows = document.querySelectorAll('.sm-table tbody tr');

    rows.forEach(row => {
        if (row.cells.length < 2) return;
        const text = row.textContent.toLowerCase();

        let matchQuery = !q || text.includes(q);
        let matchSubj = !subj || text.includes(subj);
        let matchDate = !dateVal || text.includes(dateVal);

        if (matchQuery && matchSubj && matchDate) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

function eessResetHomeworkFilter() {
    document.getElementById('eess-homework-search').value = '';
    if (document.getElementById('eess-hw-filter-subject')) document.getElementById('eess-hw-filter-subject').value = '';
    if (document.getElementById('eess-hw-filter-date')) document.getElementById('eess-hw-filter-date').value = '';
    eessFilterHomework();
}
</script>
