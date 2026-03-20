(function($) {
    'use strict';

    var RFC = {
        init: function() {
            this.bindToggles();
            this.bindCacheButtons();
            this.bindDbCleanup();
            this.bindImportExport();
            this.bindResetSettings();
            this.bindAdminBar();
            this.bindDismissNotices();
            this.bindFormValidation();
            this.bindSelectAll();
            this.bindTrialForm();
            this.bindLicenseForm();
        },

        ajax: function(action, data, callback) {
            var payload = $.extend({
                action: action,
                nonce: rfcAdmin.nonce
            }, data || {});

            $.ajax({
                url: rfcAdmin.ajaxurl,
                type: 'POST',
                data: payload,
                success: function(response) {
                    if (callback) callback(response);
                },
                error: function() {
                    RFC.showNotice('error', rfcAdmin.strings.error);
                }
            });
        },

        showNotice: function(type, message) {
            var icon = type === 'success' ? '&#10003;' : type === 'error' ? '&#10007;' : '&#9888;';
            var $toast = $('<div class="rfc-toast rfc-toast-' + type + '"><span style="margin-right:8px;font-size:16px">' + icon + '</span>' + message + '</div>');
            $('body').append($toast);
            setTimeout(function() {
                $toast.addClass('rfc-toast-hiding');
                setTimeout(function() { $toast.remove(); }, 300);
            }, 3500);
        },

        bindToggles: function() {
            $(document).on('change', '.rfc-toggle input[data-ajax-toggle]', function() {
                var $input = $(this);
                var key = $input.data('ajax-toggle');
                var value = $input.is(':checked') ? 1 : 0;

                RFC.ajax('rfc_toggle_setting', { key: key, value: value }, function(response) {
                    if (response.success) {
                        RFC.showNotice('success', rfcAdmin.strings.saved);
                    } else {
                        RFC.showNotice('error', response.data.message || rfcAdmin.strings.error);
                        $input.prop('checked', !$input.is(':checked'));
                    }
                });
            });
        },

        bindCacheButtons: function() {
            $(document).on('click', '[data-action="rfc_purge_all"]', function(e) {
                e.preventDefault();
                var $btn = $(this);

                if (!confirm(rfcAdmin.strings.confirm_clear)) return;

                $btn.prop('disabled', true).text(rfcAdmin.strings.clearing);

                RFC.ajax('rfc_purge_all', {}, function(response) {
                    $btn.prop('disabled', false).text(rfcAdmin.strings.cleared);
                    if (response.success) {
                        RFC.showNotice('success', response.data.message);
                    }
                    setTimeout(function() { $btn.text('Clear All Cache'); }, 2000);
                });
            });

            $(document).on('click', '[data-action="rfc_preload"]', function(e) {
                e.preventDefault();
                var $btn = $(this);
                $btn.prop('disabled', true).text(rfcAdmin.strings.preloading);

                RFC.ajax('rfc_preload', {}, function(response) {
                    $btn.prop('disabled', false).text(rfcAdmin.strings.preloaded);
                    if (response.success) {
                        RFC.showNotice('success', response.data.message);
                    }
                    setTimeout(function() { $btn.text('Preload Cache'); }, 2000);
                });
            });

            $(document).on('click', '[data-action="rfc_db_cleanup"]', function(e) {
                e.preventDefault();
                var $btn = $(this);
                $btn.prop('disabled', true).text(rfcAdmin.strings.cleaning);

                RFC.ajax('rfc_quick_db_cleanup', {}, function(response) {
                    $btn.prop('disabled', false).text(rfcAdmin.strings.cleaned);
                    if (response.success) {
                        RFC.showNotice('success', response.data.message);
                    }
                    setTimeout(function() { $btn.text('Run DB Cleanup'); }, 2000);
                });
            });
        },

        bindDbCleanup: function() {
            $('#rfc-db-clean-btn').on('click', function() {
                var tasks = [];
                $('input[name="rfc_db_tasks[]"]:checked').each(function() {
                    tasks.push($(this).val());
                });

                if (tasks.length === 0) return;

                var $btn = $(this);
                var $results = $('#rfc-db-results');
                var $progress = $('#rfc-db-progress');
                var $list = $('#rfc-db-result-list');

                $btn.prop('disabled', true).text(rfcAdmin.strings.cleaning);
                $results.show();
                $list.empty();
                $progress.css('width', '0%');

                var completed = 0;
                var total = tasks.length;

                tasks.forEach(function(task) {
                    RFC.ajax('rfc_db_cleanup_task', { task: task }, function(response) {
                        completed++;
                        var pct = Math.round((completed / total) * 100);
                        $progress.css('width', pct + '%');

                        var msg = response.success ? response.data.message : 'Failed: ' + task;
                        $list.append('<li>' + msg + '</li>');

                        if (completed === total) {
                            $btn.prop('disabled', false).text(rfcAdmin.strings.cleaned);
                            setTimeout(function() { $btn.text('Clean Selected'); }, 3000);
                        }
                    });
                });
            });

            $('#rfc-db-optimize-btn').on('click', function() {
                var $btn = $(this);
                $btn.prop('disabled', true);

                RFC.ajax('rfc_db_optimize', {}, function(response) {
                    $btn.prop('disabled', false);
                    if (response.success) {
                        RFC.showNotice('success', response.data.message);
                    }
                });
            });
        },

        bindImportExport: function() {
            $('#rfc-export-btn').on('click', function() {
                RFC.ajax('rfc_export_settings', {}, function(response) {
                    if (!response.success) return;

                    var blob = new Blob([response.data.json], { type: 'application/json' });
                    var url = URL.createObjectURL(blob);
                    var a = document.createElement('a');
                    a.href = url;
                    a.download = 'rocketfuel-cache-settings.json';
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    URL.revokeObjectURL(url);
                });
            });

            $('#rfc-import-btn').on('click', function() {
                var fileInput = document.getElementById('rfc-import-file');
                if (!fileInput.files.length) {
                    RFC.showNotice('error', 'Please select a JSON file first.');
                    return;
                }

                var reader = new FileReader();
                reader.onload = function(e) {
                    RFC.ajax('rfc_import_settings', { settings_json: e.target.result }, function(response) {
                        if (response.success) {
                            RFC.showNotice('success', response.data.message);
                            setTimeout(function() { location.reload(); }, 1500);
                        } else {
                            RFC.showNotice('error', response.data.message);
                        }
                    });
                };
                reader.readAsText(fileInput.files[0]);
            });
        },

        bindResetSettings: function() {
            $('#rfc-reset-btn').on('click', function() {
                if (!confirm(rfcAdmin.strings.confirm_reset)) return;
                if (!confirm(rfcAdmin.strings.confirm_reset2)) return;

                RFC.ajax('rfc_reset_settings', {}, function(response) {
                    if (response.success) {
                        RFC.showNotice('success', response.data.message);
                        setTimeout(function() { location.reload(); }, 1500);
                    }
                });
            });
        },

        bindAdminBar: function() {
            $(document).on('click', '#wp-admin-bar-rfc-clear-all a', function(e) {
                e.preventDefault();
                RFC.ajax('rfc_purge_all', {}, function(response) {
                    if (response.success) {
                        RFC.showAdminBarNotice(response.data.message);
                    }
                });
            });

            $(document).on('click', '#wp-admin-bar-rfc-clear-page a', function(e) {
                e.preventDefault();
                RFC.ajax('rfc_purge_page', { url: window.location.href }, function(response) {
                    if (response.success) {
                        RFC.showAdminBarNotice(response.data.message);
                    }
                });
            });

            $(document).on('click', '#wp-admin-bar-rfc-clear-minified a', function(e) {
                e.preventDefault();
                RFC.ajax('rfc_purge_minified', {}, function(response) {
                    if (response.success) {
                        RFC.showAdminBarNotice(response.data.message);
                    }
                });
            });

            $(document).on('click', '#wp-admin-bar-rfc-preload a', function(e) {
                e.preventDefault();
                RFC.ajax('rfc_preload', {}, function(response) {
                    if (response.success) {
                        RFC.showAdminBarNotice(response.data.message);
                    }
                });
            });

            $(document).on('click', '#wp-admin-bar-rfc-safe-mode a', function(e) {
                e.preventDefault();
                RFC.ajax('rfc_toggle_safe_mode', {}, function(response) {
                    if (response.success) {
                        RFC.showAdminBarNotice(response.data.message);
                        setTimeout(function() { location.reload(); }, 1000);
                    }
                });
            });
        },

        showAdminBarNotice: function(message) {
            var $msg = $('<div style="position:fixed;top:32px;left:50%;transform:translateX(-50%);z-index:999999;background:#1a1a2e;color:#e2e2f0;padding:10px 24px;border-radius:4px;border:1px solid #00e676;font-size:13px;box-shadow:0 4px 12px rgba(0,0,0,0.3);">' + message + '</div>');
            $('body').append($msg);
            setTimeout(function() { $msg.fadeOut(300, function() { $(this).remove(); }); }, 3000);
        },

        bindDismissNotices: function() {
            $(document).on('click', '.rfc-notice .notice-dismiss', function() {
                $(this).closest('.rfc-notice').slideUp(200, function() {
                    $(this).remove();
                });
            });
        },

        bindFormValidation: function() {
            $(document).on('submit', 'form:has([name="rfc_save_settings"])', function() {
                var $lifespan = $('[name="rfc[cache_lifespan]"]');
                if ($lifespan.length && $lifespan.val() !== '') {
                    var val = parseInt($lifespan.val(), 10);
                    if (isNaN(val) || val < 0) {
                        $lifespan.focus();
                        return false;
                    }
                }

                var $frequency = $('[name="rfc[heartbeat_frequency]"]');
                if ($frequency.length && $frequency.val() !== '') {
                    var freq = parseInt($frequency.val(), 10);
                    if (isNaN(freq) || freq < 15) {
                        $frequency.val(15).focus();
                        return false;
                    }
                }

                return true;
            });
        },

        bindSelectAll: function() {
            $('#rfc-db-select-all').on('change', function() {
                var checked = $(this).is(':checked');
                $('input[name="rfc_db_tasks[]"]:not(:disabled)').prop('checked', checked);
            });
        },

        bindTrialForm: function() {
            $(document).on('submit', '#rfc-trial-form', function(e) {
                e.preventDefault();
                var email = $('#rfc-trial-email').val();
                if (!email) return;

                RFC.ajax('rfc_activate_trial', { email: email }, function(response) {
                    if (response.success) {
                        RFC.showNotice('success', response.data.message);
                        setTimeout(function() { location.reload(); }, 1500);
                    } else {
                        RFC.showNotice('error', response.data.message);
                    }
                });
            });

            $(document).on('click', '#rfc-extend-trial-btn', function() {
                RFC.ajax('rfc_extend_trial', {}, function(response) {
                    if (response.success) {
                        RFC.showNotice('success', response.data.message);
                        setTimeout(function() { location.reload(); }, 1500);
                    } else {
                        RFC.showNotice('error', response.data.message);
                    }
                });
            });
        },

        bindLicenseForm: function() {
            $(document).on('submit', '#rfc-license-form', function(e) {
                e.preventDefault();
                var key = $('#rfc-license-key').val();
                if (!key) return;

                RFC.ajax('rfc_activate_license', { license_key: key }, function(response) {
                    if (response.success) {
                        RFC.showNotice('success', response.data.message);
                        setTimeout(function() { location.reload(); }, 1500);
                    } else {
                        RFC.showNotice('error', response.data.message);
                    }
                });
            });

            $(document).on('submit', '#rfc-deactivate-license-form', function(e) {
                e.preventDefault();
                if (!confirm('Are you sure you want to deactivate your license?')) return;

                RFC.ajax('rfc_deactivate_license', {}, function(response) {
                    if (response.success) {
                        RFC.showNotice('success', response.data.message);
                        setTimeout(function() { location.reload(); }, 1500);
                    } else {
                        RFC.showNotice('error', response.data.message);
                    }
                });
            });
        }
    };

    RFC.toast = function(type, message, duration) {
        duration = duration || 3000;
        var $toast = $('<div class="rfc-toast rfc-toast-' + type + '">' + message + '</div>');
        $('body').append($toast);
        setTimeout(function() {
            $toast.addClass('rfc-toast-hiding');
            setTimeout(function() { $toast.remove(); }, 300);
        }, duration);
    };

    RFC.animateCounter = function($el, target) {
        var start = 0;
        var duration = 1200;
        var startTime = null;

        function step(timestamp) {
            if (!startTime) startTime = timestamp;
            var progress = Math.min((timestamp - startTime) / duration, 1);
            var eased = 1 - Math.pow(1 - progress, 3);
            var current = Math.floor(eased * target);
            $el.text(current.toLocaleString());
            if (progress < 1) {
                requestAnimationFrame(step);
            } else {
                $el.text(target.toLocaleString());
            }
        }

        requestAnimationFrame(step);
    };

    RFC.initCounters = function() {
        $('.rfc-stat-value[data-count]').each(function() {
            var $this = $(this);
            var target = parseInt($this.data('count'), 10);
            if (!isNaN(target) && !$this.data('counted')) {
                $this.data('counted', true);
                RFC.animateCounter($this, target);
            }
        });
    };

    RFC.initScoreRing = function() {
        $('.rfc-ring-fill').each(function() {
            var $ring = $(this);
            var score = parseInt($ring.data('score'), 10) || 0;
            var circumference = 339.292;
            var offset = circumference - (score / 100) * circumference;
            setTimeout(function() {
                $ring.css('stroke-dashoffset', offset);
            }, 300);
        });
    };

    $(document).ready(function() {
        RFC.init();
        RFC.initCounters();
        RFC.initScoreRing();
    });

})(jQuery);
