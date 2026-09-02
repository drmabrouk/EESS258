/**
 * EESS Unified User & Employee Management Modal Controller
 * Synchronizes client-side behavior across System User Management, HR, and Employee Profile
 */

var eessCurrentStep = 1;
var eessIsEditMode = false;
var eessAjaxUrl = (typeof ajaxurl !== 'undefined') ? ajaxurl : '/wp-admin/admin-ajax.php';

window.eessOpenUnifiedUserModal = function(mode, userId) {
    mode = mode || 'add_user';
    userId = userId || 0;

    eessIsEditMode = (mode === 'edit_user' || mode === 'edit_employee_profile') && parseInt(userId) > 0;

    var modal = document.getElementById('unified-user-modal');
    if (!modal) return;

    var form = document.getElementById('eess-unified-user-form');
    if (form) form.reset();

    // Reset all input values explicitly
    ['u_first_name', 'u_last_name', 'u_employee_id', 'u_username', 'u_user_email', 'u_phone_number', 'u_civil_id', 'u_dob', 'u_nationality', 'u_address', 'u_building_info', 'u_admin_section', 'u_assigned_sections', 'u_user_pass', 'u_user_pass_confirm', 'u_sec_username_display'].forEach(function(id) {
        var el = document.getElementById(id);
        if (el) el.value = '';
    });

    ['u_user_role', 'u_institution_id', 'u_school_id', 'u_department', 'u_specialization', 'u_gender', 'u_emirate', 'u_user_status'].forEach(function(id) {
        var el = document.getElementById(id);
        if (el) el.selectedIndex = 0;
    });

    // Reset Grade Capsules
    document.querySelectorAll('input[name="assigned_grades[]"]').forEach(function(cb) {
        cb.checked = false;
        eessToggleGradeCapsule(cb);
    });

    document.getElementById('u_user_id').value = userId;
    document.getElementById('u_form_mode').value = mode;

    // Reset error labels
    var errors = document.querySelectorAll('.eess-field-error');
    errors.forEach(function(el) { el.style.display = 'none'; });

    // Set title and icon according to mode
    var titleEl = document.getElementById('u_modal_title');
    var iconEl = document.getElementById('u_modal_icon');

    if (titleEl) {
        if (mode === 'edit_employee_profile') {
            titleEl.innerText = 'تعديل ملف الموظف والبيانات المهنية';
            if (iconEl) iconEl.className = 'dashicons dashicons-id-alt';
        } else if (mode === 'add_user' || mode === 'add_employee') {
            titleEl.innerText = 'إضافة مستخدم جديد في المنصة';
            if (iconEl) iconEl.className = 'dashicons dashicons-user-plus';
        } else {
            titleEl.innerText = 'تعديل بيانات الحساب وتعيينات الموظف';
            if (iconEl) iconEl.className = 'dashicons dashicons-admin-users';
        }
    }

    var passInput = document.getElementById('u_user_pass');
    var passConfInput = document.getElementById('u_user_pass_confirm');

    if (eessIsEditMode) {
        if (passInput) passInput.required = false;
        if (passConfInput) passConfInput.required = false;
    } else {
        if (passInput) passInput.required = true;
        if (passConfInput) passConfInput.required = true;
    }

    // Clear Avatar Preview
    var photoPreview = document.getElementById('u_photo_preview');
    if (photoPreview) {
        photoPreview.src = 'data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 24 24\' fill=\'%2394a3b8\'><path d=\'M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z\'/></svg>';
    }

    // Go to Step 1
    eessGoToStep(1);

    // If Edit Mode, Fetch User Data
    if (eessIsEditMode) {
        eessLoadUserData(userId);
    }

    modal.style.display = 'flex';
};

window.eessCloseUnifiedUserModal = function() {
    var modal = document.getElementById('unified-user-modal');
    if (modal) modal.style.display = 'none';
};

