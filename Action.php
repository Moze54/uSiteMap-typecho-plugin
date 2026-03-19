<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

/**
 * Sitemap 动作类
 */
class uSitemap_Action extends Typecho_Widget implements Widget_Interface_Do
{
    private $options;
    private $db;
    
    /**
     * 初始化
     */
    public function __construct($request, $response, $params = NULL)
    {
        parent::__construct($request, $response, $params);
        $this->db = Typecho_Db::get();
        $this->options = Helper::options();
    }
    
    /**
     * 执行函数
     */
    public function action()
    {
        $this->on($this->request->is('index'))->index();
        $this->on($this->request->is('do=baidu_manual_push'))->baiduManualPush();
        $this->on($this->request->is('do=baidu-push'))->baiduPush();
        $this->on($this->request->is('do=google_manual_push'))->googleManualPush();
        $this->on($this->request->is('do=google-push'))->googlePush();
        $this->on($this->request->is('do=bing_manual_push'))->bingManualPush();
        $this->on($this->request->is('do=bing-push'))->bingPush();
        $this->on($this->request->is('do=sogou_manual_push'))->sogouManualPush();
        $this->on($this->request->is('do=sogou-push'))->sogouPush();
        $this->on($this->request->is('do=360_manual_push'))->push360ManualPush();
        $this->on($this->request->is('do=360-push'))->push360Push();
        $this->on($this->request->is('do=get_logs'))->getLogs();
        $this->on($this->request->is('do=clear_logs'))->clearLogs();
    }
    
    /**
     * 生成站点地图
     */
    public function index()
    {
        $pluginOptions = $this->options->plugin('uSitemap');
        if (!$pluginOptions) {
            $this->response->setStatus(404);
            echo 'Sitemap plugin is not configured.';
            return;
        }

        // 设置 HTTP 头
        $this->response->setContentType('application/xml');

        // 获取排除的内容ID
        $excludeCids = $this->parseExcludeList($pluginOptions->excludeCids);

        // 获取配置参数
        $changefreq = $pluginOptions->changefreq ?: 'weekly';
        $priority = floatval($pluginOptions->priority ?: 0.8);
        $maxItems = intval($pluginOptions->maxItems ?: 50000);
        $includePassword = $pluginOptions->includePassword == '1';

        // 开始输出 XML
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        $itemCount = 0;

        // 添加首页
        if ($pluginOptions->includeIndex == '1') {
            $homeUrl = rtrim($this->options->siteUrl, '/') . '/';
            $this->addUrl(
                $homeUrl,
                date('Y-m-d', $this->getLastPostTime()),
                $changefreq,
                min($priority + 0.1, 1.0)
            );
            $itemCount++;
        }

        $contentTypes = is_array($pluginOptions->contentTypes) ? $pluginOptions->contentTypes : array();

        // 添加文章
        if (in_array('post', $contentTypes) && $itemCount < $maxItems) {
            $itemCount += $this->addPosts($excludeCids, $changefreq, $priority, $maxItems - $itemCount, $includePassword);
        }

        // 添加独立页面
        if (in_array('page', $contentTypes) && $itemCount < $maxItems) {
            $itemCount += $this->addPages($excludeCids, $changefreq, $priority, $maxItems - $itemCount, $includePassword);
        }

        // 添加分类
        if (in_array('category', $contentTypes) && $itemCount < $maxItems) {
            $itemCount += $this->addCategories($changefreq, $priority - 0.1, $maxItems - $itemCount);
        }

        // 添加标签
        if (in_array('tag', $contentTypes) && $itemCount < $maxItems) {
            $itemCount += $this->addTags($changefreq, $priority - 0.2, $maxItems - $itemCount);
        }

        echo '</urlset>';
    }
    
