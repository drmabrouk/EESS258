<?php if (!defined('ABSPATH')) exit; ?>
<div class="sm-content-wrapper" dir="rtl" style="font-family: 'Cairo', sans-serif !important;">

    <!-- Single Main Banner Header (Matching Teacher Term & Annual Plans) -->
    <div style="background: #ffffff; padding: 20px 24px; border-radius: 20px; border: 1px solid #e2e8f0; margin-bottom: 18px; box-shadow: 0 4px 18px rgba(0,0,0,0.02); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
        <div style="display: flex; align-items: center; gap: 14px;">
            <div style="width: 48px; height: 48px; background: #fef2f2; border-radius: 14px; display: flex; align-items: center; justify-content: center; color: #881337; border: 1px solid #fecdd3; flex-shrink: 0;">
                <span class="dashicons dashicons-admin-users" style="font-size: 24px; width: 24px; height: 24px;"></span>
            </div>
            <div>
                <h2 style="margin: 0 0 4px 0; font-size: 20px; font-weight: 800; color: #0f172a;">إدارة أولياء الأمور</h2>
                <p style="margin: 0; font-size: 12.5px; color: #64748b; font-weight: 500;">إدارة سجلات وبيانات الاتصال لأولياء الأمور وربطهم بحسابات أبنائهم الطلاب المعتمدين</p>
            </div>
        </div>

        <?php if (current_user_can('manage_options') || current_user_can('إدارة_أولياء_الأمور')): ?>
        <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
            <button type="button" onclick="document.getElementById('add-parent-modal').style.display='flex'" class="sm-btn" style="background: #881337; color: #ffffff !important; height: 38px; border-radius: 9999px !important; padding: 0 20px; font-weight: 800; font-size: 12.5px; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
                <span class="dashicons dashicons-plus-alt2" style="font-size: 15px; width: 15px; height: 15px; color: #fff;"></span>
                <span>إضافة ولي أمر جديد</span>
            </button>
        </div>
        <?php endif; ?>
    </div>

    <!-- Search & Filter Card -->
    <div style="background: #ffffff; padding: 18px 22px; border-radius: 16px; border: 1px solid #e2e8f0; margin-bottom: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.02);">
        <form method="get" style="display: flex; gap: 14px; align-items: center; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 280px;">
                <label class="sm-label" style="font-size: 12px; font-weight: 700; color: #334155; margin-bottom: 4px; display: block;">بحث عن ولي أمر (بالاسم، البريد، الجوال، أو اسم الطالب):</label>
                <input type="text" name="parent_search" class="sm-input" value="<?php echo esc_attr(isset($_GET['parent_search']) ? $_GET['parent_search'] : ''); ?>" placeholder="أدخل اسم ولي الأمر أو بيانات الطالب..." style="height: 38px; border-radius: 9999px !important; border: 1px solid #cbd5e1; padding: 0 16px; font-size: 12.5px;">
            </div>
            <div style="display: flex; gap: 8px; align-self: flex-end;">
                <button type="submit" class="sm-btn" style="background: #1e293b; color: #fff !important; height: 38px; border-radius: 9999px !important; padding: 0 20px; font-weight: 800; font-size: 12.5px; border: none; cursor: pointer;">تطبيق البحث</button>
                <a href="<?php echo remove_query_arg('parent_search'); ?>" class="sm-btn" style="background: #f1f5f9; color: #475569 !important; height: 38px; border-radius: 9999px !important; padding: 0 16px; font-weight: 700; font-size: 12.5px; border: 1px solid #cbd5e1; text-decoration: none; display: inline-flex; align-items: center;">إعادة ضبط</a>
            </div>
        </form>
    </div>

    <div class="sm-parents-rows-container" style="display: flex; flex-direction: column; gap: 15px;">
        <?php 
        $search = !empty($_GET['parent_search']) ? sanitize_text_field($_GET['parent_search']) : '';
        $args = array('role' => 'sm_parent');

        if ($search) {
            $args['search'] = '*' . $search . '*';
            $args['search_columns'] = array('user_login', 'display_name', 'user_email');

            // Advanced Search: Join with students and check meta
            global $wpdb;
            $extra_parent_ids = $wpdb->get_col($wpdb->prepare(
                "SELECT DISTINCT parent_user_id FROM {$wpdb->prefix}sm_students WHERE (name LIKE %s OR parent_email LIKE %s) AND parent_user_id IS NOT NULL",
                '%' . $wpdb->esc_like($search) . '%',
                '%' . $wpdb->esc_like($search) . '%'
            ));

            $phone_parent_ids = $wpdb->get_col($wpdb->prepare(
                "SELECT user_id FROM {$wpdb->prefix}usermeta WHERE meta_key = 'sm_phone' AND meta_value LIKE %s",
                '%' . $wpdb->esc_like($search) . '%'
            ));

            $all_ids = array_unique(array_merge($extra_parent_ids, $phone_parent_ids));

            if (!empty($all_ids)) {
                // Get users by search first
                $search_parents = get_users($args);
                $search_ids = wp_list_pluck($search_parents, 'ID');

                // Combine and fetch all
                $final_ids = array_unique(array_merge($search_ids, $all_ids));
                unset($args['search'], $args['search_columns']);
                $args['include'] = $final_ids;
            }
        }

        $parents = get_users($args);

        $current_user_scope = EESS_Org_Helper::get_user_scope();
        if (!$current_user_scope['unrestricted']) {
            $parents = array_filter($parents, function($parent) use ($current_user_scope) {
                $children = SM_DB::get_students_by_parent($parent->ID);
                if (empty($children)) return false;
                foreach ($children as $c) {
                    if (in_array(intval($c->school_id), $current_user_scope['schools'])) {
                        return true;
                    }
                }
                return false;
            });
        }

        if (empty($parents)): ?>
            <div style="padding: 60px; text-align: center; background: #fff; border-radius: 12px; border: 1px solid var(--sm-border-color); color: #a0aec0;">
                <span class="dashicons dashicons-admin-users" style="font-size: 48px; width:48px; height:48px; margin-bottom:15px;"></span>
                <p>لا يوجد أولياء أمور مسجلون حالياً.</p>
            </div>
        <?php else: ?>
            <?php foreach ($parents as $parent): 
                $children = SM_DB::get_students_by_parent($parent->ID);
            ?>
                <div class="sm-parent-row" style="background: #fff; border-radius: 12px; border: 1px solid var(--sm-border-color); padding: 20px 30px; display: flex; align-items: center; justify-content: space-between; transition: 0.3s; gap: 20px;">
                    <div style="display: flex; align-items: center; gap: 20px; flex: 2;">
                        <div style="width: 50px; height: 50px; border-radius: 50%; background: #f0f4f8; display: flex; align-items: center; justify-content: center; font-size: 20px;">👨‍👩‍👧</div>
                        <div>
                            <div style="font-weight: 800; color: var(--sm-secondary-color); font-size: 1.1em;"><?php echo esc_html($parent->display_name); ?></div>
                            <div style="font-size: 0.85em; color: #718096; margin-top: 3px;"><?php echo esc_html($parent->user_email); ?></div>
                        </div>
                    </div>

                    <div style="flex: 2; background: #f8fafc; padding: 10px 15px; border-radius: 8px; border: 1px solid #edf2f7; font-size: 0.9em;">
                        <strong>الأبناء:</strong> 
                        <?php if (empty($children)): ?>
                            <span style="color: #e53e3e; font-size: 12px; margin-right: 10px;">لا يوجد أبناء مرتبطين</span>
                        <?php else: ?>
                            <div style="display: flex; flex-wrap: wrap; gap: 8px; margin-top: 5px;">
                                <?php foreach ($children as $c): ?>
                                    <span class="sm-badge sm-badge-low" style="background: #fff; font-size: 11px;"><?php echo esc_html($c->name); ?> (<?php echo SM_Settings::format_grade_name($c->class_name, $c->section, 'short'); ?>)</span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Standard 36px Circular Action Icons -->
                    <div style="flex: 1; display: flex; gap: 8px; justify-content: flex-end; align-items: center;">
                        <?php
                            $parent_phone = get_user_meta($parent->ID, 'sm_phone', true);
                            $formatted_phone = SM_Settings::format_uae_phone($parent_phone);
                            if (!empty($formatted_phone)):
                                $wa_msg = rawurlencode("السلام عليكم ورحمة الله وبركاته، الأخ/ت العزيز/ة " . $parent->display_name);
                        ?>
                            <a href="https://wa.me/<?php echo $formatted_phone; ?>?text=<?php echo $wa_msg; ?>" target="_blank" title="تواصل عبر واتساب" style="width: 36px; height: 36px; border-radius: 50% !important; background: #dcfce7; color: #16a34a; border: 1px solid #86efac; display: inline-flex; align-items: center; justify-content: center; text-decoration: none; transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.08)'" onmouseout="this.style.transform='scale(1)'">
                                <span class="dashicons dashicons-whatsapp" style="font-size: 18px; width: 18px; height: 18px; margin: 0;"></span>
                            </a>
                        <?php endif; ?>

                        <button onclick="requestCallIn(<?php echo $parent->ID; ?>, '<?php echo esc_js($parent->display_name); ?>', '<?php echo esc_js($parent->user_email); ?>', '<?php echo esc_js($formatted_phone ?: ''); ?>')" title="طلب استدعاء ولي الأمر" style="width: 36px; height: 36px; border-radius: 50% !important; background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.08)'" onmouseout="this.style.transform='scale(1)'">
                            <span class="dashicons dashicons-calendar-alt" style="font-size: 16px; width: 16px; height: 16px; margin: 0;"></span>
                        </button>

                        <?php if (current_user_can('manage_options') || current_user_can('edit_users')): ?>
                        <form method="post" style="display:inline;" onsubmit="return confirm('هل أنت متأكد من حذف حساب ولي الأمر بالكامل؟')">
                            <?php wp_nonce_field('sm_user_action', 'sm_nonce'); ?>
                            <input type="hidden" name="delete_user_id" value="<?php echo $parent->ID; ?>">
                            <button type="submit" name="sm_delete_user" title="حذف حساب ولي الأمر" style="width: 36px; height: 36px; border-radius: 50% !important; background: #fee2e2; color: #dc2626; border: 1px solid #fecdd3; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.08)'" onmouseout="this.style.transform='scale(1)'">
                                <span class="dashicons dashicons-trash" style="font-size: 16px; width: 16px; height: 16px; margin: 0;"></span>
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>


    <div id="add-parent-modal" class="sm-modal-overlay">
        <div class="sm-modal-content">
            <div class="sm-modal-header">
                <h3>إضافة ولي أمر جديد</h3>
                <button class="sm-modal-close" onclick="document.getElementById('add-parent-modal').style.display='none'">&times;</button>
            </div>
            <form id="add-parent-form">
                <?php wp_nonce_field('sm_user_action', 'sm_nonce'); ?>
                <input type="hidden" name="user_role" value="sm_parent">
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">
                    <div class="sm-form-group">
                        <label class="sm-label">الاسم الكامل:</label>
                        <input type="text" name="display_name" class="sm-input" required>
                    </div>
                    <div class="sm-form-group">
                        <label class="sm-label">اسم المستخدم (Login):</label>
                        <input type="text" name="user_login" class="sm-input" required>
                    </div>
                    <div class="sm-form-group">
                        <label class="sm-label">البريد الإلكتروني:</label>
                        <input type="email" name="user_email" class="sm-input" required>
                    </div>
                    <div class="sm-form-group">
                        <label class="sm-label">كلمة المرور:</label>
                        <input type="password" name="user_pass" class="sm-input" required>
                    </div>
                </div>
                <p style="font-size:12px; color:#718096; margin-top:15px;">ملاحظة: لربط ولي الأمر بطالب، قم بتحرير بيانات الطالب من قسم "إدارة الطلاب".</p>
                <button type="submit" class="sm-btn" style="margin-top:20px; width: 100%;">إنشاء الحساب الآن</button>
            </form>
        </div>
    </div>

    <script>
    (function() {
        const addForm = document.getElementById('add-parent-form');
        if (addForm) {
            addForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                formData.append('action', 'sm_add_parent_ajax');
                fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        smShowNotification('تمت إضافة ولي الأمر');
                        setTimeout(() => location.reload(), 500);
                    }
                });
            });
        }
    })();
    </script>
    <div id="call-in-modal" class="sm-modal-overlay">
        <div class="sm-modal-content" style="max-width: 500px;">
            <div class="sm-modal-header">
                <h3>إرسال طلب استدعاء ولي أمر</h3>
                <button class="sm-modal-close" onclick="document.getElementById('call-in-modal').style.display='none'">&times;</button>
            </div>
            <div style="text-align: center; padding: 20px 0;">
                <p style="font-size: 1.1em; margin-bottom: 25px;">إرسال طلب حضور للمدرسة لولي الأمر:<br><strong id="call_in_parent_name" style="color: var(--sm-primary-color); font-size: 1.2em;"></strong></p>

                <div class="sm-form-group" style="text-align: right;">
                    <label class="sm-label">نص الرسالة المقترح:</label>
                    <textarea id="call_in_msg_text" class="sm-textarea" rows="4">تحية طيبة، نرجو منكم التكرم بزيارة مكتب الإرشاد الطلابي بالمدرسة في أقرب وقت ممكن لمناقشة أمور هامة تخص ابنكم/ابنتكم. شكراً لتعاونكم.</textarea>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 30px;">
                    <button onclick="sendCallViaWhatsApp()" class="sm-btn" style="background: #25D366; gap: 10px;">
                        <span class="dashicons dashicons-whatsapp"></span> واتساب
                    </button>
                    <button onclick="sendCallViaEmail()" class="sm-btn" style="background: #111F35; gap: 10px;">
                        <span class="dashicons dashicons-email"></span> بريد إلكتروني
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
    let currentParentData = {};

    function requestCallIn(id, name, email, phone) {
        currentParentData = { id, name, email, phone };
        document.getElementById('call_in_parent_name').innerText = name;
        document.getElementById('call-in-modal').style.display = 'flex';
    }

    function sendCallViaWhatsApp() {
        const msg = encodeURIComponent(document.getElementById('call_in_msg_text').value);
        const phone = currentParentData.phone || '';
        if (!phone) {
            alert('رقم الهاتف غير مسجل لهذا الوالد أو صيغته غير صحيحة (يجب أن يكون رقماً إماراتياً).');
            return;
        }
        window.open(`https://wa.me/${phone}?text=${msg}`, '_blank');
    }

    function sendCallViaEmail() {
        const msg = encodeURIComponent(document.getElementById('call_in_msg_text').value);
        const subject = encodeURIComponent('طلب استدعاء رسمي من المدرسة');
        const email = currentParentData.email || '';
        if (!email) {
            alert('البريد الإلكتروني غير مسجل لهذا الوالد.');
            return;
        }
        window.location.href = `mailto:${email}?subject=${subject}&body=${msg}`;
    }
    </script>
</div>
