<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/** @var WP_Post[] $messages */
/** @var string $search */
/** @var string $filter */
/** @var string $status */

$current_user_id = get_current_user_id();

$status_labels = [
    'none'        => 'بدون اقدام',
    'viewed'      => 'مشاهده شده',
    'actioned'    => 'اقدام شده',
    'forwarded'   => 'ارجاع شده',
    'followup'    => 'در حال پیگیری',
    'archived'    => 'بایگانی شده',
];
?>

<div class="wrap wpim-wrap">
    <h1 class="wpim-page-title">صندوق دریافت</h1>

    <div class="wpim-card">
        <!-- Filters / Search / Bulk actions -->
        <form method="get" class="wpim-inbox-filters">
            <input type="hidden" name="page" value="wpim_messages">

            <div class="wpim-row wpim-row-tight">
                <div class="wpim-col">
                    <input type="text" name="wpim_search" value="<?php echo esc_attr( $search ); ?>"
                           class="wpim-input"
                           placeholder="جستجو در عنوان و متن پیام...">
                </div>

                <div class="wpim-col">
                    <div class="wpim-chip-filter-group">
                        <label>
                            <input type="radio" name="wpim_filter" value="all"
                                   <?php checked( $filter, 'all' ); ?>>
                            <span class="chip">همه پیام‌ها</span>
                        </label>
                        <label>
                            <input type="radio" name="wpim_filter" value="unread"
                                   <?php checked( $filter, 'unread' ); ?>>
                            <span class="chip">خوانده نشده</span>
                        </label>
                        <label>
                            <input type="radio" name="wpim_filter" value="copied"
                                   <?php checked( $filter, 'copied' ); ?>>
                            <span class="chip">پیام‌های رونوشت</span>
                        </label>
                        <label>
                            <input type="radio" name="wpim_filter" value="forwarded"
                                   <?php checked( $filter, 'forwarded' ); ?>>
                            <span class="chip">پیام‌های ارجاع شده</span>
                        </label>
                    </div>
                </div>

                <div class="wpim-col">
                    <select name="wpim_status_filter" class="wpim-input">
                        <option value="all" <?php selected( $status, 'all' ); ?>>همه وضعیت‌ها</option>
                        <?php foreach ( $status_labels as $key => $lbl ) : ?>
                            <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $status, $key ); ?>>
                                <?php echo esc_html( $lbl ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="wpim-col">
                    <button type="submit" class="wpim-btn wpim-btn-secondary">
                        اعمال فیلتر
                    </button>
                </div>
            </div>

            <!-- Group actions -->
            <div class="wpim-row wpim-row-tight">
                <div class="wpim-col-full">
                    <div class="wpim-bulk-actions">
                        <select name="wpim_bulk_action" class="wpim-input wpim-input-small">
                            <option value="">اقدام گروهی...</option>
                            <option value="mark_viewed">علامت به عنوان مشاهده شده</option>
                            <option value="mark_unread">علامت به عنوان خوانده نشده</option>
                            <option value="set_followup">علامت به عنوان در حال پیگیری</option>
                            <option value="archive">بایگانی</option>
                            <option value="delete">حذف</option>
                        </select>
                        <button type="submit" class="wpim-btn wpim-btn-secondary wpim-bulk-btn">
                            اجرا
                        </button>
                    </div>
                </div>
            </div>
        </form>

        <!-- Message list -->
        <div class="wpim-inbox-table">
            <div class="wpim-inbox-header">
                <div class="cell select-col">
                    <input type="checkbox" id="wpim_select_all">
                </div>
                <div class="cell star-pin-col"></div>
                <div class="cell subject-col">عنوان پیام</div>
                <div class="cell sender-col">فرستنده</div>
                <div class="cell cc-col">رونوشت</div>
                <div class="cell fwd-col">ارجاع</div>
                <div class="cell priority-col">اولویت</div>
                <div class="cell type-col">نوع</div>
                <div class="cell doc-col">شماره سند</div>
                <div class="cell attach-col">پیوست</div>
                <div class="cell date-col">تاریخ</div>
                <div class="cell status-col">وضعیت</div>
            </div>

            <?php if ( empty( $messages ) ) : ?>
                <div class="wpim-inbox-empty">
                    هیچ پیامی یافت نشد.
                </div>
            <?php else : ?>
                <form method="post" id="wpim-inbox-list-form">
                    <?php wp_nonce_field( 'wpim_inbox_bulk', 'wpim_inbox_bulk_nonce' ); ?>
                    <?php foreach ( $messages as $msg ) :
                        $msg_id     = $msg->ID;
                        $sender_id  = (int) get_post_meta( $msg_id, '_wpim_sender_id', true );
                        $sender     = $sender_id ? get_user_by( 'id', $sender_id ) : null;
                        $recipients = (array) get_post_meta( $msg_id, '_wpim_recipients', true );
                        $cc         = (array) get_post_meta( $msg_id, '_wpim_cc', true );
                        $sys_doc    = get_post_meta( $msg_id, '_wpim_system_doc_number', true );
                        $priority   = get_post_meta( $msg_id, '_wpim_priority', true ) ?: 'normal';
                        $type       = get_post_meta( $msg_id, '_wpim_message_type', true ) ?: 'internal';
                        $date       = get_post_meta( $msg_id, '_wpim_date', true ) ?: '';
                        $status_val = get_post_meta( $msg_id, '_wpim_message_status', true ) ?: 'none';
                        $atcs       = (array) get_post_meta( $msg_id, '_wpim_attachments', true );
                        $has_attach = ! empty( $atcs );

                        $read_meta  = get_post_meta( $msg_id, '_wpim_read_by_' . $current_user_id, true );
                        $unread     = empty( $read_meta );

                        $starred    = get_post_meta( $msg_id, '_wpim_starred_by_' . $current_user_id, true );
                        $pinned     = get_post_meta( $msg_id, '_wpim_pinned_by_' . $current_user_id, true );
                    ?>
                        <div class="wpim-inbox-row <?php echo $unread ? 'unread' : ''; ?>">
                            <div class="cell select-col">
                                <input type="checkbox" name="wpim_selected[]" value="<?php echo esc_attr( $msg_id ); ?>">
                            </div>

                            <div class="cell star-pin-col">
                                <button type="button" class="wpim-icon-btn star <?php echo $starred ? 'active' : ''; ?>"
                                        data-msg="<?php echo esc_attr( $msg_id ); ?>">★</button>
                                <button type="button" class="wpim-icon-btn pin <?php echo $pinned ? 'active' : ''; ?>"
                                        data-msg="<?php echo esc_attr( $msg_id ); ?>">📌</button>
                            </div>

                            <div class="cell subject-col">
                                <a href="<?php echo esc_url( admin_url( 'admin.php?page=wpim_view&id=' . $msg_id ) ); ?>"
                                   class="wpim-subject-link">
                                    <?php echo esc_html( $msg->post_title ?: 'بدون عنوان' ); ?>
                                </a>
                            </div>

                            <div class="cell sender-col">
                                <?php echo $sender ? esc_html( $sender->display_name ) : '—'; ?>
                            </div>

                            <div class="cell cc-col">
                                <?php echo ! empty( $cc ) ? count( $cc ) . ' نفر' : '—'; ?>
                            </div>

                            <div class="cell fwd-col">
                                <?php
                                $fwd = get_post_meta( $msg_id, '_wpim_forwarded', true );
                                echo $fwd ? '✔' : '—';
                                ?>
                            </div>

                            <div class="cell priority-col">
                                <span class="wpim-badge-priority priority-<?php echo esc_attr( $priority ); ?>">
                                    <?php
                                    echo $priority === 'low'    ? 'کم' :
                                         ($priority === 'high'   ? 'مهم' :
                                         ($priority === 'urgent' ? 'فوری' : 'عادی'));
                                    ?>
                                </span>
                            </div>

                            <div class="cell type-col">
                                <span class="wpim-badge-type type-<?php echo esc_attr( $type ); ?>">
                                    <?php echo $type === 'external' ? 'خارجی' : 'داخلی'; ?>
                                </span>
                            </div>

                            <div class="cell doc-col">
                                <?php echo $sys_doc ? esc_html( $sys_doc ) : '—'; ?>
                            </div>

                            <div class="cell attach-col">
                                <?php echo $has_attach ? '📎' : '—'; ?>
                            </div>

                            <div class="cell date-col">
                                <?php echo esc_html( $date ); ?>
                            </div>

                            <div class="cell status-col">
                                <span class="wpim-badge-status status-<?php echo esc_attr( $status_val ); ?>">
                                    <?php echo esc_html( $status_labels[ $status_val ] ?? $status_labels['none'] ); ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>