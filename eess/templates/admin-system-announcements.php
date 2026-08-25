<?php
if (!defined('ABSPATH')) exit;

global $wpdb;
$user_id = get_current_user_id();

$roles_list = array(
    'sm_system_admin'              => 'مدير النظام',
    'sm_principal'                 => 'مدير المدرسة',
    'sm_supervisor'                => 'المشرف التربوي',
    'sm_discipline_supervisor'     => 'مشرف الانضباط السلوكي',
    'sm_activities_supervisor'     => 'مشرف الأنشطة',
    'sm_transportation_supervisor' => 'مشرف النقل والمواصلات',
    'sm_coordinator'               => 'منسق المادة',
    'sm_teacher'                   => 'معلم',
    'sm_bus_supervisor'            => 'مشرف الحافلة',
    'sm_parent'                    => 'ولي أمر'
);

$announcements = $wpdb->get_results("SELECT a.*, u.display_name as author_name FROM {$wpdb->prefix}sm_system_announcements a LEFT JOIN {$wpdb->users} u ON a.created_by = u.ID ORDER BY a.created_at DESC");

// User tracking activity query
$activity_logs = $wpdb->get_results("SELECT ua.*, a.title as announcement_title, u.display_name, u.user_login FROM {$wpdb->prefix}sm_user_announcements ua INNER JOIN {$wpdb->prefix}sm_system_announcements a ON ua.announcement_id = a.id INNER JOIN {$wpdb->users} u ON ua.user_id = u.ID ORDER BY ua.updated_at DESC LIMIT 200");

// Support & Feedback Requests query with filters
$s_cat    = sanitize_text_field($_GET['support_cat'] ?? 'all');
$s_search = sanitize_text_field($_GET['support_search'] ?? '');

$support_requests = SM_DB::get_support_requests(array(
    'category' => $s_cat,
    'search'   => $s_search
));

// Shared Educational Inputs query for Admin Library
$edu_inputs = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}sm_educational_inputs ORDER BY usage_count DESC, id DESC LIMIT 150");
?>

