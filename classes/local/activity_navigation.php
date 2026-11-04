<?php
// This file is part of a 108design source-available software product.
// Copyright (C) 2026 Andreas Giesen <andreas@108design.com>.
// Use and modification are permitted only under the Software License in LICENSE.md.
namespace local_frontpage\local;

use local_frontpage\local\persistence\page_repository;
use moodle_url;

/** Viewer-specific navigation within a published standalone Frontpage activity. */
final class activity_navigation {
    /** An origin identifier is a navigation hint, never an access grant. No session-wide current origin is stored. */
    public const PARAM = 'fpnav';

    /** Resolve the currently published, accessible sequence on every request. */
    public static function resolve(int $originid, \cm_info $current): ?array {
        global $DB;

        if ($originid <= 0 || $current->modname === 'frontpage' || !$current->uservisible) {
            return null;
        }
        try {
            $modinfo = $current->get_modinfo();
            $origin = $modinfo->get_cm($originid);
            if ($origin->modname !== 'frontpage' || !$origin->uservisible
                    || !has_capability('mod/frontpage:view', $origin->context)) {
                return null;
            }
            $instance = $DB->get_record('frontpage', ['id' => $origin->instance], '*', MUST_EXIST);
            $repository = new page_repository($DB);
            $page = $repository->get_page((int) $instance->pageid);
            if (($page->ownercomponent ?? '') !== 'mod_frontpage'
                    || (int) $page->ownerinstanceid !== (int) $origin->instance
                    || (int) page_access::context($page)->instanceid !== $originid
                    || !page_access::can_view_published($page)) {
                return null;
            }
            $revision = $repository->get_revision((int) $page->id, (int) $page->publishedrevisionid);
            $document = json_decode($revision->documentjson, true, 512, JSON_THROW_ON_ERROR);
            $segments = self::ordered_segments($document,
                static fn(array $segment): bool => $segment['props']['enabled'] ?? true);
            $entries = [];
            foreach ($segments as $segment) {
                $cmid = (int) ($segment['props']['cmid'] ?? 0);
                if (isset($entries[$cmid]) || !empty($segment['props']['placeholder'])) {
                    continue;
                }
                try {
                    $cm = course_module_reference::get($origin->context, $cmid, $page);
                } catch (\moodle_exception) {
                    continue;
                }
                if ($cm->uservisible) {
                    $entries[$cmid] = ['cm' => $cm, 'segmentid' => $segment['id']];
                }
            }
            if (!isset($entries[(int) $current->id])) {
                return null;
            }
            $ids = array_keys($entries);
            $index = array_search((int) $current->id, $ids, true);
            $returnurl = new moodle_url('/mod/frontpage/view.php', ['id' => $originid]);
            $returnurl->set_anchor($entries[(int) $current->id]['segmentid']);
            $settings = activity_navigation_settings::for_document($document);
            $course = get_course((int) $origin->course);
            $previous = $index > 0 ? self::url($entries[$ids[$index - 1]]['cm'], $originid) : null;
            $next = $index + 1 < count($ids) ? self::url($entries[$ids[$index + 1]]['cm'], $originid) : null;
            $previousboundary = $previous ? null : self::boundary($origin, 'previous', $settings, $returnurl);
            $nextboundary = $next ? null : self::boundary($origin, 'next', $settings, $returnurl);
            if ($settings['boundary'] === 'course') {
                foreach ([$previousboundary, $nextboundary] as $boundary) {
                    if ($boundary !== null) {
                        $boundary['url']->param(self::PARAM, 0);
                    }
                }
            }
            return [
                'settings' => $settings, 'enabled' => activity_navigation_settings::enabled($settings, $course),
                'previousboundary' => $previousboundary, 'nextboundary' => $nextboundary,
                'origin' => $origin, 'returnurl' => $returnurl, 'entries' => $entries,
                'previousurl' => $previous, 'nexturl' => $next,
            ];
        } catch (\moodle_exception | \JsonException) {
            // A stale or forged context must not break the underlying Moodle activity.
            return null;
        }
    }

