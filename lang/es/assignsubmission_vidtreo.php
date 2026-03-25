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
 * Spanish language strings for assignsubmission_vidtreo.
 *
 * @package    assignsubmission_vidtreo
 * @copyright  2024 Vidtreo <https://vidtreo.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Grabacion de video Vidtreo';
$string['vidtreo'] = 'Grabacion de video Vidtreo';
$string['enabled'] = 'Grabacion de video Vidtreo';
$string['enabled_help'] = 'Si se habilita, los estudiantes pueden grabar y enviar grabaciones de video usando el grabador de Vidtreo directamente en su entrega de tarea.';

$string['apikey'] = 'Clave API';
$string['apikey_desc'] = 'Tu clave API de Vidtreo. Obtiene una desde el panel de Vidtreo en https://app.vidtreo.com.';
$string['backendurl'] = 'URL del backend';
$string['backendurl_desc'] = 'La URL de la API del backend de Vidtreo. Solo cambia esto si usas un despliegue personalizado.';
$string['cdnurl'] = 'URL CDN del grabador';
$string['cdnurl_desc'] = 'La URL CDN para cargar el componente web del grabador Vidtreo. Solo cambia esto si necesitas usar una version especifica.';
$string['maxrecordingtime'] = 'Tiempo maximo de grabacion';
$string['maxrecordingtime_desc'] = 'Tiempo maximo de grabacion en segundos. Establece 0 para tiempo de grabacion ilimitado.';
$string['defaultsource'] = 'Fuente de grabacion predeterminada';
$string['defaultsource_desc'] = 'La fuente de grabacion predeterminada cuando se abre el grabador.';
$string['enablesourceswitching'] = 'Habilitar cambio de fuente';
$string['enablesourceswitching_desc'] = 'Permitir a los estudiantes cambiar entre fuentes de grabacion de camara y pantalla.';
$string['enablepause'] = 'Habilitar pausa';
$string['enablepause_desc'] = 'Permitir a los estudiantes pausar y reanudar su grabacion.';

$string['source_camera'] = 'Camara';
$string['source_screen'] = 'Pantalla';
$string['source_both'] = 'Camara y pantalla';

$string['nosubmission'] = 'No se ha enviado ninguna grabacion de video.';
$string['recording_submitted'] = 'Grabacion de video ({$a}s)';
$string['recording_status_complete'] = 'Completo';
$string['recording_status_error'] = 'Error';

$string['error:noapikey'] = 'La clave API de Vidtreo no ha sido configurada. Por favor contacta al administrador del sitio.';
$string['error:nobackendurl'] = 'La URL del backend de Vidtreo no ha sido configurada. Por favor contacta al administrador del sitio.';
$string['error:recorderloadfailed'] = 'No se pudo cargar el grabador de Vidtreo. Por favor verifica tu conexion a internet e intenta de nuevo.';
$string['error:norecording'] = 'Debes grabar un video antes de enviar.';

$string['privacy:metadata:assignsubmission_vidtreo'] = 'Almacena datos de entrega de grabaciones de video para el plugin de entrega de tareas Vidtreo.';
$string['privacy:metadata:recording_id'] = 'El identificador unico de la grabacion de video almacenada en los servidores de Vidtreo.';
$string['privacy:metadata:public_id'] = 'El identificador publico de la grabacion de video usado para la construccion de la URL de reproduccion.';
$string['privacy:metadata:duration'] = 'La duracion de la grabacion de video en segundos.';
$string['privacy:metadata:status'] = 'El estado de procesamiento de la grabacion de video (completo o error).';
$string['privacy:metadata:metadata'] = 'Metadatos adicionales sobre la grabacion como tamano de archivo, tipo MIME y agente de usuario.';
$string['privacy:externalsystem'] = 'Las grabaciones de video se almacenan en la plataforma en la nube de Vidtreo. El archivo de grabacion, junto con el identificador de grabacion y los metadatos asociados, se transmite a Vidtreo para su procesamiento y almacenamiento.';

$string['player_cdnurl'] = 'URL CDN del reproductor';
$string['player_cdnurl_desc'] = 'La URL CDN para cargar el componente web del reproductor Vidtreo. Solo cambia esto si necesitas usar una version especifica.';

$string['autosave:saving'] = 'Guardando...';
$string['autosave:saved'] = 'Guardado automáticamente';
$string['autosave:error'] = 'Error al guardar automáticamente: {$a}';
$string['autosave:error_validation'] = 'Datos de grabación inválidos';
$string['autosave:error_permission'] = 'No tienes permiso para guardar';
