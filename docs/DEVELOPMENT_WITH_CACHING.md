# Development Workflow with Caching

## Cache-Friendly Development Practices

### 1. **Force Refresh Static Assets**

When developing CSS/JS, use these methods to bypass cache:

#### Browser Dev Tools

```bash
# Hard refresh (bypasses cache)
Ctrl+Shift+R (Linux/Windows)
Cmd+Shift+R (Mac)

# Or disable cache in DevTools
F12 → Network tab → Check "Disable cache"
```

#### Version Your Assets

```php
// In functions.php - add version parameter
wp_enqueue_style('flexpress-style', get_stylesheet_uri(), array(), FLEXPRESS_VERSION);
wp_enqueue_script('flexpress-script', get_template_directory_uri() . '/assets/js/main.js', array(), FLEXPRESS_VERSION);
```

#### Query Parameters

```php
// Add timestamp for development
wp_enqueue_style('flexpress-style', get_stylesheet_uri(), array(), time());
```

### 2. **WordPress Admin Changes**

- **No caching** - changes appear immediately
- **Settings, posts, pages** - instant updates
- **Plugin configurations** - real-time changes

### 3. **PHP Code Changes**

- **functions.php** - changes appear after 1 hour max (usually much faster)
- **Template files** - same 1-hour cache
- **Database changes** - immediate (no caching)

### 4. **Development Commands**

#### Clear All Caches

```bash
# Restart Docker (clears all caches)
docker-compose restart

# Or rebuild if needed
docker-compose down && docker-compose up --build -d
```

#### Test Without Cache

```bash
# Test with curl (bypasses browser cache)
curl -H "Cache-Control: no-cache" http://172.17.0.1:8085

# Test specific page
curl -H "Cache-Control: no-cache" http://172.17.0.1:8085/some-page/
```

### 5. **Development Mode Detection**

Add this to your functions.php for development-specific behavior:

```php
// Detect development environment
function flexpress_is_development() {
    return defined('WP_DEBUG') && WP_DEBUG;
}

// Shorter cache times in development
function flexpress_get_cache_duration() {
    if (flexpress_is_development()) {
        return 300; // 5 minutes in dev
    }
    return 3600; // 1 hour in production
}
```

### 6. **Cache Headers in Development**

The caching headers function already has smart detection:

```php
function flexpress_add_caching_headers() {
    // Only add headers if not in admin and not doing AJAX
    if (is_admin() || wp_doing_ajax()) {
        return; // No caching in admin
    }

    // Add shorter cache in development
    $cache_duration = flexpress_is_development() ? 300 : 3600;

    if (!headers_sent()) {
        header('Cache-Control: public, max-age=' . $cache_duration);
        // ... rest of headers
    }
}
```

## Development Workflow Examples

### CSS Changes

1. Edit CSS file
2. Hard refresh browser (Ctrl+Shift+R)
3. Or wait max 1 hour for automatic refresh

### PHP Changes

1. Edit PHP file
2. Changes appear within 1 hour (usually much faster)
3. Or restart Docker for immediate effect

### Database Changes

1. Make changes in WordPress admin
2. Changes appear immediately (no caching)

### Template Changes

1. Edit template files
2. Changes appear within 1 hour
3. Or restart Docker for immediate effect

## Benefits for Development

### ✅ **Faster Loading**

- Static assets load faster during development
- Reduced server load during testing

### ✅ **Realistic Testing**

- Test with actual caching behavior
- Catch caching-related bugs early

### ✅ **Production Parity**

- Development environment matches production
- No surprises when deploying

### ✅ **Easy Override**

- Multiple ways to bypass cache when needed
- Development tools work normally

## Troubleshooting Development Issues

### Changes Not Appearing

1. **Check if it's cached**: Look at browser Network tab
2. **Hard refresh**: Ctrl+Shift+R
3. **Disable cache**: F12 → Network → Disable cache
4. **Restart Docker**: `docker-compose restart`

### Performance Issues

1. **Check cache headers**: `curl -I http://172.17.0.1:8085`
2. **Monitor response times**: Browser DevTools
3. **Check server logs**: `docker-compose logs wordpress`

### Cache Conflicts

1. **Clear browser cache**: Clear browsing data
2. **Test in incognito**: Fresh browser session
3. **Check multiple browsers**: Ensure consistency

## Best Practices

### 1. **Version Your Assets**

```php
// Always version CSS/JS files
wp_enqueue_style('style', get_stylesheet_uri(), array(), FLEXPRESS_VERSION);
```

### 2. **Use Development Detection**

```php
// Shorter cache times in development
$cache_time = flexpress_is_development() ? 300 : 3600;
```

### 3. **Test Cache Behavior**

```bash
# Test with and without cache
curl -I http://172.17.0.1:8085
curl -H "Cache-Control: no-cache" -I http://172.17.0.1:8085
```

### 4. **Monitor Performance**

- Use browser DevTools
- Check server response times
- Monitor cache hit ratios

## Summary

The caching setup is **development-friendly** because:

- ✅ **Short cache times** for HTML (1 hour max)
- ✅ **No caching** in WordPress admin
- ✅ **Easy bypass** methods available
- ✅ **Realistic testing** environment
- ✅ **Production parity** maintained

You can develop normally and use simple techniques to bypass cache when needed!
