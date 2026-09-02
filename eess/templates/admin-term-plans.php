<?php
if (!defined('ABSPATH')) exit;

$user = wp_get_current_user();
$user_id = $user->ID;
$user_roles = (array) $user->roles;

$is_activities_sup = in_array('sm_activities_supervisor', $user_roles);
$is_admin = current_user_can('manage_options') || in_array('administrator', $user_roles) || in_array('sm_system_admin', $user_roles);
$is_reviewer = $is_admin || in_array('sm_principal', $user_roles) || in_array('sm_supervisor', $user_roles) || in_array('sm_coordinator', $user_roles) || in_array('sm_hod', $user_roles) || $is_activities_sup;
$is_teacher = (in_array('sm_teacher', $user_roles) || $is_admin) && !$is_activities_sup;

global $wpdb;

// Fetch active term plans for current user or reviewed plans for reviewers
$acad_struct = SM_Settings::get_academic_structure();
$active_academic_year = $acad_struct['academic_year'] ?? '2027/2026';
$all_subjects = SM_DB::get_subjects();
$unique_subjects = array_unique(array_map(function($s){ return is_object($s) ? $s->name : $s; }, $all_subjects));

// Retrieve existing plans for teacher
$teacher_plans = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}sm_term_plans WHERE teacher_id = %d ORDER BY term_number ASC",
    $user_id
));

// Organize teacher plans by term_number (1, 2, 3)
$plans_by_term = array(1 => null, 2 => null, 3 => null);
$completed_terms_count = 0;
$total_completion_sum = 0;
$terms_in_year = 3;

foreach ($teacher_plans as $p) {
    $plans_by_term[$p->term_number] = $p;
    $total_completion_sum += intval($p->completion_pct);
    if ($p->status === 'approved' || $p->completion_pct >= 100) {
        $completed_terms_count++;
    }
    if ($p->num_terms > 0) {
        $terms_in_year = $p->num_terms;
    }
}

$annual_completion_pct = $terms_in_year > 0 ? round($total_completion_sum / $terms_in_year) : 0;
if ($annual_completion_pct > 100) $annual_completion_pct = 100;

// Submitted plans for Reviewers
$submitted_plans = array();
if ($is_reviewer) {
    $submitted_plans = $wpdb->get_results("
        SELECT tp.*, u.display_name as teacher_name
        FROM {$wpdb->prefix}sm_term_plans tp
        LEFT JOIN {$wpdb->users} u ON tp.teacher_id = u.ID
        WHERE tp.status IN ('submitted', 'approved', 'returned', 'rejected', 'draft')
        ORDER BY tp.updated_at DESC LIMIT 100
    ");
}

$arabic_term_names = array(
    1 => 'الفصل الدراسي الأول',
    2 => 'الفصل الدراسي الثاني',
    3 => 'الفصل الدراسي الثالث'
);
?>

<div class="sm-content-wrapper" dir="rtl" style="font-family: 'Cairo', sans-serif !important;">

    <!-- Single Main Banner Header -->
    <div style="background: #ffffff; padding: 20px 24px; border-radius: 20px; border: 1px solid #e2e8f0; margin-bottom: 18px; box-shadow: 0 4px 18px rgba(0,0,0,0.02); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
        <div style="display: flex; align-items: center; gap: 14px;">
            <div style="width: 48px; height: 48px; background: #fef2f2; border-radius: 14px; display: flex; align-items: center; justify-content: center; color: #881337; border: 1px solid #fecdd3; flex-shrink: 0;">
                <span class="dashicons dashicons-calendar-alt" style="font-size: 24px; width: 24px; height: 24px;"></span>
            </div>
            <div>
                <h2 style="margin: 0 0 4px 0; font-size: 20px; font-weight: 800; color: #0f172a;">الخطط الفصلية والسنوية للمدرس</h2>
                <p style="margin: 0; font-size: 12.5px; color: #64748b; font-weight: 500;">إعداد وإدارة الخطط التعليمية والتوزيع الأسبوعي للمناهج الدراسية والاعتماد المباشر</p>
            </div>
        </div>

        <!-- Primary Header Actions (Reordered: Settings Gear on far-left, Print/Export, Red Report button, Assign) -->
        <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
            <?php if ($is_admin || $is_reviewer): ?>
            <!-- Academic Config Gear Button (Positioned on far left) -->
            <button type="button" onclick="document.getElementById('eess-acad-config-modal').style.display='flex'" title="إعدادات العام الدراسي والفصول والمهل" class="sm-btn sm-btn-outline" style="height: 38px; width: 38px; padding: 0; border-radius: 50% !important; border: 1px solid #cbd5e1; background: #ffffff; color: #334155; display: inline-flex; align-items: center; justify-content: center; cursor: pointer;">
                <span class="dashicons dashicons-admin-generic" style="font-size: 18px; width: 18px; height: 18px; margin: 0; color: #475569;"></span>
            </button>
            <?php endif; ?>


            <?php if ($is_admin || $is_activities_sup || $is_reviewer): ?>
            <!-- School-Specific Plan Report Button -->
            <button type="button" onclick="document.getElementById('eess-school-plan-report-modal').style.display='flex'" class="sm-btn" style="background: #0284c7; color: #ffffff !important; height: 38px; border-radius: 9999px !important; padding: 0 18px; font-weight: 800; font-size: 12.5px; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 4px 12px rgba(2,132,199,0.2);">
                <span class="dashicons dashicons-building" style="font-size: 16px; width: 16px; height: 16px; color: #fff;"></span>
                <span>تقرير مدرسة محددة</span>
            </button>

            <!-- Non-Submission Administrative Report Button (Red Token) -->
            <button type="button" onclick="document.getElementById('eess-non-submission-plan-modal').style.display='flex'" class="sm-btn" style="background: #dc2626; color: #ffffff !important; height: 38px; border-radius: 9999px !important; padding: 0 18px; font-weight: 800; font-size: 12.5px; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 4px 12px rgba(220,38,38,0.2);">
                <span class="dashicons dashicons-dismiss" style="font-size: 16px; width: 16px; height: 16px; color: #fff;"></span>
                <span>تقرير غير المغطين للخطط</span>
            </button>
            <?php endif; ?>

            <?php if ($is_admin): ?>
            <!-- Assign Ready-Made Plan to Teacher Button (System Admin Only) -->
            <button type="button" onclick="document.getElementById('eess-assign-plan-modal').style.display='flex'" class="sm-btn" style="background: #0f172a; color: #ffffff !important; height: 38px; border-radius: 9999px !important; padding: 0 18px; font-weight: 800; font-size: 12.5px; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
                <span class="dashicons dashicons-user-freelance" style="font-size: 16px; width: 16px; height: 16px; color: #38bdf8;"></span>
                <span>إسناد خطة لمعلم</span>
            </button>
            <?php endif; ?>
            <?php if ($is_teacher && !$is_reviewer): ?>
            <button type="button" onclick="eessOpenPlanSetupWizard(1)" class="sm-btn" style="background: #881337; color: #ffffff !important; height: 38px; border-radius: 9999px !important; padding: 0 20px; font-weight: 800; font-size: 12.5px; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
                <span class="dashicons dashicons-plus-alt2" style="font-size: 15px; width: 15px; height: 15px; color: #fff;"></span>
                <span>إعداد الخطة</span>
            </button>
            <?php endif; ?>
        </div>
    </div>

        <?php if ($is_reviewer):
            $all_teachers = get_users(array('role' => 'sm_teacher'));
            // Filter strictly by PE & Health specialization scope
            $pe_teachers = array_filter($all_teachers, function($t) {
                $spec = get_user_meta($t->ID, 'sm_specialization', true) ?: (get_user_meta($t->ID, 'specialization', true) ?: (get_user_meta($t->ID, 'subject', true) ?: ''));
                return (mb_strpos($spec, 'بدنية') !== false || mb_strpos($spec, 'رياضة') !== false || mb_strpos($spec, 'Health') !== false || mb_strpos($spec, 'Physical') !== false);
            });
            // If no PE filter matched, fallback to all active teachers
            $target_teachers = !empty($pe_teachers) ? $pe_teachers : $all_teachers;
            $target_teacher_ids = array_map(function($t) { return $t->ID; }, $target_teachers);
            $total_eligible_teachers = count($target_teacher_ids);
            $plan_stats_total_req = $total_eligible_teachers * 3;

            if (!empty($target_teacher_ids)) {
                $id_placeholders = implode(',', array_fill(0, count($target_teacher_ids), '%d'));
                $plan_stats_submitted = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}sm_term_plans WHERE teacher_id IN ($id_placeholders) AND status IN ('submitted', 'approved', 'returned', 'rejected')", ...$target_teacher_ids));
                $plan_stats_approved  = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}sm_term_plans WHERE teacher_id IN ($id_placeholders) AND status = 'approved'", ...$target_teacher_ids));
                $plan_stats_returned  = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}sm_term_plans WHERE teacher_id IN ($id_placeholders) AND status = 'returned'", ...$target_teacher_ids));
                $plan_stats_rejected  = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}sm_term_plans WHERE teacher_id IN ($id_placeholders) AND status = 'rejected'", ...$target_teacher_ids));
            } else {
                $plan_stats_submitted = 0;
                $plan_stats_approved  = 0;
                $plan_stats_returned  = 0;
                $plan_stats_rejected  = 0;
            }
            $plan_stats_missing   = max(0, $plan_stats_total_req - $plan_stats_submitted);
            $plan_compliance_rate = $plan_stats_total_req > 0 ? round(($plan_stats_submitted / $plan_stats_total_req) * 100) : 0;
        ?>
        <!-- Administrative Compliance & Follow-up Statistics for Term Plans -->
        <div style="background: #ffffff; padding: 20px 24px; border-radius: 20px; border: 1px solid #e2e8f0; box-shadow: 0 4px 16px rgba(0,0,0,0.02); margin-bottom: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; border-bottom: 1px solid #f1f5f9; padding-bottom: 8px;">
                <h3 style="margin: 0; font-size: 15px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 8px;">
                    <span class="dashicons dashicons-chart-bar" style="color: #881337; font-size: 18px; width: 18px; height: 18px;"></span>
                    <span>إحصائيات الامتثال ومتابعة الخطط الفصلية (العام الدراسي <?php echo esc_html($active_academic_year); ?>)</span>
                </h3>
                <span style="font-size: 12px; font-weight: 800; color: #0284c7; background: #e0f2fe; padding: 3px 12px; border-radius: 9999px; border: 1px solid #bae6fd;">نسبة الالتزام الإجمالية: <?php echo $plan_compliance_rate; ?>%</span>
            </div>

            <div style="display: grid; grid-template-columns: repeat(6, 1fr); gap: 10px;">
                <div onclick="eessShowTermPlanStatDetails('required')" style="background: #f8fafc; padding: 12px; border-radius: 12px; border: 1px solid #e2e8f0; border-top: 3px solid #334155; text-align: center; cursor: pointer; transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                    <div style="font-size: 11px; color: #64748b; font-weight: 700; margin-bottom: 4px;">إجمالي عدد المعلمين</div>
                    <div style="font-size: 18px; font-weight: 900; color: #0f172a;"><?php echo $total_eligible_teachers; ?></div>
                </div>
                <div onclick="eessShowTermPlanStatDetails('submitted')" style="background: #f8fafc; padding: 12px; border-radius: 12px; border: 1px solid #e2e8f0; border-top: 3px solid #0284c7; text-align: center; cursor: pointer; transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                    <div style="font-size: 11px; color: #0369a1; font-weight: 700; margin-bottom: 4px;">الخطط المرفوعة</div>
                    <div style="font-size: 18px; font-weight: 900; color: #0284c7;"><?php echo $plan_stats_submitted; ?></div>
                </div>
                <div onclick="eessShowTermPlanStatDetails('approved')" style="background: #f8fafc; padding: 12px; border-radius: 12px; border: 1px solid #e2e8f0; border-top: 3px solid #16a34a; text-align: center; cursor: pointer; transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                    <div style="font-size: 11px; color: #166534; font-weight: 700; margin-bottom: 4px;">معتمدة رسمياً</div>
                    <div style="font-size: 18px; font-weight: 900; color: #16a34a;"><?php echo $plan_stats_approved; ?></div>
                </div>
                <div onclick="eessShowTermPlanStatDetails('returned')" style="background: #f8fafc; padding: 12px; border-radius: 12px; border: 1px solid #e2e8f0; border-top: 3px solid #d97706; text-align: center; cursor: pointer; transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                    <div style="font-size: 11px; color: #b45309; font-weight: 700; margin-bottom: 4px;">طلب تعديل</div>
                    <div style="font-size: 18px; font-weight: 900; color: #d97706;"><?php echo $plan_stats_returned; ?></div>
                </div>
                <div onclick="eessShowTermPlanStatDetails('rejected')" style="background: #f8fafc; padding: 12px; border-radius: 12px; border: 1px solid #e2e8f0; border-top: 3px solid #b91c1c; text-align: center; cursor: pointer; transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                    <div style="font-size: 11px; color: #991b1b; font-weight: 700; margin-bottom: 4px;">الخطط المرفوضة</div>
                    <div style="font-size: 18px; font-weight: 900; color: #b91c1c;"><?php echo $plan_stats_rejected; ?></div>
                </div>
                <div onclick="eessShowTermPlanStatDetails('missing')" style="background: #f8fafc; padding: 12px; border-radius: 12px; border: 1px solid #e2e8f0; border-top: 3px solid #dc2626; text-align: center; cursor: pointer; transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                    <div style="font-size: 11px; color: #991b1b; font-weight: 700; margin-bottom: 4px;">غير تسليم / متأخر</div>
                    <div style="font-size: 18px; font-weight: 900; color: #dc2626;"><?php echo $plan_stats_missing; ?></div>
                </div>
            </div>
        </div>
        <?php endif; ?>


    <!-- TEACHER TAB: SUBMITTED PLANS HISTORY (Teachers & Coordinators Only) -->
    <?php
    $roles = (array) wp_get_current_user()->roles;
    $is_teacher_or_coordinator = in_array('sm_teacher', $roles) || in_array('sm_coordinator', $roles);
    if ($is_teacher_or_coordinator):
    ?>
    <div id="panel-teacher-dashboard" class="term-plan-panel" style="display: block;">

        <div style="background: #ffffff; padding: 22px 26px; border-radius: 20px; border: 1px solid #e2e8f0; box-shadow: 0 4px 16px rgba(0,0,0,0.02);">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; flex-wrap: wrap; gap: 14px; border-bottom: 1px solid #f1f5f9; padding-bottom: 14px;">
                <div style="display: flex; align-items: center; gap: 16px; flex-wrap: wrap; flex: 1;">
                    <div>
                        <h3 style="margin: 0 0 4px 0; font-size: 16px; font-weight: 800; color: #0f172a;">سجل وأرشيف الخطط الفصلية والسنوية الخاصة بي</h3>
                        <p style="margin: 0; font-size: 12px; color: #64748b;">استعراض الخطط السابقة وإعادة تعديل المسودات أو الخطط التي حُددت للتعديل</p>
                    </div>

                    <!-- Compact Search Input Aligned Beside Title -->
                    <div style="min-width: 220px; flex: 1; max-width: 320px; margin-right: auto;">
                        <input type="text" id="eess-teacher-plan-search" onkeyup="eessFilterTeacherPlansTable()" class="sm-input" placeholder="بحث سريع في الأرشيف..." style="height: 36px; border-radius: 9999px !important; border: 1px solid #cbd5e1; font-size: 12px; padding: 0 14px; width: 100%;">
                    </div>
                </div>
            </div>

            <script>
