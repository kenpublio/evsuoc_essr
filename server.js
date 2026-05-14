const express = require('express');
const bodyParser = require('body-parser');
const cors = require('cors');
const path = require('path');
require('dotenv').config();

// Database connection
const mysql = require('mysql2');
const db = mysql.createConnection({
    host: process.env.DB_HOST,
    user: process.env.DB_USER,
    password: process.env.DB_PASSWORD,
    database: process.env.DB_NAME,
    port: process.env.DB_PORT || 3306
});

// Connect to database
db.connect((err) => {
    if (err) {
        console.error('Database connection failed:', err.stack);
        return;
    }
    console.log('Connected to MySQL database');
});

// Initialize Express
const app = express();
const PORT = process.env.PORT || 3000;

// Middleware
app.use(cors());
app.use(bodyParser.json());
app.use(bodyParser.urlencoded({ extended: true }));
app.use(express.static(path.join(__dirname, 'public')));

// Initialize MojoAuth SDK
const config = {
    apiKey: process.env.MOJOAUTH_API_KEY,
};
const ma = require('mojoauth-sdk')(config);

// ============================================
// MOJOAUTH ENDPOINTS
// ============================================

// 1. Initiate MojoAuth Passwordless Login
app.post('/api/mojoauth/send-magic-link', async (req, res) => {
    try {
        const { email, state } = req.body;
        
        // Check if email exists in database
        const [users] = await db.promise().query(
            'SELECT id, username, fullname FROM users WHERE email = ?',
            [email]
        );
        
        if (users.length === 0) {
            return res.status(404).json({
                success: false,
                message: 'No account found with this email address'
            });
        }
        
        const user = users[0];
        
        // Send magic link via MojoAuth
        const mojoauthResponse = await ma.mojoAPI.sendMagicLink({
            email: email,
            language: 'en',
            redirect_url: `${process.env.WEBSITE_URL}/mojoauth-callback.html`,
            state: state
        });
        
        if (mojoauthResponse.state_id) {
            // Store state_id in session/database
            await db.promise().query(
                'INSERT INTO mojoauth_sessions (state_id, user_id, email, created_at) VALUES (?, ?, ?, NOW())',
                [mojoauthResponse.state_id, user.id, email]
            );
            
            res.json({
                success: true,
                message: 'Magic link sent successfully',
                data: {
                    email: email,
                    fullname: user.fullname
                }
            });
        } else {
            res.status(500).json({
                success: false,
                message: 'Failed to send magic link'
            });
        }
        
    } catch (error) {
        console.error('MojoAuth error:', error);
        res.status(500).json({
            success: false,
            message: 'Server error',
            error: error.message
        });
    }
});

// 2. Verify JWT Token (Callback endpoint)
app.post('/api/mojoauth/verify-token', async (req, res) => {
    try {
        const { token } = req.body;
        
        if (!token) {
            return res.status(400).json({
                success: false,
                message: 'Token is required'
            });
        }
        
        // Verify token with MojoAuth
        const verification = await ma.mojoAPI.verifyToken(token);
        
        if (verification.authenticated) {
            const { sub: mojoauthId, email, identifier } = verification;
            
            // Find user by email or create new user
            const [users] = await db.promise().query(
                'SELECT * FROM users WHERE email = ? OR mojoauth_id = ?',
                [email, mojoauthId]
            );
            
            let user;
            
            if (users.length === 0) {
                // Create new user (if signup flow)
                const [result] = await db.promise().query(
                    'INSERT INTO users (email, mojoauth_id, created_at) VALUES (?, ?, NOW())',
                    [email, mojoauthId]
                );
                
                user = {
                    id: result.insertId,
                    email: email,
                    mojoauth_id: mojoauthId
                };
            } else {
                user = users[0];
                
                // Update mojoauth_id if not set
                if (!user.mojoauth_id) {
                    await db.promise().query(
                        'UPDATE users SET mojoauth_id = ? WHERE id = ?',
                        [mojoauthId, user.id]
                    );
                }
            }
            
            // Create session
            const sessionToken = require('crypto').randomBytes(32).toString('hex');
            await db.promise().query(
                'INSERT INTO user_sessions (user_id, session_token, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 7 DAY))',
                [user.id, sessionToken]
            );
            
            res.json({
                success: true,
                authenticated: true,
                user: {
                    id: user.id,
                    email: user.email,
                    username: user.username,
                    fullname: user.fullname,
                    role: user.role || 'user'
                },
                session: {
                    token: sessionToken,
                    expires_in: '7 days'
                }
            });
            
        } else {
            res.status(401).json({
                success: false,
                authenticated: false,
                message: 'Authentication failed'
            });
        }
        
    } catch (error) {
        console.error('Token verification error:', error);
        res.status(500).json({
            success: false,
            message: 'Token verification failed',
            error: error.message
        });
    }
});

