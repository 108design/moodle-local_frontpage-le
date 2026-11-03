<?php
/**
 * @package local_frontpage
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license 108design source-available software license; see LICENSE.md
 */
namespace local_frontpage\local;
final class revision_files {
    public const COMPONENT = 'local_frontpage';
    public const INLINE_IMAGE_TYPES = ['.jpg', '.jpeg', '.png', '.gif', '.webp'];
    public const AREA_EDITOR = 'editor';
    public const AREA_PAGE_ASSETS = 'pageassets';
    public const AREA_HERO_BACKGROUND_PREFIX = 'herobg_';
    public const AREAS = [self::AREA_EDITOR, self::AREA_PAGE_ASSETS];
    public static function accepted_image_types(array $types = self::INLINE_IMAGE_TYPES,
            bool $webimagesonly = false): array {
        $registered = get_mimetypes_array();
        $expected = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png',
            'gif' => 'image/gif', 'webp' => 'image/webp', 'svg' => 'image/svg+xml'];
        return array_values(array_filter($types, static function(string $extension) use ($registered, $expected,
                $webimagesonly): bool {
            $key = ltrim($extension, '.');
            $mime = $expected[$key] ?? null;
            return $mime !== null && ($registered[$key]['type'] ?? null) === $mime
                && (!$webimagesonly || in_array('web_image', $registered[$key]['groups'] ?? [], true));
        }));
    }

    public static function areas_for_page(int $pageid): array {
        global $DB;
        $areas = $DB->get_fieldset_sql(
            'SELECT DISTINCT f.filearea FROM {files} f
               JOIN {local_frontpage_revision} r ON r.id = f.itemid
               JOIN {local_frontpage_page} p ON p.id = r.pageid AND p.contextid = f.contextid
              WHERE p.id = :pageid AND f.component = :component',
            ['pageid' => $pageid, 'component' => self::COMPONENT]);
        return array_values(array_unique(array_merge(self::AREAS,
            array_filter($areas, [self::class, 'is_hero_background_area']))));
    }

    public static function hero_background_area(string $segmentid): string {
        return self::AREA_HERO_BACKGROUND_PREFIX . sha1($segmentid);
    }

    public static function is_hero_background_area(string $filearea): bool {
        return (bool) preg_match('/^' . self::AREA_HERO_BACKGROUND_PREFIX . '[a-f0-9]{40}$/', $filearea);
    }

    public static function copy_revision(int $sourceid, int $targetid): void {
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
        foreach (self::hero_background_areas($sourceid) as $filearea) {
            self::copy_area($filearea, $sourceid, $sourcecontextid, $filearea, $targetid, $targetcontextid, true);
        }
    }

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

    public static function same_content(int $firstrevisionid, int $secondrevisionid): bool {
        return self::content_fingerprint($firstrevisionid) === self::content_fingerprint($secondrevisionid);
    }

    private static function content_fingerprint(int $revisionid): array {
        $fs = get_file_storage();
        $contextid = self::contextid_for_revision($revisionid);
        $areas = array_merge(self::AREAS, self::hero_background_areas($revisionid));
        $fingerprint = [];
        foreach ($areas as $filearea) {
            foreach ($fs->get_area_files(
                $contextid,
                self::COMPONENT,
                $filearea,
                $revisionid,
                'filepath ASC, filename ASC, contenthash ASC',
                false
            ) as $file) {
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

    public static function get_hero_background(int $revisionid, string $segmentid): ?\stored_file {
        $files = get_file_storage()->get_area_files(self::contextid_for_revision($revisionid), self::COMPONENT,
            self::hero_background_area($segmentid), $revisionid, 'id ASC', false);
        return $files ? reset($files) : null;
    }

    private static function copy_area(string $sourcearea, int $sourceid, int $sourcecontextid,
            string $targetarea, int $targetid, int $targetcontextid, bool $cleartarget): void {
        $fs = get_file_storage();
        if ($cleartarget) {
            $fs->delete_area_files($targetcontextid, self::COMPONENT, $targetarea, $targetid);
        }
        foreach ($fs->get_area_files($sourcecontextid, self::COMPONENT, $sourcearea, $sourceid, 'id ASC', false) as $file) {
            $existing = $fs->get_file($targetcontextid, self::COMPONENT, $targetarea, $targetid,
                $file->get_filepath(), $file->get_filename());
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

    private static function hero_background_areas(int $revisionid): array {
        global $DB;
        $like = $DB->sql_like('filearea', ':prefix', false, false);
        return $DB->get_fieldset_sql(
            "SELECT DISTINCT filearea
               FROM {files}
              WHERE component = :component AND itemid = :itemid AND $like",
            ['component' => self::COMPONENT, 'itemid' => $revisionid, 'prefix' => self::AREA_HERO_BACKGROUND_PREFIX . '%']
        );
    }

    public static function contextid_for_revision(int $revisionid): int {
        global $DB;
        $sql = 'SELECT p.contextid
                  FROM {local_frontpage_revision} r
                  JOIN {local_frontpage_page} p ON p.id = r.pageid
                 WHERE r.id = :revisionid';
        $contextid = $DB->get_field_sql($sql, ['revisionid' => $revisionid], MUST_EXIST);
        return (int) $contextid;
    }


    public static function duplicate_segments(int $revisionid,array $map): void {
        $contextid=self::contextid_for_revision($revisionid);
        foreach($map as $old=>$new){self::copy_area(self::hero_background_area($old),$revisionid,$contextid,
            self::hero_background_area($new),$revisionid,$contextid,true);}
    }
}