    /**
     * 添加 URL 条目
     */
    private function addUrl($loc, $lastmod, $changefreq = 'weekly', $priority = 0.8)
    {
        echo "  <url>\n";
        echo "    <loc>" . $this->xmlEncode($loc) . "</loc>\n";
        echo "    <lastmod>" . $this->xmlEncode($lastmod) . "</lastmod>\n";
        echo "    <changefreq>" . $this->xmlEncode($changefreq) . "</changefreq>\n";
        echo "    <priority>" . number_format($priority, 1) . "</priority>\n";
        echo "  </url>\n";
    }

    /**
     * 解析排除列表
     */
    private function parseExcludeList($list)
    {
        $result = array();
        if (!empty($list)) {
            // 支持逗号分隔和换行分隔
            $items = preg_split('/[,\n]+/', $list);
            foreach ($items as $item) {
                $item = trim($item);
                if (is_numeric($item)) {
                    $result[] = intval($item);
                }
            }
        }
        return $result;
    }
    
    /**
     * 添加文章
     */
    private function addPosts($excludeCids, $changefreq, $priority, $maxItems, $includePassword)
    {
        $select = $this->db->select()->from('table.contents')
            ->where('table.contents.status = ?', 'publish')
            ->where('table.contents.type = ?', 'post')
            ->order('table.contents.created', Typecho_Db::SORT_DESC)
            ->limit($maxItems);

        // 如果不包含密码保护文章
        if (!$includePassword) {
            $select->where('(table.contents.password IS NULL OR table.contents.password = ?)', '');
        }

        $posts = $this->db->fetchAll($select);
        $count = 0;

        foreach ($posts as $post) {
            if (in_array($post['cid'], $excludeCids)) {
                continue;
            }

            $permalink = Typecho_Router::url('post', $post, $this->options->index);
            $lastmod = date('Y-m-d', $post['modified']);

            $this->addUrl($permalink, $lastmod, $changefreq, $priority);
            $count++;
        }

        return $count;
    }
    
    /**
     * 添加独立页面
     */
    private function addPages($excludeCids, $changefreq, $priority, $maxItems, $includePassword)
    {
        $select = $this->db->select()->from('table.contents')
            ->where('table.contents.status = ?', 'publish')
            ->where('table.contents.type = ?', 'page')
            ->order('table.contents.created', Typecho_Db::SORT_DESC)
            ->limit($maxItems);

        // 如果不包含密码保护文章
        if (!$includePassword) {
            $select->where('(table.contents.password IS NULL OR table.contents.password = ?)', '');
        }

        $pages = $this->db->fetchAll($select);
        $count = 0;

        foreach ($pages as $page) {
            if (in_array($page['cid'], $excludeCids)) {
                continue;
            }

            $permalink = Typecho_Router::url('page', $page, $this->options->index);
            $lastmod = date('Y-m-d', $page['modified']);

            $this->addUrl($permalink, $lastmod, $changefreq, $priority);
            $count++;
        }

        return $count;
    }
    
    /**
     * 添加分类
     */
    private function addCategories($changefreq, $priority, $maxItems)
    {
        $categories = $this->db->fetchAll(
            $this->db->select()->from('table.metas')
                ->where('table.metas.type = ?', 'category')
                ->order('table.metas.order', Typecho_Db::SORT_ASC)
                ->limit($maxItems)
        );

        $count = 0;

        foreach ($categories as $category) {
            $permalink = Typecho_Router::url('category', $category, $this->options->index);
            $lastmod = $this->getCategoryLastModified($category['mid']);

            $this->addUrl($permalink, $lastmod, $changefreq, max($priority, 0.1));
            $count++;
        }

        return $count;
    }
    
    /**
     * 添加标签
     */
    private function addTags($changefreq, $priority, $maxItems)
    {
        $tags = $this->db->fetchAll(
            $this->db->select()->from('table.metas')
                ->where('table.metas.type = ?', 'tag')
                ->order('table.metas.order', Typecho_Db::SORT_ASC)
                ->limit($maxItems)
        );

        $count = 0;

        foreach ($tags as $tag) {
            $permalink = Typecho_Router::url('tag', $tag, $this->options->index);
            $lastmod = $this->getTagLastModified($tag['mid']);

            $this->addUrl($permalink, $lastmod, $changefreq, max($priority, 0.1));
            $count++;
        }

        return $count;
    }
    
