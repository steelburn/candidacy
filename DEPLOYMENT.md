# Candidacy Deployment Guide

Production deployment guide for the AI-powered Candidacy recruitment platform.

## Overview

This guide covers deploying the Candidacy platform to production environments. The system uses a microservices architecture with Docker containers.

---

## Prerequisites

### System Requirements

| Component | Minimum | Recommended |
|-----------|---------|-------------|
| CPU | 4 cores | 8+ cores |
| RAM | 8 GB | 16+ GB |
| Storage | 50 GB SSD | 100+ GB SSD |
| OS | Ubuntu 22.04 LTS | Ubuntu 22.04 LTS |

### Required Software

- Docker 24.0+
- Docker Compose 2.20+
- Git
- SSL Certificate (via Let's Encrypt or purchased)

---

## Production Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                      Production Setup                           │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│   ┌──────────┐    ┌──────────────┐    ┌──────────────────┐   │
│   │ Cloudflare│───▶│  API Gateway │───▶│  Microservices   │   │
│   │   CDN     │    │   (Laravel)  │    │  (12 Services)   │   │
│   └──────────┘    └──────────────┘    └──────────────────┘   │
│                                              │                  │
│                                              ▼                  │
│                                      ┌──────────────┐          │
│                                      │   MySQL      │          │
│                                      │   (Per Svc)  │          │
│                                      └──────────────┘          │
│                                              │                  │
│                                              ▼                  │
│                                      ┌──────────────┐          │
│                                      │    Redis     │          │
│                                      │  (Cache/Pub) │          │
│                                      └──────────────┘          │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

---

## Environment Configuration

### 1. Environment Variables

Create production environment file:

```bash
cp .env.example .env
```

**Critical Production Settings**:

```env
# Application
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# Security
APP_KEY=base64:your-generated-key

# JWT Authentication (REQUIRED)
JWT_SECRET=your-jwt-secret-min-32-chars
JWT_TTL=60                      # Access token validity in minutes
JWT_REFRESH_TTL=20160           # Refresh token validity in minutes (2 weeks)

# Database
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=candidacy_auth
DB_USERNAME=candidacy_user
DB_PASSWORD=strong-password-here

# Redis
REDIS_HOST=redis
REDIS_PASSWORD=strong-redis-password

# AI Provider
AI_PROVIDER=openrouter
OPENROUTER_API_KEY=sk-your-api-key

# Mail
MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=noreply@your-domain.com
MAIL_PASSWORD=mail-password

# Domain (for Cloudflare Tunnel)
PUBLIC_DOMAIN=your-domain.com
PUBLIC_API_URL=https://your-domain.com
```

### 2. Generate Secrets

```bash
# Generate application key
php artisan key:generate

# Generate JWT secret (REQUIRED for authentication)
# This creates a secure 64-character secret for JWT token signing
php artisan jwt:secret

# Or use Makefile (recommended - generates all secrets at once)
make generate-secrets

# Verify JWT_SECRET is set in .env:
grep JWT_SECRET .env
```

---

## Database Setup

### 1. Initialize Databases from DBML

```bash
# Validate schema
make dbml-validate

# Generate SQL
make dbml-sql

# Initialize databases
make dbml-init
```

### 2. Manual MySQL Setup

If not using Docker:

```sql
-- Create databases
CREATE DATABASE candidacy_auth;
CREATE DATABASE candidacy_candidate;
CREATE DATABASE candidacy_vacancy;
CREATE DATABASE candidacy_ai;
CREATE DATABASE candidacy_matching;
CREATE DATABASE candidacy_interview;
CREATE DATABASE candidacy_offer;
CREATE DATABASE candidacy_onboarding;
CREATE DATABASE candidacy_reporting;
CREATE DATABASE candidacy_admin;
CREATE DATABASE candidacy_notification;
CREATE DATABASE candidacy_document_parser;

-- Create user
CREATE USER 'candidacy_user'@'%' IDENTIFIED BY 'strong-password';
GRANT ALL PRIVILEGES ON candidacy_*.* TO 'candidacy_user'@'%';
FLUSH PRIVILEGES;
```

---

## Docker Deployment

### 1. Build Images

```bash
# Build all services
make build

# Or pull pre-built images
make pull
```

### 2. Start Services

```bash
# Start all services
make up

# Check status
make status
```

### 3. Verify Health

```bash
# Check all services
curl http://localhost:8080/api/system-health
```

---

## SSL/TLS Configuration

### Using Cloudflare Tunnel (Recommended)

See [CLOUDFLARE_TUNNEL.md](CLOUDFLARE_TUNNEL.md) for detailed setup.

```bash
# Start tunnel
make tunnel-up

# Check tunnel status
make tunnel-status
```

### Using Traditional SSL

1. **Obtain SSL Certificate**:
   ```bash
   # Using Certbot
   sudo apt install certbot
   sudo certbot certonly --standalone -d your-domain.com
   ```

2. **Configure Nginx/Apache**:
   - Copy certificates to `infrastructure/docker/ssl/`
   - Update docker-compose with SSL paths

---

## Production Checklist

### Pre-Deployment

- [ ] All tests passing (`make test`)
- [ ] Environment variables configured
- [ ] SSL certificates obtained
- [ ] Database migrations run
- [ ] Seed data applied
- [ ] Backup strategy in place

### Security Hardening

- [ ] `APP_DEBUG=false`
- [ ] Strong database passwords
- [ ] **JWT secret generated and rotated** (`make generate-secrets`)
- [ ] JWT_TTL configured (default: 60 minutes)
- [ ] JWT_REFRESH_TTL configured (default: 2 weeks)
- [ ] Redis password set
- [ ] Firewall configured
- [ ] Cloudflare security headers enabled
- [ ] Rate limiting enabled

### Monitoring

- [ ] Grafana accessible (http://localhost:3050)
- [ ] Log aggregation working
- [ ] Health checks passing
- [ ] Alerting configured

---

## Scaling

### Horizontal Scaling

To scale specific services:

```bash
# Scale a service
docker-compose up -d --scale candidate-service=3
```

### Load Balancing

The API Gateway handles load balancing across service instances.

### Database Scaling

- Use read replicas for high-read operations
- Implement connection pooling
- Consider sharding for large datasets

---

## Backup & Recovery

### Database Backup

```bash
# Backup all databases
docker-compose exec mysql mysqldump -u root -p candidacy_auth > backup_auth.sql

# Automated backup script
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
docker-compose exec mysql mysqldump -u root -p candidacy_auth > backup_$DATE.sql
```

### Restore

```bash
# Restore database
docker-compose exec -T mysql -u root -p candidacy_auth < backup_20240101.sql
```

### Volume Backup

```bash
# Backup Docker volumes
docker run --rm -v candidacy_mysql:/data -v $(pwd):/backup ubuntu tar czf /backup/mysql_backup.tar.gz /data
```

---

## Maintenance

### Logs Management

```bash
# View all logs
make logs

# Service-specific logs
make logs-gateway
make logs-candidate

# Rotate logs
docker-compose logs --tail=1000 > logs_$(date +%Y%m%d).txt
```

### Updates

```bash
# Pull latest images
make pull

# Restart services
make restart

# Run migrations
docker-compose exec candidate-service php artisan migrate
```

### Performance Tuning

1. **PHP-FPM**: Adjust `pm.max_children` in php-fpm.conf
2. **MySQL**: Tune `innodb_buffer_pool_size`
3. **Redis**: Configure maxmemory policy
4. **Nginx**: Enable gzip compression

---

## Troubleshooting

### Service Not Starting

```bash
# Check logs
docker-compose logs service-name

# Check configuration
docker-compose config

# Rebuild
docker-compose up -d --build service-name
```

### Database Connection Issues

```bash
# Check MySQL
docker-compose exec mysql mysql -u root -p

# Test connection
docker-compose exec candidate-service php artisan db:connect
```

### Performance Issues

1. Check resource usage: `docker stats`
2. Review slow query logs
3. Monitor Redis memory
4. Check AI service queue

---

## Monitoring & Alerts

### Grafana Dashboards

Access at: http://localhost:3050

Default credentials: `admin` / `admin`

### Key Metrics

- Response time (p50, p95, p99)
- Error rate
- CPU/Memory usage
- Database connections
- Queue length

### Log Aggregation

```bash
# View Loki logs
# Access at http://localhost:3100
```

---

## Support

For deployment issues:

1. Check [TROUBLESHOOTING.md](TROUBLESHOOTING.md)
2. Review service logs
3. Verify network connectivity
4. Check Cloudflare status

---

## Related Documentation

- [QUICKSTART.md](QUICKSTART.md) - Quick start guide
- [ARCHITECTURE.md](ARCHITECTURE.md) - System architecture
- [CLOUDFLARE_TUNNEL.md](CLOUDFLARE_TUNNEL.md) - Cloudflare setup
- [TESTING.md](TESTING.md) - Testing guide
- [CONFIGURATION.md](CONFIGURATION.md) - Configuration reference
