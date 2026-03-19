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
        
        return '插件已禁用';
    }
    
    /**
     * 获取插件配置面板
     */
    public static function config(Typecho_Widget_Helper_Form $form)
    {
        echo '<style>
        .usitemap-container {
            max-width: 800px;
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
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 2px solid #e1e4e8;
            padding-bottom: 10px;
        }
        .usitemap-tab {
            padding: 10px 20px;
            background: #f8f9fa;
            border: none;
            border-radius: 6px 6px 0 0;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
            color: #555;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .usitemap-tab:hover {
            background: #e9ecef;
        }
        .usitemap-tab.active {
            background: #90caf9;
            color: #0d47a1;
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
        </style>';

        echo '<div class="usitemap-container">
            <div class="usitemap-header">
                <h2>🗺️ uSitemap 站点地图</h2>
                <p>自动生成符合搜索引擎标准的 XML 站点地图，帮助搜索引擎更好地索引您的网站</p>
            </div>

            <div class="usitemap-tabs">
                <button class="usitemap-tab active" data-tab="basic">📄 内容设置</button>
                <button class="usitemap-tab" data-tab="filter">🔍 过滤设置</button>
                <button class="usitemap-tab" data-tab="seo">⚙️ SEO 设置</button>
            </div>

            <div id="basic-section" class="usitemap-section active">
                <div class="usitemap-section-title">📄 内容设置</div>
                <div id="basic-content"></div>
            </div>

            <div id="filter-section" class="usitemap-section">
                <div class="usitemap-section-title">🔍 过滤设置</div>
                <div id="filter-content"></div>
            </div>

            <div id="seo-section" class="usitemap-section">
                <div class="usitemap-section-title">⚙️ SEO 设置</div>
                <div id="seo-content"></div>
            </div>

            <div class="usitemap-tip">
                <div class="usitemap-tip-title">💡 使用提示</div>
                <ul>
                    <li>启用插件后，访问 <strong>/sitemap.xml</strong> 查看站点地图</li>
                    <li>建议在 Google Search Console、百度站长工具中提交站点地图</li>
                    <li>更新频率建议：活跃博客选"每天"，静态站点选"每周"</li>
                    <li>优先级范围 0.0-1.0，重要内容建议设置更高优先级</li>
                </ul>
            </div>
        </div>';

        echo '<script>
        document.addEventListener("DOMContentLoaded", function() {
            // 标签切换功能
            const tabs = document.querySelectorAll(".usitemap-tab");
            const sections = document.querySelectorAll(".usitemap-section");

            tabs.forEach(tab => {
                tab.addEventListener("click", function() {
                    const targetTab = this.getAttribute("data-tab");

                    // 移除所有 active 类
                    tabs.forEach(t => t.classList.remove("active"));
                    sections.forEach(s => s.classList.remove("active"));

                    // 添加 active 类到当前标签和对应的内容区
                    this.classList.add("active");
                    document.getElementById(targetTab + "-section").classList.add("active");
                });
            });

            // 将表单元素移动到对应的分区
            function moveToSection(fieldNames, targetId) {
                fieldNames.forEach(name => {
                    const options = document.querySelectorAll(".typecho-option");
                    options.forEach(option => {
                        const label = option.querySelector("label");
                        if (label && label.textContent.includes(name)) {
                            document.getElementById(targetId).appendChild(option);
                        }
                    });
                });
            }

            // 分组表单元素
            setTimeout(function() {
                moveToSection(["包含内容", "包含首页"], "basic-content");
                moveToSection(["排除内容", "密码保护内容"], "filter-content");
                moveToSection(["更新频率", "默认优先级", "最大条目数"], "seo-content");
            }, 100);
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
    }
    
    /**
     * 个人用户的配置面板
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form)
    {
    }
} 