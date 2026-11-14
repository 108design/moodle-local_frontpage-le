<?php
// This file is part of Moodle - https://moodle.org/
/**
 * Frontpage language strings.
 *
 * @package local_frontpage
 * @copyright 2026 Andreas Giesen <andreas@108design.com>
 * @license 108design source-available software license; see LICENSE.md
 */

$string['action_down'] = 'Move Down';
$string['action_duplicate'] = 'Duplicate';
$string['action_remove'] = 'Remove';
$string['action_toggle'] = 'Show / Hide';
$string['action_up'] = 'Move Up';
$string['addsegment'] = 'Add Segment';
$string['assignedpage'] = 'Choose another page or native Site Home in Settings before deleting this page.';
$string['assignments'] = 'Site Home Assignments';
$string['backgroundposition'] = 'Background image position (background-position)';
$string['backgroundpositioninvalid'] = 'Enter percentages between 0 and 100 for both axes.';
$string['backgroundsize'] = 'Background image size (background-size)';
$string['backgroundsizeauto'] = 'Original size (auto)';
$string['backgroundsizecontain'] = 'Fit complete image (contain)';
$string['backgroundsizecover'] = 'Crop to fill (cover)';
$string['choosefrontpageresource'] = 'Choose from Frontpage resources';
$string['cleanupresource'] = 'Review Deletion';
$string['colourvalue'] = 'Custom Colour';
$string['colourvalue_help'] = ('Enter a supported colour value, for example #334455. Select Custom in the '
    . 'corresponding colour field.');
$string['componentpreviewdark'] = 'Dark';
$string['componentpreviewlight'] = 'Light';
$string['confirmattachresource'] = ('Return to the Frontpage draft and add the resource created in Moodle? If you '
    . 'cancelled the activity form, the draft stays as it is.');
$string['confirmdeletepage'] = ('Delete this unassigned page, all its revisions and its files? Referenced '
    . 'Moodle activities and their user data remain in the course resource registry.');
$string['confirmprune'] = ('Delete this retained revision and its files permanently? Its resource '
    . 'references will no longer protect activities. The current draft and '
    . 'publication remain protected.');
$string['confirmpublish'] = 'Publish this draft for the assigned audience?';
$string['confirmremove'] = 'Remove this segment from the draft? Referenced Moodle activities and their user data are retained.';
$string['confirmresourcecleanup'] = ('Permanently delete this Moodle activity, including any attempts, submissions, '
    . 'grades and other activity data? Frontpage has found no retained references '
    . 'for at least 30 days. Check uses outside Frontpage before continuing.');
$string['confirmresourcecreate'] = ('Open Moodle’s activity chooser to create a course resource? A dedicated '
    . 'resource section will be prepared in this course.');
$string['contexthelptopics'] = 'Help Topics';
$string['coursemodule'] = 'Activity or resource';
$string['coursemodulechoicelabel'] = '{$a->type}: {$a->name}';
$string['coursemoduleplaceholderfallback'] = 'Course activity';
$string['coursemoduleplaceholdernotice'] = (
        'Activity placeholder: {$a}. This placeholder can only be assigned after the '
    . 'template has been inserted into a course. Then open the segment settings '
    . 'there to select an existing course activity or create a new one.'
    );
$string['coursemoduleunavailable'] = 'The referenced activity or resource is unavailable in this course context.';
$string['coursemoduleusedescription'] = 'Show the course-page description when available';
$string['coursemodulewrongcontext'] = 'This segment can only be used by a course-owned Frontpage activity.';
$string['createfrontpageresource'] = 'Create Activity or Resource';
$string['createpage'] = 'Create Page';
$string['deletepage'] = 'Delete Page';
$string['deleterevision'] = 'Delete Revision';
$string['draft'] = 'Draft';
$string['draftconflict'] = ('The draft has changed. Reopen the editor before saving. Your changes have not '
    . 'replaced the newer draft.');
$string['draftnotice'] = 'Changes are saved in the draft. Publish when the page is ready for its audience.';
$string['editcontent'] = 'Edit Content';
$string['frontpage:managepages'] = 'Manage Frontpage pages and drafts';
$string['frontpage:publishpages'] = 'Publish Frontpage page versions';
$string['frontpageresourcecreatehelp'] = (
        'Choose a Moodle activity type to open its settings. Frontpage creates it in a hidden course section. '
    . 'By default, learners can open the activity from a link. Save it in Moodle, then confirm to add it to the draft.'
    );
