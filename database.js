const Database = require('better-sqlite3');
const path = require('path');

const DB_PATH = path.join(__dirname, 'beibe_market.db');
const db = new Database(DB_PATH);

// Enable WAL mode for better performance
db.pragma('journal_mode = WAL');
db.pragma('foreign_keys = ON');

function initDatabase() {
  db.exec(`
    -- USERS TABLE
    CREATE TABLE IF NOT EXISTS users (
      id TEXT PRIMARY KEY,
      name TEXT NOT NULL,
      phone TEXT NOT NULL UNIQUE,
      email TEXT UNIQUE,
      password TEXT NOT NULL,
      role TEXT NOT NULL DEFAULT 'buyer',
      avatar TEXT,
      is_active INTEGER NOT NULL DEFAULT 1,
      created_at TEXT NOT NULL DEFAULT (datetime('now')),
      updated_at TEXT NOT NULL DEFAULT (datetime('now'))
    );

    -- SHOPS TABLE
    CREATE TABLE IF NOT EXISTS shops (
      id TEXT PRIMARY KEY,
      user_id TEXT NOT NULL,
      name TEXT NOT NULL UNIQUE,
      slug TEXT NOT NULL UNIQUE,
      description TEXT,
      logo TEXT,
      banner TEXT,
      phone TEXT NOT NULL,
      district TEXT NOT NULL,
      status TEXT NOT NULL DEFAULT 'pending',
      rating REAL DEFAULT 0,
      total_reviews INTEGER DEFAULT 0,
      total_sales INTEGER DEFAULT 0,
      created_at TEXT NOT NULL DEFAULT (datetime('now')),
      updated_at TEXT NOT NULL DEFAULT (datetime('now')),
      FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    );

    -- CATEGORIES TABLE
    CREATE TABLE IF NOT EXISTS categories (
      id TEXT PRIMARY KEY,
      name TEXT NOT NULL,
      slug TEXT NOT NULL UNIQUE,
      icon TEXT,
      image TEXT,
      parent_id TEXT,
      sort_order INTEGER DEFAULT 0,
      is_active INTEGER DEFAULT 1,
      created_at TEXT NOT NULL DEFAULT (datetime('now'))
    );

    -- PRODUCTS TABLE
    CREATE TABLE IF NOT EXISTS products (
      id TEXT PRIMARY KEY,
      shop_id TEXT NOT NULL,
      seller_id TEXT NOT NULL,
      name TEXT NOT NULL,
      slug TEXT NOT NULL,
      description TEXT,
      category_id TEXT NOT NULL,
      original_price REAL NOT NULL,
      discount_price REAL,
      stock INTEGER NOT NULL DEFAULT 0,
      district TEXT NOT NULL,
      images TEXT NOT NULL DEFAULT '[]',
      specifications TEXT DEFAULT '[]',
      tags TEXT DEFAULT '[]',
      rating REAL DEFAULT 0,
      total_reviews INTEGER DEFAULT 0,
      total_views INTEGER DEFAULT 0,
      total_sales INTEGER DEFAULT 0,
      status TEXT NOT NULL DEFAULT 'active',
      created_at TEXT NOT NULL DEFAULT (datetime('now')),
      updated_at TEXT NOT NULL DEFAULT (datetime('now')),
      FOREIGN KEY (shop_id) REFERENCES shops(id) ON DELETE CASCADE,
      FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE,
      FOREIGN KEY (category_id) REFERENCES categories(id)
    );

    -- CART TABLE
    CREATE TABLE IF NOT EXISTS cart_items (
      id TEXT PRIMARY KEY,
      user_id TEXT NOT NULL,
      product_id TEXT NOT NULL,
      quantity INTEGER NOT NULL DEFAULT 1,
      added_at TEXT NOT NULL DEFAULT (datetime('now')),
      FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
      FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
      UNIQUE(user_id, product_id)
    );

    -- ORDERS TABLE
    CREATE TABLE IF NOT EXISTS orders (
      id TEXT PRIMARY KEY,
      order_number TEXT NOT NULL UNIQUE,
      buyer_id TEXT NOT NULL,
      shop_id TEXT NOT NULL,
      items TEXT NOT NULL DEFAULT '[]',
      subtotal REAL NOT NULL,
      delivery_fee REAL NOT NULL DEFAULT 0,
      total REAL NOT NULL,
      delivery_district TEXT NOT NULL,
      delivery_address TEXT NOT NULL,
      buyer_phone TEXT NOT NULL,
      notes TEXT,
      status TEXT NOT NULL DEFAULT 'pending',
      payment_status TEXT NOT NULL DEFAULT 'pending',
      payment_method TEXT DEFAULT 'cash',
      created_at TEXT NOT NULL DEFAULT (datetime('now')),
      updated_at TEXT NOT NULL DEFAULT (datetime('now')),
      FOREIGN KEY (buyer_id) REFERENCES users(id),
      FOREIGN KEY (shop_id) REFERENCES shops(id)
    );

    -- ORDER STATUS HISTORY
    CREATE TABLE IF NOT EXISTS order_status_history (
      id TEXT PRIMARY KEY,
      order_id TEXT NOT NULL,
      status TEXT NOT NULL,
      note TEXT,
      created_by TEXT NOT NULL,
      created_at TEXT NOT NULL DEFAULT (datetime('now')),
      FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
    );

    -- REVIEWS TABLE
    CREATE TABLE IF NOT EXISTS reviews (
      id TEXT PRIMARY KEY,
      product_id TEXT NOT NULL,
      user_id TEXT NOT NULL,
      order_id TEXT,
      rating INTEGER NOT NULL CHECK(rating >= 1 AND rating <= 5),
      comment TEXT,
      images TEXT DEFAULT '[]',
      is_verified INTEGER DEFAULT 0,
      created_at TEXT NOT NULL DEFAULT (datetime('now')),
      FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
      FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
      UNIQUE(product_id, user_id)
    );

    -- WISHLIST TABLE
    CREATE TABLE IF NOT EXISTS wishlist (
      id TEXT PRIMARY KEY,
      user_id TEXT NOT NULL,
      product_id TEXT NOT NULL,
      added_at TEXT NOT NULL DEFAULT (datetime('now')),
      FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
      FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
      UNIQUE(user_id, product_id)
    );

    -- CHAT CONVERSATIONS TABLE
    CREATE TABLE IF NOT EXISTS conversations (
      id TEXT PRIMARY KEY,
      buyer_id TEXT NOT NULL,
      seller_id TEXT NOT NULL,
      shop_id TEXT NOT NULL,
      last_message TEXT,
      last_message_at TEXT,
      buyer_unread INTEGER DEFAULT 0,
      seller_unread INTEGER DEFAULT 0,
      created_at TEXT NOT NULL DEFAULT (datetime('now')),
      FOREIGN KEY (buyer_id) REFERENCES users(id) ON DELETE CASCADE,
      FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE,
      UNIQUE(buyer_id, shop_id)
    );

    -- MESSAGES TABLE
    CREATE TABLE IF NOT EXISTS messages (
      id TEXT PRIMARY KEY,
      conversation_id TEXT NOT NULL,
      sender_id TEXT NOT NULL,
      content TEXT NOT NULL,
      type TEXT DEFAULT 'text',
      product_id TEXT,
      is_read INTEGER DEFAULT 0,
      created_at TEXT NOT NULL DEFAULT (datetime('now')),
      FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON DELETE CASCADE,
      FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE
    );

    -- NOTIFICATIONS TABLE
    CREATE TABLE IF NOT EXISTS notifications (
      id TEXT PRIMARY KEY,
      user_id TEXT NOT NULL,
      title TEXT NOT NULL,
      body TEXT NOT NULL,
      type TEXT NOT NULL,
      reference_id TEXT,
      is_read INTEGER DEFAULT 0,
      created_at TEXT NOT NULL DEFAULT (datetime('now')),
      FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    );

    -- RECENTLY VIEWED TABLE
    CREATE TABLE IF NOT EXISTS recently_viewed (
      id TEXT PRIMARY KEY,
      user_id TEXT NOT NULL,
      product_id TEXT NOT NULL,
      viewed_at TEXT NOT NULL DEFAULT (datetime('now')),
      FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
      FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
      UNIQUE(user_id, product_id)
    );

    -- INDEXES
    CREATE INDEX IF NOT EXISTS idx_products_shop ON products(shop_id);
    CREATE INDEX IF NOT EXISTS idx_products_category ON products(category_id);
    CREATE INDEX IF NOT EXISTS idx_products_status ON products(status);
    CREATE INDEX IF NOT EXISTS idx_orders_buyer ON orders(buyer_id);
    CREATE INDEX IF NOT EXISTS idx_orders_shop ON orders(shop_id);
    CREATE INDEX IF NOT EXISTS idx_messages_conv ON messages(conversation_id);
    CREATE INDEX IF NOT EXISTS idx_notifications_user ON notifications(user_id);
  `);

  // Seed categories if empty
  const catCount = db.prepare('SELECT COUNT(*) as c FROM categories').get();
  if (catCount.c === 0) {
    const insertCat = db.prepare(`INSERT INTO categories (id, name, slug, icon, sort_order) VALUES (?, ?, ?, ?, ?)`);
    const cats = [
      ['cat-all', 'All', 'all', '🛍️', 0],
      ['cat-electronics', 'Electronics', 'electronics', '💻', 1],
      ['cat-phones', 'Phones', 'phones', '📱', 2],
      ['cat-beauty', 'Beauty', 'beauty', '💄', 3],
      ['cat-fashion', 'Fashion', 'fashion', '👗', 4],
      ['cat-home', 'Home', 'home', '🏠', 5],
      ['cat-audio', 'Audio', 'audio', '🎧', 6],
      ['cat-gaming', 'Gaming', 'gaming', '🎮', 7],
      ['cat-food', 'Food', 'food', '🍎', 8],
      ['cat-sports', 'Sports', 'sports', '⚽', 9],
      ['cat-furniture', 'Furniture', 'furniture', '🛋️', 10],
      ['cat-vehicles', 'Vehicles', 'vehicles', '🚗', 11],
    ];
    cats.forEach(c => insertCat.run(...c));
  }

  // Seed admin user if empty
  const userCount = db.prepare('SELECT COUNT(*) as c FROM users').get();
  if (userCount.c === 0) {
    const bcrypt = require('bcryptjs');
    const { v4: uuidv4 } = require('uuid');
    const hashedPwd = bcrypt.hashSync('admin123', 10);
    db.prepare(`INSERT INTO users (id, name, phone, email, password, role) VALUES (?, ?, ?, ?, ?, ?)`)
      .run(uuidv4(), 'Admin Beibe', '0700000000', 'admin@beibe.com', hashedPwd, 'admin');
    console.log('✅ Admin user created: admin@beibe.com / admin123');
  }

  console.log('✅ Database initialized');
}

module.exports = { db, initDatabase };
