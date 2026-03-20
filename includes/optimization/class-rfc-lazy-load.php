<?php
defined('ABSPATH') || exit;

final class RFC_Lazy_Load {

    private $settings;
    private $image_counter = 0;
    private $skip_count;
    private $exclusions = [];

    public function __construct(RFC_Settings $settings) {
        $this->settings = $settings;

        if ($this->settings->isSafeMode()) {
            return;
        }

        $this->skip_count = (int) $this->settings->get('lazy_load_exclude_count', 2);
        $this->exclusions = $this->parseExclusions();

        if ($this->settings->get('lazy_load_images', true)) {
            add_filter('the_content', [$this, 'processImages'], 200);
            add_filter('post_thumbnail_html', [$this, 'processImages'], 200);
            add_filter('widget_text', [$this, 'processImages'], 200);
            add_filter('get_avatar', [$this, 'processImages'], 200);
        }

        if ($this->settings->get('lazy_load_iframes', true)) {
            add_filter('the_content', [$this, 'processIframes'], 201);
            add_filter('widget_text', [$this, 'processIframes'], 201);
        }

        if ($this->settings->get('lazy_load_videos', true)) {
            add_filter('the_content', [$this, 'processVideos'], 202);
        }

        add_action('wp_enqueue_scripts', [$this, 'enqueueAssets']);
        add_action('wp_footer', [$this, 'inlineScript'], 5);
    }

    public function processImages($content) {
        if (empty($content) || is_admin() || is_feed()) {
            return $content;
        }

        return preg_replace_callback('/<img\s([^>]+)>/i', function ($match) {
            $tag = $match[0];
            $attrs = $match[1];

            if ($this->shouldSkipImage($attrs)) {
                return $tag;
            }

            $this->image_counter++;

            if ($this->image_counter <= $this->skip_count) {
                if (strpos($attrs, 'fetchpriority') === false && $this->image_counter === 1) {
                    $tag = str_replace('<img ', '<img fetchpriority="high" ', $tag);
                }
                return $tag;
            }

            $placeholder = $this->getPlaceholder($attrs);

            if (preg_match('/src=["\']([^"\']+)["\']/', $attrs, $src_match)) {
                $tag = str_replace($src_match[0], 'src="' . $placeholder . '" data-rfc-src="' . esc_attr($src_match[1]) . '"', $tag);
            }

            if (preg_match('/srcset=["\']([^"\']+)["\']/', $attrs, $srcset_match)) {
                $tag = str_replace($srcset_match[0], 'data-rfc-srcset="' . esc_attr($srcset_match[1]) . '"', $tag);
            }

            if (strpos($tag, 'loading=') === false) {
                $tag = str_replace('<img ', '<img loading="lazy" ', $tag);
            }

            $tag = str_replace('<img ', '<img class="rfc-lazy" ', $tag);

            return $tag;
        }, $content);
    }

    public function processIframes($content) {
        if (empty($content) || is_admin() || is_feed()) {
            return $content;
        }

        return preg_replace_callback('/<iframe\s([^>]+)><\/iframe>/i', function ($match) {
            $tag = $match[0];
            $attrs = $match[1];

            if ($this->shouldSkipElement($attrs)) {
                return $tag;
            }

            if ($this->settings->get('youtube_thumbnail_swap', true) && strpos($attrs, 'youtube') !== false) {
                return $this->youtubeThumbSwap($attrs);
            }

            if ($this->settings->get('vimeo_thumbnail_swap', true) && strpos($attrs, 'vimeo') !== false) {
                return $this->vimeoThumbSwap($attrs);
            }

            if (preg_match('/src=["\']([^"\']+)["\']/', $attrs, $src_match)) {
                $tag = str_replace($src_match[0], 'data-rfc-src="' . esc_attr($src_match[1]) . '"', $tag);
            }

            if (strpos($tag, 'loading=') === false) {
                $tag = str_replace('<iframe ', '<iframe loading="lazy" ', $tag);
            }

            $tag = str_replace('<iframe ', '<iframe class="rfc-lazy-iframe" ', $tag);

            return $tag;
        }, $content);
    }

    public function processVideos($content) {
        if (empty($content) || is_admin() || is_feed()) {
            return $content;
        }

        return preg_replace_callback('/<video\s([^>]*)>(.*?)<\/video>/is', function ($match) {
            $tag = $match[0];
            $attrs = $match[1];

            if ($this->shouldSkipElement($attrs)) {
                return $tag;
            }

            if (preg_match('/src=["\']([^"\']+)["\']/', $attrs, $src_match)) {
                $tag = str_replace($src_match[0], 'data-rfc-src="' . esc_attr($src_match[1]) . '"', $tag);
            }

            $tag = preg_replace_callback('/<source\s([^>]+)>/', function ($sm) {
                $source_attrs = $sm[1];
                if (preg_match('/src=["\']([^"\']+)["\']/', $source_attrs, $ss)) {
                    return str_replace($ss[0], 'data-rfc-src="' . esc_attr($ss[1]) . '"', $sm[0]);
                }
                return $sm[0];
            }, $tag);

            if (strpos($tag, 'preload') !== false) {
                $tag = preg_replace('/preload=["\'][^"\']*["\']/', 'preload="none"', $tag);
            } else {
                $tag = str_replace('<video ', '<video preload="none" ', $tag);
            }

            $tag = str_replace('<video ', '<video class="rfc-lazy-video" ', $tag);

            return $tag;
        }, $content);
    }

