<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

/**
 * Bing推送类 (使用 IndexNow API)
 *
 * 负责处理与 Bing IndexNow API 的交互
 */
class uSitemap_BingPusher extends uSitemap_SearchEnginePusher
{
    /**
     * IndexNow API 推送接口地址
     */
    const INDEXNOW_API_URL = 'https://api.indexnow.org/indexnow';

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
        $indexnowKey = $this->getConfig('indexnowKey');

        return !empty($indexnowKey);
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
            return $this->error('Bing推送配置不完整，请检查IndexNow Key设置');
        }

        if (empty($url)) {
            return $this->error('URL不能为空');
        }

        return $this->sendIndexnowRequest($url);
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
            return $this->error('Bing推送配置不完整，请检查IndexNow Key设置');
        }

        if (empty($urls) || !is_array($urls)) {
            return $this->error('URLs不能为空且必须是数组');
        }

        // IndexNow 支持批量推送，最多10,000个URL
        $urls = array_slice($urls, 0, 10000);

        return $this->sendBatchIndexnowRequest($urls);
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
            return $this->error('Bing推送配置不完整，请检查IndexNow Key设置');
        }

        if (empty($sitemapUrl)) {
            return $this->error('Sitemap URL不能为空');
        }

        // IndexNow不直接支持sitemap提交
        // 返回提示信息
        return array(
            'success' => true,
            'message' => 'IndexNow API 不支持Sitemap提交，请使用URL推送方式',
            'data' => array(
                'sitemap_url' => $sitemapUrl,
                'note' => '建议在发布新内容时自动推送URL'
            )
        );
    }

    /**
     * 发送IndexNow单个URL提交请求
     *
     * @param string $url 要推送的URL
     * @return array
     */
    private function sendIndexnowRequest($url)
    {
        $indexnowKey = $this->getConfig('indexnowKey');
        $host = $this->getHostFromUrl($url);

        // 构建API URL
        $apiUrl = self::INDEXNOW_API_URL;
        $apiUrl .= '?url=' . urlencode($url);
        $apiUrl .= '&key=' . urlencode($indexnowKey);

        return $this->sendGetRequest($apiUrl);
    }

    /**
     * 发送IndexNow批量URL提交请求
     *
     * @param array $urls 要推送的URL数组
     * @return array
     */
    private function sendBatchIndexnowRequest($urls)
    {
        $indexnowKey = $this->getConfig('indexnowKey');

        if (empty($urls)) {
            return $this->error('URL列表不能为空');
        }

        // 获取主机名（使用第一个URL的主机名）
        $host = $this->getHostFromUrl($urls[0]);

        // 构建批量提交数据
        $data = array(
            'host' => $host,
            'key' => $indexnowKey,
            'urlList' => $urls
        );

        $headers = array(
            'Content-Type: application/json'
        );

        return $this->sendPostRequest(self::INDEXNOW_API_URL, $data, $headers);
    }

    /**
     * 从URL中提取主机名
     *
     * @param string $url URL地址
     * @return string
     */
    private function getHostFromUrl($url)
    {
        $parsed = parse_url($url);
        return isset($parsed['host']) ? $parsed['host'] : '';
    }

    /**
     * 发送GET请求
     *
     * @param string $url 请求URL
     * @return array
     */
    private function sendGetRequest($url)
    {
        $result = array(
            'success' => false,
            'message' => '',
            'data' => array()
        );

        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; uSitemap/1.0)');

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                return $this->error('请求失败: ' . $error);
            }

            // IndexNow返回200表示成功
            if ($httpCode === 200) {
                $result['success'] = true;
                $result['message'] = 'URL已成功提交到IndexNow';
                $result['data'] = array('response' => $response);
            } elseif ($httpCode === 202) {
                $result['success'] = true;
                $result['message'] = 'URL已被IndexNow接受（正在处理）';
                $result['data'] = array('response' => $response);
            } elseif ($httpCode === 400) {
                $result['success'] = false;
                $result['message'] = '请求错误：请检查IndexNow Key配置';
                $result['data'] = array('response' => $response);
            } elseif ($httpCode === 403) {
                $result['success'] = false;
                $result['message'] = '验证失败：请确保Key文件已正确放置在网站根目录';
                $result['data'] = array('response' => $response);
            } elseif ($httpCode === 422) {
                $result['success'] = false;
                $result['message'] = '请求无效：请检查URL格式和Key配置';
                $result['data'] = array('response' => $response);
            } else {
                $result['success'] = false;
                $result['message'] = 'HTTP请求失败，状态码: ' . $httpCode;
                $result['data'] = array('response' => $response);
            }

            // 记录日志
            $this->log($result, !$result['success']);

        } catch (Exception $e) {
            $result['success'] = false;
            $result['message'] = '推送异常: ' . $e->getMessage();
            $result['data'] = array();
            $this->log($result, true);
        }

        return $result;
    }

    /**
     * 发送POST请求
     *
     * @param string $url 请求URL
     * @param array $data 请求数据
     * @param array $headers 请求头
     * @return array
     */
    private function sendPostRequest($url, $data, $headers = array())
    {
        $result = array(
            'success' => false,
            'message' => '',
            'data' => array()
        );

        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; uSitemap/1.0)');

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                return $this->error('请求失败: ' . $error);
            }

            // IndexNow返回200表示成功
            if ($httpCode === 200) {
                $result['success'] = true;
                $result['message'] = '已成功提交 ' . count($data['urlList']) . ' 个URL到IndexNow';
                $result['data'] = array('response' => $response);
            } elseif ($httpCode === 202) {
                $result['success'] = true;
                $result['message'] = 'URL已被IndexNow接受（正在处理）';
                $result['data'] = array('response' => $response);
            } elseif ($httpCode === 400) {
                $result['success'] = false;
                $result['message'] = '请求错误：请检查IndexNow Key配置';
                $result['data'] = array('response' => $response);
            } elseif ($httpCode === 403) {
                $result['success'] = false;
                $result['message'] = '验证失败：请确保Key文件已正确放置在网站根目录';
                $result['data'] = array('response' => $response);
            } elseif ($httpCode === 422) {
                $result['success'] = false;
                $result['message'] = '请求无效：请检查URL格式和Key配置';
                $result['data'] = array('response' => $response);
            } else {
                $result['success'] = false;
                $result['message'] = 'HTTP请求失败，状态码: ' . $httpCode;
                $result['data'] = array('response' => $response);
            }

            // 记录日志
            $this->log($result, !$result['success']);

        } catch (Exception $e) {
            $result['success'] = false;
            $result['message'] = '推送异常: ' . $e->getMessage();
            $result['data'] = array();
            $this->log($result, true);
        }

        return $result;
    }

    /**
     * 格式化成功消息
     *
     * @param array $data IndexNow返回的数据
     * @return string
     */
    protected function formatSuccessMessage($data)
    {
        return 'URL已成功提交到IndexNow';
    }

    /**
     * 记录日志
     *
     * @param array $result 推送结果
     * @param bool $isError 是否是错误日志
     * @return void
     */
    protected function log($result, $isError = false)
    {
        $logDir = __DIR__ . '/logs';
        $logFile = $logDir . '/bing_push_' . date('Ymd') . '.log';

        // 创建日志目录
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }

        $timestamp = date('Y-m-d H:i:s');
        $level = $isError ? 'ERROR' : 'INFO';

        $logMessage = sprintf(
            "[%s] [%s] %s\n",
            $timestamp,
            $level,
            $result['message']
        );

        if (!empty($result['data'])) {
            $logMessage .= sprintf(
                "[DATA] %s\n",
                json_encode($result['data'], JSON_UNESCAPED_UNICODE)
            );
        }

        @file_put_contents($logFile, $logMessage, FILE_APPEND);
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
            'indexnowKey' => isset($pluginOptions->bingIndexnowKey) ? $pluginOptions->bingIndexnowKey : '',
            'type' => isset($pluginOptions->bingPushType) ? $pluginOptions->bingPushType : 'api',
            'triggers' => isset($pluginOptions->bingPushTrigger) ? $pluginOptions->bingPushTrigger : array()
        );

        return new self($config);
    }
}