$string['frontpageresourcenotdeletable'] = (
        'This resource is referenced again, is still within its grace period, or is '
    . 'not owned by Frontpage. It was not deleted.'
    );
$string['frontpageresourcessection'] = 'Frontpage resources';
$string['resourcesectiondeletionwarning'] = 'Deleting this section also deletes its activities and their participant data. '
    . 'Links on Frontpage pages may stop working. Remove unused activities through Frontpage Resources.';
$string['frontpageresourcessectionsummary'] = (
        '<p>This protected section contains activities and resources created for '
    . 'course Frontpage cards. Manage their placement from the Frontpage editor.</p>'
    );
$string['gridalignment'] = 'Vertical alignment';
$string['gridcolumn'] = 'Column {$a}';
$string['gridcolumns'] = 'Columns';
$string['gridcolumnwidth'] = 'Column {$a} width (of 12)';
$string['gridemptycolumn'] = 'Empty column';
$string['gridgap'] = 'Column gap';
$string['gridheightmode'] = 'Grid height';
$string['gridheightvalue'] = 'Height value';
$string['gridmediafit'] = 'Fit images and videos within the fixed height';
$string['gridmobilehiddenbadge'] = 'Only on wide screens';
$string['gridmobilehidecolumn'] = 'Hide column {$a} when stacked';
$string['gridmobileorder'] = 'Mobile stacking order';
$string['gridoverflow'] = 'Overflow';
$string['gridsegmentchoiceeditorlabel'] = '{$a->label} — {$a->type}';
$string['gridstackautoheight'] = 'Use content height when columns stack';
$string['gridstackbreakpoint'] = 'Stack columns below';
$string['gridtemplate'] = 'Column proportions';
$string['guestfrontpageid'] = 'Site Home for Guests';
$string['guestfrontpageid_help'] = ('Choose native Site Home or one of up to five Frontpage pages. Only the guest '
    . 'and signed-in assignments can be published and viewed publicly. Changing an '
    . 'assignment makes the previous page private; its draft and revisions are kept.');
$string['headingcolor'] = 'Text colour';
$string['headingcolorvalue'] = 'Heading colour';
$string['headinglevel'] = 'Heading level';
$string['headingtext'] = 'Heading text';
$string['headingtitlesize'] = 'Font size';
$string['headingtitlesizevalue'] = 'Custom heading size';
$string['herobackgroundposition'] = 'Background position';
$string['herobackgroundpositionx'] = 'Horizontal focus (%)';
$string['herobackgroundpositiony'] = 'Vertical focus (%)';
$string['herobackgroundsize'] = 'Background size';
$string['herobackgroundsizeinvalid'] = 'Enter one or two positive sizes with units or auto, e.g. 120% auto.';
$string['herobackgroundsizevalue'] = 'Custom background size';
$string['herobackgroundsizevalue_help'] = (
        'One or two positive sizes with units, e.g. 120% auto, 640px or 100% 100%. The '
    . 'first value sets the width, the second sets the height. The height defaults '
    . 'to auto.'
    );
$string['herocontentinvalid'] = ('Check the product mark and actions: use an HTTPS image URL or image alias, '
    . 'and provide both a label and a valid URL for each action. Actions allow '
    . 'https://, http://, /local-path or #anchor.');
$string['herocontrast'] = 'Text contrast';
$string['herocontrastvalue'] = 'Text colour';
$string['heroeyebrow'] = 'Eyebrow';
$string['heroheading'] = 'Hero heading';
$string['heroheadinglevel'] = 'Heading level';
$string['heroheight'] = 'Height';
$string['heroheightvalue'] = 'Custom height';
$string['herolayout'] = 'Composition';
$string['heromarkasset'] = 'Product mark from page images';
$string['herooverlay'] = 'Readability overlay';
$string['heroprimarylabel'] = 'Primary action label';
$string['heroprimaryurl'] = 'Primary action URL';
$string['herosecondarylabel'] = 'Secondary action label';
$string['herosecondaryurl'] = 'Secondary action URL';
$string['herostyle'] = 'Style';
$string['herotext'] = 'Hero text';
$string['herotexteffect'] = 'Text effect';
$string['herotitlesize'] = 'Heading size';
$string['herotitlesizevalue'] = 'Custom title size';
$string['historyreadonly'] = 'Draft checkpoints are immutable. Restore a checkpoint as a new draft change.';
$string['homepagelimit'] = 'You can create up to five Site Home pages.';
$string['invalidcustomcolor'] = 'Enter a supported CSS colour value.';
$string['lengthvalue'] = 'Custom Size';
$string['lengthvalue_help'] = 'Enter a supported CSS length such as 24px, 2rem or 80%. The selected size mode must be Custom.';
$string['linkexistingactivityadvanced'] = 'Link existing course activity (advanced)';
$string['locktimeout'] = 'The page is busy. Please try again.';
$string['loggedinfrontpageid'] = 'Site Home for Signed-in Users';
$string['loggedinfrontpageid_help'] = ('The same page can serve guests and signed-in users. Disable Replace Site Home '
    . 'to return both groups to native Site Home.');
