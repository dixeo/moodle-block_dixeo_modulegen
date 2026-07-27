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
 * Language strings for the Dixeo Module Generator block.
 *
 * @package    block_dixeo_modulegen
 * @copyright  2026 Edunao SAS (contact@edunao.com)
 * @author     Josemaria Bolanos <admin@mako.digital>
 * @author     Pierre FACQ <pierre.facq@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

$string['activequeued'] = 'Activos/En cola';
$string['add'] = 'Añadir';
$string['aiactivities'] = 'Generador de Contenido Dixeo';
$string['blocktitle'] = 'Añadir contenido generado por IA';
$string['cancelgeneration'] = 'Cancelar generación';
$string['cancelled'] = 'Cancelado';
$string['canceltask'] = 'Cancelar';
$string['canceltaskconfirm'] = '¿Está seguro de que desea cancelar esta tarea? Esta acción no se puede deshacer.';
$string['category_assessment'] = 'Evaluación';
$string['category_content'] = 'Contenido';
$string['category_interactive'] = 'Interactivo';
$string['category_resource'] = 'Recursos';
$string['completed'] = 'Completado';
$string['completedon'] = 'Completado el {$a}';
$string['copyinstructions'] = 'Copiar instrucciones';
$string['dixeo_modulegen:addinstance'] = 'Añadir un bloque Generador de Contenido Dixeo';
$string['dixeo_modulegen:myaddinstance'] = 'Añadir un bloque Generador de Contenido Dixeo al panel de control';
$string['error_invalid_fill_pending'] = 'Estado de cola no válido: las tareas de relleno no pueden estar pendientes.';
$string['error_invalid_manual_pending'] = 'Estado de cola no válido: las cargas manuales no pueden estar pendientes.';
$string['error_missing_submitter'] = 'Falta el usuario remitente para la sincronización de archivos.';
$string['error_queue_failed'] = 'Error al añadir la tarea a la cola de generación.';
$string['error_required_elements'] = 'No se encontraron los elementos requeridos.';
$string['error_title'] = '¡Vaya!';
$string['error_unexpected'] = 'Algo salió mal. Inténtelo de nuevo o contacte con el administrador.';
$string['error_unsupported_module'] = 'Tipo de módulo no compatible: {$a}';
$string['eventfilltaskretried'] = 'Tarea de relleno de módulo Dixeo reintentada';
$string['eventfilltaskretrieddesc'] = 'El usuario con id \'{$a->userid}\' reintentó correctamente la tarea de relleno \'{$a->queueid}\' (tipo de módulo \'{$a->modulename}\', cmid={$a->cmid}) en el curso \'{$a->courseid}\'.';
$string['eventmanualuploadcompleted'] = 'Carga manual de módulo Dixeo registrada';
$string['eventmanualuploadcompleteddesc'] = 'El usuario con id \'{$a->userid}\' registró la tarea de carga manual \'{$a->queueid}\' (tipo de módulo \'{$a->modulename}\', cmid={$a->cmid}) en el curso \'{$a->courseid}\'.';
$string['eventqueuetaskcancelled'] = 'Tarea de generación de módulo Dixeo cancelada';
$string['eventqueuetaskcancelleddesc'] = 'El usuario con id \'{$a->userid}\' canceló la tarea de cola \'{$a->queueid}\' (tipo de módulo \'{$a->modulename}\', jobid=\'{$a->jobid}\') en el curso \'{$a->courseid}\'.';
$string['eventqueuetaskcompleted'] = 'Tarea de generación de módulo Dixeo completada';
$string['eventqueuetaskcompleteddesc'] = 'El usuario con id \'{$a->userid}\' completó la tarea de cola \'{$a->queueid}\' (tipo de módulo \'{$a->modulename}\', jobid=\'{$a->jobid}\', cmid={$a->cmid}) en el curso \'{$a->courseid}\'.';
$string['eventqueuetaskdeleted'] = 'Tarea de generación de módulo Dixeo eliminada';
$string['eventqueuetaskdeleteddesc'] = 'El usuario con id \'{$a->userid}\' eliminó la tarea de cola \'{$a->queueid}\' (tipo de módulo \'{$a->modulename}\') del curso \'{$a->courseid}\'.';
$string['eventqueuetaskfailed'] = 'Tarea de generación de módulo Dixeo fallida';
$string['eventqueuetaskfaileddesc'] = 'El usuario con id \'{$a->userid}\' marcó la tarea de cola \'{$a->queueid}\' como fallida (tipo de módulo \'{$a->modulename}\', jobid=\'{$a->jobid}\') en el curso \'{$a->courseid}\'.';
$string['eventqueuetasksubmitted'] = 'Tarea de generación de módulo Dixeo enviada';
$string['eventqueuetasksubmitteddesc'] = 'El usuario con id \'{$a->userid}\' encoló la tarea de generación \'{$a->queueid}\' (tipo de módulo \'{$a->modulename}\') en el curso \'{$a->courseid}\'.';
$string['filltask_defaulttitle'] = 'Nueva actividad';
$string['generate'] = 'Generar';
$string['generation_complete'] = '¡Su contenido ha sido generado con éxito! Actualice la página para verlo.';
$string['generationcancelled'] = 'Generación cancelada';
$string['generationerror'] = 'Error de generación';
$string['generationfailed'] = 'Generación fallida';
$string['generationinprogress'] = 'Generación en progreso (<span class="elapsed-time">0:00</span>)';
$string['generationqueued'] = 'Esperando en cola';
$string['idle'] = 'Inactivo';
$string['instructionscopied'] = 'Instrucciones copiadas';
$string['loading'] = 'Generando...';
$string['manual_upload_browse'] = 'Elegir un archivo';
$string['manual_upload_drag'] = 'Arrastre un archivo aquí o haga clic para buscar';
$string['manual_upload_error_failed'] = 'No se pudo crear la actividad.';
$string['manual_upload_error_file_too_large'] = 'El archivo es demasiado grande. Por favor, sube un archivo menor de {$a->maxsize}.';
$string['manual_upload_error_invalid_beforemod'] = 'La posición de inserción no pertenece a este curso.';
$string['manual_upload_error_invalid_resource'] = 'Solo se aceptan estos formatos de archivo: {$a->ragformats}.';
$string['manual_upload_error_invalid_scorm'] = 'Solo se aceptan paquetes SCORM de Articulate Storyline (.zip).';
$string['manual_upload_error_invalid_section'] = 'La sección del curso seleccionada no es válida.';
$string['manual_upload_error_missing'] = 'El archivo es obligatorio.';
$string['manual_upload_resource_description'] = 'Formatos aceptados: {$a->ragformats}. (Máx. {$a->maxsize})';
$string['manual_upload_scorm_description'] = 'Solo paquetes SCORM de Articulate Storyline (.zip).';
$string['manual_upload_success'] = 'Actividad « <a href="{$a->link}">{$a->name}</a> » añadida. La sincronización de archivos ha comenzado.';
$string['manual_upload_uploading'] = 'Subiendo...';
$string['needsattention'] = 'Necesitan atención';
$string['newmoduletype'] = 'Nuevo {$a}';
$string['next'] = 'Siguiente';
$string['noinstructions'] = 'Sin instrucciones para esta tarea.';
$string['notasksinthequeue'] = 'La cola de tareas está actualmente vacía.';
$string['notavailable'] = 'Este módulo no está disponible o no está configurado correctamente. Por favor, inténtelo de nuevo más tarde o contacte con su administrador.';
$string['opengeneratorqueue'] = 'Abrir cola del generador';
$string['pluginname'] = 'Generador de Contenido Dixeo';
$string['pluginrequired'] = 'Instale el plugin {$a} para generar este tipo de actividad.';
$string['privacy:metadata:external:courseid'] = 'El ID del curso asociado a la solicitud de generación o carga.';
$string['privacy:metadata:external:filename'] = 'Nombres de archivos cargados que pueden enviarse para procesamiento o indexación.';
$string['privacy:metadata:external:instructions'] = 'Instrucciones de generación o relleno introducidas por el profesor.';
$string['privacy:metadata:external:jobid'] = 'Identificadores de trabajo remotos de Dixeo usados para seguir el procesamiento.';
$string['privacy:metadata:external:modulename'] = 'El tipo de módulo de actividad que se genera o carga.';
$string['privacy:metadata:externalpurpose'] = 'Las instrucciones, el contenido o nombres de archivo, el contexto de curso y módulo, y los identificadores de trabajo se envían a servicios Dixeo/IA a través de local_dixeo para generar o procesar actividades.';
$string['privacy:metadata:queue'] = 'Filas de generación por curso: indicaciones, títulos, nombres de archivo, IDs de trabajo, errores y estado. Las filas terminales (completadas, fallidas, canceladas) se eliminan tras 90 días.';
$string['privacy:metadata:queue:beforemod'] = 'ID opcional del módulo del curso usado como posición de inserción.';
$string['privacy:metadata:queue:cmid'] = 'El ID del módulo del curso creado cuando finaliza la generación.';
$string['privacy:metadata:queue:courseid'] = 'El curso propietario de la fila de la cola.';
$string['privacy:metadata:queue:description'] = 'Descripción opcional almacenada con la fila de la cola.';
$string['privacy:metadata:queue:instructions'] = 'Instrucciones de generación o relleno en texto libre que pueden incluir datos personales.';
$string['privacy:metadata:queue:jobid'] = 'El identificador de trabajo de Dixeo o local de la fila de la cola.';
$string['privacy:metadata:queue:lang'] = 'El código de idioma usado en la solicitud.';
$string['privacy:metadata:queue:modulename'] = 'El tipo de módulo de la tarea en cola.';
$string['privacy:metadata:queue:params'] = 'Metadatos JSON (ID del usuario remitente si se conoce, nombres de archivo, errores, indicadores de modo).';
$string['privacy:metadata:queue:sectionnumber'] = 'La sección del curso destinada a la actividad.';
$string['privacy:metadata:queue:status'] = 'El estado de la tarea en la cola.';
$string['privacy:metadata:queue:timecompleted'] = 'Cuándo se completó, falló o se canceló la tarea.';
$string['privacy:metadata:queue:timecreated'] = 'Cuándo se creó la fila de la cola.';
$string['privacy:metadata:queue:timestarted'] = 'Cuándo comenzó el procesamiento de la tarea.';
$string['privacy:metadata:queue:title'] = 'Un título visible de la fila de la cola (nombre de actividad o etiqueta de carga).';
$string['privacy:notice:avoidpersonaldata'] = 'El contenido puede ser procesado por servicios Dixeo. No incluya datos personales innecesarios sobre estudiantes en las instrucciones ni en los archivos cargados.';
$string['privacy:path:queue'] = 'Cola de generación';
$string['processing'] = 'Procesando';
$string['prompt_placeholder'] = 'Instrucciones de generación para Dixeo';
$string['queue_manual_upload_label'] = 'Carga manual';
$string['queue_processor'] = 'Procesador de Cola de Generación de Contenido Dixeo';
$string['queued'] = 'En cola';
$string['queuemodaltitle'] = 'Cola de Generación';
$string['removefromdisplay'] = 'Quitar de la vista';
$string['removefromqueue'] = 'Quitar de la cola';
$string['retry'] = 'Reintentar';
$string['retry_fill_createfailed'] = 'No se pudo crear la actividad a partir del resultado del relleno.';
$string['retry_fill_failed'] = 'El relleno del módulo no se completó.';
$string['retry_fill_notfailed'] = 'Solo las tareas fallidas pueden reintentarse de esta forma.';
$string['retry_fill_notfill'] = 'Este reintento solo aplica a tareas de relleno (fill).';
$string['retry_fill_notfound'] = 'No se encontró la tarea en cola para este curso.';
$string['retry_fill_timeout'] = 'El trabajo de relleno de IA no se completó a tiempo.';
$string['retrygeneration'] = 'Reintentar generación';
$string['scorm_package_help'] = 'Subir un paquete SCORM (.zip)';
$string['scorm_package_invalid'] = 'El archivo subido no es un paquete SCORM válido.';
$string['status_0'] = 'Pendiente';
$string['status_1'] = 'Procesando';
$string['status_2'] = 'Completado';
$string['status_3'] = 'Fallido';
$string['status_4'] = 'Cancelado';
$string['success_message'] = 'Se ha añadido una nueva tarea de generación de contenido a la cola.';
$string['success_title'] = '¡Éxito!';
$string['task_cleanup_modulegen_queue'] = 'Limpiar entradas antiguas de la cola de generación de módulos Dixeo';
$string['task_completed_success'] = 'Actividad « <a href="{$a->link}">{$a->name}</a> » creada.';
$string['task_failed'] = 'Error en la generación del módulo: {$a->error}';
$string['task_process_modulegen_queue'] = 'Procesar la cola de generación de módulos Dixeo';
$string['taskcancelerror'] = 'Se produjo un error al intentar cancelar la tarea. Por favor, inténtelo de nuevo más tarde.';
$string['taskcancelled'] = 'La tarea se ha cancelado correctamente.';
$string['timecancelled'] = 'Cancelado el: {$a}';
$string['timecompleted'] = 'Completado el: {$a}';
$string['timecreated'] = 'Creado el: {$a}';
$string['timestarted'] = 'Iniciado el: {$a}';
$string['viewinstructions'] = 'Ver instrucciones';
