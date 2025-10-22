<?php

/**
 * Performance Optimization Functions
 * 
 * This file contains performance optimization functions for the FlexPress theme
 * to improve Lighthouse scores and overall site performance.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add performance optimization headers
 */
function flexpress_add_performance_headers()
{
    // Add cache control headers for static assets and ensure dynamic HTML is not cached for logged-in users
    if (!is_admin() && !wp_doing_ajax() && !headers_sent()) {
        $request_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';

        // Help proxies/CDNs differentiate based on login cookies
        header('Vary: Cookie');

        // Cache static assets for 1 year
        if (preg_match('/\.(css|js|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$/', $request_uri)) {
            header('Cache-Control: public, max-age=31536000, immutable');
        } else {
            // For HTML and other dynamic responses, ensure private/no-store for logged-in users
            if (is_user_logged_in()) {
                // Prevent intermediary caches and browsers from serving stale logged-in pages
                header('Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0');
                header('Pragma: no-cache');
                header('Expires: 0');
            } else {
                // Keep a modest public cache for anonymous HTML (tuned low)
                header('Cache-Control: public, max-age=300');
            }
        }

        // Add security headers
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('X-XSS-Protection: 1; mode=block');
    }
}
add_action('send_headers', 'flexpress_add_performance_headers');

/**
 * Ensure admin and REST responses are never cached
 */
function flexpress_admin_nocache_headers()
{
    if (function_exists('nocache_headers')) {
        nocache_headers();
    }
}
add_action('admin_init', 'flexpress_admin_nocache_headers');

/**
 * Add no-store headers for REST API responses
 *
 * @param bool   $served  Whether the request has already been served.
 * @param mixed  $result  Result to send to the client. Usually a WP_REST_Response.
 * @param object $request Request used to generate the response.
 * @param object $server  Server instance.
 * @return bool  Unmodified $served.
 */
function flexpress_rest_nocache_headers($served, $result, $request, $server)
{
    if (!headers_sent()) {
        header('Cache-Control: private, no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Vary: Cookie');
        header('Expires: 0');
    }
    return $served;
}
add_filter('rest_pre_serve_request', 'flexpress_rest_nocache_headers', 0, 4);

/**
 * Optimize WordPress queries
 */
function flexpress_optimize_queries()
{
    // Remove unnecessary WordPress features
    remove_action('wp_head', 'wp_generator');
    remove_action('wp_head', 'wlwmanifest_link');
    remove_action('wp_head', 'rsd_link');
    remove_action('wp_head', 'wp_shortlink_wp_head');
    remove_action('wp_head', 'adjacent_posts_rel_link_wp_head');

    // Remove emoji scripts
    remove_action('wp_head', 'print_emoji_detection_script', 7);
    remove_action('wp_print_styles', 'print_emoji_styles');
    remove_action('admin_print_scripts', 'print_emoji_detection_script');
    remove_action('admin_print_styles', 'print_emoji_styles');
    remove_filter('the_content_feed', 'wp_staticize_emoji');
    remove_filter('comment_text_rss', 'wp_staticize_emoji');
    remove_filter('wp_mail', 'wp_staticize_emoji_for_email');
}
add_action('init', 'flexpress_optimize_queries');

/**
 * Add critical CSS inline
 */
