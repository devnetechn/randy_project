# Randy's Painting & Drywall — Vanilla PHP + MySQL

A full rewrite of the React/Node app as **plain PHP + MySQL + vanilla JS**, built to
run on **XAMPP** with no build step, no Composer, and no Node.

| Was (framework) | Now (vanilla) |
|---|---|
| React + Vite + Tailwind + React Router | Plain HTML/CSS/JS, one `.php` file per page |
| Node + Express + PostgreSQL | PHP 8 + MySQL/MariaDB (PDO) |
| Socket.io real-time chat | AJAX polling (every 3s) |
| JWT + refresh tokens | PHP sessions |
| Gemini AI (Node SDK) | Gemini via cURL, with a scripted-bot fallback |

## Features
- Marketing site: Home, Services, Gallery, About, Contact
- Accounts: register / log in / log out (customer + admin roles)
- Booking: customers request appointments and track status; admins confirm / decline / reschedule / complete
- Chat: guest assistant → log in → live chat with "talk to a human" handoff; AI auto-replies (Gemini or scripted)
- Admin dashboard: KPI overview, live chat queue, bookings manager, gallery upload/delete

## Setup (XAMPP)

1. Put this folder in `C:\xampp\htdocs\randy` (already here).
2. Start **Apache** and **MySQL** from the XAMPP Control Panel.
3. (Optional) edit `config.php` — DB credentials, admin login, Gemini API key.
   Defaults assume XAMPP MySQL (`root`, no password) and database `randy_db`.
4. Open **http://localhost/randy/setup.php** — this creates the database, tables,
   and the first admin account.
5. Open **http://localhost/randy/** — the website is live.

Default admin login (from `config.php`): `admin@randyspaintdrywall.com` / `changeme123`
— **change the password after first login.**

## Project layout
```
randy/
├── index.php services.php gallery.php about.php contact.php   ← marketing
├── login.php register.php logout.php                          ← auth (sessions)
├── book.php bookings.php chat.php                             ← customer
├── admin/index.php                                            ← admin dashboard
├── api/                                                       ← JSON endpoints (fetch)
│   ├── chat/   appointments/   gallery/   admin/
├── includes/                                                  ← shared PHP
│   ├── app.php db.php session.php helpers.php business.php
│   ├── chat.php gemini.php scripted-bot.php marketing.php
│   ├── header.php footer.php
├── assets/  css/styles.css   js/{app,chat,gallery,admin}.js   img/
├── uploads/gallery/                                           ← admin-uploaded photos
├── sql/schema.sql                                             ← MySQL schema
├── config.php (config.example.php)                            ← settings
└── setup.php                                                  ← one-time installer
```

## Notes
- **Gemini is optional.** With no API key the chat uses a built-in rule-based bot
  that answers from the business info — no external calls.
- **Chat is near-real-time** via polling. No websockets are required, so it works
  on stock Apache.
- Serve from a different URL path? Change `base_path` in `config.php`.
- For production, delete `setup.php` and change the admin password.
