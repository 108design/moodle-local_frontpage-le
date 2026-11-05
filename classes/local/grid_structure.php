<?php
/**
 * Shared structural rules for the reusable, reference-based Grid segment.
 *
 * @package local_frontpage
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license 108design source-available software license; see LICENSE.md
 */

namespace local_frontpage\local;

use invalid_parameter_exception;

/**
 * Shared structural rules for the reusable, reference-based Grid segment.
 */
final class grid_structure {
    /**
     * Type.
     */
    public const TYPE = 'grid';
    /**
     * Max columns.
     */
    public const MAX_COLUMNS = 4;
    /**
     * Structural carriers which are not ordinary composable page segments.
     */
    private const UNSUITABLE_CHILD_TYPES = ['grid', 'course_navigation'];

    /**
     * Validate and canonicalise the Grid-owned properties.
     *
     * @param array $props Validated segment properties.
     * @return array
     */
    public static function normalise_props(array $props): array {
        $columns = $props['columns'] ?? 2;
        if (!is_int($columns) || $columns < 1 || $columns > self::MAX_COLUMNS) {
            throw new invalid_parameter_exception('The Grid column count is invalid.');
        }
        $template = (string) ($props['template'] ?? 'equal');
        if (
            !in_array($template, ['equal', 'wide_first', 'wide_last', 'custom'], true)
                || $columns === 1 && $template !== 'equal'
        ) {
            throw new invalid_parameter_exception('The Grid column proportion is invalid.');
        }
        $widths = $props['widths'] ?? [];
        if ($template === 'custom') {
            if (!is_array($widths) || !array_is_list($widths) || count($widths) !== $columns) {
                throw new invalid_parameter_exception('The custom Grid column widths are invalid.');
            }
            foreach ($widths as $width) {
                if (!is_int($width) || $width < 1 || $width > 12) {
                    throw new invalid_parameter_exception('A custom Grid column width is invalid.');
                }
            }
            if (array_sum($widths) !== 12) {
                throw new invalid_parameter_exception('The custom Grid column widths must total 12.');
            }
        } else {
            $widths = [];
        }
        $gap = (string) ($props['gap'] ?? 'medium');
        if (!in_array($gap, ['none', 'small', 'medium', 'large'], true)) {
            throw new invalid_parameter_exception('The Grid gap is invalid.');
        }
        $alignment = (string) ($props['alignment'] ?? 'stretch');
        if (!in_array($alignment, ['start', 'center', 'end', 'stretch'], true)) {
            throw new invalid_parameter_exception('The Grid alignment is invalid.');
        }
        $mobileorder = (string) ($props['mobileorder'] ?? 'natural');
        if (!in_array($mobileorder, ['natural', 'reverse'], true)) {
            throw new invalid_parameter_exception('The Grid mobile order is invalid.');
        }
        $heightmode = (string) ($props['heightmode'] ?? 'auto');
        if (!in_array($heightmode, ['auto', 'min', 'fixed'], true)) {
            throw new invalid_parameter_exception('The Grid height mode is invalid.');
        }
        $heightvalue = '';
        if ($heightmode !== 'auto') {
            $heightvalue = css_length_value::normalise((string) ($props['heightvalue'] ?? ''));
        }
        $overflow = (string) ($props['overflow'] ?? 'visible');
        if (!in_array($overflow, ['visible', 'clip'], true)) {
            throw new invalid_parameter_exception('The Grid overflow mode is invalid.');
        }
        $mediafit = $props['mediafit'] ?? false;
        if (!is_bool($mediafit)) {
            throw new invalid_parameter_exception('The Grid media fitting setting is invalid.');
        }
        $mediafit = $heightmode === 'fixed' && $mediafit;
        $stackbreakpoint = (string) ($props['stackbreakpoint'] ?? 'md');
        if (!in_array($stackbreakpoint, ['none', 'sm', 'md', 'lg', 'xl'], true)) {
            throw new invalid_parameter_exception('The Grid stacking breakpoint is invalid.');
        }
        $stackautoheight = $props['stackautoheight'] ?? true;
        if (!is_bool($stackautoheight)) {
            throw new invalid_parameter_exception('The Grid stacked-height setting is invalid.');
        }
        $stackautoheight = $heightmode === 'fixed' && $stackbreakpoint !== 'none' && $stackautoheight;
        $mobilehidden = $props['mobilehidden'] ?? [];
        if (
            !is_array($mobilehidden) || !array_is_list($mobilehidden)
                || count($mobilehidden) > self::MAX_COLUMNS
        ) {
            throw new invalid_parameter_exception('The Grid mobile visibility settings are invalid.');
        }
        foreach ($mobilehidden as $hidden) {
            if (!is_bool($hidden)) {
                throw new invalid_parameter_exception('A Grid mobile visibility setting is invalid.');
            }
        }
        if ($stackbreakpoint === 'none') {
            $mobilehidden = array_fill(0, count($mobilehidden), false);
            $mobileorder = 'natural';
        }
        $segmentids = $props['segmentids'] ?? [];
        if (!is_array($segmentids) || !array_is_list($segmentids) || count($segmentids) > self::MAX_COLUMNS) {
            throw new invalid_parameter_exception('The Grid segment references are invalid.');
        }
        $normalisedids = [];
        $seenids = [];
        foreach ($segmentids as $segmentid) {
            if (!is_string($segmentid)) {
                throw new invalid_parameter_exception('A Grid segment reference is invalid or duplicated.');
            }
            if ($segmentid === '') {
                $normalisedids[] = '';
                continue;
            }
            if (
                !preg_match('/^[a-zA-Z0-9][a-zA-Z0-9_-]{0,99}$/', $segmentid)
                    || isset($seenids[$segmentid])
            ) {
                throw new invalid_parameter_exception('A Grid segment reference is invalid or duplicated.');
            }
            $seenids[$segmentid] = true;
            $normalisedids[] = $segmentid;
        }
        $props['columns'] = $columns;
        $props['template'] = $template;
        $props['widths'] = $widths;
        $props['gap'] = $gap;
        $props['alignment'] = $alignment;
        $props['mobileorder'] = $mobileorder;
        $props['heightmode'] = $heightmode;
        $props['heightvalue'] = $heightvalue;
        $props['overflow'] = $overflow;
        $props['mediafit'] = $mediafit;
        $props['stackbreakpoint'] = $stackbreakpoint;
        $props['stackautoheight'] = $stackautoheight;
        $props['segmentids'] = array_pad(array_slice($normalisedids, 0, $columns), $columns, '');
        $props['mobilehidden'] = array_pad(array_slice($mobilehidden, 0, $columns), $columns, false);
        return $props;
    }

