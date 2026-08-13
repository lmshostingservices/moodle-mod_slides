# Slides (mod_slides)

An interactive, slide-based activity module for Moodle. Build rich learning
sequences from a set of built-in slide types — introduction, image & text,
image poster, flip card, matching, video and summary — each delivered as a
subplugin of type `slidetype`.

## Requirements
- Moodle 4.0 or later (tested to Moodle 5.x).

## Installation
1. Copy the `slides` folder into your Moodle `mod/` directory (so it lives at
   `mod/slides`), or install the ZIP via *Site administration → Plugins →
   Install plugins*.
2. Visit *Site administration → Notifications* and complete the upgrade to
   register the module and its seven `slidetype_*` subplugins.

## Subplugins
Slide types live under `mod/slides/slide/<type>` and are declared in
`db/subplugins.json` as plugin type `slidetype`.

## License
GNU GPL v3 or later — see `LICENSE`.
