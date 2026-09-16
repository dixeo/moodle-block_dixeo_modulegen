/**
 * Activity chooser for AI module generation.
 *
 * Main entry point for the block's JavaScript functionality. Handles:
 * - Fetching available module types from the API
 * - Transforming module data into categorized display format
 * - Rendering the activity chooser interface
 * - Initializing queue_status and ai_action modules
 *
 * @module     block_dixeo_modulegen/activitychooser
 * @copyright  2026 Edunao SAS (contact@edunao.com)
 * @author     Josemaria Bolanos <admin@mako.digital>
 * @author     Pierre FACQ <pierre.facq@edunao.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define([
    'core/templates',
    'core/ajax',
    'core/str',
    'block_dixeo_modulegen/job_manager',
    'block_dixeo_modulegen/queue_status',
    'block_dixeo_modulegen/ai_action',
    'block_dixeo_modulegen/manual_upload_action',
    'block_dixeo_modulegen/generation_notifications'
], function(Templates, Ajax, Str, JobManager, QueueStatus, AiAction, ManualUploadAction, GenerationNotifications) {
    'use strict';

    let initialized = false;
    let course = null;
    let categories = null;

    /** Ensures we only register document-level drop delegation once (covers CMS/sections added after page load). */
    let courseDropDelegationAttached = false;

    /**
     * True when the page has core course-format activity lists (not formats that replace them entirely).
     *
     * @returns {boolean}
     */
    function hasStandardCourseModuleList() {
        return !!document.querySelector('[data-for="section"] ul[data-for="cmlist"]');
    }

    /** @type {HTMLElement|null} Drop target currently flagged (avoids scanning each dragover). */
    let highlightedGenerationDropTarget = null;
    /** @type {string} Insertion point resolved on the last dragover, replayed on drop. */
    let pendingBeforeMod = '';
    /** @type {HTMLElement|null} Insertion bar drawn while dragging over an activity. */
    let dropMarker = null;

    const CHOOSER_MODAL_OPTION =
        '.optionscontainer .optioninfo a[data-bs-target="#generationModal"], ' +
        '.optionscontainer .optioninfo a[data-bs-target="#manualUploadModal"]';
    const CM_ITEM = 'li.activity[data-for="cmitem"]';
    /** Block-specific hook for theme/format overrides; deliberately unstyled by default. */
    const DROP_TARGET_CLASS = 'dixeo-modulegen-drop-target';
    /** DataTransfer type that marks a drag started from the catalogue. */
    const DRAG_MIME = 'application/x-dixeo-modulegen';
    const DROP_MARKER_CLASS = 'dixeo-modulegen-drop-marker';
    const DROP_MARKER_THICKNESS = 4;
    /** Use capture so we run before core course DragDrop, which often stops propagation on activities. */
    const USE_CAPTURE = true;

    const fallbackIconUrl = M.cfg.wwwroot + '/blocks/dixeo_modulegen/pix/monologo.svg';

    /**
     * Get the icon URL for a module type.
     * If the module is not installed, always use the block fallback to avoid 404s.
     *
     * @param {string} type - The module type (page, label, etc.).
     * @param {string} component - The Moodle component (mod_page, mod_quiz, etc.).
     * @param {boolean} installed - Whether the module plugin is installed.
     * @returns {string} The icon URL.
     */
    const getModuleIconUrl = (type, component, installed) => {
        if (!installed) {
            return fallbackIconUrl;
        }
        if (component && component.startsWith('mod_')) {
            return M.cfg.wwwroot + '/mod/' + component.substring('mod_'.length) + '/pix/monologo.svg';
        }
        return fallbackIconUrl;
    };

    /**
     * Transform the local_dixeo API response to the block UI format.
     *
     * Converts the flat list of types into categorized items with display metadata.
     * All module types from the API are displayed, using Moodle's native styling.
     *
     * @param {Object} response - The API response from local_dixeo_get_module_types.
     * @param {Object} categoryStrings - The localized category name strings.
     * @returns {Object} Categories structure for the template.
     */
    const transformModuleTypes = (response, categoryStrings) => {
        if (!response.success || !response.types || !Array.isArray(response.types)) {
            return {categories: []};
        }

        // Build categories dynamically from API response.
        const categoryMap = {};

        response.types.forEach(moduleType => {
            const category = moduleType.category || 'content';

            // Create category if it doesn't exist.
            if (!categoryMap[category]) {
                categoryMap[category] = {
                    key: category,
                    name: categoryStrings[category] || category,
                    items: []
                };
            }

            const installed = moduleType.installed !== false;
            const item = {
                shortname: moduleType.type,
                displayname: moduleType.label || moduleType.type,
                description: moduleType.description || '',
                iconurl: getModuleIconUrl(moduleType.type, moduleType.component, installed),
                category: category,
                installed: installed,
                component: moduleType.component || ('mod_' + moduleType.type),
                options: {
                    href: '#',
                    enabled: installed && moduleType.supported !== false,
                    beta: false,
                    modalTarget: '#generationModal'
                }
            };

            categoryMap[category].items.push(item);
        });

        // Convert to array, keeping a consistent order (content first, then assessment, interactive last).
        const order = ['content', 'resource', 'collaboration', 'communication', 'assessment', 'interactive'];
        const categoriesArray = order
            .filter(cat => categoryMap[cat])
            .map(cat => categoryMap[cat]);

        // Add any categories not in the predefined order.
        Object.keys(categoryMap).forEach(cat => {
            if (!order.includes(cat)) {
                categoriesArray.push(categoryMap[cat]);
            }
        });

        return {categories: categoriesArray};
    };

    /**
     * Add SCORM and File upload options to the Content category when those mods are installed.
     *
     * @param {Array} categoriesArray Existing categories from API transform.
     * @param {Object} manualConfig Config from PHP (scormInstalled, resourceInstalled).
     * @returns {Promise<Array>} Categories with upload items merged into Content when applicable.
     */
    const appendManualUploadToContent = async(categoriesArray, manualConfig) => {
        if (!manualConfig || (!manualConfig.scormInstalled && !manualConfig.resourceInstalled)) {
            return categoriesArray;
        }

        const stringRequests = [Str.get_string('category_content', 'block_dixeo_modulegen')];
        if (manualConfig.resourceInstalled) {
            stringRequests.push(Str.get_string('modulename', 'mod_resource'));
        }
        if (manualConfig.scormInstalled) {
            stringRequests.push(Str.get_string('modulename', 'mod_scorm'));
        }

        const strings = await Promise.all(stringRequests);
        let index = 0;
        const contentCategoryName = strings[index++];

        let contentCategory = categoriesArray.find((category) => category.key === 'content');
        if (!contentCategory) {
            contentCategory = {
                key: 'content',
                name: contentCategoryName,
                items: [],
            };
            categoriesArray.unshift(contentCategory);
        }

        contentCategory.items = contentCategory.items.filter(
            (item) => item.shortname !== 'scorm' && item.shortname !== 'resource'
        );

        const uploadItems = [];
        if (manualConfig.resourceInstalled) {
            const displayname = strings[index++];
            uploadItems.push({
                shortname: 'resource',
                displayname: displayname,
                iconurl: M.cfg.wwwroot + '/mod/resource/pix/monologo.svg',
                manualUploadType: 'resource',
                installed: true,
                component: 'mod_resource',
                options: {
                    href: '#',
                    enabled: true,
                    modalTarget: '#manualUploadModal',
                },
            });
        }
        if (manualConfig.scormInstalled) {
            const displayname = strings[index++];
            uploadItems.push({
                shortname: 'scorm',
                displayname: displayname,
                iconurl: M.cfg.wwwroot + '/mod/scorm/pix/monologo.svg',
                manualUploadType: 'scorm',
                installed: true,
                component: 'mod_scorm',
                options: {
                    href: '#',
                    enabled: true,
                    modalTarget: '#manualUploadModal',
                },
            });
        }

        contentCategory.items.push(...uploadItems);

        return categoriesArray;
    };

    /**
     * Fetch module types from the local_dixeo API and transform for UI.
     *
     * @param {number} courseId - Course id (required for correct language when course forces a locale).
     * @returns {Promise<Object>} Promise resolving to categories structure.
     */
    const getAvailableModules = async(courseId) => {
        // Fetch category strings and API data in parallel.
        const [contentStr, resourceStr, interactiveStr, assessmentStr, apiResponse] = await Promise.all([
            Str.get_string('category_content', 'block_dixeo_modulegen'),
            Str.get_string('category_resource', 'block_dixeo_modulegen'),
            Str.get_string('category_interactive', 'block_dixeo_modulegen'),
            Str.get_string('category_assessment', 'block_dixeo_modulegen'),
            Ajax.call([{
                methodname: 'local_dixeo_get_module_types',
                args: {courseid: courseId}
            }])[0]
        ]);

        const categoryStrings = {
            content: contentStr,
            resource: resourceStr,
            interactive: interactiveStr,
            assessment: assessmentStr
        };

        return transformModuleTypes(apiResponse, categoryStrings);
    };

    /**
     * Set up the activity chooser.
     *
     * @method init
     * @param {Number} courseId - Course ID to use later on in fetchModules()
     * @param {Object} [manualUploadConfig] - Manual upload config from PHP.
     */
    async function init(courseId, manualUploadConfig) {
        const available = await getAvailableModules(courseId);

        course = courseId;
        categories = await appendManualUploadToContent(available.categories, manualUploadConfig || {});

        // Ensure we only add our listeners once.
        if (initialized) {
            return;
        }

        const block = document.querySelector('.block_dixeo_modulegen');
        if (block) {
            let origin = window.location.pathname.includes('/course/section.php') ? 'section' : 'view';
            const context = {
                courseid: course,
                categories: categories,
                sectionid: findLastSectionId(),
                origin: origin,
                config: {wwwroot: M.cfg.wwwroot},
                generationtitle: ''
            };

            let setupDone = false;
            Templates.render('block_dixeo_modulegen/activitychooser', context)
            .then(function(html, js) {
                const container = block.querySelector('#dixeo-module-generator');
                if (!container) {
                    return undefined;
                }

                initialized = true;
                setupDone = true;
                container.insertAdjacentHTML('beforeend', html);

                if (js) {
                    Templates.runTemplateJS(js);
                }

                registerCategoryToggles(block);
                if (hasStandardCourseModuleList()) {
                    addDragAndDrop(block);
                }

                // Initialize job manager FIRST - it handles all job lifecycle.
                // This must complete before other modules try to submit/poll jobs.
                return JobManager.init(course);
            })
            .then(function() {
                if (!setupDone) {
                    return undefined;
                }
                GenerationNotifications.init(course);
                // Initialize UI modules after job manager is ready.
                QueueStatus.init(course, categories);
                AiAction.init();
                ManualUploadAction.init(manualUploadConfig || {});

                // Trigger a custom event to notify that the chooser is ready.
                document.dispatchEvent(new Event('activityChooserReady'));
                return undefined;
            })
            .catch(function(error) {
                if (!setupDone) {
                    return undefined;
                }
                // Graceful degradation - still initialize UI but log warning.
                // eslint-disable-next-line no-console
                console.error('JobManager init failed:', error);
                GenerationNotifications.init(course);
                QueueStatus.init(course, categories);
                AiAction.init();
                ManualUploadAction.init(manualUploadConfig || {});
                document.dispatchEvent(new Event('activityChooserReady'));
                return undefined;
            });
        }
    }

    /**
     * Register category expand/collapse toggles (no inline handlers).
     *
     * @param {HTMLElement} block - The block element.
     */
    function registerCategoryToggles(block) {
        const optionsContainer = block.querySelector('.optionscontainer');
        if (!optionsContainer) {
            return;
        }
        optionsContainer.addEventListener('click', function(e) {
            const btn = e.target.closest('[data-action="toggle-category"]');
            if (!btn) {
                return;
            }
            e.preventDefault();
            const category = btn.closest('.category');
            if (!category) {
                return;
            }
            const expanded = category.classList.toggle('dixeo-collapsed');
            btn.setAttribute('aria-expanded', expanded ? 'false' : 'true');
        });
    }

    /**
     * Find the id of the last section on the page.
     *
     * @returns {string} The last course_sections id as string, or '0' when the page has no section marker.
     */
    const findLastSectionId = () => {
        const sections = document.querySelectorAll('[data-for="section"][data-id]');
        for (let i = sections.length - 1; i >= 0; i--) {
            const id = parseInt(sections[i].dataset.id, 10);
            if (Number.isFinite(id) && id > 0) {
                return String(id);
            }
        }
        return '0';
    };

    /**
     * Resolve the course-module list item or section header under the cursor.
     * Uses [data-for="section"] so it works across formats (topics, edai, etc.), not only .course-content.
     *
     * @param {EventTarget|null} eventTarget - Event target from drag events.
     * @returns {HTMLElement|null}
     */
    const findGenerationDropTarget = (eventTarget) => {
        if (!eventTarget || !eventTarget.closest) {
            return null;
        }
        const el = /** @type {HTMLElement} */ (eventTarget);
        const activity = el.closest(CM_ITEM);
        if (activity && activity.closest('[data-for="section"]')) {
            return activity;
        }
        const sectionTitle = el.closest('[data-for="section_title"]');
        if (sectionTitle && sectionTitle.closest('[data-for="section"]')) {
            return sectionTitle;
        }
        return null;
    };

    /**
     * Viewport rectangle of an element, or the union of its children's boxes when it has none
     * (formats rendering the cm list item as display: contents).
     *
     * @param {HTMLElement} el - Element to measure.
     * @returns {Object} Rectangle with left, top, right, bottom, width and height.
     */
    const rectOf = (el) => {
        const rect = el.getBoundingClientRect();
        if (rect.width > 0 && rect.height > 0) {
            return rect;
        }
        let union = null;
        Array.prototype.forEach.call(el.children, (child) => {
            const childRect = rectOf(child);
            if (childRect.width <= 0 || childRect.height <= 0) {
                return;
            }
            if (!union) {
                union = {
                    left: childRect.left,
                    top: childRect.top,
                    right: childRect.right,
                    bottom: childRect.bottom
                };
                return;
            }
            union.left = Math.min(union.left, childRect.left);
            union.top = Math.min(union.top, childRect.top);
            union.right = Math.max(union.right, childRect.right);
            union.bottom = Math.max(union.bottom, childRect.bottom);
        });
        if (!union) {
            return rect;
        }
        union.width = union.right - union.left;
        union.height = union.bottom - union.top;
        return union;
    };

    /**
     * True when two rectangles overlap vertically enough to sit on the same row.
     *
     * @param {Object} a - First rectangle.
     * @param {Object} b - Second rectangle.
     * @returns {boolean}
     */
    const sharesRow = (a, b) => {
        const overlap = Math.min(a.bottom, b.bottom) - Math.max(a.top, b.top);
        return overlap > Math.min(a.height, b.height) / 2;
    };

    /**
     * Whether a viewport point falls inside a rectangle.
     *
     * @param {Object} rect - Rectangle to test.
     * @param {number} x - Point x in viewport coordinates.
     * @param {number} y - Point y in viewport coordinates.
     * @returns {boolean}
     */
    const rectContains = (rect, x, y) => x >= rect.left && x <= rect.right && y >= rect.top && y <= rect.bottom;

    /**
     * Nearest sibling activity of a course-module list item.
     *
     * @param {HTMLElement} el - Course-module list item.
     * @param {boolean} forward - Look after the item instead of before it.
     * @returns {HTMLElement|null}
     */
    const siblingCmItem = (el, forward) => {
        let sibling = forward ? el.nextElementSibling : el.previousElementSibling;
        while (sibling && !sibling.matches(CM_ITEM)) {
            sibling = forward ? sibling.nextElementSibling : sibling.previousElementSibling;
        }
        return sibling;
    };

    /**
     * Middle of the gap between the target edge and its neighbour, or that edge when there is none.
     *
     * @param {number} inner - Target edge on the insertion side.
     * @param {number|null} outer - Facing neighbour edge.
     * @param {boolean} after - Inserting after the target.
     * @returns {number}
     */
    const gapCentre = (inner, outer, after) => {
        if (outer === null || (after ? outer < inner : outer > inner)) {
            return inner;
        }
        return (inner + outer) / 2;
    };

    /**
     * Where a drop over an activity inserts, and where to draw the insertion bar.
     *
     * @param {HTMLElement} target - Activity under the pointer.
     * @param {number} clientX - Pointer x in viewport coordinates.
     * @param {number} clientY - Pointer y in viewport coordinates.
     * @returns {Object} beforeMod plus the marker placement (horizontal, x, y, length).
     */
    const describeDrop = (target, clientX, clientY) => {
        const rect = rectOf(target);
        const prev = siblingCmItem(target, false);
        const next = siblingCmItem(target, true);
        const prevRect = prev ? rectOf(prev) : null;
        const nextRect = next ? rectOf(next) : null;
        // A neighbour on the same row means the section flows left to right (grid, tiles, cards).
        const horizontal = !!((prevRect && sharesRow(rect, prevRect)) || (nextRect && sharesRow(rect, nextRect)));
        let after = clientY > rect.top + rect.height / 2;
        if (horizontal) {
            after = clientX > rect.left + rect.width / 2;
        }

        let beforeMod = target.dataset.id || '';
        if (after) {
            beforeMod = next ? (next.dataset.id || '') : '';
        }

        const neighbour = after ? nextRect : prevRect;
        let outer = null;
        if (horizontal) {
            if (neighbour && sharesRow(rect, neighbour)) {
                outer = after ? neighbour.left : neighbour.right;
            }
            const inner = after ? rect.right : rect.left;
            return {
                beforeMod: beforeMod,
                horizontal: true,
                x: gapCentre(inner, outer, after),
                y: rect.top,
                length: rect.height
            };
        }
        if (neighbour) {
            outer = after ? neighbour.top : neighbour.bottom;
        }
        const inner = after ? rect.bottom : rect.top;
        return {
            beforeMod: beforeMod,
            horizontal: false,
            x: rect.left,
            y: gapCentre(inner, outer, after),
            length: rect.width
        };
    };

    /**
     * Insertion point for a drop: id of the module to insert before, empty for the end of the section.
     *
     * @param {HTMLElement} target - Drop target under the pointer.
     * @param {number} clientX - Pointer x in viewport coordinates.
     * @param {number} clientY - Pointer y in viewport coordinates.
     * @returns {string}
     */
    const resolveBeforeMod = (target, clientX, clientY) => {
        if (target.matches(CM_ITEM)) {
            return describeDrop(target, clientX, clientY).beforeMod;
        }
        const next = target.nextElementSibling;
        return (next && next.dataset && next.dataset.id) ? next.dataset.id : '';
    };

    /**
     * Draw the insertion bar at a computed placement.
     *
     * @param {Object} placement - Placement returned by describeDrop().
     */
    const showDropMarker = (placement) => {
        if (!dropMarker) {
            dropMarker = document.createElement('div');
            dropMarker.className = DROP_MARKER_CLASS;
            dropMarker.setAttribute('aria-hidden', 'true');
            document.body.appendChild(dropMarker);
        }
        const half = DROP_MARKER_THICKNESS / 2;
        dropMarker.style.left = (placement.horizontal ? placement.x - half : placement.x) + 'px';
        dropMarker.style.top = (placement.horizontal ? placement.y : placement.y - half) + 'px';
        dropMarker.style.width = (placement.horizontal ? DROP_MARKER_THICKNESS : placement.length) + 'px';
        dropMarker.style.height = (placement.horizontal ? placement.length : DROP_MARKER_THICKNESS) + 'px';
    };

    /** Remove the insertion bar. */
    const hideDropMarker = () => {
        if (dropMarker) {
            dropMarker.remove();
            dropMarker = null;
        }
    };

    /**
     * Wire generation links as drag sources and course sections/activities as drop targets.
     * On drop, sets data-section-id / data-before-mod on the link and triggers click to open the modal.
     *
     * @param {HTMLElement} block - The activity chooser block element.
     */
    const addDragAndDrop = function(block) {
        // Mobile devices don't support drag and drop well.
        if (window.innerWidth <= 992) {
            return;
        }

        const options = block.querySelectorAll(CHOOSER_MODAL_OPTION);
        options.forEach(function(option) {
            if (option.classList.contains('disabled')) {
                return;
            }

            option.classList.add('draggable');
            option.setAttribute('draggable', 'true');

            // Dragstart runs before any dragover; the drag event can fire too late for document delegation.
            option.addEventListener('dragstart', function(ev) {
                option.classList.add('dragging');
                const dt = ev.dataTransfer;
                if (!dt) {
                    return;
                }
                try {
                    dt.setData(DRAG_MIME, option.getAttribute('data-module-name') || '');
                    dt.effectAllowed = 'copyMove';
                } catch (e) {
                    // DataTransfer#setData may throw in restricted drag contexts.
                }
            });

            option.addEventListener('dragend', function() {
                option.classList.remove('dragging');
            });
        });

        if (courseDropDelegationAttached) {
            return;
        }
        courseDropDelegationAttached = true;

        const getActiveDragOption = () => block.querySelector('.optioninfo a.dragging');

        /**
         * Whether a drag event carries a catalogue option, as opposed to any other drag on the page.
         *
         * @param {DragEvent} e
         * @returns {boolean}
         */
        const isCatalogueDrag = (e) => {
            const types = e.dataTransfer ? e.dataTransfer.types : null;
            return !!types && Array.prototype.includes.call(types, DRAG_MIME);
        };

        const clearDropFeedback = () => {
            hideDropMarker();
            if (highlightedGenerationDropTarget) {
                highlightedGenerationDropTarget.classList.remove(DROP_TARGET_CLASS);
                highlightedGenerationDropTarget = null;
            }
            pendingBeforeMod = '';
        };

        document.addEventListener('dragover', (e) => {
            if (!isCatalogueDrag(e) || !getActiveDragOption()) {
                clearDropFeedback();
                return;
            }
            const dropEl = findGenerationDropTarget(e.target);
            if (!dropEl) {
                clearDropFeedback();
                return;
            }
            e.preventDefault();
            const dt = e.dataTransfer;
            if (dt) {
                dt.dropEffect = 'copy';
            }
            if (highlightedGenerationDropTarget !== dropEl) {
                clearDropFeedback();
                highlightedGenerationDropTarget = dropEl;
                dropEl.classList.add(DROP_TARGET_CLASS);
            }
            if (dropEl.matches(CM_ITEM)) {
                const placement = describeDrop(dropEl, e.clientX, e.clientY);
                pendingBeforeMod = placement.beforeMod;
                showDropMarker(placement);
                return;
            }
            // Section title: appends to the section, so there is no insertion point to draw.
            pendingBeforeMod = resolveBeforeMod(dropEl, e.clientX, e.clientY);
            hideDropMarker();
        }, USE_CAPTURE);

        document.addEventListener('dragleave', (e) => {
            if (highlightedGenerationDropTarget
                    && !rectContains(rectOf(highlightedGenerationDropTarget), e.clientX, e.clientY)) {
                clearDropFeedback();
            }
        }, USE_CAPTURE);

        document.addEventListener('drop', (e) => {
            const activeOption = isCatalogueDrag(e) ? getActiveDragOption() : null;
            if (!activeOption) {
                return;
            }
            const dropEl = findGenerationDropTarget(e.target);
            if (!dropEl) {
                return;
            }
            e.preventDefault();
            e.stopPropagation();

            // The dragover decision is replayed; the pointer has not moved since.
            let beforeMod = pendingBeforeMod;
            if (highlightedGenerationDropTarget !== dropEl) {
                beforeMod = resolveBeforeMod(dropEl, e.clientX, e.clientY);
            }
            clearDropFeedback();

            const section = dropEl.closest('[data-for="section"]');
            if (!section || section.dataset.id === undefined) {
                return;
            }

            activeOption.dataset.sectionId = section.dataset.id;
            activeOption.dataset.beforeMod = beforeMod;
            activeOption.click();
        }, USE_CAPTURE);

        document.addEventListener('dragend', () => {
            clearDropFeedback();
            block.querySelectorAll('.optioninfo a.dragging').forEach((o) => o.classList.remove('dragging'));
        });
    };

    return {
        init: init
    };
});
