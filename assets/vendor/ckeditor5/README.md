# CKEditor Decoupled Build Scaffold for WP-Office-Editor

This repository scaffold helps you build a **Vanilla JS Decoupled CKEditor 5** build
suitable for self-hosting inside the WordPress plugin `wp-office-editor`.

**Important**: This scaffold does NOT include the compiled `ckeditor.js` (the real build).
Use the instructions below to create the real build on your machine (or Codespaces), then copy
the generated `build/ckeditor.js` into the plugin path:

```
wp-office-editor/assets/vendor/ckeditor5/ckeditor.js
```

## Quick steps (recommended: run in Codespaces or your local dev machine)

1. Open a terminal in this scaffold folder.
2. Install dev dependencies:
   ```
   npm install
   ```
3. Build the editor:
   ```
   npm run build
   ```
   This will create `build/ckeditor.js`.
4. Copy the generated `build/ckeditor.js` into your plugin:
   ```
   cp build/ckeditor.js /path/to/wp-office-editor/assets/vendor/ckeditor5/ckeditor.js
   ```
5. (Optional) Copy translations/styles if present.

## What this scaffold contains
- `src/ckeditor.js` : example entry file that imports the desired CKEditor plugins (use npm to install real packages)
- `webpack.config.js` : simple config to bundle the editor into `build/ckeditor.js`
- `package.json` : scripts to build
- `build/ckeditor.placeholder.js` : a small placeholder file to let the plugin load without failing (replace with real build)
- `README.md` : instructions (this file)

## Notes on Pick Technology
Use **Vanilla JS** when building for a WordPress admin page (not React/Vue/Angular).

## If you want me to run the build for you
I cannot run `npm install` and produce the official CKEditor build inside this chat environment.
But I can provide exact commands and troubleshoot build errors you get when running them in Codespaces.
