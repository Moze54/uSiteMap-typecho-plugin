# AGENTS.md - uSitemap Typecho Plugin

## Project Overview

This is **uSiteMap**, a Typecho CMS plugin that automatically generates XML sitemaps and pushes URLs to Baidu's search engine platform. The plugin is written in PHP and follows Typecho's plugin architecture.

**Repository:** `Moze54/uSiteMap-typecho-plugin`
**Blog:** https://blog.uuhb.cn

## File Structure

```
uSitemap/
├── Plugin.php    # Main plugin class (activation, config, hooks)
├── Action.php    # Core logic: sitemap generation, Baidu push, URL building
├── API.php       # REST-like API widget for external access
├── XML.php       # Sitemap XML serving widget with caching
├── Push.php      # Admin panel UI for manual push operations
├── AGENTS.md     # This file
└── log.txt       # Runtime log file (created when PluginLog=1)
```

## Architecture

- **Plugin.php**: Implements `Typecho_Plugin_Interface`. Registers routes (`/sitemap-api`, `/sitemap.xml`), admin panel, and hooks into `finishPublish` for auto-push. Contains `activate()`, `deactivate()`, `config()`, `auto()`, and `update_sitemap()` static methods.
- **Action.php**: Procedural functions (`update`, `submit`, `getPermalink`, `getPermalinkCategory`). Included via `require_once("Action.php")` — must be required before calling any function in this file.
- **API.php / XML.php**: Extend `Typecho_Widget` implementing `Widget_Interface_Do` with an `action()` method. These are route handlers registered by Plugin.php.
- **Push.php**: Admin panel page mixed HTML/PHP, included via `Helper::addPanel()`. Handles both display and action processing via `$_GET['action']`.

## Build / Test / Lint Commands

This is a Typecho plugin with **no formal build system, test suite, or linting configuration**. There are no Composer dependencies, no PHPUnit, no CI/CD pipelines, and no `.php-cs-fixer` or similar config files.

### Manual Testing

1. **Install**: Copy plugin directory to `usr/plugins/uSiteMap` in your Typecho installation.
2. **Activate**: Enable via Typecho admin panel → Plugins → uSiteMap → Activate.
3. **Test sitemap generation**: Visit `https://yoursite.com/sitemap.xml` — should return valid XML.
4. **Test API (update + push)**: `https://yoursite.com/sitemap-api?sitemap=update&push=new&token=YOUR_TOKEN`
5. **Test API (update only)**: `https://yoursite.com/sitemap-api?sitemap=update&token=YOUR_TOKEN`
6. **Test API (push latest N articles)**: `https://yoursite.com/sitemap-api?push=new&token=YOUR_TOKEN`
7. **Test API (push core pages)**: `https://yoursite.com/sitemap-api?push=main&token=YOUR_TOKEN`
8. **Test API (push all articles)**: `https://ysitemap-api?push=all&token=YOUR_TOKEN`
9. **Test admin panel**: Navigate to Typecho admin → Manage → 推送百度 (Push Baidu).

### Debugging

- Enable `PluginLog` in plugin settings to write logs to `log.txt` in the plugin directory.
- Check log format: `【YYYY-MM-DD HH:MM:SS】source message`.
- Common issues: empty `baidu_url` config, invalid API token, empty content responses from Baidu API.

## Code Style Guidelines

### PHP Conventions
- **PHP Version**: Targets PHP 7.x+ (no type declarations, no strict types, no return types).
- **Opening Tags**: `<?php` at file start, no closing `?>` tags.
- **Indentation**: Tabs in `Action.php`, 4 spaces in other files. Match the file you're editing.
- **Line Endings**: Unix-style (LF).
- **Short Tags**: Never use `<?` or `<?=` — always use full `<?php` and `<?php echo`.

### Naming Conventions
- **Classes**: PascalCase with plugin prefix: `uSiteMap_Plugin`, `uSiteMap_API`, `uSiteMap_XML`. The `uSiteMap_` prefix is mandatory for Typecho class discovery.
- **Functions**: camelCase for helper functions: `getPermalink()`, `getPermalinkCategory()`, `update()`, `submit()`.
- **Variables**: Mixed camelCase/snake_case (both used in codebase): `$sitemap_cachetime`, `$NumberOfLatestArticles`, `$result_json`. Follow existing style in each file.
- **Config Keys**: snake_case or PascalCase (mixed): `sitemap_cachetime`, `baidu_url`, `api_token`, `AutoPush`, `AutoSitemap`, `PluginLog`, `NumberOfLatestArticles`.

### Imports and Includes
- Use `require_once("Action.php")` for the shared function file (relative path from plugin directory).
- Include it before calling `update()`, `submit()`, `getPermalink()`, or `getPermalinkCategory()`.
- No namespaces, no autoloading, no Composer. Typecho discovers classes by naming convention (`uSiteMap_*`).
- Admin panel includes Typecho header/footer: `include 'header.php'`, `include 'menu.php'`, `include 'copyright.php'`, `include 'footer.php'`.

### Typecho-Specific Patterns
- **Database queries**: Use `Typecho_Db::get()` with fluent query builder:
  ```php
  $db->select()->from('table.contents')
     ->where('table.contents.status = ?', 'publish')
     ->where('table.contents.type = ?', 'post')
     ->order('table.contents.created', Typecho_Db::SORT_DESC)
  ```
