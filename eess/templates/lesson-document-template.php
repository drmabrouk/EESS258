<?php
if (!defined('ABSPATH')) exit;

/**
 * Official Lesson Preparation Document Template (EESS)
 * High-quality printable PDF document dynamically adapting to teacher's subject fields.
 */

$data = json_decode($prep->lesson_data, true) ?: array();
$teacher = get_userdata($prep->teacher_id);
$teacher_name = $teacher ? $teacher->display_name : 'غير محدد';
$emp_id = get_user_meta($prep->teacher_id, 'sm_employee_id', true) ?: (get_user_meta($prep->teacher_id, 'sm_employee_code', true) ?: $prep->teacher_id);
$school_info = SM_Settings::get_school_info();

// Dynamic Institutional Branding for Assigned Teacher
$assigned_school = get_user_meta($prep->teacher_id, 'eess_school_name', true);
if (empty($assigned_school)) {
    $assigned_school = get_user_meta($prep->teacher_id, 'sm_school_name', true);
}
if (empty($assigned_school)) {
    $assigned_school = $school_info['school_name'] ?? 'خدمات الأنظمة الإلكترونية التعليمية (EESS)';
}

$school_logo = get_user_meta($prep->teacher_id, 'eess_school_logo', true) ?: ($school_info['school_logo'] ?? '');
$school_phone = get_user_meta($prep->teacher_id, 'eess_school_phone', true) ?: ($school_info['phone'] ?? '');

