<?php if (!defined('ABSPATH')) exit; ?>
<div class="sm-violation-modal-container" dir="rtl" style="font-family: 'Cairo', sans-serif !important; background: #ffffff; color: #0f172a; border-radius: 20px; padding: 0; box-sizing: border-box; width: 100%;">

    <!-- Modal Header (Edge-to-Edge Attached) -->
    <div style="background: #0f172a; color: #ffffff; padding: 18px 24px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #1e293b; margin: 0; width: 100%; box-sizing: border-box; border-top-left-radius: 20px; border-top-right-radius: 20px;">
        <div style="display: flex; align-items: center; gap: 10px;">
            <div style="width: 36px; height: 36px; background: rgba(220, 38, 38, 0.2); border: 1px solid rgba(220, 38, 38, 0.4); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #f87171; flex-shrink: 0;">
                <span class="dashicons dashicons-shield-alt" style="font-size: 20px; width: 20px; height: 20px; line-height: 20px;"></span>
            </div>
            <div>
                <h3 style="margin: 0; font-size: 16px; font-weight: 800; color: #ffffff; font-family: 'Cairo', sans-serif !important;">تسجيل مخالفة جديدة</h3>
                <p style="margin: 2px 0 0 0; font-size: 11px; color: #94a3b8; font-weight: 600; font-family: 'Cairo', sans-serif !important;">رصد وتوثيق المخالفات السلوكية للطلاب فردياً وجماعياً</p>
            </div>
        </div>
        <button type="button" onclick="smCloseViolationModal()" style="background: rgba(255, 255, 255, 0.12); border: none; color: #cbd5e1; width: 32px; height: 32px; min-width: 32px; min-height: 32px; max-width: 32px; max-height: 32px; border-radius: 50% !important; aspect-ratio: 1 / 1; font-size: 18px; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; transition: all 0.2s; line-height: 1; padding: 0; box-sizing: border-box;" onmouseover="this.style.background='rgba(239,68,68,0.3)'; this.style.color='#fff';" onmouseout="this.style.background='rgba(255,255,255,0.12)'; this.style.color='#cbd5e1';">&times;</button>
    </div>

    <!-- Wizard Step Progress Header Bar -->
    <div style="background: #f8fafc; border-bottom: 1px solid #e2e8f0; padding: 12px 24px; display: flex; justify-content: space-between; align-items: center; font-family: 'Cairo', sans-serif !important;">
        <div id="vstep-node-1" class="vstep-node active" style="display: flex; align-items: center; gap: 8px; font-size: 12px; font-weight: 800; color: #dc2626;">
            <span style="width: 24px; height: 24px; border-radius: 50%; background: #dc2626; color: #ffffff; display: inline-flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 900;">1</span>
            <span>تحديد الطلاب / الأشخاص</span>
        </div>
        <div style="flex: 1; height: 2px; background: #e2e8f0; margin: 0 12px;" id="vstep-line-1"></div>
        <div id="vstep-node-2" class="vstep-node" style="display: flex; align-items: center; gap: 8px; font-size: 12px; font-weight: 700; color: #94a3b8;">
            <span style="width: 24px; height: 24px; border-radius: 50%; background: #e2e8f0; color: #64748b; display: inline-flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 900;">2</span>
            <span>بيانات ومعلومات المخالفة</span>
        </div>
        <div style="flex: 1; height: 2px; background: #e2e8f0; margin: 0 12px;" id="vstep-line-2"></div>
        <div id="vstep-node-3" class="vstep-node" style="display: flex; align-items: center; gap: 8px; font-size: 12px; font-weight: 700; color: #94a3b8;">
            <span style="width: 24px; height: 24px; border-radius: 50%; background: #e2e8f0; color: #64748b; display: inline-flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 900;">3</span>
            <span>التأكيد والتنفيذ</span>
        </div>
    </div>

    <!-- Form Content Container -->
    <form method="post" id="violation-form" style="padding: 20px 24px; font-family: 'Cairo', sans-serif !important;">
        <?php wp_nonce_field('sm_record_action', 'sm_nonce'); ?>
        <input type="hidden" name="record_id" id="edit_record_id" value="0">
        <input type="hidden" name="student_ids" id="selected_student_ids" value="">

        <!-- ==================== STEP 1: SELECT PERSONS ==================== -->
        <div id="sm-vstep-1" style="display: block;">
            <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 12px; padding: 12px 16px; margin-bottom: 16px; display: flex; align-items: center; gap: 10px;">
                <span class="dashicons dashicons-info" style="color: #2563eb; font-size: 18px; width: 18px; height: 18px;"></span>
                <span style="font-size: 12px; font-weight: 700; color: #1e40af; line-height: 1.5;">ابحث عن الطالب بالاسم أو الكود، أو استخدم الماسح الضوئي لإضافة الطلاب. يمكنك اختيار حتى 30 طالباً للمخالفات الجماعية.</span>
            </div>

            <!-- Search Field & Scanner Button Row -->
            <div style="margin-bottom: 14px; position: relative;">
                <label style="display: block; font-size: 12.5px; font-weight: 800; color: #1e293b; margin-bottom: 6px;">البحث عن الطالب / الشخص: <span style="color:#ef4444;">*</span></label>

                <div style="display: flex; gap: 8px; align-items: center;">
                    <div style="position: relative; flex: 1;">
                        <input type="text" id="student_unified_search" class="sm-input" placeholder="اكتب اسم الطالب أو الكود للبحث المباشر..." autocomplete="off" style="width: 100%; height: 42px; border-radius: 12px; border: 1px solid #cbd5e1; padding: 0 14px; font-size: 13px; font-family: 'Cairo', sans-serif !important; box-sizing: border-box; background: #f8fafc; transition: all 0.2s;" onfocus="this.style.background='#fff'; this.style.borderColor='#2563eb';" onblur="this.style.background='#f8fafc'; this.style.borderColor='#cbd5e1';">

                        <!-- Search Dropdown Results -->
                        <div id="search_results_dropdown" style="display:none; position:absolute; top:100%; left:0; right:0; background:white; border:1px solid #cbd5e1; border-radius:12px; z-index:1000; box-shadow:0 15px 25px -5px rgba(0,0,0,0.15); max-height:220px; overflow-y:auto; margin-top: 4px;">
                        </div>
                    </div>

                    <!-- Camera / Scan Button -->
                    <button id="start-scanner" type="button" class="sm-btn" title="فتح الكاميرا والماسح الضوئي" style="height: 42px; padding: 0 16px; background: #0f172a; color: #ffffff !important; border-radius: 12px; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; font-weight: 800; font-size: 12px; transition: all 0.2s; white-space: nowrap;" onmouseover="this.style.background='#1e293b'" onmouseout="this.style.background='#0f172a'">
                        <span class="dashicons dashicons-camera" style="font-size: 16px; width: 16px; height: 16px;"></span>
                        <span>الماسح الضوئي</span>
                    </button>
                </div>
            </div>

            <!-- QR Reader Container -->
            <div id="reader" style="width: 100%; max-width: 360px; margin: 0 auto 14px auto; display: none; border-radius: 12px; overflow: hidden; border: 2px solid #2563eb; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);"></div>

            <!-- Selected Students Chips Container -->
            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 14px; padding: 14px; margin-bottom: 16px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                    <span style="font-size: 12px; font-weight: 800; color: #334155;">الطلاب المحددون لهذه المخالفة:</span>
                    <span id="selected_count_badge" style="font-size: 11px; font-weight: 800; background: #e0f2fe; color: #0369a1; padding: 2px 10px; border-radius: 9999px; border: 1px solid #bae6fd;">تم تحديد 0 طلاب</span>
                </div>

                <div id="selected_students_container" style="display:flex; flex-wrap:wrap; gap:8px; min-height: 42px; align-items: center;">
                    <span id="empty_selection_notice" style="font-size: 12px; color: #94a3b8; font-weight: 600;">لم يتم اختيار أي طالب حتى الآن. يرجى البحث أعلاه واختيار الطلاب.</span>
                </div>

                <span class="eess-field-error" id="err_student_ids" style="display:none; color:#dc2626; font-size:11.5px; font-weight:800; margin-top:8px;">⚠️ يرجى اختيار طالب واحد على الأقل للمتابعة.</span>
            </div>

            <!-- Step 1 Actions -->
            <div style="display: flex; gap: 10px; justify-content: space-between; align-items: center; border-top: 1px solid #e2e8f0; padding-top: 14px;">
                <button type="button" onclick="smCloseViolationModal()" class="sm-btn" style="height: 40px; padding: 0 18px; font-size: 12.5px; font-weight: 800; background: #f1f5f9; color: #1e293b !important; border: 1px solid #cbd5e1; border-radius: 12px; cursor: pointer;" onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">إلغاء</button>

                <button type="button" id="btn-to-step-2" onclick="smSetViolationStep(2)" class="sm-btn" style="height: 40px; padding: 0 22px; font-weight: 800; font-size: 13px; background: #dc2626; color: white !important; border: none; border-radius: 12px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 4px 12px rgba(220,38,38,0.25);" onmouseover="this.style.background='#b91c1c'" onmouseout="this.style.background='#dc2626'">
                    <span>التالي: إدخال بيانات المخالفة</span>
                    <span>➔</span>
                </button>
            </div>
        </div>

        <!-- ==================== STEP 2: VIOLATION INFORMATION ==================== -->
        <div id="sm-vstep-2" style="display: none;">

            <!-- Selected Students Banner Preview -->
            <div style="background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 12px; padding: 10px 14px; margin-bottom: 16px; display: flex; justify-content: space-between; align-items: center;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span class="dashicons dashicons-groups" style="color: #0f172a; font-size: 18px; width: 18px; height: 18px;"></span>
                    <span style="font-size: 12px; font-weight: 800; color: #0f172a;">المستهدفون بالنقل والتسجيل:</span>
                    <span id="step2_students_summary" style="font-size: 12px; font-weight: 700; color: #2563eb;">---</span>
                </div>
                <button type="button" onclick="smSetViolationStep(1)" style="background: #ffffff; border: 1px solid #cbd5e1; color: #334155; padding: 4px 12px; border-radius: 8px; font-size: 11px; font-weight: 800; cursor: pointer; display: flex; align-items: center; gap: 4px;">
                    <span>تعديل التحديد</span>
                </button>
            </div>

            <!-- Optional Violation Form Fields (NO Mandatory Fields) -->
            <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 16px; margin-bottom: 16px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; border-bottom: 1px solid #f1f5f9; padding-bottom: 6px;">
                    <h4 style="margin: 0; font-size: 13px; font-weight: 800; color: #0f172a;">تصنيف وبيانات الموقف السلوكي (جميع الحقول اختيارية)</h4>
                    <span style="font-size: 11px; color: #64748b; font-weight: 600; background: #f1f5f9; padding: 2px 8px; border-radius: 6px;">اختياري Optional</span>
                </div>

                <!-- Date & Degree Row -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                    <div class="sm-form-group" style="margin: 0;">
                        <label style="display: block; font-size: 11.5px; font-weight: 700; color: #334155; margin-bottom: 4px;">تاريخ المخالفة:</label>
                        <input type="date" name="custom_date" id="violation_custom_date" class="sm-input" value="<?php echo date('Y-m-d'); ?>" style="width: 100%; height: 38px; border-radius: 10px; border: 1px solid #cbd5e1; padding: 0 10px; font-size: 12px; font-family: 'Cairo', sans-serif !important; box-sizing: border-box;">
                    </div>

                    <div class="sm-form-group" style="margin: 0;">
                        <label style="display: block; font-size: 11.5px; font-weight: 700; color: #334155; margin-bottom: 4px;">درجة المخالفة (المستوى):</label>
                        <select name="degree" id="violation_degree" class="sm-select" onchange="updateHierarchicalViolations()" style="width: 100%; height: 38px; border-radius: 10px; border: 1px solid #cbd5e1; padding: 0 10px; font-size: 12px; font-family: 'Cairo', sans-serif !important; box-sizing: border-box;">
                            <option value="1">المستوى الأول (بسيطة)</option>
                            <option value="2">المستوى الثاني (متوسطة)</option>
                            <option value="3">المستوى الثالث (جسيمة)</option>
                            <option value="4">المستوى الرابع (شديدة الخطورة)</option>
                        </select>
                    </div>
                </div>

                <!-- Violation Type / Code -->
                <div class="sm-form-group" style="margin-bottom: 12px;">
                    <label style="display: block; font-size: 11.5px; font-weight: 700; color: #334155; margin-bottom: 4px;">البند القانوني / نوع المخالفة:</label>
                    <select name="violation_code" id="violation_code_select" class="sm-select" onchange="onViolationSelected()" style="width: 100%; height: 38px; border-radius: 10px; border: 1px solid #cbd5e1; padding: 0 10px; font-size: 12px; font-family: 'Cairo', sans-serif !important; box-sizing: border-box;">
                        <option value="">-- اختر البند القانوني (اختياري) --</option>
                    </select>
                </div>

                <!-- Location, Points, Severity Grid -->
                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px; margin-bottom: 12px;">
                    <div class="sm-form-group" style="margin: 0;">
                        <label style="display: block; font-size: 11.5px; font-weight: 700; color: #334155; margin-bottom: 4px;">موقع الموقف:</label>
                        <select name="classification" id="violation_classification" class="sm-select" style="width: 100%; height: 38px; border-radius: 10px; border: 1px solid #cbd5e1; padding: 0 10px; font-size: 12px; font-family: 'Cairo', sans-serif !important; box-sizing: border-box;">
                            <option value="general">عام</option>
                            <option value="inside_class">داخل الفصل الدراسي</option>
                            <option value="yard">في الساحة / الطابور</option>
                            <option value="labs">في المختبرات والمرافق</option>
                            <option value="bus">في الحافلة المدرسية</option>
                        </select>
                    </div>

                    <div class="sm-form-group" style="margin: 0;">
                        <label style="display: block; font-size: 11.5px; font-weight: 700; color: #334155; margin-bottom: 4px;">النقاط المستحقة:</label>
                        <input type="number" name="points" id="violation_points" class="sm-input" value="0" min="0" style="width: 100%; height: 38px; border-radius: 10px; border: 1px solid #cbd5e1; padding: 0 10px; font-size: 12px; font-family: 'Cairo', sans-serif !important; box-sizing: border-box;">
                    </div>

                    <div class="sm-form-group" style="margin: 0;">
                        <label style="display: block; font-size: 11.5px; font-weight: 700; color: #334155; margin-bottom: 4px;">الحدة التقديرية:</label>
                        <select name="severity" id="violation_severity" class="sm-select" style="width: 100%; height: 38px; border-radius: 10px; border: 1px solid #cbd5e1; padding: 0 10px; font-size: 12px; font-family: 'Cairo', sans-serif !important; box-sizing: border-box;">
                            <option value="low">منخفضة</option>
                            <option value="medium">متوسطة</option>
                            <option value="high">عالية</option>
                        </select>
                    </div>

                    <input type="hidden" name="type" id="hidden_violation_type" value="مخالفة سلوكية">
                </div>

                <!-- Action Taken -->
                <div class="sm-form-group" style="margin-bottom: 12px;">
                    <label style="display: block; font-size: 11.5px; font-weight: 700; color: #334155; margin-bottom: 4px;">الإجراء المتخذ:</label>
                    <select name="action_taken" id="action_taken" class="sm-select" style="width: 100%; height: 38px; border-radius: 10px; border: 1px solid #cbd5e1; padding: 0 10px; font-size: 12px; font-family: 'Cairo', sans-serif !important; box-sizing: border-box;">
                        <option value="تسجيل ملاحظة">تسجيل ملاحظة</option>
                        <?php foreach (SM_Settings::get_disciplinary_actions() as $level => $act): ?>
                            <option value="<?php echo esc_attr($act); ?>" data-level="<?php echo $level; ?>"><?php echo $level . '. ' . esc_html($act); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Details / Description -->
                <div class="sm-form-group" style="margin-bottom: 12px;">
                    <label style="display: block; font-size: 11.5px; font-weight: 700; color: #334155; margin-bottom: 4px;">شرح وتفاصيل الموقف السلوكي:</label>
                    <textarea name="details" id="violation_details" class="sm-input" placeholder="اشرح تفاصيل الموقف السلوكي إن وجدت..." style="width: 100%; height: 60px; border-radius: 10px; border: 1px solid #cbd5e1; padding: 8px 10px; font-size: 12px; font-family: 'Cairo', sans-serif !important; box-sizing: border-box; resize: vertical;"></textarea>
                </div>

                <!-- Evidence Link -->
                <div class="sm-form-group" style="margin-bottom: 0;">
                    <label style="display: block; font-size: 11.5px; font-weight: 700; color: #334155; margin-bottom: 4px;">إرفاق مستند أو دليل مؤيد (اختياري):</label>
                    <input type="text" name="reward_penalty" id="violation_evidence_link" class="sm-input" placeholder="أدخل رابط المستند أو ملاحظة إضافية..." style="width: 100%; height: 36px; border-radius: 10px; border: 1px solid #cbd5e1; padding: 0 10px; font-size: 12px; font-family: 'Cairo', sans-serif !important; box-sizing: border-box;">
                </div>
            </div>

            <!-- Step 2 Actions -->
            <div style="display: flex; gap: 10px; justify-content: space-between; align-items: center; border-top: 1px solid #e2e8f0; padding-top: 14px;">
                <button type="button" onclick="smSetViolationStep(1)" class="sm-btn" style="height: 40px; padding: 0 18px; font-size: 12.5px; font-weight: 700; background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; border-radius: 12px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
                    <span>←</span>
                    <span>السابق: تحديد الطلاب</span>
                </button>

                <button type="submit" id="submit-btn" class="sm-btn" style="height: 40px; padding: 0 24px; font-weight: 800; font-size: 13px; background: #000000; color: white !important; border: none; border-radius: 12px; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.2); transition: all 0.2s;" onmouseover="this.style.background='#1f2937'" onmouseout="this.style.background='#000000'">
                    <span class="dashicons dashicons-saved" style="font-size: 16px; width: 16px; height: 16px;"></span>
                    <span>حفظ وتسجيل المخالفة الآن</span>
                </button>
            </div>
        </div>

        <!-- ==================== STEP 3: CONFIRMATION / SUCCESS STATE ==================== -->
        <div id="sm-vstep-3" style="display: none; text-align: center; padding: 30px 10px;">
            <div style="width: 72px; height: 72px; background: #dcfce7; border: 2px solid #86efac; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; color: #16a34a; margin-bottom: 16px; box-shadow: 0 10px 20px -5px rgba(22, 163, 74, 0.25);">
                <span class="dashicons dashicons-yes" style="font-size: 40px; width: 40px; height: 40px; line-height: 40px;"></span>
            </div>

            <h3 style="margin: 0 0 8px 0; font-size: 20px; font-weight: 900; color: #0f172a; font-family: 'Cairo', sans-serif !important;">تم تسجيل المخالفة بنجاح</h3>

            <p id="sm-step3-msg" style="margin: 0 0 20px 0; font-size: 13.5px; font-weight: 700; color: #475569; line-height: 1.6; font-family: 'Cairo', sans-serif !important;">
                تم تسجيل واستيعاب المخالفة بنجاح في المنظومة للطلاب المحددين.
            </p>

            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 12px; display: inline-block; max-width: 380px; width: 100%; margin-bottom: 20px;">
                <div style="font-size: 12px; font-weight: 700; color: #64748b; font-family: 'Cairo', sans-serif !important;">
                    سيتم إغلاق النافذة وتحديث السجلات تلقائياً خلال <strong id="sm-close-timer" style="color: #dc2626; font-size: 15px;">3</strong> ثوان...
                </div>
            </div>

            <div>
                <button type="button" onclick="smCloseViolationModalNow()" class="sm-btn" style="height: 38px; padding: 0 20px; font-size: 12.5px; font-weight: 800; background: #0f172a; color: #ffffff !important; border-radius: 10px; border: none; cursor: pointer;">
                    إغلاق النافذة الآن
                </button>
            </div>
        </div>

    </form>
