<?php
if (!defined('ABSPATH')) exit;

global $wpdb;
$user_id = get_current_user_id();
$user = wp_get_current_user();
$roles = (array) $user->roles;

$is_admin = in_array('administrator', $roles) || current_user_can('manage_options');
$is_sys_admin = in_array('sm_system_admin', $roles);
$is_principal = in_array('sm_principal', $roles);
$is_supervisor = in_array('sm_supervisor', $roles);
$is_coordinator = in_array('sm_coordinator', $roles);
$is_activities_sup = in_array('sm_activities_supervisor', $roles);
$is_teacher = in_array('sm_teacher', $roles);

$can_review = $is_admin || $is_sys_admin || $is_principal || $is_supervisor || $is_coordinator || $is_activities_sup;

// Retrieve teacher / staff profile info for institutional auto-fill
$emp_school = get_user_meta($user_id, 'eess_school_name', true) ?: 'المدرسة الرئيسية';
$emp_number = get_user_meta($user_id, 'eess_employee_number', true) ?: ('EMP-' . $user_id);
$emp_dept   = get_user_meta($user_id, 'department', true) ?: 'التربية البدنية والصحية';
$active_acad_year = '2027/2026';

// Seed catalog if empty
$catalog_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sm_asset_catalog");
if ($catalog_count == 0) {
    $default_items = array(
        // Sports Equipment
        array('كرة قدم معتمدة', 'معدات رياضية', 'كرة'),
        array('كرة سلة معتمدة', 'معدات رياضية', 'كرة'),
        array('كرة طائرة', 'معدات رياضية', 'كرة'),
        array('كرة يد', 'معدات رياضية', 'كرة'),
        array('كرة فوتسال صالة', 'معدات رياضية', 'كرة'),
        array('كرة مضرب تنس أرضي', 'معدات رياضية', 'علبة'),
        array('كرة طاولة تنس صالة', 'معدات رياضية', 'علبة'),
        array('مضرب تنس أرضي', 'معدات رياضية', 'مضرب'),
        array('مضرب ريشة طائرة', 'معدات رياضية', 'مضرب'),
        array('كرات ريشة طائرة', 'معدات رياضية', 'علبة'),
        array('مضرب تنس طاولة', 'معدات رياضية', 'مضرب'),
        array('مخاريط تدريب رياضية', 'معدات رياضية', 'طقم'),
        array('حواجز قفز رياضية', 'معدات رياضية', 'طقم'),
        array('سلم لياقة وتوافق حركي', 'معدات رياضية', 'عدد'),
        array('حلقات تدريب بدني', 'معدات رياضية', 'طقم'),
        array('حبال قفز للياقة', 'معدات رياضية', 'حبل'),
        array('أحبال مقاومة مطاطية', 'معدات رياضية', 'طقم'),
        array('كرة طبية أوزان', 'معدات رياضية', 'كرة'),
        array('فرشات تمارين لياقة', 'معدات رياضية', 'فرشة'),

        // PE Equipment
        array('فرشة جمباز حماية', 'معدات التربية البدنية', 'فرشة'),
        array('مقعد جمباز خشب', 'معدات التربية البدنية', 'مقعد'),
        array('عقلة وميزان جمباز', 'معدات التربية البدنية', 'جهاز'),
        array('صندوق قفز سويدي', 'معدات التربية البدنية', 'صندوق'),
        array('ساعة إيقاف زمنية (Stopwatch)', 'معدات التربية البدنية', 'ساعة'),
        array('صفارات تحكيم رياضية', 'معدات التربية البدنية', 'عدد'),
        array('شريط قياس مسافات', 'معدات التربية البدنية', 'شريط'),
        array('ميزان قياس وزن بدني', 'معدات التربية البدنية', 'جهاز'),
        array('جهاز قياس طول القامة', 'معدات التربية البدنية', 'جهاز'),

        // Gym & Fitness
        array('أثقال دمبلز أوزان', 'معدات اللياقة والجيم', 'طقم'),
        array('بار حديد أوزان (Barbell)', 'معدات اللياقة والجيم', 'بار'),
        array('أقراص أوزان حديدية', 'معدات اللياقة والجيم', 'قرص'),
        array('كتل كيتل بيل (Kettlebell)', 'معدات اللياقة والجيم', 'عدد'),
        array('دراجة تمارين ثابتة', 'معدات اللياقة والجيم', 'دراجة'),
        array('جهاز سير مشي كهربائي', 'معدات اللياقة والجيم', 'جهاز'),
        array('بنش تمارين متعدد المستويات', 'معدات اللياقة والجيم', 'بنش'),
        array('حامل تخزين الأوزان والمعدات', 'معدات اللياقة والجيم', 'حامل'),

        // Clothing & Supplies
        array('قمصان تدريب تمييز (Bibs)', 'مستلزمات وأزياء رياضية', 'طقم'),
        array('حقيبة إسعافات أولية رياضية', 'مستلزمات وأزياء رياضية', 'حقيبة'),
        array('لوحة نتائج رياضية محمولة', 'مستلزمات وأزياء رياضية', 'لوحة'),
        array('مرمى كرة قدم محمول', 'مستلزمات وأزياء رياضية', 'مرمى')
    );

    foreach ($default_items as $item) {
        $wpdb->insert("{$wpdb->prefix}sm_asset_catalog", array(
            'item_name' => $item[0],
            'category'  => $item[1],
            'unit'      => $item[2],
            'is_active' => 1,
            'created_at'=> current_time('mysql')
        ));
    }
}

