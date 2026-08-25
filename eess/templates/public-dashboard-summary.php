<?php
if (!defined('ABSPATH')) exit;

$user = wp_get_current_user();
$user_id = $user->ID;
$roles = (array) $user->roles;

$is_admin = in_array('administrator', $roles) || in_array('sm_system_admin', $roles) || current_user_can('manage_options');
$is_principal = in_array('sm_principal', $roles);
$is_supervisor = in_array('sm_supervisor', $roles);
$is_hod = in_array('sm_hod', $roles);
$is_coordinator = in_array('sm_coordinator', $roles);
$is_teacher = in_array('sm_teacher', $roles);
$is_discipline_sup = in_array('sm_discipline_supervisor', $roles);
$is_activities_sup = in_array('sm_activities_supervisor', $roles);
$is_parent = in_array('sm_parent', $roles) || in_array('sm_student', $roles);

$dash_data = SM_DB::get_personalized_dashboard_data($user_id);
?>

<div class="sm-content-wrapper" dir="rtl" style="font-family: 'Cairo', sans-serif !important;">

    <!-- Single Main Banner Header (Dashboard) -->
    <div style="background: #ffffff; padding: 20px 24px; border-radius: 20px; border: 1px solid #e2e8f0; margin-bottom: 18px; box-shadow: 0 4px 18px rgba(0,0,0,0.02); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
        <div style="display: flex; align-items: center; gap: 14px;">
            <div style="width: 48px; height: 48px; background: #fef2f2; border-radius: 14px; display: flex; align-items: center; justify-content: center; color: #881337; border: 1px solid #fecdd3; flex-shrink: 0;">
                <span class="dashicons dashicons-dashboard" style="font-size: 24px; width: 24px; height: 24px;"></span>
            </div>
            <div>
                <h2 style="margin: 0 0 4px 0; font-size: 20px; font-weight: 800; color: #0f172a;">لوحة التحكم الرئيسية والعمليات اليومية</h2>
                <p style="margin: 0; font-size: 12.5px; color: #64748b; font-weight: 500;">متابعة الحصص الحالية، نسبة الحضور والغياب، التنبيهات السلوكية والعمليات التعليمية المباشرة</p>
            </div>
        </div>

        <!-- Primary Header Actions -->
        <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
            <a href="<?php echo home_url('/attendance/'); ?>" class="sm-btn" style="background: #881337; color: #ffffff !important; height: 38px; border-radius: 9999px !important; padding: 0 20px; font-weight: 800; font-size: 12.5px; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; text-decoration: none;">
                <span class="dashicons dashicons-calendar-alt" style="font-size: 15px; width: 15px; height: 15px; color: #fff;"></span>
                <span>تسجيل الحضور اليومي</span>
            </a>
        </div>
    </div>

