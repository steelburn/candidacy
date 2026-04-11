<!-- files: src/auth-service/** -->

- [ ] <!-- task:T1 --> Set up the project structure for `auth-service`
  - [ ] Initialize a new Node.js project with required dependencies (`express`, `jsonwebtoken`, `bcrypt`, `pg`, `winston`)
  - [ ] Create the folder structure for controllers, services, repositories, and models
  - [ ] Configure environment variables for database and JWT secrets

- [x] <!-- task:T2 --> Implement the `/setup-check` endpoint
  - [x] Create `AuthController` to handle the `/setup-check` request
  - [x] Write unit tests for the `/setup-check` endpoint

- [x] <!-- task:T3 --> Implement the `/create-first-admin` endpoint
  - [x] Create `AuthController` to handle the `/create-first-admin` request
  - [x] Write unit tests for the `/create-first-admin` endpoint

- [ ] <!-- task:T4 --> Implement the `/login` endpoint
  - [x] Create `AuthController` to handle the `/login` request
  - [ ] Implement `AuthService` to validate user credentials and call `TokenService`
  - [ ] Implement `UserRepository` to fetch user data from the database
  - [x] Write unit tests for the `/login` endpoint

- [ ] <!-- task:T5 --> Implement the `/validate-token` endpoint
  - [ ] Create `AuthController` to handle the `/validate-token` request
  - [ ] Implement `TokenService` to validate JWTs and return token payload
  - [ ] Write unit tests for the `/validate-token` endpoint

- [ ] <!-- task:T6 --> Implement the `/role-check` endpoint
  - [ ] Create `AuthController` to handle the `/role-check` request
  - [ ] Extend `AuthService` to validate user roles based on the token payload
  - [ ] Write unit tests for the `/role-check` endpoint

- [ ] <!-- task:T7 --> Set up database schema and integration
  - [ ] Create a `users` table with fields for `id`, `username`, `passwordHash`, and `roles`
  - [x] Seed the database with test users and roles
  - [ ] Write integration tests for `UserRepository`

- [ ] <!-- task:T8 --> Implement logging and error handling
  - [ ] Integrate `winston` for logging authentication attempts and errors
  - [x] Add middleware for consistent error handling across endpoints
  - [ ] Write tests to verify logging and error handling behavior

- [ ] <!-- task:T9 --> Optimize performance and security
  - [ ] Implement rate-limiting middleware for login attempts
  - [x] Ensure password hashing uses `bcrypt` with a secure salt round configuration
  - [ ] Verify compliance with OAuth 2.0 and JWT standards

- [ ] <!-- task:T10 --> Write documentation
  - [x] Document all API endpoints with request/response examples
  - [x] Add setup instructions for running the `auth-service`
  - [ ] Include security considerations and best practices in the documentation

- [ ] <!-- task:T11 --> Perform end-to-end testing
  - [ ] Write integration tests for all endpoints to ensure proper functionality
  - [ ] Test concurrency handling for 1000+ requests
  - [ ] Verify response times meet the non-functional requirements