    /**
     * 获取最新文章的修改时间
     */
    private function getLastPostTime()
    {
        $post = $this->db->fetchRow(
            $this->db->select('modified')->from('table.contents')
                ->where('table.contents.status = ?', 'publish')
                ->where('table.contents.type = ?', 'post')
                ->where('(table.contents.password IS NULL OR table.contents.password = ?)', '')
                ->order('table.contents.modified', Typecho_Db::SORT_DESC)
                ->limit(1)
        );
        
        // 如果没有文章，返回当前时间
        return $post ? $post['modified'] : time();
    }
    
    /**
     * 获取分类下最新文章的修改时间
     */
    private function getCategoryLastModified($mid)
    {
        $post = $this->db->fetchRow(
            $this->db->select('table.contents.modified')->from('table.contents')
                ->join('table.relationships', 'table.contents.cid = table.relationships.cid')
                ->where('table.relationships.mid = ?', $mid)
                ->where('table.contents.status = ?', 'publish')
                ->where('table.contents.type = ?', 'post')
                ->where('(table.contents.password IS NULL OR table.contents.password = ?)', '')
                ->order('table.contents.modified', Typecho_Db::SORT_DESC)
                ->limit(1)
        );
        
        // 如果分类下没有文章，返回当前日期
        return $post ? date('Y-m-d', $post['modified']) : date('Y-m-d');
    }
    
    /**
     * 获取标签下最新文章的修改时间
     */
    private function getTagLastModified($mid)
    {
        $post = $this->db->fetchRow(
            $this->db->select('table.contents.modified')->from('table.contents')
                ->join('table.relationships', 'table.contents.cid = table.relationships.cid')
                ->where('table.relationships.mid = ?', $mid)
                ->where('table.contents.status = ?', 'publish')
                ->where('table.contents.type = ?', 'post')
                ->where('(table.contents.password IS NULL OR table.contents.password = ?)', '')
                ->order('table.contents.modified', Typecho_Db::SORT_DESC)
                ->limit(1)
        );
        
        // 如果标签下没有文章，返回当前日期
        return $post ? date('Y-m-d', $post['modified']) : date('Y-m-d');
    }
    
    /**
     * XML 实体转义
     */
    private function xmlEncode($str)
    {
        return htmlspecialchars($str, ENT_XML1, 'UTF-8');
    }

    /**
     * 百度推送接口
     */
    public function baiduPush()
    {
        $pluginOptions = $this->options->plugin('uSitemap');

        // 检查是否启用百度推送
        if (!$pluginOptions || $pluginOptions->enableBaiduPush != '1') {
            $this->response->setStatus(403);
            $this->response->throwJson(array(
                'success' => false,
                'message' => '百度推送功能未启用'
            ));
            return;
        }

        // 获取推送的URL
        $url = $this->request->get('url');

        if (empty($url)) {
            $this->response->setStatus(400);
            $this->response->throwJson(array(
                'success' => false,
                'message' => 'URL参数不能为空'
            ));
            return;
        }

        // 创建推送实例
        require_once __DIR__ . '/BaiduPusher.php';
        $pusher = uSitemap_BaiduPusher::createFromPlugin($pluginOptions);

        // 执行推送
        $result = $pusher->pushUrl($url);

        // 返回结果
        $this->response->throwJson($result);
    }