<div class="sm-container" style="padding: 10px 0; font-family: 'Cairo', sans-serif !important; direction: rtl;">

    <!-- Single Main Banner Header (System Settings & Announcements) -->
    <div style="background: #ffffff; padding: 20px 24px; border-radius: 20px; border: 1px solid #e2e8f0; margin-bottom: 18px; box-shadow: 0 4px 18px rgba(0,0,0,0.02); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
        <div style="display: flex; align-items: center; gap: 14px;">
            <div style="width: 48px; height: 48px; background: #fef2f2; border-radius: 14px; display: flex; align-items: center; justify-content: center; color: #881337; border: 1px solid #fecdd3; flex-shrink: 0;">
                <span class="dashicons dashicons-admin-generic" style="font-size: 24px; width: 24px; height: 24px;"></span>
            </div>
            <div>
                <h2 style="margin: 0 0 4px 0; font-size: 20px; font-weight: 800; color: #0f172a;">إعدادات النظام والتعاميم الإدارية</h2>
                <p style="margin: 0; font-size: 12.5px; color: #64748b; font-weight: 500;">إدارة إعدادات المنصة، الهوية البصرية، التعاميم الإدارية ومكتبة المدخلات التعليمية</p>
            </div>
        </div>

        <!-- Primary Header Actions -->
        <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
            <button type="button" onclick="document.getElementById('eess-announcement-form').scrollIntoView({behavior: 'smooth'})" class="sm-btn" style="background: #881337; color: #ffffff !important; height: 38px; border-radius: 9999px !important; padding: 0 20px; font-weight: 800; font-size: 12.5px; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
                <span class="dashicons dashicons-mega" style="font-size: 15px; width: 15px; height: 15px; color: #fff;"></span>
                <span>إضافة تعميم جديد</span>
            </button>
        </div>
    </div>

    <!-- Announcement Creation Form Card -->
    <div style="background: #ffffff; border-radius: 16px; padding: 25px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); margin-bottom: 30px;">
        <h3 style="margin: 0 0 20px 0; font-size: 16px; font-weight: 800; color: #0f172a; border-bottom: 2px solid #f1f5f9; padding-bottom: 10px; display: flex; align-items: center; gap: 8px;">
            <span class="dashicons dashicons-plus-alt" style="color: #2563eb;"></span>
            إضافة إعلان أو تعميم إداري جديد
        </h3>

        <form id="eess-announcement-form" onsubmit="eessCreateAnnouncement(event)">
            <?php wp_nonce_field('sm_announcement_action', 'sm_announcement_nonce'); ?>

            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 700; color: #334155; margin-bottom: 6px;">عنوان الإشعار / التعميم <span style="color:#ef4444;">*</span></label>
                    <input type="text" name="title" required placeholder="مثال: تنبيه هام بشأن مواعيد الاختبارات النصفية" style="width: 100%; height: 42px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 12px; font-size: 13px; box-sizing: border-box;">
                </div>
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 700; color: #334155; margin-bottom: 6px;">نوع الإشعار / الأيقونة</label>
                    <select name="type" style="width: 100%; height: 42px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 12px; font-size: 13px; box-sizing: border-box;">
                        <option value="info">ℹ️ إداري عام (Info)</option>
                        <option value="warning">⚠️ تنبيه هام (Warning)</option>
                        <option value="urgent">🚨 عاجل جداً (Urgent)</option>
                        <option value="success">✅ تحديث جديد (Success)</option>
                    </select>
                </div>
            </div>

            <div style="margin-bottom: 20px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                    <label style="font-size: 13px; font-weight: 700; color: #334155;">تفاصيل الإشعار (الحد الأقصى 500 حرف) <span style="color:#ef4444;">*</span></label>
                    <span id="char-counter" style="font-size: 12px; font-weight: 700; color: #64748b;">0 / 500</span>
                </div>
                <textarea name="details" id="announcement-details" required maxlength="500" rows="4" placeholder="أدخل التفاصيل والتعليمات الخاصة بالإشعار..." oninput="document.getElementById('char-counter').innerText = this.value.length + ' / 500'" style="width: 100%; border: 1px solid #cbd5e1; border-radius: 8px; padding: 10px; font-size: 13px; box-sizing: border-box;"></textarea>
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 13px; font-weight: 700; color: #334155; margin-bottom: 10px;">الرتب المستهدفة بالإشعار <span style="color:#ef4444;">*</span></label>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 10px; background: #f8fafc; padding: 15px; border-radius: 10px; border: 1px solid #e2e8f0;">
                    <?php foreach ($roles_list as $role_key => $role_name): ?>
                        <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 600; color: #334155; cursor: pointer;">
                            <input type="checkbox" name="target_roles[]" value="<?php echo esc_attr($role_key); ?>" checked style="width: 16px; height: 16px;">
                            <span><?php echo esc_html($role_name); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 25px;">
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 700; color: #334155; margin-bottom: 6px;">مدة العرض التلقائي (بالثواني)</label>
                    <input type="number" name="display_duration" value="10" min="3" max="60" style="width: 100%; height: 42px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 12px; font-size: 13px; box-sizing: border-box;">
                </div>
                <div>
                    <label style="display: block; font-size: 13px; font-weight: 700; color: #334155; margin-bottom: 6px;">عدد مرات الظهور التلقائي لكل مستخدم</label>
                    <input type="number" name="display_frequency" value="1" min="1" max="10" style="width: 100%; height: 42px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 12px; font-size: 13px; box-sizing: border-box;">
                </div>
            </div>

            <div id="create-msg-status" style="display: none; margin-bottom: 15px; padding: 12px; border-radius: 8px; font-size: 13px; font-weight: 700;"></div>

            <button type="submit" id="btn-save-announcement" class="sm-btn" style="width: 220px; height: 44px; background: #2563eb; color: white; font-weight: 800; font-size: 14px; border-radius: 10px; border: none; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; gap: 8px;">
                <span class="dashicons dashicons-send"></span> نشر واعتماد الإشعار
            </button>
        </form>
    </div>

    <!-- Active Announcements Table Card -->
    <div style="background: #ffffff; border-radius: 16px; padding: 25px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); margin-bottom: 30px;">
        <h3 style="margin: 0 0 20px 0; font-size: 16px; font-weight: 800; color: #0f172a; border-bottom: 2px solid #f1f5f9; padding-bottom: 10px; display: flex; align-items: center; gap: 8px;">
            <span class="dashicons dashicons-list-view" style="color: #2563eb;"></span>
            سجل الإشعارات المنشورة وإحصائيات القراءة
        </h3>

        <div style="overflow-x: auto;">
            <table class="sm-table" style="width: 100%; border-collapse: collapse; font-size: 13px;">
                <thead>
                    <tr style="background: #f8fafc; text-align: right; border-bottom: 2px solid #e2e8f0;">
                        <th style="padding: 12px;">العنوان</th>
                        <th style="padding: 12px;">الرتب المستهدفة</th>
                        <th style="padding: 12px;">تاريخ النشر</th>
                        <th style="padding: 12px; text-align: center;">المشاهدين</th>
                        <th style="padding: 12px; text-align: center;">المغلقين</th>
                        <th style="padding: 12px; text-align: center;">الحالة</th>
                        <th style="padding: 12px; text-align: center;">إجراءات (Actions)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($announcements)): ?>
                        <tr><td colspan="7" style="padding: 20px; text-align: center; color: #94a3b8;">لا توجد إشعارات منشورة حالياً.</td></tr>
                    <?php else: ?>
                        <?php foreach ($announcements as $anc):
                            $target_arr = json_decode($anc->target_roles, true) ?: array();
                            $target_labels = array_map(function($r) use ($roles_list) { return $roles_list[$r] ?? $r; }, $target_arr);

                            $viewed_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}sm_user_announcements WHERE announcement_id = %d AND status IN ('viewed', 'closed')", $anc->id));
                            $closed_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}sm_user_announcements WHERE announcement_id = %d AND status = 'closed'", $anc->id));
                            $is_active = ($anc->status === 'active');
                        ?>
                            <tr style="border-bottom: 1px solid #f1f5f9;">
                                <td style="padding: 12px; font-weight: 700; color: #0f172a;"><?php echo esc_html($anc->title); ?></td>
                                <td style="padding: 12px; font-size: 11px; color: #475569;"><?php echo esc_html(implode('، ', $target_labels)); ?></td>
                                <td style="padding: 12px; font-size: 12px; color: #64748b;"><?php echo esc_html($anc->created_at); ?></td>
                                <td style="padding: 12px; text-align: center;"><span style="background: #dbeafe; color: #1e40af; padding: 4px 10px; border-radius: 50px; font-weight: 800; font-size: 11px;"><?php echo $viewed_count; ?></span></td>
                                <td style="padding: 12px; text-align: center;"><span style="background: #fef3c7; color: #92400e; padding: 4px 10px; border-radius: 50px; font-weight: 800; font-size: 11px;"><?php echo $closed_count; ?></span></td>
                                <td style="padding: 12px; text-align: center;">
                                    <?php if ($is_active): ?>
                                        <span style="background: #dcfce7; color: #166534; padding: 4px 10px; border-radius: 50px; font-weight: 800; font-size: 11px;">نشط (Active)</span>
                                    <?php else: ?>
                                        <span style="background: #fee2e2; color: #991b1b; padding: 4px 10px; border-radius: 50px; font-weight: 800; font-size: 11px;">معطل (Disabled)</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 12px; text-align: center; display: flex; gap: 6px; justify-content: center;">
                                    <?php if ($is_active): ?>
                                        <button type="button" onclick="eessDisableAnnouncement(<?php echo $anc->id; ?>)" style="background: #ef4444; color: white; border: none; border-radius: 6px; padding: 5px 10px; font-size: 11px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 4px;">
                                            <span class="dashicons dashicons-no-alt" style="font-size: 14px; width: 14px; height: 14px;"></span>
                                            تعطيل
                                        </button>
                                    <?php endif; ?>
                                    <button type="button" onclick="eessDeleteAnnouncement(<?php echo $anc->id; ?>)" style="background: #991b1b; color: white; border: none; border-radius: 6px; padding: 5px 10px; font-size: 11px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 4px;">
                                        <span class="dashicons dashicons-trash" style="font-size: 14px; width: 14px; height: 14px;"></span>
                                        حذف الإشعار
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Detailed User Audit Log & Show Again Action Card -->
    <div style="background: #ffffff; border-radius: 16px; padding: 25px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); margin-bottom: 30px;">
        <h3 style="margin: 0 0 20px 0; font-size: 16px; font-weight: 800; color: #0f172a; border-bottom: 2px solid #f1f5f9; padding-bottom: 10px; display: flex; align-items: center; gap: 8px;">
            <span class="dashicons dashicons-admin-users" style="color: #2563eb;"></span>
            سجل تفاعل المستخدمين الفردي وإعادة إظهار الإشعار (Show Again)
        </h3>

        <div style="overflow-x: auto;">
            <table class="sm-table" style="width: 100%; border-collapse: collapse; font-size: 12px;">
                <thead>
                    <tr style="background: #f8fafc; text-align: right; border-bottom: 2px solid #e2e8f0;">
                        <th style="padding: 12px;">المستخدم</th>
                        <th style="padding: 12px;">الإشعار</th>
                        <th style="padding: 12px; text-align: center;">الحالة</th>
                        <th style="padding: 12px; text-align: center;">عدد المشاهدات</th>
                        <th style="padding: 12px;">تاريخ المشاهدة/الإغلاق</th>
                        <th style="padding: 12px; text-align: center;">إجراءات (Actions)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($activity_logs)): ?>
                        <tr><td colspan="6" style="padding: 20px; text-align: center; color: #94a3b8;">لا توجد سجلات تفاعل حتى الآن.</td></tr>
                    <?php else: ?>
                        <?php foreach ($activity_logs as $log): ?>
                            <tr id="user-log-row-<?php echo $log->id; ?>" style="border-bottom: 1px solid #f1f5f9;">
                                <td style="padding: 12px; font-weight: 700; color: #0f172a;"><?php echo esc_html($log->display_name . ' (' . $log->user_login . ')'); ?></td>
                                <td style="padding: 12px; color: #334155; font-weight: 600;"><?php echo esc_html($log->announcement_title); ?></td>
                                <td style="padding: 12px; text-align: center;">
                                    <?php if ($log->status === 'closed'): ?>
                                        <span style="background: #fee2e2; color: #991b1b; padding: 4px 10px; border-radius: 50px; font-weight: 800; font-size: 11px;">مغلق</span>
                                    <?php elseif ($log->status === 'viewed'): ?>
                                        <span style="background: #dbeafe; color: #1e40af; padding: 4px 10px; border-radius: 50px; font-weight: 800; font-size: 11px;">تمت المشاهدة</span>
                                    <?php else: ?>
                                        <span style="background: #fef3c7; color: #92400e; padding: 4px 10px; border-radius: 50px; font-weight: 800; font-size: 11px;">معلق</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 12px; text-align: center; font-weight: 800;"><?php echo intval($log->view_count); ?></td>
                                <td style="padding: 12px; color: #64748b; font-size: 11px;"><?php echo esc_html($log->closed_at ?: ($log->viewed_at ?: $log->updated_at)); ?></td>
                                <td style="padding: 12px; text-align: center; display: flex; gap: 6px; justify-content: center;">
                                    <button type="button" onclick="eessResetUserAnnouncement(<?php echo $log->announcement_id; ?>, <?php echo $log->user_id; ?>, <?php echo $log->id; ?>)" style="background: #f59e0b; color: white; border: none; border-radius: 6px; padding: 5px 10px; font-size: 11px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 4px;">
                                        <span class="dashicons dashicons-redo" style="font-size: 14px; width: 14px; height: 14px;"></span>
                                        إظهار مجدداً
                                    </button>
                                    <button type="button" onclick="eessDeleteUserLog(<?php echo $log->id; ?>)" style="background: #991b1b; color: white; border: none; border-radius: 6px; padding: 5px 10px; font-size: 11px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 4px;">
                                        <span class="dashicons dashicons-trash" style="font-size: 14px; width: 14px; height: 14px;"></span>
                                        حذف السجل
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- SUPPORT & FEEDBACK RECORDS MANAGEMENT CARD -->
    <div id="eess-support-management-card" style="background: #ffffff; border-radius: 16px; padding: 25px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
        <h3 style="margin: 0 0 20px 0; font-size: 16px; font-weight: 800; color: #0f172a; border-bottom: 2px solid #f1f5f9; padding-bottom: 10px; display: flex; align-items: center; gap: 8px;">
            <span class="dashicons dashicons-sos" style="color: #2563eb;"></span>
            سجلات الدعم الفني والتقييمات (Support & Feedback Records)
        </h3>

        <!-- Search & Category Filters -->
        <form method="get" style="display: flex; gap: 15px; flex-wrap: wrap; margin-bottom: 20px; background: #f8fafc; padding: 15px; border-radius: 12px; border: 1px solid #e2e8f0;">
            <input type="hidden" name="sm_tab" value="global-settings">

            <div style="flex: 1; min-width: 200px;">
                <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; margin-bottom: 4px;">البحث الشامل</label>
                <input type="text" name="support_search" value="<?php echo esc_attr($s_search); ?>" placeholder="اسم المرسل، الرقم الوظيفي، العنوان..." class="sm-input" style="height: 38px; font-size: 12px; width: 100%;">
            </div>

            <div style="width: 180px;">
                <label style="display: block; font-size: 11px; font-weight: 700; color: #475569; margin-bottom: 4px;">تصنيف الرسالة</label>
                <select name="support_cat" class="sm-select" style="height: 38px; font-size: 12px; width: 100%;">
                    <option value="all" <?php selected($s_cat, 'all'); ?>>الكل (All)</option>
                    <option value="suggestion" <?php selected($s_cat, 'suggestion'); ?>>المقترحات (Suggestions)</option>
                    <option value="technical_issue" <?php selected($s_cat, 'technical_issue'); ?>>المشاكل الفنية (Technical Issues)</option>
                    <option value="rating" <?php selected($s_cat, 'rating'); ?>>التقييمات والشكر (Ratings)</option>
                </select>
            </div>

            <div style="display: flex; align-items: flex-end; gap: 8px;">
                <button type="submit" class="sm-btn" style="height: 38px; background: #2563eb; padding: 0 16px; font-size: 12px;">تصفية</button>
                <a href="<?php echo add_query_arg('sm_tab', 'global-settings'); ?>" class="sm-btn sm-btn-outline" style="height: 38px; padding: 0 16px; font-size: 12px; text-decoration: none; display: inline-flex; align-items: center;">إعادة ضبط</a>
            </div>
        </form>

        <!-- Table of Support Records -->
        <div style="overflow-x: auto;">
            <table class="sm-table" style="width: 100%; border-collapse: collapse; font-size: 12px;">
                <thead>
                    <tr style="background: #f8fafc; text-align: right; border-bottom: 2px solid #e2e8f0;">
                        <th style="padding: 12px;">المرسل</th>
                        <th style="padding: 12px;">التصنيف</th>
                        <th style="padding: 12px;">العنوان / التقييم</th>
                        <th style="padding: 12px;">التاريخ</th>
                        <th style="padding: 12px; text-align: center;">الحالة</th>
                        <th style="padding: 12px; text-align: center;">المرفق</th>
                        <th style="padding: 12px; text-align: center;">الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($support_requests)): ?>
                        <tr><td colspan="7" style="padding: 20px; text-align: center; color: #94a3b8;">لا توجد رسائل أو طلبات دعم متطابقة حالياً.</td></tr>
                    <?php else: ?>
                        <?php foreach ($support_requests as $s_req):
                            $sender_emp_id = get_user_meta($s_req->user_id, 'eess_employee_number', true) ?: ($s_req->user_login ?? '');
                            $cat_map = array(
                                'suggestion'      => array('label' => 'مقترح', 'bg' => '#fef3c7', 'color' => '#92400e'),
                                'technical_issue' => array('label' => 'مشكلة فنية', 'bg' => '#fee2e2', 'color' => '#991b1b'),
                                'rating'          => array('label' => 'تقييم وشكر', 'bg' => '#dbeafe', 'color' => '#1e40af'),
                            );
                            $cat_badge = $cat_map[$s_req->category] ?? array('label' => $s_req->category, 'bg' => '#f1f5f9', 'color' => '#475569');

                            $status_map = array(
                                'new'         => array('label' => 'جديد', 'bg' => '#fef9c3', 'color' => '#a16207'),
                                'in_progress' => array('label' => 'قيد المراجعة', 'bg' => '#e0f2fe', 'color' => '#0369a1'),
                                'resolved'    => array('label' => 'تم الحل', 'bg' => '#dcfce7', 'color' => '#15803d'),
                                'closed'      => array('label' => 'مغلق', 'bg' => '#f1f5f9', 'color' => '#475569'),
                            );
                            $st_badge = $status_map[$s_req->status] ?? array('label' => $s_req->status, 'bg' => '#f1f5f9', 'color' => '#475569');
                        ?>
                            <tr id="support-req-row-<?php echo $s_req->id; ?>" style="border-bottom: 1px solid #f1f5f9;">
                                <td style="padding: 12px; font-weight: 700; color: #0f172a;">
                                    <?php echo esc_html($s_req->display_name ?: $s_req->user_login); ?>
                                    <div style="font-size: 10px; color: #64748b; font-weight: normal; margin-top: 2px;">رقم الموظف: <?php echo esc_html($sender_emp_id); ?></div>
                                </td>
                                <td style="padding: 12px;">
                                    <span style="background: <?php echo $cat_badge['bg']; ?>; color: <?php echo $cat_badge['color']; ?>; padding: 3px 8px; border-radius: 50px; font-weight: 800; font-size: 10px;">
                                        <?php echo $cat_badge['label']; ?>
                                    </span>
                                </td>
                                <td style="padding: 12px; font-weight: 700;">
                                    <?php echo esc_html($s_req->title); ?>
                                    <?php if ($s_req->category === 'rating' && $s_req->rating_stars > 0): ?>
                                        <div style="color: #f59e0b; font-size: 12px; margin-top: 2px;">
                                            <?php echo str_repeat('★', $s_req->rating_stars) . str_repeat('☆', 5 - $s_req->rating_stars); ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 12px; color: #64748b; font-size: 11px;"><?php echo esc_html($s_req->created_at); ?></td>
                                <td style="padding: 12px; text-align: center;">
                                    <span style="background: <?php echo $st_badge['bg']; ?>; color: <?php echo $st_badge['color']; ?>; padding: 3px 8px; border-radius: 50px; font-weight: 800; font-size: 10px;">
                                        <?php echo $st_badge['label']; ?>
                                    </span>
                                </td>
                                <td style="padding: 12px; text-align: center;">
                                    <?php if (!empty($s_req->attachment_url)): ?>
                                        <a href="<?php echo esc_url($s_req->attachment_url); ?>" target="_blank" style="color: #2563eb; text-decoration: underline; font-weight: 700;">🖼️ عرض المرفق</a>
                                    <?php else: ?>
                                        <span style="color: #94a3b8;">لا يوجد</span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 12px; text-align: center; display: flex; gap: 6px; justify-content: center;">
                                    <button type="button" onclick="eessViewSupportRecord(<?php echo $s_req->id; ?>)" style="background: #334155; color: white; border: none; border-radius: 6px; padding: 5px 10px; font-size: 11px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 4px;">
                                        <span class="dashicons dashicons-visibility" style="font-size: 14px; width: 14px; height: 14px;"></span>
                                        عرض التفاصيل
                                    </button>
                                    <button type="button" onclick="eessDeleteSupportRecord(<?php echo $s_req->id; ?>)" style="background: #dc2626; color: white; border: none; border-radius: 6px; padding: 5px 10px; font-size: 11px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 4px;">
                                        <span class="dashicons dashicons-trash" style="font-size: 14px; width: 14px; height: 14px;"></span>
                                        حذف
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- VIEW SUPPORT RECORD MODAL -->
<div id="eess-view-support-modal" style="display: none; position: fixed; inset: 0; width: 100vw; height: 100vh; background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(5px); z-index: 999999; justify-content: center; align-items: center; padding: 20px; box-sizing: border-box; font-family: 'Cairo', sans-serif; direction: rtl;">
    <div style="background: #ffffff; border-radius: 16px; max-width: 600px; width: 100%; border: 1px solid #cbd5e1; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); overflow: hidden; display: flex; flex-direction: column; max-height: 90vh;">
        <div style="background: #1e293b; color: #ffffff; padding: 18px 20px; display: flex; justify-content: space-between; align-items: center;">
            <h3 style="margin: 0; font-size: 15px; font-weight: 800;">تفاصيل طلب الدعم / الرسالة</h3>
            <button type="button" onclick="document.getElementById('eess-view-support-modal').style.display='none'" style="background: none; border: none; color: #ffffff; font-size: 22px; cursor: pointer; line-height: 1;">&times;</button>
        </div>
        <div style="padding: 20px; overflow-y: auto; flex: 1; box-sizing: border-box;" id="support-modal-content-body">
            <!-- Rendered dynamically -->
        </div>
    </div>