- **Routing**: Register via `Helper::addRoute('name', '/path', 'uSiteMap_Class', 'action')`.
- **Widgets**: Access via `Typecho_Widget::widget('Widget_Options')` or `Helper::options()`.
- **Plugin config**: `Helper::options()->plugin('uSiteMap')->config_key`.
- **URL building**: `Typecho_Common::url($path, $options->index)` and `Typecho_Router::url($type, $data)`.
- **Admin notices**: `Typecho_Widget::widget('Widget_Notice')->set(_t("message"), 'success'|'error')`.
- **Translations**: Use `_t("string")` for user-facing text.

### Error Handling
- Minimal error handling; mostly silent failures or simple string returns.
- Check for null/empty config values before use: `if (Helper::options()->plugin('uSiteMap')->baidu_url == null)`.
- API returns plain text errors: `'token error'`, `'api closed'`, `'BadiuApi is null'` (note: typo exists in source).
- Baidu API responses parsed with `json_decode($result_json, true)`. Error responses have `$result['error'] == 400`.
- `try/catch` used only in `Plugin.php` config method for initial token generation.

### Logging
- Optional file-based logging to `log.txt` in plugin directory.
- Enabled when `PluginLog` config equals `1` (radio button in config panel).
- Format: `【YYYY-MM-DD HH:MM:SS】source message\n` (note: uses full-width brackets).
- Write via: `file_put_contents(__TYPECHO_ROOT_DIR__ . __TYPECHO_PLUGIN_DIR__ . '/uSiteMap/log.txt', $log, FILE_APPEND)`.

### HTML/Template Style (Push.php)
- Mixed PHP and HTML with `<?php ... ?>` blocks.
- Use `_e()` for translatable output, `echo` for dynamic/config values.
- Inline CSS in `<style>` blocks; no external stylesheets or Tailwind.
- Buttons use Typecho's `btn primary` class.
- Page redirects via `header("location: ...")` after form submissions.
- Auto-return via `header("Refresh:10;url=...")` for long-running push operations.

## Key Implementation Details

- **Sitemap file path**: `__TYPECHO_ROOT_DIR__ . __TYPECHO_PLUGIN_DIR__ . '/uSiteMap/sitemap'` (no `.xml` extension in filename).
- **Cache invalidation**: XML widget compares `filectime($dir)` against configured cache time (converted to seconds: `days * 86400`).
- **URL placeholders in permalink templates**: `{slug}`, `{category}`, `{directory}`, `{year}`, `{month}`, `{day}` — replaced via `str_replace()` in `getPermalink()`.
- **Category hierarchy**: Traverses `parent` field in `table.metas` via while loop to build full directory paths like `parent-slug/child-slug`.
- **cURL for Baidu push**: Uses `CURLOPT_POSTFIELDS` with newline-joined URL list, `Content-Type: text/plain`.
- **Auto-push hooks**: Registered on both `Widget_Contents_Post_Edit` and `Widget_Contents_Page_Edit` `finishPublish` events.
- **Visibility check**: Auto-push skips hidden posts and future-scheduled posts (`$contents['created'] > time()`).

## Known Issues / Tech Debt

- **Typo in error string**: `Action.php:113` returns `'BadiuApi is null'` (should be `BaiduApi`).
- **Undefined variable**: `$index_result` at `Action.php:14` is used without initialization (relies on PHP's null-to-empty-string behavior).
- **Duplicate require_once**: `Action.php` is included multiple times in some paths (`Plugin.php:26` and `Plugin.php:97`); `require_once` prevents double-inclusion but is redundant in some flows.
- **No XML escaping**: URLs in sitemap output are not XML-escaped; could break sitemap if URLs contain special characters.
- **No input validation**: API token and Baidu URL are accepted without sanitization beyond Typecho's config form.
- **Log path uses full-width brackets**: Log format uses `【】` (full-width) instead of `[]` — not a bug, but unusual for log parsers.

## API Endpoint Reference

The `/sitemap-api` endpoint accepts these query parameters:

| Parameter | Values | Description |
|-----------|--------|-------------|
| `token` | string | Required. Must match plugin's `api_token` setting. |
| `sitemap` | `update` | Regenerates `sitemap.xml` file. |
| `push` | `main` | Pushes homepage, categories, and standalone pages to Baidu. |
| `push` | `new` | Pushes the latest N articles (configured by `NumberOfLatestArticles`). |
| `push` | `all` | Pushes all published articles to Baidu. |

Combining `sitemap=update` with a `push` value performs both operations.

## Response Format

- **Success**: Returns JSON with `update` and/or `push` keys containing operation results.
- **Token error**: Returns plain string `'token error'`.
- **API disabled**: Returns plain string `'api closed'` (when `api_token` config is empty).

## Database Tables Used

| Table | Purpose |
|-------|---------|
| `table.contents` | Posts and pages; filtered by `status = 'publish'`, `type = 'post'` or `'page'`. |
| `table.metas` | Categories and tags; `type = 'category'` or `'tag'`, with `parent` for hierarchy. |
| `table.relationships` | Links posts to categories via `cid` and `mid`. |

## When Making Changes

1. Keep functions in `Action.php` procedural (no classes, no OOP).
2. Maintain backward compatibility with Typecho's `Typecho_Plugin_Interface`.
3. Test both `web` (admin UI) and `api` (HTTP endpoint) code paths when modifying push/sitemap logic — they return results differently (`Widget_Notice` vs string returns).
4. Use `Helper::options()->plugin('uSiteMap')->*` for reading all config values.
5. Do not add Composer, namespaces, or modern PHP features unless explicitly requested.
6. Match indentation style of the file you're editing (tabs in Action.php, spaces elsewhere).
7. When modifying XML output, ensure proper XML escaping and valid sitemap schema compliance.
8. Be cautious with the `update()` function — it deletes and recreates the sitemap file via `unlink()`, which will fail silently if the file doesn't exist on first run.
