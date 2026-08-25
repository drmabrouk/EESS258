<?php
if (!defined('ABSPATH')) exit;

/**
 * Official Term & Annual Plan Print/PDF Document Template (EESS)
 * Features a dynamic 3-column lesson card grid (3 cards per row),
 * school header, teacher info, status badge, and RTL PDF page-break formatting.
 */

$school_info = SM_Settings::get_school_info();

// If printing annual plan, fetch all terms for this teacher/year/subject/grade
$is_annual = isset($_GET['print_type']) && $_GET['print_type'] === 'annual_plan';
global $wpdb;

if ($is_annual) {
    $teacher_id = isset($_GET['teacher_id']) ? intval($_GET['teacher_id']) : get_current_user_id();
    $acad_struct = SM_Settings::get_academic_structure();
    $year = isset($_GET['academic_year']) ? sanitize_text_field($_GET['academic_year']) : ($acad_struct['academic_year'] ?? '2027/2026');
    $subj = isset($_GET['subject']) ? sanitize_text_field($_GET['subject']) : '';
    $grade = isset($_GET['grade']) ? sanitize_text_field($_GET['grade']) : '';

    $plans = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}sm_term_plans WHERE teacher_id = %d AND academic_year = %s AND subject = %s AND grade = %s ORDER BY term_number ASC",
        $teacher_id, $year, $subj, $grade
    ));
    if (empty($plans)) {
        // Fallback: fetch all plans for teacher
        $plans = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}sm_term_plans WHERE teacher_id = %d ORDER BY term_number ASC",
            $teacher_id
        ));
    }
} else {
    $plan_id = isset($_GET['plan_id']) ? intval($_GET['plan_id']) : 0;
    $term_num = isset($_GET['term_number']) ? intval($_GET['term_number']) : 0;
    $teacher_id = isset($_GET['teacher_id']) ? intval($_GET['teacher_id']) : get_current_user_id();

    if ($plan_id > 0) {
        $single_plan = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sm_term_plans WHERE id = %d", $plan_id));
    } elseif ($term_num > 0) {
        $single_plan = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}sm_term_plans WHERE teacher_id = %d AND term_number = %d ORDER BY updated_at DESC LIMIT 1", $teacher_id, $term_num));
    } else {
        $single_plan = null;
    }
    $plans = $single_plan ? array($single_plan) : array();
}

if (empty($plans)) {
    wp_die('لم يتم العثور على الخطة الفصلية المطلوبة للطباعة.');
}

$first_plan = $plans[0];
$teacher = get_userdata($first_plan->teacher_id);
$teacher_name = $teacher ? $teacher->display_name : 'غير محدد';
$emp_id = get_user_meta($first_plan->teacher_id, 'sm_employee_id', true) ?: (get_user_meta($first_plan->teacher_id, 'sm_employee_code', true) ?: $first_plan->teacher_id);

// Dynamic Institutional Branding for Assigned Teacher
$assigned_school = get_user_meta($first_plan->teacher_id, 'eess_school_name', true);
if (empty($assigned_school)) {
    $assigned_school = get_user_meta($first_plan->teacher_id, 'sm_school_name', true);
}
if (empty($assigned_school)) {
    $assigned_school = $school_info['school_name'] ?? 'خدمات الأنظمة الإلكترونية التعليمية (EESS)';
}