</div>

<script>
const eessSupportRequestsJS = <?php
    $support_js_map = array();
    if (!empty($support_requests)) {
        foreach ($support_requests as $s_r) {
            $user_obj = get_userdata($s_r->user_id);
            $roles = $user_obj ? (array)$user_obj->roles : array();
            $role_label = !empty($roles) ? ($roles_list[reset($roles)] ?? reset($roles)) : 'غير محدد';
            $inst = get_user_meta($s_r->user_id, 'eess_school_name', true) ?: (get_user_meta($s_r->user_id, 'institution', true) ?: 'غير محدد');

            $support_js_map[$s_r->id] = array(
                'id' => $s_r->id,
                'sender_name' => $s_r->display_name ?: $s_r->user_login,
                'sender_role' => $role_label,
                'institution' => $inst,
                'category' => $s_r->category,
                'title' => $s_r->title,
                'details' => $s_r->details,
                'rating_stars' => $s_r->rating_stars,
                'status' => $s_r->status,
                'created_at' => $s_r->created_at,
                'attachment_url' => $s_r->attachment_url
            );
        }
    }
    echo json_encode($support_js_map);
?>;

function eessViewSupportRecord(id) {
    const data = eessSupportRequestsJS[id];
    if (!data) return;

    let attachmentHtml = '';
    if (data.attachment_url) {
        attachmentHtml = `
            <div style="margin-top: 15px; border-top: 1px solid #cbd5e1; padding-top: 12px;">
                <strong style="display: block; font-size: 12px; color: #334155; margin-bottom: 8px;">لقطة الشاشة / المرفق:</strong>
                <a href="${data.attachment_url}" target="_blank">
                    <img src="${data.attachment_url}" style="max-width: 100%; max-height: 250px; border-radius: 8px; border: 1px solid #cbd5e1; object-fit: contain;">
                </a>
            </div>
        `;
    }

    let starsHtml = '';
    if (data.category === 'rating' && data.rating_stars > 0) {
        starsHtml = `
            <div style="margin-bottom: 12px;">
                <strong>التقييم:</strong> <span style="color: #f59e0b; font-size: 16px;">${'★'.repeat(data.rating_stars)}${'☆'.repeat(5 - data.rating_stars)}</span> (${data.rating_stars} من 5)
            </div>
        `;
    }

    const html = `
        <div style="background: #f8fafc; padding: 15px; border-radius: 10px; border: 1px solid #e2e8f0; margin-bottom: 15px; font-size: 12px; line-height: 1.8;">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <div><strong>المرسل:</strong> ${data.sender_name}</div>
                <div><strong>الرتبة الوظيفية:</strong> ${data.sender_role}</div>
                <div><strong>المؤسسة/المدرسة:</strong> ${data.institution}</div>
                <div><strong>التاريخ:</strong> ${data.created_at}</div>
            </div>
        </div>

        ${starsHtml}

        <div style="margin-bottom: 15px;">
            <strong style="display: block; font-size: 13px; color: #0f172a; margin-bottom: 6px;">عنوان الرسالة:</strong>
            <div style="font-size: 14px; font-weight: 800; color: #1e293b;">${data.title}</div>
        </div>

        <div style="margin-bottom: 15px;">
            <strong style="display: block; font-size: 13px; color: #0f172a; margin-bottom: 6px;">التفاصيل الكاملة:</strong>
            <div style="background: #ffffff; border: 1px solid #cbd5e1; border-radius: 8px; padding: 12px; font-size: 13px; line-height: 1.7; color: #334155; white-space: pre-line;">${data.details}</div>
        </div>

        ${attachmentHtml}

        <div style="margin-top: 20px; border-top: 2px dashed #cbd5e1; padding-top: 15px;">
            <label style="display: block; font-size: 12px; font-weight: 800; color: #0f172a; margin-bottom: 6px;">تحديث حالة الطلب:</label>
            <div style="display: flex; gap: 10px; align-items: center;">
                <select id="update_support_status_select" class="sm-select" style="height: 38px; font-size: 12px; flex: 1;">
                    <option value="new" ${data.status === 'new' ? 'selected' : ''}>جديد (New)</option>
                    <option value="in_progress" ${data.status === 'in_progress' ? 'selected' : ''}>قيد المراجعة (In Progress)</option>
                    <option value="resolved" ${data.status === 'resolved' ? 'selected' : ''}>تم الحل (Resolved)</option>
                    <option value="closed" ${data.status === 'closed' ? 'selected' : ''}>مغلق (Closed)</option>
                </select>
                <button type="button" onclick="eessSaveSupportStatus(${data.id})" class="sm-btn" style="background: #16a34a; height: 38px; font-size: 12px; padding: 0 16px;">تطبيق الحالة</button>
            </div>
        </div>
    `;

    document.getElementById('support-modal-content-body').innerHTML = html;
    document.getElementById('eess-view-support-modal').style.display = 'flex';
}