    /**
     * 百度手动推送接口（推送sitemap）
     */
    public function baiduManualPush()
    {
        $pluginOptions = $this->options->plugin('uSitemap');

        // 检查是否启用百度推送
        if (!$pluginOptions || $pluginOptions->enableBaiduPush != '1') {
            $this->response->setStatus(403);
            $this->response->throwJson(array(
                'success' => false,
                'message' => '百度推送功能未启用'
            ));
            return;
        }

        // 获取推送类型
        $pushType = isset($pluginOptions->baiduPushType) ? $pluginOptions->baiduPushType : 'api';

        // 创建推送实例
        require_once __DIR__ . '/BaiduPusher.php';
        $pusher = uSitemap_BaiduPusher::createFromPlugin($pluginOptions);

        $result = array();

        if ($pushType === 'sitemap') {
            // Sitemap推送方式
            $sitemapUrl = rtrim($this->options->siteUrl, '/') . '/sitemap.xml';
            $result = $pusher->pushSitemap($sitemapUrl);
        } else {
            // API推送方式 - 获取指定数量的URL并推送
            $count = isset($pluginOptions->baiduPushCount) ? intval($pluginOptions->baiduPushCount) : 10;
            $count = max(1, min($count, 2000)); // 限制在1-2000之间
            $urls = $this->getLatestUrls($count);

            if (empty($urls)) {
                $this->response->throwJson(array(
                    'success' => false,
                    'message' => '没有可推送的URL'
                ));
                return;
            }

            // 批量推送
            $result = $pusher->pushUrls($urls);
        }

        // 返回结果
        $this->response->throwJson($result);
    }

    /**
     * 获取最新N条URL
     */
    private function getLatestUrls($count)
    {
        $pluginOptions = $this->options->plugin('uSitemap');
        $urls = array();

        // 获取排除的内容ID
        $excludeCids = $this->parseExcludeList($pluginOptions->excludeCids);
        $includePassword = $pluginOptions->includePassword == '1';

        // 添加首页
        if ($pluginOptions->includeIndex == '1') {
            $urls[] = rtrim($this->options->siteUrl, '/') . '/';
        }

        // 获取最新的文章URL
        $remaining = $count - count($urls);
        if ($remaining > 0) {
            $urls = array_merge($urls, $this->getPostUrls($excludeCids, $includePassword, $remaining));
        }

        return $urls;
    }

    /**
     * 获取所有URL（用于批量推送）
     */
    private function getAllUrls()
    {
        $pluginOptions = $this->options->plugin('uSitemap');
        $urls = array();

        // 获取排除的内容ID
        $excludeCids = $this->parseExcludeList($pluginOptions->excludeCids);
        $includePassword = $pluginOptions->includePassword == '1';

        // 添加首页
        if ($pluginOptions->includeIndex == '1') {
            $urls[] = rtrim($this->options->siteUrl, '/') . '/';
        }

        $contentTypes = is_array($pluginOptions->contentTypes) ? $pluginOptions->contentTypes : array();

        // 添加文章
        if (in_array('post', $contentTypes)) {
            $urls = array_merge($urls, $this->getPostUrls($excludeCids, $includePassword));
        }

        // 添加独立页面
        if (in_array('page', $contentTypes)) {
            $urls = array_merge($urls, $this->getPageUrls($excludeCids, $includePassword));
        }

        // 添加分类
        if (in_array('category', $contentTypes)) {
            $urls = array_merge($urls, $this->getCategoryUrls());
        }

        // 添加标签
        if (in_array('tag', $contentTypes)) {
            $urls = array_merge($urls, $this->getTagUrls());
        }

        return $urls;
    }

    /**
     * 获取文章URLs
     */
    private function getPostUrls($excludeCids, $includePassword, $limit = 2000)
    {
        $select = $this->db->select()->from('table.contents')
            ->where('table.contents.status = ?', 'publish')
            ->where('table.contents.type = ?', 'post')
            ->order('table.contents.created', Typecho_Db::SORT_DESC)
            ->limit($limit);

        if (!$includePassword) {
            $select->where('(table.contents.password IS NULL OR table.contents.password = ?)', '');
        }

        $posts = $this->db->fetchAll($select);
        $urls = array();

        foreach ($posts as $post) {
            if (in_array($post['cid'], $excludeCids)) {
                continue;
            }

            $urls[] = Typecho_Router::url('post', $post, $this->options->index);
        }

        return $urls;
    }