// Fetch institutional inventories
$inventories_raw = $wpdb->get_results("SELECT i.*, u.display_name as responsible_name FROM {$wpdb->prefix}sm_asset_inventories i LEFT JOIN {$wpdb->users} u ON i.responsible_user_id = u.ID ORDER BY i.updated_at DESC");

// Fetch asset requests
$requests_raw = $wpdb->get_results("SELECT r.*, u.display_name as requester_name FROM {$wpdb->prefix}sm_asset_requests r LEFT JOIN {$wpdb->users} u ON r.requester_user_id = u.ID ORDER BY r.created_at DESC");

// Fetch catalog items for quick dropdowns
$catalog_items = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}sm_asset_catalog WHERE is_active = 1 ORDER BY category ASC, item_name ASC");

// Calculate Stats
$total_registered_qty = $wpdb->get_var("SELECT SUM(qty_total) FROM {$wpdb->prefix}sm_asset_inventory_items") ?: 0;
$total_usable_qty     = $wpdb->get_var("SELECT SUM(qty_usable) FROM {$wpdb->prefix}sm_asset_inventory_items") ?: 0;
$total_damaged_qty    = $wpdb->get_var("SELECT SUM(qty_damaged) FROM {$wpdb->prefix}sm_asset_inventory_items") ?: 0;
$total_missing_qty    = $wpdb->get_var("SELECT SUM(qty_missing) FROM {$wpdb->prefix}sm_asset_inventory_items") ?: 0;
$total_requests_count = count($requests_raw);
$pending_requests_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}sm_asset_requests WHERE status = 'submitted'") ?: 0;
?>