</div>

<script>
const hViolations = <?php echo json_encode(SM_Settings::get_hierarchical_violations()); ?>;

function updateHierarchicalViolations() {
    const degreeEl = document.getElementById('violation_degree');
    if (!degreeEl) return;
    const degree = degreeEl.value;
    const select = document.getElementById('violation_code_select');
    if (!select) return;

    select.innerHTML = '<option value="">-- اختر البند القانوني (اختياري) --</option>';
    if (!degree || !hViolations[degree]) {
        return;
    }

    Object.keys(hViolations[degree]).forEach(code => {
        const v = hViolations[degree][code];
        const opt = document.createElement('option');
        opt.value = code;
        opt.innerText = code + ' - ' + v.name;
        select.appendChild(opt);
    });
}

function onViolationSelected() {
    const degreeEl = document.getElementById('violation_degree');
    const selectEl = document.getElementById('violation_code_select');
    if (!degreeEl || !selectEl) return;
    const degree = degreeEl.value;
    const code = selectEl.value;

    if (!degree || !code || !hViolations[degree] || !hViolations[degree][code]) return;

    const v = hViolations[degree][code];
    const pointsEl = document.getElementById('violation_points');
    if (pointsEl) pointsEl.value = v.points || 0;

    const actionSelect = document.getElementById('action_taken');
    if (actionSelect && v.action) {
        for (let i = 0; i < actionSelect.options.length; i++) {
            if (actionSelect.options[i].value === v.action) {
                actionSelect.selectedIndex = i;
                break;
            }
        }
    }

    const typeHidden = document.getElementById('hidden_violation_type');
    if (typeHidden) typeHidden.value = v.name || 'مخالفة سلوكية';

    const sev = document.getElementById('violation_severity');
    if (sev) {
        if (degree == 1) sev.value = 'low';
        else if (degree == 2) sev.value = 'medium';
        else sev.value = 'high';
    }
}

