<?php

/**
 * Plugin Name: bbPress Exif Lightbox
 * Plugin URI: https://github.com/BenjaminMedia/bbpress-exif-lightbox
 * Description: Display exif data for the images attached in posts and comments
 * Author:      Michael Sørensen
 * Domain Path: /languages/
 * Version:     0.0.1
 * License:     GPL
 */

namespace bbPress\DisplayExifLightbox;

// Do not access this file directly
if (!defined('ABSPATH')) {
    exit;
}
// Handle autoload so we can use namespaces
spl_autoload_register(function ($className) {
    if (strpos($className, __NAMESPACE__) !== false) {
        $className = str_replace("\\", DIRECTORY_SEPARATOR, $className);
        require_once(__DIR__ . DIRECTORY_SEPARATOR . Plugin::CLASS_DIR . DIRECTORY_SEPARATOR . $className . '.php');
    }
});
class Plugin
{
    /**
     * Text domain for translators
     */
    const TEXT_DOMAIN = 'bbpress-exif-lightbox';
    const CLASS_DIR = 'src';
    /**
     * @var object Instance of this class.
     */
    private static $instance;
    public $settings;
    /**
     * @var string Filename of this class.
     */
    public $file;
    /**
     * @var string Basename of this class.
     */
    public $basename;
    /**
     * @var string Plugins directory for this plugin.
     */
    public $plugin_dir;
    /**
     * @var string Plugins url for this plugin.
     */
    public $plugin_url;

    public $settingsLabel = '';
    /**
     * Do not load this more than once.
     */
    private function __construct()
    {
        // Set plugin file variables
        $this->file = __FILE__;
        $this->basename = plugin_basename($this->file);
        $this->plugin_dir = plugin_dir_path($this->file);
        $this->plugin_url = plugin_dir_url($this->file);
        $this->settingsLabel = 'Display Image Exif';
        // Load textdomain
        load_plugin_textdomain(self::TEXT_DOMAIN, false, dirname($this->basename) . '/languages');
    }

    /**
     * Returns the instance of this class.
     */
    public static function instance()
    {
        if (!self::$instance) {
            self::$instance = new self;
            global $bbp_rma;
            $bbp_rma = self::$instance;
            self::$instance->boostrap();
            /**
             * Run after the plugin has been loaded.
             */
            do_action('bbpress-exif-lightbox_loaded');
        }
        return self::$instance;
    }

    private function boostrap() {
        if (class_exists('bbPress')) {
            // Setup plugin

            add_action('wp_enqueue_scripts', [$this, 'includeJs']);
            add_action('wp_enqueue_scripts', [$this, 'includeCss']);
            add_action('bbp_theme_after_topic_content', [$this, 'displayExifLightbox']);
            add_action('bbp_theme_after_reply_content', [$this, 'displayExifLightbox']);

            add_action('wp_footer', [$this, 'lightboxContent']);
        }
    }

    public function includeJs() {
        wp_enqueue_script('photoswipe-js', plugins_url('photoswipe.min.js', __FILE__ ));
        wp_enqueue_script('photoswipeui-js', plugins_url('photoswipe-ui-default.min.js', __FILE__ ));
        wp_enqueue_script('exif-lightbox-js', plugins_url('exif-lightbox.js', __FILE__ ));
    }

    public function includeCss() {
        wp_enqueue_style('photoswipe', plugins_url('photoswipe.css', __FILE__));
        wp_enqueue_style('photoswipe-default-skin', plugins_url('default-skin/default-skin.css', __FILE__));
    }