var eessPlanSortAsc = false;
function eessTogglePlansTableSort() {
    eessPlanSortAsc = !eessPlanSortAsc;
    var lbl = document.getElementById('eess-sort-plans-lbl');
    if (lbl) lbl.innerText = eessPlanSortAsc ? 'الأقدم أولاً' : 'الأحدث أولاً';

    var tbody = document.querySelector('#eess-reviewer-plans-table tbody');
    if (!tbody) return;
    var rows = Array.from(tbody.querySelectorAll('tr.reviewer-plan-row'));

    rows.sort(function(a, b) {
        var tA = parseInt(a.getAttribute('data-timestamp') || '0');
        var tB = parseInt(b.getAttribute('data-timestamp') || '0');
        return eessPlanSortAsc ? (tA - tB) : (tB - tA);
    });

    rows.forEach(function(r) { tbody.appendChild(r); });
}

            function eessFilterTeacherPlansTable() {
                var q = document.getElementById('eess-teacher-plan-search').value.trim().toLowerCase();
                var rows = document.querySelectorAll('#eess-teacher-plans-table tbody tr');
                rows.forEach(function(row) {
                    if (row.cells.length < 2) return;
                    var text = row.textContent.toLowerCase();
                    row.style.display = text.includes(q) ? '' : 'none';
                });
            }
            </script>

            <div style="overflow-x: auto;">
                <table id="eess-teacher-plans-table" style="width: 100%; border-collapse: separate; border-spacing: 0; text-align: right;">
                    <thead>
                        <tr style="background: #212121; color: #ffffff;">
                            <th style="padding: 12px 16px; font-size: 12.5px; font-weight: 800; color: #ffffff; border-radius: 0 10px 0 0;">المادة والمعلم والتسكين</th>
                            <th style="padding: 12px 16px; font-size: 12.5px; font-weight: 800; color: #ffffff;">الفصل الدراسي والتاريخ</th>
                            <th style="padding: 12px 16px; font-size: 12.5px; font-weight: 800; color: #ffffff; text-align: center;">طريقة التسليم</th>
                            <th style="padding: 12px 16px; font-size: 12.5px; font-weight: 800; color: #ffffff; text-align: center;">الحالة</th>
                            <th style="padding: 12px 16px; font-size: 12.5px; font-weight: 800; color: #ffffff; text-align: center; border-radius: 10px 0 0 0;">الإجراءات السريعة</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($teacher_plans)): ?>
                            <tr>
                                <td colspan="5" style="padding: 40px; text-align: center; color: #94a3b8; font-weight: 700;">لا توجد خطط فصلية أو سنوية مسجلة لك حالياً. اضغط "إعداد الخطة" للبدء.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($teacher_plans as $tp):
                                $s_bg = '#f1f5f9'; $s_col = '#64748b'; $s_lbl = 'مسودة';
                                if ($tp->status === 'submitted') { $s_bg = '#e0f2fe'; $s_col = '#0369a1'; $s_lbl = 'مرفوعة للمراجعة'; }
                                elseif ($tp->status === 'approved') { $s_bg = '#dcfce7'; $s_col = '#15803d'; $s_lbl = 'معتمدة رسمياً'; }
                                elseif ($tp->status === 'returned') { $s_bg = '#fee2e2'; $s_col = '#b91c1c'; $s_lbl = 'طلب تعديل'; }

                                $teacher_school_name = get_user_meta($user_id, 'eess_school_name', true) ?: 'المدرسة الرئيسية';
                                $term_name = $arabic_term_names[intval($tp->term_number)] ?? ('الفصل ' . intval($tp->term_number));
                            ?>
                                <tr style="border-bottom: 1px solid #f1f5f9;" class="teacher-plan-row" data-status="<?php echo esc_attr($tp->status); ?>">
                                    <!-- Rich Multi-Line Subject & School Cell -->
                                    <td style="padding: 14px 16px;">
                                        <div style="font-weight: 800; font-size: 14px; color: #0f172a; margin-bottom: 6px;"><?php echo esc_html($tp->subject); ?></div>
                                        <div style="display: flex; gap: 6px; flex-wrap: wrap; align-items: center;">
                                            <span style="display: inline-flex; align-items: center; gap: 4px; padding: 2px 8px; border-radius: 6px; background: #f8fafc; color: #334155; border: 1px solid #cbd5e1; font-size: 10.5px; font-weight: 800;">
                                                <span class="dashicons dashicons-building" style="font-size: 12px; width: 12px; height: 12px; color: #64748b;"></span>
                                                <span><?php echo esc_html($teacher_school_name); ?></span>
                                            </span>
                                            <span style="display: inline-flex; align-items: center; padding: 2px 8px; border-radius: 6px; background: #fef2f2; color: #881337; border: 1px solid #fecdd3; font-size: 10.5px; font-weight: 800;">
                                                <?php echo esc_html($tp->grade); ?>
                                            </span>
                                        </div>
                                    </td>

                                    <!-- Term Name & Period Dates -->
                                    <td style="padding: 14px 16px;">
                                        <div style="font-weight: 800; font-size: 13px; color: #334155;"><?php echo esc_html($term_name); ?></div>
                                        <div style="font-size: 11px; color: #94a3b8; font-family: monospace; margin-top: 3px;">
                                            <?php echo esc_html($tp->start_date . ' إلى ' . $tp->end_date); ?>
                                        </div>
                                    </td>

                                    <!-- Progress Capsule -->
                                    <td style="padding: 14px 16px; text-align: center;">
                                        <span style="display: inline-flex; padding: 3px 10px; border-radius: 9999px; background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; font-weight: 900; font-size: 12px;">
                                            <?php echo intval($tp->completion_pct); ?>%
                                        </span>
                                    </td>

                                    <!-- Status Capsule -->
                                    <td style="padding: 14px 16px; text-align: center;">
                                        <span style="padding: 3px 10px; border-radius: 9999px; background: <?php echo $s_bg; ?>; color: <?php echo $s_col; ?>; font-weight: 800; font-size: 11px;">
                                            <?php echo $s_lbl; ?>
                                        </span>
                                    </td>

                                    <!-- Standardized Circular Action Buttons -->
                                    <td style="padding: 14px 16px; text-align: center;">
                                        <div class="sm-action-btn-group">
                                            <?php if (!empty($tp->plan_file_url) || $tp->planning_method === 'upload'): ?>
                                                <!-- Preview File Button -->
                                                <a href="<?php echo esc_url($tp->plan_file_url); ?>" target="_blank" title="معاينة ملف الخطة المرفوعة" class="sm-action-btn sm-action-btn-neutral">
                                                    <span class="dashicons dashicons-visibility"></span>
                                                </a>
                                                <!-- Direct Download File Button -->
                                                <a href="<?php echo esc_url($tp->plan_file_url); ?>" download title="تحميل ملف الخطة المرفوعة" class="sm-action-btn sm-action-btn-primary">
                                                    <span class="dashicons dashicons-download"></span>
                                                </a>
                                            <?php else: ?>
                                                <!-- Print System PDF Button -->
                                                <a href="<?php echo admin_url('admin-ajax.php?action=sm_print&print_type=term_plan&plan_id=' . $tp->id); ?>" target="_blank" title="طباعة وثيقة الخطة PDF" class="sm-action-btn sm-action-btn-success">
                                                    <span class="dashicons dashicons-printer"></span>
                                                </a>
                                            <?php endif; ?>

                                            <!-- Edit Button -->
                                            <button type="button" onclick="eessOpenPlanSetupWizard(<?php echo intval($tp->term_number); ?>)" title="تعديل الخطة" class="sm-action-btn sm-action-btn-warning">
                                                <span class="dashicons dashicons-edit"></span>
                                            </button>

                                            <!-- Delete Button (Far-Left in RTL) -->
                                            <button type="button" onclick="eessPromptDeletePlanModal(<?php echo $tp->id; ?>, '<?php echo esc_js($tp->subject . ' - ' . $arabic_term_names[intval($tp->term_number)]); ?>')" title="حذف الخطة" class="sm-action-btn sm-action-btn-danger">
                                                <span class="dashicons dashicons-trash"></span>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- REVIEWER TAB: SUBMITTED PLANS INSPECTION (Auto-Rendered with Tight Gap) -->
    <?php if ($is_reviewer): ?>
    <div id="panel-reviewer-dashboard" class="term-plan-panel" style="display: block; margin-top: 18px;">
        <div style="background: #ffffff; padding: 22px 26px; border-radius: 20px; border: 1px solid #e2e8f0; box-shadow: 0 4px 16px rgba(0,0,0,0.02);">

            <?php
            $term1_count = 0; $term2_count = 0; $term3_count = 0;
            foreach ($submitted_plans as $sp) {
                if (intval($sp->term_number) === 1) $term1_count++;
                elseif (intval($sp->term_number) === 2) $term2_count++;
                elseif (intval($sp->term_number) === 3) $term3_count++;
            }
            ?>
            <!-- Table Header & Live Search Engine Bar with Semester Counters -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; flex-wrap: wrap; gap: 14px; border-bottom: 1px solid #f1f5f9; padding-bottom: 14px;">
                <div>
                    <h3 style="margin: 0 0 4px 0; font-size: 16px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 8px;">
                        <span class="dashicons dashicons-shield" style="color: #881337; font-size: 18px; width: 18px; height: 18px;"></span>
                        <span>سجل الخطط الفصلية المرفوعة للمراجعة والاعتماد المباشر</span>
                    </h3>
                    <p style="margin: 0; font-size: 12px; color: #64748b;">متابعة اعتماد الخطط والموافقة الفورية، رفض، أو طلب تعديلات إدارية</p>
                </div>

                <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                    <!-- Semester Status Pastel Counters -->
                    <div style="display: flex; align-items: center; gap: 6px;">
                        <span style="font-size: 11px; font-weight: 800; color: #dc2626; background: #fef2f2; border: 1px solid #fecdd3; padding: 4px 10px; border-radius: 9999px;">
                            الفصل 1: <strong><?php echo $term1_count; ?></strong>
                        </span>
                        <span style="font-size: 11px; font-weight: 800; color: #0284c7; background: #e0f2fe; border: 1px solid #bae6fd; padding: 4px 10px; border-radius: 9999px;">
                            الفصل 2: <strong><?php echo $term2_count; ?></strong>
                        </span>
                        <span style="font-size: 11px; font-weight: 800; color: #16a34a; background: #dcfce7; border: 1px solid #bbf7d0; padding: 4px 10px; border-radius: 9999px;">
                            الفصل 3: <strong><?php echo $term3_count; ?></strong>
                        </span>
                    </div>

                    <!-- Sorting Control & Live Search Input -->
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <button type="button" onclick="eessTogglePlansTableSort()" title="تبديل الترتيب (من الأحدث للأقدم / العكس)" class="sm-btn" style="height: 38px; padding: 0 12px; border-radius: 9999px !important; border: 1px solid #cbd5e1; background: #ffffff; color: #475569; font-size: 12px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 4px;">
                            <span class="dashicons dashicons-sort" style="font-size: 15px; width: 15px; height: 15px;"></span>
                            <span id="eess-sort-plans-lbl">الأحدث أولاً</span>
                        </button>

                        <div style="position: relative; width: 220px;">
                            <input type="text" id="eess-reviewer-plans-search" onkeyup="eessFilterReviewerPlansTable()" placeholder="ابحث باسم المدرس، المادة، أو الصف..." style="width: 100%; height: 38px; padding: 0 36px 0 14px; border: 1px solid #cbd5e1; border-radius: 9999px !important; font-size: 12.5px; outline: none; background: #f8fafc;">
                            <span style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; pointer-events: none; display: flex; align-items: center;">
                                <span class="dashicons dashicons-search" style="font-size: 15px; width: 15px; height: 15px;"></span>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Table Wrapper -->
            <div style="overflow-x: auto;">
                <table id="eess-reviewer-plans-table" style="width: 100%; border-collapse: separate; border-spacing: 0; text-align: right;">
                    <thead>
                        <tr style="background: #212121; color: #ffffff;">
                            <th style="padding: 12px 12px; font-size: 12.5px; font-weight: 800; color: #ffffff; text-align: center; border-radius: 0 10px 0 0; width: 45px;">#</th>
                            <th style="padding: 12px 16px; font-size: 12.5px; font-weight: 800; color: #ffffff;">المدرس والرقم الوظيفي</th>
                            <th style="padding: 12px 16px; font-size: 12.5px; font-weight: 800; color: #ffffff;">المدرسة والمادة والتسكين</th>
                            <th style="padding: 12px 16px; font-size: 12.5px; font-weight: 800; color: #ffffff;">الفصل الدراسي</th>
                            <th style="padding: 12px 16px; font-size: 12.5px; font-weight: 800; color: #ffffff; text-align: center;">الجهاز</th>
                            <th style="padding: 12px 16px; font-size: 12.5px; font-weight: 800; color: #ffffff; text-align: center;">الحالة</th>
                            <th style="padding: 12px 16px; font-size: 12.5px; font-weight: 800; color: #ffffff; text-align: center; border-radius: 10px 0 0 0;">الإجراءات السريعة</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($submitted_plans)): ?>
                            <tr>
                                <td colspan="7" style="padding: 40px; text-align: center; color: #94a3b8; font-weight: 700;">لا توجد خطط فصلية مرفوعة للمراجعة حالياً.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($submitted_plans as $sp_idx => $sp):
                                $emp_code = get_user_meta($sp->teacher_id, 'eess_employee_number', true) ?: (get_user_meta($sp->teacher_id, 'sm_teacher_id', true) ?: 'EMP-' . $sp->teacher_id);
                                $t_school = get_user_meta($sp->teacher_id, 'eess_school_name', true) ?: 'المدرسة الرئيسية';

                                $s_bg = '#f1f5f9'; $s_col = '#64748b'; $s_lbl = 'مسودة';
                                if ($sp->status === 'submitted') { $s_bg = '#e0f2fe'; $s_col = '#0369a1'; $s_lbl = 'مرفوعة للمراجعة'; }
                                elseif ($sp->status === 'approved') { $s_bg = '#dcfce7'; $s_col = '#15803d'; $s_lbl = 'معتمدة رسمياً'; }
                                elseif ($sp->status === 'returned') { $s_bg = '#fee2e2'; $s_col = '#b91c1c'; $s_lbl = 'طلب تعديل'; }
                                elseif ($sp->status === 'rejected') { $s_bg = '#fef2f2'; $s_col = '#991b1b'; $s_lbl = 'مرفوضة'; }

                                $term_arabic = $arabic_term_names[intval($sp->term_number)] ?? ('الفصل ' . intval($sp->term_number));
                                $sp_timestamp = strtotime($sp->updated_at ?: $sp->created_at);
                            ?>
                                <tr style="border-bottom: 1px solid #f1f5f9;" class="reviewer-plan-row" data-timestamp="<?php echo $sp_timestamp; ?>">
                                    <!-- Index Number Column -->
                                    <td style="padding: 12px 12px; text-align: center; font-size: 12px; font-weight: 800; color: #64748b;">
                                        <?php echo ($sp_idx + 1); ?>
                                    </td>

                                    <!-- Teacher Name & Employee ID Pastel Capsule (No "رقم الموظف" text) -->
                                    <td style="padding: 12px 16px;">
                                        <a href="javascript:void(0)" onclick="window.eessOpenUnifiedUserModal('edit_user', <?php echo $sp->teacher_id; ?>)" style="font-weight: 800; font-size: 13.5px; color: #0f172a; text-decoration: none;" onmouseover="this.style.color='#0284c7'; this.style.textDecoration='underline';" onmouseout="this.style.color='#0f172a'; this.style.textDecoration='none';">
                                            <?php echo esc_html($sp->teacher_name ?: 'مدرس غير محدد'); ?>
                                        </a>
                                        <div style="margin-top: 4px; display: flex; gap: 4px; align-items: center; flex-wrap: wrap;">
                                            <span style="display: inline-flex; align-items: center; padding: 2px 8px; border-radius: 6px; background: #fef2f2; color: #881337; border: 1px solid #fecdd3; font-size: 10.5px; font-weight: 800; font-family: monospace;">
                                                <?php echo esc_html($emp_code); ?>
                                            </span>
                                            <?php
                                            $app_year = intval(get_user_meta($sp->teacher_id, 'eess_appointment_year', true) ?: get_user_meta($sp->teacher_id, 'sm_appointment_year', true));
                                            $exp_years = $app_year > 0 ? (intval(date('Y')) - $app_year) : 0;
                                            ?>
                                            <span style="display: inline-flex; align-items: center; padding: 2px 8px; border-radius: 6px; background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; font-size: 10.5px; font-weight: 800;">
                                                خبرة: <?php echo $exp_years > 0 ? ($exp_years . ' سنوات') : 'جديد'; ?>
                                            </span>
                                            <?php
                                            $is_contacted_plan = get_user_meta($sp->teacher_id, 'eess_contacted_plan_' . $sp->id, true);
                                            if ($is_contacted_plan): ?>
                                                <span style="display:inline-flex; align-items:center; padding:2px 6px; background:#dcfce7; color:#15803d; border-radius:4px; font-size:9.5px; font-weight:800; border:1px solid #bbf7d0;">✓ تم التواصل</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>

                                    <!-- School, Subject & Grade Pastel Capsules -->
                                    <td style="padding: 12px 16px;">
                                        <div style="font-size: 13px; font-weight: 800; color: #0f172a; margin-bottom: 5px; display: flex; align-items: center; gap: 5px;">
                                            <span class="dashicons dashicons-building" style="font-size: 15px; width: 15px; height: 15px; color: #475569;"></span>
                                            <span><?php echo esc_html($t_school); ?></span>
                                        </div>
                                        <div style="display: flex; gap: 6px; flex-wrap: wrap; align-items: center;">
                                            <span style="display: inline-flex; align-items: center; padding: 2px 8px; border-radius: 6px; background: #fef2f2; color: #881337; border: 1px solid #fecdd3; font-size: 11px; font-weight: 800;">
                                                <?php echo esc_html($sp->subject); ?>
                                            </span>
                                            <span style="display: inline-flex; align-items: center; padding: 2px 8px; border-radius: 6px; background: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; font-size: 11px; font-weight: 800;">
                                                <?php echo esc_html($sp->grade); ?>
                                            </span>
                                        </div>
                                    </td>

                                    <!-- Arabic Term Name & Submission Date Pastel Capsule (Abbreviated AM/PM) -->
                                    <td style="padding: 12px 16px;">
                                        <div style="font-size: 13px; font-weight: 800; color: #334155; margin-bottom: 4px;"><?php echo esc_html($term_arabic); ?></div>
                                        <?php
                                        $sub_dt = $sp->updated_at ?: $sp->created_at;
                                        $raw_formatted = date_i18n('j M Y • h:i A', strtotime($sub_dt));
                                        $formatted_date_time = str_replace(array('AM', 'PM', 'صباحًا', 'مساءً'), array('ص', 'م', 'ص', 'م'), $raw_formatted);
                                        ?>
                                        <span style="display: inline-flex; align-items: center; padding: 2px 8px; border-radius: 6px; background: #f8fafc; color: #64748b; border: 1px solid #e2e8f0; font-size: 10px; font-weight: 700; font-family: monospace;">
                                            📅 <?php echo esc_html($formatted_date_time); ?>
                                        </span>
                                    </td>

                                    <!-- Submission Method Pastel Capsule -->
                                    <td style="padding: 12px 16px; text-align: center;">
                                        <?php
                                        $method_key = $sp->planning_method ?? 'create';
                                        $m_label = 'كمبيوتر'; $m_bg = '#e0f2fe'; $m_col = '#0369a1'; $m_icon = 'dashicons-desktop';
                                        if (strpos(strtolower($method_key), 'mobile') !== false) {
                                            $m_label = 'موبايل'; $m_bg = '#dcfce7'; $m_col = '#15803d'; $m_icon = 'dashicons-smartphone';
                                        } elseif (strpos(strtolower($method_key), 'ipad') !== false || strpos(strtolower($method_key), 'tablet') !== false) {
                                            $m_label = 'آيباد'; $m_bg = '#fef3c7'; $m_col = '#b45309'; $m_icon = 'dashicons-tablet';
                                        }
                                        ?>
                                        <span style="display: inline-flex; align-items: center; gap: 4px; padding: 3px 10px; border-radius: 9999px; background: <?php echo $m_bg; ?>; color: <?php echo $m_col; ?>; font-weight: 800; font-size: 11px;">
                                            <span class="dashicons <?php echo $m_icon; ?>" style="font-size: 13px; width: 13px; height: 13px;"></span>
                                            <span><?php echo $m_label; ?></span>
                                        </span>
                                    </td>

                                    <!-- Expanded Status Capsule -->
                                    <td style="padding: 12px 16px; text-align: center;">
                                        <span style="padding: 3px 10px; border-radius: 9999px; background: <?php echo $s_bg; ?>; color: <?php echo $s_col; ?>; font-weight: 800; font-size: 11px;">
                                            <?php echo $s_lbl; ?>
                                        </span>
                                    </td>

                                    <!-- Quick Action Circular Buttons (Approve, Reject, Modification Request) -->
                                    <td style="padding: 12px 16px; text-align: center;">
                                        <div class="sm-action-btn-group">
                                            <?php if (!empty($sp->plan_file_url) || $sp->planning_method === 'upload'): ?>
                                                <!-- Preview File Button (For uploaded file plans only) -->
                                                <a href="<?php echo esc_url($sp->plan_file_url); ?>" target="_blank" title="معاينة ملف الخطة المرفوعة" class="sm-action-btn sm-action-btn-neutral">
                                                    <span class="dashicons dashicons-visibility"></span>
                                                </a>
                                                <!-- Direct Download File Button -->
                                                <a href="<?php echo esc_url($sp->plan_file_url); ?>" download title="تحميل ملف الخطة المرفوعة" class="sm-action-btn sm-action-btn-primary">
                                                    <span class="dashicons dashicons-download"></span>
                                                </a>
                                            <?php else: ?>
                                                <!-- Administrative Direct Print PDF Button (System-created plans only) -->
                                                <a href="<?php echo admin_url('admin-ajax.php?action=sm_print&print_type=term_plan&plan_id=' . $sp->id); ?>" target="_blank" title="طباعة الخطة المعتمدة رسمياً" class="sm-action-btn sm-action-btn-neutral">
                                                    <span class="dashicons dashicons-printer"></span>
                                                </a>
                                            <?php endif; ?>


                                            <!-- Approve Button (Positive Green) -->
                                            <button type="button" onclick="eessDirectReviewPlan(<?php echo $sp->id; ?>, 'approved')" title="اعتماد الخطة رسمياً" class="sm-action-btn sm-action-btn-success">
                                                <span class="dashicons dashicons-yes-alt"></span>
                                            </button>

                                            <!-- Reject Button with Reason Notes Modal (Danger Red) -->
                                            <button type="button" onclick="eessOpenModificationNotesModal(<?php echo $sp->id; ?>, '<?php echo esc_js($sp->teacher_name); ?>', 'rejected')" title="رفض الخطة وتدوين الملاحظات" class="sm-action-btn sm-action-btn-danger">
                                                <span class="dashicons dashicons-no-alt"></span>
                                            </button>

                                            <?php if ($is_admin): ?>
                                            <!-- Copy Record Button (System Administrator Only) -->
                                            <button type="button" onclick="eessOpenCopyRecordModal('term_plan', <?php echo $sp->id; ?>, '<?php echo esc_js($sp->teacher_name . ' - ' . $sp->subject); ?>')" title="نسخ الخطة ونقلها لمستخدم آخر" class="sm-action-btn sm-action-btn-primary">
                                                <span class="dashicons dashicons-admin-page"></span>
                                            </button>
                                            <?php endif; ?>

                                            <!-- WhatsApp Direct Contact Button (Positioned immediately to the left of Delete in RTL) -->
                                            <?php
                                            $tp_phone = get_user_meta($sp->teacher_id, 'phone_number', true) ?: (get_user_meta($sp->teacher_id, 'sm_phone', true) ?: (get_user_meta($sp->teacher_id, 'phone', true) ?: ''));
                                            $clean_tp_phone = preg_replace('/[^0-9]/', '', $tp_phone);
                                            if (empty($clean_tp_phone) || strlen($clean_tp_phone) < 8) $clean_tp_phone = '971500000000';
                                            $wa_plan_msg = rawurlencode("السلام عليكم، كيف حالك؟\nتحية طيبة من نظام إدارة المدارس. نود التواصل معك بخصوص متابعتك التعليمية.");
                                            $wa_plan_url = "https://wa.me/" . $clean_tp_phone . "?text=" . $wa_plan_msg;
                                            ?>
                                            <a href="<?php echo esc_url($wa_plan_url); ?>" target="_blank" onclick="eessMarkTeacherContacted(<?php echo $sp->teacher_id; ?>, 'plan', <?php echo $sp->id; ?>)" class="sm-action-btn sm-action-btn-success" title="تواصل مباشر عبر واتساب مع المعلم">
                                                <span class="dashicons dashicons-whatsapp"></span>
                                            </a>

                                            <!-- Delete Button (Far-Left in RTL) -->
                                            <button type="button" onclick="eessPromptDeletePlanModal(<?php echo $sp->id; ?>, '<?php echo esc_js($sp->teacher_name . ' - ' . $sp->subject); ?>')" title="حذف الخطة نهائياً" class="sm-action-btn sm-action-btn-danger">
                                                <span class="dashicons dashicons-trash"></span>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