<?php if ($is_teacher || $is_coordinator || $is_activities_sup): ?>
    <!-- TEACHER & COORDINATOR PERSONALIZED OPERATIONAL DASHBOARD -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px; margin-bottom: 25px;">

        <!-- 1. Current / Next Lesson Card -->
        <div style="background: #ffffff; border-radius: 16px; padding: 20px; border: 1px solid #cbd5e1; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); border-top: 4px solid #2563eb;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                <span style="font-size: 12px; font-weight: 800; color: #64748b;">الحصة الحالية / القادمة</span>
                <span class="dashicons dashicons-clock" style="color: #2563eb; font-size: 18px;"></span>
            </div>
            <?php if (!empty($dash_data['current_lesson'])):
                $les = $dash_data['current_lesson'];
            ?>
                <div style="font-size: 15px; font-weight: 800; color: #0f172a; margin-bottom: 4px; line-height: 1.4;"><?php echo esc_html($les['lesson_title'] ?? $les['subject']); ?></div>
                <div style="font-size: 12px; font-weight: 700; color: #2563eb; margin-bottom: 6px;"><?php echo esc_html($les['subject'] . ' - ' . $les['grade'] . ' (' . $les['section'] . ')'); ?></div>
                <div style="font-size: 11px; color: #64748b; font-weight: 600;"><?php echo esc_html($les['period']); ?></div>
            <?php else: ?>
                <div style="padding: 15px 0; text-align: center; color: #94a3b8; font-size: 12px; font-weight: 700;">
                    لا توجد حصص مجدولة حالياً لهذا الوقت.
                </div>
            <?php endif; ?>
        </div>

        <!-- 2. Daily Attendance Card -->
        <div style="background: #ffffff; border-radius: 16px; padding: 20px; border: 1px solid #cbd5e1; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); border-top: 4px solid #16a34a;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                <span style="font-size: 12px; font-weight: 800; color: #64748b;">نسبة حضور فصولي اليوم</span>
                <span class="dashicons dashicons-groups" style="color: #16a34a; font-size: 18px;"></span>
            </div>
            <?php $att = $dash_data['attendance_stats'] ?? array('pct' => 100, 'present' => 0, 'absent' => 0, 'excused' => 0, 'total_students' => 0); ?>
            <div style="font-size: 28px; font-weight: 900; color: #16a34a; line-height: 1; margin-bottom: 6px;"><?php echo $att['pct']; ?>%</div>
            <div style="font-size: 11px; color: #64748b; display: flex; gap: 8px;">
                <span>حاضر: <strong><?php echo $att['present']; ?></strong></span>
                <span>غائب: <strong><?php echo $att['absent']; ?></strong></span>
                <span>المجموع: <strong><?php echo $att['total_students']; ?></strong></span>
            </div>
        </div>

        <!-- 3. Tasks Awaiting Evaluation Card -->
        <a href="<?php echo add_query_arg('sm_tab', 'assignments'); ?>" style="text-decoration: none; background: #ffffff; border-radius: 16px; padding: 20px; border: 1px solid #cbd5e1; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); border-top: 4px solid #f59e0b; display: block;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                <span style="font-size: 12px; font-weight: 800; color: #64748b;">مهام بانتظار التقييم والمراجعة</span>
                <span class="dashicons dashicons-welcome-write-blog" style="color: #f59e0b; font-size: 18px;"></span>
            </div>
            <?php $tasks = $dash_data['tasks_eval'] ?? array('total' => 0, 'pending_homework' => 0, 'pending_preps' => 0); ?>
            <div style="font-size: 28px; font-weight: 900; color: #d97706; line-height: 1; margin-bottom: 6px;"><?php echo $tasks['total']; ?></div>
            <div style="font-size: 11px; color: #64748b;">واجبات سريعة وتحضيرات بانتظار مراجعتك</div>
        </a>

        <!-- 4. Academic Alerts Card -->
        <a href="<?php echo add_query_arg('sm_tab', 'stats'); ?>" style="text-decoration: none; background: #ffffff; border-radius: 16px; padding: 20px; border: 1px solid #cbd5e1; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); border-top: 4px solid #dc2626; display: block;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                <span style="font-size: 12px; font-weight: 800; color: #64748b;">تنبيهات انضباطية وأكاديمية</span>
                <span class="dashicons dashicons-warning" style="color: #dc2626; font-size: 18px;"></span>
            </div>
            <div style="font-size: 28px; font-weight: 900; color: #dc2626; line-height: 1; margin-bottom: 6px;"><?php echo intval($dash_data['academic_alerts_count'] ?? 0); ?></div>
            <div style="font-size: 11px; color: #64748b;">طلاب بحاجة لمتابعة خاصة وسجل سلوكي</div>
        </a>
    </div>

    <!-- Teacher Quick Actions Bar -->
    <div style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 20px; padding: 18px 24px; margin-bottom: 30px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 14px; box-shadow: 0 4px 16px rgba(0,0,0,0.02);">
        <div style="font-weight: 800; font-size: 14px; color: #0f172a; display: flex; align-items: center; gap: 8px;">
            <span class="dashicons dashicons-bolt" style="color: #f59e0b; font-size: 20px; width: 20px; height: 20px;"></span>
            <span>إجراءات سريعة للمعلم:</span>
        </div>
        <div style="display: flex; gap: 10px; flex-wrap: wrap; align-items: center;">
            <a href="<?php echo home_url('/attendance/'); ?>" class="sm-btn" style="height: 38px; padding: 0 18px; font-size: 12.5px; background: #2563eb; color: #ffffff !important; border-radius: 9999px !important; font-weight: 800; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                <span class="dashicons dashicons-calendar-alt" style="font-size: 15px; width: 15px; height: 15px;"></span>
                <span>تسجيل غياب للحصة الحالية</span>
            </a>
            <a href="<?php echo add_query_arg('sm_tab', 'grades'); ?>" class="sm-btn" style="height: 38px; padding: 0 18px; font-size: 12.5px; background: #000000; color: #ffffff !important; border-radius: 9999px !important; font-weight: 800; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                <span class="dashicons dashicons-welcome-write-blog" style="font-size: 15px; width: 15px; height: 15px;"></span>
                <span>رصد درجات / تقييم جديد</span>
            </a>
            <a href="<?php echo add_query_arg('sm_tab', 'assignments'); ?>" class="sm-btn" style="height: 38px; padding: 0 18px; font-size: 12.5px; background: #475569; color: #ffffff !important; border-radius: 9999px !important; font-weight: 800; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                <span class="dashicons dashicons-edit" style="font-size: 15px; width: 15px; height: 15px;"></span>
                <span>إضافة واجب / تكليف</span>
            </a>
            <button type="button" onclick="eessOpenQuickParentNoteModal()" class="sm-btn" style="height: 38px; padding: 0 20px; font-size: 12.5px; background: #881337; color: #ffffff !important; border-radius: 9999px !important; font-weight: 800; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
                <span class="dashicons dashicons-email-alt" style="font-size: 15px; width: 15px; height: 15px;"></span>
                <span>إرسال ملاحظة لولي الأمر</span>
            </button>
        </div>
    </div>
