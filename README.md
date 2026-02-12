# Pragmatic SEO

Craft CMS 5 plugin scaffold for a Pragmatic SEO control panel section, with a two-tab CP interface ready to extend.

## Features
- CP section labeled `Pragmatic` with subnavigation item: `SEO`
- SEO section entry point redirects to `General`
- Four CP tabs: `General`, `Contenido`, `Imagenes`, and `Opciones`
- Custom field type `SEO` with subfields:
- `titulo`
- `descripcion`
- `imagen` (Asset ID)
- `descripcion de imagen`
- `Contenido` view with inline-edit table for all `SEO` fields created, plus save button
- `Imagenes` view with inline-edit table for all image assets:
- editable `titulo`
- editable `alt text`
- usage indicator per row (`usado` / `no usado`)
- filter to show only used assets
- Base Twig layout for SEO pages: `pragmatic-seo/_layout`
- Plugin registered as `pragmatic-seo` for Craft CMS 5 projects

## Requirements
- Craft CMS `^5.0`
- PHP `>=8.2`

## Installation
1. Add the plugin to your Craft project and run `composer install`.
2. Install the plugin from the Craft Control Panel.
3. Run migrations when prompted.

## Usage
### CP
- Go to `Pragmatic > SEO`.
- Use the **General** tab for global SEO settings (page scaffold ready).
- Use the **Contenido** tab to edit default SEO values for each `SEO` field type instance.
- Use the **Imagenes** tab to edit image metadata and filter by used assets.
- Use the **Opciones** tab for additional configuration (page scaffold ready).

## Project structure
```
src/
  PragmaticSeo.php
  controllers/
    DefaultController.php
  fields/
    SeoField.php
    SeoFieldValue.php
  templates/
    _layout.twig
    content.twig
    images.twig
    fields/
      seo_input.twig
      seo_settings.twig
    general.twig
    options.twig
```

## Notes
- This repository currently provides the control panel structure and routing scaffold.
- Business logic, settings models, and persistence can be added incrementally on top of this base.
