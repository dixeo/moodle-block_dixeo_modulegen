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

$string['activequeued'] = 'Attivi/In coda';
$string['add'] = 'Aggiungi';
$string['aiactivities'] = 'Generatore di Contenuti Dixeo';
$string['blocktitle'] = 'Aggiungi contenuto generato da AI';
$string['cancelgeneration'] = 'Annulla generazione';
$string['cancelled'] = 'Annullato';
$string['canceltask'] = 'Annulla';
$string['canceltaskconfirm'] = 'Sei sicuro di voler annullare questa attività? Questa azione non può essere annullata.';
$string['category_assessment'] = 'Valutazione';
$string['category_content'] = 'Contenuto';
$string['category_interactive'] = 'Interattivo';
$string['category_resource'] = 'Risorse';
$string['completed'] = 'Completato';
$string['completedon'] = 'Completato il {$a}';
$string['copyinstructions'] = 'Copia istruzioni';
$string['dixeo_modulegen:addinstance'] = 'Aggiungi un blocco Generatore di Contenuti Dixeo';
$string['dixeo_modulegen:myaddinstance'] = 'Aggiungi un blocco Generatore di Contenuti Dixeo alla Dashboard';
$string['error_invalid_fill_pending'] = 'Stato coda non valido: le attività fill non possono essere in sospeso.';
$string['error_invalid_manual_pending'] = 'Stato coda non valido: i caricamenti manuali non possono essere in sospeso.';
$string['error_missing_submitter'] = 'Utente mittente mancante per la sincronizzazione dei file.';
$string['error_queue_failed'] = 'Impossibile aggiungere l\'attività alla coda di generazione.';
$string['error_title'] = 'Ops!';
$string['error_unexpected'] = 'Qualcosa è andato storto. Riprova o contatta l\'amministratore.';
$string['error_unsupported_module'] = 'Tipo di modulo non supportato: {$a}';
$string['filltask_defaulttitle'] = 'Nuova attività';
$string['generate'] = 'Genera';
$string['generation_complete'] = 'Il tuo contenuto è stato generato con successo! Aggiorna la pagina per visualizzarlo.';
$string['generationcancelled'] = 'Generazione annullata';
$string['generationerror'] = 'Errore di generazione';
$string['generationfailed'] = 'Generazione fallita';
$string['generationinprogress'] = 'Generazione in corso (<span class="elapsed-time">0:00</span>)';
$string['generationqueued'] = 'In attesa nella coda';
$string['idle'] = 'Inattivo';
$string['instructionscopied'] = 'Istruzioni copiate';
$string['loading'] = 'Generazione in corso...';
$string['manual_upload_browse'] = 'Scegli un file';
$string['manual_upload_drag'] = 'Trascina un file qui o fai clic per sfogliare';
$string['manual_upload_error_failed'] = 'Impossibile creare l\'attività.';
$string['manual_upload_error_file_too_large'] = 'Il file è troppo grande. Carica un file inferiore a {$a->maxsize}.';
$string['manual_upload_error_invalid_beforemod'] = 'La posizione di inserimento non appartiene a questo corso.';
$string['manual_upload_error_invalid_resource'] = 'Sono accettati solo questi formati di file: {$a->ragformats}.';
$string['manual_upload_error_invalid_scorm'] = 'Sono accettati solo pacchetti SCORM Articulate Storyline (.zip).';
$string['manual_upload_error_invalid_section'] = 'La sezione del corso selezionata non è valida.';
$string['manual_upload_error_missing'] = 'Il file è obbligatorio.';
$string['manual_upload_resource_description'] = 'Formati accettati: {$a->ragformats}. (Max {$a->maxsize})';
$string['manual_upload_scorm_description'] = 'Solo pacchetti SCORM Articulate Storyline (.zip).';
$string['manual_upload_success'] = 'Attività « <a href="{$a->link}">{$a->name}</a> » aggiunta. La sincronizzazione dei file è iniziata.';
$string['manual_upload_uploading'] = 'Caricamento in corso...';
$string['needsattention'] = 'Richiedono attenzione';
$string['newmoduletype'] = 'Nuovo {$a}';
$string['next'] = 'Prossimo';
$string['noinstructions'] = 'Nessuna istruzione per questa attività.';
$string['notasksinthequeue'] = 'La coda delle attività è attualmente vuota.';
$string['notavailable'] = 'Questo modulo non è disponibile o non è configurato correttamente. Riprova più tardi o contatta il tuo amministratore.';
$string['opengeneratorqueue'] = 'Apri coda generatore';
$string['pluginname'] = 'Generatore di Contenuti Dixeo';
$string['pluginrequired'] = 'Installa il plugin {$a} per generare questo tipo di attività.';
$string['processing'] = 'In elaborazione';
$string['prompt_placeholder'] = 'Istruzioni di generazione per Dixeo';
$string['queue_manual_upload_label'] = 'Caricamento manuale';
$string['queue_processor'] = 'Processore Coda Generazione Contenuti Dixeo';
$string['queued'] = 'In coda';
$string['queuemodaltitle'] = 'Coda di Generazione';
$string['removefromdisplay'] = 'Rimuovi dalla vista';
$string['removefromqueue'] = 'Rimuovi dalla coda';
$string['retry'] = 'Riprova';
$string['retry_fill_createfailed'] = 'Impossibile creare l\'attività dal risultato del fill.';
$string['retry_fill_failed'] = 'Il completamento del modulo non è terminato.';
$string['retry_fill_notfailed'] = 'Solo le attività fallite possono essere ripetute in questo modo.';
$string['retry_fill_notfill'] = 'Questo ripiego si applica solo alle attività di tipo fill.';
$string['retry_fill_notfound'] = 'Attività in coda non trovata per questo corso.';
$string['retry_fill_timeout'] = 'Il lavoro di fill IA non è stato completato in tempo.';
$string['retrygeneration'] = 'Riprova generazione';
$string['scorm_package_help'] = 'Carica un pacchetto SCORM (.zip)';
$string['scorm_package_invalid'] = 'Il file caricato non è un pacchetto SCORM valido.';
$string['status_0'] = 'In attesa';
$string['status_1'] = 'In elaborazione';
$string['status_2'] = 'Completato';
$string['status_3'] = 'Fallito';
$string['status_4'] = 'Annullato';
$string['success_message'] = 'Una nuova attività di generazione contenuti è stata aggiunta alla coda.';
$string['success_title'] = 'Successo!';
$string['task_completed_success'] = 'Attività « <a href="{$a->link}">{$a->name}</a> » creata.';
$string['task_failed'] = 'Generazione del modulo non riuscita: {$a->error}';
$string['task_process_modulegen_queue'] = 'Elaborare la coda di generazione moduli Dixeo';
$string['taskcancelerror'] = 'Si è verificato un errore durante l\'annullamento dell\'attività. Riprova più tardi.';
$string['taskcancelled'] = 'L\'attività è stata annullata con successo.';
$string['timecancelled'] = 'Annullato il: {$a}';
$string['timecompleted'] = 'Completato il: {$a}';
$string['timecreated'] = 'Creato il: {$a}';
$string['timestarted'] = 'Iniziato il: {$a}';
$string['viewinstructions'] = 'Visualizza istruzioni';
