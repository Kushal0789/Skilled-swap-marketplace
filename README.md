<div align="center">

# 🤝 Skilled Swap Marketplace

### Share Skills • Exchange Knowledge • Grow Together

A modern peer-to-peer skill-sharing platform that connects people who want to teach what they know and learn something new in return.

<p>
  <a href="https://github.com/Kushal0789/Skilled-swap-marketplace">
    <img src="https://img.shields.io/badge/GitHub-Repository-181717?style=for-the-badge&logo=github" alt="GitHub Repository">
  </a>
  <img src="https://img.shields.io/badge/PHP-8.x-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP">
  <img src="https://img.shields.io/badge/MySQL-Database-4479A1?style=for-the-badge&logo=mysql&logoColor=white" alt="MySQL">
  <img src="https://img.shields.io/badge/JavaScript-ES6%2B-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black" alt="JavaScript">
</p>

<p>
  <img src="https://img.shields.io/badge/HTML5-E34F26?style=flat-square&logo=html5&logoColor=white">
  <img src="https://img.shields.io/badge/CSS3-1572B6?style=flat-square&logo=css3&logoColor=white">
  <img src="https://img.shields.io/badge/Fetch%20API-HTTP-4CAF50?style=flat-square">
  <img src="https://img.shields.io/badge/PDO-PHP%20Database-777BB4?style=flat-square">
  <img src="https://img.shields.io/badge/Apache-Server-D22128?style=flat-square&logo=apache&logoColor=white">
  <img src="https://img.shields.io/badge/XAMPP-Local%20Development-FB7A24?style=flat-square&logo=xampp&logoColor=white">
</p>

</div>

---

## 📑 Table of Contents

- [📌 About the Project](#-about-the-project)
- [🎯 Problem Statement](#-problem-statement)
- [🎯 Project Objectives](#-project-objectives)
- [✨ Features](#-features)
- [🔄 How It Works](#-how-it-works)
- [🛠️ Technology Stack](#️-technology-stack)
- [🏗️ System Architecture](#️-system-architecture)
- [📁 Project Structure](#-project-structure)
- [🗄️ Database](#️-database)
- [🔌 API & Fetch API](#-api--fetch-api)
- [🔐 Security](#-security)
- [🚀 Installation & Setup](#-installation--setup)
- [🖥️ Screenshots](#️-screenshots)
- [📚 Documentation](#-documentation)
- [🔮 Future Improvements](#-future-improvements)
- [🤝 Contributing](#-contributing)
- [👨‍💻 Author](#-author)
- [📄 License](#-license)
- [⭐ Support](#-support)

---

## 📌 About the Project

**Skilled Swap Marketplace** is a peer-to-peer skill-sharing platform designed to help people exchange knowledge and skills with one another.

Instead of paying for every skill they want to learn, users can offer something they already know and find someone who can teach them something they want to learn.

### 💡 Example

- A developer can teach **Web Development** while learning **Graphic Design**.
- A designer can teach **Photoshop** while learning **JavaScript**.
- A photographer can teach **Photography** while learning **Video Editing**.
- A musician can teach **Music** while learning another creative skill.

### What Users Can Do

- Create and manage profiles
- Add skills they can teach
- Add skills they want to learn
- Discover other users
- Find compatible skill matches
- Send and manage swap requests
- Communicate with other users
- Receive notifications
- Manage account settings

---

## 🎯 Problem Statement

Many people possess valuable skills but have difficulty finding the right people to exchange knowledge with.

Traditional learning methods may require:

- Paid courses
- Tutors
- Expensive training
- Multiple platforms
- Limited peer-to-peer interaction

**Skilled Swap Marketplace** provides a centralized platform where users can discover people with complementary skills and create mutually beneficial learning exchanges.

---

## 🎯 Project Objectives

The main objectives of the project are:

1. Provide a platform for **peer-to-peer learning**.
2. Allow users to showcase the skills they can offer.
3. Help users discover skills they want to learn.
4. Make it easier to find compatible skill partners.
5. Provide a system for sending and managing swap requests.
6. Allow users to communicate through the platform.
7. Implement secure user authentication and authorization.
8. Provide administrative and moderation functionality.
9. Demonstrate a complete full-stack web application using PHP, MySQL, JavaScript, HTML, and CSS.

---

## ✨ Features

| Feature | Description |
|---|---|
| 🔐 **User Authentication** | Registration, login, sessions, password hashing, and logout |
| 👤 **User Profiles** | Manage personal profiles and skills |
| 🛠️ **Skill Management** | Add skills users can offer and skills they want to learn |
| 🔎 **Skill Discovery** | Discover users and available skills |
| 🤝 **Skill Matching** | Find users with compatible skills |
| 📩 **Swap Requests** | Send, accept, decline, and manage skill-swap requests |
| 💬 **Messaging** | Communicate with other users |
| 🔔 **Notifications** | Receive updates related to platform activity |
| ⚙️ **Settings** | Manage account and application settings |
| 🛡️ **Admin & Moderation** | Administrative functionality for managing the platform |

---

## 🔄 How It Works

### 1. Register

Create an account using the registration system.

### 2. Create Your Profile

Add your personal information and build your profile.

### 3. Add Skills

Specify:

- Skills you can teach
- Skills you want to learn

### 4. Discover Users

Browse other users and their available skills.

### 5. Find a Match

Look for users whose skills complement what you want to learn.

### 6. Send a Swap Request

Send a request to another user to propose a skill exchange.

### 7. Accept or Decline

The receiving user can manage the request.

### 8. Connect & Exchange

Once the request is accepted, users can communicate and exchange knowledge.

---

## 🛠️ Technology Stack

### Frontend

| Technology | Purpose |
|---|---|
| **HTML5** | Page structure |
| **CSS3** | Styling and responsive interface |
| **JavaScript ES6+** | Client-side functionality |
| **Fetch API** | Asynchronous communication with PHP endpoints |

### Backend

| Technology | Purpose |
|---|---|
| **PHP 8.x** | Server-side application logic |
| **PDO** | Database interaction |
| **JSON** | Data exchange between frontend and backend |

### Database

| Technology | Purpose |
|---|---|
| **MySQL** | Application database |
| **phpMyAdmin** | Database administration |

### Development Tools

| Tool | Purpose |
|---|---|
| **Apache** | Local web server |
| **XAMPP** | Local PHP/MySQL development environment |
| **Git** | Version control |
| **GitHub** | Source-code hosting and collaboration |

---

## 🏗️ System Architecture

Skilled Swap Marketplace follows a simple web application architecture.

### Architecture Layers

**Frontend**

HTML + CSS + JavaScript

⬇️

**Fetch API / HTTP**

⬇️

**PHP Application**

Business Logic + API Endpoints

⬇️

**PDO**

⬇️

**MySQL Database**

### Data Flow

The application follows this general request flow:

**User Action → JavaScript → Fetch API → PHP API → PDO → MySQL → JSON Response → UI Update**

This approach allows the frontend to communicate with the backend asynchronously without requiring a complete page reload for every operation.

---

## 📁 Project Structure


Skilled-swap-marketplace/
│
├── api/
├── assets/
│   ├── css/
│   ├── js/
│   └── images/
│
├── config/
├── database/
├── includes/
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