$string['mediaalt'] = 'Alternative text';
$string['mediaaspect'] = 'Aspect ratio';
$string['mediaaspectinvalid'] = 'Enter a positive aspect ratio, e.g. 21 / 9 or 1.5.';
$string['mediaaspectvalue'] = 'Custom aspect ratio';
$string['mediaaspectvalue_help'] = 'Enter a positive ratio such as 21 / 9 or a decimal such as 1.5.';
$string['mediacaption'] = 'Caption';
$string['mediadecorative'] = 'Decorative image';
$string['mediadecorative_help'] = ('Mark an image as decorative when it adds no information. Decorative images '
    . 'have empty alternative text. Describe meaningful images in Alternative Text.');
$string['mediafit'] = 'Image fit';
$string['mediafitinvalid'] = 'Select a valid image fit.';
$string['mediafitvalue'] = 'Custom image fit';
$string['mediafocal'] = 'Focal position';
$string['mediafocalx'] = 'Horizontal focus (%)';
$string['mediafocaly'] = 'Vertical focus (%)';
$string['mediaimage'] = 'Image';
$string['medialoading'] = 'Loading priority';
$string['missingimage'] = 'Upload the referenced image before publishing. The current publication has been retained.';
$string['moodlecoursecategory'] = 'Course category';
$string['moodlecourselistempty'] = 'No course content is available for this selection.';
$string['moodlecourselistmode'] = 'Course list mode';
$string['nativesitehome'] = 'Native Site Home';
$string['option_auto'] = 'Auto';
$string['option_available'] = 'Available';
$string['option_band'] = 'Band';
$string['option_border'] = 'Border';
$string['option_bottom'] = 'Bottom';
$string['option_bottom_left'] = 'Bottom Left';
$string['option_bottom_right'] = 'Bottom Right';
$string['option_card'] = 'Card';
$string['option_categories'] = 'Categories';
$string['option_category'] = 'Category';
$string['option_center'] = 'Center';
$string['option_clip'] = 'Clip';
$string['option_combo'] = 'Combo';
$string['option_configured'] = 'Configured';
$string['option_contain'] = 'Contain';
$string['option_content'] = 'Content';
$string['option_cover'] = 'Cover';
$string['option_custom'] = 'Custom';
$string['option_dark'] = 'Dark';
$string['option_display'] = 'Display';
$string['option_eager'] = 'Eager';
$string['option_end'] = 'End';
$string['option_enrolled'] = 'Enrolled';
$string['option_equal'] = 'Equal';
$string['option_fill'] = 'Fill';
$string['option_fixed'] = 'Fixed';
$string['option_four_three'] = 'Four Three';
$string['option_full'] = 'Full';
$string['option_glow'] = 'Glow';
$string['option_gradient'] = 'Gradient';
$string['option_inherit'] = 'Inherit';
$string['option_large'] = 'Large';
$string['option_lazy'] = 'Lazy';
$string['option_left'] = 'Left';
$string['option_lg'] = '992 px';
$string['option_light'] = 'Light';
$string['option_md'] = '768 px';
$string['option_medium'] = 'Medium';
$string['option_min'] = 'Min';
$string['option_natural'] = 'Natural';
$string['option_none'] = 'None';
$string['option_normal'] = 'Normal';
$string['option_overlay'] = 'Overlay';
$string['option_plain'] = 'Plain';
$string['option_portrait'] = 'Portrait';
$string['option_primary'] = 'Primary';
$string['option_reverse'] = 'Reverse';
$string['option_right'] = 'Right';
$string['option_scale-down'] = 'Scale Down';
$string['option_search'] = 'Search';
$string['option_secondary'] = 'Secondary';
$string['option_shadow'] = 'Shadow';
$string['option_sixteen_nine'] = 'Sixteen Nine';
$string['option_sm'] = '576 px';
$string['option_small'] = 'Small';
$string['option_spacious'] = 'Spacious';
$string['option_splitleft'] = 'Splitleft';
$string['option_splitright'] = 'Splitright';
$string['option_spotlight'] = 'Spotlight';
$string['option_square'] = 'Square';
$string['option_start'] = 'Start';
$string['option_stretch'] = 'Stretch';
$string['option_strong'] = 'Strong';
$string['option_subtle'] = 'Subtle';
$string['option_surface'] = 'Surface';
$string['option_theme'] = 'Theme';
$string['option_three_two'] = 'Three Two';
$string['option_top'] = 'Top';
$string['option_top_left'] = 'Top Left';
$string['option_top_right'] = 'Top Right';
$string['option_transparent'] = 'Transparent';
$string['option_viewport'] = 'Viewport';
$string['option_visible'] = 'Visible';
$string['option_white'] = 'White';
$string['option_wide'] = 'Wide';
$string['option_wide_first'] = 'Wide First';
$string['option_wide_last'] = 'Wide Last';
$string['option_xl'] = '1200 px';
$string['pagecontentwidth'] = 'Content Width';
$string['pagecontentwidthvalue'] = 'Custom Content Width';
$string['pagefiles'] = 'Page Files';
$string['pagefiles_help'] = ('Upload files used by this page. Earlier revisions keep their copies when you '
    . 'remove a file from the draft.');
