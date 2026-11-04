<?php
/**
 * @package local_frontpage
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license 108design source-available software license; see LICENSE.md
 */
namespace local_frontpage\local\document;
use invalid_parameter_exception;
use JsonException;
final class validator {
    public const SCHEMA_VERSION = 2;
    public const MAX_PAGE_CSS_LENGTH = 100000;
    public const TYPES = ['heading', 'rich_text', 'media', 'hero', 'spacer', 'grid', 'moodle_course_list',
        'course_module', 'course_navigation'];
    public function empty_document(?array $presentation = null): string {
        return json_encode(['schemaVersion' => 2, 'aboveHeaderSegment' => null, 'segments' => []], JSON_THROW_ON_ERROR);
    }
    public function validate_and_normalise(string $documentjson): string {
        try {
            $document = json_decode($documentjson, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new invalid_parameter_exception('Invalid document JSON.');
        }
        if (!is_array($document) || ($document['schemaVersion'] ?? null) !== 2
                || !is_array($document['segments'] ?? null) || !array_is_list($document['segments'])
                || array_diff(array_keys($document), ['schemaVersion', 'aboveHeaderSegment', 'segments', 'css', 'activitynavigation'])
                || !empty($document['aboveHeaderSegment'])) {
            throw new invalid_parameter_exception('Unsupported document structure.');
        }
        if(array_key_exists('css',$document)){$document['css']=$this->normalise_page_css($document['css']);}
        if (array_key_exists(\local_frontpage\local\activity_navigation_settings::KEY, $document)) {
            $document[\local_frontpage\local\activity_navigation_settings::KEY] =
                \local_frontpage\local\activity_navigation_settings::normalise(
                    $document[\local_frontpage\local\activity_navigation_settings::KEY]);
        }
        $ids = [];
        foreach ($document['segments'] as &$segment) {
            $this->validate_segment($segment, $ids);
            if ($segment['props'] === []) {$segment['props'] = (object)[];}
        }
        unset($segment);
        \local_frontpage\local\grid_structure::validate_document($document);
        return json_encode($document, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
    /** Same revision-owned text contract as the standard page CSS editor. */
    public function normalise_page_css(mixed $css): string {
        if(!is_string($css)){throw new invalid_parameter_exception('Page CSS must be text.');}
        if(strlen($css)>self::MAX_PAGE_CSS_LENGTH){throw new invalid_parameter_exception('Page CSS exceeds the maximum allowed size.');}
        return rtrim(str_replace(["\r\n","\r"],"\n",$css));
    }
    private function validate_segment(mixed &$segment, array &$ids): void {
        if (!is_array($segment) || array_is_list($segment)) {
            throw new invalid_parameter_exception('Every page segment must be a JSON object.');
        }

        $id = $segment['id'] ?? null;
        if (!is_string($id) || !preg_match('/^[a-zA-Z0-9][a-zA-Z0-9_-]{0,99}$/', $id)) {
            throw new invalid_parameter_exception('Every page segment must have a valid id.');
        }
        if (isset($ids[$id])) {
            throw new invalid_parameter_exception('Page segment ids must be unique.');
        }
        $ids[$id] = true;

        $type = $segment['type'] ?? null;
        if (!is_string($type) || !preg_match('/^[a-z][a-z0-9_]{0,99}$/', $type)) {
            throw new invalid_parameter_exception('Every page segment must have a valid type.');
        }

        if (!isset($segment['props']) || !is_array($segment['props']) ||
                ($segment['props'] !== [] && array_is_list($segment['props']))) {
            throw new invalid_parameter_exception('Every page segment must have a props object.');
        }

        if (!in_array($type, self::TYPES, true)) {
            throw new invalid_parameter_exception('This segment type is not supported.');
        }
        if (array_diff(array_keys($segment), ['id', 'type', 'props'])) {
            throw new invalid_parameter_exception('Unsupported segment properties.');
        }
        foreach (['css', 'scripts', 'libraryid', 'coursecontent', 'trusted', 'restrictions', 'drawerblock'] as $key) {
            if (array_key_exists($key, $segment['props'])) {
                throw new invalid_parameter_exception('Unsupported segment option.');
            }
        }
        if (array_key_exists('editorlabel', $segment['props'])) {
            if (!is_string($segment['props']['editorlabel'])
                    || \core_text::strlen(trim($segment['props']['editorlabel'])) > 120) {
                throw new invalid_parameter_exception('The segment editor label is invalid.');
            }
            $segment['props']['editorlabel'] = trim($segment['props']['editorlabel']);
            if ($segment['props']['editorlabel'] === '') {
                unset($segment['props']['editorlabel']);
            }
        }
        if (isset($segment['props']['enabled']) && !is_bool($segment['props']['enabled'])) {
            throw new invalid_parameter_exception('The segment enabled state must be boolean.');
        }
        if ($type === 'heading') {
            $props = &$segment['props'];
            if (!is_string($props['text'] ?? null) || !in_array($props['level'] ?? null, [2, 3, 4], true)) {
                throw new invalid_parameter_exception('Every Heading segment must contain text and a supported level.');
            }
            if (isset($props['includeinindex']) && !is_bool($props['includeinindex'])) {
                throw new invalid_parameter_exception('The Heading page-index setting must be a boolean.');
            }
            if (isset($props['indexlabel']) && !is_string($props['indexlabel'])) {
                throw new invalid_parameter_exception('The Heading page-index label must be text.');
            }
            if (!in_array($props['titlesize'] ?? 'theme', ['theme', 'large', 'custom'], true)
                    || !in_array($props['color'] ?? 'inherit', ['inherit', 'custom'], true)) {
                throw new invalid_parameter_exception('A Heading presentation setting is invalid.');
            }
            if (($props['titlesize'] ?? 'theme') === 'custom') {
                $props['titlesizevalue'] = \local_frontpage\local\css_length_value::normalise(
                    (string) ($props['titlesizevalue'] ?? '')
                );
            } else {
                unset($props['titlesizevalue']);
            }
            if (($props['color'] ?? 'inherit') === 'custom') {
                $props['colorvalue'] = \local_frontpage\local\color_value::normalise_picker_value(
                    (string) ($props['colorvalue'] ?? '')
                );
            } else {
                unset($props['colorvalue']);
            }
        }
        if ($type === 'hero') {
            $segment['props'] = \local_frontpage\local\hero_content::normalise($segment['props']);
            $props = &$segment['props'];
            foreach (['eyebrow', 'heading', 'text'] as $property) {
                if (isset($props[$property]) && !is_string($props[$property])) {
                    throw new invalid_parameter_exception('A Hero text setting is invalid.');
                }
            }
            foreach ([
                'style' => ['plain', 'surface', 'card', 'spotlight'],
                'height' => ['normal', 'spacious', 'viewport', 'custom'],
                'titlesize' => ['normal', 'large', 'display', 'custom'],
                'herolayout' => ['overlay', 'splitright', 'splitleft'],
                'overlay' => ['auto', 'subtle', 'strong', 'gradient', 'none'],
                'contrast' => ['auto', 'light', 'dark', 'custom'],
                'texteffect' => ['none', 'shadow', 'glow'],
                'backgroundsize' => ['cover', 'contain', 'auto', 'custom'],
                'backgroundposition' => [...array_keys(\local_frontpage\local\hero_media_presentation::POSITIONS), 'custom'],
            ] as $property => $values) {
                if (isset($props[$property]) && !in_array($props[$property], $values, true)) {
                    throw new invalid_parameter_exception('A Hero presentation setting is invalid.');
                }
            }
            $props = \local_frontpage\local\hero_media_presentation::normalise($props);
            foreach (['height' => 'heightvalue', 'titlesize' => 'titlesizevalue']
                    as $modeproperty => $valueproperty) {
                if (($props[$modeproperty] ?? '') === 'custom') {
                    $props[$valueproperty] = \local_frontpage\local\css_length_value::normalise(
                        (string) ($props[$valueproperty] ?? '')
                    );
                } else {
                    unset($props[$valueproperty]);
                }
            }
            if (($props['contrast'] ?? 'auto') === 'custom') {
                $props['contrastvalue'] = \local_frontpage\local\color_value::normalise_picker_value(
                    (string) ($props['contrastvalue'] ?? '')
                );
            } else {
                unset($props['contrastvalue']);
            }
        }
        if ($type === 'media') {
            $props = &$segment['props'];
            $props += [
                'alt' => '',
                'decorative' => false,
                'caption' => '',
                'aspect' => 'three_two',
                'fit' => 'cover',
                'focal' => 'center',
                'loading' => 'lazy',
            ];
            if (!is_string($props['asset'] ?? null)
                    || ($props['asset'] !== \local_frontpage\local\media_files::ASSET
                    && !preg_match('/^\{\{image-url:[a-zA-Z0-9][a-zA-Z0-9._ -]{0,199}\}\}$/', $props['asset']))) {
                throw new invalid_parameter_exception('A Media segment must reference its owned image or a legacy page image asset.');
            }
            if (!is_string($props['alt']) || !is_bool($props['decorative']) || !is_string($props['caption'])) {
                throw new invalid_parameter_exception('A Media text setting is invalid.');
            }
            $props['alt'] = trim($props['alt']);
            $props['caption'] = trim($props['caption']);
            if (!$props['decorative'] && $props['alt'] === '') {
                throw new invalid_parameter_exception('A non-decorative Media segment requires alternative text.');
            }
            if ($props['decorative']) {
                $props['alt'] = '';
            }
            if (\core_text::strlen($props['alt']) > 500 || \core_text::strlen($props['caption']) > 1000) {
                throw new invalid_parameter_exception('A Media text setting is too long.');
            }
            if (!in_array($props['aspect'], ['auto', 'portrait', 'three_two', 'four_three', 'sixteen_nine', 'square', 'custom'], true)
                    || !in_array($props['fit'], ['cover', 'contain', 'custom'], true)
                    || !in_array($props['focal'], [
                        'center', 'top', 'bottom', 'left', 'right', 'top_left', 'top_right',
                        'bottom_left', 'bottom_right', 'custom',
                    ], true)
                    || !in_array($props['loading'], ['lazy', 'eager'], true)) {
                throw new invalid_parameter_exception('A Media presentation setting is invalid.');
            }
            $props = \local_frontpage\local\media_presentation::normalise($props);
            unset($props);
        }
        if ($type === 'spacer') {
            $height = $segment['props']['height'] ?? null;
            if (!is_int($height) || $height < 1 || $height > 1200) {
                throw new invalid_parameter_exception('The Spacer height must be between 1 and 1200 pixels.');
            }
            $colour = trim((string) ($segment['props']['color'] ?? ''));
            $segment['props']['color'] = $colour === ''
                ? '' : \local_frontpage\local\color_value::normalise_picker_value($colour);
            $segment['props']['layout'] = array_merge(['width' => 'inherit', 'background' => 'transparent'],
                $segment['props']['layout'] ?? [], [
                'appearance' => 'plain', 'padding' => 'none', 'radius' => 'none', 'spacing' => 'none',
            ]);
        }
        if ($type === 'moodle_course_list') {
            $mode = $segment['props']['mode'] ?? null;
            $allowedmodes = ['configured', 'available', 'enrolled', 'category', 'categories', 'combo', 'search'];
            if (!is_string($mode) || !in_array($mode, $allowedmodes, true)) {
                throw new invalid_parameter_exception('The Moodle course list mode is not supported.');
            }
            if ($mode === 'category' && empty($segment['props']['categoryid'])) {
                throw new invalid_parameter_exception('A category course list must identify a category.');
            }
        }
        if ($type === 'course_module') {
            if (isset($segment['props']['placeholder']) && !is_bool($segment['props']['placeholder'])) {
                throw new invalid_parameter_exception('The course module placeholder setting must be boolean.');
            }
            $isplaceholder = !empty($segment['props']['placeholder']);
            if (!is_int($segment['props']['cmid'] ?? null)
                    || (!$isplaceholder && $segment['props']['cmid'] <= 0)
                    || ($isplaceholder && $segment['props']['cmid'] !== 0)) {
                throw new invalid_parameter_exception('A course module segment must contain a valid cmid.');
            }
            if ($isplaceholder && (!is_string($segment['props']['placeholderlabel'] ?? null)
                    || trim($segment['props']['placeholderlabel']) === ''
                    || \core_text::strlen($segment['props']['placeholderlabel']) > 255)) {
                throw new invalid_parameter_exception('A course module placeholder must contain a valid label.');
            }
            if (isset($segment['props']['usedescription']) && !is_bool($segment['props']['usedescription'])) {
                throw new invalid_parameter_exception('The course module description setting must be boolean.');
            }
        }
        if (isset($segment['props']['layout'])) {
            $layout = $segment['props']['layout'];
            if (!is_array($layout) || array_is_list($layout)) {
                throw new invalid_parameter_exception('Segment layout settings must be a JSON object.');
            }
            $allowed = [
                'width' => ['inherit', 'theme', 'content', 'wide', 'full', 'custom'],
                'background' => ['transparent', 'white', 'light', 'dark', 'primary', 'secondary', 'custom'],
                'padding' => ['none', 'small', 'medium', 'large', 'custom'],
            ];
            foreach ($allowed as $property => $values) {
                if (!isset($layout[$property]) || !in_array($layout[$property], $values, true)) {
                    throw new invalid_parameter_exception('A segment layout setting is not supported.');
                }
            }
            $optional = [
                'appearance' => ['plain', 'surface', 'band', 'card'],
                'textcolor' => ['auto', 'light', 'dark'],
                'alignment' => ['start', 'center', 'end'],
                'radius' => ['none', 'small', 'medium', 'large', 'custom'],
                'spacing' => ['none', 'small', 'medium', 'large', 'custom'],
                'spacingtop' => ['none', 'small', 'medium', 'large', 'custom'],
                'bordercolor' => ['border', 'primary', 'secondary', 'custom'],
            ];
            foreach ($optional as $property => $values) {
                if (isset($layout[$property]) && !in_array($layout[$property], $values, true)) {
                    throw new invalid_parameter_exception('A segment layout setting is not supported.');
                }
            }
            $layout += [
                'spacingtop' => 'none',
                'bordertop' => false,
                'borderright' => false,
                'borderbottom' => false,
                'borderleft' => false,
                'borderthickness' => '1px',
                'bordercolor' => 'border',
            ];
            if (($layout['width'] ?? '') === 'custom') {
                if (!isset($layout['customwidth']) || !is_string($layout['customwidth'])) {
                    throw new invalid_parameter_exception('A custom segment width must contain a CSS width value.');
                }
                \local_frontpage\local\width_value::normalise($layout['customwidth']);
            } else if (isset($layout['customwidth'])) {
                throw new invalid_parameter_exception('A custom segment width is only valid in custom width mode.');
            }
            if ($layout['background'] === 'custom') {
                $layout['backgroundvalue'] = \local_frontpage\local\color_value::normalise_picker_value(
                    (string) ($layout['backgroundvalue'] ?? '')
                );
            } else {
                unset($layout['backgroundvalue']);
            }
            foreach (['padding' => 'paddingvalue', 'radius' => 'radiusvalue', 'spacing' => 'spacingvalue',
                'spacingtop' => 'spacingtopvalue']
                    as $modeproperty => $valueproperty) {
                if (($layout[$modeproperty] ?? '') === 'custom') {
                    $layout[$valueproperty] = \local_frontpage\local\css_length_value::normalise(
                        (string) ($layout[$valueproperty] ?? '')
                    );
                } else {
                    unset($layout[$valueproperty]);
                }
            }
            foreach (['bordertop', 'borderright', 'borderbottom', 'borderleft'] as $property) {
                if (!isset($layout[$property]) || !is_bool($layout[$property])) {
                    throw new invalid_parameter_exception('A segment border side is invalid.');
                }
            }
            $layout['borderthickness'] = \local_frontpage\local\css_length_value::normalise(
                (string) ($layout['borderthickness'] ?? '')
            );
            if ($layout['bordercolor'] === 'custom') {
                $layout['bordercolorvalue'] = \local_frontpage\local\color_value::normalise_picker_value(
                    (string) ($layout['bordercolorvalue'] ?? '')
                );
            } else {
                unset($layout['bordercolorvalue']);
            }
            if (isset($layout['colourmodes'])) {
                $layout['colourmodes'] = \local_frontpage\local\segment_colour_modes::normalise($layout['colourmodes']);
                if (!$layout['colourmodes']) { unset($layout['colourmodes']); }
            }
            $segment['props']['layout'] = $layout;

            foreach (['customwidth', 'backgroundvalue', 'paddingvalue', 'radiusvalue', 'spacingvalue',
                'spacingtopvalue', 'borderthickness', 'bordercolorvalue', 'bordertop', 'borderright',
                'borderbottom', 'borderleft', 'colourmodes'] as $property) {
                $optional[$property] = [];
            }
            $knownproperties = array_merge(array_keys($allowed), array_keys($optional));
            if (array_diff(array_keys($layout), $knownproperties)) {
                throw new invalid_parameter_exception('A segment layout setting is not supported.');
            }
        }

        if ($type === 'grid') {
            if (array_key_exists('overlap', $segment['props']) || array_key_exists('overlapsize', $segment['props'])) {
                throw new invalid_parameter_exception('Unsupported Grid option.');
            }
            $segment['props'] = \local_frontpage\local\grid_structure::normalise_props($segment['props']);
        }
        if ($type === 'rich_text' && !is_string($segment['props']['content'] ?? null)) {
            throw new invalid_parameter_exception('Rich Text content must be text.');
        }
        if ($type === 'course_navigation' && $segment['id'] !== 'course_navigation') {
            throw new invalid_parameter_exception('Course navigation is protected.');
        }
    }
}
