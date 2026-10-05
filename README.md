<div align="center">

# 🤝 Skilled Swap Marketplace

### Share Skills • Exchange Knowledge • Grow Together

A modern peer-to-peer skill-sharing platform that connects people who
want to teach what they know and learn something new in return.

<br>

<a href="https://github.com/Kushal0789/Skilled-swap-marketplace">
  <img src="https://img.shields.io/badge/💻_GitHub-Repository-181717?style=for-the-badge&logo=github" alt="GitHub">
</a>

<a href="#-features">
  <img src="https://img.shields.io/badge/✨_Features-Explore-6C63FF?style=for-the-badge" alt="Features">
</a>

<br><br>

<img src="https://img.shields.io/badge/PHP-8.x-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP">
<img src="https://img.shields.io/badge/MySQL-Database-4479A1?style=for-the-badge&logo=mysql&logoColor=white" alt="MySQL">
<img src="https://img.shields.io/badge/HTML5-Frontend-E34F26?style=for-the-badge&logo=html5&logoColor=white" alt="HTML5">
<img src="https://img.shields.io/badge/CSS3-Styling-1572B6?style=for-the-badge&logo=css3&logoColor=white" alt="CSS3">
<img src="https://img.shields.io/badge/JavaScript-ES6+-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black" alt="JavaScript">
<img src="https://img.shields.io/badge/Fetch_API-AJAX-FF6B35?style=for-the-badge" alt="Fetch API">
<img src="https://img.shields.io/badge/PDO-Secure_DB-4CAF50?style=for-the-badge" alt="PDO">

<br><br>

</div>

---


# 🌟 About the Project

**Skilled Swap Marketplace** is a web-based platform designed to make
peer-to-peer learning easier through skill exchange.

Instead of paying for every skill someone wants to learn, users can
share their existing knowledge in exchange for learning another skill.

For example:

- 💻 A developer can teach web development while learning graphic design.
- 🎨 A designer can teach Photoshop while learning JavaScript.
- 📸 A photographer can teach photography while learning video editing.
- 🎵 A musician can teach music while learning another creative skill.

The platform provides a centralized environment where users can
showcase their skills, discover compatible learning partners, propose
skill swaps, and communicate with other members.

> **Everyone has something to teach and something new to learn.**

---

# 🎯 Problem Statement

People have different skills and knowledge, but finding someone who can
teach a desired skill while also benefiting from the skills they offer
can be difficult.

Traditional learning methods may require:

- Paid courses
- Private tutors
- Expensive training
- Multiple platforms for communication and discovery

**Skilled Swap Marketplace** provides a single platform where users can
discover people with complementary skills and exchange knowledge
directly.

---

# 🎯 Project Objectives

The main objectives of the project are:

- 🤝 Encourage peer-to-peer learning and knowledge sharing.
- 🔍 Help users discover people with complementary skills.
- 🎯 Make skill exchange easier and more organized.
- 💬 Provide communication between skill-exchange partners.
- 🔄 Allow users to manage skill-swap proposals.
- 🔐 Provide secure authentication and account management.
- 🛡️ Provide administrative and moderation functionality.
- 💻 Demonstrate practical full-stack web development.

---

# ✨ Features

<table>
<tr>
<td width="50%">

### 🔐 User Authentication

- User registration
- Secure login
- Session management
- Password hashing
- Logout functionality

</td>

<td width="50%">

### 👤 User Profiles

- Personal profiles
- Skills offered
- Skills requested
- Profile management
- Account settings

</td>
</tr>

<tr>
<td>

### 🔍 Skill Discovery

- Explore available skills
- Discover other users
- Find learning opportunities
- Explore compatible skills

</td>

<td>

### 🎯 Skill Matching

- Match users based on skills
- Discover complementary interests
- Find potential learning partners

</td>
</tr>

<tr>
<td>

### 🔄 Swap Proposals

- Send swap proposals
- Receive proposals
- Accept proposals
- Decline proposals
- Track proposal status

</td>

<td>

### 💬 In-App Messaging

