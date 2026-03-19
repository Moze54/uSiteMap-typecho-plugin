<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;

/**
 * 搜索引擎推送基类
 *
 * 所有搜索引擎推送类的抽象基类
 */
abstract class uSitemap_SearchEnginePusher
{
    /**
     * 配置选项
     * @var array
     */
    protected $config;

    /**
     * 搜索引擎名称
     * @var string
     */
    protected $engineName;

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
    abstract public function isConfigured();

    /**
     * API推送单个URL
     *
     * @param string $url 要推送的URL
     * @return array 返回推送结果
     */
    abstract public function pushUrl($url);

    /**
     * API批量推送URLs
     *
     * @param array $urls 要推送的URL数组
     * @return array 返回推送结果
     */
    abstract public function pushUrls($urls);

    /**
     * Sitemap推送
     *
     * @param string $sitemapUrl Sitemap地址
     * @return array 返回推送结果
     */
    abstract public function pushSitemap($sitemapUrl);

    /**
     * 发送HTTP请求
     *
     * @param string $url API地址
     * @param array $data 要发送的数据
     * @param string $method 请求方法
     * @param array $headers 请求头
     * @return array
     */
    protected function sendRequest($url, $data = array(), $method = 'POST', $headers = array())
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
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

            if ($method === 'POST') {
                curl_setopt($ch, CURLOPT_POST, true);

                if (!empty($data)) {
                    if (isset($headers['Content-Type']) && strpos($headers['Content-Type'], 'application/json') !== false) {
                        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                    } else {
                        curl_setopt($ch, CURLOPT_POSTFIELDS, is_array($data) ? http_build_query($data) : $data);
                    }
                }
            }

            if (!empty($headers)) {
                $headerArray = array();
                foreach ($headers as $key => $value) {
                    $headerArray[] = "$key: $value";
                }
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headerArray);
            }

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
     * 解析响应
     *
     * @param string $response 响应内容
     * @return array
     */
    protected function parseResponse($response)
    {
        $result = array(
            'success' => false,
            'message' => '',
            'data' => array()
        );

        // 尝试JSON解析
        $data = json_decode($response, true);

        if ($data !== null) {
            $result['data'] = $data;
            $result['success'] = true;
            $result['message'] = $this->formatSuccessMessage($data);
        } else {
            // 如果不是JSON,可能直接返回成功
            $result['success'] = true;
            $result['message'] = '推送成功';
            $result['data'] = array('response' => $response);
        }

        return $result;
    }

    /**
     * 格式化成功消息（子类可覆盖）
     *
     * @param array $data 返回的数据
     * @return string
     */
    protected function formatSuccessMessage($data)
    {
        return '推送成功';
    }

    /**
     * 返回错误结果
     *
     * @param string $message 错误消息
     * @return array
     */
    protected function error($message)
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
    protected function log($result, $isError = false)
    {
        $logDir = __DIR__ . '/logs';
        $engineName = strtolower($this->engineName);
        $logFile = $logDir . '/' . $engineName . '_push_' . date('Ymd') . '.log';

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
}