// Global Selected Students State
window.selectedStudents = [];
let smAutoCloseTimerInterval = null;

// Initialize Options on load
document.addEventListener('DOMContentLoaded', function() {
    if (typeof updateHierarchicalViolations === 'function') {
        updateHierarchicalViolations();
    }
});

// Step State Controller
window.smSetViolationStep = function(step) {
    if (step === 2 && window.selectedStudents.length === 0) {
        const errEl = document.getElementById('err_student_ids');
        if (errEl) errEl.style.display = 'block';
        return;
    }

    const errEl = document.getElementById('err_student_ids');
    if (errEl) errEl.style.display = 'none';

    document.getElementById('sm-vstep-1').style.display = (step === 1) ? 'block' : 'none';
    document.getElementById('sm-vstep-2').style.display = (step === 2) ? 'block' : 'none';
    document.getElementById('sm-vstep-3').style.display = (step === 3) ? 'block' : 'none';

    // Step Progress Node Styles
    const n1 = document.getElementById('vstep-node-1');
    const n2 = document.getElementById('vstep-node-2');
    const n3 = document.getElementById('vstep-node-3');

    if (step === 1) {
        n1.style.color = '#dc2626';
        n1.querySelector('span').style.background = '#dc2626';
        n1.querySelector('span').style.color = '#ffffff';

        n2.style.color = '#94a3b8';
        n2.querySelector('span').style.background = '#e2e8f0';
        n2.querySelector('span').style.color = '#64748b';

        n3.style.color = '#94a3b8';
        n3.querySelector('span').style.background = '#e2e8f0';
        n3.querySelector('span').style.color = '#64748b';
    } else if (step === 2) {
        n1.style.color = '#16a34a';
        n1.querySelector('span').style.background = '#16a34a';
        n1.querySelector('span').style.color = '#ffffff';

        n2.style.color = '#dc2626';
        n2.querySelector('span').style.background = '#dc2626';
        n2.querySelector('span').style.color = '#ffffff';

        n3.style.color = '#94a3b8';
        n3.querySelector('span').style.background = '#e2e8f0';
        n3.querySelector('span').style.color = '#64748b';

        // Update Step 2 preview summary text
        const summaryText = window.selectedStudents.map(s => s.name).join('، ');
        const summaryEl = document.getElementById('step2_students_summary');
        if (summaryEl) {
            summaryEl.innerText = `${summaryText} (${window.selectedStudents.length} طلاب)`;
        }
    } else if (step === 3) {
        n1.style.color = '#16a34a';
        n1.querySelector('span').style.background = '#16a34a';
        n1.querySelector('span').style.color = '#ffffff';

        n2.style.color = '#16a34a';
        n2.querySelector('span').style.background = '#16a34a';
        n2.querySelector('span').style.color = '#ffffff';

        n3.style.color = '#16a34a';
        n3.querySelector('span').style.background = '#16a34a';
        n3.querySelector('span').style.color = '#ffffff';
    }
};