    public function enqueueAssets() {
        if (is_admin()) {
            return;
        }

        $js_file = RFC_PATH . 'assets/js/lazyload.js';
        $version = file_exists($js_file) ? filemtime($js_file) : RFC_VERSION;

        wp_enqueue_script(
            'rfc-lazyload',
            RFC_URL . 'assets/js/lazyload.js',
            [],
            $version,
            true
        );
    }

    public function inlineScript() {
        if (is_admin()) {
            return;
        }
        ?>
        <script data-rfc-skip>
        (function(){
            if(!('IntersectionObserver' in window)){
                var els=document.querySelectorAll('.rfc-lazy,.rfc-lazy-iframe,.rfc-lazy-video,[data-rfc-src]');
                for(var i=0;i<els.length;i++){
                    var s=els[i].getAttribute('data-rfc-src');
                    if(s)els[i].src=s;
                    var ss=els[i].getAttribute('data-rfc-srcset');
                    if(ss)els[i].srcset=ss;
                }
                return;
            }
            var observer=new IntersectionObserver(function(entries){
                entries.forEach(function(entry){
                    if(!entry.isIntersecting)return;
                    var el=entry.target;
                    var src=el.getAttribute('data-rfc-src');
                    if(src){el.src=src;el.removeAttribute('data-rfc-src');}
                    var srcset=el.getAttribute('data-rfc-srcset');
                    if(srcset){el.srcset=srcset;el.removeAttribute('data-rfc-srcset');}
                    el.classList.remove('rfc-lazy','rfc-lazy-iframe','rfc-lazy-video');
                    observer.unobserve(el);
                });
            },{rootMargin:'250px 0px',threshold:0.01});
            document.querySelectorAll('.rfc-lazy,.rfc-lazy-iframe,.rfc-lazy-video,[data-rfc-src]').forEach(function(el){
                observer.observe(el);
            });
            document.querySelectorAll('.rfc-yt-swap').forEach(function(wrap){
                wrap.addEventListener('click',function(){
                    var id=this.getAttribute('data-rfc-yt');
                    if(!id)return;
                    var ifr=document.createElement('iframe');
                    ifr.src='https://www.youtube.com/embed/'+id+'?autoplay=1';
                    ifr.setAttribute('allow','accelerometer;autoplay;clipboard-write;encrypted-media;gyroscope;picture-in-picture');
                    ifr.setAttribute('allowfullscreen','');
                    ifr.style.cssText='position:absolute;top:0;left:0;width:100%;height:100%;border:0';
                    this.innerHTML='';
                    this.appendChild(ifr);
                });
            });
            document.querySelectorAll('.rfc-vimeo-swap').forEach(function(wrap){
                wrap.addEventListener('click',function(){
                    var id=this.getAttribute('data-rfc-vimeo');
                    if(!id)return;
                    var ifr=document.createElement('iframe');
                    ifr.src='https://player.vimeo.com/video/'+id+'?autoplay=1';
                    ifr.setAttribute('allow','autoplay;fullscreen;picture-in-picture');
                    ifr.setAttribute('allowfullscreen','');
                    ifr.style.cssText='position:absolute;top:0;left:0;width:100%;height:100%;border:0';
                    this.innerHTML='';
                    this.appendChild(ifr);
                });
            });
        })();
        </script>
        <?php
    }

