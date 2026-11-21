<?php
// This file is part of Moodle - https://moodle.org/
/**
 * Manage revision-owned editor images and their segment mappings.
 *
 * @package local_frontpage
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license 108design source-available software license; see LICENSE.md
 */

namespace local_frontpage\local;

/**
 * Manage revision-owned editor images and their segment mappings.
 */
final class revision_files {
    /**
     * Component.
     */
    public const COMPONENT = 'local_frontpage';
    /**
     * Inline image types.
     */
    public const INLINE_IMAGE_TYPES = ['.jpg', '.jpeg', '.png', '.gif', '.webp'];
    /**
     * Area editor.
     */
    public const AREA_EDITOR = 'editor';
    /**
     * Area page assets.
     */
    public const AREA_PAGE_ASSETS = 'pageassets';
    /**
     * Area hero background prefix.
     */
    public const AREA_HERO_BACKGROUND_PREFIX = 'herobg_';
    /**
     * Areas.
     */
    public const AREAS = [self::AREA_EDITOR, self::AREA_PAGE_ASSETS];
    /**
     * Return the raster image types accepted by the editor.
     *
     * @param array $types Types.
     * @param bool $webimagesonly Webimagesonly.
     * @return array
     */
    public static function accepted_image_types(
        array $types = self::INLINE_IMAGE_TYPES,
        bool $webimagesonly = false
    ): array {
        $registered = get_mimetypes_array();
        $expected = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png',
            'gif' => 'image/gif', 'webp' => 'image/webp', 'svg' => 'image/svg+xml'];
        return array_values(array_filter($types, static function (string $extension) use (
            $registered,
            $expected,
            $webimagesonly
): bool {
            $key = ltrim($extension, '.');
            $mime = $expected[$key] ?? null;
            return $mime !== null && ($registered[$key]['type'] ?? null) === $mime
                && (!$webimagesonly || in_array('web_image', $registered[$key]['groups'] ?? [], true));
        }));
    }

    /**
     * Return the file areas owned by the page revision.
     *
     * @param int $pageid Page ID.
     * @return array
     */
    public static function areas_for_page(int $pageid): array {
        global $DB;
        $areas = $DB->get_fieldset_sql(
            'SELECT DISTINCT f.filearea FROM {files} f
               JOIN {local_frontpage_revision} r ON r.id = f.itemid
               JOIN {local_frontpage_page} p ON p.id = r.pageid AND p.contextid = f.contextid
              WHERE p.id = :pageid AND f.component = :component',
            ['pageid' => $pageid, 'component' => self::COMPONENT]
        );
        return array_values(array_unique(array_merge(
            self::AREAS,
            array_filter($areas, [self::class, 'is_hero_background_area'])
        )));
    }

    /**
     * Return the file-area name for the hero background.
     *
     * @param string $segmentid Stable segment identifier.
     * @return string
     */
    public static function hero_background_area(string $segmentid): string {
        return self::AREA_HERO_BACKGROUND_PREFIX . sha1($segmentid);
    }

    /**
     * Check whether a file area belongs to a hero background.
     *
     * @param string $filearea Native Moodle file area.
     * @return bool
     */
    public static function is_hero_background_area(string $filearea): bool {
        return (bool) preg_match('/^' . self::AREA_HERO_BACKGROUND_PREFIX . '[a-f0-9]{40}$/', $filearea);
    }

    /**
     * Copy the files belonging to a revision into the target revision.
     *
     * @param int $sourceid Source record ID.
     * @param int $targetid Target record ID.
     * @return void
     */
    public static function copy_revision(int $sourceid, int $targetid): void {
        if ($sourceid === $targetid) {
            return;
        }
        $fs = get_file_storage();
        $sourcecontextid = self::contextid_for_revision($sourceid);
        $targetcontextid = self::contextid_for_revision($targetid);
        foreach (self::AREAS as $filearea) {
            $fs->delete_area_files($targetcontextid, self::COMPONENT, $filearea, $targetid);
            $files = $fs->get_area_files(
                $sourcecontextid,
                self::COMPONENT,
                $filearea,
                $sourceid,
                'id ASC',
                false
            );
            foreach ($files as $file) {
                $record = [
                    'contextid' => $targetcontextid,
                    'component' => self::COMPONENT,
                    'filearea' => $filearea,
                    'itemid' => $targetid,
                    'filepath' => $file->get_filepath(),
                    'filename' => $file->get_filename(),
                    'userid' => $file->get_userid(),
                    'source' => $file->get_source(),
                    'author' => $file->get_author(),
                    'license' => $file->get_license(),
                ];
                $fs->create_file_from_storedfile($record, $file);
            }
        }
        $sourceareas = self::hero_background_areas($sourceid);
        foreach (self::hero_background_areas($targetid) as $filearea) {
            $fs->delete_area_files($targetcontextid, self::COMPONENT, $filearea, $targetid);
        }
        foreach ($sourceareas as $filearea) {
            self::copy_area($filearea, $sourceid, $sourcecontextid, $filearea, $targetid, $targetcontextid, false);
        }
    }

    /**
     * Delete the files owned by the selected revision.
     *
     * @param int $revisionid Revision ID.
     * @return void
     */
    public static function delete_revision(int $revisionid): void {
        $fs = get_file_storage();
        $contextid = self::contextid_for_revision($revisionid);
        foreach (self::AREAS as $filearea) {
            $fs->delete_area_files($contextid, self::COMPONENT, $filearea, $revisionid);
        }
        foreach (self::hero_background_areas($revisionid) as $filearea) {
            $fs->delete_area_files($contextid, self::COMPONENT, $filearea, $revisionid);
        }
    }

    /**
     * Check whether two stored files contain the same content.
     *
     * @param int $firstrevisionid Firstrevisionid.
     * @param int $secondrevisionid Secondrevisionid.
     * @return bool
     */
    public static function same_content(int $firstrevisionid, int $secondrevisionid): bool {
        return self::content_fingerprint($firstrevisionid) === self::content_fingerprint($secondrevisionid);
    }

    /**
     * Return a stable fingerprint of the stored file contents.
     *
     * @param int $revisionid Revision ID.
     * @return array
     */
    private static function content_fingerprint(int $revisionid): array {
        $fs = get_file_storage();
        $contextid = self::contextid_for_revision($revisionid);
        $areas = array_merge(self::AREAS, self::hero_background_areas($revisionid));
        $fingerprint = [];
        foreach ($areas as $filearea) {
            foreach (
                $fs->get_area_files(
                    $contextid,
                    self::COMPONENT,
                    $filearea,
                    $revisionid,
                    'filepath ASC, filename ASC, contenthash ASC',
                    false
                ) as $file
            ) {
                $fingerprint[] = implode('|', [
                    $filearea,
                    $file->get_filepath(),
                    $file->get_filename(),
                    $file->get_contenthash(),
                    $file->get_filesize(),
                ]);
            }
        }
        sort($fingerprint, SORT_STRING);
        return $fingerprint;
    }

    /**
     * Return the stored hero background for the revision and segment.
     *
     * @param int $revisionid Revision ID.
     * @param string $segmentid Stable segment identifier.
     * @return \stored_file|null
     */
    public static function get_hero_background(int $revisionid, string $segmentid): ?\stored_file {
        $files = get_file_storage()->get_area_files(
            self::contextid_for_revision($revisionid),
            self::COMPONENT,
            self::hero_background_area($segmentid),
            $revisionid,
            'id ASC',
            false
        );
        return $files ? reset($files) : null;
    }

    /**
     * Copy one revision-owned file area into its target.
     *
     * @param string $sourcearea Sourcearea.
     * @param int $sourceid Source record ID.
     * @param int $sourcecontextid Sourcecontextid.
     * @param string $targetarea Targetarea.
     * @param int $targetid Target record ID.
     * @param int $targetcontextid Targetcontextid.
     * @param bool $cleartarget Cleartarget.
     * @return void
     */
    private static function copy_area(
        string $sourcearea,
        int $sourceid,
        int $sourcecontextid,
        string $targetarea,
        int $targetid,
        int $targetcontextid,
        bool $cleartarget
    ): void {
        $fs = get_file_storage();
        if ($cleartarget) {
            $fs->delete_area_files($targetcontextid, self::COMPONENT, $targetarea, $targetid);
        }
        foreach (
            $fs->get_area_files(
                $sourcecontextid,
                self::COMPONENT,
                $sourcearea,
                $sourceid,
                'id ASC',
                false
            ) as $file
        ) {
            $existing = $fs->get_file(
                $targetcontextid,
                self::COMPONENT,
                $targetarea,
                $targetid,
                $file->get_filepath(),
                $file->get_filename()
            );
            if ($existing) {
                $existing->delete();
            }
            $fs->create_file_from_storedfile([
                'contextid' => $targetcontextid, 'component' => self::COMPONENT, 'filearea' => $targetarea,
                'itemid' => $targetid, 'filepath' => $file->get_filepath(), 'filename' => $file->get_filename(),
                'userid' => $file->get_userid(), 'source' => $file->get_source(), 'author' => $file->get_author(),
                'license' => $file->get_license(),
            ], $file);
        }
    }

    /**
     * Return the hero background areas referenced by the document.
     *
     * @param int $revisionid Revision ID.
     * @return array
     */
    private static function hero_background_areas(int $revisionid): array {
        global $DB;
        $like = $DB->sql_like('filearea', ':prefix', false, false);
        return $DB->get_fieldset_sql(
            "SELECT DISTINCT filearea
               FROM {files}
              WHERE component = :component AND itemid = :itemid AND $like",
            [
            'component' => self::COMPONENT,
            'itemid' => $revisionid,
            'prefix' => self::AREA_HERO_BACKGROUND_PREFIX . '%',
            ]
        );
    }

    /**
     * Resolve the native context owning the revision files.
     *
     * @param int $revisionid Revision ID.
     * @return int
     */
    public static function contextid_for_revision(int $revisionid): int {
        global $DB;
        $sql = 'SELECT p.contextid
                  FROM {local_frontpage_revision} r
                  JOIN {local_frontpage_page} p ON p.id = r.pageid
                 WHERE r.id = :revisionid';
        $contextid = $DB->get_field_sql($sql, ['revisionid' => $revisionid], MUST_EXIST);
        return (int) $contextid;
    }


    /**
     * Copy segment files using the mapping produced by document duplication.
     *
     * @param int $revisionid Revision ID.
     * @param array $map Map.
     * @return void
     */
    public static function duplicate_segments(int $revisionid, array $map): void {
        $contextid = self::contextid_for_revision($revisionid);
        foreach ($map as $old => $new) {
            self::copy_area(
                self::hero_background_area($old),
                $revisionid,
                $contextid,
                self::hero_background_area($new),
                $revisionid,
                $contextid,
                true
            );
        }
    }
}