$string['pagehelp_activities'] = 'Moodle Activities';
$string['pagehelp_activities_article'] = ('<p>In the segment picker, choose Course activity or resource. '
    . 'Select an existing activity, or choose Create Activity or Resource to open Moodle’s activity chooser. Site Home '
    . 'uses its own course. After saving in Moodle, confirm the return to add a '
    . 'reference to the draft. If the draft changed in the meantime, select the '
    . 'resource from the existing-activity list. Removing a reference preserves the '
    . 'activity and user data.</p>');
$string['pagehelp_activities_help'] = ('Choose Course activity or resource in the segment picker to link or create '
    . 'an activity in this course.');
$string['pagehelp_activitysettings_title'] = 'Frontpage Help';
$string['pagehelp_assignments'] = 'Site Home Assignments';
$string['pagehelp_assignments_article'] = (
        '<p>Native Moodle Site Home must be enabled. Moodle’s selected default home '
    . 'page and forced-login setting still apply. In Settings, choose a page for '
    . 'guests and a page for signed-in users. Both choices can use the same page. '
    . 'Replace Site Home enables the replacement; switching it off restores native '
    . 'Site Home. Unpublished pages fall back to native Site Home. Assign another '
    . 'page or native Site Home before deleting an assigned page.</p>'
    );
$string['pagehelp_assignments_help'] = 'In Settings, choose a page for guests and a page for signed-in users.';
$string['pagehelp_backup'] = 'Backup and Recovery';
$string['pagehelp_backup_article'] = ('<p>Course activities use Moodle activity and course backup, restore, course '
    . 'copy and duplication. References to activities included in the same restore '
    . 'are remapped. References unavailable in the target become placeholders for an '
    . 'editor to resolve. To protect Site Home, back up the complete Moodle '
    . 'database, moodledata and config.php as one consistent site backup, together '
    . 'with the matching plugin code. Restore the database and moodledata together '
    . 'to recover page IDs, audience settings, revisions and files. Activity backup '
    . 'alone does not include Site Home. Keep backups outside the live web directory '
    . 'and test recovery.</p>');
$string['pagehelp_backup_help'] = 'Course activities use Moodle activity and course backup, restore, course copy and duplication.';
$string['pagehelp_completion'] = 'Activity Completion';
$string['pagehelp_completion_article'] = (
        '<p>The optional All Referenced Activities rule uses enabled references in the '
    . 'current publication. Activities without completion tracking are skipped. At '
    . 'least one tracked activity is required. Missing or deleted references keep '
    . 'the rule incomplete. Hidden or temporarily unavailable tracked activities '
    . 'still use their native completion state. Completion with pass or fail counts '
    . 'as completed. Historical previews do not award completion.</p>'
    );
