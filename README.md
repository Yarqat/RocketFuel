# RocketFuel Cache

**The only WordPress performance plugin you need.** Replaces 9 plugins. Page caching, CSS/JS optimization, lazy loading, database cleanup, and 160+ features.

[![WordPress](https://img.shields.io/badge/WordPress-6.2%2B-blue.svg)](https://wordpress.org)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)](https://php.net)
[![License](https://img.shields.io/badge/License-GPLv2-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)

## Replaces 9 Plugins — Save $385+/year

| Plugin Replaced | Their Price | What RocketFuel Covers |
|----------------|-------------|----------------------|
| WP Rocket | $59–299/yr | Page caching, minify, lazy load, preload, defer JS, CDN |
| ShortPixel / Imagify | $99/yr | Image compression, WebP, AVIF conversion |
| Perfmatters | $24.95/yr | Disable bloat, local analytics, script manager |
| Asset CleanUp Pro | $39/yr | Per-page CSS/JS control, unused CSS removal |
| Autoptimize | Free | CSS/JS combine + minify |
| Flying Scripts | $29/yr | Delay JS until user interaction |
| CAOS | $29/yr | Host Google Analytics/GTM locally |
| Heartbeat Control | Free | WordPress Heartbeat management |
| EWWW / Smush Pro | $36–99/yr | Image compression, WebP, lazy load |

## Free Features (89 features)

### Page Caching
- Full page caching with <10ms serving via advanced-cache.php drop-in
- GZIP pre-compressed cached pages
- Mobile-separate cache for mobile-specific themes
- Configurable cache lifespan
- Smart cache purging (only affected pages cleared on content change)
- Query string handling (strip UTM params, cache allowed params)
- Cache exclusion rules (URLs, cookies, user agents)
- Sitemap-based cache preloading
- Preload on publish (instant cache warming)
- Cache status headers (X-RocketFuel-Cache: HIT/MISS)

### CSS Optimization
- Minify CSS files
- Combine multiple CSS into single file
- CSS exclusion list
- Disable Gutenberg global styles
- Disable block library CSS on non-Gutenberg pages

### JavaScript Optimization
- Minify JavaScript files
- Combine JS files (header/footer separately, jQuery never combined)
- Defer JavaScript loading
- Remove jQuery Migrate
- JS exclusion list
- Disable WordPress Embeds script
- Disable Emoji script
- Disable Gutenberg block frontend JS

### Lazy Loading
- Lazy load images (native + IntersectionObserver)
- Lazy load iframes
- Lazy load videos
- YouTube thumbnail swap (save ~800KB per embed)
- Vimeo thumbnail swap
- Skip first N above-fold images
- Add missing image dimensions (prevent CLS)

### Font Optimization
- Host Google Fonts locally (GDPR compliant)
- Font-display: swap (prevent FOIT)
- Preload critical fonts
- Disable Google Fonts entirely

### Preloading & Prefetching
- DNS prefetching for external domains
- Preconnect for critical origins
- Preload key requests (fonts, CSS, JS)
- Sitemap-based cache preloading with speed control

### WordPress Cleanup (18 features)
- Disable Emojis (removes 14KB JS)
- Disable Embeds (removes wp-embed.js)
- Disable Dashicons on frontend (removes 46KB CSS)
- Disable XML-RPC
- Disable RSS Feeds
- Disable Self-Pingbacks
- Disable REST API for non-logged-in users
- Remove WordPress version
- Remove query strings from static resources
- Remove wlwmanifest link
- Remove RSD link
- Remove Shortlink
- Limit post revisions
- Disable Comments site-wide
- Disable Gravatar
- Disable Block Library CSS
- Disable Global Styles CSS
- Disable WooCommerce marketing bloat

### Database Optimization
- Delete post revisions (keep last N)
- Delete auto-drafts
- Delete trashed posts
- Delete spam comments
- Delete trashed comments
- Delete expired transients
- Delete orphaned post meta
- Optimize database tables

### Heartbeat Control
- Dashboard: Allow / Reduce / Disable
- Post Editor: Allow / Reduce / Disable
- Frontend: Allow / Reduce / Disable
- Custom frequency (15–300 seconds)

### CDN Support
- CDN URL rewriting for assets
- Configure included directories and excluded extensions

### Security Hardening
- Disable file editor (DISALLOW_FILE_EDIT)
- Disable directory browsing
- Block author enumeration scans
- Hide WordPress version

### Server Optimization
- GZIP compression (auto-detect and enable)
- Browser caching headers (Expires + Cache-Control)
- Server type auto-detection (Apache/Nginx/LiteSpeed)
- .htaccess optimization rules
- Nginx config generator
- LiteSpeed cache headers
- OPcache recommendations

### Performance Reports
- Google PageSpeed Insights integration
- Before vs after score comparison
- Core Web Vitals tracking (LCP, CLS, TTFB)
- 20 smart optimization suggestions with auto-fix

### Support
- Built-in support ticket system
- Auto-attaches system diagnostics
- Track tickets from WP admin

### Developer Features
- WP-CLI commands (20+)
- 20+ action hooks and 20+ filter hooks
- Import/export settings (JSON)
- Safe Mode (1-click disable all optimizations)
- Debug logging with auto-rotation
- Server info page
- Automatic conflict detection
- Crash detection with auto-recovery

### Admin UX
- Zero-config auto-setup (install, activate, done)
- Premium dark theme admin interface
- Admin bar cache controls
- Dashboard widget with stats
- Bulk post actions (clear cache)
- Post edit screen meta box
- Animated UI with glassmorphism effects

## Pro Features (80+ additional)

- Critical CSS generation (above-the-fold)
- Remove unused CSS per page
- Delay JavaScript until user interaction
- Script Manager (per-page CSS/JS control)
- Image optimization (lossy/lossless compression)
- WebP and AVIF conversion
- Adaptive responsive images
- Local Google Analytics and GTM hosting
- Minimal Analytics (<1KB replacement)
- Instant Page (preload on hover)
- Scheduled database cleanup
- CDN integrations (Cloudflare, BunnyCDN, KeyCDN, Varnish, Sucuri)
- WooCommerce-specific optimizations
- Cache analytics and hit rate tracking
- PageSpeed Insights score tracking over time
- Weekly email performance reports
- Change login URL
- Security headers (X-Frame-Options, CSP, etc.)
- White-label mode for agencies
- REST API for programmatic control
- Multisite network support
- Priority support

## Requirements

- PHP 7.4 or higher
- WordPress 6.2 or higher
- Any web server (Apache, Nginx, LiteSpeed)

## Installation

1. Download the plugin ZIP
2. Go to WordPress Admin → Plugins → Add New → Upload Plugin
3. Upload the ZIP and click Install Now
4. Activate the plugin
5. That's it — RocketFuel auto-configures optimal settings

## 15-Day Free Trial

Try all Pro features free for 15 days. No credit card required. Just enter your email in RocketFuel → License tab.

## Support

- [Documentation](https://shahfahad.info/rocketfuel-cache/docs/)
- [Support](https://shahfahad.info/support/)

## Author

**Shah Fahad** — [shahfahad.info](https://shahfahad.info)

## License

GPLv2 or later — [https://www.gnu.org/licenses/gpl-2.0.html](https://www.gnu.org/licenses/gpl-2.0.html)