<div class="sm-main-container" style="direction: rtl; font-family: 'Cairo', sans-serif; padding: 20px; background: #f8fafc;">

    <!-- TOP BANNER -->
    <div style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); color: #ffffff; border-radius: 20px; padding: 24px; margin-bottom: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
        <div style="display: flex; align-items: center; gap: 14px;">
            <div style="width: 52px; height: 52px; background: rgba(255,255,255,0.1); border-radius: 14px; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
                <span class="dashicons dashicons-store" style="font-size: 28px; width: 28px; height: 28px; color: #38bdf8;"></span>
            </div>
            <div>
                <h2 style="margin: 0 0 4px 0; font-size: 20px; font-weight: 900; color: #ffffff;">نظام إدارة العهد والمعدات والأصول المؤسسية</h2>
                <p style="margin: 0; font-size: 12.5px; color: #94a3b8; font-weight: 600;">حصر، متابعة، طلب، واعتماد العهد الرياضية والأدوات والأجهزة المدرسية - <?php echo esc_html($emp_school); ?></p>
            </div>
        </div>

        <div style="display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
            <button type="button" onclick="document.getElementById('asset-inventory-modal').style.display='flex'" class="sm-btn" style="background: #881337; color: #ffffff !important; height: 38px; border-radius: 9999px !important; padding: 0 20px; font-weight: 800; font-size: 12.5px; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 4px 12px rgba(136,19,55,0.3);">
                <span class="dashicons dashicons-plus-alt2" style="font-size: 16px; width: 16px; height: 16px;"></span>
                <span>تحديث / تقديم جرد عهدة جديدة</span>
            </button>

            <button type="button" onclick="document.getElementById('asset-request-modal').style.display='flex'" class="sm-btn" style="background: #0284c7; color: #ffffff !important; height: 38px; border-radius: 9999px !important; padding: 0 20px; font-weight: 800; font-size: 12.5px; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 4px 12px rgba(2,132,199,0.3);">
                <span class="dashicons dashicons-cart" style="font-size: 16px; width: 16px; height: 16px;"></span>
                <span>طلب معدات وعهد جديدة</span>
            </button>
        </div>
    </div>

    <!-- STATS DASHBOARD -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 14px; margin-bottom: 20px;">
        <div style="background: #ffffff; padding: 16px; border-radius: 16px; border: 1px solid #e2e8f0; border-top: 4px solid #0f172a; text-align: center;">
            <div style="font-size: 11.5px; color: #64748b; font-weight: 700; margin-bottom: 4px;">إجمالي العهد المسجلة</div>
            <div style="font-size: 22px; font-weight: 900; color: #0f172a;"><?php echo number_format($total_registered_qty); ?> <span style="font-size: 11px; font-weight: 700; color: #64748b;">قطعة</span></div>
        </div>

        <div style="background: #ffffff; padding: 16px; border-radius: 16px; border: 1px solid #e2e8f0; border-top: 4px solid #16a34a; text-align: center;">
            <div style="font-size: 11.5px; color: #166534; font-weight: 700; margin-bottom: 4px;">صالحة للاستخدام</div>
            <div style="font-size: 22px; font-weight: 900; color: #16a34a;"><?php echo number_format($total_usable_qty); ?> <span style="font-size: 11px; font-weight: 700; color: #166534;">قطعة</span></div>
        </div>

        <div style="background: #ffffff; padding: 16px; border-radius: 16px; border: 1px solid #e2e8f0; border-top: 4px solid #d97706; text-align: center;">
            <div style="font-size: 11.5px; color: #b45309; font-weight: 700; margin-bottom: 4px;">معدات تالفة</div>
            <div style="font-size: 22px; font-weight: 900; color: #d97706;"><?php echo number_format($total_damaged_qty); ?> <span style="font-size: 11px; font-weight: 700; color: #b45309;">قطعة</span></div>
        </div>

        <div style="background: #ffffff; padding: 16px; border-radius: 16px; border: 1px solid #e2e8f0; border-top: 4px solid #dc2626; text-align: center;">
            <div style="font-size: 11.5px; color: #991b1b; font-weight: 700; margin-bottom: 4px;">مفقودة / مفقودات</div>
            <div style="font-size: 22px; font-weight: 900; color: #dc2626;"><?php echo number_format($total_missing_qty); ?> <span style="font-size: 11px; font-weight: 700; color: #991b1b;">قطعة</span></div>
        </div>

        <div style="background: #ffffff; padding: 16px; border-radius: 16px; border: 1px solid #e2e8f0; border-top: 4px solid #0284c7; text-align: center;">
            <div style="font-size: 11.5px; color: #0369a1; font-weight: 700; margin-bottom: 4px;">طلبات التوريد بانتظار الاعتماد</div>
            <div style="font-size: 22px; font-weight: 900; color: #0284c7;"><?php echo number_format($pending_requests_count); ?> <span style="font-size: 11px; font-weight: 700; color: #0369a1;">طلب</span></div>
        </div>
    </div>

    <!-- MAIN INVENTORY TABLE SECTION -->
    <div style="background: #ffffff; border-radius: 20px; border: 1px solid #e2e8f0; padding: 22px; box-shadow: 0 4px 16px rgba(0,0,0,0.02); margin-bottom: 24px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 10px;">
            <h3 style="margin: 0; font-size: 16px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 8px;">
                <span class="dashicons dashicons-portfolio" style="color: #881337;"></span>
                <span>سجل العهد والمعدات المؤسسية المعتمدة - <?php echo esc_html($emp_school); ?></span>
            </h3>

            <div style="position: relative; width: 240px;">
                <input type="text" id="asset-search-input" onkeyup="eessFilterAssetsTable()" placeholder="بحث باسم المعدة، الفئة، أو الحالة..." class="sm-input" style="height: 36px; border-radius: 9999px !important; border: 1px solid #cbd5e1; font-size: 12px; padding: 0 14px 0 34px; width: 100%;">
                <span class="dashicons dashicons-search" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 16px;"></span>
            </div>
        </div>

        <div style="overflow-x: auto;">
            <table id="assets-main-table" style="width: 100%; border-collapse: separate; border-spacing: 0; text-align: right; font-size: 12px;">
                <thead>
                    <tr style="background: #0f172a; color: #ffffff;">
                        <th style="padding: 10px 12px; text-align: center; border-radius: 0 10px 0 0;">#</th>
                        <th style="padding: 10px 14px;">اسم المعدة / الأصول</th>
                        <th style="padding: 10px 14px;">الفئة التصنيفية</th>
                        <th style="padding: 10px 14px; text-align: center;">إجمالي الكمية</th>
                        <th style="padding: 10px 14px; text-align: center;">صالحة</th>
                        <th style="padding: 10px 14px; text-align: center;">تالفة</th>
                        <th style="padding: 10px 14px; text-align: center;">مفقودة</th>
                        <th style="padding: 10px 14px;">الموقع بالتفصيل</th>
                        <th style="padding: 10px 14px; text-align: center;">حالة العهدة</th>
                        <th style="padding: 10px 14px; text-align: center; border-radius: 10px 0 0 0;">الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $all_items = $wpdb->get_results("SELECT i.*, inv.school_name, inv.status as inv_status FROM {$wpdb->prefix}sm_asset_inventory_items i JOIN {$wpdb->prefix}sm_asset_inventories inv ON i.inventory_id = inv.id ORDER BY i.id DESC");

                    if (empty($all_items)): ?>
                        <tr>
                            <td colspan="10" style="padding: 40px; text-align: center; color: #94a3b8; font-weight: 700;">لا توجد عهد أو معدات مسجلة حالياً في حصر العهدة المؤسسية.</td>
                        </tr>
                    <?php else:
                        foreach ($all_items as $idx => $item):
                    ?>
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 10px 12px; text-align: center; font-weight: 800; color: #64748b;"><?php echo ($idx + 1); ?></td>
                            <td style="padding: 10px 14px; font-weight: 800; color: #0f172a;"><?php echo esc_html($item->item_name); ?></td>
                            <td style="padding: 10px 14px;"><span style="background: #f1f5f9; color: #475569; padding: 2px 8px; border-radius: 6px; font-weight: 700; font-size: 11px; border: 1px solid #cbd5e1;"><?php echo esc_html($item->category); ?></span></td>
                            <td style="padding: 10px 14px; text-align: center; font-weight: 900; color: #0f172a;"><?php echo $item->qty_total; ?></td>
                            <td style="padding: 10px 14px; text-align: center;"><span style="color: #15803d; font-weight: 800; background: #dcfce7; padding: 2px 8px; border-radius: 9999px;"><?php echo $item->qty_usable; ?></span></td>
                            <td style="padding: 10px 14px; text-align: center;"><span style="color: #b45309; font-weight: 800; background: #fef3c7; padding: 2px 8px; border-radius: 9999px;"><?php echo $item->qty_damaged; ?></span></td>
                            <td style="padding: 10px 14px; text-align: center;"><span style="color: #b91c1c; font-weight: 800; background: #fee2e2; padding: 2px 8px; border-radius: 9999px;"><?php echo $item->qty_missing; ?></span></td>
                            <td style="padding: 10px 14px; color: #475569; font-weight: 600;"><?php echo esc_html($item->location); ?></td>
                            <td style="padding: 10px 14px; text-align: center;">
                                <span style="background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; padding: 2px 8px; border-radius: 9999px; font-weight: 800; font-size: 10.5px;">✓ ممتازة / متاحة</span>
                            </td>
                            <td style="padding: 10px 14px; text-align: center;">
                                <div class="sm-action-btn-group">
                                    <a href="<?php echo admin_url('admin-ajax.php?action=sm_print&print_type=asset_inventory&id=' . $item->inventory_id); ?>" target="_blank" class="sm-action-btn sm-action-btn-neutral" title="طباعة تقرير العهدة الرسمي A4">
                                        <span class="dashicons dashicons-printer"></span>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- CREATE INVENTORY MODAL -->