<?php else: ?>
    <!-- ADMIN & GENERAL STATS CARD GRID -->
    <div class="sm-card-grid" style="margin-bottom: 30px;">
        <div class="sm-stat-card" style="border-top: 4px solid var(--sm-primary-color); border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.02);">
            <div style="font-size: 0.9em; color: var(--sm-text-gray); margin-bottom: 8px; font-weight: 700;">إجمالي الطلاب</div>
            <div style="font-size: 2.6em; font-weight: 800; color: var(--sm-primary-color); line-height: 1.2;"><?php echo esc_html($stats['total_students'] ?? $dash_data['total_students'] ?? 0); ?></div>
        </div>
        <div class="sm-stat-card" style="border-top: 4px solid var(--sm-secondary-color); border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.02);">
            <div style="font-size: 0.9em; color: var(--sm-text-gray); margin-bottom: 8px; font-weight: 700;">إجمالي المعلمين</div>
            <div style="font-size: 2.6em; font-weight: 800; color: var(--sm-secondary-color); line-height: 1.2;"><?php echo esc_html($stats['total_teachers'] ?? $dash_data['total_teachers'] ?? 0); ?></div>
        </div>
        <div class="sm-stat-card" style="border-top: 4px solid var(--sm-accent-color); border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.02);">
            <div style="font-size: 0.9em; color: var(--sm-text-gray); margin-bottom: 8px; font-weight: 700;">مخالفات اليوم</div>
            <div style="font-size: 2.6em; font-weight: 800; color: var(--sm-accent-color); line-height: 1.2;"><?php echo esc_html($stats['violations_today'] ?? $dash_data['violations_today'] ?? 0); ?></div>
        </div>
        <div class="sm-stat-card" style="border-top: 4px solid var(--sm-dark-color); border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.02);">
            <div style="font-size: 0.9em; color: var(--sm-text-gray); margin-bottom: 8px; font-weight: 700;">الإجراءات المتخذة</div>
            <div style="font-size: 2.6em; font-weight: 800; color: var(--sm-dark-color); line-height: 1.2;"><?php echo esc_html($stats['total_actions'] ?? 0); ?></div>
        </div>
    </div>
<?php endif; ?>



