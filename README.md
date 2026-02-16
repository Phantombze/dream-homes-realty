# Dream Homes Realty

A modern, responsive real estate website for Dream Homes Realty - a Belize-based property company.

## 🏡 About

Dream Homes Realty helps clients find, buy, sell, and rent quality properties throughout Belize with integrity, local expertise, and personalized guidance.

## 🚀 Features

- **Property Listings** - Browse available properties with detailed information
- **Search & Filters** - Find properties by location, price, category
- **Individual Property Pages** - Detailed property views with image galleries
- **Responsive Design** - Mobile-friendly across all devices
- **Admin Panel** - Manage listings, settings, and content
- **SEO Optimized** - Structured data, meta tags, sitemap
- **Community Focus** - Showcase of community support initiatives

## 🛠️ Tech Stack

- **Frontend:** HTML5, CSS3 (Custom), Vanilla JavaScript
- **Backend:** PHP 7.4+
- **Database:** MySQL/MariaDB
- **Deployment:** Apache/Nginx

## 📁 Project Structure

```
Dream Homes/
├── assets/
│   ├── css/
│   │   ├── site.css           # Main stylesheet (source)
│   │   ├── site.min.css       # Minified for production
│   │   └── admin.css          # Admin panel styles
│   ├── js/                    # JavaScript files
│   └── img/                   # Images & media
├── admin/                     # Admin panel
├── includes/                  # PHP includes (header, footer)
├── index.php                  # Homepage
├── listings.php               # Property listings page
├── listing.php                # Individual property page
├── about.php                  # About page
├── contact.php                # Contact page
├── config.php                 # Configuration
└── build.sh                   # Build script for minification
```

## 🔧 Development Setup

### Prerequisites
- PHP 7.4 or higher
- MySQL/MariaDB
- Web server (Apache/Nginx/XAMPP/WAMP)

### Local Installation

1. **Clone the repository:**
   ```bash
   git clone https://github.com/PhantomBze/dream-homes.git
   cd dream-homes
   ```

2. **Configure environment:**
   - Copy `config.php` and update settings
   - Set `ENVIRONMENT` to `development`

3. **Database setup:**
   - Import database schema (if applicable)
   - Update database credentials in `config.php`

4. **Start local server:**
   ```bash
   # Using PHP built-in server
   php -S localhost:8000

   # Or use XAMPP/WAMP and place in htdocs
   ```

5. **Access the site:**
   - Frontend: `http://localhost:8000`
   - Admin: `http://localhost:8000/admin`

## 📦 Deployment

See [DEPLOYMENT.md](DEPLOYMENT.md) for detailed deployment instructions.

**Quick deployment steps:**

1. Build minified assets:
   ```bash
   bash build.sh
   ```

2. Upload to production server:
   - All PHP files
   - `assets/css/*.min.css` (minified CSS)
   - `assets/img/` (images)
   - `assets/js/` (scripts)

3. Set environment on server:
   ```apache
   # In .htaccess
   SetEnv ENVIRONMENT production
   ```

## 🎨 CSS Architecture

- **Organized by sections** - Navigation, Hero, Listings, Cards, Forms, Footer
- **CSS Variables** - Consistent colors, spacing, transitions
- **Mobile-first** - Responsive breakpoints at 992px, 768px, 480px
- **BEM-like naming** - `.dh-` prefix for all classes
- **Table of contents** - Quick navigation in site.css

## 🤝 Contributing

This is a private project for Dream Homes Realty. For internal team members:

1. Create a feature branch: `git checkout -b feature/your-feature`
2. Make your changes and commit: `git commit -m 'Add feature'`
3. Push to the branch: `git push origin feature/your-feature`
4. Create a Pull Request

## 📝 License

© 2026 Dream Homes Realty. All rights reserved.

## 📧 Contact

**Dream Homes Realty**
Belize

- Website: dreamhomes-realty.com
- Email: info@dreamhomes-realty.com

---

Built with ❤️ for Belize real estate