    /**
     * Validate cross-segment references after every segment has been parsed.
     *
     * @param array $document Validated document data.
     * @return void
     */
    public static function validate_document(array $document): void {
        if (in_array($document['aboveHeaderSegment']['type'] ?? '', [self::TYPE], true)) {
            throw new invalid_parameter_exception('A Grid cannot occupy the above-header slot.');
        }
        $segments = $document['segments'] ?? [];
        $byid = [];
        foreach ($segments as $segment) {
            $byid[(string) ($segment['id'] ?? '')] = $segment;
        }
        $consumed = [];
        $ownedids = [];
        foreach ($segments as $grid) {
            if (($grid['type'] ?? '') !== self::TYPE) {
                continue;
            }

            foreach ($grid['props']['segmentids'] ?? [] as $segmentid) {
                if ($segmentid === '') {
                    continue;
                }
                $child = $byid[$segmentid] ?? null;
                if (
                    !$child || in_array($child['type'] ?? '', self::UNSUITABLE_CHILD_TYPES, true)
                        || !empty($child['props']['drawerblock'])
                        || $segmentid === ($grid['id'] ?? '') || isset($consumed[$segmentid])
                ) {
                    throw new invalid_parameter_exception('A Grid contains an unavailable segment reference.');
                }
                $consumed[$segmentid] = true;
            }
        }
    }