// 3. Get User Profile
app.get('/api/user/profile', async (req, res) => {
    try {
        const token = req.headers.authorization?.replace('Bearer ', '');
        
        if (!token) {
            return res.status(401).json({
                success: false,
                message: 'No token provided'
            });
        }
        
        // Verify session token
        const [sessions] = await db.promise().query(
            'SELECT u.* FROM user_sessions s JOIN users u ON s.user_id = u.id WHERE s.session_token = ? AND s.expires_at > NOW()',
            [token]
        );
        
        if (sessions.length === 0) {
            return res.status(401).json({
                success: false,
                message: 'Invalid or expired session'
            });
        }
        
        const user = sessions[0];
        
        // Remove sensitive data
        delete user.password;
        delete user.session_token;
        
        res.json({
            success: true,
            user: user
        });
        
    } catch (error) {
        console.error('Profile error:', error);
        res.status(500).json({
            success: false,
            message: 'Server error'
        });
    }
});

// 4. Logout
app.post('/api/user/logout', async (req, res) => {
    try {
        const token = req.headers.authorization?.replace('Bearer ', '');
        
        if (token) {
            await db.promise().query(
                'DELETE FROM user_sessions WHERE session_token = ?',
                [token]
            );
        }
        
        res.json({
            success: true,
            message: 'Logged out successfully'
        });
        
    } catch (error) {
        console.error('Logout error:', error);
        res.status(500).json({
            success: false,
            message: 'Server error'
        });
    }
});

// ============================================
// TRADITIONAL AUTH ENDPOINTS
// ============================================

// 1. Traditional Login
app.post('/api/auth/login', async (req, res) => {
    try {
        const { username, password } = req.body;
        
        const [users] = await db.promise().query(
            'SELECT * FROM users WHERE (username = ? OR email = ?)',
            [username, username]
        );
        
        if (users.length === 0) {
            return res.status(401).json({
                success: false,
                message: 'Invalid credentials'
            });
        }
        
        const user = users[0];
        
        // Verify password (assuming passwords are hashed)
        // You should use bcrypt in production
        if (user.password !== password) { // Replace with bcrypt.compare
            return res.status(401).json({
                success: false,
                message: 'Invalid credentials'
            });
        }
        
        // Create session
        const sessionToken = require('crypto').randomBytes(32).toString('hex');
        await db.promise().query(
            'INSERT INTO user_sessions (user_id, session_token, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 7 DAY))',
            [user.id, sessionToken]
        );
        
        res.json({
            success: true,
            user: {
                id: user.id,
                username: user.username,
                email: user.email,
                fullname: user.fullname,
                role: user.role
            },
            token: sessionToken
        });
        
    } catch (error) {
        console.error('Login error:', error);
        res.status(500).json({
            success: false,
            message: 'Server error'
        });
    }
});

// 2. Traditional Forgot Password
app.post('/api/auth/forgot-password', async (req, res) => {
    try {
        const { email } = req.body;
        
        const [users] = await db.promise().query(
            'SELECT id, email, username, fullname FROM users WHERE email = ?',
            [email]
        );
        
        if (users.length === 0) {
            return res.status(404).json({
                success: false,
                message: 'No account found with this email'
            });
        }
        
        const user = users[0];
        const resetToken = require('crypto').randomBytes(32).toString('hex');
        const expiresAt = new Date(Date.now() + 3600000); // 1 hour
        
        // Store reset token
        await db.promise().query(
            'INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, ?)',
            [user.id, resetToken, expiresAt]
        );
        
        // Generate reset link
        const resetLink = `${process.env.WEBSITE_URL}/reset-password.html?token=${resetToken}`;
        
        res.json({
            success: true,
            message: 'Reset link sent to email',
            data: {
                reset_link: resetLink,
                email: user.email
            }
        });
        
    } catch (error) {
        console.error('Forgot password error:', error);
        res.status(500).json({
            success: false,
            message: 'Server error'
        });
    }
});

// ============================================
// DATABASE SETUP SQL
// ============================================

const setupDatabase = async () => {
    const queries = [
        `CREATE TABLE IF NOT EXISTS mojoauth_sessions (
            id INT PRIMARY KEY AUTO_INCREMENT,
            state_id VARCHAR(255) NOT NULL UNIQUE,
            user_id INT,
            email VARCHAR(255) NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_state_id (state_id),
            INDEX idx_user_id (user_id)
        )`,
        
        `CREATE TABLE IF NOT EXISTS user_sessions (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            session_token VARCHAR(255) NOT NULL UNIQUE,
            expires_at DATETIME NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_session_token (session_token),
            INDEX idx_user_id (user_id),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )`,
        
        `ALTER TABLE users ADD COLUMN IF NOT EXISTS mojoauth_id VARCHAR(255) NULL`
    ];
    
    try {
        for (const query of queries) {
            await db.promise().query(query);
        }
        console.log('Database tables created/updated successfully');
    } catch (error) {
        console.error('Database setup error:', error);
    }
};

// Start server
app.listen(PORT, () => {
    console.log(`Server running at http://localhost:${PORT}`);
    setupDatabase();
});