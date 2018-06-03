var link = window.location.href;
var page = 1;
var count = 0;
var status = false;
var id = 0;
var url = '';
var urlBack = '';

// show calendar when clicking on date input
function showDatepicker(elem, level) {
    level = typeof level === 'undefined' ? 0 : 1;
    var target = $(elem).is('input') ? $(elem) : (level === 0 ? $(elem).prev() : $(elem).parent().prev());
    target.datepicker({
        autoclose: true,
        todayHighlight: true,
        dateFormat: "yy-mm-dd"
    }).trigger('focus');
}

function popupDeleteFinish(caption, message, backLink) {
    // change Done icon to Delete icon
    $('.popup-finish .title').find('i').remove();
    $('.popup-finish .title').prepend('<i class="material-icons delete_forever" style="font-size: 60px;">delete_forever</i>');
    
    popupFinish(caption, message, backLink);
}

// get list by filter when pressing ENTER
function filter(event) {
    if (event.which === 13 || event.keyCode === 13) {
        var url = link + '?page=' + page + getFilter();
        getData(url);
    }
}

// get list by filter when onchange event 
function filterWhenChange() {
    var url = link + '?page=' + page + getFilter();
    getData(url);
}

// get list by sort
function sort() {
    count = 0;
    var url = link + '?page=' + page + getFilter();
    getData(url);
}

// get the filter params
function getFilter() {
    var query = '';
    var limit = '&limit=' + $('#rpp').val() + '&unit=' + $('#unit').val() + '&sortActivityPurchase=' + $('#sort-activity-purchase').val();

    $('.input-search').parent().find('.input-search').each(function (idx, elem) {
        if ($(elem).val().length) {
            query += '&' + $(elem).attr('id') + '=' + $(elem).val();
        }
    });

    return query + limit;
}

// get data by ajax
function getData(url) {
    $.ajax({
        url: url,
        dataType: 'html'
    })
    .done(function (data) {
        $('.data').html(data);
        var span = $('#totalPromotions');
        var total = span.attr('data-total');
        $('#presult').html(total);
        // disable/enable CSV Download Button
        if (total == 0) {
            $('#csv').addClass('csv-disabled');
        } else {
            $('#csv').removeClass('csv-disabled');
        }
    })
    .fail(function (jqXHR) {
        if (jqXHR.status === 422) {
            $('.err-message .errors').html('');
            $.each(jqXHR.responseJSON.errors, function (key, val) {
                $('.errors').append('<li>' + val + '</li>');
            });

            return $('.err-message').fadeIn();
        }

        return handleErrors(app.trans('error.server.' + jqXHR.status));
    });
}

// Load data on first
if ($('#data-promotion').length) {
    var newUrl = '?limit=' + $('#rpp').val() + '&sortActivityPurchase=' + $('#sort-activity-purchase').val();
    getData(link + newUrl);
}

// click on pagination links
$('body').on('click', '.pagination a', function (e) {
    e.preventDefault();

    var url = $(this).attr('href');
    getData(url + getFilter());
});

// reset global vars
function resetVariable() {
    status = false;
    id = 0;
    url = '';
}

/* === Basic Setting === */
function addLine(typeMile) {
    isAccumulationType = typeof typeMile === 'undefined' ? true : typeMile === 2; // 2: accumulation, 1: redemption
    newData.push({
        id: null,
        date: null,
        amount: null,
        status: 3
    });

    $k = newData.length - 1;
    $('.scheduledList').append(newMileHtml($k, isAccumulationType));
    enableSaveBtn();
}

function highlightErrors(dateError) {
    // highlight the error rows
    var dateInputs = $('.scheduledList').find('input[type="text"]');
    dateInputs.each(function() {
        if ($(this).val() == dateError) {
            $(this).closest('.row-setting').addClass('has-error');
        } else {
            $(this).closest('.row-setting').removeClass('has-error');
        }
    });
}

function clearHighlights() {
    var rows = $('.scheduledList').find('.row-setting');
    rows.each(function() {
        $(this).removeClass('has-error');
    });
}

function enableSaveBtn(status) {
    status = status === false ? false : true;
    if (!status) {
        $('.btnSave').attr('disabled', 'disabled');
    } else {
        $('.btnSave').removeAttr('disabled');
    }
}

