<?php
/**
 * 自动生成网站地图插件
 * 
 * @package uSitemap
 * @author 优优
 * @version 1.0.0
 * @link https://blog.uuhb.cn
 */
class uSitemap_Plugin implements Typecho_Plugin_Interface
{
    /**
     * 激活插件方法
     */
    public static function activate()
    {
        // 注册路由
        Helper::addRoute('sitemap', '/sitemap.xml', 'uSitemap_Action', 'index');

        // 注册action
        Helper::addAction('uSitemap', 'uSitemap_Action');

        // 注册文章发布和更新钩子 - 支持多个搜索引擎
        Typecho_Plugin::factory('Widget_Contents_Post_Edit')->finishPublish = array('uSitemap_Plugin', 'pushToSearchEngines');
        Typecho_Plugin::factory('Widget_Contents_Page_Edit')->finishPublish = array('uSitemap_Plugin', 'pushToSearchEngines');

        // 清除旧版本配置
        $options = Helper::options();
        $db = Typecho_Db::get();

        // 检查是否存在旧配置并删除
        try {
            $config = $db->fetchRow($db->select()->from('table.options')->where('name = ?', 'plugin:uSitemap'));
            if ($config) {
                $values = unserialize($config['value']);
                // 删除旧的配置项
                $keysToDelete = [
                    'excludeCategories', 'excludeTags', 'autoPing', 'pingEngines',
                    'enableGooglePush', 'googleApiKey', 'googlePushTrigger', 'googlePushCount',
                    'enableSogouPush', 'sogouToken', 'sogouPushType', 'sogouPushTrigger', 'sogouPushCount',
                    'enablePush360Push', 'push360Site', 'push360Token', 'push360PushType', 'push360PushTrigger', 'push360PushCount'
                ];
                foreach ($keysToDelete as $key) {
                    unset($values[$key]);
                }
                // 更新配置
                $db->query($db->update('table.options')->rows(array('value' => serialize($values)))->where('name = ?', 'plugin:uSitemap'));
            }
        } catch (Exception $e) {
            // 忽略错误
        }

        return '插件已启用，访问 /sitemap.xml 查看网站地图';
    }
    
    /**
     * 禁用插件方法
     */
    public static function deactivate()
    {
        // 删除 IndexNow 验证文件
        try {
            $options = Helper::options();
            $pluginOptions = $options->plugin('uSitemap');

            if ($pluginOptions && !empty($pluginOptions->bingIndexnowKey)) {
                $key = $pluginOptions->bingIndexnowKey;
                $rootDir = defined('__TYPECHO_ROOT_DIR__') ? __TYPECHO_ROOT_DIR__ : dirname(__DIR__, 3);
                $keyFile = $rootDir . '/' . $key . '.txt';

                // 删除验证文件
                if (file_exists($keyFile)) {
                    @unlink($keyFile);
                }
            }
        } catch (Exception $e) {
            // 忽略错误，继续禁用插件
        }

        // 移除路由
        Helper::removeRoute('sitemap');

        // 移除action
        Helper::removeAction('uSitemap');

        return '插件已禁用';
    }
    
