/* global ajax_object, tinyga_bulk_modal, tinyga_settings */

jQuery(document).ready(function($) {

    var tipsySettings =  {
        gravity: 'e',
        html: true,
        trigger: 'manual',
        className: function() {
            return 'tipsy-' + $(this).data('id');
        },
        title: function() {
            activeId = $(this).data('id');
            return $(this).attr('original-title');
        }
    };

    $('.tinygaWhatsThis').tipsy({
        fade: true,
        gravity: 'w'
    });

    $('.tinygaError').tipsy({
        fade: true,
        gravity: 'e'
    });

    var data = {
            action: 'tinyga_request'
        },

        errorTpl = '<div class="tinygaErrorWrap"><a class="tinygaError">Failed! Hover here</a></div>',
        $btnApplyBulkAction = $("#doaction"),
        $btnApplyBulkAction2 = $("#doaction2"),
        $topBulkActionDropdown = $(".tablenav.top .bulkactions select[name='action']"),
        $bottomBulkActionDropdown = $(".tablenav.bottom .bulkactions select[name='action2']");


    var requestSuccess = function(data) {
        var $button = $(this),
            $parent = $(this).closest('.tinyga-wrap, .buttonWrap'),
            $cell = $(this).closest("td");

        if (data.html) {
            $button.text("Image optimized");

            var originalSize = data.original_size,
                $originalSizeColumn = $(this).parent().prev("td.tinyga_original_size");

            $parent.fadeOut("fast", function() {
                $cell
                    .find(".noSavings, .tinygaErrorWrap")
                    .remove();
                $cell.html(data.html);
                $cell.find('.tinyga-item-details')
                    .tipsy(tipsySettings);
                $originalSizeColumn.html(originalSize);
                $parent.remove();
            });

        } else if (data.error) {

            var $error = $(errorTpl).attr("title", data.error);

            $parent
                .closest("td")
                .find(".tinygaErrorWrap")
                .remove();


            $parent.after($error);
            $error.tipsy({
                fade: true,
                gravity: 'e'
            });

            $button
                .text("Retry request")
                .removeAttr("disabled")
                .css({
                    opacity: 1
                });
        }
    };

    var requestFail = function() {
        $(this).removeAttr("disabled");
    };

    var requestComplete = function() {
        $(this).removeAttr("disabled");
        $(this)
            .parent()
            .find(".tinygaSpinner")
            .css("display", "none");
    };

    var opts = '<option value="tinyga-bulk-optimization">' + "Tinyga optimize all" + '</option>';

    $topBulkActionDropdown.find("option:last-child").before(opts);
    $bottomBulkActionDropdown.find("option:last-child").before(opts);


    var getBulkImageData = function() {
        var $rows = $("tr[id^='post-']"),
            $row = null,
            postId = 0,
            $optimizeBtn = null,
            btnData = {},
            originalSize = '',
            rv = [];
        $rows.each(function() {
            $row = $(this);
            postId = this.id.replace(/^\D+/g, '');
            if ($row.find("input[type='checkbox'][value='" + postId + "']:checked").length) {
                $optimizeBtn = $row.find(".tinyga_req");
                if ($optimizeBtn.length) {
                    btnData = $optimizeBtn.data();
                    originalSize = $.trim($row.find('td.tinyga_original_size').text());
                    btnData.originalSize = originalSize;
                    rv.push(btnData);
                }
            }
        });
        return rv;
    };

    var bulkModalOptions = {
        zIndex: 4,
        escapeClose: true,
        clickClose: false,
        closeText: 'close',
        showClose: false
    };

    var renderBulkImageSummary = function(bulkImageData) {
        var $modal = $(tinyga_bulk_modal['modal']);
        var $modal_row = $(tinyga_bulk_modal['modal_row']);
        var nImages = bulkImageData.length;

        $modal.find(".tinyga-modal-image-count").html(nImages);

        $modal
            .appendTo("body")
            .kmodal(bulkModalOptions)
            .bind($.kmodal.CLOSE, function() {
                $("#tinyga-bulk-modal").remove();
            })
            .css({
                top: "10px",
                marginTop: "40px"
            });

        // to prevent close on clicking overlay div
        $(".jquery-modal.blocker").on("click", function() {
            return false;
        });

        // otherwise media submenu shows through modal overlay
        $("#menu-media ul.wp-submenu").css({
            "z-index": 1
        });

        var $table = $modal.find("#tinyga-bulk");

        $.each(bulkImageData, function(index, element) {
            var $row = $modal_row.clone();
            $row.data('tinyga-bulk-id', element.id)
                .attr('data-tinyga-bulk-id', element.id);
            $row.find('.tinyga-bulk-filename').html(element.filename);
            $row.find('.tinyga-original-size').html(element.originalSize);
            $table.append($row);
        });

        $(".close-tinyga-bulk").on("click", function() {
            $.kmodal.close();
        });

        if (!nImages) {
            $(".tinyga_req_bulk")
                .attr("disabled", true)
                .css({
                    opacity: 0.5
                });
        }
    };


    var bulkAction = function(bulkImageData) {

        var $bulkTable = $("#tinyga-bulk");
        var jqxhr = null;

        var q = async.queue(function(task, callback) {
            var id = task.id;

            var $row = $bulkTable.find("tr[data-tinyga-bulk-id='" + id + "']"),
                $optimizedSizeColumn = $row.find(".tinyga-optimized-size"),
                $spinner = $optimizedSizeColumn.find(".tinygaBulkSpinner").css({display: "inline-block"}),
                $savingsPercentColumn = $row.find(".tinyga-savingsPercent"),
                $savingsBytesColumn = $row.find(".tinyga-savings");

            jqxhr = $.ajax({
                url: ajax_object.ajax_url,
                data: {
                    'action': 'tinyga_request',
                    'id': id,
                    'quality': $("#tinyga-bulk-quality option:selected").val(),
                    'origin': 'bulk_optimizer'
                },
                type: "post",
                dataType: "json",
                timeout: 360000
            })
            .done(function(data) {
                if (data.success && !data.message) {
                    var originalSize = data.original_size,
                        savingsPercent = data.savings_percent,
                        savingsBytes = data.saved_bytes;

                    $optimizedSizeColumn.html(data.html);

                    $optimizedSizeColumn
                        .find('.tinyga-item-details')
                        .remove();

                    $savingsPercentColumn.text(savingsPercent);
                    $savingsBytesColumn.text(savingsBytes);

                    var $button = $("button[id='tinygaid-" + id + "']"),
                        $parent = $button.parent(),
                        $cell = $button.closest("td"),
                        $originalSizeColumn = $button.parent().prev("td.tinyga_original_size");


                    $parent.fadeOut("fast", function() {
                        $cell.find(".noSavings, .tinygaErrorWrap").remove();
                        $cell
                            .empty()
                            .html(data.html);
                        $cell
                            .find('.tinyga-item-details')
                            .tipsy(tipsySettings);
                        $originalSizeColumn.html(originalSize);
                        $parent.remove();
                    });

                } else if (data.error) {
                    if (data.error === 'This image can not be optimized any further') {
                        $optimizedSizeColumn.text('No savings found.');
                    } else {

                    }
                }
            })
            .fail(function() {

            })
            .always(function() {
                $spinner.css({
                    display: "none"
                });
                callback();
            });
        }, tinyga_settings.bulk_async_limit);

        q.drain = function() {
            $(".tinyga_req_bulk")
                .removeAttr("disabled")
                .css({
                    opacity: 1
                })
                .text("Done")
                .unbind("click")
                .click(function() {
                    $.kmodal.close();
                });
        };

        // add some items to the queue (batch-wise)
        q.push(bulkImageData, function(err) {

        });
    };


    $btnApplyBulkAction.add($btnApplyBulkAction2)
        .click(function(e) {
            if ($(this).prev("select").val() === 'tinyga-bulk-optimization') {
                e.preventDefault();
                var bulkImageData = getBulkImageData();
                renderBulkImageSummary(bulkImageData);

                $('.tinyga_req_bulk').on("click", function(e) {
                    e.preventDefault();
                    $(this)
                        .attr("disabled", true)
                        .css({
                            opacity: 0.5
                        });
                    bulkAction(bulkImageData);
                });
            }
        });

    var activeId = null;
    $('.tinyga-item-details').tipsy(tipsySettings);

    var $body = $('body');

    $body.on('click', '.tinyga-item-details', function() {
        var id = $(this).data('id');
        $('.tipsy').remove();
        if (id === activeId) {
            activeId = null;
            $(this).text('Show details');
            return;
        }
        $('.tinyga-item-details').text('Show details');
        $(this).tipsy('show');
        $(this).text('Hide details');
    });

    $body.on('click', function(e) {
        var $t = $(e.target);

        if (($t.hasClass('tipsy') || $t.closest('.tipsy').length) || $t.hasClass('tinyga-item-details')) {
            return;
        }

        activeId = null;
        $('.tinyga-item-details').text('Show details');
        $('.tipsy').remove();
    });

    $body.on('click', 'small.tinygaReset', function(e) {
        e.preventDefault();
        var $resetButton = $(this);
        var resetData = {
            action: 'tinyga_reset'
        };

        resetData.id = $(this).data("id");
        var $row = $('#post-' + resetData.id).find('.tinyga_optimized_size');

        $resetButton.siblings(".tinygaSpinner").css({
            display: 'inline-block',
            position: 'relative'
        });

        $.ajax({
            url: ajax_object.ajax_url,
            data: resetData,
            type: "post",
            dataType: "json",
            timeout: 360000
        })
        .done(function(data) {
            if (data.success !== 'undefined') {
                $row
                    .hide()
                    .html(data.html)
                    .fadeIn()
                    .prev(".tinyga_original_size.column-tinyga_original_size")
                    .html(data.original_size);

                $('.tipsy').remove();
            }
        });
    });

    $body.on('click', '.tinyga-reset-all', function(e) {
        e.preventDefault();

        var reset = confirm('This will immediately remove all Tinyga metadata associated with your images. \n\nAre you sure you want to do this?');
        if (!reset) {
            return;
        }

        var $resetButton = $(this);
        $resetButton
            .text('Resetting images, pleaes wait...')
            .attr('disabled', true);
        var resetData = {
            action: 'tinyga_reset_all'
        };


        var $spinner = $('<span class="resetSpinner"></span>');
        $resetButton.after($spinner);

        $.ajax({
            url: ajax_object.ajax_url,
            data: resetData,
            type: "post",
            dataType: "json",
            timeout: 360000
        }).done(function() {
            $spinner.remove();
            $resetButton
                .text('Your images have been reset.')
                .removeAttr('disabled')
                .removeClass('enabled');
        });
    });

    $body.on("click", ".tinyga_req", function(e) {
        e.preventDefault();
        var $button = $(this),
            $parent = $(this).parent();

        data.id = $(this).data("id");

        $button
            .text("Optimizing image...")
            .attr("disabled", true)
            .css({
                opacity: 0.5
            });


        $parent
            .find(".tinygaSpinner")
            .css("display", "inline");


        $.ajax({
            url: ajax_object.ajax_url,
            data: data,
            type: "post",
            dataType: "json",
            timeout: 360000,
            context: $button
        })
        .done(requestSuccess)
        .fail(requestFail)
        .always(requestComplete);
    });
});
