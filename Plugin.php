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
                unset($values['excludeCategories'], $values['excludeTags'], $values['autoPing'], $values['pingEngines']);
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
        </style>

        <div class="usitemap-container">
            <div class="usitemap-header">
                <h2>🗺️ uSitemap 站点地图</h2>
                <p>自动生成符合搜索引擎标准的 XML 站点地图，帮助搜索引擎更好地索引您的网站</p>
            </div>

            <div class="usitemap-tabs">
                <button type="button" class="usitemap-tab active" data-tab="sitemap">⚙️ Sitemap设置</button>
                <button type="button" class="usitemap-tab" data-tab="baidu">📍 百度推送</button>
                <button type="button" class="usitemap-tab" data-tab="google">🔍 Google推送</button>
                <button type="button" class="usitemap-tab" data-tab="bing">🎯 Bing推送</button>
                <button type="button" class="usitemap-tab" data-tab="sogou">🔍 搜狗推送</button>
                <button type="button" class="usitemap-tab" data-tab="360">🛡️ 360推送</button>
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

            <div id="google-section" class="usitemap-section">
                <div class="usitemap-section-title">🔍 Google推送</div>
                <div id="google-content"></div>
                <div style="margin-top: 30px; padding: 20px; background: #e8f0fe; border-left: 4px solid #4285f4; border-radius: 8px;">
                    <h4 style="margin: 0 0 15px 0; font-size: 15px; color: #1967d2;">💡 如何获取Google推送配置</h4>
                    <ol style="margin: 0; padding-left: 20px; color: #1967d2; font-size: 13px; line-height: 2;">
                        <li>访问 <a href="https://console.cloud.google.com/" target="_blank" style="color: #1976d2; text-decoration: underline;">Google Cloud Console</a></li>
                        <li>创建新项目或选择现有项目</li>
                        <li>启用「Indexing API」</li>
                        <li>创建服务账号并下载JSON密钥文件</li>
                        <li>在 <a href="https://search.google.com/search-console" target="_blank" style="color: #1976d2; text-decoration: underline;">Google Search Console</a> 中验证网站并添加服务账号为资源所有者</li>
                    </ol>
                </div>
                <div class="usitemap-manual-push-area" style="margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 8px; border: 1px solid #e1e4e8;">
                    <h4 style="margin: 0 0 15px 0; font-size: 15px; color: #333;">🚀 手动推送</h4>
                    <p style="margin: 0 0 15px 0; font-size: 13px; color: #666; line-height: 1.6;">
                        点击下方按钮将当前站点地图推送到Google搜索引擎。建议在发布新文章后手动推送以加快收录速度。
                    </p>
                    <button type="button" id="google-push-btn" class="btn-primary" style="padding: 10px 24px; background: #4285f4; color: #fff; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: 500; transition: all 0.3s;">
                        立即推送
                    </button>
                    <div id="google-push-result" style="margin-top: 15px; padding: 12px; border-radius: 6px; font-size: 13px; display: none;"></div>
                </div>
            </div>

            <div id="bing-section" class="usitemap-section">
                <div class="usitemap-section-title">🎯 Bing推送</div>
                <div id="bing-content"></div>
                <div style="margin-top: 30px; padding: 20px; background: #e0f2f1; border-left: 4px solid #008374; border-radius: 8px;">
                    <h4 style="margin: 0 0 15px 0; font-size: 15px; color: #00695c;">💡 如何获取Bing推送配置</h4>
                    <ol style="margin: 0; padding-left: 20px; color: #00695c; font-size: 13px; line-height: 2;">
                        <li>访问 <a href="https://www.bing.com/webmasters" target="_blank" style="color: #1976d2; text-decoration: underline;">Bing Webmaster Tools</a></li>
                        <li>登录并验证网站所有权</li>
                        <li>进入「API Access」-「API Key」</li>
                        <li>点击「Generate API Key」生成API密钥</li>
                        <li>复制API密钥和站点URL</li>
                    </ol>
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

            <div id="sogou-section" class="usitemap-section">
                <div class="usitemap-section-title">🔍 搜狗推送</div>
                <div id="sogou-content"></div>
                <div style="margin-top: 30px; padding: 20px; background: #fff3e0; border-left: 4px solid #ff6900; border-radius: 8px;">
                    <h4 style="margin: 0 0 15px 0; font-size: 15px; color: #e65100;">💡 如何获取搜狗推送配置</h4>
                    <ol style="margin: 0; padding-left: 20px; color: #e65100; font-size: 13px; line-height: 2;">
                        <li>访问 <a href="http://zhanzhang.sogou.com/" target="_blank" style="color: #1976d2; text-decoration: underline;">搜狗站长平台</a></li>
                        <li>登录并验证网站所有权</li>
                        <li>进入「网页收录」-「API推送」</li>
                        <li>复制推送接口令牌(Token)</li>
                        <li>选择推送方式（API推送或Sitemap推送）</li>
                    </ol>
                </div>
                <div class="usitemap-manual-push-area" style="margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 8px; border: 1px solid #e1e4e8;">
                    <h4 style="margin: 0 0 15px 0; font-size: 15px; color: #333;">🚀 手动推送</h4>
                    <p style="margin: 0 0 15px 0; font-size: 13px; color: #666; line-height: 1.6;">
                        点击下方按钮将当前站点地图推送到搜狗搜索引擎。建议在发布新文章后手动推送以加快收录速度。
                    </p>
                    <button type="button" id="sogou-push-btn" class="btn-primary" style="padding: 10px 24px; background: #ff6900; color: #fff; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: 500; transition: all 0.3s;">
                        立即推送
                    </button>
                    <div id="sogou-push-result" style="margin-top: 15px; padding: 12px; border-radius: 6px; font-size: 13px; display: none;"></div>
                </div>
            </div>

            <div id="360-section" class="usitemap-section">
                <div class="usitemap-section-title">🛡️ 360推送</div>
                <div id="360-content"></div>
                <div style="margin-top: 30px; padding: 20px; background: #e8f5e9; border-left: 4px solid #19b955; border-radius: 8px;">
                    <h4 style="margin: 0 0 15px 0; font-size: 15px; color: #1b5e20;">💡 如何获取360推送配置</h4>
                    <ol style="margin: 0; padding-left: 20px; color: #1b5e20; font-size: 13px; line-height: 2;">
                        <li>访问 <a href="http://zhanzhang.so.com/" target="_blank" style="color: #1976d2; text-decoration: underline;">360站长平台</a></li>
                        <li>登录并验证网站所有权</li>
                        <li>进入「网页收录」-「API推送」</li>
                        <li>复制推送接口令牌(Token)</li>
                        <li>填写已验证的站点域名</li>
                    </ol>
                </div>
                <div class="usitemap-manual-push-area" style="margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 8px; border: 1px solid #e1e4e8;">
                    <h4 style="margin: 0 0 15px 0; font-size: 15px; color: #333;">🚀 手动推送</h4>
                    <p style="margin: 0 0 15px 0; font-size: 13px; color: #666; line-height: 1.6;">
                        点击下方按钮将当前站点地图推送到360搜索引擎。建议在发布新文章后手动推送以加快收录速度。
                    </p>
                    <button type="button" id="360-push-btn" class="btn-primary" style="padding: 10px 24px; background: #19b955; color: #fff; border: none; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: 500; transition: all 0.3s;">
                        立即推送
                    </button>
                    <div id="360-push-result" style="margin-top: 15px; padding: 12px; border-radius: 6px; font-size: 13px; display: none;"></div>
                </div>
            </div>
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

            // 设置其他搜索引擎的推送按钮
            setupPushButton("google-push-btn", "google-push-result", "google_manual_push");
            setupPushButton("bing-push-btn", "bing-push-result", "bing_manual_push");
            setupPushButton("sogou-push-btn", "sogou-push-result", "sogou_manual_push");
            setupPushButton("360-push-btn", "360-push-result", "360_manual_push");

            // 推送记录功能
            var logsContent = document.getElementById("logs-content");
            var refreshLogsBtn = document.getElementById("refresh-logs-btn");
            var clearLogsBtn = document.getElementById("clear-logs-btn");

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
            _t('选择何时自动推送到百度')
        );
        $form->addInput($baiduPushTrigger->multiMode());

        /** 手动推送数量 */
        $baiduPushCount = new Typecho_Widget_Helper_Form_Element_Text(
            'baiduPushCount',
            NULL,
            '10',
            _t('手动推送数量'),
            _t('手动推送时推送最新的N条内容，建议不超过100条')
        );
        $baiduPushCount->input->setAttribute('class', 'mini');
        $form->addInput($baiduPushCount);

        // ========== Google推送配置 ==========

        /** 启用Google推送 */
        $enableGooglePush = new Typecho_Widget_Helper_Form_Element_Radio(
            'enableGooglePush',
            array('1' => _t('启用'), '0' => _t('禁用')),
            '0',
            _t('启用Google推送'),
            _t('开启后，文章发布/更新时会自动推送到Google搜索引擎')
        );
        $form->addInput($enableGooglePush);

        /** Google API密钥 */
        $googleApiKey = new Typecho_Widget_Helper_Form_Element_Text(
            'googleApiKey',
            NULL,
            '',
            _t('Google API密钥'),
            _t('在Google Cloud Console中创建的API密钥，用于Indexing API')
        );
        $form->addInput($googleApiKey);

        /** 自动推送触发时机 */
        $googlePushTrigger = new Typecho_Widget_Helper_Form_Element_Checkbox(
            'googlePushTrigger',
            array(
                'publish' => _t('发布文章时'),
                'update' => _t('更新文章时')
            ),
            array('publish'),
            _t('自动推送触发'),
            _t('选择何时自动推送到Google')
        );
        $form->addInput($googlePushTrigger->multiMode());

        /** 手动推送数量 */
        $googlePushCount = new Typecho_Widget_Helper_Form_Element_Text(
            'googlePushCount',
            NULL,
            '10',
            _t('手动推送数量'),
            _t('手动推送时推送最新的N条内容，建议不超过100条')
        );
        $googlePushCount->input->setAttribute('class', 'mini');
        $form->addInput($googlePushCount);

        // ========== Bing推送配置 ==========

        /** 启用Bing推送 */
        $enableBingPush = new Typecho_Widget_Helper_Form_Element_Radio(
            'enableBingPush',
            array('1' => _t('启用'), '0' => _t('禁用')),
            '0',
            _t('启用Bing推送'),
            _t('开启后，文章发布/更新时会自动推送到Bing搜索引擎')
        );
        $form->addInput($enableBingPush);

        /** Bing API密钥 */
        $bingApiKey = new Typecho_Widget_Helper_Form_Element_Text(
            'bingApiKey',
            NULL,
            '',
            _t('Bing API密钥'),
            _t('在Bing Webmaster Tools中获取的API密钥')
        );
        $form->addInput($bingApiKey);

        /** Bing站点URL */
        $bingSiteUrl = new Typecho_Widget_Helper_Form_Element_Text(
            'bingSiteUrl',
            NULL,
            '',
            _t('Bing站点URL'),
            _t('在Bing Webmaster Tools中验证的站点URL')
        );
        $form->addInput($bingSiteUrl);

        /** 自动推送触发时机 */
        $bingPushTrigger = new Typecho_Widget_Helper_Form_Element_Checkbox(
            'bingPushTrigger',
            array(
                'publish' => _t('发布文章时'),
                'update' => _t('更新文章时')
            ),
            array('publish'),
            _t('自动推送触发'),
            _t('选择何时自动推送到Bing')
        );
        $form->addInput($bingPushTrigger->multiMode());

        /** 手动推送数量 */
        $bingPushCount = new Typecho_Widget_Helper_Form_Element_Text(
            'bingPushCount',
            NULL,
            '10',
            _t('手动推送数量'),
            _t('手动推送时推送最新的N条内容，建议不超过100条')
        );
        $bingPushCount->input->setAttribute('class', 'mini');
        $form->addInput($bingPushCount);

        // ========== 搜狗推送配置 ==========

        /** 启用搜狗推送 */
        $enableSogouPush = new Typecho_Widget_Helper_Form_Element_Radio(
            'enableSogouPush',
            array('1' => _t('启用'), '0' => _t('禁用')),
            '0',
            _t('启用搜狗推送'),
            _t('开启后，文章发布/更新时会自动推送到搜狗搜索引擎')
        );
        $form->addInput($enableSogouPush);

        /** 搜狗推送Token */
        $sogouToken = new Typecho_Widget_Helper_Form_Element_Text(
            'sogouToken',
            NULL,
            '',
            _t('搜狗推送Token'),
            _t('在搜狗站长平台中获取的推送接口令牌')
        );
        $form->addInput($sogouToken);

        /** 推送类型 */
        $sogouPushType = new Typecho_Widget_Helper_Form_Element_Radio(
            'sogouPushType',
            array('api' => _t('API推送'), 'sitemap' => _t('Sitemap推送')),
            'api',
            _t('推送方式'),
            _t('API推送：实时推送单个URL，速度更快<br />Sitemap推送：推送sitemap地址，批量提交')
        );
        $form->addInput($sogouPushType);

        /** 自动推送触发时机 */
        $sogouPushTrigger = new Typecho_Widget_Helper_Form_Element_Checkbox(
            'sogouPushTrigger',
            array(
                'publish' => _t('发布文章时'),
                'update' => _t('更新文章时')
            ),
            array('publish', 'update'),
            _t('自动推送触发'),
            _t('选择何时自动推送到搜狗')
        );
        $form->addInput($sogouPushTrigger->multiMode());

        /** 手动推送数量 */
        $sogouPushCount = new Typecho_Widget_Helper_Form_Element_Text(
            'sogouPushCount',
            NULL,
            '10',
            _t('手动推送数量'),
            _t('手动推送时推送最新的N条内容，建议不超过100条')
        );
        $sogouPushCount->input->setAttribute('class', 'mini');
        $form->addInput($sogouPushCount);

        // ========== 360推送配置 ==========

        /** 启用360推送 */
        $enablePush360Push = new Typecho_Widget_Helper_Form_Element_Radio(
            'enablePush360Push',
            array('1' => _t('启用'), '0' => _t('禁用')),
            '0',
            _t('启用360推送'),
            _t('开启后，文章发布/更新时会自动推送到360搜索引擎')
        );
        $form->addInput($enablePush360Push);

        /** 360站点 */
        $push360Site = new Typecho_Widget_Helper_Form_Element_Text(
            'push360Site',
            NULL,
            '',
            _t('360站点'),
            _t('360站长平台中验证的站点域名，如：example.com')
        );
        $form->addInput($push360Site);

        /** 360推送Token */
        $push360Token = new Typecho_Widget_Helper_Form_Element_Text(
            'push360Token',
            NULL,
            '',
            _t('360推送Token'),
            _t('在360站长平台中获取的推送接口令牌')
        );
        $form->addInput($push360Token);

        /** 推送类型 */
        $push360PushType = new Typecho_Widget_Helper_Form_Element_Radio(
            'push360PushType',
            array('api' => _t('API推送'), 'sitemap' => _t('Sitemap推送')),
            'api',
            _t('推送方式'),
            _t('API推送：实时推送单个URL，速度更快<br />Sitemap推送：推送sitemap地址，批量提交')
        );
        $form->addInput($push360PushType);

        /** 自动推送触发时机 */
        $push360PushTrigger = new Typecho_Widget_Helper_Form_Element_Checkbox(
            'push360PushTrigger',
            array(
                'publish' => _t('发布文章时'),
                'update' => _t('更新文章时')
            ),
            array('publish', 'update'),
            _t('自动推送触发'),
            _t('选择何时自动推送到360')
        );
        $form->addInput($push360PushTrigger->multiMode());

        /** 手动推送数量 */
        $push360PushCount = new Typecho_Widget_Helper_Form_Element_Text(
            'push360PushCount',
            NULL,
            '10',
            _t('手动推送数量'),
            _t('手动推送时推送最新的N条内容，建议不超过100条')
        );
        $push360PushCount->input->setAttribute('class', 'mini');
        $form->addInput($push360PushCount);

        echo '<script>
        document.addEventListener("DOMContentLoaded", function() {
            setTimeout(function() {
                // 将配置项移动到对应的标签页
                var baiduContent = document.getElementById("baidu-content");
                var googleContent = document.getElementById("google-content");
                var bingContent = document.getElementById("bing-content");
                var sogouContent = document.getElementById("sogou-content");
                var push360Content = document.getElementById("360-content");

                if (baiduContent) {
                    var baiduOptions = [];
                    var options = document.querySelectorAll(".typecho-option");
                    for (var i = 0; i < options.length; i++) {
                        var label = options[i].querySelector("label");
                        if (label && (
                            label.textContent.includes("百度推送") ||
                            label.textContent.includes("百度站点") ||
                            label.textContent.includes("百度推送Token") ||
                            label.textContent.includes("推送方式") ||
                            label.textContent.includes("自动推送触发") ||
                            label.textContent.includes("手动推送数量")
                        )) {
                            baiduOptions.push(options[i]);
                        }
                    }
                    for (var j = 0; j < baiduOptions.length; j++) {
                        baiduContent.appendChild(baiduOptions[j]);
                    }
                }

                if (googleContent) {
                    var googleOptions = [];
                    var options = document.querySelectorAll(".typecho-option");
                    for (var i = 0; i < options.length; i++) {
                        var label = options[i].querySelector("label");
                        if (label && (
                            label.textContent.includes("Google推送") ||
                            label.textContent.includes("Google API密钥") ||
                            (label.textContent.includes("自动推送触发") && options[i].innerHTML.includes("googlePushTrigger"))
                        )) {
                            googleOptions.push(options[i]);
                        }
                    }
                    for (var j = 0; j < googleOptions.length; j++) {
                        googleContent.appendChild(googleOptions[j]);
                    }
                }

                if (bingContent) {
                    var bingOptions = [];
                    var options = document.querySelectorAll(".typecho-option");
                    for (var i = 0; i < options.length; i++) {
                        var label = options[i].querySelector("label");
                        if (label && (
                            label.textContent.includes("Bing推送") ||
                            label.textContent.includes("Bing API密钥") ||
                            label.textContent.includes("Bing站点URL")
                        )) {
                            bingOptions.push(options[i]);
                        }
                    }
                    for (var j = 0; j < bingOptions.length; j++) {
                        bingContent.appendChild(bingOptions[j]);
                    }
                }

                if (sogouContent) {
                    var sogouOptions = [];
                    var options = document.querySelectorAll(".typecho-option");
                    for (var i = 0; i < options.length; i++) {
                        var label = options[i].querySelector("label");
                        if (label && (
                            label.textContent.includes("搜狗推送") ||
                            label.textContent.includes("搜狗推送Token")
                        )) {
                            sogouOptions.push(options[i]);
                        }
                    }
                    for (var j = 0; j < sogouOptions.length; j++) {
                        sogouContent.appendChild(sogouOptions[j]);
                    }
                }

                if (push360Content) {
                    var push360Options = [];
                    var options = document.querySelectorAll(".typecho-option");
                    for (var i = 0; i < options.length; i++) {
                        var label = options[i].querySelector("label");
                        if (label && (
                            label.textContent.includes("360推送") ||
                            label.textContent.includes("360站点") ||
                            label.textContent.includes("360推送Token")
                        )) {
                            push360Options.push(options[i]);
                        }
                    }
                    for (var j = 0; j < push360Options.length; j++) {
                        push360Content.appendChild(push360Options[j]);
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

        // 判断是新发布还是更新（created大于当前时间-60秒视为新发布）
        $isNewPublish = isset($contents['created']) && $contents['created'] >= time() - 60;

        // 推送到百度
        self::pushToSearchEngine('baidu', $pluginOptions, $permalink, $isNewPublish);

        // 推送到Google
        self::pushToSearchEngine('google', $pluginOptions, $permalink, $isNewPublish);

        // 推送到Bing
        self::pushToSearchEngine('bing', $pluginOptions, $permalink, $isNewPublish);

        // 推送到搜狗
        self::pushToSearchEngine('sogou', $pluginOptions, $permalink, $isNewPublish);

        // 推送到360
        self::pushToSearchEngine('360', $pluginOptions, $permalink, $isNewPublish);
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
            $result = $pusher->pushUrl($permalink);

            // 记录推送结果到日志
            if (!$result['success']) {
                error_log('uSitemap ' . $engine . '推送失败: ' . $result['message']);
            }
        } catch (Exception $e) {
            error_log('uSitemap ' . $engine . '推送异常: ' . $e->getMessage());
        }
    }
} 