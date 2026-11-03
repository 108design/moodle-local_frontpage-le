<?php
/**
 * @package local_frontpage
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license 108design source-available software license; see LICENSE.md
 */
namespace local_frontpage;
final class activity_return_test extends \PHPUnit\Framework\TestCase {
    public function test_two_open_forms_do_not_overwrite_each_other(): void {
        global $SESSION,$USER,$SCRIPT;
        $oldsession=$SESSION;$olduser=$USER;$oldpost=$_POST;$oldscript=$SCRIPT;
        try{
            $SESSION=new \stdClass();$USER=(object)['id'=>123];$SCRIPT='/course/modedit.php';
            $base=['mode'=>'create','pageid'=>4,'courseid'=>7,'sectionid'=>8,'modname'=>'page'];
            $first=\local_frontpage\local\activity_return::begin($base);
            $second=\local_frontpage\local\activity_return::begin($base+['segmentid'=>'second']);
            $_POST['frontpagereturntoken']=$first;
            self::assertTrue(\local_frontpage\local\activity_return::record_created(21,7,8,123));
            self::assertSame(21,\local_frontpage\local\activity_return::pending($first)['createdcmid']);
            self::assertSame(0,\local_frontpage\local\activity_return::pending($second)['createdcmid']);
            \local_frontpage\local\activity_return::consume($first);
            self::assertNull(\local_frontpage\local\activity_return::pending($first));
            self::assertNotNull(\local_frontpage\local\activity_return::pending($second));
        } finally {$SESSION=$oldsession;$USER=$olduser;$_POST=$oldpost;$SCRIPT=$oldscript;}
    }
    public function test_native_return_requires_the_correct_module_path_and_course(): void {
        $ticket=['modname'=>'page','courseid'=>7];
        self::assertTrue(\local_frontpage\local\activity_return::matches_moodle_return('/mod/page/view.php',7,$ticket));
        self::assertFalse(\local_frontpage\local\activity_return::matches_moodle_return('/course/view.php',7,$ticket));
        self::assertFalse(\local_frontpage\local\activity_return::matches_moodle_return('/mod/page/view.php',8,$ticket));
    }
}
