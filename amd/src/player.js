define(['core/notification'], function(Notification) {
    var PLAYER_TAG_NAME = 'vidtreo-player';
    var PLAYER_REGION_SELECTOR = '[data-region="vidtreo-player"]';
    var PLAYER_MOUNT_SELECTOR = '.vidtreo-player-mount';
    var LOADING_SELECTOR = '.vidtreo-loading';

    function showError(container, message) {
        if (Notification && typeof Notification.alert === 'function') {
            Notification.alert('Vidtreo', message);
        }

        var loadingElement = container.querySelector(LOADING_SELECTOR);
        if (loadingElement) {
            loadingElement.style.display = 'none';
        }

        var errorElement = document.createElement('div');
        errorElement.className = 'alert alert-danger';
        errorElement.textContent = message;

        var mountPoint = container.querySelector(PLAYER_MOUNT_SELECTOR);
        if (mountPoint) {
            mountPoint.appendChild(errorElement);
            return;
        }

        container.appendChild(errorElement);
    }

    function waitForPlayerDefinition() {
        return new Promise(function(resolve, reject) {
            window.customElements.whenDefined(PLAYER_TAG_NAME).then(function() {
                resolve();
            }).catch(function(error) {
                reject(error);
            });
        });
    }

    function loadPlayerScript(cdnUrl) {
        return new Promise(function(resolve, reject) {
            if (document.querySelector('script[data-vidtreo-player="true"]')) {
                waitForPlayerDefinition().then(resolve).catch(reject);
                return;
            }

            if (document.querySelector(PLAYER_TAG_NAME)) {
                resolve();
                return;
            }

            var script = document.createElement('script');
            script.src = cdnUrl;
            script.async = true;
            script.dataset.vidtreoPlayer = 'true';
            script.onload = function() {
                waitForPlayerDefinition().then(resolve).catch(reject);
            };
            script.onerror = function() {
                reject(new Error('Failed to load player script'));
            };
            document.head.appendChild(script);
        });
    }

    function mountPlayerElement(container) {
        var mountPoint = container.querySelector(PLAYER_MOUNT_SELECTOR);
        if (!mountPoint) {
            showError(container, 'Vidtreo player mount point not found');
            return;
        }

        var recordingId = container.getAttribute('data-recording-id');
        var apiKey = container.getAttribute('data-api-key');
        var backendUrl = container.getAttribute('data-backend-url');
        if (!recordingId) {
            showError(container, 'Vidtreo recording ID is missing');
            return;
        }

        var loadingElement = mountPoint.querySelector(LOADING_SELECTOR);
        if (loadingElement) {
            loadingElement.style.display = 'none';
        }

        var playerElement = document.createElement(PLAYER_TAG_NAME);
        playerElement.setAttribute('recording-id', recordingId);

        if (apiKey) {
            playerElement.setAttribute('api-key', apiKey);
        }

        if (backendUrl) {
            playerElement.setAttribute('backend-url', backendUrl);
        }

        playerElement.addEventListener('error', function(event) {
            var detail = event.detail || {};
            var message = 'Vidtreo playback error';
            if (detail.message) {
                message = detail.message;
            }

            showError(container, message);
        });

        mountPoint.appendChild(playerElement);
    }

    return {
        init: function(submissionId, playerCdnUrl) {
            var selector = PLAYER_REGION_SELECTOR + '[data-submission-id="' + submissionId + '"]';
            var container = document.querySelector(selector);
            if (!container) {
                return;
            }

            loadPlayerScript(playerCdnUrl).then(function() {
                mountPlayerElement(container);
            }).catch(function(error) {
                showError(container, error.message);
            });
        }
    };
});
