/**
 * In-page notifications for module generation and manual upload completion.
 *
 * @module     block_dixeo_modulegen/generation_notifications
 * @copyright  2026 Edunao SAS (contact@edunao.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define([
    'core/notification',
    'core/str'
], function(Notification, Str) {
    'use strict';

    /** @type {number|null} */
    let courseId = null;

    /** @type {boolean} */
    let initialized = false;

    /**
     * Whether the current page is course content (view, section, or module).
     *
     * @returns {boolean}
     */
    const isOnCourseContentPage = () => {
        const path = window.location.pathname;
        if (path.includes('/course/view.php') || path.includes('/course/section.php')) {
            return true;
        }
        return document.body && document.body.classList.contains('path-mod');
    };

    /**
     * Whether a toast should be shown for the current page context.
     *
     * @param {Object|null} detail Event detail.
     * @returns {boolean}
     */
    const shouldNotify = (detail) => {
        if (!detail) {
            return false;
        }

        const eventCourseId = parseInt(detail.courseid, 10);
        if (!courseId || Number.isNaN(eventCourseId) || eventCourseId !== courseId) {
            return false;
        }

        const pageCourseId = parseInt(M.cfg.courseId, 10);
        if (Number.isNaN(pageCourseId) || pageCourseId !== courseId) {
            return false;
        }

        return isOnCourseContentPage();
    };

    /**
     * Escape plain text for an HTML attribute or text node context.
     *
     * @param {string} text
     * @returns {string}
     */
    const escapeHtml = (text) => {
        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    };

    /**
     * Accept only same-origin Moodle activity view URLs.
     *
     * @param {string} url
     * @returns {string|null} Sanitized absolute URL, or null if rejected.
     */
    const sanitizeActivityLink = (url) => {
        if (!url || typeof url !== 'string') {
            return null;
        }
        try {
            const root = new URL(M.cfg.wwwroot);
            const parsed = new URL(url, root);
            if (parsed.origin !== root.origin) {
                return null;
            }
            if (parsed.username || parsed.password) {
                return null;
            }
            if (!/^\/mod\/[a-z][a-z0-9_]*\/view\.php$/i.test(parsed.pathname)) {
                return null;
            }
            const id = parsed.searchParams.get('id');
            if (!id || !/^\d+$/.test(id)) {
                return null;
            }
            // Rebuild to drop unexpected query params / fragments.
            return root.origin + parsed.pathname + '?id=' + id;
        } catch (e) {
            return null;
        }
    };

    /**
     * Build escaped params for success strings that embed an activity link.
     *
     * @param {string} name
     * @param {string} link
     * @returns {{link: string, name: string}|null} Null when the link is not safe.
     */
    const buildSuccessParams = (name, link) => {
        const safeLink = sanitizeActivityLink(link);
        if (!safeLink || !name) {
            return null;
        }
        return {
            // Escape for href="..." and text-node contexts inside the lang string HTML.
            link: escapeHtml(safeLink),
            name: escapeHtml(String(name)),
        };
    };

    /**
     * @param {string} type success|error
     * @param {string} message Pre-cleaned HTML message.
     */
    const showNotification = (type, message) => {
        Notification.addNotification({
            type: type,
            message: message,
        });
    };

    /**
     * Resolve a lang string with already-escaped params, then show a toast.
     *
     * @param {string} type success|error
     * @param {string} stringKey
     * @param {Object|string} params
     * @returns {Promise<void>}
     */
    const showFromString = (type, stringKey, params) => {
        return Str.get_string(stringKey, 'block_dixeo_modulegen', params).then((message) => {
            showNotification(type, message);
            return undefined;
        }).catch(() => {
            // Lang string missing or fetch failed — skip silently.
            return undefined;
        });
    };

    /**
     * @param {CustomEvent} event job-completed event.
     */
    const handleJobCompleted = (event) => {
        const detail = event.detail || {};
        if (detail.source === 'manual') {
            return;
        }
        if (!shouldNotify(detail)) {
            return;
        }
        const mode = detail.queuemode || 'generate';
        if (mode === 'fill' || mode === 'manual') {
            return;
        }

        const params = buildSuccessParams(
            detail.displaytitle || detail.modulename || '',
            detail.link || ''
        );
        if (!params) {
            return;
        }

        showFromString('success', 'task_completed_success', params);
    };

    /**
     * @param {CustomEvent} event job-failed event.
     */
    const handleJobFailed = (event) => {
        const detail = event.detail || {};
        if (!shouldNotify(detail)) {
            return;
        }
        const mode = detail.queuemode || 'generate';
        if (mode === 'fill' || mode === 'manual') {
            return;
        }

        showFromString('error', 'task_failed', {
            error: escapeHtml(String(detail.error || '')),
        });
    };

    return {
        /**
         * Listen for generate job completion/failure toasts.
         *
         * @param {number} cid Course ID for the block context.
         */
        init: function(cid) {
            const id = parseInt(cid, 10);
            if (initialized && courseId === id) {
                return;
            }

            courseId = id;
            initialized = true;

            document.addEventListener('job-completed', handleJobCompleted);
            document.addEventListener('job-failed', handleJobFailed);
        },

        /**
         * Success toast after manual upload (includes sync started message).
         *
         * @param {Object} payload
         * @param {string} payload.link Activity view URL.
         * @param {string} payload.name Activity display name.
         * @param {number} payload.courseid Course ID.
         * @returns {Promise<void>}
         */
        showManualUploadSuccess: function(payload) {
            if (!shouldNotify(payload)) {
                return Promise.resolve();
            }
            const params = buildSuccessParams(payload.name || '', payload.link || '');
            if (!params) {
                return Promise.resolve();
            }
            return showFromString('success', 'manual_upload_success', params);
        },

        /**
         * Error toast for manual upload or validation failures.
         *
         * @param {string} message Error message (treated as plain text).
         */
        showError: function(message) {
            if (message === null || message === undefined || message === '') {
                return;
            }
            showNotification('error', escapeHtml(String(message)));
        },
    };
});
