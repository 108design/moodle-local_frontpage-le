<?php
/**
 * @package local_frontpage
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license 108design source-available software license; see LICENSE.md
 */
namespace local_frontpage\form;
defined('MOODLE_INTERNAL') || die();
final class page_css_form extends \moodleform {
    protected function definition(): void {
        $f=$this->_form;
        foreach(['pageid','baserevisionid','baseversion'] as $key){$f->addElement('hidden',$key);$f->setType($key,PARAM_INT);}
        $f->addElement('static','pagecssnotice','',get_string('pagecssnotice','local_frontpage'));
        $f->addElement('textarea','pagecss',get_string('pagecss','local_frontpage'),['rows'=>24,'spellcheck'=>'false','wrap'=>'off']);
        $f->setType('pagecss',PARAM_RAW);
        if(!\local_frontpage\output\context_help::field($f,'pagecss','pagecss','pagecss')){$f->addHelpButton('pagecss','pagecss','local_frontpage');}
        $this->add_action_buttons(true,get_string('savedraft','local_frontpage'));
    }
    public function validation($data,$files): array {
        $errors=parent::validation($data,$files);
        if(strlen((string)($data['pagecss']??''))>\local_frontpage\local\document\validator::MAX_PAGE_CSS_LENGTH){
            $errors['pagecss']=get_string('pagecsstoolarge','local_frontpage');
        }
        return $errors;
    }
}
