<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

/**
 * Bing推送类
 *
 * 负责处理与Bing Webmaster Tools的交互
 */
class uSitemap_BingPusher extends uSitemap_SearchEnginePusher
{
    /**
     * API推送接口地址
     */
    const API_PUSH_URL = 'https://ssl.bing.com/webmaster/api.svc/json/SubmitUrl';

    /**
     * 构造函数
     *
     * @param array $config 配置数组
     */
    public function __construct($config = array())
    {
        parent::__construct($config);
        $this->engineName = 'Bing';
    }

    /**
     * 检查配置是否完整
     *
     * @return bool
     */
    public function isConfigured()
    {
        $apiKey = $this->getConfig('apiKey');
        $siteUrl = $this->getConfig('siteUrl');

        return !empty($apiKey) && !empty($siteUrl);
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
            return $this->error('Bing推送配置不完整，请检查API密钥和站点URL设置');
        }

        if (empty($url)) {
            return $this->error('URL不能为空');
        }

        return $this->sendSubmitRequest($url);
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
            return $this->error('Bing推送配置不完整，请检查API密钥和站点URL设置');
        }

        if (empty($urls) || !is_array($urls)) {
            return $this->error('URLs不能为空且必须是数组');
        }

        $results = array();
        $successCount = 0;
        $failCount = 0;

        // Bing API需要逐个推送
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
            return $this->error('Bing推送配置不完整，请检查API密钥和站点URL设置');
        }

        if (empty($sitemapUrl)) {
            return $this->error('Sitemap URL不能为空');
        }

        // Bing不直接支持通过API提交sitemap
        // 需要在Bing Webmaster Tools手动提交
        return array(
            'success' => true,
            'message' => '请在Bing Webmaster Tools中手动提交Sitemap',
            'data' => array(
                'sitemap_url' => $sitemapUrl,
                'note' => '访问 https://www.bing.com/webmasters 提交sitemap'
            )
        );
    }

    /**
     * 发送提交请求
     *
     * @param string $url 要推送的URL
     * @return array
     */
    private function sendSubmitRequest($url)
    {
        $apiKey = $this->getConfig('apiKey');
        $siteUrl = $this->getConfig('siteUrl');

        // 构建API URL
        $apiUrl = 'https://ssl.bing.com/webmaster/api.svc/json/SubmitUrl?apikey=' . urlencode($apiKey);

        $data = array(
            'siteUrl' => rtrim($siteUrl, '/'),
            'url' => $url
        );

        $headers = array(
            'Content-Type: application/json'
        );

        return $this->sendRequest($apiUrl, $data, 'POST', $headers);
    }

    /**
     * 格式化成功消息
     *
     * @param array $data Bing返回的数据
     * @return string
     */
    protected function formatSuccessMessage($data)
    {
        $message = '推送成功';

        if (isset($data['d'])) {
            if (isset($data['d']['success']) && $data['d']['success'] === true) {
                $message = 'URL已成功提交';
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
            'apiKey' => isset($pluginOptions->bingApiKey) ? $pluginOptions->bingApiKey : '',
            'siteUrl' => isset($pluginOptions->bingSiteUrl) ? $pluginOptions->bingSiteUrl : '',
            'type' => isset($pluginOptions->bingPushType) ? $pluginOptions->bingPushType : 'api',
            'triggers' => isset($pluginOptions->bingPushTrigger) ? $pluginOptions->bingPushTrigger : array()
        );

        return new self($config);
    }
}
