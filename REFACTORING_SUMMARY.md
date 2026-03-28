# Project Refactoring Summary

## Overview
Major restructuring completed to move API and configuration files outside the public directory for better security and organization.

## Changes Made

### 1. Directory Structure

**Created:**
- `/api/` - All API endpoints (moved from `/public/api/`)
- `/config/` - Configuration and utility files (moved from `/public/partials/`)

**Kept in `/public/partials/`:**
- `footer.php`
- `header.php`
- `meta.php`
- `sidebar.php`

### 2. Files Moved

**From `/public/api/` to `/api/`:**
- All 26 API endpoint files including:
  - `brushlearn-book_*.php` (4 files)
  - `cal_verify.php`
  - `licence_approve.php`
  - `ohc_assignment_*.php` (3 files)
  - `ohc_location_*.php` (3 files)
  - `page_*.php` (5 files)
  - `settings_*.php` (5 files)
  - `shopify_tag.php`
  - `sync_external_ids.php`
  - `theme_toggle.php`
  - `functions.php`

**From `/public/partials/` to `/config/`:**
- `config.php` - Main configuration file
- `classes.php` - Theme and utility classes
- `account-emailtoken.php` - Email sending functionality
- `account-ratelimit.php` - Rate limiting with APCu

### 3. Path Updates

**All API files (`/api/*.php`):**
- Updated: `require_once __DIR__ . '/../partials/config.php'`
- To: `require_once __DIR__ . '/../config/config.php'`

**All public PHP files:**
- Updated: `require_once __DIR__ . '/partials/config.php'`
- To: `require_once __DIR__ . '/../config/config.php'`

**Config files:**
- Updated vendor autoload paths from `../../vendor/` to `../vendor/`
- Updated .env path to `__DIR__ . '/../.env'`

**JavaScript fetch calls:**
- All relative paths `fetch('api/...)` updated to `fetch('/api/...)`
- Maintained backward compatibility with existing `/api/` URLs

### 4. API Router Implementation

**Created `/public/api.php`:**
- Routes all `/api/*` requests to actual API files in `/api/` folder
- Security: Validates file names, prevents directory traversal
- Returns proper JSON error responses for invalid requests

**Created `/public/.htaccess`:**
- Rewrites `/api/*.php` requests to `api.php` router
- Maintains clean URL structure for frontend

### 5. Security Improvements

**Rate Limiting:**
- Uses APCu (confirmed available on server)
- 5 requests per 60 seconds per IP
- Applied to login and registration endpoints

**Account Enumeration Protection:**
- Login always returns same message regardless of account existence
- HTTP 200 status for all valid requests

**API Security:**
- API files now outside public directory
- Only accessible through controlled router
- Input validation and sanitization maintained

## Testing Checklist

- [ ] Login page works (`/account-login.php`)
- [ ] Registration page works (`/account-register.php`)
- [ ] Dashboard loads correctly (`/index.php`)
- [ ] All API endpoints respond correctly
- [ ] Rate limiting functions properly
- [ ] Theme toggle works
- [ ] Brush & Learn booking flow works
- [ ] Setup pages (licence, OHC, forms, pages) work
- [ ] Profile editing works
- [ ] Shop verification works

## File Locations Reference

```
/curaproxclub.com_pro/
├── api/                          # All API endpoints (26 files)
├── config/                       # Configuration files (4 files)
│   ├── config.php               # Main config
│   ├── classes.php              # Utility classes
│   ├── account-emailtoken.php   # Email functions
│   └── account-ratelimit.php    # Rate limiting
├── public/                       # Public web root
│   ├── api.php                  # API router (NEW)
│   ├── .htaccess                # URL rewriting (NEW)
│   ├── partials/                # UI components only
│   │   ├── footer.php
│   │   ├── header.php
│   │   ├── meta.php
│   │   └── sidebar.php
│   └── [all other PHP pages]
└── vendor/                       # Composer dependencies
```

## Notes

- All frontend `/api/` fetch calls continue to work without changes
- API router provides transparent proxy to actual API files
- Configuration files are now properly separated from UI components
- Better security: sensitive files outside public directory
- Cleaner organization: clear separation of concerns
