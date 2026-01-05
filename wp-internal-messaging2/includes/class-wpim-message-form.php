<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WPIM_Message_Form {

    public function handle_post() {
        if ( ! isset( $_POST['wpim_message_submit'] ) ) {
            return;
        }

        check_admin_referer( 'wpim_create_message', 'wpim_nonce' );

        // Later: save to custom table or CPT
        // Detect if this was "send" or "draft"
        $action = sanitize_text_field( $_POST['wpim_action'] ?? 'draft' );

        // For now, just a placeholder message
        if ( $action === 'send' ) {
            add_settings_error( 'wpim_messages', 'sent', __( 'Message sent (not actually saved yet).', 'wpim' ), 'updated' );
        } else {
            add_settings_error( 'wpim_messages', 'draft', __( 'Draft saved (not actually saved yet).', 'wpim' ), 'updated' );
        }
    }

    public function render() {
        settings_errors( 'wpim_messages' );

        $current_user  = wp_get_current_user();
        $today         = current_time( 'Y-m-d' );
        $system_number = $this->generate_system_doc_number();

        ?>
        <div class="wrap wpim-wrap">
            <h1><?php esc_html_e( 'Create Message', 'wpim' ); ?></h1>

            <form method="post" enctype="multipart/form-data" id="wpim-create-message-form">
                <?php wp_nonce_field( 'wpim_create_message', 'wpim_nonce' ); ?>

                <!-- 1. Subject + Internal document number -->
                <div class="wpim-form-row">
                    <div class="wpim-form-col">
                        <label for="wpim_subject"><?php esc_html_e( 'Subject', 'wpim' ); ?></label>
                        <input type="text" name="wpim_subject" id="wpim_subject" class="regular-text" required />
                    </div>

                    <div class="wpim-form-col">
                        <label for="wpim_internal_doc_no"><?php esc_html_e( 'Internal Document No.', 'wpim' ); ?></label>
                        <input type="text" name="wpim_internal_doc_no" id="wpim_internal_doc_no" class="regular-text" />
                    </div>
                </div>

                <!-- 2. Sender + Message type + Priority + System doc number + Date -->
                <div class="wpim-form-row">
                    <div class="wpim-form-col">
                        <label><?php esc_html_e( 'Sender', 'wpim' ); ?></label>
                        <input type="text" value="<?php echo esc_attr( $current_user->display_name ); ?>" disabled />
                        <input type="hidden" name="wpim_sender_id" value="<?php echo esc_attr( $current_user->ID ); ?>" />
                    </div>

                    <div class="wpim-form-col">
                        <label for="wpim_message_type"><?php esc_html_e( 'Message Type', 'wpim' ); ?></label>
                        <select name="wpim_message_type" id="wpim_message_type" class="wpim-colored-select">
                            <option value="internal"><?php esc_html_e( 'Internal', 'wpim' ); ?></option>
                            <option value="external"><?php esc_html_e( 'External', 'wpim' ); ?></option>
                        </select>
                    </div>

                    <div class="wpim-form-col">
                        <label for="wpim_priority"><?php esc_html_e( 'Priority', 'wpim' ); ?></label>
                        <select name="wpim_priority" id="wpim_priority" class="wpim-colored-select">
                            <option value="low"><?php esc_html_e( 'Low', 'wpim' ); ?></option>
                            <option value="normal" selected><?php esc_html_e( 'Normal', 'wpim' ); ?></option>
                            <option value="high"><?php esc_html_e( 'High', 'wpim' ); ?></option>
                            <option value="urgent"><?php esc_html_e( 'Urgent', 'wpim' ); ?></option>
                        </select>
                    </div>

                    <div class="wpim-form-col">
                        <label for="wpim_system_doc_no"><?php esc_html_e( 'System Document No.', 'wpim' ); ?></label>
                        <input type="text" name="wpim_system_doc_no" id="wpim_system_doc_no"
                               value="<?php echo esc_attr( $system_number ); ?>" readonly />
                    </div>

                    <div class="wpim-form-col">
                        <label for="wpim_date"><?php esc_html_e( 'Date', 'wpim' ); ?></label>
                        <input type="date" name="wpim_date" id="wpim_date" value="<?php echo esc_attr( $today ); ?>" />
                    </div>
                </div>

                <!-- 3. Recipients/CC as AJAX list -->
                <div class="wpim-form-row">
                    <div class="wpim-form-col">
                        <label for="wpim_recipients"><?php esc_html_e( 'Recipients', 'wpim' ); ?></label>
                        <select name="wpim_recipients[]" id="wpim_recipients" multiple class="wpim-user-select">
                            <!-- Populated via AJAX (Select2-style) -->
                        </select>
                    </div>

                    <div class="wpim-form-col">
                        <label for="wpim_cc"><?php esc_html_e( 'CC', 'wpim' ); ?></label>
                        <select name="wpim_cc[]" id="wpim_cc" multiple class="wpim-user-select">
                            <!-- Populated via AJAX -->
                        </select>
                    </div>
                </div>

                <!-- 4. Notifications (email / SMS) -->
                <div class="wpim-form-row">
                    <div class="wpim-form-col">
                        <label><?php esc_html_e( 'Email Notification', 'wpim' ); ?></label>
                        <label><input type="checkbox" name="wpim_notify_email" value="1" checked /> <?php esc_html_e( 'Send email notification', 'wpim' ); ?></label>
                    </div>
                    <div class="wpim-form-col">
                        <label><?php esc_html_e( 'SMS Notification', 'wpim' ); ?></label>
                        <label><input type="checkbox" name="wpim_notify_sms" value="1" /> <?php esc_html_e( 'Send SMS notification', 'wpim' ); ?></label>
                    </div>
                </div>

                <!-- 5. Message text (WordPress editor) -->
                <div class="wpim-form-row">
                    <div class="wpim-form-col-full">
                        <label for="wpim_message_content"><?php esc_html_e( 'Message', 'wpim' ); ?></label>
                        <?php
                        $content   = '';
                        $editor_id = 'wpim_message_content';
                        $settings  = [
                            'textarea_name' => 'wpim_message_content',
                            'media_buttons' => true,
                            'editor_height' => 200,
                        ];
                        wp_editor( $content, $editor_id, $settings );
                        ?>
                    </div>
                </div>

                <!-- 6. Signature + Internal note -->
                <div class="wpim-form-row">
                    <div class="wpim-form-col">
                        <label for="wpim_signature"><?php esc_html_e( 'Signature', 'wpim' ); ?></label>
                        <textarea name="wpim_signature" id="wpim_signature" rows="3" class="large-text"></textarea>
                    </div>
                    <div class="wpim-form-col">
                        <label for="wpim_internal_note"><?php esc_html_e( 'Internal Note', 'wpim' ); ?></label>
                        <textarea name="wpim_internal_note" id="wpim_internal_note" rows="3" class="large-text"></textarea>
                    </div>
                </div>

                <!-- 7. Attachments -->
                <div class="wpim-form-row">
                    <div class="wpim-form-col-full">
                        <label for="wpim_attachments"><?php esc_html_e( 'Attachments', 'wpim' ); ?></label>
                        <input type="file" name="wpim_attachments[]" id="wpim_attachments" multiple />
                        <div id="wpim-attachment-list"></div>
                        <div id="wpim-upload-progress" class="wpim-progress-wrapper" style="display:none;">
                            <div class="wpim-progress-bar"></div>
                        </div>
                    </div>
                </div>

                <!-- 8. Send / Save draft -->
                <div class="wpim-form-row wpim-form-actions">
                    <input type="hidden" name="wpim_action" id="wpim_action" value="draft" />
                    <button type="submit" class="button button-primary" onclick="document.getElementById('wpim_action').value='send';">
                        <?php esc_html_e( 'Send Message', 'wpim' ); ?>
                    </button>
                    <button type="submit" class="button" onclick="document.getElementById('wpim_action').value='draft';">
                        <?php esc_html_e( 'Save as Draft', 'wpim' ); ?>
                    </button>
                </div>

            </form>
        </div>
        <?php
    }

    /**
     * System document number: YYYYMMDD + sequential N per day
     * For now, we just return YYYYMMDD-001 as a placeholder.
     * Later you can query DB to find last number for that day.
     */
    protected function generate_system_doc_number() {
        $date = current_time( 'Ymd' );
        // TODO: read last N from DB and increment.
        return $date . '-001';
    }
}