<div id="asset-inventory-modal" class="sm-modal-overlay" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.75); backdrop-filter: blur(5px); z-index: 999999; justify-content: center; align-items: center; padding: 20px; box-sizing: border-box; font-family: 'Cairo', sans-serif;" dir="rtl">
    <div style="background: #ffffff; border-radius: 20px; max-width: 780px; width: 100%; border: 1px solid #cbd5e1; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.3); overflow: hidden; max-height: 90vh; display: flex; flex-direction: column;">
        <div style="background: #0f172a; color: #ffffff; padding: 18px 24px; display: flex; justify-content: space-between; align-items: center;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <span class="dashicons dashicons-store" style="font-size: 22px; color: #38bdf8;"></span>
                <h3 style="margin: 0; font-size: 16px; font-weight: 800; color: #ffffff;">تقديم حصر العهدة والمعدات المؤسسية</h3>
            </div>
            <button type="button" onclick="document.getElementById('asset-inventory-modal').style.display='none'" style="background: none; border: none; color: #ffffff; font-size: 24px; cursor: pointer;">&times;</button>
        </div>

        <form onsubmit="eessSaveAssetInventorySubmit(event)" style="padding: 24px; overflow-y: auto; flex: 1;">
            <?php wp_nonce_field('eess_admin_action', 'sm_nonce'); ?>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; background: #f8fafc; padding: 14px; border-radius: 12px; border: 1px solid #e2e8f0; margin-bottom: 18px; font-size: 12px;">
                <div><strong>المدرسة/المؤسسة:</strong> <?php echo esc_html($emp_school); ?></div>
                <div><strong>القسم الفني:</strong> <?php echo esc_html($emp_dept); ?></div>
                <div><strong>المسؤول عن الحصر:</strong> <?php echo esc_html($user->display_name); ?></div>
                <div><strong>الرقم الوظيفي:</strong> <?php echo esc_html($emp_number); ?></div>
            </div>

            <div style="margin-bottom: 16px;">
                <label style="font-size: 12.5px; font-weight: 800; color: #334155; margin-bottom: 6px; display: block;">اختر المعدة من الكتالوج المعتمد <span style="color:#ef4444;">*</span></label>
                <select id="inv_catalog_id" class="sm-input" style="height: 40px; width: 100%; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 10px; font-size: 12.5px; font-weight: 700;" required>
                    <?php foreach ($catalog_items as $c): ?>
                        <option value="<?php echo $c->id; ?>"><?php echo esc_html($c->item_name . ' (' . $c->category . ')'); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-bottom: 16px;">
                <div>
                    <label style="font-size: 11.5px; font-weight: 700; color: #334155; display: block; margin-bottom: 4px;">إجمالي الكمية *</label>
                    <input type="number" min="1" id="inv_qty_total" value="10" class="sm-input" style="height: 38px; width: 100%; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 10px; font-size: 12px;" required>
                </div>
                <div>
                    <label style="font-size: 11.5px; font-weight: 700; color: #334155; display: block; margin-bottom: 4px;">كمية صالحة *</label>
                    <input type="number" min="0" id="inv_qty_usable" value="8" class="sm-input" style="height: 38px; width: 100%; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 10px; font-size: 12px;" required>
                </div>
                <div>
                    <label style="font-size: 11.5px; font-weight: 700; color: #334155; display: block; margin-bottom: 4px;">كمية تالفة</label>
                    <input type="number" min="0" id="inv_qty_damaged" value="1" class="sm-input" style="height: 38px; width: 100%; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 10px; font-size: 12px;">
                </div>
            </div>

            <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; margin-bottom: 16px;">
                <div>
                    <label style="font-size: 11.5px; font-weight: 700; color: #334155; display: block; margin-bottom: 4px;">كمية مفقودة</label>
                    <input type="number" min="0" id="inv_qty_missing" value="1" class="sm-input" style="height: 38px; width: 100%; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 10px; font-size: 12px;">
                </div>
                <div>
                    <label style="font-size: 11.5px; font-weight: 700; color: #334155; display: block; margin-bottom: 4px;">موقع التواجد بالتفصيل</label>
                    <input type="text" id="inv_location" value="مخزن الصالة الرياضية" class="sm-input" style="height: 38px; width: 100%; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 10px; font-size: 12px;">
                </div>
            </div>

            <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 20px;">
                <button type="submit" id="inv_submit_btn" class="sm-btn" style="background: #881337; color: #ffffff !important; height: 38px; padding: 0 22px; font-weight: 800; border-radius: 9999px !important; border: none; cursor: pointer;">حفظ وتأكيد حصر العهدة</button>
                <button type="button" onclick="document.getElementById('asset-inventory-modal').style.display='none'" class="sm-btn sm-btn-outline" style="height: 38px; padding: 0 18px; border-radius: 9999px !important; border: 1px solid #cbd5e1; color: #475569; cursor: pointer;">إلغاء</button>
            </div>
        </form>
    </div>
