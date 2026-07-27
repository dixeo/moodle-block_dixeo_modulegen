/**
 * AI action handler for module generation.
 *
 * Handles the generation modal form submission and UI state. This module is UI-only:
 * - Modal display and form handling
 * - Form validation and state management
 * - Prevents modal closing during generation
 * - Delegates job submission to job_manager.js
 * - Refreshes course sections when modules are created
 *
 * @module     block_dixeo_modulegen/ai_action
 * @copyright  2026 Edunao SAS (contact@edunao.com)
 * @author     Josemaria Bolanos <admin@mako.digital>
 * @author     Pierre FACQ <pierre.facq@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define([
    'jquery',
    'core/notification',
    'core/str',
    'core/local/aria/focuslock',
    'block_dixeo_modulegen/job_manager',
    'block_dixeo_modulegen/course_section_refresh'
], function($, Notification, Str, FocusLock, JobManager, CourseSectionRefresh) {
    'use strict';

    let isModalClosingDisabled = false;
    let initialized = false;
    /** @type {Object|null} Set before opening modal for retry; consumed in handleModalShow. */
    let retryContext = null;

    return {
        init: function() {
            // Prevent double initialization (loaded by both PHP and modal template).
            if (initialized) {
                return;
            }

            const generationModal = document.getElementById('generationModal');
            if (!generationModal) {
                return;
            }

            initialized = true;
            isModalClosingDisabled = false;

            // Move modal to body for proper z-index stacking.
            document.body.appendChild(generationModal);

            // Prevent closing during generation.
            $(generationModal).on('hide.bs.modal', function(event) {
                if (isModalClosingDisabled) {
                    event.preventDefault();
                }
            });

            $(generationModal).on('show.bs.modal', this.handleModalShow.bind(this));

            document.addEventListener('generationModalRetry', (event) => {
                if (event.detail) {
                    retryContext = event.detail;
                    $(generationModal).modal('show');
                }
            });

            // Listen for job completion to refresh course section.
            document.addEventListener('job-completed', (event) => {
                const detail = event.detail;
                if (detail && typeof detail.sectionNumber !== 'undefined') {
                    CourseSectionRefresh.refreshCourseSection(detail.sectionNumber);
                }
            });
        },

        /**
         * Prefill the generation modal for retry of a failed task.
         *
         * @param {Object} ctx - Retry context from generationModalRetry.
         * @param {Object} els - Modal form elements.
         * @param {Element|null} els.titleElement
         * @param {HTMLInputElement|null} els.beforeModInput
         * @param {HTMLInputElement|null} els.modulenameInput
         * @param {HTMLInputElement|null} els.sectionnumberInput
         * @param {HTMLInputElement|null} els.courseidInput
         * @param {HTMLInputElement|null} els.retryTaskIdInput
         * @param {HTMLTextAreaElement|null} els.instructionsTextarea
         * @param {Element|null} els.generateButton
         */
        applyRetryContext: function(ctx, els) {
            Str.get_string('retrygeneration', 'block_dixeo_modulegen').then((s) => {
                if (els.titleElement) {
                    els.titleElement.textContent = s;
                }
                return undefined;
            }).catch(() => undefined);
            if (els.beforeModInput) {
                els.beforeModInput.value = ctx.beforemod || '0';
            }
            if (els.modulenameInput) {
                els.modulenameInput.value = ctx.modulename || '';
            }
            if (els.sectionnumberInput) {
                els.sectionnumberInput.value = ctx.sectionnumber || '0';
            }
            if (els.courseidInput) {
                els.courseidInput.value = ctx.courseid || '';
            }
            if (els.retryTaskIdInput) {
                els.retryTaskIdInput.value = ctx.taskId || '';
            }
            if (els.instructionsTextarea) {
                els.instructionsTextarea.value = ctx.instructions || '';
                els.instructionsTextarea.readOnly = false;
                this.initializeAutoResize(els.instructionsTextarea);
                els.instructionsTextarea.addEventListener('keydown', function(ev) {
                    if (ev.key === 'Enter' && !ev.shiftKey) {
                        ev.preventDefault();
                        els.generateButton.click();
                    }
                });
            }
        },

        /**
         * Prefill the generation modal from the activity chooser button that opened it.
         *
         * @param {Event} event - The show.bs.modal event.
         * @param {Object} els - Modal form elements (same shape as applyRetryContext).
         */
        applyChooserButtonContext: function(event, els) {
            if (els.retryTaskIdInput) {
                els.retryTaskIdInput.value = '';
            }
            const button = event.relatedTarget;
            if (button) {
                const modalTitle = button.getAttribute('data-modal-title');
                const moduleName = button.getAttribute('data-module-name');
                const sectionNumber = button.getAttribute('data-section-number') ?? 0;
                const beforeMod = button.getAttribute('data-before-mod');

                if (els.titleElement) {
                    els.titleElement.textContent = modalTitle;
                }
                if (els.beforeModInput) {
                    els.beforeModInput.value = beforeMod;
                }
                if (els.modulenameInput) {
                    els.modulenameInput.value = moduleName;
                }
                if (els.sectionnumberInput) {
                    els.sectionnumberInput.value = sectionNumber;
                }
            }

            if (els.instructionsTextarea) {
                els.instructionsTextarea.value = '';
                els.instructionsTextarea.readOnly = false;
                this.initializeAutoResize(els.instructionsTextarea);
                els.instructionsTextarea.addEventListener('keydown', function(ev) {
                    if (ev.key === 'Enter' && !ev.shiftKey) {
                        ev.preventDefault();
                        els.generateButton.click();
                    }
                });
            }
        },

        /**
         * Handle modal show event - set up form data and handlers.
         *
         * @param {Event} event - The show.bs.modal event.
         */
        handleModalShow: function(event) {
            const generationModal = document.getElementById('generationModal');
            FocusLock.untrapFocus();
            isModalClosingDisabled = false;

            const form = generationModal.querySelector('form');
            const closeButton = generationModal.querySelector('.close');
            const generateButton = generationModal.querySelector('#generate_button');

            const els = {
                titleElement: generationModal.querySelector('.modal-title'),
                beforeModInput: generationModal.querySelector('input[name="beforemod"]'),
                modulenameInput: generationModal.querySelector('input[name="modulename"]'),
                sectionnumberInput: generationModal.querySelector('input[name="sectionnumber"]'),
                courseidInput: generationModal.querySelector('input[name="courseid"]'),
                retryTaskIdInput: generationModal.querySelector('input[name="retry_task_id"]'),
                instructionsTextarea: generationModal.querySelector('#instructions'),
                generateButton: generateButton,
            };

            if (retryContext) {
                this.applyRetryContext(retryContext, els);
                retryContext = null;
            } else {
                this.applyChooserButtonContext(event, els);
            }

            if (closeButton) {
                closeButton.classList.remove('disabled');
                closeButton.style.pointerEvents = 'auto';
            }

            if (form) {
                $(form).off('submit');
                $(form).on('submit', (submitEvent) => {
                    this.handleGenerationForm(submitEvent, form);
                });
            }

            document.dispatchEvent(new Event('generationModalReady'));
        },

        /**
         * Show a generation error alert.
         *
         * @param {Error|string} failure
         * @returns {Promise}
         */
        showGenerationError: function(failure) {
            return Str.get_string('error_title', 'block_dixeo_modulegen')
                .then((title) => {
                    Notification.alert(title, failure.message || String(failure));
                    return undefined;
                })
                .catch(() => undefined);
        },

        /**
         * Handle form submission for module generation.
         *
         * @param {Event} event - The submit event.
         * @param {HTMLFormElement} form - The form element.
         */
        handleGenerationForm: async function(event, form) {
            event.preventDefault();

            const closeButton = form.querySelector('.close');
            const generateButton = form.querySelector('#generate_button');
            const instructionsTextarea = form.querySelector('#instructions');
            const retryTaskIdInput = form.querySelector('input[name="retry_task_id"]');

            if (!generateButton || !instructionsTextarea) {
                const message = await Str.get_string('error_required_elements', 'block_dixeo_modulegen');
                Notification.exception({message});
                return;
            }

            // Lock UI during submission.
            generateButton.disabled = true;
            instructionsTextarea.readOnly = true;
            isModalClosingDisabled = true;

            if (closeButton) {
                closeButton.classList.add('disabled');
                closeButton.style.pointerEvents = 'none';
            }

            const args = {
                courseid: parseInt(form.courseid.value, 10),
                modulename: form.modulename.value,
                instructions: form.instructions.value,
                sectionnumber: parseInt(form.sectionnumber.value, 10),
                beforemod: parseInt(form.beforemod.value, 10),
            };

            const doSubmit = () => {
                return JobManager.submitJob(args)
                    .then(() => {
                        this.resetFormState(form, closeButton, generateButton, instructionsTextarea, true);
                        if (retryTaskIdInput) {
                            retryTaskIdInput.value = '';
                        }
                        document.dispatchEvent(new Event('newTaskAdded'));
                        return undefined;
                    })
                    .catch((error) => {
                        this.resetFormState(form, closeButton, generateButton, instructionsTextarea, false);
                        return this.showGenerationError(error);
                    });
            };

            // Retry: delete the failed task then create a new one with the form data.
            const retryTaskId = retryTaskIdInput ? retryTaskIdInput.value.trim() : '';
            if (retryTaskId) {
                JobManager.removeTask(parseInt(retryTaskId, 10))
                    .then(() => {
                        if (retryTaskIdInput) {
                            retryTaskIdInput.value = '';
                        }
                        return doSubmit();
                    })
                    .catch((error) => {
                        this.resetFormState(form, closeButton, generateButton, instructionsTextarea, false);
                        return this.showGenerationError(error);
                    });
            } else {
                doSubmit().catch(() => undefined);
            }
        },

        /**
         * Reset form to initial state after success or error.
         *
         * On success: re-enables controls, clears instructions, closes modal.
         * On error: re-enables controls only so the user can edit and retry without losing input.
         *
         * @param {HTMLFormElement} form - The form element.
         * @param {Element} closeButton - The close button.
         * @param {Element} generateButton - The generate button.
         * @param {Element} textarea - The instructions textarea.
         * @param {boolean} [closeModal=true] - If true, close the modal (success path); if false, keep modal open (error path).
         */
        resetFormState: function(form, closeButton, generateButton, textarea, closeModal = true) {
            generateButton.disabled = false;
            textarea.readOnly = false;
            if (closeModal) {
                textarea.value = '';
            }
            isModalClosingDisabled = false;

            if (closeButton) {
                closeButton.classList.remove('disabled');
                closeButton.style.pointerEvents = 'auto';
                if (closeModal) {
                    const span = closeButton.querySelector('span');
                    if (span) {
                        span.click();
                    }
                }
            }
        },

        /**
         * Initialize auto-resizing for textarea.
         *
         * @param {HTMLTextAreaElement} textarea - The textarea element.
         */
        initializeAutoResize: function(textarea) {
            const maxLines = 9;
            const minLines = 3;
            const computedStyle = getComputedStyle(textarea);
            const lineHeight = parseInt(computedStyle.lineHeight);

            const minHeight = lineHeight * minLines;
            const maxHeight = lineHeight * maxLines;

            const adjustHeight = function() {
                textarea.style.height = 'auto';
                let newHeight = textarea.scrollHeight;

                if (newHeight < minHeight) {
                    newHeight = minHeight;
                }

                if (newHeight <= maxHeight) {
                    textarea.style.height = newHeight + 'px';
                    textarea.style.overflowY = 'hidden';
                } else {
                    textarea.style.height = maxHeight + 'px';
                    textarea.style.overflowY = 'scroll';
                }
            };

            adjustHeight();
            textarea.removeEventListener('input', adjustHeight);
            textarea.addEventListener('input', adjustHeight);
        }
    };
});
