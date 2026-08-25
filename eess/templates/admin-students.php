<?php if (!defined('ABSPATH')) exit; ?>
<?php
$is_admin = current_user_can('شؤون_الطلاب') || current_user_can('manage_options');
$import_results = get_transient('sm_import_results_' . get_current_user_id());
if ($import_results) {
    delete_transient('sm_import_results_' . get_current_user_id());
}

// Query parameters & Pagination for Student Affairs (10 per page default)
$paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$limit = isset($_GET['limit']) ? max(1, intval($_GET['limit'])) : 10;
$search = isset($_GET['student_search']) ? sanitize_text_field($_GET['student_search']) : '';
$class_filter = isset($_GET['class_filter']) ? sanitize_text_field($_GET['class_filter']) : '';
$section_filter = isset($_GET['section_filter']) ? sanitize_text_field($_GET['section_filter']) : '';
$teacher_filter = isset($_GET['teacher_filter']) ? intval($_GET['teacher_filter']) : 0;

global $wpdb;
$students_list = is_array($students) ? $students : array();
$total_students_count = count($students_list);

$total_pages = max(1, ceil($total_students_count / $limit));
if ($paged > $total_pages) $paged = $total_pages;
$offset = ($paged - 1) * $limit;

// Paginated slice of students
$paginated_students = array_slice($students_list, $offset, $limit);
$from_num = $total_students_count > 0 ? $offset + 1 : 0;
$to_num = min($offset + $limit, $total_students_count);
?>
<div class="sm-content-wrapper" dir="rtl" style="font-family: system-ui, -apple-system, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif !important; color: #1e293b;">

    <?php if ($import_results): ?>
        <div style="background: #fff; border-radius: 16px; border: 1px solid #e2e8f0; margin-bottom: 24px; overflow: hidden; box-shadow: 0 4px 18px rgba(0,0,0,0.02);">
            <div style="background: #f8fafc; padding: 16px 24px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center;">
                <h4 style="margin:0; color: #0f172a; font-weight: 800; font-size: 15px;">تقرير استيراد الطلاب الأخير</h4>
                <span style="font-size: 12px; color: #64748b; font-weight: 700;">إجمالي السجلات المعالجة: <?php echo $import_results['total']; ?></span>
            </div>
            <div style="padding: 24px;">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 15px; margin-bottom: 20px;">
                    <div style="background: #f0fff4; padding: 14px; border-radius: 12px; border: 1px solid #c6f6d5; text-align: center;">
                        <div style="font-size: 22px; font-weight: 800; color: #2f855a;"><?php echo $import_results['success'] - ($import_results['duplicate'] ?? 0); ?></div>
                        <div style="font-size: 11.5px; color: #38a169; font-weight: 700;">سجلات جديدة</div>
                    </div>
                    <div style="background: #e6fffa; padding: 14px; border-radius: 12px; border: 1px solid #b2f5ea; text-align: center;">
                        <div style="font-size: 22px; font-weight: 800; color: #2c7a7b;"><?php echo $import_results['generated'] ?? 0; ?></div>
                        <div style="font-size: 11.5px; color: #319795; font-weight: 700;">أكواد تم توليدها</div>
                    </div>
                    <div style="background: #ebf8ff; padding: 14px; border-radius: 12px; border: 1px solid #bee3f8; text-align: center;">
                        <div style="font-size: 22px; font-weight: 800; color: #2b6cb0;"><?php echo $import_results['duplicate'] ?? 0; ?></div>
                        <div style="font-size: 11.5px; color: #3182ce; font-weight: 700;">سجلات مكررة</div>
                    </div>
                    <div style="background: #fffaf0; padding: 14px; border-radius: 12px; border: 1px solid #feebc8; text-align: center;">
                        <div style="font-size: 22px; font-weight: 800; color: #c05621;"><?php echo $import_results['warning']; ?></div>
                        <div style="font-size: 11.5px; color: #dd6b20; font-weight: 700;">تنبيهات</div>
                    </div>
                    <div style="background: #fff5f5; padding: 14px; border-radius: 12px; border: 1px solid #fed7d7; text-align: center;">
                        <div style="font-size: 22px; font-weight: 800; color: #c53030;"><?php echo $import_results['error']; ?></div>
                        <div style="font-size: 11.5px; color: #e53e3e; font-weight: 700;">أخطاء</div>
                    </div>
                </div>

                <?php if (!empty($import_results['details'])): ?>
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 10px; max-height: 220px; overflow-y: auto;">
                        <table style="width: 100%; border-collapse: collapse; font-size: 12px; text-align: right;">
                            <thead>
                                <tr style="background: #edf2f7; position: sticky; top: 0;">
                                    <th style="padding: 10px 15px; border-bottom: 1px solid #cbd5e0; width: 80px;">النوع</th>
                                    <th style="padding: 10px 15px; border-bottom: 1px solid #cbd5e0;">التفاصيل والسبب</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($import_results['details'] as $detail): ?>
                                    <tr>
                                        <td style="padding: 10px 15px; border-bottom: 1px solid #e2e8f0;">
                                            <?php if ($detail['type'] == 'error'): ?>
                                                <span style="color: #e53e3e; font-weight: 700;">خطأ</span>
                                            <?php elseif ($detail['type'] == 'info'): ?>
                                                <span style="color: #3182ce; font-weight: 700;">تكرار</span>
                                            <?php else: ?>
                                                <span style="color: #dd6b20; font-weight: 700;">تنبيه</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="padding: 10px 15px; border-bottom: 1px solid #e2e8f0; color: #4a5568;"><?php echo esc_html($detail['msg']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- 1. Header Banner Card (Wine Red / Red Pastel Theme) -->
    <div style="background: #ffffff; padding: 22px 28px; border-radius: 20px; border: 1px solid #e2e8f0; margin-bottom: 20px; box-shadow: 0 4px 18px rgba(0, 0, 0, 0.02); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
        <div style="display: flex; align-items: center; gap: 16px;">
            <div style="width: 52px; height: 52px; background: #fef2f2; border-radius: 16px; display: flex; align-items: center; justify-content: center; color: #881337; border: 1px solid #fecdd3; flex-shrink: 0;">
                <span class="dashicons dashicons-groups" style="font-size: 26px; width: 26px; height: 26px; line-height: 1;"></span>
            </div>
            <div>
                <h2 style="margin: 0 0 4px 0; font-size: 22px; font-weight: 800; color: #0f172a; letter-spacing: -0.3px;">
                    إدارة شؤون الطلاب
                </h2>
                <p style="margin: 0; font-size: 13px; color: #64748b; font-weight: 500;">
                    المركز الرئيسي لإدارة بيانات الطلاب، الملفات الأكاديمية والشخصية، السجلات المدرسية، واستيراد وتصدير ملفات البيانات المعتمدة
                </p>
            </div>
        </div>

        <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
            <!-- Export / Print Reports Dropdown -->
            <div style="position: relative; display: inline-block;">
                <button type="button" onclick="const d = document.getElementById('eess-students-export-dropdown'); d.style.display = d.style.display === 'none' ? 'block' : 'none'; event.stopPropagation();" class="eess-hdr-btn" style="background: #f8fafc !important; color: #334155 !important; border: 1px solid #cbd5e1 !important; border-radius: 12px; padding: 0 16px; height: 42px; font-weight: 700; font-size: 13px; display: inline-flex; align-items: center; gap: 8px; cursor: pointer; transition: all 0.2s; box-shadow: 0 1px 2px rgba(0,0,0,0.04);">
                    <span class="dashicons dashicons-download" style="font-size: 18px; width: 18px; height: 18px; color: #334155;"></span>
                    <span style="color: #334155 !important;">تصدير التقارير</span>
                    <span class="dashicons dashicons-arrow-down-alt2" style="font-size: 12px; width: 12px; height: 12px; color: #334155;"></span>
                </button>

                <div id="eess-students-export-dropdown" style="display: none; position: absolute; left: 0; top: 115%; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 14px; width: 250px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); z-index: 99999; padding: 6px 0; text-align: right;">
                    <div style="padding: 6px 16px; font-size: 11px; color: #94a3b8; font-weight: 800; border-bottom: 1px solid #f1f5f9;">تصدير واستيراد البيانات (Excel/CSV)</div>
                    <a href="<?php echo admin_url('admin-ajax.php?action=sm_export_students_csv&nonce=' . wp_create_nonce('sm_admin_action')); ?>" style="display: flex; align-items: center; gap: 8px; padding: 10px 16px; color: #334155; font-size: 12px; font-weight: 600; text-decoration: none; border-bottom: 1px solid #f1f5f9; transition: background 0.15s;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'">
                        <span class="dashicons dashicons-table-col-before" style="font-size: 16px; width: 16px; height: 16px;"></span>
                        <span>تصدير جميع الطلاب (Excel/CSV)</span>
                    </a>
                    <a href="<?php echo admin_url('admin-ajax.php?action=sm_download_student_import_template'); ?>" target="_blank" style="display: flex; align-items: center; gap: 8px; padding: 10px 16px; color: #334155; font-size: 12px; font-weight: 600; text-decoration: none; border-bottom: 1px solid #f1f5f9; transition: background 0.15s;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'">
                        <span class="dashicons dashicons-download" style="font-size: 16px; width: 16px; height: 16px;"></span>
                        <span>تحميل نموذج الاستيراد الرسمي (11 عمود)</span>
                    </a>

                    <div style="padding: 6px 16px; font-size: 11px; color: #94a3b8; font-weight: 800; border-bottom: 1px solid #f1f5f9;">طباعة البطاقات والتقارير</div>
                    <a href="<?php echo admin_url('admin-ajax.php?action=sm_print&print_type=id_card'); ?>" target="_blank" style="display: flex; align-items: center; gap: 8px; padding: 10px 16px; color: #15803d; font-size: 12px; font-weight: 700; text-decoration: none; transition: background 0.15s;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'">
                        <span class="dashicons dashicons-printer" style="font-size: 16px; width: 16px; height: 16px;"></span>
                        <span>طباعة بطاقات الهوية الأكاديمية</span>
                    </a>
                </div>
            </div>

            <!-- Secondary Action: Import -->
            <button type="button" onclick="const f=document.getElementById('csv-import-form'); f.style.display = f.style.display==='none'?'block':'none';" class="eess-hdr-btn" style="background: #f8fafc !important; color: #334155 !important; border: 1px solid #cbd5e1 !important; border-radius: 12px; padding: 0 16px; height: 42px; font-weight: 700; font-size: 13px; display: inline-flex; align-items: center; gap: 8px; cursor: pointer; transition: all 0.2s; box-shadow: 0 1px 2px rgba(0,0,0,0.04);">
                <span class="dashicons dashicons-upload" style="font-size: 18px; width: 18px; height: 18px; color: #334155;"></span>
                <span style="color: #334155 !important;">استيراد</span>
            </button>

            <!-- Primary Action: Add Student (Wine Red) -->
            <?php if ($is_admin): ?>
            <button type="button" onclick="openAddStudentWizard()" class="sm-btn sm-btn-custom" style="background: #881337; color: #ffffff; border: none; border-radius: 12px; padding: 0 20px; height: 42px; font-weight: 800; font-size: 13.5px; display: inline-flex; align-items: center; gap: 8px; cursor: pointer; box-shadow: 0 4px 12px rgba(136, 19, 55, 0.25); transition: all 0.2s;" onmouseover="this.style.background='#700c2a'" onmouseout="this.style.background='#881337'">
                <span class="dashicons dashicons-plus-alt2" style="font-size: 18px; width: 18px; height: 18px; color: #ffffff;"></span>
                <span>إضافة طالب جديد</span>
            </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- 2. Search & Filtering Card -->
    <div style="background: #ffffff; padding: 20px 24px; border: 1px solid #e2e8f0; border-radius: 20px; margin-bottom: 20px; box-shadow: 0 4px 16px rgba(0,0,0,0.02);">
        <form method="get" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; align-items: end;">
            <input type="hidden" name="page" value="<?php echo esc_attr($_GET['page'] ?? 'sm-dashboard'); ?>">
            <input type="hidden" name="sm_tab" value="students">
            <input type="hidden" name="paged" value="1">
            <input type="hidden" name="limit" value="<?php echo esc_attr($limit); ?>">

            <!-- Student Search -->
            <div>
                <label style="display: block; margin-bottom: 6px; font-size: 12.5px; font-weight: 700; color: #334155;">
                    اسم الطالب / الكود / الهوية
                </label>
                <div style="position: relative;">
                    <input type="text" name="student_search" value="<?php echo esc_attr($search); ?>" placeholder="بحث بالاسم، الكود، الهوية..." style="width: 100%; height: 42px; padding: 0 38px 0 14px; border: 1px solid #cbd5e1; border-radius: 12px; font-size: 13px; outline: none; background: #f8fafc; transition: all 0.2s;" onfocus="this.style.borderColor='#881337'; this.style.background='#fff';" onblur="this.style.borderColor='#cbd5e1'; this.style.background='#f8fafc';">
                    <span style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; pointer-events: none; display: flex; align-items: center;">
                        <span class="dashicons dashicons-search" style="font-size: 16px; width: 16px; height: 16px;"></span>
                    </span>
                </div>
            </div>

            <!-- Grade Filter -->
            <div>
                <label style="display: block; margin-bottom: 6px; font-size: 12.5px; font-weight: 700; color: #334155;">
                    الصف الدراسي
                </label>
                <div style="position: relative;">
                    <select name="class_filter" style="width: 100%; height: 42px; padding: 0 38px 0 26px; border: 1px solid #cbd5e1; border-radius: 12px; font-size: 13px; outline: none; background: #f8fafc; appearance: none; -webkit-appearance: none; cursor: pointer; transition: all 0.2s;" onfocus="this.style.borderColor='#881337'; this.style.background='#fff';" onblur="this.style.borderColor='#cbd5e1'; this.style.background='#f8fafc';">
                        <option value="">جميع الصفوف</option>
                        <?php
                        $academic = SM_Settings::get_academic_structure();
                        foreach ($academic['active_grades'] as $grade_num) {
                            $grade_label = 'الصف ' . $grade_num;
                            echo '<option value="' . esc_attr($grade_label) . '" ' . selected($class_filter == $grade_label, true, false) . '>' . esc_html($grade_label) . '</option>';
                        }
                        ?>
                    </select>
                    <span style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; pointer-events: none; display: flex; align-items: center;">
                        <span class="dashicons dashicons-welcome-learn-more" style="font-size: 16px; width: 16px; height: 16px;"></span>
                    </span>
                    <span style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #94a3b8; pointer-events: none; font-size: 9px;">▼</span>
                </div>
            </div>

            <!-- Section Filter -->
            <div>
                <label style="display: block; margin-bottom: 6px; font-size: 12.5px; font-weight: 700; color: #334155;">
                    الشعبة / الفصل
                </label>
                <div style="position: relative;">
                    <input type="text" name="section_filter" value="<?php echo esc_attr($section_filter); ?>" placeholder="مثال: أ" list="existing-sections" style="width: 100%; height: 42px; padding: 0 38px 0 14px; border: 1px solid #cbd5e1; border-radius: 12px; font-size: 13px; outline: none; background: #f8fafc; transition: all 0.2s;" onfocus="this.style.borderColor='#881337'; this.style.background='#fff';" onblur="this.style.borderColor='#cbd5e1'; this.style.background='#f8fafc';">
                    <datalist id="existing-sections">
                        <?php
                        $sections = $wpdb->get_col("SELECT DISTINCT section FROM {$wpdb->prefix}sm_students WHERE section != '' ORDER BY section ASC");
                        foreach ($sections as $sec) echo "<option value='".esc_attr($sec)."'>";
                        ?>
                    </datalist>
                    <span style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; pointer-events: none; display: flex; align-items: center;">
                        <span class="dashicons dashicons-category" style="font-size: 16px; width: 16px; height: 16px;"></span>
                    </span>
                </div>
            </div>

            <!-- Teacher Filter -->
            <div>
                <label style="display: block; margin-bottom: 6px; font-size: 12.5px; font-weight: 700; color: #334155;">
                    المعلم المربّي
                </label>
                <div style="position: relative;">
                    <select name="teacher_filter" style="width: 100%; height: 42px; padding: 0 38px 0 26px; border: 1px solid #cbd5e1; border-radius: 12px; font-size: 13px; outline: none; background: #f8fafc; appearance: none; -webkit-appearance: none; cursor: pointer; transition: all 0.2s;" onfocus="this.style.borderColor='#881337'; this.style.background='#fff';" onblur="this.style.borderColor='#cbd5e1'; this.style.background='#f8fafc';">
                        <option value="">جميع المعلمين</option>
                        <?php
                        $teachers = get_users(array('role' => 'sm_teacher'));
                        foreach ($teachers as $t) {
                            echo '<option value="' . $t->ID . '" ' . selected($teacher_filter == $t->ID, true, false) . '>' . esc_html($t->display_name) . '</option>';
                        }
                        ?>
                    </select>
                    <span style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; pointer-events: none; display: flex; align-items: center;">
                        <span class="dashicons dashicons-admin-users" style="font-size: 16px; width: 16px; height: 16px;"></span>
                    </span>
                    <span style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #94a3b8; pointer-events: none; font-size: 9px;">▼</span>
                </div>
            </div>

            <!-- Apply Filters Button -->
            <div>
                <button type="submit" class="sm-btn" style="background: #881337; color: #ffffff; border: none; border-radius: 12px; height: 42px; padding: 0 22px; font-weight: 800; font-size: 13.5px; width: 100%; display: inline-flex; align-items: center; justify-content: center; gap: 8px; cursor: pointer; box-shadow: 0 4px 12px rgba(136, 19, 55, 0.2); transition: all 0.2s;" onmouseover="this.style.background='#700c2a'" onmouseout="this.style.background='#881337'">
                    <span class="dashicons dashicons-filter" style="font-size: 16px; width: 16px; height: 16px; color: #fff;"></span>
                    <span>تطبيق الفلترة</span>
                </button>
            </div>
        </form>
    </div>

    <!-- Chunked File Upload Progress Form & Documentation -->
    <div id="csv-import-form" style="display:none; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 16px; padding: 25px; margin-bottom: 24px; box-shadow: 0 4px 16px rgba(0,0,0,0.02);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; border-bottom: 1px solid #e2e8f0; padding-bottom: 12px;">
            <h4 style="margin:0; color:#0f172a; font-weight: 800; font-size: 16px;">استيراد ذكي لملف الطلاب الشامل (Excel / CSV)</h4>
            <a href="<?php echo admin_url('admin-ajax.php?action=sm_download_student_import_template'); ?>" target="_blank" class="sm-btn" style="background: #881337; color: white !important; font-size: 11px; padding: 6px 16px; width: auto; height: 32px; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                <span class="dashicons dashicons-download" style="font-size: 14px; width: 14px; height: 14px; color: #fff;"></span>
                <span>تحميل نموذج الاستيراد المعتمد (11 عمود)</span>
            </a>
        </div>

        <p style="font-size:12.5px; color:#64748b; line-height:1.6; margin-bottom:15px;">
            يرجى اختيار ملف الطلاب المعتمد بصيغة CSV/Excel. يدعم النظام بنظام الدفعات (Chunking) لمعالجة الملفات الضخمة وتحديث بيانات الطلاب الحاليين تلقائياً عند مطابقة الكود أو الهوية.
        </p>

        <!-- Official Column Structure Documentation Card -->
        <div style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 12px; padding: 16px; margin-bottom: 20px;">
            <div style="font-size: 12.5px; font-weight: 800; color: #1e293b; margin-bottom: 10px;">📋 دليل ترتيب أعمدة نموذج الاستيراد الرسمي (الأعمدة من A إلى K):</div>
            <div style="max-height: 180px; overflow-y: auto;">
                <table style="width: 100%; border-collapse: collapse; font-size: 11.5px; text-align: right;">
                    <thead>
                        <tr style="background: #f1f5f9; position: sticky; top: 0; color: #334155;">
                            <th style="padding: 8px 10px; border-bottom: 1px solid #cbd5e1; width: 50px;">العمود</th>
                            <th style="padding: 8px 10px; border-bottom: 1px solid #cbd5e1;">اسم الحقل</th>
                            <th style="padding: 8px 10px; border-bottom: 1px solid #cbd5e1; width: 90px;">الحالة</th>
                            <th style="padding: 8px 10px; border-bottom: 1px solid #cbd5e1;">الصيغة التوضيحية</th>
                            <th style="padding: 8px 10px; border-bottom: 1px solid #cbd5e1;">مثال</th>
                        </tr>
                    </thead>
                    <tbody style="color: #475569;">
                        <tr><td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9; font-weight:700;">A</td><td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9;">كود الطالب (Student Code)</td><td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9; color: #881337; font-weight:700;">اختياري (تلقائي)</td><td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9;">نصي / رقمي</td><td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9;">STU-1001</td></tr>
                        <tr><td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9; font-weight:700;">B</td><td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9;">الاسم الكامل (Full Name)</td><td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9; color: #dc2626; font-weight:700;">إجباري</td><td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9;">نص ثلاثي أو رباعي</td><td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9;">علي أحمد عبدالله</td></tr>
                        <tr><td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9; font-weight:700;">C</td><td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9;">الهوية الوطنية (National ID)</td><td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9; color: #881337; font-weight:700;">اختياري (فريد)</td><td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9;">أرقام فقط</td><td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9;">784199012345678</td></tr>
                        <tr><td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9; font-weight:700;">D</td><td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9;">الصف الدراسي (Grade)</td><td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9; color: #dc2626; font-weight:700;">إجباري</td><td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9;">الصف 1 إلى 12</td><td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9;">الصف 5</td></tr>
                        <tr><td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9; font-weight:700;">E</td><td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9;">الشعبة / الفصل (Section)</td><td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9; color: #64748b;">اختياري</td><td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9;">حرف أو رقم</td><td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9;">أ</td></tr>
                        <tr><td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9; font-weight:700;">F</td><td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9;">الجنسية (Nationality)</td><td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9; color: #64748b;">اختياري</td><td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9;">اسم الدولة</td><td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9;">إماراتي</td></tr>
                        <tr><td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9; font-weight:700;">G</td><td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9;">تاريخ التسجيل (Reg Date)</td><td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9; color: #64748b;">اختياري</td><td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9;">YYYY-MM-DD</td><td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9;">2024-09-01</td></tr>
                        <tr><td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9; font-weight:700;">H</td><td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9;">بريد ولي الأمر (Email)</td><td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9; color: #64748b;">اختياري</td><td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9;">بريد إلكتروني صالح</td><td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9;">parent@domain.com</td></tr>
                        <tr><td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9; font-weight:700;">I</td><td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9;">هاتف ولي الأمر (Phone)</td><td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9; color: #64748b;">اختياري</td><td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9;">أرقام الهاتف</td><td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9;">0501234567</td></tr>
                        <tr><td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9; font-weight:700;">J</td><td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9;">رابط الصورة (Photo URL)</td><td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9; color: #64748b;">اختياري</td><td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9;">رابط URL مباشر</td><td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9;">https://site.com/p.jpg</td></tr>
                        <tr><td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9; font-weight:700;">K</td><td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9;">معرف المدرسة (School ID)</td><td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9; color: #64748b;">اختياري</td><td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9;">رقم المعرف</td><td style="padding: 6px 10px; border-bottom: 1px solid #f1f5f9;">1</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div id="import-selection-area">
            <input type="file" id="csv-file-input" accept=".csv" class="sm-input" style="width: auto; display: inline-block; margin-bottom:15px; font-size:12px; height:36px;">
            <button onclick="startChunkedUpload()" class="sm-btn" style="width: auto; height:36px; font-size:12px; background: #881337; border-color: #881337;">بدء الاستيراد المجدول</button>
        </div>
        <div id="import-progress-area" style="display:none; margin-top:15px;">
            <div style="display:flex; justify-content:space-between; margin-bottom:8px; font-size:12px; font-weight:700;">
                <span id="import-status-text">جاري تحليل ومعالجة ملف الاستيراد...</span>
                <span id="import-percentage">0%</span>
            </div>
            <div style="background:#edf2f7; border-radius:50px; height:12px; overflow:hidden;">
                <div id="import-progress-bar" style="background:#881337; width:0%; height:100%; transition:0.3s;"></div>
            </div>
        </div>
    </div>

    <!-- Dynamic Bulk Actions Toolbar -->
    <div id="student-bulk-actions-toolbar" style="display: none; gap: 10px; margin-bottom: 15px; align-items: center; background: #fff5f5; padding: 12px 20px; border-radius: 12px; border: 1px solid #fed7d7;">
        <span style="font-size: 12.5px; font-weight: 700; color: #c53030;">الإجراءات الجماعية للطلاب المحددين:</span>
        <button onclick="bulkDeleteSelected()" class="sm-btn" style="background: #dc2626; font-size: 11.5px; padding: 5px 16px; width: auto; height: 32px; border-radius: 8px;">حذف المحدد نهائياً</button>
    </div>

    <!-- 3. Records Table Card -->
    <div style="background: #ffffff; border-radius: 20px; border: 1px solid #e2e8f0; box-shadow: 0 4px 20px rgba(0,0,0,0.02); overflow: hidden; margin-bottom: 24px;">

        <!-- Table Top Control Bar -->
        <div style="padding: 18px 24px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 14px; background: #ffffff;">
            <!-- Left Header Info -->
            <div style="display: flex; align-items: center; gap: 10px;">
                <span style="font-weight: 800; font-size: 15px; color: #0f172a;">قائمة الطلاب المسجلين</span>
                <span style="display: inline-flex; align-items: center; padding: 4px 12px; background: #fef2f2; color: #881337; border-radius: 12px; font-size: 12px; font-weight: 800; border: 1px solid #fecdd3;">
                    <?php echo $total_students_count; ?> طالب
                </span>
            </div>

            <!-- Page Limit Selector -->
            <div style="display: flex; align-items: center; gap: 8px; font-size: 12.5px; color: #64748b; font-weight: 600;">
                <span>عرض بالسفرة:</span>
                <select id="stu_page_limit_select" onchange="changeStudentPageLimit(this.value)" style="height: 36px; padding: 0 24px 0 10px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 12.5px; color: #334155; font-weight: 700; outline: none; background: #f8fafc; cursor: pointer;">
                    <option value="10" <?php selected($limit == 10); ?>>10 طلاب</option>
                    <option value="25" <?php selected($limit == 25); ?>>25 طالب</option>
                    <option value="50" <?php selected($limit == 50); ?>>50 طالب</option>
                    <option value="100" <?php selected($limit == 100); ?>>100 طالب</option>
                </select>
            </div>
        </div>

        <!-- Table Responsive Wrapper -->
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: separate; border-spacing: 0; text-align: right;">
                <thead>
                    <tr style="background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                        <th style="width: 40px; text-align: center; padding: 14px 10px; border-bottom: 2px solid #e2e8f0;"><input type="checkbox" onclick="toggleAllStudents(this)"></th>
                        <th style="padding: 14px 18px; font-size: 12.5px; font-weight: 800; color: #475569; border-bottom: 2px solid #e2e8f0; width: 28%;">بيانات الطالب والهوية</th>
                        <th style="padding: 14px 18px; font-size: 12.5px; font-weight: 800; color: #475569; border-bottom: 2px solid #e2e8f0; width: 24%;">التسكين الأكاديمي والمدرسة</th>
                        <th style="padding: 14px 18px; font-size: 12.5px; font-weight: 800; color: #475569; border-bottom: 2px solid #e2e8f0; width: 20%;">المعلم المربّي</th>
                        <th style="padding: 14px 18px; font-size: 12.5px; font-weight: 800; color: #475569; border-bottom: 2px solid #e2e8f0; text-align: center; width: 10%;">النقاط</th>
                        <th style="padding: 14px 18px; font-size: 12.5px; font-weight: 800; color: #475569; border-bottom: 2px solid #e2e8f0; text-align: center; width: 18%;">الإجراءات الإدارية</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($paginated_students)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; color: #94a3b8; padding: 50px 20px; font-weight: 700;">لا يوجد طلاب يطابقون شروط البحث حالياً في قاعدة البيانات.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($paginated_students as $student): ?>
                            <tr id="stu-row-<?php echo $student->id; ?>" style="border-bottom: 1px solid #f1f5f9; transition: background 0.15s;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'">
                                <td style="text-align: center; padding: 14px 10px;"><input type="checkbox" class="student-checkbox" value="<?php echo $student->id; ?>" onchange="updateStudentBulkToolbar()"></td>

                                <!-- Student Cell (Clean Capsule without 'Code' text) -->
                                <td style="padding: 14px 18px;">
                                    <div style="display: flex; align-items: center; gap: 12px;">
                                        <?php if (!empty($student->photo_url)): ?>
                                            <img src="<?php echo esc_url($student->photo_url); ?>" style="width: 44px; height: 44px; border-radius: 50% !important; object-fit: cover; border: 2px solid #e2e8f0; box-shadow: 0 2px 6px rgba(0,0,0,0.05);">
                                        <?php else: ?>
                                            <div style="background: #f1f5f9; width: 44px; height: 44px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #94a3b8; border: 1px solid #e2e8f0; font-size: 18px;">
                                                <span class="dashicons dashicons-admin-users" style="font-size: 20px; width:20px; height:20px;"></span>
                                            </div>
                                        <?php endif; ?>
                                        <div style="line-height: 1.4;">
                                            <a href="javascript:void(0)" onclick="openUnifiedProfileModal(<?php echo htmlspecialchars(json_encode($student)); ?>)" style="font-weight: 800; font-size: 14px; color: #0f172a; text-decoration: none;" onmouseover="this.style.color='#881337'" onmouseout="this.style.color='#0f172a'">
                                                <?php echo esc_html($student->name); ?>
                                            </a>
                                            <div style="display: flex; align-items: center; gap: 6px; margin-top: 4px; flex-wrap: wrap;">
                                                <!-- Clean Student ID without 'Code' label -->
                                                <span style="display: inline-flex; align-items: center; padding: 2px 8px; background: #fef2f2; color: #881337; border: 1px solid #fecdd3; border-radius: 6px; font-size: 11px; font-weight: 800; font-family: monospace;">
                                                    <?php echo esc_html($student->student_code); ?>
                                                </span>
                                                <?php if (!empty($student->national_id)): ?>
                                                    <span style="display: inline-flex; align-items: center; padding: 2px 8px; background: #f1f5f9; color: #475569; border-radius: 6px; font-size: 10.5px; font-weight: 700; font-family: monospace;">
                                                        <?php echo esc_html($student->national_id); ?>
                                                    </span>
                                                <?php endif; ?>
                                                <?php if (!empty($student->nationality)): ?>
                                                    <span style="display: inline-flex; align-items: center; padding: 2px 8px; background: #fffaf0; color: #9a3412; border: 1px solid #ffedd5; border-radius: 6px; font-size: 10.5px; font-weight: 700;">
                                                        <?php echo esc_html($student->nationality); ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Academic Placement Cell (Compact Table Capsules) -->
                                <td style="padding: 14px 18px;">
                                    <div style="font-weight: 700; font-size: 11px; color: #64748b; margin-bottom: 4px; display: flex; align-items: center; gap: 4px;">
                                        <span class="dashicons dashicons-bank" style="font-size: 13px; width: 13px; height: 13px; color: #64748b;"></span>
                                        <span><?php echo esc_html($student->school_name ?? 'المدرسة الرئيسية'); ?></span>
                                    </div>
                                    <div style="display: flex; align-items: center; gap: 5px; flex-wrap: wrap;">
                                        <!-- Distinct Compact Grade Badge -->
                                        <span style="display: inline-flex; align-items: center; padding: 2px 8px; background: #f1f5f9; color: #1e293b; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 11px; font-weight: 800;">
                                            <?php echo esc_html($student->class_name); ?>
                                        </span>
                                        <!-- Distinct Compact Section Badge -->
                                        <?php if (!empty($student->section)): ?>
                                            <span style="display: inline-flex; align-items: center; padding: 2px 8px; background: #fef2f2; color: #881337; border: 1px solid #fecdd3; border-radius: 6px; font-size: 11px; font-weight: 800;">
                                                شعبة <?php echo esc_html($student->section); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>

                                <!-- Homeroom Teacher Cell with Real Phone Number -->
                                <td style="padding: 14px 18px;">
                                    <?php
                                    $teacher = $student->teacher_id ? get_userdata($student->teacher_id) : null;
                                    $t_phone = $teacher ? get_user_meta($teacher->ID, 'sm_phone', true) : '';
                                    if (empty($t_phone) && $teacher) $t_phone = get_user_meta($teacher->ID, 'phone_number', true);

                                    if ($teacher): ?>
                                        <div style="font-weight: 800; font-size: 13px; color: #0f172a;"><?php echo esc_html($teacher->display_name); ?></div>
                                        <?php if (!empty($t_phone)): ?>
                                            <div style="font-size: 11px; color: #64748b; font-family: monospace; font-weight: 700; margin-top: 2px;">
                                                📞 <?php echo esc_html($t_phone); ?>
                                            </div>
                                        <?php else: ?>
                                            <div style="font-size: 11px; color: #94a3b8;"><?php echo esc_html($teacher->user_email); ?></div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span style="color: #cbd5e1; font-style: italic; font-size: 12px;">غير معيّن</span>
                                    <?php endif; ?>
                                </td>

                                <!-- Behavior Points Cell -->
                                <td style="padding: 14px 18px; text-align: center;">
                                    <span style="display: inline-flex; align-items: center; justify-content: center; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 800; background: <?php echo $student->behavior_points > 10 ? '#fee2e2' : ($student->behavior_points > 4 ? '#fef3c7' : '#dcfce7'); ?>; color: <?php echo $student->behavior_points > 10 ? '#dc2626' : ($student->behavior_points > 4 ? '#d97706' : '#15803d'); ?>;">
                                        <?php echo intval($student->behavior_points); ?> نقطة
                                    </span>
                                </td>

                                <!-- Standardized Perfectly Circular 36px Action Buttons with Official Dashicons -->
                                <td style="padding: 14px 18px; text-align: center;">
                                    <div style="display: flex; align-items: center; justify-content: center; gap: 6px;">
                                        <!-- Report PDF Button -->
                                        <a href="<?php echo admin_url('admin-ajax.php?action=sm_print_student_full_report&student_id=' . $student->id); ?>" target="_blank" title="التقرير الشامل (PDF)" style="width: 36px; height: 36px; border-radius: 50% !important; flex-shrink: 0; background: #dcfce7; color: #16a34a; display: inline-flex; align-items: center; justify-content: center; text-decoration: none; transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.08)'" onmouseout="this.style.transform='scale(1)'">
                                            <span class="dashicons dashicons-printer" style="font-size: 16px; width: 16px; height: 16px; margin: 0;"></span>
                                        </a>

                                        <!-- Behavioral Profile Drawer -->
                                        <button type="button" onclick="viewSmStudent(<?php echo htmlspecialchars(json_encode($student)); ?>)" title="الملف السلوكي والتحليلي" class="sm-action-btn sm-action-btn-warning">
                                            <span class="dashicons dashicons-clipboard"></span>
                                        </button>

                                        <?php if ($is_admin): ?>
                                            <!-- Credentials Button -->
                                            <button type="button" onclick="showStudentCreds('<?php echo esc_js($student->student_code); ?>', '<?php echo esc_js(get_user_meta($student->parent_user_id, 'sm_temp_pass', true)); ?>', '<?php echo esc_js($student->name); ?>', <?php echo $student->id; ?>)" title="حساب الدخول الأكاديمي" class="sm-action-btn sm-action-btn-neutral">
                                                <span class="dashicons dashicons-key"></span>
                                            </button>

                                            <!-- Edit Student Button -->
                                            <button type="button" onclick='openUnifiedProfileModal(<?php echo json_encode($student); ?>)' title="تعديل الطالب" class="sm-action-btn sm-action-btn-warning">
                                                <span class="dashicons dashicons-edit"></span>
                                            </button>

                                            <!-- Delete Student Button -->
                                            <button type="button" onclick="confirmDeleteStudent(<?php echo $student->id; ?>, '<?php echo esc_js($student->name); ?>')" title="حذف الطالب نهائياً" class="sm-action-btn sm-action-btn-danger">
                                                <span class="dashicons dashicons-trash"></span>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- 4. Server-Side Pagination Footer matching Student Behavior Records -->
        <div style="padding: 16px 24px; border-top: 1px solid #f1f5f9; background: #ffffff; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 14px;">
            <div style="font-size: 13px; color: #64748b; font-weight: 600;">
                عرض <span style="color: #0f172a; font-weight: 800;"><?php echo $from_num; ?></span> - <span style="color: #0f172a; font-weight: 800;"><?php echo $to_num; ?></span> من إجمالي <span style="color: #0f172a; font-weight: 800;"><?php echo $total_students_count; ?></span> طالب
            </div>

            <div style="display: flex; align-items: center; gap: 6px;">
                <?php
                $prev_disabled = ($paged <= 1);
                $next_disabled = ($paged >= $total_pages);
                $base_url = remove_query_arg(['paged']);
                ?>
                <a href="<?php echo add_query_arg('paged', 1, $base_url); ?>" <?php if ($prev_disabled) echo 'style="pointer-events:none; opacity:0.5;"'; ?> class="sm-btn" style="height: 36px; padding: 0 10px; border-radius: 8px; border: 1px solid #cbd5e1; background: #ffffff; color: #334155; font-size: 12px; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center;">الأولى</a>
                <a href="<?php echo add_query_arg('paged', max(1, $paged - 1), $base_url); ?>" <?php if ($prev_disabled) echo 'style="pointer-events:none; opacity:0.5;"'; ?> class="sm-btn" style="height: 36px; padding: 0 12px; border-radius: 8px; border: 1px solid #cbd5e1; background: #ffffff; color: #334155; font-size: 12px; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center;">السابق</a>

                <div style="display: flex; gap: 4px;">
                    <?php
                    $start_p = max(1, $paged - 2);
                    $end_p = min($total_pages, $paged + 2);
                    for ($p = $start_p; $p <= $end_p; $p++):
                        $is_active = ($p == $paged);
                    ?>
                        <a href="<?php echo add_query_arg('paged', $p, $base_url); ?>" class="sm-btn" style="height: 36px; min-width: 36px; padding: 0 8px; border-radius: 8px; border: 1px solid <?php echo $is_active ? '#881337' : '#cbd5e1'; ?>; background: <?php echo $is_active ? '#881337' : '#ffffff'; ?>; color: <?php echo $is_active ? '#ffffff' : '#334155'; ?>; font-size: 12px; font-weight: 800; text-decoration: none; display: inline-flex; align-items: center; justify-content: center;"><?php echo $p; ?></a>
                    <?php endfor; ?>
                </div>

                <a href="<?php echo add_query_arg('paged', min($total_pages, $paged + 1), $base_url); ?>" <?php if ($next_disabled) echo 'style="pointer-events:none; opacity:0.5;"'; ?> class="sm-btn" style="height: 36px; padding: 0 12px; border-radius: 8px; border: 1px solid #cbd5e1; background: #ffffff; color: #334155; font-size: 12px; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center;">التالي</a>
                <a href="<?php echo add_query_arg('paged', $total_pages, $base_url); ?>" <?php if ($next_disabled) echo 'style="pointer-events:none; opacity:0.5;"'; ?> class="sm-btn" style="height: 36px; padding: 0 10px; border-radius: 8px; border: 1px solid #cbd5e1; background: #ffffff; color: #334155; font-size: 12px; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center;">الأخيرة</a>
            </div>
        </div>
    </div>

    <!-- UNIFIED REUSABLE STUDENT PROFILE EDIT MODAL COMPONENT -->
    <?php if ($is_admin): ?>
        <?php include SM_PLUGIN_DIR . 'templates/partials/student-profile-edit-modal.php'; ?>
    <?php endif; ?>

    <!-- VIEW STUDENT RECORD MODAL -->
    <div id="view-student-modal" class="sm-modal-overlay">
        <div class="sm-modal-content" style="max-width: 900px; background: white;">
            <div class="sm-modal-header" style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #eee; padding-bottom:15px; margin-bottom:20px;">
                <h3 style="margin:0; font-weight:800; font-size: 15px;">الملف السلوكي والتحليلي التفصيلي للطالب</h3>
                <div style="display:flex; gap:10px;">
                    <button id="print-full-record-btn" class="sm-btn" style="background:#15803d; width:auto; font-size:11px; height:28px; display:inline-flex; align-items:center; gap:4px;">
                        <span class="dashicons dashicons-printer" style="font-size:14px; width:14px; height:14px;"></span>
                        <span>طباعة الملف بالكامل</span>
                    </button>
                    <button class="sm-modal-close" onclick="document.getElementById('view-student-modal').style.display='none'" style="width:28px; height:28px; display:flex; align-items:center; justify-content:center;">&times;</button>
                </div>
            </div>
            <div class="sm-modal-body" id="stu_details_content" style="max-height: 70vh; overflow-y: auto;">
                <!-- Loaded via AJAX -->
            </div>
        </div>
    </div>

    <!-- DELETE STUDENT MODAL -->
    <div id="delete-student-modal" class="sm-modal-overlay">
        <div class="sm-modal-content" style="max-width: 450px;">
            <div class="sm-modal-header">
                <h3 style="color:#e53e3e;">تأكيد حذف الطالب نهائياً</h3>
                <button class="sm-modal-close" onclick="document.getElementById('delete-student-modal').style.display='none'">&times;</button>
            </div>
            <form id="delete-student-form">
                <p id="delete-confirm-msg" style="font-size:13px; color:#4a5568; line-height:1.6;"></p>
                <div style="background:#fff5f5; border:1px solid #fed7d7; padding:12px; border-radius:8px; font-size:11px; color:#c53030; margin-bottom:20px;">
                    ⚠️ تحذير: هذا الإجراء سيقوم بمسح كافة مخالفات الطالب، سجل الحضور، السجل الطبي، والدرجات نهائياً من قاعدة البيانات، ولا يمكن التراجع عنه.
                </div>
                <input type="hidden" id="confirm_delete_stu_id">
                <div style="display:flex; gap:10px; justify-content:flex-end;">
                    <button type="submit" class="sm-btn" style="background:#e53e3e; width:auto; height:36px; padding:0 20px; font-size:12px;">نعم، حذف الطالب الآن</button>
                    <button type="button" onclick="document.getElementById('delete-student-modal').style.display='none'" class="sm-btn sm-btn-outline" style="width:auto; height:36px; padding:0 15px; font-size:12px;">تراجع</button>
                </div>
            </form>
        </div>
    </div>

    <!-- STUDENT CREDENTIALS MODAL -->
    <div id="student-creds-modal" class="sm-modal-overlay">
        <div class="sm-modal-content" style="max-width: 450px;">
            <div class="sm-modal-header">
                <h3>بيانات الدخول الأكاديمية للطالب</h3>
                <button class="sm-modal-close" onclick="document.getElementById('student-creds-modal').style.display='none'">&times;</button>
            </div>
            <div style="background:#f8fafc; border:1px solid #e2e8f0; padding:20px; border-radius:12px; margin-bottom:20px; line-height:1.8;">
                <div style="font-weight:700; font-size:14px; color:#881337; border-bottom:1px solid #eee; padding-bottom:8px; margin-bottom:15px;" id="cred-stu-name"></div>
                <div style="display:flex; justify-content:space-between; font-size:13px; margin-bottom:10px;">
                    <span style="color:#718096;">اسم المستخدم (كود الطالب):</span>
                    <strong style="font-family:monospace;" id="cred-username"></strong>
                </div>
                <div style="display:flex; justify-content:space-between; font-size:13px; margin-bottom:10px;">
                    <span style="color:#718096;">كلمة المرور الافتراضية:</span>
                    <strong style="font-family:monospace;" id="cred-password"></strong>
                </div>
            </div>
            <div style="display:flex; gap:10px; justify-content:flex-end;">
                <a id="cred-download-link" href="#" target="_blank" class="sm-btn" style="background:#881337; text-decoration:none; display:inline-flex; align-items:center; justify-content:center; width:auto; height:36px; padding:0 20px; font-size:12px; gap:6px;">
                    <span class="dashicons dashicons-download" style="font-size:14px; width:14px; height:14px;"></span>
                    <span>تحميل بطاقة الدخول (PDF)</span>
                </a>
                <button onclick="document.getElementById('student-creds-modal').style.display='none'" class="sm-btn sm-btn-outline" style="width:auto; height:36px; padding:0 15px; font-size:12px;">إغلاق</button>
            </div>
        </div>
    </div>

    <script>
    function changeStudentPageLimit(limitVal) {
        const url = new URL(window.location.href);
        url.searchParams.set('limit', limitVal);
        url.searchParams.set('paged', 1);
        window.location.href = url.toString();
    }

    function openAddStudentWizard() {
        // Reset form for Add Mode
        const form = document.getElementById('edit-student-form');
        if (form) form.reset();

        if (document.getElementById('edit_stu_id')) document.getElementById('edit_stu_id').value = '0';
        if (document.getElementById('edit-modal-title-text')) {
            document.getElementById('edit-modal-title-text').innerText = 'إضافة طالب جديد في المنظومة';
        }

        if (typeof goUnifiedEditStep === 'function') goUnifiedEditStep(1);
        else if (typeof goEditStep === 'function') goEditStep(1);
        const modal = document.getElementById('edit-student-modal');
        if (modal) modal.style.display = 'flex';
    }

    function openUnifiedProfileModal(s) {
        if (document.getElementById('edit_stu_id')) document.getElementById('edit_stu_id').value = s.id || 0;
        if (document.getElementById('edit_stu_name')) document.getElementById('edit_stu_name').value = s.name || '';
        if (document.getElementById('edit_stu_class')) document.getElementById('edit_stu_class').value = s.class_name || s.class || '';
        if (document.getElementById('edit_stu_section')) document.getElementById('edit_stu_section').value = s.section || '';
        if (document.getElementById('edit_stu_email')) document.getElementById('edit_stu_email').value = s.parent_email || '';
        if (document.getElementById('edit_stu_code')) document.getElementById('edit_stu_code').value = s.student_code || s.student_id || '';
        if (document.getElementById('edit_stu_phone')) document.getElementById('edit_stu_phone').value = s.guardian_phone || '';
        if (document.getElementById('edit_stu_nationality')) document.getElementById('edit_stu_nationality').value = s.nationality || '';
        if (document.getElementById('edit_stu_reg_date')) document.getElementById('edit_stu_reg_date').value = s.registration_date || '';

        if (document.getElementById('edit-modal-title-text')) {
            document.getElementById('edit-modal-title-text').innerText = 'تعديل الملف الشامل للطالب: ' + (s.name || '');
        }

        if (typeof goUnifiedEditStep === 'function') goUnifiedEditStep(1);
        else if (typeof goEditStep === 'function') goEditStep(1);
        const modal = document.getElementById('edit-student-modal');
        if (modal) modal.style.display = 'flex';
    }

    function updateStudentBulkToolbar() {
        const selected = document.querySelectorAll('.student-checkbox:checked').length;
        const toolbar = document.getElementById('student-bulk-actions-toolbar');
        if (toolbar) {
            toolbar.style.display = selected > 0 ? 'flex' : 'none';
        }
    }

    // Chunked File Upload Progress Form
    let chunkedFile, chunkedSize, chunkedId, chunkedTotalParts, chunkedCurrentPart;
    const CHUNK_SIZE = 100 * 1024; // 100kb chunks

    window.startChunkedUpload = function() {
        const fileInput = document.getElementById('csv-file-input');
        if (fileInput.files.length === 0) {
            alert('يرجى تحديد ملف CSV أولاً.');
            return;
        }

        chunkedFile = fileInput.files[0];
        chunkedSize = chunkedFile.size;
        chunkedTotalParts = Math.ceil(chunkedSize / CHUNK_SIZE);
        chunkedCurrentPart = 0;

        document.getElementById('import-selection-area').style.display = 'none';
        document.getElementById('import-progress-area').style.display = 'block';
        updateImportProgress('جاري رفع وتحليل ملف البيانات...', 0);

        uploadNextChunk();
    };

    function uploadNextChunk() {
        const start = chunkedCurrentPart * CHUNK_SIZE;
        const end = Math.min(start + CHUNK_SIZE, chunkedSize);
        const chunk = chunkedFile.slice(start, end);

        const formData = new FormData();
        formData.append('action', 'sm_upload_import_csv');
        formData.append('csv_file', chunk, chunkedFile.name);
        formData.append('nonce', '<?php echo wp_create_nonce("sm_admin_action"); ?>');

        fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                processImportChunk(res.data.file_path, 0);
            } else {
                alert('فشل رفع الملف: ' + res.data);
                resetImportUI();
            }
        });
    }

    function processImportChunk(filePath, offset) {
        const formData = new FormData();
        formData.append('action', 'sm_process_import_chunk');
        formData.append('file_path', filePath);
        formData.append('offset', offset);
        formData.append('nonce', '<?php echo wp_create_nonce("sm_admin_action"); ?>');

        fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                const finished = res.data.finished;
                const processed = res.data.total_so_far;

                if (finished) {
                    updateImportProgress('تم الانتهاء من استيراد كافة البيانات بنجاح!', 100);
                    setTimeout(() => location.reload(), 1500);
                } else {
                    const pct = Math.min(99, Math.round((offset / chunkedTotalParts) * 100));
                    updateImportProgress(`جاري معالجة السجلات... تم معالجة ${processed} طالب`, pct);
                    processImportChunk(filePath, offset + res.data.processed);
                }
            } else {
                alert('خطأ أثناء المعالجة: ' + res.data);
                resetImportUI();
            }
        });
    }

    function updateImportProgress(text, pct) {
        document.getElementById('import-status-text').innerText = text;
        document.getElementById('import-percentage').innerText = pct + '%';
        document.getElementById('import-progress-bar').style.width = pct + '%';
    }

    function resetImportUI() {
        document.getElementById('import-selection-area').style.display = 'block';
        document.getElementById('import-progress-area').style.display = 'none';
    }

    (function() {
        document.addEventListener('click', function(e) {
            const dropdown = document.getElementById('eess-students-export-dropdown');
            if (dropdown && dropdown.style.display === 'block') {
                dropdown.style.display = 'none';
            }
        });

        window.showStudentCreds = function(user, pass, name, id) {
            document.getElementById('cred-username').innerText = user;
            document.getElementById('cred-password').innerText = pass;
            document.getElementById('cred-stu-name').innerText = name;
            document.getElementById('cred-download-link').href = '<?php echo admin_url('admin-ajax.php?action=sm_print&print_type=student_credentials_card&student_id='); ?>' + id;
            document.getElementById('student-creds-modal').style.display = 'flex';
        };

        window.viewSmStudent = function(student) {
            const modal = document.getElementById('view-student-modal');
            const content = document.getElementById('stu_details_content');
            const printBtn = document.getElementById('print-full-record-btn');
            if (!modal || !content) return;
            
            content.innerHTML = '<div style="text-align:center; padding:50px;"><p style="font-weight:700; color:#718096;">جاري جلب الملف الانضباطي وتنسيقه...</p></div>';
            modal.style.display = 'flex';

            printBtn.onclick = function() {
                window.open('<?php echo admin_url('admin-ajax.php'); ?>?action=sm_print&print_type=disciplinary_report&student_id=' + student.id, '_blank');
            };

            fetch('<?php echo admin_url('admin-ajax.php'); ?>?action=sm_print&print_type=disciplinary_report&student_id=' + student.id)
                .then(r => r.text())
                .then(html => {
                    const doc = new DOMParser().parseFromString(html, 'text/html');
                    doc.querySelectorAll('.no-print').forEach(el => el.remove());
                    content.innerHTML = doc.body.innerHTML;
                });
        };

        const addForm = document.getElementById('add-student-form');
        if (addForm) {
            addForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                formData.append('action', 'sm_add_student_ajax');
                
                fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        smShowNotification('تمت إضافة الطالب بنجاح');
                        setTimeout(() => location.reload(), 500);
                    } else {
                        smShowNotification('خطأ: ' + res.data, true);
                    }
                })
                .catch(err => {
                    smShowNotification('حدث خطأ أثناء الاتصال بالخادم', true);
                });
            });
        }

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
                        smShowNotification('تم تحديث بيانات الطالب');
                        setTimeout(() => location.reload(), 500);
                    } else {
                        smShowNotification('خطأ: ' + res.data, true);
                    }
                })
                .catch(err => {
                    smShowNotification('حدث خطأ أثناء الاتصال بالخادم', true);
                });
            });
        }

        window.confirmDeleteStudent = function(id, name) {
            document.getElementById('confirm_delete_stu_id').value = id;
            document.getElementById('delete-confirm-msg').innerText = `هل أنت متأكد من حذف الطالب "${name}" وكافة سجلاته؟`;
            document.getElementById('delete-student-modal').style.display = 'flex';
        };

        const deleteForm = document.getElementById('delete-student-form');
        if (deleteForm) {
            deleteForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                formData.append('action', 'sm_delete_student_ajax');
                formData.append('nonce', '<?php echo wp_create_nonce("sm_delete_student"); ?>');
                formData.append('student_id', document.getElementById('confirm_delete_stu_id').value);

                fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        smShowNotification('تم حذف الطالب');
                        setTimeout(() => location.reload(), 500);
                    } else {
                        smShowNotification('خطأ: ' + res.data, true);
                    }
                })
                .catch(err => {
                    smShowNotification('حدث خطأ أثناء الاتصال بالخادم', true);
                });
            });
        }

        window.editSmStudent = function(s) {
            openUnifiedProfileModal(s);
        };

        window.toggleAllStudents = function(master) {
            document.querySelectorAll('.student-checkbox').forEach(cb => cb.checked = master.checked);
            updateStudentBulkToolbar();
        };

        window.bulkDeleteSelected = function() {
            const selected = Array.from(document.querySelectorAll('.student-checkbox:checked')).map(cb => cb.value);
            if (selected.length === 0) { alert('يرجى اختيار طلاب أولاً'); return; }
            if (!confirm(`هل أنت متأكد من حذف ${selected.length} طالب نهائياً؟`)) return;

            const formData = new FormData();
            formData.append('action', 'sm_bulk_delete_students_ajax');
            formData.append('student_ids', selected.join(','));
            formData.append('nonce', '<?php echo wp_create_nonce("sm_delete_student"); ?>');

            fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    smShowNotification(`تم حذف ${selected.length} طالب بنجاح`);
                    setTimeout(() => location.reload(), 500);
                }
            });
        };
    })();
    </script>
</div>
