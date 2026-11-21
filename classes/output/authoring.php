<?php
// This file is part of Moodle - https://moodle.org/
/**
 * Native authoring presentation, shared by Site Home and the course activity.
 *
 * @package local_frontpage
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license 108design source-available software license; see LICENSE.md
 */

namespace local_frontpage\output;

use html_writer;
use moodle_url;

defined('MOODLE_INTERNAL') || die();
/**
 * Native authoring presentation, shared by Site Home and the course activity.
 */
final class authoring {
    /** Icon for the page settings shared by the editor and public page actions. */
    public const PAGE_SETTINGS_ICON = 'local/page-settings';
    /** Icon for the page file manager. */
    public const PAGE_FILES_ICON = 'fa/folder-open';
    /** Icon for the page revision history. */
    public const VERSIONS_ICON = 'fa/history';

    /**
     * Render a native Moodle icon.
     *
     * @param string $name Display name.
     * @return string
     */
    public static function icon(string $name): string {
        global $OUTPUT;
        if ($name === self::PAGE_SETTINGS_ICON) {
            static $pagesettingssvg = null;
            return $pagesettingssvg ??= file_get_contents(__DIR__ . '/../../pix/page-settings.svg');
        }
        if (str_starts_with($name, 'fa/')) {
            return html_writer::tag(
                'i',
                '',
                [ 'class' => 'fa fa-' . substr($name, 3) . ' icon', 'aria-hidden' => 'true' ]
            );
        }
        return $OUTPUT->pix_icon($name, '', 'moodle', ['aria-hidden' => 'true']);
    }
    /**
     * Render an accessible link with its optional action icon.
     *
     * @param moodle_url $url Local or validated destination URL.
     * @param string $label Accessible control label.
     * @param string $icon Native icon name.
     * @param bool $compact Whether to use the compact presentation.
     * @param array $attributes HTML attributes for the generated element.
     * @return string
     */
    public static function link(
        moodle_url $url,
        string $label,
        string $icon,
        bool $compact = false,
        array $attributes = [ ]
    ): string {
        return html_writer::link(
            $url,
            self::icon($icon) . html_writer::span(s($label), $compact ? 'sr-only' : ''),
            ['class' => $compact ? 'local-frontpage-icon-button' : 'btn btn-outline-secondary',
            'aria-label' => $label,
            'title' => $label] + $attributes
        );
    }
    /**
     * Render the disabled state of an editor action.
     *
     * @param string $label Accessible control label.
     * @param string $icon Native icon name.
     * @param string $reason Reason.
     * @return string
     */
    public static function disabled(string $label, string $icon, string $reason): string {
        return html_writer::span(
            self::icon($icon) . html_writer::span(s($label . ': ' . $reason), 'sr-only'),
            'local-frontpage-icon-button text-muted',
            ['role' => 'button', 'aria-disabled' => 'true', 'tabindex' => '0',
            'aria-label' => $label . ': ' . $reason,
            'title' => $reason]
        );
    }
    /**
     * Render a POST action protected by the current sesskey.
     *
     * @param string $label Accessible control label.
     * @param string $icon Native icon name.
     * @param array $params Validated parameters for the operation.
     * @param bool $primary Primary.
     * @param bool $labelled Display the label for a secondary action.
     * @return string
     */
    public static function post(string $label, string $icon, array $params, bool $primary = false, bool $labelled = false): string {
        $url = new moodle_url('/local/frontpage/action.php');
        return html_writer::tag(
            'form',
            html_writer::input_hidden_params(
                new moodle_url($url, $params + [ 'sesskey' => sesskey() ])
            )
            . html_writer::tag('button', self::icon($icon) . html_writer::span(s($label), $primary || $labelled ? '' : 'sr-only'), [
                'type' => 'submit', 'class' => $primary ? 'btn btn-primary'
                    : ($labelled ? 'btn btn-outline-secondary' : 'local-frontpage-icon-button'),
                'aria-label' => $label,
            'title' => $label]),
            [ 'method' => 'post', 'action' => $url->out(false), 'class' => 'd-inline-block' ]
        );
    }
    /**
     * Return the icon associated with the segment type.
     *
     * @param string $type Supported segment or field type.
     * @return string
     */
    public static function type_icon(string $type): string {
        return match ($type) {
            'heading'=>'i/edit', 'rich_text'=>'e/file-text', 'media'=>'e/insert_edit_image', 'hero'=>'i/home',
            'spacer'=>'t/move',
            'grid'=>'e/table',
            'moodle_course_list'=>'i/course',
            'course_module'=>'i/activities',
            default=>'i/settings'
        };
    }
    /**
     * Use the same segment identity in editor toolbars and settings forms.
     *
     * @param string $type Supported segment or field type.
     * @return string
     */
    public static function type_badge(string $type): string {
        $label = get_string('segmenttype' . str_replace('_', '', $type), 'local_frontpage');
        return html_writer::span(
            self::icon(self::type_icon($type)) . s($label),
            'badge local-frontpage-segment-type local-frontpage-segment-type-' . str_replace('_', '-', $type)
        );
    }
    /**
     * Render the action controls for one editable segment.
     *
     * @param array $segment Page segment to process.
     * @param string $content Content to process.
     * @param array $actions Action definitions for this view.
     * @param array $segments Ordered page segments.
     * @return string
     */
    public static function segment(array $segment, string $content, array $actions, array $segments): string {
        $label = trim((string)($segment['props']['editorlabel'] ?? ''));
        $status = self::type_badge($segment['type']);
        if ($label !== '') {
            $status .= html_writer::span(format_string($label), 'local-frontpage-editor-label');
        }
        $enabled = $segment['props']['enabled'] ?? true;
        if (!$enabled) {
            $status .= html_writer::span(
                get_string('editorhidden', 'local_frontpage'),
                'badge bg-secondary text-white'
            );
        }
        $params = $actions + ['segmentid' => $segment['id']];
        $buttons = self::link(
            new moodle_url('/local/frontpage/segment.php', $params),
            get_string('edit'),
            't/edit',
            true
        );
        $consumed = \local_frontpage\local\grid_structure::consumed_ids(array_values($segments));
        $siblings = array_filter(
            $segments,
            static fn(
            $s
            )=>$s['type'] !== 'course_navigation' && !isset($consumed[$s['id']])
        );
        $position = array_search($segment['id'], array_keys($siblings), true);
        $values = array_values($siblings);
        foreach (
            [
            'up' => 't/up',
            'down' => 't/down',
            'duplicate' => 't/copy',
            'toggle' => $enabled ? 't/hide' : 't/show',
            'remove' => 't/delete',
            ] as $action => $icon
        ) {
            if (
                in_array($action, [ 'up', 'down' ], true)
                && (
                $position === false || ($action === 'up' && $position === 0)
                || (
                $action === 'down' && $position === count($values) - 1
                )
                )
            ) {
                continue;
            }
            $actionlabel = $action === 'toggle' ? get_string($enabled ? 'hide' : 'show')
                : get_string('action_' . $action, 'local_frontpage');
            $buttons .= self::post($actionlabel, $icon, $params + ['action' => $action]);
        }
        return html_writer::div(
            html_writer::div(html_writer::div($status, 'local-frontpage-segment-status')
            . html_writer::div($buttons, 'local-frontpage-segment-actions'), 'local-frontpage-segment-toolbar')
            . html_writer::div($content, 'local-frontpage-segment-content'),
            'local-frontpage-editor-segment' . ($enabled ? '' : ' local-frontpage-editor-segment-disabled')
        );
    }
    /**
     * Render the chooser for the supported segment types.
     *
     * @param array $actions Action definitions for this view.
     * @return string
     */
    public static function chooser(array $actions): string {
        return html_writer::div(
            self::link(
                new moodle_url('/local/frontpage/segment.php', $actions),
                get_string('addsegment', 'local_frontpage'),
                't/add'
            ),
            'local-frontpage-segment-chooser'
        );
    }
}
