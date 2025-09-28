# FlexPress Docker Deployment Guide

## 🚀 Complete Docker Setup for Multi-Site Deployment

FlexPress is fully containerized and ready for deployment to any server. All caching, Redis, and performance optimizations are built into the Docker containers.

## 📦 What's Included in Docker

### Core Services

- **WordPress**: Custom build with all extensions and optimizations
- **MySQL 8.0**: Database server with optimized configuration
- **Redis 7**: Object cache server with persistent storage
- **phpMyAdmin**: Database administration interface

### Built-in Optimizations

- **Apache Modules**: rewrite, expires, headers enabled
- **PHP Extensions**: Redis, mysqli, pdo_mysql installed
- **Caching Headers**: All HTTP caching headers configured
- **Object Cache**: Redis integration with WordPress
- **Security Headers**: XSS protection, content type options, etc.

### Configuration Files

- **Docker Compose**: All services and networking configured
- **Apache Config**: Caching directives and security headers
- **Redis Config**: Memory management and persistence
- **PHP Config**: Upload limits and memory settings

## 🔧 Deployment Steps

### 1. Copy Project Files

```bash
# Copy the entire project directory
scp -r /home/zak/projects/flexpress/ user@new-server:/path/to/deployment/

# Or clone from git repository
git clone <repository-url> /path/to/deployment/
cd /path/to/deployment/
```

### 2. Configure Environment

```bash
# Copy and edit environment file
cp .env.example .env

# Edit .env with your settings
nano .env
```

**Required Environment Variables:**

```env
MYSQL_DATABASE=your_database_name
MYSQL_USER=your_db_user
MYSQL_PASSWORD=your_secure_password
MYSQL_ROOT_PASSWORD=your_root_password
WORDPRESS_CONFIG_EXTRA=define('WP_DEBUG', false);
```

### 3. Update Domain Configuration

#### For Caddyfile (if using Caddy reverse proxy):

```caddy
# Update domain in Caddyfile
your-domain.com {
    reverse_proxy 172.17.0.1:8085
    # ... rest of configuration
}
```

#### For Apache config (if using Apache directly):

```apache
# Update ServerName in apache-config.conf
ServerName your-domain.com
```

### 4. Deploy with Docker

```bash
# Start all services
docker-compose up -d

# Check service status
docker-compose ps

# View logs if needed
docker-compose logs -f
```

### 5. Verify Deployment

```bash
# Test WordPress site
curl -I http://your-server-ip:8085

# Test Redis connection
docker exec flexpress_wordpress php -r "
\$redis = new Redis();
\$redis->connect('redis', 6379);
echo 'Redis: ' . \$redis->ping() . PHP_EOL;
"

# Test database connection
docker exec flexpress_wordpress wp db check
```

## 🌐 Multi-Site Deployment Options

### Option 1: Single Server, Multiple Sites

```bash
# Deploy multiple FlexPress instances on same server
/path/to/site1/
├── docker-compose.yml
├── .env
└── wp-content/

/path/to/site2/
├── docker-compose.yml
├── .env
└── wp-content/

# Use different ports for each site
# Site 1: ports 8085:80, 8086:80
# Site 2: ports 8087:80, 8088:80
```

### Option 2: Separate Servers

```bash
# Each server gets complete FlexPress setup
server1: your-domain1.com
server2: your-domain2.com
server3: your-domain3.com
```

### Option 3: Load Balanced Deployment

```bash
# Multiple WordPress containers behind load balancer
# Shared Redis and MySQL for high availability
```

## 🔄 Port Configuration

### Default Ports

- **WordPress**: 8085
- **phpMyAdmin**: 8086
- **Redis**: 6379 (internal only)
- **MySQL**: 3306 (internal only)

### Custom Ports for Multiple Sites

```yaml
# Site 1
ports:
  - "8085:80"  # WordPress
  - "8086:80"  # phpMyAdmin

# Site 2
ports:
  - "8087:80"  # WordPress
  - "8088:80"  # phpMyAdmin
```

## 📋 Pre-Deployment Checklist

### Server Requirements

- [ ] Docker and Docker Compose installed
- [ ] Ports 8085+ available
- [ ] Sufficient disk space (2GB+ recommended)
- [ ] Memory: 1GB+ RAM recommended
- [ ] Network access for domain resolution

### Configuration Files

- [ ] `.env` file configured with secure passwords
- [ ] `Caddyfile` updated with correct domain
- [ ] `apache-config.conf` updated if needed
- [ ] `docker-compose.yml` ports configured

### Domain Setup

- [ ] Domain DNS pointing to server IP
- [ ] SSL certificate configured (if using HTTPS)
- [ ] Firewall rules allowing necessary ports

## 🚀 Quick Deployment Script

Create a deployment script for easy setup:

