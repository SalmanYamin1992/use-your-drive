<?php

namespace TheLion\UseyourDrive;

define('USEYOURDRIVE_CURRENT_BLOG_ID', get_current_blog_id());

class CSS
{
    public $custom_css;
    public $colors;
    public $loaders;
    public $css_template_path;
    public static $css_url = USEYOURDRIVE_CACHEURL.USEYOURDRIVE_CURRENT_BLOG_ID.'_style.min.css';
    public static $css_path = USEYOURDRIVE_CACHEDIR.USEYOURDRIVE_CURRENT_BLOG_ID.'_style.min.css';

    public function __construct($settings)
    {
        $this->custom_css = $settings['custom_css'];
        $this->colors = $settings['colors'];
        $this->loaders = $settings['loaders'];

        $this->css_template_path = USEYOURDRIVE_ROOTDIR.'/css/skin.'.$this->colors['style'].'.min.css';
    }

    public function register_style()
    {
        if (!file_exists(self::$css_path)) {
            $this->generate_custom_css();
        }

        wp_register_style('UseyourDrive.CustomStyle', self::$css_url, ['UseyourDrive'], filemtime(self::$css_path));
    }

    public function generate_custom_css()
    {
        $css = '';

        if (!empty($this->custom_css)) {
            $css .= $this->custom_css."\n";
        }

        if ('custom' === $this->loaders['style']) {
            $css .= '#UseyourDrive .loading{  background-image: url('.$this->loaders['loading'].');}'."\n";
            $css .= '#UseyourDrive .loading.upload{    background-image: url('.$this->loaders['upload'].');}'."\n";
            $css .= '#UseyourDrive .loading.error{  background-image: url('.$this->loaders['error'].');}'."\n";
            $css .= '#UseyourDrive .no_results{  background-image: url('.$this->loaders['no_results'].');}'."\n";
        }

        $css .= "
    iframe[src*='useyourdrive'] {
        background-image: url({$this->loaders['iframe']});
        background-repeat: no-repeat;
        background-position: center center;
        background-size: auto 128px;
    }\n";

        $css .= $this->get_basic_style_css();

        $css_minified = \TheLion\UseyourDrive\Helpers::compress_css($css);

        \file_put_contents(self::$css_path, $css_minified);
    }

    public function get_basic_style_css()
    {
        $css = file_get_contents($this->css_template_path);

        return preg_replace_callback('/%(.*)%/iU', [$this, 'fill_placeholder_styles'], $css);
    }

    public function fill_placeholder_styles($matches)
    {
        if (isset($this->colors[$matches[1]])) {
            return $this->colors[$matches[1]];
        }

        return 'initial';
    }

    public static function reset_custom_css()
    {
        @unlink(self::$css_path);
    }
}