window.eessGoToStep = function(step) {
    if (step > eessCurrentStep) {
        if (eessCurrentStep === 1 && !eessValidateStep1()) return;
        if (eessCurrentStep === 2 && !eessValidateStep2()) return;
        if (eessCurrentStep === 3 && !eessValidateStep3()) return;
    }

    eessCurrentStep = step;
    if (eessCurrentStep < 1) eessCurrentStep = 1;
    if (eessCurrentStep > 5) eessCurrentStep = 5;

    for (var i = 1; i <= 5; i++) {
        var container = document.getElementById('u_step_' + i + '_container');
        var indicator = document.getElementById('u_indicator_step' + i);

        if (container) container.style.display = (i === eessCurrentStep) ? 'block' : 'none';
        if (indicator) {
            if (i === eessCurrentStep) {
                indicator.style.background = '#881337';
                indicator.style.color = 'white';
            } else if (i < eessCurrentStep) {
                indicator.style.background = '#16a34a';
                indicator.style.color = 'white';
            } else {
                indicator.style.background = '#f1f5f9';
                indicator.style.color = '#64748b';
            }
        }
    }

    var btnPrev = document.getElementById('u_btn_prev');
    var btnNext = document.getElementById('u_btn_next');
    var btnSave = document.getElementById('u_btn_save');

    if (btnPrev) btnPrev.style.display = (eessCurrentStep > 1) ? 'inline-block' : 'none';
    if (btnNext) btnNext.style.display = (eessCurrentStep < 5) ? 'inline-block' : 'none';
    if (btnSave) btnSave.style.display = (eessCurrentStep === 5) ? 'inline-block' : 'none';

    if (eessCurrentStep === 4) {
        eessRenderStepSummary();
    }

    if (eessCurrentStep === 5) {
        var empIdVal = document.getElementById('u_employee_id').value;
        var usernameDisplay = document.getElementById('u_sec_username_display');
        if (usernameDisplay) {
            usernameDisplay.value = empIdVal ? empIdVal : 'سيتم توليده تلقائياً من الرقم الوظيفي';
        }
    }
};

window.eessToggleGradeCapsule = function(cb) {
    if (!cb) return;
    var parentLabel = cb.closest('.u-grade-capsule-label');
    if (!parentLabel) return;

    if (cb.checked) {
        parentLabel.style.background = '#fef2f2';
        parentLabel.style.borderColor = '#fecdd3';
        parentLabel.style.color = '#881337';
    } else {
        parentLabel.style.background = '#f1f5f9';
        parentLabel.style.borderColor = '#cbd5e1';
        parentLabel.style.color = '#334155';
    }
};

