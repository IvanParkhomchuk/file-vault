import $ from 'jquery';
import 'bootstrap/dist/js/bootstrap.bundle.min.js';

window.$ = window.jQuery = $;

$(function () {
    const $form = $('#upload-form');

    const $file = $('#file');
    const $fileError = $('#file-error');
    const $uploadStatus = $('#upload-status');
    const $listStatus = $('#list-status');
    const $list = $('#file-list');
    const $button = $('#upload-button');
    const $dropZone = $('#drop-zone');
    const $selectedFile = $('#selected-file');
    const $deleteModal = $('#delete-file-modal');
    let $selectedDeleteButton = null;

    function refreshList(onSuccess, failureMessage = 'The list could not be refreshed. Reload the page.') {
        $list.attr('aria-busy', 'true');

        $.ajax({
            url: $list.data('list-url'),
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
        $dropZone.removeClass('is-invalid');
        $fileError.text('');
    }

    $file.on('change', function () {
        clearError();
        const name = this.files?.[0]?.name;
        $selectedFile.toggleClass('d-none', !name).text(name ? `Selected: ${name}` : '');
    });

    $dropZone.on('dragenter dragover', function (event) {
        event.preventDefault();
        $dropZone.addClass('is-dragging');
    }).on('dragleave dragend drop', function (event) {
        event.preventDefault();
        $dropZone.removeClass('is-dragging');
    }).on('drop', function (event) {
        const files = event.originalEvent.dataTransfer?.files;
        if (files?.length) {
            $file[0].files = files;
            $file.trigger('change');
        }
    });

    $form.on('submit', function (event) {
        event.preventDefault();
        clearError();
        $uploadStatus.addClass('d-none').text('');
        $button.prop('disabled', true).find('span:first').text('Uploading…');

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
            $selectedFile.addClass('d-none').text('');
            window.location.assign($form.data('manage-url'));
        }).fail(function (xhr) {
            if (xhr.status === 422) {
                const message = xhr.responseJSON?.errors?.file?.[0];
                if (message) {
                    $file.addClass('is-invalid').attr('aria-invalid', 'true');
                    $dropZone.addClass('is-invalid');
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
            $button.prop('disabled', false).find('span:first').text('Upload document');
        });
    });

    $deleteModal.on('show.bs.modal', function (event) {
        $selectedDeleteButton = $(event.relatedTarget);
        $('#delete-file-name').text($selectedDeleteButton.attr('data-file-name'));
    });

    $deleteModal.on('hidden.bs.modal', function () {
        $selectedDeleteButton = null;
    });

    $('#confirm-delete').on('click', function () {
        if (!$selectedDeleteButton?.length) {
            return;
        }

        const $deleteButton = $selectedDeleteButton;
        $selectedDeleteButton = null;
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
