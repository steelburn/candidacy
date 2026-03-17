# Candidacy Troubleshooting Guide

Common issues and solutions for the AI-powered Candidacy recruitment platform.

---

## Table of Contents

- [Service Issues](#service-issues)
- [Database Issues](#database-issues)
- [AI/ML Issues](#aiml-issues)
- [Authentication Issues](#authentication-issues)
- [Frontend Issues](#frontend-issues)
- [Network Issues](#network-issues)
- [Performance Issues](#performance-issues)

---

## Service Issues

### Service Won't Start

**Symptoms**: Container exits immediately after starting

**Diagnosis**:
```bash
# Check container status
docker-compose ps

# View logs
docker-compose logs service-name
```

**Solutions**:

1. **Port conflict**:
   ```bash
   # Find conflicting process
   lsof -i :8080
   # Kill or change port
   ```

2. **Missing environment variables**:
   ```bash
   # Copy .env.example to .env
   cp .env.example .env
   # Edit with correct values
   ```

3. **Database connection failed**:
   ```bash
   # Check MySQL is running
   docker-compose ps mysql
   # Restart MySQL
   docker-compose restart mysql
   ```

### Service Returns 502 Bad Gateway

**Diagnosis**:
```bash
# Check if target service is running
docker-compose ps

# Test service health
curl http://localhost:8082/api/health
```

**Solutions**:
1. Restart the failing service: `docker-compose restart candidate-service`
2. Check service logs for errors
3. Verify Docker network connectivity

### All Services Down

**Recovery Steps**:
```bash
# Stop all services
make down

# Clean up
make clean

# Restart everything
make up

# Check status
make status
```

---

## Database Issues

### Database Connection Refused

**Error**: `SQLSTATE[HY000] [2002] Connection refused`

**Solutions**:
```bash
# Check MySQL is running
docker-compose ps mysql

# Restart MySQL
docker-compose restart mysql

# Check MySQL logs
docker-compose logs mysql

# Verify connection from service
docker-compose exec candidate-service php artisan db:connect
```

### Migration Failures

**Error**: `SQLSTATE[42S01]: Table already exists`

**Solutions**:
```bash
# Fresh database (development only!)
make db-reset

# Or run specific migration
docker-compose exec candidate-service php artisan migrate:fresh
```

### Schema Out of Sync

**Symptoms**: Unexpected database errors, missing columns

**Diagnosis**:
```bash
# Check DBML sync status
make dbml-check
```

**Solutions**:
```bash
# Regenerate SQL from DBML
make dbml-sql

# Reset databases with new schema (WARNING: data loss)
make db-reset
```

### MySQL Container Won't Start

**Diagnosis**:
```bash
# Check Docker volumes
docker volume ls | grep mysql

# Check disk space
df -h
```

**Solutions**:
```bash
# Remove stuck volume
docker volume rm candidacy_mysql

# Start fresh
docker-compose up -d mysql
```

---

## AI/ML Issues

### AI Service Returns Timeout

**Error**: `Connection timeout after 300000 ms`

**Solutions**:

1. **Check Ollama is running**:
   ```bash
   curl http://192.168.88.120:11535/api/tags
   ```

2. **Pull required models**:
   ```bash
   docker-compose exec ollama ollama pull gemma2:2b
   docker-compose exec ollama ollama pull llama3.2
   ```

3. **Switch to OpenRouter** (cloud):
   ```env
   AI_PROVIDER=openrouter
   OPENROUTER_API_KEY=sk-your-key
   ```

4. **Increase timeout** in Admin panel:
   - Go to Settings → AI → Generation Timeout
   - Increase from 300s to 600s

### CV Parsing Not Working

**Symptoms**: CV uploaded but no parsed data

**Diagnosis**:
```bash
# Check document parser service
curl http://localhost:8095/api/health

# Check AI service
curl http://localhost:8084/api/health

# View parsing logs
make logs-document-parser
```

**Solutions**:
1. Verify CV format (PDF/DOCX only)
2. Check file size limit (max 10MB)
3. Ensure AI service has valid configuration

### Matching Scores All Zero

**Diagnosis**:
```bash
# Check matching service
docker-compose logs matching-service | grep -i error

# Check AI service
docker-compose logs ai-service | grep -i error
```

**Solutions**:
1. Verify candidates and vacancies exist
2. Check AI provider configuration
3. Ensure minimum threshold not too high (default: 40)

---

## Authentication Issues

### Login Returns 401 Unauthorized

**Solutions**:
```bash
# Clear cache
docker-compose exec gateway php artisan cache:clear

# Check user exists
docker-compose exec auth-service php artisan tinker
# Then: App\Models\User::first()
```

### JWT Token Expired Immediately

**Solutions**:
```bash
# Generate new JWT secret
php artisan jwt:secret --force

# Clear cache
docker-compose exec auth-service php artisan cache:clear
```

### CORS Errors

**Error**: `Access-Control-Allow-Origin` not set

**Solutions**:
1. Update `SANCTUM_STATEFUL_DOMAINS` in .env
2. Rebuild gateway service
3. Clear browser cache

---

## Frontend Issues

### Frontend Won't Load

**Diagnosis**:
```bash
# Check frontend container
docker-compose ps web-app

# Check for build errors
docker-compose logs web-app
```

**Solutions**:
```bash
# Rebuild frontend
docker-compose up -d --build web-app

# Clear node_modules
rm -rf frontend/web-app/node_modules
docker-compose up -d --build web-app
```

### API Calls Return 404

**Diagnosis**:
```bash
# Check API Gateway logs
make logs-gateway

# Verify route configuration
curl http://localhost:8080/api/health
```

**Solutions**:
1. Verify service is running
2. Check API Gateway routes are configured
3. Ensure correct endpoint URL

### WebSocket Connection Failed

**Solutions**:
```bash
# Check if Pusher/WebSocket service is configured
# Update .env with correct WebSocket credentials
```

---

## Network Issues

### Cannot Connect to Services

**Diagnosis**:
```bash
# Check Docker network
docker network ls | grep candidacy

# Inspect network
docker network inspect candidacy_candidacy-network
```

**Solutions**:
```bash
# Recreate network
docker-compose down
docker-compose up -d
```

### Port Already in Use

**Error**: `Bind for 0.0.0.0:8080 failed: port is already allocated`

**Solutions**:
```bash
# Find process using port
lsof -i :8080

# Kill process or edit docker-compose.yml to use different port
```

### DNS Resolution Failed

**Error**: `Could not resolve host: service-name`

**Solutions**:
```bash
# Restart Docker
sudo systemctl restart docker

# Recreate containers
docker-compose down
docker-compose up -d
```

---

## Performance Issues

### Slow Response Times

**Diagnosis**:
```bash
# Check resource usage
docker stats

# Check database queries
docker-compose exec candidate-service php artisan --env=local
```

**Solutions**:
1. Increase container resources
2. Optimize database queries
3. Enable Redis caching
4. Check for N+1 query problems

### High Memory Usage

**Solutions**:
```bash
# Restart services
docker-compose restart

# Clear Redis cache
docker-compose exec redis redis-cli FLUSHALL
```

### Database Connections Exhausted

**Error**: `Too many connections`

**Solutions**:
```bash
# Check connection count
docker-compose exec mysql mysql -u root -p -e "SHOW PROCESSLIST;"

# Increase max_connections in MySQL config
# Or restart services to clear idle connections
```

---

## Getting More Help

### Enable Debug Mode

```env
APP_DEBUG=true
```

### View All Logs

```bash
# All services
make logs

# Specific service
make logs-candidate

# Follow in real-time
docker-compose logs -f
```

### Check System Health

```bash
curl http://localhost:8080/api/system-health
```

### Run Diagnostics

```bash
# Test all backend services
make test-backend

# Test API endpoints
make test-api
```

---

## Related Documentation

- [TESTING.md](TESTING.md) - Testing procedures
- [DEPLOYMENT.md](DEPLOYMENT.md) - Production deployment
- [CONFIGURATION.md](CONFIGURATION.md) - Configuration reference
- [CLOUDFLARE_TUNNEL.md](CLOUDFLARE_TUNNEL.md) - Network setup
