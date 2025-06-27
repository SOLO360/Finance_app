-- Create categories table
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    icon VARCHAR(50) NOT NULL,
    type ENUM('income', 'expense') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create transactions table
CREATE TABLE IF NOT EXISTS transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    description TEXT,
    type ENUM('income', 'expense') NOT NULL,
    date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (category_id) REFERENCES categories(id)
);

-- Insert default categories
INSERT INTO categories (name, icon, type) VALUES
-- Income categories
('Salary', '💰', 'income'),
('Freelance', '💼', 'income'),
('Investments', '📈', 'income'),
('Gifts', '🎁', 'income'),
('Other Income', '📥', 'income'),

-- Expense categories
('Food & Dining', '🍽️', 'expense'),
('Transportation', '🚗', 'expense'),
('Housing', '🏠', 'expense'),
('Utilities', '💡', 'expense'),
('Entertainment', '🎬', 'expense'),
('Shopping', '🛍️', 'expense'),
('Healthcare', '⚕️', 'expense'),
('Education', '📚', 'expense'),
('Personal Care', '💇', 'expense'),
('Other Expenses', '📤', 'expense'); 