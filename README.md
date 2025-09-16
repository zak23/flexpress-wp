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

## 💳 Payment Integration

### Flowguard Payment System

FlexPress now uses **Flowguard** as the primary payment processing system, replacing Verotel FlexPay. Flowguard provides:

- **Embedded Payment Forms**: No redirects, seamless user experience
- **PCI DSS Compliance**: Secure payment processing with hosted iframes
- **3D Secure Support**: Enhanced security for card transactions
- **Webhook Integration**: Real-time payment notifications
- **Admin Dashboard**: Complete payment management interface

#### Flowguard Configuration

1. **Access Settings**: Go to `FlexPress Settings → Flowguard`
2. **Configure API**: Enter your Shop ID and Signature Key from ControlCenter
3. **Set Environment**: Choose between Sandbox (testing) or Production (live)
4. **Test Integration**: Use the built-in testing tools to verify setup

#### Payment Pages

- **Registration**: `/register-flowguard` - Cheeky user registration form
- **Join Page**: `/join-flowguard` - Modern membership signup with Flowguard integration
- **Payment Form**: `/flowguard-payment` - Embedded payment processing
- **Success Page**: `/payment-success` - Payment completion confirmation
- **Declined Page**: `/payment-declined` - Payment failure handling

#### Webhook Endpoint

Flowguard webhooks are automatically handled at:
```
/wp-admin/admin-ajax.php?action=flowguard_webhook
```

#### Database Tables

The integration creates three database tables:
- `wp_flexpress_flowguard_webhooks` - Webhook event logging
- `wp_flexpress_flowguard_transactions` - Transaction records
- `wp_flexpress_flowguard_sessions` - Payment session tracking

#### Troubleshooting

**Common Issues Fixed During Implementation:**

1. **API URL**: Use `https://flowguard.yoursafe.com/api/merchant` (not `api.yoursafe.com`)
2. **Minimum Amount**: Flowguard requires minimum $2.95 USD for transactions
3. **Minimum Period**: Subscriptions require minimum 2 days (`P2D`)
4. **Environment**: Sandbox and production use the same API URL
5. **Credentials**: Shop ID `134837` and Signature Key from ControlCenter

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
