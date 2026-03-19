# Typecho uSitemap 插件

自动生成符合标准的 XML 网站地图，支持多搜索引擎推送，帮助搜索引擎更好地索引您的网站内容。

## ✨ 功能特性

### Sitemap生成
- ✅ **符合标准** - 完全遵循 [Sitemap 协议规范](https://www.sitemaps.org/protocol.html)
- 🔄 **自动生成** - 自动包含文章、页面、分类和标签
- ⚙️ **灵活配置** - 可自定义包含的内容类型
- 🚫 **排除功能** - 支持排除特定文章或页面
- 🔒 **隐私保护** - 自动排除加密文章和草稿
- 📅 **准确时间** - 使用页面实际修改时间，非生成时间
- 🎯 **简洁高效** - 仅包含必需标签，输出更简洁

### 搜索引擎推送
- 📍 **百度推送** - 支持API推送和Sitemap推送两种方式
- 🔍 **Google推送** - 基于Indexing API实现
- 🎯 **Bing推送** - 支持API批量推送
- 🔍 **搜狗推送** - 支持API和Sitemap推送
- 🛡️ **360推送** - 支持API和Sitemap推送
- 🤖 **自动推送** - 文章发布/更新时自动推送到搜索引擎
- 📊 **推送日志** - 详细的推送记录，便于追踪推送状态
- 🎛️ **灵活配置** - 可自定义推送时机和推送数量

## 📦 安装方法

1. 下载插件文件
2. 将文件夹重命名为 `uSitemap`
3. 上传到 Typecho 的 `usr/plugins/` 目录
4. 在后台「控制台」→「插件」中启用插件
5. 点击「设置」进行配置

## ⚙️ 配置选项

### Sitemap基础设置

#### 包含的内容类型
选择要包含在站点地图中的内容：
- **文章 (Posts)** - 博客文章
- **独立页面 (Pages)** - 关于、联系等独立页面
- **分类 (Categories)** - 分类归档页面
- **标签 (Tags)** - 标签归档页面

#### 排除内容
输入要排除的文章或页面 ID，每行一个。例如：
```
15
28
36
```

#### 密码保护内容
选择是否包含设置了密码访问的内容（默认不包含）

#### 更新频率
设置内容的更新频率，这将影响搜索引擎抓取频率：
- 始终
- 每小时
- 每天
- 每周
- 每月
- 每年
- 从不

#### 默认优先级
设置 URL 的默认优先级（0.0 - 1.0），首页优先级会自动加 0.1

#### 最大条目数
站点地图中包含的最大 URL 数量，默认 50000

### 搜索引擎推送配置

#### 百度推送
- **启用百度推送**：开启后，文章发布/更新时会自动推送到百度
- **百度站点**：百度站长平台中验证的站点域名
- **百度推送Token**：在百度站长平台「普通收录」中获取的推送接口令牌
- **推送方式**：
  - API推送：实时推送单个URL，速度更快
  - Sitemap推送：推送sitemap地址，批量提交
- **自动推送触发**：可选择在发布文章时或更新文章时推送
- **手动推送数量**：手动推送时推送的最新N条内容

#### Google推送
- **启用Google推送**：开启后，文章发布/更新时会自动推送到Google
- **Google API密钥**：在Google Cloud Console中创建的API密钥，用于Indexing API
- **自动推送触发**：可选择在发布文章时或更新文章时推送
- **手动推送数量**：手动推送时推送的最新N条内容

**获取Google API密钥步骤**：
1. 访问 [Google Cloud Console](https://console.cloud.google.com/)
2. 创建新项目或选择现有项目
3. 启用「Indexing API」
4. 创建服务账号并下载JSON密钥文件
5. 在 [Google Search Console](https://search.google.com/search-console) 中验证网站并添加服务账号为资源所有者

#### Bing推送
- **启用Bing推送**：开启后，文章发布/更新时会自动推送到Bing
- **Bing API密钥**：在Bing Webmaster Tools中获取的API密钥
- **Bing站点URL**：在Bing Webmaster Tools中验证的站点URL
- **自动推送触发**：可选择在发布文章时或更新文章时推送
- **手动推送数量**：手动推送时推送的最新N条内容

**获取Bing API密钥步骤**：
1. 访问 [Bing Webmaster Tools](https://www.bing.com/webmasters)
2. 登录并验证网站所有权
3. 进入「API Access」-「API Key」
4. 点击「Generate API Key」生成API密钥

#### 搜狗推送
- **启用搜狗推送**：开启后，文章发布/更新时会自动推送到搜狗
- **搜狗推送Token**：在搜狗站长平台中获取的推送接口令牌
- **推送方式**：支持API推送和Sitemap推送两种方式
- **自动推送触发**：可选择在发布文章时或更新文章时推送
- **手动推送数量**：手动推送时推送的最新N条内容

#### 360推送
- **启用360推送**：开启后，文章发布/更新时会自动推送到360
- **360站点**：360站长平台中验证的站点域名
- **360推送Token**：在360站长平台中获取的推送接口令牌
- **推送方式**：支持API推送和Sitemap推送两种方式
- **自动推送触发**：可选择在发布文章时或更新文章时推送
- **手动推送数量**：手动推送时推送的最新N条内容

## 📝 使用方法

### 访问站点地图
启用插件后，访问以下地址查看生成的站点地图：
```
https://your-domain.com/sitemap.xml
```

### 手动推送
在插件设置页面，每个搜索引擎都有「手动推送」按钮：
1. 切换到对应搜索引擎的标签页
2. 配置好推送参数（API密钥、Token等）
3. 点击「立即推送」按钮
4. 查看推送结果

### 自动推送
启用搜索引擎推送后，当您发布或更新文章时，系统会自动推送到已启用的搜索引擎。

### 查看推送记录
1. 切换到「推送记录」标签页
2. 点击「刷新记录」查看最新的推送日志
3. 点击「清空记录」可以删除所有推送日志

### 提交Sitemap到搜索引擎

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

#### 搜狗站长平台
1. 登录 [搜狗站长平台](http://zhanzhang.sogou.com)
2. 进入「网页收录」-「Sitemap」
3. 提交您的 sitemap 地址

#### 360站长平台
1. 登录 [360站长平台](http://zhanzhang.so.com)
2. 进入「网页收录」-「Sitemap」
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
    <changefreq>weekly</changefreq>
    <priority>0.9</priority>
  </url>
  <url>
    <loc>https://your-domain.com/archives/1/</loc>
    <lastmod>2025-10-10</lastmod>
    <changefreq>weekly</changefreq>
    <priority>0.8</priority>
  </url>
  <url>
    <loc>https://your-domain.com/about.html</loc>
    <lastmod>2025-10-01</lastmod>
    <changefreq>weekly</changefreq>
    <priority>0.8</priority>
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

#### `<changefreq>` (可选)
- 页面更新频率
- 可选值：always、hourly、daily、weekly、monthly、yearly、never

#### `<priority>` (可选)
- 页面优先级
- 范围：0.0-1.0
- 默认值：0.5
- 首页会自动加 0.1

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

### 推送机制

#### 自动推送
- 文章发布/更新时自动触发
- 支持配置推送时机（发布时/更新时/两者都推送）
- 异步执行，不影响用户体验

#### 手动推送
- 支持批量推送最新的N条URL
- 实时显示推送结果
- 推送记录保存在日志文件中

#### 推送限制
- 百度API：单次最多2000条
- Google API：单次最多1000条
- Bing API：逐个推送
- 搜狗API：单次最多1000条
- 360API：单次最多1000条

## ❓ 常见问题

### Sitemap相关

#### 1. 访问 sitemap.xml 返回 404
- 确保插件已启用
- 尝试在后台禁用并重新启用插件
- 检查 URL 重写是否正常工作
- 确认伪静态规则已正确配置

#### 2. 某些文章没有出现在站点地图中
- 检查文章状态是否为「已发布」
- 确认文章没有设置密码
- 检查是否在「排除内容 ID」中添加了该文章
- 确认内容类型（文章/页面/分类/标签）已勾选

#### 3. 如何更新站点地图？
站点地图是实时生成的，每次访问都会显示最新内容，无需手动更新。

#### 4. 站点地图可以被缓存吗？
可以，建议在服务器或 CDN 层面设置适当的缓存时间（如 1-6 小时）以提高性能。

#### 5. 支持站点地图索引吗？
当前版本生成单个站点地图文件。如果内容超过 50,000 条或文件大小超过 50MB，建议使用专业的 SEO 插件。

### 推送相关

#### 6. 推送失败怎么办？
- 检查API密钥/Token是否正确
- 确认网站已在搜索引擎平台验证通过
- 查看推送记录中的错误信息
- 检查服务器网络连接是否正常

#### 7. 为什么Google推送需要服务账号？
Google的Indexing API需要使用OAuth 2.0认证，必须创建服务账号并授权。

#### 8. 推送次数有限制吗？
各搜索引擎对API推送都有配额限制：
- 百度：每天最多10万次
- Google：每天最多600次（可通过申请增加）
- Bing：每天最多2000次
- 搜狗：每天最多10万次
- 360：每天最多10万次

#### 9. 自动推送和手动推送有什么区别？
- **自动推送**：文章发布/更新时自动触发，无需手动操作
- **手动推送**：主动推送最新的N条URL，适合批量推送历史内容

#### 10. 推送日志保存在哪里？
推送日志保存在 `usr/plugins/uSitemap/logs/` 目录下，按日期和搜索引擎分类存储。

## 🔧 高级用法

### 自定义路由
如果需要修改站点地图的访问路径，可以编辑 `Plugin.php` 中的路由设置：

```php
Helper::addRoute('sitemap', '/your-custom-path.xml', 'uSitemap_Action', 'index');
```

### 与静态缓存插件配合
如果使用了静态缓存插件，建议将 `sitemap.xml` 设置为动态路径或定期更新缓存。

### 批量推送历史内容
如果需要推送大量历史文章：
1. 在配置中设置较大的手动推送数量（如100-500）
2. 点击「立即推送」按钮
3. 等待推送完成
4. 查看推送记录确认结果

### 自定义推送时机
如果只想在特定情况下推送：
- 取消勾选「发布文章时」
- 取消勾选「更新文章时」
- 仅在需要时使用手动推送功能

## 📊 SEO 最佳实践

1. **定期提交** - 每次发布新内容后，通知搜索引擎抓取站点地图
2. **监控收录情况** - 通过搜索引擎管理员工具查看收录统计
3. **配合 robots.txt** - 确保站点地图可被搜索引擎访问
4. **保持更新** - 修改文章后，站点地图会自动反映最新的修改时间
5. **合理设置优先级** - 重要内容设置更高的优先级（0.8-1.0）
6. **选择合适的更新频率** - 根据内容更新频率设置changefreq
7. **多平台推送** - 同时向多个搜索引擎推送，提高覆盖率
8. **分析推送效果** - 定期查看推送日志，优化推送策略

## 📄 协议规范

本插件严格遵循以下规范：
- [Sitemap 协议 0.9](https://www.sitemaps.org/protocol.html)
- [Google Sitemap 指南](https://developers.google.com/search/docs/advanced/sitemaps/overview)
- [Google Indexing API](https://developers.google.com/webmaster-tools/v3/how-tos/search-console/indexing-api)
- [Bing Webmaster API](https://docs.microsoft.com/en-us/bing/webmaster-tools/)
- [W3C Datetime 格式](https://www.w3.org/TR/NOTE-datetime)

## 🔗 相关资源

### 官方平台
- [Typecho 官网](http://typecho.org)
- [Sitemap 协议官网](https://www.sitemaps.org)
- [Google Search Console](https://search.google.com/search-console)
- [Bing Webmaster Tools](https://www.bing.com/webmasters)
- [百度搜索资源平台](https://ziyuan.baidu.com)
- [搜狗站长平台](http://zhanzhang.sogou.com)
- [360站长平台](http://zhanzhang.so.com)

### 开发文档
- [Google Indexing API](https://developers.google.com/webmaster-tools/v3/how-tos/search-console/indexing-api)
- [Bing Submit URL API](https://docs.microsoft.com/en-us/bing/webmaster-tools/submit-url-api)
- [百度推送接口](https://ziyuan.baidu.com/wiki/last调用)

## 📝 更新日志

### 2.0.0 (2025-03-19)
- 🎉 **重大更新** - 新增多搜索引擎推送功能
- ✅ **百度推送** - 支持API推送和Sitemap推送两种方式
- ✅ **Google推送** - 基于Indexing API实现
- ✅ **Bing推送** - 支持API批量推送
- ✅ **搜狗推送** - 支持API和Sitemap推送
- ✅ **360推送** - 支持API和Sitemap推送
- 🤖 **自动推送** - 文章发布/更新时自动推送
- 📊 **推送日志** - 详细的推送记录功能
- 🎨 **界面优化** - 全新的标签页式配置界面
- 📝 **帮助提示** - 每个搜索引擎配置都附带详细的获取指南

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

## 📁 项目结构

```
uSitemap/
├── Plugin.php              # 插件主文件
├── Action.php              # 动作类，处理Sitemap生成和推送
├── BaiduPusher.php         # 百度推送类
├── GooglePusher.php        # Google推送类
├── BingPusher.php          # Bing推送类
├── SogouPusher.php         # 搜狗推送类
├── Push360Pusher.php       # 360推送类
├── SearchEnginePusher.php  # 搜索引擎推送基类
├── logs/                   # 推送日志目录
│   ├── baidu_push_*.log
│   ├── google_push_*.log
│   ├── bing_push_*.log
│   ├── sogou_push_*.log
│   └── 360_push_*.log
└── README.md               # 说明文档
```

## 🤝 贡献

欢迎提交 Issue 和 Pull Request！

## ⭐ Star History

如果这个项目对你有帮助，请给个 Star 支持一下！

## 📮 联系方式

- 个人博客：https://blog.uuhb.cn
- 项目地址：https://github.com/Moze54/uSiteMap-typecho-plugin

---

**注意**：使用本插件前请确保您的服务器支持 PHP curl 扩展，否则推送功能无法使用。
