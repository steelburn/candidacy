# DBML Tools - Containerized Database Schema Management

## Overview

The DBML (Database Markup Language) tools have been containerized to eliminate the need for Node.js installation on the host machine. All DBML operations now run inside a dedicated Docker container.

## What Changed

### Before
- Required Node.js and npm installed on the host machine
- Commands like `make dbml-validate` ran `npm run dbml:validate` directly on the host
- Users had to manage Node.js dependencies separately

### After
- **No Node.js required on the host** - all operations run in a Docker container
- Commands like `make dbml-validate` run inside the `dbml-tools` container
- Dependencies are managed automatically within the container

## Architecture

### New Files

1. **`infrastructure/docker/Dockerfile.dbml`**
   - Node.js 20 Alpine-based image
   - Includes bash and MySQL client
   - Pre-installs npm dependencies

2. **Updated `docker-compose.yml`**
   - New `dbml-tools` service with `dbml` profile
   - Mounts workspace directory
   - Connects to MySQL for database operations

### Updated Files

1. **`Makefile`**
   - All `dbml-*` commands now use `docker compose run --rm dbml-tools`
   - No direct npm commands on host

2. **`scripts/init-databases-from-dbml.sh`**
   - Removed Node.js installation check
   - Removed npm install step (handled by container)

## Usage

All DBML commands work exactly the same as before:

```bash
# Validate DBML schema syntax
make dbml-validate

# Generate SQL from DBML
make dbml-sql

# Check if generated SQL is in sync with DBML
make dbml-check

# Initialize databases from DBML (fresh install)
make dbml-init

# Reset databases from DBML (drops all data!)
make dbml-reset
```

## How It Works

1. **Container Profile**: The `dbml-tools` service uses the `dbml` profile, so it's only started when needed
2. **Automatic Cleanup**: Commands use `--rm` flag to automatically remove the container after execution
3. **Volume Mounting**: The entire workspace is mounted at `/workspace` in the container
4. **Network Access**: Container connects to the `candidacy-network` to access MySQL

## Benefits

✅ **No Host Dependencies**: No need to install Node.js on the host machine  
✅ **Consistent Environment**: Same Node.js version for all developers  
✅ **Isolated Dependencies**: npm packages don't pollute host system  
✅ **Easy Cleanup**: Containers are automatically removed after use  
✅ **CI/CD Ready**: Works seamlessly in containerized CI/CD pipelines  

## Technical Details

### Container Specifications

- **Base Image**: `node:20-alpine`
- **Additional Packages**: bash, mysql-client
- **Working Directory**: `/workspace`
- **Network**: `candidacy-network`
- **Profile**: `dbml` (on-demand)

### Command Flow

When you run `make dbml-init`:

1. Docker Compose builds the `dbml-tools` image (if not exists)
2. Starts a temporary container with workspace mounted
3. Runs `bash scripts/init-databases-from-dbml.sh` inside container
4. Script generates SQL using `npm run dbml:sql`
5. Script connects to MySQL and applies schema
6. Container is automatically removed

## Troubleshooting

### Container Build Issues

If you encounter build issues:

```bash
# Rebuild the DBML tools image
docker compose build dbml-tools

# Or force rebuild without cache
docker compose build --no-cache dbml-tools
```

### Permission Issues

If you encounter permission issues with generated files:

```bash
# The container runs as root, so generated files might need ownership change
sudo chown -R $USER:$USER database/sql/
```

### MySQL Connection Issues

If the script can't connect to MySQL:

```bash
# Ensure MySQL is running
docker compose ps mysql

# Check MySQL logs
docker compose logs mysql

# Verify network connectivity
docker compose run --rm dbml-tools ping mysql
```

## Migration Guide

If you previously had Node.js installed for DBML operations:

1. **You can safely uninstall Node.js from your host** (if not needed for other projects)
2. **Remove `node_modules/` from your workspace** (optional, won't interfere)
3. **All existing `make dbml-*` commands work unchanged**

## Development

### Running Custom Commands

You can run any npm command inside the container:

```bash
# Run any npm script
docker compose run --rm dbml-tools npm run <script-name>

# Access container shell for debugging
docker compose run --rm dbml-tools bash

# Install new npm packages (updates package.json)
docker compose run --rm dbml-tools npm install <package-name> --save-dev
```

### Updating Dependencies

To update npm dependencies:

```bash
# Update package.json manually, then rebuild
docker compose build dbml-tools

# Or run npm update inside container
docker compose run --rm dbml-tools npm update
```

## See Also

- [DATABASE.md](../DATABASE.md) - DBML workflow and schema documentation
- [CONTRIBUTING.md](../CONTRIBUTING.md) - Database change guidelines
- [Makefile](../Makefile) - All available DBML commands