    /**
     * 获取页面URLs
     */
    private function getPageUrls($excludeCids, $includePassword)
    {
        $select = $this->db->select()->from('table.contents')
            ->where('table.contents.status = ?', 'publish')
            ->where('table.contents.type = ?', 'page')
            ->order('table.contents.created', Typecho_Db::SORT_DESC)
            ->limit(2000);

        if (!$includePassword) {
            $select->where('(table.contents.password IS NULL OR table.contents.password = ?)', '');
        }

        $pages = $this->db->fetchAll($select);
        $urls = array();

        foreach ($pages as $page) {
            if (in_array($page['cid'], $excludeCids)) {
                continue;
            }

            $urls[] = Typecho_Router::url('page', $page, $this->options->index);
        }

        return $urls;
    }

    /**
     * 获取分类URLs
     */
    private function getCategoryUrls()
    {
        $categories = $this->db->fetchAll(
            $this->db->select()->from('table.metas')
                ->where('table.metas.type = ?', 'category')
                ->order('table.metas.order', Typecho_Db::SORT_ASC)
                ->limit(2000)
        );

        $urls = array();

        foreach ($categories as $category) {
            $urls[] = Typecho_Router::url('category', $category, $this->options->index);
        }

        return $urls;
    }

    /**
     * 获取标签URLs
     */
    private function getTagUrls()
    {
        $tags = $this->db->fetchAll(
            $this->db->select()->from('table.metas')
                ->where('table.metas.type = ?', 'tag')
                ->order('table.metas.order', Typecho_Db::SORT_ASC)
                ->limit(2000)
        );

        $urls = array();

        foreach ($tags as $tag) {
            $urls[] = Typecho_Router::url('tag', $tag, $this->options->index);
        }

        return $urls;
    }

    /**
     * 获取推送日志
     */
    public function getLogs()
    {
        $logDir = __DIR__ . '/logs';
        $logs = array();

        if (is_dir($logDir)) {
            $files = glob($logDir . '/baidu_push_*.log');
            rsort($files); // 按日期倒序

            foreach ($files as $file) {
                $content = @file_get_contents($file);
                if ($content) {
                    $date = preg_replace('/.*baidu_push_(\d{8})\.log/', '$1', basename($file));
                    $logs[] = array(
                        'date' => $date,
                        'content' => $content
                    );
                }
            }
        }

        $this->response->throwJson(array(
            'success' => true,
            'logs' => $logs
        ));
    }

    /**
     * 清空推送日志
     */
    public function clearLogs()
    {
        $logDir = __DIR__ . '/logs';

        if (is_dir($logDir)) {
            $files = glob($logDir . '/*_push_*.log');
            foreach ($files as $file) {
                @unlink($file);
            }
        }

        $this->response->throwJson(array(
            'success' => true,
            'message' => '日志已清空'
        ));
    }

    /**
     * Google推送接口
     */
    public function googlePush()
    {
        $pluginOptions = $this->options->plugin('uSitemap');

        // 检查是否启用Google推送
        if (!$pluginOptions || $pluginOptions->enableGooglePush != '1') {
            $this->response->setStatus(403);
            $this->response->throwJson(array(
                'success' => false,
                'message' => 'Google推送功能未启用'
            ));
            return;
        }

        // 获取推送的URL
        $url = $this->request->get('url');

        if (empty($url)) {
            $this->response->setStatus(400);
            $this->response->throwJson(array(
                'success' => false,
                'message' => 'URL参数不能为空'
            ));
            return;
        }

        // 创建推送实例
        require_once __DIR__ . '/GooglePusher.php';
        $pusher = uSitemap_GooglePusher::createFromPlugin($pluginOptions);

        // 执行推送
        $result = $pusher->pushUrl($url);

        // 返回结果
        $this->response->throwJson($result);
    }

