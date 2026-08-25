<?php
if (!defined('ABSPATH')) exit;
/**
 * 6-Step Multi-Step Modal Wizard for Teacher Behavioral Referrals
 */
?>
<div id="eess-teacher-referral-modal" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.75); backdrop-filter: blur(5px); z-index: 999999; justify-content: center; align-items: center; padding: 20px; box-sizing: border-box; font-family: 'Cairo', sans-serif; direction: rtl;">
    <div style="background: #ffffff; border-radius: 20px; max-width: 780px; width: 100%; border: 1px solid #cbd5e1; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.3); overflow: hidden; display: flex; flex-direction: column; max-height: 92vh;">

        <!-- Modal Header (Thinner, Dark Flush Header with White Title and White Icon) -->
        <div style="background: #0f172a; color: #ffffff; padding: 16px 24px; border-bottom: 1px solid #1e293b; display: flex; justify-content: space-between; align-items: center; width: 100%; box-sizing: border-box;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <span class="dashicons dashicons-warning" style="color: #ffffff; font-size: 22px; width: 22px; height: 22px; margin: 0;"></span>
                <div>
                    <h3 style="margin: 0; font-size: 16px; font-weight: 800; color: #ffffff; font-family: 'Cairo', sans-serif;">تقديم مخالفة سلوكية لطالب – إحالة إلى مشرف السلوك</h3>
                    <p style="margin: 3px 0 0 0; font-size: 11.5px; color: #94a3b8; font-weight: 600;">رصد وإحالة المواقف السلوكية بدقة لمتابعة مشرف الانضباط</p>
                </div>
            </div>
            <button type="button" onclick="eessCloseTeacherReferralModal()" style="background: none; border: none; color: #ffffff; font-size: 26px; cursor: pointer; line-height: 1;">&times;</button>
        </div>

        <!-- Wizard Progress Bar (6 Steps) -->
        <div style="background: #f8fafc; padding: 12px 24px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
            <div id="ref-step-indicator-1" class="ref-step-node ref-step-active">1. الطالب</div>
            <div id="ref-step-indicator-2" class="ref-step-node">2. الموقف</div>
            <div id="ref-step-indicator-3" class="ref-step-node">3. التفاصيل</div>
            <div id="ref-step-indicator-4" class="ref-step-node">4. الأدلة والصلات</div>
            <div id="ref-step-indicator-5" class="ref-step-node">5. المرفقات</div>
            <div id="ref-step-indicator-6" class="ref-step-node">6. المراجعة والرفع</div>
        </div>

        <!-- Wizard Steps Container -->
        <form id="eess-teacher-referral-form" style="padding: 24px; overflow-y: auto; flex: 1;" onsubmit="eessSubmitTeacherReferral(event)">

            <!-- Step 1: Student Selection -->
            <div id="ref-step-1" class="ref-step-content" style="display: block;">
                <h4 style="margin: 0 0 15px 0; font-size: 14px; font-weight: 800; color: #0f172a;">الخطوة 1: اختيار الطالب المعني بالإحالة السلوكية</h4>
                <div style="margin-bottom: 15px;">
                    <label style="display: block; font-size: 12.5px; font-weight: 700; color: #334155; margin-bottom: 6px;">البحث عن اسم الطالب أو الكود *</label>
                    <input type="text" id="ref_student_query" onkeyup="eessSearchReferralStudent(this.value)" class="sm-input" placeholder="اكتب اسم الطالب أو الكود لمطابقة البيانات..." style="width: 100%; height: 42px; border-radius: 10px; padding: 0 14px; font-size: 13px;">
                    <div id="ref_student_results" style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 10px; max-height: 180px; overflow-y: auto; display: none; margin-top: 6px; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);"></div>
                </div>

                <div id="ref_selected_student_box" style="display: none; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 12px; padding: 15px; margin-bottom: 15px;">
                    <div style="font-weight: 800; font-size: 14px; color: #166534;" id="ref_selected_student_name">---</div>
                    <div style="font-size: 12px; color: #15803d; margin-top: 4px;" id="ref_selected_student_info">---</div>
                    <input type="hidden" id="ref_selected_student_id" required>
                </div>
            </div>

            <!-- Step 2: Violation Title & Classification -->
            <div id="ref-step-2" class="ref-step-content" style="display: none;">
                <h4 style="margin: 0 0 15px 0; font-size: 14px; font-weight: 800; color: #0f172a;">الخطوة 2: تحديد بند وم عنوان السلوك الملاحظ</h4>
                <div style="margin-bottom: 15px;">
                    <label style="display: block; font-size: 12.5px; font-weight: 700; color: #334155; margin-bottom: 6px;">عنوان الموقف / اسم المخالفة السلوكية *</label>
                    <input type="text" id="ref_title" required placeholder="مثال: عدم الالتزام بالهدوء والتأخر التكراري عن الحصة..." style="width: 100%; height: 42px; border: 1px solid #cbd5e1; border-radius: 10px; padding: 0 14px; font-size: 13px;">
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div>
                        <label style="display: block; font-size: 12.5px; font-weight: 700; color: #334155; margin-bottom: 6px;">تصنيف الموقف الملاحظ</label>
                        <select id="ref_classification" style="width: 100%; height: 42px; border: 1px solid #cbd5e1; border-radius: 10px; padding: 0 12px; font-size: 13px;">
                            <option value="inside_class">داخل الفصل الدراسي</option>
                            <option value="yard">في الساحة / الطابور الصباحي</option>
                            <option value="labs">في المختبرات / غرف الأنشطة</option>
                            <option value="bus">في الحافلة المدرسية</option>
                            <option value="general">عام / ممر المدرسة</option>
                        </select>
                    </div>
                    <div>
                        <label style="display: block; font-size: 12.5px; font-weight: 700; color: #334155; margin-bottom: 6px;">تقدير درجة الأهمية المبدئي</label>
                        <select id="ref_degree" style="width: 100%; height: 42px; border: 1px solid #cbd5e1; border-radius: 10px; padding: 0 12px; font-size: 13px;">
                            <option value="1">مستوى أول (بسيطة)</option>
                            <option value="2">مستوى ثاني (متوسطة)</option>
                            <option value="3">مستوى ثالث (جسيمة)</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Step 3: Detailed Description -->
            <div id="ref-step-3" class="ref-step-content" style="display: none;">
                <h4 style="margin: 0 0 15px 0; font-size: 14px; font-weight: 800; color: #0f172a;">الخطوة 3: الوصف التفصيلي والوقائع المرصودة</h4>
                <div style="margin-bottom: 15px;">
                    <label style="display: block; font-size: 12.5px; font-weight: 700; color: #334155; margin-bottom: 6px;">شرح وتفاصيل الموقف الملاحظ *</label>
                    <textarea id="ref_details" required rows="5" placeholder="اكتب بالتفصيل زمن ووقائع السلوك الملاحظ وتأثيره على البيئة الصفية..." style="width: 100%; border: 1px solid #cbd5e1; border-radius: 10px; padding: 10px 14px; font-size: 13px; font-family: 'Cairo', sans-serif;"></textarea>
                </div>
            </div>

            <!-- Step 4: Evidence & Images -->
            <div id="ref-step-4" class="ref-step-content" style="display: none;">
                <h4 style="margin: 0 0 15px 0; font-size: 14px; font-weight: 800; color: #0f172a;">الخطوة 4: الأدلة والصور الإثباتية (إن وجدت)</h4>
                <div style="margin-bottom: 15px;">
                    <label style="display: block; font-size: 12.5px; font-weight: 700; color: #334155; margin-bottom: 6px;">صورة أو لقطة شاشة توثيقية</label>
                    <input type="file" id="ref_image_file" accept="image/*" style="width: 100%; padding: 8px; border: 1px dashed #cbd5e1; border-radius: 10px; font-size: 12px;">
                </div>
            </div>

            <!-- Step 5: PDF Attachment -->
            <div id="ref-step-5" class="ref-step-content" style="display: none;">
                <h4 style="margin: 0 0 15px 0; font-size: 14px; font-weight: 800; color: #0f172a;">الخطوة 5: إرفاق تقرير أو ملف PDF (اختياري)</h4>
                <div style="margin-bottom: 15px;">
                    <label style="display: block; font-size: 12.5px; font-weight: 700; color: #334155; margin-bottom: 6px;">ملف مستند إضافي (PDF / Doc)</label>
                    <input type="file" id="ref_doc_file" accept=".pdf,.doc,.docx" style="width: 100%; padding: 8px; border: 1px dashed #cbd5e1; border-radius: 10px; font-size: 12px;">
                </div>
            </div>

            <!-- Step 6: Review & Final Submission -->
            <div id="ref-step-6" class="ref-step-content" style="display: none;">
                <h4 style="margin: 0 0 15px 0; font-size: 14px; font-weight: 800; color: #0f172a;">الخطوة 6: مراجعة البيانات ورفع الإحالة لمشرف السلوك</h4>
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 15px; margin-bottom: 15px; font-size: 13px;">
                    <div style="margin-bottom: 8px;"><strong>الطالب:</strong> <span id="rev_student_name">---</span></div>
                    <div style="margin-bottom: 8px;"><strong>عنوان الموقف:</strong> <span id="rev_title">---</span></div>
                    <div style="margin-bottom: 8px;"><strong>التصنيف:</strong> <span id="rev_classification">---</span></div>
                    <div style="margin-bottom: 8px;"><strong>التفاصيل:</strong> <span id="rev_details">---</span></div>
                    <div style="color: #dc2626; font-size: 11.5px; font-weight: 700; margin-top: 10px;">⚠️ سيتم إرسال هذا التقرير مباشرة لمشرف السلوك والاعتماد للانضباط. لن يتم تسجيل المخالفة رسمياً في سجل الطالب إلا بعد مراجعتها واعتمادها من المشرف.</div>
                </div>
            </div>

            <!-- Footer Controls -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 20px; padding-top: 15px; border-top: 1px solid #e2e8f0;">
                <button type="button" id="ref-prev-btn" onclick="eessTeacherReferralNav(-1)" style="background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; border-radius: 10px; padding: 8px 18px; font-weight: 700; font-size: 13px; cursor: pointer; display: none;">← السابق</button>
                <div></div>
                <button type="button" id="ref-next-btn" onclick="eessTeacherReferralNav(1)" style="background: #dc2626; color: #ffffff; border: none; border-radius: 10px; padding: 8px 22px; font-weight: 800; font-size: 13.5px; cursor: pointer;">التالي →</button>
                <button type="submit" id="ref-submit-btn" style="background: #16a34a; color: #ffffff; border: none; border-radius: 10px; padding: 8px 24px; font-weight: 800; font-size: 13.5px; cursor: pointer; display: none;">رفع الإحالة لمشرف السلوك</button>
            </div>

        </form>
    </div>