    private function youtubeThumbSwap($attrs) {
        $video_id = '';

        if (preg_match('/src=["\'](?:https?:)?\/\/(?:www\.)?youtube\.com\/embed\/([a-zA-Z0-9_-]+)/', $attrs, $yt)) {
            $video_id = $yt[1];
        }

        if (empty($video_id)) {
            return '<iframe ' . $attrs . '></iframe>';
        }

        $width = '100%';
        $height = '100%';
        if (preg_match('/width=["\'](\d+)["\']/', $attrs, $wm)) {
            $width = $wm[1] . 'px';
        }
        if (preg_match('/height=["\'](\d+)["\']/', $attrs, $hm)) {
            $height = $hm[1] . 'px';
        }

        $thumb_url = 'https://i.ytimg.com/vi/' . $video_id . '/hqdefault.jpg';

        $html = '<div class="rfc-yt-swap" data-rfc-yt="' . esc_attr($video_id) . '" ';
        $html .= 'style="position:relative;cursor:pointer;overflow:hidden;';
        $html .= 'max-width:' . $width . ';aspect-ratio:16/9;background:#000;">';
        $html .= '<img src="' . esc_url($thumb_url) . '" alt="" loading="lazy" ';
        $html .= 'style="width:100%;height:100%;object-fit:cover;">';
        $html .= '<div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);';
        $html .= 'width:68px;height:48px;background:rgba(0,0,0,.8);border-radius:14px;">';
        $html .= '<svg viewBox="0 0 68 48" style="width:100%;height:100%">';
        $html .= '<path d="M66.52 7.74c-.78-2.93-2.49-5.41-5.42-6.19C55.79.13 34 0 34 0S12.21.13 6.9 1.55';
        $html .= 'C3.97 2.33 2.27 4.81 1.48 7.74.06 13.05 0 24 0 24s.06 10.95 1.48 16.26';
        $html .= 'c.78 2.93 2.49 5.41 5.42 6.19C12.21 47.87 34 48 34 48s21.79-.13 27.1-1.55';
        $html .= 'c2.93-.78 4.64-3.26 5.42-6.19C67.94 34.95 68 24 68 24S67.94 13.05 66.52 7.74z" ';
        $html .= 'fill="red"/><path d="M45 24L27 14v20" fill="#fff"/></svg></div></div>';

        return $html;
    }

    private function vimeoThumbSwap($attrs) {
        $video_id = '';

        if (preg_match('/src=["\'](?:https?:)?\/\/(?:player\.)?vimeo\.com\/(?:video\/)?(\d+)/', $attrs, $vm)) {
            $video_id = $vm[1];
        }

        if (empty($video_id)) {
            return '<iframe ' . $attrs . '></iframe>';
        }

        $width = '100%';
        $height = '100%';
        if (preg_match('/width=["\'](\d+)["\']/', $attrs, $wm)) {
            $width = $wm[1] . 'px';
        }
        if (preg_match('/height=["\'](\d+)["\']/', $attrs, $hm)) {
            $height = $hm[1] . 'px';
        }

        $thumb_url = 'https://vumbnail.com/' . $video_id . '.jpg';

        $html = '<div class="rfc-vimeo-swap" data-rfc-vimeo="' . esc_attr($video_id) . '" ';
        $html .= 'style="position:relative;cursor:pointer;overflow:hidden;';
        $html .= 'max-width:' . $width . ';aspect-ratio:16/9;background:#000;">';
        $html .= '<img src="' . esc_url($thumb_url) . '" alt="" loading="lazy" ';
        $html .= 'style="width:100%;height:100%;object-fit:cover;">';
        $html .= '<div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);';
        $html .= 'width:65px;height:65px;background:rgba(0,173,239,.9);border-radius:50%;">';
        $html .= '<svg viewBox="0 0 40 40" style="width:100%;height:100%;padding:12px;box-sizing:border-box">';
        $html .= '<polygon points="14,10 14,30 30,20" fill="#fff"/></svg></div></div>';

        return $html;
    }

    private function shouldSkipImage($attrs) {
        if (strpos($attrs, 'data-rfc-src') !== false) {
            return true;
        }

        if (strpos($attrs, 'data-rfc-skip') !== false) {
            return true;
        }

        if (preg_match('/class=["\'][^"\']*skip-lazy[^"\']*["\']/', $attrs)) {
            return true;
        }

        if (preg_match('/class=["\'][^"\']*no-lazy[^"\']*["\']/', $attrs)) {
            return true;
        }

        foreach ($this->exclusions as $pattern) {
            if (strpos($attrs, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }

    private function shouldSkipElement($attrs) {
        if (strpos($attrs, 'data-rfc-skip') !== false) {
            return true;
        }

        if (strpos($attrs, 'data-rfc-src') !== false) {
            return true;
        }

        foreach ($this->exclusions as $pattern) {
            if (strpos($attrs, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }

    private function getPlaceholder($attrs) {
        $method = $this->settings->get('lazy_load_placeholder', 'transparent');

        if ($method === 'svg') {
            $w = 1;
            $h = 1;
            if (preg_match('/width=["\'](\d+)["\']/', $attrs, $wm)) {
                $w = (int) $wm[1];
            }
            if (preg_match('/height=["\'](\d+)["\']/', $attrs, $hm)) {
                $h = (int) $hm[1];
            }
            return 'data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 ' . $w . ' ' . $h . '%22%3E%3C/svg%3E';
        }

        return 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';
    }

    private function parseExclusions() {
        $raw = $this->settings->get('lazy_load_exclusions', '');
        if (empty($raw)) {
            return [];
        }

        return array_filter(array_map('trim', preg_split('/[\n,]+/', $raw)));
    }
}