    /**
     * Return ids rendered inside a Grid rather than in the ordinary vertical flow.
     *
     * @param array $segments Ordered page segments.
     * @return array
     */
    public static function consumed_ids(array $segments): array {
        $ids = [];
        foreach ($segments as $segment) {
            if (($segment['type'] ?? '') !== self::TYPE) {
                continue;
            }
            foreach ($segment['props']['segmentids'] ?? [] as $segmentid) {
                if ($segmentid === '') {
                    continue;
                }
                $ids[(string) $segmentid] = true;
            }
        }
        return $ids;
    }

    /**
     * Remove references to a segment before that segment is deleted.
     *
     * @param array $document Validated document data.
     * @param string $segmentid Stable segment identifier.
     * @return void
     */
    public static function release_segment(array &$document, string $segmentid): void {
        if (empty($document['segments']) || !is_array($document['segments'])) {
            return;
        }
        foreach ($document['segments'] as &$segment) {
            if (($segment['type'] ?? '') !== self::TYPE) {
                continue;
            }
            $segment['props']['segmentids'] = array_map(
                static fn(string $candidate): string => $candidate === $segmentid ? '' : $candidate,
                $segment['props']['segmentids'] ?? []
            );
        }
        unset($segment);
    }

    /**
     * Candidate segments for Grid slot selects. Selecting an owned segment transfers it when the Grid is saved.
     *
     * @param array $document Validated document data.
     * @param string $currentgridid Identifier of the grid being edited.
     * @return array
     */
    public static function choices(array $document, string $currentgridid = ''): array {
        $choices = ['' => get_string('gridemptycolumn', 'local_frontpage')];
        foreach (self::candidates($document, $currentgridid) as $candidate) {
            $choices[$candidate['id']] = $candidate['label'];
        }
        return $choices;
    }

    /**
     * Candidate segments and their editor-facing labels for the Grid picker.
     *
     * @param array $document Validated document data.
     * @param string $currentgridid Identifier of the grid being edited.
     * @return array
     */
    public static function candidates(array $document, string $currentgridid = ''): array {
        $candidates = [];
        foreach ($document['segments'] ?? [] as $segment) {
            $id = (string) ($segment['id'] ?? '');
            $type = (string) ($segment['type'] ?? '');
            if (
                $id === '' || $id === $currentgridid || in_array($type, self::UNSUITABLE_CHILD_TYPES, true)
                    || !empty($segment['props']['drawerblock'])
            ) {
                continue;
            }
            $key = 'segmenttype' . str_replace('_', '', $type);
            $typelabel = get_string_manager()->string_exists($key, 'local_frontpage')
                ? get_string($key, 'local_frontpage') : $type;
            $editorlabel = trim((string) ($segment['props']['editorlabel'] ?? ''));
            $label = $editorlabel === ''
                ? $typelabel
                : get_string(
                    'gridsegmentchoiceeditorlabel',
                    'local_frontpage',
                    (object) ['type' => $typelabel, 'label' => $editorlabel]
                );
            $candidates[] = ['id' => $id, 'type' => $type, 'typelabel' => $typelabel,
                'editorlabel' => $editorlabel, 'label' => $label, 'segment' => $segment];
        }
        return $candidates;
    }

    /**
     * Release selected children from every other Grid so the target Grid can claim them atomically.
     *
     * @param array $document Validated document data.
     * @param string $targetgridid Targetgridid.
     * @param array $segmentids Segmentids.
     * @return void
     */
    public static function claim_segments(array &$document, string $targetgridid, array $segmentids): void {
        $claimed = array_fill_keys(array_filter($segmentids, static fn(string $id): bool => $id !== ''), true);
        if (!$claimed) {
            return;
        }
        if (empty($document['segments']) || !is_array($document['segments'])) {
            return;
        }
        foreach ($document['segments'] as &$segment) {
            if (
                ($segment['type'] ?? '') !== self::TYPE || ($segment['id'] ?? '') === $targetgridid
            ) {
                continue;
            }
            $segment['props']['segmentids'] = array_map(
                static fn(string $id): string => isset($claimed[$id]) ? '' : $id,
                $segment['props']['segmentids'] ?? []
            );
        }
        unset($segment);
    }
}