    /**
     * Google手动推送接口（推送sitemap）
     */
    public function googleManualPush()
    {
        $pluginOptions = $this->options->plugin('uSitemap');

        // 检查是否启用Google推送
        if (!$pluginOptions || $pluginOptions->enableGooglePush != '1') {
            $this->response->setStatus(403);
            $this->response->throwJson(array(
                'success' => false,
                'message' => 'Google推送功能未启用'
            ));
            return;
        }

        // 创建推送实例
        require_once __DIR__ . '/GooglePusher.php';
        $pusher = uSitemap_GooglePusher::createFromPlugin($pluginOptions);

        // 获取推送数量
        $count = isset($pluginOptions->googlePushCount) ? intval($pluginOptions->googlePushCount) : 10;
        $count = max(1, min($count, 1000)); // 限制在1-1000之间
        $urls = $this->getLatestUrls($count);

        if (empty($urls)) {
            $this->response->throwJson(array(
                'success' => false,
                'message' => '没有可推送的URL'
            ));
            return;
        }

        // 批量推送
        $result = $pusher->pushUrls($urls);

        // 返回结果
        $this->response->throwJson($result);
    }

    /**
     * Bing推送接口
     */
    public function bingPush()
    {
        $pluginOptions = $this->options->plugin('uSitemap');

        // 检查是否启用Bing推送
        if (!$pluginOptions || $pluginOptions->enableBingPush != '1') {
            $this->response->setStatus(403);
            $this->response->throwJson(array(
                'success' => false,
                'message' => 'Bing推送功能未启用'
            ));
            return;
        }

        // 获取推送的URL
        $url = $this->request->get('url');

        if (empty($url)) {
            $this->response->setStatus(400);
            $this->response->throwJson(array(
                'success' => false,
                'message' => 'URL参数不能为空'
            ));
            return;
        }

        // 创建推送实例
        require_once __DIR__ . '/BingPusher.php';
        $pusher = uSitemap_BingPusher::createFromPlugin($pluginOptions);

        // 执行推送
        $result = $pusher->pushUrl($url);

        // 返回结果
        $this->response->throwJson($result);
    }

    /**
     * Bing手动推送接口（推送sitemap）
     */
    public function bingManualPush()
    {
        $pluginOptions = $this->options->plugin('uSitemap');

        // 检查是否启用Bing推送
        if (!$pluginOptions || $pluginOptions->enableBingPush != '1') {
            $this->response->setStatus(403);
            $this->response->throwJson(array(
                'success' => false,
                'message' => 'Bing推送功能未启用'
            ));
            return;
        }

        // 创建推送实例
        require_once __DIR__ . '/BingPusher.php';
        $pusher = uSitemap_BingPusher::createFromPlugin($pluginOptions);

        // 获取推送数量
        $count = isset($pluginOptions->bingPushCount) ? intval($pluginOptions->bingPushCount) : 10;
        $count = max(1, min($count, 1000)); // 限制在1-1000之间
        $urls = $this->getLatestUrls($count);

        if (empty($urls)) {
            $this->response->throwJson(array(
                'success' => false,
                'message' => '没有可推送的URL'
            ));
            return;
        }

        // 批量推送
        $result = $pusher->pushUrls($urls);

        // 返回结果
        $this->response->throwJson($result);
    }

    /**
     * 搜狗推送接口
     */
    public function sogouPush()
    {
        $pluginOptions = $this->options->plugin('uSitemap');

        // 检查是否启用搜狗推送
        if (!$pluginOptions || $pluginOptions->enableSogouPush != '1') {
            $this->response->setStatus(403);
            $this->response->throwJson(array(
                'success' => false,
                'message' => '搜狗推送功能未启用'
            ));
            return;
        }

        // 获取推送的URL
        $url = $this->request->get('url');

        if (empty($url)) {
            $this->response->setStatus(400);
            $this->response->throwJson(array(
                'success' => false,
                'message' => 'URL参数不能为空'
            ));
            return;
        }

        // 创建推送实例
        require_once __DIR__ . '/SogouPusher.php';
        $pusher = uSitemap_SogouPusher::createFromPlugin($pluginOptions);

        // 执行推送
        $result = $pusher->pushUrl($url);

        // 返回结果
        $this->response->throwJson($result);
    }