</div>

<!-- Professional Multi-Step Plan Setup Wizard Modal -->
<div id="eess-plan-setup-modal" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.75); backdrop-filter: blur(5px); z-index: 999999; justify-content: center; align-items: center; padding: 20px; box-sizing: border-box; font-family: 'Cairo', sans-serif; direction: rtl;">
    <div style="background: #ffffff; border-radius: 20px; max-width: 820px; width: 100%; border: 1px solid #cbd5e1; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.3); overflow: hidden; display: flex; flex-direction: column; max-height: 88vh;">
        <!-- Thinner Flush Full-Width Wizard Header Banner with White Icon & Subtitle -->
        <div style="background: #0f172a; color: #ffffff; padding: 16px 24px; border-bottom: 1px solid #1e293b; display: flex; justify-content: space-between; align-items: center; width: 100%; box-sizing: border-box;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <span class="dashicons dashicons-calendar-alt" style="color: #ffffff; font-size: 22px; width: 22px; height: 22px; margin: 0;"></span>
                <div>
                    <h3 style="margin: 0; font-size: 16px; font-weight: 800; color: #ffffff; font-family: 'Cairo', sans-serif;">إعداد خطة الفصل الدراسي</h3>
                    <p style="margin: 3px 0 0 0; font-size: 11.5px; color: #94a3b8; font-weight: 600;">إنشاء وإعداد الخطة الدراسية خطوة بخطوة وفق بياناتك الأكاديمية المعتمدة.</p>
                </div>
            </div>
            <button type="button" onclick="eessClosePlanSetupWizard()" style="background: none; border: none; color: #ffffff; font-size: 26px; cursor: pointer; line-height: 1;">&times;</button>
        </div>

        <!-- Full-Width Balanced RTL Stepper Track (Hidden until method selection) -->
        <div id="eess-wizard-stepper-track" style="display: none; background: #f8fafc; padding: 14px 24px; border-bottom: 1px solid #e2e8f0; position: relative;">
            <div style="position: absolute; top: 50%; left: 40px; right: 40px; height: 2px; background: #e2e8f0; transform: translateY(-50%); z-index: 1;"></div>
            <div style="position: relative; z-index: 2; display: flex; justify-content: space-between; align-items: center; width: 100%;">
                <div id="wiz-step-node-1" class="eess-prep-step-indicator active" style="font-weight: 800; font-size: 11.5px; color: #881337; display: flex; flex-direction: column; align-items: center; gap: 4px; background: #f8fafc; padding: 0 6px;">
                    <span id="wiz-step-num-1" style="background: #881337; color: white; width: 28px; height: 28px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 800; box-shadow: 0 0 0 4px #f8fafc;">1</span>
                    <span id="wiz-step-lbl-1">البيانات والجدول</span>
                </div>
                <div id="wiz-step-node-2" class="eess-prep-step-indicator" style="font-weight: 700; font-size: 11.5px; color: #94a3b8; display: flex; flex-direction: column; align-items: center; gap: 4px; background: #f8fafc; padding: 0 6px;">
                    <span id="wiz-step-num-2" style="background: #e2e8f0; color: #475569; width: 28px; height: 28px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 800; box-shadow: 0 0 0 4px #f8fafc;">2</span>
                    <span id="wiz-step-lbl-2">المحتوى والأسابيع</span>
                </div>
                <div id="wiz-step-node-3" class="eess-prep-step-indicator" style="font-weight: 700; font-size: 11.5px; color: #94a3b8; display: flex; flex-direction: column; align-items: center; gap: 4px; background: #f8fafc; padding: 0 6px;">
                    <span id="wiz-step-num-3" style="background: #e2e8f0; color: #475569; width: 28px; height: 28px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 800; box-shadow: 0 0 0 4px #f8fafc;">3</span>
                    <span id="wiz-step-lbl-3">المراجعة والاعتماد</span>
                </div>
            </div>
        </div>

        <!-- Wizard Body Container -->
        <form id="eess-wizard-setup-form" style="padding: 24px; overflow-y: auto; flex: 1;" onsubmit="eessSaveWizardPlanSubmit(event)">
            <input type="hidden" name="plan_id" id="tp_plan_id" value="0">
            <?php
                $assigned_teacher_subject = get_user_meta($user_id, 'sm_specialization', true) ?: (get_user_meta($user_id, 'specialization', true) ?: (get_user_meta($user_id, 'subject', true) ?: 'التربية البدنية والصحية'));
                $assigned_teacher_grade   = get_user_meta($user_id, 'sm_grade_level', true) ?: (get_user_meta($user_id, 'grade', true) ?: 'الصف العاشر');
                $assigned_teacher_school  = get_user_meta($user_id, 'eess_school_name', true) ?: 'المدرسة الرئيسية';
                $is_pe_subject = (mb_strpos($assigned_teacher_subject, 'بدنية') !== false || mb_strpos($assigned_teacher_subject, 'رياضة') !== false || mb_strpos($assigned_teacher_subject, 'Health') !== false || mb_strpos($assigned_teacher_subject, 'Physical') !== false);
                $default_weekly_lessons = $is_pe_subject ? 1 : 2;
            ?>
            <input type="hidden" id="wiz_academic_year" value="<?php echo esc_attr($active_academic_year); ?>">
            <input type="hidden" id="wiz_subject" value="<?php echo esc_attr($assigned_teacher_subject); ?>">

            <!-- Initial Method Selection Screen (Pure Card Selection Only) -->
            <div id="wiz-step-method-select" style="display: block; text-align: center; padding: 10px 0;">
                <h4 style="margin: 0 0 6px 0; font-size: 16px; font-weight: 800; color: #0f172a; text-align: center;">مرحباً أ. <?php echo esc_html($user->display_name); ?> — إعداد خطة الفصل الدراسي</h4>
                <p style="margin: 0 0 24px 0; font-size: 13px; color: #64748b; line-height: 1.6; text-align: center;">يرجى اختيار طريقة إعداد الخطة المناسبة لك للبدء:</p>

                <!-- Active File Upload Mode Only -->
                <div style="display: flex; justify-content: center; margin-bottom: 10px;">
                    <!-- Option 1: Upload Ready-Made Plan -->
                    <div id="eess-method-card-upload" onclick="eessChooseMethodAndProceed('upload')" style="background: #ffffff; border: 2px solid #0284c7; border-radius: 16px; padding: 22px 28px; cursor: pointer; transition: all 0.2s ease; text-align: center; max-width: 480px; width: 100%; box-shadow: 0 4px 12px rgba(2,132,199,0.08);">
                        <div style="width: 52px; height: 52px; background: #e0f2fe; border-radius: 14px; display: inline-flex; align-items: center; justify-content: center; color: #0284c7; margin-bottom: 12px;">
                            <span class="dashicons dashicons-upload" style="font-size: 26px; width: 26px; height: 26px;"></span>
                        </div>
                        <h4 style="margin: 0 0 6px 0; font-size: 15px; font-weight: 800; color: #0f172a;">رفع خطة جاهزة (PDF / Word)</h4>
                        <p style="margin: 0; font-size: 12px; color: #64748b; line-height: 1.5;">رفع وثيقة خطة مجهزة ومكتملة سابقاً مباشرة للمراجعة والاعتماد.</p>
                    </div>
                </div>

                <input type="hidden" name="planning_method" id="wiz_planning_method" value="create">
            </div>

            <!-- Step 1: Academic Data & Semester Schedule + Multi-Grade Selection -->
            <div id="wiz-step-1" class="wiz-step-content" style="display: none;">
                <h4 style="margin: 0 0 6px 0; font-size: 14px; font-weight: 800; color: #0f172a;">الخطوة 1: البيانات الأكاديمية والجدول الزمني</h4>
                <p style="margin: 0 0 16px 0; font-size: 12px; color: #64748b;">تحديد المناهج والصفوف المستهدفة وجدول الفصل الدراسي.</p>

                <!-- Multi-Grade Capsule Selection (KG to Grade 12) -->
                <div style="margin-bottom: 16px;">
                    <label class="sm-label" style="font-weight: 800; font-size: 12.5px; color: #334155; margin-bottom: 8px; display: block;">الصفوف الدراسية المشمولة بالخطة (اختر المناهج/الصفوف المستهدفة) <span style="color:#ef4444;">*</span></label>

                    <?php
                    $all_grade_options = array(
                        'مرحلة الروضة', 'الصف الأول', 'الصف الثاني', 'الصف الثالث',
                        'الصف الرابع', 'الصف الخامس', 'الصف السادس', 'الصف السابع',
                        'الصف الثامن', 'الصف التاسع', 'الصف العاشر', 'الصف الحادي عشر', 'الصف الثاني عشر'
                    );
                    ?>
                    <div style="display: flex; flex-wrap: wrap; gap: 8px; background: #ffffff; padding: 12px; border-radius: 12px; border: 1px solid #cbd5e1; max-height: 130px; overflow-y: auto;">
                        <?php foreach ($all_grade_options as $g_opt): ?>
                            <label class="eess-grade-capsule-label" style="display: inline-flex; align-items: center; gap: 6px; padding: 5px 12px; border-radius: 9999px; background: #f1f5f9; border: 1px solid #cbd5e1; font-size: 11.5px; font-weight: 700; color: #334155; cursor: pointer; user-select: none;">
                                <input type="checkbox" name="target_grades[]" value="<?php echo esc_attr($g_opt); ?>" onchange="eessTogglePlanGradeCapsule(this)" style="display: none;">
                                <span><?php echo esc_html($g_opt); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <input type="hidden" id="wiz_grade" value="<?php echo esc_attr($assigned_teacher_grade); ?>">
                <input type="hidden" id="wiz_weekly_lessons" value="<?php echo $default_weekly_lessons; ?>">

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                    <div>
                        <label class="sm-label" style="font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 5px; display: block;">الفصل الدراسي المحدد (التقويم المعتمد: 3 فصول) *</label>
                        <select id="wiz_term_number" class="sm-select" disabled style="height: 42px; border-radius: 9999px !important; border: 1px solid #cbd5e1; padding: 0 16px; font-size: 13px; text-align: right; direction: rtl; box-sizing: border-box; background: #f8fafc; font-weight: 800; color: #0f172a;">
                            <option value="1">الفصل الدراسي الأول (Term 1)</option>
                            <option value="2">الفصل الدراسي الثاني (Term 2)</option>
                            <option value="3">الفصل الدراسي الثالث (Term 3)</option>
                        </select>
                        <input type="hidden" id="wiz_num_terms" value="3">
                    </div>
                    <div>
                        <label class="sm-label" style="font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 5px; display: block;">تاريخ بداية الفصل *</label>
                        <input type="date" id="wiz_start_date" onchange="wizCalculateWeeksAuto()" class="sm-input" required style="height: 42px; border-radius: 9999px !important; border: 1px solid #cbd5e1; padding: 0 16px; font-size: 12.5px; text-align: right; box-sizing: border-box;">
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                    <div>
                        <label class="sm-label" style="font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 5px; display: block;">تاريخ نهاية الفصل *</label>
                        <input type="date" id="wiz_end_date" onchange="wizCalculateWeeksAuto()" class="sm-input" required style="height: 42px; border-radius: 9999px !important; border: 1px solid #cbd5e1; padding: 0 16px; font-size: 12.5px; text-align: right; box-sizing: border-box;">
                    </div>
                    <div style="display: flex; align-items: flex-end;">
                        <div style="width: 100%; background: #f0f9ff; border: 1px solid #bae6fd; padding: 10px 16px; border-radius: 9999px; font-size: 12.5px; color: #0369a1; font-weight: 700; text-align: center;">
                            إجمالي الأسابيع المحسوبة: <strong id="wiz_weeks_count_label" style="color: #2563eb; font-size: 14px;">0 أسابيع</strong>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Step 2 -->
            <div id="wiz-step-2" class="wiz-step-content" style="display: none;">
                <!-- Option A: Upload File Field -->
                <div id="wiz-upload-step-fields" style="display: none;">
                    <h4 style="margin: 0 0 6px 0; font-size: 14px; font-weight: 800; color: #0f172a;">الخطوة 2: رفع ملف الخطة الدراسية المكتملة</h4>
                    <p style="margin: 0 0 14px 0; font-size: 12px; color: #64748b;">يرجى رفع الخطة بصيغة PDF أو Word فقط للمراجعة المباشرة والاعتماد.</p>
                    <div style="background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 14px; padding: 18px; margin-bottom: 16px;">
                        <label style="display: block; font-weight: 800; font-size: 12.5px; color: #0369a1; margin-bottom: 6px;">اختر ملف الخطة الدراسية (PDF, DOC, DOCX) <span style="color:#ef4444;">*</span></label>
                        <input type="file" name="plan_document_file" id="wiz_plan_document_file" accept=".pdf,.doc,.docx" class="sm-input" onchange="eessValidateUploadFile(this)" style="height: 42px; border-radius: 10px; border: 1px solid #cbd5e1; background: #ffffff; font-size: 12px; padding: 6px 12px; width: 100%; box-sizing: border-box;">
                        <div id="wiz_file_status_preview" style="display: none; margin-top: 10px; font-size: 12px; font-weight: 700; color: #166534; background: #dcfce7; padding: 8px 12px; border-radius: 8px; border: 1px solid #bbf7d0;"></div>
                    </div>
                </div>

                <!-- Option B: System Content Fields -->
                <div id="wiz-create-step-fields" style="display: none;">
                    <h4 style="margin: 0 0 6px 0; font-size: 14px; font-weight: 800; color: #0f172a;">الخطوة 2: محتوى الخطة والتوزيع الأسبوعي</h4>
                    <p style="margin: 0 0 14px 0; font-size: 12px; color: #64748b;">أضف موضوعات الدروس والنبذة الخاصة بكل أسبوع باختيار الاقتراحات التلقائية للمادة أو الكتابة مباشرة.</p>
                    <div id="wiz_weekly_inputs_grid" style="display: flex; flex-direction: column; gap: 14px; max-height: 45vh; overflow-y: auto; padding-right: 5px;">
                        <!-- Generated via JS -->
                    </div>
                </div>
            </div>

            <!-- Step 3 -->
            <div id="wiz-step-3" class="wiz-step-content" style="display: none;">
                <h4 style="margin: 0 0 6px 0; font-size: 14px; font-weight: 800; color: #0f172a;">الخطوة 3: المراجعة النهائية والتأكيد والتقديم</h4>
                <p style="margin: 0 0 14px 0; font-size: 12px; color: #64748b;">راجع جميع البيانات المكتملة أدناه قبل رفع الخطة رسمياً للاعتماد الإداري.</p>

                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px; margin-bottom: 15px; font-size: 13px;">
                    <div style="margin-bottom: 10px;"><strong>المادة والصف الدراسي:</strong> <span id="wiz_rev_subj_grade" style="color: #0284c7; font-weight: 800;">---</span></div>
                    <div style="margin-bottom: 10px;"><strong>الفصل والتاريخ:</strong> <span id="wiz_rev_dates" style="font-weight: 700;">---</span></div>
                    <div style="margin-bottom: 10px;"><strong>عدد الأسابيع / نوع التقديم:</strong> <span id="wiz_rev_weeks" style="font-weight: 800; color: #15803d;">---</span></div>
                    <div style="color: #16a34a; font-weight: 700; margin-top: 12px; background: #dcfce7; border: 1px solid #bbf7d0; padding: 10px; border-radius: 8px; display: flex; align-items: center; gap: 6px;">
                        <span class="dashicons dashicons-yes-alt"></span>
                        <span>تم حفظ جميع البيانات المدخلة وتجهيزها للارسال والاعتماد.</span>
                    </div>
                </div>
            </div>

            <script>
            function eessChooseMethodAndProceed(method) {
                document.getElementById('wiz_planning_method').value = method;
                document.getElementById('wiz-step-method-select').style.display = 'none';
                document.getElementById('eess-wizard-stepper-track').style.display = 'block';

                if (method === 'upload') {
                    // Option A Workflow Labels
                    document.getElementById('wiz-step-lbl-1').innerText = 'البيانات والأجندة';
                    document.getElementById('wiz-step-lbl-2').innerText = 'رفع ملف الخطة';
                    document.getElementById('wiz-step-lbl-3').innerText = 'تأكيد الخطة وتقديمها';

                    document.getElementById('wiz-upload-step-fields').style.display = 'block';
                    document.getElementById('wiz-create-step-fields').style.display = 'none';
                } else {
                    // Option B Workflow Labels
                    document.getElementById('wiz-step-lbl-1').innerText = 'البيانات والجدول';
                    document.getElementById('wiz-step-lbl-2').innerText = 'المحتوى والأسابيع';
                    document.getElementById('wiz-step-lbl-3').innerText = 'المراجعة والاعتماد';

                    document.getElementById('wiz-upload-step-fields').style.display = 'none';
                    document.getElementById('wiz-create-step-fields').style.display = 'block';
                }

                wizCurrentStep = 1;
                updateWizardUI();
            }

            function eessSelectPlanningMethod(method) {
                document.getElementById('wiz_planning_method').value = method;
                var cardUpload = document.getElementById('eess-method-card-upload');
                var cardCreate = document.getElementById('eess-method-card-create');
                var uploadBlock = document.getElementById('eess-upload-method-block');
                var createBlock = document.getElementById('eess-create-method-block');
                var stepperTrack = document.getElementById('eess-wizard-stepper-track');

                if (method === 'upload') {
                    if (cardUpload) { cardUpload.style.background = '#f0f9ff'; cardUpload.style.borderColor = '#0284c7'; }
                    if (cardCreate) { cardCreate.style.background = '#ffffff'; cardCreate.style.borderColor = '#cbd5e1'; }
                    if (uploadBlock) uploadBlock.style.display = 'block';
                    if (createBlock) createBlock.style.display = 'none';
                    if (stepperTrack) stepperTrack.style.display = 'none';

                    // Directly enable submit button on Step 1 for upload method
                    var submitBtn = document.getElementById('wiz-submit-btn');
                    var nextBtn = document.getElementById('wiz-next-btn');
                    if (submitBtn) submitBtn.style.display = 'inline-flex';
                    if (nextBtn) nextBtn.style.display = 'none';
                } else {
                    if (cardCreate) { cardCreate.style.background = '#fef2f2'; cardCreate.style.borderColor = '#881337'; }
                    if (cardUpload) { cardUpload.style.background = '#ffffff'; cardUpload.style.borderColor = '#cbd5e1'; }
                    if (uploadBlock) uploadBlock.style.display = 'none';
                    if (createBlock) createBlock.style.display = 'block';
                    if (stepperTrack) stepperTrack.style.display = 'block';

                    var submitBtn = document.getElementById('wiz-submit-btn');
                    var nextBtn = document.getElementById('wiz-next-btn');
                    if (submitBtn) submitBtn.style.display = 'none';
                    if (nextBtn) nextBtn.style.display = 'inline-flex';
                }
            }

            function eessTogglePlanGradeCapsule(cb) {
                var parentLabel = cb.closest('.eess-grade-capsule-label');
                if (!parentLabel) return;
                if (cb.checked) {
                    parentLabel.style.background = '#881337';
                    parentLabel.style.borderColor = '#881337';
                    parentLabel.style.color = '#ffffff';
                } else {
                    parentLabel.style.background = '#f1f5f9';
                    parentLabel.style.borderColor = '#cbd5e1';
                    parentLabel.style.color = '#334155';
                }
            }

            function eessValidateUploadFile(input) {
                var preview = document.getElementById('wiz_file_status_preview');
                if (input.files && input.files[0]) {
                    var file = input.files[0];
                    var ext = file.name.split('.').pop().toLowerCase();
                    if (!['pdf', 'doc', 'docx'].includes(ext)) {
                        alert('يرجى اختيار ملف بصيغة PDF أو Word فقط.');
                        input.value = '';
                        if (preview) preview.style.display = 'none';
                        return;
                    }
                    if (preview) {
                        preview.innerHTML = '📄 تم اختيار الملف: <strong>' + file.name + '</strong> (' + Math.round(file.size / 1024) + ' كيلوبايت)';
                        preview.style.display = 'block';
                    }
                } else if (preview) {
                    preview.style.display = 'none';
                }
            }
            </script>


            <!-- Wizard Footer Buttons (RTL structure: Next/Submit on far-left, Previous on far-right) -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 20px; padding-top: 15px; border-top: 1px solid #e2e8f0;">
                <div>
                    <button type="button" id="wiz-next-btn" onclick="wizNav(1)" class="sm-btn" style="background: #881337; color: #ffffff !important; border: none; border-radius: 9999px !important; padding: 8px 24px; font-weight: 800; font-size: 13px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
                        <span>المتابعة للخطوة التالية</span>
                        <span class="dashicons dashicons-arrow-left-alt2" style="font-size: 15px; width: 15px; height: 15px;"></span>
                    </button>
                    <button type="submit" id="wiz-submit-btn" class="sm-btn" style="background: #dc2626; color: #ffffff !important; border: none; border-radius: 9999px !important; padding: 10px 28px; font-weight: 800; font-size: 13.5px; cursor: pointer; display: none; align-items: center; gap: 8px; box-shadow: 0 4px 12px rgba(220,38,38,0.2);">
                        <span class="dashicons dashicons-upload" style="font-size: 16px; width: 16px; height: 16px;"></span>
                        <span>إرسال الخطة للمراجعة</span>
                    </button>
                </div>

                <button type="button" id="wiz-prev-btn" onclick="wizNav(-1)" class="sm-btn sm-btn-outline" style="background: #ffffff; color: #475569 !important; border: 1px solid #cbd5e1; border-radius: 9999px !important; padding: 8px 20px; font-weight: 700; font-size: 12.5px; cursor: pointer; display: none; align-items: center; gap: 6px;">
                    <span class="dashicons dashicons-arrow-right-alt2" style="font-size: 15px; width: 15px; height: 15px;"></span>
                    <span>السابق</span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- School-Specific Term Plan Report Modal -->
