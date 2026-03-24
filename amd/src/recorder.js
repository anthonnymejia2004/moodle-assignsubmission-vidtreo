define(['core/notification'], function(Notification) {
    var RECORDER_TAG_NAME = 'vidtreo-recorder';
    var RECORDER_REGION_SELECTOR = '[data-region="vidtreo-recorder"]';
    var RECORDER_MOUNT_SELECTOR = '.vidtreo-recorder-mount';
    var UPLOAD_STATUS_SELECTOR = '.vidtreo-upload-status';
    var PROGRESS_BAR_SELECTOR = '.progress-bar';
    var FIELD_RECORDING_ID = 'vidtreo_recording_id';
    var FIELD_PUBLIC_ID = 'vidtreo_public_id';
    var FIELD_DURATION = 'vidtreo_duration';
    var FIELD_STATUS = 'vidtreo_status';
    var FIELD_METADATA = 'vidtreo_metadata';
    var STATUS_COMPLETE = 'complete';

    function loadRecorderScript(cdnUrl) {
        return new Promise(function(resolve, reject) {
            if (document.querySelector('script[data-vidtreo-recorder="true"]')) {
                waitForRecorderDefinition().then(resolve).catch(reject);
                return;
            }

            if (document.querySelector(RECORDER_TAG_NAME)) {
                resolve();
                return;
            }

            var script = document.createElement('script');
            script.src = cdnUrl;
            script.async = true;
            script.dataset.vidtreoRecorder = 'true';
            script.onload = function() {
                waitForRecorderDefinition().then(resolve).catch(reject);
            };
            script.onerror = function() {
                reject(new Error('Failed to load recorder script'));
            };
            document.head.appendChild(script);
        });
    }

    function waitForRecorderDefinition() {
        return new Promise(function(resolve, reject) {
            window.customElements.whenDefined(RECORDER_TAG_NAME).then(function() {
                resolve();
            }).catch(function(error) {
                reject(error);
            });
        });
    }

    function setHiddenFieldValue(fieldName, value) {
        var field = document.querySelector('input[name="' + fieldName + '"]');
        if (!field) {
            return;
        }

        if (value === null || value === undefined) {
            field.value = '';
            return;
        }

        field.value = String(value);
    }

    function updateProgress(container, progressValue) {
        var statusElement = container.querySelector(UPLOAD_STATUS_SELECTOR);
        var progressBar = container.querySelector(PROGRESS_BAR_SELECTOR);
        if (!statusElement || !progressBar) {
            return;
        }

        var safeProgressValue = progressValue;
        if (typeof safeProgressValue !== 'number' || Number.isNaN(safeProgressValue)) {
            safeProgressValue = 0;
        }

        statusElement.style.display = 'block';
        progressBar.style.width = safeProgressValue + '%';
        progressBar.setAttribute('aria-valuenow', String(safeProgressValue));
    }

    function hideProgress(container) {
        var statusElement = container.querySelector(UPLOAD_STATUS_SELECTOR);
        if (!statusElement) {
            return;
        }

        statusElement.style.display = 'none';
    }

    function showError(error) {
        if (Notification && typeof Notification.exception === 'function' && error instanceof Error) {
            Notification.exception(error);
            return;
        }

        var message = 'Vidtreo recorder failed to initialize';
        if (error && error.message) {
            message = error.message;
        }

        if (Notification && typeof Notification.alert === 'function') {
            Notification.alert('Vidtreo', message);
            return;
        }

        throw error;
    }

    function getExistingData(container) {
        var rawExistingData = container.dataset.existing;
        if (!rawExistingData || rawExistingData === 'null') {
            return null;
        }

        try {
            return JSON.parse(rawExistingData);
        } catch (error) {
            return null;
        }
    }

    function populateExistingData(container) {
        var existingData = getExistingData(container);
        if (!existingData) {
            return;
        }

        setHiddenFieldValue(FIELD_RECORDING_ID, existingData.recordingId);
        setHiddenFieldValue(FIELD_PUBLIC_ID, existingData.publicId);
        setHiddenFieldValue(FIELD_DURATION, existingData.duration);
        setHiddenFieldValue(FIELD_STATUS, existingData.status);
    }

    function bindRecorderEvents(container, recorderElement) {
        recorderElement.addEventListener('upload-complete', function(event) {
            var detail = event.detail || {};
            setHiddenFieldValue(FIELD_RECORDING_ID, detail.recordingId);
            setHiddenFieldValue(FIELD_PUBLIC_ID, detail.publicId);
            setHiddenFieldValue(FIELD_DURATION, detail.duration);
            setHiddenFieldValue(FIELD_STATUS, STATUS_COMPLETE);

            if (detail.metadata !== undefined) {
                setHiddenFieldValue(FIELD_METADATA, JSON.stringify(detail.metadata));
            }

            hideProgress(container);
        });

        recorderElement.addEventListener('upload-progress', function(event) {
            var detail = event.detail || {};
            updateProgress(container, detail.progress);
        });

        recorderElement.addEventListener('upload-error', function(event) {
            var detail = event.detail || {};
            var message = 'Vidtreo upload failed';
            if (detail.error) {
                message = detail.error;
            } else if (detail.message) {
                message = detail.message;
            }

            showError(new Error(message));
        });

        recorderElement.addEventListener('error', function(event) {
            var detail = event.detail || {};
            var message = 'Vidtreo recorder error';
            if (detail.error) {
                message = detail.error;
            } else if (detail.message) {
                message = detail.message;
            }

            showError(new Error(message));
        });
    }

    function mountRecorder(container, config) {
        var mountPoint = container.querySelector(RECORDER_MOUNT_SELECTOR);
        if (!mountPoint) {
            throw new Error('Vidtreo recorder mount point not found');
        }

        mountPoint.innerHTML = '';

        var recorderElement = document.createElement(RECORDER_TAG_NAME);
        recorderElement.setAttribute('api-key', config.apiKey);
        recorderElement.setAttribute('backend-url', config.backendUrl);
        recorderElement.setAttribute('max-recording-time', String(config.maxRecordingTime));
        recorderElement.setAttribute('enable-source-switching', String(Boolean(config.enableSourceSwitching)));
        recorderElement.setAttribute('enable-pause', String(Boolean(config.enablePause)));
        recorderElement.setAttribute('language', config.lang);

        if (config.submissionId) {
            recorderElement.setAttribute('user-metadata', JSON.stringify({submissionId: config.submissionId}));
        }

        bindRecorderEvents(container, recorderElement);
        mountPoint.appendChild(recorderElement);
        populateExistingData(container);
    }

    return {
        init: function(submissionId, config) {
            var selector = RECORDER_REGION_SELECTOR + '[data-submission-id="' + submissionId + '"]';
            var container = document.querySelector(selector);
            if (!container) {
                return;
            }

            var form = container.closest('form');
            if (form) {
                form.addEventListener('submit', function(event) {
                    var recordingIdField = document.querySelector('input[name="' + FIELD_RECORDING_ID + '"]');
                    if (recordingIdField && !recordingIdField.value) {
                        event.preventDefault();
                        showError(new Error('Please complete the recording before submitting'));
                    }
                });
            }

            loadRecorderScript(config.cdnUrl).then(function() {
                mountRecorder(container, config);
            }).catch(function(error) {
                showError(error);
            });
        }
    };
});