// Student Selection & Chip Functions
window.selectStudent = function(s) {
    if (!s || !s.id) return;

    // Prevent duplicate selection
    if (window.selectedStudents.some(x => parseInt(x.id) === parseInt(s.id))) {
        return;
    }

    // Limit up to 30 students
    if (window.selectedStudents.length >= 30) {
        alert('يمكن اختيار حتى 30 طالباً فقط في العملية الواحدة.');
        return;
    }

    window.selectedStudents.push(s);
    renderSelectedStudents();

    const searchInput = document.getElementById('student_unified_search');
    if (searchInput) searchInput.value = '';
    const dropdown = document.getElementById('search_results_dropdown');
    if (dropdown) dropdown.style.display = 'none';
};

window.removeStudent = function(id) {
    window.selectedStudents = window.selectedStudents.filter(x => parseInt(x.id) !== parseInt(id));
    renderSelectedStudents();
};

function renderSelectedStudents() {
    const container = document.getElementById('selected_students_container');
    const badge = document.getElementById('selected_count_badge');
    const idsInput = document.getElementById('selected_student_ids');
    const errEl = document.getElementById('err_student_ids');

    if (!container) return;
    container.innerHTML = '';

    if (window.selectedStudents.length === 0) {
        container.innerHTML = '<span id="empty_selection_notice" style="font-size: 12px; color: #94a3b8; font-weight: 600;">لم يتم اختيار أي طالب حتى الآن. يرجى البحث أعلاه واختيار الطلاب.</span>';
        if (badge) badge.innerText = 'تم تحديد 0 طلاب';
        if (idsInput) idsInput.value = '';
        return;
    }

    if (errEl) errEl.style.display = 'none';

    const ids = [];
    window.selectedStudents.forEach(s => {
        ids.push(s.id);

        const chip = document.createElement('div');
        chip.className = 'student-chip-item';
        chip.innerHTML = `
            <span>${s.name} <small style="opacity: 0.8; font-size: 10px;">(${s.class_name || ''} ${s.section || ''})</small></span>
            <span class="student-chip-remove" title="إزالة الطالب" onclick="removeStudent(${s.id})">&times;</span>
        `;
        container.appendChild(chip);
    });

    if (idsInput) idsInput.value = ids.join(',');
    if (badge) badge.innerText = `تم تحديد ${window.selectedStudents.length} طلاب`;
}

