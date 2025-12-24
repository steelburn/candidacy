# Database Schema Documentation

This directory contains the complete database schema for the Candidacy application using DBML (Database Markup Language).

## DBML-First Workflow

The Candidacy application uses **DBML as the single source of truth** for database schema. Instead of writing Laravel migrations, you edit `schema.dbml` and generate SQL from it.

### Quick Start

```bash
# Install dependencies
npm install

# Validate DBML syntax
make dbml-validate

# Generate SQL from DBML
make dbml-sql

# Initialize databases from DBML (fresh install)
make dbml-init

# Reset databases from DBML (drops all data!)
make dbml-reset
```

### Making Schema Changes

1. **Edit schema.dbml**
   ```bash
   # Edit the DBML file with your changes
   vim schema.dbml
   ```

2. **Validate syntax**
   ```bash
   make dbml-validate
   ```

3. **Generate SQL**
   ```bash
   make dbml-sql
   ```

4. **Apply to databases**
   ```bash
   # For development (drops and recreates - loses data!)
   make dbml-reset
   
   # For production (review generated SQL first)
   # Check database/sql/*.sql files
   # Apply manually or create migration
   ```

### Available Commands

| Command | Description |
|---------|-------------|
| `make dbml-validate` | Validate DBML syntax |
| `make dbml-sql` | Generate SQL from DBML |
| `make dbml-check` | Check if DBML is in sync with databases |
| `make dbml-init` | Initialize databases from DBML |
| `make dbml-reset` | Drop & recreate databases from DBML ⚠️ |

## Files

- **`schema.dbml`** - Complete database schema for all microservices

## What is DBML?

DBML (Database Markup Language) is a simple, readable DSL language designed to define database structures. It allows you to:

- Document database schemas in a human-readable format
- Generate database diagrams automatically
- Use as a single source of truth for database structure
- Version control your database design

## Schema Overview

The Candidacy application uses a microservices architecture with separate databases for each service:

### Service Databases

1. **candidacy_auth** - Authentication and authorization
   - users, roles, permissions, role_user, permission_role

2. **candidacy_candidate** - Candidate management
   - candidates, cv_files, candidate_tokens, applicant_answers, job_statuses

3. **candidacy_vacancy** - Job vacancy management
   - vacancies, vacancy_questions, required_skills

4. **candidacy_matching** - AI-powered candidate-vacancy matching
   - matches, job_statuses_matching

5. **candidacy_interview** - Interview scheduling and feedback
   - interviews, interview_feedback

6. **candidacy_offer** - Job offer management
   - offers

7. **candidacy_onboarding** - New hire onboarding
   - onboarding_checklists

8. **candidacy_admin** - Application settings
   - settings

9. **candidacy_document_parser** - Asynchronous document parsing
   - parse_jobs

## Using the DBML Schema

### Viewing the Schema

You can visualize the database schema using:

1. **dbdiagram.io** - Paste the DBML content at https://dbdiagram.io/d
2. **dbdocs.io** - Generate interactive documentation
3. **VS Code Extension** - Install "DBML Language" extension for syntax highlighting

### Generating SQL

You can convert DBML to SQL using the `@dbml/cli` tool:

```bash
# Install the CLI
npm install -g @dbml/cli

# Generate MySQL SQL
dbml2sql schema.dbml --mysql -o schema.sql

# Generate PostgreSQL SQL
dbml2sql schema.dbml --postgres -o schema.sql
```

### Generating Diagrams

```bash
# Generate diagram image
dbml2sql schema.dbml --mysql -o schema.sql
```

## Cross-Service Relationships

Due to the microservices architecture, relationships between tables in different services are **logical** rather than enforced by foreign keys. These relationships are documented in the DBML file with comments.

### Key Logical Relationships

- **Candidate ↔ Vacancy**: Through matches, interviews, offers
- **Candidate ↔ User**: Through interviews (interviewer), feedback (reviewer)
- **Vacancy ↔ Matches**: AI-generated candidate-vacancy pairs
- **Matches ↔ Interviews**: Progression from match to interview
- **Interviews ↔ Offers**: Progression from interview to offer
- **Offers ↔ Onboarding**: Progression from accepted offer to onboarding

## Maintaining the Schema

### When to Update

Update `schema.dbml` whenever you need to:

1. Add new tables
2. Modify existing tables (add/remove columns)
3. Change column types or constraints
4. Add or remove indexes
5. Modify relationships

### Best Practices

1. **DBML is the source of truth** - All schema changes start in `schema.dbml`
2. **Validate before applying** - Always run `make dbml-validate` before generating SQL
3. **Document changes** - Add notes to tables explaining business logic
4. **Version control** - Commit schema changes with related code changes
5. **Review generated SQL** - Check `database/sql/*.sql` files before applying to production
6. **Use dbml-reset for development** - Fast, clean slate for local development
7. **Manual migrations for production** - Review and apply SQL changes carefully

## Validation

To validate the DBML syntax:

```bash
# Install DBML CLI
npm install -g @dbml/cli

# Validate syntax
dbml2sql schema.dbml --mysql > /dev/null
```

If there are no errors, the syntax is valid.

## Indexes

All performance indexes are documented in the schema with their names matching the actual database indexes:

- Single column indexes: `idx_{table}_{column}`
- Composite indexes: `idx_{table}_{col1}_{col2}`
- Unique constraints: `{table}_{col1}_{col2}_unique`

## Enumerations

Common enum values are documented at the end of the schema file for reference. These represent the allowed values for various status and type fields.

## Future Enhancements

Potential improvements to the schema:

1. Add candidate_experiences, candidate_educations, candidate_skills tables
2. Add vacancy_skills table for structured skill requirements
3. Add notification_logs table for tracking sent notifications
4. Add audit_logs table for compliance and tracking
5. Add document_templates table for offer letters, contracts

## Resources

- [DBML Documentation](https://www.dbml.org/docs/)
- [dbdiagram.io](https://dbdiagram.io/) - Online diagram tool
- [DBML CLI](https://www.dbml.org/cli/) - Command-line tools
- [VS Code Extension](https://marketplace.visualstudio.com/items?itemName=matt-meyers.vscode-dbml)
