#!/bin/bash
# FlexPress Docker Deployment Script
# Usage: ./deploy.sh [environment]

set -e

ENVIRONMENT=${1:-production}
PROJECT_NAME="flexpress"

echo "🚀 Deploying FlexPress ($ENVIRONMENT environment)..."

# Check prerequisites
check_prerequisites() {
    echo "🔍 Checking prerequisites..."
    
    if ! command -v docker &> /dev/null; then
        echo "❌ Docker not found. Please install Docker first."
        exit 1
    fi
    
    if ! command -v docker-compose &> /dev/null; then
        echo "❌ Docker Compose not found. Please install Docker Compose first."
        exit 1
    fi
    
    echo "✅ Prerequisites check passed"
}

# Setup environment
setup_environment() {
    echo "📝 Setting up environment..."
    
    if [ ! -f .env ]; then
        echo "📄 Creating .env file from template..."
        cp .env.example .env
        echo "⚠️  Please edit .env file with your settings:"
        echo "   - Database credentials"
        echo "   - WordPress configuration"
        echo "   - Domain settings"
        echo ""
        echo "Then run this script again."
        exit 1
    fi
    
    echo "✅ Environment file ready"
}

# Deploy services
deploy_services() {
    echo "🐳 Deploying Docker services..."
    
    # Stop existing services
    docker-compose down 2>/dev/null || true
    
    # Start services
    docker-compose up -d
    
    echo "✅ Services deployed"
}

# Wait for services
wait_for_services() {
    echo "⏳ Waiting for services to start..."
    
    # Wait for MySQL
    echo "   Waiting for MySQL..."
    timeout 60 bash -c 'until docker exec flexpress_mysql mysqladmin ping -h localhost --silent; do sleep 2; done' || {
        echo "❌ MySQL failed to start"
        exit 1
    }
    
    # Wait for Redis
    echo "   Waiting for Redis..."
    timeout 30 bash -c 'until docker exec flexpress_redis redis-cli ping | grep -q PONG; do sleep 2; done' || {
        echo "❌ Redis failed to start"
        exit 1
    }
    
    # Wait for WordPress
    echo "   Waiting for WordPress..."
    timeout 60 bash -c 'until curl -s http://localhost:8085 > /dev/null; do sleep 2; done' || {
        echo "❌ WordPress failed to start"
        exit 1
    }
    
    echo "✅ All services are running"
}

# Test deployment
test_deployment() {
    echo "🔍 Testing deployment..."
    
    # Test Redis connection
    if docker exec flexpress_wordpress php -r "\$redis = new Redis(); \$redis->connect('redis', 6379); echo \$redis->ping();" | grep -q "PONG"; then
        echo "✅ Redis connection successful"
    else
        echo "❌ Redis connection failed"
        return 1
    fi
    
    # Test WordPress response
    HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" http://localhost:8085)
    if [ "$HTTP_CODE" = "200" ]; then
        echo "✅ WordPress is responding"
    else
        echo "❌ WordPress test failed (HTTP $HTTP_CODE)"
        return 1
    fi
    
    # Test caching headers
    CACHE_HEADER=$(curl -s -I http://localhost:8085 | grep -i "cache-control" || echo "")
    if [ -n "$CACHE_HEADER" ]; then
        echo "✅ Caching headers present"
    else
        echo "⚠️  Caching headers not detected"
    fi
    
    echo "✅ Deployment tests passed"
}

# Show status
show_status() {
    echo ""
    echo "🎉 FlexPress deployment complete!"
    echo ""
    echo "📊 Services status:"
    docker-compose ps
    echo ""
    echo "🌐 Access your site:"
    echo "   WordPress: http://localhost:8085"
    echo "   phpMyAdmin: http://localhost:8086"
    echo ""
    echo "📚 Documentation:"
    echo "   - Deployment Guide: docs/DOCKER_DEPLOYMENT_GUIDE.md"
    echo "   - Redis Cache: docs/REDIS_OBJECT_CACHE.md"
    echo "   - Caching Setup: docs/CACHING_CONFIGURATION.md"
    echo ""
    echo "🔧 Useful commands:"
    echo "   View logs: docker-compose logs -f"
    echo "   Stop services: docker-compose down"
    echo "   Restart services: docker-compose restart"
    echo "   Update WordPress: docker exec flexpress_wordpress wp core update"
    echo ""
}

# Main deployment flow
main() {
    check_prerequisites
    setup_environment
    deploy_services
    wait_for_services
    test_deployment
    show_status
}

# Run main function
main "$@"