// Dynamic subject-specific field labels
$fields = SM_Settings::get_subject_lesson_fields($prep->subject);
?>
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <title>وثيقة إعداد وتحضير الدرس المعتمدة - <?php echo esc_html($prep->title); ?></title>
    <style>
        @page {
            size: A4 portrait;
            margin: 10mm 12mm;
        }
        body {
            font-family: 'Cairo', Arial, sans-serif;
            padding: 15px;
            color: #0f172a;
            background: #ffffff;
            line-height: 1.4;
            direction: rtl;
            text-align: right;
            box-sizing: border-box;
            max-width: 210mm;
            margin: 0 auto;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .doc-header {
            border-bottom: 2px solid #0f172a;
            padding-bottom: 8px;
            margin-bottom: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .doc-title {
            font-size: 17px;
            font-weight: 900;
            margin: 0;
            color: #0f172a;
        }
        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
            page-break-inside: avoid;
        }
        .meta-table th, .meta-table td {
            border: 1px solid #cbd5e1;
            padding: 6px 10px;
            text-align: right;
            font-size: 11px;
        }
        .meta-table th {
            background: #f8fafc;
            font-weight: bold;
            width: 20%;
            color: #334155;
        }
        .section-card {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 8px 12px;
            margin-bottom: 10px;
            page-break-inside: avoid;
        }
        .section-card-title {
            font-size: 12px;
            font-weight: 800;
            color: #881337;
            margin: 0 0 4px 0;
            border-bottom: 1px solid #f1f5f9;
            padding-bottom: 4px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .section-card-body {
            font-size: 11px;
            color: #334155;
            white-space: pre-line;
            line-height: 1.45;
        }
        .status-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 50px;
            font-weight: 800;
            font-size: 10px;
        }
        .status-approved { background: #dcfce7; color: #15803d; }
        .status-submitted { background: #dbeafe; color: #1e40af; }
        .status-draft { background: #fef3c7; color: #92400e; }
        @media print {
            .no-print { display: none !important; }
            body { padding: 0; margin: 0; }
        }
    </style>
</head>
<body onload="window.print()">

    <div class="no-print" style="background: #f1f5f9; padding: 10px 16px; border-radius: 10px; margin-bottom: 15px; display: flex; align-items: center; justify-content: space-between; border: 1px solid #cbd5e1;">
        <span style="font-weight: 700; font-size: 12px; color: #334155;">وثيقة تحضير رسمية معتمدة (A4) قابلة للطباعة والمشاركة الحية</span>
        <div style="display: flex; gap: 8px; align-items: center;">
            <button onclick="eessShareDocumentPDF()" style="padding: 6px 14px; background: #881337; color: white; border: none; border-radius: 9999px; font-weight: bold; cursor: pointer; font-size: 12px; display: flex; align-items: center; gap: 4px;">📤 مشاركة الوثيقة</button>
            <button onclick="window.print()" style="padding: 6px 14px; background: #0f172a; color: white; border: none; border-radius: 9999px; font-weight: bold; cursor: pointer; font-size: 12px;">🖨️ طباعة / حفظ PDF</button>
        </div>
    </div>

    <script>
    function eessShareDocumentPDF() {
        const docTitle = "<?php echo esc_js($prep->title); ?>";
        const shareUrl = window.location.href;
        if (navigator.share) {
            navigator.share({
                title: 'وثيقة تحضير درس: ' + docTitle,
                text: 'استعراض وثيقة تحضير درس معتمدة - ' + docTitle,
                url: shareUrl
            }).catch(() => {});
        } else {
            navigator.clipboard.writeText(shareUrl).then(() => {
                alert('تم نسخ رابط وثيقة التحضير إلى الحافظة لمشاركتها بنجاح!');
            }).catch(() => {
                alert('رابط الوثيقة: ' + shareUrl);
            });
        }
    }
    </script>

    <!-- Official Header (Dynamic Institutional Branding) -->
    <div class="doc-header">
        <div style="display: flex; align-items: center; gap: 15px;">
            <?php if (!empty($school_logo)): ?>
                <img src="<?php echo esc_url($school_logo); ?>" style="max-height: 55px; width: auto; object-fit: contain;">
            <?php endif; ?>
            <div>
                <h1 class="doc-title"><?php echo esc_html($assigned_school); ?></h1>
                <p style="margin: 4px 0 0 0; color: #64748b; font-size: 12px; font-weight: 700;">وثيقة تحضير وإعداد درس معتمدة | تاريخ التصدير: <?php echo current_time('Y-m-d H:i'); ?></p>
            </div>
        </div>
        <div style="text-align: left;">
            <div style="font-weight: 900; font-size: 16px; color: #881337;"><?php echo esc_html($assigned_school); ?></div>
            <?php if (!empty($school_phone)): ?>
                <div style="font-size: 11px; color: #64748b; font-family: monospace;">هاتف: <?php echo esc_html($school_phone); ?></div>
            <?php else: ?>
                <div style="font-size: 11px; color: #64748b;">منظومة تحضير الدروس الرقمية</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Metadata Grid Table -->
    <table class="meta-table">
        <tr>
            <th>اسم المعلم المعدّ:</th>
            <td><strong><?php echo esc_html($teacher_name); ?></strong></td>
            <th>الرقم الوظيفي (ID):</th>
            <td><?php echo esc_html($emp_id); ?></td>
        </tr>
        <tr>
            <th>المؤسسة / المدرسة:</th>
            <td><?php echo esc_html($assigned_school); ?></td>
            <th>التخصص والمادة:</th>
            <td><?php echo esc_html($prep->subject); ?></td>
        </tr>
        <tr>
            <th>عنوان الدرس الرئيسي:</th>
            <td><strong style="color: #0f172a; font-size: 13.5px;"><?php echo esc_html($prep->title); ?></strong></td>
            <th>تاريخ الدرس:</th>
            <td><?php echo esc_html($prep->lesson_date); ?></td>
        </tr>
        <tr>
            <th>الصف والشعبة:</th>
            <td><?php echo esc_html($prep->grade_level . ' / ' . $prep->class_section); ?></td>
            <th>حالة التوثيق:</th>
            <td>
                <?php
                if ($prep->status === 'approved') {
                    echo '<span class="status-badge status-approved">✓ معتمد رسمياً من المشرف</span>';
                } elseif ($prep->status === 'submitted' || $prep->status === 'late') {
                    echo '<span class="status-badge status-submitted">مقدم ومحفوظ بنجاح</span>';
                } else {
                    echo '<span class="status-badge status-draft">مسودة قيد الإعداد</span>';
                }
                ?>
            </td>
        </tr>
    </table>

    <!-- Subject Specific Dynamic Fields -->
    <div class="section-card">
        <h3 class="section-card-title">1. هدف الدرس السلوكي والتعلمي</h3>
        <div class="section-card-body"><?php echo esc_html(!empty($data['objectives']) ? $data['objectives'] : 'غير مسجل'); ?></div>
    </div>

    <?php
    $sub_name = strtolower($prep->subject);
    $is_pe_doc = (strpos($sub_name, 'رياضية') !== false || strpos($sub_name, 'بدنية') !== false || strpos($sub_name, 'pe') !== false || strpos($sub_name, 'physical') !== false);
    if ($is_pe_doc || !empty($data['physical_prep'])):
    ?>
    <!-- Specialized Physical Education Lesson Practical Cards (2x2 Grid) -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 10px;">
        <div class="section-card" style="margin-bottom: 0; border-color: #bae6fd; background: #f0f9ff;">
            <h3 class="section-card-title" style="color: #0369a1; font-size: 11.5px;">🏃 1. الإحماء والتهيئة البدنية (Warm-Up)</h3>
            <div class="section-card-body" style="font-size: 11px;"><?php echo esc_html(!empty($data['warmup']) ? $data['warmup'] : 'غير مسجل'); ?></div>
        </div>

        <div class="section-card" style="margin-bottom: 0; border-color: #bbf7d0; background: #f0fdf4;">
            <h3 class="section-card-title" style="color: #15803d; font-size: 11.5px;">💪 2. الإعداد البدني العام والخاص</h3>
            <div class="section-card-body" style="font-size: 11px;"><?php echo esc_html(!empty($data['physical_prep']) ? $data['physical_prep'] : 'غير مسجل'); ?></div>
        </div>

        <div class="section-card" style="margin-bottom: 0; border-color: #fef3c7; background: #fffbeb;">
            <h3 class="section-card-title" style="color: #b45309; font-size: 11.5px;">⚽ 3. الإعداد المهاري والخططي</h3>
            <div class="section-card-body" style="font-size: 11px;"><?php echo esc_html(!empty($data['skill_prep']) ? $data['skill_prep'] : 'غير مسجل'); ?></div>
        </div>

        <div class="section-card" style="margin-bottom: 0; border-color: #fecdd3; background: #fff1f2;">
            <h3 class="section-card-title" style="color: #991b1b; font-size: 11.5px;">🧘 4. الخاتمة والتهدئة الإطالات</h3>
            <div class="section-card-body" style="font-size: 11px;"><?php echo esc_html(!empty($data['conclusion']) ? $data['conclusion'] : 'غير مسجل'); ?></div>
        </div>
    </div>
    <?php else: ?>
    <div class="section-card">
        <h3 class="section-card-title">2. <?php echo esc_html($fields['label2']); ?></h3>
        <div class="section-card-body"><?php echo esc_html(!empty($data['warmup']) ? $data['warmup'] : 'غير مسجل'); ?></div>
    </div>

    <div class="section-card">
        <h3 class="section-card-title">3. <?php echo esc_html($fields['label3']); ?></h3>
        <div class="section-card-body"><?php echo esc_html(!empty($data['activities']) ? $data['activities'] : 'غير مسجل'); ?></div>
    </div>
    <?php endif; ?>

    <!-- Educational Connections Card -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 10px;">
        <div class="section-card" style="margin-bottom: 0; background: #f8fafc;">
            <h3 class="section-card-title" style="color: #0f172a; font-size: 11.5px;">🇦🇪 الربط بالأجندة الوطنية ورؤية الدولة</h3>
            <div class="section-card-body" style="font-size: 11px;"><?php echo esc_html(!empty($data['national_agenda']) ? $data['national_agenda'] : 'غير مسجل'); ?></div>
        </div>

        <div class="section-card" style="margin-bottom: 0; background: #f8fafc;">
            <h3 class="section-card-title" style="color: #0f172a; font-size: 11.5px;">🔗 الربط بالمواد والتخصصات الأخرى</h3>
            <div class="section-card-body" style="font-size: 11px;"><?php echo esc_html(!empty($data['cross_subject']) ? $data['cross_subject'] : 'غير مسجل'); ?></div>
        </div>
    </div>

    <?php if (!empty($data['resources']) && is_array($data['resources'])): ?>
    <div class="section-card">
        <h3 class="section-card-title">📚 مصادر ووسائل التعلم المعتمدة للدرس</h3>
        <div class="section-card-body" style="display: flex; flex-wrap: wrap; gap: 6px; margin-top: 2px;">
            <?php foreach ($data['resources'] as $res_item): ?>
                <span style="background: #f1f5f9; color: #0f172a; border: 1px solid #cbd5e1; padding: 2px 8px; border-radius: 50px; font-weight: 700; font-size: 10.5px;">
                    <?php echo esc_html($res_item); ?>
                </span>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="section-card">
        <h3 class="section-card-title">الملاحظات والتأملات التربوية / إرشادات السلامة والتوجيهات</h3>
        <div class="section-card-body"><?php echo esc_html(!empty($data['notes']) ? $data['notes'] : 'لا توجد ملاحظات إضافية مسجلة'); ?></div>
    </div>

    <!-- Official Signatures Footer Alignment (Right: Teacher, Center: HOD PE, Left: Principal) -->
    <div style="margin-top: 25px; padding-top: 12px; border-top: 2px solid #cbd5e1; display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; text-align: center; page-break-inside: avoid;">
        <div style="text-align: right;">
            <div style="font-weight: 800; font-size: 11.5px; color: #0f172a; margin-bottom: 20px;">توقيع المعلم:</div>
            <div style="font-size: 11px; color: #334155; font-weight: 800;"><?php echo esc_html($teacher_name); ?></div>
        </div>
        <div style="text-align: center;">
            <div style="font-weight: 800; font-size: 11.5px; color: #0f172a; margin-bottom: 20px;">توقيع رئيس قسم التربية البدنية:</div>
            <div style="font-size: 11px; color: #64748b; font-weight: 700;">الاسم والتوقيع: ...........................</div>
        </div>
        <div style="text-align: left;">
            <div style="font-weight: 800; font-size: 11.5px; color: #0f172a; margin-bottom: 20px;">توقيع مدير المدرسة:</div>
            <div style="font-size: 11px; color: #64748b; font-weight: 700;">الاسم والتوقيع: ...........................</div>
        </div>
    </div>

</body>
</html>