function eessSaveSupportStatus(id) {
    const status = document.getElementById('update_support_status_select').value;
    const formData = new FormData();
    formData.append('action', 'eess_update_support_status');
    formData.append('id', id);
    formData.append('status', status);

    fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            alert(res.data.message);
            location.reload();
        } else {
            alert(res.data);
        }
    });
}

function eessDeleteSupportRecord(id) {
    if (!confirm('هل أنت تأكد من رغبتك في حذف سجل الدعم/التقييم هذا نهائياً مع المرفق الخاص به؟')) return;

    const formData = new FormData();
    formData.append('action', 'eess_delete_support_request');
    formData.append('id', id);

    fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            alert(res.data.message);
            const row = document.getElementById('support-req-row-' + id);
            if (row) row.remove();
        } else {
            alert(res.data);
        }
    });
}
</script>

    <!-- Shared Educational Input Library Card for System Admins -->
    <div style="background: #ffffff; border-radius: 16px; padding: 25px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); margin-top: 30px;">
        <h3 style="margin: 0 0 15px 0; font-size: 16px; font-weight: 800; color: #0f172a; border-bottom: 2px solid #f1f5f9; padding-bottom: 10px; display: flex; align-items: center; justify-content: space-between;">
            <div style="display: flex; align-items: center; gap: 8px;">
                <span class="dashicons dashicons-book" style="color: #881337;"></span>
                <span>مكتبة المدوّنات والمدخلات التعليمية المشتركة (تخطيط الدروس والفصول)</span>
            </div>
            <span style="font-size: 12px; color: #64748b; font-weight: 600;">(إدارة الاقتراحات والمصطلحات التلقائية للمدرسين)</span>
        </h3>

        <div style="overflow-x: auto;">
            <table class="sm-table" style="width: 100%; border-collapse: collapse; text-align: right;">
                <thead>
                    <tr style="background: #212121; color: #ffffff;">
                        <th style="padding: 10px 14px; font-size: 12px; font-weight: 800;">المادة الدراسية</th>
                        <th style="padding: 10px 14px; font-size: 12px; font-weight: 800;">نوع المدخل</th>
                        <th style="padding: 10px 14px; font-size: 12px; font-weight: 800;">محتوى النص المقترح</th>
                        <th style="padding: 10px 14px; font-size: 12px; font-weight: 800; text-align: center;">مرات الاستخدام</th>
                        <th style="padding: 10px 14px; font-size: 12px; font-weight: 800; text-align: center;">الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($edu_inputs)): ?>
                        <tr><td colspan="5" style="padding: 30px; text-align: center; color: #94a3b8; font-weight: 700;">لا توجد مدخلات تعليمية مسجلة في المكتبة حالياً.</td></tr>
                    <?php else: ?>
                        <?php foreach ($edu_inputs as $inp): ?>
                            <tr style="border-bottom: 1px solid #f1f5f9;">
                                <td style="padding: 10px 14px; font-weight: 800; font-size: 12.5px; color: #0f172a;"><?php echo esc_html($inp->subject); ?></td>
                                <td style="padding: 10px 14px; font-size: 12px; color: #334155;">
                                    <span style="padding: 2px 8px; border-radius: 6px; background: #f1f5f9; font-weight: 700; font-size: 11px;">
                                        <?php echo esc_html($inp->input_type === 'title' ? 'عنوان درس' : ($inp->input_type === 'objective' ? 'هدف تعليمي' : 'نشاط/محتوى')); ?>
                                    </span>
                                </td>
                                <td style="padding: 10px 14px; font-size: 12.5px; color: #1e293b; font-weight: 600;"><?php echo esc_html($inp->content); ?></td>
                                <td style="padding: 10px 14px; text-align: center;">
                                    <span style="padding: 2px 8px; border-radius: 9999px; background: #eff6ff; color: #2563eb; font-weight: 800; font-size: 11.5px;">
                                        <?php echo intval($inp->usage_count); ?>
                                    </span>
                                </td>
                                <td style="padding: 10px 14px; text-align: center;">
                                    <button type="button" onclick="eessDeleteEducationalInput(<?php echo $inp->id; ?>)" title="حذف المدخل" style="width: 32px; height: 32px; border-radius: 50% !important; background: #fee2e2; color: #dc2626; border: 1px solid #fecdd3; display: inline-flex; align-items: center; justify-content: center; cursor: pointer;">
                                        <span class="dashicons dashicons-trash" style="font-size: 15px; width: 15px; height: 15px; margin: 0;"></span>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