function flexpress_add_critical_css()
{
    if (!is_admin() && !wp_doing_ajax()) {
        $critical_css = '
        <style id="flexpress-critical-css">
        /* Critical CSS for above-the-fold content */
        body { margin: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        .container { max-width: 1200px; margin: 0 auto; padding: 0 15px; }
        .hero-section-wrapper { background: #000; color: #fff; }
        .btn { display: inline-block; padding: 12px 24px; text-decoration: none; border-radius: 4px; }
        .btn-primary { background: #007bff; color: #fff; }
        .loading { opacity: 0.7; }
        </style>';

        echo $critical_css;
    }
}
add_action('wp_head', 'flexpress_add_critical_css', 1);

/**
 * Optimize images with lazy loading
 */
function flexpress_add_lazy_loading_to_images($content)
{
    if (!is_admin() && !wp_doing_ajax()) {
        // Add lazy loading to images that don't already have it
        $content = preg_replace(
            '/<img(?!.*loading=)([^>]*)>/i',
            '<img loading="lazy"$1>',
            $content
        );

        // Add decoding="async" to images
        $content = preg_replace(
            '/<img(?!.*decoding=)([^>]*)>/i',
            '<img decoding="async"$1>',
            $content
        );
    }

    return $content;
}
add_filter('the_content', 'flexpress_add_lazy_loading_to_images');
add_filter('post_thumbnail_html', 'flexpress_add_lazy_loading_to_images');

/**
 * Add resource hints for better performance
 */
function flexpress_add_resource_hints()
{
    if (!is_admin() && !wp_doing_ajax()) {
        // Preconnect to external domains
        echo '<link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>' . "\n";
        echo '<link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>' . "\n";
        echo '<link rel="preconnect" href="https://storage.bunnycdn.com" crossorigin>' . "\n";

        // Prefetch next page
        if (is_home() || is_front_page()) {
            echo '<link rel="prefetch" href="' . home_url('/episodes/') . '">' . "\n";
            echo '<link rel="prefetch" href="' . home_url('/models/') . '">' . "\n";
        }
    }
}
add_action('wp_head', 'flexpress_add_resource_hints', 2);

/**
 * Optimize database queries
 */
function flexpress_optimize_database_queries()
{
    // Remove unnecessary database queries
    remove_action('wp_head', 'wp_generator');
    remove_action('wp_head', 'wlwmanifest_link');
    remove_action('wp_head', 'rsd_link');

    // Optimize post queries
    add_action('pre_get_posts', 'flexpress_optimize_post_queries');
}
add_action('init', 'flexpress_optimize_database_queries');

/**
 * Optimize post queries
 */
function flexpress_optimize_post_queries($query)
{
    if (!is_admin() && $query->is_main_query()) {
        // Limit posts per page for better performance
        if (is_home() || is_archive()) {
            $query->set('posts_per_page', 12);
        }

        // Optimize meta queries
        if (is_post_type_archive('episode')) {
            $query->set('meta_key', 'release_date');
            $query->set('orderby', 'meta_value');
            $query->set('order', 'DESC');
        }
    }
}

/**
 * Add performance monitoring
 */
function flexpress_add_performance_monitoring()
{
    if (!is_admin() && !wp_doing_ajax() && current_user_can('administrator')) {
        echo '<script>
        // Performance monitoring for administrators
        window.addEventListener("load", function() {
            if (window.performance && window.performance.timing) {
                const timing = window.performance.timing;
                
                // Only calculate if timing data is valid (loadEventEnd > 0)
                if (timing.loadEventEnd > 0) {
                    const loadTime = timing.loadEventEnd - timing.navigationStart;
                    const domReady = timing.domContentLoadedEventEnd - timing.navigationStart;
                    
                    // Only log in debug mode
                    if (' . (defined('WP_DEBUG') && WP_DEBUG ? 'true' : 'false') . ') {
                        console.log("Page Load Time: " + loadTime + "ms");
                        console.log("DOM Ready Time: " + domReady + "ms");
                    }
                    
                    // Log to server if needed (always check for slow pages)
                    if (loadTime > 3000) {
                        fetch("' . admin_url('admin-ajax.php') . '", {
                            method: "POST",
                            headers: { "Content-Type": "application/x-www-form-urlencoded" },
                            body: "action=log_slow_page&load_time=" + loadTime + "&url=" + encodeURIComponent(window.location.href)
                        });
                    }
                }
            }
        });
        </script>';
    }
}
add_action('wp_footer', 'flexpress_add_performance_monitoring');

/**
 * Log slow page loads
 */
function flexpress_log_slow_page()
{
    if (current_user_can('administrator')) {
        $load_time = intval($_POST['load_time']);
        $url = sanitize_url($_POST['url']);

        error_log("Slow page load: {$url} - {$load_time}ms");
        wp_die();
    }
}
add_action('wp_ajax_log_slow_page', 'flexpress_log_slow_page');
add_action('wp_ajax_nopriv_log_slow_page', 'flexpress_log_slow_page');

/**
 * Optimize CSS delivery
 */
function flexpress_optimize_css_delivery()
{
    if (!is_admin() && !wp_doing_ajax()) {
        // Add CSS optimization
        add_filter('style_loader_tag', 'flexpress_optimize_style_loader_tag', 10, 2);
    }
}
add_action('init', 'flexpress_optimize_css_delivery');

/**
 * Optimize style loader tag
 */
function flexpress_optimize_style_loader_tag($html, $handle)
{
    // Add media="print" onload="this.media='all'" for non-critical CSS
    $non_critical_css = array(
        'font-awesome',
        'slick-css',
        'slick-theme-css',
        'flexpress-gallery',
        'flexpress-casting-section',
        'flexpress-join-now-cta'
    );

    if (in_array($handle, $non_critical_css)) {
        $html = str_replace("rel='stylesheet'", "rel='preload' as='style' onload=\"this.onload=null;this.rel='stylesheet'\"", $html);
        $html .= '<noscript>' . str_replace("rel='preload' as='style' onload=\"this.onload=null;this.rel='stylesheet'\"", "rel='stylesheet'", $html) . '</noscript>';
    }

    return $html;
}

/**
 * Optimize JavaScript delivery
 */
function flexpress_optimize_js_delivery()
{
    if (!is_admin() && !wp_doing_ajax()) {
        // Add JavaScript optimization
        add_filter('script_loader_tag', 'flexpress_optimize_script_loader_tag', 10, 2);
    }
}
add_action('init', 'flexpress_optimize_js_delivery');

/**
 * Optimize script loader tag
 */
function flexpress_optimize_script_loader_tag($tag, $handle)
{
    // Add async attribute to non-critical scripts
    $async_scripts = array(
        'slick-js',
        'chart-js',
        'flexpress-gallery-lightbox',
        'flexpress-models-lazy-load'
    );

    if (in_array($handle, $async_scripts)) {
        $tag = str_replace('<script ', '<script async ', $tag);
    }

    return $tag;
}

/**
 * Add service worker for caching
 */
function flexpress_add_service_worker()
{
    if (!is_admin() && !wp_doing_ajax()) {
        echo '<script>
        if ("serviceWorker" in navigator) {
            window.addEventListener("load", function() {
                navigator.serviceWorker.register("' . get_template_directory_uri() . '/sw.js")
                    .then(function(registration) {
                        // Only log success in debug mode
                        if (' . (defined('WP_DEBUG') && WP_DEBUG ? 'true' : 'false') . ') {
                            console.log("ServiceWorker registration successful");
                        }
                    })
                    .catch(function(err) {
                        // Always log errors (important for debugging)
                        console.log("ServiceWorker registration failed: " + err.message);
                    });
            });
        }
        </script>';
    }
}
add_action('wp_footer', 'flexpress_add_service_worker');

/**
 * Create service worker file
 */
function flexpress_create_service_worker()
{
    $sw_file = get_template_directory() . '/sw.js';

    // Only create service worker if it doesn't exist
    if (file_exists($sw_file)) {
        return;
    }

    $sw_content = '
// FlexPress Service Worker
// - Never cache HTML
// - Cache static assets only (cache-first)
// - Bypass all admin routes completely

const CACHE_NAME = "flexpress-v2";
const ASSET_EXT = /(\\.(css|js|png|jpg|jpeg|gif|webp|svg|woff2?|ttf|eot)(\\?.*)?$)/i;

self.addEventListener("install", (event) => {
  // Skip waiting so updated SW takes control ASAP
  self.skipWaiting();
});

self.addEventListener("activate", (event) => {
  // Clean up old caches if needed and take control of clients
  event.waitUntil(
    caches.keys().then((keys) => Promise.all(keys.map((key) => {
      if (key !== CACHE_NAME) {
        return caches.delete(key);
      }
    }))).then(() => self.clients.claim())
  );
});

self.addEventListener("fetch", (event) => {
  const req = event.request;
  const url = new URL(req.url);

  // Only handle GET requests
  if (req.method !== "GET") {
    return;
  }

  // Never intercept admin or login routes
  if (url.pathname.startsWith("/wp-admin/") || url.pathname === "/wp-login.php") {
    return;
  }

  // Cache-first for static assets only
  if (ASSET_EXT.test(url.pathname)) {
    event.respondWith(
      caches.open(CACHE_NAME).then(async (cache) => {
        const cached = await cache.match(req);
        if (cached) return cached;
        const res = await fetch(req);
        // Only cache successful opaque/basic responses
        if (res && res.status === 200 && (res.type === "basic" || res.type === "opaque")) {
          cache.put(req, res.clone());
        }
        return res;
      })
    );
    return;
  }

  // For everything else (HTML, REST, etc.), do network-first and do not cache
  event.respondWith(fetch(req));
});
';

    // Check if file is writable before attempting to write
    if (!is_writable($sw_file) && file_exists($sw_file)) {
        error_log('FlexPress: Service worker file is not writable at ' . $sw_file . '. Please check file permissions.');
        return;
    }

    $result = file_put_contents($sw_file, $sw_content);
    if ($result === false) {
        error_log('FlexPress: Failed to write service worker file at ' . $sw_file . '. Check file permissions.');
    }
}
// Create service worker on admin init instead of theme setup to avoid header conflicts
add_action('admin_init', 'flexpress_create_service_worker');
