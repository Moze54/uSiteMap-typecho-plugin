<?php
/**
 * 自动生成网站地图插件
 * 
 * @package Sitemap
 * @author cxuxrxsxoxr
 * @version 1.0.0
 * @link http://www.typecho.org
 */
class Sitemap_Plugin implements Typecho_Plugin_Interface
{
    /**
     * 激活插件方法
     */
    public static function activate()
    {
        // 注册路由
        Helper::addRoute('sitemap', '/sitemap.xml', 'Sitemap_Action', 'index');
        
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
        // 包含内容类型
        $contentTypes = new Typecho_Widget_Helper_Form_Element_Checkbox(
            'contentTypes',
            array(
                'post' => '文章 (Posts)',
                'page' => '独立页面 (Pages)',
                'category' => '分类 (Categories)',
                'tag' => '标签 (Tags)'
            ),
            array('post', 'page', 'category', 'tag'),
            '包含的内容类型',
            '选择要包含在站点地图中的内容类型'
        );
        $form->addInput($contentTypes);
        
        // 排除内容
        $excludeCids = new Typecho_Widget_Helper_Form_Element_Textarea(
            'excludeCids',
            NULL,
            '',
            '排除的内容ID',
            '输入要排除的文章或页面ID，每行一个'
        );
        $form->addInput($excludeCids);
        
        // 是否包含首页
        $includeIndex = new Typecho_Widget_Helper_Form_Element_Radio(
            'includeIndex',
            array(
                'true' => '是',
                'false' => '否'
            ),
            'true',
            '包含首页',
            '是否在站点地图中包含首页'
        );
        $form->addInput($includeIndex);
    }
    
    /**
     * 个人用户的配置面板
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form)
    {
    }
} 