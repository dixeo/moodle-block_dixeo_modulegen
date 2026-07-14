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

$string['activequeued'] = 'Ativos/Na fila';
$string['add'] = 'Adicionar';
$string['aiactivities'] = 'Gerador de Conteúdo Dixeo';
$string['blocktitle'] = 'Adicionar conteúdo gerado por IA';
$string['cancelgeneration'] = 'Cancelar geração';
$string['cancelled'] = 'Cancelado';
$string['canceltask'] = 'Cancelar';
$string['canceltaskconfirm'] = 'Tem a certeza de que deseja cancelar esta tarefa? Esta ação não pode ser desfeita.';
$string['category_assessment'] = 'Avaliação';
$string['category_content'] = 'Conteúdo';
$string['category_interactive'] = 'Interativo';
$string['category_resource'] = 'Recursos';
$string['completed'] = 'Concluído';
$string['completedon'] = 'Concluído em {$a}';
$string['copyinstructions'] = 'Copiar instruções';
$string['dixeo_modulegen:addinstance'] = 'Adicionar um bloco Gerador de Conteúdo Dixeo';
$string['dixeo_modulegen:myaddinstance'] = 'Adicionar um bloco Gerador de Conteúdo Dixeo ao Painel';
$string['error_queue_failed'] = 'Falha ao adicionar a tarefa à fila de geração.';
$string['error_title'] = 'Ops!';
$string['error_unsupported_module'] = 'Tipo de módulo não suportado: {$a}';
$string['filltask_defaulttitle'] = 'Nova atividade';
$string['generate'] = 'Gerar';
$string['generation_complete'] = 'O seu conteúdo foi gerado com sucesso! Atualize a página para o ver.';
$string['generationcancelled'] = 'Geração cancelada';
$string['generationerror'] = 'Erro de geração';
$string['generationfailed'] = 'Geração falhou';
$string['generationinprogress'] = 'Geração em curso (<span class="elapsed-time">0:00</span>)';
$string['generationqueued'] = 'À espera na fila';
$string['idle'] = 'Inativo';
$string['instructionscopied'] = 'Instruções copiadas';
$string['loading'] = 'A gerar...';
$string['manual_upload_browse'] = 'Escolher um ficheiro';
$string['manual_upload_drag'] = 'Arraste um ficheiro para aqui ou clique para procurar';
$string['manual_upload_error_failed'] = 'Não foi possível criar a atividade.';
$string['manual_upload_error_file_too_large'] = 'O ficheiro é demasiado grande. Carregue um ficheiro com menos de {$a->maxsize}.';
$string['manual_upload_error_invalid_resource'] = 'Só são aceites estes formatos de ficheiro: {$a->ragformats}.';
$string['manual_upload_error_invalid_scorm'] = 'Só são aceites pacotes SCORM Articulate Storyline (.zip).';
$string['manual_upload_error_missing'] = 'O ficheiro é obrigatório.';
$string['manual_upload_resource_description'] = 'Formatos aceites: {$a->ragformats}. (Máx. {$a->maxsize})';
$string['manual_upload_scorm_description'] = 'Apenas pacotes SCORM Articulate Storyline (.zip).';
$string['manual_upload_success'] = 'Atividade « <a href="{$a->link}">{$a->name}</a> » adicionada. A sincronização de ficheiros foi iniciada.';
$string['manual_upload_uploading'] = 'A carregar...';
$string['needsattention'] = 'Requer atenção';
$string['newmoduletype'] = 'Novo {$a}';
$string['next'] = 'Seguinte';
$string['noinstructions'] = 'Sem instruções para esta tarefa.';
$string['notasksinthequeue'] = 'A fila de tarefas está atualmente vazia.';
$string['notavailable'] = 'Este módulo não está disponível ou não está configurado corretamente. Tente novamente mais tarde ou contacte o seu administrador.';
$string['opengeneratorqueue'] = 'Abrir fila do gerador';
$string['pluginname'] = 'Gerador de Conteúdo Dixeo';
$string['pluginrequired'] = 'Instale o plugin {$a} para gerar este tipo de atividade.';
$string['processing'] = 'A processar';
$string['prompt_placeholder'] = 'Instruções de geração para Dixeo';
$string['queue_manual_upload_label'] = 'Carregamento manual';
$string['queue_processor'] = 'Processador da Fila de Geração de Conteúdo Dixeo';
$string['queued'] = 'Na fila';
$string['queuemodaltitle'] = 'Fila de Geração';
$string['removefromdisplay'] = 'Remover da visualização';
$string['removefromqueue'] = 'Remover da fila';
$string['retry'] = 'Repetir';
$string['retry_fill_createfailed'] = 'Não foi possível criar a atividade a partir do resultado do preenchimento.';
$string['retry_fill_failed'] = 'O preenchimento do módulo não foi concluído.';
$string['retry_fill_notfailed'] = 'Apenas tarefas falhadas podem ser repetidas desta forma.';
$string['retry_fill_notfill'] = 'Esta repetição aplica-se apenas a tarefas de preenchimento (fill).';
$string['retry_fill_notfound'] = 'Tarefa na fila não encontrada para este curso.';
$string['retry_fill_timeout'] = 'O trabalho de preenchimento IA não foi concluído a tempo.';
$string['retrygeneration'] = 'Repetir geração';
$string['scorm_package_help'] = 'Carregar um pacote SCORM (.zip)';
$string['scorm_package_invalid'] = 'O ficheiro carregado não é um pacote SCORM válido.';
$string['status_0'] = 'Pendente';
$string['status_1'] = 'A processar';
$string['status_2'] = 'Concluído';
$string['status_3'] = 'Falhou';
$string['status_4'] = 'Cancelado';
$string['success_message'] = 'Uma nova tarefa de geração de conteúdo foi adicionada à fila.';
$string['success_title'] = 'Sucesso!';
$string['task_completed_success'] = 'Atividade « <a href="{$a->link}">{$a->name}</a> » criada.';
$string['task_failed'] = 'Falha na geração do módulo: {$a->error}';
$string['task_process_modulegen_queue'] = 'Processar fila de geração de módulos Dixeo';
$string['taskcancelerror'] = 'Ocorreu um erro ao tentar cancelar a tarefa. Tente novamente mais tarde.';
$string['taskcancelled'] = 'A tarefa foi cancelada com sucesso.';
$string['timecancelled'] = 'Cancelado em: {$a}';
$string['timecompleted'] = 'Concluído em: {$a}';
$string['timecreated'] = 'Criado em: {$a}';
$string['timestarted'] = 'Iniciado em: {$a}';
$string['viewinstructions'] = 'Ver instruções';