function deleteMile(elem) {
    var k = $(elem).data('key');
    var isNew = $(elem).data('check');

    //$('.scheduledList').find('#id-' + k).remove();
    $(elem).closest('.row-setting').remove();
    if (!isNaN(isNew)) {
        newData.splice(k, 1);
    } else {
        newData.map(function (val, key) {
            if (k === key) {
                newData[key].status = 2;
            }
        });
    }

    // disable/enable Save button
    if (newData.length === 0) {
        enableSaveBtn(false);
        $('.err-message .errors').html('');
        $('.err-message').hide();
    } else {
        enableSaveBtn();
    }
}

function updateMile(elem) {
    var k = $(elem).data('key');
    var $selector = $('#id-' + k);
    var date = $selector.find('.date').val();
    var amount = $selector.find('.amount').val();

    newData.map(function (val, key) {

        if (date === val.date && key !== k) {
            $('.errors').html('').append('<li>入力直が重複しています。<br/>もう一度入力内容をお確かめのうえ、入力してください。</li>');
            $('#id-' + key).addClass('has-error');
            $selector.addClass('has-error');
            $selector.find('.date').focus();
            $('.err-message').show();
        }

        if (k === key) {
            newData[key].date = date;
            newData[key].amount = amount;
            newData[key].status = newData[key].status === 3 ? newData[key].status : 1;
            return false;
        }
    });
}

function reqAjaxUpdateMile() {
    clearHighlights();
    loadingImage();
    $('.err-message').hide();
    var that = $('#frm');
    var url = that.attr('action');
    var backUrl = that.data('back');
    var caption = '保存完了';
    var message = '設定を保存しました';

    $.ajax({
        type: 'POST',
        url: url,
        data: {mile: newData},
        dataType: 'json'
    }).done(function (data) {
        loadingImage(false);
        if (data.type === 'success') {
            return popupFinish(caption, message, backUrl);
        } else if (data.errors && data.errors.lower_current_date) { // lower than the current date
            // highlight the error rows
            highlightErrors(data.errors.lower_current_date.date);
            // show the error message
            $('.err-message .errors').html('');
            $('.errors').append('<li>' + data.errors.lower_current_date.message + '</li>');
            return $('.err-message').show();
        }
        else if (data.errors && data.errors.duplicated) { // duplicated date
            // highlight the duplicated rows
            highlightErrors(data.errors.duplicated.date);
            // show the error message
            $('.err-message .errors').html('');
            $('.errors').append('<li>' + data.errors.duplicated.message + '</li>');
            return $('.err-message').show();
        }
    }).fail(function (jqXHR) {
        loadingImage(false);
        closePopup('.popup');

        if (jqXHR.status === 422) {
            $('.err-message .errors').html('');
            var listErrors = [];

            $.each(jqXHR.responseJSON.errors, function (key, val) {
                if (listErrors.indexOf(val[0]) === -1) {
                    listErrors.push(val[0]);
                }
            });

            if (listErrors.length) {
                listErrors.map(function (err) {
                    $('.errors').append('<li>' + err + '</li>');
                });
            }

            return $('.err-message').show();
        }
    });
}

function newMileHtml(k, isAccumulationType) {
    var totalRows = $('.scheduledList').find('.row-setting').length + 1;
    return '<div class="row row-setting" id="id-' + k + '" data-key="' + k + '"><label style="width: 50px; margin-right: 0; padding-right: 0;">' + (totalRows === 1 ? '開始日' : '') + '</label> ' +
        '<span class="input-group datepicker" placeholder="yyyy-mm-dd"> ' +
        '<input type="text" data-key="' + k + '" onchange="updateMile(this)" onclick="showDatepicker(this)" class="form-control date" style="width: 70px;">' +
        '<i class="material-icons icon-date" onclick="showDatepicker(this)">date_range</i>' +
        '</span>' +
        (isAccumulationType ? '' : '<span class="control-label">1マイル=</span>') +
        '<span>' +
        '<input type="number" value="" data-key="' + k + '" onchange="updateMile(this)" class="form-control amount" style="width: 70px;">' +
        '</span>' +
        '<span class="control-label">' + (isAccumulationType ? '円＝1マイル' : '円') + '</span>' +
        '<span>' +
        '<a href="javascript:void(0)" data-check="1" data-key="' + k + '" onclick="deleteMile(this)" class="btn btn-link text-left" style="width: 80px; border:none; padding-left:0; margin-left: 10px;" title="Delete">削除</a> ' +
        '</span></div>';
}

