# DBML Tools - Quick Reference

## ✅ What Changed

**Before:** Required Node.js installed on host  
**After:** All DBML operations run in Docker container

## 🚀 Usage (No Changes!)

All commands work exactly the same:

```bash
make dbml-validate    # Validate DBML syntax
make dbml-sql         # Generate SQL from DBML
make dbml-check       # Check sync status
make dbml-init        # Initialize databases
make dbml-reset       # Reset databases (destructive)
```

## 🎯 Key Benefits

✅ **No Node.js required on host**  
✅ **Consistent environment** for all developers  
✅ **Isolated dependencies** (no npm pollution)  
✅ **Auto cleanup** (containers removed after use)  
✅ **CI/CD ready** out of the box  

## 🔧 How It Works

1. Commands run inside `dbml-tools` container
2. Container has Node.js 20 + MySQL client pre-installed
3. Workspace mounted at `/workspace` in container
4. Container automatically removed after execution

## 📚 Documentation

- **Full Guide:** [docs/DBML-TOOLS.md](docs/DBML-TOOLS.md)
- **DBML Workflow:** [DATABASE.md](DATABASE.md)
- **Changelog:** [CHANGELOG.md](CHANGELOG.md)

## 🐛 Troubleshooting

### Rebuild container if needed:
```bash
docker compose build dbml-tools
```

### Run custom commands:
```bash
docker compose run --rm dbml-tools npm run <script>
docker compose run --rm dbml-tools bash
```

### Check container logs:
```bash
docker compose logs dbml-tools
```

## 🔄 Migration

If you previously had Node.js for DBML:

1. ✅ You can uninstall Node.js from host (if not needed elsewhere)
2. ✅ All `make dbml-*` commands work unchanged
3. ✅ No configuration changes needed