$string['pagehelp_completion_help'] = 'The optional All Referenced Activities rule uses enabled references in the current publication.';
$string['pagehelp_editing'] = 'Editing Segments';
$string['pagehelp_editing_article'] = ('<p>On a published page, use the pencil in Page Actions to open the editor. '
    . 'The pencil on a segment opens its settings in the current draft.</p>'
    . '<p>Choose a segment type in the editor. Edit content in Moodle forms, then '
    . 'use Save Draft. Move, duplicate, hide or remove a segment through its row '
    . 'actions. Grid columns refer to other segments on the same page; deleting a '
    . 'Grid returns its children to the page flow. Stale forms cannot replace a '
    . 'newer draft.</p>');
$string['pagehelp_editing_help'] = 'Choose a segment type in the editor.';
$string['pagehelp_editor_title'] = 'Frontpage Help';
$string['pagehelp_files'] = 'Files and Images';
$string['pagehelp_files_article'] = ('<p>Images and editor attachments belong to a revision and its page context. '
    . 'Page Files stores additional files for the page. Earlier revisions retain '
    . 'their files. A draft preview requires editing rights; course files also '
    . 'require native course and activity access. Use a static JPG, PNG, GIF or WebP '
    . 'image for Media and Hero.</p>');
$string['pagehelp_files_help'] = 'Images and editor attachments belong to a revision and its page context.';
$string['pagehelp_grid'] = 'Grid Layout';
$string['pagehelp_grid_article'] = ('<p>Use one to four columns with segment references from this page. A segment '
    . 'can belong to one Grid. Grids cannot nest. Set column proportions, gap and a '
    . 'stacking breakpoint; hidden columns apply at that breakpoint. Custom '
    . 'proportions must total 12. Reducing the column count releases removed '
    . 'references to normal page flow.</p>');
$string['pagehelp_grid_help'] = 'Use one to four columns with segment references from this page.';
$string['pagehelp_history'] = 'Revision History';
$string['pagehelp_history_article'] = ('<p>Revision History shows the current draft and retained earlier drafts and '
    . 'publications. Preview is read-only. Restore as Draft copies the selected '
    . 'revision and its files into a new draft; the publication remains unchanged '
    . 'until you publish. Revisions remain retained until explicitly removed.</p>');
$string['pagehelp_history_help'] = 'Revision History shows the current draft and retained earlier drafts and publications.';
$string['pagehelp_more'] = 'More in the help panel';
$string['pagehelp_pagefiles_title'] = 'Frontpage Help';
$string['pagehelp_pageoptions'] = 'Page Options';
$string['pagehelp_pageoptions_article'] = ('<p>Page Options changes the page title and width within the native Moodle '
    . 'frame. These metadata settings apply immediately. Content changes remain in '
    . 'the draft until you publish.</p>');
$string['pagehelp_pageoptions_help'] = 'Title and content width apply immediately.';
$string['pagehelp_pagesettings_title'] = 'Page Options Help';
$string['pagehelp_pluginsettings_title'] = 'Frontpage Help';
$string['pagehelp_publication'] = 'Draft and Publication';
$string['pagehelp_publication_article'] = ('<p>Save Draft keeps changes for authorised editors. Publish makes that '
    . 'revision visible to the page’s audience. Later edits do not change the '
    . 'publication. Publishing requires the publication permission for the page’s '
    . 'context.</p>');
$string['pagehelp_publication_help'] = 'Save Draft keeps changes for authorised editors.';
$string['pagehelp_resources'] = 'Resource Cleanup';
$string['pagehelp_resources_article'] = ('<p>Open Frontpage Resources in the course navigation, or from the page '
    . 'editor. The course route remains available after the source page is deleted. '
    . 'The registry distinguishes Frontpage-created resources from existing '
    . 'activities. All retained revisions protect references, including hidden '
    . 'segments. Unreferenced resources become deletion candidates after 30 days. '
    . 'Deletion requires a separate confirmation and removes the native activity and '
    . 'its user data. Check links and uses outside Frontpage yourself.</p>');