<div id="eess-school-plan-report-modal" class="sm-modal-overlay" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.75); backdrop-filter: blur(5px); z-index: 999999; justify-content: center; align-items: center; padding: 20px; box-sizing: border-box; font-family: 'Cairo', sans-serif;" dir="rtl">
    <div style="background: #ffffff; border-radius: 20px; max-width: 520px; width: 100%; border: 1px solid #cbd5e1; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.3); overflow: hidden;">
        <div style="background: #0284c7; color: #ffffff; padding: 18px 24px; display: flex; justify-content: space-between; align-items: center;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <span class="dashicons dashicons-building" style="font-size: 22px; width: 22px; height: 22px; color: #ffffff;"></span>
                <h3 style="margin: 0; font-size: 16px; font-weight: 800; color: #ffffff;">تقرير مدرسة محددة — الخطط الفصلية</h3>
            </div>
            <button type="button" onclick="document.getElementById('eess-school-plan-report-modal').style.display='none'" style="background: none; border: none; color: #ffffff; font-size: 24px; cursor: pointer;">&times;</button>
        </div>
        <div style="padding: 24px;">
            <div style="margin-bottom: 14px;">
                <label style="font-size: 12.5px; font-weight: 700; color: #334155; margin-bottom: 6px; display: block;">اختر المدرسة / المؤسسة التعليمية المستهدفة <span style="color:#ef4444;">*</span></label>
                <select id="eess_target_school_plan" class="sm-input" style="height: 42px; width: 100%; border-radius: 10px; border: 1px solid #cbd5e1; padding: 0 12px; font-size: 13px; font-weight: 700;">
                    <?php
                    $all_schools_list = class_exists('EESS_Org_Helper') ? EESS_Org_Helper::get_all_schools() : array();
                    if (!empty($all_schools_list)):
                        foreach ($all_schools_list as $sch): ?>
                            <option value="<?php echo esc_attr($sch->name); ?>"><?php echo esc_html($sch->name); ?></option>
                        <?php endforeach;
                    else: ?>
                        <option value="المدرسة الرئيسية">المدرسة الرئيسية</option>
                    <?php endif; ?>
                </select>
            </div>
            <div style="margin-bottom: 18px;">
                <label style="font-size: 12.5px; font-weight: 700; color: #334155; margin-bottom: 6px; display: block;">الفصل الدراسي المستهدف</label>
                <select id="eess_target_term_plan" class="sm-input" style="height: 42px; width: 100%; border-radius: 10px; border: 1px solid #cbd5e1; padding: 0 12px; font-size: 13px; font-weight: 700;">
                    <option value="1">الفصل الدراسي الأول (Term 1)</option>
                    <option value="2">الفصل الدراسي الثاني (Term 2)</option>
                    <option value="3">الفصل الدراسي الثالث (Term 3)</option>
                </select>
            </div>
            <div style="display: flex; gap: 12px; justify-content: flex-end;">
                <button type="button" onclick="eessGenerateSchoolPlanReport()" class="sm-btn" style="background: #0284c7; color: #ffffff !important; height: 40px; padding: 0 22px; font-weight: 800; border-radius: 9999px !important; border: none; cursor: pointer;">🖨️ طباعة التقرير الرسمي A4</button>
                <button type="button" onclick="document.getElementById('eess-school-plan-report-modal').style.display='none'" class="sm-btn sm-btn-outline" style="height: 40px; padding: 0 18px; border-radius: 9999px !important; border: 1px solid #cbd5e1; color: #475569; cursor: pointer;">إلغاء</button>
            </div>
        </div>
    </div>
