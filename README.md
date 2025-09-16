# FlexPress WordPress Project

A modern WordPress website running in Docker containers with MySQL database and phpMyAdmin for database management.

## 🚀 Quick Start

### Prerequisites
- Docker and Docker Compose installed
- Ports 8085 and 8086 available

### Installation

1. **Clone and navigate to the project:**
   ```bash
   cd /home/zak/projects/flexpress
   ```

2. **Start the containers:**
   ```bash
   docker-compose up -d
   ```

3. **Access your WordPress site:**
   - WordPress: https://zakspov.com
   - phpMyAdmin: http://localhost:8086

## 📁 Project Structure

```
flexpress/
├── docker-compose.yml    # Docker services configuration
├── Dockerfile           # Custom WordPress image
├── apache-config.conf   # Apache virtual host config
├── .env                 # Environment variables
├── .env.example         # Environment template
├── wp-content/          # WordPress themes, plugins, uploads
└── README.md            # This file
```

## 🐳 Docker Services

| Service | Container | Port | Description |
|---------|-----------|------|-------------|
| WordPress | flexpress_wordpress | 8085 | Main WordPress application |
| MySQL | flexpress_mysql | 3306 (internal) | Database server |
| phpMyAdmin | flexpress_phpmyadmin | 8086 | Database administration |

## ⚙️ Configuration

### Environment Variables
Edit `.env` file to customize:
- Database credentials
- WordPress debug settings
- Port configurations

### WordPress Customization
- Themes: `wp-content/themes/`
- Plugins: `wp-content/plugins/`
- Uploads: `wp-content/uploads/`

## 🛠️ Development Commands

```bash
# Start all services
docker-compose up -d

# View logs
docker-compose logs -f

# Stop services
docker-compose down

# Rebuild containers
docker-compose up --build -d

# Access WordPress container
docker exec -it flexpress_wordpress bash

# Access MySQL container
docker exec -it flexpress_mysql mysql -u root -p
```

## 🔧 Troubleshooting

### Port Conflicts
- If port 8085 is occupied, change `SERVER_PORT` in `.env`
- Update docker-compose.yml ports mapping accordingly

### Database Issues
- Check container logs: `docker-compose logs db`
- Access phpMyAdmin at http://localhost:8086
- Default credentials in `.env` file

### WordPress Issues
- Enable debug mode in `.env`: `WORDPRESS_DEBUG=1`
- Check logs: `docker-compose logs wordpress`
- Access container: `docker exec -it flexpress_wordpress bash`

## 🔒 Security Notes

- Change default passwords before production
- Use environment variables for sensitive data
- Enable SSL/HTTPS in production
- Regular security updates required
- Keep WordPress core and plugins updated

## 📝 Development Guidelines

- Use IP addresses instead of localhost for server configuration
- Avoid port 3000 (reserved for MCP tools)
- All customizations in `wp-content/` directory
- Follow WordPress coding standards
- Test changes in development before production

## 🆘 Support

For issues or questions:
1. Check Docker container logs
2. Verify port availability
3. Check environment configuration
4. Review this documentation