$string['pagehelp_resources_help'] = 'Open Frontpage Resources in the course navigation, or from the page editor.';
$string['pagehelp_resources_title'] = 'Frontpage Help';
$string['pagehelp_revisionhistory_title'] = 'Frontpage Help';
$string['pagehelp_segmentsettings_title'] = 'Frontpage Help';
$string['pagehelp_title'] = 'Frontpage Help';
$string['pageoptions'] = 'Page settings';
$string['pages'] = 'Pages';
$string['pluginname'] = 'Frontpage';
$string['previewdraft'] = 'Preview Draft';
$string['previewnotice'] = 'This is an authorised preview. Visitors continue to see the current publication.';
$string['privacy:author'] = 'User attributed to the shared page, revision or resource.';
$string['privacy:content'] = 'Shared page content retained after attribution is anonymised.';
$string['privacy:files'] = 'Revision-owned Moodle files.';
$string['privacy:pages'] = 'Page metadata and authorship.';
$string['privacy:resources'] = 'Ownership of course resources.';
$string['privacy:revisions'] = 'Revisioned shared content and publication attribution.';
$string['publish'] = 'Publish';
$string['published'] = 'Published';
$string['resourcegrace'] = 'Waiting for the 30-day grace period';
$string['resourceintro'] = ('This registry contains resources created through Frontpage in this course. '
    . 'Retained drafts and publications protect their references. Review other uses '
    . 'and possible user data before deleting a resource.');
$string['resourceprotected'] = 'Protected by retained revisions';
$string['resourceretained'] = ('The resource remains in Frontpage Resources. Reopen the draft and select the '
    . 'existing resource to add it.');
$string['resources'] = 'Frontpage Resources';
$string['restore'] = 'Restore as Draft';
$string['retainedreferences'] = 'Retained References';
$string['revision'] = 'Revision';
$string['revisions'] = 'Versions';
$string['savedraft'] = 'Save draft';
$string['segmentalignment'] = 'Text alignment';
$string['segmentappearance'] = 'Presentation style';
$string['segmentbackground'] = 'Background';
$string['segmentbackgroundvalue'] = 'Background colour';
$string['segmentborderbottom'] = 'Show bottom border';
$string['segmentbordercolor'] = 'Border colour';
$string['segmentbordercolorvalue'] = 'Border colour';
$string['segmentborderleft'] = 'Show left border';
$string['segmentborderright'] = 'Show right border';
$string['segmentborderthickness'] = 'Border thickness (border-width)';
$string['segmentbordertop'] = 'Show top border';
$string['segmentcolourbackground'] = 'Background';
$string['segmentcolourborder'] = 'Border';
$string['segmentcoloureditmode'] = 'Edit colour variant';
$string['segmentcolourhelp'] = ('Empty fields keep the existing design. Overrides apply only in their mode to '
    . 'the segment surface, inherited text and existing borders. Explicit formatting '
    . 'in content can take precedence. Content and layout remain shared.');
$string['segmentcolourinherit'] = 'Inherit from design';
$string['segmentcolourown'] = 'Custom light/dark values';
$string['segmentcoloursource'] = 'Colour styling';
$string['segmentcolourtext'] = 'Text';
$string['segmentcontent'] = 'Content';
$string['segmentcustomwidth'] = 'Custom width';
$string['segmenteditorlabel'] = 'Editor label';
$string['segmentimage'] = 'Image';
$string['segmentimage_help'] = 'Upload one static image. Files belong to this revision and are copied when you save or publish.';
$string['segmentpadding'] = 'Inner spacing';
$string['segmentpaddingvalue'] = 'Custom padding';
$string['segmentradius'] = 'Corner radius';
$string['segmentradiusvalue'] = 'Custom corner radius';
$string['segmentspacing'] = 'Space after segment';
$string['segmentspacingtop'] = 'Space before segment';
$string['segmentspacingtopvalue'] = 'Custom space above';
$string['segmentspacingvalue'] = 'Custom space below';
$string['segmenttextcolor'] = 'Text colour';
$string['segmenttypecoursemodule'] = 'Course activity or resource';
$string['segmenttypecoursenavigation'] = 'Course navigation';
$string['segmenttypegrid'] = 'Grid';
$string['segmenttypeheading'] = 'Heading';
$string['segmenttypehero'] = 'Hero';
$string['segmenttypemedia'] = 'Media';
$string['segmenttypemoodlecourselist'] = 'Course overviews';
$string['segmenttyperichtext'] = 'Moodle Editor';
$string['segmenttypespacer'] = 'Spacer';
$string['segmentwidth'] = 'Segment width';
$string['sitehomeenabled'] = 'Replace Site Home';
$string['sourcepage'] = 'Source Page';
$string['sourceremoved'] = 'Source page removed';
$string['spacercolor'] = 'Custom spacer colour';
$string['spacerheight'] = 'Spacer height';
$string['title'] = 'Title';
$string['unassigned'] = 'Unassigned';
$string['valuecustom'] = 'Custom …';