- Communicate with other users
- Discuss skill exchanges
- Coordinate learning sessions

</td>
</tr>

<tr>
<td>

### 🔔 Notifications

- Swap proposal updates
- Activity notifications
- User interaction updates

</td>

<td>

### 🛡️ Admin & Moderation

- Platform monitoring
- User management
- Skill monitoring
- Basic moderation functionality

</td>
</tr>
</table>

---

# 🔄 How It Works

The basic workflow of Skilled Swap Marketplace is:

```text
        👤 Register
             │
             ▼
      📝 Create Profile
             │
             ▼
        🎯 Add Skills
             │
             ▼
      🔍 Discover Users
             │
             ▼
      🤝 Find Skill Match
             │
             ▼
       📩 Send Proposal
             │
             ▼
     ✅ Accept / Decline
             │
             ▼
        💬 Connect
             │
             ▼
      🧠 Exchange Skills


🛠️ Technology Stack

Technology	Purpose
PHP 8.x:	Backend development and server-side logic
MySQL:	Relational database management
PDO:	Secure database interaction
HTML5:	Page structure and semantic markup
CSS3:	Styling, layouts, themes, and responsive UI
JavaScript (ES6+):	Client-side interactions and dynamic functionality
Fetch API:	Asynchronous communication between frontend and backend
JSON:	Data exchange between JavaScript and PHP API endpoints
Apache:	Local web server
XAMPP:	Local development environment
Git	Version control
GitHub	Source-code hosting and collaboration
🏗️ System Architecture

Skilled Swap Marketplace follows a simple three-layer web application architecture.

┌─────────────────────────────┐
│         Frontend            │
│     HTML + CSS + JavaScript │
└──────────────┬──────────────┘
               │
               │ Fetch API / HTTP
               ▼
┌─────────────────────────────┐
│       PHP Application       │
│   Business Logic + API      │
└──────────────┬──────────────┘
               │
               │ PDO
               ▼
┌─────────────────────────────┐
│          MySQL              │
│        Database             │
└─────────────────────────────┘
Architecture Flow

Frontend → Fetch API → PHP/API → PDO → MySQL

The frontend communicates with PHP endpoints using the Fetch API. PHP processes the request and interacts with MySQL through PDO before returning the required response, commonly in JSON format.

📁 Project Structure
Skilled-swap-marketplace/
│
├── api/
│   └── API endpoints and backend request handlers
│
├── assets/
│   ├── css/
│   ├── js/
│   └── images/
│
├── config/
│   └── Database and application configuration
│
├── database/
│   └── Database-related files and SQL resources
│
├── includes/
│   └── Reusable PHP components and shared functionality
│
├── admin.php
├── dashboard.php
├── discover.php
├── index.php
├── login.php
├── logout.php
├── matches.php
├── messages.php
├── profile.php
├── register.php
├── requests.php
├── settings.php
│
├── DOCUMENTATION.md
└── README.md

The repository structure separates reusable components, database configuration, API functionality, frontend assets, and application pages to keep the project organized and maintainable.

🗄️ Database

The application uses MySQL as its relational database.

Database interaction is handled through PHP Data Objects (PDO).

Why PDO?

PDO provides:

Secure database connections.
Prepared statements.
Protection against SQL injection when used correctly.
A consistent interface for database operations.
Cleaner and reusable database code.
Database Relationship

At a high level, the application connects users with their skills and skill-exchange activities.

Users
  │
  ├────────── Skills
  │
  ├────────── Swap Requests
  │
  ├────────── Messages
  │
  └────────── Notifications

The database uses relational concepts such as:

Primary keys
Foreign keys
Relationships between tables
Unique identifiers
Structured relational data
🔌 API & Fetch API

The project uses JavaScript's Fetch API to communicate asynchronously with backend PHP endpoints.

Instead of requiring a complete page reload for every interaction, JavaScript can send requests to the backend and process the returned response dynamically.

Basic Request Flow
User Action
     ↓
JavaScript
     ↓
Fetch API
     ↓
PHP API Endpoint
     ↓
PDO
     ↓
MySQL
     ↓
PHP Response
     ↓
JSON
     ↓
JavaScript
     ↓
UI Update

This approach makes the application more interactive and provides a smoother user experience.

🔐 Security

Security was considered throughout the application.

Password Hashing

User passwords are stored using password hashing rather than storing plain-text passwords.

PDO Prepared Statements

Database queries use PDO and prepared statements to reduce the risk of SQL injection.

Session Authentication

Sessions are used to maintain authenticated user states and restrict access to protected pages.

Input Validation

User-provided information should be validated and sanitized before being processed or stored.

Access Control

Different areas of the application are protected according to the user's authentication and role.

🚀 Installation & Setup
Prerequisites

Before running the project, install:

XAMPP
PHP 8.x
MySQL
Apache
Git
A modern web browser
1. Clone the Repository
git clone https://github.com/Kushal0789/Skilled-swap-marketplace.git

Navigate into the project:

cd Skilled-swap-marketplace
2. Move the Project to XAMPP

If you are using XAMPP on Windows, place the project inside:

C:\xampp\htdocs\

The final path should look similar to:

C:\xampp\htdocs\Skilled-swap-marketplace
3. Start XAMPP

Open the XAMPP Control Panel and start:

Apache
MySQL

Both services should be running before accessing the application.

4. Create the Database

Open phpMyAdmin:

http://localhost/phpmyadmin

Create the required MySQL database and import the SQL file provided with the project.

5. Configure Database Connection

Update the project's database configuration with your local MySQL credentials.

Typical local XAMPP configuration:

Host: localhost
Username: root
Password: 
Database: your_database_name

Use the database name and configuration expected by the SQL file and project configuration.

6. Run the Application

Open the project through your local XAMPP server:

http://localhost/Skilled-swap-marketplace/

The application should now be available in your browser.

📸 Screenshots
🏠 Home Page

<img width="1467" height="875" alt="image" src="https://github.com/user-attachments/assets/4fae9936-c152-4327-bacf-134129c7dc66" />


🔐 Login Page

<img width="1227" height="872" alt="image" src="https://github.com/user-attachments/assets/d86f8377-e874-475d-9844-079c907b055b" />

📊 Dashboard

<img width="1351" height="871" alt="image" src="https://github.com/user-attachments/assets/b83fe93f-61ab-4fe6-9394-37ca37c02e5b" />

🔍 Discover Page

<img width="1317" height="880" alt="image" src="https://github.com/user-attachments/assets/7afb55d8-fd26-4d5d-9c3e-f6b2c9dabdf3" />



📚 Documentation

For more detailed information about the project, including technical implementation and project documentation, see:

DOCUMENTATION.md

🔮 Future Improvements

Possible future enhancements include:

⭐ User ratings and reviews
🔎 Advanced skill search and filtering
📅 Skill-exchange session scheduling
📱 Improved mobile responsiveness
🔔 More advanced notification functionality
🏆 Skill-learning achievements and milestones
📊 User activity and learning analytics
🖼️ Improved profile portfolios
🔐 More advanced role-based access control
🌐 Deployment to a production hosting environment
⚡ Further API and database optimization
🤝 Contributing

Contributions, suggestions, and improvements are welcome.

Contribution Workflow
Fork the repository
        ↓
Create a new branch
        ↓
Make your changes
        ↓
Test the changes
        ↓
Commit your changes
        ↓
Push the branch
        ↓
Create a Pull Request
👨‍💻 Author
Kushal Bhusal

GitHub: @Kushal0789

Project: Skilled Swap Marketplace

📄 License

This project currently does not include a separate open-source license.

If you plan to distribute or allow reuse of the project, consider adding an appropriate license such as the MIT License.

⭐ Support the Project

If you find this project useful or interesting, consider giving the repository a ⭐ on GitHub.

<div align="center">

🤝 Share What You Know. Learn What You Love. Grow Together.

Skilled Swap Marketplace

Built with PHP • MySQL • JavaScript • HTML • CSS

</div>
