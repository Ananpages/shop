const jwt = require('jsonwebtoken');
const { db } = require('../database');

const JWT_SECRET = process.env.JWT_SECRET || 'beibe_market_secret_2024_uganda';

const authenticate = (req, res, next) => {
  const authHeader = req.headers.authorization;
  if (!authHeader || !authHeader.startsWith('Bearer ')) {
    return res.status(401).json({ success: false, message: 'Access token required' });
  }
  const token = authHeader.split(' ')[1];
  try {
    const decoded = jwt.verify(token, JWT_SECRET);
    const user = db.prepare('SELECT id, name, phone, email, role, avatar, is_active FROM users WHERE id = ?').get(decoded.userId);
    if (!user) return res.status(401).json({ success: false, message: 'User not found' });
    if (!user.is_active) return res.status(403).json({ success: false, message: 'Account suspended' });
    req.user = user;
    next();
  } catch (err) {
    return res.status(401).json({ success: false, message: 'Invalid or expired token' });
  }
};

const requireSeller = (req, res, next) => {
  if (!['seller', 'admin'].includes(req.user.role)) {
    return res.status(403).json({ success: false, message: 'Seller access required' });
  }
  next();
};

const requireAdmin = (req, res, next) => {
  if (req.user.role !== 'admin') {
    return res.status(403).json({ success: false, message: 'Admin access required' });
  }
  next();
};

const optionalAuth = (req, res, next) => {
  const authHeader = req.headers.authorization;
  if (!authHeader || !authHeader.startsWith('Bearer ')) return next();
  const token = authHeader.split(' ')[1];
  try {
    const decoded = jwt.verify(token, JWT_SECRET);
    const user = db.prepare('SELECT id, name, phone, email, role, avatar, is_active FROM users WHERE id = ?').get(decoded.userId);
    if (user && user.is_active) req.user = user;
  } catch {}
  next();
};

module.exports = { authenticate, requireSeller, requireAdmin, optionalAuth, JWT_SECRET };
