# Typecho Sitemap 插件

自动生成符合标准的 XML 网站地图，帮助搜索引擎更好地索引您的网站内容。

## ✨ 功能特性

- ✅ **符合标准** - 完全遵循 [Sitemap 协议规范](https://www.sitemaps.org/protocol.html)
- 🔄 **自动生成** - 自动包含文章、页面、分类和标签
- ⚙️ **灵活配置** - 可自定义包含的内容类型
- 🚫 **排除功能** - 支持排除特定文章或页面
- 🔒 **隐私保护** - 自动排除加密文章和草稿
- 📅 **准确时间** - 使用页面实际修改时间，非生成时间
- 🎯 **简洁高效** - 仅包含必需标签，输出更简洁

## 📦 安装方法

1. 下载插件文件
2. 将文件夹重命名为 `Sitemap`
3. 上传到 Typecho 的 `usr/plugins/` 目录
4. 在后台「控制台」→「插件」中启用插件
5. 点击「设置」进行配置

## ⚙️ 配置选项

### 包含的内容类型
选择要包含在站点地图中的内容：
- **文章 (Posts)** - 博客文章
- **独立页面 (Pages)** - 关于、联系等独立页面
- **分类 (Categories)** - 分类归档页面
- **标签 (Tags)** - 标签归档页面

### 排除内容
输入要排除的文章或页面 ID，每行一个。例如：
```
15
28
36
```

### 包含首页
选择是否在站点地图中包含网站首页（推荐开启）。

## 📝 使用方法

### 访问站点地图
启用插件后，访问以下地址查看生成的站点地图：
```
https://your-domain.com/sitemap.xml
```

### 提交到搜索引擎

#### Google Search Console
1. 登录 [Google Search Console](https://search.google.com/search-console)
2. 选择您的网站
3. 在左侧菜单选择「站点地图」
4. 输入 `sitemap.xml` 并提交

#### Bing 网站管理员工具
1. 登录 [Bing 网站管理员工具](https://www.bing.com/webmasters)
2. 选择您的网站
3. 在「站点地图」部分提交 `sitemap.xml`

#### 百度搜索资源平台
1. 登录 [百度搜索资源平台](https://ziyuan.baidu.com)
2. 选择「普通收录」→「sitemap」
3. 提交您的 sitemap 地址

### robots.txt 配置
建议在网站根目录的 `robots.txt` 文件中添加站点地图地址：

```txt
User-agent: *
Allow: /

Sitemap: https://your-domain.com/sitemap.xml
```

## 📋 站点地图格式示例

生成的 XML 文件格式如下：

```xml
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <url>
    <loc>https://your-domain.com/</loc>
    <lastmod>2025-10-13</lastmod>
  </url>
  <url>
    <loc>https://your-domain.com/archives/1/</loc>
    <lastmod>2025-10-10</lastmod>
  </url>
  <url>
    <loc>https://your-domain.com/about.html</loc>
    <lastmod>2025-10-01</lastmod>
  </url>
  <!-- 更多 URL ... -->
</urlset>
```

## 🔍 技术说明

### XML 标签说明

#### `<urlset>` (必需)
- 包裹所有 URL 的根标签
- 必须包含命名空间声明

#### `<url>` (必需)
- 每个 URL 的父标签
- 每个 URL 一个 `<url>` 标签

#### `<loc>` (必需)
- 页面的完整 URL
- 必须以协议开头（http 或 https）
- 最多 2,048 字符

#### `<lastmod>` (可选)
- 页面最后修改时间
- W3C Datetime 格式
- 格式：`YYYY-MM-DD`（使用简洁的日期格式）
- **重要**：显示的是页面实际修改时间，而非站点地图生成时间



### 自动排除的内容

插件会自动排除以下内容：
- 草稿、待审核等非发布状态的内容
- 设置了密码的加密文章
- 在配置中手动排除的内容 ID

### 性能优化

- 直接从数据库查询，避免多次对象实例化
- 使用流式输出，节省内存
- 仅在访问时生成，不占用存储空间

### lastmod 时间优化

插件智能处理各类内容的最后修改时间：

- **文章/页面**：使用实际的 `modified` 字段（内容最后修改时间）
- **首页**：使用最新发布文章的修改时间
- **分类**：使用该分类下最新文章的修改时间
- **标签**：使用该标签下最新文章的修改时间
- **格式**：使用 `YYYY-MM-DD` 格式（W3C Datetime 标准）

这样确保搜索引擎获得准确的页面更新信息，而不是站点地图生成时间。

## ❓ 常见问题

### 1. 访问 sitemap.xml 返回 404
- 确保插件已启用
- 尝试在后台禁用并重新启用插件
- 检查 URL 重写是否正常工作

### 2. 某些文章没有出现在站点地图中
- 检查文章状态是否为「已发布」
- 确认文章没有设置密码
- 检查是否在「排除内容 ID」中添加了该文章

### 3. 如何更新站点地图？
站点地图是实时生成的，每次访问都会显示最新内容，无需手动更新。

### 4. 站点地图可以被缓存吗？
可以，建议在服务器或 CDN 层面设置适当的缓存时间（如 1-6 小时）以提高性能。

### 5. 支持站点地图索引吗？
当前版本生成单个站点地图文件。如果内容超过 50,000 条或文件大小超过 50MB，建议使用专业的 SEO 插件。

## 🔧 高级用法

### 自定义路由
如果需要修改站点地图的访问路径，可以编辑 `Plugin.php` 中的路由设置：

```php
Helper::addRoute('sitemap', '/your-custom-path.xml', 'Sitemap_Action', 'index');
```

### 与静态缓存插件配合
如果使用了静态缓存插件，建议将 `sitemap.xml` 设置为动态路径或定期更新缓存。

## 📊 SEO 最佳实践

1. **定期提交** - 每次发布新内容后，通知搜索引擎抓取站点地图
2. **监控收录情况** - 通过搜索引擎管理员工具查看收录统计
3. **配合 robots.txt** - 确保站点地图可被搜索引擎访问
4. **保持更新** - 修改文章后，站点地图会自动反映最新的修改时间

## 📄 协议规范

本插件严格遵循以下规范：
- [Sitemap 协议 0.9](https://www.sitemaps.org/protocol.html)
- [Google Sitemap 指南](https://developers.google.com/search/docs/advanced/sitemaps/overview)
- [W3C Datetime 格式](https://www.w3.org/TR/NOTE-datetime)

## 🔗 相关资源

- [Typecho 官网](http://typecho.org)
- [Sitemap 协议官网](https://www.sitemaps.org)
- [Google Search Console](https://search.google.com/search-console)
- [Bing 网站管理员工具](https://www.bing.com/webmasters)

## 📝 更新日志

### 1.0.0 (2025-10-13)
- 🎉 首次发布
- ✅ 支持文章、页面、分类、标签
- 📅 智能获取页面实际修改时间
- 🚫 支持排除特定内容
- 📊 符合 Sitemap 0.9 协议规范
- 🎯 简洁输出，仅包含必需的 `<loc>` 和 `<lastmod>` 标签

## 👨‍💻 作者

**cxuxrxsxoxr**

## 📄 许可证

本插件遵循 Typecho 相关许可协议。 