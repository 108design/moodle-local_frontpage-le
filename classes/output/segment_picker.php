<?php
/**
 * Discover the existing supported segment types using the full product's picker layout.
 *
 * @package local_frontpage
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license 108design source-available software license; see LICENSE.md
 */
namespace local_frontpage\output;

defined('MOODLE_INTERNAL') || die();

use html_writer as h;
use moodle_url;

/** Read-only samples; insertion uses the existing permission-checked segment form. */
final class segment_picker {
    /**
     * Render only the types allowed by the independent document validator.
     *
     * @param array $actions Existing page and draft identity.
     * @return string
     */
    public static function render(array $actions): string {
        $rows = '';
        foreach (\local_frontpage\local\document\validator::TYPES as $type) {
            if ($type === 'course_navigation') {
                continue;
            }
            $label = get_string('segmenttype' . str_replace('_', '', $type), 'local_frontpage');
            $purpose = get_string('chooser_' . $type, 'local_frontpage');
            $choice = h::tag('button', h::span($label, 'local-frontpage-segment-chooser-item-title')
                . h::span($purpose, 'small text-muted d-block mt-1'), [
                    'type' => 'button', 'class' => 'local-frontpage-segment-chooser-item', 'aria-pressed' => 'false',
                ]);
            $insert = authoring::link(new moodle_url('/local/frontpage/segment.php', $actions + ['type' => $type]),
                get_string('addsegment', 'local_frontpage') . ': ' . $label, 't/add', true);
            $rows .= h::div($choice . h::div($insert, 'local-frontpage-segment-chooser-item-actions')
                . h::tag('template', self::sample($type, $label)), 'local-frontpage-segment-chooser-item-row', [
                    'data-picker-entry' => $type, 'data-label' => $label, 'data-purpose' => $purpose,
                ]);
        }
        $search = h::empty_tag('input', ['type' => 'search', 'class' => 'form-control mb-4',
            'aria-label' => get_string('uisearchsegments', 'local_frontpage'),
            'placeholder' => get_string('uisearchsegments', 'local_frontpage')]);
        $preview = h::tag('section', h::tag('h2', '', ['class' => 'h4 mb-2', 'data-picker-heading' => '1'])
            . h::tag('p', '', ['class' => 'text-muted mb-3', 'data-picker-purpose' => '1'])
            . h::div('', 'local-frontpage-picker-sample', ['data-picker-preview' => '1', 'inert' => 'inert']),
            ['class' => 'local-frontpage-segment-chooser-preview']);
        return h::div($search . h::div(h::div($rows, 'local-frontpage-segment-chooser-list') . $preview,
            'local-frontpage-segment-chooser-body')
            . h::div(get_string('uinoresultsegments', 'local_frontpage'), 'alert alert-info mt-3',
                ['data-picker-empty' => '1', 'hidden' => 'hidden']),
            'local-frontpage-segment-chooser-browser', ['data-frontpage-segment-picker' => '1']);
    }

    /**
     * Return inert examples containing no authored content, live queries or new segment fields.
     *
     * @param string $type Existing segment type.
     * @param string $label Translated type label.
     * @return string
     */
    private static function sample(string $type, string $label): string {
        $title = get_string('uisampleheading', 'local_frontpage');
        $text = get_string('uisampletext', 'local_frontpage');
        return match ($type) {
            'heading' => h::tag('h2', $title),
            'rich_text' => h::tag('p', $text) . h::tag('p', h::tag('strong', $title) . ' ' . $text),
            'hero' => h::div(h::tag('h2', $title) . h::tag('p', $text), 'local-frontpage-picker-hero'),
            'media' => h::div(authoring::icon('e/insert_edit_image'), 'local-frontpage-picker-media'),
            'spacer' => h::div('', 'local-frontpage-picker-spacer'),
            'grid' => h::div(h::div($title, 'p-3 border rounded') . h::div($text, 'p-3 border rounded'),
                'local-frontpage-picker-grid'),
            default => h::div(authoring::icon($type === 'course_module' ? 'i/activities' : 'i/course')
                . h::tag('strong', s($label)) . h::tag('p', $text, ['class' => 'small mt-3 mb-0']), 'border rounded p-4'),
        };
    }
}