window.eessRenderStepSummary = function() {
    var fn = document.getElementById('u_first_name').value;
    var ln = document.getElementById('u_last_name').value;
    var nat = document.getElementById('u_nationality').value || 'غير محدد';
    var dob = document.getElementById('u_dob').value || 'غير محدد';
    var genderSel = document.getElementById('u_gender');
    var genderTxt = genderSel.options[genderSel.selectedIndex] ? genderSel.options[genderSel.selectedIndex].text : 'غير محدد';

    var email = document.getElementById('u_user_email').value;
    var phone = document.getElementById('u_country_code').value + ' ' + document.getElementById('u_phone_number').value;
    var country = document.getElementById('u_country_residence').value;
    var emirate = document.getElementById('u_emirate').value || 'غير محدد';
    var address = document.getElementById('u_address').value || 'غير محدد';
    var bldg = document.getElementById('u_building_info').value || 'غير محدد';

    var roleSel = document.getElementById('u_user_role');
    var roleTxt = roleSel.options[roleSel.selectedIndex] ? roleSel.options[roleSel.selectedIndex].text : '-';
    var roleVal = roleSel.value;
    var empId = document.getElementById('u_employee_id').value;

    var instSel = document.getElementById('u_institution_id');
    var instTxt = instSel.options[instSel.selectedIndex] ? instSel.options[instSel.selectedIndex].text : '-';

    var schSel = document.getElementById('u_school_id');
    var schTxt = schSel.options[schSel.selectedIndex] ? schSel.options[schSel.selectedIndex].text : '-';

    var specSel = document.getElementById('u_specialization');
    var specTxt = specSel.options[specSel.selectedIndex] ? specSel.options[specSel.selectedIndex].text : '-';

    var sectionsTxt = document.getElementById('u_assigned_sections').value || 'جميع الشعب';

    var checkedGrades = [];
    document.querySelectorAll('input[name="assigned_grades[]"]:checked').forEach(function(g) {
        checkedGrades.push(g.value);
    });

    if (document.getElementById('rev_u_fullname')) document.getElementById('rev_u_fullname').innerText = (fn + ' ' + ln).trim() || '-';
    if (document.getElementById('rev_u_nat_dob')) document.getElementById('rev_u_nat_dob').innerText = nat + ' (' + dob + ')';
    if (document.getElementById('rev_u_gender')) document.getElementById('rev_u_gender').innerText = genderTxt;
    if (document.getElementById('rev_u_email')) document.getElementById('rev_u_email').innerText = email || '-';
    if (document.getElementById('rev_u_phone')) document.getElementById('rev_u_phone').innerText = phone || '-';
    if (document.getElementById('rev_u_location')) document.getElementById('rev_u_location').innerText = country + ' - ' + emirate;
    if (document.getElementById('rev_u_address_info')) document.getElementById('rev_u_address_info').innerText = address + ' (' + bldg + ')';
    if (document.getElementById('rev_u_role_id')) document.getElementById('rev_u_role_id').innerText = roleTxt + ' (ID: ' + empId + ')';

    if (roleVal === 'administrator') {
        if (document.getElementById('rev_u_inst_container')) document.getElementById('rev_u_inst_container').style.display = 'none';
        if (document.getElementById('rev_u_grades_container')) document.getElementById('rev_u_grades_container').style.display = 'none';
    } else {
        if (document.getElementById('rev_u_inst_container')) document.getElementById('rev_u_inst_container').style.display = 'block';
        if (document.getElementById('rev_u_grades_container')) document.getElementById('rev_u_grades_container').style.display = 'block';
        if (document.getElementById('rev_u_inst_subj')) document.getElementById('rev_u_inst_subj').innerText = instTxt + (schTxt !== '-' ? ' — ' + schTxt : '') + ' (' + specTxt + ')';
        if (document.getElementById('rev_u_grades')) document.getElementById('rev_u_grades').innerText = (checkedGrades.length > 0 ? checkedGrades.join('، ') : 'جميع الصفوف') + ' — شعب: ' + sectionsTxt;
    }
};

window.eessSyncUsername = function(empInput) {
    if (!empInput) return;
    var clean = empInput.value.replace(/^(EMP|EMP-|_)+/i, '').trim();
    empInput.value = clean;
    var usernameInput = document.getElementById('u_username');
    if (usernameInput) usernameInput.value = clean;
    var errEl = document.getElementById('err_u_employee_id');
    if (errEl && clean !== '') errEl.style.display = 'none';
};

window.eessPreviewAvatar = function(input) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            var photoPreview = document.getElementById('u_photo_preview');
            if (photoPreview) photoPreview.src = e.target.result;
        };
        reader.readAsDataURL(input.files[0]);
    }
};

window.eessValidateField = function(input) {
    if (!input) return;
    var errEl = document.getElementById('err_' + input.id);
    if (input.checkValidity() && input.value.trim() !== '') {
        if (errEl) errEl.style.display = 'none';
    }
};

window.eessValidateStep1 = function() {
    var valid = true;
    var firstName = document.getElementById('u_first_name');
    var lastName = document.getElementById('u_last_name');
    var nationality = document.getElementById('u_nationality');
    var dob = document.getElementById('u_dob');

    if (!firstName.value.trim()) {
        document.getElementById('err_u_first_name').style.display = 'block';
        valid = false;
    } else { document.getElementById('err_u_first_name').style.display = 'none'; }

    if (!lastName.value.trim()) {
        document.getElementById('err_u_last_name').style.display = 'block';
        valid = false;
    } else { document.getElementById('err_u_last_name').style.display = 'none'; }

    if (!nationality.value.trim()) {
        document.getElementById('err_u_nationality').style.display = 'block';
        valid = false;
    } else { document.getElementById('err_u_nationality').style.display = 'none'; }

    if (!dob.value.trim()) {
        document.getElementById('err_u_dob').style.display = 'block';
        valid = false;
    } else { document.getElementById('err_u_dob').style.display = 'none'; }

    return valid;
};