    /** Resolve a sequence boundary from the original FP course position, never its resource section. */
    public static function boundary(\cm_info $origin, string $direction, array $settings, moodle_url $returnurl): array {
        if (!in_array($direction, ['previous', 'next'], true)) {
            throw new \coding_exception('Invalid Frontpage navigation direction.');
        }
        if ($settings['boundary'] === 'frontpage') {
            $overview = new moodle_url('/mod/frontpage/view.php', ['id' => $origin->id]);
            return ['url' => $overview, 'label' => get_string('activitynavigation' . $direction . 'overview', 'local_frontpage')];
        }
        $courseurl = new moodle_url('/course/view.php', ['id' => $origin->course]);
        $modinfo = $origin->get_modinfo();
        if (activity_navigation_settings::supports_inheritance(get_course((int) $origin->course))) {
            $navigation = new \core_course\route\controller\course_navigation();
            $section = $navigation->get_section($origin);
            if ($section !== null) {
                $cms = $navigation->get_all_section_cms($modinfo, $section);
                $index = array_search($origin, $cms, true);
                $step = $direction === 'previous' ? -1 : 1;
                if ($index !== false) {
                    for ($i = $index + $step; isset($cms[$i]); $i += $step) {
                        $cm = $cms[$i];
                        if ($cm->uservisible && $cm->is_visible_on_course_page() && $cm->get_navigation_url()
                                && \course_modinfo::is_mod_type_visible_on_course($cm->modname)) {
                            return ['url' => new moodle_url($cm->get_navigation_url()),
                                'label' => get_string('activitynavigation' . $direction . 'course', 'local_frontpage')];
                        }
                    }
                }
                $adjacent = $navigation->get_adjacent_section($modinfo, $section, $direction);
                $resourceid = (int) get_config('local_frontpage', 'resourcesection_' . $origin->course);
                while ($adjacent && (int) $adjacent->id === $resourceid) {
                    $adjacent = $navigation->get_adjacent_section($modinfo, $adjacent, $direction);
                }
                if ($adjacent) {
                    // Preserve native section landing semantics, skipping the ordinary technical resources section.
                    return ['url' => course_get_url($modinfo->get_course(),
                        $direction === 'next' ? $adjacent : $section, ['navigation' => true]),
                        'label' => get_string('activitynavigation' . $direction . 'course', 'local_frontpage')];
                }
                return ['url' => $courseurl, 'label' => get_string('activitynavigationbacktocourse', 'local_frontpage')];
            }
            // A missing parent is not a valid Core route origin.
            return ['url' => $courseurl, 'label' => get_string('activitynavigationbacktocourse', 'local_frontpage')];
        }
        $ordered = [];
        foreach ($modinfo->get_sections() as $sectionnum => $ids) {
            $section = $modinfo->get_section_info($sectionnum);
            if ((int) $section->id === (int) get_config('local_frontpage', 'resourcesection_' . $origin->course)) {
                continue;
            }
            foreach ($ids as $id) {
                $ordered[] = (int) $id;
            }
        }
        $index = array_search((int) $origin->id, $ordered, true);
        $step = $direction === 'previous' ? -1 : 1;
        if ($index !== false) {
            for ($i = $index + $step; isset($ordered[$i]); $i += $step) {
                $candidate = $modinfo->get_cm($ordered[$i]);
                if (!$candidate->uservisible || !$candidate->is_visible_on_course_page() || !$candidate->url
                        || (method_exists(\course_modinfo::class, 'is_mod_type_visible_on_course')
                            && !\course_modinfo::is_mod_type_visible_on_course($candidate->modname))) {
                    continue;
                }
                $url = new moodle_url($candidate->url);
                return ['url' => $url,
                    'label' => get_string('activitynavigation' . $direction . 'course', 'local_frontpage')];
            }
        }
        return ['url' => $courseurl, 'label' => get_string('activitynavigationbacktocourse', 'local_frontpage')];
    }

    /** Follow the same top-level/reference Grid placement order as page_renderer. */
    public static function ordered_segments(array $document, callable $allows): array {
        $segments = $document['segments'] ?? [];
        $byid = array_column($segments, null, 'id');
        $consumed = grid_structure::consumed_ids($segments);
        $result = [];
        foreach ($segments as $segment) {
            if (isset($consumed[$segment['id']]) || !$allows($segment)) {
                continue;
            }
            if (($segment['type'] ?? '') === 'course_module') {
                $result[] = $segment;
            } else if (($segment['type'] ?? '') === grid_structure::TYPE) {
                foreach ($segment['props']['segmentids'] ?? [] as $id) {
                    $child = $byid[$id] ?? null;
                    if ($child && ($child['type'] ?? '') === 'course_module' && $allows($child)) {
                        $result[] = $child;
                    }
                }
            }
        }
        return $result;
    }

    /** Keep the native activity URL and parameters, adding only the validated origin hint. */
    public static function url(\cm_info $cm, int $originid): moodle_url {
        $url = new moodle_url($cm->url);
        $url->param(self::PARAM, $originid);
        return $url;
    }

