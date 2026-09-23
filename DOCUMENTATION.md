# Skill Swap Marketplace - Technical Documentation & Viva Examination Guide

This document is prepared for academic evaluation, project defense, and viva examinations for college/university degree requirements.

---

## 1. Project Overview

### What is the Skill Swap Marketplace?
The **Skill Swap Marketplace** is a peer-to-peer web application that facilitates cashless, reciprocal knowledge exchange. Rather than paying monetary fees for tutors or online courses, users trade their existing expertise (e.g., teaching Python in exchange for learning Adobe Photoshop).

### Problem Statement
Traditional education platforms impose financial barriers to acquiring new skills. Meanwhile, millions of individuals possess valuable skills they could share with peers in return for personalized mentorship. Skill Swap eliminates monetary barriers and builds community reciprocity.

---

## 2. Three-Tier Architectural Design

The application strictly adheres to the classical **Three-Tier Architecture** pattern:

```
┌────────────────────────────────────────────────────────┐
│         PRESENTATION LAYER (Client / Frontend)        │
│  - HTML5 (Semantic Structure)                          │
│  - Pure CSS3 (Custom Properties, Flexbox, CSS Grid)    │
│  - Vanilla JavaScript ES6+ (Fetch API, DOM Events)     │
│  - Asynchronous AJAX Polling & Modals                  │
└──────────────────────────┬─────────────────────────────┘
                           │ HTTP Request / JSON Response
┌──────────────────────────▼─────────────────────────────┐
│       APPLICATION / BUSINESS LAYER (Server / Logic)     │
│  - PHP 8.x Processing & REST-like API Endpoints        │
│  - Two-Way Mutual Skill Matchmaking Engine             │
│  - State Machine for Swap Request Transitions          │
│  - Session Authentication & Role Authorization         │
│  - Input Sanitization & CSRF Token Validation          │
└──────────────────────────┬─────────────────────────────┘
                           │ PDO Prepared Queries
┌──────────────────────────▼─────────────────────────────┐
│              DATA LAYER (Persistence / Storage)        │
│  - MySQL Relational Database (skill_swap)              │
│  - Normalized Schema (users, skills, user_skills, etc.)│
│  - Foreign Key Constraints with Referential Integrity  │
│  - B-Tree Composite Indexes for Query Optimization     │
└────────────────────────────────────────────────────────┘
```

### Layer Responsibilities

1. **Presentation Layer (Frontend)**:
   - Responsible strictly for rendering data, capturing user gestures, dynamic theme switching (Dark/Light mode via CSS custom properties), and sending non-blocking asynchronous Fetch requests.
   - Contains **zero SQL queries** and **zero business state logic**.

2. **Application Layer (Business Logic / Backend)**:
   - Encapsulated inside `api/` and `includes/`.
   - Validates all incoming payloads before processing.
   - Implements authentication, authorization gates (`require_auth()`, `require_admin()`), the reciprocal skill matching algorithm, notification dispatching, and error handling.
   - Formats responses uniformly using HTTP status codes and JSON payloads.

3. **Data Layer (Database)**:
   - Centrally connected via `config/db.php` using PHP Data Objects (PDO).
   - Enforces referential integrity using relational constraints (`ON DELETE CASCADE`).
   - Stores encrypted password hashes and relational state.

---

## 3. Technology Justification (Why These Tools?)

### Why Pure PHP 8.x (No Frameworks)?
1. **Core Understanding**: Frameworks like Laravel or Symfony abstract request routing, ORM, and dependency injection behind magic methods. Writing pure PHP demonstrates a solid grasp of HTTP methods, session cookies, superglobals (`$_POST`, `$_SERVER`), and raw server execution.
2. **Portability**: Pure PHP runs instantly on any default Apache/XAMPP stack without requiring Composer dependencies, Node package managers, or build steps.
3. **Execution Performance & Modern Syntax**: PHP 8.x introduces typed properties, union types, nullsafe operators, and JIT compilation, ensuring clean and type-safe code.

