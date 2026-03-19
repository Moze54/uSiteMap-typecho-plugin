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
} 