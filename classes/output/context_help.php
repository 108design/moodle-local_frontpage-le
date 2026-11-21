<?php
// This file is part of Moodle - https://moodle.org/
/**
 * Read-only customer help, limited to the current authoring context.
 *
 * @package local_frontpage
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license 108design source-available software license; see LICENSE.md
 */

namespace local_frontpage\output;

/**
 * Read-only customer help, limited to the current authoring context.
 */
final class context_help {
    /**
     * Contexts.
     */
    private const CONTEXTS = [
        'pages' => ['publication', 'assignments', 'backup'],
        'pluginsettings' => ['assignments', 'backup'],
        'editor' => ['editing', 'publication', 'files', 'activities', 'pagecss'],
        'activitynavigation' => ['activitynavigation', 'publication', 'activities'],
        'segmentsettings' => ['editing', 'grid', 'activities', 'files'],
        'revisionhistory' => ['history', 'publication'],
        'pagefiles' => ['files', 'publication'],
        'pagesettings' => ['pageoptions', 'publication'],
        'pagecss' => ['pagecss', 'publication', 'history', 'files'],
        'resources' => ['resources', 'activities'],
        'activitysettings' => ['activities', 'completion', 'backup'],
    ];
    /**
     * @var ?string Context.
     */
    private static ?string $context = null;
    /**
     * Additional topics belong to the registered help context.
     *
     * @var array
     */
    private static array $buildertopics = [];

    /**
     * Supply source help for every Frontpage page host, linking to that host's panel.
     */


    /**
     * Shared forms keep their original labels outside a registered help page.
     *
     * @param string $key Stable lookup key.
     * @param string $topic Registered contextual help topic.
     * @param string $context Native Moodle context.
     * @return string
     */
    public static function label(string $key, string $topic, string $context): string {
        return get_string($key, 'local_frontpage')
            . (self::registered($context) ? ' ' . self::icon($topic, $context) : '');
    }

    /**
     * Attach linked form help while preserving native label text and field behaviour.
     *
     * @param \MoodleQuickForm $form Native form receiving the controls.
     * @param string $field Field name.
     * @param string $topic Registered contextual help topic.
     * @param string $context Native Moodle context.
     * @return bool
     */
    public static function field(
        \MoodleQuickForm $form,
        string $field,
        string $topic,
        string $context
    ): bool {
        if (!self::registered($context) || !$form->elementExists($field)) {
            return false;
        }
        $element = $form->getElement($field);
        $icon = self::icon($topic, $context);
        if ($element->getType() === 'filemanager') {
            $element->_helpbutton = $icon;
        } else {
            $element->setLabel($element->getLabel() . ' ' . $icon);
        }
        return true;
    }

    /**
     * Shared preview renderers must not add help to unregistered chooser contexts.
     *
     * @param string $context Native Moodle context.
     * @return bool
     */
    public static function registered(string $context): bool {
        return self::$context === $context;
    }

    /**
     * Register the assets needed by the current native page.
     *
     * @param string $context Native Moodle context.
     * @return void
     */
    public static function prepare(string $context): void {
        global $CFG, $PAGE;
        self::topics($context);
        self::$context = $context;
        $PAGE->requires->css(new \moodle_url('/local/frontpage/styles/context_help.css', [
            'rev' => get_config('local_frontpage', 'version'),
        ]));
        $module = match (true) {
            (int)$CFG->version < 2025041400 => 'context_help_legacy',
            (int)$CFG->version < 2026100500 => 'context_help',
            default => 'context_help_53',
        };
        $PAGE->requires->js_call_amd('local_frontpage/context_help_form', 'init');
        $PAGE->requires->js_call_amd('local_frontpage/' . $module, 'init', [[
            'topic' => optional_param('frontpagehelp', '', PARAM_ALPHA),
        ]]);
    }

    /**
     * Return the help topics available in the current editing context.
     *
     * @param string $context Native Moodle context.
     * @return array
     */
    private static function topics(string $context): array {
        if (!isset(self::CONTEXTS[$context])) {
            throw new \coding_exception('Unknown help context.');
        }
        return array_values(array_unique(array_merge(self::CONTEXTS[$context], self::$buildertopics[$context] ?? [])));
    }

    /**
     * Keep Moodle's help popover, using an information icon for our own context help.
     *
     * @param string $topic Registered contextual help topic.
     * @param string $context Native Moodle context.
     * @return string
     */
    public static function icon(string $topic, string $context = 'pages'): string {
        global $CFG, $PAGE;
        if (!in_array($topic, self::topics($context), true)) {
            throw new \coding_exception('Unknown help topic.');
        }
        $url = $context === 'pages' ? new \moodle_url('/local/frontpage/pages.php') : new \moodle_url($PAGE->url);
        $url->param('frontpagehelp', $topic);
        $help = new \help_icon('pagehelp_' . $topic, 'local_frontpage', $url->out(false));
        // Form labels and dynamic source configuration may request help before output starts.
        $renderer = $PAGE->get_renderer('core');
        $data = (array)$help->export_for_template($renderer);
        $data['completedoclink'] = \html_writer::link($url, get_string('pagehelp_more', 'local_frontpage'));
        $data['legacy'] = (int)$CFG->version < 2025041400;
        $data['infoicon'] = (int)$CFG->version < 2026100500 ? 'i/info' : 'i/circleinfo';
        return $renderer->render_from_template('local_frontpage/context_help_icon', $data);
    }