</div>

<script>
function eessGenerateSchoolPlanReport() {
    var schName = document.getElementById('eess_target_school_plan').value;
    var termNum = document.getElementById('eess_target_term_plan').value;
    if (!schName) return;
    document.getElementById('eess-school-plan-report-modal').style.display = 'none';
    window.open('<?php echo admin_url('admin-ajax.php?action=sm_print&print_type=school_term_plans_report&school_name='); ?>' + encodeURIComponent(schName) + '&term_number=' + termNum, '_blank');
}
</script>

<?php include_once SM_PLUGIN_DIR . 'templates/partials/unified-user-modal.php'; ?>

<!-- Assign Term Plan Modal (System Administrator Only) -->
<div id="eess-assign-plan-modal" class="sm-modal-overlay" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.75); backdrop-filter: blur(5px); z-index: 999999; justify-content: center; align-items: center; padding: 20px; box-sizing: border-box; font-family: 'Cairo', sans-serif;" dir="rtl">
    <div style="background: #ffffff; border-radius: 20px; max-width: 540px; width: 100%; border: 1px solid #cbd5e1; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.3); overflow: hidden;">
        <div style="background: #0f172a; color: #ffffff; padding: 18px 24px; display: flex; justify-content: space-between; align-items: center;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <span class="dashicons dashicons-user-freelance" style="font-size: 20px; width: 20px; height: 20px; color: #38bdf8;"></span>
                <h3 style="margin: 0; font-size: 16px; font-weight: 800; color: #ffffff;">إسناد ورفع خطة فصلية لمعلم</h3>
            </div>
            <button type="button" onclick="document.getElementById('eess-assign-plan-modal').style.display='none'" style="background: none; border: none; color: #ffffff; font-size: 24px; cursor: pointer;">&times;</button>
        </div>
        <form id="eess-assign-plan-form" onsubmit="eessSubmitAssignPlanForm(event)" style="padding: 24px;">
            <div style="margin-bottom: 14px;">
                <label style="font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 5px; display: block;">اختر المعلم المستهدف <span style="color:#ef4444;">*</span></label>
                <?php $teachers_list = get_users(array('role' => 'sm_teacher', 'orderby' => 'display_name', 'order' => 'ASC')); ?>
                <select id="assign_plan_teacher_id" class="sm-input" style="height: 40px; width: 100%; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 12px; font-size: 12.5px; font-weight: 700;" required>
                    <option value="">-- اختر المعلم --</option>
                    <?php foreach ($teachers_list as $t): ?>
                        <option value="<?php echo $t->ID; ?>"><?php echo esc_html($t->display_name . ' (' . $t->user_login . ')'); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                <div>
                    <label style="font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 5px; display: block;">الفصل الدراسي</label>
                    <select id="assign_plan_term_number" class="sm-input" style="height: 40px; width: 100%; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 12px; font-size: 12px;">
                        <option value="1">الفصل الأول</option>
                        <option value="2">الفصل الثاني</option>
                        <option value="3">الفصل الثالث</option>
                    </select>
                </div>
                <div>
                    <label style="font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 5px; display: block;">المادة الدراسية</label>
                    <input type="text" id="assign_plan_subject" placeholder="مثال: التربية البدنية" class="sm-input" style="height: 40px; width: 100%; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 12px; font-size: 12px;" required>
                </div>
            </div>

            <div style="margin-bottom: 14px;">
                <label style="font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 5px; display: block;">الصف الدراسي / المنهج</label>
                <input type="text" id="assign_plan_grade" placeholder="مثال: الصف العاشر" class="sm-input" style="height: 40px; width: 100%; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 12px; font-size: 12px;" required>
            </div>

            <div style="margin-bottom: 20px;">
                <label style="font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 5px; display: block;">ملف الخطة الجاهز (PDF / Word) <span style="color:#ef4444;">*</span></label>
                <input type="file" id="assign_plan_file" accept=".pdf,.doc,.docx" required style="width: 100%; font-size: 12px;">
            </div>

            <div style="display: flex; gap: 12px; justify-content: flex-end;">
                <button type="submit" id="assign_plan_submit_btn" class="sm-btn" style="background: #000000; color: #ffffff !important; height: 38px; padding: 0 22px; font-weight: 800; border-radius: 9999px !important; border: none; cursor: pointer;">إسناد ورفع الخطة</button>
                <button type="button" onclick="document.getElementById('eess-assign-plan-modal').style.display='none'" class="sm-btn sm-btn-outline" style="height: 38px; padding: 0 18px; border-radius: 9999px !important; border: 1px solid #cbd5e1; color: #475569; cursor: pointer;">إلغاء</button>
            </div>
        </form>
    </div>
