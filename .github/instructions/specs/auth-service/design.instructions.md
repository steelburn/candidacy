---
name: auth-service Design
applyTo: auth-service/**
description: Architecture and design for the auth-service feature
---
# Design Document: Auth-Service

## 1. Architecture Overview

The `auth-service` is a stateless microservice responsible for authentication and authorization. It will expose RESTful APIs for login, token generation, and role-based access control. The service will use JSON Web Tokens (JWT) for stateless authentication and will integrate with a database for user credential validation and role management.

### Key Technologies:
- **Programming Language**: Node.js
- **Framework**: Express.js
- **Database**: PostgreSQL
- **Token Standard**: JWT (RFC 7519)
- **Encryption**: bcrypt for password hashing, AES-256 for sensitive data

---

## 2. Components

### 2.1 API Endpoints
| Endpoint               | Method | Description                          | Request Body                  | Response                  |
|------------------------|--------|--------------------------------------|-------------------------------|---------------------------|
| `/login`               | POST   | Authenticates user and generates JWT | `{ username, password }`      | `{ token, expiresIn }`    |
| `/validate-token`      | POST   | Validates the provided JWT           | `{ token }`                   | `{ valid: true/false }`   |
| `/role-check`          | POST   | Verifies user role for access        | `{ token, requiredRole }`     | `{ access: true/false }`  |

### 2.2 Internal Modules
- **AuthController**: Handles HTTP requests and responses.
- **AuthService**: Business logic for authentication and role validation.
- **TokenService**: Generates and validates JWTs.
- **UserRepository**: Interacts with the database for user data.
- **Logger**: Logs authentication attempts and errors.

### 2.3 Data Models
- **User**:
  ```typescript
  interface User {
    id: string;
    username: string;
    passwordHash: string;
    roles: string[];
  }
  ```
- **TokenPayload**:
  ```typescript
  interface TokenPayload {
    userId: string;
    roles: string[];
    exp: number;
  }
  ```

### 2.4 Current Implementation Status

- **Implemented Endpoints**:
  - `/login`: Authenticates user and generates JWT.
  - `/register`: Registers a new user.
  - `/setup-check`: Checks if the system requires initial setup.
  - `/create-first-admin`: Creates the first admin user.

- **Pending Endpoints**:
  - `/validate-token`: Validates the provided JWT.
  - `/role-check`: Verifies user role for access.

- **Middleware**:
  - `auth:api` applied to all routes except `login`, `register`, `setupCheck`, and `createFirstAdmin`.

- **Password Hashing**:
  - `bcrypt` with 12 rounds is used for secure password storage.

- **Token Standard**:
  - JWT (RFC 7519) is used for stateless authentication.

---

## 3. Data Flow

```
+-------------------+       +-------------------+       +-------------------+
|   Client Request  | --->  |   Auth-Service    | --->  |   Database (User) |
+-------------------+       +-------------------+       +-------------------+
        |                          |                          |
        | <------------------------| <------------------------|
        |       Response           |       User Data          |
```

---

## 4. Sequence Diagram

### User Login
```
Client -> AuthController: POST /login
AuthController -> UserRepository: Validate credentials
UserRepository -> AuthController: Return user data
AuthController -> TokenService: Generate JWT
TokenService -> AuthController: Return JWT
AuthController -> Client: Return token
```

### Role Validation
```
Client -> AuthController: POST /role-check
AuthController -> TokenService: Validate JWT
TokenService -> AuthController: Return token payload
AuthController -> UserRepository: Check user role
UserRepository -> AuthController: Return role validation
AuthController -> Client: Return access result
```

---

## 5. Error Handling

| Error Code | Scenario                              | Response Message                  |
|------------|--------------------------------------|-----------------------------------|
| 401        | Invalid credentials during login     | "Unauthorized: Invalid credentials" |
| 401        | Expired or invalid JWT               | "Unauthorized: Invalid token"     |
| 403        | Insufficient role permissions        | "Forbidden: Access denied"        |
| 500        | Internal server error                | "Internal Server Error"           |

---

## 6. Dependencies

- **bcrypt**: For password hashing.
- **jsonwebtoken**: For JWT generation and validation.
- **pg**: PostgreSQL client for database interaction.
- **winston**: For logging authentication attempts and errors.

---

## 7. Open Questions

1. Should the `auth-service` support token revocation (e.g., blacklist)?
2. Should the service include rate-limiting for login attempts to prevent brute force attacks?
3. Will the `auth-service` need to integrate with an external identity provider in future releases?