<?php if (!defined('ABSPATH')) exit; ?>
<!-- REUSABLE UNIFIED MULTI-STEP STUDENT PROFILE EDIT MODAL -->
<div id="edit-student-modal" class="sm-modal-overlay" style="display: none; z-index: 999999;">
    <div class="sm-modal-content" style="max-width: 680px; border-radius: 20px; padding: 28px; background: #ffffff;">
        <div class="sm-modal-header" style="border-bottom: 1px solid #e2e8f0; padding-bottom: 14px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0; font-size: 17px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 8px;">
                <svg width="20" height="20" fill="none" stroke="#2563eb" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                تعديل الملف المعلوماتي للطالب
            </h3>
            <button type="button" class="sm-modal-close" onclick="closeUnifiedEditStudentModal()" style="background: none; border: none; font-size: 24px; color: #94a3b8; cursor: pointer;">&times;</button>
        </div>

        <!-- Wizard Step Progress Indicator -->
        <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 24px; position: relative;">
            <div style="position: absolute; top: 50%; left: 15%; right: 15%; height: 2px; background: #e2e8f0; z-index: 1;"></div>

            <!-- Step 1 Node -->
            <div id="eess-wiz-node-1" onclick="goUnifiedEditStep(1)" style="position: relative; z-index: 2; width: 36px; height: 36px; border-radius: 50%; background: #2563eb; color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 13px; cursor: pointer; border: 2px solid #2563eb; transition: all 0.25s;">
                1
            </div>

            <!-- Step 2 Node -->
            <div id="eess-wiz-node-2" onclick="goUnifiedEditStep(2)" style="position: relative; z-index: 2; width: 36px; height: 36px; border-radius: 50%; background: #fff; color: #64748b; border: 2px solid #cbd5e1; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 13px; cursor: pointer; transition: all 0.25s;">
                2
            </div>

            <!-- Step 3 Node -->
            <div id="eess-wiz-node-3" onclick="goUnifiedEditStep(3)" style="position: relative; z-index: 2; width: 36px; height: 36px; border-radius: 50%; background: #fff; color: #64748b; border: 2px solid #cbd5e1; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 13px; cursor: pointer; transition: all 0.25s;">
                3
            </div>
        </div>

        <form id="edit-student-form">
            <?php wp_nonce_field('sm_add_student', 'sm_nonce'); ?>
            <input type="hidden" name="student_id" id="edit_stu_id">

            <!-- STEP 1: Student Photo & Basic Personal Information -->
            <div id="eess-wiz-step-1" class="eess-wiz-panel" style="display: block;">
                <div style="background: #f8fafc; padding: 20px; border-radius: 16px; border: 1px solid #e2e8f0; margin-bottom: 20px;">
                    <div style="border-bottom: 1px solid #e2e8f0; padding-bottom: 8px; margin-bottom: 16px; color: #2563eb; font-weight: 800; font-size: 13.5px; display: flex; align-items: center; gap: 6px;">
                        <span>الخطوة 1: صورة الطالب والبيانات الأساسية</span>
                    </div>

                    <!-- Photo Upload Section -->
                    <div style="display: flex; flex-direction: column; align-items: center; text-align: center; margin-bottom: 18px; padding: 16px; background: #ffffff; border-radius: 14px; border: 1px dashed #cbd5e1;">
                        <div id="edit_stu_photo_preview_box" style="width: 80px; height: 80px; border-radius: 12px; overflow: hidden; background: #f1f5f9; display: flex; align-items: center; justify-content: center; border: 2px solid #cbd5e1; margin-bottom: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
                            <svg id="edit_stu_default_icon" width="36" height="36" fill="#94a3b8" viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                            <img id="edit_stu_photo_img" src="" style="width: 100%; height: 100%; object-fit: cover; display: none;" />
                        </div>

                        <div style="display: flex; gap: 8px; align-items: center; margin-bottom: 6px;">
                            <label for="edit_stu_photo_file" class="sm-btn" style="background: #2563eb; color: #ffffff; height: 32px; padding: 0 14px; font-size: 11.5px; border-radius: 8px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 4px;">
                                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                                رفع / تغيير الصورة
                            </label>
                            <input type="file" id="edit_stu_photo_file" accept="image/*" style="display: none;" onchange="handleStudentPhotoSelected(this)">
                        </div>

                        <p style="margin: 0; font-size: 11.5px; color: #64748b; font-weight: 600;">
                            "Student photo must be square and have a white background."
                        </p>
                    </div>

                    <!-- Basic Fields Grid -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                        <div class="sm-form-group" style="margin-bottom: 0;">
                            <label class="sm-label" style="font-size: 12px; font-weight: 700; color: #334155;">الاسم الكامل للطالب *:</label>
                            <input type="text" name="name" id="edit_stu_name" class="sm-input" required style="height: 38px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 10px; font-size: 12.5px;">
                        </div>

                        <div class="sm-form-group" style="margin-bottom: 0;">
                            <label class="sm-label" style="font-size: 12px; font-weight: 700; color: #334155;">الرقم الأكاديمي (الكود):</label>
                            <input type="text" name="student_code" id="edit_stu_code" class="sm-input" readonly style="height: 38px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 10px; font-size: 12.5px; background: #e2e8f0; font-weight: 700;">
                        </div>

                        <div class="sm-form-group" style="margin-bottom: 0;">
                            <label class="sm-label" style="font-size: 12px; font-weight: 700; color: #334155;">جنسية الطالب:</label>
                            <input type="text" name="nationality" id="edit_stu_nationality" class="sm-input" placeholder="مثال: إماراتي" style="height: 38px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 10px; font-size: 12.5px;">
                        </div>

                        <div class="sm-form-group" style="margin-bottom: 0;">
                            <label class="sm-label" style="font-size: 12px; font-weight: 700; color: #334155;">تاريخ التسجيل:</label>
                            <input type="date" name="registration_date" id="edit_stu_reg_date" class="sm-input" style="height: 38px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 10px; font-size: 12.5px;">
                        </div>
                    </div>
                </div>
            </div>

            <!-- STEP 2: Academic Placement Information -->
            <div id="eess-wiz-step-2" class="eess-wiz-panel" style="display: none;">
                <div style="background: #f8fafc; padding: 20px; border-radius: 16px; border: 1px solid #e2e8f0; margin-bottom: 20px;">
                    <div style="border-bottom: 1px solid #e2e8f0; padding-bottom: 8px; margin-bottom: 16px; color: #2563eb; font-weight: 800; font-size: 13.5px;">
                        الخطوة 2: التسكين والمرحلة الدراسية
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                        <div class="sm-form-group" style="margin-bottom: 0;">
                            <label class="sm-label" style="font-size: 12px; font-weight: 700; color: #334155;">الصف الدراسي *:</label>
                            <select name="class_name" id="edit_stu_class" class="sm-select" required style="height: 38px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 10px; font-size: 12.5px;">
                                <option value="">-- اختر الصف الدراسي --</option>
                                <?php
                                $academic = SM_Settings::get_academic_structure();
                                foreach ($academic['active_grades'] as $grade_num) {
                                    echo "<option value='الصف $grade_num'>الصف $grade_num</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div class="sm-form-group" style="margin-bottom: 0;">
                            <label class="sm-label" style="font-size: 12px; font-weight: 700; color: #334155;">الشعبة / الفصل *:</label>
                            <input type="text" name="section" id="edit_stu_section" class="sm-input" required placeholder="مثال: أ أو 1" style="height: 38px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 10px; font-size: 12.5px;">
                        </div>
                    </div>
                </div>
            </div>

            <!-- STEP 3: Guardian & Contact Information -->
            <div id="eess-wiz-step-3" class="eess-wiz-panel" style="display: none;">
                <div style="background: #f8fafc; padding: 20px; border-radius: 16px; border: 1px solid #e2e8f0; margin-bottom: 20px;">
                    <div style="border-bottom: 1px solid #e2e8f0; padding-bottom: 8px; margin-bottom: 16px; color: #2563eb; font-weight: 800; font-size: 13.5px;">
                        الخطوة 3: معلومات ولي الأمر والتواصل
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                        <div class="sm-form-group" style="margin-bottom: 0;">
                            <label class="sm-label" style="font-size: 12px; font-weight: 700; color: #334155;">بريد ولي الأمر:</label>
                            <input type="email" name="parent_email" id="edit_stu_email" class="sm-input" placeholder="parent@example.com" style="height: 38px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 10px; font-size: 12.5px;">
                        </div>

                        <div class="sm-form-group" style="margin-bottom: 0;">
                            <label class="sm-label" style="font-size: 12px; font-weight: 700; color: #334155;">رقم هاتف ولي الأمر:</label>
                            <input type="text" name="guardian_phone" id="edit_stu_phone" class="sm-input" placeholder="0501234567" style="height: 38px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 10px; font-size: 12.5px;">
                        </div>

                        <div class="sm-form-group" style="grid-column: span 2; margin-bottom: 0;">
                            <label class="sm-label" style="font-size: 12px; font-weight: 700; color: #334155;">ربط بحساب الطالب (اختياري):</label>
                            <select name="parent_user_id" id="edit_stu_parent_user" class="sm-select" style="height: 38px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 10px; font-size: 12.5px;">
                                <option value="">-- بلا ربط --</option>
                                <?php foreach (get_users(array('role' => 'sm_student')) as $p): ?>
                                    <option value="<?php echo $p->ID; ?>"><?php echo esc_html($p->display_name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Wizard Navigation Actions Footer -->
            <div style="display: flex; gap: 10px; justify-content: flex-end; align-items: center;">
                <button type="button" id="eess-wiz-prev-btn" onclick="goUnifiedEditStep(currentUnifiedStep - 1)" class="sm-btn" style="background: #cbd5e1; color: #334155; height: 38px; padding: 0 16px; border-radius: 8px; font-weight: 700; border: none; display: none; cursor: pointer;">السابق</button>
                <button type="button" id="eess-wiz-next-btn" onclick="goUnifiedEditStep(currentUnifiedStep + 1)" class="sm-btn" style="background: #0f172a; color: #ffffff; height: 38px; padding: 0 20px; border-radius: 8px; font-weight: 800; border: none; cursor: pointer;">التالي</button>
                <button type="submit" id="eess-wiz-submit-btn" class="sm-btn" style="background: #dc2626; color: #ffffff; height: 38px; padding: 0 22px; border-radius: 8px; font-weight: 800; border: none; display: none; cursor: pointer;">تحديث البيانات الآن</button>
                <button type="button" onclick="closeUnifiedEditStudentModal()" class="sm-btn" style="background: #f1f5f9; color: #64748b; height: 38px; padding: 0 16px; border-radius: 8px; font-weight: 700; border: none; cursor: pointer;">إلغاء</button>
            </div>
        </form>
    </div>
</div>

<script>
let currentUnifiedStep = 1;

function goUnifiedEditStep(step) {
    if (step < 1) step = 1;
    if (step > 3) step = 3;
    currentUnifiedStep = step;

    document.querySelectorAll('.eess-wiz-panel').forEach(p => p.style.display = 'none');
    document.getElementById('eess-wiz-step-' + step).style.display = 'block';

    for (let i = 1; i <= 3; i++) {
        const node = document.getElementById('eess-wiz-node-' + i);
        if (node) {
            if (i === step) {
                node.style.background = '#2563eb';
                node.style.color = '#ffffff';
                node.style.borderColor = '#2563eb';
            } else if (i < step) {
                node.style.background = '#16a34a';
                node.style.color = '#ffffff';
                node.style.borderColor = '#16a34a';
            } else {
                node.style.background = '#ffffff';
                node.style.color = '#64748b';
                node.style.borderColor = '#cbd5e1';
            }
        }
    }

    document.getElementById('eess-wiz-prev-btn').style.display = (step > 1) ? 'inline-flex' : 'none';
    document.getElementById('eess-wiz-next-btn').style.display = (step < 3) ? 'inline-flex' : 'none';
    document.getElementById('eess-wiz-submit-btn').style.display = (step === 3) ? 'inline-flex' : 'none';
}

function closeUnifiedEditStudentModal() {
    const modal = document.getElementById('edit-student-modal');
    if (modal) modal.style.display = 'none';
}

function handleStudentPhotoSelected(input) {
    if (!input.files || !input.files[0]) return;
    const file = input.files[0];
    const studentId = document.getElementById('edit_stu_id').value;

    if (!studentId) {
        alert('يرجى تحديد الطالب أولاً قبل رفع الصورة');
        return;
    }

    const formData = new FormData();
    formData.append('action', 'sm_update_student_photo');
    formData.append('student_id', studentId);
    formData.append('student_photo', file);
    formData.append('sm_photo_nonce', '<?php echo wp_create_nonce("sm_photo_action"); ?>');

    const previewImg = document.getElementById('edit_stu_photo_img');
    const defaultIcon = document.getElementById('edit_stu_default_icon');

    fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
        if (res.success && res.data.photo_url) {
            previewImg.src = res.data.photo_url;
            previewImg.style.display = 'block';
            if (defaultIcon) defaultIcon.style.display = 'none';
            if (typeof smShowNotification === 'function') smShowNotification('تم تحديث صورة الطالب بنجاح');
        } else {
            alert('فشل رفع الصورة: ' + (res.data || 'خطأ غير معروف'));
        }
    });
}