/* === Promotion === */
// onkeyup event for searching ActivityID
function searchByActivityID(e) {
    var url = $(e.target).data('url');
    reqAjaxSearchByActivityID(e, url);
}

// onkeyup for searching ActivityTitle
function searchByActivityTitle(e) {
    var url = $(e.target).data('url');
    reqAjaxSearchByActivityTitle(e, url);
}

// onkeyup event for searching AreaPath
function searchByAreaPath(e) {
    var url = $(e.target).data('url');
    reqAjaxSearchByAreaPath(e, url);
}

// search by ActivityID through ajax
function reqAjaxSearchByActivityID(e, url) {
    // clear errors message if exists
    showErrorSearchActivity('');
    loadingImage();

    var activityID = $(e.target).val();

    if (!activityID) {
        loadingImage(false);
        showResultSearchActivity('', 0);
    } else {
        $.ajax({
            url: url,
            type: 'post',
            data: {activity_id: activityID},
            dataType: 'json'
        }).done(function (result) {
            loadingImage(false);
            showErrorSearchActivity('');

            if (result && result.statusCode === 200) {
                // show result on popup
                showResultSearchActivity(result.data, result.total);
            } else {
                // show errors message on popup
                var msg = '<label class="alert alert-danger" style="width: 100%;">'+ app.trans('error.veltra.' + result.statusCode)+ '</label>';
                showErrorSearchActivity(msg);
                showResultSearchActivity('', 0);
            }
        }).fail(function (jqXHR) {
            loadingImage(false);
            showResultSearchActivity('', 0);

            var msgErrors = '';
            if (jqXHR.status === 422) {
                $.each(jqXHR.responseJSON.errors, function (key, val) {
                    msgErrors += '<li>' + val + '</li>';
                });
                return showErrorSearchActivity(msgErrors);
            }
            return handleErrors(app.trans('error.server.' + jqXHR.status));
        });
    }
}

// search by ActivityTitle through ajax
function reqAjaxSearchByActivityTitle(e, url) {
    // clear errors message if exists
    showErrorSearchActivity('');
    loadingImage();

    var activityTitle = $(e.target).val();

    if (!activityTitle) {
        loadingImage(false);
        showResultSearchActivity('', 0);
    } else {
        $.ajax({
            url: url,
            type: 'post',
            data: {title: activityTitle},
            dataType: 'json'
        }).done(function (result) {
            loadingImage(false);
            showErrorSearchActivity('');

            if (result && result.statusCode === 200) {
                // show result on popup
                showResultSearchActivity(result.data, result.total);
            } else {
                // show errors message on popup
                var msg = '<label class="alert alert-danger" style="width: 100%;">'+ app.trans('error.veltra.' + result.statusCode)+ '</label>';
                showErrorSearchActivity(msg);
                showResultSearchActivity('', 0);
            }
        }).fail(function (jqXHR) {
            loadingImage(false);
            // clear errors if exists
            showResultSearchActivity('', 0);

            if (jqXHR.status === 422) {
                var msgErrors = '';
                $.each(jqXHR.responseJSON.errors, function (key, val) {
                    msgErrors += '<li>' + val + '</li>';
                });
                return showErrorSearchActivity(msgErrors);
            }
            return handleErrors(app.trans('error.server.' + jqXHR.status));
        });
    }
}

// search by area path through ajax
function reqAjaxSearchByAreaPath(e, url) {
    // clear errors message if exists
    showErrorSearchAreaPath('');
    loadingImage();
    
    var areaPath = $(e.target).val();

    if (!areaPath) {
        loadingImage(false);
        showResultSearchAreaPath('', 0);
    } else {
        $.ajax({
            url: url,
            type: 'post',
            data: { area_pathJP: areaPath},
            dataType: 'json'
        }).done(function (result) {
            loadingImage(false);
            showErrorSearchAreaPath('');

            if (result && result.statusCode === 200) {
                // show result on popup
                showResultSearchAreaPath(result.data, result.total);
            } else {
                var msg = '<label class="alert alert-danger" style="width: 100%;">'+ app.trans('error.server.' + result.statusCode)+ '</label>';
                showErrorSearchAreaPath(msg);
                showResultSearchAreaPath('', 0);
            }
        }).fail(function (jqXHR) {
            loadingImage(false);
            // clear errors if exists
            showResultSearchAreaPath('', 0);
            
            if (jqXHR.status === 422) {
                var msgErrors = '';
                $.each(jqXHR.responseJSON.errors, function (key, val) {
                    msgErrors += '<li>' + val + '</li>';
                });
                return showErrorSearchAreaPath(msgErrors);
            }
            return handleErrors(app.trans('error.server.' + jqXHR.status));
        });
    }
}

