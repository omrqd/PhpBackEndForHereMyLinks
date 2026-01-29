CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    email_notifications BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS pages (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE UNIQUE,
    slug VARCHAR(50) NOT NULL UNIQUE,
    bio TEXT,
    profile_image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS links (
    id SERIAL PRIMARY KEY,
    page_id INTEGER REFERENCES pages(id) ON DELETE CASCADE,
    title VARCHAR(255) NOT NULL,
    url TEXT NOT NULL,
    icon VARCHAR(50),      -- e.g., 'instagram', 'twitter', 'web'
    type VARCHAR(20) DEFAULT 'link', -- 'link', 'header', etc.
    sort_order INTEGER DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
