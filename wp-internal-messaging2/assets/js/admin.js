jQuery(function($) {

    /* ---------- Toast on successful send ---------- */

    (function initToast() {
        var $wrap = $('.wpim-wrap');
        if (!$wrap.length) return;

        var success = $wrap.data('wpim-success') === 1 || $wrap.data('wpim-success') === '1';
        if (success) {
            var $toast = $('#wpim-toast');
            if ($toast.length) {
                $toast.fadeIn(200);
                setTimeout(function() {
                    $toast.fadeOut(300);
                }, 3500);
            }
        }
    })();

    /* ---------- Chips: Message Type & Priority ---------- */

    $('.wpim-chip-group').each(function () {
        var $group = $(this);
        var targetSelector = $group.data('target-input');
        var $hidden = $(targetSelector);

        $group.on('click', '.wpim-chip', function () {
            var $btn = $(this);
            var val  = $btn.data('value');

            $group.find('.wpim-chip').removeClass('active');
            $btn.addClass('active');

            if ($hidden.length) {
                $hidden.val(val);
            }
        });
    });

    /* ---------- Multi-select users (Recipients / CC) ---------- */

    function initMultiSelect(displaySelector, listSelector, tokensSelector, hiddenSelector) {
        var $display = $(displaySelector);
        var $dropdown = $(listSelector);
        var $tokens = $(tokensSelector);
        var $hidden = $(hiddenSelector);
        var $search = $dropdown.find('.wpim-select-search');
        var $optionsContainer = $dropdown.find('.wpim-select-options');

        var selectedUsers = [];

        function refreshHidden() {
            var ids = selectedUsers.map(function(u){ return u.id; });
            $hidden.val(ids.join(','));
        }

        function renderTokens() {
            $tokens.empty();
            selectedUsers.forEach(function(user) {
                var $token  = $('<span class="wpim-token"></span>').text(user.label);
                var $remove = $('<span class="wpim-token-remove">&#10005;</span>');
                $remove.on('click', function() {
                    selectedUsers = selectedUsers.filter(function(u){ return u.id !== user.id; });
                    renderTokens();
                    refreshHidden();
                    $optionsContainer.find('.wpim-select-option[data-user-id="' + user.id + '"]').removeClass('selected');
                    updateDisplayPlaceholder();
                });
                $token.append($remove);
                $tokens.append($token);
            });
        }

        function updateDisplayPlaceholder() {
            var $ph = $display.find('.placeholder');
            if (selectedUsers.length) {
                $ph.text(selectedUsers.length + ' کاربر انتخاب شده');
            } else {
                $ph.text($display.data('placeholder') || 'انتخاب...');
            }
        }

        function toggleUserOption($opt) {
            var id    = parseInt($opt.data('user-id'), 10);
            var label = $opt.data('user-label');
            var idx   = selectedUsers.findIndex(function(u){ return u.id === id; });

            if (idx > -1) {
                selectedUsers.splice(idx, 1);
                $opt.removeClass('selected');
            } else {
                selectedUsers.push({ id: id, label: label });
                $opt.addClass('selected');
            }

            renderTokens();
            refreshHidden();
            updateDisplayPlaceholder();
        }

        $display.on('click', function () {
            $('.wpim-select-dropdown').not($dropdown).removeClass('open');
            $dropdown.toggleClass('open');
            if ($dropdown.hasClass('open')) {
                $search.val('');
                $search.trigger('keyup');
                $search.focus();
            }
        });

        $search.on('keyup', function () {
            var term = $(this).val().toLowerCase();
            $optionsContainer.find('.wpim-select-option').each(function () {
                var text = $(this).text().toLowerCase();
                $(this).toggle(!term || text.indexOf(term) !== -1);
            });
        });

        $optionsContainer.on('click', '.wpim-select-option', function () {
            toggleUserOption($(this));
        });

        $(document).on('click', function (e) {
            if (!$(e.target).closest('.wpim-select-field').length) {
                $dropdown.removeClass('open');
            }
        });

        $display.data('placeholder', $display.find('.placeholder').text());
    }

    initMultiSelect('#wpim_recipients_display', '#wpim_recipients_list',
                    '#wpim_recipients_tokens', '#wpim_recipients');

    initMultiSelect('#wpim_cc_display', '#wpim_cc_list',
                    '#wpim_cc_tokens', '#wpim_cc');

    /* ---------- Attachments ---------- */

    var allFiles = [];

    function rebuildFileInput() {
        var dt = new DataTransfer();
        allFiles.forEach(function(file) {
            dt.items.add(file);
        });
        var input = document.getElementById('wpim_attachments');
        input.files = dt.files;
    }

    function renderAttachmentList() {
        var $list = $('#wpim_attachments_list');
        $list.empty();

        if (!allFiles.length) {
            return;
        }

        allFiles.forEach(function(file, index) {
            var sizeKB = Math.round(file.size / 1024);
            var $item  = $('<div class="wpim-attachment-item"></div>');
            var $name  = $('<span class="name"></span>').text(file.name + ' (' + sizeKB + ' کیلوبایت)');
            var $del   = $('<button type="button" class="wpim-attachment-delete">حذف</button>');

            $del.on('click', function() {
                allFiles.splice(index, 1);
                rebuildFileInput();
                renderAttachmentList();
            });

            $item.append($name).append($del);
            $list.append($item);
        });
    }

    $('#wpim_attachments').on('change', function () {
        var files = this.files;
        if (!files || !files.length) {
            return;
        }

        for (var i = 0; i < files.length; i++) {
            allFiles.push(files[i]);
        }

        rebuildFileInput();
        renderAttachmentList();
    });

    /* ---------- Tags ---------- */

    var tags = [];

    function refreshTagsHidden() {
        $('#wpim_tags').val(tags.join(','));
    }

    function renderTagsTokens() {
        var $tokens = $('#wpim_tags_tokens');
        $tokens.empty();
        tags.forEach(function(tag) {
            var $token  = $('<span class="wpim-tag-token"></span>').text(tag);
            var $remove = $('<span class="wpim-tag-remove">&#10005;</span>');
            $remove.on('click', function() {
                tags = tags.filter(function(t) { return t !== tag; });
                renderTagsTokens();
                refreshTagsHidden();
            });
            $token.append($remove);
            $tokens.append($token);
        });
    }

    $('#wpim_tag_input').on('keydown', function(e) {
        if (e.key === 'Enter' || e.key === ',') {
            e.preventDefault();
            var val = $(this).val().trim();
            if (val && tags.indexOf(val) === -1) {
                tags.push(val);
                renderTagsTokens();
                refreshTagsHidden();
            }
            $(this).val('');
        }
    });

    /* ---------- Notifications: toggle .active ---------- */

    $('.wpim-notify-card input[type="checkbox"]').each(function () {
        var $input = $(this);
        var $card  = $input.closest('.wpim-notify-card');

        function syncCard() {
            $card.toggleClass('active', $input.is(':checked'));
        }

        $input.on('change', syncCard);
        syncCard();
    });

    /* ---------- Ajax-style validation (subject + recipients + message text) ---------- */

    $('#wpim-create-message-form').on('submit', function(e) {
        var errors = [];

        var subject = $('#wpim_subject').val().trim();
        var recipients = $('#wpim_recipients').val().trim();

        var messageBody = '';
        if (typeof tinymce !== 'undefined' && tinymce.get('wpim_message_body')) {
            messageBody = tinymce.get('wpim_message_body').getContent({ format: 'text' }).trim();
        } else {
            messageBody = $('#wpim_message_body').val().trim();
        }

        if (!subject) {
            errors.push('عنوان پیام را وارد کنید.');
            $('#wpim_subject').addClass('wpim-field-error');
        } else {
            $('#wpim_subject').removeClass('wpim-field-error');
        }

        if (!recipients) {
            errors.push('حداقل یک گیرنده انتخاب کنید.');
            $('#wpim_recipients_display').addClass('wpim-field-error');
        } else {
            $('#wpim_recipients_display').removeClass('wpim-field-error');
        }

        if (!messageBody) {
            errors.push('متن پیام را وارد کنید.');
            $('.wpim-editor-wrapper').addClass('wpim-field-error');
        } else {
            $('.wpim-editor-wrapper').removeClass('wpim-field-error');
        }

        if (errors.length) {
            e.preventDefault();
            var $errorBox = $('#wpim-form-errors');
            $errorBox.html(
                '<ul>' + errors.map(function(err) {
                    return '<li>' + err + '</li>';
                }).join('') + '</ul>'
            );
            $errorBox.fadeIn(200);

            $('html, body').animate({
                scrollTop: $errorBox.offset().top - 120
            }, 300);
        } else {
            $('#wpim-form-errors').hide();
        }
    });

});