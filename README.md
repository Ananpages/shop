# 🛍️ Beibe Marketplace — Laravel Backend

Full-stack Uganda marketplace API built with **Laravel 11 + Sanctum + SQLite/MySQL**.

---

## 🚀 Quick Setup

### 1. Install Dependencies
```bash
composer install
```

### 2. Environment Setup
```bash
cp .env.example .env
php artisan key:generate
```

### 3. Database (SQLite — zero config)
```bash
touch database/database.sqlite
php artisan migrate --seed
```

> For **MySQL**, update `.env`:
> ```
> DB_CONNECTION=mysql
> DB_HOST=127.0.0.1
> DB_PORT=3306
> DB_DATABASE=beibe_market
> DB_USERNAME=root
> DB_PASSWORD=yourpassword
> ```

### 4. Storage Link (for file uploads)
```bash
php artisan storage:link
```

### 5. Run the Server
```bash
php artisan serve
# → http://localhost:8000
```

---

## 🔑 Default Accounts

| Role   | Phone        | Email               | Password   |
|--------|-------------|---------------------|------------|
| Admin  | 0700000000  | admin@beibe.com     | admin123   |
| Seller | 0712345678  | seller@beibe.com    | seller123  |

---

## 🔗 Connecting React Frontend

In your React frontend `vite.config.js`, the proxy is already set to port 5000 (old Node.js backend). **Update it to 8000** for Laravel:

```js
// frontend/vite.config.js
proxy: {
  '/api': {
    target: 'http://localhost:8000',  // ← Change this from 5000 to 8000
    changeOrigin: true,
  }
}
```

Also update `frontend/src/utils/api.js` base URL if needed:
```js
const API_BASE = '/api'; // stays the same — proxy handles it
```

---

## 📁 Project Structure

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── AuthController.php
│   │   ├── ShopController.php
│   │   ├── ProductController.php
│   │   ├── CartController.php
│   │   ├── OrderController.php
│   │   ├── ReviewController.php
│   │   ├── WishlistController.php
│   │   ├── ChatController.php
│   │   ├── NotificationController.php
│   │   ├── UploadController.php
│   │   ├── CategoryController.php
│   │   └── AdminController.php
│   └── Middleware/
│       ├── CorsMiddleware.php
│       └── CheckRole.php
├── Models/
│   ├── User.php
│   ├── Shop.php
│   ├── Product.php
│   ├── Category.php
│   ├── CartItem.php
│   ├── Order.php
│   ├── OrderStatusHistory.php
│   ├── Review.php
│   └── SocialModels.php  (Wishlist, Conversation, Message, Notification, RecentlyViewed)
database/
├── migrations/           (4 migration files)
└── seeders/
    └── DatabaseSeeder.php
routes/
└── api.php               (all 40+ routes)
config/
└── beibe.php             (districts, categories, delivery fee)
```

---

## 📡 API Endpoints

| Method | Endpoint                         | Auth | Description |
|--------|----------------------------------|------|-------------|
| POST   | /api/auth/register               | —    | Register |
| POST   | /api/auth/login                  | —    | Login → returns token |
| GET    | /api/auth/me                     | ✓    | Current user + shop |
| GET    | /api/products                    | —    | List products (filter/sort/paginate) |
| GET    | /api/products/{id}               | —    | Product detail + reviews |
| POST   | /api/products                    | ✓    | Create product |
| GET    | /api/shops/{slug}                | —    | Shop page + products |
| POST   | /api/shops                       | ✓    | Open shop |
| GET    | /api/shops/my/dashboard          | ✓    | Seller dashboard stats |
| GET    | /api/cart                        | ✓    | Get cart |
| POST   | /api/cart                        | ✓    | Add to cart |
| POST   | /api/orders                      | ✓    | Place order |
| GET    | /api/orders                      | ✓    | My orders |
| PUT    | /api/orders/{id}/status          | ✓    | Update order status |
| POST   | /api/wishlist/toggle             | ✓    | Toggle wishlist |
| GET    | /api/chat/conversations          | ✓    | Chat list |
| POST   | /api/chat/start                  | ✓    | Start conversation |
| POST   | /api/chat/{id}/messages          | ✓    | Send message |
| GET    | /api/notifications               | ✓    | Notifications |
| POST   | /api/upload                      | ✓    | Upload single image |
| POST   | /api/upload/multiple             | ✓    | Upload multiple images |
| GET    | /api/admin/stats                 | Admin| Platform stats |
| PUT    | /api/admin/shops/{id}/approve    | Admin| Approve shop |

---

## 🔐 Authentication

Laravel Sanctum is used for API token auth. All protected routes require:
```
Authorization: Bearer YOUR_TOKEN_HERE
```

Tokens are issued on login/register and expire after **30 days**.

---

## 📤 File Uploads

Images are stored at `storage/app/public/uploads/` and served at `/storage/uploads/filename.jpg`.

Make sure to run `php artisan storage:link` after setup.

---

## 🇺🇬 Uganda-Specific

- 30 Uganda districts pre-configured in `config/beibe.php`
- Default delivery fee: **USh 3,000**
- Currency: **Uganda Shillings (UGX)**
- Phone login support (no email required)