```bash
#!/bin/bash
# deploy-flexpress.sh

set -e

echo "🚀 Deploying FlexPress..."

# Check if Docker is installed
if ! command -v docker &> /dev/null; then
    echo "❌ Docker not found. Please install Docker first."
    exit 1
fi

if ! command -v docker-compose &> /dev/null; then
    echo "❌ Docker Compose not found. Please install Docker Compose first."
    exit 1
fi

# Check if .env exists
if [ ! -f .env ]; then
    echo "📝 Creating .env file..."
    cp .env.example .env
    echo "⚠️  Please edit .env file with your settings before continuing."
    exit 1
fi

# Start services
echo "🐳 Starting Docker services..."
docker-compose up -d

# Wait for services to be ready
echo "⏳ Waiting for services to start..."
sleep 10

# Test Redis connection
echo "🔍 Testing Redis connection..."
if docker exec flexpress_wordpress php -r "\$redis = new Redis(); \$redis->connect('redis', 6379); echo \$redis->ping();" | grep -q "PONG"; then
    echo "✅ Redis connection successful"
else
    echo "❌ Redis connection failed"
fi

# Test WordPress
echo "🔍 Testing WordPress..."
if curl -s -o /dev/null -w "%{http_code}" http://localhost:8085 | grep -q "200"; then
    echo "✅ WordPress is running"
else
    echo "❌ WordPress test failed"
fi

echo "🎉 FlexPress deployment complete!"
echo "📊 Services status:"
docker-compose ps

echo ""
echo "🌐 Access your site:"
echo "   WordPress: http://localhost:8085"
echo "   phpMyAdmin: http://localhost:8086"
echo ""
echo "📚 Documentation: docs/"
```

## 🔧 Environment-Specific Configurations

### Development Environment

```env
WORDPRESS_DEBUG=1
WP_DEBUG=true
WP_DEBUG_LOG=true
```

### Production Environment

```env
WORDPRESS_DEBUG=0
WP_DEBUG=false
WP_DEBUG_LOG=false
```

### Staging Environment

```env
WORDPRESS_DEBUG=1
WP_DEBUG=true
WP_DEBUG_LOG=true
```

## 📊 Monitoring and Maintenance

### Health Checks

```bash
# Check all services
docker-compose ps

# Check Redis status
docker exec flexpress_redis redis-cli ping

# Check MySQL status
docker exec flexpress_mysql mysqladmin ping

# Check WordPress
curl -I http://localhost:8085
```

### Backup Commands

```bash
# Backup database
docker exec flexpress_mysql mysqldump -u root -p$MYSQL_ROOT_PASSWORD $MYSQL_DATABASE > backup.sql

# Backup Redis data
docker run --rm -v flexpress_redis_data:/data -v $(pwd):/backup alpine tar czf /backup/redis-backup.tar.gz -C /data .

# Backup WordPress files
docker cp flexpress_wordpress:/var/www/html/wp-content ./wp-content-backup
```

### Update Commands

```bash
# Update WordPress
docker exec flexpress_wordpress wp core update

# Update plugins
docker exec flexpress_wordpress wp plugin update --all

# Update theme
docker exec flexpress_wordpress wp theme update --all
```

## 🎯 Performance Verification

After deployment, verify all optimizations are working:

### 1. Check Caching Headers

```bash
curl -I http://your-domain.com
# Should show: Cache-Control, Expires, ETag, X-Cache-Enabled
```

### 2. Check Redis Object Cache

```bash
# WordPress admin should show Redis status notice
# Or check via command line:
docker exec flexpress_wordpress wp eval "var_dump(wp_cache_get('test', 'test_group'));"
```

### 3. Check Static Asset Caching

```bash
curl -I http://your-domain.com/wp-content/themes/flexpress/style.css
# Should show: Cache-Control: public, max-age=31536000
```

## 🔒 Security Considerations

### Production Security

- [ ] Change all default passwords
- [ ] Use strong database passwords
- [ ] Enable SSL/HTTPS
- [ ] Configure firewall rules
- [ ] Regular security updates
- [ ] Monitor logs for suspicious activity

### Network Security

- [ ] Redis only accessible internally
- [ ] MySQL only accessible internally
- [ ] phpMyAdmin access restricted
- [ ] WordPress admin access secured

## 📈 Scaling Considerations

### High Traffic Sites

- Increase Redis memory: `--maxmemory 1gb`
- Use external MySQL for multiple WordPress instances
- Implement load balancing
- Use CDN for static assets

### Multiple Sites

- Separate Docker networks per site
- Shared Redis cluster for object caching
- Database per site or shared with prefixes
- Centralized logging and monitoring

## 🎉 Summary

**FlexPress is fully containerized and ready for deployment anywhere!**

✅ **Complete Docker Setup** - All services containerized  
✅ **Performance Optimized** - Redis + HTTP caching configured  
✅ **Security Hardened** - Headers and access controls in place  
✅ **Documentation Complete** - Comprehensive guides included  
✅ **Multi-Site Ready** - Easy to deploy multiple instances  
✅ **Production Ready** - Scalable and maintainable architecture

**Deploy with confidence** - everything is built into the Docker containers!
