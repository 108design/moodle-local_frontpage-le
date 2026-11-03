<?php
/**
 * @package local_frontpage
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license 108design source-available software license; see LICENSE.md
 */
namespace local_frontpage\output;
use html_writer;
use moodle_url;
use local_frontpage\local\service\factory;
final class page_renderer {
    private array $currentsegments = [];
    private \context $context;
    public function __construct(private readonly \core_renderer $output, private readonly \stdClass $frontpage,
            private readonly int $revisionid, private readonly bool $showdisabled = false, private readonly array $editactions = []) {
        $this->context = \local_frontpage\local\page_access::context($frontpage);
    }
    public function render(string $json): string {
        $GLOBALS['PAGE']->requires->js_call_amd('local_frontpage/hero_contrast','init');
        $document = json_decode((new \local_frontpage\local\document\validator())->validate_and_normalise($json),true);
        $this->currentsegments = array_column($document['segments'],null,'id');
        $consumed = \local_frontpage\local\grid_structure::consumed_ids($document['segments']);
        $html = '';
        foreach ($document['segments'] as $segment) {
            if (!isset($consumed[$segment['id']]) && $segment['type']!=='course_navigation'
                    && ($this->is_segment_enabled($segment) || $this->showdisabled)) {
                $html .= $this->render_segment($segment);
            }
        }
        $width=match($this->frontpage->contentwidth??'theme'){
            'content'=>'960px','wide'=>'1320px','full'=>'100%',
            'custom'=>\local_frontpage\local\width_value::normalise((string)$this->frontpage->contentwidthvalue),default=>'',
        };
        return html_writer::div($html,'local-frontpage-document',$width!==''?['style'=>'max-width:'.$width.';margin-inline:auto']:[]);
    }
    private function is_segment_enabled(array $segment): bool {return $segment['props']['enabled'] ?? true;}
    private function is_authoring_view(): bool {return $this->showdisabled;}
    private function replace_segment_asset_shortcodes(string $source, array $segment): string {
        return preg_replace_callback('/\{\{image-url:([a-zA-Z0-9][a-zA-Z0-9._ -]{0,199})\}\}/',function($m) {
            $file = get_file_storage()->get_file($this->context->id,'local_frontpage','pageassets',
                $this->revisionid,'/',$m[1]);
            return $file ? moodle_url::make_pluginfile_url($this->context->id,'local_frontpage','pageassets',
                $this->revisionid,'/',$m[1])->out(false) : '';
        },$source);
    }
    private function render_segment(array $segment): string {
        $props = $segment['props'];
        $herobackgroundurl = '';
        $file = \local_frontpage\local\revision_files::get_hero_background($this->revisionid,$segment['id']);
        if ($file && \local_frontpage\local\media_files::is_media($file)) {
            $herobackgroundurl = moodle_url::make_pluginfile_url($this->context->id,'local_frontpage',
                \local_frontpage\local\revision_files::hero_background_area($segment['id']),$this->revisionid,
                $file->get_filepath(),$file->get_filename())->out(false);
        }
        $mediaurl = ($props['asset'] ?? '') === \local_frontpage\local\media_files::ASSET
            ? $herobackgroundurl : $this->replace_segment_asset_shortcodes((string)($props['asset']??''),$segment);
        $content = match ($segment['type']) {
            'grid' => $this->render_grid_segment($segment),
            'spacer' => $this->output->render_from_template('local_frontpage/segment_spacer', [
                'id' => $segment['id'],
                'height' => (int) ($props['height'] ?? 96),
                'color' => (string) ($props['color'] ?? ''),
            ]),
            'hero' => $this->output->render_from_template('local_frontpage/segment_hero', [
                'id' => $segment['id'],
                'eyebrow' => (string) ($props['eyebrow'] ?? ''),
                'hasidentity' => !empty($props['eyebrow']) || !empty($props['mark']),
                'mark' => $this->replace_segment_asset_shortcodes((string) ($props['mark'] ?? ''), $segment),
                'markalt' => (string) ($props['markalt'] ?? ''),
                'headinglevel1' => ($props['headinglevel'] ?? 2) === 1,
                'hasactions' => !empty($props['primaryurl']) || !empty($props['secondaryurl']),
                'primarylabel' => (string) ($props['primarylabel'] ?? ''),
                'primaryurl' => (string) ($props['primaryurl'] ?? ''),
                'secondarylabel' => (string) ($props['secondarylabel'] ?? ''),
                'secondaryurl' => (string) ($props['secondaryurl'] ?? ''),
                'heading' => (string) ($props['heading'] ?? ''),
                'text' => (string) ($props['text'] ?? ''),
                'style' => $props['style'] ?? 'surface',
                'height' => $props['height'] ?? 'normal',
                'heightvalue' => $props['heightvalue'] ?? '',
                'titlesize' => $props['titlesize'] ?? 'large',
                'titlesizevalue' => $props['titlesizevalue'] ?? '',
                'herolayout' => $props['herolayout'] ?? 'overlay',
                'overlay' => $props['overlay'] ?? 'auto',
                'contrast' => $props['contrast'] ?? 'auto',
                'contrastvalue' => $props['contrastvalue'] ?? '',
                'texteffect' => $props['texteffect'] ?? 'shadow',
                'mediastyle' => \local_frontpage\local\hero_media_presentation::style($props),
                'backgroundsize' => $props['backgroundsize'] ?? 'cover',
                'backgroundposition' => $props['backgroundposition'] ?? 'center',
                'backgroundimage' => $herobackgroundurl,
                'backgroundphoto' => $herobackgroundurl,
            ]),
            'media' => $this->output->render_from_template('local_frontpage/segment_media', [
                'presentationstyle' => \local_frontpage\local\media_presentation::style($props),
                'id' => $segment['id'],
                'src' => $mediaurl,
                'alt' => (string) ($props['alt'] ?? ''),
                'decorative' => !empty($props['decorative']),
                'caption' => (string) ($props['caption'] ?? ''),
                'aspect' => (string) ($props['aspect'] ?? 'three_two'),
                'fit' => (string) ($props['fit'] ?? 'cover'),
                'focal' => (string) ($props['focal'] ?? 'center'),
                'loading' => (string) ($props['loading'] ?? 'lazy'),
            ]),
            'heading' => $this->output->render_from_template('local_frontpage/segment_heading', [
                'id' => $segment['id'],
                'anchor' => 'fp-heading-' . $segment['id'],
                'text' => (string) ($props['text'] ?? ''),
                'level2' => ((int) ($props['level'] ?? 2)) === 2,
                'level3' => ((int) ($props['level'] ?? 2)) === 3,
                'level4' => ((int) ($props['level'] ?? 2)) === 4,
                'titlesize' => $props['titlesize'] ?? 'theme',
                'titlesizevalue' => $props['titlesizevalue'] ?? '',
                'color' => $props['color'] ?? 'inherit',
                'colorvalue' => $props['colorvalue'] ?? '',
            ]),

            'rich_text' => $this->output->render_from_template('local_frontpage/segment_rich_text',[
                'id'=>$segment['id'],'content'=>format_text(file_rewrite_pluginfile_urls(
                    $this->replace_segment_asset_shortcodes($props['content'],$segment),'pluginfile.php',
                    $this->context->id,'local_frontpage','editor',$this->revisionid),FORMAT_HTML,
                    ['context'=>$this->context,'noclean'=>false])]),
            'moodle_course_list' => $this->course_list($props),
            'course_module' => $this->render_course_module($segment),
            default => '',
        };
        $layout = $props['layout'] ?? [];
        $themewidth = null;
        $width = $layout['width'] ?? 'wide';
        $customwidth = null;
        if ($width === 'custom') {
            $customwidth = $layout['customwidth'] ?? null;
        } else if ($width === 'theme') {
            $customwidth = $themewidth;
            if ($customwidth === null) {
                $width = 'wide';
            }
        }
        $layoutstyles = [];
        if ($customwidth !== null && $customwidth !== '') {
            $layoutstyles[] = 'max-width: ' . $customwidth;
        }
        foreach ([
            'backgroundvalue' => '--local-frontpage-segment-background',
            'paddingvalue' => '--local-frontpage-segment-padding',
            'radiusvalue' => '--local-frontpage-segment-radius',
            'spacingvalue' => '--local-frontpage-segment-spacing',
            'spacingtopvalue' => '--local-frontpage-segment-spacing-top',
            'borderthickness' => '--local-frontpage-segment-border-width',
        ] as $property => $variable) {
            if (!empty($layout[$property])) {
                $layoutstyles[] = $variable . ': ' . $layout[$property];
            }
        }
        if (!empty($layout['backgroundvalue'])) {
            $layoutstyles[] = '--local-frontpage-segment-foreground: '
                . \local_frontpage\local\color_value::contrasting_text_colour($layout['backgroundvalue']);
        }
        $bordercolour = match ($layout['bordercolor'] ?? 'border') {
            'primary' => 'var(--bs-primary, #0d6efd)',
            'secondary' => 'var(--bs-secondary, #6c757d)',
            'custom' => (string) ($layout['bordercolorvalue'] ?? ''),
            default => 'var(--bs-border-color, #dee2e6)',
        };
        if ($bordercolour !== '') {
            $layoutstyles[] = '--local-frontpage-segment-border-color: ' . $bordercolour;
        }
        $content = $this->output->render_from_template('local_frontpage/segment_layout', [
            'content' => $content,
            'segmentid' => $segment['id'],
            'segmenttype' => $segment['type'],
            'typekey' => str_replace('_', '-', $segment['type']),
            'width' => $width,
            'customwidth' => $customwidth,
            'inlinestyle' => implode('; ', $layoutstyles),
            'hascolourmodes' => !empty($layout['colourmodes']),
            'appearance' => $layout['appearance'] ?? 'plain',
            'background' => $layout['background'] ?? 'transparent',
            'padding' => $layout['padding'] ?? 'none',
            'textcolor' => $layout['textcolor'] ?? 'auto',
            'alignment' => $layout['alignment'] ?? 'start',
            'radius' => $layout['radius'] ?? 'none',
            'spacing' => $layout['spacing'] ?? 'medium',
            'spacingtop' => $layout['spacingtop'] ?? 'none',
            'bordertop' => !empty($layout['bordertop']),
            'borderright' => !empty($layout['borderright']),
            'borderbottom' => !empty($layout['borderbottom']),
            'borderleft' => !empty($layout['borderleft']),
        ]);

        if (!empty($layout['colourmodes'])) {
            $GLOBALS['PAGE']->requires->js_call_amd('local_frontpage/segment_colour_modes','init');
            $content = html_writer::tag('style',\local_frontpage\local\segment_colour_modes::css(
                $segment['id'],$layout['colourmodes'])).$content;
        }
        return $this->editactions ? authoring::segment($segment,$content,$this->editactions,$this->currentsegments) : $content;
    }
    private function render_grid_segment(array $grid): string {
        $props = $grid['props'] ?? [];
        $cells = [];
        foreach ($props['segmentids'] ?? [] as $index => $segmentid) {
            $child = $this->currentsegments[(string) $segmentid] ?? null;

            if (!$child || ($child['type'] ?? '') === \local_frontpage\local\grid_structure::TYPE) {
                $cells[] = ['content' => '', 'position' => $index + 1, 'empty' => true,
                    'mobilehidden' => !empty($props['mobilehidden'][$index])];
                continue;
            }
            $content = '';
            if ($this->is_segment_enabled($child) || $this->showdisabled) {
                $content = $this->render_segment($child);
            }
            $cells[] = ['content' => $content, 'position' => $index + 1, 'empty' => $content === '',
                'mobilehidden' => !empty($props['mobilehidden'][$index])];
        }
        while (count($cells) < (int) ($props['columns'] ?? 2)) {
            $cells[] = ['content' => '', 'position' => count($cells) + 1, 'empty' => true,
                'mobilehidden' => false];
        }
        $customstyle = '';
        if (($props['template'] ?? 'equal') === 'custom') {
            $customstyle = '--local-frontpage-grid-template: ' . implode(' ', array_map(
                static fn(int $width): string => 'minmax(0, ' . $width . 'fr)',
                $props['widths'] ?? []
            )) . ';';
        }
        if (($props['heightmode'] ?? 'auto') !== 'auto') {
            $customstyle .= '--local-frontpage-grid-height: ' . (string) ($props['heightvalue'] ?? '150px') . ';';
        }
        return $this->output->render_from_template('local_frontpage/segment_grid', [
            'id' => $grid['id'],
            'columns' => (int) ($props['columns'] ?? 2),
            'template' => (string) ($props['template'] ?? 'equal'),
            'gap' => (string) ($props['gap'] ?? 'medium'),
            'alignment' => (string) ($props['alignment'] ?? 'stretch'),
            'heightmode' => (string) ($props['heightmode'] ?? 'auto'),
            'overflow' => (string) ($props['overflow'] ?? 'visible'),
            'mediafit' => !empty($props['mediafit']),
            'stackbreakpoint' => (string) ($props['stackbreakpoint'] ?? 'md'),
            'stackautoheight' => ($props['heightmode'] ?? 'auto') === 'fixed'
                && ($props['stackbreakpoint'] ?? 'md') !== 'none'
                && ($props['stackautoheight'] ?? true),
            'reverse' => ($props['mobileorder'] ?? 'natural') === 'reverse',
            'customstyle' => $customstyle,
            'cells' => $cells,
            'authoring' => $this->is_authoring_view(),
        ]);
    }
    private function render_course_module(array $segment): string {
        global $CFG, $USER;

        if (!empty($segment['props']['placeholder'])) {
            return $this->showdisabled
                ? $this->output->notification(get_string('coursemoduleplaceholdernotice', 'local_frontpage',
                    $segment['props']['placeholderlabel'] ?? get_string('coursemoduleplaceholderfallback',
                        'local_frontpage')), 'warning')
                : '';
        }

        require_once($CFG->dirroot . '/course/format/lib.php');

        if ($this->context->contextlevel !== CONTEXT_MODULE && !$this->frontpage?->sitehomescope) {
            return $this->showdisabled
                ? $this->output->notification(get_string('coursemodulewrongcontext', 'local_frontpage'), 'warning')
                : '';
        }
        try {
            $cm = \local_frontpage\local\course_module_reference::get(
                $this->context,
                (int) ($segment['props']['cmid'] ?? 0),
                $this->frontpage
            );
        } catch (\Throwable) {
            return $this->showdisabled
                ? $this->output->notification(get_string('coursemoduleunavailable', 'local_frontpage'), 'warning')
                : '';
        }
        if (!$cm->uservisible && empty($cm->availableinfo) && !$this->showdisabled) {
            return $this->showdisabled
                ? $this->output->notification(get_string('coursemoduleunavailable', 'local_frontpage'), 'warning')
                : '';
        }

        $format = course_get_format($cm->course);
        $section = $format->get_modinfo()->get_section_info((int) $cm->sectionnum, MUST_EXIST);
        $cmclass = $format->get_output_classname('content\\cm');
        $hasediting = property_exists($USER, 'editing');
        $editing = $USER->editing ?? null;
        $USER->editing = 0;
        try {
            $card = new $cmclass($format, $section, $cm);
            $cardhtml = $this->output->render($card);
        } finally {
            if ($hasediting) {
                $USER->editing = $editing;
            } else {
                unset($USER->editing);
            }
        }
        return $this->output->render_from_template('local_frontpage/segment_course_module', [
            'id' => $segment['id'],
            'cmid' => $cm->id,
            'card' => $cardhtml,
        ]);
    }

    private function course_list(array $props): string {
        global $CFG, $PAGE;
        require_once($CFG->dirroot.'/course/lib.php');
        $r = $PAGE->get_renderer('core','course');
            return match ($props['mode']) {
                'configured'=>$r->frontpage(), 'available'=>$r->frontpage_available_courses(),
                'enrolled'=>$r->frontpage_my_courses(), 'categories'=>$r->frontpage_categories_list(),
                'combo'=>$r->frontpage_combo_list(), 'search'=>$r->course_search_form(),
                'category'=>(function() use ($r,$props,$CFG) {
                    $cat = \core_course_category::get((int)$props['categoryid'],IGNORE_MISSING);
                    if (!$cat || !$cat->is_uservisible()) {return '';}
                    $limit=max(1,(int)($CFG->frontpagecourselimit??$CFG->coursesperpage));
                    return $r->courses_list($cat->get_courses(['recursive'=>true,'limit'=>$limit]),false,
                        'frontpage-course-list-category',new moodle_url('/course/index.php',['categoryid'=>$cat->id]),
                        $cat->get_courses_count(['recursive'=>true]),0,$limit);
                })(), default=>'',
            };
    }
}