    public function displayExifLightbox() {
        $post_id = get_the_ID();

        $args = [
            'order' => 'ASC',
            'post_mime_type' => 'image',
            'post_parent' => $post_id,
            'post_status' => null,
            'post_type' => 'attachment',
        ];

        $attachments = get_children( $args );

        // Skip if empty
        if (!$attachments) {
            return;
        }

        $output = "";
        $exifIsEmpty = false;
        // Go through every attachment and echo exif data
        foreach ($attachments as $attachment)
        {
            $metadata = wp_get_attachment_metadata($attachment->ID);

            $exif = '';

            foreach ($metadata['image_meta'] as $key => $value) {
                if ($key == 'aperture' && $value != '0') {
                    $exif .= ' | <strong>Aperture:</strong>&nbsp;f/' . $value;
                }
                if ($key == 'camera' && $value != '') {
                    $exif .= ' | <strong>Camera:</strong>&nbsp;' . $value;
                }
                if ($key == 'shutter_speed' && $value != '0') {
                    $exif .= ' | <strong>Exposure time:</strong>&nbsp;' . self::format_exp_time($value);
                }
                if ($key == 'focal_length' && $value != '0') {
                    $exif .= ' | <strong>Focal length:</strong>&nbsp;' . $value . ' mm';
                }
                if ($key == 'iso' && $value != '0') {
                    $exif .= ' | <strong>ISO:</strong>&nbsp;' . $value;
                }
            }

            if ($exif) {
                /*$output .= '
    <div class="exif-data">
    <div class="mdImg">
    <a href="' . wp_get_attachment_url($attachment->ID) . '"><img src="' . wp_get_attachment_url($attachment->ID) . '" alt="" title="" width="124" height="124" /></a></div>
    </div><span class="mdLabel">exif</span><span class="exif">' . $exif . '</span>';*/
                $output .= '<div class="exif-data" data-att-id="'.$attachment->ID.'" style="display: none"><span class="mdLabel">exif</span><span class="exif">' . $exif . '</span></div>';
            }
        }

        if(!$exifIsEmpty)
            echo $output;
    }

    /**
     * Return the exposure time formatted
     * E.g.:
     * 2 seconds => "2
     * 0.25 second => 1/4
     */
    private function format_exp_time($time) {
        if ($time<1){
            $tmp = (1/$time);
            $tmp = round($tmp * 1000) / 1000;   //make sure time=0.0666667 is turning into 1/15 and not 1/14.999999
            return '1/' . $tmp;
        }

        return '"' . $time;
    }

    public function lightboxContent()
    {
        ?>
<!-- Root element of PhotoSwipe. Must have class pswp. -->
<div class="pswp" tabindex="-1" role="dialog" aria-hidden="true">

    <!-- Background of PhotoSwipe.
    It's a separate element as animating opacity is faster than rgba(). -->
    <div class="pswp__bg"></div>

    <!-- Slides wrapper with overflow:hidden. -->
    <div class="pswp__scroll-wrap">

        <!-- Container that holds slides.
            PhotoSwipe keeps only 3 of them in the DOM to save memory.
            Don't modify these 3 pswp__item elements, data is added later on. -->
        <div class="pswp__container">
            <div class="pswp__item"></div>
            <div class="pswp__item"></div>
            <div class="pswp__item"></div>
        </div>

        <!-- Default (PhotoSwipeUI_Default) interface on top of sliding area. Can be changed. -->
        <div class="pswp__ui pswp__ui--hidden">

            <div class="pswp__top-bar">

                <!--  Controls are self-explanatory. Order can be changed. -->

                <div class="pswp__counter"></div>

                <button class="pswp__button pswp__button--close" title="Close (Esc)"></button>

                <button class="pswp__button pswp__button--share" title="Share"></button>

                <button class="pswp__button pswp__button--fs" title="Toggle fullscreen"></button>

                <button class="pswp__button pswp__button--zoom" title="Zoom in/out"></button>

                <!-- Preloader demo http://codepen.io/dimsemenov/pen/yyBWoR -->
                <!-- element will get class pswp__preloader--active when preloader is running -->
                <div class="pswp__preloader">
                    <div class="pswp__preloader__icn">
                      <div class="pswp__preloader__cut">
                        <div class="pswp__preloader__donut"></div>
                      </div>
                    </div>
                </div>
            </div>

            <div class="pswp__share-modal pswp__share-modal--hidden pswp__single-tap">
                <div class="pswp__share-tooltip"></div>
            </div>

            <button class="pswp__button pswp__button--arrow--left" title="Previous (arrow left)">
            </button>

            <button class="pswp__button pswp__button--arrow--right" title="Next (arrow right)">
            </button>

            <div class="pswp__caption">
                <div class="pswp__caption__center"></div>
            </div>

        </div>

    </div>

</div>
<?php
    }
}
/**
 * @return Plugin $instance returns an instance of the plugin
 */
function instance()
{
    return Plugin::instance();
}

add_action('plugins_loaded', __NAMESPACE__ . '\instance', 0);