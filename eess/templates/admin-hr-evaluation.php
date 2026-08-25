<?php
if (!defined('ABSPATH')) exit;

$current_user = wp_get_current_user();
$roles = (array) $current_user->roles;
$is_admin = in_array('administrator', $roles) || current_user_can('manage_options');
$is_sys_admin = in_array('sm_system_admin', $roles);
$is_principal = in_array('sm_principal', $roles);
$is_supervisor = in_array('sm_supervisor', $roles);
$is_coordinator = in_array('sm_coordinator', $roles);
$is_hod = in_array('sm_hod', $roles);
$is_activities_sup = in_array('sm_activities_supervisor', $roles);
$is_hr = in_array('sm_hr', $roles) || current_user_can('manage_hr');

// Access security check: Only authorized supervisors can evaluate
$can_evaluate = $is_admin || $is_sys_admin || $is_principal || $is_supervisor || $is_coordinator || $is_hod || $is_hr || $is_activities_sup;

if (!$can_evaluate) {
    echo '<div style="background:#fee2e2; color:#991b1b; padding:15px; border-radius:8px; border:1px solid #fca5a5; font-weight:700; font-family:\'Cairo\'; text-align:center;">🚫 عذراً، لا تمتلك الصلاحيات الكافية للوصول لصفحة تقييم الموظفين.</div>';
    return;
}

// Arabic role translation map
$role_map = array(
    'administrator' => 'الإدارة المركزية',
    'sm_system_admin' => 'مدير النظام التقني',
    'sm_principal' => 'مدير المدرسة',
    'sm_supervisor' => 'مشرف تربوي',
    'sm_coordinator' => 'منسق مادة',
    'sm_hod' => 'رئيس قسم',
    'sm_teacher' => 'معلم',
    'sm_discipline_supervisor' => 'مشرف سلوك / انضباط',
    'sm_activities_supervisor' => 'مشرف أنشطة',
    'sm_transportation_supervisor' => 'مشرف نقل ومواصلات',
    'sm_bus_supervisor' => 'مشرف حافلة',
    'sm_clinic' => 'العيادة المدرسية',
    'sm_hr' => 'الموارد البشرية'
);

// Form templates definition
$eval_templates = array(
    'academic' => array(
        'name' => 'نموذج تقييم الكادر التدريسي والأكاديمي',
        'metrics' => array(
            'm1' => array('label' => 'جودة الأداء التعليمي والتدريس الفعال', 'max' => 20),
            'm2' => array('label' => 'التخطيط التربوي وتحضير الدروس والابتكار', 'max' => 20),
            'm3' => array('label' => 'الالتزام بالسلوك والانضباط الوظيفي والمواعيد', 'max' => 20),
            'm4' => array('label' => 'التفاعل والتواصل مع الطلاب وأولياء الأمور', 'max' => 20),
            'm5' => array('label' => 'القيادة والمبادرة والمساهمة في الأنشطة', 'max' => 20)
        )
    ),
    'phys_health' => array(
        'name' => 'نموذج تقييم التربية البدنية والصحية (Physical Education)',
        'metrics' => array(
            'm1' => array('label' => 'اللياقة البدنية وتطبيق المهارات الرياضية والحركية بفاعلية', 'max' => 20),
            'm2' => array('label' => 'الاهتمام بالتثقيف الصحي والعادات الغذائية السليمة والوقاية', 'max' => 20),
            'm3' => array('label' => 'إدارة وتنظيم الأنشطة الرياضية والمسابقات المدرسية والتفاعلية', 'max' => 20),
            'm4' => array('label' => 'تأمين بيئة رياضية آمنة خالية من الإصابات وتطبيق معايير السلامة', 'max' => 20),
            'm5' => array('label' => 'تطوير القيادة والروح الرياضية والعمل الجماعي والمثابرة لدى الطلاب', 'max' => 20)
        )
    ),
    'administrative' => array(
        'name' => 'نموذج تقييم الكادر الإداري والوظائف المعاونة',
        'metrics' => array(
            'm1' => array('label' => 'القيام بالواجبات والمهام الوظيفية الإدارية بدقة', 'max' => 25),
            'm2' => array('label' => 'التعاون والعمل بروح الفريق الواحد والاتصال', 'max' => 25),
            'm3' => array('label' => 'الالتزام والاتساق مع اللوائح والسياسات المعتمدة', 'max' => 25),
            'm4' => array('label' => 'المبادرة بتقديم اقتراحات وحل المشكلات التنظيمية', 'max' => 25)
        )
    ),
    'leadership' => array(
        'name' => 'نموذج تقييم الكادر القيادي والإشرافي والمنسقين',
        'metrics' => array(
            'm1' => array('label' => 'التخطيط الاستراتيجي ومتابعة وتقييم أداء الفرق', 'max' => 25),
            'm2' => array('label' => 'تمكين وتحفيز وتطوير مهارات مرؤوسيه بفاعلية', 'max' => 25),
            'm3' => array('label' => 'الإدارة والاستغلال الأمثل للموارد والميزانيات المتاحة', 'max' => 25),
            'm4' => array('label' => 'سرعة اتخاذ القرارات الصحيحة وإدارة الأزمات', 'max' => 25)
        )
    )
);

