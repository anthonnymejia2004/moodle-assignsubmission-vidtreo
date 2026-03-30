<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Shared API client for communicating with the Vidtreo backend.
 *
 * @package    local_vidtreo
 * @copyright  2024 Vidtreo <https://vidtreo.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_vidtreo;

defined('MOODLE_INTERNAL') || die();

/**
 * Shared API client for all Vidtreo subplugins.
 *
 * Handles HTTP communication with the Vidtreo backend, including retry logic
 * with exponential backoff and localized error handling.
 *
 * Usage from subplugins:
 *   use local_vidtreo\api_client;
 *   $client = new api_client();
 */
class api_client {

    /** @var string API key read from global settings */
    private string $apikey;

    /** @var string Backend base URL read from global settings */
    private string $backendurl;

    /** @var int Maximum number of retry attempts */
    private const MAX_RETRIES = 3;

    /** @var int Base backoff delay in seconds (doubles each retry: 1, 2, 4) */
    private const BASE_BACKOFF = 1;

    /** @var int cURL timeout in seconds */
    private const CURL_TIMEOUT = 30;

    /**
     * Constructor — reads API key and backend URL from global plugin settings.
     *
     * @throws \moodle_exception If API key or backend URL is not configured.
     */
    public function __construct() {
        $this->apikey = (string) get_config('local_vidtreo', 'apikey');
        $this->backendurl = rtrim((string) get_config('local_vidtreo', 'backendurl'), '/');

        if (empty($this->apikey)) {
            throw new \moodle_exception('error:noapikey', 'local_vidtreo');
        }

        if (empty($this->backendurl)) {
            throw new \moodle_exception('error:nobackendurl', 'local_vidtreo');
        }
    }

    /**
     * Uploads a recording to the Vidtreo backend.
     *
     * @param string $recording_id The recording ID to upload.
     * @param array  $metadata     Optional additional metadata.
     * @return array Response containing public_id, duration, status.
     * @throws \moodle_exception On HTTP or network errors.
     */
    public function upload_recording(string $recording_id, array $metadata = []): array {
        $payload = array_merge(['recording_id' => $recording_id], $metadata);
        $response = $this->request('POST', '/recordings/upload', $payload);
        return $response;
    }

    /**
     * Retrieves metadata for a recording from the Vidtreo backend.
     *
     * @param string $recording_id The recording ID to look up.
     * @return array Metadata including public_id, duration, status, created_at.
     * @throws \moodle_exception On HTTP or network errors.
     */
    public function get_recording(string $recording_id): array {
        $response = $this->request('GET', '/recordings/' . urlencode($recording_id));
        return $response;
    }

    /**
     * Deletes a recording from the Vidtreo backend.
     *
     * @param string $recording_id The recording ID to delete.
     * @return bool True if deleted successfully.
     * @throws \moodle_exception On HTTP or network errors.
     */
    public function delete_recording(string $recording_id): bool {
        $this->request('DELETE', '/recordings/' . urlencode($recording_id));
        return true;
    }