// show list of activities & total of the found records
function showResultSearchActivity(rowsHtml, numResult) {
    $('.listActivity').html(rowsHtml);
    $('.resultSearchActivity').text(numResult);
}

// show errors for searching activity
function showErrorSearchActivity(msg) {
    $('.msgSearchActivity').html(msg);
}

// show list of AreaPaths & total of the found records
function showResultSearchAreaPath(rowsHtml, numResult) {
    $('.listAreaPath').html(rowsHtml);
    $('.resultSearchAreaPath').text(numResult);
}

// show errors for searching AreaPath
function showErrorSearchAreaPath(msg) {
    $('.msgSearchArea').html(msg);
}

// toogle Unit button
function toggleUnit(type) {
    // change vallue of unit
    $('input[name="unit"]').attr('value', type);
    
    if (type == 2) { // activity
        $('.btnArea').removeClass('btn-primary');
        $('.btnActivity').addClass('btn-primary');
        $('.rowActivity').show();
        $('.rowArea').hide();
    } else { // area
        $('.btnActivity').removeClass('btn-primary');
        $('.btnArea').addClass('btn-primary');
        $('.rowActivity').hide();
        $('.rowArea').show();
    }
}

// tick on RateType radio button
function tickRateType(v) {
    if (v == 1) {
        $('.plusMile').hide();
        $('.asMile').show();
        $('.currentSetting').show();
    } else {
        $('.plusMile').show();
        $('.asMile').hide();
        $('.currentSetting').hide();
    }
}

// toogle loading image
function loadingImage(flag) {
    flag = flag === false ? false : true;
    $('.loading').css('display', flag ? 'block' : 'none');
}

// save promotion
function savePromotion() {
    caption = '保存完了';
    message = '設定を保存しました';
    reqAjaxCreateOrUpdate();
}

// delete promotion
function deletePromotion(ele) {
    linkDelete = $(ele).data('url');
    urlBack    = $(ele).data('back');
    popupConfirm('このマイル積算プロモーション設定を削除しますか？');
}

// delete promotion by ajax action
function reqAjaxDelete() {
    $.ajax({
        url: linkDelete,
        type: 'delete',
        dataType: 'json'
    }).done(function (data) {
        if (data.type === 'success') {
            closePopup('.popup');
            popupDeleteFinish('削除完了', '設定を削除しました', urlBack);
            urlBack = '';
        } else {
            closePopup('.popup');
        }
    }).fail(function (jqXHR) {
        return handleErrors(app.trans('error.server.' + jqXHR.status));
    });
}

$(function() {
    // process popup Search By Activity
    $('#activityModal').on('show.bs.modal', function (event) {
        var modal = $(this);
        // when click to select activity
        $(document).on('click', '#activityModal .listActivity .rowActive a', function (e) {
            var row           = $(this).closest('.rowActive');
            var activityID    = row.find('a.idActivity').text();
            var activityTitle = row.find('a.titleActivity').text();

            // show ActivityTitle on screen
            $('input[name="activity_id"]').attr('value', activityID);
            $('input[name="activity_title"]').attr('value', activityTitle);
            $('input[name="activity_title"]').show();
            $('#selActivity').text('編集');

            // close popup
            modal.modal('hide');
        });
    });

    // process popup Search By Area
    $('#areaPathModal').on('show.bs.modal', function (event) {
        var modal = $(this);

        // when click to select area
        $(document).on('click', '#areaPathModal .listAreaPath .rowActive a', function (e) {
            var areaPath = $(this).data('id');
            
            // show AreaPathJP on screen
            $('#area-path-name').html($(this).data('title'));
            $('input[name="area_path"]').attr('value', areaPath);
            $('#selAreaPath').text('編集');

            // close popup
            modal.modal('hide');
        });
    });
});