### Why MySQL & Relational Database?
1. **Strong Referential Integrity**: A skill swap involves relationships between users, master skills, swap proposals, and conversations. Foreign key constraints ensure orphaned messages or broken references cannot occur.
2. **ACID Compliance**: Transactional integrity ensures database states remain consistent even if unexpected errors arise.

### Why PDO (PHP Data Objects)?
1. **Security**: PDO provides true parameterized prepared statements that completely separate SQL logic from user input, rendering SQL Injection impossible.
2. **Consistency & Error Handling**: Standardizes database exceptions under `PDOException` and supports transactions (`beginTransaction`, `commit`, `rollBack`).

### Why Vanilla JavaScript & Fetch API?
1. **Zero Build Tools Required**: Works immediately in any modern browser without Webpack, Vite, Babel, or transpilations.
2. **Native Asynchronous Programming**: ES6+ `async/await` and `fetch()` handle REST API communication asynchronously without page flickering or reloads.
3. **Lightweight & High Performance**: Zero library overhead or virtual DOM diffing calculations.

---

## 4. Key Algorithms & Systems Explained

### A. The Two-Way Skill Matchmaking Engine (`api/matches/find.php`)

The algorithm analyzes the intersection between two complementary skill matrices:

#### Logic:
Let:
- $O_A$ = Set of skills User $A$ **Offers**
- $W_A$ = Set of skills User $A$ **Wants**
- $O_B$ = Set of skills User $B$ **Offers**
- $W_B$ = Set of skills User $B$ **Wants**

1. **Two-Way Reciprocal Match (100% Compatibility)**:
   $$\text{Match}_{2\text{-way}} \iff (W_A \cap O_B \neq \emptyset) \land (O_A \cap W_B \neq \emptyset)$$
   User $B$ offers at least one skill that User $A$ wants, **AND** User $A$ offers at least one skill that User $B$ wants.
   *Example*: Sarah offers Python and wants Photoshop. Alex offers Photoshop and wants Python. The engine highlights this as a primary reciprocal exchange.

2. **One-Way Mentorship Opportunity (70% Compatibility)**:
   $$\text{Match}_{1\text{-way}} \iff (W_A \cap O_B \neq \emptyset) \land (O_A \cap W_B = \emptyset)$$
   User $B$ offers what User $A$ wants, but does not currently have any of User $A$'s offerings on their wishlist.

---

### B. Request State Machine (`api/requests/`)

The life cycle of a swap request follows a deterministic state machine:

```
                  ┌───────────────┐
       ┌──────────│    Pending    │──────────┐
       │          └───────┬───────┘          │
       │ Receiver         │ Receiver         │ Sender
       │ Accepts          │ Rejects          │ Cancels
       ▼                  ▼                  ▼
┌──────────────┐   ┌──────────────┐   ┌──────────────┐
│   Accepted   │   │   Rejected   │   │  Cancelled   │
└──────┬───────┘   └──────────────┘   └──────────────┘
       │
       ▼
Spawns Chat Thread & Allows Mutual Review
```

---

### C. Near Real-Time Messaging via Asynchronous Polling (`api/messages/`)

Instead of requiring complex WebSocket servers (such as Ratchet or Node.js daemons), the chat client implements lightweight **delta polling**:
1. When a conversation is opened, it retrieves the initial history and stores the highest message ID: `lastMessageId`.
2. Every 3.5 seconds, `fetchMessages(true)` requests:
   `GET api/messages/list.php?conversation_id=X&since_id={lastMessageId}`
3. The server executes:
   `SELECT * FROM messages WHERE conversation_id = :cid AND id > :since_id`
4. If no new messages exist, the payload size is ~20 bytes, placing virtually zero burden on the Apache server.
5. New messages are seamlessly appended to the DOM without layout disruption or full re-renders.

---

## 5. Security Architecture

