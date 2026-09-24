import $ from 'jquery';
import 'bootstrap/dist/js/bootstrap.bundle.min.js';

window.$ = window.jQuery = $;

$(function () {
    const $form = $('#upload-form');

    if (!$form.length) {
        return;
    }

    const $file = $('#file');
    const $fileError = $('#file-error');
    const $uploadStatus = $('#upload-status');
    const $listStatus = $('#list-status');
    const $list = $('#file-list');
    const $button = $('#upload-button');

    function refreshList(onSuccess, failureMessage = 'The list could not be refreshed. Reload the page.') {
        $list.attr('aria-busy', 'true');

        $.ajax({
            url: $form.data('list-url'),
            method: 'GET',
            dataType: 'html',
        }).done(function (html) {
            $list.html(html);
            onSuccess();
        }).fail(function () {
            showStatus($listStatus, failureMessage, true);
        }).always(function () {
            $list.attr('aria-busy', 'false');
        });
    }

    function showStatus($element, message, isError = false) {
        $element.removeClass('d-none alert-success alert-danger')
            .addClass(`alert ${isError ? 'alert-danger' : 'alert-success'}`)
            .text(message);
    }

    function clearError() {
        $file.removeClass('is-invalid').removeAttr('aria-invalid');
        $fileError.text('');
    }

    $file.on('change', clearError);

    $form.on('submit', function (event) {
        event.preventDefault();
        clearError();
        $uploadStatus.addClass('d-none').text('');
        $listStatus.addClass('d-none').text('');
        $button.prop('disabled', true).text('Uploading…');

        $.ajax({
            url: $form.attr('action'),
            method: 'POST',
            data: new FormData(this),
            processData: false,
            contentType: false,
            dataType: 'json',
            headers: { Accept: 'application/json' },
        }).done(function () {
            $form[0].reset();
            showStatus($uploadStatus, 'File uploaded. Refreshing the list.');
            refreshList(function () {
                showStatus($uploadStatus, 'File uploaded. The list is up to date.');
            }, 'The file was uploaded, but the list could not be refreshed. Reload the page.');
        }).fail(function (xhr) {
            if (xhr.status === 422) {
                const message = xhr.responseJSON?.errors?.file?.[0];
                if (message) {
                    $file.addClass('is-invalid').attr('aria-invalid', 'true');
                    $fileError.text(message);
                }
                showStatus($uploadStatus, message || 'The file failed validation. Check the selected file.', true);
                return;
            }

            if (xhr.status === 413) {
                showStatus($uploadStatus, 'The file is too large to upload. Choose a smaller file.', true);
            } else if (xhr.status === 419) {
                showStatus($uploadStatus, 'Your session has expired. Reload the page and try again.', true);
            } else if (xhr.status === 0) {
                showStatus($uploadStatus, 'Could not connect to the server. Check your connection and try again.', true);
            } else {
                showStatus($uploadStatus, 'The server could not upload the file. Try again later.', true);
            }
        }).always(function () {
            $button.prop('disabled', false).text('Upload');
        });
    });

    $list.on('click', '.delete-file', function () {
        const $deleteButton = $(this);
        $deleteButton.prop('disabled', true).text('Deleting…');
        $listStatus.addClass('d-none').text('');

        $.ajax({
            url: $deleteButton.data('delete-url'),
            method: 'DELETE',
            dataType: 'json',
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            },
        }).done(function (response) {
            showStatus($listStatus, response.message);
            refreshList(function () {});
        }).fail(function (xhr) {
            if (xhr.status === 503) {
                showStatus($listStatus, xhr.responseJSON?.message || 'The notification is pending. Retry deletion later.', true);
            } else if (xhr.status === 404) {
                showStatus($listStatus, 'The file is no longer listed. Refreshing the list.', true);
                refreshList(function () {});
            } else if (xhr.status === 419) {
                showStatus($listStatus, 'Your session has expired. Reload the page and try again.', true);
            } else if (xhr.status === 0) {
                showStatus($listStatus, 'Could not connect to the server. Check your connection and try again.', true);
            } else {
                showStatus($listStatus, xhr.responseJSON?.message || 'Deletion could not be completed. Retry later.', true);
            }
        }).always(function () {
            $deleteButton.prop('disabled', false).text('Delete');
        });
    });
});
