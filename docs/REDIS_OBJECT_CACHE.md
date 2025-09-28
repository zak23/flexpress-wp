# FlexPress Redis Object Cache Implementation

## Overview

FlexPress now includes Redis as a persistent object cache to significantly improve WordPress performance by caching database queries, object data, and computed results in memory.

## Problem Solved

WordPress was suggesting:

> "A persistent object cache makes your site's database more efficient, resulting in faster load times because WordPress can retrieve your site's content and settings much more quickly."

## Solution Architecture

### 1. Redis Service Configuration

**File:** `docker-compose.yml`

```yaml
redis:
  image: redis:7-alpine
  container_name: flexpress_redis
  restart: unless-stopped
  volumes:
    - redis_data:/data
  networks:
    - flexpress_network
  command: redis-server --appendonly yes --maxmemory 256mb --maxmemory-policy allkeys-lru
```

**Key Features:**

- **Redis 7 Alpine**: Lightweight, production-ready Redis server
- **Persistent Storage**: Data survives container restarts (`--appendonly yes`)
- **Memory Management**: 256MB limit with LRU eviction policy
- **Internal Network**: Only accessible from WordPress container

### 2. PHP Redis Extension

**File:** `Dockerfile`

```dockerfile
# Install Redis PHP extension
RUN pecl install redis && docker-php-ext-enable redis
```

**Benefits:**

- **Native Performance**: C extension for optimal speed
- **Full Redis API**: Complete feature set support
- **Session Storage**: Can be used for PHP sessions
- **Object Serialization**: Automatic PHP object handling

### 3. WordPress Object Cache Drop-in

**File:** `wp-content/object-cache.php`

Custom Redis object cache implementation with:

#### Configuration Constants

```php
define('WP_REDIS_HOST', 'redis');
define('WP_REDIS_PORT', 6379);
define('WP_REDIS_DATABASE', 0);
define('WP_REDIS_PASSWORD', '');
define('WP_REDIS_TIMEOUT', 1);
define('WP_REDIS_PREFIX', 'flexpress:prod:');
```

#### Core Features

- **Automatic Connection**: Connects to Redis on first use
- **Error Handling**: Graceful fallback to default cache
- **Key Namespacing**: Prevents conflicts with other sites
- **Group Support**: Organizes cache by WordPress groups
- **Expiration**: Supports TTL for automatic cleanup

#### Admin Integration

- **Status Notices**: Shows Redis connection status in WordPress admin
- **Performance Stats**: Displays memory usage, hit/miss ratios
- **Health Monitoring**: Real-time connection status

## Performance Benefits

### Database Query Reduction

- **Object Caching**: WordPress objects cached in Redis
- **Query Results**: Database query results cached
- **Transients**: WordPress transients stored in Redis
- **Options**: Site options cached for faster access

### Memory Efficiency

- **Shared Memory**: Multiple PHP processes share Redis cache
- **Persistent Storage**: Cache survives between requests
- **LRU Eviction**: Automatic cleanup of old data
- **Compression**: Redis handles data compression

### Speed Improvements

- **Sub-millisecond Access**: Redis response times < 1ms
- **Reduced Database Load**: Fewer MySQL queries
- **Faster Page Loads**: Cached data served instantly
- **Better Scalability**: Handles high traffic efficiently

## Cache Strategy

### Cache Groups

WordPress organizes cache into logical groups:

- **`default`**: General WordPress objects
- **`posts`**: Post data and metadata
- **`users`**: User information and capabilities
- **`terms`**: Taxonomy terms and relationships
- **`options`**: WordPress options and settings
- **`transients`**: Temporary data with expiration

### Cache Duration

- **Objects**: Cached until explicitly cleared
- **Transients**: Respect WordPress expiration times
- **Options**: Cached until option updates
- **Queries**: Cached for query-specific duration

### Cache Invalidation

- **Automatic**: WordPress clears cache on content updates
- **Manual**: Admin can flush cache via WordPress tools
- **Selective**: Clear specific groups or keys
- **Bulk Operations**: Clear all cache when needed

## Monitoring & Management

### Admin Dashboard

Redis status appears in WordPress admin with:

- **Connection Status**: Green/red indicator
- **Memory Usage**: Current Redis memory consumption
- **Hit Ratio**: Cache effectiveness metrics
- **Key Count**: Number of cached items

### Command Line Tools

```bash
# Check Redis status
docker exec flexpress_redis redis-cli info

# Monitor Redis in real-time
docker exec flexpress_redis redis-cli monitor

# Check cache keys
docker exec flexpress_redis redis-cli keys "flexpress:*"

# Clear all cache
docker exec flexpress_redis redis-cli flushall
```

### Performance Monitoring

```bash
# Check Redis memory usage
docker exec flexpress_redis redis-cli info memory

# View cache statistics
docker exec flexpress_redis redis-cli info stats

# Monitor slow queries
docker exec flexpress_redis redis-cli slowlog get 10
```

## Development Workflow

### Cache Behavior in Development

- **Same as Production**: Redis works identically in dev
- **Realistic Testing**: Test with actual caching behavior
- **Debug Information**: Admin notices show cache status
- **Easy Clearing**: Simple commands to clear cache

### Cache Debugging