</div>

<script>
function eessSubmitAssignPlanForm(e) {
    e.preventDefault();
    var tUid = document.getElementById('assign_plan_teacher_id').value;
    var term = document.getElementById('assign_plan_term_number').value;
    var subj = document.getElementById('assign_plan_subject').value;
    var grade = document.getElementById('assign_plan_grade').value;
    var fileInput = document.getElementById('assign_plan_file');

    if (!tUid || !fileInput.files[0]) {
        alert('يرجى اختيار المعلم وتحديد ملف الخطة.');
        return;
    }

    var btn = document.getElementById('assign_plan_submit_btn');
    btn.disabled = true;
    btn.innerText = 'جاري الإسناد...';

    var formData = new FormData();
    formData.append('action', 'sm_assign_term_plan');
    formData.append('target_user_id', tUid);
    formData.append('term_number', term);
    formData.append('subject', subj);
    formData.append('grade', grade);
    formData.append('plan_file', fileInput.files[0]);
    formData.append('nonce', '<?php echo wp_create_nonce("eess_admin_action"); ?>');

    fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
        btn.disabled = false;
        btn.innerText = 'إسناد ورفع الخطة';
        if (res.success) {
            if (typeof smShowNotification === 'function') smShowNotification(res.data.message || 'تمت الإسناد بنجاح');
            document.getElementById('eess-assign-plan-modal').style.display = 'none';
            setTimeout(() => location.reload(), 600);
        } else {
            alert('خطأ: ' + (res.data || 'فشل إسناد الخطة.'));
        }
    });
}
</script>

<!-- In-System Plan Deletion Confirmation Modal -->
<div id="eess-delete-plan-modal" class="sm-modal-overlay" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.75); backdrop-filter: blur(5px); z-index: 999999; justify-content: center; align-items: center; padding: 20px; box-sizing: border-box; font-family: 'Cairo', sans-serif; direction: rtl;">
    <div style="background: #ffffff; border-radius: 20px; max-width: 440px; width: 100%; border: 1px solid #e2e8f0; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); overflow: hidden; display: flex; flex-direction: column; padding: 28px; text-align: center;">
        <div style="width: 56px; height: 56px; background: #fef2f2; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; color: #dc2626; margin: 0 auto 16px auto; border: 1px solid #fecdd3;">
            <span class="dashicons dashicons-trash" style="font-size: 28px; width: 28px; height: 28px;"></span>
        </div>

        <h3 style="margin: 0 0 8px 0; font-size: 17px; font-weight: 800; color: #0f172a;">تأكيد حذف الخطة التعليمية</h3>
        <p style="margin: 0 0 16px 0; font-size: 12.5px; color: #64748b; line-height: 1.5;">هل أنت متأكد من رغبتك في حذف هذه الخطة نهائياً؟ لا يمكن التراجع عن هذا الإجراء.</p>

        <div id="eess-delete-plan-details" style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 10px 14px; border-radius: 10px; color: #334155; font-size: 12px; font-weight: 700; margin-bottom: 24px;">
            <!-- Filled via JS -->
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
            <button type="button" id="eess-confirm-delete-plan-btn" onclick="eessExecutePlanDeletion()" class="sm-btn" style="height: 40px; border-radius: 9999px !important; font-size: 13px; background: #dc2626; color: #ffffff !important; font-weight: 800; border: none; cursor: pointer;">تأكيد الحذف</button>
            <button type="button" onclick="document.getElementById('eess-delete-plan-modal').style.display='none'" class="sm-btn sm-btn-outline" style="height: 40px; border-radius: 9999px !important; font-size: 13px; color: #475569; font-weight: 700; border: 1px solid #cbd5e1; background: #ffffff;">إلغاء</button>
        </div>
    </div>
</div>


<script>
let currentPlanData = null;
let currentInspectedPlanId = 0;

function eessFilterTeacherPlansTable() {
    const q = document.getElementById('eess-teacher-plans-search') ? document.getElementById('eess-teacher-plans-search').value.trim().toLowerCase() : '';
    const st = document.getElementById('eess-teacher-plans-status-filter') ? document.getElementById('eess-teacher-plans-status-filter').value : '';
    const rows = document.querySelectorAll('.teacher-plan-row');

    rows.forEach(r => {
        const text = r.innerText.toLowerCase();
        const rowSt = r.getAttribute('data-status') || '';

        const matchQ = !q || text.includes(q);
        const matchSt = !st || rowSt === st;

        if (matchQ && matchSt) {
            r.style.display = '';
        } else {
            r.style.display = 'none';
        }
    });
}

function eessFilterReviewerPlansTable() {
    const q = document.getElementById('eess-reviewer-plans-search').value.trim().toLowerCase();
    const rows = document.querySelectorAll('.reviewer-plan-row');
    rows.forEach(r => {
        const text = r.innerText.toLowerCase();
        if (!q || text.includes(q)) {
            r.style.display = '';
        } else {
            r.style.display = 'none';
        }
    });
}

let eessPlanToDeleteId = 0;

function eessPromptDeletePlanModal(planId, planLabel) {
    eessPlanToDeleteId = planId;
    document.getElementById('eess-delete-plan-details').innerText = 'الخطة المستهدفة: ' + planLabel;
    document.getElementById('eess-delete-plan-modal').style.display = 'flex';
}

function eessExecutePlanDeletion() {
    if (!eessPlanToDeleteId) return;

    const btn = document.getElementById('eess-confirm-delete-plan-btn');
    btn.disabled = true;
    btn.innerText = 'جاري الحذف...';

    const formData = new FormData();
    formData.append('action', 'sm_delete_term_plan');
    formData.append('plan_id', eessPlanToDeleteId);
    formData.append('sm_nonce', '<?php echo wp_create_nonce("sm_term_plan_action"); ?>');

    fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
        btn.disabled = false;
        btn.innerText = 'نعم، تأكيد الحذف';
        document.getElementById('eess-delete-plan-modal').style.display = 'none';

        if (res.success) {
            if (typeof smShowNotification === 'function') {
                smShowNotification('تم حذف الخطة بنجاح');
            }
            setTimeout(() => location.reload(), 600);
        } else {
            if (typeof smShowNotification === 'function') {
                smShowNotification('خطأ: ' + (res.data || 'تعذر حذف الخطة'), true);
            }
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerText = 'نعم، تأكيد الحذف';
        document.getElementById('eess-delete-plan-modal').style.display = 'none';
        if (typeof smShowNotification === 'function') {
            smShowNotification('حدث خطأ في الاتصال بالخادم', true);
        }
    });
}

function eessDirectReviewPlan(planId, reviewStatus) {
    if (!planId) return;
    const confirmMsg = reviewStatus === 'approved' ? 'هل أنت متأكد من اعتماد هذه الخطة رسمياً؟' : 'هل أنت متأكد من تغيير حالة هذه الخطة؟';

    var proceed = function() {
        const formData = new FormData();
        formData.append('action', 'sm_review_term_plan');
        formData.append('plan_id', planId);
        formData.append('review_status', reviewStatus);
        formData.append('sm_nonce', '<?php echo wp_create_nonce("sm_term_plan_action"); ?>');

        fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                if (typeof smShowNotification === 'function') {
                    smShowNotification(reviewStatus === 'approved' ? 'تم اعتماد الخطة الفصلية بنجاح' : 'تم تحديث حالة الخطة بنجاح');
                }
                setTimeout(() => location.reload(), 500);
            } else {
                if (typeof smShowNotification === 'function') {
                    smShowNotification('خطأ: ' + (res.data || 'تعذر معالجة الطلب'), true);
                }
            }
        });
    };

    if (typeof window.smConfirmAction === 'function') {
        window.smConfirmAction({
            title: reviewStatus === 'approved' ? 'اعتماد الخطة الفصلية' : 'مراجعة الخطة الفصلية',
            message: confirmMsg,
            type: reviewStatus === 'approved' ? 'success' : 'danger',
            confirmText: 'تأكيد الإجراء'
        }).then(function(confirmed) {
            if (confirmed) proceed();
        });
    } else {
        if (confirm(confirmMsg)) proceed();
    }
}

function eessCheckAnnualPlanPrintComplete(completedCount) {
    if (completedCount >= 3) {
        window.open('<?php echo admin_url('admin-ajax.php?action=sm_print&print_type=annual_plan&teacher_id=' . $user_id); ?>', '_blank');
    } else {
        document.getElementById('tp_inspect_title').innerText = 'تنبيه: الخطة السنوية غير مكتملة';
        document.getElementById('tp_inspect_body').innerHTML = `
            <div style="background:#fef2f2; border:1px solid #fecdd3; border-radius:12px; padding:16px; color:#991b1b; font-size:13px; line-height:1.6;">
                <strong>⚠️ تعذر تحميل الخطة السنوية الشاملة:</strong><br>
                يجب إكمال واعتماد جميع الفصول الدراسية الثلاثة أولاً لطباعة الخطة السنوية الموحدة.<br>
                الفصول المكتملة حالياً: <strong>${completedCount} من 3 فصول</strong>.
            </div>
        `;
        document.getElementById('tp_inspect_modal').style.display = 'flex';
    }
}

var eessActiveRejectionTargetStatus = 'rejected';
function eessOpenModificationNotesModal(planId, teacherName, status) {
    currentInspectedPlanId = planId;
    eessActiveRejectionTargetStatus = status || 'rejected';
    document.getElementById('tp_inspect_title').innerText = 'رفض الخطة وتدوين الملاحظات: ' + teacherName;
    document.getElementById('tp_inspect_body').innerHTML = '<p style="color:#64748b; font-size:12.5px; margin-bottom:10px;">يرجى كتابة سبب وسبب الرفض والملاحظات المطلوبة ليتم توثيقها بالمنصة.</p>';
    document.getElementById('tp_inspect_modal').style.display = 'flex';
}

function switchTermPlanTab(tabKey) {
    document.querySelectorAll('.term-plan-panel').forEach(p => p.style.display = 'none');
    document.getElementById('panel-' + tabKey).style.display = 'block';

    const btnTeacher = document.getElementById('btn-tab-teacher');
    const btnReviewer = document.getElementById('btn-tab-reviewer');

    if (tabKey === 'teacher-dashboard') {
        if (btnTeacher) { btnTeacher.style.background = '#2563eb'; btnTeacher.style.color = '#ffffff'; }
        if (btnReviewer) { btnReviewer.style.background = '#f1f5f9'; btnReviewer.style.color = '#475569'; }
    } else {
        if (btnTeacher) { btnTeacher.style.background = '#f1f5f9'; btnTeacher.style.color = '#475569'; }
        if (btnReviewer) { btnReviewer.style.background = '#2563eb'; btnReviewer.style.color = '#ffffff'; }
    }
}

function onNumTermsChanged(num) {
    const termSelect = document.getElementById('tp_term_number');
    termSelect.innerHTML = '';
    for (let i = 1; i <= parseInt(num); i++) {
        const opt = document.createElement('option');
        opt.value = i;
        opt.innerText = 'الفصل الدراسي ' + (i === 1 ? 'الأول (Term 1)' : (i === 2 ? 'الثاني (Term 2)' : 'الثالث (Term 3)'));
        termSelect.appendChild(opt);
    }
}

function onTermNumberSelected(tNum) {
    // Optionally auto-fill dates or load draft for term
}

function calculateWeeksAuto() {
    const sDate = document.getElementById('tp_start_date').value;
    const eDate = document.getElementById('tp_end_date').value;
    const badge = document.getElementById('tp_calc_weeks_badge');

    if (sDate && eDate) {
        const t1 = new Date(sDate).getTime();
        const t2 = new Date(eDate).getTime();
        if (t2 >= t1) {
            const days = Math.floor((t2 - t1) / (1000 * 60 * 60 * 24));
            const weeks = Math.max(1, Math.ceil(days / 7));
            badge.innerText = weeks + ' أسبوعاً';
            return weeks;
        }
    }
    badge.innerText = '0 أسبوعاً';
    return 0;
}