    /**
     * Called by Moodle's navbar callback only after a permitted page registers its help.
     *
     * @param \renderer_base $renderer Native renderer used for output.
     * @return string
     */
    public static function navbar(\renderer_base $renderer): string {
        global $CFG;
        if (self::$context === null) {
            return '';
        }
        $key = self::$context === 'pages' ? 'pagehelp_title' : 'pagehelp_' . self::$context . '_title';
        $label = get_string($key, 'local_frontpage');
        return \html_writer::tag(
            'button',
            $renderer->pix_icon((int)$CFG->version < 2026100500 ? 'i/info' : 'i/circleinfo', ''),
            [

            'type' => 'button',
            'class' => 'nav-link icon-no-margin local-frontpage-help-navbar',
            'data-frontpage-help-open' => '', 'data-frontpage-help-context' => self::$context,
            'aria-label' => $label, 'title' => $label,
            'aria-controls' => 'local-frontpage-context-help', 'aria-expanded' => 'false',

            ]
        );
    }

    /**
     * Render the help panel for the registered page context.
     *
     * @param string $context Native Moodle context.
     * @return string
     */
    public static function render(string $context = 'pages'): string {
        global $CFG, $PAGE;
        $key = $context === 'pages' ? 'pagehelp_title' : 'pagehelp_' . $context . '_title';
        $title = get_string($key, 'local_frontpage');
        $close = \html_writer::tag('button', \html_writer::span('&#215;', '', ['aria-hidden' => 'true']), [
            'type' => 'button', 'class' => 'btn btn-secondary btn-sm local-frontpage-help-close',
            'aria-label' => get_string('closebuttontitle', 'moodle'), 'data-frontpage-help-close' => '1',
        ]);
        $options = '';
        $articles = '';
        foreach (self::topics($context) as $topic) {
            $label = get_string('pagehelp_' . $topic, 'local_frontpage');
            $options .= \html_writer::tag('option', $label, ['value' => $topic]);
            $articles .= \html_writer::tag(
                'article',
                \html_writer::tag('h3', $label, [ 'class' => 'h5', 'tabindex' => '-1' ])
                . get_string('pagehelp_' . $topic . '_article', 'local_frontpage'),
                [ 'data-frontpage-help-article' => $topic, 'hidden' => 'hidden' ]
            );
        }
        $choice = \html_writer::tag('label', get_string('contexthelptopics', 'local_frontpage'), [
            'for' => 'local-frontpage-help-topic', 'class' => 'form-label',
        ]) . \html_writer::tag('select', $options, [
            'id' => 'local-frontpage-help-topic', 'class' => 'form-select custom-select',
        ]);
        // Shared help-panel identity: no link or action; close remains beside the topic selector.
        $brand = \html_writer::tag('span', \html_writer::empty_tag('img', [
            'src' => $PAGE->get_renderer('core')->image_url('helpbrand', 'local_frontpage')->out(false),
            'alt' => '', 'width' => 25, 'height' => 24,
        ]), [
            'class' => 'local-frontpage-help-brand', 'role' => 'img', 'aria-label' => '108design',
            'title' => '108design', 'tabindex' => '0', 'data-toggle' => 'tooltip', 'data-bs-toggle' => 'tooltip',
        ]);
        $header = \html_writer::tag(
            'header',
            \html_writer::tag('h2', $title, [
            'id' => 'local-frontpage-context-help-title', 'class' => 'sr-only visually-hidden', 'tabindex' => '-1',
            ]) . \html_writer::div($choice, 'local-frontpage-help-choices') . $brand . $close,
            ['class' => 'local-frontpage-help-header local-frontpage-help-header-branded']
        );
        return \html_writer::tag('aside', $header
            . \html_writer::div($articles, 'local-frontpage-help-content', ['tabindex' => '0']), [
                'id' => 'local-frontpage-context-help', 'class' => 'local-frontpage-context-help',
                'aria-labelledby' => 'local-frontpage-context-help-title', 'hidden' => 'hidden',
                'data-frontpage-help-url' => $PAGE->url->out(false),
            ]);
    }

    /**
     * Return the component output appended to the native page footer.
     *
     * @return string
     */
    public static function footer(): string {
        return self::$context === null ? '' : self::render(self::$context);
    }
}
