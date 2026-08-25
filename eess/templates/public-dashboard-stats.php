<?php if (!defined('ABSPATH')) exit; ?>
<div class="sm-admin-panel" dir="rtl" style="font-family: system-ui, -apple-system, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #1e293b;">
    <?php
    $user_roles = (array) wp_get_current_user()->roles;
    $is_parent = in_array('sm_parent', $user_roles) || in_array('sm_student', $user_roles);

    // Initial query parameters
    $initial_filters = array();
    if (isset($_GET['student_search'])) $initial_filters['search'] = sanitize_text_field($_GET['student_search']);
    if (isset($_GET['class_filter'])) $initial_filters['class_name'] = sanitize_text_field($_GET['class_filter']);
    if (isset($_GET['section_filter'])) $initial_filters['section'] = sanitize_text_field($_GET['section_filter']);
    if (isset($_GET['type_filter'])) $initial_filters['type'] = sanitize_text_field($_GET['type_filter']);

    $paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    $limit = isset($_GET['limit']) ? max(1, intval($_GET['limit'])) : 10;
    $orderby = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'created_at';
    $order = isset($_GET['order']) ? sanitize_text_field($_GET['order']) : 'DESC';

    $total_records = SM_DB::get_records_count($initial_filters);
    $total_pages = max(1, ceil($total_records / $limit));
    if ($paged > $total_pages) {
        $paged = $total_pages;
    }
    $offset = ($paged - 1) * $limit;

    $query_filters = array_merge($initial_filters, array(
        'limit' => $limit,
        'offset' => $offset,
        'orderby' => $orderby,
        'order' => $order
    ));

    $records = SM_DB::get_records($query_filters);
    $from_num = $total_records > 0 ? $offset + 1 : 0;
    $to_num = min($offset + $limit, $total_records);
    ?>

    <!-- 1. Header Banner Card -->
    <div style="background: #ffffff; padding: 22px 28px; border-radius: 20px; border: 1px solid #e2e8f0; margin-bottom: 20px; box-shadow: 0 4px 18px rgba(0, 0, 0, 0.02); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
        <div style="display: flex; align-items: center; gap: 16px;">
            <div style="width: 52px; height: 52px; background: #fef2f2; border-radius: 16px; display: flex; align-items: center; justify-content: center; color: #dc2626; border: 1px solid #fee2e2; flex-shrink: 0;">
                <svg width="26" height="26" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
            </div>
            <div>
                <h2 style="margin: 0 0 4px 0; font-size: 22px; font-weight: 800; color: #0f172a; letter-spacing: -0.3px;">
                    سجل المخالفات
                </h2>
                <p style="margin: 0; font-size: 13px; color: #64748b; font-weight: 500;">
                    إدارة ومتابعة المخالفات السلوكية والانضباطية للطلاب وإجراءات التواصل مع أولياء الأمور
                </p>
            </div>
        </div>

        <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
            <?php if (!$is_parent): ?>
                <!-- Export Reports Dropdown -->
                <div style="position: relative; display: inline-block;">
                    <button type="button" onclick="const d = document.getElementById('eess-violation-export-dropdown'); d.style.display = d.style.display === 'none' ? 'block' : 'none'; event.stopPropagation();" class="eess-hdr-btn" style="background: #f8fafc !important; color: #334155 !important; border: 1px solid #cbd5e1 !important; border-radius: 12px; padding: 0 16px; height: 42px; font-weight: 700; font-size: 13px; display: inline-flex; align-items: center; gap: 8px; cursor: pointer; transition: all 0.2s; box-shadow: 0 1px 2px rgba(0,0,0,0.04);">
                        <svg width="18" height="18" fill="none" stroke="#334155" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                        <span style="color: #334155 !important;">تصدير التقارير</span>
                        <svg width="12" height="12" fill="none" stroke="#334155" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>

                    <div id="eess-violation-export-dropdown" style="display: none; position: absolute; left: 0; top: 115%; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 14px; width: 230px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); z-index: 99999; padding: 6px 0; text-align: right;">
                        <div style="padding: 6px 16px; font-size: 11px; color: #94a3b8; font-weight: 800; border-bottom: 1px solid #f1f5f9;">تحميل تقارير (PDF)</div>
                        <a href="javascript:void(0)" onclick="exportViolationPDF('today')" style="display: block; padding: 10px 16px; color: #334155; font-size: 12px; font-weight: 600; text-decoration: none; border-bottom: 1px solid #f1f5f9; transition: background 0.15s;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'">📄 مخالفات اليوم (PDF)</a>
                        <a href="javascript:void(0)" onclick="exportViolationPDF('week')" style="display: block; padding: 10px 16px; color: #334155; font-size: 12px; font-weight: 600; text-decoration: none; border-bottom: 1px solid #f1f5f9; transition: background 0.15s;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'">📄 مخالفات الأسبوع (PDF)</a>
                        <a href="javascript:void(0)" onclick="exportViolationPDF('month')" style="display: block; padding: 10px 16px; color: #334155; font-size: 12px; font-weight: 600; text-decoration: none; border-bottom: 1px solid #f1f5f9; transition: background 0.15s;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'">📄 مخالفات الشهر (PDF)</a>

                        <div style="padding: 6px 16px; font-size: 11px; color: #94a3b8; font-weight: 800; border-bottom: 1px solid #f1f5f9;">تصدير بيانات (CSV)</div>
                        <a href="<?php echo admin_url('admin-ajax.php?action=sm_export_violations_csv&range=today&nonce='.wp_create_nonce('sm_export_action')); ?>" style="display: block; padding: 10px 16px; color: #334155; font-size: 12px; font-weight: 600; text-decoration: none; border-bottom: 1px solid #f1f5f9; transition: background 0.15s;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'">📊 مخالفات اليوم (CSV)</a>
                        <a href="<?php echo admin_url('admin-ajax.php?action=sm_export_violations_csv&range=week&nonce='.wp_create_nonce('sm_export_action')); ?>" style="display: block; padding: 10px 16px; color: #334155; font-size: 12px; font-weight: 600; text-decoration: none; border-bottom: 1px solid #f1f5f9; transition: background 0.15s;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'">📊 مخالفات الأسبوع (CSV)</a>
                        <a href="<?php echo admin_url('admin-ajax.php?action=sm_export_violations_csv&nonce='.wp_create_nonce('sm_export_action')); ?>" style="display: block; padding: 10px 16px; color: #334155; font-size: 12px; font-weight: 600; text-decoration: none; transition: background 0.15s;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'">📊 جميع المخالفات (CSV)</a>
                    </div>
                </div>

                <!-- Secondary Action: Import -->
                <button type="button" onclick="const f=document.getElementById('violation-import-form'); f.style.display = f.style.display==='none'?'block':'none';" class="eess-hdr-btn" style="background: #f8fafc !important; color: #334155 !important; border: 1px solid #cbd5e1 !important; border-radius: 12px; padding: 0 16px; height: 42px; font-weight: 700; font-size: 13px; display: inline-flex; align-items: center; gap: 8px; cursor: pointer; transition: all 0.2s; box-shadow: 0 1px 2px rgba(0,0,0,0.04);">
                    <svg width="18" height="18" fill="none" stroke="#334155" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                    <span style="color: #334155 !important;">استيراد</span>
                </button>

                <!-- Primary Action: Register Violation / Submit Referral -->
                <?php if (in_array('sm_teacher', (array)wp_get_current_user()->roles)): ?>
                <button type="button" onclick="eessOpenTeacherReferralModal()" class="sm-btn sm-btn-custom" style="background: #dc2626; color: #ffffff; border: none; border-radius: 12px; padding: 0 20px; height: 42px; font-weight: 800; font-size: 13.5px; display: inline-flex; align-items: center; gap: 8px; cursor: pointer; box-shadow: 0 4px 12px rgba(220, 38, 38, 0.25); transition: all 0.2s;" onmouseover="this.style.background='#b91c1c'" onmouseout="this.style.background='#dc2626'">
                    <svg width="18" height="18" fill="none" stroke="#ffffff" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path></svg>
                    <span>تقديم مخالفة سلوكية لطالب</span>
                </button>
                <?php elseif (current_user_can('تسجيل_مخالفة') || current_user_can('إدارة_المخالفات') || current_user_can('manage_options')): ?>
                <button type="button" onclick="if(document.getElementById('sm-global-violation-modal')){document.getElementById('sm-global-violation-modal').style.display='flex';}" class="sm-btn sm-btn-custom" style="background: #dc2626; color: #ffffff; border: none; border-radius: 12px; padding: 0 20px; height: 42px; font-weight: 800; font-size: 13.5px; display: inline-flex; align-items: center; gap: 8px; cursor: pointer; box-shadow: 0 4px 12px rgba(220, 38, 38, 0.25); transition: all 0.2s;" onmouseover="this.style.background='#b91c1c'" onmouseout="this.style.background='#dc2626'">
                    <svg width="18" height="18" fill="none" stroke="#ffffff" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path></svg>
                    <span>تسجيل مخالفة</span>
                </button>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- 2. Search & Filtering Card -->
    <div style="background: #ffffff; padding: 20px 24px; border: 1px solid #e2e8f0; border-radius: 20px; margin-bottom: 20px; box-shadow: 0 4px 16px rgba(0,0,0,0.02);">
        <form id="violation-filter-form" method="get">
            <input type="hidden" name="page" value="sm-dashboard">
            <input type="hidden" name="sm_tab" value="stats">
            <input type="hidden" name="paged" id="filter_paged" value="1">
            <input type="hidden" name="limit" id="filter_limit" value="<?php echo esc_attr($limit); ?>">
            <input type="hidden" name="orderby" id="filter_orderby" value="<?php echo esc_attr($orderby); ?>">
            <input type="hidden" name="order" id="filter_order" value="<?php echo esc_attr($order); ?>">

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; align-items: end;">
                <?php if (!$is_parent): ?>
                <!-- Student Search -->
                <div>
                    <label style="display: block; margin-bottom: 6px; font-size: 12.5px; font-weight: 700; color: #334155;">
                        البحث عن طالب
                    </label>
                    <div style="position: relative;">
                        <input type="text" id="filter_student_search" name="student_search" value="<?php echo esc_attr($_GET['student_search'] ?? ''); ?>" placeholder="اسم الطالب / رقم الهوية / الكود..." style="width: 100%; height: 42px; padding: 0 38px 0 14px; border: 1px solid #cbd5e1; border-radius: 12px; font-size: 13px; outline: none; background: #f8fafc; transition: all 0.2s;" onfocus="this.style.borderColor='#dc2626'; this.style.background='#fff';" onblur="this.style.borderColor='#cbd5e1'; this.style.background='#f8fafc';">
                        <span style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; pointer-events: none; display: flex; align-items: center;">
                            <svg width="16" height="16" fill="none" stroke="#94a3b8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        </span>
                    </div>
                </div>

                <!-- Grade Filter -->
                <div>
                    <label style="display: block; margin-bottom: 6px; font-size: 12.5px; font-weight: 700; color: #334155;">
                        الصف الدراسي
                    </label>
                    <div style="position: relative;">
                        <select id="filter_class" name="class_filter" style="width: 100%; height: 42px; padding: 0 38px 0 26px; border: 1px solid #cbd5e1; border-radius: 12px; font-size: 13px; outline: none; background: #f8fafc; appearance: none; -webkit-appearance: none; cursor: pointer; transition: all 0.2s;" onfocus="this.style.borderColor='#dc2626'; this.style.background='#fff';" onblur="this.style.borderColor='#cbd5e1'; this.style.background='#f8fafc';">
                            <option value="">جميع الصفوف</option>
                            <?php
                            global $wpdb;
                            $classes = $wpdb->get_col("SELECT DISTINCT class_name FROM {$wpdb->prefix}sm_students ORDER BY CAST(REPLACE(class_name, 'الصف ', '') AS UNSIGNED) ASC");
                            foreach ($classes as $c): ?>
                                <option value="<?php echo esc_attr($c); ?>" <?php selected(isset($_GET['class_filter']) && $_GET['class_filter'] == $c); ?>><?php echo esc_html($c); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <span style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; pointer-events: none; display: flex; align-items: center;">
                            <svg width="16" height="16" fill="none" stroke="#94a3b8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                        </span>
                        <span style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #94a3b8; pointer-events: none; font-size: 9px;">▼</span>
                    </div>
                </div>

                <!-- Section Filter -->
                <div>
                    <label style="display: block; margin-bottom: 6px; font-size: 12.5px; font-weight: 700; color: #334155;">
                        الشعبة
                    </label>
                    <div style="position: relative;">
                        <select id="filter_section" name="section_filter" style="width: 100%; height: 42px; padding: 0 38px 0 26px; border: 1px solid #cbd5e1; border-radius: 12px; font-size: 13px; outline: none; background: #f8fafc; appearance: none; -webkit-appearance: none; cursor: pointer; transition: all 0.2s;" onfocus="this.style.borderColor='#dc2626'; this.style.background='#fff';" onblur="this.style.borderColor='#cbd5e1'; this.style.background='#f8fafc';">
                            <option value="">جميع الشعب</option>
                            <?php
                            $sections = $wpdb->get_col("SELECT DISTINCT section FROM {$wpdb->prefix}sm_students WHERE section != '' ORDER BY section ASC");
                            foreach ($sections as $s): ?>
                                <option value="<?php echo esc_attr($s); ?>" <?php selected(isset($_GET['section_filter']) && $_GET['section_filter'] == $s); ?>><?php echo esc_html($s); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <span style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; pointer-events: none; display: flex; align-items: center;">
                            <svg width="16" height="16" fill="none" stroke="#94a3b8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        </span>
                        <span style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #94a3b8; pointer-events: none; font-size: 9px;">▼</span>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Violation Type Filter -->
                <div>
                    <label style="display: block; margin-bottom: 6px; font-size: 12.5px; font-weight: 700; color: #334155;">
                        نوع المخالفة
                    </label>
                    <div style="position: relative;">
                        <select id="filter_type" name="type_filter" style="width: 100%; height: 42px; padding: 0 38px 0 26px; border: 1px solid #cbd5e1; border-radius: 12px; font-size: 13px; outline: none; background: #f8fafc; appearance: none; -webkit-appearance: none; cursor: pointer; transition: all 0.2s;" onfocus="this.style.borderColor='#dc2626'; this.style.background='#fff';" onblur="this.style.borderColor='#cbd5e1'; this.style.background='#f8fafc';">
                            <option value="">جميع الأنواع</option>
                            <?php foreach (SM_Settings::get_violation_types() as $k => $v): ?>
                                <option value="<?php echo esc_attr($k); ?>" <?php selected(isset($_GET['type_filter']) && $_GET['type_filter'] == $k); ?>><?php echo esc_html($v); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <span style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; pointer-events: none; display: flex; align-items: center;">
                            <svg width="16" height="16" fill="none" stroke="#94a3b8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
                        </span>
                        <span style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #94a3b8; pointer-events: none; font-size: 9px;">▼</span>
                    </div>
                </div>

                <!-- Apply Filters Button -->
                <div style="display: flex; gap: 8px; align-items: center;">
                    <button type="submit" class="sm-btn" style="background: #dc2626; color: #ffffff; border: none; border-radius: 12px; height: 42px; padding: 0 22px; font-weight: 800; font-size: 13.5px; width: 100%; display: inline-flex; align-items: center; justify-content: center; gap: 8px; cursor: pointer; box-shadow: 0 4px 12px rgba(220, 38, 38, 0.2); transition: all 0.2s;" onmouseover="this.style.background='#b91c1c'" onmouseout="this.style.background='#dc2626'">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
                        <span>تطبيق الفلترة</span>
                    </button>
                    <div id="filter-loader" style="display:none; align-self:center;"><span class="dashicons dashicons-update spin"></span></div>
                </div>
            </div>
        </form>
    </div>

    <!-- Import Form Drawer (Hidden by default) -->
    <div id="violation-import-form" style="display:none; background: #f8fafc; padding: 24px; border: 2px dashed #cbd5e1; border-radius: 16px; margin-bottom: 20px;">
        <h3 style="margin-top:0; color:#1e293b; font-size: 16px; font-weight: 800;">دليل استيراد سجلات المخالفات السلوكية (Excel / CSV)</h3>
        
        <p style="font-size: 12.5px; color: #64748b; line-height: 1.6; margin-bottom: 15px;">
            يتم مطابقة المخرجات مع قاعدة بيانات الطلاب باستخدام <strong>رقم الطالب (Student Number) في العمود A</strong> كمرجع رئيسي لربط المخالفة بالطالب تلقائياً دون تكرار.
        </p>

        <div style="background:#fff; padding:15px; border-radius:12px; border:1px solid #e2e8f0; margin-bottom:20px; overflow-x: auto;">
            <p style="font-size:12.5px; font-weight:700; margin-bottom:10px; color:#334155;">هيكل ملف السجلات القياسي (ترتيب الأعمدة):</p>
            <table style="width:100%; font-size:11px; border-collapse:collapse; text-align:center; min-width: 600px;">
                <thead>
                    <tr style="background:#f1f5f9;">
                        <th style="border:1px solid #cbd5e1; padding:6px; color:#dc2626;">A: رقم الطالب *</th>
                        <th style="border:1px solid #cbd5e1; padding:6px;">B: اسم الطالب</th>
                        <th style="border:1px solid #cbd5e1; padding:6px;">C: الجنسية</th>
                        <th style="border:1px solid #cbd5e1; padding:6px;">D: المدرسة</th>
                        <th style="border:1px solid #cbd5e1; padding:6px;">E: الصف</th>
                        <th style="border:1px solid #cbd5e1; padding:6px;">F: الشعبة</th>
                        <th style="border:1px solid #cbd5e1; padding:6px; color:#dc2626;">G: نوع المخالفة *</th>
                        <th style="border:1px solid #cbd5e1; padding:6px;">H: بند المخالفة</th>
                        <th style="border:1px solid #cbd5e1; padding:6px; color:#dc2626;">I: التفاصيل *</th>
                        <th style="border:1px solid #cbd5e1; padding:6px; color:#dc2626;">J: التاريخ *</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="border:1px solid #cbd5e1; padding:6px; font-weight:700; color:#dc2626;">10001</td>
                        <td style="border:1px solid #cbd5e1; padding:6px;">أحمد علي</td>
                        <td style="border:1px solid #cbd5e1; padding:6px;">إماراتي</td>
                        <td style="border:1px solid #cbd5e1; padding:6px;">المدرسة الرئيسية</td>
                        <td style="border:1px solid #cbd5e1; padding:6px;">الصف 10</td>
                        <td style="border:1px solid #cbd5e1; padding:6px;">1</td>
                        <td style="border:1px solid #cbd5e1; padding:6px; font-weight:700; color:#dc2626;">سلوكية</td>
                        <td style="border:1px solid #cbd5e1; padding:6px;">V-102</td>
                        <td style="border:1px solid #cbd5e1; padding:6px;">التأخر عن الحصة</td>
                        <td style="border:1px solid #cbd5e1; padding:6px; font-weight:700; color:#dc2626;"><?php echo date('Y-m-d'); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <form method="post" enctype="multipart/form-data" onsubmit="return handleImportSubmit(this, 'sm_import_violations_csv')">
            <?php wp_nonce_field('sm_admin_action', 'sm_admin_nonce'); ?>
            <div class="sm-form-group">
                <label class="sm-label" style="font-size: 13px; font-weight: 700; color: #334155;">اختر ملف CSV / Excel للمخالفات السلوكية:</label>
                <input type="file" name="csv_file" accept=".csv, .txt" required style="margin-top: 6px;">
            </div>
            <div id="import-loading" style="display:none; margin-bottom: 15px; padding: 10px; background: #ebf8ff; border-left: 4px solid #3182ce; color: #2c5282; font-weight: 700; border-radius: 8px;">
                <span class="dashicons dashicons-update spin" style="margin-left: 10px;"></span>
                جاري استيراد البيانات وربط المخالفات بالطلاب... يرجى عدم إغلاق الصفحة.
            </div>
            <div style="display:flex; gap:10px; margin-top:20px;">
                <button type="submit" name="sm_import_violations_csv" class="sm-btn" style="width:auto; background:#16a34a; border-radius: 10px; height: 40px; padding: 0 18px; font-weight: 700;">استيراد السجلات وتثبيتها الآن</button>
                <button type="button" onclick="this.parentElement.parentElement.parentElement.style.display='none'" class="sm-btn" style="width:auto; background:#94a3b8; border-radius: 10px; height: 40px; padding: 0 18px; font-weight: 700;">إلغاء</button>
            </div>
        </form>

        <script>
        function handleImportSubmit(form, btnName) {
            const btn = form.querySelector('button[name="' + btnName + '"]');
            const loader = form.querySelector('#import-loading');

            btn.disabled = true;
            btn.style.opacity = '0.5';
            btn.innerText = 'جاري المعالجة...';
            if(loader) loader.style.display = 'block';

            return true;
        }
        </script>
        <style>
        @keyframes spin { 100% { transform:rotate(360deg); } }
        .spin { animation: spin 1s linear infinite; }
        </style>
    </div>

    <!-- 3. Records Table Card -->
    <div style="background: #ffffff; border-radius: 20px; border: 1px solid #e2e8f0; box-shadow: 0 4px 20px rgba(0,0,0,0.02); overflow: hidden; margin-bottom: 24px;">

        <!-- Table Top Control Bar -->
        <div style="padding: 18px 24px; border-bottom: 1px solid #f1f5f9; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 14px; background: #ffffff;">
            <!-- Left Header Info -->
            <div style="display: flex; align-items: center; gap: 10px;">
                <span style="font-weight: 800; font-size: 15px; color: #0f172a;">سجلات المخالفات المسجلة</span>
                <span id="violation-total-badge" style="display: inline-flex; align-items: center; padding: 3px 10px; background: #fee2e2; color: #dc2626; border-radius: 12px; font-size: 12px; font-weight: 800;">
                    <?php echo $total_records; ?> مخالفة
                </span>
            </div>

            <!-- Right Controls (Sorting & Items per page) -->
            <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                <!-- Sorting dropdown -->
                <div style="display: flex; align-items: center; gap: 6px; font-size: 12.5px; color: #64748b; font-weight: 600;">
                    <span>الترتيب حسب:</span>
                    <select id="table_sort_select" onchange="changeTableSorting(this.value)" style="height: 36px; padding: 0 28px 0 12px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 12.5px; color: #334155; font-weight: 700; outline: none; background: #f8fafc; cursor: pointer;">
                        <option value="created_at_DESC" <?php selected($orderby == 'created_at' && $order == 'DESC'); ?>>الأحدث أولاً</option>
                        <option value="created_at_ASC" <?php selected($orderby == 'created_at' && $order == 'ASC'); ?>>الأقدم أولاً</option>
                        <option value="degree_DESC" <?php selected($orderby == 'degree' && $order == 'DESC'); ?>>الأعلى درجة</option>
                        <option value="degree_ASC" <?php selected($orderby == 'degree' && $order == 'ASC'); ?>>الأقل درجة</option>
                        <option value="student_ASC" <?php selected($orderby == 'student' && $order == 'ASC'); ?>>اسم الطالب (أ - ي)</option>
                    </select>
                </div>

                <!-- Page limit dropdown -->
                <div style="display: flex; align-items: center; gap: 6px; font-size: 12.5px; color: #64748b; font-weight: 600;">
                    <span>عرض:</span>
                    <select id="table_limit_select" onchange="changeTableLimit(this.value)" style="height: 36px; padding: 0 24px 0 10px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 12.5px; color: #334155; font-weight: 700; outline: none; background: #f8fafc; cursor: pointer;">
                        <option value="10" <?php selected($limit == 10); ?>>10</option>
                        <option value="25" <?php selected($limit == 25); ?>>25</option>
                        <option value="50" <?php selected($limit == 50); ?>>50</option>
                        <option value="100" <?php selected($limit == 100); ?>>100</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Table Responsive Wrapper -->
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: separate; border-spacing: 0; text-align: right;">
                <thead>
                    <tr style="background: #f8fafc; border-bottom: 1px solid #e2e8f0;">
                        <th style="padding: 14px 18px; font-size: 12.5px; font-weight: 800; color: #475569; border-bottom: 2px solid #e2e8f0; width: 28%;">الطالب</th>
                        <th style="padding: 14px 18px; font-size: 12.5px; font-weight: 800; color: #475569; border-bottom: 2px solid #e2e8f0; width: 20%;">المدرسة / الصف / الشعبة</th>
                        <th style="padding: 14px 18px; font-size: 12.5px; font-weight: 800; color: #475569; border-bottom: 2px solid #e2e8f0; width: 14%;">التاريخ واليوم</th>
                        <th style="padding: 14px 18px; font-size: 12.5px; font-weight: 800; color: #475569; border-bottom: 2px solid #e2e8f0; width: 18%;">بند المخالفة والدرجة</th>
                        <th style="padding: 14px 18px; font-size: 12.5px; font-weight: 800; color: #475569; border-bottom: 2px solid #e2e8f0; text-align: center; width: 5%;">تكرار</th>
                        <th style="padding: 14px 18px; font-size: 12.5px; font-weight: 800; color: #475569; border-bottom: 2px solid #e2e8f0; text-align: center; width: 8%;">الشدة / الحالة</th>
                        <th style="padding: 14px 18px; font-size: 12.5px; font-weight: 800; color: #475569; border-bottom: 2px solid #e2e8f0; text-align: center; width: 17%;">الإجراءات الإدارية</th>
                    </tr>
                </thead>
                <tbody id="violations-table-body">
                    <?php include SM_PLUGIN_DIR . 'templates/partials/violations-table-rows.php'; ?>
                </tbody>
            </table>
        </div>

        <!-- 4. Pagination Footer -->
        <div style="padding: 16px 24px; border-top: 1px solid #f1f5f9; background: #ffffff; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 14px;">
            <div id="pagination-info" style="font-size: 13px; color: #64748b; font-weight: 600;">
                عرض <span id="pag-from" style="color: #0f172a; font-weight: 800;"><?php echo $from_num; ?></span> - <span id="pag-to" style="color: #0f172a; font-weight: 800;"><?php echo $to_num; ?></span> من إجمالي <span id="pag-total" style="color: #0f172a; font-weight: 800;"><?php echo $total_records; ?></span> مخالفة
            </div>

            <div id="pagination-controls" style="display: flex; align-items: center; gap: 6px;">
                <?php
                // Generate Pagination HTML
                $prev_disabled = ($paged <= 1);
                $next_disabled = ($paged >= $total_pages);
                ?>
                <button type="button" onclick="goToPage(1)" <?php if ($prev_disabled) echo 'disabled'; ?> class="pag-btn" style="height: 36px; padding: 0 10px; border-radius: 8px; border: 1px solid #cbd5e1; background: <?php echo $prev_disabled ? '#f8fafc' : '#ffffff'; ?>; color: <?php echo $prev_disabled ? '#94a3b8' : '#334155'; ?>; font-size: 12px; font-weight: 700; cursor: <?php echo $prev_disabled ? 'not-allowed' : 'pointer'; ?>;">الأولى</button>
                <button type="button" onclick="goToPage(<?php echo max(1, $paged - 1); ?>)" <?php if ($prev_disabled) echo 'disabled'; ?> class="pag-btn" style="height: 36px; padding: 0 12px; border-radius: 8px; border: 1px solid #cbd5e1; background: <?php echo $prev_disabled ? '#f8fafc' : '#ffffff'; ?>; color: <?php echo $prev_disabled ? '#94a3b8' : '#334155'; ?>; font-size: 12px; font-weight: 700; cursor: <?php echo $prev_disabled ? 'not-allowed' : 'pointer'; ?>;">السابق</button>

                <div id="pag-numbers" style="display: flex; gap: 4px;">
                    <?php
                    $start_p = max(1, $paged - 2);
                    $end_p = min($total_pages, $paged + 2);
                    for ($p = $start_p; $p <= $end_p; $p++):
                        $is_active = ($p == $paged);
                    ?>
                        <button type="button" onclick="goToPage(<?php echo $p; ?>)" class="pag-btn" style="height: 36px; min-width: 36px; padding: 0 8px; border-radius: 8px; border: 1px solid <?php echo $is_active ? '#dc2626' : '#cbd5e1'; ?>; background: <?php echo $is_active ? '#dc2626' : '#ffffff'; ?>; color: <?php echo $is_active ? '#ffffff' : '#334155'; ?>; font-size: 12px; font-weight: 800; cursor: pointer;"><?php echo $p; ?></button>
                    <?php endfor; ?>
                </div>

                <button type="button" onclick="goToPage(<?php echo min($total_pages, $paged + 1); ?>)" <?php if ($next_disabled) echo 'disabled'; ?> class="pag-btn" style="height: 36px; padding: 0 12px; border-radius: 8px; border: 1px solid #cbd5e1; background: <?php echo $next_disabled ? '#f8fafc' : '#ffffff'; ?>; color: <?php echo $next_disabled ? '#94a3b8' : '#334155'; ?>; font-size: 12px; font-weight: 700; cursor: <?php echo $next_disabled ? 'not-allowed' : 'pointer'; ?>;">التالي</button>
                <button type="button" onclick="goToPage(<?php echo $total_pages; ?>)" <?php if ($next_disabled) echo 'disabled'; ?> class="pag-btn" style="height: 36px; padding: 0 10px; border-radius: 8px; border: 1px solid #cbd5e1; background: <?php echo $next_disabled ? '#f8fafc' : '#ffffff'; ?>; color: <?php echo $next_disabled ? '#94a3b8' : '#334155'; ?>; font-size: 12px; font-weight: 700; cursor: <?php echo $next_disabled ? 'not-allowed' : 'pointer'; ?>;">الأخيرة</button>
            </div>
        </div>

    </div>

    <!-- Unified Student Profile Edit Modal -->
    <?php if (current_user_can('إدارة_الطلاب')): ?>
        <?php include SM_PLUGIN_DIR . 'templates/partials/student-profile-edit-modal.php'; ?>
    <?php endif; ?>

    <!-- Edit Record Modal -->
    <div id="edit-record-modal" class="sm-modal-overlay" style="display: none;">
        <div class="sm-modal-content" style="max-width: 750px; border-radius: 20px; padding: 28px;">
            <div class="sm-modal-header" style="border-bottom: 1px solid #e2e8f0; padding-bottom: 14px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
                <h3 style="margin:0; font-size: 18px; font-weight: 800; color: #0f172a;">تعديل بيانات المخالفة</h3>
                <button class="sm-modal-close" onclick="document.getElementById('edit-record-modal').style.display='none'" style="background: none; border: none; font-size: 24px; color: #94a3b8; cursor: pointer;">&times;</button>
            </div>
            <form method="post" id="edit-record-form" class="sm-form-container">
                <?php wp_nonce_field('sm_record_action', 'sm_nonce'); ?>
                <input type="hidden" name="record_id" id="edit_record_id">
                
                <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 16px; background: #f8fafc; padding: 18px; border-radius: 14px; border: 1px solid #e2e8f0; margin-bottom: 20px;">
                    <div class="sm-form-group" style="margin-bottom:0;">
                        <label class="sm-label" style="font-size: 12.5px; font-weight: 700; color: #334155;">درجة المخالفة (المستوى):</label>
                        <select name="degree" id="edit_violation_degree" class="sm-select" onchange="updateEditHierarchicalViolations()" required style="height: 40px; border-radius: 10px; border: 1px solid #cbd5e1; padding: 0 10px; font-size: 13px;">
                            <option value="1">المستوى الأول (بسيطة)</option>
                            <option value="2">المستوى الثاني (متوسطة)</option>
                            <option value="3">المستوى الثالث (جسيمة)</option>
                            <option value="4">المستوى الرابع (شديدة الخطورة)</option>
                        </select>
                    </div>

                    <div class="sm-form-group" style="margin-bottom:0;">
                        <label class="sm-label" style="font-size: 12.5px; font-weight: 700; color: #334155;">البند القانوني / نوع المخالفة:</label>
                        <select name="violation_code" id="edit_violation_code_select" class="sm-select" onchange="onEditViolationSelected()" required style="height: 40px; border-radius: 10px; border: 1px solid #cbd5e1; padding: 0 10px; font-size: 13px;">
                            <option value="">-- اختر البند --</option>
                        </select>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px; margin-bottom: 16px;">
                    <div class="sm-form-group">
                        <label class="sm-label" style="font-size: 12.5px; font-weight: 700; color: #334155;">تصنيف الموقف:</label>
                        <select name="classification" id="edit_classification" class="sm-select" style="height: 40px; border-radius: 10px; border: 1px solid #cbd5e1; padding: 0 10px; font-size: 13px;">
                            <option value="general">عام</option>
                            <option value="inside_class">داخل الفصل</option>
                            <option value="yard">في الساحة</option>
                            <option value="labs">في المختبرات</option>
                            <option value="bus">الحافلة المدرسية</option>
                        </select>
                    </div>

                    <div class="sm-form-group">
                        <label class="sm-label" style="font-size: 12.5px; font-weight: 700; color: #334155;">النقاط المستحقة:</label>
                        <input type="number" name="points" id="edit_violation_points" class="sm-input" value="0" style="height: 40px; border-radius: 10px; border: 1px solid #cbd5e1; padding: 0 12px; font-size: 13px;">
                    </div>
                    <input type="hidden" name="type" id="edit_hidden_violation_type">
                    <input type="hidden" name="severity" id="edit_violation_severity">
                </div>

                <div class="sm-form-group" style="margin-bottom: 16px;">
                    <label class="sm-label" style="font-size: 12.5px; font-weight: 700; color: #334155;">الإجراء المتخذ:</label>
                    <input type="text" name="action_taken" id="edit_action_taken" class="sm-input" style="height: 40px; border-radius: 10px; border: 1px solid #cbd5e1; padding: 0 12px; font-size: 13px; width: 100%;">
                </div>

                <div class="sm-form-group" style="margin-bottom: 20px;">
                    <label class="sm-label" style="font-size: 12.5px; font-weight: 700; color: #334155;">التفاصيل:</label>
                    <textarea name="details" id="edit_details" class="sm-textarea" rows="3" style="border-radius: 10px; border: 1px solid #cbd5e1; padding: 10px; font-size: 13px; width: 100%;"></textarea>
                </div>

                <div style="display:flex; gap:12px; justify-content: flex-end;">
                    <button type="submit" name="sm_update_record" class="sm-btn" style="background: #dc2626; color: #fff; height: 42px; border-radius: 10px; padding: 0 20px; font-weight: 800; border: none;">حفظ التغييرات</button>
                    <button type="button" onclick="document.getElementById('edit-record-modal').style.display='none'" class="sm-btn" style="background: #cbd5e1; color: #334155; height: 42px; border-radius: 10px; padding: 0 16px; font-weight: 700; border: none;">إلغاء</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    const hViolations = <?php echo json_encode(SM_Settings::get_hierarchical_violations()); ?>;

    function updateEditHierarchicalViolations(selectedCode = '') {
        const degree = document.getElementById('edit_violation_degree').value;
        const select = document.getElementById('edit_violation_code_select');

        select.innerHTML = '<option value="">-- اختر البند --</option>';
        if (!degree || !hViolations[degree]) return;

        Object.keys(hViolations[degree]).forEach(code => {
            const v = hViolations[degree][code];
            const opt = document.createElement('option');
            opt.value = code;
            opt.innerText = code + ' - ' + v.name;
            if (code === selectedCode) opt.selected = true;
            select.appendChild(opt);
        });
    }

    function onEditViolationSelected() {
        const degree = document.getElementById('edit_violation_degree').value;
        const code = document.getElementById('edit_violation_code_select').value;
        if (!degree || !code || !hViolations[degree][code]) return;

        const v = hViolations[degree][code];
        document.getElementById('edit_violation_points').value = v.points;
        document.getElementById('edit_action_taken').value = v.action;
        document.getElementById('edit_hidden_violation_type').value = v.name;

        const sev = document.getElementById('edit_violation_severity');
        if (degree == 1) sev.value = 'low';
        else if (degree == 2) sev.value = 'medium';
        else sev.value = 'high';
    }

    function editSmRecord(record) {
        document.getElementById('edit_record_id').value = record.id;
        document.getElementById('edit_violation_degree').value = record.degree || 1;
        document.getElementById('edit_classification').value = record.classification || 'general';
        document.getElementById('edit_violation_points').value = record.points || 0;
        document.getElementById('edit_action_taken').value = record.action_taken || '';
        document.getElementById('edit_details').value = record.details || '';
        document.getElementById('edit_hidden_violation_type').value = record.type || '';
        document.getElementById('edit_violation_severity').value = record.severity || 'low';

        updateEditHierarchicalViolations(record.violation_code);
        document.getElementById('edit-record-modal').style.display = 'flex';
    }
    </script>

    <!-- View Details Modal -->
    <div id="view-record-modal" class="sm-modal-overlay" style="display: none;">
        <div class="sm-modal-content" style="max-width: 650px; border-radius: 20px; padding: 28px;">
            <div class="sm-modal-header" style="border-bottom: 1px solid #e2e8f0; padding-bottom: 16px; margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between;">
                <h3 style="margin: 0; font-size: 18px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 8px;">
                    <svg width="20" height="20" fill="none" stroke="#dc2626" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    تفاصيل المخالفة السلوكية
                </h3>
                <button type="button" class="sm-modal-close" onclick="document.getElementById('view-record-modal').style.display='none'" style="background: none; border: none; font-size: 24px; color: #94a3b8; cursor: pointer;">&times;</button>
            </div>

            <div style="display: flex; flex-direction: column; gap: 16px;">
                <div style="background: #f8fafc; border-radius: 14px; padding: 16px; border: 1px solid #e2e8f0; display: flex; align-items: center; gap: 14px;">
                    <div id="view_stu_photo" style="width: 50px; height: 50px; border-radius: 50%; overflow: hidden; background: #cbd5e1; display: flex; align-items: center; justify-content: center; border: 2px solid #fff; flex-shrink: 0;"></div>
                    <div>
                        <div id="view_stu_name" style="font-size: 16px; font-weight: 800; color: #0f172a;"></div>
                        <div style="font-size: 12px; color: #64748b; margin-top: 3px;">
                            المدرسة: <span id="view_school_name" style="font-weight: 700; color: #334155;"></span> | الصف والشعبة: <span id="view_class_sec" style="font-weight: 700; color: #334155;"></span>
                        </div>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <div style="background: #ffffff; border: 1px solid #e2e8f0; padding: 12px 16px; border-radius: 12px;">
                        <span style="font-size: 11px; color: #64748b; font-weight: 700; display: block; margin-bottom: 4px;">كود المخالفة / البند</span>
                        <span id="view_violation_code" style="font-size: 14px; font-weight: 800; color: #0f172a;"></span>
                    </div>
                    <div style="background: #ffffff; border: 1px solid #e2e8f0; padding: 12px 16px; border-radius: 12px;">
                        <span style="font-size: 11px; color: #64748b; font-weight: 700; display: block; margin-bottom: 4px;">درجة المخالفة</span>
                        <span id="view_degree" style="font-size: 14px; font-weight: 800; color: #dc2626;"></span>
                    </div>
                    <div style="background: #ffffff; border: 1px solid #e2e8f0; padding: 12px 16px; border-radius: 12px;">
                        <span style="font-size: 11px; color: #64748b; font-weight: 700; display: block; margin-bottom: 4px;">التاريخ</span>
                        <span id="view_date" style="font-size: 13px; font-weight: 700; color: #334155;"></span>
                    </div>
                    <div style="background: #ffffff; border: 1px solid #e2e8f0; padding: 12px 16px; border-radius: 12px;">
                        <span style="font-size: 11px; color: #64748b; font-weight: 700; display: block; margin-bottom: 4px;">عدد مرات التكرار</span>
                        <span id="view_recurrence" style="font-size: 13px; font-weight: 800; color: #334155;"></span>
                    </div>
                </div>

                <div style="background: #ffffff; border: 1px solid #e2e8f0; padding: 14px 16px; border-radius: 12px;">
                    <span style="font-size: 11px; color: #64748b; font-weight: 700; display: block; margin-bottom: 4px;">تفاصيل الموقف السلوكي</span>
                    <p id="view_details_text" style="margin: 0; font-size: 13px; color: #334155; line-height: 1.6; font-weight: 500;"></p>
                </div>

                <div style="background: #ffffff; border: 1px solid #e2e8f0; padding: 14px 16px; border-radius: 12px;">
                    <span style="font-size: 11px; color: #64748b; font-weight: 700; display: block; margin-bottom: 4px;">الإجراء المتخذ</span>
                    <p id="view_action_text" style="margin: 0; font-size: 13px; color: #16a34a; line-height: 1.6; font-weight: 700;"></p>
                </div>
            </div>

            <div style="margin-top: 24px; display: flex; justify-content: flex-end;">
                <button type="button" onclick="document.getElementById('view-record-modal').style.display='none'" class="sm-btn" style="background: #475569; color: #fff; border-radius: 10px; padding: 0 22px; height: 40px; font-weight: 700; border: none;">إغلاق</button>
            </div>
        </div>
    </div>

    <!-- Delete Record Confirmation Modal -->
    <div id="delete-record-modal" class="sm-modal-overlay" style="display: none;">
        <div class="sm-modal-content" style="max-width: 400px; text-align: center; border-radius: 20px; padding: 28px;">
            <div style="color: #dc2626; font-size: 40px; margin-bottom: 15px;">
                <svg width="48" height="48" fill="none" stroke="#dc2626" viewBox="0 0 24 24" style="margin: 0 auto;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            </div>
            <h3 style="margin:0 0 10px 0; border:none; font-size: 18px; font-weight: 800; color: #0f172a;">تأكيد حذف المخالفة</h3>
            <p style="color: #64748b; font-size: 13.5px; margin-bottom: 20px; line-height: 1.5;">هل أنت متأكد من حذف هذا السجل نهائياً؟ لا يمكن التراجع عن هذه العملية.</p>
            <input type="hidden" id="confirm_delete_record_id">
            <div style="display: flex; gap: 12px; justify-content: center;">
                <button onclick="executeDeleteRecord()" class="sm-btn" style="background: #dc2626; color: #fff; border-radius: 10px; height: 40px; padding: 0 20px; font-weight: 800; border: none;">حذف نهائي</button>
                <button onclick="document.getElementById('delete-record-modal').style.display='none'" class="sm-btn" style="background: #cbd5e1; color: #334155; border-radius: 10px; height: 40px; padding: 0 20px; font-weight: 700; border: none;">تراجع</button>
            </div>
        </div>
    </div>

    <!-- Main JavaScript Logic -->
    <script>
    // Global filter and pagination state
    let currentPage = <?php echo $paged; ?>;
    let currentLimit = <?php echo $limit; ?>;
    let currentOrderBy = '<?php echo esc_js($orderby); ?>';
    let currentOrder = '<?php echo esc_js($order); ?>';

    function fetchViolationsData() {
        const loader = document.getElementById('filter-loader');
        const tbody = document.getElementById('violations-table-body');

        if (loader) loader.style.display = 'inline-block';
        if (tbody) tbody.style.opacity = '0.5';

        const filterForm = document.getElementById('violation-filter-form');
        const formData = new FormData(filterForm);
        formData.append('action', 'sm_filter_violations');
        formData.append('nonce', '<?php echo wp_create_nonce("sm_record_action"); ?>');
        formData.append('paged', currentPage);
        formData.append('limit', currentLimit);
        formData.append('orderby', currentOrderBy);
        formData.append('order', currentOrder);

        fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                if (tbody) tbody.innerHTML = res.data.html;

                // Update counts and pagination UI
                const total = res.data.total;
                const totalPages = res.data.total_pages;
                currentPage = res.data.paged;

                const badge = document.getElementById('violation-total-badge');
                if (badge) badge.innerText = total + ' مخالفة';

                const pagFrom = document.getElementById('pag-from');
                const pagTo = document.getElementById('pag-to');
                const pagTotal = document.getElementById('pag-total');
                if (pagFrom) pagFrom.innerText = res.data.from;
                if (pagTo) pagTo.innerText = res.data.to;
                if (pagTotal) pagTotal.innerText = total;

                renderPaginationControls(currentPage, totalPages);
            }
        })
        .finally(() => {
            if (loader) loader.style.display = 'none';
            if (tbody) tbody.style.opacity = '1';
        });
    }

    function renderPaginationControls(page, totalPages) {
        const container = document.getElementById('pagination-controls');
        if (!container) return;

        const prevDisabled = (page <= 1);
        const nextDisabled = (page >= totalPages);

        let html = '';
        html += `<button type="button" onclick="goToPage(1)" ${prevDisabled ? 'disabled' : ''} class="pag-btn" style="height: 36px; padding: 0 10px; border-radius: 8px; border: 1px solid #cbd5e1; background: ${prevDisabled ? '#f8fafc' : '#ffffff'}; color: ${prevDisabled ? '#94a3b8' : '#334155'}; font-size: 12px; font-weight: 700; cursor: ${prevDisabled ? 'not-allowed' : 'pointer'};">الأولى</button>`;
        html += `<button type="button" onclick="goToPage(${Math.max(1, page - 1)})" ${prevDisabled ? 'disabled' : ''} class="pag-btn" style="height: 36px; padding: 0 12px; border-radius: 8px; border: 1px solid #cbd5e1; background: ${prevDisabled ? '#f8fafc' : '#ffffff'}; color: ${prevDisabled ? '#94a3b8' : '#334155'}; font-size: 12px; font-weight: 700; cursor: ${prevDisabled ? 'not-allowed' : 'pointer'};">السابق</button>`;

        html += `<div style="display: flex; gap: 4px;">`;
        const startP = Math.max(1, page - 2);
        const endP = Math.min(totalPages, page + 2);
        for (let p = startP; p <= endP; p++) {
            const isActive = (p === page);
            html += `<button type="button" onclick="goToPage(${p})" class="pag-btn" style="height: 36px; min-width: 36px; padding: 0 8px; border-radius: 8px; border: 1px solid ${isActive ? '#dc2626' : '#cbd5e1'}; background: ${isActive ? '#dc2626' : '#ffffff'}; color: ${isActive ? '#ffffff' : '#334155'}; font-size: 12px; font-weight: 800; cursor: pointer;">${p}</button>`;
        }
        html += `</div>`;

        html += `<button type="button" onclick="goToPage(${Math.min(totalPages, page + 1)})" ${nextDisabled ? 'disabled' : ''} class="pag-btn" style="height: 36px; padding: 0 12px; border-radius: 8px; border: 1px solid #cbd5e1; background: ${nextDisabled ? '#f8fafc' : '#ffffff'}; color: ${nextDisabled ? '#94a3b8' : '#334155'}; font-size: 12px; font-weight: 700; cursor: ${nextDisabled ? 'not-allowed' : 'pointer'};">التالي</button>`;
        html += `<button type="button" onclick="goToPage(${totalPages})" ${nextDisabled ? 'disabled' : ''} class="pag-btn" style="height: 36px; padding: 0 10px; border-radius: 8px; border: 1px solid #cbd5e1; background: ${nextDisabled ? '#f8fafc' : '#ffffff'}; color: ${nextDisabled ? '#94a3b8' : '#334155'}; font-size: 12px; font-weight: 700; cursor: ${nextDisabled ? 'not-allowed' : 'pointer'};">الأخيرة</button>`;

        container.innerHTML = html;
    }

    function goToPage(page) {
        currentPage = page;
        fetchViolationsData();
    }

    function changeTableSorting(val) {
        const parts = val.split('_');
        if (parts.length >= 2) {
            currentOrder = parts.pop();
            currentOrderBy = parts.join('_');
            currentPage = 1;
            fetchViolationsData();
        }
    }

    function changeTableLimit(val) {
        currentLimit = parseInt(val, 10);
        currentPage = 1;
        fetchViolationsData();
    }

    function exportViolationPDF(range = '') {
        const student = document.getElementById('filter_student_search') ? document.getElementById('filter_student_search').value : '';
        const grade = document.getElementById('filter_class') ? document.getElementById('filter_class').value : '';
        const section = document.getElementById('filter_section') ? document.getElementById('filter_section').value : '';
        const type = document.getElementById('filter_type') ? document.getElementById('filter_type').value : '';

        let url = '<?php echo admin_url('admin-ajax.php?action=sm_print&print_type=violation_report'); ?>';
        if (range) url += '&range=' + encodeURIComponent(range);
        if (student) url += '&search=' + encodeURIComponent(student);
        if (grade) url += '&class_filter=' + encodeURIComponent(grade);
        if (section) url += '&section_filter=' + encodeURIComponent(section);
        if (type) url += '&type_filter=' + encodeURIComponent(type);

        window.open(url, '_blank');
    }

    (function() {
        // Filter Form submit event
        const filterForm = document.getElementById('violation-filter-form');
        if (filterForm) {
            filterForm.onsubmit = function(e) {
                e.preventDefault();
                currentPage = 1;
                fetchViolationsData();
            };
        }

        // Close export dropdown when clicking outside
        document.addEventListener('click', function(e) {
            const dropdown = document.getElementById('eess-violation-export-dropdown');
            if (dropdown && dropdown.style.display === 'block') {
                dropdown.style.display = 'none';
            }
        });

        window.viewViolationDetails = function(record) {
            document.getElementById('view_stu_name').innerText = record.student_name || '---';
            document.getElementById('view_school_name').innerText = record.school_name || 'المدرسة الرئيسية';
            document.getElementById('view_class_sec').innerText = (record.class_name || '') + ' ' + (record.section || '');
            document.getElementById('view_violation_code').innerText = record.violation_code || record.type || '---';
            document.getElementById('view_degree').innerText = 'المستوى / الدرجة ' + (record.degree || 1);
            document.getElementById('view_date').innerText = record.created_at || '---';
            document.getElementById('view_recurrence').innerText = record.recurrence_count || 1;
            document.getElementById('view_details_text').innerText = record.details || 'لا توجد تفاصيل إضافية مسجلة.';
            document.getElementById('view_action_text').innerText = record.action_taken || 'لم يتم تسجيل إجراء إداري بعد.';

            const photoBox = document.getElementById('view_stu_photo');
            if (record.photo_url) {
                photoBox.innerHTML = '<img src="' + record.photo_url + '" style="width:100%; height:100%; object-fit:cover;" />';
            } else {
                photoBox.innerHTML = '<svg width="24" height="24" fill="#94a3b8" viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>';
            }

            document.getElementById('view-record-modal').style.display = 'flex';
        };

        window.updateRecordStatus = function(id, status) {
            const formData = new FormData();
            formData.append('action', 'sm_update_record_status');
            formData.append('record_id', id);
            formData.append('status', status);
            formData.append('nonce', '<?php echo wp_create_nonce("sm_record_action"); ?>');

            fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    if (typeof smShowNotification === 'function') {
                        smShowNotification('تم تحديث حالة المخالفة بنجاح');
                    }
                    fetchViolationsData();
                }
            });
        };

        window.markAsContacted = function(recordId) {
            const formData = new FormData();
            formData.append('action', 'sm_mark_contacted');
            formData.append('record_id', recordId);
            formData.append('nonce', '<?php echo wp_create_nonce("sm_record_action"); ?>');

            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    fetchViolationsData();
                }
            });
        };

        window.editSmStudentFromStats = function(s) {
            if (typeof window.editSmStudent === 'function') {
                window.editSmStudent(s);
            } else {
                const modal = document.getElementById('edit-student-modal');
                if (modal) {
                    document.getElementById('edit_stu_id').value = s.id || '';
                    document.getElementById('edit_stu_name').value = s.name || '';
                    document.getElementById('edit_stu_class').value = s.class_name || '';
                    document.getElementById('edit_stu_section').value = s.section || '';
                    document.getElementById('edit_stu_code').value = s.student_id || s.student_code || '';
                    document.getElementById('edit_stu_email').value = s.parent_email || '';
                    document.getElementById('edit_stu_phone').value = s.guardian_phone || '';
                    modal.style.display = 'flex';
                }
            }
        };

        window.confirmDeleteRecord = function(id) {
            document.getElementById('confirm_delete_record_id').value = id;
            document.getElementById('delete-record-modal').style.display = 'flex';
        };

        window.executeDeleteRecord = function() {
            const id = document.getElementById('confirm_delete_record_id').value;
            const formData = new FormData();
            formData.append('action', 'sm_delete_record_ajax');
            formData.append('record_id', id);
            formData.append('nonce', '<?php echo wp_create_nonce("sm_record_action"); ?>');

            fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    if (typeof smShowNotification === 'function') smShowNotification('تم حذف السجل بنجاح');
                    const row = document.getElementById('record-row-' + id);
                    if (row) row.remove();
                    document.getElementById('delete-record-modal').style.display = 'none';
                    fetchViolationsData();
                }
            });
        };
    })();
    </script>
</div>

<style>
@media print {
    body * { visibility: hidden; }
    .sm-admin-panel, .sm-admin-panel * { visibility: visible; }
    .sm-admin-panel { position: absolute; left: 0; top: 0; width: 100%; }
    .no-print { display: none !important; }
}
</style>