function generateWeeklyPlanningFields() {
    const weeks = calculateWeeksAuto();
    if (weeks <= 0) {
        alert('يرجى تحديد تواريخ بداية ونهاية الفصل الصحيحة أولاً.');
        return;
    }

    const container = document.getElementById('tp_weeks_grid');
    container.innerHTML = '';

    for (let i = 1; i <= weeks; i++) {
        const weekCard = document.createElement('div');
        weekCard.style.cssText = 'background: #f8fafc; border: 1px solid #e2e8f0; padding: 16px; border-radius: 12px; display: flex; flex-direction: column; gap: 10px;';

        weekCard.innerHTML = `
            <div style="font-size: 13.5px; font-weight: 800; color: #2563eb; display: flex; align-items: center; justify-content: space-between;">
                <span>الأسبوع ${i}</span>
                <span style="font-size: 11px; color: #64748b; font-weight: 600;">تخطيط الحصص الأسبوعية</span>
            </div>
            <div>
                <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 4px;">عنوان الدرس / الموضوع الرئيسي:</label>
                <input type="text" name="weeks[${i}][title]" class="sm-input tp-week-input" oninput="triggerAutoSaveDebounced()" style="height: 38px; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 10px; font-size: 12.5px; width: 100%;">
            </div>
            <div>
                <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 4px;">ملخص المحتوى والأنشطة المقترحة:</label>
                <textarea name="weeks[${i}][summary]" class="sm-textarea tp-week-input" oninput="triggerAutoSaveDebounced()" rows="2" style="border-radius: 8px; border: 1px solid #cbd5e1; padding: 8px; font-size: 12.5px; width: 100%;"></textarea>
            </div>
        `;
        container.appendChild(weekCard);
    }

    document.getElementById('tp_weekly_editor_container').style.display = 'block';
}

let autoSaveTimer = null;
function triggerAutoSaveDebounced() {
    clearTimeout(autoSaveTimer);
    autoSaveTimer = setTimeout(() => {
        saveTermPlanDraft('draft', true);
    }, 2000);
}

function saveTermPlanDraft(targetStatus = 'draft', isSilent = false) {
    const form = document.getElementById('eess-term-plan-setup-form');
    const formData = new FormData(form);

    // Append weekly inputs
    document.querySelectorAll('.tp-week-input').forEach(input => {
        formData.append(input.name, input.value);
    });

    formData.append('action', 'sm_save_term_plan');
    formData.append('status', targetStatus);

    fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            document.getElementById('tp_plan_id').value = res.data.plan_id;
            const indicator = document.getElementById('tp_autosave_indicator');
            if (indicator) {
                indicator.style.display = 'inline-block';
                setTimeout(() => indicator.style.display = 'none', 3000);
            }
            if (!isSilent) {
                if (typeof smShowNotification === 'function') {
                    smShowNotification(targetStatus === 'submitted' ? 'تم رفع الخطة الفصلية للاعتماد بنجاح' : 'تم حفظ المسودة بنجاح');
                }
            }
        }
    });
}

let wizCurrentStep = 1;

let eessActiveWizardPlans = <?php echo json_encode(array_values((array)$teacher_plans)); ?>;

function eessOpenPlanSetupWizard(termNum = 1) {
    wizCurrentStep = 1;

    // Lock term number
    const termSelect = document.getElementById('wiz_term_number');
    if (termSelect) {
        termSelect.value = termNum;
    }

    // Default Academic Calendar Dates by Term Number
    let defaultStart = '2025-09-01';
    let defaultEnd = '2025-12-18';
    if (parseInt(termNum) === 2) {
        defaultStart = '2026-01-05';
        defaultEnd = '2026-03-26';
    } else if (parseInt(termNum) === 3) {
        defaultStart = '2026-04-12';
        defaultEnd = '2026-06-25';
    }

    // Load existing plan data for selected term if available
    const existing = eessActiveWizardPlans.find(p => parseInt(p.term_number) === parseInt(termNum));
    if (existing) {
        document.getElementById('tp_plan_id').value = existing.id || 0;
        const method = existing.planning_method || 'create';
        eessChooseMethodAndProceed(method);

        if (document.getElementById('wiz_academic_year')) document.getElementById('wiz_academic_year').value = existing.academic_year || '2025/2026';
        if (document.getElementById('wiz_grade') && existing.grade) document.getElementById('wiz_grade').value = existing.grade;

        // Restore multi-grade capsule selections
        if (existing.grade) {
            const gradesArr = existing.grade.split('،').map(g => g.trim());
            document.querySelectorAll('input[name="target_grades[]"]').forEach(cb => {
                cb.checked = gradesArr.includes(cb.value);
                if (typeof eessTogglePlanGradeCapsule === 'function') eessTogglePlanGradeCapsule(cb);
            });
        }

        if (document.getElementById('wiz_weekly_lessons')) document.getElementById('wiz_weekly_lessons').value = existing.weekly_lessons || 2;
        if (document.getElementById('wiz_num_terms')) document.getElementById('wiz_num_terms').value = existing.num_terms || 3;
        if (document.getElementById('wiz_start_date')) document.getElementById('wiz_start_date').value = existing.start_date || defaultStart;
        if (document.getElementById('wiz_end_date')) document.getElementById('wiz_end_date').value = existing.end_date || defaultEnd;

        // File preview for upload method
        if (existing.plan_file_url) {
            const preview = document.getElementById('wiz_file_status_preview');
            if (preview) {
                preview.innerHTML = `📄 ملف الخطة المرفوع حالياً: <a href="${existing.plan_file_url}" target="_blank" style="color:#0369a1; text-decoration:underline; font-weight:800;">عرض/تحميل الملف المرفوع</a>`;
                preview.style.display = 'block';
            }
        }

        // Pre-fill weekly data if available
        if (existing.weeks_data) {
            try {
                const wData = typeof existing.weeks_data === 'string' ? JSON.parse(existing.weeks_data) : existing.weeks_data;
                generateWizWeeklyFields();
                Object.keys(wData).forEach(wKey => {
                    const item = wData[wKey];
                    const titleInp = document.querySelector(`input[name="wiz_weeks[${wKey}][title]"]`);
                    const sumInp = document.querySelector(`textarea[name="wiz_weeks[${wKey}][summary]"]`);
                    if (titleInp && item.title) titleInp.value = item.title;
                    if (sumInp && item.summary) sumInp.value = item.summary;
                });
            } catch(e) {}
        }
    } else {
        document.getElementById('tp_plan_id').value = 0;
        document.getElementById('wiz_planning_method').value = 'create';
        document.getElementById('wiz-step-method-select').style.display = 'block';
        document.getElementById('eess-wizard-stepper-track').style.display = 'none';
        for (let i = 1; i <= 3; i++) {
            const stepEl = document.getElementById('wiz-step-' + i);
            if (stepEl) stepEl.style.display = 'none';
        }
        document.getElementById('wiz-prev-btn').style.display = 'none';
        document.getElementById('wiz-next-btn').style.display = 'none';
        document.getElementById('wiz-submit-btn').style.display = 'none';

        document.querySelectorAll('input[name="target_grades[]"]').forEach(cb => {
            cb.checked = false;
            if (typeof eessTogglePlanGradeCapsule === 'function') eessTogglePlanGradeCapsule(cb);
        });
        if (document.getElementById('wiz_start_date')) document.getElementById('wiz_start_date').value = defaultStart;
        if (document.getElementById('wiz_end_date')) document.getElementById('wiz_end_date').value = defaultEnd;
        generateWizWeeklyFields();
    }

    wizCalculateWeeksAuto();
    document.getElementById('eess-plan-setup-modal').style.display = 'flex';
}

function eessClosePlanSetupWizard() {
    document.getElementById('eess-plan-setup-modal').style.display = 'none';
}

function wizCalculateWeeksAuto() {
    const sDate = document.getElementById('wiz_start_date').value;
    const eDate = document.getElementById('wiz_end_date').value;
    const label = document.getElementById('wiz_weeks_count_label');

    if (sDate && eDate) {
        const t1 = new Date(sDate).getTime();
        const t2 = new Date(eDate).getTime();
        if (t2 >= t1) {
            const days = Math.floor((t2 - t1) / (1000 * 60 * 60 * 24));
            const weeks = Math.max(1, Math.ceil(days / 7));
            label.innerText = weeks + ' أسابيع';
            return weeks;
        }
    }
    label.innerText = '0 أسابيع';
    return 0;
}

function wizNav(dir) {
    const method = document.getElementById('wiz_planning_method') ? document.getElementById('wiz_planning_method').value : 'create';

    if (dir === 1) {
        if (wizCurrentStep === 1) {
            const checkedGrades = document.querySelectorAll('input[name="target_grades[]"]:checked');
            const sDate = document.getElementById('wiz_start_date') ? document.getElementById('wiz_start_date').value : '';
            const eDate = document.getElementById('wiz_end_date') ? document.getElementById('wiz_end_date').value : '';

            if (!checkedGrades || checkedGrades.length === 0) {
                if (typeof smShowNotification === 'function') {
                    smShowNotification('يرجى اختيار صف دراسي واحد على الأقل من القائمة أعلاه للمتابعة', true);
                } else {
                    alert('يرجى اختيار صف دراسي واحد على الأقل من القائمة أعلاه للمتابعة');
                }
                return;
            }
            if (!sDate || !eDate) {
                if (typeof smShowNotification === 'function') {
                    smShowNotification('يرجى تحديد تواريخ بداية ونهاية الفصل الدراسي قبل المتابعة', true);
                } else {
                    alert('يرجى تحديد تواريخ بداية ونهاية الفصل الدراسي قبل المتابعة');
                }
                return;
            }
        } else if (wizCurrentStep === 2) {
            if (method === 'upload') {
                const docFile = document.getElementById('wiz_plan_document_file');
                const hasExistingFile = document.getElementById('wiz_file_status_preview') && document.getElementById('wiz_file_status_preview').style.display !== 'none';
                if ((!docFile || !docFile.files || docFile.files.length === 0) && !hasExistingFile) {
                    if (typeof smShowNotification === 'function') {
                        smShowNotification('يرجى رفع ملف الخطة الدراسية بصيغة (PDF أو Word) قبل المتابعة', true);
                    } else {
                        alert('يرجى رفع ملف الخطة الدراسية بصيغة (PDF أو Word) قبل المتابعة');
                    }
                    return;
                }
            } else {
                generateWizWeeklyFields();
            }
        }
    }

    wizCurrentStep += dir;
    if (wizCurrentStep < 1) wizCurrentStep = 1;
    if (wizCurrentStep > 3) wizCurrentStep = 3;

    if (wizCurrentStep === 3) {
        const subj = document.getElementById('wiz_subject') ? document.getElementById('wiz_subject').value : '';
        const grade = document.getElementById('wiz_grade') ? document.getElementById('wiz_grade').value : '';
        const sDate = document.getElementById('wiz_start_date') ? document.getElementById('wiz_start_date').value : '';
        const eDate = document.getElementById('wiz_end_date') ? document.getElementById('wiz_end_date').value : '';

        if (document.getElementById('wiz_rev_subj_grade')) document.getElementById('wiz_rev_subj_grade').innerText = subj + ' - ' + grade;
        if (document.getElementById('wiz_rev_dates')) document.getElementById('wiz_rev_dates').innerText = sDate + ' إلى ' + eDate;
        if (document.getElementById('wiz_rev_weeks')) {
            document.getElementById('wiz_rev_weeks').innerText = (method === 'upload') ? 'رفع ملف خطة جاهز (PDF/Word)' : (wizCalculateWeeksAuto() + ' أسابيع محددة');
        }
    }

    updateWizardUI();
}

function updateWizardUI() {
    for (let i = 1; i <= 3; i++) {
        const stepEl = document.getElementById('wiz-step-' + i);
        if (stepEl) stepEl.style.display = (i === wizCurrentStep) ? 'block' : 'none';

        const node = document.getElementById('wiz-step-node-' + i);
        if (node) {
            const badge = node.querySelector('span:first-child');

            if (i < wizCurrentStep) {
                // Completed Step
                node.style.color = '#16a34a';
                node.style.fontWeight = '800';
                if (badge) {
                    badge.style.background = '#16a34a';
                    badge.style.color = '#ffffff';
                    badge.innerText = '✓';
                }
            } else if (i === wizCurrentStep) {
                // Active Step
                node.style.color = '#881337';
                node.style.fontWeight = '800';
                if (badge) {
                    badge.style.background = '#881337';
                    badge.style.color = '#ffffff';
                    badge.innerText = i;
                }
            } else {
                // Upcoming Step
                node.style.color = '#94a3b8';
                node.style.fontWeight = '700';
                if (badge) {
                    badge.style.background = '#e2e8f0';
                    badge.style.color = '#475569';
                    badge.innerText = i;
                }
            }
        }
    }

    const prevBtn = document.getElementById('wiz-prev-btn');
    const nextBtn = document.getElementById('wiz-next-btn');
    const submitBtn = document.getElementById('wiz-submit-btn');

    if (prevBtn) prevBtn.style.display = (wizCurrentStep > 1) ? 'inline-flex' : 'none';
    if (nextBtn) nextBtn.style.display = (wizCurrentStep < 3) ? 'inline-flex' : 'none';
    if (submitBtn) submitBtn.style.display = (wizCurrentStep === 3) ? 'inline-flex' : 'none';
}

function generateWizWeeklyFields() {
    const weeks = wizCalculateWeeksAuto();
    const grid = document.getElementById('wiz_weekly_inputs_grid');
    grid.innerHTML = '';

    for (let i = 1; i <= weeks; i++) {
        const card = document.createElement('div');
        card.style.cssText = 'background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 12px; padding: 14px; display: flex; flex-direction: column; gap: 8px; position: relative;';
        card.innerHTML = `
            <div style="font-size: 13px; font-weight: 800; color: #881337;">الأسبوع ${i}</div>
            <div style="position: relative;">
                <label style="display: block; font-size: 11.5px; font-weight: 700; color: #334155; margin-bottom: 4px;">عنوان الدرس والموضوع الرئيسية (ابدأ الكتابة لاقتراحات مادة ${document.getElementById('wiz_subject').value}):</label>
                <input type="text" id="wiz_title_${i}" name="wiz_weeks[${i}][title]" onkeyup="eessShowEducationalSuggestions(this, '${document.getElementById('wiz_subject').value}', 'title')" class="sm-input wiz-week-input" placeholder="مثال: الإرسال من أعلى في الكرة الطائرة..." style="height: 38px; border-radius: 8px; padding: 0 10px; font-size: 12.5px; width: 100%;">
                <div id="wiz_title_${i}_sug" class="eess-sug-box" style="display:none; position:absolute; top:100%; right:0; left:0; background:white; border:1px solid #cbd5e1; border-radius:8px; box-shadow:0 10px 15px rgba(0,0,0,0.1); z-index:999; max-height:160px; overflow-y:auto;"></div>
            </div>
            <div style="position: relative;">
                <label style="display: block; font-size: 11.5px; font-weight: 700; color: #334155; margin-bottom: 4px;">ملخص المحتوى والأنشطة المقترحة:</label>
                <textarea id="wiz_summary_${i}" name="wiz_weeks[${i}][summary]" onkeyup="eessShowEducationalSuggestions(this, '${document.getElementById('wiz_subject').value}', 'activity')" class="sm-textarea wiz-week-input" rows="2" placeholder="ملخص المحتوى الأسبوعي والمهارات..." style="border-radius: 8px; padding: 8px; font-size: 12.5px; width: 100%;"></textarea>
                <div id="wiz_summary_${i}_sug" class="eess-sug-box" style="display:none; position:absolute; top:100%; right:0; left:0; background:white; border:1px solid #cbd5e1; border-radius:8px; box-shadow:0 10px 15px rgba(0,0,0,0.1); z-index:999; max-height:160px; overflow-y:auto;"></div>
            </div>
        `;
        grid.appendChild(card);
    }
}