window.eessValidateStep2 = function() {
    var valid = true;
    var phone = document.getElementById('u_phone_number');
    var email = document.getElementById('u_user_email');
    var emirate = document.getElementById('u_emirate');

    if (!phone.value.trim()) {
        document.getElementById('err_u_phone_number').style.display = 'block';
        valid = false;
    } else { document.getElementById('err_u_phone_number').style.display = 'none'; }

    var emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailPattern.test(email.value.trim())) {
        document.getElementById('err_u_user_email').style.display = 'block';
        valid = false;
    } else { document.getElementById('err_u_user_email').style.display = 'none'; }

    if (!emirate.value) {
        document.getElementById('err_u_emirate').style.display = 'block';
        valid = false;
    } else { document.getElementById('err_u_emirate').style.display = 'none'; }

    return valid;
};

window.eessValidateStep3 = function() {
    var valid = true;
    var role = document.getElementById('u_user_role');
    var empId = document.getElementById('u_employee_id');
    var inst = document.getElementById('u_institution_id');

    if (!role.value) {
        document.getElementById('err_u_user_role').style.display = 'block';
        valid = false;
    } else { document.getElementById('err_u_user_role').style.display = 'none'; }

    if (!empId.value.trim()) {
        document.getElementById('err_u_employee_id').style.display = 'block';
        valid = false;
    } else { document.getElementById('err_u_employee_id').style.display = 'none'; }

    if (role.value !== 'administrator') {
        if (!inst.value) {
            document.getElementById('err_u_institution_id').style.display = 'block';
            valid = false;
        } else { document.getElementById('err_u_institution_id').style.display = 'none'; }
    }

    return valid;
};

window.eessCheckUniqueness = function(field) {
    var userId = document.getElementById('u_user_id').value;
    var val = '';
    if (field === 'username') val = document.getElementById('u_username').value.trim();
    if (field === 'email') val = document.getElementById('u_user_email').value.trim();
    if (field === 'employee_id') val = document.getElementById('u_employee_id').value.trim();

    if (!val) return;

    var nonceEl = document.querySelector('#eess-unified-user-form [name="sm_nonce"]');
    var nonce = nonceEl ? nonceEl.value : '';

    var formData = new FormData();
    formData.append('action', 'eess_check_user_uniqueness');
    formData.append('sm_nonce', nonce);
    formData.append('field', field);
    formData.append('value', val);
    formData.append('user_id', userId);

    fetch(eessAjaxUrl, { method: 'POST', body: formData })
    .then(function(r) { return r.json(); })
    .then(function(res) {
        if (res.success && res.data.exists) {
            var errEl = document.getElementById('err_u_' + (field === 'email' ? 'user_email' : field));
            if (errEl) {
                errEl.innerText = res.data.message || 'القيمة مُستخدمة سابقاً في النظام.';
                errEl.style.display = 'block';
            }
        }
    })
    .catch(function(e) { console.error(e); });
};