<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 30px;">
    <!-- Trends and Categories Charts -->
    <div style="background: #fff; padding: 20px; border-radius: 8px; border: 1px solid var(--sm-border-color); position: relative; max-height: 300px; overflow: hidden;">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 10px;">
            <h3 style="margin:0; font-size: 1.0em;">اتجاهات المخالفات (آخر 30 يوم)</h3>
            <button onclick="smDownloadChart('violationTrendsChart', 'اتجاهات_المخالفات')" class="sm-action-btn" title="تحميل كصورة" style="background:none; border:none; color:var(--sm-text-gray); cursor:pointer;"><span class="dashicons dashicons-download"></span></button>
        </div>
        <div style="height: 180px;"><canvas id="violationTrendsChart"></canvas></div>
    </div>
    <!-- Support & Updates Card replacing Category Chart with Same Dimensions -->
    <div style="background: #fff; padding: 20px; border-radius: 16px; border: 1px solid #e2e8f0; position: relative; max-height: 300px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); display: flex; flex-direction: column;">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #f1f5f9; padding-bottom: 10px; margin-bottom: 12px;">
            <h3 style="margin:0; font-size: 14px; font-weight: 800; color: #0f172a; display: flex; align-items: center; gap: 8px;">
                <span class="dashicons dashicons-megaphone" style="color: #2563eb;"></span>
                <span>الدعم والمستجدات الإدارية</span>
            </h3>
            <span style="font-size: 11px; color: #64748b; font-weight: 700;">أحدث 3 رسائل</span>
        </div>

        <div style="flex: 1; overflow-y: auto; display: flex; flex-direction: column; gap: 8px;">
            <?php
            global $wpdb;
            $latest_support = $wpdb->get_results("
                SELECT * FROM {$wpdb->prefix}sm_system_announcements
                WHERE status = 'active'
                ORDER BY created_at DESC LIMIT 3
            ");

            if (empty($latest_support)): ?>
                <div style="padding: 30px; text-align: center; color: #94a3b8; font-size: 12px; font-weight: 600;">لا توجد مستجدات أو رسائل دعم جديدة حالياً.</div>
            <?php else:
                $pastel_colors = array(
                    array('bg' => '#f0f9ff', 'border' => '#bae6fd', 'text' => '#0369a1', 'tag' => 'تحديث إداري'),
                    array('bg' => '#f0fdf4', 'border' => '#bbf7d0', 'text' => '#15803d', 'tag' => 'إشعار هام'),
                    array('bg' => '#fffbeb', 'border' => '#fef3c7', 'text' => '#b45309', 'tag' => 'تنبيه النظام')
                );
                $idx = 0;
                foreach ($latest_support as $sup_msg):
                    $p_theme = $pastel_colors[$idx % count($pastel_colors)];
                    $idx++;
            ?>
                <div onclick="eessOpenSupportUpdateModal('<?php echo esc_js($sup_msg->title); ?>', '<?php echo esc_js(str_replace(array("\r", "\n"), ' ', $sup_msg->details)); ?>', '<?php echo esc_js($sup_msg->created_at); ?>')" style="background: <?php echo $p_theme['bg']; ?>; border: 1px solid <?php echo $p_theme['border']; ?>; border-radius: 12px; padding: 10px 14px; cursor: pointer; transition: all 0.2s ease;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                        <span style="font-size: 12.5px; font-weight: 800; color: #0f172a;"><?php echo esc_html($sup_msg->title); ?></span>
                        <span style="font-size: 10px; font-weight: 700; color: <?php echo $p_theme['text']; ?>; background: rgba(255,255,255,0.7); padding: 1px 8px; border-radius: 50px; border: 1px solid <?php echo $p_theme['border']; ?>;"><?php echo $p_theme['tag']; ?></span>
                    </div>
                    <div style="font-size: 11.5px; color: #475569; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; line-height: 1.4;">
                        <?php echo esc_html($sup_msg->details); ?>
                    </div>
                </div>
            <?php endforeach; endif; ?>
        </div>
    </div>
</div>





<script>
function smDownloadChart(chartId, fileName) {
    const canvas = document.getElementById(chartId);
    if (!canvas) return;
    const link = document.createElement('a');
    link.download = fileName + '.png';
    link.href = canvas.toDataURL('image/png');
    link.click();
}

(function() {
    window.smCharts = window.smCharts || {};

    const initSummaryCharts = function() {
        if (typeof Chart === 'undefined') {
            setTimeout(initSummaryCharts, 200);
            return;
        }

        const chartOptions = { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } };
        const severityLabels = <?php echo json_encode(SM_Settings::get_severities()); ?>;

        const createOrUpdateChart = (id, config) => {
            if (window.smCharts[id]) {
                window.smCharts[id].destroy();
            }
            const el = document.getElementById(id);
            if (el) {
                window.smCharts[id] = new Chart(el.getContext('2d'), config);
            }
        };

        // Trends Chart
        createOrUpdateChart('violationTrendsChart', {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_map(function($t){ return date('m/d', strtotime($t->date)); }, $stats['trends'] ?? [])); ?>,
                datasets: [{
                    label: 'المخالفات',
                    data: <?php echo json_encode(array_map(function($t){ return $t->count; }, $stats['trends'] ?? [])); ?>,
                    borderColor: '#334155',
                    backgroundColor: 'rgba(51, 65, 85, 0.08)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
            }
        });

        // Categories Chart
        const typeLabels = <?php echo json_encode(SM_Settings::get_violation_types()); ?>;
        createOrUpdateChart('violationCategoriesChart', {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_map(function($t) use ($typeLabels){ return $typeLabels[$t->type] ?? $t->type; }, $stats['by_type'] ?? [])); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_map(function($t){ return $t->count; }, $stats['by_type'] ?? [])); ?>,
                    backgroundColor: ['#334155', '#475569', '#64748B', '#1E293B', '#94A3B8']
                }]
            },
            options: chartOptions
        });

    };

    if (document.readyState === 'complete') initSummaryCharts();
    else window.addEventListener('load', initSummaryCharts);
})();
</script>
</div>