    /**
     * Validates that a recording exists and is complete on the backend.
     *
     * Unlike other methods, this returns false instead of throwing on 404,
     * since "not found" is a valid non-exceptional outcome for validation.
     *
     * @param string $recording_id The recording ID to validate.
     * @return bool True if the recording exists and its status is 'complete'.
     */
    public function validate_recording(string $recording_id): bool {
        try {
            $data = $this->get_recording($recording_id);
            return isset($data['status']) && $data['status'] === 'complete';
        } catch (\moodle_exception $e) {
            // A 404 means the recording simply doesn't exist — not an error.
            return false;
        }
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Executes an HTTP request with retry logic and exponential backoff.
     *
     * @param string $method  HTTP method (GET, POST, DELETE, etc.).
     * @param string $path    API path (e.g. '/recordings/upload').
     * @param array  $payload Request body for POST/PUT requests.
     * @return array Decoded JSON response body.
     * @throws \moodle_exception On unrecoverable HTTP or network errors.
     */
    private function request(string $method, string $path, array $payload = []): array {
        $url = $this->backendurl . $path;
        $last_exception = null;

        for ($attempt = 0; $attempt < self::MAX_RETRIES; $attempt++) {
            if ($attempt > 0) {
                // Exponential backoff: 1s, 2s, 4s.
                $delay = self::BASE_BACKOFF * (2 ** ($attempt - 1));
                sleep($delay);
            }

            try {
                return $this->execute_curl($method, $url, $payload);
            } catch (\moodle_exception $e) {
                $last_exception = $e;

                // Do not retry on client errors (4xx) — they won't change.
                $errorcode = $e->errorcode;
                if ($errorcode === 'error:unauthorized' || $errorcode === 'error:notfound') {
                    throw $e;
                }

                // Log transient errors and retry.
                debugging(
                    'local_vidtreo api_client: transient error on attempt ' . ($attempt + 1) .
                    ' for ' . $method . ' ' . $path . ' — ' . $e->getMessage(),
                    DEBUG_DEVELOPER
                );
            }
        }

        throw $last_exception;
    }

    /**
     * Performs a single cURL request and maps HTTP status codes to exceptions.
     *
     * @param string $method  HTTP method.
     * @param string $url     Full URL.
     * @param array  $payload Request body (JSON-encoded for POST/PUT).
     * @return array Decoded JSON response.
     * @throws \moodle_exception On HTTP errors, timeout, or network failure.
     */
    private function execute_curl(string $method, string $url, array $payload = []): array {
        $ch = curl_init();

        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
            // Never log the actual key value — use a placeholder in debug output.
            'Authorization: Bearer ' . $this->apikey,
        ];

        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => self::CURL_TIMEOUT,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_CUSTOMREQUEST  => $method,
        ]);

        if (!empty($payload) && in_array($method, ['POST', 'PUT', 'PATCH'], true)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        }

        $body     = curl_exec($ch);
        $errno    = curl_errno($ch);
        $errmsg   = curl_error($ch);
        $httpcode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Network-level errors (DNS failure, connection refused, etc.).
        if ($errno !== 0) {
            if ($errno === CURLE_OPERATION_TIMEDOUT) {
                debugging(
                    'local_vidtreo api_client: timeout reaching ' . $url,
                    DEBUG_DEVELOPER
                );
                throw new \moodle_exception('error:timeout', 'local_vidtreo');
            }

            debugging(
                'local_vidtreo api_client: curl error ' . $errno . ' — ' . $errmsg .
                ' (API key redacted)',
                DEBUG_DEVELOPER
            );
            throw new \moodle_exception('error:network', 'local_vidtreo');
        }

        // HTTP-level errors.
        if ($httpcode === 401) {
            debugging(
                'local_vidtreo api_client: HTTP 401 Unauthorized for ' . $url .
                ' (API key redacted)',
                DEBUG_DEVELOPER
            );
            throw new \moodle_exception('error:unauthorized', 'local_vidtreo');
        }

        if ($httpcode === 404) {
            debugging(
                'local_vidtreo api_client: HTTP 404 Not Found for ' . $url,
                DEBUG_DEVELOPER
            );
            throw new \moodle_exception('error:notfound', 'local_vidtreo');
        }

        if ($httpcode >= 500) {
            debugging(
                'local_vidtreo api_client: HTTP ' . $httpcode . ' Server Error for ' . $url .
                ' — response: ' . $body,
                DEBUG_DEVELOPER
            );
            throw new \moodle_exception('error:servererror', 'local_vidtreo');
        }

        // Decode JSON response.
        $decoded = json_decode($body, true);
        if (!is_array($decoded)) {
            debugging(
                'local_vidtreo api_client: invalid JSON response from ' . $url,
                DEBUG_DEVELOPER
            );
            // Return empty array so callers can handle gracefully.
            return [];
        }

        return $decoded;
    }
}