</div>

<style>
.ref-step-node { font-size: 11px; font-weight: 700; color: #94a3b8; padding: 4px 8px; border-radius: 20px; transition: all 0.2s; }
.ref-step-active { background: #fee2e2; color: #dc2626; font-weight: 800; }
</style>

<script>
let eessRefCurrentStep = 1;

function eessOpenTeacherReferralModal() {
    eessRefCurrentStep = 1;
    eessUpdateReferralWizardUI();
    document.getElementById('eess-teacher-referral-modal').style.display = 'flex';
}

function eessCloseTeacherReferralModal() {
    document.getElementById('eess-teacher-referral-modal').style.display = 'none';
}

function eessTeacherReferralNav(dir) {
    if (dir === 1) {
        if (eessRefCurrentStep === 1 && !document.getElementById('ref_selected_student_id').value) {
            if (typeof smShowNotification === 'function') smShowNotification('يرجى اختيار الطالب المعني أولاً', true);
            return;
        }
        if (eessRefCurrentStep === 2 && !document.getElementById('ref_title').value) {
            if (typeof smShowNotification === 'function') smShowNotification('يرجى كتابة عنوان الموقف/المخالفة السلوكية', true);
            return;
        }
        if (eessRefCurrentStep === 3 && !document.getElementById('ref_details').value) {
            if (typeof smShowNotification === 'function') smShowNotification('يرجى كتابة التفاصيل والوقائع المرصودة', true);
            return;
        }
    }

    eessRefCurrentStep += dir;
    if (eessRefCurrentStep < 1) eessRefCurrentStep = 1;
    if (eessRefCurrentStep > 6) eessRefCurrentStep = 6;

    if (eessRefCurrentStep === 6) {
        document.getElementById('rev_student_name').innerText = document.getElementById('ref_selected_student_name').innerText;
        document.getElementById('rev_title').innerText = document.getElementById('ref_title').value;
        document.getElementById('rev_classification').innerText = document.getElementById('ref_classification').value;
        document.getElementById('rev_details').innerText = document.getElementById('ref_details').value;
    }

    eessUpdateReferralWizardUI();
}

function eessUpdateReferralWizardUI() {
    for (let i = 1; i <= 6; i++) {
        document.getElementById('ref-step-' + i).style.display = (i === eessRefCurrentStep) ? 'block' : 'none';
        const node = document.getElementById('ref-step-indicator-' + i);
        if (node) {
            if (i === eessRefCurrentStep) {
                node.classList.add('ref-step-active');
            } else {
                node.classList.remove('ref-step-active');
            }
        }
    }

    document.getElementById('ref-prev-btn').style.display = (eessRefCurrentStep > 1) ? 'inline-block' : 'none';
    document.getElementById('ref-next-btn').style.display = (eessRefCurrentStep < 6) ? 'inline-block' : 'none';
    document.getElementById('ref-submit-btn').style.display = (eessRefCurrentStep === 6) ? 'inline-block' : 'none';
}

function eessSearchReferralStudent(q) {
    if (q.length < 2) {
        document.getElementById('ref_student_results').style.display = 'none';
        return;
    }

    const formData = new FormData();
    formData.append('action', 'sm_search_students');
    formData.append('query', q);

    fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
        if (res.success && res.data.length > 0) {
            let html = '';
            res.data.forEach(s => {
                html += `<div style="padding: 10px; border-bottom: 1px solid #f1f5f9; cursor: pointer;" onclick="eessSelectReferralStudent(${s.id}, '${s.name}', '${s.class_name || ''} ${s.section || ''} (${s.student_code || ''})')">
                            <strong>${s.name}</strong><br><small style="color:#64748b;">${s.class_name || ''} ${s.section || ''} | ${s.student_code || ''}</small>
                         </div>`;
            });
            document.getElementById('ref_student_results').innerHTML = html;
            document.getElementById('ref_student_results').style.display = 'block';
        }
    });
}