    /**
     * 获取插件配置面板
     */
    public static function config(Typecho_Widget_Helper_Form $form)
    {
        echo '<style>
        .typecho-option-max-width-1000 .typecho-body {
            max-width: 1400px !important;
        }
        .usitemap-container {
            max-width: 1400px;
            margin: 0 auto;
        }
        .usitemap-header {
            background: #e3f2fd;
            color: #1976d2;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            text-align: center;
        }
        .usitemap-header h2 {
            margin: 0 0 10px 0;
            font-size: 28px;
            color: #1976d2;
        }
        .usitemap-header p {
            margin: 0;
            font-size: 14px;
            opacity: 0.8;
            color: #1565c0;
        }
        .usitemap-requirements {
            background: linear-gradient(135deg, #fff3e0 0%, #ffe0b2 100%);
            border-radius: 10px;
            padding: 20px 25px;
            margin-bottom: 25px;
            box-shadow: 0 2px 8px rgba(255, 152, 0, 0.15);
        }
        .usitemap-requirements-title {
            font-size: 16px;
            font-weight: 700;
            color: #e65100;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .usitemap-requirements-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
        }
        .usitemap-requirements-section {
            background: rgba(255, 255, 255, 0.7);
            border-radius: 8px;
            padding: 15px;
        }
        .usitemap-requirements-section-title {
            font-size: 14px;
            font-weight: 600;
            color: #e65100;
            margin-bottom: 10px;
        }
        .usitemap-requirements-list {
            margin: 0;
            padding-left: 20px;
            list-style-type: disc;
        }
        .usitemap-requirements-list li {
            color: #bf360c;
            font-size: 13px;
            line-height: 1.8;
            margin-bottom: 5px;
        }
        .usitemap-requirements-list strong {
            color: #e65100;
            font-family: Consolas, Monaco, monospace;
            background: rgba(255, 255, 255, 0.8);
            padding: 2px 6px;
            border-radius: 4px;
        }
        .usitemap-tabs {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            gap: 10px;
            margin-bottom: 20px;
        }
        .usitemap-submit-area button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(25, 118, 210, 0.4) !important;
        }
        .usitemap-submit-area button:active {
            transform: translateY(0);
            box-shadow: 0 2px 8px rgba(25, 118, 210, 0.3) !important;
        }
        .usitemap-tab {
            padding: 12px 16px;
            background: #f8f9fa;
            border: 2px solid #e1e4e8;
            border-radius: 8px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 500;
            color: #555;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            white-space: nowrap;
            text-align: center;
        }
        .usitemap-tab:hover {
            background: #e3f2fd;
            border-color: #90caf9;
            transform: translateY(-2px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .usitemap-tab.active {
            background: linear-gradient(135deg, #42a5f5 0%, #1976d2 100%);
            color: #fff;
            border-color: #1976d2;
            box-shadow: 0 4px 12px rgba(25, 118, 210, 0.3);
        }
        .usitemap-section {
            display: none;
            background: #fff;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            margin-bottom: 20px;
            border: 1px solid #e1e4e8;
        }
        .usitemap-section.active {
            display: block;
            animation: fadeIn 0.3s ease-in;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .usitemap-section-title {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #90caf9;
        }
        .typecho-option {
            margin-bottom: 20px;
            padding: 18px;
            background: #fafbfc;
            border-radius: 8px;
            border: 1px solid #e8ecf1;
            transition: all 0.3s;
        }
        .typecho-option:hover {
            background: #ffffff;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
            border-color: #90caf9;
        }
        .typecho-option label {
            font-weight: 600;
            color: #333;
            font-size: 14px;
        }
        .typecho-option .description {
            color: #666;
            font-size: 13px;
            margin-top: 8px;
            line-height: 1.6;
        }
        .usitemap-tip {
            background: #fff8e1;
            border-left: 4px solid #ffb74d;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }
        .usitemap-tip-title {
            font-weight: bold;
            color: #e65100;
            margin-bottom: 10px;
            font-size: 16px;
        }
        .usitemap-tip ul {
            margin: 0;
            padding-left: 20px;
            color: #e65100;
            font-size: 13px;
            line-height: 1.8;
        }
        .usitemap-loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            z-index: 9999;
            display: none;
            align-items: center;
            justify-content: center;
        }
        .usitemap-loading-box {
            background: #fff;
            padding: 40px 50px;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            text-align: center;
            min-width: 320px;
        }
        .usitemap-loading-spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #1976d2;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: usitemap-spin 1s linear infinite;
            margin: 0 auto 20px;
        }
        @keyframes usitemap-spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .usitemap-loading-text {
            font-size: 16px;
            color: #333;
            font-weight: 600;
            margin-bottom: 8px;
        }
        .usitemap-loading-desc {
            font-size: 13px;
            color: #666;
            line-height: 1.6;
        }
        .usitemap-toast {
            position: fixed;
            top: 80px;
            right: 20px;
            min-width: 300px;
            max-width: 500px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            padding: 16px 20px;
            z-index: 10000;
            display: none;
            align-items: flex-start;
            gap: 12px;
            animation: usitemap-slideIn 0.3s ease-out;
        }
        @keyframes usitemap-slideIn {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        .usitemap-toast.success {
            border-left: 4px solid #4caf50;
        }
        .usitemap-toast.error {
            border-left: 4px solid #f44336;
        }
        .usitemap-toast.info {
            border-left: 4px solid #2196f3;
        }
        .usitemap-toast-icon {
            flex-shrink: 0;
            width: 24px;
            height: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-size: 14px;
            font-weight: bold;
        }
        .usitemap-toast.success .usitemap-toast-icon {
            background: #e8f5e9;
            color: #4caf50;
        }
        .usitemap-toast.error .usitemap-toast-icon {
            background: #ffebee;
            color: #f44336;
        }
        .usitemap-toast.info .usitemap-toast-icon {
            background: #e3f2fd;
            color: #2196f3;
        }
        .usitemap-toast-content {
            flex: 1;
        }
        .usitemap-toast-title {
            font-weight: 600;
            font-size: 14px;
            color: #333;
            margin-bottom: 4px;
        }
        .usitemap-toast-message {
            font-size: 13px;
            color: #666;
            line-height: 1.5;
        }
        .usitemap-toast-close {
            flex-shrink: 0;
            cursor: pointer;
            color: #999;
            font-size: 18px;
            line-height: 1;
            transition: color 0.2s;
        }
        .usitemap-toast-close:hover {
            color: #666;
        }
        </style>

        <div class="usitemap-container">
            <div class="usitemap-header">
                <h2>🗺️ uSitemap</h2>
                <p>自动生成 XML 站点地图，支持百度和必应推送</p>
            </div>
            <div class="usitemap-requirements">
                <div class="usitemap-requirements-title">⚠️ 环境要求</div>
                <div class="usitemap-requirements-content">
                    <div class="usitemap-requirements-section">
                        <div class="usitemap-requirements-section-title">📦 必需的 PHP 扩展</div>
                        <ul class="usitemap-requirements-list">
                            <li><strong>curl</strong> - 用于搜索引擎推送</li>
                            <li><strong>dom</strong> 或 <strong>simplexml</strong> - 用于生成 XML 站点地图</li>
                        </ul>
                    </div>
                    <div class="usitemap-requirements-section">
                        <div class="usitemap-requirements-section-title">🔐 必需的权限</div>
                        <ul class="usitemap-requirements-list">
                            <li><strong>网站根目录写入权限</strong> - 用于创建 IndexNow 验证文件（{key}.txt）</li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="usitemap-tabs">
                <button type="button" class="usitemap-tab active" data-tab="sitemap">⚙️ Sitemap设置</button>
                <button type="button" class="usitemap-tab" data-tab="baidu">📍 百度推送</button>
                <button type="button" class="usitemap-tab" data-tab="bing">🎯 Bing推送</button>
                <button type="button" class="usitemap-tab" data-tab="logs">📋 推送记录</button>
            </div>

            <div class="usitemap-submit-area" style="margin-bottom: 20px; text-align: right;">
                <button type="submit" class="btn primary" style="padding: 10px 28px; height: 40px; line-height: 20px; background: linear-gradient(135deg, #42a5f5 0%, #1976d2 100%); border: none; border-radius: 6px; color: #fff; font-size: 14px; font-weight: 600; cursor: pointer; transition: all 0.3s; box-shadow: 0 2px 8px rgba(25, 118, 210, 0.3); display: inline-block; vertical-align: middle;">保存配置</button>
            </div>

            <div id="sitemap-section" class="usitemap-section active">
                <div class="usitemap-section-title">⚙️ Sitemap设置</div>
                <div id="sitemap-content"></div>
                <div class="usitemap-tip">
                    <div class="usitemap-tip-title">💡 使用提示</div>
                    <ul>
                        <li>启用插件后，访问 <strong>/sitemap.xml</strong> 查看站点地图</li>
                        <li>建议在 Google Search Console、百度站长工具中提交站点地图</li>
                        <li>更新频率建议：活跃博客选"每天"，静态站点选"每周"</li>
                        <li>优先级范围 0.0-1.0，重要内容建议设置更高优先级</li>
                    </ul>
                </div>
            </div>

            <div id="baidu-section" class="usitemap-section">
                <div class="usitemap-section-title">📍 百度推送</div>
                <div id="baidu-content"></div>
                <div style="margin-top: 30px; padding: 20px; background: #fff8e1; border-left: 4px solid #ffb74d; border-radius: 8px;">
                    <h4 style="margin: 0 0 15px 0; font-size: 15px; color: #e65100;">💡 如何获取百度推送配置</h4>
                    <ol style="margin: 0; padding-left: 20px; color: #e65100; font-size: 13px; line-height: 2;">
                        <li>访问 <a href="https://ziyuan.baidu.com/" target="_blank" style="color: #1976d2; text-decoration: underline;">百度搜索资源平台</a></li>
                        <li>登录并验证网站所有权</li>
                        <li>进入「普通收录」→「资源提交」→「普通收录」</li>
                        <li>选择「API推送」方式，获取推送接口令牌(Token)</li>
                        <li>在「资源提交」-「资源替换」中获取站点域名</li>
                    </ol>
                </div>
                <div class="usitemap-manual-push-area" style="margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 8px; border: 1px solid #e1e4e8;">
                    <h4 style="margin: 0 0 15px 0; font-size: 15px; color: #333;">🚀 手动推送</h4>
                    <p style="margin: 0 0 15px 0; font-size: 13px; color: #666; line-height: 1.6;">
                        点击下方按钮将当前站点地图推送到百度搜索引擎。建议在发布新文章后手动推送以加快收录速度。
                    </p>
                    <button type="button" id="baidu-push-btn" class="btn-primary" style="padding: 10px 24px; background: #1976d2; color: #fff; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: 500; transition: all 0.3s;">
                        立即推送
                    </button>
                    <div id="baidu-push-result" style="margin-top: 15px; padding: 12px; border-radius: 6px; font-size: 13px; display: none;"></div>
                </div>
            </div>

            <div id="logs-section" class="usitemap-section">
                <div class="usitemap-section-title">📋 推送记录</div>
                <div style="margin-bottom: 15px;">
                    <button type="button" id="refresh-logs-btn" style="padding: 8px 16px; background: #f0f0f0; border: 1px solid #ddd; border-radius: 4px; cursor: pointer; font-size: 13px;">刷新记录</button>
                    <button type="button" id="clear-logs-btn" style="padding: 8px 16px; background: #fff; border: 1px solid #f44336; color: #f44336; border-radius: 4px; cursor: pointer; font-size: 13px; margin-left: 8px;">清空记录</button>
                </div>
                <div id="logs-content" style="background: #f8f9fa; border-radius: 8px; border: 1px solid #e1e4e8; padding: 20px; min-height: 200px; max-height: 600px; overflow-y: auto;">
                    <div style="text-align: center; color: #999; padding: 40px;">加载中...</div>
                </div>
            </div>

            <div id="bing-section" class="usitemap-section">
                <div class="usitemap-section-title">🎯 Bing推送 (IndexNow API)</div>
                <div id="bing-content"></div>
                <div style="margin-top: 30px; padding: 20px; background: #e0f2f1; border-left: 4px solid #008374; border-radius: 8px;">
                    <h4 style="margin: 0 0 15px 0; font-size: 15px; color: #00695c;">💡 关于 IndexNow</h4>
                    <p style="margin: 0 0 15px 0; font-size: 13px; color: #00695c; line-height: 1.8;">
                        本插件使用 Microsoft Bing 的 IndexNow API 进行推送。启用时会自动生成验证密钥并创建验证文件到网站根目录。
                    </p>
                    <h4 style="margin: 0 0 10px 0; font-size: 14px; color: #00695c;">✨ 自动配置流程</h4>
                    <ol style="margin: 0; padding-left: 20px; color: #00695c; font-size: 13px; line-height: 2;">
                        <li>启用 Bing 推送功能（Key 留空即可自动生成）</li>
                        <li>保存配置后，插件会自动生成随机密钥</li>
                        <li>插件会自动在网站根目录创建验证文件（格式：{key}.txt）</li>
                        <li>验证文件内容为密钥本身，确保可公网访问</li>
                        <li>即可开始推送 URL 到 Bing 和其他支持 IndexNow 的搜索引擎</li>
                    </ol>
                    <p style="margin: 15px 0 0 0; font-size: 12px; color: #00695c;">
                        📌 IndexNow 会同时推送到 Bing、Yandex、Seznam 等多个搜索引擎
                    </p>
                </div>
                <div class="usitemap-check-file-area" style="margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 8px; border: 1px solid #e1e4e8;">
                    <h4 style="margin: 0 0 15px 0; font-size: 15px; color: #333;">🔍 检测验证文件</h4>
                    <p style="margin: 0 0 15px 0; font-size: 13px; color: #666; line-height: 1.6;">
                        点击下方按钮检测 IndexNow 验证文件是否已正确创建到网站根目录。
                    </p>
                    <button type="button" id="check-indexnow-file-btn" class="btn-primary" style="padding: 10px 24px; background: #008374; color: #fff; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: 500; transition: all 0.3s;">
                        检测验证文件
                    </button>
                    <div id="check-indexnow-file-result" style="margin-top: 15px; padding: 12px; border-radius: 6px; font-size: 13px; display: none;"></div>
                </div>
                <div class="usitemap-manual-push-area" style="margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 8px; border: 1px solid #e1e4e8;">
                    <h4 style="margin: 0 0 15px 0; font-size: 15px; color: #333;">🚀 手动推送</h4>
                    <p style="margin: 0 0 15px 0; font-size: 13px; color: #666; line-height: 1.6;">
                        点击下方按钮将当前站点地图推送到Bing搜索引擎。建议在发布新文章后手动推送以加快收录速度。
                    </p>
                    <button type="button" id="bing-push-btn" class="btn-primary" style="padding: 10px 24px; background: #008374; color: #fff; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: 500; transition: all 0.3s;">
                        立即推送
                    </button>
                    <div id="bing-push-result" style="margin-top: 15px; padding: 12px; border-radius: 6px; font-size: 13px; display: none;"></div>
                </div>
            </div>
        </div>

        <div class="usitemap-loading-overlay" id="usitemap-loading-overlay">
            <div class="usitemap-loading-box">
                <div class="usitemap-loading-spinner"></div>
                <div class="usitemap-loading-text">正在生成 IndexNow 密钥</div>
                <div class="usitemap-loading-desc">正在创建验证文件到网站根目录，请稍候...</div>
            </div>
        </div>

        <div class="usitemap-toast" id="usitemap-toast">
            <div class="usitemap-toast-icon"></div>
            <div class="usitemap-toast-content">
                <div class="usitemap-toast-title"></div>
                <div class="usitemap-toast-message"></div>
            </div>
            <div class="usitemap-toast-close">&times;</div>
        </div>

        <div class="usitemap-temp-container" style="display:none;"></div>';

        echo '<script>
        document.addEventListener("DOMContentLoaded", function() {
            // 等待表单元素渲染完成
            setTimeout(function() {
                // 找到form标签和自定义容器
                var form = document.querySelector("form");
                var tempContainer = document.querySelector(".usitemap-temp-container");
                var sitemapContent = document.getElementById("sitemap-content");

                if (form && tempContainer && sitemapContent) {
                    // 先将自定义的tab结构移动到form内部的开头
                    var usitemapContainer = document.querySelector(".usitemap-container");
                    if (usitemapContainer) {
                        form.insertBefore(usitemapContainer, form.firstChild);
                    }

                    // 隐藏Typecho默认的提交按钮区域及其父容器
                    var typechoFoot = form.querySelector(".typecho-foot");
                    if (typechoFoot) {
                        // 隐藏自身
                        typechoFoot.style.display = "none";
                        // 也隐藏可能的父容器（通常是一个带边框的div）
                        if (typechoFoot.parentElement) {
                            typechoFoot.parentElement.style.display = "none";
                        }
                    }

                    // 隐藏typecho-option-submit元素（Typecho的提交按钮选项）
                    var submitOption = form.querySelector(".typecho-option-submit");
                    if (submitOption) {
                        submitOption.style.display = "none";
                    }

                    // 隐藏表单内所有的submit按钮（除了我们自定义的那个）
                    var allSubmitBtns = form.querySelectorAll("button[type=submit], input[type=submit]");
                    for (var i = 0; i < allSubmitBtns.length; i++) {
                        // 如果不是usitemap-submit-area内的按钮，则隐藏
                        if (!allSubmitBtns[i].closest(".usitemap-submit-area")) {
                            allSubmitBtns[i].style.display = "none";
                            // 如果按钮的父元素只有这一个按钮，也隐藏父元素
                            var parent = allSubmitBtns[i].parentElement;
                            if (parent && parent.children.length === 1) {
                                parent.style.display = "none";
                            }
                        }
                    }

                    // 将所有表单元素移动到sitemap-content中（排除提交按钮选项）
                    var options = document.querySelectorAll(".typecho-option");
                    for (var i = 0; i < options.length; i++) {
                        // 跳过提交按钮选项
                        if (options[i].classList.contains("typecho-option-submit")) {
                            continue;
                        }
                        sitemapContent.appendChild(options[i]);
                    }
                }
            }, 100);

            // 标签切换功能
            var tabs = document.querySelectorAll(".usitemap-tab");
            var sections = document.querySelectorAll(".usitemap-section");

            for (var i = 0; i < tabs.length; i++) {
                tabs[i].addEventListener("click", function() {
                    var targetTab = this.getAttribute("data-tab");

                    // 移除所有 active 类
                    for (var j = 0; j < tabs.length; j++) {
                        tabs[j].classList.remove("active");
                    }
                    for (var k = 0; k < sections.length; k++) {
                        sections[k].classList.remove("active");
                    }

                    // 添加 active 类到当前标签和对应的内容区
                    this.classList.add("active");
                    document.getElementById(targetTab + "-section").classList.add("active");
                });
            }

            // 百度手动推送功能
            var baiduPushBtn = document.getElementById("baidu-push-btn");
            var baiduPushResult = document.getElementById("baidu-push-result");

            if (baiduPushBtn) {
                baiduPushBtn.addEventListener("click", function() {
                    // 禁用按钮，显示加载状态
                    baiduPushBtn.disabled = true;
                    baiduPushBtn.textContent = "推送中...";
                    baiduPushBtn.style.opacity = "0.6";

                    // 显示加载提示
                    baiduPushResult.style.display = "block";
                    baiduPushResult.style.background = "#e3f2fd";
                    baiduPushResult.style.color = "#1976d2";
                    baiduPushResult.textContent = "正在推送，请稍候...";

                    // 发送AJAX请求
                    var xhr = new XMLHttpRequest();
                    xhr.open("POST", "' . Helper::options()->index . '/action/uSitemap?do=baidu_manual_push", true);
                    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                    xhr.setRequestHeader("X-Requested-With", "XMLHttpRequest");

                    xhr.onreadystatechange = function() {
                        if (xhr.readyState === 4) {
                            // 恢复按钮状态
                            baiduPushBtn.disabled = false;
                            baiduPushBtn.textContent = "立即推送";
                            baiduPushBtn.style.opacity = "1";

                            // 显示结果
                            baiduPushResult.style.display = "block";

                            if (xhr.status === 200) {
                                try {
                                    var response = JSON.parse(xhr.responseText);

                                    if (response.success) {
                                        baiduPushResult.style.background = "#e8f5e9";
                                        baiduPushResult.style.color = "#2e7d32";
                                        baiduPushResult.textContent = "✓ " + response.message;
                                    } else {
                                        baiduPushResult.style.background = "#ffebee";
                                        baiduPushResult.style.color = "#c62828";
                                        baiduPushResult.textContent = "✗ " + response.message;
                                    }
                                } catch (e) {
                                    baiduPushResult.style.background = "#ffebee";
                                    baiduPushResult.style.color = "#c62828";
                                    baiduPushResult.textContent = "✗ 解析响应失败: " + xhr.responseText;
                                }
                            } else {
                                baiduPushResult.style.background = "#ffebee";
                                baiduPushResult.style.color = "#c62828";
                                baiduPushResult.textContent = "✗ 请求失败，HTTP状态码: " + xhr.status;
                            }
                        }
                    };

                    xhr.onerror = function() {
                        baiduPushBtn.disabled = false;
                        baiduPushBtn.textContent = "立即推送";
                        baiduPushBtn.style.opacity = "1";

                        baiduPushResult.style.display = "block";
                        baiduPushResult.style.background = "#ffebee";
                        baiduPushResult.style.color = "#c62828";
                        baiduPushResult.textContent = "✗ 网络请求失败，请检查网络连接";
                    };

                    xhr.send();
                });
            }

            // 通用推送函数
            function setupPushButton(btnId, resultId, actionName, successColor, errorColor) {
                var pushBtn = document.getElementById(btnId);
                var pushResult = document.getElementById(resultId);

                if (pushBtn) {
                    pushBtn.addEventListener("click", function() {
                        // 禁用按钮，显示加载状态
                        pushBtn.disabled = true;
                        pushBtn.textContent = "推送中...";
                        pushBtn.style.opacity = "0.6";

                        // 显示加载提示
                        pushResult.style.display = "block";
                        pushResult.style.background = "#e3f2fd";
                        pushResult.style.color = "#1976d2";
                        pushResult.textContent = "正在推送，请稍候...";

                        // 发送AJAX请求
                        var xhr = new XMLHttpRequest();
                        xhr.open("POST", "' . Helper::options()->index . '/action/uSitemap?do=" + actionName, true);
                        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                        xhr.setRequestHeader("X-Requested-With", "XMLHttpRequest");

                        xhr.onreadystatechange = function() {
                            if (xhr.readyState === 4) {
                                // 恢复按钮状态
                                pushBtn.disabled = false;
                                pushBtn.textContent = "立即推送";
                                pushBtn.style.opacity = "1";

                                // 显示结果
                                pushResult.style.display = "block";

                                if (xhr.status === 200) {
                                    try {
                                        var response = JSON.parse(xhr.responseText);

                                        if (response.success) {
                                            pushResult.style.background = successColor || "#e8f5e9";
                                            pushResult.style.color = "#2e7d32";
                                            pushResult.textContent = "✓ " + response.message;
                                        } else {
                                            pushResult.style.background = errorColor || "#ffebee";
                                            pushResult.style.color = "#c62828";
                                            pushResult.textContent = "✗ " + response.message;
                                        }
                                    } catch (e) {
                                        pushResult.style.background = errorColor || "#ffebee";
                                        pushResult.style.color = "#c62828";
                                        pushResult.textContent = "✗ 解析响应失败: " + xhr.responseText;
                                    }
                                } else {
                                    pushResult.style.background = errorColor || "#ffebee";
                                    pushResult.style.color = "#c62828";
                                    pushResult.textContent = "✗ 请求失败，HTTP状态码: " + xhr.status;
                                }
                            }
                        };

                        xhr.onerror = function() {
                            pushBtn.disabled = false;
                            pushBtn.textContent = "立即推送";
                            pushBtn.style.opacity = "1";

                            pushResult.style.display = "block";
                            pushResult.style.background = errorColor || "#ffebee";
                            pushResult.style.color = "#c62828";
                            pushResult.textContent = "✗ 网络请求失败，请检查网络连接";
                        };

                        xhr.send();
                    });
                }
            }

            // 设置Bing推送按钮
            setupPushButton("bing-push-btn", "bing-push-result", "bing_manual_push");

            // IndexNow 验证文件检测功能
            var checkIndexnowFileBtn = document.getElementById("check-indexnow-file-btn");
            var checkIndexnowFileResult = document.getElementById("check-indexnow-file-result");

            if (checkIndexnowFileBtn) {
                checkIndexnowFileBtn.addEventListener("click", function() {
                    // 禁用按钮，显示加载状态
                    checkIndexnowFileBtn.disabled = true;
                    checkIndexnowFileBtn.textContent = "检测中...";
                    checkIndexnowFileBtn.style.opacity = "0.6";

                    // 显示加载提示
                    checkIndexnowFileResult.style.display = "block";
                    checkIndexnowFileResult.style.background = "#e3f2fd";
                    checkIndexnowFileResult.style.color = "#1976d2";
                    checkIndexnowFileResult.textContent = "正在检测验证文件，请稍候...";

                    // 发送AJAX请求
                    var xhr = new XMLHttpRequest();
                    xhr.open("GET", "' . Helper::options()->index . '/action/uSitemap?do=check_indexnow_file", true);
                    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                    xhr.setRequestHeader("X-Requested-With", "XMLHttpRequest");

                    xhr.onreadystatechange = function() {
                        if (xhr.readyState === 4) {
                            // 恢复按钮状态
                            checkIndexnowFileBtn.disabled = false;
                            checkIndexnowFileBtn.textContent = "检测验证文件";
                            checkIndexnowFileBtn.style.opacity = "1";

                            // 显示结果
                            checkIndexnowFileResult.style.display = "block";

                            if (xhr.status === 200) {
                                try {
                                    var response = JSON.parse(xhr.responseText);

                                    if (response.success && response.exists && response.valid) {
                                        // 文件存在且内容正确
                                        checkIndexnowFileResult.style.background = "#e8f5e9";
                                        checkIndexnowFileResult.style.color = "#2e7d32";
                                        var message = "✓ " + response.message;
                                        if (response.file_url) {
                                            message += `<br>文件地址: <a href="` + response.file_url + `" target="_blank" style="color: #1976d2;">` + response.file_url + `</a>`;
                                        }
                                        checkIndexnowFileResult.innerHTML = message;
                                    } else if (response.success && response.exists && !response.valid) {
                                        // 文件存在但内容不正确
                                        checkIndexnowFileResult.style.background = "#fff3e0";
                                        checkIndexnowFileResult.style.color = "#e65100";
                                        checkIndexnowFileResult.textContent = "✗ " + response.message + "，请重新生成密钥";
                                    } else if (!response.success && !response.exists) {
                                        // 文件不存在
                                        checkIndexnowFileResult.style.background = "#ffebee";
                                        checkIndexnowFileResult.style.color = "#c62828";
                                        checkIndexnowFileResult.textContent = "✗ " + response.message;
                                    } else {
                                        // 其他错误
                                        checkIndexnowFileResult.style.background = "#ffebee";
                                        checkIndexnowFileResult.style.color = "#c62828";
                                        checkIndexnowFileResult.textContent = "✗ " + response.message;
                                    }
                                } catch (e) {
                                    checkIndexnowFileResult.style.background = "#ffebee";
                                    checkIndexnowFileResult.style.color = "#c62828";
                                    checkIndexnowFileResult.textContent = "✗ 解析响应失败: " + xhr.responseText;
                                }
                            } else {
                                checkIndexnowFileResult.style.background = "#ffebee";
                                checkIndexnowFileResult.style.color = "#c62828";
                                checkIndexnowFileResult.textContent = "✗ 请求失败，HTTP状态码: " + xhr.status;
                            }
                        }
                    };

                    xhr.onerror = function() {
                        checkIndexnowFileBtn.disabled = false;
                        checkIndexnowFileBtn.textContent = "检测验证文件";
                        checkIndexnowFileBtn.style.opacity = "1";

                        checkIndexnowFileResult.style.display = "block";
                        checkIndexnowFileResult.style.background = "#ffebee";
                        checkIndexnowFileResult.style.color = "#c62828";
                        checkIndexnowFileResult.textContent = "✗ 网络请求失败，请检查网络连接";
                    };

                    xhr.send();
                });
            }

            // 推送记录功能
            var logsContent = document.getElementById("logs-content");
            var refreshLogsBtn = document.getElementById("refresh-logs-btn");
            var clearLogsBtn = document.getElementById("clear-logs-btn");

            // 拦截表单提交，处理 Bing IndexNow Key 自动生成和删除
            var form = document.querySelector("form");
            var loadingOverlay = document.getElementById("usitemap-loading-overlay");
            var toast = document.getElementById("usitemap-toast");

            // 显示加载遮罩层
            function showLoading(title, desc) {
                var titleEl = loadingOverlay.querySelector(".usitemap-loading-text");
                var descEl = loadingOverlay.querySelector(".usitemap-loading-desc");
                if (titleEl) titleEl.textContent = title;
                if (descEl) descEl.textContent = desc;
                loadingOverlay.style.display = "flex";
            }

            // 隐藏加载遮罩层
            function hideLoading() {
                loadingOverlay.style.display = "none";
            }

            // 显示 toast 提示
            function showToast(type, title, message, duration) {
                duration = duration || 4000;

                toast.className = "usitemap-toast " + type;
                toast.style.display = "flex";

                var iconEl = toast.querySelector(".usitemap-toast-icon");
                var titleEl = toast.querySelector(".usitemap-toast-title");
                var messageEl = toast.querySelector(".usitemap-toast-message");

                if (type === "success") {
                    iconEl.textContent = "✓";
                } else if (type === "error") {
                    iconEl.textContent = "✗";
                } else {
                    iconEl.textContent = "ℹ";
                }

                titleEl.textContent = title;
                messageEl.textContent = message;

                // 自动隐藏
                setTimeout(function() {
                    toast.style.display = "none";
                }, duration);
            }

            // toast 关闭按钮
            var toastClose = toast.querySelector(".usitemap-toast-close");
            if (toastClose) {
                toastClose.addEventListener("click", function() {
                    toast.style.display = "none";
                });
            }

            if (form) {
                form.addEventListener("submit", function(e) {
                    var enableBingPush = form.querySelector("input[name=enableBingPush]:checked");
                    var keyInput = form.querySelector("input[name=bingIndexnowKey]");

                    // 检查是否启用了 Bing 推送
                    if (enableBingPush && enableBingPush.value === "1") {
                        if (keyInput && !keyInput.value.trim()) {
                            // 如果 key 为空，阻止提交并生成 key
                            e.preventDefault();

                            // 显示加载提示
                            showLoading("正在生成 IndexNow 密钥", "正在创建验证文件到网站根目录，请稍候...");

                            // 发送请求生成 key
                            var xhr = new XMLHttpRequest();
                            xhr.open("POST", "' . Helper::options()->index . '/action/uSitemap?do=generate_indexnow_key", true);
                            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                            xhr.setRequestHeader("X-Requested-With", "XMLHttpRequest");

                            xhr.onreadystatechange = function() {
                                if (xhr.readyState === 4) {
                                    hideLoading();
                                    if (xhr.status === 200) {
                                        try {
                                            var response = JSON.parse(xhr.responseText);
                                            if (response.success) {
                                                // 设置生成的 key
                                                keyInput.value = response.key;
                                                // 显示 toast 提示
                                                showToast("success", "密钥生成成功", "IndexNow Key: " + response.key + "，验证文件已创建");
                                                // 继续提交表单
                                                form.submit();
                                            } else {
                                                showToast("error", "生成失败", response.message);
                                            }
                                        } catch (e) {
                                            showToast("error", "解析失败", "响应数据解析失败");
                                        }
                                    } else {
                                        showToast("error", "请求失败", "HTTP状态码: " + xhr.status);
                                    }
                                }
                            };

                            xhr.onerror = function() {
                                hideLoading();
                                showToast("error", "网络错误", "网络请求失败，请检查网络连接");
                            };

                            xhr.send();
                        } else {
                            // 已有 key，显示简短提示后继续提交
                            showLoading("正在保存配置", "正在保存 Bing 推送配置，请稍候...");
                            setTimeout(function() {
                                hideLoading();
                            }, 500);
                        }
                    }
                });
            }

            function loadLogs() {
                logsContent.innerHTML = "<div style=\"text-align: center; color: #999; padding: 40px;\">加载中...</div>";

                var xhr = new XMLHttpRequest();
                xhr.open("GET", "' . Helper::options()->index . '/action/uSitemap?do=get_logs", true);
                xhr.onreadystatechange = function() {
                    if (xhr.readyState === 4 && xhr.status === 200) {
                        try {
                            var response = JSON.parse(xhr.responseText);
                            if (response.success && response.logs && response.logs.length > 0) {
                                var html = "";
                                for (var i = 0; i < response.logs.length; i++) {
                                    var log = response.logs[i];
                                    var dateStr = log.date.substring(0, 4) + "-" + log.date.substring(4, 6) + "-" + log.date.substring(6, 8);
                                    html += "<div style=\"margin-bottom: 15px; padding: 12px; background: #fff; border-radius: 6px; border: 1px solid #e1e4e8;\">";
                                    html += "<div style=\"font-weight: 600; color: #1976d2; margin-bottom: 8px;\">📅 " + dateStr + "</div>";
                                    html += "<pre style=\"margin: 0; font-size: 12px; color: #333; white-space: pre-wrap; word-break: break-all;\">" + log.content + "</pre>";
                                    html += "</div>";
                                }
                                logsContent.innerHTML = html;
                            } else {
                                logsContent.innerHTML = "<div style=\"text-align: center; color: #999; padding: 40px;\">暂无推送记录</div>";
                            }
                        } catch (e) {
                            logsContent.innerHTML = "<div style=\"text-align: center; color: #c62828; padding: 40px;\">加载失败</div>";
                        }
                    }
                };
                xhr.send();
            }

            if (refreshLogsBtn) {
                refreshLogsBtn.addEventListener("click", loadLogs);
            }

            if (clearLogsBtn) {
                clearLogsBtn.addEventListener("click", function() {
                    if (!confirm("确定要清空所有推送记录吗？")) return;

                    var xhr = new XMLHttpRequest();
                    xhr.open("GET", "' . Helper::options()->index . '/action/uSitemap?do=clear_logs", true);
                    xhr.onreadystatechange = function() {
                        if (xhr.readyState === 4 && xhr.status === 200) {
                            loadLogs();
                        }
                    };
                    xhr.send();
                });
            }

            // 点击推送记录标签时加载
            var logsTab = document.querySelector("[data-tab=logs]");
            if (logsTab) {
                logsTab.addEventListener("click", function() {
                    setTimeout(loadLogs, 100);
                });
            }
        });
        </script>';

        /** 内容类型 */
        $contentTypes = new Typecho_Widget_Helper_Form_Element_Checkbox(
            'contentTypes',
            array(
                'post' => _t('文章'),
                'page' => _t('独立页面'),
                'category' => _t('分类'),
                'tag' => _t('标签')
            ),
            array('post', 'page'),
            _t('包含内容'),
            _t('选择要包含在站点地图中的内容类型')
        );
        $form->addInput($contentTypes->multiMode());

        /** 包含首页 */
        $includeIndex = new Typecho_Widget_Helper_Form_Element_Radio(
            'includeIndex',
            array('1' => _t('包含'), '0' => _t('不包含')),
            '1',
            _t('包含首页'),
            _t('是否在站点地图中包含网站首页')
        );
        $form->addInput($includeIndex);

        /** 排除内容 */
        $excludeCids = new Typecho_Widget_Helper_Form_Element_Textarea(
            'excludeCids',
            NULL,
            '',
            _t('排除内容'),
            _t('输入要排除的文章或页面 ID，多个 ID 用逗号或换行分隔。<br />例如：1, 2, 3 或每行一个 ID')
        );
        $form->addInput($excludeCids);

        /** 密码保护内容 */
        $includePassword = new Typecho_Widget_Helper_Form_Element_Radio(
            'includePassword',
            array('1' => _t('包含'), '0' => _t('不包含')),
            '0',
            _t('密码保护内容'),
            _t('是否包含设置了密码访问的内容')
        );
        $form->addInput($includePassword);

        /** 更新频率 */
        $changefreq = new Typecho_Widget_Helper_Form_Element_Select(
            'changefreq',
            array(
                'always' => _t('始终'),
                'hourly' => _t('每小时'),
                'daily' => _t('每天'),
                'weekly' => _t('每周'),
                'monthly' => _t('每月'),
                'yearly' => _t('每年'),
                'never' => _t('从不')
            ),
            'weekly',
            _t('更新频率'),
            _t('设置内容的更新频率，这将影响搜索引擎抓取频率')
        );
        $form->addInput($changefreq);

        /** 默认优先级 */
        $priority = new Typecho_Widget_Helper_Form_Element_Text(
            'priority',
            NULL,
            '0.8',
            _t('默认优先级'),
            _t('设置 URL 的默认优先级（0.0 - 1.0），首页优先级会自动加 0.1')
        );
        $priority->input->setAttribute('class', 'mini');
        $form->addInput($priority);

        /** 最大条目数 */
        $maxItems = new Typecho_Widget_Helper_Form_Element_Text(
            'maxItems',
            NULL,
            '50000',
            _t('最大条目数'),
            _t('站点地图中包含的最大 URL 数量，默认 50000')
        );
        $maxItems->input->setAttribute('class', 'mini');
        $form->addInput($maxItems);

        // ========== 百度推送配置 ==========

        /** 启用百度推送 */
        $enableBaiduPush = new Typecho_Widget_Helper_Form_Element_Radio(
            'enableBaiduPush',
            array('1' => _t('启用'), '0' => _t('禁用')),
            '0',
            _t('启用百度推送'),
            _t('开启后，文章发布/更新时会自动推送到百度搜索引擎')
        );
        $form->addInput($enableBaiduPush);

        /** 百度站点验证token */
        $baiduSite = new Typecho_Widget_Helper_Form_Element_Text(
            'baiduSite',
            NULL,
            '',
            _t('百度站点'),
            _t('百度站长平台中验证的站点域名，如：example.com')
        );
        $form->addInput($baiduSite);

        /** 百度推送token */
        $baiduToken = new Typecho_Widget_Helper_Form_Element_Text(
            'baiduToken',
            NULL,
            '',
            _t('百度推送Token'),
            _t('在百度站长平台「普通收录」-「资源提交」-「普通收录」中获取的推送接口令牌')
        );
        $form->addInput($baiduToken);

        /** 推送类型 */
        $baiduPushType = new Typecho_Widget_Helper_Form_Element_Radio(
            'baiduPushType',
            array('api' => _t('API推送'), 'sitemap' => _t('Sitemap推送')),
            'api',
            _t('推送方式'),
            _t('API推送：实时推送单个URL，速度更快<br />Sitemap推送：推送sitemap地址，批量提交')
        );
        $form->addInput($baiduPushType);

        /** 自动推送触发时机 */
        $baiduPushTrigger = new Typecho_Widget_Helper_Form_Element_Checkbox(
            'baiduPushTrigger',
            array(
                'publish' => _t('发布文章时'),
                'update' => _t('更新文章时')
            ),
            array('publish', 'update'),
            _t('自动推送触发'),
            _t('百度推送：选择何时自动推送到百度')
        );
        $form->addInput($baiduPushTrigger->multiMode());

        /** 手动推送数量 */
        $baiduPushCount = new Typecho_Widget_Helper_Form_Element_Text(
            'baiduPushCount',
            NULL,
            '10',
            _t('手动推送数量'),
            _t('百度推送：手动推送时推送最新的N条内容，建议不超过100条')
        );
        $baiduPushCount->input->setAttribute('class', 'mini');
        $form->addInput($baiduPushCount);

        // ========== Bing推送配置 ==========

        /** 启用Bing推送 */
        $enableBingPush = new Typecho_Widget_Helper_Form_Element_Radio(
            'enableBingPush',
            array('1' => _t('启用'), '0' => _t('禁用')),
            '0',
            _t('启用Bing推送'),
            _t('开启后，文章发布/更新时会自动推送到Bing搜索引擎（使用IndexNow API）')
        );
        $form->addInput($enableBingPush);

        /** Bing IndexNow Key */
        $bingIndexnowKey = new Typecho_Widget_Helper_Form_Element_Text(
            'bingIndexnowKey',
            NULL,
            '',
            _t('Bing IndexNow Key'),
            _t('IndexNow验证密钥，留空则启用时自动生成。插件会自动在网站根目录创建验证文件')
        );
        $form->addInput($bingIndexnowKey);

        /** 自动推送触发时机 */
        $bingPushTrigger = new Typecho_Widget_Helper_Form_Element_Checkbox(
            'bingPushTrigger',
            array(
                'publish' => _t('发布文章时'),
                'update' => _t('更新文章时')
            ),
            array('publish', 'update'),
            _t('自动推送触发'),
            _t('Bing推送：选择何时自动推送到Bing（建议同时勾选，IndexNow API 支持实时通知搜索引擎内容更新）')
        );
        $form->addInput($bingPushTrigger->multiMode());

        /** 手动推送数量 */
        $bingPushCount = new Typecho_Widget_Helper_Form_Element_Text(
            'bingPushCount',
            NULL,
            '10',
            _t('手动推送数量'),
            _t('Bing推送：手动推送时推送最新的N条内容，建议不超过100条')
        );
        $bingPushCount->input->setAttribute('class', 'mini');
        $form->addInput($bingPushCount);

        echo '<script>
        document.addEventListener("DOMContentLoaded", function() {
            setTimeout(function() {
                // 将配置项移动到对应的标签页
                var baiduContent = document.getElementById("baidu-content");
                var bingContent = document.getElementById("bing-content");

                if (baiduContent) {
                    var baiduOptions = [];
                    var options = document.querySelectorAll(".typecho-option");
                    for (var i = 0; i < options.length; i++) {
                        var label = options[i].querySelector("label");
                        if (label && (
                            label.textContent.includes("百度推送") ||
                            label.textContent.includes("百度站点") ||
                            label.textContent.includes("百度推送Token") ||
                            label.textContent.includes("推送方式")
                        )) {
                            baiduOptions.push(options[i]);
                        }
                        // 单独处理"自动推送触发"和"手动推送数量"，需要确保是百度相关的
                        if (label && label.textContent.includes("自动推送触发")) {
                            var description = options[i].querySelector(".description");
                            if (description && description.textContent.includes("百度")) {
                                baiduOptions.push(options[i]);
                            }
                        }
                        if (label && label.textContent.includes("手动推送数量")) {
                            var description = options[i].querySelector(".description");
                            if (description && description.textContent.includes("百度")) {
                                baiduOptions.push(options[i]);
                            }
                        }
                    }
                    for (var j = 0; j < baiduOptions.length; j++) {
                        baiduContent.appendChild(baiduOptions[j]);
                    }
                }

                if (bingContent) {
                    var bingOptions = [];
                    var options = document.querySelectorAll(".typecho-option");
                    for (var i = 0; i < options.length; i++) {
                        var label = options[i].querySelector("label");
                        if (label && (
                            label.textContent.includes("Bing推送") ||
                            label.textContent.includes("IndexNow Key")
                        )) {
                            bingOptions.push(options[i]);
                        }
                        // 单独处理"自动推送触发"和"手动推送数量"，需要确保是Bing相关的
                        if (label && label.textContent.includes("自动推送触发")) {
                            var description = options[i].querySelector(".description");
                            if (description && description.textContent.includes("Bing")) {
                                bingOptions.push(options[i]);
                            }
                        }
                        if (label && label.textContent.includes("手动推送数量")) {
                            var description = options[i].querySelector(".description");
                            if (description && description.textContent.includes("Bing")) {
                                bingOptions.push(options[i]);
                            }
                        }
                    }
                    for (var j = 0; j < bingOptions.length; j++) {
                        bingContent.appendChild(bingOptions[j]);
                    }
                }
            }, 150);
        });
        </script>';
    }
    
    /**
     * 个人用户的配置面板
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form)
    {
    }

    /**
     * 文章/页面发布后推送到搜索引擎
     *
     * @param array $contents 文章内容数组
     * @param Widget_Contents_Post_Edit $widget 发布组件
     * @return void
     */
    public static function pushToSearchEngines($contents, $widget)
    {
        $options = Helper::options();
        $pluginOptions = $options->plugin('uSitemap');

        // 获取文章URL
        $permalink = $widget->permalink;

        if (empty($permalink)) {
            return;
        }

        // 判断是新发布还是更新
        // 方法：检查 created 和 modified 时间差，如果基本一致（5秒内）则认为是新发布
        $isNewPublish = false;
        if (isset($contents['created']) && isset($contents['modified'])) {
            $timeDiff = abs($contents['modified'] - $contents['created']);
            $isNewPublish = ($timeDiff <= 5);
        }

        // 推送到百度
        self::pushToSearchEngine('baidu', $pluginOptions, $permalink, $isNewPublish);

        // 推送到Bing
        self::pushToSearchEngine('bing', $pluginOptions, $permalink, $isNewPublish);
    }

    /**
     * 推送到指定搜索引擎
     *
     * @param string $engine 搜索引擎名称
     * @param Typecho_Config $pluginOptions 插件配置
     * @param string $permalink 文章URL
     * @param bool $isNewPublish 是否是新发布
     * @return void
     */
    private static function pushToSearchEngine($engine, $pluginOptions, $permalink, $isNewPublish)
    {
        $engine = ucfirst(strtolower($engine));

        // 检查是否启用
        $enableKey = 'enable' . $engine . 'Push';
        if ($engine === '360') {
            $enableKey = 'enablePush' . $engine . 'Push';
        }

        if (!$pluginOptions || $pluginOptions->$enableKey != '1') {
            return;
        }

        // 检查触发条件
        $triggerKey = $engine . 'PushTrigger';
        if ($engine === '360') {
            $triggerKey = 'push' . $engine . 'PushTrigger';
        }

        $triggers = isset($pluginOptions->$triggerKey) ? $pluginOptions->$triggerKey : array();

        // 确保 $triggers 是数组
        if (!is_array($triggers)) {
            $triggers = array($triggers);
        }

        // 兼容性处理：如果 triggers 为空，使用默认值（同时支持发布和更新）
        if (empty($triggers)) {
            $triggers = array('publish', 'update');
        }

        // 检查是否应该触发推送
        $shouldPush = false;
        if ($isNewPublish && in_array('publish', $triggers)) {
            // 新发布
            $shouldPush = true;
        } elseif (!$isNewPublish && in_array('update', $triggers)) {
            // 更新
            $shouldPush = true;
        }

        if (!$shouldPush) {
            return;
        }

        // 引入推送类并创建实例
        $pusherClass = 'uSitemap_' . $engine . 'Pusher';
        if ($engine === '360') {
            $pusherClass = 'uSitemap_Push' . $engine . 'Pusher';
        }

        require_once __DIR__ . '/' . $engine . 'Pusher.php';

        $pusher = $pusherClass::createFromPlugin($pluginOptions);

        // 执行推送
        try {
            $pusher->pushUrl($permalink);
        } catch (Exception $e) {
            // 忽略异常，避免影响文章发布
        }
    }
} 