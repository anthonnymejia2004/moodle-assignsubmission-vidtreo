# Implementation Plan: Video Auto-Save

## Overview

Implement an AJAX-based auto-save mechanism for the `assignsubmission_vidtreo` Moodle plugin. When a video upload completes, the frontend automatically persists recording data as a draft submission via a new Moodle external function, without requiring manual form submission. The implementation follows Moodle's official AJAX pattern: AMD module → `core/ajax` → external function registered in `db/services.php`.

## Tasks

- [x] 1. Create the external function class and register the web service
  - [x] 1.1 Create `classes/external/autosave_recording.php` with the external function
    - Create namespace `assignsubmission_vidtreo\external`
    - Implement `autosave_recording` extending `\core_external\external_api`
    - Implement `execute_parameters()` with: assignmentid (PARAM_INT, required), recordingid (PARAM_TEXT, required), publicid (PARAM_TEXT, required), duration (PARAM_INT, default 0), status (PARAM_TEXT, default 'complete'), metadata (PARAM_RAW, default '')
    - Implement `execute_returns()` returning: success (PARAM_BOOL), submissionid (PARAM_INT), message (PARAM_TEXT, optional)
    - Implement `execute()` logic: validate parameters, get course module context from assignmentid, require capability `assignsubmission/vidtreo:use`, validate recording_id is not empty/whitespace, get or create draft submission via `$assign->get_user_submission($USER->id, true)`, upsert record in `assignsubmission_vidtreo` table, return success with submission id
    - _Requirements: 2.1, 2.2, 2.3, 2.4, 2.5, 2.6, 2.7, 3.1, 3.2, 3.3, 5.2, 5.3_

  - [x] 1.2 Create `db/services.php` to register the external function
    - Register `assignsubmission_vidtreo_autosave_recording` with `ajax => true`, `loginrequired => true`, `type => 'write'`
    - _Requirements: 5.1, 5.4_

  - [x]* 1.3 Write property test: Round-trip upsert idempotence (Property 1)
    - **Property 1: Round-trip de auto-guardado (upsert idempotente)**
    - Generate random valid recording data (recording_id, public_id, duration, status, metadata), call `execute()`, verify DB record matches. Call again with different data, verify single record updated without duplicates.
    - **Validates: Requirements 2.3, 2.4, 2.5, 3.3**

  - [x]* 1.4 Write property test: Permission control (Property 2)
    - **Property 2: Control de permisos**
    - Generate users with and without `assignsubmission/vidtreo:use` capability, call autosave, verify only permitted users persist data and table is unmodified for unpermitted users.
    - **Validates: Requirements 2.2, 2.6**

  - [ ]* 1.5 Write property test: Draft submission management (Property 3)
    - **Property 3: Gestión de borrador de entrega**
    - Generate scenarios with and without pre-existing submissions, call autosave, verify exactly one draft submission exists per user/assignment pair.
    - **Validates: Requirements 3.1, 3.2**

  - [ ]* 1.6 Write property test: Empty recording_id validation (Property 4)
    - **Property 4: Validación de recording_id vacío**
    - Generate whitespace-only strings of random length (including empty string), verify all are rejected and table is unmodified.
    - **Validates: Requirements 2.7**

- [x] 2. Checkpoint - Verify backend service
  - Ensure all tests pass, ask the user if questions arise.

- [x] 3. Add language strings and update template
  - [x] 3.1 Add auto-save language strings to `lang/en/assignsubmission_vidtreo.php` and `lang/es/assignsubmission_vidtreo.php`
    - Add keys: `autosave:saving`, `autosave:saved`, `autosave:error`, `autosave:error_validation`, `autosave:error_permission`
    - EN values: "Saving...", "Auto-saved successfully", "Auto-save failed: {$a}", "Invalid recording data", "You do not have permission to save"
    - ES values: "Guardando...", "Guardado automáticamente", "Error al guardar automáticamente: {$a}", "Datos de grabación inválidos", "No tienes permiso para guardar"
    - _Requirements: 4.1, 4.2, 4.3_

  - [x] 3.2 Add auto-save status indicator to `templates/recorder.mustache`
    - Add `<div class="vidtreo-autosave-status" data-region="vidtreo-autosave-status">` with a `<span class="vidtreo-autosave-message">` after the `.vidtreo-upload-status` block
    - Initially hidden with `style="display: none;"`
    - _Requirements: 4.1, 4.2, 4.3_

- [x] 4. Implement frontend auto-save in recorder.js
  - [x] 4.1 Pass `assignmentId` to the frontend config in `locallib.php`
    - In `get_form_elements()`, add `'assignmentId' => $this->assignment->get_instance()->id` to the `$config` array passed to the JS module
    - _Requirements: 1.1, 1.2_

  - [x] 4.2 Implement `performAutosave()` function in `amd/src/recorder.js`
    - Add `core/ajax` dependency to the AMD `define()` call
    - Create `performAutosave(config, detail)` function that:
      - Checks `config.assignmentId` is available; if not, skip AJAX (only hidden fields)
      - Shows "Guardando..." / "Saving..." indicator via the autosave status element
      - Calls `Ajax.call([{methodname: 'assignsubmission_vidtreo_autosave_recording', args: {assignmentid, recordingid, publicid, duration, status, metadata}}])`
      - On success: updates local `submissionId`, shows "Auto-saved successfully" for 3 seconds, then hides indicator
      - On failure: retries once after 2 seconds; if second attempt fails, shows error message in indicator
    - Invoke `performAutosave()` inside the existing `upload-complete` event handler, after `setHiddenFieldValue()` calls
    - _Requirements: 1.1, 1.2, 1.3, 4.1, 4.2, 4.3, 4.4, 6.3_

- [x] 5. Checkpoint - Verify frontend integration
  - Ensure all tests pass, ask the user if questions arise.

- [x] 6. Update plugin version and compatibility tests
  - [x] 6.1 Increment `$plugin->version` in `version.php`
    - Bump version number so Moodle discovers the new service in `db/services.php` on plugin upgrade
    - _Requirements: 5.1_

  - [ ]* 6.2 Write property test: Autosave → save() compatibility (Property 5)
    - **Property 5: Compatibilidad autosave → save() (sin duplicados)**
    - Generate random data, call `autosave_recording::execute()`, then call `save()` with potentially different data, verify exactly one record exists with the latest data from `save()`.
    - **Validates: Requirements 6.1**

  - [ ]* 6.3 Write property test: Backward compatibility of save() (Property 6)
    - **Property 6: Compatibilidad retroactiva de save()**
    - Generate random data, call `save()` without prior autosave, verify behavior is identical to current: insert if no record, update if exists.
    - **Validates: Requirements 6.2**

- [x] 7. Final checkpoint - Ensure all tests pass
  - Ensure all tests pass, ask the user if questions arise.

## Notes

- Tasks marked with `*` are optional and can be skipped for faster MVP
- Each task references specific requirements for traceability
- Property tests use PHPUnit with randomized data generation (100+ iterations) as described in the design
- The existing `save()` method in `locallib.php` already performs upsert, so no modification is needed for compatibility
- Checkpoints ensure incremental validation after backend and frontend phases
