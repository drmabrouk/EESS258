<?php if (!defined('ABSPATH')) exit; ?>

<!-- Add Grade Modal -->
<div id="add-grade-modal" class="sm-modal-overlay" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 99999; justify-content: center; align-items: center; padding: 20px; backdrop-filter: blur(2px);">
    <div style="background: #fff; width: 100%; max-width: 500px; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); overflow: hidden; display: flex; flex-direction: column;">
        <div style="background: #1e293b; color: white; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0; font-size: 1.1rem; font-weight: 800; font-family: 'Cairo', sans-serif;">إضافة نتيجة أكاديمية فردية</h3>
            <button type="button" onclick="document.getElementById('add-grade-modal').style.display='none'" style="background: none; border: none; color: white; font-size: 24px; cursor: pointer; line-height: 1;">&times;</button>
        </div>
        <div style="padding: 20px; font-family: 'Cairo', sans-serif; text-align: right;">
            <div class="sm-form-group" style="margin-bottom:15px;">
                <label class="sm-label">اختر الطالب:</label>
                <select id="modal-grade-student-id" class="sm-select" style="width:100%; height:40px; border-radius:8px; font-family:'Cairo'; font-size:13px;">
                    <option value="">-- اختر طالب --</option>
                    <?php foreach ($students as $s): ?>
                        <option value="<?php echo $s->id; ?>"><?php echo esc_html($s->name); ?> (<?php echo $s->class_name; ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="sm-form-group" style="margin-bottom:15px;">
                <label class="sm-label">المادة:</label>
                <select id="modal-grade-subject" class="sm-select" style="width:100%; height:40px; border-radius:8px; font-family:'Cairo'; font-size:13px;">
                    <option value="">-- اختر المادة --</option>
                    <?php
                    $unique_subjects_modal = array_unique(array_column(SM_DB::get_subjects(), 'name'));
                    foreach ($unique_subjects_modal as $subname) echo '<option value="'.$subname.'">'.$subname.'</option>';
                    ?>
                </select>
            </div>
            <div class="sm-form-group" style="margin-bottom:15px;">
                <label class="sm-label">الفصل:</label>
                <select id="modal-grade-term" class="sm-select" style="width:100%; height:40px; border-radius:8px; font-family:'Cairo'; font-size:13px;">
                    <option value="الفصل الأول">الفصل الأول</option>
                    <option value="الفصل الثاني">الفصل الثاني</option>
                    <option value="الفصل الثالث">الفصل الثالث</option>
                </select>
            </div>
            <div class="sm-form-group" style="margin-bottom:15px;">
                <label class="sm-label">الدرجة:</label>
                <input type="text" id="modal-grade-val" class="sm-input" placeholder="100/95" style="width:100%; height:40px; border-radius:8px; font-family:'Cairo'; font-size:13px;">
            </div>
            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px;">
                <button type="button" onclick="document.getElementById('add-grade-modal').style.display='none'" class="sm-btn sm-btn-outline" style="background:#f1f5f9; color:#475569; border-color:#cbd5e1; border-radius:8px; height:38px; cursor:pointer;">إلغاء</button>
                <button onclick="saveStudentGradeModal()" class="sm-btn" style="height: 38px; background: #000; border-color:#000; color:white !important; border-radius:8px; cursor:pointer;">رصد الدرجة</button>
            </div>
        </div>
    </div>
</div>

<!-- Excel Grades Import Modal -->
<div id="eess-grades-import-modal" class="sm-modal-overlay" style="display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 99999; justify-content: center; align-items: center; padding: 20px; backdrop-filter: blur(2px);">
    <div style="background: #fff; width: 100%; max-width: 650px; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); overflow: hidden; display: flex; flex-direction: column; max-height: 90vh;">
        <div style="background: #1e293b; color: white; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0; font-size: 1.1rem; font-weight: 800; font-family: 'Cairo', sans-serif;">استيراد الدرجات والنتائج الأكاديمية (Excel/CSV)</h3>
            <button type="button" onclick="document.getElementById('eess-grades-import-modal').style.display='none'" style="background: none; border: none; color: white; font-size: 24px; cursor: pointer; line-height: 1;">&times;</button>
        </div>
        <div style="padding: 20px; overflow-y: auto; flex: 1; font-family: 'Cairo', sans-serif; text-align: right;">
            <p style="font-size: 12px; color: #475569; margin-top:0;">يرجى اختيار ملف CSV يحتوي على درجات الطلاب. يدعم النظام التحليل التلقائي للأعمدة والمطابقة الذكية لبيانات الطلاب.</p>

            <div style="background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid #cbd5e1; margin-bottom: 20px;">
                <label style="display: block; font-weight: bold; font-size: 12px; color: #1e293b; margin-bottom: 8px;">اختر ملف النتائج (CSV):</label>
                <input type="file" id="eess-grades-file-input" accept=".csv" style="display: block; font-size: 13px; font-family:'Cairo';">
            </div>

            <!-- Preview and Column Mapping Section -->
            <div id="eess-grades-import-preview-section" style="display: none;">
                <h4 style="margin: 0 0 10px 0; font-size: 13px; font-weight: 800; color: #1e293b;">📊 معاينة البيانات والمطابقة التلقائية</h4>
                <div style="max-height: 250px; overflow-y: auto; border: 1px solid #e2e8f0; border-radius: 8px; margin-bottom: 20px;">
                    <table class="sm-table" style="margin: 0; width: 100%;" id="eess-grades-preview-table">
                        <thead>
                            <tr>
                                <th style="text-align: right; padding-right: 15px;">كود الطالب / الاسم</th>
                                <th>المادة</th>
                                <th>الفصل</th>
                                <th>الدرجة</th>
                                <th>الحالة</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px;">
                <button type="button" onclick="document.getElementById('eess-grades-import-modal').style.display='none'" class="sm-btn sm-btn-outline" style="background:#f1f5f9; color:#475569; border-color:#cbd5e1; border-radius:8px; height:38px; cursor:pointer;">إلغاء</button>
                <button type="button" id="eess-grades-confirm-import-btn" class="sm-btn" style="height: 38px; background: #15803d; border-color:#15803d; color:white !important; border-radius:8px; display: none; cursor:pointer;" onclick="eessConfirmGradesImport()">تأكيد واستيراد النتائج</button>
            </div>
        </div>
    </div>
</div>