</div>

<!-- CREATE ASSET REQUEST MODAL -->
<div id="asset-request-modal" class="sm-modal-overlay" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.75); backdrop-filter: blur(5px); z-index: 999999; justify-content: center; align-items: center; padding: 20px; box-sizing: border-box; font-family: 'Cairo', sans-serif;" dir="rtl">
    <div style="background: #ffffff; border-radius: 20px; max-width: 680px; width: 100%; border: 1px solid #cbd5e1; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.3); overflow: hidden;">
        <div style="background: #0284c7; color: #ffffff; padding: 18px 24px; display: flex; justify-content: space-between; align-items: center;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <span class="dashicons dashicons-cart" style="font-size: 22px; color: #ffffff;"></span>
                <h3 style="margin: 0; font-size: 16px; font-weight: 800; color: #ffffff;">تقديم طلب معدات وأصول جديدة</h3>
            </div>
            <button type="button" onclick="document.getElementById('asset-request-modal').style.display='none'" style="background: none; border: none; color: #ffffff; font-size: 24px; cursor: pointer;">&times;</button>
        </div>

        <form onsubmit="eessSaveAssetRequestSubmit(event)" style="padding: 24px;">
            <div style="margin-bottom: 14px;">
                <label style="font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 4px; display: block;">المعدة المطلوبة من الكتالوج *</label>
                <select id="req_catalog_id" class="sm-input" style="height: 38px; width: 100%; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 10px; font-size: 12px;" required>
                    <?php foreach ($catalog_items as $c): ?>
                        <option value="<?php echo $c->id; ?>"><?php echo esc_html($c->item_name); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                <div>
                    <label style="font-size: 11.5px; font-weight: 700; color: #334155; display: block; margin-bottom: 4px;">الكمية المطلوبة *</label>
                    <input type="number" min="1" id="req_qty_requested" value="5" class="sm-input" style="height: 38px; width: 100%; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 10px; font-size: 12px;" required>
                </div>
                <div>
                    <label style="font-size: 11.5px; font-weight: 700; color: #334155; display: block; margin-bottom: 4px;">سبب الطلب *</label>
                    <select id="req_reason" class="sm-input" style="height: 38px; width: 100%; border-radius: 8px; border: 1px solid #cbd5e1; padding: 0 10px; font-size: 12px;">
                        <option value="استبدال معدات تالفة">استبدال معدات تالفة</option>
                        <option value="تغطية زيادة أعداد الطلاب">زيادة أعداد الطلاب والمجموعات</option>
                        <option value="نشاط رياضي جديد">استحداث نشاط أو بطولة جديدة</option>
                        <option value="تجهيز صالة بدنية جديدة">تجهيز صالة أو مرفق بدني جديد</option>
                    </select>
                </div>
            </div>

            <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 20px;">
                <button type="submit" id="req_submit_btn" class="sm-btn" style="background: #0284c7; color: #ffffff !important; height: 38px; padding: 0 22px; font-weight: 800; border-radius: 9999px !important; border: none; cursor: pointer;">إرسال طلب التوريد للمراجعة</button>
                <button type="button" onclick="document.getElementById('asset-request-modal').style.display='none'" class="sm-btn sm-btn-outline" style="height: 38px; padding: 0 18px; border-radius: 9999px !important; border: 1px solid #cbd5e1; color: #475569; cursor: pointer;">إلغاء</button>
            </div>
        </form>
    </div>
