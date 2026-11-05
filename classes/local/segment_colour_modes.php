<?php
/**
 * Optional, revision-owned colour overrides; absence preserves the existing design.
 *
 * @package local_frontpage
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license 108design source-available software license; see LICENSE.md
 */

namespace local_frontpage\local;

/**
 * Optional, revision-owned colour overrides; absence preserves the existing design.
 */
final class segment_colour_modes {
    /**
     * Modes.
     */
    public const MODES = ['light', 'dark'];
    /**
     * Roles.
     */
    public const ROLES = ['background', 'text', 'border'];

    /**
     * Validate and normalise the supplied value.
     *
     * @param mixed $values Values to process.
     * @return array
     */
    public static function normalise($values): array {
        if (!is_array($values) || array_diff(array_keys($values), self::MODES)) {
            throw new \invalid_parameter_exception('Invalid segment colour modes.');
        }
        $result = [];
        foreach ($values as $mode => $roles) {
            if (!is_array($roles) || array_diff(array_keys($roles), self::ROLES)) {
                throw new \invalid_parameter_exception('Invalid segment colour roles.');
            }
            foreach ($roles as $role => $value) {
                if (!is_string($value)) {
                    throw new \invalid_parameter_exception('Invalid segment colour.');
                }
                if (trim($value) !== '') {
                    $result[$mode][$role] = color_value::normalise_picker_value($value);
                }
            }
        }
        return $result;
    }

    /**
     * Return the supported field definitions for this form section.
     *
     * @return array
     */
    public static function fields(): array {
        $fields = ['layoutcolourcustom', 'layoutcolourhelp', 'layoutcolourview'];
        foreach (self::MODES as $mode) {
            foreach (self::ROLES as $role) {
                $fields[] = 'layoutcolour' . $mode . $role;
            }
        }
        return $fields;
    }

    /**
     * Add the native form controls for these settings.
     *
     * @param \MoodleQuickForm $form Native form receiving the controls.
     * @return void
     */
    public static function add_form_fields(\MoodleQuickForm $form): void {
        global $PAGE;
        $form->addElement('select', 'layoutcolourcustom', get_string('segmentcoloursource', 'local_frontpage'), [
            0 => get_string('segmentcolourinherit', 'local_frontpage'),
            1 => get_string('segmentcolourown', 'local_frontpage'),
        ]);
        $form->setType('layoutcolourcustom', PARAM_BOOL);
        $form->addElement('static', 'layoutcolourhelp', '', get_string('segmentcolourhelp', 'local_frontpage'));
        $form->addElement('select', 'layoutcolourview', get_string('segmentcoloureditmode', 'local_frontpage'), [
            'light' => get_string('componentpreviewlight', 'local_frontpage'),
            'dark' => get_string('componentpreviewdark', 'local_frontpage'),
        ]);
        $form->setType('layoutcolourview', PARAM_ALPHA);
        $form->hideIf('layoutcolourview', 'layoutcolourcustom', 'eq', 0);
        foreach (self::MODES as $mode) {
            foreach (self::ROLES as $role) {
                $name = 'layoutcolour' . $mode . $role;
                $form->addElement('text', $name, get_string('segmentcolour' . $role, 'local_frontpage'), [
                    'maxlength' => 120, 'data-colour-input' => 1,
                    'data-colour-empty-label' => get_string('segmentcolourinherit', 'local_frontpage'),
                    'placeholder' => get_string('segmentcolourinherit', 'local_frontpage'),
                ]);
                $form->setType($name, PARAM_RAW_TRIMMED);
                $form->hideIf($name, 'layoutcolourcustom', 'eq', 0);
                $form->hideIf($name, 'layoutcolourview', 'neq', $mode);
            }
        }
        $PAGE->requires->js_call_amd('local_frontpage/segment_colour_modes', 'init');
    }

    /**
     * Return the default values for the supported settings.
     *
     * @param array $layout Layout.
     * @return array
     */
    public static function defaults(array $layout): array {
        $values = $layout['colourmodes'] ?? [];
        $result = ['layoutcolourcustom' => !empty($values), 'layoutcolourview' => 'light'];
        foreach (self::MODES as $mode) {
            foreach (self::ROLES as $role) {
                $result['layoutcolour' . $mode . $role] = $values[$mode][$role] ?? '';
            }
        }
        return $result;
    }

    /**
     * Read and normalise the settings submitted by the native form.
     *
     * @param object $data Submitted or stored data.
     * @return array
     */
    public static function from_form(object $data): array {
        if (empty($data->layoutcolourcustom)) {
            return [];
        }
        $values = [];
        foreach (self::MODES as $mode) {
            foreach (self::ROLES as $role) {
                $values[$mode][$role] = $data->{'layoutcolour' . $mode . $role} ?? '';
            }
        }
        return self::normalise($values);
    }

    /**
     * Return field-specific errors for the submitted settings.
     *
     * @param array $data Submitted or stored data.
     * @return array
     */
    public static function form_errors(array $data): array {
        $errors = [];
        if (empty($data['layoutcolourcustom'])) {
            return $errors;
        }
        foreach (self::MODES as $mode) {
            foreach (self::ROLES as $role) {
                $name = 'layoutcolour' . $mode . $role;
                if (trim($data[$name] ?? '') === '') {
                    continue;
                }
                try {
                    color_value::normalise_picker_value($data[$name]);
                } catch (\invalid_parameter_exception $e) {
                    $errors[$name] = get_string('invalidcustomcolor', 'local_frontpage');
                }
            }
        }
        return $errors;
    }

    /**
     * Return the styles derived from the validated settings.
     *
     * @param string $id Record ID.
     * @param array $values Values to process.
     * @return string
     */
    public static function css(string $id, array $values): string {
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $id)) {
            throw new \invalid_parameter_exception('Invalid segment id.');
        }
        $css = '';
        foreach (self::normalise($values) as $mode => $roles) {
            $selector = '#fp-segment-' . $id . '[data-frontpage-colour-mode="' . $mode . '"]';
            foreach ($roles as $role => $value) {
                $property = ['background' => 'background-color', 'text' => 'color', 'border' => 'border-color'][$role];
                $css .= $selector . '{' . $property . ':' . $value . ';}';
                if ($role === 'text') {
                    $text = $selector . ' > .local-frontpage-layout-inner > :is(.local-frontpage-heading,'
                        . '.local-frontpage-rich-text,.local-frontpage-hero)';
                    $css .= $text . '{color:' . $value . ';}' . $text . ' :where(h1,h2,h3,h4,h5,h6){color:inherit;}';
                }
            }
        }
        return $css;
    }
}
