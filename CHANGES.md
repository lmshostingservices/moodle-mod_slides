# Changelog — mod_slides

All notable changes to the Slides activity module. Versions use Moodle's
10-digit `YYYYMMDDXX` format.

## 2026081214 (v1.4.14) - 2026-08-13
- Teachers / site admins can now TRIAL activities like a student. Previously anyone
  with editing rights was force-completed (`has_view_option()` returned completed),
  so matching, flip cards and other interactions rendered pre-answered/locked with
  green ticks. Their completion is now reset on each view, so every slide type shows
  its fresh, interactive version. The forced-next navigation is also shown to them
  (was hidden for editors), so their experience matches a student's.
- Fixed the "mod_slides/slide - notfound" error on the Activity Finished screen: the
  load-next-slide fragment tried to render a non-existent slide after the last one;
  it now returns nothing when there is no next slide.
- Backup no longer fatals: implemented the two missing helper methods
  (backup_include_slides_areafiles / backup_include_listenaudio) so backup annotates
  every slide media filearea; restore now re-adds all those areas (previously only
  the intro image survived a restore).
- Data-integrity fixes: corrected a wrong table name (`slides_slides` → `slides_type`)
  in the slide-type lookup; fixed a mis-named completion field and timestamp on the
  per-slide completion insert (`contentcount`/`timecreated` → `contentscount`/
  `timemodified`); and the activity-completion insert now honours an explicit userid.
- Secured the web services: `update_slidecompletion` and `update_fontsize` now
  validate the activity context and capability (and `update_fontsize` no longer
  targets a non-existent column).

## 2026081213 (v1.4.13) - 2026-08-13
- Redesigned the slide pagination dots. The old numbered `<ol><li>` markup used
  Bootstrap-4 carousel-indicator classes that collapsed into one broken oval on
  Bootstrap 5. Replaced with discrete circular step-dots (`<button.slides-dot>`)
  that don't depend on Bootstrap's indicator CSS; the nav highlights the current
  dot and marks reached slides as available.
- Bootstrap-5 colour safety: every `var(--primary)`/`var(--success)` now falls
  back to `var(--bs-primary/…, #hex)` so fills/lines don't vanish on BS5 themes.
- Accessibility: carousel controls use `.visually-hidden` (BS5) + `aria-label`;
  dots carry `aria-label`.
- JS hardening: removed an unused `theme_boost/bootstrap/carousel` import from
  `nctslides` (it was a hard AMD dependency that could fail the whole module on
  some Bootstrap builds), and added the missing `core/notification` import (the
  load-next-slide error handler referenced `Notification` without importing it).
- Security: added `require_capability('mod/slides:viewslideeditor', …)` to the
  delete-slide, toggle-visibility and reorder-slides fragment endpoints, which
  previously performed destructive writes with no editing-capability check.

## 2026081212 (v1.4.12) - 2026-08-13
- Fixed the frozen drag-and-drop on the matching slide type. The build tool was
  double-wrapping `slidetype_matching/ddmatching` (an already-AMD module that
  begins with `//` comments): it emitted `define("slidetype_matching/ddmatching",
  [], function(){ define([...real deps...], factory) })`, so RequireJS threw
  "Mismatched anonymous define() module" and the drag-drop engine never loaded —
  its `init` (called for incomplete questions only) failed, so no drag handlers
  were ever attached and learners could not move the items to complete the
  activity. The build now detects an existing AMD `define()` past line comments
  and emits a single correctly-named module (no Babel wrapping). Verified: the
  module now loads as one named define exporting `init()`. Also corrects
  `mod_slides/slide_editor` (the other already-AMD module) to a named define.

## 2026081211 (v1.4.11) - 2026-08-13
- Replaced the carousel navigation with a self-contained implementation that does
  NOT drive Bootstrap's carousel JS. The previous approach (v1.4.7) called
  Bootstrap's carousel API, but on Bootstrap 5 that API is unreliable on the
  plugin's legacy markup: after one transition the `slid` event often never fires,
  so Bootstrap's `_isSliding` flag sticks and blocks all further navigation (arrows
  dead after one move, no going back) and the incoming slide's content never
  initialises (blank card). The new code performs the slide transition directly and
  always fires slide/slid.bs.carousel, with a guard + safety-net timeout so it can
  never get stuck. Verified live: forward and backward through every slide,
  multi-step indicator jumps, and content rendering on each slide. Fixes: "couldn't
  move to next slide", "move forward once but not back", and "nothing displays in
  the cards after the video".

## 2026081210 (v1.4.10) - 2026-08-13
- Version bump only, to trigger the Moodle DB upgrade past 2026081209. Identical
  code to 2026081206/v1.4.7 below (Bootstrap 5 carousel navigation fix + 10-digit
  upgrade savepoints).