</div>

<script>
function eessFilterAssetsTable() {
    var q = document.getElementById('asset-search-input').value.trim().toLowerCase();
    var rows = document.querySelectorAll('#assets-main-table tbody tr');
    rows.forEach(function(row) {
        if (row.cells.length < 2) return;
        var text = row.textContent.toLowerCase();
        row.style.display = text.includes(q) ? '' : 'none';
    });
}

function eessSaveAssetInventorySubmit(e) {
    e.preventDefault();
    var btn = document.getElementById('inv_submit_btn');
    btn.disabled = true;
    btn.innerText = 'جاري الحفظ...';

    var formData = new FormData();
    formData.append('action', 'sm_save_asset_inventory');
    formData.append('catalog_id', document.getElementById('inv_catalog_id').value);
    formData.append('qty_total', document.getElementById('inv_qty_total').value);
    formData.append('qty_usable', document.getElementById('inv_qty_usable').value);
    formData.append('qty_damaged', document.getElementById('inv_qty_damaged').value);
    formData.append('qty_missing', document.getElementById('inv_qty_missing').value);
    formData.append('location', document.getElementById('inv_location').value);
    formData.append('nonce', '<?php echo wp_create_nonce("eess_admin_action"); ?>');

    fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
        btn.disabled = false;
        btn.innerText = 'حفظ وتأكيد حصر العهدة';
        if (res.success) {
            document.getElementById('asset-inventory-modal').style.display = 'none';
            if (typeof smShowNotification === 'function') {
                smShowNotification('✓ تم حفظ وتحديث حصر العهدة المؤسسية بنجاح.');
            }
            setTimeout(function() { location.reload(); }, 600);
        } else {
            alert('خطأ: ' + (res.data || 'فشل حفظ حصر العهدة.'));
        }
    });
}

function eessSaveAssetRequestSubmit(e) {
    e.preventDefault();
    var btn = document.getElementById('req_submit_btn');
    btn.disabled = true;
    btn.innerText = 'جاري إرسال الطلب...';

    var formData = new FormData();
    formData.append('action', 'sm_save_asset_request');
    formData.append('catalog_id', document.getElementById('req_catalog_id').value);
    formData.append('qty_requested', document.getElementById('req_qty_requested').value);
    formData.append('request_reason', document.getElementById('req_reason').value);
    formData.append('nonce', '<?php echo wp_create_nonce("eess_admin_action"); ?>');

    fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
        btn.disabled = false;
        btn.innerText = 'إرسال طلب التوريد للمراجعة';
        if (res.success) {
            document.getElementById('asset-request-modal').style.display = 'none';
            if (typeof smShowNotification === 'function') {
                smShowNotification('✓ تم إرسال طلب التوريد بنجاح للمراجعة والاعتماد.');
            }
            setTimeout(function() { location.reload(); }, 600);
        } else {
            alert('خطأ: ' + (res.data || 'فشل إرسال طلب التوريد.'));
        }
    });
}
</script>
