<?php if (!defined('ABSPATH')) exit; ?>
<div class="sm-content-wrapper" dir="rtl" style="font-family: 'Cairo', sans-serif !important;">

    <!-- Single Main Banner Header (Matching Teacher Term & Annual Plans) -->
    <div style="background: #ffffff; padding: 20px 24px; border-radius: 20px; border: 1px solid #e2e8f0; margin-bottom: 18px; box-shadow: 0 4px 18px rgba(0,0,0,0.02); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
        <div style="display: flex; align-items: center; gap: 14px;">
            <div style="width: 48px; height: 48px; background: #fef2f2; border-radius: 14px; display: flex; align-items: center; justify-content: center; color: #881337; border: 1px solid #fecdd3; flex-shrink: 0;">
                <span class="dashicons dashicons-portfolio" style="font-size: 24px; width: 24px; height: 24px;"></span>
            </div>
            <div>
                <h2 style="margin: 0 0 4px 0; font-size: 20px; font-weight: 800; color: #0f172a;">مكتبة الوثائق والتقارير</h2>
                <p style="margin: 0; font-size: 12.5px; color: #64748b; font-weight: 500;">مكتبة وأرشيف الوثائق والتقارير المدرسية الرسمية والقرارات والتعاميم المعتمدة للمؤسسة</p>
            </div>
        </div>

        <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
            <button onclick="document.getElementById('add-doc-modal').style.display='flex'" class="sm-btn" style="background: #881337; color: #ffffff !important; height: 38px; border-radius: 9999px !important; padding: 0 20px; font-weight: 800; font-size: 12.5px; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
                <span class="dashicons dashicons-plus-alt2" style="font-size: 15px; width: 15px; height: 15px; color: #fff;"></span>
                <span>إضافة مستند جديد</span>
            </button>
        </div>
    </div>

    <?php
    $user_roles = (array) wp_get_current_user()->roles;
    $is_teacher_role = in_array('sm_teacher', $user_roles) && !in_array('administrator', $user_roles) && !in_array('sm_system_admin', $user_roles) && !in_array('sm_principal', $user_roles) && !in_array('sm_supervisor', $user_roles) && !in_array('sm_coordinator', $user_roles) && !in_array('sm_hod', $user_roles);
    $can_upload_general_doc = in_array('administrator', $user_roles) || in_array('sm_system_admin', $user_roles) || in_array('sm_principal', $user_roles) || in_array('sm_supervisor', $user_roles) || in_array('sm_coordinator', $user_roles) || in_array('sm_hod', $user_roles) || current_user_can('manage_options');
    ?>

    <div class="sm-tabs-wrapper" style="display: flex; gap: 10px; margin-bottom: 25px; border-bottom: 2px solid #eee; padding-bottom: 10px;">
        <button class="sm-tab-btn sm-active" onclick="smOpenInternalTab('doc-library-tab', this)">مكتبة الوثائق الخاصة والتقارير</button>
        <button class="sm-tab-btn" onclick="smOpenInternalTab('general-doc-library-tab', this)">مكتبة الوثائق والتقارير العامة</button>
        <?php if (!$is_teacher_role && current_user_can('تسجيل_مخالفة')): ?>
            <button class="sm-tab-btn" onclick="smOpenInternalTab('regulation-custom-tab', this)">تخصيص اللائحة التنظيمية</button>
        <?php endif; ?>
    </div>

    <div id="doc-library-tab" class="sm-internal-tab">

    <?php
    global $wpdb;
    $query = "SELECT * FROM {$wpdb->prefix}sm_documents";
    if (!current_user_can('إدارة_النظام')) {
        $query .= " WHERE status = 'published'";
    }
    $query .= " ORDER BY created_at DESC";
    $docs = $wpdb->get_results($query);

    $doc_categories = array(
        'الوثائق الإدارية',
        'مستندات الموارد البشرية',
        'شؤون الطلاب',
        'الوثائق الأكاديمية',
        'التعاميم الرسمية',
        'النماذج والقوالب',
        'السياسات واللوائح',
        'أخرى'
    );
    ?>

    <!-- Instant Search and Filter Bar -->
    <div style="background: #f8fafc; padding: 18px; border-radius: 12px; border: 1px solid #cbd5e0; margin-bottom: 25px; display: grid; grid-template-columns: 2fr 1fr; gap: 15px; direction: rtl;">
        <div>
            <label class="sm-label" style="font-weight: 700; font-size: 12px;">البحث الفوري عن وثيقة</label>
            <input type="text" id="doc-instant-search" onkeyup="eessFilterDocuments()" placeholder="ابحث باسم الوثيقة أو الوصف..." class="sm-input" style="height: 38px; font-size: 12px; width: 100%;">
        </div>
        <div>
            <label class="sm-label" style="font-weight: 700; font-size: 12px;">تصنيف الوثيقة</label>
            <select id="doc-category-filter" onchange="eessFilterDocuments()" class="sm-select" style="height: 38px; font-size: 12px; width: 100%;">
                <option value="">كل التصنيفات</option>
                <?php foreach ($doc_categories as $cat): ?>
                    <option value="<?php echo esc_attr($cat); ?>"><?php echo esc_html($cat); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="sm-docs-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 25px; margin-bottom: 40px;">
        <?php if (empty($docs)): ?>
            <div style="grid-column: 1 / -1; background: #f8fafc; padding: 60px; border-radius: 12px; text-align: center; border: 2px dashed #e2e8f0;">
                <span class="dashicons dashicons-media-document" style="font-size: 50px; width: 50px; height: 50px; color: #cbd5e0; margin-bottom: 15px;"></span>
                <p style="color: #718096; font-weight: 700;">لا توجد مستندات متاحة حالياً في المكتبة.</p>
            </div>
        <?php else: ?>
            <?php foreach ($docs as $doc): ?>
                <div class="sm-doc-card" data-title="<?php echo esc_attr(strtolower($doc->title)); ?>" data-desc="<?php echo esc_attr(strtolower($doc->description)); ?>" data-category="<?php echo esc_attr($doc->category); ?>" style="background: white; border: 1px solid #cbd5e0; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); transition: transform 0.2s;">
                    <div style="padding: 20px; border-bottom: 1px solid #cbd5e0; background: #f8fafc; display: flex; align-items: center; gap: 15px;">
                        <div style="width: 45px; height: 45px; background: white; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #3182ce; border: 1px solid #edf2f7;">
                            <span class="dashicons dashicons-media-document" style="font-size: 24px; width: 24px; height: 24px;"></span>
                        </div>
                        <div style="flex: 1; min-width: 0;">
                            <h4 style="margin: 0; font-weight: 800; color: #1a202c; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;"><?php echo esc_html($doc->title); ?></h4>
                            <div style="display: flex; align-items: center; gap: 8px; margin-top: 4px;">
                                <span style="font-size: 10px; color: #475569; font-weight: 800; background: #e2e8f0; padding: 1px 6px; border-radius: 4px;"><?php echo esc_html($doc->category ?: 'الوثائق الإدارية'); ?></span>
                                <span style="font-size: 11px; color: #718096;"><?php echo date('Y-m-d', strtotime($doc->created_at)); ?></span>
                            </div>
                        </div>
                        <?php if (current_user_can('إدارة_النظام')): ?>
                            <div style="display: flex; gap: 5px;">
                                <button onclick='editDoc(<?php echo json_encode($doc); ?>)' style="background: none; border: none; cursor: pointer; color: #4a5568;"><span class="dashicons dashicons-edit" style="font-size: 16px;"></span></button>
                                <button onclick="deleteDoc(<?php echo $doc->id; ?>)" style="background: none; border: none; cursor: pointer; color: #e53e3e;"><span class="dashicons dashicons-trash" style="font-size: 16px;"></span></button>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div style="padding: 20px;">
                        <p style="margin: 0 0 20px 0; font-size: 13px; color: #4a5568; line-height: 1.6; height: 42px; overflow: hidden; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;">
                            <?php echo esc_html($doc->description ?: 'لا يوجد وصف متاح لهذا المستند.'); ?>
                        </p>
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                            <a href="<?php echo esc_url($doc->file_url); ?>" download class="sm-btn" style="height: 38px; font-size: 12px; text-decoration: none; display: flex; align-items: center; justify-content: center; gap: 8px;">
                                <span class="dashicons dashicons-download"></span> تحميل الملف
                            </a>
                            <button onclick="printPDF('<?php echo esc_url($doc->file_url); ?>')" class="sm-btn sm-btn-outline" style="height: 38px; font-size: 12px; display: flex; align-items: center; justify-content: center; gap: 8px;">
                                <span class="dashicons dashicons-printer"></span> طباعة فورية
                            </button>
                        </div>
                        <?php if (current_user_can('إدارة_النظام')): ?>
                            <div style="margin-top: 15px; font-size: 11px; text-align: center;">
                                <span style="padding: 2px 10px; border-radius: 20px; <?php echo $doc->status === 'published' ? 'background: #f0fff4; color: #2f855a; border: 1px solid #c6f6d5;' : 'background: #fff5f5; color: #c53030; border: 1px solid #fed7d7;'; ?>">
                                    <?php echo $doc->status === 'published' ? 'منشور للجميع' : 'مخفي عن المستخدمين'; ?>
                                </span>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Add Document Modal -->
    <div id="add-doc-modal" class="sm-modal-overlay">
        <div class="sm-modal-content" style="max-width: 500px;">
            <div class="sm-modal-header">
                <h3>إضافة مستند جديد للمكتبة</h3>
                <button class="sm-modal-close" onclick="document.getElementById('add-doc-modal').style.display='none'">&times;</button>
            </div>
            <form id="add-doc-form">
                <?php wp_nonce_field('sm_admin_action', 'sm_nonce'); ?>
                <div class="sm-form-group">
                    <label class="sm-label">عنوان المستند:</label>
                    <input type="text" name="title" class="sm-input" required placeholder="مثال: نموذج طلب إجازة رسمي">
                </div>
                <div class="sm-form-group">
                    <label class="sm-label">وصف مختصر:</label>
                    <textarea name="description" class="sm-textarea" rows="3" placeholder="أدخل تفاصيل عن محتوى الملف..."></textarea>
                </div>
                <div class="sm-form-group">
                    <label class="sm-label">رابط الملف (PDF):</label>
                    <div style="display: flex; gap: 10px;">
                        <input type="text" name="file_url" id="doc_file_url" class="sm-input" required placeholder="ارفع الملف أو أدخل الرابط...">
                        <button type="button" onclick="smOpenMediaUploader('doc_file_url')" class="sm-btn sm-btn-secondary" style="width: auto; white-space: nowrap;">رفع الملف</button>
                    </div>
                </div>
                <div class="sm-form-group">
                    <label class="sm-label">تصنيف المستند:</label>
                    <select name="category" class="sm-select">
                        <?php foreach ($doc_categories as $cat): ?>
                            <option value="<?php echo esc_attr($cat); ?>"><?php echo esc_html($cat); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="sm-form-group">
                    <label class="sm-label">حالة الظهور:</label>
                    <select name="status" class="sm-select">
                        <option value="published">منشور للجميع</option>
                        <option value="hidden">مخفي (للمدراء فقط)</option>
                    </select>
                </div>
                <div style="margin-top: 25px; display: flex; gap: 10px;">
                    <button type="submit" class="sm-btn">حفظ في المكتبة</button>
                    <button type="button" onclick="document.getElementById('add-doc-modal').style.display='none'" class="sm-btn sm-btn-outline">إلغاء</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Document Modal -->
    <div id="edit-doc-modal" class="sm-modal-overlay">
        <div class="sm-modal-content" style="max-width: 500px;">
            <div class="sm-modal-header">
                <h3>تعديل بيانات المستند</h3>
                <button class="sm-modal-close" onclick="document.getElementById('edit-doc-modal').style.display='none'">&times;</button>
            </div>
            <form id="edit-doc-form">
                <?php wp_nonce_field('sm_admin_action', 'sm_nonce'); ?>
                <input type="hidden" name="doc_id" id="edit_doc_id">
                <div class="sm-form-group">
                    <label class="sm-label">عنوان المستند:</label>
                    <input type="text" name="title" id="edit_doc_title" class="sm-input" required>
                </div>
                <div class="sm-form-group">
                    <label class="sm-label">وصف مختصر:</label>
                    <textarea name="description" id="edit_doc_description" class="sm-textarea" rows="3"></textarea>
                </div>
                <div class="sm-form-group">
                    <label class="sm-label">رابط الملف (PDF):</label>
                    <div style="display: flex; gap: 10px;">
                        <input type="text" name="file_url" id="edit_doc_file_url" class="sm-input" required>
                        <button type="button" onclick="smOpenMediaUploader('edit_doc_file_url')" class="sm-btn sm-btn-secondary" style="width: auto;">تغيير</button>
                    </div>
                </div>
                <div class="sm-form-group">
                    <label class="sm-label">تصنيف المستند:</label>
                    <select name="category" id="edit_doc_category" class="sm-select">
                        <?php foreach ($doc_categories as $cat): ?>
                            <option value="<?php echo esc_attr($cat); ?>"><?php echo esc_html($cat); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="sm-form-group">
                    <label class="sm-label">حالة الظهور:</label>
                    <select name="status" id="edit_doc_status" class="sm-select">
                        <option value="published">منشور للجميع</option>
                        <option value="hidden">مخفي</option>
                    </select>
                </div>
                <div style="margin-top: 25px; display: flex; gap: 10px;">
                    <button type="submit" class="sm-btn">تحديث البيانات</button>
                    <button type="button" onclick="document.getElementById('edit-doc-modal').style.display='none'" class="sm-btn sm-btn-outline">إلغاء</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function printPDF(url) {
        const win = window.open(url, '_blank');
        win.onload = function() {
            win.print();
        };
    }

    <?php if (current_user_can('إدارة_النظام')): ?>
    document.getElementById('add-doc-form').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('action', 'sm_add_document_ajax');

        fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                smShowNotification('تمت إضافة المستند بنجاح');
                location.reload();
            } else {
                smShowNotification('خطأ: ' + res.data, true);
            }
        });
    });

    window.editDoc = function(doc) {
        document.getElementById('edit_doc_id').value = doc.id;
        document.getElementById('edit_doc_title').value = doc.title;
        document.getElementById('edit_doc_description').value = doc.description;
        document.getElementById('edit_doc_file_url').value = doc.file_url;
        document.getElementById('edit_doc_status').value = doc.status;
        if (document.getElementById('edit_doc_category')) {
            document.getElementById('edit_doc_category').value = doc.category || 'الوثائق الإدارية';
        }
        document.getElementById('edit-doc-modal').style.display = 'flex';
    };

    window.eessFilterDocuments = function() {
        const query = document.getElementById('doc-instant-search').value.toLowerCase().trim();
        const cat = document.getElementById('doc-category-filter').value;

        const cards = document.querySelectorAll('.sm-doc-card');
        cards.forEach(card => {
            const title = card.getAttribute('data-title') || '';
            const desc = card.getAttribute('data-desc') || '';
            const category = card.getAttribute('data-category') || '';

            const matchesSearch = !query || title.includes(query) || desc.includes(query);
            const matchesCat = !cat || category === cat;

            if (matchesSearch && matchesCat) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
    };

    document.getElementById('edit-doc-form').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        formData.append('action', 'sm_update_document_ajax');

        fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                smShowNotification('تم تحديث بيانات المستند');
                location.reload();
            } else {
                smShowNotification('خطأ: ' + res.data, true);
            }
        });
    });

    window.deleteDoc = function(id) {
        if (!confirm('هل أنت متأكد من حذف هذا المستند نهائياً؟')) return;
        const formData = new FormData();
        formData.append('action', 'sm_delete_document_ajax');
        formData.append('doc_id', id);
        formData.append('nonce', '<?php echo wp_create_nonce("sm_admin_action"); ?>');

        fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                smShowNotification('تم حذف المستند بنجاح');
                location.reload();
            }
        });
    };
    <?php endif; ?>
    </script>

    <style>
    .sm-doc-card:hover { transform: translateY(-5px); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1) !important; }
    </style>
    </div><!-- End doc-library-tab -->

    <?php if (current_user_can('تسجيل_مخالفة')):
        $can_edit_regulation = current_user_can('إدارة_النظام') || current_user_can('sm_principal') || current_user_can('sm_supervisor');
    ?>
    <div id="regulation-custom-tab" class="sm-internal-tab" style="display:none;">
        <div style="background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:25px; margin-bottom:30px;">
            <h4 style="margin-top:0; border-bottom:1px solid #eee; padding-bottom:15px; color:var(--sm-primary-color);">إعدادات المخالفات العامة</h4>
            <form id="sm-violation-settings-form">
                <?php wp_nonce_field('sm_admin_action', 'sm_nonce'); ?>
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">
                    <div class="sm-form-group">
                        <label class="sm-label">أنواع المخالفات العامة (مفتاح|اسم):</label>
                        <textarea name="violation_types" class="sm-textarea" rows="5" <?php if (!$can_edit_regulation) echo 'readonly'; ?>><?php foreach(SM_Settings::get_violation_types() as $k=>$v) echo "$k|$v\n"; ?></textarea>
                    </div>
                    <div class="sm-form-group">
                        <?php $actions = SM_Settings::get_suggested_actions(); ?>
                        <label class="sm-label">اقتراحات الإجراءات (كل سطر خيار):</label>
                        <div style="font-size:11px; margin-bottom:5px;">منخفضة:</div>
                        <textarea name="suggested_low" class="sm-textarea" rows="2" <?php if (!$can_edit_regulation) echo 'readonly'; ?>><?php echo esc_textarea($actions['low']); ?></textarea>
                        <div style="font-size:11px; margin-top:5px; margin-bottom:5px;">متوسطة:</div>
                        <textarea name="suggested_medium" class="sm-textarea" rows="2" <?php if (!$can_edit_regulation) echo 'readonly'; ?>><?php echo esc_textarea($actions['medium']); ?></textarea>
                        <div style="font-size:11px; margin-top:5px; margin-bottom:5px;">خطيرة:</div>
                        <textarea name="suggested_high" class="sm-textarea" rows="2" <?php if (!$can_edit_regulation) echo 'readonly'; ?>><?php echo esc_textarea($actions['high']); ?></textarea>
                    </div>
                </div>
                <?php if ($can_edit_regulation): ?>
                    <button type="submit" class="sm-btn" style="width:auto;">حفظ إعدادات المخالفات</button>
                <?php endif; ?>
            </form>
        </div>

        <form id="sm-hierarchical-violations-form">
            <?php wp_nonce_field('sm_admin_action', 'sm_nonce');
            $h_violations = SM_Settings::get_hierarchical_violations();
            ?>
            <h4 style="margin-top:0;">إدارة اللائحة التنظيمية والمخالفات الهرمية</h4>
            <p style="font-size:12px; color:#666; margin-bottom:20px;">تعديل تفاصيل المخالفات، النقاط المستحقة، والإجراءات الافتراضية لكل مستوى. التغييرات تطبق فوراً عبر النظام.</p>

            <?php for($i=1; $i<=4; $i++): ?>
                <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:20px; margin-bottom:20px;">
                    <div style="font-weight:800; color:var(--sm-primary-color); margin-bottom:15px; display:flex; justify-content:space-between; align-items:center;">
                        <span>المستوى <?php echo $i; ?> (الدرجة <?php echo $i; ?>)</span>
                        <span style="font-size:11px; background:#fff; padding:2px 10px; border-radius:4px; color:#666; border:1px solid #ddd;">المخالفات: <?php echo count($h_violations[$i]); ?></span>
                    </div>
                    <div style="display:grid; grid-template-columns: 80px 1fr 60px 1fr <?php echo $can_edit_regulation ? 'auto' : ''; ?>; gap:10px; font-weight:700; font-size:11px; margin-bottom:10px; border-bottom:1px solid #eee; padding-bottom:5px;">
                        <div>الكود</div>
                        <div>الوصف / المسمى</div>
                        <div>النقاط</div>
                        <div>الإجراء المقترح</div>
                        <?php if ($can_edit_regulation): ?><div>-</div><?php endif; ?>
                    </div>
                    <div class="violation-rows-container" data-level="<?php echo $i; ?>">
                        <?php foreach($h_violations[$i] as $code => $v): ?>
                            <div style="display:grid; grid-template-columns: 80px 1fr 60px 1fr <?php echo $can_edit_regulation ? 'auto' : ''; ?>; gap:10px; margin-bottom:8px;">
                                <input type="text" name="h_viol[<?php echo $i; ?>][<?php echo $code; ?>][code]" value="<?php echo esc_attr($code); ?>" class="sm-input" style="padding:5px; font-size:12px;" <?php if (!$can_edit_regulation) echo 'readonly'; ?>>
                                <input type="text" name="h_viol[<?php echo $i; ?>][<?php echo $code; ?>][name]" value="<?php echo esc_attr($v['name']); ?>" class="sm-input" style="padding:5px; font-size:12px;" <?php if (!$can_edit_regulation) echo 'readonly'; ?>>
                                <input type="number" name="h_viol[<?php echo $i; ?>][<?php echo $code; ?>][points]" value="<?php echo esc_attr($v['points']); ?>" class="sm-input" style="padding:5px; font-size:12px;" <?php if (!$can_edit_regulation) echo 'readonly'; ?>>
                                <input type="text" name="h_viol[<?php echo $i; ?>][<?php echo $code; ?>][action]" value="<?php echo esc_attr($v['action']); ?>" class="sm-input" style="padding:5px; font-size:12px;" <?php if (!$can_edit_regulation) echo 'readonly'; ?>>
                                <?php if ($can_edit_regulation): ?>
                                    <button type="button" onclick="this.parentElement.remove()" class="sm-btn sm-btn-outline" style="padding:0; width:28px; height:28px; color:red;">×</button>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if ($can_edit_regulation): ?>
                        <button type="button" class="sm-btn sm-btn-outline" style="font-size:11px; margin-top:10px;" onclick="addViolationRow(<?php echo $i; ?>, this)">+ إضافة بند جديد للمستوى <?php echo $i; ?></button>
                    <?php endif; ?>
                </div>
            <?php endfor; ?>

            <?php if ($can_edit_regulation): ?>
                <button type="submit" class="sm-btn" style="width:auto; margin-top:10px;">حفظ اللائحة بالكامل</button>
            <?php endif; ?>
        </form>
    </div>

    <script>
    function addViolationRow(level, btn) {
        const container = btn.previousElementSibling;
        const div = document.createElement('div');
        div.style = "display:grid; grid-template-columns: 80px 1fr 60px 1fr auto; gap:10px; margin-bottom:8px;";
        const id = 'new_' + Math.random().toString(36).substr(2, 5);
        div.innerHTML = `
            <input type="text" name="h_viol[${level}][${id}][code]" placeholder="كود" class="sm-input" style="padding:5px; font-size:12px;">
            <input type="text" name="h_viol[${level}][${id}][name]" placeholder="الوصف" class="sm-input" style="padding:5px; font-size:12px;">
            <input type="number" name="h_viol[${level}][${id}][points]" value="0" class="sm-input" style="padding:5px; font-size:12px;">
            <input type="text" name="h_viol[${level}][${id}][action]" placeholder="الإجراء" class="sm-input" style="padding:5px; font-size:12px;">
            <button type="button" onclick="this.parentElement.remove()" class="sm-btn sm-btn-outline" style="padding:0; width:28px; height:28px; color:red;">×</button>
        `;
        container.appendChild(div);
    }

    (function() {
        const vSettingsForm = document.getElementById('sm-violation-settings-form');
        if (vSettingsForm) {
            vSettingsForm.onsubmit = function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                formData.append('action', 'sm_save_regulation_settings_ajax');
                fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
                .then(r => r.json()).then(res => {
                    if (res.success) smShowNotification('تم حفظ الإعدادات بنجاح');
                });
            };
        }

        const hViolForm = document.getElementById('sm-hierarchical-violations-form');
        if (hViolForm) {
            hViolForm.onsubmit = function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                formData.append('action', 'sm_save_hierarchical_violations_ajax');
                fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
                .then(r => r.json()).then(res => {
                    if (res.success) smShowNotification('تم تحديث اللائحة بنجاح وتطبيقها على النظام');
                });
            };
        }
    })();
    </script>
    <?php endif; ?>
</div>