// Print PDF standalone trigger interceptor
if (isset($_GET['eess_print_eval'])) {
    $print_eval_id = sanitize_text_field($_GET['eess_print_eval']);
    $global_evals = get_option('eess_global_evaluations', array());
    $target_eval = null;
    foreach ($global_evals as $ev) {
        if ($ev['id'] === $print_eval_id) {
            $target_eval = $ev;
            break;
        }
    }

    if ($target_eval) {
        $pe_user = get_userdata($target_eval['employee_id']);
        if ($pe_user) {
            $emp_num_val = get_user_meta($pe_user->ID, 'eess_employee_number', true) ?: 'غير محدد';
            $emp_dept_val = get_user_meta($pe_user->ID, 'eess_department', true) ?: 'غير محدد';
            $emp_school_val = get_user_meta($pe_user->ID, 'eess_school_name', true) ?: 'غير محدد';
            $emp_role_val = !empty($pe_user->roles) ? $pe_user->roles[0] : '';
            $emp_role_lbl = $role_map[$emp_role_val] ?? $emp_role_val;
            ?>
            <!DOCTYPE html>
            <html dir="rtl" lang="ar">
            <head>
                <meta charset="UTF-8">
                <title>تقرير تقييم الأداء المهني المعتمد - <?php echo esc_html($pe_user->display_name); ?></title>
                <style>
                    body { font-family: 'Cairo', sans-serif; padding: 40px; color: #1e293b; background: white; line-height: 1.6; }
                    .header { border-bottom: 3px solid #1e293b; padding-bottom: 20px; margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; }
                    .title { font-size: 22px; font-weight: 900; margin: 0; color: #1e293b; }
                    .meta-table { width: 100%; border-collapse: collapse; margin-bottom: 25px; }
                    .meta-table th, .meta-table td { border: 1px solid #cbd5e1; padding: 12px; text-align: right; }
                    .meta-table th { background: #f8fafc; font-weight: bold; width: 30%; }
                    .section-title { font-size: 16px; font-weight: 800; border-bottom: 2px solid #cbd5e1; padding-bottom: 5px; margin: 30px 0 15px 0; color: #1e293b; }
                    .records-table { width: 100%; border-collapse: collapse; margin-bottom: 25px; }
                    .records-table th, .records-table td { border: 1px solid #cbd5e1; padding: 12px; text-align: right; font-size: 13px; }
                    .records-table th { background: #f1f5f9; font-weight: bold; }
                    .score-big { font-size: 32px; font-weight: 900; color: #16a34a; text-align: center; margin: 20px 0; }
                    @media print {
                        .no-print { display: none; }
                    }
                </style>
            </head>
            <body onload="window.print()">
                <div class="no-print" style="background:#f1f5f9; padding:15px; border-radius:8px; margin-bottom:30px; text-align:center;">
                    <button onclick="window.print()" style="padding:10px 20px; font-weight:bold; cursor:pointer; font-family:'Cairo';">🖨️ بدء طباعة تقرير التقييم</button>
                </div>

                <div class="header">
                    <div>
                        <h1 class="title">تقرير تقييم الأداء المهني والسنوي المعتمد</h1>
                        <p style="margin:5px 0 0 0; color:#64748b;">خدمات الأنظمة الإلكترونية التعليمية (EESS)</p>
                    </div>
                    <div style="font-weight: 900; font-size: 20px; color: #334155;">EESS ONLINE</div>
                </div>

                <div style="display:flex; gap:30px; align-items:center; margin-bottom:30px;">
                    <?php echo get_avatar($pe_user->ID, 90, '', '', array('style' => 'border-radius: 50%; border: 3px solid #cbd5e1; width: 90px; height: 90px;')); ?>
                    <div>
                        <h2 style="margin:0; font-weight:800; font-size:18px;"><?php echo esc_html($pe_user->display_name); ?></h2>
                        <p style="margin:5px 0 0 0; color:#475569;">المسمى الوظيفي: <?php echo esc_html($emp_role_lbl); ?></p>
                    </div>
                </div>

                <h3 class="section-title">📋 بيانات الموظف والتعيين</h3>
                <table class="meta-table">
                    <tr><th>الرقم الوظيفي</th><td><?php echo esc_html($emp_num_val); ?></td></tr>
                    <tr><th>القسم / الإدارة</th><td><?php echo esc_html($emp_dept_val); ?></td></tr>
                    <tr><th>المؤسسة / المدرسة التابع لها</th><td><?php echo esc_html($emp_school_val); ?></td></tr>
                    <tr><th>البريد الإلكتروني المعتمد</th><td><?php echo esc_html($pe_user->user_email); ?></td></tr>
                    <tr><th>فترة التقييم الحالية</th><td><?php echo esc_html($target_eval['period']); ?></td></tr>
                    <tr><th>النموذج المستخدم</th><td><?php echo esc_html($eval_templates[$target_eval['template']]['name'] ?? 'نموذج مخصص'); ?></td></tr>
                    <tr><th>تاريخ الاعتماد الرسمي</th><td><?php echo date_i18n('Y-m-d H:i', strtotime($target_eval['date'])); ?></td></tr>
                </table>

                <h3 class="section-title">📊 تفاصيل درجات وبنود التقييم</h3>
                <table class="records-table">
                    <thead>
                        <tr>
                            <th>البند / عنصر التقييم المعتمد</th>
                            <th>الدرجة المستحقة</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $template_key = $target_eval['template'];
                        $metrics = $eval_templates[$template_key]['metrics'] ?? array();
                        $scores = $target_eval['scores'] ?? array();
                        $i = 0;
                        foreach ($metrics as $m_key => $m_data):
                            $sc = $scores[$i] ?? 0;
                            $i++;
                        ?>
                            <tr>
                                <td><?php echo esc_html($m_data['label']); ?></td>
                                <td style="font-weight:bold; font-family:monospace;"><?php echo $sc; ?> / <?php echo $m_data['max']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <tr style="background:#f8fafc; font-weight:bold;">
                            <td>الدرجة الإجمالية النهائية المستحقة</td>
                            <td style="color:#16a34a; font-family:monospace;"><?php echo $target_eval['score']; ?>%</td>
                        </tr>
                    </tbody>
                </table>

                <div class="score-big">
                    التقدير النهائي العام: <?php echo esc_html($target_eval['grade']); ?>
                </div>

                <h3 class="section-title">📝 توصيات وملاحظات وتوقيع جهة الاعتماد</h3>
                <div style="background:#f8fafc; padding:20px; border:1px solid #cbd5e1; border-radius:8px; font-size:14px; color:#334155; margin-bottom:40px;">
                    <?php echo nl2br(esc_html($target_eval['notes'])); ?>
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:40px; margin-top:50px; font-size:12px; color:#475569;">
                    <div>
                        <p>توقيع واعتماد المقيّم المباشر:</p>
                        <p style="font-weight:bold; margin-bottom:5px;">المشرف / المدير: <?php echo esc_html($target_eval['evaluator']); ?></p>
                        <p>التوقيع: _______________________</p>
                    </div>
                    <div style="text-align:left;">
                        <p>اعتماد قسم الموارد البشرية واللوائح العامة:</p>
                        <p style="font-weight:bold; margin-bottom:5px;">التاريخ: <?php echo date('Y-m-d'); ?></p>
                        <p>الختم والتوقيع الرسمي: _______________________</p>
                    </div>
                </div>
            </body>
            </html>
            <?php
            exit;
        }
    }
}

// Global evaluations options setup
$global_evals = get_option('eess_global_evaluations', array());
if (!is_array($global_evals)) $global_evals = array();

// Handle creating new evaluation records
$success_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eess_submit_evaluation'])) {
    if (!isset($_POST['eess_eval_nonce']) || !wp_verify_nonce($_POST['eess_eval_nonce'], 'eess_submit_evaluation_action')) {
        wp_die('عذراً، انتهت صلاحية الجلسة. يرجى المحاولة مجدداً.');
    }

    $target_emp_id  = intval($_POST['employee_id'] ?? 0);
    $period         = sanitize_text_field($_POST['eval_period'] ?? '');
    $template_key   = sanitize_text_field($_POST['eval_template'] ?? 'academic');
    $comments       = sanitize_textarea_field($_POST['eval_comments'] ?? '');
    $status_wf      = sanitize_text_field($_POST['workflow_status'] ?? 'approved');

    // Retrieve dynamically submitted metrics scores
    $scores = array();
    $total_score = 0;

    $selected_tmpl = $eval_templates[$template_key] ?? $eval_templates['academic'];
    $i = 1;
    foreach ($selected_tmpl['metrics'] as $m_key => $m_data) {
        $sc = intval($_POST['m_score_' . $i] ?? 0);
        if ($sc > $m_data['max']) $sc = $m_data['max'];
        if ($sc < 0) $sc = 0;
        $scores[] = $sc;
        $total_score += $sc;
        $i++;
    }

    // Determine Arabic Grade
    if ($total_score >= 90) {
        $grade = 'ممتاز';
    } elseif ($total_score >= 80) {
        $grade = 'جيد جداً';
    } elseif ($total_score >= 70) {
        $grade = 'جيد';
    } elseif ($total_score >= 60) {
        $grade = 'مقبول';
    } else {
        $grade = 'ضعيف / غير مرضٍ';
    }

    if ($target_emp_id > 0 && !empty($period)) {
        $eval_id = uniqid();
        $new_eval = array(
            'id'           => $eval_id,
            'employee_id'  => $target_emp_id,
            'date'         => current_time('Y-m-d H:i:s'),
            'period'       => $period,
            'template'     => $template_key,
            'scores'       => $scores,
            'score'        => $total_score,
            'grade'        => $grade,
            'notes'        => $comments,
            'status'       => $status_wf,
            'evaluator'    => $current_user->display_name,
            'evaluator_id' => $current_user->ID
        );

        // Append to employee's own Work Profile meta
        $employee_evals = get_user_meta($target_emp_id, 'eess_hr_evaluations', true) ?: array();
        if (!is_array($employee_evals)) $employee_evals = json_decode($employee_evals, true) ?: array();
        array_unshift($employee_evals, $new_eval);
        update_user_meta($target_emp_id, 'eess_hr_evaluations', $employee_evals);

        // Append to global searchable evaluations table
        array_unshift($global_evals, $new_eval);
        update_option('eess_global_evaluations', $global_evals);

        // Log into employee's activity timeline
        $timeline = get_user_meta($target_emp_id, 'eess_hr_activity_timeline', true) ?: array();
        if (!is_array($timeline)) $timeline = array();
        array_unshift($timeline, array(
            'date' => current_time('Y-m-d H:i:s'),
            'action' => 'تقييم أداء جديد',
            'actor' => $current_user->display_name,
            'details' => "تم تسجيل تقييم أداء للفترة ($period) بالدرجة $total_score% بتقدير ($grade)، الحالة ($status_wf)."
        ));
        update_user_meta($target_emp_id, 'eess_hr_activity_timeline', $timeline);

        clean_user_cache($target_emp_id);
        wp_cache_flush();

        $success_msg = '✅ تم تسجيل وحفظ تقييم الأداء بنجاح في السجل ومزامنته مع الملف الوظيفي للموظف فوراً.';
        // Refresh local memory global evaluations reference
        $global_evals = get_option('eess_global_evaluations', array());
    }
}

// Fetch all staff users for selection (exclude student/parent)
$staff_users = get_users(array(
    'role__not_in' => array('sm_student', 'sm_parent'),
    'orderby'      => 'display_name',
    'order'        => 'ASC'
));
?>

<div class="sm-container" style="padding: 10px 0; font-family: 'Cairo', sans-serif !important; direction: rtl;">

    <!-- Single Main Banner Header (Matching Teacher Term & Annual Plans) -->
    <div style="background: #ffffff; padding: 20px 24px; border-radius: 20px; border: 1px solid #e2e8f0; margin-bottom: 18px; box-shadow: 0 4px 18px rgba(0,0,0,0.02); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
        <div style="display: flex; align-items: center; gap: 14px;">
            <div style="width: 48px; height: 48px; background: #fef2f2; border-radius: 14px; display: flex; align-items: center; justify-content: center; color: #881337; border: 1px solid #fecdd3; flex-shrink: 0;">
                <span class="dashicons dashicons-awards" style="font-size: 24px; width: 24px; height: 24px;"></span>
            </div>
            <div>
                <h2 style="margin: 0 0 4px 0; font-size: 20px; font-weight: 800; color: #0f172a;">تقييم أداء الموظفين</h2>
                <p style="margin: 0; font-size: 12.5px; color: #64748b; font-weight: 500;">المنظومة الاحترافية الشاملة لتقييم الأداء السنوي، الفصلي والدوري لمنتسبي الهيئة الأكاديمية والإدارية والقيادية</p>
            </div>
        </div>

        <?php if ($can_evaluate): ?>
        <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
            <button type="button" onclick="jQuery('#eess-new-eval-container').slideToggle();" class="sm-btn" style="background: #881337; color: #ffffff !important; height: 38px; border-radius: 9999px !important; padding: 0 20px; font-weight: 800; font-size: 12.5px; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
                <span class="dashicons dashicons-plus-alt2" style="font-size: 15px; width: 15px; height: 15px; color: #fff;"></span>
                <span>إجراء تقييم جديد</span>
            </button>
        </div>
        <?php endif; ?>
    </div>

    <?php if (!empty($success_msg)): ?>
        <div style="background: #dcfce7; color: #15803d; padding: 15px; border-radius: 8px; border: 1px solid #bbf7d0; font-weight: 700; margin-bottom: 25px; font-size: 13px;">
            <?php echo $success_msg; ?>
        </div>
    <?php endif; ?>

    <!-- Interactive Section: New Evaluation Form -->
    <div id="eess-new-eval-container" style="display: none; background: #fff; padding: 25px; border-radius: 12px; border: 1px solid #e2e8f0; margin-bottom: 25px; box-shadow: var(--sm-shadow);">
        <h3 style="margin: 0 0 20px 0; font-weight: 800; color: #1e293b; font-size: 14px; border-bottom: 2px solid #f1f5f9; padding-bottom: 10px;">📋 استمارة تقييم أداء جديدة</h3>

        <form method="POST" action="" oninput="eessLiveCalculateScore()">
            <?php wp_nonce_field('eess_submit_evaluation_action', 'eess_eval_nonce'); ?>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 15px; margin-bottom: 20px;">
                <!-- Employee Selector -->
                <div>
                    <select name="employee_id" required class="sm-select" style="width: 100%; height: 40px; font-size: 13px; font-family:'Cairo'; border-radius:8px;">
                        <option value="">اختر الموظف المراد تقييمه *</option>
                        <?php foreach ($staff_users as $staff):
                            $staff_role = !empty($staff->roles) ? $staff->roles[0] : '';
                            $role_lbl = $role_map[$staff_role] ?? $staff_role;
                        ?>
                            <option value="<?php echo $staff->ID; ?>">
                                <?php echo esc_html($staff->display_name); ?> (<?php echo esc_html($role_lbl); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Template Selector -->
                <div>
                    <select name="eval_template" id="eval_template_sel" required onchange="eessSwitchEvalTemplate(this.value)" class="sm-select" style="width: 100%; height: 40px; font-size: 13px; font-family:'Cairo'; border-radius:8px;">
                        <option value="academic">نموذج تقييم الكادر التدريسي والأكاديمي</option>
                        <option value="phys_health">نموذج تقييم التربية البدنية والصحية (Physical Education)</option>
                        <option value="administrative">نموذج تقييم الكادر الإداري والوظائف المعاونة</option>
                        <option value="leadership">نموذج تقييم الكادر القيادي والإشرافي</option>
                    </select>
                </div>

                <!-- Evaluation Period -->
                <div>
                    <select name="eval_period" required class="sm-select" style="width: 100%; height: 40px; font-size: 13px; font-family:'Cairo'; border-radius:8px;">
                        <option value="">اختر فترة التقييم المستهدفة *</option>
                        <option value="التقييم السنوي للعام الدراسي 2024">التقييم السنوي للعام الدراسي 2024</option>
                        <option value="التقييم الفصلي - الفصل الأول 2024-2025">التقييم الفصلي - الفصل الأول 2024-2025</option>
                        <option value="التقييم الفصلي - الفصل الثاني 2024-2025">التقييم الفصلي - الفصل الثاني 2024-2025</option>
                        <option value="التقييم الفصلي - الفصل الثالث 2024-2025">التقييم الفصلي - الفصل الثالث 2024-2025</option>
                    </select>
                </div>

                <!-- Workflow Status -->
                <div>
                    <select name="workflow_status" class="sm-select" style="width: 100%; height: 40px; font-size: 13px; font-family:'Cairo'; border-radius:8px;">
                        <option value="approved">معتمد رسمياً (Approved)</option>
                        <option value="pending_approval">مسودة - قيد المراجعة والاعتماد</option>
                        <option value="draft">تحت التحضير (Draft)</option>
                    </select>
                </div>
            </div>

            <!-- Dynamic Metrics Forms Container -->
            <div id="eess-metrics-wrapper" style="background:#f8fafc; padding:20px; border-radius:10px; border:1px solid #e2e8f0; margin-bottom:20px;">
                <!-- Filled dynamically by JavaScript -->
            </div>

            <!-- Total Score live calculation -->
            <div style="background: #f1f5f9; padding: 15px; border-radius: 8px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <strong style="font-size: 13px; color: #1e293b;">الدرجة الكلية والتقدير التلقائي:</strong>
                <div style="display:flex; gap:10px; align-items:center;">
                    <span id="eess-live-score" style="background:#334155; color:white; padding:4px 12px; border-radius:6px; font-weight:800; font-family:monospace;">0 %</span>
                    <span id="eess-live-grade" style="background:#dc2626; color:white; padding:4px 12px; border-radius:6px; font-weight:800; font-size:12px;">ضعيف</span>
                </div>
            </div>

            <!-- Evaluator Comments -->
            <div style="margin-bottom: 20px;">
                <textarea name="eval_comments" rows="3" required placeholder="توصيات وملاحظات المقيّم المباشر للتحسين..." style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 8px; font-family: 'Cairo'; font-size: 13px; resize: vertical;"></textarea>
            </div>

            <div style="text-align: left;">
                <button type="submit" name="eess_submit_evaluation" class="sm-btn" style="background:#000; border: 1px solid #000; color:#fff; border-radius:8px; font-weight:700; height:38px; cursor:pointer;">
                    💾 اعتماد وإرسال نموذج التقييم فوراً
                </button>
            </div>
        </form>
    </div>

    <!-- Advanced Search and Filtering Engine for History -->
    <div style="background: #f8fafc; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; margin-bottom: 25px;">
        <h3 style="margin: 0 0 15px 0; font-weight: 800; color: #1e293b; font-size: 13px;">🔍 محرك البحث والتصفية المتقدم لتقارير التقييمات</h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px;">
            <input type="text" id="filter-employee" onkeyup="eessFilterHistory()" placeholder="ابحث باسم الموظف المقيّم..." class="sm-input" style="height: 36px; font-size: 12px;">
            <input type="text" id="filter-evaluator" onkeyup="eessFilterHistory()" placeholder="ابحث باسم المقيّم المعتمد..." class="sm-input" style="height: 36px; font-size: 12px;">
            <input type="text" id="filter-dept" onkeyup="eessFilterHistory()" placeholder="تصفية حسب القسم..." class="sm-input" style="height: 36px; font-size: 12px;">
            <select id="filter-template" onchange="eessFilterHistory()" class="sm-select" style="height: 36px; font-size: 12px; font-family:'Cairo';">
                <option value="">الكل (النماذج)</option>
                <option value="academic">الكادر التدريسي والأكاديمي</option>
                <option value="administrative">الكادر الإداري</option>
                <option value="leadership">الكادر القيادي</option>
            </select>
            <select id="filter-grade" onchange="eessFilterHistory()" class="sm-select" style="height: 36px; font-size: 12px; font-family:'Cairo';">
                <option value="">الكل (التقدير)</option>
                <option value="ممتاز">ممتاز</option>
                <option value="جيد جداً">جيد جداً</option>
                <option value="جيد">جيد</option>
                <option value="مقبول">مقبول</option>
                <option value="ضعيف">ضعيف / غير مرضٍ</option>
            </select>
        </div>
    </div>

    <!-- Complete Evaluation History Section (Direct Clean Table without outer container framing) -->
    <div style="margin-bottom: 25px;">
        <h3 style="margin: 0 0 15px 0; font-weight: 800; color: #1e293b; font-size: 14px; border-bottom: 2px solid #e2e8f0; padding-bottom: 10px;">📈 أرشيف وسجل التقييمات التاريخي للعام الدراسي</h3>

        <div style="overflow-x: auto;">
            <table class="sm-table" id="eess-eval-history-table" style="width:100%; border-collapse: separate; border-spacing: 0; border: none;">
                <thead>
                    <tr>
                        <th style="text-align: right; padding-right: 20px;">تفاصيل الموظف والتقييم</th>
                        <th>النتيجة الكلية والتقدير</th>
                        <th>حالة الاعتماد</th>
                        <th>إجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($global_evals)): ?>
                        <tr><td colspan="4" style="text-align: center; color: #94a3b8; padding: 30px;">لا يوجد أي سجلات تقييم أداء مدخلة في النظام حتى الآن.</td></tr>
                    <?php else: ?>
                        <?php foreach ($global_evals as $ev):
                            $e_user = get_userdata($ev['employee_id']);
                            if (!$e_user) continue;
                            $e_dept = get_user_meta($e_user->ID, 'eess_department', true) ?: 'غير محدد';
                        ?>
                            <tr class="eess-eval-row"
                                data-employee="<?php echo esc_attr(strtolower($e_user->display_name)); ?>"
                                data-evaluator="<?php echo esc_attr(strtolower($ev['evaluator'])); ?>"
                                data-dept="<?php echo esc_attr(strtolower($e_dept)); ?>"
                                data-template="<?php echo esc_attr($ev['template']); ?>"
                                data-grade="<?php echo esc_attr($ev['grade']); ?>"
                            >
                                <td style="text-align: right; padding: 12px 20px;">
                                    <strong style="font-size: 14px; color: #1e293b; display: block;"><?php echo esc_html($e_user->display_name); ?></strong>
                                    <div style="font-size: 11px; color: #64748b; margin-top: 5px; display: flex; flex-wrap: wrap; gap: 8px; align-items: center;">
                                        <span><strong>القسم:</strong> <?php echo esc_html($e_dept); ?></span> |
                                        <span><strong>النموذج:</strong> <?php echo esc_html($eval_templates[$ev['template']]['name'] ?? 'نموذج مخصص'); ?></span> |
                                        <span><strong>الفترة:</strong> <?php echo esc_html($ev['period']); ?></span> |
                                        <span><strong>التاريخ:</strong> <?php echo date_i18n('Y-m-d H:i', strtotime($ev['date'])); ?></span> |
                                        <span><strong>بواسطة:</strong> <?php echo esc_html($ev['evaluator']); ?></span>
                                    </div>
                                </td>
                                <td>
                                    <span style="font-family: monospace; font-weight: bold; font-size:14px; color: var(--sm-primary-color);"><?php echo $ev['score']; ?>%</span>
                                    <span style="background: #f1f5f9; padding: 2px 8px; border-radius: 4px; font-weight: bold; font-size: 11px; margin-right: 8px;">
                                        <?php echo esc_html($ev['grade']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (($ev['status'] ?? 'approved') === 'approved'): ?>
                                        <span style="background: #dcfce7; color: #15803d; padding: 2px 8px; border-radius: 4px; font-weight: bold; font-size: 10px;">معتمد رسمياً</span>
                                    <?php else: ?>
                                        <span style="background: #f1f5f9; color: #475569; padding: 2px 8px; border-radius: 4px; font-weight: bold; font-size: 10px;">تحت المراجعة</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="<?php echo add_query_arg('eess_print_eval', $ev['id']); ?>" target="_blank" class="sm-btn" style="padding: 4px 10px; font-size: 11px; height: 26px; width: auto; background: #475569; text-decoration: none; color: white !important; display: inline-flex; align-items: center; justify-content: center; border-radius: 6px;">
                                        🖨️ طباعة PDF
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Interactive Client-side Script for Templates & Scores Live Calculations -->
<script>
const eessTmpls = <?php echo json_encode($eval_templates); ?>;

function eessSwitchEvalTemplate(tmplKey) {
    const tmpl = eessTmpls[tmplKey];
    if (!tmpl) return;

    let html = '<h4 style="margin: 0 0 15px 0; font-size: 13px; font-weight: bold; color: #334155; border-bottom: 1px dashed #cbd5e1; padding-bottom: 8px;">عناصر التقييم في النموذج المختار:</h4>';
    let i = 1;
    for (const key in tmpl.metrics) {
        const m = tmpl.metrics[key];
        html += `
            <div style="display: flex; justify-content: space-between; align-items: center; gap: 20px; flex-wrap: wrap; margin-bottom: 12px; ${i > 1 ? 'border-top:1px solid #f1f5f9; padding-top:12px;' : ''}">
                <div style="flex: 1; min-width: 250px;">
                    <strong style="font-size: 13px; color: #1e293b; display: block;">${i}. ${m.label}</strong>
                </div>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <input type="number" name="m_score_${i}" id="m_score_${i}" min="0" max="${m.max}" required class="sm-input" style="width: 80px; padding: 6px; border: 1px solid #cbd5e1; border-radius: 6px; text-align: center; font-family: monospace;" value="${m.max}">
                    <span style="font-size: 12px; color: #64748b; font-weight: bold;">/ ${m.max}</span>
                </div>
            </div>
        `;
        i++;
    }

    document.getElementById('eess-metrics-wrapper').innerHTML = html;
    eessLiveCalculateScore();
}

function eessLiveCalculateScore() {
    const tmplKey = document.getElementById('eval_template_sel').value;
    const tmpl = eessTmpls[tmplKey];
    if (!tmpl) return;

    let total = 0;
    let i = 1;
    for (const key in tmpl.metrics) {
        const inputEl = document.getElementById('m_score_' + i);
        if (inputEl) {
            total += parseInt(inputEl.value) || 0;
        }
        i++;
    }

    // Set Live Score Badge
    const scoreBadge = document.getElementById('eess-live-score');
    scoreBadge.innerText = total + " %";

    // Set Live Grade Badge
    const gradeBadge = document.getElementById('eess-live-grade');
    let gradeText = "";
    let gradeColor = "";

    if (total >= 90) {
        gradeText = "ممتاز";
        gradeColor = "#16a34a";
    } else if (total >= 80) {
        gradeText = "جيد جداً";
        gradeColor = "#2563eb";
    } else if (total >= 70) {
        gradeText = "جيد";
        gradeColor = "#ca8a04";
    } else if (total >= 60) {
        gradeText = "مقبول";
        gradeColor = "#ea580c";
    } else {
        gradeText = "ضعيف";
        gradeColor = "#dc2626";
    }

    gradeBadge.innerText = gradeText;
    gradeBadge.style.backgroundColor = gradeColor;
}

// History table filter function
function eessFilterHistory() {
    const emp = document.getElementById('filter-employee').value.toLowerCase().trim();
    const evaluator = document.getElementById('filter-evaluator').value.toLowerCase().trim();
    const dept = document.getElementById('filter-dept').value.toLowerCase().trim();
    const tmpl = document.getElementById('filter-template').value;
    const grade = document.getElementById('filter-grade').value;

    const rows = document.querySelectorAll('.eess-eval-row');
    rows.forEach(row => {
        const rEmp = row.getAttribute('data-employee') || '';
        const rEval = row.getAttribute('data-evaluator') || '';
        const rDept = row.getAttribute('data-dept') || '';
        const rTmpl = row.getAttribute('data-template') || '';
        const rGrade = row.getAttribute('data-grade') || '';

        const mEmp = !emp || rEmp.includes(emp);
        const mEval = !evaluator || rEval.includes(evaluator);
        const mDept = !dept || rDept.includes(dept);
        const mTmpl = !tmpl || rTmpl === tmpl;
        const mGrade = !grade || rGrade === grade;

        if (mEmp && mEval && mDept && mTmpl && mGrade) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}

// Initialize on page load
eessSwitchEvalTemplate('academic');
</script>