<script>
function eessDeleteEducationalInput(id) {
    if (!confirm('هل أنت متأكد من حذف هذا المدخل التعليمي من اقتراحات المكتبة؟')) return;
    jQuery.post('<?php echo admin_url('admin-ajax.php'); ?>', {
        action: 'sm_save_educational_input',
        delete_id: id,
        subject: 'حذف',
        content: 'حذف'
    }, function() {
        location.reload();
    });
}

function eessCreateAnnouncement(e) {
    e.preventDefault();
    const btn = document.getElementById('btn-save-announcement');
    const statusBox = document.getElementById('create-msg-status');

    btn.disabled = true;
    btn.innerHTML = '<span class="dashicons dashicons-update spin"></span> جاري النشر...';
    statusBox.style.display = 'none';

    const formData = jQuery('#eess-announcement-form').serialize() + '&action=sm_create_system_announcement';

    jQuery.post('<?php echo admin_url('admin-ajax.php'); ?>', formData, function(res) {
        btn.disabled = false;
        btn.innerHTML = '<span class="dashicons dashicons-send"></span> نشر واعتماد الإشعار';

        statusBox.style.display = 'block';
        if (res.success) {
            statusBox.style.background = '#f0fdf4';
            statusBox.style.color = '#166534';
            statusBox.innerText = res.data.message || 'تم نشر الإشعار بنجاح!';
            setTimeout(() => location.reload(), 1200);
        } else {
            statusBox.style.background = '#fef2f2';
            statusBox.style.color = '#991b1b';
            statusBox.innerText = res.data || 'حدث خطأ أثناء حفظ الإشعار.';
        }
    });
}