function eessShowEducationalSuggestions(inputEl, subj, inputType) {
    const query = inputEl.value.trim();
    const sugBox = document.getElementById(inputEl.id + '_sug');
    if (!sugBox) return;

    if (query.length < 2) {
        sugBox.style.display = 'none';
        return;
    }

    const formData = new FormData();
    formData.append('action', 'sm_get_educational_suggestions');
    formData.append('query', query);
    formData.append('subject', subj);
    formData.append('input_type', inputType);

    fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
        if (res.success && res.data.length > 0) {
            let html = '';
            res.data.forEach(item => {
                html += `<div onclick="eessSelectEducationalSuggestion('${inputEl.id}', '${item.content.replace(/'/g, "\\'")}', '${subj}', '${inputType}')" style="padding:8px 12px; font-size:12px; color:#1e293b; border-bottom:1px solid #f1f5f9; cursor:pointer; font-weight:600;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='white'">💡 ${item.content}</div>`;
            });
            sugBox.innerHTML = html;
            sugBox.style.display = 'block';
        } else {
            sugBox.style.display = 'none';
        }
    });
}

function eessSelectEducationalSuggestion(inputId, val, subj, inputType) {
    const el = document.getElementById(inputId);
    if (el) el.value = val;
    const sugBox = document.getElementById(inputId + '_sug');
    if (sugBox) sugBox.style.display = 'none';

    // Auto record usage count
    const formData = new FormData();
    formData.append('action', 'sm_save_educational_input');
    formData.append('subject', subj);
    formData.append('input_type', inputType);
    formData.append('content', val);
    fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData });
}

function eessMarkTeacherContacted(teacherId, recordType, recordId) {
    var formData = new FormData();
    formData.append('action', 'sm_mark_teacher_contacted');
    formData.append('teacher_id', teacherId);
    formData.append('record_type', recordType);
    formData.append('record_id', recordId);
    formData.append('nonce', '<?php echo wp_create_nonce("eess_admin_action"); ?>');

    jQuery.ajax({
        url: '<?php echo esc_url(admin_url("admin-ajax.php")); ?>',
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function(res) {
            if (res.success) {
                if (typeof eessShowMobileToast === 'function') {
                    eessShowMobileToast('✓ ' + res.data.message, 'success');
                }
            }
        }
    });
}

function eessSaveWizardPlanSubmit(e) {
    e.preventDefault();
    const btn = document.getElementById('wiz-submit-btn');
    btn.disabled = true;
    btn.innerText = 'جاري الحفظ والرفع...';

    const formData = new FormData();
    formData.append('action', 'sm_save_term_plan');
    formData.append('sm_nonce', '<?php echo wp_create_nonce("sm_term_plan_action"); ?>');
    formData.append('plan_id', document.getElementById('tp_plan_id').value || 0);
    formData.append('planning_method', document.getElementById('wiz_planning_method').value || 'create');
    formData.append('academic_year', document.getElementById('wiz_academic_year').value);
    formData.append('subject', document.getElementById('wiz_subject').value);
    formData.append('grade', document.getElementById('wiz_grade').value);
    formData.append('weekly_lessons', document.getElementById('wiz_weekly_lessons').value);
    formData.append('num_terms', document.getElementById('wiz_num_terms').value);
    formData.append('term_number', document.getElementById('wiz_term_number').value);
    formData.append('start_date', document.getElementById('wiz_start_date').value);
    formData.append('end_date', document.getElementById('wiz_end_date').value);
    formData.append('status', 'submitted');

    // Attach selected multi-grade capsules if present
    document.querySelectorAll('input[name="target_grades[]"]:checked').forEach(cb => {
        formData.append('target_grades[]', cb.value);
    });

    // Attach uploaded plan document if method is 'upload'
    const docFile = document.getElementById('wiz_plan_document_file');
    if (docFile && docFile.files && docFile.files[0]) {
        formData.append('plan_document_file', docFile.files[0]);
    }

    document.querySelectorAll('.wiz-week-input').forEach(input => {
        formData.append(input.name.replace('wiz_weeks', 'weeks'), input.value);
    });

    fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
        btn.disabled = false;
        btn.innerText = 'إرسال الخطة للمراجعة';
        if (res.success) {
            alert('تم إعداد ورفع الخطة الفصلية/السنوية بنجاح وبانتظار اعتماد المشرف.');
            eessClosePlanSetupWizard();
            setTimeout(() => location.reload(), 500);
        } else {
            alert('خطأ: ' + (res.data || 'تعذر حفظ البيانات'));
        }
    });
}

</script>

<!-- Non-Submission Administrative Report Modal for Term Plans -->
<div id="eess-non-submission-plan-modal" class="sm-modal-overlay" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.75); backdrop-filter: blur(5px); z-index: 999999; justify-content: center; align-items: center; padding: 20px; box-sizing: border-box; font-family: 'Cairo', sans-serif;" dir="rtl">
    <div style="background: #ffffff; border-radius: 20px; max-width: 460px; width: 100%; border: 1px solid #cbd5e1; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.3); overflow: hidden;">
        <div style="background: #dc2626; color: #ffffff; padding: 18px 24px; display: flex; justify-content: space-between; align-items: center;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <span class="dashicons dashicons-dismiss" style="font-size: 20px; width: 20px; height: 20px; color: #ffffff;"></span>
                <h3 style="margin: 0; font-size: 16px; font-weight: 800; color: #ffffff;">تقرير عدم تسليم الخطط الفصلية</h3>
            </div>
            <button type="button" onclick="document.getElementById('eess-non-submission-plan-modal').style.display='none'" style="background: none; border: none; color: #ffffff; font-size: 24px; cursor: pointer;">&times;</button>
        </div>
        <div style="padding: 24px;">
            <p style="margin: 0 0 16px 0; font-size: 12.5px; color: #64748b; line-height: 1.5;">اختر الفصل الدراسي لرصد المعلمين غير المغطين للخطط وتصدير تقرير A4 المعتمد:</p>
            <div style="margin-bottom: 20px;">
                <label style="font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px; display: block;">الفصل الدراسي المستهدف *</label>
                <select id="eess_report_term_number" class="sm-input" style="height: 40px; width: 100%; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 12px; font-size: 12.5px; font-weight: 700;">
                    <option value="1">الفصل الدراسي الأول (Term 1)</option>
                    <option value="2">الفصل الدراسي الثاني (Term 2)</option>
                    <option value="3">الفصل الدراسي الثالث (Term 3)</option>
                </select>
            </div>
            <div style="display: flex; gap: 12px; justify-content: flex-end;">
                <button type="button" onclick="eessGenerateNonSubmissionReport()" class="sm-btn" style="background: #dc2626; color: #ffffff !important; height: 38px; padding: 0 22px; font-weight: 800; border-radius: 9999px !important; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
                    <span class="dashicons dashicons-printer" style="font-size: 15px; width: 15px; height: 15px;"></span>
                    <span>توليد تقرير A4 المعتمد</span>
                </button>
                <button type="button" onclick="document.getElementById('eess-non-submission-plan-modal').style.display='none'" class="sm-btn sm-btn-outline" style="height: 38px; padding: 0 18px; border-radius: 9999px !important; border: 1px solid #cbd5e1; color: #475569; cursor: pointer;">إلغاء</button>
            </div>
        </div>
    </div>
</div>

<script>
function eessGenerateNonSubmissionReport() {
    var termNum = document.getElementById('eess_report_term_number').value;
    window.open('<?php echo admin_url('admin-ajax.php?action=sm_print&print_type=non_submission_term_plans&term_number='); ?>' + termNum, '_blank');
    document.getElementById('eess-non-submission-plan-modal').style.display = 'none';
}

function eessShowTermPlanStatDetails(statKey) {
    if (statKey === 'missing') {
        document.getElementById('eess-non-submission-plan-modal').style.display = 'flex';
    } else {
        const filterInput = document.getElementById('eess-reviewer-plans-search');
        if (filterInput) {
            filterInput.value = (statKey === 'approved' ? 'معتمدة' : (statKey === 'returned' ? 'تعديل' : ''));
            if (typeof eessFilterReviewerPlansTable === 'function') eessFilterReviewerPlansTable();
            const revTable = document.getElementById('eess-reviewer-plans-table');
            if (revTable) revTable.scrollIntoView({ behavior: 'smooth' });
        }
    }
}
</script>

<!-- Academic Structure Configuration Modal -->
<div id="eess-acad-config-modal" class="sm-modal-overlay" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.75); backdrop-filter: blur(5px); z-index: 999999; justify-content: center; align-items: center; padding: 20px; box-sizing: border-box; font-family: 'Cairo', sans-serif;" dir="rtl">
    <div style="background: #ffffff; border-radius: 20px; max-width: 520px; width: 100%; border: 1px solid #cbd5e1; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.3); overflow: hidden;">
        <div style="background: #0f172a; color: #ffffff; padding: 18px 24px; display: flex; justify-content: space-between; align-items: center;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <span class="dashicons dashicons-admin-generic" style="font-size: 20px; width: 20px; height: 20px;"></span>
                <h3 style="margin: 0; font-size: 16px; font-weight: 800; color: #ffffff;">تعديل العام الدراسي وإعدادات الفصول</h3>
            </div>
            <button type="button" onclick="document.getElementById('eess-acad-config-modal').style.display='none'" style="background: none; border: none; color: #ffffff; font-size: 24px; cursor: pointer;">&times;</button>
        </div>
        <form method="post" action="" style="padding: 24px;">
            <?php wp_nonce_field('sm_admin_action', 'sm_admin_nonce'); ?>
            <input type="hidden" name="sm_save_academic_structure" value="1">

            <div style="margin-bottom: 16px;">
                <label style="font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px; display: block;">العام الدراسي الحقيقي</label>
                <input type="text" name="academic_year" value="<?php echo esc_attr($active_academic_year); ?>" class="sm-input" style="height: 38px; width: 100%; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 12px;" required>
            </div>

            <div style="margin-bottom: 16px;">
                <label style="font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px; display: block;">عدد الفصول الدراسية</label>
                <select name="terms_count" class="sm-input" style="height: 38px; width: 100%; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 12px;">
                    <option value="1" <?php selected($acad_struct['terms_count'] ?? 3, 1); ?>>فصل دراسي واحد (1)</option>
                    <option value="2" <?php selected($acad_struct['terms_count'] ?? 3, 2); ?>>فصلان دراسيان (2)</option>
                    <option value="3" <?php selected($acad_struct['terms_count'] ?? 3, 3); ?>>ثلاثة فصول دراسية (3)</option>
                </select>
            </div>

            <div style="border-top: 1px solid #e2e8f0; padding-top: 14px; margin-top: 14px; margin-bottom: 16px;">
                <h4 style="margin: 0 0 10px 0; font-size: 13px; font-weight: 800; color: #0f172a;">المواعيد والمهل النهائية المعتمدة للتسليم (الخطط)</h4>
                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <div>
                        <label style="font-size: 11.5px; font-weight: 700; color: #475569; display: block; margin-bottom: 3px;">الموعد النهائي للفصل الأول (Term 1) *</label>
                        <input type="datetime-local" name="deadline_term1" value="<?php echo esc_attr($acad_struct['term_dates']['term1']['deadline'] ?? '2026-10-15T23:59'); ?>" class="sm-input" style="height: 36px; width: 100%; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 10px; font-size: 12px;">
                    </div>
                    <div>
                        <label style="font-size: 11.5px; font-weight: 700; color: #475569; display: block; margin-bottom: 3px;">الموعد النهائي للفصل الثاني (Term 2) *</label>
                        <input type="datetime-local" name="deadline_term2" value="<?php echo esc_attr($acad_struct['term_dates']['term2']['deadline'] ?? '2027-01-15T23:59'); ?>" class="sm-input" style="height: 36px; width: 100%; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 10px; font-size: 12px;">
                    </div>
                    <div>
                        <label style="font-size: 11.5px; font-weight: 700; color: #475569; display: block; margin-bottom: 3px;">الموعد النهائي للفصل الثالث (Term 3) *</label>
                        <input type="datetime-local" name="deadline_term3" value="<?php echo esc_attr($acad_struct['term_dates']['term3']['deadline'] ?? '2027-04-15T23:59'); ?>" class="sm-input" style="height: 36px; width: 100%; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 10px; font-size: 12px;">
                    </div>
                </div>
            </div>

            <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 24px;">
                <button type="submit" class="sm-btn" style="background: #881337; color: #ffffff !important; height: 38px; padding: 0 20px; font-weight: 800; border-radius: 9999px !important; border: none; cursor: pointer;">حفظ الإعدادات</button>
                <button type="button" onclick="document.getElementById('eess-acad-config-modal').style.display='none'" class="sm-btn sm-btn-outline" style="height: 38px; padding: 0 18px; border-radius: 9999px !important; border: 1px solid #cbd5e1; color: #475569; cursor: pointer;">إلغاء</button>
            </div>
        </form>
    </div>
</div>