window.eessOnRoleChanged = function() {
    var role = document.getElementById('u_user_role').value;
    var isSysAdmin = (role === 'administrator');

    var instWrapper = document.getElementById('u_inst_wrapper');
    var schoolWrapper = document.getElementById('u_school_wrapper');
    var deptWrapper = document.getElementById('u_department_wrapper');
    var adminSecWrapper = document.getElementById('u_admin_sec_wrapper');
    var subjWrapper = document.getElementById('u_subject_wrapper');
    var gradesWrapper = document.getElementById('u_grades_wrapper');
    var sectionsWrapper = document.getElementById('u_sections_wrapper');

    if (isSysAdmin) {
        if (instWrapper) instWrapper.style.display = 'none';
        if (schoolWrapper) schoolWrapper.style.display = 'none';
        if (deptWrapper) deptWrapper.style.display = 'none';
        if (adminSecWrapper) adminSecWrapper.style.display = 'none';
        if (subjWrapper) subjWrapper.style.display = 'none';
        if (gradesWrapper) gradesWrapper.style.display = 'none';
        if (sectionsWrapper) sectionsWrapper.style.display = 'none';

        var instSel = document.getElementById('u_institution_id');
        if (instSel) instSel.required = false;
    } else {
        if (instWrapper) instWrapper.style.display = 'block';
        if (schoolWrapper) schoolWrapper.style.display = 'block';
        if (deptWrapper) deptWrapper.style.display = 'block';
        if (adminSecWrapper) adminSecWrapper.style.display = 'block';
        if (subjWrapper) subjWrapper.style.display = 'block';
        if (gradesWrapper) gradesWrapper.style.display = 'block';
        if (sectionsWrapper) sectionsWrapper.style.display = 'block';

        var instSel = document.getElementById('u_institution_id');
        if (instSel) instSel.required = true;
    }
};

window.eessOnInstitutionChanged = function() {
    var instId = document.getElementById('u_institution_id').value;
    var schoolSelect = document.getElementById('u_school_id');

    if (!schoolSelect) return;

    for (var i = 0; i < schoolSelect.options.length; i++) {
        var opt = schoolSelect.options[i];
        if (!opt.value) continue;

        var optInst = opt.getAttribute('data-institution');
        if (!instId || optInst === instId) {
            opt.style.display = 'block';
        } else {
            opt.style.display = 'none';
        }
    }
};

window.eessLoadUserData = function(userId) {
    var nonceEl = document.querySelector('#eess-unified-user-form [name="sm_nonce"]');
    var nonce = nonceEl ? nonceEl.value : '';

    var formData = new FormData();
    formData.append('action', 'eess_get_user_unified');
    formData.append('sm_nonce', nonce);
    formData.append('user_id', userId);

    fetch(eessAjaxUrl, { method: 'POST', body: formData })
    .then(function(r) { return r.json(); })
    .then(function(res) {
        if (res.success && res.data) {
            var u = res.data;
            document.getElementById('u_first_name').value = u.first_name || '';
            document.getElementById('u_last_name').value = u.last_name || '';
            var cleanEmpId = (u.employee_id || u.user_login || '').replace(/^(EMP|EMP-|_)+/i, '').trim();
            document.getElementById('u_employee_id').value = cleanEmpId;
            document.getElementById('u_username').value = cleanEmpId;
            document.getElementById('u_user_email').value = u.user_email || '';
            if (u.country_code) {
                var cc = document.getElementById('u_country_code');
                if (cc) cc.value = u.country_code;
            }
            document.getElementById('u_phone_number').value = u.phone_number || '';
            document.getElementById('u_civil_id').value = u.civil_id || '';
            if (document.getElementById('u_dob')) document.getElementById('u_dob').value = u.dob || '';
            if (document.getElementById('u_nationality')) document.getElementById('u_nationality').value = u.nationality || '';
            if (document.getElementById('u_gender') && u.gender) document.getElementById('u_gender').value = u.gender;
            if (document.getElementById('u_address') && u.address) document.getElementById('u_address').value = u.address;
            if (document.getElementById('u_building_info') && u.building_info) document.getElementById('u_building_info').value = u.building_info;
            if (document.getElementById('u_emirate') && u.emirate) document.getElementById('u_emirate').value = u.emirate;
            if (document.getElementById('u_appointment_year')) document.getElementById('u_appointment_year').value = u.appointment_year || new Date().getFullYear();
            if (document.getElementById('u_job_rank')) document.getElementById('u_job_rank').value = u.job_rank || 'teacher';

            var normalizedRole = u.role || 'sm_teacher';
            if (normalizedRole === 'teachers') normalizedRole = 'sm_teacher';
            if (normalizedRole === 'school_manager') normalizedRole = 'sm_principal';
            if (normalizedRole === 'educational_supervisor') normalizedRole = 'sm_supervisor';
            if (normalizedRole === 'clinic') normalizedRole = 'sm_clinic';

            var roleSelect = document.getElementById('u_user_role');
            if (roleSelect) {
                roleSelect.value = normalizedRole;
                var isSystemAdminUser = (typeof eessIsAdmin !== 'undefined' && eessIsAdmin) || (u.can_edit_roles === true);
                if (!isSystemAdminUser) {
                    roleSelect.disabled = true;
                    roleSelect.style.background = '#f8fafc';
                    roleSelect.style.cursor = 'not-allowed';
                } else {
                    roleSelect.disabled = false;
                    roleSelect.style.background = '#ffffff';
                    roleSelect.style.cursor = 'pointer';
                }
            }
            document.getElementById('u_institution_id').value = u.institution_id || '';
            if (document.getElementById('u_school_id') && u.school_id) document.getElementById('u_school_id').value = u.school_id;
            document.getElementById('u_department').value = u.department || '';
            if (document.getElementById('u_admin_section') && u.admin_section) document.getElementById('u_admin_section').value = u.admin_section;
            document.getElementById('u_specialization').value = u.specialization || '';
            if (document.getElementById('u_assigned_sections') && u.assigned_sections) {
                document.getElementById('u_assigned_sections').value = u.assigned_sections;
            }
            if (document.getElementById('u_user_status') && u.user_status) document.getElementById('u_user_status').value = u.user_status;

            // Bind Grade Capsules
            var assignedGrades = Array.isArray(u.assigned_grades) ? u.assigned_grades : [];
            document.querySelectorAll('input[name="assigned_grades[]"]').forEach(function(cb) {
                cb.checked = assignedGrades.includes(cb.value);
                eessToggleGradeCapsule(cb);
            });

            if (u.photo_url) {
                document.getElementById('u_photo_preview').src = u.photo_url;
            }

            eessOnInstitutionChanged();
            eessOnRoleChanged();
        }
    })
    .catch(function(e) { console.error(e); });
};