$school_logo = get_user_meta($first_plan->teacher_id, 'eess_school_logo', true) ?: ($school_info['school_logo'] ?? '');
$school_phone = get_user_meta($first_plan->teacher_id, 'eess_school_phone', true) ?: ($school_info['phone'] ?? '');
?>
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <title><?php echo $is_annual ? 'وثيقة الخطة السنوية الشاملة' : 'وثيقة الخطة الفصلية المعتمدة'; ?> - <?php echo esc_html($teacher_name); ?></title>
    <style>
        @page {
            size: A4 portrait;
            margin: 15mm 15mm 15mm 15mm;
        }
        body {
            font-family: 'Cairo', Arial, sans-serif;
            padding: 25px;
            color: #0f172a;
            background: #ffffff;
            line-height: 1.5;
            direction: rtl;
            text-align: right;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .doc-header {
            border-bottom: 3px solid #0f172a;
            padding-bottom: 12px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .doc-title {
            font-size: 20px;
            font-weight: 900;
            margin: 0;
            color: #0f172a;
        }
        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
            page-break-inside: avoid;
        }
        .meta-table th, .meta-table td {
            border: 1px solid #cbd5e1;
            padding: 8px 12px;
            text-align: right;
            font-size: 12.5px;
        }
        .meta-table th {
            background: #f8fafc;
            font-weight: bold;
            width: 22%;
            color: #334155;
        }
        .term-section {
            margin-bottom: 30px;
            page-break-after: always;
        }
        .term-section:last-child {
            page-break-after: auto;
        }
        .term-heading {
            font-size: 16px;
            font-weight: 800;
            background: #0f172a;
            color: #ffffff;
            padding: 8px 16px;
            border-radius: 8px;
            margin-bottom: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            page-break-inside: avoid;
        }

        /* DYNAMIC 3-COLUMN LESSON CARD GRID */
        .cards-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 14px;
            width: 100%;
        }
        .lesson-card {
            background: #ffffff;
            border: 1.5px solid #cbd5e1;
            border-radius: 10px;
            padding: 12px 14px;
            box-sizing: border-box;
            display: flex;
            flex-direction: column;
            gap: 8px;
            page-break-inside: avoid;
            break-inside: avoid;
            min-height: 120px;
        }
        .lesson-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 6px;
        }
        .week-badge {
            background: #eff6ff;
            color: #2563eb;
            border: 1px solid #bfdbfe;
            padding: 2px 8px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 800;
        }
        .lesson-card-title {
            font-size: 12.5px;
            font-weight: 800;
            color: #0f172a;
            margin: 0;
            line-height: 1.4;
            word-wrap: break-word;
        }
        .lesson-card-body {
            font-size: 11.5px;
            color: #334155;
            line-height: 1.5;
            white-space: pre-line;
            word-wrap: break-word;
        }
        .status-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 50px;
            font-weight: 800;
            font-size: 11px;
        }
        .status-approved { background: #dcfce7; color: #15803d; }
        .status-submitted { background: #dbeafe; color: #1e40af; }
        .status-draft { background: #fef3c7; color: #92400e; }

        @media print {
            .no-print { display: none !important; }
            body { padding: 0; }
            .cards-grid {
                display: grid !important;
                grid-template-columns: repeat(3, 1fr) !important;
            }
        }
    </style>
</head>
<body onload="window.print()">

    <div class="no-print" style="background: #f1f5f9; padding: 12px 20px; border-radius: 10px; margin-bottom: 25px; display: flex; align-items: center; justify-content: space-between; border: 1px solid #cbd5e1;">
        <span style="font-weight: 700; font-size: 13px; color: #334155;">وثيقة الخطة المعتمدة جاهزة للطباعة الحية والحفظ كملف PDF شبكي (3 كروت بالسطر)</span>
        <button onclick="window.print()" style="padding: 8px 18px; background: #0f172a; color: white; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 13px;">🖨️ بدء طباعة الخطة / حفظ PDF</button>
    </div>

    <!-- Official School Document Header (Dynamic Institutional Branding) -->
    <div class="doc-header">
        <div style="display: flex; align-items: center; gap: 15px;">
            <?php if (!empty($school_logo)): ?>
                <img src="<?php echo esc_url($school_logo); ?>" style="max-height: 55px; width: auto; object-fit: contain;">
            <?php endif; ?>
            <div>
                <h1 class="doc-title"><?php echo esc_html($assigned_school); ?></h1>
                <p style="margin: 4px 0 0 0; color: #64748b; font-size: 12px; font-weight: 700;">
                    <?php echo $is_annual ? 'وثيقة الخطة السنوية الشاملة للمنهج' : 'وثيقة التوزيع الأسبوعي للخطة الفصلية المعتمدة'; ?> | تاريخ التصدير: <?php echo current_time('Y-m-d H:i'); ?>
                </p>
            </div>
        </div>
        <div style="text-align: left;">
            <div style="font-weight: 900; font-size: 16px; color: #881337;"><?php echo esc_html($assigned_school); ?></div>
            <?php if (!empty($school_phone)): ?>
                <div style="font-size: 11px; color: #64748b; font-family: monospace;">هاتف: <?php echo esc_html($school_phone); ?></div>
            <?php else: ?>
                <div style="font-size: 11px; color: #64748b;">منظومة الخطط المعتمدة</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Teacher & Metadata Summary Table -->
    <table class="meta-table">
        <tr>
            <th>اسم المعلم المعدّ:</th>
            <td><?php echo esc_html($teacher_name); ?></td>
            <th>الرقم الوظيفي (ID):</th>
            <td><?php echo esc_html($emp_id); ?></td>
        </tr>
        <tr>
            <th>المادة الدراسية:</th>
            <td><strong><?php echo esc_html($first_plan->subject); ?></strong></td>
            <th>الصف الدراسي:</th>
            <td><?php echo esc_html($first_plan->grade); ?></td>
        </tr>
        <tr>
            <th>العام الأكاديمي:</th>
            <td><?php echo esc_html($first_plan->academic_year); ?></td>
            <th>الحصص الأسبوعية المقرر:</th>
            <td><?php echo intval($first_plan->weekly_lessons); ?> حصص أسبوعياً</td>
        </tr>
    </table>

    <!-- Loop through Terms -->
    <?php foreach ($plans as $plan_item):
        $weeks_data = json_decode($plan_item->weeks_data, true) ?: array();
        $total_weeks_count = count($weeks_data);
    ?>
        <div class="term-section">
            <div class="term-heading">
                <span>الفصل الدراسي <?php echo intval($plan_item->term_number); ?> (Term <?php echo intval($plan_item->term_number); ?>) — <?php echo esc_html($plan_item->start_date); ?> إلى <?php echo esc_html($plan_item->end_date); ?></span>
                <span style="font-size: 12px; font-weight: 700;">
                    <?php if ($plan_item->status === 'approved'): ?>
                        <span class="status-badge status-approved">✓ معتمدة رسمياً</span>
                    <?php elseif ($plan_item->status === 'submitted'): ?>
                        <span class="status-badge status-submitted">مرفوعة للمراجعة</span>
                    <?php else: ?>
                        <span class="status-badge status-draft">مسودة</span>
                    <?php endif; ?>
                    (<?php echo $total_weeks_count; ?> أسبوعاً - <?php echo intval($plan_item->completion_pct); ?>% إنجاز)
                </span>
            </div>

            <!-- Dynamic 3-Column Cards Grid -->
            <div class="cards-grid">
                <?php if (empty($weeks_data)): ?>
                    <div style="grid-column: 1 / -1; padding: 20px; text-align: center; color: #94a3b8; font-weight: 700;">لا توجد دروس مدخلة لهذا الفصل بعد.</div>
                <?php else: ?>
                    <?php foreach ($weeks_data as $wNum => $wContent): ?>
                        <div class="lesson-card">
                            <div class="lesson-card-header">
                                <span class="week-badge">الأسبوع <?php echo esc_html($wNum); ?></span>
                            </div>
                            <h4 class="lesson-card-title"><?php echo esc_html(!empty($wContent['title']) ? $wContent['title'] : 'درس الأسبوع ' . $wNum); ?></h4>
                            <div class="lesson-card-body">
                                <?php echo esc_html(!empty($wContent['summary']) ? $wContent['summary'] : 'لا يوجد ملخص تفصيلي مدخل.'); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>

</body>
</html>