## 2026081209 (v1.4.9) - 2026-08-13
- Version bump only, to upgrade strictly past the current stamp (2026081208).
  Identical code to 2026081206/v1.4.7 below (Bootstrap 5 carousel navigation fix
  + 10-digit upgrade savepoints).

## 2026081208 (v1.4.8) - 2026-08-13
- Version bump only, to clear the LMS Labs auto-promotion check (the pipeline had
  a promoted numeric of 2026081207; a build must be strictly greater). Identical
  code to 2026081206/v1.4.7 below.

## 2026081206 (v1.4.7) - 2026-08-13
- Fixed carousel navigation on Bootstrap 5 Moodle sites. The prev/next controls
  used Bootstrap 4 data-api attributes (`data-slide` / `href`), but modern Moodle
  (4.4+/5.x) ships Bootstrap 5 and registers no carousel click data-api on the
  activity view, so the arrows rendered but never moved the carousel. This left
  learners stuck after the video: the arrows did nothing, the video never
  advanced to the next slide, and the "reach the end" completion rule could never
  be met. The controls are now wired directly in JavaScript, so navigation works
  on both Bootstrap 4 and 5. Event delegation preserves the existing
  forced-listen gating (a control disabled via pointer-events is still ignored).
- Hardened `db/upgrade.php`: converted four legacy 13-digit upgrade savepoints to
  10-digit values below the current version, so upgrading from the 10-digit
  version line no longer risks re-stamping a 13-digit version (which previously
  caused "downgrade not allowed" revert loops). No schema changes.

## 2026081205 (v1.4.6) - 2026-08-12
- Fixed a null-reference crash in the slide event setup: the course-index click
  handler assumed the drawer element always exists. On themes without it,
  `addEventListener` threw and aborted ALL slide handlers, breaking navigation
  arrows, video-end auto-advance, and completion tracking. Now null-guarded.

## 2026081204 (v1.4.5) - 2026-08-12
- Recompiled ALL AMD modules (parent + video/flip/matching subplugins) with one
  consistent build so cross-module imports and class inheritance resolve
  correctly. Fixes `Class extends value #<Object> is not a constructor` when the
  video slide-type extends the base slide class. This completes the JS chain that
  renders the video.

## 2026081203 (v1.4.4) - 2026-08-12
- Fixed slide-type module loading: replaced a native dynamic `import()` (which
  browsers cannot resolve for bare Moodle specifiers like `slidetype_video/slide`)
  with Moodle's `require()` loader. This was the last blocker preventing the
  video slide from rendering.

## 2026081202 (v1.4.3) - 2026-08-12
- Exposed `init` as a named export on the `nctslides` AMD module so Moodle's
  `js_call_amd('mod_slides/nctslides','init')` resolves (fixes
  `amd.init is not a function`, which left the slide on a loading spinner).

## 2026081201 (v1.4.2) — 2026-08-12
- Version bump so Moodle runs a normal plugin upgrade on upload.
- Tightened form-field parameter types to `PARAM_TEXT` on the button-group and matching-answer
  form fields (Moodle marketplace security check).
- Added the missing `cachedef_webfonts` language string.
- Ships the JavaScript fix: `selectors.js` provides named exports (`SELECTORS`,
  `forceListen`) and the AMD build is regenerated, resolving the
  `Cannot read properties of undefined (reading 'root')` error that left Video
  Activity pages stuck with no content.

## 2026081200 (v1.4.1) — 2026-08-12
- Fixed `db/subplugins.json` to use the `plugintypes` key (was `subplugintypes`),
  which on Moodle 4.x/5.x caused the `slidetype` subplugin type to fail to
  register and Video Activity pages to throw `slide video - notfound`.
- Standardised every component to a 10-digit version number (parent and all
  seven `slidetype_*` subplugins now `2026081200`) for Moodle plugin directory
  compliance.
- Fixed a JavaScript import/export mismatch: `selectors.js` now provides named
  exports (`SELECTORS`, `forceListen`) so `slide.js`'s named imports resolve
  (previously `SELECTORS` was `undefined`, breaking slide content rendering).
- Recompiled the AMD JavaScript modules (`slide`, `nctslides`, `selectors`,
  `autosplit`) from source to correct `define()` output.

## 2026081100 (v1.4)
- Video slide type and slide-editor improvements.

## 2026072700 (v1.3)
- Added the `video` slide type and packaging of all built-in slide types.

## 2025051500 (v1.2)
- Slide-type subplugin framework (flip, imagetext, imageposter, introduction,
  matching, summary).

## 2024093012 (v1.1)
- Initial public release of the Slides activity module.
