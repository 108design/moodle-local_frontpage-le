<?php
/**
 * @package local_frontpage
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license 108design source-available software license; see LICENSE.md
 */
namespace local_frontpage\form;
final class segment_form extends \moodleform {
    public static function fields(string $type): array {
        $spec=json_decode(file_get_contents(__DIR__.'/segment_fields.json'),true,512,JSON_THROW_ON_ERROR);
        if (!isset($spec['types'][$type])) {throw new \invalid_parameter_exception('Unsupported segment type.');}
        return $spec['types'][$type];
    }
    public static function layout_fields(): array {
        return json_decode(file_get_contents(__DIR__.'/segment_fields.json'),true,512,JSON_THROW_ON_ERROR)['layout'];
    }
    protected function definition(): void {
        $f=$this->_form; $data=$this->_customdata;
        foreach (['pageid','baserevisionid','baseversion'] as $name) {$f->addElement('hidden',$name);$f->setType($name,PARAM_INT);}
        foreach (['segmentid','segmenttype'] as $name) {$f->addElement('hidden',$name);$f->setType($name,PARAM_ALPHANUMEXT);}
        $f->addElement('text','editorlabel',get_string('segmenteditorlabel','local_frontpage'));
        $f->setType('editorlabel',PARAM_TEXT);$f->addHelpButton('editorlabel','segmenteditorlabel','local_frontpage');
        $f->addElement('header','contentheader',get_string('segmentcontent','local_frontpage'));
        $groups=[];
        foreach(self::fields($data['type']) as $key=>$spec){
            if($spec[0]==='owned'){continue;}
            $group=in_array($key,['heading','text','content','eyebrow','mark','markalt','primarylabel','primaryurl','secondarylabel','secondaryurl','cmid','categoryid','limit','enrolledonly'],true)?'content':
                (in_array($key,['alt','decorative','caption','asset','backgroundsize','backgroundsizevalue','backgroundposition','backgroundpositionx','backgroundpositiony','fit','fitvalue','focal','focalx','focaly','aspect','aspectvalue','loading'],true)?'media':'display');
            $groups[$group][$key]=$spec;
        }
        foreach($groups as $group=>$fields){
            if($group!=='content'){$f->addElement('header','type_'.$group,get_string('editorgroup_'.$group,'local_frontpage'));}
            foreach($fields as $key=>$spec){$this->field($key,$spec);}
        }
        if (in_array($data['type'],['hero','media'],true)) {
            $f->addElement('filemanager','image',get_string('segmentimage','local_frontpage'),null,
                ['maxfiles'=>1,'subdirs'=>0,'accepted_types'=>\local_frontpage\local\revision_files::INLINE_IMAGE_TYPES]);
            $f->addHelpButton('image','segmentimage','local_frontpage');
        }
        if ($data['type']==='grid') {
            $choices=\local_frontpage\local\grid_structure::choices($data['document'],$data['segmentid']);
            for($i=0;$i<4;$i++) {
                $f->addElement('header','gridcolumnheader'.$i,get_string('gridcolumn','local_frontpage',$i+1));
                $f->addElement('select','slot'.$i,get_string('gridcolumn','local_frontpage',$i+1),$choices);
                $f->setType('slot'.$i,PARAM_ALPHANUMEXT);
                $f->addElement('text','weight'.$i,get_string('gridcolumnwidth','local_frontpage',$i+1),['size'=>3]);
                $f->setType('weight'.$i,PARAM_INT);$f->setDefault('weight'.$i,3);
                $f->addElement('advcheckbox','mobilehidden'.$i,get_string('gridmobilehidecolumn','local_frontpage',$i+1));
            }
        }
        $f->addElement('header','layoutheader',get_string('segmentappearance','local_frontpage'));
        foreach(self::layout_fields() as $key=>$spec){
            if(in_array($key,['padding','bordercolor'],true)){$f->addElement('header','layoutgroup_'.$key,get_string('editorgroup_'.$key,'local_frontpage'));}
            $this->field('layout_'.$key,$spec);
        }
        \local_frontpage\local\segment_colour_modes::add_form_fields($f);
        foreach(self::fields($data['type']) as $key=>$spec) {
            $dependent=['titlesizevalue'=>'titlesize','colorvalue'=>'color','heightvalue'=>'height',
                'contrastvalue'=>'contrast','backgroundsizevalue'=>'backgroundsize','aspectvalue'=>'aspect',
                'fitvalue'=>'fit','focalx'=>'focal','focaly'=>'focal','backgroundpositionx'=>'backgroundposition',
                'backgroundpositiony'=>'backgroundposition'];
            if(isset($dependent[$key])&&$f->elementExists($dependent[$key])){$f->hideIf($key,$dependent[$key],'neq','custom');}
        }
        if($data['type']==='grid') {
            for($i=0;$i<4;$i++) {
                foreach(['slot','weight','mobilehidden'] as $prefix){for($count=1;$count<=$i;$count++){$f->hideIf($prefix.$i,'columns','eq',$count);}}
                $f->hideIf('weight'.$i,'template','neq','custom');
            }
            $f->hideIf('heightvalue','heightmode','eq','auto');$f->hideIf('template','columns','eq',1);
            \local_frontpage\output\context_help::field($f,'columns','grid','segmentsettings');
        }
        if($data['type']==='course_module'){\local_frontpage\output\context_help::field($f,'cmid','activities','segmentsettings');}
        foreach(['customwidth'=>'width','backgroundvalue'=>'background','paddingvalue'=>'padding','radiusvalue'=>'radius',
            'spacingvalue'=>'spacing','spacingtopvalue'=>'spacingtop','bordercolorvalue'=>'bordercolor'] as $field=>$mode){
            $f->hideIf('layout_'.$field,'layout_'.$mode,'neq','custom');
        }
        $this->add_action_buttons(true,get_string('savedraft','local_frontpage'));
    }
    private function field(string $key, array $spec): void {
        global $DB;
        $f=$this->_form;[$kind,$default,$label]=$spec;
        if ($kind==='owned') {return;}
        $label=get_string($label,'local_frontpage');
        if ($kind==='editor') {
            $f->addElement('editor',$key,$label,null,['context'=>$this->_customdata['context'],
                'maxfiles'=>-1,'subdirs'=>1,'trusttext'=>false]);$f->setType($key,PARAM_RAW);
        } else if ($kind==='select') {
            $options=[];foreach ($spec[3] as $v) {$options[$v]=is_int($v)?$v:get_string('option_'.$v,'local_frontpage');}
            $f->addElement('select',$key,$label,$options);$f->setType($key,is_int($default)?PARAM_INT:PARAM_ALPHANUMEXT);
        } else if ($kind==='checkbox') {$f->addElement('advcheckbox',$key,$label);$f->setType($key,PARAM_BOOL);
        } else if ($kind==='module') {
            $f->addElement('selectgroups',$key,$label,\local_frontpage\local\course_module_reference::choices(
                $this->_customdata['context'],$this->_customdata['page']));$f->setType($key,PARAM_INT);
        } else if ($kind==='category') {
            $f->addElement('select',$key,$label,\core_course_category::make_categories_list());$f->setType($key,PARAM_INT);
        } else {
            $f->addElement($kind==='textarea'?'textarea':'text',$key,$label,['size'=>50]);
            $f->setType($key,$kind==='int'?PARAM_INT:PARAM_RAW_TRIMMED);
        }
        if ($kind!=='editor') {$f->setDefault($key,$default);}
        if($key==='decorative'){$f->addHelpButton($key,'mediadecorative','local_frontpage');}
        if($key==='aspectvalue'){$f->addHelpButton($key,'mediaaspectvalue','local_frontpage');}
        else if($key==='backgroundsizevalue'){$f->addHelpButton($key,'herobackgroundsizevalue','local_frontpage');}
        else if($kind==='text'&&(str_ends_with($key,'value')||str_ends_with($key,'width')||str_ends_with($key,'thickness'))){
            $f->addHelpButton($key,str_contains($key,'color')||str_contains($key,'contrast')?'colourvalue':'lengthvalue','local_frontpage');
        }
    }
    public static function props(array $data,string $type,array $old=[]): array {
        $props=$old;
        foreach(self::fields($type) as $key=>$spec) {
            [$kind,$default]=$spec;
            $v=$kind==='owned'?$default:($data[$key]??$default);
            $props[$key]=match($kind){'checkbox'=>(bool)$v,'int','module','category'=>(int)$v,
                'select'=>is_int($default)?(int)$v:(string)$v,'editor'=>(string)($v['text']??''),default=>(string)$v};
        }
        $layout=[];
        foreach(self::layout_fields() as $key=>$spec) {
            $v=$data['layout_'.$key]??$spec[1];$layout[$key]=$spec[0]==='checkbox'?(bool)$v:(string)$v;
        }
        if($layout['width']!=='custom'){unset($layout['customwidth']);}
        $modes=\local_frontpage\local\segment_colour_modes::from_form((object)$data);
        if($modes){$layout['colourmodes']=$modes;}
        $props['layout']=$layout;$props['editorlabel']=trim((string)($data['editorlabel']??''));
        if($type==='grid') {
            if($props['columns']===1){$props['template']='equal';}
            $props['segmentids']=[];$props['widths']=[];$props['mobilehidden']=[];
            for($i=0;$i<(int)$props['columns'];$i++) {
                $props['segmentids'][]=(string)($data['slot'.$i]??'');
                $props['widths'][]=(int)($data['weight'.$i]??3);$props['mobilehidden'][]=!empty($data['mobilehidden'.$i]);
            }
        }
        return $props;
    }
    public function validation($data,$files): array {
        $errors=parent::validation($data,$files);
        try {
            $segment=['id'=>$data['segmentid'],'type'=>$this->_customdata['type'],
                'props'=>self::props($data,$this->_customdata['type'],$this->_customdata['oldprops']??[])];
            $doc=$this->_customdata['document'];
            if($segment['type']==='grid'){\local_frontpage\local\grid_structure::claim_segments($doc,$segment['id'],$segment['props']['segmentids']);}
            $found=false;
            foreach($doc['segments'] as &$existing){if($existing['id']===$segment['id']){$existing=$segment;$found=true;}}
            unset($existing);if(!$found){$doc['segments'][]=$segment;}
            (new \local_frontpage\local\document\validator())->validate_and_normalise(json_encode($doc,JSON_THROW_ON_ERROR));
        } catch (\invalid_parameter_exception $e) {$errors['editorlabel']=$e->getMessage();}
        return $errors;
    }
}