// Live Search Listener
(function() {
    // Inject custom inline style to strictly guarantee Cairo font
    const styleEl = document.createElement('style');
    styleEl.innerHTML = `
        .sm-violation-modal-container,
        .sm-violation-modal-container *,
        #sm-global-violation-modal,
        #sm-global-violation-modal * {
            font-family: 'Cairo', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
        }
        .vstep-node { transition: all 0.25s ease; }
        .sm-search-result-item:hover { background-color: #f1f5f9 !important; }
        .student-chip-item {
            background: #f0f7ff;
            border: 1px solid #bae6fd;
            color: #0369a1;
            padding: 6px 12px;
            border-radius: 9999px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
            font-weight: 700;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
            transition: all 0.2s;
        }
        .student-chip-item:hover {
            border-color: #38bdf8;
            background: #e0f2fe;
        }
        .student-chip-remove {
            cursor: pointer;
            color: #ef4444;
            font-weight: bold;
            font-size: 13px;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(239, 68, 68, 0.1);
            transition: all 0.15s;
        }
        .student-chip-remove:hover {
            background: #ef4444;
            color: #ffffff;
        }
    `;
    document.head.appendChild(styleEl);

    let searchTimer = null;

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
            const query = this.value.trim();
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
                    const dropdown = document.getElementById('search_results_dropdown');
                    if (!dropdown) return;
                    dropdown.innerHTML = '';

                    if (res.success && res.data && res.data.length > 0) {
                        res.data.forEach(s => {
                            const item = document.createElement('div');
                            item.className = 'sm-search-result-item';
                            item.style = 'padding:10px 14px; border-bottom:1px solid #f1f5f9; cursor:pointer; display:flex; align-items:center; gap:10px; transition: background 0.2s;';
                            item.innerHTML = `
                                ${s.photo_url ? `<img src="${s.photo_url}" style="width:30px; height:30px; border-radius:50%; object-fit:cover;">` : '<span class="dashicons dashicons-admin-users" style="color:#64748b; font-size:20px; width:20px; height:20px;"></span>'}
                                <div>
                                    <div style="font-weight:800; font-size:12.5px; color:#0f172a;">${s.name}</div>
                                    <div style="font-size:10.5px; color:#64748b;">كود: ${s.student_code || s.id} | فصل: ${s.class_name || ''} ${s.section || ''}</div>
                                </div>
                            `;
                            item.onclick = function() { selectStudent(s); };
                            dropdown.appendChild(item);
                        });
                        dropdown.style.display = 'block';
                    } else {
                        dropdown.innerHTML = '<div style="padding:12px; color:#94a3b8; text-align:center; font-size:12px; font-weight:700;">لم يتم العثور على نتائج للبحث.</div>';
                        dropdown.style.display = 'block';
                    }
                })
                .catch(() => {});
            }, 300);
        });
    }

    // Camera / Scanner Integration
    const scannerBtn = document.getElementById('start-scanner');
    if (scannerBtn) {
        scannerBtn.addEventListener('click', function() {
            const reader = document.getElementById('reader');
            if (!reader) return;

            if (reader.style.display === 'block') {
                reader.style.display = 'none';
                return;
            }

            reader.style.display = 'block';
            if (typeof Html5Qrcode !== 'undefined') {
                const html5QrCode = new Html5Qrcode("reader");
                html5QrCode.start({ facingMode: "environment" }, { fps: 15, qrbox: 250 }, onScanSuccess)
                .catch(err => {
                    alert('تعذر الوصول للكاميرا: ' + err);
                    reader.style.display = 'none';
                });

                function onScanSuccess(decodedText) {
                    html5QrCode.stop().then(() => {
                        reader.style.display = 'none';

                        const formData = new FormData();
                        formData.append('action', 'sm_get_student');
                        formData.append('code', decodedText);

                        fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
                        .then(r => r.json())
                        .then(res => {
                            if (res.success && res.data) {
                                selectStudent(res.data);
                            } else {
                                alert('عذراً، كود غير معروف أو طالب غير مسجل: ' + decodedText);
                            }
                        });
                    }).catch(() => { reader.style.display = 'none'; });
                }
            } else {
                alert('مكتبة الماسح الضوئي غير محملة بالجلسة.');
                reader.style.display = 'none';
            }
        });
    }

    // Form Submission AJAX Handler
    const vForm = document.getElementById('violation-form');
    if (vForm) {
        vForm.addEventListener('submit', function(e) {
            e.preventDefault();

            if (window.selectedStudents.length === 0) {
                smSetViolationStep(1);
                return;
            }

            const btn = document.getElementById('submit-btn');
            if (btn) {
                btn.innerText = 'جاري حفظ المخالفة... ⏳';
                btn.disabled = true;
            }

            const formData = new FormData(this);
            formData.append('action', 'sm_save_record_ajax');

            fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(res => {
                if (btn) {
                    btn.innerText = 'حفظ وتسجيل المخالفة الآن';
                    btn.disabled = false;
                }

                if (res.success) {
                    const count = window.selectedStudents.length;
                    const msgEl = document.getElementById('sm-step3-msg');
                    if (msgEl) {
                        if (count === 1) {
                            msgEl.innerText = `تم تسجيل واستيعاب المخالفة بنجاح في المنظومة للطالب (${window.selectedStudents[0].name}).`;
                        } else {
                            msgEl.innerText = `تم تسجيل واستيعاب سجلات المخالفة بنجاح في المنظومة لـ (${count}) طلاب محددين.`;
                        }
                    }

                    // Move to Step 3 Confirmation State
                    smSetViolationStep(3);

                    // Trigger Dynamic UI Refresh if available
                    if (typeof smFilterViolations === 'function') {
                        smFilterViolations();
                    } else if (typeof fetchViolations === 'function') {
                        fetchViolations();
                    }
                    document.dispatchEvent(new CustomEvent('smViolationRecorded', { detail: res }));

                    // Start 3-second Auto Close Timer
                    let secondsLeft = 3;
                    const timerEl = document.getElementById('sm-close-timer');
                    if (timerEl) timerEl.innerText = secondsLeft;

                    clearInterval(smAutoCloseTimerInterval);
                    smAutoCloseTimerInterval = setInterval(() => {
                        secondsLeft--;
                        if (timerEl) timerEl.innerText = secondsLeft;
                        if (secondsLeft <= 0) {
                            clearInterval(smAutoCloseTimerInterval);
                            smCloseViolationModalNow();
                        }
                    }, 1000);
                } else {
                    alert('خطأ: ' + (res.data || 'فشل في حفظ سجل المخالفة.'));
                }
            })
            .catch(err => {
                if (btn) {
                    btn.innerText = 'حفظ وتسجيل المخالفة الآن';
                    btn.disabled = false;
                }
                alert('حدث خطأ أثناء التواصل مع الخادم.');
            });
        });
    }

    // Modal Close Handler
    window.smCloseViolationModalNow = function() {
        clearInterval(smAutoCloseTimerInterval);

        // Reset state
        window.selectedStudents = [];
        renderSelectedStudents();

        const form = document.getElementById('violation-form');
        if (form) form.reset();

        smSetViolationStep(1);

        const modal = document.getElementById('sm-global-violation-modal');
        if (modal) modal.style.display = 'none';
    };

    window.smCloseViolationModal = function() {
        window.smCloseViolationModalNow();
    };

    window.smOpenViolationModal = function() {
        smSetViolationStep(1);
        const modal = document.getElementById('sm-global-violation-modal');
        if (modal) modal.style.display = 'flex';
    };

})();
</script>
