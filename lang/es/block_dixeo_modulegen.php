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
$string['error_queue_failed'] = 'Error al añadir la tarea a la cola de generación.';
$string['error_title'] = '¡Vaya!';
$string['error_unsupported_module'] = 'Tipo de módulo no compatible: {$a}';
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
