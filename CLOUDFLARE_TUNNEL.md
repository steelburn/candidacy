# Cloudflare Tunnel Setup Guide

This guide explains how to expose your Candidacy application to the internet using Cloudflare Tunnel, making it accessible at `https://ne1-candidacy.comulo.app` (or your custom domain).

## Table of Contents

- [What is Cloudflare Tunnel?](#what-is-cloudflare-tunnel)
- [Why Use Cloudflare Tunnel?](#why-use-cloudflare-tunnel)
- [Prerequisites](#prerequisites)
- [Step-by-Step Setup](#step-by-step-setup)
- [Configuration](#configuration)
- [Starting the Tunnel](#starting-the-tunnel)
- [Accessing Your Application](#accessing-your-application)
- [Troubleshooting](#troubleshooting)
- [Security Considerations](#security-considerations)
- [Advanced Configuration](#advanced-configuration)

## What is Cloudflare Tunnel?

Cloudflare Tunnel (formerly Argo Tunnel) creates a secure, outbound-only connection from your server to Cloudflare's edge network. This eliminates the need to:
- Open inbound firewall ports
- Configure port forwarding
- Expose your server's IP address
- Set up reverse proxies or load balancers

## Why Use Cloudflare Tunnel?

✅ **Security**: No inbound ports needed, reducing attack surface  
✅ **Simplicity**: No complex networking configuration  
✅ **Free**: Included with Cloudflare's free plan  
✅ **SSL/TLS**: Automatic HTTPS with Cloudflare's certificates  
✅ **DDoS Protection**: Built-in Cloudflare protection  
✅ **Global CDN**: Fast access from anywhere in the world  

## Prerequisites

Before you begin, ensure you have:

1. **Cloudflare Account**: Sign up at [cloudflare.com](https://cloudflare.com) (free tier works)
2. **Domain**: A domain added to your Cloudflare account (e.g., `comulo.app`)
3. **Docker**: Docker and Docker Compose installed and running
4. **Application Running**: Candidacy services should be up and running locally

## Step-by-Step Setup

### 1. Create a Cloudflare Tunnel

1. **Login to Cloudflare Dashboard**
   - Go to [https://one.dash.cloudflare.com/](https://one.dash.cloudflare.com/)
   - Select your account

2. **Navigate to Zero Trust**
   - Click on **Zero Trust** in the left sidebar
   - If prompted, set up Zero Trust (it's free)

3. **Create a Tunnel**
   - Go to **Access** → **Tunnels**
   - Click **Create a tunnel**
   - Choose **Cloudflared** as the tunnel type
   - Name your tunnel (e.g., `ne1-candidacy`)
   - Click **Save tunnel**

4. **Get Your Tunnel Token**
   - After creating the tunnel, you'll see installation instructions
   - Look for the `docker run` command
   - Copy the **tunnel token** (the long string after `--token`)
   - It looks like: `eyJhIjoiXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX...`

### 2. Configure Public Hostname

1. **Add Public Hostname**
   - In the tunnel configuration page, go to **Public Hostname** tab
   - Click **Add a public hostname**

2. **Configure Main Frontend**
   - **Subdomain**: `ne1-candidacy` (or your preferred subdomain)
   - **Domain**: Select `comulo.app` (or your domain)
   - **Path**: Leave empty
   - **Type**: `HTTP`
   - **URL**: `frontend:3000`
   - Click **Save hostname**

3. **Add Additional Routes** (Optional)
   You can add more specific routes for different services:
   
   | Service | Subdomain | Path | URL |
   |---------|-----------|------|-----|
   | API Gateway | ne1-candidacy | /api | api-gateway:8080 |
   | Applicant Portal | ne1-candidacy | /applicant | applicant-frontend:3000 |
   | Grafana | ne1-candidacy | /grafana | grafana:3000 |

### 3. Configure Environment Variables

1. **Copy the example environment file** (if you haven't already):
   ```bash
   cp .env.example .env
   ```

2. **Edit `.env` file** and add your tunnel token:
   ```bash
   # Cloudflare Tunnel Configuration
   CLOUDFLARE_TUNNEL_TOKEN=eyJhIjoiXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX...
   PUBLIC_DOMAIN=ne1-candidacy.comulo.app
   PUBLIC_API_URL=https://ne1-candidacy.comulo.app/api
   ```

3. **Save the file**

### 4. Start the Tunnel

Start (or restart) your services to include the Cloudflare Tunnel:

```bash
# If services are already running
docker-compose up -d

# Or restart all services
make restart

# Or start just the tunnel
docker-compose up -d cloudflared
```

### 5. Verify Tunnel Status

Check if the tunnel is running:

```bash
# View tunnel logs
docker-compose logs -f cloudflared

# Or use the Makefile command
make tunnel-logs
```

You should see output like:
```
INF Connection registered connIndex=0
INF Connection registered connIndex=1
INF Connection registered connIndex=2
INF Connection registered connIndex=3
```

## Configuration

### Tunnel Configuration File

The tunnel configuration is defined in `infrastructure/cloudflare/config.yml`:

```yaml
tunnel: <TUNNEL_ID>
credentials-file: /etc/cloudflared/credentials.json

ingress:
  # Main Frontend
  - hostname: ne1-candidacy.comulo.app
    service: http://frontend:3000
  
  # API Gateway
  - hostname: ne1-candidacy.comulo.app
    path: /api/*
    service: http://api-gateway:8080
  
  # Catch-all
  - service: http_status:404
```

### CORS Configuration

The API Gateway is automatically configured to accept requests from your public domain. The configuration is in `gateway/api-gateway/config/cors.php`:

```php
'allowed_origins' => array_filter([
    'http://localhost:3001',
    'http://localhost:3002',
    'http://localhost:5173',
    env('PUBLIC_DOMAIN') ? 'https://' . env('PUBLIC_DOMAIN') : null,
]),
```

## Starting the Tunnel

### Using Docker Compose

```bash
# Start all services including tunnel
docker-compose up -d

# Start only the tunnel
docker-compose up -d cloudflared

# Stop the tunnel
docker-compose stop cloudflared

# View tunnel logs
docker-compose logs -f cloudflared
```

### Using Makefile

```bash
# Start tunnel
make tunnel-up

# Stop tunnel
make tunnel-down

# View logs
make tunnel-logs

# Check status
make tunnel-status
```

## Accessing Your Application

Once the tunnel is running, you can access your application at:

- **Main Dashboard**: https://ne1-candidacy.comulo.app
- **Applicant Portal**: https://ne1-candidacy.comulo.app/applicant
- **API Gateway**: https://ne1-candidacy.comulo.app/api
- **Grafana**: https://ne1-candidacy.comulo.app/grafana

### Local Access Still Works

Your application is still accessible locally:
- **Main Dashboard**: http://localhost:3501
- **Applicant Portal**: http://localhost:5173
- **API Gateway**: http://localhost:9080

## Troubleshooting

### Tunnel Not Connecting

**Problem**: Tunnel shows as disconnected in Cloudflare dashboard

**Solutions**:
1. Check if the tunnel token is correct in `.env`
2. Verify the cloudflared container is running:
   ```bash
   docker ps | grep cloudflared
   ```
3. Check tunnel logs for errors:
   ```bash
   docker-compose logs cloudflared
   ```

### 502 Bad Gateway

**Problem**: Accessing the public URL shows "502 Bad Gateway"

**Solutions**:
1. Ensure all services are running:
   ```bash
   docker-compose ps
   ```
2. Check if the frontend service is healthy:
   ```bash
   curl http://localhost:3501
   ```
3. Verify the tunnel configuration points to the correct service names

### CORS Errors

**Problem**: Browser console shows CORS errors when accessing the public URL

**Solutions**:
1. Verify `PUBLIC_DOMAIN` is set correctly in `.env`
2. Restart the API Gateway after updating CORS settings:
   ```bash
   docker-compose restart api-gateway
   ```
3. Clear browser cache and cookies

### Authentication Issues

**Problem**: Unable to login via the public URL

**Solutions**:
1. Ensure JWT tokens are properly configured in `.env`
2. Check that cookies are being set correctly (HTTPS required)
3. Verify the API Gateway is accessible:
   ```bash
   curl https://ne1-candidacy.comulo.app/api/health
   ```

### DNS Not Resolving

**Problem**: Domain doesn't resolve to Cloudflare

**Solutions**:
1. Verify your domain is active in Cloudflare
2. Check DNS settings in Cloudflare dashboard
3. Wait for DNS propagation (can take up to 24 hours)
4. Test with `nslookup ne1-candidacy.comulo.app`

## Security Considerations

### ⚠️ Important Security Notes

1. **Authentication is Critical**
   - Your application is now publicly accessible
   - Ensure strong passwords are used
   - Consider enabling 2FA for admin accounts
   - Review user permissions regularly

2. **Environment Variables**
   - Never commit `.env` file to version control
   - Use strong, unique values for `APP_KEY` and `JWT_SECRET`
   - Rotate secrets regularly

3. **Database Security**
   - Database is not exposed through the tunnel (good!)
   - Keep database credentials secure
   - Regularly backup your database

4. **Rate Limiting**
   - Consider enabling Cloudflare rate limiting
   - Configure API rate limits in Laravel
   - Monitor for suspicious activity

5. **Cloudflare Security Features**
   - Enable **WAF (Web Application Firewall)** in Cloudflare
   - Set up **Access Policies** for admin routes
   - Enable **Bot Fight Mode**
   - Configure **Security Level** appropriately

6. **HTTPS Only**
   - The tunnel automatically provides HTTPS
   - Configure your app to redirect HTTP to HTTPS
   - Set secure cookie flags

### Recommended Cloudflare Settings

1. **SSL/TLS**: Set to "Full" or "Full (strict)"
2. **Always Use HTTPS**: Enable
3. **Automatic HTTPS Rewrites**: Enable
4. **Minimum TLS Version**: 1.2 or higher
5. **Authenticated Origin Pulls**: Consider enabling

## Advanced Configuration

### Custom Domain

To use a different domain or subdomain:

1. Update `.env`:
   ```bash
   PUBLIC_DOMAIN=your-subdomain.yourdomain.com
   PUBLIC_API_URL=https://your-subdomain.yourdomain.com/api
   ```

2. Update Cloudflare tunnel public hostname accordingly

3. Restart services:
   ```bash
   docker-compose restart
   ```

### Multiple Tunnels

You can run multiple tunnels for different environments:

```yaml
# Production tunnel
cloudflared-prod:
  image: cloudflare/cloudflared:latest
  environment:
    - TUNNEL_TOKEN=${CLOUDFLARE_TUNNEL_TOKEN_PROD}

# Staging tunnel
cloudflared-staging:
  image: cloudflare/cloudflared:latest
  environment:
    - TUNNEL_TOKEN=${CLOUDFLARE_TUNNEL_TOKEN_STAGING}
```

### Access Control

Restrict access to specific routes using Cloudflare Access:

1. Go to **Zero Trust** → **Access** → **Applications**
2. Click **Add an application**
3. Choose **Self-hosted**
4. Configure:
   - **Application name**: Candidacy Admin
   - **Application domain**: `ne1-candidacy.comulo.app`
   - **Path**: `/admin/*`
5. Set up access policies (e.g., require email authentication)

### Monitoring

Monitor your tunnel in Cloudflare:

1. Go to **Zero Trust** → **Access** → **Tunnels**
2. Click on your tunnel name
3. View:
   - Connection status
   - Traffic metrics
   - Active connections
   - Logs

## Additional Resources

- [Cloudflare Tunnel Documentation](https://developers.cloudflare.com/cloudflare-one/connections/connect-apps/)
- [Cloudflare Zero Trust](https://www.cloudflare.com/products/zero-trust/)
- [Cloudflare WAF](https://developers.cloudflare.com/waf/)
- [Candidacy Configuration Guide](CONFIGURATION.md)

## Support

If you encounter issues:

1. Check the [Troubleshooting](#troubleshooting) section
2. Review Cloudflare Tunnel logs: `make tunnel-logs`
3. Check Cloudflare dashboard for tunnel status
4. Review application logs: `make logs`
5. Consult Cloudflare community forums

---

**Note**: This setup is suitable for development and small-scale production deployments. For large-scale production use, consider additional security hardening, load balancing, and monitoring solutions.
