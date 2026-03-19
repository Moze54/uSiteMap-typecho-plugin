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
        $pluginOptions = $this->options->plugin('Sitemap');
        if (!$pluginOptions) {
            $this->response->setStatus(404);
            echo 'Sitemap plugin is not configured.';
            return;
        }
        
        // 设置 HTTP 头
        $this->response->setContentType('application/xml');
        
        // 获取排除的内容ID
        $excludeCids = array();
        if (!empty($pluginOptions->excludeCids)) {
            $excludeLines = explode("\n", $pluginOptions->excludeCids);
            foreach ($excludeLines as $line) {
                $cid = trim($line);
                if (is_numeric($cid)) {
                    $excludeCids[] = intval($cid);
                }
            }
        }
        
        // 开始输出 XML
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        
        // 添加首页
        if ($pluginOptions->includeIndex === 'true') {
            // 确保首页 URL 末尾有斜杠
            $homeUrl = rtrim($this->options->siteUrl, '/') . '/';
            $this->addUrl(
                $homeUrl,
                date('Y-m-d', $this->getLastPostTime())
            );
        }
        
        $contentTypes = $pluginOptions->contentTypes;
        
        // 添加文章
        if (in_array('post', $contentTypes)) {
            $this->addPosts($pluginOptions, $excludeCids);
        }
        
        // 添加独立页面
        if (in_array('page', $contentTypes)) {
            $this->addPages($pluginOptions, $excludeCids);
        }
        
        // 添加分类
        if (in_array('category', $contentTypes)) {
            $this->addCategories($pluginOptions);
        }
        
        // 添加标签
        if (in_array('tag', $contentTypes)) {
            $this->addTags($pluginOptions);
        }
        
        echo '</urlset>';
    }
    
    /**
     * 添加 URL 条目
     */
    private function addUrl($loc, $lastmod)
    {
        echo "  <url>\n";
        echo "    <loc>" . $this->xmlEncode($loc) . "</loc>\n";
        echo "    <lastmod>" . $this->xmlEncode($lastmod) . "</lastmod>\n";
        echo "  </url>\n";
    }
    
    /**
     * 添加文章
     */
    private function addPosts($pluginOptions, $excludeCids)
    {
        $posts = $this->db->fetchAll(
            $this->db->select()->from('table.contents')
                ->where('table.contents.status = ?', 'publish')
                ->where('table.contents.type = ?', 'post')
                ->where('(table.contents.password IS NULL OR table.contents.password = ?)', '')
                ->order('table.contents.created', Typecho_Db::SORT_DESC)
        );
        
        foreach ($posts as $post) {
            if (in_array($post['cid'], $excludeCids)) {
                continue;
            }
            
            $permalink = Typecho_Router::url('post', $post, $this->options->index);
            $lastmod = date('Y-m-d', $post['modified']);
            
            $this->addUrl(
                $permalink,
                $lastmod
            );
        }
    }
    
    /**
     * 添加独立页面
     */
    private function addPages($pluginOptions, $excludeCids)
    {
        $pages = $this->db->fetchAll(
            $this->db->select()->from('table.contents')
                ->where('table.contents.status = ?', 'publish')
                ->where('table.contents.type = ?', 'page')
                ->where('(table.contents.password IS NULL OR table.contents.password = ?)', '')
                ->order('table.contents.created', Typecho_Db::SORT_DESC)
        );
        
        foreach ($pages as $page) {
            if (in_array($page['cid'], $excludeCids)) {
                continue;
            }
            
            $permalink = Typecho_Router::url('page', $page, $this->options->index);
            $lastmod = date('Y-m-d', $page['modified']);
            
            $this->addUrl(
                $permalink,
                $lastmod
            );
        }
    }
    
    /**
     * 添加分类
     */
    private function addCategories($pluginOptions)
    {
        $categories = $this->db->fetchAll(
            $this->db->select()->from('table.metas')
                ->where('table.metas.type = ?', 'category')
                ->order('table.metas.order', Typecho_Db::SORT_ASC)
        );
        
        foreach ($categories as $category) {
            $permalink = Typecho_Router::url('category', $category, $this->options->index);
            // 获取该分类下最新文章的修改时间
            $lastmod = $this->getCategoryLastModified($category['mid']);
            
            $this->addUrl(
                $permalink,
                $lastmod
            );
        }
    }
    
    /**
     * 添加标签
     */
    private function addTags($pluginOptions)
    {
        $tags = $this->db->fetchAll(
            $this->db->select()->from('table.metas')
                ->where('table.metas.type = ?', 'tag')
                ->order('table.metas.order', Typecho_Db::SORT_ASC)
        );
        
        foreach ($tags as $tag) {
            $permalink = Typecho_Router::url('tag', $tag, $this->options->index);
            // 获取该标签下最新文章的修改时间
            $lastmod = $this->getTagLastModified($tag['mid']);
            
            $this->addUrl(
                $permalink,
                $lastmod
            );
        }
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