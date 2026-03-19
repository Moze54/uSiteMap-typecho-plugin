<?php
class uSiteMap_XML extends Typecho_Widget implements Widget_Interface_Do
{
    public function action()
    {
        //检查更新时间
        $dir = __TYPECHO_ROOT_DIR__ . __TYPECHO_PLUGIN_DIR__ . '/uSiteMap/sitemap';
        $XmlTime = filectime($dir);
        $cachetime = Helper::options()->plugin('uSiteMap')->sitemap_cachetime;
        $cachetime = $cachetime * 86400;
        header('Cache-Control:max-age=' . $cachetime);
        header("Content-Type: application/xml");
        if (time() - $XmlTime > $cachetime) {
            require_once("Action.php");
            update('update', 'auto');
        };
        //返回xml
        $myfile = fopen($dir, "r");
        echo fread($myfile, filesize($dir));
        fclose($myfile);
    }
}