<!-- Support Update Detail Viewer Modal -->
<div id="eess-support-update-modal" class="sm-modal-overlay" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.75); backdrop-filter: blur(5px); z-index: 999999; justify-content: center; align-items: center; padding: 20px; box-sizing: border-box; font-family: 'Cairo', sans-serif; direction: rtl;">
    <div style="background: #ffffff; border-radius: 20px; max-width: 520px; width: 100%; border: 1px solid #cbd5e1; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.3); overflow: hidden; display: flex; flex-direction: column;">
        <div style="background: #0f172a; color: #ffffff; padding: 16px 22px; display: flex; justify-content: space-between; align-items: center; width: 100%; box-sizing: border-box;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <span class="dashicons dashicons-megaphone" style="color: #ffffff; font-size: 20px; width: 20px; height: 20px;"></span>
                <h3 style="margin: 0; font-size: 15.5px; font-weight: 800; color: #ffffff;" id="m_sup_modal_title">تفاصيل المستجد الإداري</h3>
            </div>
            <button type="button" onclick="document.getElementById('eess-support-update-modal').style.display='none'" style="background: none; border: none; color: #ffffff; font-size: 24px; cursor: pointer; line-height: 1;">&times;</button>
        </div>

        <div style="padding: 22px;">
            <div style="font-size: 11px; color: #64748b; font-family: monospace; margin-bottom: 10px;" id="m_sup_modal_date">---</div>
            <div style="background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 12px; padding: 16px; font-size: 13px; color: #1e293b; line-height: 1.7; white-space: pre-line; margin-bottom: 20px;" id="m_sup_modal_details">
                <!-- Message content -->
            </div>

            <div style="display: flex; justify-content: flex-end;">
                <button type="button" onclick="document.getElementById('eess-support-update-modal').style.display='none'" class="sm-btn" style="height: 38px; padding: 0 24px; border-radius: 9999px !important; font-size: 12.5px; background: #000000; color: #ffffff !important; font-weight: 800; border: none; cursor: pointer;">إغلاق التنبيه</button>
            </div>
        </div>
    </div>
</div>

<script>
function eessOpenSupportUpdateModal(title, details, dateStr) {
    document.getElementById('m_sup_modal_title').innerText = title;
    document.getElementById('m_sup_modal_details').innerText = details;
    document.getElementById('m_sup_modal_date').innerText = 'تاريخ النشر: ' + dateStr;
    document.getElementById('eess-support-update-modal').style.display = 'flex';
}
</script>

