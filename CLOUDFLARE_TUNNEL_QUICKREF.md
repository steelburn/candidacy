# Cloudflare Tunnel - Quick Reference

## Quick Setup (5 Minutes)

### 1. Create Tunnel in Cloudflare
```
1. Go to: https://one.dash.cloudflare.com/
2. Navigate: Access → Tunnels → Create a tunnel
3. Name: ne1-candidacy
4. Copy the tunnel token (starts with eyJh...)
```

### 2. Configure Environment
```bash
# Edit .env file
CLOUDFLARE_TUNNEL_TOKEN=eyJhIjoiXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX...
PUBLIC_DOMAIN=ne1-candidacy.comulo.app
PUBLIC_API_URL=https://ne1-candidacy.comulo.app/api
```

### 3. Start Tunnel
```bash
make tunnel-up
```

### 4. Access Application
```
https://ne1-candidacy.comulo.app
```

---

## Commands

| Command | Description |
|---------|-------------|
| `make tunnel-up` | Start Cloudflare Tunnel |
| `make tunnel-down` | Stop Cloudflare Tunnel |
| `make tunnel-logs` | View tunnel logs |
| `make tunnel-status` | Check tunnel status |

---

## URLs

### Public Access (via Tunnel)
- **Main Dashboard**: https://ne1-candidacy.comulo.app
- **Applicant Portal**: https://ne1-candidacy.comulo.app/applicant
- **API Gateway**: https://ne1-candidacy.comulo.app/api
- **Grafana**: https://ne1-candidacy.comulo.app/grafana

### Local Access (Development)
- **Main Dashboard**: http://localhost:3001
- **Applicant Portal**: http://localhost:5173
- **API Gateway**: http://localhost:8080
- **Grafana**: http://localhost:3050

---

## Troubleshooting

### Tunnel Not Starting
```bash
# Check if token is set
grep CLOUDFLARE_TUNNEL_TOKEN .env

# View logs
make tunnel-logs

# Restart tunnel
make tunnel-down && make tunnel-up
```

### 502 Bad Gateway
```bash
# Check if services are running
docker-compose ps

# Restart services
docker-compose restart frontend api-gateway
```

### CORS Errors
```bash
# Verify PUBLIC_DOMAIN is set
grep PUBLIC_DOMAIN .env

# Restart API Gateway
docker-compose restart api-gateway
```

---

## Environment Variables

```bash
# Required
CLOUDFLARE_TUNNEL_TOKEN=<your-token-here>

# Optional (defaults shown)
PUBLIC_DOMAIN=ne1-candidacy.comulo.app
PUBLIC_API_URL=https://ne1-candidacy.comulo.app/api
```

---

## Security Checklist

- [ ] Strong passwords enabled
- [ ] JWT tokens configured
- [ ] Cloudflare WAF enabled
- [ ] Rate limiting configured
- [ ] Bot protection enabled
- [ ] SSL/TLS set to "Full" or "Full (strict)"
- [ ] Access Policies configured for admin routes

---

## Files

- **Configuration**: `infrastructure/cloudflare/config.yml`
- **Docker Service**: `docker-compose.yml` (cloudflared service)
- **Environment**: `.env` (CLOUDFLARE_TUNNEL_TOKEN)
- **CORS**: `gateway/api-gateway/config/cors.php`
- **Documentation**: `CLOUDFLARE_TUNNEL.md`

---

## Support

- **Full Guide**: See [CLOUDFLARE_TUNNEL.md](CLOUDFLARE_TUNNEL.md)
- **Cloudflare Docs**: https://developers.cloudflare.com/cloudflare-one/connections/connect-apps/
- **Dashboard**: https://one.dash.cloudflare.com/
