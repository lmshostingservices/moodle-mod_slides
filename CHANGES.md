# Changelog — mod_slides

All notable changes to the Slides activity module. Versions use Moodle's
10-digit `YYYYMMDDXX` format.

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
