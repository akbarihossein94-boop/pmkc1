<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Vars from WPIM_Admin::render_view_message_page():
 * @var WP_Post   $message
 * @var WP_User   $sender
 * @var array     $recipients
 * @var array     $cc
 * @var string    $sys_doc
 * @var string    $int_doc
 * @var string    $date
 * @var string    $type
 * @var string    $priority
 * @var string    $status
 * @var array     $attachments
 * @var string    $signature
 * @var string    $internal_note
 */

$current_user_id = get_current_user_id();
?>

<div class="wrap wpim-wrap">
    <h1 class="wpim-page-title">نمایش پیام</h1>

    <div class="wpim-card">
        <div class="wpim-row wpim-row-tight wpim-row-five">
            <div class="wpim-col">
                <div class="wpim-meta-card wpim-meta-equal">
                    <div class="wpim-meta-avatar">
                        <?php echo $sender ? get_avatar( $sender->ID, 32 ) : ''; ?>
                    </div>
                    <div class="wpim-meta-content">
                        <div class="wpim-meta-label">فرستنده</div>
                        <div class="wpim-meta-value">
                            <?php echo $sender ? esc_html( $sender->display_name ) : '—'; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="wpim-col">
                <div class="wpim-meta-card wpim-meta-equal">
                    <div class="wpim-meta-icon doc"></div>
                    <div class="wpim-meta-content">
                        <div class="wpim-meta-label">شماره سند سیستمی</div>
                        <div class="wpim-meta-value"><?php echo esc_html( $sys_doc ?: '—' ); ?></div>
                    </div>
                </div>
            </div>

            <div class="wpim-col">
                <div class="wpim-meta-card wpim-meta-equal">
                    <div class="wpim-meta-icon date"></div>
                    <div class="wpim-meta-content">
                        <div class="wpim-meta-label">تاریخ</div>
                        <div class="wpim-meta-value"><?php echo esc_html( $date ?: '—' ); ?></div>
                    </div>
                </div>
            </div>

            <div class="wpim-col">
                <div class="wpim-meta-card wpim-meta-equal">
                    <div class="wpim-meta-content">
                        <div class="wpim-meta-label">نوع پیام</div>
                        <div class="wpim-meta-value">
                            <span class="wpim-badge-type type-<?php echo esc_attr( $type ); ?>">
                                <?php echo $type === 'external' ? 'خارجی' : 'داخلی'; ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="wpim-col">
                <div class="wpim-meta-card wpim-meta-equal">
                    <div class="wpim-meta-content">
                        <div class="wpim-meta-label">اولویت</div>
                        <div class="wpim-meta-value">
                            <span class="wpim-badge-priority priority-<?php echo esc_attr( $priority ); ?>">
                                <?php
                                echo $priority === 'low'    ? 'کم' :
                                     ($priority === 'high'   ? 'مهم' :
                                     ($priority === 'urgent' ? 'فوری' : 'عادی'));
                                ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Subject -->
        <div class="wpim-row">
            <div class="wpim-col-full">
                <label><strong>عنوان پیام</strong></label>
                <div class="wpim-view-subject">
                    <?php echo esc_html( $message->post_title ?: 'بدون عنوان' ); ?>
                </div>
            </div>
        </div>

        <!-- Recipients / CC -->
        <div class="wpim-row">
            <div class="wpim-col">
                <label><strong>گیرندگان</strong></label>
                <div class="wpim-view-list">
                    <?php
                    if ( empty( $recipients ) ) {
                        echo '—';
                    } else {
                        $names = [];
                        foreach ( $recipients as $uid ) {
                            $u = get_user_by( 'id', $uid );
                            if ( $u ) {
                                $names[] = esc_html( $u->display_name );
                            }
                        }
                        echo $names ? implode( '، ', $names ) : '—';
                    }
                    ?>
                </div>
            </div>

            <div class="wpim-col">
                <label><strong>رونوشت (CC)</strong></label>
                <div class="wpim-view-list">
                    <?php
                    if ( empty( $cc ) ) {
                        echo '—';
                    } else {
                        $names = [];
                        foreach ( $cc as $uid ) {
                            $u = get_user_by( 'id', $uid );
                            if ( $u ) {
                                $names[] = esc_html( $u->display_name );
                            }
                        }
                        echo $names ? implode( '، ', $names ) : '—';
                    }
                    ?>
                </div>
            </div>
        </div>

        <!-- Message body -->
        <div class="wpim-row">
            <div class="wpim-col-full">
                <label><strong>متن پیام</strong></label>
                <div class="wpim-view-body">
                    <?php echo apply_filters( 'the_content', $message->post_content ); ?>
                </div>
            </div>
        </div>

        <!-- Signature + Internal note -->
        <div class="wpim-row">
            <div class="wpim-col">
                <label><strong>امضاء</strong></label>
                <div class="wpim-view-box">
                    <?php echo $signature ? wp_kses_post( nl2br( $signature ) ) : '—'; ?>
                </div>
            </div>
            <div class="wpim-col">
                <label><strong>یادداشت داخلی</strong></label>
                <div class="wpim-view-box">
                    <?php echo $internal_note ? wp_kses_post( nl2br( $internal_note ) ) : '—'; ?>
                </div>
            </div>
        </div>

        <!-- Attachments -->
        <div class="wpim-row">
            <div class="wpim-col-full">
                <label><strong>پیوست‌ها</strong></label>
                <div class="wpim-view-list">
                    <?php
                    if ( empty( $attachments ) ) {
                        echo '—';
                    } else {
                        echo '<ul class="wpim-attachment-list-view">';
                        foreach ( $attachments as $att_id ) {
                            $url  = wp_get_attachment_url( $att_id );
                            $name = get_the_title( $att_id );
                            if ( $url ) {
                                echo '<li><a href="' . esc_url( $url ) . '" target="_blank" rel="noreferrer">' .
                                     esc_html( $name ) . '</a></li>';
                            }
                        }
                        echo '</ul>';
                    }
                    ?>
                </div>
            </div>
        </div>

        <!-- Status -->
        <div class="wpim-row">
            <div class="wpim-col-full">
                <label><strong>وضعیت پیام</strong></label>
                <div class="wpim-view-status">
                    <span class="wpim-badge-status status-<?php echo esc_attr( $status ); ?>">
                        <?php
                        $status_labels = [
                            'none'      => 'بدون اقدام',
                            'viewed'    => 'مشاهده شده',
                            'actioned'  => 'اقدام شده',
                            'forwarded' => 'ارجاع شده',
                            'followup'  => 'در حال پیگیری',
                            'archived'  => 'بایگانی شده',
                        ];
                        echo esc_html( $status_labels[ $status ] ?? $status_labels['none'] );
                        ?>
                    </span>
                </div>
            </div>
        </div>

        <div class="wpim-row wpim-actions">
            <div class="wpim-actions-inner">
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=wpim_messages' ) ); ?>"
                   class="wpim-btn wpim-btn-secondary">بازگشت به صندوق دریافت</a>
            </div>
        </div>
    </div>
</div>