<?php
if (!defined('ABSPATH')) exit;

$institutions = class_exists('EESS_Org_Helper') ? EESS_Org_Helper::get_institutions() : array();
$all_schools  = class_exists('EESS_Org_Helper') ? EESS_Org_Helper::get_all_schools() : array();
$subjects     = class_exists('SM_Settings') ? SM_Settings::get_subjects() : array();
$departments  = class_exists('SM_Settings') ? SM_Settings::get_departments() : array();
?>

<!-- UNIFIED USER & EMPLOYEE MANAGEMENT MODAL -->
<div id="unified-user-modal" class="sm-modal-overlay" style="display: none; align-items: center; justify-content: center; z-index: 999999; background: rgba(15, 23, 42, 0.75); backdrop-filter: blur(5px); position: fixed; inset: 0; padding: 20px; box-sizing: border-box;">
    <div class="sm-modal-content" style="background: #ffffff; border-radius: 20px; width: 100%; max-width: 980px; max-height: 94vh; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.3); border: 1px solid #cbd5e1; font-family: 'Cairo', sans-serif; box-sizing: border-box; display: flex; flex-direction: column;" dir="rtl">

        <!-- Modal Header -->
        <div style="display: flex; justify-content: space-between; align-items: center; padding: 18px 24px; background: #0f172a; color: #ffffff; border-bottom: 1px solid #1e293b; box-sizing: border-box; width: 100%; margin: 0;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <span id="u_modal_icon" class="dashicons dashicons-admin-users" style="font-size: 24px; width: 24px; height: 24px; color: #ffffff; margin: 0;"></span>
                <h3 id="u_modal_title" style="margin: 0; font-size: 16px; font-weight: 800; color: #ffffff; font-family: 'Cairo', sans-serif;">تعديل بيانات الحساب وتعيينات الموظف</h3>
            </div>
            <button type="button" class="sm-modal-close" onclick="eessCloseUnifiedUserModal()" style="background: transparent; border: none; font-size: 26px; color: #ffffff; cursor: pointer; line-height: 1;">&times;</button>
        </div>

        <!-- 5-Step Indicator Bar -->
        <div style="padding: 14px 20px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; position: relative;">
            <div style="position: absolute; top: 50%; left: 5%; right: 5%; height: 2px; background: #e2e8f0; z-index: 1; transform: translateY(-50%);"></div>
            <div style="display: flex; align-items: center; justify-content: space-between; position: relative; z-index: 2; width: 100%; gap: 6px;">
                <div id="u_indicator_step1" class="u-step-indicator active" style="background: #881337; color: white; padding: 5px 10px; border-radius: 9999px; font-size: 11px; font-weight: 800; display: flex; align-items: center; gap: 5px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <span style="background: rgba(255,255,255,0.2); width: 18px; height: 18px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 10px;">1</span>
                    <span>البيانات الشخصية</span>
                </div>
                <div id="u_indicator_step2" class="u-step-indicator" style="background: #f1f5f9; color: #64748b; padding: 5px 10px; border-radius: 9999px; font-size: 11px; font-weight: 800; display: flex; align-items: center; gap: 5px; border: 1px solid #cbd5e1;">
                    <span style="background: #cbd5e1; color: #334155; width: 18px; height: 18px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 10px;">2</span>
                    <span>التواصل والإقامة</span>
                </div>
                <div id="u_indicator_step3" class="u-step-indicator" style="background: #f1f5f9; color: #64748b; padding: 5px 10px; border-radius: 9999px; font-size: 11px; font-weight: 800; display: flex; align-items: center; gap: 5px; border: 1px solid #cbd5e1;">
                    <span style="background: #cbd5e1; color: #334155; width: 18px; height: 18px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 10px;">3</span>
                    <span>التسكين المهني والأكاديمي</span>
                </div>
                <div id="u_indicator_step4" class="u-step-indicator" style="background: #f1f5f9; color: #64748b; padding: 5px 10px; border-radius: 9999px; font-size: 11px; font-weight: 800; display: flex; align-items: center; gap: 5px; border: 1px solid #cbd5e1;">
                    <span style="background: #cbd5e1; color: #334155; width: 18px; height: 18px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 10px;">4</span>
                    <span>المراجعة والتحقق</span>
                </div>
                <div id="u_indicator_step5" class="u-step-indicator" style="background: #f1f5f9; color: #64748b; padding: 5px 10px; border-radius: 9999px; font-size: 11px; font-weight: 800; display: flex; align-items: center; gap: 5px; border: 1px solid #cbd5e1;">
                    <span style="background: #cbd5e1; color: #334155; width: 18px; height: 18px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 10px;">5</span>
                    <span>أمان الحساب</span>
                </div>
            </div>
        </div>

        <!-- Form Container -->
        <form id="eess-unified-user-form" enctype="multipart/form-data" style="padding: 24px; flex: 1; overflow-y: auto;" onsubmit="return false;">
            <?php wp_nonce_field('sm_user_action', 'sm_nonce'); ?>
            <input type="hidden" name="user_id" id="u_user_id" value="0">
            <input type="hidden" name="form_mode" id="u_form_mode" value="add_user">

            <!-- STEP 1: PERSONAL INFORMATION -->
            <div id="u_step_1_container">
                <div style="background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 16px; padding: 20px; margin-bottom: 20px; text-align: center; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                    <div style="width: 110px; height: 110px; border-radius: 12px; border: 2px solid #cbd5e1; background: #ffffff; overflow: hidden; display: flex; align-items: center; justify-content: center; margin-bottom: 12px; box-shadow: 0 4px 10px rgba(0,0,0,0.05);">
                        <img id="u_photo_preview" src="" alt="صورة الموظف" style="width: 100%; height: 100%; object-fit: cover;" onerror="this.src='data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 24 24\' fill=\'%2394a3b8\'><path d=\'M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z\'/></svg>'">
                    </div>
                    <input type="file" name="profile_photo" id="u_profile_photo" accept="image/*" style="font-size: 12px; margin-bottom: 6px;" onchange="eessPreviewAvatar(this)">
                    <p style="margin: 0; font-size: 12px; font-weight: 700; color: #881337;">يرجى رفع صورة شخصية مربعة واضحة للموظف.</p>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 16px; margin-top: 10px;">
                    <div class="eess-float-group">
                        <input type="text" name="first_name" id="u_first_name" class="sm-input eess-float-input" placeholder=" " required style="height: 44px; border-radius: 12px; border: 1px solid #cbd5e1; padding: 0 14px; font-size: 13px;" oninput="eessValidateField(this)">
                        <label for="u_first_name" class="eess-float-label">الاسم الأول <span style="color:#ef4444;">*</span></label>
                        <span class="eess-field-error" id="err_u_first_name" style="display:none; color:#dc2626; font-size:11px; font-weight:bold; margin-top:2px;">يرجى إدخال الاسم الأول.</span>
                    </div>

                    <div class="eess-float-group">
                        <input type="text" name="last_name" id="u_last_name" class="sm-input eess-float-input" placeholder=" " required style="height: 44px; border-radius: 12px; border: 1px solid #cbd5e1; padding: 0 14px; font-size: 13px;" oninput="eessValidateField(this)">
                        <label for="u_last_name" class="eess-float-label">اسم العائلة / اللقب <span style="color:#ef4444;">*</span></label>
                        <span class="eess-field-error" id="err_u_last_name" style="display:none; color:#dc2626; font-size:11px; font-weight:bold; margin-top:2px;">يرجى إدخال اسم العائلة.</span>
                    </div>

                    <div class="eess-float-group">
                        <input type="date" name="dob" id="u_dob" class="sm-input eess-float-input" required style="height: 44px; border-radius: 12px; border: 1px solid #cbd5e1; padding: 0 14px; font-size: 12.5px;" oninput="eessValidateField(this)">
                        <label for="u_dob" class="eess-float-label">تاريخ الميلاد <span style="color:#ef4444;">*</span></label>
                        <span class="eess-field-error" id="err_u_dob" style="display:none; color:#dc2626; font-size:11px; font-weight:bold; margin-top:2px;">يرجى تحديد تاريخ الميلاد.</span>
                    </div>

                    <div class="eess-float-group">
                        <input type="text" name="nationality" id="u_nationality" class="sm-input eess-float-input" placeholder=" " required style="height: 44px; border-radius: 12px; border: 1px solid #cbd5e1; padding: 0 14px; font-size: 13px;" oninput="eessValidateField(this)">
                        <label for="u_nationality" class="eess-float-label">الجنسية <span style="color:#ef4444;">*</span></label>
                        <span class="eess-field-error" id="err_u_nationality" style="display:none; color:#dc2626; font-size:11px; font-weight:bold; margin-top:2px;">يرجى تحديد الجنسية.</span>
                    </div>

                    <div class="eess-float-group">
                        <select name="gender" id="u_gender" class="sm-select eess-float-input" style="height: 44px; border-radius: 12px; border: 1px solid #cbd5e1; padding: 0 14px; font-size: 13px;">
                            <option value="">-- اختر الجنس --</option>
                            <option value="male">ذكر (Male)</option>
                            <option value="female">أنثى (Female)</option>
                        </select>
                        <label for="u_gender" class="eess-float-label">الجنس</label>
                    </div>

                    <div class="eess-float-group">
                        <input type="text" name="civil_id" id="u_civil_id" class="sm-input eess-float-input" placeholder=" " style="height: 44px; border-radius: 12px; border: 1px solid #cbd5e1; padding: 0 14px; font-size: 13px;">
                        <label for="u_civil_id" class="eess-float-label">الرقم المدني / الهوية الوطنية</label>
                    </div>
                </div>
            </div>

            <!-- STEP 2: CONTACT & RESIDENCE -->
            <div id="u_step_2_container" style="display: none;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 16px; margin-top: 10px;">
                    <div class="eess-float-group" style="grid-column: span 2;">
                        <input type="email" name="user_email" id="u_user_email" class="sm-input eess-float-input" placeholder=" " required style="height: 44px; border-radius: 12px; border: 1px solid #cbd5e1; padding: 0 14px; font-size: 13px; direction: ltr; text-align: right;" onblur="eessCheckUniqueness('email')" oninput="eessValidateField(this)">
                        <label for="u_user_email" class="eess-float-label">البريد الإلكتروني الرسمي <span style="color:#ef4444;">*</span></label>
                        <span class="eess-field-error" id="err_u_user_email" style="display:none; color:#dc2626; font-size:11px; font-weight:bold; margin-top:2px;">يرجى إدخال بريد إلكتروني صحيح.</span>
                    </div>

                    <div id="u_phone_wrapper" style="grid-column: span 2;">
                        <div style="display: flex; gap: 8px; direction: ltr;">
                            <select name="country_code" id="u_country_code" class="sm-select" style="height: 44px; border-radius: 12px; border: 1px solid #cbd5e1; font-size: 12.5px; width: 140px; font-weight: bold;">
                                <option value="+971">🇦🇪 +971 (الإمارات)</option>
                                <option value="+966">🇸🇦 +966 (السعودية)</option>
                                <option value="+965">🇰🇼 +965 (الكويت)</option>
                                <option value="+974">🇶🇦 +974 (قطر)</option>
                                <option value="+973">🇧🇭 +973 (البحرين)</option>
                                <option value="+968">🇴🇲 +968 (عمان)</option>
                            </select>
                            <div class="eess-float-group" style="flex: 1;">
                                <input type="text" name="phone_number" id="u_phone_number" class="sm-input eess-float-input" placeholder=" " required style="height: 44px; border-radius: 12px; border: 1px solid #cbd5e1; padding: 0 14px; font-size: 13px; text-align: left; direction: ltr;" oninput="eessValidateField(this)">
                                <label for="u_phone_number" class="eess-float-label">رقم الجوال الأساسي <span style="color:#ef4444;">*</span></label>
                            </div>
                        </div>
                        <span class="eess-field-error" id="err_u_phone_number" style="display:none; color:#dc2626; font-size:11px; font-weight:bold; margin-top:2px;">يرجى إدخال رقم الجوال الأساسي.</span>
                    </div>

                    <div class="eess-float-group" style="grid-column: span 2;">
                        <input type="text" name="address" id="u_address" class="sm-input eess-float-input" placeholder=" " style="height: 44px; border-radius: 12px; border: 1px solid #cbd5e1; padding: 0 14px; font-size: 13px;">
                        <label for="u_address" class="eess-float-label">عنوان السكن / الشارع</label>
                    </div>

                    <div class="eess-float-group" style="grid-column: span 2;">
                        <input type="text" name="building_info" id="u_building_info" class="sm-input eess-float-input" placeholder=" " style="height: 44px; border-radius: 12px; border: 1px solid #cbd5e1; padding: 0 14px; font-size: 13px;">
                        <label for="u_building_info" class="eess-float-label">بيانات البناية / المجمع السكني / الشقة</label>
                    </div>

                    <div class="eess-float-group">
                        <input type="text" name="country_residence" id="u_country_residence" value="الإمارات العربية المتحدة" readonly class="sm-input eess-float-input" style="height: 44px; border-radius: 12px; border: 1px solid #cbd5e1; padding: 0 14px; font-size: 13px; background: #f8fafc; font-weight: 800; color: #0f172a;">
                        <label for="u_country_residence" class="eess-float-label">دولة الإقامة <span style="color:#ef4444;">*</span></label>
                    </div>

                    <div class="eess-float-group">
                        <select name="emirate" id="u_emirate" class="sm-select eess-float-input" required style="height: 44px; border-radius: 12px; border: 1px solid #cbd5e1; padding: 0 14px; font-size: 13px;" onchange="eessValidateField(this)">
                            <option value="">-- اختر الإمارة --</option>
                            <option value="أبوظبي">أبوظبي (Abu Dhabi)</option>
                            <option value="دبي">دبي (Dubai)</option>
                            <option value="الشارقة">الشارقة (Sharjah)</option>
                            <option value="عجمان">عجمان (Ajman)</option>
                            <option value="أم القيوين">أم القيوين (Umm Al Quwain)</option>
                            <option value="رأس الخيمة">رأس الخيمة (Ras Al Khaimah)</option>
                            <option value="الفجيرة">الفجيرة (Fujairah)</option>
                        </select>
                        <label for="u_emirate" class="eess-float-label">الإمارة <span style="color:#ef4444;">*</span></label>
                        <span class="eess-field-error" id="err_u_emirate" style="display:none; color:#dc2626; font-size:11px; font-weight:bold; margin-top:2px;">يرجى اختيار الإمارة.</span>
                    </div>
                </div>
            </div>

            <!-- STEP 3: PROFESSIONAL & ACADEMIC ASSIGNMENT -->
            <div id="u_step_3_container" style="display: none;">
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px; margin-bottom: 20px;">
                    <h4 style="margin: 0 0 14px 0; font-size: 13.5px; font-weight: 800; color: #0f172a; border-bottom: 1px solid #e2e8f0; padding-bottom: 8px;">التسكين المهني والأكاديمي</h4>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 16px; margin-top: 10px;">
                        <div id="u_empid_wrapper" class="eess-float-group">
                            <input type="text" name="employee_id" id="u_employee_id" class="sm-input eess-float-input" placeholder=" " required style="height: 44px; border-radius: 12px; border: 1px solid #cbd5e1; padding: 0 14px; font-size: 13px; font-weight: bold; direction: ltr; text-align: right;" oninput="eessSyncUsername(this)" onblur="eessCheckUniqueness('employee_id')">
                            <label for="u_employee_id" class="eess-float-label">الرقم الوظيفي (Employee ID) <span style="color:#ef4444;">*</span></label>
                            <input type="hidden" name="username" id="u_username">
                            <span class="eess-field-error" id="err_u_employee_id" style="display:none; color:#dc2626; font-size:11px; font-weight:bold; margin-top:2px;">يرجى إدخال الرقم الوظيفي.</span>
                        </div>

                        <div class="eess-float-group">
                            <select name="user_role" id="u_user_role" class="sm-select eess-float-input" onchange="eessOnRoleChanged()" required style="height: 44px; border-radius: 12px; border: 1px solid #cbd5e1; padding: 0 14px; font-size: 13px; font-weight: bold;">
                                <option value="">-- اختر الرتبة --</option>
                                <option value="sm_teacher">معلم / عضو هيئة تدريس</option>
                                <option value="sm_hod">رئيس قسم (HOD)</option>
                                <option value="sm_principal">مدير مدرسة / القائد التربوي</option>
                                <option value="sm_supervisor">موجه / مشرف تربوي</option>
                                <option value="sm_activities_supervisor">مشرف أنشطة وفعاليات</option>
                                <option value="sm_clinic">طبيب / زائر صحي للمدرسة</option>
                                <option value="administrator">مدير النظام (System Administrator)</option>
                            </select>
                            <label for="u_user_role" class="eess-float-label">الرتبة / المنصب الوظيفي <span style="color:#ef4444;">*</span></label>
                            <span class="eess-field-error" id="err_u_user_role" style="display:none; color:#dc2626; font-size:11px; font-weight:bold; margin-top:2px;">يرجى تحديد الرتبة الوظيفية.</span>
                        </div>

                        <div class="eess-float-group">
                            <input type="number" name="appointment_year" id="u_appointment_year" class="sm-input eess-float-input" placeholder=" " min="1970" max="<?php echo date('Y'); ?>" value="<?php echo date('Y'); ?>" style="height: 44px; border-radius: 12px; border: 1px solid #cbd5e1; padding: 0 14px; font-size: 13px; font-weight: bold;">
                            <label for="u_appointment_year" class="eess-float-label">سنة التعيين (Year of Appointment)</label>
                        </div>

                        <div class="eess-float-group">
                            <select name="job_rank" id="u_job_rank" class="sm-select eess-float-input" style="height: 44px; border-radius: 12px; border: 1px solid #cbd5e1; padding: 0 14px; font-size: 13px; font-weight: bold;">
                                <option value="teacher">معلم / أخصائي</option>
                                <option value="senior_teacher">معلم أول / خبير</option>
                                <option value="coordinator">منسق مادة</option>
                                <option value="supervisor">مشرف / موجه</option>
                                <option value="vice_principal">نائب مدير مدرسة</option>
                                <option value="principal">مدير مدرسة</option>
                                <option value="sys_admin">مدير نظام</option>
                            </select>
                            <label for="u_job_rank" class="eess-float-label">المرتبة الوظيفية (Job Rank)</label>
                        </div>

                        <div id="u_inst_wrapper" class="eess-float-group">
                            <select name="institution_id" id="u_institution_id" class="sm-select eess-float-input" onchange="eessOnInstitutionChanged()" required style="height: 44px; border-radius: 12px; border: 1px solid #cbd5e1; padding: 0 14px; font-size: 13px;">
                                <option value="">-- اختر المؤسسة التابعة --</option>
                                <?php foreach ($institutions as $inst): ?>
                                    <option value="<?php echo esc_attr($inst->id); ?>"><?php echo esc_html($inst->name); ?> (<?php echo esc_html($inst->type ?? 'مؤسسة'); ?>)</option>
                                <?php endforeach; ?>
                            </select>
                            <label for="u_institution_id" class="eess-float-label">المؤسسة / المدرسة <span style="color:#ef4444;">*</span></label>
                            <span class="eess-field-error" id="err_u_institution_id" style="display:none; color:#dc2626; font-size:11px; font-weight:bold; margin-top:2px;">يرجى اختيار المؤسسة.</span>
                        </div>

                        <div id="u_school_wrapper" class="eess-float-group">
                            <select name="school_id" id="u_school_id" class="sm-select eess-float-input" style="height: 44px; border-radius: 12px; border: 1px solid #cbd5e1; padding: 0 14px; font-size: 13px;">
                                <option value="">-- اختر المدرسة بالكامل --</option>
                                <?php foreach ($all_schools as $sch): ?>
                                    <option value="<?php echo esc_attr($sch->id); ?>" data-institution="<?php echo esc_attr($sch->institution_id ?? ''); ?>"><?php echo esc_html($sch->name); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <label for="u_school_id" class="eess-float-label">اسم المدرسة بالكامل</label>
                        </div>

                        <div id="u_department_wrapper" class="eess-float-group">
                            <select name="department" id="u_department" class="sm-select eess-float-input" style="height: 44px; border-radius: 12px; border: 1px solid #cbd5e1; padding: 0 14px; font-size: 13px;">
                                <option value="">-- اختر القسم --</option>
                                <?php foreach ($departments as $dept_key => $dept_lbl): ?>
                                    <option value="<?php echo esc_attr($dept_lbl); ?>"><?php echo esc_html($dept_lbl); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <label for="u_department" class="eess-float-label">القسم / الإدارة</label>
                        </div>

                        <div id="u_admin_sec_wrapper" class="eess-float-group">
                            <input type="text" name="admin_section" id="u_admin_section" class="sm-input eess-float-input" placeholder=" " style="height: 44px; border-radius: 12px; border: 1px solid #cbd5e1; padding: 0 14px; font-size: 13px;">
                            <label for="u_admin_section" class="eess-float-label">الشعبة الإدارية / المسمى الإداري</label>
                        </div>
                    </div>

                    <div id="u_grades_wrapper" style="margin-bottom: 16px;">
                        <label class="sm-label" style="font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px; display: block;">الصفوف الدراسية المسندة (كبسولات متعددة):</label>
                        <div style="display: flex; flex-wrap: wrap; gap: 8px; background: #ffffff; padding: 12px; border-radius: 10px; border: 1px solid #cbd5e1;">
                            <?php for ($g = 1; $g <= 12; $g++): ?>
                                <label class="u-grade-capsule-label" style="display: inline-flex; align-items: center; gap: 6px; padding: 5px 12px; border-radius: 9999px; background: #f1f5f9; border: 1px solid #cbd5e1; font-size: 12px; font-weight: 700; color: #334155; cursor: pointer; user-select: none;">
                                    <input type="checkbox" name="assigned_grades[]" value="الصف <?php echo $g; ?>" onchange="eessToggleGradeCapsule(this)" style="display: none;">
                                    <span>الصف <?php echo $g; ?></span>
                                </label>
                            <?php endfor; ?>
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 10px;">
                        <div id="u_subject_wrapper" class="eess-float-group">
                            <select name="specialization" id="u_specialization" class="sm-select eess-float-input" style="height: 44px; border-radius: 12px; border: 1px solid #cbd5e1; padding: 0 14px; font-size: 13px;">
                                <option value="">-- اختر المادة --</option>
                                <?php foreach ($subjects as $subj_code => $subj_name): ?>
                                    <option value="<?php echo esc_attr($subj_name); ?>"><?php echo esc_html($subj_name); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <label for="u_specialization" class="eess-float-label">المادة / التخصص <span style="color:#ef4444;">*</span></label>
                            <span class="eess-field-error" id="err_u_specialization" style="display:none; color:#dc2626; font-size:11px; font-weight:bold; margin-top:2px;">يرجى اختيار المادة للتخصص.</span>
                        </div>

                        <div id="u_sections_wrapper" class="eess-float-group">
                            <input type="text" name="assigned_sections" id="u_assigned_sections" class="sm-input eess-float-input" placeholder=" " style="height: 44px; border-radius: 12px; border: 1px solid #cbd5e1; padding: 0 14px; font-size: 13px;">
                            <label for="u_assigned_sections" class="eess-float-label">الشعب المسندة (مثال: أ، ب)</label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- STEP 4: DATA REVIEW & VERIFICATION -->
            <div id="u_step_4_container" style="display: none;">
                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 16px; padding: 20px; margin-bottom: 20px;">
                    <h4 style="margin: 0 0 14px 0; font-size: 14px; font-weight: 800; color: #0f172a; border-bottom: 2px solid #e2e8f0; padding-bottom: 8px;">مراجعة والتحقق الشامل من كافة بيانات الموظف</h4>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; font-size: 13px; color: #334155;">
                        <div><strong>الاسم الكامل:</strong> <span id="rev_u_fullname">-</span></div>
                        <div><strong>الجنسية وتاريخ الميلاد:</strong> <span id="rev_u_nat_dob">-</span></div>
                        <div><strong>الجنس والنوع:</strong> <span id="rev_u_gender">-</span></div>
                        <div><strong>البريد الإلكتروني:</strong> <span id="rev_u_email" style="font-family: monospace;">-</span></div>
                        <div><strong>رقم الجوال الأساسي:</strong> <span id="rev_u_phone" style="font-family: monospace;">-</span></div>
                        <div><strong>دولة الإقامة والإمارة:</strong> <span id="rev_u_location">-</span></div>
                        <div><strong>العنوان والبناية:</strong> <span id="rev_u_address_info">-</span></div>
                        <div><strong>الرتبة والرقم الوظيفي:</strong> <span id="rev_u_role_id" style="color: #881337; font-weight: 800;">-</span></div>
                        <div id="rev_u_inst_container"><strong>المؤسسة والتخصص:</strong> <span id="rev_u_inst_subj" style="color: #0284c7; font-weight: 800;">-</span></div>
                        <div id="rev_u_grades_container"><strong>الصفوف والشعب المسندة:</strong> <span id="rev_u_grades">-</span></div>
                    </div>
                </div>
            </div>

            <!-- STEP 5: ACCOUNT SECURITY & PASSWORDS -->
            <div id="u_step_5_container" style="display: none;">
                <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 20px; margin-bottom: 20px;">
                    <h4 style="margin: 0 0 14px 0; font-size: 14px; font-weight: 800; color: #0f172a; border-bottom: 2px solid #e2e8f0; padding-bottom: 8px;">أمان وكلمة مرور الحساب وحالة الوصول</h4>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 10px;">
                        <div class="eess-float-group">
                            <input type="text" id="u_sec_username_display" readonly class="sm-input eess-float-input" style="height: 44px; border-radius: 12px; border: 1px solid #cbd5e1; padding: 0 14px; font-size: 13px; background: #f8fafc; font-weight: bold; direction: ltr; text-align: right;">
                            <label class="eess-float-label">اسم المستخدم (مطابق للرقم الوظيفي)</label>
                        </div>

                        <div class="eess-float-group">
                            <select name="user_status" id="u_user_status" class="sm-select eess-float-input" style="height: 44px; border-radius: 12px; border: 1px solid #cbd5e1; padding: 0 14px; font-size: 13px; font-weight: bold;">
                                <option value="active">نشط بالكامل (Active)</option>
                                <option value="suspended">موقوف مؤقتاً (Suspended)</option>
                                <option value="pending">قيد الانتظار (Pending Approval)</option>
                            </select>
                            <label for="u_user_status" class="eess-float-label">حالة الحساب والوصول <span style="color:#ef4444;">*</span></label>
                        </div>

                        <div class="eess-float-group">
                            <input type="password" name="user_pass" id="u_user_pass" class="sm-input eess-float-input" placeholder=" " style="height: 44px; border-radius: 12px; border: 1px solid #cbd5e1; padding: 0 14px; font-size: 13px;" oninput="eessValidateField(this)">
                            <label for="u_user_pass" class="eess-float-label">كلمة المرور الجديدة</label>
                            <span class="eess-field-error" id="err_u_user_pass" style="display:none; color:#dc2626; font-size:11px; font-weight:bold; margin-top:2px;">كلمة المرور يجب أن لا تقل عن 6 خانات.</span>
                        </div>

                        <div class="eess-float-group">
                            <input type="password" name="user_pass_confirm" id="u_user_pass_confirm" class="sm-input eess-float-input" placeholder=" " style="height: 44px; border-radius: 12px; border: 1px solid #cbd5e1; padding: 0 14px; font-size: 13px;" oninput="eessValidateField(this)">
                            <label for="u_user_pass_confirm" class="eess-float-label">تأكيد كلمة المرور</label>
                            <span class="eess-field-error" id="err_u_user_pass_confirm" style="display:none; color:#dc2626; font-size:11px; font-weight:bold; margin-top:2px;">كلمتا المرور غير متطابقتين.</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer Navigation Controls -->
            <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #e2e8f0; padding-top: 16px; margin-top: 20px;">
                <button type="button" onclick="eessCloseUnifiedUserModal()" class="sm-btn sm-btn-outline" style="height: 40px; padding: 0 20px; font-size: 13px; color: #64748b; border-radius: 9999px !important;">إلغاء</button>
                <div style="display: flex; gap: 10px;">
                    <button type="button" id="u_btn_prev" onclick="eessGoToStep(eessCurrentStep - 1)" class="sm-btn sm-btn-outline" style="height: 40px; padding: 0 22px; font-size: 13px; border-radius: 9999px !important; display: none;">السابق</button>
                    <button type="button" id="u_btn_next" onclick="eessGoToStep(eessCurrentStep + 1)" class="sm-btn" style="height: 40px; padding: 0 24px; font-size: 13px; font-weight: 800; background: #881337; color: white !important; border: none; border-radius: 9999px !important; cursor: pointer;">التالي</button>
                    <button type="button" id="u_btn_save" onclick="eessSubmitUnifiedUserForm()" class="sm-btn" style="height: 40px; padding: 0 28px; font-size: 13px; font-weight: 800; background: #000000; color: white !important; border: none; border-radius: 9999px !important; cursor: pointer; display: none;">حفظ وتزامن البيانات</button>
                </div>
            </div>
        </form>
    </div>
</div>

<style>
.eess-float-group {
    position: relative;
    width: 100%;
}
.eess-float-input {
    width: 100%;
    box-sizing: border-box;
    transition: all 0.2s ease;
}
.eess-float-label {
    position: absolute;
    top: -10px;
    right: 14px;
    background: #ffffff;
    padding: 0 6px;
    font-size: 11px;
    font-weight: 800;
    color: #881337;
    pointer-events: none;
    border-radius: 4px;
    z-index: 5;
    transition: all 0.2s ease;
}
</style>

<script src="<?php echo SM_PLUGIN_URL . 'public/js/unified-user-modal.js'; ?>"></script>
