<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

/**
 * 百度推送类
 *
 * 负责处理与百度站长平台的交互
 */
class uSitemap_BaiduPusher
{
    /**
     * API推送接口地址
     */
    const API_PUSH_URL = 'http://data.zz.baidu.com/urls';

    /**
     * Sitemap推送接口地址
     */
    const SITEMAP_PUSH_URL = 'http://data.zz.baidu.com/url';

    /**
     * 推送接口
     */
    const PUSH_ENDPOINT = 'site=%s&token=%s';

    /**
     * 配置选项
     * @var array
     */
    private $config;

    /**
     * 构造函数
     *
     * @param array $config 配置数组
     */
    public function __construct($config = array())
    {
        $this->config = $config;
    }

    /**
     * 设置配置
     *
     * @param string $key 配置键
     * @param mixed $value 配置值
     * @return void
     */
    public function setConfig($key, $value)
    {
        $this->config[$key] = $value;
    }

    /**
     * 获取配置
     *
     * @param string $key 配置键
     * @param mixed $default 默认值
     * @return mixed
     */
    public function getConfig($key, $default = null)
    {
        return isset($this->config[$key]) ? $this->config[$key] : $default;
    }

    /**
     * 检查配置是否完整
     *
     * @return bool
     */
    public function isConfigured()
    {
        $site = $this->getConfig('site');
        $token = $this->getConfig('token');

        return !empty($site) && !empty($token);
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
            return $this->error('百度推送配置不完整，请检查站点和Token设置');
        }

        if (empty($url)) {
            return $this->error('URL不能为空');
        }

        $apiUrl = $this->buildApiUrl('api');
        $urls = array($url);

        return $this->sendRequest($apiUrl, $urls);
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
            return $this->error('百度推送配置不完整，请检查站点和Token设置');
        }

        if (empty($urls) || !is_array($urls)) {
            return $this->error('URLs不能为空且必须是数组');
        }

        // 百度API推送一次最多2000条
        $urls = array_slice($urls, 0, 2000);

        $apiUrl = $this->buildApiUrl('api');

        return $this->sendRequest($apiUrl, $urls);
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
            return $this->error('百度推送配置不完整，请检查站点和Token设置');
        }

        if (empty($sitemapUrl)) {
            return $this->error('Sitemap URL不能为空');
        }

        $apiUrl = $this->buildApiUrl('sitemap');
        $apiUrl .= '&sitemap=' . urlencode($sitemapUrl);

        return $this->sendRequest($apiUrl, array(), 'GET');
    }

    /**
     * 推送更新
     *
     * @param string $url 更新的URL
     * @return array 返回推送结果
     */
    public function pushUpdate($url)
    {
        // 更新和新增使用同一个接口
        return $this->pushUrl($url);
    }

    /**
     * 推送删除
     *
     * @param string $url 删除的URL
     * @return array 返回推送结果
     */
    public function pushDelete($url)
    {
        if (!$this->isConfigured()) {
            return $this->error('百度推送配置不完整，请检查站点和Token设置');
        }

        if (empty($url)) {
            return $this->error('URL不能为空');
        }

        // 删除使用del接口
        $apiUrl = $this->buildApiUrl('del');
        $urls = array($url);

        return $this->sendRequest($apiUrl, $urls);
    }

    /**
     * 获取推送状态
     *
     * @return array
     */
    public function getPushStatus()
    {
        // 这个功能需要更高级的权限，暂时保留接口
        return $this->error('获取推送状态功能暂未实现');
    }

    /**
     * 构建API URL
     *
     * @param string $type 推送类型 (api/sitemap/del)
     * @return string
     */
    private function buildApiUrl($type)
    {
        $site = $this->getConfig('site');
        $token = $this->getConfig('token');

        $endpoint = sprintf(self::PUSH_ENDPOINT, $site, $token);

        switch ($type) {
            case 'api':
                return self::API_PUSH_URL . '?' . $endpoint;
            case 'del':
                return self::API_PUSH_URL . '/del?' . $endpoint;
            case 'sitemap':
                return self::SITEMAP_PUSH_URL . '?' . $endpoint;
            default:
                return self::API_PUSH_URL . '?' . $endpoint;
        }
    }

    /**
     * 发送HTTP请求
     *
     * @param string $url API地址
     * @param array $urls 要推送的URL数组
     * @param string $method 请求方法 (POST/GET)
     * @return array
     */
    private function sendRequest($url, $urls = array(), $method = 'POST')
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
            $data = json_decode($response, true);

            if ($data === null) {
                return $this->error('解析响应失败: ' . $response);
            }

            // 判断是否成功
            if (isset($data['success'])) {
                $successCount = (int)$data['success'];
                $notValid = isset($data['not_valid']) ? $data['not_valid'] : array();
                $notSameSite = isset($data['not_same_site']) ? $data['not_same_site'] : array();

                if ($successCount > 0) {
                    $result['success'] = true;
                    $result['message'] = $this->formatSuccessMessage($data);
                } else {
                    $result['success'] = false;
                    if (!empty($notValid)) {
                        $result['message'] = 'URL无效，请检查百度站长平台是否已验证站点';
                    } elseif (!empty($notSameSite)) {
                        $result['message'] = 'URL不属于已验证的站点';
                    } else {
                        $result['message'] = '推送失败，请检查配置';
                    }
                }
                $result['data'] = $data;

                // 记录日志
                $this->log($result, !$result['success']);
            } elseif (isset($data['error_code'])) {
                $result['success'] = false;
                $result['message'] = $this->formatErrorMessage($data);
                $result['data'] = $data;

                // 记录错误日志
                $this->log($result, true);
            } else {
                $result['success'] = true;
                $result['message'] = '推送成功';
                $result['data'] = $data;

                $this->log($result);
            }

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
     * @param array $data 百度返回的数据
     * @return string
     */
    private function formatSuccessMessage($data)
    {
        $message = '推送成功';

        if (isset($data['success'])) {
            $message = '成功推送 ' . $data['success'] . ' 条';

            if (isset($data['remain'])) {
                $message .= '，今日剩余配额: ' . $data['remain'];
            }
        }

        return $message;
    }

    /**
     * 格式化错误消息
     *
     * @param array $data 百度返回的错误数据
     * @return string
     */
    private function formatErrorMessage($data)
    {
        $message = '推送失败';

        if (isset($data['message'])) {
            $message .= ': ' . $data['message'];
        }

        if (isset($data['error_code'])) {
            $message .= ' (错误码: ' . $data['error_code'] . ')';
        }

        return $message;
    }

    /**
     * 返回错误结果
     *
     * @param string $message 错误消息
     * @return array
     */
    private function error($message)
    {
        return array(
            'success' => false,
            'message' => $message,
            'data' => array()
        );
    }

    /**
     * 记录日志
     *
     * @param array $result 推送结果
     * @param bool $isError 是否是错误日志
     * @return void
     */
    private function log($result, $isError = false)
    {
        $logDir = __DIR__ . '/logs';
        $logFile = $logDir . '/baidu_push_' . date('Ymd') . '.log';

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
            'site' => isset($pluginOptions->baiduSite) ? $pluginOptions->baiduSite : '',
            'token' => isset($pluginOptions->baiduToken) ? $pluginOptions->baiduToken : '',
            'type' => isset($pluginOptions->baiduPushType) ? $pluginOptions->baiduPushType : 'api',
            'triggers' => isset($pluginOptions->baiduPushTrigger) ? $pluginOptions->baiduPushTrigger : array()
        );

        return new self($config);
    }
}
