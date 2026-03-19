# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is **uSitemap**, a Typecho CMS plugin that automatically generates sitemap.xml files and pushes URLs to Baidu Search Engine Platform. The plugin provides both automatic and manual URL submission capabilities.

## Architecture

### Core Components

- **Plugin.php** (`Plugin.php:12-181`)
  - Main plugin class implementing `Typecho_Plugin_Interface`
  - Handles plugin activation/deactivation, configuration panel, and auto-hooks
  - Registers routes: `/sitemap-api` and `/sitemap.xml`
  - Hooks into post/page publish events for auto-push functionality

- **Action.php** (`Action.php:1-309`)
  - Core business logic with standalone functions (not a class)
  - `update()`: Generates sitemap.xml including index, pages, categories, posts, and tags
  - `submit()`: Pushes URLs to Baidu API with error handling
  - `getPermalink()`: Generates post permalinks with custom URL structure support
  - `getPermalinkCategory()`: Generates category permalinks with parent category hierarchy

- **API.php** (`API.php:2-52`)
  - API endpoint class extending `Typecho_Widget`
  - Token-based authentication for remote operations
  - JSON API responses for sitemap updates and URL pushes

- **XML.php** (`XML.php:2-22`)
  - Serves sitemap.xml with cache-control headers
  - Auto-regenerates sitemap when cache expires (configurable in days)
  - Returns XML content-type for proper SEO indexing

- **Push.php** (`Push.php:1-68`)
  - Admin panel interface for manual operations
  - HTML-based UI with buttons for various push operations
  - Integrates with Typecho admin header/footer/menu system

### Data Flow

1. **Sitemap Generation**: `update()` function queries Typecho database for all content types and generates XML sitemap at `__TYPECHO_ROOT_DIR__ . __TYPECHO_PLUGIN_DIR__ . '/uSiteMap/sitemap'`

2. **URL Pushing**: `submit()` function collects URLs based on operation type and POSTs to Baidu API using curl

3. **Auto-Operations**: Plugin hooks `finishPublish` on `Widget_Contents_Post_Edit` and `Widget_Contents_Page_Edit` trigger auto-push and sitemap update when content is published

### Configuration Options

All settings stored in Typecho options table under `plugin('uSiteMap')`:
- `sitemap_cachetime`: Cache duration in days (default: 7)
- `baidu_url`: Baidu push API endpoint
- `api_token`: 32-character token for API authentication
- `NumberOfLatestArticles`: Number of recent posts to push (default: 5)
- `AutoPush`: Enable automatic push on publish (0/1)
- `AutoSitemap`: Enable automatic sitemap update on publish (0/1)
- `PluginLog`: Enable logging to `log.txt` (0/1)

### URL Structure Handling

The plugin supports Typecho's custom permalink structures including:
- `{slug}`: Post slug
- `{category}`: Category slug
- `{directory}`: Parent category hierarchy (e.g., `parent/child`)
- `{year}`, `{month}`, `{day}`: Date components

The `getPermalink()` function builds complete permalinks by querying the database for post metadata and category relationships, including recursive parent category traversal.

## Development Notes

### Plugin Activation/Deactivation

- Activation registers routes and panel, creates initial sitemap file
- Deactivation removes routes, panel, and deletes sitemap file
- Routes must be unique: `sitemap-api` and `sitemap`

### API Usage

API endpoint: `/sitemap-api?sitemap=update&push=new&token={api_token}`

Parameters:
- `token`: Required, must match configured `api_token`
- `sitemap=update`: Trigger sitemap regeneration
- `push=main`: Push core pages (home, pages, categories)
- `push=all`: Push all posts
- `push=new`: Push latest N posts (configured via `NumberOfLatestArticles`)

Returns JSON response with operation results or error messages.

### Baidu API Integration

The plugin uses Baidu's "普通收录" (Normal Inclusion) API:
- Content-Type: `text/plain`
- POST body: URLs separated by newlines
- Response: JSON with `success`, `remain` count or `error` code
- Handles error codes: 400 "over quota", "empty content"

### Logging

When `PluginLog` is enabled, operations are logged to:
`__TYPECHO_ROOT_DIR__ . __TYPECHO_PLUGIN_DIR__ . '/uSiteMap/log.txt'`

Log format: `[timestamp] [source] [operation] [details]`

### Typecho Integration Points

- Uses `Helper::addRoute()` for custom routing
- Uses `Helper::addPanel()` for admin panel integration
- Hooks via `Typecho_Plugin::factory()` for publish events
- Database access via `Typecho_Db::get()`
- URL generation via `Typecho_Router::url()` and `Typecho_Common::url()`

## Important Considerations

1. **File Operations**: The plugin directly manipulates the sitemap file using `fopen()`, `fwrite()`, `unlink()` - ensure proper file permissions

2. **CURL Dependency**: Baidu push requires curl extension

3. **Token Security**: API token is auto-generated on first activation but should be treated as sensitive

4. **Cache Invalidation**: Sitemap cache is based on file creation time, not content changes

5. **Error Handling**: Plugin uses Typecho's notice system for admin feedback and returns JSON for API errors

6. **Hierarchical Categories**: Category permalink generation supports nested parent categories with recursive traversal