$string['chooser_heading'] = 'A clear heading for a new section.';
$string['chooser_rich_text'] = 'Text, links and images with the Moodle editor.';
$string['chooser_media'] = 'An image with caption and display settings.';
$string['chooser_hero'] = 'A prominent introduction with image and links.';
$string['chooser_spacer'] = 'Space between sections.';
$string['chooser_grid'] = 'Arrange existing segments in up to four columns.';
$string['chooser_moodle_course_list'] = 'Show Moodle courses from a category or enrolments.';
$string['chooser_course_module'] = 'Reference an activity or resource in this course.';
$string['editorhidden'] = 'Hidden';

$string['publishassignmentrequired'] = 'Assign this page as Site Home before publishing.';

$string['editorgroup_media'] = 'Image and media';
$string['editorgroup_display'] = 'Display options';
$string['editorgroup_padding'] = 'Spacing';
$string['editorgroup_bordercolor'] = 'Borders';

$string['pagecss'] = 'Page CSS';
$string['pagecssnotice'] = ('CSS is saved in the page draft. Preview it, then publish to apply it to the '
    . 'public page. The authoring forms do not load it.');
$string['pagecss_help'] = ('Write CSS for this page only. The styles can affect the whole displayed page, '
    . 'including Moodle navigation. They are stored with its content revisions. The '
    . 'maximum is 100,000 bytes.');
$string['pagecsstoolarge'] = 'Page CSS must not exceed 100,000 bytes.';
$string['frontpage:editcss'] = 'Edit page CSS';
$string['pagehelp_pagecss'] = 'Page CSS';
$string['pagehelp_pagecss_help'] = 'Edit this page stylesheet in the draft, preview it and publish it with the page.';
$string['pagehelp_pagecss_title'] = 'Page CSS Help';
$string['pagehelp_pagecss_article'] = ('<p>Page CSS belongs to the same draft and published revisions as the page '
    . 'content. Saving CSS creates a new draft; visitors keep seeing the published '
    . 'CSS until you publish.</p><p>The stylesheet applies to this page view, '
    . 'including its Moodle navigation and controls. Start with a selector for the '
    . 'content you want to change. It is not loaded in the authoring forms, so you '
    . 'can return here if a style makes the page difficult to use.</p><p>Page files '
    . 'can be referenced with <code>{{image-url:filename}}</code>. Restoring an '
    . 'earlier revision restores its CSS too. Changing CSS, including through a '
    . 'revision restore, requires the separate page CSS permission. A publisher can '
    . 'publish an unchanged CSS draft without receiving that permission.</p>');

$string['activitysettings'] = 'Activity settings';

$string['pagecsspermissionneeded'] = 'Changing page CSS requires the page CSS permission.';

$string['restorerevisionlabel'] = 'Restore revision {$a}';

$string['currentdraft'] = 'Current draft';

$string['currentpublication'] = 'Published now';

$string['previouspublication'] = 'Earlier publication';

$string['draftcheckpoint'] = 'Draft checkpoint';

$string['activitynavigationreturn'] = 'Back to {$a}';
$string['activitynavigation'] = 'Activity navigation';
$string['activitymetadatacompletion'] = 'Moodle completion';
$string['activitymetadatacompletionsingle'] = 'Moodle completion · {$a} requirement';
$string['activitymetadatacompletionmultiple'] = 'Moodle completion · {$a} requirements';
$string['activitymetadatacompletionnote'] = 'Moodle shows these conditions as activity information.';
$string['pagesettings'] = 'Page settings';
$string['draftstatus'] = 'Draft';
$string['publishandview'] = 'Publish and view';
$string['currentpublishedversion'] = 'View current publication';
$string['activitynavigationinherit'] = 'Inherit course setting';
$string['activitynavigationmode'] = 'Show linear navigation';
$string['activitynavigationmode_help'] = ('Adds Moodle-style Previous and Next actions to activities opened from this '
    . 'standalone Frontpage activity. On and Off override the course setting. '
    . 'Inherit is available only with Moodle 5.3 and a course format that supports '
    . 'native linear navigation. Older versions and other formats offer On and Off. '
    . 'The change takes effect when this draft is published. Breadcrumbs and the '
    . 'return to the Frontpage activity remain available.');