function eessSelectReferralStudent(id, name, info) {
    document.getElementById('ref_selected_student_id').value = id;
    document.getElementById('ref_selected_student_name').innerText = name;
    document.getElementById('ref_selected_student_info').innerText = info;
    document.getElementById('ref_selected_student_box').style.display = 'block';
    document.getElementById('ref_student_results').style.display = 'none';
    document.getElementById('ref_student_query').value = '';
}

function eessSubmitTeacherReferral(e) {
    e.preventDefault();
    const btn = document.getElementById('ref-submit-btn');
    btn.disabled = true;
    btn.innerText = 'جاري الرفع...';

    const formData = new FormData();
    formData.append('action', 'sm_submit_behavior_referral');
    formData.append('student_id', document.getElementById('ref_selected_student_id').value);
    formData.append('title', document.getElementById('ref_title').value);
    formData.append('classification', document.getElementById('ref_classification').value);
    formData.append('degree', document.getElementById('ref_degree').value);
    formData.append('details', document.getElementById('ref_details').value);
    formData.append('nonce', '<?php echo wp_create_nonce("sm_record_action"); ?>');

    const imgInput = document.getElementById('ref_image_file');
    if (imgInput.files.length > 0) {
        formData.append('image_file', imgInput.files[0]);
    }
    const docInput = document.getElementById('ref_doc_file');
    if (docInput.files.length > 0) {
        formData.append('doc_file', docInput.files[0]);
    }

    fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
        btn.disabled = false;
        btn.innerText = 'رفع الإحالة لمشرف السلوك';
        if (res.success) {
            if (typeof smShowNotification === 'function') smShowNotification('تم تقديم المخالفة السلوكية بنجاح وهي قيد المراجعة والاعتماد من مشرف السلوك');
            eessCloseTeacherReferralModal();
            setTimeout(() => location.reload(), 500);
        } else {
            if (typeof smShowNotification === 'function') smShowNotification('خطأ: ' + (res.data || 'تعذر حفظ البيانات'), true);
        }
    });
}
</script>
