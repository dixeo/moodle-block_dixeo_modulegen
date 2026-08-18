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
 * Tests for notification escaping before HTML lang strings (DIXEO-SEC-007).
 *
 * Mirrors the contract in amd/src/generation_notifications.js: escape name/error
 * and validate activity links before core/str substitution into core/notification.
 *
 * @package    block_dixeo_modulegen
 * @category   test
 * @copyright  2026 Edunao SAS (contact@edunao.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_dixeo_modulegen;

/**
 * Language strings may contain fixed HTML; dynamic values must be escaped first.
 *
 * @coversNothing
 */
final class notification_message_test extends \advanced_testcase {
    /** Hostile name/error payload from the MindFree exploit scenario. */
    private const HOSTILE_MARKUP = '<img src=x onerror=window.__dixeo_xss=1>';

    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * Mirror of generation_notifications.js sanitizeActivityLink.
     *
     * @param string $url Absolute or site-relative URL.
     * @return string|null Sanitized absolute URL, or null if rejected.
     */
    private static function sanitize_activity_link(string $url): ?string {
        global $CFG;

        $url = trim($url);
        if ($url === '') {
            return null;
        }

        $parts = parse_url($url);
        if ($parts === false) {
            return null;
        }

        $root = parse_url($CFG->wwwroot);
        if ($root === false || empty($root['scheme']) || empty($root['host'])) {
            return null;
        }

        if (empty($parts['scheme'])) {
            $path = $parts['path'] ?? '';
            if (str_starts_with($path, '/')) {
                $url = $CFG->wwwroot . $path . (isset($parts['query']) ? '?' . $parts['query'] : '');
            } else {
                $url = $CFG->wwwroot . '/' . ltrim($path, '/') . (isset($parts['query']) ? '?' . $parts['query'] : '');
            }
            $parts = parse_url($url);
            if ($parts === false) {
                return null;
            }
        }

        $origin = ($parts['scheme'] ?? '') . '://' . ($parts['host'] ?? '');
        if (!empty($parts['port'])) {
            $origin .= ':' . $parts['port'];
        }
        $rootorigin = $root['scheme'] . '://' . $root['host'];
        if (!empty($root['port'])) {
            $rootorigin .= ':' . $root['port'];
        }
        if (strcasecmp($origin, $rootorigin) !== 0) {
            return null;
        }
        if (!empty($parts['user']) || !empty($parts['pass'])) {
            return null;
        }

        $path = $parts['path'] ?? '';
        $rootpath = rtrim($root['path'] ?? '', '/');
        if ($rootpath !== '' && str_starts_with($path, $rootpath)) {
            $sitepath = substr($path, strlen($rootpath));
        } else {
            $sitepath = $path;
        }
        if (!preg_match('#^/mod/[a-z][a-z0-9_]*/view\.php$#i', $sitepath)) {
            return null;
        }

        parse_str($parts['query'] ?? '', $query);
        $id = $query['id'] ?? null;
        if ($id === null || $id === '' || !preg_match('/^\d+$/', (string) $id)) {
            return null;
        }

        return $origin . $path . '?id=' . $id;
    }

    /**
     * Mirror of generation_notifications.js success-string param building.
     *
     * @param string $stringkey Lang string key.
     * @param string $name Activity display name.
     * @param string $link Candidate activity URL.
     * @return string|null
     */
    private static function format_success(string $stringkey, string $name, string $link): ?string {
        $safelink = self::sanitize_activity_link($link);
        if ($safelink === null || $name === '') {
            return null;
        }

        return get_string($stringkey, 'block_dixeo_modulegen', (object) [
            'link' => s($safelink),
            'name' => s($name),
        ]);
    }

    /**
     * Mirror of generation_notifications.js task_failed param building.
     *
     * @param string $error Raw error text.
     * @return string
     */
    private static function format_failure(string $error): string {
        return get_string('task_failed', 'block_dixeo_modulegen', (object) [
            'error' => s($error),
        ]);
    }

    /**
     * Assert notification HTML has no executable injected nodes.
     *
     * @param string $html
     * @param string $plaintextexpected
     */
    private function assert_notification_html_safe(string $html, string $plaintextexpected): void {
        $this->assertStringContainsString(s($plaintextexpected), $html);

        $dom = new \DOMDocument();
        @$dom->loadHTML('<?xml encoding="utf-8" ?><div id="root">' . $html . '</div>');
        $xpath = new \DOMXPath($dom);
        $this->assertSame(0, $xpath->query('//img')->length, 'Unexpected img element in notification HTML');
        $this->assertSame(0, $xpath->query('//*[@onerror]')->length, 'Unexpected onerror attribute in notification HTML');

        foreach ($xpath->query('//@href') as $href) {
            $value = strtolower((string) $href->nodeValue);
            $this->assertStringNotContainsString('javascript:', $value);
        }
    }

    /**
     * javascript: and other hostile links are rejected.
     */
    public function test_sanitize_activity_link_rejects_hostile_urls(): void {
        $this->assertNull(self::sanitize_activity_link('javascript:window.__dixeo_xss=2'));
        $this->assertNull(self::sanitize_activity_link('https://evil.example/mod/page/view.php?id=1'));
        $this->assertNull(self::sanitize_activity_link('/course/view.php?id=1'));
    }

    /**
     * Same-origin mod view URLs are accepted and normalised.
     */
    public function test_sanitize_activity_link_accepts_mod_view(): void {
        global $CFG;

        $raw = $CFG->wwwroot . '/mod/page/view.php?id=42&extra=1#frag';
        $safe = self::sanitize_activity_link($raw);
        $this->assertSame($CFG->wwwroot . '/mod/page/view.php?id=42', $safe);
    }

    /**
     * Success toast escapes the name and omits unsafe links.
     */
    public function test_format_success_escapes_name_and_requires_safe_link(): void {
        global $CFG;

        $this->assertNull(self::format_success(
            'task_completed_success',
            self::HOSTILE_MARKUP,
            'javascript:alert(1)'
        ));

        $html = self::format_success(
            'task_completed_success',
            self::HOSTILE_MARKUP,
            $CFG->wwwroot . '/mod/page/view.php?id=42'
        );
        $this->assertNotNull($html);
        $this->assert_notification_html_safe($html, self::HOSTILE_MARKUP);
        $this->assertStringContainsString('href="' . s($CFG->wwwroot . '/mod/page/view.php?id=42') . '"', $html);
    }

    /**
     * Manual upload success uses the same escaping contract.
     */
    public function test_format_manual_upload_success_escapes_name(): void {
        global $CFG;

        $html = self::format_success(
            'manual_upload_success',
            self::HOSTILE_MARKUP,
            $CFG->wwwroot . '/mod/resource/view.php?id=7'
        );
        $this->assertNotNull($html);
        $this->assert_notification_html_safe($html, self::HOSTILE_MARKUP);
    }

    /**
     * Failure toast escapes remote error.detail-style payloads.
     */
    public function test_format_failure_escapes_hostile_error(): void {
        $html = self::format_failure(self::HOSTILE_MARKUP);
        $this->assert_notification_html_safe($html, self::HOSTILE_MARKUP);
    }
}