    /**
     * 搜狗手动推送接口（推送sitemap）
     */
    public function sogouManualPush()
    {
        $pluginOptions = $this->options->plugin('uSitemap');

        // 检查是否启用搜狗推送
        if (!$pluginOptions || $pluginOptions->enableSogouPush != '1') {
            $this->response->setStatus(403);
            $this->response->throwJson(array(
                'success' => false,
                'message' => '搜狗推送功能未启用'
            ));
            return;
        }

        // 获取推送类型
        $pushType = isset($pluginOptions->sogouPushType) ? $pluginOptions->sogouPushType : 'api';

        // 创建推送实例
        require_once __DIR__ . '/SogouPusher.php';
        $pusher = uSitemap_SogouPusher::createFromPlugin($pluginOptions);

        $result = array();

        if ($pushType === 'sitemap') {
            // Sitemap推送方式
            $sitemapUrl = rtrim($this->options->siteUrl, '/') . '/sitemap.xml';
            $result = $pusher->pushSitemap($sitemapUrl);
        } else {
            // API推送方式 - 获取指定数量的URL并推送
            $count = isset($pluginOptions->sogouPushCount) ? intval($pluginOptions->sogouPushCount) : 10;
            $count = max(1, min($count, 1000)); // 限制在1-1000之间
            $urls = $this->getLatestUrls($count);

            if (empty($urls)) {
                $this->response->throwJson(array(
                    'success' => false,
                    'message' => '没有可推送的URL'
                ));
                return;
            }

            // 批量推送
            $result = $pusher->pushUrls($urls);
        }

        // 返回结果
        $this->response->throwJson($result);
    }

    /**
     * 360推送接口
     */
    public function push360Push()
    {
        $pluginOptions = $this->options->plugin('uSitemap');

        // 检查是否启用360推送
        if (!$pluginOptions || $pluginOptions->enablePush360Push != '1') {
            $this->response->setStatus(403);
            $this->response->throwJson(array(
                'success' => false,
                'message' => '360推送功能未启用'
            ));
            return;
        }

        // 获取推送的URL
        $url = $this->request->get('url');

        if (empty($url)) {
            $this->response->setStatus(400);
            $this->response->throwJson(array(
                'success' => false,
                'message' => 'URL参数不能为空'
            ));
            return;
        }

        // 创建推送实例
        require_once __DIR__ . '/Push360Pusher.php';
        $pusher = uSitemap_Push360Pusher::createFromPlugin($pluginOptions);

        // 执行推送
        $result = $pusher->pushUrl($url);

        // 返回结果
        $this->response->throwJson($result);
    }

    /**
     * 360手动推送接口（推送sitemap）
     */
    public function push360ManualPush()
    {
        $pluginOptions = $this->options->plugin('uSitemap');

        // 检查是否启用360推送
        if (!$pluginOptions || $pluginOptions->enablePush360Push != '1') {
            $this->response->setStatus(403);
            $this->response->throwJson(array(
                'success' => false,
                'message' => '360推送功能未启用'
            ));
            return;
        }

        // 获取推送类型
        $pushType = isset($pluginOptions->push360PushType) ? $pluginOptions->push360PushType : 'api';

        // 创建推送实例
        require_once __DIR__ . '/Push360Pusher.php';
        $pusher = uSitemap_Push360Pusher::createFromPlugin($pluginOptions);

        $result = array();

        if ($pushType === 'sitemap') {
            // Sitemap推送方式
            $sitemapUrl = rtrim($this->options->siteUrl, '/') . '/sitemap.xml';
            $result = $pusher->pushSitemap($sitemapUrl);
        } else {
            // API推送方式 - 获取指定数量的URL并推送
            $count = isset($pluginOptions->push360PushCount) ? intval($pluginOptions->push360PushCount) : 10;
            $count = max(1, min($count, 1000)); // 限制在1-1000之间
            $urls = $this->getLatestUrls($count);

            if (empty($urls)) {
                $this->response->throwJson(array(
                    'success' => false,
                    'message' => '没有可推送的URL'
                ));
                return;
            }

            // 批量推送
            $result = $pusher->pushUrls($urls);
        }

        // 返回结果
        $this->response->throwJson($result);
    }
} 