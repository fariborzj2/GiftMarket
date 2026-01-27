# Gift Card Wholesale & Retail System

A multilingual gift card sales platform designed for both wholesale and retail workflows, built with a lightweight PHP backend and a plugin-based architecture for extensibility and long-term maintainability.

## Project Status
**Current Phase:** MVP (Minimum Viable Product) with Admin Panel & SSR.
The system now features a lightweight PHP backend with Server-Side Rendering (SSR), a secure Admin Panel for product management, and a MySQL database for data persistence.

## Core Features
- Wholesale and retail gift card management
- Customer management system (profiles, order history, status tracking)
- Order and transaction management
- Plugin-based architecture for modular feature development
- Lightweight and optimized PHP backend
- Multilingual support (i18n-ready)
- Light and dark theme support
- Fully responsive user interface
- SEO-optimized structure and metadata
- Frontend powered by `.tpl` template engine
- Built-in installer for fast and guided setup

## Admin Panel
- Professional PHP-based admin panel
- Modern, fully responsive, and user-friendly UI/UX
- `.tpl` template engine for admin views
- Role-based access control (admin / staff)
- Product, category, pricing, and inventory management
- Customer management and segmentation
- Order management and tracking
- Modular plugin management
- Site configuration and content management

## Payment & Extensibility
- Currently, no integrated online payment gateway
- Plugin-based architecture allows easy future integration of payment gateways
- Developers can create custom payment plugins without modifying the core system

## Architecture Overview
- Lightweight PHP backend with minimal dependencies
- Plugin system using hooks/events for safe extensibility
- Core logic isolated from plugins to avoid core modification
- API-ready backend structure for future REST/JSON endpoints
- Centralized configuration management

## Installation
The system includes a built-in installer that guides users through the setup process, including database configuration and initial system settings.

Installation is performed through a step-by-step web-based installer, allowing the system to be deployed and ready for use in a few simple steps.

### MySQL Setup (cPanel)
1. Create a MySQL database in cPanel.
2. Create a MySQL user and assign it to the database with all privileges.
3. Import the `database.sql` file into your database via **phpMyAdmin**.
4. Update `includes/config.php` with your database credentials (`DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`).

## Use Cases
- Gift card wholesalers
- Retail gift card stores
- Multi-language digital product platforms
- Custom gift card marketplaces

## Project Goals
- Keep the backend lightweight and maintainable
- Allow easy extension through plugins, including future payment gateways
- Provide a modern and user-friendly admin interface with optimal UI/UX
- Avoid framework lock-in
- Provide a clean and scalable foundation for real-world usage

## License
This project is licensed under the MIT License - see a LICENSE file for details (or specify your license).
