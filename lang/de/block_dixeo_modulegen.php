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

$string['activequeued'] = 'Aktiv/In Warteschlange';
$string['add'] = 'Hinzufügen';
$string['aiactivities'] = 'Dixeo-Inhaltsgenerator';
$string['blocktitle'] = 'KI-generierte Inhalte hinzufügen';
$string['cancelgeneration'] = 'Generierung abbrechen';
$string['cancelled'] = 'Abgebrochen';
$string['canceltask'] = 'Abbrechen';
$string['canceltaskconfirm'] = 'Sind Sie sicher, dass Sie diese Aufgabe abbrechen möchten? Diese Aktion kann nicht rückgängig gemacht werden.';
$string['category_assessment'] = 'Bewertung';
$string['category_content'] = 'Inhalt';
$string['category_interactive'] = 'Interaktiv';
$string['category_resource'] = 'Ressourcen';
$string['completed'] = 'Abgeschlossen';
$string['completedon'] = 'Abgeschlossen am {$a}';
$string['copyinstructions'] = 'Anweisungen kopieren';
$string['dixeo_modulegen:addinstance'] = 'Einen Dixeo-Inhaltsgenerator-Block hinzufügen';
$string['dixeo_modulegen:myaddinstance'] = 'Einen Dixeo-Inhaltsgenerator-Block zur Übersicht hinzufügen';
$string['error_invalid_fill_pending'] = 'Ungültiger Warteschlangenstatus: Fill-Aufgaben dürfen nicht ausstehend sein.';
$string['error_invalid_manual_pending'] = 'Ungültiger Warteschlangenstatus: manuelle Uploads dürfen nicht ausstehend sein.';
$string['error_missing_submitter'] = 'Fehlender einreichender Benutzer für die Dateisynchronisierung.';
$string['error_queue_failed'] = 'Die Aufgabe konnte nicht zur Generierungswarteschlange hinzugefügt werden.';
$string['error_required_elements'] = 'Erforderliche Elemente wurden nicht gefunden.';
$string['error_title'] = 'Hoppla!';
$string['error_unexpected'] = 'Etwas ist schiefgelaufen. Bitte versuchen Sie es erneut oder kontaktieren Sie Ihren Administrator.';
$string['error_unsupported_module'] = 'Nicht unterstützter Modultyp: {$a}';
$string['eventfilltaskretried'] = 'Dixeo-Modul-Fill-Aufgabe erneut versucht';
$string['eventfilltaskretrieddesc'] = 'Der Benutzer mit der ID \'{$a->userid}\' hat die Fill-Warteschlangenaufgabe \'{$a->queueid}\' (Modultyp \'{$a->modulename}\', cmid={$a->cmid}) im Kurs \'{$a->courseid}\' erfolgreich wiederholt.';
$string['eventmanualuploadcompleted'] = 'Dixeo manueller Modul-Upload protokolliert';
$string['eventmanualuploadcompleteddesc'] = 'Der Benutzer mit der ID \'{$a->userid}\' hat die manuelle Upload-Warteschlangenaufgabe \'{$a->queueid}\' (Modultyp \'{$a->modulename}\', cmid={$a->cmid}) im Kurs \'{$a->courseid}\' protokolliert.';
$string['eventqueuetaskcancelled'] = 'Dixeo-Modulgenerierungsaufgabe abgebrochen';
$string['eventqueuetaskcancelleddesc'] = 'Der Benutzer mit der ID \'{$a->userid}\' hat die Warteschlangenaufgabe \'{$a->queueid}\' (Modultyp \'{$a->modulename}\', jobid=\'{$a->jobid}\') im Kurs \'{$a->courseid}\' abgebrochen.';
$string['eventqueuetaskcompleted'] = 'Dixeo-Modulgenerierungsaufgabe abgeschlossen';
$string['eventqueuetaskcompleteddesc'] = 'Der Benutzer mit der ID \'{$a->userid}\' hat die Warteschlangenaufgabe \'{$a->queueid}\' (Modultyp \'{$a->modulename}\', jobid=\'{$a->jobid}\', cmid={$a->cmid}) im Kurs \'{$a->courseid}\' abgeschlossen.';
$string['eventqueuetaskdeleted'] = 'Dixeo-Modulgenerierungsaufgabe gelöscht';
$string['eventqueuetaskdeleteddesc'] = 'Der Benutzer mit der ID \'{$a->userid}\' hat die Warteschlangenaufgabe \'{$a->queueid}\' (Modultyp \'{$a->modulename}\') aus dem Kurs \'{$a->courseid}\' gelöscht.';
$string['eventqueuetaskfailed'] = 'Dixeo-Modulgenerierungsaufgabe fehlgeschlagen';
$string['eventqueuetaskfaileddesc'] = 'Der Benutzer mit der ID \'{$a->userid}\' hat die Warteschlangenaufgabe \'{$a->queueid}\' als fehlgeschlagen markiert (Modultyp \'{$a->modulename}\', jobid=\'{$a->jobid}\') im Kurs \'{$a->courseid}\'.';
$string['eventqueuetasksubmitted'] = 'Dixeo-Modulgenerierungsaufgabe eingereicht';
$string['eventqueuetasksubmitteddesc'] = 'Der Benutzer mit der ID \'{$a->userid}\' hat die Modulgenerierungsaufgabe \'{$a->queueid}\' (Modultyp \'{$a->modulename}\') im Kurs \'{$a->courseid}\' in die Warteschlange gestellt.';
$string['filltask_defaulttitle'] = 'Neue Aktivität';
$string['generate'] = 'Generieren';
$string['generation_complete'] = 'Ihr Inhalt wurde erfolgreich generiert! Aktualisieren Sie die Seite, um ihn zu sehen.';
$string['generationcancelled'] = 'Generierung abgebrochen';
$string['generationerror'] = 'Generierungsfehler';
$string['generationfailed'] = 'Generierung fehlgeschlagen';
$string['generationinprogress'] = 'Generierung läuft (<span class="elapsed-time">0:00</span>)';
$string['generationqueued'] = 'Wartet in der Warteschlange';
$string['idle'] = 'Inaktiv';
$string['instructionscopied'] = 'Anweisungen kopiert';
$string['loading'] = 'Wird generiert...';
$string['manual_upload_browse'] = 'Datei auswählen';
$string['manual_upload_drag'] = 'Datei hierher ziehen oder zum Durchsuchen klicken';
$string['manual_upload_error_failed'] = 'Die Aktivität konnte nicht erstellt werden.';
$string['manual_upload_error_file_too_large'] = 'Datei ist zu groß. Bitte laden Sie eine Datei unter {$a->maxsize} hoch.';
$string['manual_upload_error_invalid_beforemod'] = 'Die Einfügeposition gehört nicht zu diesem Kurs.';
$string['manual_upload_error_invalid_resource'] = 'Es werden nur diese Dateiformate akzeptiert: {$a->ragformats}.';
$string['manual_upload_error_invalid_scorm'] = 'Es werden nur Articulate Storyline SCORM-Pakete (.zip) akzeptiert.';
$string['manual_upload_error_invalid_section'] = 'Der ausgewählte Kursabschnitt ist ungültig.';
$string['manual_upload_error_missing'] = 'Eine Datei ist erforderlich.';
$string['manual_upload_resource_description'] = 'Akzeptierte Formate: {$a->ragformats}. (Max. {$a->maxsize})';
$string['manual_upload_scorm_description'] = 'Nur Articulate Storyline SCORM-Pakete (.zip).';
$string['manual_upload_success'] = 'Aktivität „<a href="{$a->link}">{$a->name}</a>“ wurde hinzugefügt. Die Dateisynchronisation wurde gestartet.';
$string['manual_upload_uploading'] = 'Wird hochgeladen...';
$string['needsattention'] = 'Benötigt Aufmerksamkeit';
$string['newmoduletype'] = 'Neu: {$a}';
$string['next'] = 'Weiter';
$string['noinstructions'] = 'Keine Anweisungen für diese Aufgabe.';
$string['notasksinthequeue'] = 'Die Aufgabewarteschlange ist derzeit leer.';
$string['notavailable'] = 'Dieses Modul ist nicht verfügbar oder nicht richtig konfiguriert. Bitte versuchen Sie es später erneut oder wenden Sie sich an Ihren Administrator.';
$string['opengeneratorqueue'] = 'Generierungswarteschlange öffnen';
$string['pluginname'] = 'Dixeo-Inhaltsgenerator';
$string['pluginrequired'] = 'Installieren Sie das Plugin {$a}, um diesen Aktivitätstyp zu erstellen.';
$string['privacy:metadata:external:courseid'] = 'Die Kurs-ID der Generierungs- oder Upload-Anfrage.';
$string['privacy:metadata:external:filename'] = 'Hochgeladene Dateinamen, die zur Verarbeitung oder Indexierung gesendet werden können.';
$string['privacy:metadata:external:instructions'] = 'Vom Lehrenden eingegebene Generierungs- oder Fill-Anweisungen.';
$string['privacy:metadata:external:jobid'] = 'Remote-Dixeo-Jobkennungen zur Nachverfolgung der Verarbeitung.';
$string['privacy:metadata:external:modulename'] = 'Der Aktivitätstyp, der generiert oder hochgeladen wird.';
$string['privacy:metadata:externalpurpose'] = 'Anweisungen, Dateiinhalte oder -namen, Kurs- und Modulkontext sowie Jobkennungen werden über local_dixeo an Dixeo/KI-Dienste gesendet, um Aktivitäten zu erzeugen oder zu verarbeiten.';
$string['privacy:metadata:queue'] = 'Kursbezogene Generierungswarteschlange: Prompts, Titel, Dateinamen, Job-IDs, Fehler und Status. Terminal-Einträge (abgeschlossen, fehlgeschlagen, abgebrochen) werden nach 90 Tagen gelöscht.';
$string['privacy:metadata:queue:beforemod'] = 'Optionale Kursmodul-ID als Einfügeposition.';
$string['privacy:metadata:queue:cmid'] = 'Die ID des erstellten Kursmoduls nach Abschluss der Generierung.';
$string['privacy:metadata:queue:courseid'] = 'Der Kurs, dem der Warteschlangeneintrag gehört.';
$string['privacy:metadata:queue:description'] = 'Optionale Beschreibung zum Warteschlangeneintrag.';
$string['privacy:metadata:queue:instructions'] = 'Freitext-Anweisungen zur Generierung oder zum Fill, die personenbezogene Daten enthalten können.';
$string['privacy:metadata:queue:jobid'] = 'Die Dixeo- oder lokale Jobkennung des Warteschlangeneintrags.';
$string['privacy:metadata:queue:lang'] = 'Der für die Anfrage verwendete Sprachcode.';
$string['privacy:metadata:queue:modulename'] = 'Der Modultyp der Warteschlangenaufgabe.';
$string['privacy:metadata:queue:params'] = 'JSON-Metadaten (Absender-Benutzer-ID falls bekannt, Dateinamen, Fehler, Modusflags).';
$string['privacy:metadata:queue:sectionnumber'] = 'Der Kursabschnitt, für den die Aktivität vorgesehen ist.';
$string['privacy:metadata:queue:status'] = 'Der Warteschlangenstatus der Aufgabe.';
$string['privacy:metadata:queue:timecompleted'] = 'Wann die Aufgabe abgeschlossen, fehlgeschlagen oder abgebrochen wurde.';
$string['privacy:metadata:queue:timecreated'] = 'Wann der Warteschlangeneintrag erstellt wurde.';
$string['privacy:metadata:queue:timestarted'] = 'Wann die Verarbeitung der Aufgabe begann.';
$string['privacy:metadata:queue:title'] = 'Anzeigetitel des Warteschlangeneintrags (Aktivitätsname oder Upload-Bezeichnung).';
$string['privacy:notice:avoidpersonaldata'] = 'Inhalte können von Dixeo-Diensten verarbeitet werden. Nehmen Sie keine unnötigen personenbezogenen Daten über Studierende in Anweisungen oder hochgeladene Dateien auf.';
$string['privacy:path:queue'] = 'Generierungswarteschlange';
$string['processing'] = 'In Bearbeitung';
$string['prompt_placeholder'] = 'Generierungsanweisungen für Dixeo';
$string['queue_manual_upload_label'] = 'Manueller Upload';
$string['queue_processor'] = 'Dixeo-Inhaltsgenerierungswarteschlangen-Prozessor';
$string['queued'] = 'In der Warteschlange';
$string['queuemodaltitle'] = 'Generierungswarteschlange';
$string['removefromdisplay'] = 'Aus Anzeige entfernen';
$string['removefromqueue'] = 'Aus Warteschlange entfernen';
$string['retry'] = 'Wiederholen';
$string['retry_fill_createfailed'] = 'Die Aktivität konnte aus dem Fill-Ergebnis nicht erstellt werden.';
$string['retry_fill_failed'] = 'Das Ausfüllen des Moduls wurde nicht abgeschlossen.';
$string['retry_fill_notfailed'] = 'Nur fehlgeschlagene Aufgaben können auf diese Weise wiederholt werden.';
$string['retry_fill_notfill'] = 'Diese Wiederholung gilt nur für Fill-Aufgaben.';
$string['retry_fill_notfound'] = 'Warteschlangenaufgabe für diesen Kurs nicht gefunden.';
$string['retry_fill_timeout'] = 'Der KI-Fill-Job wurde nicht rechtzeitig abgeschlossen.';
$string['retrygeneration'] = 'Generierung wiederholen';
$string['scorm_package_help'] = 'SCORM-Paket hochladen (.zip)';
$string['scorm_package_invalid'] = 'Die hochgeladene Datei ist kein gültiges SCORM-Paket.';
$string['status_0'] = 'Ausstehend';
$string['status_1'] = 'In Bearbeitung';
$string['status_2'] = 'Abgeschlossen';
$string['status_3'] = 'Fehlgeschlagen';
$string['status_4'] = 'Abgebrochen';
$string['success_message'] = 'Eine neue Inhaltsgenerierungsaufgabe wurde zur Warteschlange hinzugefügt.';
$string['success_title'] = 'Erfolg!';
$string['task_cleanup_modulegen_queue'] = 'Alte Einträge der Dixeo-Modulgenerierungs-Warteschlange bereinigen';
$string['task_completed_success'] = 'Aktivität „<a href="{$a->link}">{$a->name}</a>“ wurde erstellt.';
$string['task_failed'] = 'Modulgenerierung fehlgeschlagen: {$a->error}';
$string['task_process_modulegen_queue'] = 'Dixeo-Modulgenerierungs-Warteschlange verarbeiten';
$string['taskcancelerror'] = 'Beim Abbrechen der Aufgabe ist ein Fehler aufgetreten. Bitte versuchen Sie es später erneut.';
$string['taskcancelled'] = 'Die Aufgabe wurde erfolgreich abgebrochen.';
$string['timecancelled'] = 'Abgebrochen am: {$a}';
$string['timecompleted'] = 'Abgeschlossen am: {$a}';
$string['timecreated'] = 'Erstellt am: {$a}';
$string['timestarted'] = 'Gestartet am: {$a}';
$string['viewinstructions'] = 'Anweisungen anzeigen';
