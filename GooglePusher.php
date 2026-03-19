<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

/**
 * Google推送类
 *
 * 负责处理与Google Search Console的交互
 */
class uSitemap_GooglePusher extends uSitemap_SearchEnginePusher
{
    /**
     * Indexing API端点
     */
    const INDEXING_API_URL = 'https://indexing.googleapis.com/v3/urlNotifications:publish';

    /**
     * 构造函数
     *
     * @param array $config 配置数组
     */
    public function __construct($config = array())
    {
        parent::__construct($config);
        $this->engineName = 'Google';
    }

    /**
     * 检查配置是否完整
     *
     * @return bool
     */
    public function isConfigured()
    {
        $apiKey = $this->getConfig('apiKey');

        return !empty($apiKey);
    }

    /**
     * API推送单个URL
     *
     * @param string $url 要推送的URL
     * @return array 返回推送结果
     */
    public function pushUrl($url)
    {
        if (!$this->isConfigured()) {
            return $this->error('Google推送配置不完整，请检查API密钥设置');
        }

        if (empty($url)) {
            return $this->error('URL不能为空');
        }

        return $this->sendIndexingRequest($url, 'URL_UPDATED');
    }

    /**
     * API批量推送URLs
     *
     * @param array $urls 要推送的URL数组
     * @return array 返回推送结果
     */
    public function pushUrls($urls)
    {
        if (!$this->isConfigured()) {
            return $this->error('Google推送配置不完整，请检查API密钥设置');
        }

        if (empty($urls) || !is_array($urls)) {
            return $this->error('URLs不能为空且必须是数组');
        }

        $results = array();
        $successCount = 0;
        $failCount = 0;

        // Google Indexing API需要逐个推送
        foreach ($urls as $url) {
            $result = $this->pushUrl($url);
            $results[] = $result;

            if ($result['success']) {
                $successCount++;
            } else {
                $failCount++;
            }
        }

        return array(
            'success' => $failCount === 0,
            'message' => sprintf('成功推送 %d 条，失败 %d 条', $successCount, $failCount),
            'data' => $results
        );
    }

    /**
     * Sitemap推送
     *
     * @param string $sitemapUrl Sitemap地址
     * @return array 返回推送结果
     */
    public function pushSitemap($sitemapUrl)
    {
        if (!$this->isConfigured()) {
            return $this->error('Google推送配置不完整，请检查API密钥设置');
        }

        if (empty($sitemapUrl)) {
            return $this->error('Sitemap URL不能为空');
        }

        // Google不直接支持通过API提交sitemap
        // 需要在Google Search Console手动提交
        return array(
            'success' => true,
            'message' => '请在Google Search Console中手动提交Sitemap',
            'data' => array(
                'sitemap_url' => $sitemapUrl,
                'note' => '访问 https://search.google.com/search-console 提交sitemap'
            )
        );
    }

    /**
     * 发送Indexing API请求
     *
     * @param string $url 要推送的URL
     * @param string $type 类型 (URL_UPDATED/URL_DELETED)
     * @return array
     */
    private function sendIndexingRequest($url, $type = 'URL_UPDATED')
    {
        $apiKey = $this->getConfig('apiKey');

        $apiUrl = self::INDEXING_API_URL . '?key=' . urlencode($apiKey);

        $data = array(
            'url' => $url,
            'type' => $type
        );

        $headers = array(
            'Content-Type: application/json'
        );

        return $this->sendRequest($apiUrl, $data, 'POST', $headers);
    }

    /**
     * 格式化成功消息
     *
     * @param array $data Google返回的数据
     * @return string
     */
    protected function formatSuccessMessage($data)
    {
        $message = '推送成功';

        if (isset($data['urlNotificationMetadata'])) {
            $metadata = $data['urlNotificationMetadata'];
            if (isset($metadata['latestUpdate']['notifyTime'])) {
                $message .= '，通知时间: ' . $metadata['latestUpdate']['notifyTime'];
            }
        }

        return $message;
    }

    /**
     * 从插件配置创建实例
     *
     * @param Typecho_Config $pluginOptions 插件配置
     * @return self
     */
    public static function createFromPlugin($pluginOptions)
    {
        $config = array(
            'apiKey' => isset($pluginOptions->googleApiKey) ? $pluginOptions->googleApiKey : '',
            'type' => isset($pluginOptions->googlePushType) ? $pluginOptions->googlePushType : 'api',
            'triggers' => isset($pluginOptions->googlePushTrigger) ? $pluginOptions->googlePushTrigger : array()
        );

        return new self($config);
    }
}