<!-- Quick Parent Note Modal -->
<div id="eess-quick-parent-note-modal" class="sm-modal-overlay" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.75); backdrop-filter: blur(5px); z-index: 999999; justify-content: center; align-items: center; padding: 20px; box-sizing: border-box; font-family: 'Cairo', sans-serif; direction: rtl;">
    <div style="background: #ffffff; border-radius: 20px; max-width: 520px; width: 100%; border: 1px solid #cbd5e1; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.3); overflow: hidden; display: flex; flex-direction: column;">
        <div style="background: #0f172a; color: #ffffff; padding: 16px 22px; display: flex; justify-content: space-between; align-items: center; width: 100%; box-sizing: border-box;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <span class="dashicons dashicons-email-alt" style="color: #ffffff; font-size: 20px; width: 20px; height: 20px;"></span>
                <h3 style="margin: 0; font-size: 15.5px; font-weight: 800; color: #ffffff;">إرسال ملاحظة أو استفسار لولي الأمر</h3>
            </div>
            <button type="button" onclick="document.getElementById('eess-quick-parent-note-modal').style.display='none'" style="background: none; border: none; color: #ffffff; font-size: 24px; cursor: pointer; line-height: 1;">&times;</button>
        </div>

        <div style="padding: 22px;">
            <div style="margin-bottom: 16px;">
                <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px;">اختر الطالب المستهدف *</label>
                <select id="eess_note_student_id" class="sm-select" style="width: 100%; height: 42px; border-radius: 9999px !important; border: 1px solid #cbd5e1; padding: 0 16px; font-size: 13px;">
                    <option value="">-- اختر اسم الطالب --</option>
                    <?php
                    $all_st_list = SM_DB::get_students();
                    foreach ($all_st_list as $st) {
                        echo "<option value='{$st->id}'>" . esc_html($st->name) . " (" . esc_html($st->class_name) . " - " . esc_html($st->section) . ")</option>";
                    }
                    ?>
                </select>
            </div>

            <div style="margin-bottom: 20px;">
                <label style="display: block; font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 6px;">نص الملاحظة أو الاستفسار *</label>
                <textarea id="eess_note_text" rows="4" class="sm-textarea" placeholder="اكتب الملاحظة أو المتابعة المطلوبة لولي الأمر هنا..." style="width: 100%; border-radius: 12px; border: 1px solid #cbd5e1; padding: 12px; font-size: 13px; box-sizing: border-box;"></textarea>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" onclick="document.getElementById('eess-quick-parent-note-modal').style.display='none'" class="sm-btn sm-btn-outline" style="height: 38px; padding: 0 18px; border-radius: 9999px !important; font-size: 12.5px; color: #475569; font-weight: 700;">إلغاء</button>
                <button type="button" id="eess-btn-send-note" onclick="eessSubmitQuickParentNote()" class="sm-btn" style="height: 38px; padding: 0 24px; border-radius: 9999px !important; font-size: 12.5px; background: #881337; color: #ffffff !important; font-weight: 800; border: none; cursor: pointer;">إرسال الملاحظة الآن</button>
            </div>
        </div>
    </div>
</div>

<script>
function eessOpenQuickParentNoteModal() {
    document.getElementById('eess-quick-parent-note-modal').style.display = 'flex';
}

function eessSubmitQuickParentNote() {
    const studentId = document.getElementById('eess_note_student_id').value;
    const noteText  = document.getElementById('eess_note_text').value.trim();

    if (!studentId || !noteText) {
        if (typeof smShowNotification === 'function') {
            smShowNotification('يرجى اختيار الطالب وكتابة نص الملاحظة', true);
        } else {
            alert('يرجى اختيار الطالب وكتابة نص الملاحظة');
        }
        return;
    }

    const btn = document.getElementById('eess-btn-send-note');
    btn.disabled = true;
    btn.innerText = 'جاري الإرسال...';

    const formData = new FormData();
    formData.append('action', 'eess_send_quick_parent_note');
    formData.append('student_id', studentId);
    formData.append('note', noteText);

    fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(res => {
        btn.disabled = false;
        btn.innerText = 'إرسال الملاحظة الآن';

        if (res.success) {
            if (typeof smShowNotification === 'function') {
                smShowNotification(res.data.message || 'تم إرسال الملاحظة بنجاح إلى ولي الأمر');
            } else {
                alert(res.data.message || 'تم إرسال الملاحظة بنجاح إلى ولي الأمر');
            }
            document.getElementById('eess-quick-parent-note-modal').style.display = 'none';
            document.getElementById('eess_note_text').value = '';
        } else {
            if (typeof smShowNotification === 'function') {
                smShowNotification(res.data || 'حدث خطأ أثناء إرسال الملاحظة', true);
            } else {
                alert(res.data || 'حدث خطأ أثناء إرسال الملاحظة');
            }
        }
    });
}
</script>
