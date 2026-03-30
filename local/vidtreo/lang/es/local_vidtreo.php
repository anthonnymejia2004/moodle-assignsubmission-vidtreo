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
 * Spanish language strings for local_vidtreo.
 *
 * @package    local_vidtreo
 * @copyright  2024 Vidtreo <https://vidtreo.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Vidtreo';

$string['apikey'] = 'Clave API';
$string['apikey_desc'] = 'Tu clave API de Vidtreo. Obtiene una desde el panel de Vidtreo en https://app.vidtreo.com.';
$string['backendurl'] = 'URL del backend';
$string['backendurl_desc'] = 'La URL de la API del backend de Vidtreo. Solo cambia esto si usas un despliegue personalizado.';
$string['cdnurl'] = 'URL CDN del grabador';
$string['cdnurl_desc'] = 'La URL CDN para cargar el componente web del grabador Vidtreo. Solo cambia esto si necesitas usar una versión específica.';
$string['player_cdnurl'] = 'URL CDN del reproductor';
$string['player_cdnurl_desc'] = 'La URL CDN para cargar el componente web del reproductor Vidtreo. Solo cambia esto si necesitas usar una versión específica.';
$string['maxrecordingtime'] = 'Tiempo máximo de grabación';
$string['maxrecordingtime_desc'] = 'Tiempo máximo de grabación en segundos. Establece 0 para tiempo de grabación ilimitado.';
$string['enablesourceswitching'] = 'Habilitar cambio de fuente';
$string['enablesourceswitching_desc'] = 'Permitir a los usuarios cambiar entre fuentes de grabación de cámara y pantalla.';
$string['enablepause'] = 'Habilitar pausa';
$string['enablepause_desc'] = 'Permitir a los usuarios pausar y reanudar su grabación.';

$string['recording_exists'] = 'Grabación existente';
$string['uploading'] = 'Subiendo...';
$string['recording_completed'] = 'Grabación completada';
$string['duration'] = 'Duración';

$string['error:unauthorized'] = 'La clave API es inválida o ha expirado. Por favor contacta al administrador.';
$string['error:notfound'] = 'Grabación no encontrada en el servidor.';
$string['error:servererror'] = 'Error del servidor Vidtreo. Por favor intenta de nuevo más tarde.';
$string['error:timeout'] = 'Tiempo de espera agotado. Por favor verifica tu conexión a internet.';
$string['error:network'] = 'Error de red. Por favor verifica tu conexión a internet.';
$string['error:noapikey'] = 'La clave API de Vidtreo no ha sido configurada. Por favor contacta al administrador del sitio.';
$string['error:nobackendurl'] = 'La URL del backend de Vidtreo no ha sido configurada. Por favor contacta al administrador del sitio.';
$string['norecording'] = 'No hay grabación de video enviada.';
$string['error:no_recording'] = 'Debes grabar un video antes de enviar.';
$string['error:recorder_load_failed'] = 'No se pudo cargar el grabador de Vidtreo. Por favor verifica tu conexión a internet e intenta de nuevo.';

$string['autosave:saving'] = 'Guardando...';
$string['autosave:saved'] = 'Guardado automáticamente';
$string['autosave:error'] = 'Error al guardar automáticamente: {$a}';

$string['privacy:metadata:local_vidtreo_recordings'] = 'Almacena datos de grabaciones de video para todas las actividades Vidtreo en los módulos de Moodle.';
$string['privacy:metadata:recording_id'] = 'El identificador único de la grabación de video almacenada en los servidores de Vidtreo.';
$string['privacy:metadata:public_id'] = 'El identificador público de la grabación de video usado para la construcción de la URL de reproducción.';
$string['privacy:metadata:duration'] = 'La duración de la grabación de video en segundos.';
$string['privacy:metadata:status'] = 'El estado de procesamiento de la grabación de video (completo o error).';
$string['privacy:metadata:metadata'] = 'Metadatos adicionales sobre la grabación como tamaño de archivo, tipo MIME y agente de usuario.';
$string['privacy:externalsystem'] = 'Las grabaciones de video se almacenan en la plataforma en la nube de Vidtreo. El archivo de grabación, junto con el identificador de grabación y los metadatos asociados, se transmite a Vidtreo para su procesamiento y almacenamiento.';
