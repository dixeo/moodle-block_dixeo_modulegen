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

$string['activequeued'] = 'Actifs/En attente';
$string['add'] = 'Ajouter';
$string['aiactivities'] = 'Générateur de contenu Dixeo';
$string['blocktitle'] = 'Ajouter du contenu généré par IA';
$string['cancelgeneration'] = 'Annuler la génération';
$string['cancelled'] = 'Annulé';
$string['canceltask'] = 'Annuler';
$string['canceltaskconfirm'] = 'Êtes-vous sûr de vouloir annuler cette tâche ? Cette action ne peut pas être annulée.';
$string['category_assessment'] = 'Évaluation';
$string['category_content'] = 'Contenu';
$string['category_interactive'] = 'Interactif';
$string['category_resource'] = 'Ressources';
$string['completed'] = 'Terminé';
$string['completedon'] = 'Terminé le {$a}';
$string['copyinstructions'] = 'Copier les instructions';
$string['dixeo_modulegen:addinstance'] = 'Ajouter un bloc Générateur de contenu Dixeo';
$string['dixeo_modulegen:myaddinstance'] = 'Ajouter un bloc Générateur de contenu Dixeo au tableau de bord';
$string['error_queue_failed'] = 'Échec de l\'ajout de la tâche à la file d\'attente de génération.';
$string['error_title'] = 'Oups !';
$string['error_unsupported_module'] = 'Type de module non pris en charge : {$a}';
$string['filltask_defaulttitle'] = 'Nouvelle activité';
$string['generate'] = 'Générer';
$string['generation_complete'] = 'Votre contenu a été généré avec succès ! Actualisez la page pour le voir.';
$string['generationcancelled'] = 'Génération annulée';
$string['generationerror'] = 'Erreur de génération';
$string['generationfailed'] = 'La génération a échoué';
$string['generationinprogress'] = 'Génération en cours (<span class="elapsed-time">0:00</span>)';
$string['generationqueued'] = 'En attente dans la file';
$string['idle'] = 'Inactif';
$string['instructionscopied'] = 'Instructions copiées';
$string['loading'] = 'Génération en cours...';
$string['manual_upload_browse'] = 'Choisir un fichier';
$string['manual_upload_drag'] = 'Glissez-déposez un fichier ici ou cliquez pour parcourir';
$string['manual_upload_error_failed'] = 'Impossible de créer l\'activité.';
$string['manual_upload_error_file_too_large'] = 'Le fichier est trop volumineux. Veuillez télécharger un fichier de moins de {$a->maxsize}.';
$string['manual_upload_error_invalid_beforemod'] = 'La position d’insertion n’appartient pas à ce cours.';
$string['manual_upload_error_invalid_resource'] = 'Seuls ces formats de fichier sont acceptés : {$a->ragformats}.';
$string['manual_upload_error_invalid_scorm'] = 'Seuls les paquets SCORM Articulate Storyline (.zip) sont acceptés.';
$string['manual_upload_error_invalid_section'] = 'La section de cours sélectionnée n’est pas valide.';
$string['manual_upload_error_missing'] = 'Un fichier est obligatoire.';
$string['manual_upload_resource_description'] = 'Formats acceptés : {$a->ragformats}. (Max. {$a->maxsize})';
$string['manual_upload_scorm_description'] = 'Paquets SCORM Articulate Storyline (.zip) uniquement.';
$string['manual_upload_success'] = 'Activité « <a href="{$a->link}">{$a->name}</a> » ajoutée. La synchronisation des fichiers a démarré.';
$string['manual_upload_uploading'] = 'Téléversement en cours...';
$string['needsattention'] = 'À traiter';
$string['newmoduletype'] = 'Nouveau {$a}';
$string['next'] = 'Suivant';
$string['noinstructions'] = 'Aucune instruction pour cette tâche.';
$string['notasksinthequeue'] = 'La file d\'attente des tâches est actuellement vide.';
$string['notavailable'] = 'Ce module n\'est pas disponible ou n\'est pas correctement configuré. Veuillez réessayer plus tard ou contacter votre administrateur.';
$string['opengeneratorqueue'] = 'Ouvrir la file du générateur';
$string['pluginname'] = 'Générateur de contenu Dixeo';
$string['pluginrequired'] = 'Installez le plugin {$a} pour générer ce type d\'activité.';
$string['processing'] = 'En cours de traitement';
$string['prompt_placeholder'] = 'Instructions de génération pour Dixeo';
$string['queue_manual_upload_label'] = 'Téléversement manuel';
$string['queue_processor'] = 'Processeur de file d\'attente de génération de contenu Dixeo';
$string['queued'] = 'En attente';
$string['queuemodaltitle'] = 'File d\'attente de génération';
$string['removefromdisplay'] = 'Retirer de l\'affichage';
$string['removefromqueue'] = 'Retirer de la file';
$string['retry'] = 'Réessayer';
$string['retry_fill_createfailed'] = 'Impossible de créer l\'activité à partir du résultat du remplissage.';
$string['retry_fill_failed'] = 'Le remplissage du module ne s\'est pas terminé.';
$string['retry_fill_notfailed'] = 'Seules les tâches échouées peuvent être relancées de cette façon.';
$string['retry_fill_notfill'] = 'Cette relance s\'applique uniquement aux tâches de remplissage (fill).';
$string['retry_fill_notfound'] = 'Tâche de file d\'attente introuvable pour ce cours.';
$string['retry_fill_timeout'] = 'Le travail de remplissage IA n\'a pas abouti à temps.';
$string['retrygeneration'] = 'Réessayer la génération';
$string['scorm_package_help'] = 'Téléverser un paquet SCORM (.zip)';
$string['scorm_package_invalid'] = 'Le fichier téléversé n\'est pas un paquet SCORM valide.';
$string['status_0'] = 'En attente';
$string['status_1'] = 'Traitement en cours';
$string['status_2'] = 'Terminé';
$string['status_3'] = 'Échoué';
$string['status_4'] = 'Annulé';
$string['success_message'] = 'Une nouvelle tâche de génération de contenu a été ajoutée à la file d\'attente.';
$string['success_title'] = 'Succès !';
$string['task_completed_success'] = 'Activité « <a href="{$a->link}">{$a->name}</a> » créée.';
$string['task_failed'] = 'Échec de la génération du module : {$a->error}';
$string['task_process_modulegen_queue'] = 'Traiter la file d\'attente de génération de modules Dixeo';
$string['taskcancelerror'] = 'Une erreur s\'est produite lors de l\'annulation de la tâche. Veuillez réessayer plus tard.';
$string['taskcancelled'] = 'La tâche a été annulée avec succès.';
$string['timecancelled'] = 'Annulé le : {$a}';
$string['timecompleted'] = 'Terminé le : {$a}';
$string['timecreated'] = 'Créé le : {$a}';
$string['timestarted'] = 'Démarré le : {$a}';
$string['viewinstructions'] = 'Voir les instructions';