function eessResetUserAnnouncement(ancId, userId, logId) {
    if (!confirm('هل أنت تأكد من إعادة تفعيل ظهور هذا الإشعار لهذا المستخدم بعينه؟')) return;

    jQuery.post('<?php echo admin_url('admin-ajax.php'); ?>', {
        action: 'sm_reset_user_announcement',
        announcement_id: ancId,
        user_id: userId,
        nonce: '<?php echo wp_create_nonce('sm_announcement_action'); ?>'
    }, function(res) {
        if (res.success) {
            alert('تم إعادة ضبط الإشعار بنجاح وسيظهر للمستخدم عند تسجيل الدخول القادم.');
            location.reload();
        } else {
            alert(res.data || 'حدث خطأ أثناء تنفيذ الإجراء.');
        }
    });
}

function eessDisableAnnouncement(ancId) {
    if (!confirm('هل أنت تأكد من تعطيل هذا الإشعار؟ سيتم إيقاف ظهوره فوراً لجميع المستخدمين المستهدفين.')) return;

    jQuery.post('<?php echo admin_url('admin-ajax.php'); ?>', {
        action: 'sm_disable_system_announcement',
        announcement_id: ancId,
        nonce: '<?php echo wp_create_nonce('sm_announcement_action'); ?>'
    }, function(res) {
        if (res.success) {
            alert(res.data.message || 'تم تعطيل الإشعار بنجاح.');
            location.reload();
        } else {
            alert(res.data || 'حدث خطأ أثناء تعطيل الإشعار.');
        }
    });
}

