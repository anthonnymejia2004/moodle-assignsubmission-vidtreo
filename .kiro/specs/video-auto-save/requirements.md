# Documento de Requisitos

## Introducción

Actualmente, el plugin assignsubmission_vidtreo permite a los estudiantes grabar videos directamente en la entrega de tareas de Moodle. Sin embargo, los datos de la grabación (recording_id, public_id, duración, estado, metadatos) solo se persisten cuando el usuario envía manualmente el formulario de Moodle. Si el estudiante graba un video pero cierra la pestaña, navega a otra página o pierde la conexión antes de enviar el formulario, los datos de la grabación se pierden aunque el video ya fue subido exitosamente a los servidores de Vidtreo.

Esta funcionalidad implementa un mecanismo de auto-guardado vía AJAX que se dispara automáticamente al completarse la subida del video, persistiendo los datos de la grabación como borrador sin requerir que el usuario envíe el formulario manualmente.

## Glosario

- **Sistema_AutoGuardado**: Módulo JavaScript del frontend responsable de enviar la solicitud AJAX de auto-guardado al servidor de Moodle tras la subida exitosa del video.
- **Servicio_AutoGuardado**: Servicio web externo de Moodle (external function) que recibe y procesa las solicitudes de auto-guardado desde el frontend.
- **Tabla_Vidtreo**: Tabla de base de datos `assignsubmission_vidtreo` que almacena los datos de las grabaciones de video asociadas a entregas.
- **Evento_SubidaCompleta**: Evento `upload-complete` emitido por el componente web `vidtreo-recorder` cuando el video ha sido subido exitosamente a los servidores de Vidtreo.
- **Datos_Grabación**: Conjunto de campos que identifican una grabación: recording_id, public_id, duration, status y metadata.
- **Borrador**: Estado de una entrega de Moodle (`assign_submission`) que aún no ha sido enviada para calificación.
- **Indicador_AutoGuardado**: Elemento visual en la interfaz que informa al usuario sobre el estado del proceso de auto-guardado.

## Requisitos

### Requisito 1: Disparar auto-guardado tras subida completa

**Historia de Usuario:** Como estudiante, quiero que los datos de mi grabación se guarden automáticamente cuando el video termina de subirse, para no perder mi trabajo si cierro la pestaña o navego a otra página.

#### Criterios de Aceptación

1. WHEN el Evento_SubidaCompleta es recibido por el recorder, THE Sistema_AutoGuardado SHALL enviar una solicitud AJAX al Servicio_AutoGuardado con los Datos_Grabación (recording_id, public_id, duration, status, metadata) y el identificador de la tarea (assignment id).
2. WHEN el Evento_SubidaCompleta es recibido y no existe un submission id válido (primera entrega), THE Sistema_AutoGuardado SHALL incluir el assignment id y el contexto del módulo en la solicitud para que el Servicio_AutoGuardado pueda crear o localizar el borrador correspondiente.
3. WHEN la solicitud AJAX de auto-guardado se completa exitosamente, THE Sistema_AutoGuardado SHALL actualizar el submission id local con el valor retornado por el servidor para futuras operaciones.

### Requisito 2: Servicio web de auto-guardado en el backend

**Historia de Usuario:** Como sistema, quiero un endpoint de servicio web que reciba los datos de la grabación y los persista como borrador, para que la información no dependa del envío manual del formulario.

#### Criterios de Aceptación

1. THE Servicio_AutoGuardado SHALL exponer una función externa de Moodle (`assignsubmission_vidtreo_autosave_recording`) accesible vía AJAX.
2. WHEN el Servicio_AutoGuardado recibe una solicitud válida con Datos_Grabación, THE Servicio_AutoGuardado SHALL verificar que el usuario tiene la capacidad `assignsubmission/vidtreo:use` en el contexto del módulo de tarea.
3. WHEN el usuario tiene permisos válidos y no existe un registro previo en la Tabla_Vidtreo para esa entrega, THE Servicio_AutoGuardado SHALL crear un nuevo registro con los Datos_Grabación proporcionados y el estado de borrador.
4. WHEN el usuario tiene permisos válidos y existe un registro previo en la Tabla_Vidtreo para esa entrega, THE Servicio_AutoGuardado SHALL actualizar el registro existente con los nuevos Datos_Grabación.
5. WHEN el Servicio_AutoGuardado completa la operación exitosamente, THE Servicio_AutoGuardado SHALL retornar el submission id y un indicador de éxito al frontend.
6. IF el usuario no tiene la capacidad requerida, THEN THE Servicio_AutoGuardado SHALL retornar un error de permisos sin modificar la Tabla_Vidtreo.
7. IF los Datos_Grabación recibidos contienen un recording_id vacío, THEN THE Servicio_AutoGuardado SHALL retornar un error de validación sin modificar la Tabla_Vidtreo.

