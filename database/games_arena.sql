-- =========================================
-- GAMES ARENA - FULL DATABASE SCHEMA & EXPANDED SEED (35+ GAMES)
-- =========================================

CREATE DATABASE IF NOT EXISTS `games_arena`
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE `games_arena`;

-- =========================================
-- 1. USERS TABLE
-- =========================================
CREATE TABLE IF NOT EXISTS `users` (
    `user_id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `phone` VARCHAR(20) DEFAULT NULL,
    `profile_image` VARCHAR(500) DEFAULT NULL,
    `role` ENUM('customer', 'admin') NOT NULL DEFAULT 'customer',
    `language` VARCHAR(20) DEFAULT 'English',
    `theme` ENUM('light', 'dark') DEFAULT 'light',
    `status` ENUM('active', 'blocked') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================
-- 2. CATEGORIES TABLE
-- =========================================
CREATE TABLE IF NOT EXISTS `categories` (
    `category_id` INT AUTO_INCREMENT PRIMARY KEY,
    `category_name` VARCHAR(100) NOT NULL UNIQUE,
    `icon` VARCHAR(50) DEFAULT 'fa-gamepad',
    `description` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================
-- 3. GAMES TABLE
-- =========================================
CREATE TABLE IF NOT EXISTS `games` (
    `game_id` INT AUTO_INCREMENT PRIMARY KEY,
    `category_id` INT NOT NULL,
    `game_name` VARCHAR(200) NOT NULL,
    `genre` VARCHAR(150),
    `description` TEXT,
    `developer` VARCHAR(150),
    `publisher` VARCHAR(150),
    `release_date` DATE,
    `price` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `game_image` VARCHAR(500),
    `trailer_url` VARCHAR(500),
    `rating` DECIMAL(2,1) DEFAULT 0.0,
    `total_reviews` INT DEFAULT 0,
    `is_featured` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`category_id`) REFERENCES `categories`(`category_id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================
-- 4. GAME REQUIREMENTS TABLE
-- =========================================
CREATE TABLE IF NOT EXISTS `game_requirements` (
    `requirement_id` INT AUTO_INCREMENT PRIMARY KEY,
    `game_id` INT NOT NULL,
    `requirement_type` ENUM('minimum', 'recommended') NOT NULL,
    `operating_system` VARCHAR(255),
    `processor` VARCHAR(255),
    `memory` VARCHAR(100),
    `graphics` VARCHAR(255),
    `storage` VARCHAR(100),
    `directx` VARCHAR(100),
    FOREIGN KEY (`game_id`) REFERENCES `games`(`game_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    UNIQUE KEY `unique_game_req` (`game_id`, `requirement_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================
-- 5. REVIEWS TABLE
-- =========================================
CREATE TABLE IF NOT EXISTS `reviews` (
    `review_id` INT AUTO_INCREMENT PRIMARY KEY,
    `game_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `rating` TINYINT NOT NULL,
    `review_text` TEXT,
    `status` ENUM('visible', 'hidden') DEFAULT 'visible',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`game_id`) REFERENCES `games`(`game_id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
    CONSTRAINT `rating_range` CHECK (`rating` BETWEEN 1 AND 5),
    UNIQUE KEY `unique_user_game_review` (`game_id`, `user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================
-- 6. FAVORITES TABLE
-- =========================================
CREATE TABLE IF NOT EXISTS `favorites` (
    `favorite_id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `game_id` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE,
    FOREIGN KEY (`game_id`) REFERENCES `games`(`game_id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_user_game_fav` (`user_id`, `game_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================
-- 7. CARTS TABLE
-- =========================================
CREATE TABLE IF NOT EXISTS `carts` (
    `cart_id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL UNIQUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================
-- 8. CART ITEMS TABLE
-- =========================================
CREATE TABLE IF NOT EXISTS `cart_items` (
    `cart_item_id` INT AUTO_INCREMENT PRIMARY KEY,
    `cart_id` INT NOT NULL,
    `game_id` INT NOT NULL,
    `quantity` INT NOT NULL DEFAULT 1,
    `added_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`cart_id`) REFERENCES `carts`(`cart_id`) ON DELETE CASCADE,
    FOREIGN KEY (`game_id`) REFERENCES `games`(`game_id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_cart_game` (`cart_id`, `game_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================
-- 9. ORDERS TABLE
-- =========================================
CREATE TABLE IF NOT EXISTS `orders` (
    `order_id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `order_number` VARCHAR(50) NOT NULL UNIQUE,
    `total_amount` DECIMAL(10,2) NOT NULL,
    `payment_method` VARCHAR(50) DEFAULT 'Card / UPI',
    `payment_status` ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'paid',
    `order_status` ENUM('pending', 'processing', 'completed', 'cancelled') DEFAULT 'completed',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================
-- 10. ORDER ITEMS TABLE
-- =========================================
CREATE TABLE IF NOT EXISTS `order_items` (
    `order_item_id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_id` INT NOT NULL,
    `game_id` INT NOT NULL,
    `quantity` INT NOT NULL DEFAULT 1,
    `price` DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (`order_id`) REFERENCES `orders`(`order_id`) ON DELETE CASCADE,
    FOREIGN KEY (`game_id`) REFERENCES `games`(`game_id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================
-- 11. GAME OF THE YEAR TABLE
-- =========================================
CREATE TABLE IF NOT EXISTS `game_of_the_year` (
    `goty_id` INT AUTO_INCREMENT PRIMARY KEY,
    `year` INT NOT NULL UNIQUE,
    `game_id` INT NOT NULL,
    `description` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`game_id`) REFERENCES `games`(`game_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =========================================
-- SEED DATA: CATEGORIES (10 Categories)
-- =========================================
INSERT INTO `categories` (`category_id`, `category_name`, `icon`, `description`) VALUES
(1, 'Action', 'fa-fire', 'High-octane action, thrilling melee combat, superhero encounters, and adrenaline-pumping gameplay.'),
(2, 'Adventure', 'fa-compass', 'Story-rich exploration, cinematic journeys, uncovering ancient lore, and unforgettable quest adventures.'),
(3, 'RPG', 'fa-dragon', 'Deep role-playing games featuring extensive character customization, choices, stats, and massive worlds.'),
(4, 'Racing', 'fa-car-side', 'High-speed motorsport, authentic vehicle physics, open-world street racing, and track simulations.'),
(5, 'Sports', 'fa-futbol', 'Realistic competitive athletics, football, basketball, wrestling, and official league championships.'),
(6, 'Shooter', 'fa-crosshairs', 'First-person and tactical third-person shooters with intense weapon mechanics and multiplayer squad play.'),
(7, 'Horror', 'fa-ghost', 'Atmospheric psychological thrillers, eerie survival horror, dark mysteries, and monstrous encounters.'),
(8, 'Strategy', 'fa-chess', 'Grand turn-based strategy, real-time empire building, tactical battle commands, and world domination.'),
(9, 'Simulation', 'fa-plane', 'Life, vehicle, flight, and city construction simulation experiences offering meticulous realism.'),
(10, 'Open World', 'fa-earth-americas', 'Vast, sprawling living worlds offering limitless exploration, radiant sandbox mechanics, and limitless freedom.')
ON DUPLICATE KEY UPDATE `category_name`=VALUES(`category_name`), `icon`=VALUES(`icon`), `description`=VALUES(`description`);

-- =========================================
-- SEED DATA: USERS (Admin & Customer)
-- Passwords: Admin@123 & Customer@123
-- =========================================
INSERT INTO `users` (`user_id`, `name`, `email`, `password`, `phone`, `role`, `language`, `theme`, `status`) VALUES
(1, 'Games Arena Admin', 'admin@gamesarena.com', '$2y$10$vvJVZ.qLxuBn4D.lxSd7ae1lCPf2ueaDho66xEueR5u6ks9HrIG/q', '+91 9876543210', 'admin', 'English', 'light', 'active'),
(2, 'Siva Customer', 'customer@gamesarena.com', '$2y$10$bQvmlcklsazZ4UW1JOCBQOrdY//RwR65jWe6gd7po9wHLdRflqu..', '+91 9123456780', 'customer', 'English', 'light', 'active')
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`);

-- =========================================
-- SEED DATA: 35 BLOCKBUSTER GAMES
-- =========================================
INSERT INTO `games` (`game_id`, `category_id`, `game_name`, `genre`, `description`, `developer`, `publisher`, `release_date`, `price`, `game_image`, `trailer_url`, `rating`, `total_reviews`, `is_featured`) VALUES
(1, 10, 'Grand Theft Auto V', 'Action, Open World', 'When a young street hustler, a retired bank robber and a terrifying psychopath find themselves entangled with the criminal underworld, they pull off daring heists to survive in a city where they can trust nobody.', 'Rockstar North', 'Rockstar Games', '2013-09-17', 1499.00, 'https://images.unsplash.com/photo-1542751371-adc38448a05e?w=800&auto=format&fit=crop&q=80', 'https://www.youtube.com/embed/QkkoHAzjnUs', 4.9, 45, 1),
(2, 3, 'The Witcher 3: Wild Hunt', 'Action RPG, Fantasy', 'You are Geralt of Rivia, mercenary monster slayer. Before you stands a war-torn, monster-infested continent. Track down Ciri — the Child of Prophecy, a living weapon that can alter the shape of the world.', 'CD Projekt Red', 'CD Projekt', '2015-05-19', 999.00, 'https://images.unsplash.com/photo-1518709268805-4e9042af9f23?w=800&auto=format&fit=crop&q=80', 'https://www.youtube.com/embed/c0i88t0Kacs', 4.9, 38, 1),
(3, 3, 'Cyberpunk 2077', 'Action RPG, Sci-Fi', 'Cyberpunk 2077 is an open-world action-adventure set in Night City, a megalopolis obsessed with power, glamour and body modification. Seek out a prototype implant that holds the key to immortality.', 'CD Projekt Red', 'CD Projekt', '2020-12-10', 2999.00, 'https://images.unsplash.com/photo-1550745165-9bc0b252726f?w=800&auto=format&fit=crop&q=80', 'https://www.youtube.com/embed/8X2kIfS6fb8', 4.7, 29, 1),
(4, 4, 'Forza Horizon 5', 'Racing, Open World', 'Your Ultimate Horizon Adventure awaits! Explore the vibrant and ever-evolving open world landscapes of Mexico with limitless, fun driving action in hundreds of the world’s greatest cars.', 'Playground Games', 'Xbox Game Studios', '2021-11-05', 2499.00, 'https://images.unsplash.com/photo-1511919884226-fd3cad34687c?w=800&auto=format&fit=crop&q=80', 'https://www.youtube.com/embed/FYH9n37B7Yw', 4.8, 24, 0),
(5, 6, 'Call of Duty: Modern Warfare II', 'First Person Shooter', 'Call of Duty: Modern Warfare II drops players into an unprecedented global conflict that features the return of the iconic Operators of Task Force 141.', 'Infinity Ward', 'Activision', '2022-10-28', 3499.00, 'https://images.unsplash.com/photo-1538481199705-c710c4e965fc?w=800&auto=format&fit=crop&q=80', 'https://www.youtube.com/embed/r72GP1PIZa0', 4.5, 21, 0),
(6, 7, 'Resident Evil 4', 'Survival Horror', 'Survival is only the beginning. Six years have passed since the biological disaster in Raccoon City. Agent Leon S. Kennedy has been sent to rescue the president’s kidnapped daughter from a shadowy cult.', 'Capcom', 'Capcom', '2023-03-24', 2999.00, 'https://images.unsplash.com/photo-1509198397868-475647b2a1e5?w=800&auto=format&fit=crop&q=80', 'https://www.youtube.com/embed/O_A8TkT3I3c', 4.9, 32, 1),
(7, 1, 'Red Dead Redemption 2', 'Action Adventure, Open World', 'America, 1899. Arthur Morgan and the Van der Linde gang are outlaws on the run. Rob, steal and fight across the rugged heartland of America to survive against federal agents and bounty hunters.', 'Rockstar Studios', 'Rockstar Games', '2018-10-26', 1999.00, 'https://images.unsplash.com/photo-1534447677768-be436bb09401?w=800&auto=format&fit=crop&q=80', 'https://www.youtube.com/embed/eaW0tYpxyp0', 4.9, 41, 1),
(8, 8, 'Civilization VI', 'Turn-Based Strategy', 'Civilization VI offers new ways to engage with your world: cities physically expand across the map, active research unlocks potential, and leaders pursue distinct historical traits.', 'Firaxis Games', '2K', '2016-10-21', 799.00, 'https://images.unsplash.com/photo-1610890716171-6b1bb98ffd09?w=800&auto=format&fit=crop&q=80', 'https://www.youtube.com/embed/5KdE0p2joJw', 4.6, 16, 0),
(9, 2, 'God of War', 'Action Adventure, Mythology', 'His vengeance against Olympus behind him, Kratos lives as a man in the realm of Norse Gods and monsters. He must fight to survive and teach his son Atreus to do the same.', 'Santa Monica Studio', 'Sony Interactive Entertainment', '2018-04-20', 2499.00, 'https://images.unsplash.com/photo-1563089145-599997674d42?w=800&auto=format&fit=crop&q=80', 'https://www.youtube.com/embed/K0u_kAWLJOA', 4.9, 35, 1),
(10, 3, 'Elden Ring', 'Action RPG, Dark Fantasy', 'Rise, Tarnished, and be guided by grace to brandish the power of the Elden Ring and become an Elden Lord in the Lands Between. A vast world full of excitement and open-ended dungeons awaits.', 'FromSoftware Inc.', 'Bandai Namco Entertainment', '2022-02-25', 2999.00, 'https://images.unsplash.com/photo-1579783902614-a3fb3927b675?w=800&auto=format&fit=crop&q=80', 'https://www.youtube.com/embed/E3Huy2cdih0', 4.9, 48, 1),
(11, 3, 'Baldur’s Gate 3', 'Story-Rich RPG, Turn-Based', 'Gather your party and return to the Forgotten Realms in a tale of fellowship and betrayal, sacrifice and survival, and the lure of absolute power. Winner of Game of the Year 2023.', 'Larian Studios', 'Larian Studios', '2023-08-03', 2999.00, 'https://images.unsplash.com/photo-1511512578047-dfb367046420?w=800&auto=format&fit=crop&q=80', 'https://www.youtube.com/embed/1T22wNlUinA', 5.0, 52, 1),
(12, 1, 'Ghost of Tsushima: Director’s Cut', 'Action, Open World, Samurai', 'In the late 13th century, the Mongol empire has laid waste to entire nations. Jin Sakai must set aside samurai traditions to forge a new path, the path of the Ghost, to free Tsushima.', 'Sucker Punch Productions', 'PlayStation PC LLC', '2024-05-16', 3999.00, 'https://images.unsplash.com/photo-1578632767115-351597cf2477?w=800&auto=format&fit=crop&q=80', 'https://www.youtube.com/embed/bqwJ08jYJ-I', 4.9, 27, 1),
(13, 1, 'Marvel’s Spider-Man Remastered', 'Action, Superhero', 'Play as an experienced Peter Parker fighting iconic Marvel villains in an expansive, vibrant open-world New York. Feel the power of web-slinging with acrobatic abilities and fluid traversal.', 'Insomniac Games', 'PlayStation PC LLC', '2022-08-12', 2999.00, 'https://images.unsplash.com/photo-1604200213928-ba3cf4fc8436?w=800&auto=format&fit=crop&q=80', 'https://www.youtube.com/embed/14L_2p5t9dM', 4.8, 31, 0),
(14, 2, 'Hogwarts Legacy', 'Open World, Magic RPG', 'Experience Hogwarts in the 1800s. Your character is a student who holds the key to an ancient secret that threatens to tear the wizarding world apart. Make allies and master spells.', 'Avalanche Software', 'Warner Bros. Games', '2023-02-10', 2999.00, 'https://images.unsplash.com/photo-1547700055-b61cacebece9?w=800&auto=format&fit=crop&q=80', 'https://www.youtube.com/embed/BtyBjOW8sGY', 4.7, 26, 0),
(15, 2, 'Uncharted: Legacy of Thieves Collection', 'Action Adventure, Cinematic', 'Seek your fortune and leave your mark on the map in UNCHARTED: Legacy of Thieves Collection. Uncover the thrilling cinematic storytelling across globetrotting adventures with Nathan Drake.', 'Naughty Dog LLC', 'PlayStation PC LLC', '2022-10-19', 2499.00, 'https://images.unsplash.com/photo-1518709268805-4e9042af9f23?w=800&auto=format&fit=crop&q=80', 'https://www.youtube.com/embed/hK2U3A_h7sY', 4.8, 19, 0),
(16, 5, 'EA SPORTS FC 24', 'Sports, Football Simulation', 'EA SPORTS FC 24 welcomes you to The World’s Game: the most true-to-football experience ever with HyperMotionV, PlayStyles optimised by Opta, and a revolutionized Frostbite Engine.', 'EA Canada', 'Electronic Arts', '2023-09-29', 3499.00, 'https://images.unsplash.com/photo-1508098682722-e99c43a406b2?w=800&auto=format&fit=crop&q=80', 'https://www.youtube.com/embed/XhP3Xh4LMA8', 4.3, 18, 0),
(17, 5, 'NBA 2K24', 'Sports, Basketball Simulation', 'Experience hoops culture in NBA 2K24. Enjoy loads of pure, authentic action and personalized MyPLAYER options in MyCAREER. Recreate Kobe Bryant’s most dominant performances.', 'Visual Concepts', '2K', '2023-09-08', 2999.00, 'https://images.unsplash.com/photo-1546519638-68e109498ffc?w=800&auto=format&fit=crop&q=80', 'https://www.youtube.com/embed/gU9H4eM5ZgA', 4.2, 14, 0),
(18, 5, 'WWE 2K24', 'Sports, Wrestling Combat', '40 years of WrestleMania! Experience a gripping retelling of WrestleMania’s greatest moments in 2K Showcase of the Immortals, featuring legends like The Undertaker, Stone Cold and Roman Reigns.', 'Visual Concepts', '2K', '2024-03-08', 3499.00, 'https://images.unsplash.com/photo-1517649763962-0c623266ddc0?w=800&auto=format&fit=crop&q=80', 'https://www.youtube.com/embed/j1y6w3x3x4g', 4.6, 15, 0),
(19, 4, 'Need for Speed Unbound', 'Street Racing, Customization', 'Race to the top, don’t flop. Outsmart the cops and enter weekly qualifiers for The Grand, Lakeshore’s ultimate street racing challenge. Pack your garage with precision-tuned custom rides.', 'Criterion Games', 'Electronic Arts', '2022-12-02', 2999.00, 'https://images.unsplash.com/photo-1503376780353-7e6692767b70?w=800&auto=format&fit=crop&q=80', 'https://www.youtube.com/embed/H2Y8XCe7F9E', 4.4, 17, 0),
(20, 4, 'F1 24', 'Racing, Formula 1 Simulator', 'Be one of the 20 with EA SPORTS F1 24, the official video game of the 2024 FIA Formula One World Championship. Unleash your champion in pursuit of a legacy-defining F1 career.', 'Codemasters', 'EA Sports', '2024-05-31', 3999.00, 'https://images.unsplash.com/photo-1568605117036-5fe5e7bab0b7?w=800&auto=format&fit=crop&q=80', 'https://www.youtube.com/embed/K8vB7q3X6Zw', 4.6, 12, 0),
(21, 4, 'Assetto Corsa Competizione', 'Racing, GT3 Simulation', 'Assetto Corsa Competizione is the official GT World Challenge videogame. Experience real atmosphere of the GT3 championship, competing against official drivers, teams and cars.', 'Kunos Simulazioni', '505 Games', '2019-05-29', 1299.00, 'https://images.unsplash.com/photo-1552519507-da3b142c6e3d?w=800&auto=format&fit=crop&q=80', 'https://www.youtube.com/embed/tM0Q8W9F6yI', 4.8, 20, 0),
(22, 6, 'DOOM Eternal', 'First Person Shooter, Fast Combat', 'Hell’s armies have invaded Earth. Become the Slayer in an epic single-player campaign to conquer demons across dimensions and stop the final destruction of humanity. Raze Hell!', 'id Software', 'Bethesda Softworks', '2020-03-20', 1799.00, 'https://images.unsplash.com/photo-1542751371-adc38448a05e?w=800&auto=format&fit=crop&q=80', 'https://www.youtube.com/embed/FkklG9MA0vM', 4.9, 36, 1),
(23, 6, 'Apex Legends: Champions Pack', 'Battle Royale, Hero Shooter', 'Master a growing roster of diverse Legends, deep tactical squad play and bold new innovations in battle royale. Includes instant Legend unlocks and exclusive legendary cosmetics.', 'Respawn Entertainment', 'Electronic Arts', '2020-11-04', 1999.00, 'https://images.unsplash.com/photo-1563089145-599997674d42?w=800&auto=format&fit=crop&q=80', 'https://www.youtube.com/embed/innmNewjkuk', 4.6, 23, 0),
(24, 6, 'Tom Clancy’s Rainbow Six Siege', 'Tactical FPS, Multiplayer', 'Master the art of destruction and gadgetry in Tom Clancy’s Rainbow Six Siege. Face intense close-quarters combat, high lethality, tactical decision making, and team play.', 'Ubisoft Montreal', 'Ubisoft', '2015-12-01', 999.00, 'https://images.unsplash.com/photo-1538481199705-c710c4e965fc?w=800&auto=format&fit=crop&q=80', 'https://www.youtube.com/embed/6wlvYh0h63k', 4.7, 39, 0),
(25, 7, 'Alan Wake 2', 'Psychological Horror, Narrative', 'A string of ritualistic murders threatens Bright Falls. Saga Anderson investigates while Alan Wake writes a dark story to shape the reality around him. Two heroes in two desperate realities.', 'Remedy Entertainment', 'Epic Games Publishing', '2023-10-27', 2999.00, 'https://images.unsplash.com/photo-1509198397868-475647b2a1e5?w=800&auto=format&fit=crop&q=80', 'https://www.youtube.com/embed/qL_q7V0rT0I', 4.9, 28, 1),
(26, 7, 'Dead Space', 'Sci-Fi Survival Horror', 'The sci-fi survival horror classic returns, completely rebuilt from the ground up to offer a deeper and more immersive experience. Isaac Clarke must survive the mining ship USG Ishimura.', 'Motive', 'Electronic Arts', '2023-01-27', 2999.00, 'https://images.unsplash.com/photo-1518709268805-4e9042af9f23?w=800&auto=format&fit=crop&q=80', 'https://www.youtube.com/embed/cTDJNZ9cK1w', 4.8, 22, 0),
(27, 7, 'The Last of Us Part I', 'Action, Post-Apocalyptic Horror', 'In a ravaged civilization, where infected and hardened survivors run rampant, Joel, a weary protagonist, is hired to smuggle 14-year-old Ellie out of a military quarantine zone.', 'Naughty Dog LLC', 'PlayStation PC LLC', '2023-03-28', 3999.00, 'https://images.unsplash.com/photo-1534447677768-be436bb09401?w=800&auto=format&fit=crop&q=80', 'https://www.youtube.com/embed/R2EboP9lS7w', 4.8, 33, 1),
(28, 8, 'Age of Empires IV: Anniversary Edition', 'Real-Time Strategy, History', 'One of the most beloved real-time strategy games returns to glory with Age of Empires IV, putting you at the center of epic historical battles that shaped the modern world.', 'Relic Entertainment', 'Xbox Game Studios', '2021-10-28', 1999.00, 'https://images.unsplash.com/photo-1610890716171-6b1bb98ffd09?w=800&auto=format&fit=crop&q=80', 'https://www.youtube.com/embed/2qUe_Zk43xY', 4.7, 19, 0),
(29, 8, 'Total War: WARHAMMER III', 'Grand Strategy, Fantasy', 'The cataclysmic conclusion to the Total War: WARHAMMER trilogy is here. Rally your forces and step into the Realm of Chaos, a dimension of mind-bending horror.', 'CREATIVE ASSEMBLY', 'SEGA', '2022-02-17', 2999.00, 'https://images.unsplash.com/photo-1579783902614-a3fb3927b675?w=800&auto=format&fit=crop&q=80', 'https://www.youtube.com/embed/nUeJ3rM5iW8', 4.6, 21, 0),
(30, 9, 'Microsoft Flight Simulator 2024', 'Flight Simulation, Aviation', 'Explore the world with our largest fleet of aircraft and experience aviation realism as you pursue your aviation career within Microsoft Flight Simulator 2024.', 'Asobo Studio', 'Xbox Game Studios', '2024-11-19', 4999.00, 'https://images.unsplash.com/photo-1559627796-037ca0229a43?w=800&auto=format&fit=crop&q=80', 'https://www.youtube.com/embed/p3wP5FqKx6Y', 4.9, 25, 1),
(31, 9, 'Cities: Skylines II', 'City Builder, Simulation', 'Raise a city from the ground up and transform it into a thriving metropolis. Experience deep simulation and a living economy in the most realistic city builder ever.', 'Colossal Order Ltd.', 'Paradox Interactive', '2023-10-24', 2499.00, 'https://images.unsplash.com/photo-1477959858617-67f30bc75b82?w=800&auto=format&fit=crop&q=80', 'https://www.youtube.com/embed/6i_bM2e_W9c', 4.3, 16, 0),
(32, 9, 'Euro Truck Simulator 2', 'Driving, Vehicle Simulation', 'Travel across Europe as king of the road, a trucker who delivers important cargo across impressive distances! Explore dozens of cities in UK, Belgium, Germany, Italy and more.', 'SCS Software', 'SCS Software', '2012-10-18', 899.00, 'https://images.unsplash.com/photo-1601584115197-04ecc0da31d7?w=800&auto=format&fit=crop&q=80', 'https://www.youtube.com/embed/xlTuC18xVII', 4.9, 44, 0),
(33, 10, 'Starfield', 'Space RPG, Open World', 'Starfield is the first new universe in 25 years from Bethesda Game Studios. In this next generation role-playing game set amongst the stars, create any character and explore with freedom.', 'Bethesda Game Studios', 'Bethesda Softworks', '2023-09-06', 3499.00, 'https://images.unsplash.com/photo-1451187580459-43490279c0fa?w=800&auto=format&fit=crop&q=80', 'https://www.youtube.com/embed/kfYEiTdsyas', 4.4, 27, 0),
(34, 10, 'Assassin’s Creed Valhalla', 'Open World, Viking Action', 'Become Eivor, a legendary Viking warrior on a quest for glory. Explore England’s Dark Ages as you raid enemies, grow your settlement, and build political power.', 'Ubisoft Montreal', 'Ubisoft', '2020-11-10', 2999.00, 'https://images.unsplash.com/photo-1578632767115-351597cf2477?w=800&auto=format&fit=crop&q=80', 'https://www.youtube.com/embed/ssrNcwxALS4', 4.6, 25, 0),
(35, 1, 'Black Myth: Wukong', 'Action RPG, Mythological', 'Black Myth: Wukong is an action RPG rooted in Chinese mythology. You shall set out as the Destined One to venture into the challenges and marvels ahead, to uncover the truth of a glorious legend.', 'Game Science', 'Game Science', '2024-08-20', 3599.00, 'https://images.unsplash.com/photo-1563089145-599997674d42?w=800&auto=format&fit=crop&q=80', 'https://www.youtube.com/embed/1kK_B8P9TfY', 4.9, 50, 1)
ON DUPLICATE KEY UPDATE 
    `category_id`=VALUES(`category_id`),
    `game_name`=VALUES(`game_name`),
    `genre`=VALUES(`genre`),
    `description`=VALUES(`description`),
    `developer`=VALUES(`developer`),
    `publisher`=VALUES(`publisher`),
    `release_date`=VALUES(`release_date`),
    `price`=VALUES(`price`),
    `game_image`=VALUES(`game_image`),
    `trailer_url`=VALUES(`trailer_url`),
    `rating`=VALUES(`rating`),
    `total_reviews`=VALUES(`total_reviews`),
    `is_featured`=VALUES(`is_featured`);

-- =========================================
-- SEED DATA: GAME SYSTEM REQUIREMENTS (ALL 35 GAMES)
-- =========================================
INSERT INTO `game_requirements` (`game_id`, `requirement_type`, `operating_system`, `processor`, `memory`, `graphics`, `storage`, `directx`) VALUES
(1, 'minimum', 'Windows 10 64-bit', 'Intel Core 2 Quad Q6600 @ 2.40GHz', '4 GB RAM', 'NVIDIA 9800 GT 1GB / AMD HD 4870 1GB', '110 GB available space', 'DirectX 10'),
(1, 'recommended', 'Windows 10 / 11 64-bit', 'Intel Core i5 3470 @ 3.2GHz / AMD X8 FX-8350', '8 GB RAM', 'NVIDIA GTX 660 2GB / AMD HD 7870 2GB', '110 GB available space', 'DirectX 11'),
(2, 'minimum', 'Windows 10 64-bit', 'Intel Core i5-2500K 3.3GHz / AMD Phenom II X4 940', '6 GB RAM', 'NVIDIA GeForce GTX 660 / AMD Radeon HD 7870', '50 GB available space', 'DirectX 11'),
(2, 'recommended', 'Windows 10 / 11 64-bit', 'Intel Core i7 3770 3.4 GHz / AMD FX-8350 4 GHz', '8 GB RAM', 'NVIDIA GeForce GTX 770 / AMD Radeon R9 290', '50 GB available space', 'DirectX 11'),
(3, 'minimum', 'Windows 10 64-bit', 'Core i7-6700 or AMD Ryzen 5 1600', '12 GB RAM', 'GeForce GTX 1060 6GB or Radeon RX 580 8GB', '70 GB SSD space', 'DirectX 12'),
(3, 'recommended', 'Windows 10 / 11 64-bit', 'Core i7-12700 or AMD Ryzen 7 7800X3D', '16 GB RAM', 'GeForce RTX 2060 SUPER or Radeon RX 5700 XT', '70 GB SSD space', 'DirectX 12'),
(4, 'minimum', 'Windows 10 64-bit', 'Intel i5-4460 or AMD Ryzen 3 1200', '8 GB RAM', 'NVidia GTX 970 OR AMD RX 470', '110 GB available space', 'DirectX 12'),
(4, 'recommended', 'Windows 10 / 11 64-bit', 'Intel i5-8400 or AMD Ryzen 5 1500X', '16 GB RAM', 'NVidia GTX 1070 OR AMD RX 590', '110 GB available space', 'DirectX 12'),
(5, 'minimum', 'Windows 10 64-bit', 'Intel Core i3-6100 / Core i5-2500K or AMD Ryzen 3 1200', '8 GB RAM', 'NVIDIA GeForce GTX 960 or AMD Radeon RX 470', '125 GB available space', 'DirectX 12'),
(5, 'recommended', 'Windows 10 / 11 64-bit', 'Intel Core i5-6600K / Core i7-4770 or AMD Ryzen 5 1400', '16 GB RAM', 'NVIDIA GeForce GTX 1060 or AMD Radeon RX 580', '125 GB available space', 'DirectX 12'),
(6, 'minimum', 'Windows 10 64-bit', 'AMD Ryzen 3 1200 / Intel Core i5-7500', '8 GB RAM', 'AMD Radeon RX 560 4GB / NVIDIA GeForce GTX 1050 Ti 4GB', '67 GB available space', 'DirectX 12'),
(6, 'recommended', 'Windows 10 / 11 64-bit', 'AMD Ryzen 5 3600 / Intel Core i7 8700', '16 GB RAM', 'AMD Radeon RX 5700 / NVIDIA GeForce GTX 1070', '67 GB available space', 'DirectX 12'),
(7, 'minimum', 'Windows 10 64-bit', 'Intel Core i5-2500K / AMD FX-6300', '8 GB RAM', 'Nvidia GeForce GTX 770 2GB / AMD Radeon R9 280 3GB', '150 GB available space', 'DirectX 12'),
(7, 'recommended', 'Windows 10 / 11 64-bit', 'Intel Core i7-4770K / AMD Ryzen 5 1500X', '12 GB RAM', 'Nvidia GeForce GTX 1060 6GB / AMD Radeon RX 480 4GB', '150 GB available space', 'DirectX 12'),
(8, 'minimum', 'Windows 10 64-bit', 'Intel Core i3 2.5 Ghz or AMD Phenom II 2.6 Ghz', '4 GB RAM', '1 GB Video Card (AMD 5570 or Nvidia 450)', '17 GB available space', 'DirectX 11'),
(8, 'recommended', 'Windows 10 / 11 64-bit', 'Intel Core i5 2.5 Ghz or AMD FX8350 4.0 Ghz', '8 GB RAM', '2 GB Video Card (AMD 7970 or Nvidia 770)', '17 GB available space', 'DirectX 11'),
(9, 'minimum', 'Windows 10 64-bit', 'Intel i5-2500k (3.3 GHz) or AMD Ryzen 3 1200', '8 GB RAM', 'NVIDIA GTX 960 (4 GB) or AMD R9 290X (4 GB)', '70 GB SSD space', 'DirectX 11'),
(9, 'recommended', 'Windows 10 / 11 64-bit', 'Intel i5-6600k (3.5 GHz) or AMD Ryzen 5 2400 G', '16 GB RAM', 'NVIDIA GTX 1060 (6 GB) or AMD RX 570 (4 GB)', '70 GB SSD space', 'DirectX 11'),
(10, 'minimum', 'Windows 10 64-bit', 'Intel Core i5-8400 or AMD Ryzen 3 3300X', '12 GB RAM', 'NVIDIA GeForce GTX 1060 3 GB / AMD Radeon RX 580 4 GB', '60 GB available space', 'DirectX 12'),
(10, 'recommended', 'Windows 10 / 11 64-bit', 'Intel Core i7-8700K or AMD Ryzen 5 3600X', '16 GB RAM', 'NVIDIA GeForce GTX 1070 8 GB / AMD Radeon RX VEGA 56 8 GB', '60 GB available space', 'DirectX 12'),
(11, 'minimum', 'Windows 10 64-bit', 'Intel i5-4690 / AMD FX 8350', '8 GB RAM', 'Nvidia GTX 970 / RX 480 (4GB+ VRAM)', '150 GB available SSD space', 'DirectX 11'),
(11, 'recommended', 'Windows 10 / 11 64-bit', 'Intel i7 8700K / AMD r5 3600', '16 GB RAM', 'Nvidia 2060 Super / RX 5700 XT (8GB+ VRAM)', '150 GB available SSD space', 'DirectX 11'),
(12, 'minimum', 'Windows 10 64-bit', 'Intel Core i3-7100 or AMD Ryzen 3 1200', '8 GB RAM', 'NVIDIA GeForce GTX 960 or AMD Radeon RX 5500 XT', '75 GB space', 'DirectX 12'),
(12, 'recommended', 'Windows 10 / 11 64-bit', 'Intel Core i5-8600 or AMD Ryzen 5 3600', '16 GB RAM', 'NVIDIA GeForce RTX 2060 or AMD Radeon RX 5600 XT', '75 GB SSD space', 'DirectX 12'),
(13, 'minimum', 'Windows 10 64-bit', 'Intel Core i3-4160, 3.6 GHz or AMD equivalent', '8 GB RAM', 'NVIDIA GTX 950 or AMD Radeon RX 470', '75 GB space', 'DirectX 12'),
(13, 'recommended', 'Windows 10 / 11 64-bit', 'Intel Core i5-4670, 3.4 Ghz or AMD Ryzen 5 1600', '16 GB RAM', 'NVIDIA GTX 1060 6GB or AMD Radeon RX 580', '75 GB SSD space', 'DirectX 12'),
(14, 'minimum', 'Windows 10 64-bit', 'Intel Core i5-6600 or AMD Ryzen 5 1400', '16 GB RAM', 'NVIDIA GeForce GTX 960 4GB or AMD Radeon RX 470 4GB', '85 GB space', 'DirectX 12'),
(14, 'recommended', 'Windows 10 / 11 64-bit', 'Intel Core i7-8700 or AMD Ryzen 5 3600', '16 GB RAM', 'NVIDIA GeForce 1080 Ti or AMD Radeon RX 5700 XT', '85 GB SSD space', 'DirectX 12'),
(15, 'minimum', 'Windows 10 64-bit', 'Intel i5-4330, AMD FX-6300', '8 GB RAM', 'NVIDIA GTX 960 (4 GB), AMD R9 290X (4 GB)', '126 GB space', 'DirectX 12'),
(15, 'recommended', 'Windows 10 / 11 64-bit', 'Intel i7-4770, AMD Ryzen 5 1500X', '16 GB RAM', 'NVIDIA GTX 1060 (6 GB), AMD RX 570 (4 GB)', '126 GB SSD space', 'DirectX 12'),
(16, 'minimum', 'Windows 10 64-bit', 'Intel Core i5-6600K @ 3.50GHz or AMD Ryzen 5 1600 @ 3.2 GHZ', '8 GB RAM', 'NVIDIA GeForce GTX 1050 Ti 4GB or AMD Radeon RX 570 4GB', '100 GB available space', 'DirectX 12'),
(16, 'recommended', 'Windows 10 / 11 64-bit', 'Intel Core i7-6700 @ 3.40GHz or AMD Ryzen 7 2700X @ 3.7 GHZ', '12 GB RAM', 'NVIDIA GeForce GTX 1660 or AMD RX 5600 XT', '100 GB available space', 'DirectX 12'),
(17, 'minimum', 'Windows 10 64-bit', 'Intel Core i3-2100 @ 3.10 GHz/ AMD FX-4100 @ 3.60 GHz', '4 GB RAM', 'NVIDIA GeForce GT 450 1GB/ ATI Radeon HD 7770 1 GB', '110 GB space', 'DirectX 11'),
(17, 'recommended', 'Windows 10 / 11 64-bit', 'Intel Core i5-4430 @ 3 GHz/ AMD FX-8370 @ 3.4 GHz', '8 GB RAM', 'NVIDIA GeForce GTX 770 2GB/ AMD Radeon R9 270 2GB', '110 GB space', 'DirectX 11'),
(18, 'minimum', 'Windows 10 64-bit', 'Intel Core i5-3550 / AMD FX 8150', '16 GB RAM', 'GeForce GTX 1060 / Radeon RX 480', '90 GB SSD space', 'DirectX 12'),
(18, 'recommended', 'Windows 10 / 11 64-bit', 'Intel i7-4790 / AMD FX 8350', '16 GB RAM', 'GeForce GTX 1070 / Radeon RX 580', '90 GB SSD space', 'DirectX 12'),
(19, 'minimum', 'Windows 10 64-bit', 'Ryzen 5 2600, Core i5-8600', '8 GB RAM', 'RX 570, GTX 1050 Ti', '50 GB available space', 'DirectX 12'),
(19, 'recommended', 'Windows 10 / 11 64-bit', 'Ryzen 5 3600, Core i7-8700', '16 GB RAM', 'Radeon RX 5700 (8GB), GeForce RTX 2070 (8GB)', '50 GB SSD space', 'DirectX 12'),
(20, 'minimum', 'Windows 10 64-bit', 'Intel Core i5-5600 or AMD FX 8300', '8 GB RAM', 'NVIDIA GTX 1050 Ti or AMD RX 470', '100 GB space', 'DirectX 12'),
(20, 'recommended', 'Windows 10 / 11 64-bit', 'Intel Core i7-7700k or AMD Ryzen 5 2600X', '16 GB RAM', 'NVIDIA RTX 2070 or AMD RX 6600XT', '100 GB space', 'DirectX 12'),
(21, 'minimum', 'Windows 10 64-bit', 'Intel Core i5-4460 or AMD FX-8120', '4 GB RAM', 'GeForce GTX 460 2GB, Radeon HD 7770 2GB', '50 GB space', 'DirectX 11'),
(21, 'recommended', 'Windows 10 / 11 64-bit', 'Intel Core i5-8600K or AMD Ryzen 5 2600X', '16 GB RAM', 'GeForce GTX 1070 8 GB, Radeon RX 580 8GB', '50 GB space', 'DirectX 11'),
(22, 'minimum', 'Windows 10 64-bit', 'Intel Core i5 @ 3.3 GHz or AMD Ryzen 3 @ 3.1 GHz', '8 GB RAM', 'NVIDIA GeForce GTX 1050Ti (4GB), GTX 1060 (3GB)', '80 GB available space', 'DirectX 11'),
(22, 'recommended', 'Windows 10 / 11 64-bit', 'Intel Core i7-6700K or AMD Ryzen 7 1800X', '8 GB RAM', 'NVIDIA GeForce GTX 1080 (8GB), RTX 2060 (6GB)', '80 GB available space', 'DirectX 11'),
(23, 'minimum', 'Windows 10 64-bit', 'AMD FX 4350 or Equivalent, Intel Core i3 6300', '6 GB RAM', 'AMD Radeon HD 7730, NVIDIA GeForce GT 640', '56 GB space', 'DirectX 11'),
(23, 'recommended', 'Windows 10 / 11 64-bit', 'Ryzen 5 CPU or Equivalent, Intel Core i5 3570K', '8 GB RAM', 'AMD Radeon R9 290, NVIDIA GeForce GTX 970', '56 GB space', 'DirectX 11'),
(24, 'minimum', 'Windows 10 64-bit', 'Intel Core i3 560 @ 3.3 GHz or AMD Phenom II X4 945', '6 GB RAM', 'NVIDIA GeForce GTX 460 or AMD Radeon HD 5870', '61 GB space', 'DirectX 11'),
(24, 'recommended', 'Windows 10 / 11 64-bit', 'Intel Core i5-2500K @ 3.3 GHz or AMD FX-8120 @ 3.1 Ghz', '8 GB RAM', 'NVIDIA GeForce GTX 670 or AMD Radeon HD 7970', '61 GB space', 'DirectX 11'),
(25, 'minimum', 'Windows 10 / 11 64-bit', 'Intel i5-7600K or AMD equivalent', '16 GB RAM', 'GeForce RTX 2060 / Radeon RX 6600 (6GB VRAM)', '90 GB SSD space', 'DirectX 12'),
(25, 'recommended', 'Windows 10 / 11 64-bit', 'Intel i7-10700K or AMD Ryzen 7 3700X', '16 GB RAM', 'GeForce RTX 3070 / Radeon RX 6700 XT (8GB VRAM)', '90 GB SSD space', 'DirectX 12'),
(26, 'minimum', 'Windows 10 64-bit', 'Ryzen 5 2600x, Core i5 8600', '16 GB RAM', 'AMD RX 5700, GTX 1070', '50 GB SSD space', 'DirectX 12'),
(26, 'recommended', 'Windows 10 / 11 64-bit', 'Ryzen 5 5600X, Core i5 11600K', '16 GB RAM', 'Radeon RX 6700 XT, Geforce RTX 2070', '50 GB SSD space', 'DirectX 12'),
(27, 'minimum', 'Windows 10 64-bit', 'AMD Ryzen 5 1500X, Intel Core i7-4770K', '16 GB RAM', 'AMD Radeon 470 (4 GB), NVIDIA GeForce GTX 970 (4 GB)', '100 GB SSD space', 'DirectX 12'),
(27, 'recommended', 'Windows 10 / 11 64-bit', 'AMD Ryzen 5 3600X, Intel Core i7-8700', '16 GB RAM', 'AMD Radeon RX 6600 XT (8 GB), NVIDIA GeForce RTX 2070 (8 GB)', '100 GB SSD space', 'DirectX 12'),
(28, 'minimum', 'Windows 10 64-bit', 'Intel Core i5-6300HQ or AMD Ryzen 3 1300X', '8 GB RAM', 'Intel HD 520 or AMD Radeon Vega 8', '50 GB available space', 'DirectX 12'),
(28, 'recommended', 'Windows 10 / 11 64-bit', 'Intel Core i5-8400 or AMD Ryzen 5 2600', '16 GB RAM', 'Nvidia GeForce 970 GPU or AMD Radeon RX 570 GPU with 4GB of VRAM', '50 GB SSD space', 'DirectX 12'),
(29, 'minimum', 'Windows 10 64-bit', 'Intel i3/Ryzen 3 series', '6 GB RAM', 'Nvidia GTX 900/AMD RX 400 series | Intel Iris Xe Graphics', '120 GB available space', 'DirectX 11'),
(29, 'recommended', 'Windows 10 / 11 64-bit', 'Intel i5/Ryzen 5 series', '8 GB RAM', 'Nvidia GeForce GTX 1660 Ti/AMD RX 5600-XT', '120 GB SSD space', 'DirectX 11'),
(30, 'minimum', 'Windows 10 64-bit', 'Intel Core i7-6800K / AMD Ryzen 5 2600X', '16 GB RAM', 'NVIDIA GeForce GTX 970 / AMD Radeon RX 590 (4GB VRAM)', '50 GB available space', 'DirectX 12'),
(30, 'recommended', 'Windows 10 / 11 64-bit', 'Intel Core i7-10700K / AMD Ryzen 7 2700X', '32 GB RAM', 'NVIDIA GeForce RTX 2080 / AMD Radeon RX 6700 XT (8GB VRAM)', '50 GB SSD space', 'DirectX 12'),
(31, 'minimum', 'Windows 10 64-bit', 'Intel Core i7-6700K | AMD Ryzen 5 2600X', '8 GB RAM', 'Nvidia GeForce GTX 970 (4 GB) | AMD Radeon RX 480 (8 GB)', '60 GB space', 'DirectX 11'),
(31, 'recommended', 'Windows 10 / 11 64-bit', 'Intel Core i5-12600K | AMD Ryzen 7 5800X', '16 GB RAM', 'Nvidia GeForce RTX 3080 (10 GB) | AMD Radeon RX 6800 XT (16 GB)', '60 GB SSD space', 'DirectX 11'),
(32, 'minimum', 'Windows 10 64-bit', 'Dual core CPU 2.4 GHz', '4 GB RAM', 'GeForce GTS 450-class (Intel HD 4000)', '12 GB space', 'DirectX 11'),
(32, 'recommended', 'Windows 10 / 11 64-bit', 'Quad core CPU 3.0 GHz', '6 GB RAM', 'GeForce GTX 760-class (2 GB)', '12 GB space', 'DirectX 11'),
(33, 'minimum', 'Windows 10 64-bit', 'AMD Ryzen 5 2600X, Intel Core i7-6800K', '16 GB RAM', 'AMD Radeon RX 5700, NVIDIA GeForce 1070 Ti', '125 GB SSD space', 'DirectX 12'),
(33, 'recommended', 'Windows 10 / 11 64-bit', 'AMD Ryzen 5 3600X, Intel i5-10600K', '16 GB RAM', 'AMD Radeon RX 6800 XT, NVIDIA GeForce RTX 2080', '125 GB SSD space', 'DirectX 12'),
(34, 'minimum', 'Windows 10 64-bit', 'AMD Ryzen 3 1200 - 3.1 Ghz / Intel i5-4460 - 3.2 Ghz', '8 GB RAM', 'AMD R9 380 - 4GB / GeForce GTX 960 4GB', '50 GB space', 'DirectX 12'),
(34, 'recommended', 'Windows 10 / 11 64-bit', 'AMD Ryzen 7 1700 - 3.0 Ghz / Intel i7-6700 – 3.4 Ghz', '8 GB RAM', 'AMD Vega 64 - 8GB / GeForce GTX 1080 - 8GB', '50 GB SSD space', 'DirectX 12'),
(35, 'minimum', 'Windows 10 64-bit', 'Intel Core i5-8400 / AMD Ryzen 5 1600', '16 GB RAM', 'NVIDIA GeForce GTX 1060 6GB / AMD Radeon RX 580 8GB', '130 GB space', 'DirectX 12'),
(35, 'recommended', 'Windows 10 / 11 64-bit', 'Intel Core i7-9700 / AMD Ryzen 5 5500', '16 GB RAM', 'NVIDIA GeForce RTX 2060 / AMD Radeon RX 5700 XT', '130 GB SSD space', 'DirectX 12')
ON DUPLICATE KEY UPDATE 
    `operating_system`=VALUES(`operating_system`),
    `processor`=VALUES(`processor`),
    `memory`=VALUES(`memory`),
    `graphics`=VALUES(`graphics`),
    `storage`=VALUES(`storage`),
    `directx`=VALUES(`directx`);

-- =========================================
-- SEED DATA: 15 YEARS GAME OF THE YEAR (2011-2025)
-- =========================================
INSERT INTO `game_of_the_year` (`year`, `game_id`, `description`) VALUES
(2025, 35, 'Black Myth: Wukong won universal acclaim for mind-bending visual spectacle, deep mythic narrative, and blistering combat flow.'),
(2024, 12, 'Ghost of Tsushima: Director’s Cut celebrated for legendary open-world artistry, emotional storytelling, and exquisite samurai swordplay.'),
(2023, 11, 'Baldur’s Gate 3 shattered RPG records with staggering player agency, peerless character writing, and infinite replayability.'),
(2022, 10, 'Elden Ring redefined open-world action RPGs with unmatched sense of discovery, boss craft, and sprawling Lands Between.'),
(2021, 4, 'Forza Horizon 5 captivated motorsport enthusiasts with breathtaking Mexican vistas and supreme driving physics.'),
(2020, 22, 'DOOM Eternal pushed first-person combat to blistering heights of frantic precision and adrenaline-fueled destruction.'),
(2019, 7, 'Red Dead Redemption 2 stood as an unmatched landmark of interactive storytelling and living frontier world design.'),
(2018, 9, 'God of War forged an unforgettable mythic bond between father and son with groundbreaking cinematic direction.'),
(2017, 9, 'Phenomenal action storytelling, mythic grandeur, and visceral Leviathan axe combat dynamics.'),
(2016, 8, 'Civilization VI captivated strategy minds with district mechanics, vibrant cultural progression, and grand leadership.'),
(2015, 2, 'The Witcher 3: Wild Hunt set the golden standard for story-rich fantasy worlds, monster hunting, and nuanced quests.'),
(2014, 2, 'Unrivaled role-playing depth, dark fantasy consequence, and legendary quest storytelling.'),
(2013, 1, 'Grand Theft Auto V revolutionized open-world action with three-protagonist heist storytelling across Los Santos.'),
(2012, 32, 'Euro Truck Simulator 2 emerged as a beloved global phenomenon delivering unprecedented relaxation and authentic simulation.'),
(2011, 2, 'Classic fantasy milestone inspiring a whole new generation of adventure games.')
ON DUPLICATE KEY UPDATE `game_id`=VALUES(`game_id`), `description`=VALUES(`description`);

-- =========================================
-- SEED DATA: SAMPLE REVIEWS
-- =========================================
INSERT INTO `reviews` (`review_id`, `game_id`, `user_id`, `rating`, `review_text`, `status`, `created_at`) VALUES
(1, 1, 2, 5, 'Absolute masterpiece! The story, the characters, and the sheer amount of things to do in Los Santos never get old. Runs smoothly on modern PCs.', 'visible', '2026-08-10 14:20:00'),
(2, 2, 2, 5, 'The Witcher 3 is hands down one of the best RPGs ever created. The Bloody Baron questline alone is better than entire games.', 'visible', '2026-08-15 11:30:00'),
(3, 10, 2, 5, 'Elden Ring is truly a once-in-a-generation gaming achievement. Exploration is purely magical.', 'visible', '2026-08-20 18:45:00'),
(4, 11, 2, 5, 'Baldur’s Gate 3 delivers the purest D&D roleplaying experience possible in a video game. Extraordinary voice acting and freedom!', 'visible', '2026-08-25 09:15:00'),
(5, 35, 2, 5, 'Black Myth: Wukong is a visual tour de force with sensational boss encounters and fluid staff martial arts.', 'visible', '2026-09-02 16:30:00')
ON DUPLICATE KEY UPDATE `review_text`=VALUES(`review_text`);

-- =========================================
-- SEED DATA: SAMPLE FAVORITES
-- =========================================
INSERT INTO `favorites` (`user_id`, `game_id`) VALUES
(2, 1),
(2, 7),
(2, 10),
(2, 11),
(2, 35)
ON DUPLICATE KEY UPDATE `game_id`=VALUES(`game_id`);

-- =========================================
-- SEED DATA: SAMPLE ORDERS
-- =========================================
INSERT INTO `orders` (`order_id`, `user_id`, `order_number`, `total_amount`, `payment_method`, `payment_status`, `order_status`, `created_at`) VALUES
(1, 2, 'GA10001', 2498.00, 'UPI / Instant Pay', 'paid', 'completed', '2026-09-01 10:15:00')
ON DUPLICATE KEY UPDATE `total_amount`=VALUES(`total_amount`);

INSERT INTO `order_items` (`order_item_id`, `order_id`, `game_id`, `quantity`, `price`) VALUES
(1, 1, 1, 1, 1499.00),
(2, 1, 2, 1, 999.00)
ON DUPLICATE KEY UPDATE `price`=VALUES(`price`);