```php
// Check if object is cached
$cached = wp_cache_get('my_key', 'my_group');
if ($cached === false) {
    // Not cached, fetch from database
    $data = get_data_from_database();
    wp_cache_set('my_key', $data, 'my_group', 3600);
} else {
    // Use cached data
    $data = $cached;
}
```

### Cache Testing

```bash
# Test Redis connection
docker exec flexpress_wordpress php -r "
\$redis = new Redis();
\$redis->connect('redis', 6379);
echo 'Redis: ' . \$redis->ping() . PHP_EOL;
"

# Test WordPress cache
docker exec flexpress_wordpress wp eval "
wp_cache_set('test_key', 'test_value', 'test_group', 3600);
echo 'Cache set: ' . wp_cache_get('test_key', 'test_group') . PHP_EOL;
"
```

## Troubleshooting

### Common Issues

#### Redis Connection Failed

```bash
# Check Redis container status
docker ps | grep redis

# Check Redis logs
docker logs flexpress_redis

# Test Redis connectivity
docker exec flexpress_wordpress ping redis
```

#### Cache Not Working

```bash
# Verify Redis extension loaded
docker exec flexpress_wordpress php -m | grep redis

# Check object-cache.php exists
docker exec flexpress_wordpress ls -la /var/www/html/wp-content/object-cache.php

# Test cache functions
docker exec flexpress_wordpress wp eval "var_dump(function_exists('wp_cache_get'));"
```

#### Memory Issues

```bash
# Check Redis memory usage
docker exec flexpress_redis redis-cli info memory

# Clear cache if needed
docker exec flexpress_redis redis-cli flushall

# Adjust memory limit in docker-compose.yml
# command: redis-server --maxmemory 512mb
```

### Performance Optimization

#### Redis Configuration

```yaml
# Increase memory limit for high-traffic sites
command: redis-server --appendonly yes --maxmemory 512mb --maxmemory-policy allkeys-lru

# Enable compression for large datasets
command: redis-server --appendonly yes --maxmemory 256mb --maxmemory-policy allkeys-lru --rdbcompression yes
```

#### WordPress Optimization

```php
// Increase cache duration for stable data
wp_cache_set($key, $data, $group, 7200); // 2 hours

// Use appropriate cache groups
wp_cache_set($key, $data, 'posts', 3600);
wp_cache_set($key, $data, 'users', 1800);
wp_cache_set($key, $data, 'options', 7200);
```

## Security Considerations

### Network Security

- **Internal Only**: Redis only accessible from WordPress container
- **No External Ports**: Redis not exposed to host system
- **Docker Network**: Isolated network for container communication

### Data Security

- **No Authentication**: Internal network doesn't require Redis auth
- **Namespace Isolation**: Cache keys prefixed to prevent conflicts
- **Automatic Cleanup**: LRU policy prevents memory exhaustion

### Access Control

- **Container Isolation**: Redis runs in separate container
- **Limited Access**: Only WordPress container can connect
- **No Direct Access**: No external Redis clients possible

## Backup & Recovery

### Redis Data Persistence

```bash
# Redis data stored in Docker volume
docker volume inspect flexpress_redis_data

# Backup Redis data
docker run --rm -v flexpress_redis_data:/data -v $(pwd):/backup alpine tar czf /backup/redis-backup.tar.gz -C /data .

# Restore Redis data
docker run --rm -v flexpress_redis_data:/data -v $(pwd):/backup alpine tar xzf /backup/redis-backup.tar.gz -C /data
```

### Cache Recovery

- **Automatic**: Redis restarts with persisted data
- **Manual**: Clear cache if corruption detected
- **Selective**: Clear specific groups if needed

## Production Deployment

### Scaling Considerations

- **Memory Allocation**: Adjust Redis memory based on traffic
- **Monitoring**: Set up Redis monitoring and alerts
- **Backup Strategy**: Regular Redis data backups
- **Health Checks**: Monitor Redis connection status

### Performance Tuning

```yaml
# Production Redis configuration
redis:
  image: redis:7-alpine
  command: redis-server
    --appendonly yes
    --maxmemory 1gb
    --maxmemory-policy allkeys-lru
    --save 900 1
    --save 300 10
    --save 60 10000
```

### Monitoring Setup

```bash
# Install Redis monitoring tools
docker exec flexpress_redis redis-cli --latency-history

# Set up log monitoring
docker logs -f flexpress_redis

# Performance metrics
docker exec flexpress_redis redis-cli info replication
```

## Integration with Existing Caching

### Page Cache Compatibility

- **Complementary**: Works alongside page caching
- **Different Layers**: Object cache + page cache = optimal performance
- **No Conflicts**: Redis doesn't interfere with HTTP caching

### BunnyCDN Integration

- **Separate Systems**: Redis for objects, BunnyCDN for static assets
- **Optimal Performance**: Both systems work together
- **Cache Hierarchy**: Browser → CDN → Page Cache → Object Cache → Database

## Summary

The Redis object cache implementation provides:

✅ **Persistent Object Caching** - Database queries cached in memory  
✅ **Automatic Management** - WordPress handles cache lifecycle  
✅ **Performance Monitoring** - Admin dashboard with real-time stats  
✅ **Development Friendly** - Same behavior in dev and production  
✅ **Production Ready** - Scalable, secure, and reliable  
✅ **Easy Management** - Simple commands for cache operations

**Result**: WordPress now has a persistent object cache that significantly improves database efficiency and page load times!