### Requisito 3: Gestión del borrador de entrega

**Historia de Usuario:** Como estudiante, quiero que el sistema cree automáticamente un borrador de entrega si aún no existe, para que mis datos de grabación se asocien correctamente a mi tarea.

#### Criterios de Aceptación

1. WHEN el Servicio_AutoGuardado recibe una solicitud y no existe una entrega (submission) en estado borrador para el usuario y la tarea, THE Servicio_AutoGuardado SHALL crear una nueva entrega en estado borrador utilizando la API de asignaciones de Moodle.
2. WHEN el Servicio_AutoGuardado recibe una solicitud y ya existe una entrega en estado borrador para el usuario y la tarea, THE Servicio_AutoGuardado SHALL reutilizar la entrega existente para almacenar los Datos_Grabación.
3. THE Servicio_AutoGuardado SHALL asociar los Datos_Grabación al submission id correcto en la Tabla_Vidtreo, independientemente de si la entrega fue creada o reutilizada.

### Requisito 4: Retroalimentación visual al usuario

**Historia de Usuario:** Como estudiante, quiero ver una confirmación visual cuando mis datos de grabación se guardan automáticamente, para tener confianza de que mi trabajo está seguro.

#### Criterios de Aceptación

1. WHILE la solicitud AJAX de auto-guardado está en curso, THE Indicador_AutoGuardado SHALL mostrar un mensaje de estado "Guardando..." visible en la interfaz del recorder.
2. WHEN la solicitud AJAX de auto-guardado se completa exitosamente, THE Indicador_AutoGuardado SHALL mostrar un mensaje de confirmación "Guardado automáticamente" durante al menos 3 segundos.
3. IF la solicitud AJAX de auto-guardado falla, THEN THE Indicador_AutoGuardado SHALL mostrar un mensaje de error descriptivo al usuario.
4. IF la solicitud AJAX de auto-guardado falla, THEN THE Sistema_AutoGuardado SHALL reintentar la solicitud una vez después de 2 segundos antes de mostrar el error definitivo al usuario.

### Requisito 5: Definición del servicio externo de Moodle

**Historia de Usuario:** Como desarrollador, quiero que el servicio web esté correctamente registrado en la infraestructura de Moodle, para que sea accesible vía AJAX desde el frontend.

#### Criterios de Aceptación

1. THE Servicio_AutoGuardado SHALL estar registrado en el archivo `db/services.php` como una función externa con `ajax` habilitado.
2. THE Servicio_AutoGuardado SHALL definir parámetros de entrada validados: assignment id (entero, requerido), recording_id (texto, requerido), public_id (texto, requerido), duration (entero, opcional), status (texto, opcional), metadata (texto, opcional).
3. THE Servicio_AutoGuardado SHALL definir parámetros de retorno: success (booleano), submission_id (entero) y message (texto opcional).
4. THE Servicio_AutoGuardado SHALL requerir inicio de sesión activo (`'loginrequired' => true`).

### Requisito 6: Compatibilidad con el flujo de envío manual existente

**Historia de Usuario:** Como estudiante, quiero que el envío manual del formulario siga funcionando correctamente después de un auto-guardado, para que el flujo existente no se vea afectado.

#### Criterios de Aceptación

1. WHEN el usuario envía el formulario manualmente después de un auto-guardado exitoso, THE método `save()` de la clase `assign_submission_vidtreo` SHALL actualizar el registro existente en la Tabla_Vidtreo en lugar de crear un duplicado.
2. WHEN el usuario envía el formulario manualmente sin que se haya ejecutado un auto-guardado previo, THE método `save()` SHALL continuar funcionando con el comportamiento actual sin modificaciones.
3. THE Sistema_AutoGuardado SHALL mantener los campos ocultos del formulario (vidtreo_recording_id, vidtreo_public_id, vidtreo_duration, vidtreo_status, vidtreo_metadata) sincronizados con los valores auto-guardados.
