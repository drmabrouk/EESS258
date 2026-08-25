<?php if (!defined('ABSPATH')) exit; ?>
<?php if (empty($records)): ?>
    <tr>
        <td colspan="7" style="padding: 60px 20px; text-align: center; color: #64748b;">
            <div style="width: 64px; height: 64px; background: #f1f5f9; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; color: #94a3b8; margin-bottom: 16px;">
                <svg width="32" height="32" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
            </div>
            <p style="margin: 0; font-size: 15px; font-weight: 700; color: #334155;">لا توجد سجلات مخالفات مطابقة حالياً.</p>
        </td>
    </tr>
<?php else: ?>
    <?php
    $type_labels = SM_Settings::get_violation_types();
    $severity_labels = SM_Settings::get_severities();
    $current_user = wp_get_current_user();
    $sender_name = $current_user->display_name;

    $weekdays = array(
        'Sunday'    => 'الأحد',
        'Monday'    => 'الاثنين',
        'Tuesday'   => 'الثلاثاء',
        'Wednesday' => 'الأربعاء',
        'Thursday'  => 'الخميس',
        'Friday'    => 'الجمعة',
        'Saturday'  => 'السبت',
    );

    foreach ($records as $row):
        // Dynamic Linking
        $reg = SM_Settings::get_regulation_by_code($row->violation_code);
        $display_type = $reg ? $reg['name'] : $row->type;
        $display_action = $reg ? $reg['action'] : $row->action_taken;

        // Date and Day
        $created_timestamp = strtotime($row->created_at);
        $formatted_date = date('Y-m-d', $created_timestamp);
        $english_day = date('l', $created_timestamp);
        $day_name = $weekdays[$english_day] ?? '';

        // WhatsApp Message
        $msg_text = "*السلام عليكم ورحمة الله وبركاته،*\n\n";
        $msg_text .= "إلى ولي أمر الطالب/ة: *{$row->student_name}*\n";
        $msg_text .= "الصف والشعبة: " . SM_Settings::format_grade_name($row->class_name, $row->section, 'short') . "\n";
        $msg_text .= "نوع المخالفة: *{$display_type}*\n\n";

        $msg_text .= "نرجو منكم المتابعة مع إدارة المدرسة.\n\n";
        $msg_text .= "*وتقبلوا فائق الاحترام والتقدير،*\n";
        $msg_text .= "{$sender_name}";

        $waMsg = rawurlencode($msg_text);
        $raw_phone = $row->guardian_phone ?? '';
        $formatted_phone = SM_Settings::format_uae_phone($raw_phone);

        // School Display
        $school_display = !empty($row->school_name) ? $row->school_name : 'المدرسة الرئيسية';

        // Nationality & ID
        $student_id_code = !empty($row->student_code) ? $row->student_code : '---';
        $nationality_str = !empty($row->nationality) ? $row->nationality : 'غير محدد';

        // Severity Soft Pastel Color Styles
        $severity_bg = '#f1f5f9';
        $severity_color = '#475569';
        $severity_text = $severity_labels[$row->severity] ?? $row->severity;

        if ($row->severity === 'low') {
            $severity_bg = '#dcfce7';
            $severity_color = '#15803d';
        } elseif ($row->severity === 'medium') {
            $severity_bg = '#fef3c7';
            $severity_color = '#b45309';
        } elseif ($row->severity === 'high' || $row->severity === 'severe') {
            $severity_bg = '#fee2e2';
            $severity_color = '#b91c1c';
        }
    ?>
        <tr id="record-row-<?php echo $row->id; ?>" style="border-bottom: 1px solid #f1f5f9; transition: background 0.15s;" onmouseover="this.style.background='#f8fafc';" onmouseout="this.style.background='transparent';">

            <!-- 1. Student Cell (Photo, Name, Student Number & Nationality Badges) -->
            <td style="padding: 14px 18px; vertical-align: middle;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div style="width: 42px; height: 42px; min-width: 42px; min-height: 42px; border-radius: 50%; overflow: hidden; background: #e2e8f0; display: flex; align-items: center; justify-content: center; border: 2px solid #ffffff; box-shadow: 0 2px 6px rgba(0,0,0,0.06); flex-shrink: 0;">
                        <?php if (!empty($row->photo_url)): ?>
                            <img src="<?php echo esc_url($row->photo_url); ?>" alt="<?php echo esc_attr($row->student_name); ?>" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%; display: block;" />
                        <?php else: ?>
                            <svg width="20" height="20" fill="#94a3b8" viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                        <?php endif; ?>
                    </div>
                    <div>
                        <div style="font-weight: 800; font-size: 13.5px; color: #0f172a; display: flex; align-items: center; gap: 6px; line-height: 1.3; margin-bottom: 4px;">
                            <?php if (current_user_can('إدارة_الطلاب') || current_user_can('manage_options')): ?>
                                <a href="javascript:void(0)" onclick='openUnifiedProfileModal(<?php echo json_encode(array(
                                    "id" => $row->student_id,
                                    "name" => $row->student_name,
                                    "class_name" => $row->class_name,
                                    "section" => $row->section,
                                    "parent_email" => $row->parent_email ?? "",
                                    "guardian_phone" => $row->guardian_phone ?? "",
                                    "nationality" => $row->nationality ?? "",
                                    "student_code" => $row->student_code ?? "",
                                    "student_id" => $row->student_code ?? "",
                                    "photo_url" => $row->photo_url ?? ""
                                )); ?>)' style="color: #0f172a; text-decoration: none; transition: color 0.2s;" onmouseover="this.style.color='#881337'; this.style.textDecoration='underline';" onmouseout="this.style.color='#0f172a'; this.style.textDecoration='none';" title="انقر لعرض وتعديل ملف الطالب">
                                    <?php echo esc_html($row->student_name); ?>
                                </a>
                            <?php else: ?>
                                <span><?php echo esc_html($row->student_name); ?></span>
                            <?php endif; ?>
                        </div>
                        <!-- Student Code & Nationality Badges on same horizontal line -->
                        <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                            <span style="display: inline-flex; align-items: center; padding: 2px 8px; background: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; border-radius: 12px; font-size: 11px; font-weight: 700;" title="رقم الطالب">
                                <?php echo esc_html($student_id_code); ?>
                            </span>
                            <span style="display: inline-flex; align-items: center; padding: 2px 8px; background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; border-radius: 12px; font-size: 11px; font-weight: 700;" title="الجنسية">
                                <?php echo esc_html($nationality_str); ?>
                            </span>
                        </div>
                    </div>
                </div>
            </td>

            <!-- 2. School / Academic Placement (Compact School Name + Reduced Grade & Section Badges) -->
            <td style="padding: 12px 16px; vertical-align: middle;">
                <div style="font-weight: 700; font-size: 12px; color: #334155; margin-bottom: 4px; line-height: 1.2;">
                    <?php echo esc_html($school_display); ?>
                </div>
                <div style="display: flex; align-items: center; gap: 4px; flex-wrap: wrap;">
                    <span style="font-weight: 700; color: #334155; background: #f1f5f9; padding: 1.5px 7px; border-radius: 6px; border: 1px solid #e2e8f0; font-size: 11px;">
                        <?php echo esc_html($row->class_name ?? 'غير محدد'); ?>
                    </span>
                    <span style="font-weight: 700; color: #0369a1; background: #f0f9ff; padding: 1.5px 7px; border-radius: 6px; border: 1px solid #bae6fd; font-size: 11px;">
                        <?php echo esc_html(!empty($row->section) ? 'شعبة ' . $row->section : 'شعبة عامة'); ?>
                    </span>
                </div>
            </td>

            <!-- 4. Incident Date & Day -->
            <td style="padding: 14px 18px; vertical-align: middle;">
                <div style="font-weight: 700; font-size: 13px; color: #0f172a;">
                    <?php echo esc_html($formatted_date); ?>
                </div>
                <div style="font-size: 11.5px; color: #0284c7; font-weight: 600; margin-top: 2px;">
                    <?php echo esc_html($day_name); ?>
                </div>
            </td>

            <!-- 5. Violation Item & Severity Level -->
            <td style="padding: 14px 18px; vertical-align: middle;">
                <div style="display: flex; align-items: center; gap: 6px; margin-bottom: 2px;">
                    <span style="display: inline-flex; align-items: center; justify-content: center; padding: 2px 8px; background: #eff6ff; color: #2563eb; border-radius: 6px; font-size: 11px; font-weight: 800; border: 1px solid #dbeafe;">
                        درجة <?php echo (int)$row->degree; ?>
                    </span>
                    <span style="font-weight: 800; font-size: 13px; color: #0f172a;">
                        <?php echo esc_html($row->violation_code); ?>
                    </span>
                </div>
                <div style="font-size: 12px; color: #64748b; font-weight: 500; max-width: 220px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?php echo esc_attr($display_type); ?>">
                    <?php echo esc_html($display_type); ?>
                </div>
            </td>

            <!-- 6. Occurrence Frequency -->
            <td style="padding: 14px 18px; vertical-align: middle; text-align: center;">
                <span style="display: inline-flex; align-items: center; justify-content: center; width: 28px; height: 28px; background: #f8fafc; color: #334155; border-radius: 50%; font-weight: 800; font-size: 12px; border: 1px solid #cbd5e1;">
                    <?php echo (int)$row->recurrence_count; ?>
                </span>
            </td>

            <!-- 7. Severity & Status Badges -->
            <td style="padding: 14px 18px; vertical-align: middle; text-align: center;">
                <span style="display: inline-block; padding: 4px 12px; background: <?php echo $severity_bg; ?>; color: <?php echo $severity_color; ?>; border-radius: 12px; font-weight: 800; font-size: 11.5px;">
                    <?php echo esc_html($severity_text); ?>
                </span>

                <?php if (!empty($row->contacted)): ?>
                    <div style="margin-top: 4px; font-size: 11px; color: #16a34a; font-weight: 700; display: flex; align-items: center; justify-content: center; gap: 4px;">
                        <svg width="13" height="13" fill="#16a34a" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                        تم التواصل
                    </div>
                <?php endif; ?>
            </td>

            <!-- 8. Administrative Action Buttons (Fixed 4-Button Grid in RTL Order: WhatsApp -> Print -> Edit -> Delete) -->
            <td style="padding: 14px 18px; vertical-align: middle; text-align: center;">
                <div style="display: flex; align-items: center; justify-content: center; gap: 8px;">

                    <!-- 1. WhatsApp Button (Active Green / Contacted Checkmark / Disabled Muted Gray) -->
                    <?php if (!empty($formatted_phone)): ?>
                        <?php if (!empty($row->contacted)): ?>
                            <a href="https://wa.me/<?php echo $formatted_phone; ?>?text=<?php echo $waMsg; ?>"
                               target="_blank"
                               onclick="markAsContacted(<?php echo $row->id; ?>)"
                               class="eess-action-btn"
                               style="width: 36px; height: 36px; min-width: 36px; min-height: 36px; border-radius: 50%; background: #dcfce7 !important; color: #15803d !important; border: 1px solid #86efac !important; display: inline-flex; align-items: center; justify-content: center; text-decoration: none; position: relative; cursor: pointer; transition: all 0.2s;"
                               title="تم التواصل عبر واتساب بنجاح" aria-label="تم التواصل عبر واتساب بنجاح">
                                <svg width="16" height="16" fill="#15803d" viewBox="0 0 24 24" style="width: 16px; height: 16px; display: block;"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                                <span style="position: absolute; top: -3px; right: -3px; width: 14px; height: 14px; background: #16a34a; color: #ffffff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 9px; border: 1.5px solid #ffffff; font-weight: 900;">✓</span>
                            </a>
                        <?php else: ?>
                            <a href="https://wa.me/<?php echo $formatted_phone; ?>?text=<?php echo $waMsg; ?>"
                               target="_blank"
                               onclick="markAsContacted(<?php echo $row->id; ?>)"
                               class="eess-action-btn"
                               style="width: 36px; height: 36px; min-width: 36px; min-height: 36px; border-radius: 50%; background: #f0fdf4 !important; color: #16a34a !important; border: 1px solid #bbf7d0 !important; display: inline-flex; align-items: center; justify-content: center; text-decoration: none; cursor: pointer; transition: all 0.2s;"
                               title="إرسال عبر واتساب" aria-label="إرسال عبر واتساب">
                               <svg width="16" height="16" fill="#16a34a" viewBox="0 0 24 24" style="width: 16px; height: 16px; display: block;"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                            </a>
                        <?php endif; ?>
                    <?php else: ?>
                        <!-- Disabled State: Muted Gray (Phone missing/invalid) -->
                        <button type="button" disabled
                                class="eess-action-btn"
                                style="width: 36px; height: 36px; min-width: 36px; min-height: 36px; border-radius: 50%; background: #f8fafc !important; color: #94a3b8 !important; border: 1px solid #e2e8f0 !important; display: inline-flex; align-items: center; justify-content: center; opacity: 0.4; pointer-events: none; cursor: not-allowed;"
                                title="واتساب غير متاح (رقم هاتف مفقود أو غير صحيح)" aria-label="واتساب غير متاح">
                            <svg width="16" height="16" fill="#94a3b8" viewBox="0 0 24 24" style="width: 16px; height: 16px; display: block;"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981zm11.387-5.464c-.074-.124-.272-.198-.57-.347-.297-.149-1.758-.868-2.031-.967-.272-.099-.47-.149-.669.149-.198.297-.768.967-.941 1.165-.173.198-.347.223-.644.074-.297-.149-1.255-.462-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.297-.347.446-.521.151-.172.2-.296.3-.495.099-.198.05-.372-.025-.521-.075-.148-.669-1.611-.916-2.206-.242-.579-.487-.501-.669-.51l-.57-.01c-.198 0-.52.074-.792.372s-1.04 1.016-1.04 2.479 1.065 2.876 1.213 3.074c.149.198 2.095 3.2 5.076 4.487.709.306 1.263.489 1.694.626.712.226 1.36.194 1.872.118.571-.085 1.758-.719 2.006-1.413.248-.695.248-1.29.173-1.414z"/></svg>
                        </button>
                    <?php endif; ?>

                    <!-- 2. Print Button (Active Blue) -->
                    <a href="<?php echo admin_url('admin-ajax.php?action=sm_print&print_type=single_violation&record_id=' . $row->id); ?>"
                       target="_blank"
                       class="sm-action-btn sm-action-btn-neutral"
                       title="طباعة التقرير" aria-label="طباعة التقرير">
                        <span class="dashicons dashicons-printer"></span>
                    </a>

                    <!-- 3. Edit Button (Active Amber or Disabled Muted Gray) -->
                    <?php if (current_user_can('إدارة_المخالفات') || current_user_can('manage_options')): ?>
                        <button type="button" onclick="editSmRecord(<?php echo htmlspecialchars(json_encode($row)); ?>)"
                                class="sm-action-btn sm-action-btn-warning"
                                title="تعديل السجل" aria-label="تعديل السجل">
                            <span class="dashicons dashicons-edit"></span>
                        </button>
                    <?php else: ?>
                        <button type="button" disabled
                                class="sm-action-btn sm-action-btn-neutral"
                                style="opacity: 0.4; pointer-events: none; cursor: not-allowed;"
                                title="تعديل غير متاح" aria-label="تعديل غير متاح">
                            <span class="dashicons dashicons-edit"></span>
                        </button>
                    <?php endif; ?>

                    <!-- 4. Delete Button (Active Red or Disabled Muted Gray) -->
                    <?php if (current_user_can('إدارة_المخالفات') || current_user_can('manage_options')): ?>
                        <button type="button" onclick="confirmDeleteRecord(<?php echo $row->id; ?>)"
                                class="sm-action-btn sm-action-btn-danger"
                                title="حذف المخالفة" aria-label="حذف المخالفة">
                            <span class="dashicons dashicons-trash"></span>
                        </button>
                    <?php else: ?>
                        <button type="button" disabled
                                class="sm-action-btn sm-action-btn-neutral"
                                style="opacity: 0.4; pointer-events: none; cursor: not-allowed;"
                                title="حذف غير متاح" aria-label="حذف غير متاح">
                            <span class="dashicons dashicons-trash"></span>
                        </button>
                    <?php endif; ?>

                </div>

                <?php if ($row->status === 'pending' && (current_user_can('إدارة_المخالفات') || current_user_can('manage_options'))): ?>
                    <div style="margin-top: 8px; display: flex; gap: 6px; justify-content: center;">
                        <button type="button" onclick="updateRecordStatus(<?php echo $row->id; ?>, 'accepted')" style="background: #16a34a; color: #fff; border: none; border-radius: 8px; font-size: 11px; font-weight: 700; padding: 4px 10px; cursor: pointer;">اعتماد</button>
                        <button type="button" onclick="updateRecordStatus(<?php echo $row->id; ?>, 'rejected')" style="background: #dc2626; color: #fff; border: none; border-radius: 8px; font-size: 11px; font-weight: 700; padding: 4px 10px; cursor: pointer;">رفض</button>
                    </div>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
<?php endif; ?>
