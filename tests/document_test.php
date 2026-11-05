<?php
/**
 * Regression tests for document.
 *
 * @package local_frontpage
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license 108design source-available software license; see LICENSE.md
 */

namespace local_frontpage;

use local_frontpage\local\document\validator;
use local_frontpage\local\document\mutator;

/**
 * Regression tests for document.
 */
final class document_test extends \PHPUnit\Framework\TestCase {
    /**
     * Build the document used by the test.
     *
     * @param array $segments Ordered page segments.
     * @return string
     */
    private function document(array $segments): string {
        return json_encode(
            [ 'schemaVersion' => 2, 'aboveHeaderSegment' => null, 'segments' => $segments ],
            JSON_THROW_ON_ERROR
        );
    }
    /**
     * Verify that owned image uses the stable document marker.
     *
     * @return void
     */
    public function test_owned_image_uses_the_stable_document_marker(): void {
        self::assertSame('{{segment-image}}', \local_frontpage\local\media_files::ASSET);
    }
    /**
     * Verify that heading and custom layout remain typed.
     *
     * @return void
     */
    public function test_heading_and_custom_layout_remain_typed(): void {
        $json = (new validator())->validate_and_normalise($this->document([['id' => 'title', 'type' => 'heading',
            'props' => ['text' => 'A title', 'level' => 2, 'layout' => ['width' => 'custom', 'customwidth' => '50rem',
                'background' => 'custom',
         'backgroundvalue' => '#ffeedd',
         'padding' => 'custom',
         'paddingvalue' => '2rem']]]]));
        $doc = json_decode($json, true);
        self::assertSame('title', $doc['segments'][0]['id']);
        self::assertSame('2rem', $doc['segments'][0]['props']['layout']['paddingvalue']);
    }
    /**
     * Verify that unsupported type is rejected.
     *
     * @return void
     */
    public function test_unsupported_type_is_rejected(): void {
        $this->expectException(\invalid_parameter_exception::class);
        (
        new validator()
        )->validate_and_normalise(
            $this->document(
                [ [ 'id' => 'unavailable', 'type' => 'raw', 'props' => [ 'content' => 'x' ] ] ]
            )
        );
    }
    /**
     * Verify that page script selection is rejected.
     *
     * @return void
     */
    public function test_page_script_selection_is_rejected(): void {
        $this->expectException(\invalid_parameter_exception::class);
        (new validator())->validate_and_normalise('{"schemaVersion":2,"segments":[],"scripts":[4]}');
    }
    /**
     * Verify that script URL in hero is rejected.
     *
     * @return void
     */
    public function test_script_url_in_hero_is_rejected(): void {
        $this->expectException(\invalid_parameter_exception::class);
        (new validator())->validate_and_normalise($this->document([['id' => 'hero', 'type' => 'hero', 'props' => [
            'heading' => 'Welcome', 'primarylabel' => 'Go', 'primaryurl' => 'javascript:alert(1)']]]));
    }
    /**
     * Verify that image requires alt or explicit decoration.
     *
     * @return void
     */
    public function test_image_requires_alt_or_explicit_decoration(): void {
        $this->expectException(\invalid_parameter_exception::class);
        (new validator())->validate_and_normalise($this->document([['id' => 'image', 'type' => 'media', 'props' => [
            'asset' => \local_frontpage\local\media_files::ASSET]]]));
    }
    /**
     * Verify that grid cannot nest.
     *
     * @return void
     */
    public function test_grid_cannot_nest(): void {
        $this->expectException(\invalid_parameter_exception::class);
        (new validator())->validate_and_normalise($this->document([
            ['id' => 'one', 'type' => 'grid', 'props' => ['segmentids' => ['two']]],
            ['id' => 'two', 'type' => 'grid', 'props' => []]]));
    }
    /**
     * Verify that one child cannot be consumed twice.
     *
     * @return void
     */
    public function test_one_child_cannot_be_consumed_twice(): void {
        $this->expectException(\invalid_parameter_exception::class);
        (new validator())->validate_and_normalise($this->document([
            ['id' => 'one', 'type' => 'grid', 'props' => ['segmentids' => ['text']]],
            ['id' => 'two', 'type' => 'grid', 'props' => ['segmentids' => ['text']]],
            ['id' => 'text', 'type' => 'rich_text', 'props' => ['content' => 'A']]]));
    }
    /**
     * Verify that removing grid releases children without deleting them.
     *
     * @return void
     */
    public function test_removing_grid_releases_children_without_deleting_them(): void {
        $json = (new validator())->validate_and_normalise($this->document([
            ['id' => 'grid', 'type' => 'grid', 'props' => ['segmentids' => ['text']]],
            ['id' => 'text', 'type' => 'rich_text', 'props' => ['content' => 'A']]]));
        $result = mutator::apply($json, 'grid', 'remove');
        $doc = json_decode($result['documentjson'], true);
        self::assertSame(['text'], array_column($doc['segments'], 'id'));
    }
    /**
     * Verify that duplicating grid clones children and returns file mapping.
     *
     * @return void
     */
    public function test_duplicating_grid_clones_children_and_returns_file_mapping(): void {
        $json = (new validator())->validate_and_normalise($this->document([
            ['id' => 'grid', 'type' => 'grid', 'props' => ['segmentids' => ['text']]],
            ['id' => 'text', 'type' => 'rich_text', 'props' => ['content' => 'A']]]));
        $result = mutator::apply($json, 'grid', 'duplicate');
        $doc = json_decode($result['documentjson'], true);
        self::assertCount(4, $doc['segments']);
        self::assertCount(2, $result['filemap']);
        self::assertNotSame('text', $doc['segments'][1]['props']['segmentids'][0]);
    }
    /**
     * Verify that removing reference only changes document.
     *
     * @return void
     */
    public function test_removing_reference_only_changes_document(): void {
        $json = $this->document([['id' => 'activity', 'type' => 'course_module', 'props' => ['cmid' => 42]]]);
        $result = mutator::apply($json, 'activity', 'remove');
        self::assertSame([], json_decode($result['documentjson'], true)['segments']);
    }
}
