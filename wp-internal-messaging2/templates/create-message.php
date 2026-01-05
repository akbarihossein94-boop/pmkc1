<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/** @var string $system_doc_preview */
/** @var string $today */
/** @var WP_User[] $all_users */
$current_user = wp_get_current_user();
$success = ! empty( $_GET['wpim_status'] ) && $_GET['wpim_status'] === 'saved';
?>

<div class="wrap wpim-wrap" data-wpim-success="<?php echo $success ? '1' : '0'; ?>">
    <h1 class="wpim-page-title">ایجاد پیام جدید</h1>

    <div id="wpim-toast" class="wpim-toast" style="display:none;">
        <div class="wpim-toast-icon">✓</div>
        <div class="wpim-toast-text">پیام با موفقیت ارسال شد.</div>
    </div>

    <div id="wpim-form-errors" class="wpim-form-errors" style="display:none;"></div>

    <div class="wpim-card">
        <form id="wpim-create-message-form" method="post" enctype="multipart/form-data">
            <?php wp_nonce_field( 'wpim_save_message', 'wpim_nonce' ); ?>

            <!-- ردیف ۱: عنوان پیام (بزرگ) + شماره سند داخلی (کوچک) -->
            <div class="wpim-row">
                <div class="wpim-col wpim-col-wide">
                    <label for="wpim_subject"><strong>عنوان پیام</strong></label>
                    <input type="text" id="wpim_subject" name="wpim_subject"
                           class="wpim-input wpim-input-large" required>
                </div>
                <div class="wpim-col wpim-col-narrow">
                    <label for="wpim_internal_doc_number"><strong>شماره سند داخلی</strong></label>
                    <input type="text" id="wpim_internal_doc_number" name="wpim_internal_doc_number"
                           class="wpim-input wpim-input-small">
                </div>
            </div>

            <!-- ردیف ۲: ۵ فیلد در یک ردیف با ارتفاع یکسان -->
            <div class="wpim-row wpim-row-tight wpim-row-five">

                <!-- فرستنده (با آواتار) -->
                <div class="wpim-col">
                    <div class="wpim-meta-card wpim-meta-equal">
                        <div class="wpim-meta-avatar">
                            <?php echo get_avatar( $current_user->ID, 32 ); ?>
                        </div>
                        <div class="wpim-meta-content">
                            <div class="wpim-meta-label">فرستنده</div>
                            <div class="wpim-meta-value">
                                <?php echo esc_html( $current_user->display_name ); ?>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" name="wpim_sender_id"
                           value="<?php echo esc_attr( get_current_user_id() ); ?>">
                </div>

                <!-- شماره سند سیستمی -->
                <div class="wpim-col">
                    <div class="wpim-meta-card wpim-meta-equal">
                        <div class="wpim-meta-icon doc"></div>
                        <div class="wpim-meta-content">
                            <div class="wpim-meta-label">شماره سند سیستمی</div>
                            <div class="wpim-meta-value">
                                <?php echo esc_html( $system_doc_preview ); ?>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" name="wpim_system_doc_number" value="">
                </div>

                <!-- تاریخ شمسی -->
                <div class="wpim-col">
                    <div class="wpim-meta-card wpim-meta-equal">
                        <div class="wpim-meta-icon date"></div>
                        <div class="wpim-meta-content">
                            <div class="wpim-meta-label">تاریخ</div>
                            <div class="wpim-meta-value">
                                <?php echo esc_html( $today ); ?>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" name="wpim_date" value="<?php echo esc_attr( $today ); ?>">
                </div>

                <!-- نوع پیام -->
                <div class="wpim-col">
                    <div class="wpim-meta-card wpim-meta-equal meta-select">
                        <div class="wpim-meta-content full">
                            <div class="wpim-meta-label">نوع پیام</div>
                            <div class="wpim-chip-group wpim-chip-type" data-target-input="#wpim_message_type">
                                <button type="button" class="wpim-chip chip-type-internal active"
                                        data-value="internal">داخلی</button>
                                <button type="button" class="wpim-chip chip-type-external"
                                        data-value="external">خارجی</button>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" id="wpim_message_type" name="wpim_message_type" value="internal">
                </div>

                <!-- اولویت -->
                <div class="wpim-col">
                    <div class="wpim-meta-card wpim-meta-equal meta-select">
                        <div class="wpim-meta-content full">
                            <div class="wpim-meta-label">اولویت</div>
                            <div class="wpim-chip-group wpim-chip-priority" data-target-input="#wpim_priority">
                                <button type="button" class="wpim-chip chip-priority-normal active"
                                        data-value="normal">عادی</button>
                                <button type="button" class="wpim-chip chip-priority-low"
                                        data-value="low">کم</button>
                                <button type="button" class="wpim-chip chip-priority-high"
                                        data-value="high">مهم</button>
                                <button type="button" class="wpim-chip chip-priority-urgent"
                                        data-value="urgent">فوری</button>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" id="wpim_priority" name="wpim_priority" value="normal">
                </div>
            </div>

            <!-- ردیف ۳: گیرندگان / رونوشت -->
            <div class="wpim-row">
                <div class="wpim-col">
                    <label><strong>گیرندگان</strong></label>

                    <div class="wpim-select-field">
                        <div class="wpim-select-display" id="wpim_recipients_display">
                            <span class="placeholder">انتخاب گیرندگان...</span>
                        </div>
                        <div class="wpim-select-dropdown" id="wpim_recipients_list">
                            <input type="text" class="wpim-select-search" placeholder="جستجوی کاربر...">
                            <div class="wpim-select-options">
                                <?php foreach ( $all_users as $user ) :
                                    $label = $user->display_name . ' (' . $user->user_email . ')';
                                ?>
                                    <button type="button"
                                            class="wpim-select-option"
                                            data-user-id="<?php echo esc_attr( $user->ID ); ?>"
                                            data-user-label="<?php echo esc_attr( $label ); ?>">
                                        <?php echo esc_html( $label ); ?>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <div id="wpim_recipients_tokens" class="wpim-tokens"></div>
                    <input type="hidden" id="wpim_recipients" name="wpim_recipients" value="">
                </div>

                <div class="wpim-col">
                    <label><strong>رونوشت (CC)</strong></label>

                    <div class="wpim-select-field">
                        <div class="wpim-select-display" id="wpim_cc_display">
                            <span class="placeholder">انتخاب رونوشت...</span>
                        </div>
                        <div class="wpim-select-dropdown" id="wpim_cc_list">
                            <input type="text" class="wpim-select-search" placeholder="جستجوی کاربر...">
                            <div class="wpim-select-options">
                                <?php foreach ( $all_users as $user ) :
                                    $label = $user->display_name . ' (' . $user->user_email . ')';
                                ?>
                                    <button type="button"
                                            class="wpim-select-option"
                                            data-user-id="<?php echo esc_attr( $user->ID ); ?>"
                                            data-user-label="<?php echo esc_attr( $label ); ?>">
                                        <?php echo esc_html( $label ); ?>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <div id="wpim_cc_tokens" class="wpim-tokens"></div>
                    <input type="hidden" id="wpim_cc" name="wpim_cc" value="">
                </div>
            </div>

            <!-- ردیف ۴: اعلان‌ها – طراحی جدید -->
            <div class="wpim-row">
                <div class="wpim-col">
                    <label><strong>اعلان برای گیرندگان</strong></label>
                    <div class="wpim-notify-row">
                        <label class="wpim-notify-card email">
                            <input type="checkbox" name="wpim_notify_recipients_email" value="1" checked>
                            <span class="wpim-notify-text">ایمیل</span>
                        </label>
                        <label class="wpim-notify-card sms">
                            <input type="checkbox" name="wpim_notify_recipients_sms" value="1">
                            <span class="wpim-notify-text">پیامک</span>
                        </label>
                    </div>
                </div>

                <div class="wpim-col">
                    <label><strong>اعلان برای رونوشت (CC)</strong></label>
                    <div class="wpim-notify-row">
                        <label class="wpim-notify-card email">
                            <input type="checkbox" name="wpim_notify_cc_email" value="1" checked>
                            <span class="wpim-notify-text">ایمیل</span>
                        </label>
                        <label class="wpim-notify-card sms">
                            <input type="checkbox" name="wpim_notify_cc_sms" value="1">
                            <span class="wpim-notify-text">پیامک</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- ردیف ۵: متن پیام -->
            <div class="wpim-row">
                <div class="wpim-col-full">
                    <label for="wpim_message_body"><strong>متن پیام</strong></label>
                    <div class="wpim-editor-wrapper">
                        <?php
                        wp_editor(
                            '',
                            'wpim_message_body',
                            [
                                'textarea_name' => 'wpim_message_body',
                                'media_buttons' => false,
                                'textarea_rows' => 10,
                            ]
                        );
                        ?>
                    </div>
                </div>
            </div>

            <!-- ردیف ۵.۵: برچسب‌ها -->
            <div class="wpim-row">
                <div class="wpim-col-full">
                    <label for="wpim_tag_input"><strong>برچسب‌ها</strong></label>
                    <div class="wpim-tags-wrapper">
                        <div id="wpim_tags_tokens" class="wpim-tags-tokens"></div>
                        <input type="text" id="wpim_tag_input" class="wpim-input wpim-tag-input"
                               placeholder="برچسب را بنویسید و Enter بزنید...">
                        <input type="hidden" id="wpim_tags" name="wpim_tags" value="">
                    </div>
                </div>
            </div>

            <!-- ردیف ۶: امضاء + یادداشت داخلی -->
            <div class="wpim-row">
                <div class="wpim-col">
                    <label for="wpim_signature"><strong>امضاء</strong></label>
                    <textarea id="wpim_signature" name="wpim_signature" rows="3"
                              class="wpim-input wpim-textarea"></textarea>
                </div>
                <div class="wpim-col">
                    <label for="wpim_internal_note"><strong>یادداشت داخلی</strong></label>
                    <textarea id="wpim_internal_note" name="wpim_internal_note" rows="3"
                              class="wpim-input wpim-textarea"></textarea>
                </div>
            </div>

            <!-- ردیف ۷: پیوست‌ها -->
            <div class="wpim-row">
                <div class="wpim-col-full">
                    <label><strong>پیوست‌ها</strong></label>
                    <div class="wpim-attachments-field">
                        <label class="wpim-attach-btn">
                            پیوست فایل
                            <input type="file" id="wpim_attachments" name="wpim_attachments[]" multiple>
                        </label>
                        <span class="wpim-attach-hint">می‌توانید چند فایل را همزمان انتخاب کنید</span>
                    </div>
                    <div id="wpim_attachments_list" class="wpim-attachments-list"></div>
                </div>
            </div>

            <!-- ردیف ۸: دکمه‌ها -->
            <div class="wpim-row wpim-actions">
                <div class="wpim-actions-inner">
                    <button type="submit" class="wpim-btn wpim-btn-primary"
                            name="wpim_action" value="send">
                        ارسال پیام
                    </button>
                    <button type="submit" class="wpim-btn wpim-btn-secondary"
                            name="wpim_action" value="draft">
                        ذخیره پیش‌نویس
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>