function eessDeleteAnnouncement(ancId) {
    if (!confirm('تنبيه هام: هل أنت تأكد من حذف هذا الإشعار نهائياً؟ سيتم حذف جميع إحصائيات القراءة والتفاعل المرتبطة به.')) return;

    jQuery.post('<?php echo admin_url('admin-ajax.php'); ?>', {
        action: 'sm_delete_system_announcement',
        announcement_id: ancId,
        nonce: '<?php echo wp_create_nonce('sm_announcement_action'); ?>'
    }, function(res) {
        if (res.success) {
            alert(res.data.message || 'تم حذف الإشعار نهائياً.');
            location.reload();
        } else {
            alert(res.data || 'حدث خطأ أثناء حذف الإشعار.');
        }
    });
}

function eessDeleteUserLog(logId) {
    if (!confirm('هل أنت تأكد من حذف سجل تفاعل هذا المستخدم بعينه؟')) return;

    jQuery.post('<?php echo admin_url('admin-ajax.php'); ?>', {
        action: 'sm_delete_user_announcement_log',
        log_id: logId,
        nonce: '<?php echo wp_create_nonce('sm_announcement_action'); ?>'
    }, function(res) {
        if (res.success) {
            alert(res.data.message || 'تم حذف سجل المستخدم بنجاح.');
            location.reload();
        } else {
            alert(res.data || 'حدث خطأ أثناء حذف السجل.');
        }
    });
}
</script>
