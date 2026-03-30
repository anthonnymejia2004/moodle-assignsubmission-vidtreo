define(['core/notification', 'core/ajax'], function(Notification, Ajax) {
    var RECORDER_TAG_NAME = 'vidtreo-recorder';
    var RECORDER_REGION_SELECTOR = '[data-region="vidtreo-recorder"]';
    var RECORDER_MOUNT_SELECTOR = '.vidtreo-recorder-mount';
    var UPLOAD_STATUS_SELECTOR = '.vidtreo-upload-status';
    var PROGRESS_BAR_SELECTOR = '.progress-bar';
    var COMPLETION_MESSAGE_SELECTOR = '[data-region="vidtreo-completion-message"]';
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

    var AUTOSAVE_STATUS_SELECTOR = '[data-region="vidtreo-autosave-status"]';
    var AUTOSAVE_MESSAGE_SELECTOR = '.vidtreo-autosave-message';

    function disableRecorder(recorderElement) {
        if (!recorderElement) {
            return;
        }
        recorderElement.setAttribute('disabled', 'true');
    }

    function showCompletionMessage(container, message) {
        var completionEl = container.querySelector(COMPLETION_MESSAGE_SELECTOR);
        if (!completionEl) {
            return;
        }
        var messageEl = completionEl.querySelector('.vidtreo-completion-text');
        if (messageEl) {
            messageEl.textContent = message;
        }
        completionEl.style.display = 'block';
    }

    function showAutosaveStatus(container, message, color) {
        var statusEl = container.querySelector(AUTOSAVE_STATUS_SELECTOR);
        var messageEl = statusEl ? statusEl.querySelector(AUTOSAVE_MESSAGE_SELECTOR) : null;
        if (!statusEl || !messageEl) {
            return;
        }
        messageEl.textContent = message;
        messageEl.style.color = color || '#6c757d';
        statusEl.style.display = 'block';
    }

    function hideAutosaveStatus(container) {
        var statusEl = container.querySelector(AUTOSAVE_STATUS_SELECTOR);
        if (statusEl) {
            statusEl.style.display = 'none';
        }
    }

    function performAutosave(container, config, detail, retryCount) {
        if (!config.contextId) {
            return;
        }

        var currentRetry = retryCount || 0;

        showAutosaveStatus(container, 'Saving...', '#6c757d');

        var args = {
            contextid: config.contextId,
            recordingid: detail.recordingId || '',
            publicid: detail.publicId || '',
            duration: detail.duration || 0,
            status: 'complete',
            metadata: detail.metadata !== undefined ? JSON.stringify(detail.metadata) : ''
        };

        var request = Ajax.call([{
            methodname: 'local_vidtreo_autosave_recording',
            args: args
        }]);

        request[0].then(function(response) {
            if (response.success) {
                if (response.submissionid) {
                    config.submissionId = response.submissionid;
                }
                showAutosaveStatus(container, 'Auto-saved successfully', '#28a745');
                setTimeout(function() {
                    hideAutosaveStatus(container);
                }, 3000);
            } else {
                var errorMsg = response.message || 'Auto-save failed';
                if (currentRetry < 1) {
                    setTimeout(function() {
                        performAutosave(container, config, detail, currentRetry + 1);
                    }, 2000);
                } else {
                    showAutosaveStatus(container, errorMsg, '#dc3545');
                }
            }
            return null;
        }).catch(function(error) {
            if (currentRetry < 1) {
                setTimeout(function() {
                    performAutosave(container, config, detail, currentRetry + 1);
                }, 2000);
            } else {
                var message = 'Auto-save failed';
                if (error && error.message) {
                    message = error.message;
                }
                showAutosaveStatus(container, message, '#dc3545');
            }
        });
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

    function populateExistingData(container, config) {
        var existingData = getExistingData(container);
        if (!existingData) {
            return;
        }

        var fieldNames = config.fieldNames || {
            recordingId: FIELD_RECORDING_ID,
            publicId: FIELD_PUBLIC_ID,
            duration: FIELD_DURATION,
            status: FIELD_STATUS
        };

        setHiddenFieldValue(fieldNames.recordingId, existingData.recordingId);
        setHiddenFieldValue(fieldNames.publicId, existingData.publicId);
        setHiddenFieldValue(fieldNames.duration, existingData.duration);
        setHiddenFieldValue(fieldNames.status, existingData.status);
    }

    function bindRecorderEvents(container, recorderElement, config) {
        var fieldNames = config.fieldNames || {
            recordingId: FIELD_RECORDING_ID,
            publicId: FIELD_PUBLIC_ID,
            duration: FIELD_DURATION,
            status: FIELD_STATUS
        };

        recorderElement.addEventListener('upload-complete', function(event) {
            var detail = event.detail || {};

            var recordingId = detail.recordingId || detail.recording_id || detail.id || '';
            var publicId = detail.publicId || detail.public_id || detail.id || '';
            var duration = Math.round(detail.duration || 0);

            setHiddenFieldValue(fieldNames.recordingId, recordingId);
            setHiddenFieldValue(fieldNames.publicId, publicId);
            setHiddenFieldValue(fieldNames.duration, duration);
            setHiddenFieldValue(fieldNames.status, STATUS_COMPLETE);

            if (detail.metadata !== undefined) {
                setHiddenFieldValue(FIELD_METADATA, JSON.stringify(detail.metadata));
            }

            hideProgress(container);

            performAutosave(container, config, {
                recordingId: recordingId,
                publicId: publicId,
                duration: duration,
                metadata: detail.metadata
            }, 0);

            setTimeout(function() {
                disableRecorder(recorderElement);
                var completionMessage = container.dataset.completionMessage || 'Recording completed';
                showCompletionMessage(container, completionMessage);
            }, 3500);
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

        recorderElement.addEventListener('click', function(event) {
            var target = event.target;
            if (target && target.tagName === 'BUTTON' && !target.getAttribute('type')) {
                event.preventDefault();
            }
        }, true);

        mountPoint.addEventListener('submit', function(event) {
            event.preventDefault();
            event.stopPropagation();
        });

        bindRecorderEvents(container, recorderElement, config);
        mountPoint.appendChild(recorderElement);
        populateExistingData(container, config);

        var existingData = getExistingData(container);
        if (existingData && existingData.recordingId) {
            disableRecorder(recorderElement);
            var completionMessage = container.dataset.completionMessage || 'Recording completed';
            showCompletionMessage(container, completionMessage);
        }
    }

    return {
        init: function(contextId, config) {
            var selector = RECORDER_REGION_SELECTOR + '[data-context-id="' + contextId + '"]';
            var container = document.querySelector(selector);
            if (!container) {
                return;
            }

            // Store field names from config or use defaults for backward compatibility
            var fieldNames = config.fieldNames || {
                recordingId: FIELD_RECORDING_ID,
                publicId: FIELD_PUBLIC_ID,
                duration: FIELD_DURATION,
                status: FIELD_STATUS
            };
            config.fieldNames = fieldNames;

            var form = container.closest('form');
            if (form) {
                form.addEventListener('submit', function(event) {
                    var submitButton = event.submitter || document.activeElement;
                    if (submitButton && submitButton.name === 'submitbutton') {
                        var recordingIdField = document.querySelector('input[name="' + config.fieldNames.recordingId + '"]');
                        var hasRecordingInField = recordingIdField && recordingIdField.value;

                        var hasExistingRecording = false;
                        var rawExisting = container.dataset.existing;
                        if (rawExisting && rawExisting !== 'null') {
                            try {
                                var existingObj = JSON.parse(rawExisting);
                                if (existingObj && existingObj.recordingId) {
                                    hasExistingRecording = true;
                                    if (recordingIdField && !recordingIdField.value) {
                                        recordingIdField.value = existingObj.recordingId;
                                    }
                                }
                            } catch (e) {
                                // ignore parse errors
                            }
                        }

                        var completionMsgEl = container.querySelector(COMPLETION_MESSAGE_SELECTOR);
                        var isCompletionVisible = completionMsgEl && completionMsgEl.style.display !== 'none';

                        if (!hasRecordingInField && !hasExistingRecording && !isCompletionVisible) {
                            event.preventDefault();
                            showError(new Error('Please complete the recording before submitting'));
                            return;
                        }

                        if (!form.querySelector('input[name="id"]')) {
                            var urlParams = new URLSearchParams(window.location.search);
                            var courseModuleId = urlParams.get('id');
                            if (courseModuleId) {
                                var idInput = document.createElement('input');
                                idInput.type = 'hidden';
                                idInput.name = 'id';
                                idInput.value = courseModuleId;
                                form.appendChild(idInput);
                            }
                        }
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