(function() {
    // Shared Global Function
    window.editSmStudent = function(s) {
        if (!s) return;
        document.getElementById('edit_stu_id').value = s.id || s.student_id || '';
        document.getElementById('edit_stu_name').value = s.name || s.student_name || '';
        document.getElementById('edit_stu_class').value = s.class_name || s.class || '';
        document.getElementById('edit_stu_section').value = s.section || '';
        document.getElementById('edit_stu_email').value = s.parent_email || '';
        document.getElementById('edit_stu_phone').value = s.guardian_phone || '';
        document.getElementById('edit_stu_nationality').value = s.nationality || '';
        document.getElementById('edit_stu_code').value = s.student_code || s.student_id || '';
        if (document.getElementById('edit_stu_reg_date')) {
            document.getElementById('edit_stu_reg_date').value = s.registration_date || '';
        }
        if (document.getElementById('edit_stu_parent_user')) {
            document.getElementById('edit_stu_parent_user').value = s.parent_user_id || s.parent_id || '';
        }

        const previewImg = document.getElementById('edit_stu_photo_img');
        const defaultIcon = document.getElementById('edit_stu_default_icon');
        if (s.photo_url) {
            previewImg.src = s.photo_url;
            previewImg.style.display = 'block';
            if (defaultIcon) defaultIcon.style.display = 'none';
        } else {
            previewImg.src = '';
            previewImg.style.display = 'none';
            if (defaultIcon) defaultIcon.style.display = 'block';
        }

        goUnifiedEditStep(1);
        const modal = document.getElementById('edit-student-modal');
        if (modal) modal.style.display = 'flex';
    };

    window.editSmStudentFromStats = window.editSmStudent;

    const editForm = document.getElementById('edit-student-form');
    if (editForm) {
        editForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('action', 'sm_update_student_ajax');

            fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    if (typeof smShowNotification === 'function') smShowNotification('تم تحديث بيانات الطالب بنجاح');
                    closeUnifiedEditStudentModal();

                    // Refresh violation filter or reload list
                    const filterForm = document.getElementById('violation-filter-form');
                    if (filterForm && typeof fetchViolationsData === 'function') {
                        fetchViolationsData();
                    } else {
                        setTimeout(() => location.reload(), 500);
                    }
                } else {
                    alert('خطأ أثناء التحديث: ' + res.data);
                }
            });
        });
    }
})();
</script>