$string['activitynavigationboundary'] = 'At the ends of the Frontpage sequence';
$string['activitynavigationboundaryfrontpage'] = 'Return to the Frontpage overview';
$string['activitynavigationboundarycourse'] = 'Continue in the course';
$string['activitynavigationboundary_help'] = (
        'The sequence follows the accessible activities in the published Frontpage '
    . 'layout, including Grids. Return to the Frontpage overview closes the sequence '
    . 'at both ends. Continue in the course uses the course position of this '
    . 'Frontpage activity to find the preceding or following course content. At the '
    . 'course boundary it returns to the course. Hidden, unavailable and managed '
    . 'resource activities are skipped.'
    );
$string['activitynavigationpreviousoverview'] = 'Start of Frontpage sequence · Overview';
$string['activitynavigationnextoverview'] = 'End of Frontpage sequence · Overview';
$string['activitynavigationpreviouscourse'] = 'Back in the course';
$string['activitynavigationnextcourse'] = 'Continue in the course';

$string['pagehelp_activitynavigation_title'] = 'Activity navigation';
$string['pagehelp_activitynavigation'] = 'Activity navigation';
$string['pagehelp_activitynavigation_help'] = 'Follow the published activity order and return to its Frontpage card.';
$string['pagehelp_activitynavigation_article'] = (
        '<p>Open Activity navigation in the course activity editor. Save the draft, '
    . 'then publish it to apply the settings. Previous and Next follow accessible '
    . 'activities in the published page and its Grids. Disabled segments and '
    . 'repeated references do not add steps.</p><p>On and Off override the course '
    . 'setting. Moodle 5.3 formats with native linear navigation also offer Inherit. '
    . 'Older versions show a compatible footer; completion remains in the native '
    . 'activity header. Activities with their own footer keep it.</p><p>At either '
    . 'end, return to the Frontpage overview or continue from the original Frontpage '
    . 'activity position in the course. The technical resource section is skipped. '
    . 'The additional return link opens the original card. Ordinary course links '
    . 'leave this sequence. Draft and historical previews do not start a published '
    . 'sequence.</p>'
    );

$string['activitynavigationbacktocourse'] = 'Back to course';

// Shared independent signed update protection.
$string['updateguard_heading'] = 'Update stopped';
$string['updateguard_component'] = 'The package for {$a} was rejected.';
$string['unsupportedupgrade'] = 'This package cannot update the installed version. Restore the previous plugin files.';
$string['updateguard_hint'] = 'Use an approved package for the installed edition. You can review the operation again.';
$string['updateguard_unchanged'] = 'This request did not replace plugin files.';
$string['updateguard_help'] = 'The installed plugin checks the publisher signature, package contents, permitted update channel and compatibility before replacement. A valid signature does not authorise a different edition. If one package is rejected, the whole batch stops.';
$string['updateguard_signature'] = 'The publisher signature or package contents could not be verified.';
$string['updateguard_policy'] = 'This package does not match the installed edition or permitted package profile.';
$string['updateguard_channel'] = 'Updates through this channel are not enabled for this plugin.';
$string['updateguard_metadata'] = 'The version or dependency information is inconsistent.';
$string['updateguard_dependency'] = 'A required plugin version is missing. Update the dependency together with this plugin or beforehand.';
$string['updateguard_approval'] = 'The packages or installed state have changed since the preview. Review the operation again.';
$string['updateguard_busy'] = 'Another installation is running. Try again after it finishes.';
$string['updateguard_installed'] = 'The installed update protection is incomplete. Restore the files of the last approved installed version.';
$string['updateguard_operation'] = 'This combination of installation actions is not supported. Start a single update operation again.';
$string['updateguard_failed'] = 'The update could not be verified. No installation was started.';
$string['updateguard_back'] = 'Return to plugin overview';
// End shared independent signed update protection.

$string['uipageactions'] = 'Page Actions';
$string['quickeditsegment'] = 'Edit segment: {$a}';
$string['uiopentools'] = 'Open Page Actions';
$string['uiclosetools'] = 'Close Page Actions';
$string['uisearchsegments'] = 'Search Segment Types';
$string['uinoresultsegments'] = 'No segment types match your search.';
$string['uisampleheading'] = 'Your Heading';
$string['uisampletext'] = 'Add your own content and adjust its presentation in the segment settings.';
$string['managerdateformat'] = '%d %b %Y, %H:%M';
$string['uipagesintro'] = 'Prepare up to five Site Home pages. Choose the guest and signed-in pages under Settings before publishing.';
$string['title_help'] = 'Choose a title that identifies this page in the page list and browser tab.';