    /** Add the origin to native activity-card launch links without mutating shared cm_info objects. */
    public static function launch_links(string $html, \cm_info $cm, int $originid): string {
        return preg_replace_callback('~(<a\b[^>]*\bhref=)(["\'])(.*?)\2~is',
            static function(array $match) use ($cm, $originid): string {
                $raw = html_entity_decode($match[3], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                try {
                    $url = new moodle_url($raw);
                } catch (\moodle_exception) {
                    return $match[0];
                }
                if (!$url->compare($cm->url, URL_MATCH_BASE)
                        || (int) $url->get_param('id') !== (int) $cm->url->get_param('id')) {
                    return $match[0];
                }
                $url->param(self::PARAM, $originid);
                return $match[1] . $match[2] . s($url->out(false)) . $match[2];
            }, $html);
    }

    /** Obtain a context from this request, or a same-module referrer after a native form redirect. */
    public static function request_origin(\moodle_page $page): int {
        global $CFG;

        $origin = optional_param(self::PARAM, 0, PARAM_INT);
        if (array_key_exists(self::PARAM, $_GET) || array_key_exists(self::PARAM, $_POST)
                || !$page->cm || empty($_SERVER['HTTP_REFERER'])) {
            return $origin;
        }
        try {
            $referrer = new moodle_url($_SERVER['HTTP_REFERER']);
        } catch (\moodle_exception) {
            return 0;
        }
        $root = new moodle_url($CFG->wwwroot);
        $modulepath = $root->get_path() . '/mod/' . $page->cm->modname . '/';
        if ($referrer->get_scheme() !== $root->get_scheme() || $referrer->get_host() !== $root->get_host()
                || $referrer->get_port() !== $root->get_port() || !str_starts_with($referrer->get_path(), $modulepath)) {
            return 0;
        }
        return (int) $referrer->get_param(self::PARAM);
    }

    /** Prepare native breadcrumbs and propagation before header rendering. */
    public static function prepare(\core\hook\output\before_http_headers $hook): void {
        if (during_initial_install()) {
            return;
        }
        $page = $hook->renderer->get_page();
        if (!$page->cm) {
            return;
        }
        $context = self::resolve(self::request_origin($page), $page->cm);
        if (!$context) {
            // The 5.3 controller cannot navigate a delegated resource section with no parent.
            // Direct resource visits still render their normal header completion control.
            if (method_exists($page, 'set_show_navigation_footer')
                    && $page->cm->get_section_info()->is_delegated()
                    && $page->cm->get_section_info()->component === 'local_frontpage') {
                $page->set_show_navigation_footer(false);
            }
            return;
        }
        $origin = $context['origin'];
        if (!$context['enabled'] && method_exists($page, 'set_show_navigation_footer')) {
            $page->set_show_navigation_footer(false);
        }
        $page->set_url(new moodle_url($page->url, [self::PARAM => (int) $origin->id]));
        $children = $page->navbar->children;
        $page->navbar->children = [];
        $page->navbar->ignore_active();
        $page->navbar->add(format_string($page->course->shortname),
            new moodle_url('/course/view.php', ['id' => $page->course->id]));
        $page->navbar->add($origin->get_formatted_name(), new moodle_url('/mod/frontpage/view.php', ['id' => $origin->id]));
        $page->navbar->add($page->cm->get_formatted_name(), self::url($page->cm, (int) $origin->id));
        foreach ($children as $child) {
            $page->navbar->add($child->text, $child->action, $child->type, $child->shorttext, $child->key, $child->icon);
        }
        $page->add_body_class('local-frontpage-activity-navigation-active');
        $page->requires->css(new moodle_url('/local/frontpage/activity_navigation.css'));
        $page->requires->js_call_amd('local_frontpage/activity_navigation', 'init', [[
            'origin' => (int) $origin->id, 'module' => $page->cm->modname,
            'returnurl' => $context['returnurl']->out(false),
            'returnlabel' => get_string('activitynavigationreturn', 'local_frontpage',
                html_to_text($origin->get_formatted_name(), 0, false)),
        ]]);
    }

    /** Render before the Core linear-navigation hook; rendering claims the single native sticky footer. */
    public static function footer(\core\hook\output\before_footer_html_generation $hook): void {
        if (during_initial_install()) {
            return;
        }
        $page = $hook->renderer->get_page();
        if (!$page->cm || !($context = self::resolve(self::request_origin($page), $page->cm))) {
            return;
        }
        if (method_exists($page, 'has_sticky_footer') && $page->has_sticky_footer()) {
            return; // Preserve native quiz/form/action footers.
        }
        if (!$context['enabled']) {
            return;
        }
        if (method_exists($page, 'should_show_navigation_footer') && !$page->should_show_navigation_footer()) {
            return;
        }
        if (!class_exists(\core_courseformat\local\linearnavigationsettings::class)
                && !in_array(basename($page->url->get_path()), ['view.php', 'discuss.php'], true)) {
            return;
        }
        $content = (new \local_frontpage\output\activity_navigation($page->cm, $context))->render($hook->renderer);
        $footer = new \core\output\sticky_footer($content, 'course-linear-navigation local-frontpage-activity-navigation');
        $hook->add_html($hook->renderer->render($footer));
    }
}
