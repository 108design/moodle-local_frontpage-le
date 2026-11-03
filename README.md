# Frontpage

Create a Moodle Site Home page with headings, rich text, images, a static Hero and responsive grids. Add Moodle course lists and references to activities in the Site Home course.

## Installation

Install this plugin in `local/frontpage` and complete Moodle’s upgrade process. Moodle 4.5 or later is required.

Open **Site administration > Plugins > Local plugins > Frontpage**. Create up to five pages and save their content as drafts. Preview them while you experiment. Under **Settings**, assign pages separately to guests and signed-in users, then publish the assigned pages and enable **Replace Site Home**. Only those two assignments can be published or viewed publicly. Changing an assignment returns the previous page to a private draft while keeping its content and revisions. Both audiences can use the same page. Switching the replacement off returns to Moodle’s native Site Home. Native Site Home must be enabled; Moodle’s default home page and forced-login settings still apply.

Managers with **Manage Frontpage pages** can edit content. **Publish Frontpage pages** controls publication separately. Files and earlier revisions remain associated with their pages. Use **Versions** to preview an earlier revision or restore it as a new draft.

## Course activities

The optional **Frontpage Course Activity** plugin uses this document engine for pages in a course. Install it separately in `mod/frontpage`. Teachers edit in the activity’s own context; native course access, availability and completion remain in effect.

## Resources and recovery

Choose an existing activity from the same course, or open Moodle’s activity chooser with **Create Activity or Resource**. Removing a segment removes its reference; the activity and its user data remain.

**Frontpage Resources** is available in the course navigation and remains accessible after the source page is deleted. Retained revisions protect their activity references. Resources created through Frontpage become deletion candidates after 30 days without retained references. Deletion requires confirmation and removes the native activity and its user data. Review other links and uses before deleting it. Earlier revisions can be removed individually; the current draft and publication are protected.

For Site Home recovery, keep a consistent backup of the full Moodle database, `moodledata` and `config.php`, together with the matching plugin code. Restore the database and file data together to retain IDs, audience settings, revisions and files. A course-activity backup does not contain Site Home. Keep backups outside the live web directory and test recovery.

## License

This release is available free of charge under the 108design Software License.

See [LICENSE.md](LICENSE.md) for the terms and Moodle Marketplace permission.

## Page CSS

Use the page CSS action in the editor to save a stylesheet in the current draft. Preview and publication follow the same revision as the content. The stylesheet is loaded on the page view; authoring forms remain available for recovery. Page CSS has its own permission, including for CSS changes made through a revision restore. Site managers need an explicit grant; course activity editors use the native module CSS permission. The editor bundle contains CodeMirror under MIT; its source and locked build are included.
