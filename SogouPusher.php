<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

/**
 * 搜狗推送类
 *
 * 负责处理与搜狗站长平台的交互
 */
class uSitemap_SogouPusher extends uSitemap_SearchEnginePusher
{
    /**
     * API推送接口地址
     */
    const API_PUSH_URL = 'http://fankui.help.sogou.com/index.php/push';

    /**
     * 构造函数
     *
     * @param array $config 配置数组
     */
    public function __construct($config = array())
    {
        parent::__construct($config);
        $this->engineName = 'Sogou';
    }

    /**
     * 检查配置是否完整
     *
     * @return bool
     */
    public function isConfigured()
    {
        $token = $this->getConfig('token');

        return !empty($token);
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
            return $this->error('搜狗推送配置不完整，请检查Token设置');
        }

        if (empty($url)) {
            return $this->error('URL不能为空');
        }

        $urls = array($url);
        return $this->pushUrls($urls);
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
            return $this->error('搜狗推送配置不完整，请检查Token设置');
        }

        if (empty($urls) || !is_array($urls)) {
            return $this->error('URLs不能为空且必须是数组');
        }

        // 搜狗API推送一次最多1000条
        $urls = array_slice($urls, 0, 1000);

        return $this->sendRequest($this->buildApiUrl(), $urls);
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
            return $this->error('搜狗推送配置不完整，请检查Token设置');
        }

        if (empty($sitemapUrl)) {
            return $this->error('Sitemap URL不能为空');
        }

        // 搜狗Sitemap推送
        $apiUrl = $this->buildApiUrl();
        $apiUrl .= '&sitemap=' . urlencode($sitemapUrl);

        return $this->sendRequest($apiUrl, array(), 'GET');
    }

    /**
     * 构建API URL
     *
     * @return string
     */
    private function buildApiUrl()
    {
        $token = $this->getConfig('token');

        return self::API_PUSH_URL . '?token=' . urlencode($token);
    }

    /**
     * 发送HTTP请求（重写以适配搜狗的特殊格式）
     *
     * @param string $url API地址
     * @param array $urls 要推送的URL数组
     * @param string $method 请求方法
     * @return array
     */
    protected function sendRequest($url, $urls = array(), $method = 'POST')
    {
        $result = array(
            'success' => false,
            'message' => '',
            'data' => array()
        );

        try {
            if ($method === 'POST' && !empty($urls)) {
                // POST请求，urls按行分隔
                $postData = implode("\n", $urls);

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                    'Content-Type: text/plain'
                ));
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);

                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $error = curl_error($ch);
                curl_close($ch);

                if ($error) {
                    return $this->error('请求失败: ' . $error);
                }

                if ($httpCode !== 200) {
                    return $this->error('HTTP请求失败，状态码: ' . $httpCode);
                }
            } else {
                // GET请求
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);

                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $error = curl_error($ch);
                curl_close($ch);

                if ($error) {
                    return $this->error('请求失败: ' . $error);
                }

                if ($httpCode !== 200) {
                    return $this->error('HTTP请求失败，状态码: ' . $httpCode);
                }
            }

            // 解析响应
            $result = $this->parseResponse($response);

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
     * @param array $data 搜狗返回的数据
     * @return string
     */
    protected function formatSuccessMessage($data)
    {
        $message = '推送成功';

        if (isset($data['success'])) {
            $successCount = (int)$data['success'];
            $message = '成功推送 ' . $successCount . ' 条';

            if (isset($data['remain'])) {
                $message .= '，今日剩余配额: ' . $data['remain'];
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
            'token' => isset($pluginOptions->sogouToken) ? $pluginOptions->sogouToken : '',
            'type' => isset($pluginOptions->sogouPushType) ? $pluginOptions->sogouPushType : 'api',
            'triggers' => isset($pluginOptions->sogouPushTrigger) ? $pluginOptions->sogouPushTrigger : array()
        );

        return new self($config);
    }
}