window.eessSubmitUnifiedUserForm = function() {
    var role = document.getElementById('u_user_role').value;
    var inst = document.getElementById('u_institution_id').value;

    if (!role) {
        document.getElementById('err_u_user_role').style.display = 'block';
        return;
    } else { document.getElementById('err_u_user_role').style.display = 'none'; }

    if (role !== 'administrator' && !inst) {
        document.getElementById('err_u_institution_id').style.display = 'block';
        return;
    } else { document.getElementById('err_u_institution_id').style.display = 'none'; }

    var saveBtn = document.getElementById('u_btn_save');
    saveBtn.innerText = 'جاري الحفظ والتزامن...';
    saveBtn.disabled = true;

    var form = document.getElementById('eess-unified-user-form');
    var formData = new FormData(form);
    formData.append('action', 'eess_save_user_unified');

    fetch(eessAjaxUrl, { method: 'POST', body: formData })
    .then(function(r) { return r.json(); })
    .then(function(res) {
        if (res.success) {
            if (typeof smShowNotification === 'function') {
                smShowNotification('تم حفظ وتزامن بيانات الموظف بنجاح');
            } else {
                alert('تم حفظ وتزامن بيانات الموظف بنجاح');
            }
            eessCloseUnifiedUserModal();

            if (res.data && res.data.user_data) {
                var u = res.data.user_data;
                var fnEl = document.querySelector('.wp-tab-content h2');
                if (fnEl && u.display_name) fnEl.innerText = u.display_name;
            }

            setTimeout(function() { location.reload(); }, 600);
        } else {
            alert('خطأ: ' + (res.data || 'حدث خطأ أثناء حفظ البيانات.'));
            saveBtn.innerText = 'حفظ وتزامن البيانات';
            saveBtn.disabled = false;
        }
    })
    .catch(function(err) {
        alert('حدث خطأ غير متوقع في الاتصال بالسيرفر.');
        saveBtn.innerText = 'حفظ وتزامن البيانات';
        saveBtn.disabled = false;
    });
};