1. **SQL Injection (SQLi) Defense**:
   - Every database query utilizes PDO prepared statements with bounded parameters (e.g. `:id`, `:email`). No concatenated queries exist in the codebase.

2. **Cross-Site Scripting (XSS) Defense**:
   - All server output rendered in HTML templates is escaped using `htmlspecialchars($str, ENT_QUOTES, 'UTF-8')` via the `e()` helper function.
   - Client-side DOM manipulation uses `textContent` or custom `escapeHtml()` sanitizers.

3. **Cross-Site Request Forgery (CSRF) Defense**:
   - Cryptographically secure 64-character hex tokens generated via `bin2hex(random_bytes(32))` stored in `$_SESSION['csrf_token']`.
   - Sent automatically in the `X-CSRF-Token` header for API requests and verified with `hash_equals()`.

4. **Password Security**:
   - Never stored in plaintext.
   - Hashed using PHP's native `password_hash($password, PASSWORD_BCRYPT)`.
   - Verified during login via `password_verify()`.

5. **File Upload Hardening**:
   - Validates MIME type using `finfo(FILEINFO_MIME_TYPE)` rather than trusting client-supplied file extensions.
   - Enforces a 2MB maximum file size limit.
   - Renames files using `avatar_` followed by cryptographically random hexadecimal strings (`bin2hex(random_bytes(16))`) to prevent directory traversal and name collision attacks.
   - Includes `.htaccess` within the avatars directory that disables CGI and script execution.

---

## 6. Common Viva Questions & Model Answers

### Q1: What makes this a 3-tier architecture?
**Answer**:
The application clearly partitions responsibility into three distinct layers:
1. **Presentation Layer**: HTML5 templates, CSS3 design system, and Vanilla JavaScript controllers that handle user interface interactions.
2. **Business Layer**: PHP scripts in `api/` and `includes/` that validate incoming data, authenticate sessions, and compute skill matching compatibility.
3. **Data Layer**: Centralized PDO repository in `config/db.php` connecting to MySQL relational tables with indexed foreign keys. No frontend file contains direct SQL queries.

### Q2: How does your skill matching algorithm differentiate between two-way and one-way matches?
**Answer**:
A two-way match occurs when User A offers what User B wants AND User B offers what User A wants ($W_A \cap O_B \neq \emptyset \land O_A \cap W_B \neq \emptyset$). This represents perfect mutual reciprocity. A one-way match occurs when another user teaches a skill on the current user's wishlist, but doesn't seek any skill the current user currently offers.

### Q3: How do you prevent SQL injection attacks in pure PHP?
**Answer**:
We use PDO (PHP Data Objects) with prepared statements and parameter binding. When using prepared statements, the SQL query structure is parsed and compiled by the database engine *before* the parameters are injected. Therefore, user input is strictly treated as data literals rather than executable SQL code.

### Q4: Why did you implement polling instead of WebSockets for messaging?
**Answer**:
Standard WebSockets require a persistent Node.js or Ratchet daemon process running on a custom port alongside Apache. In educational, shared-hosting, and standard XAMPP environments, persistent daemon processes are often restricted or complex to configure. Incremental polling with `since_id` provides a lightweight, highly compatible alternative that works out-of-the-box on default Apache setups with minimal bandwidth overhead.

### Q5: How is user authentication maintained across page requests?
**Answer**:
Authentication is maintained using PHP sessions. Upon successful validation with `password_verify()`, the server regenerates the session ID to prevent session fixation and stores the user's ID in `$_SESSION['user_id']`. The client receives a session cookie with `HttpOnly` and `SameSite=Lax` flags, preventing malicious scripts from intercepting the session identifier.

### Q6: How do you prevent duplicate swap requests between two users?
**Answer**:
Before inserting into the `swap_requests` table, the server executes a query verifying whether an active request with status `pending` or `accepted` already exists between the sender and receiver in either direction. If found, the endpoint returns an HTTP 409 Conflict status.
