USE club_db;

-- NOTE: The password hashes below are bcrypt-style and intended for local testing.
-- Plain-text test passwords (for local testing only):
--   Admin (Maya Thompson):      Admin$yd2025
--   User (Oliver Nguyen):       SydUserOne2025
--   User (Sophie Carter):       SydUserTwo2025
--
-- After importing, log in using the above credentials, then change them via the app or phpMyAdmin if desired.


-- Users
INSERT INTO users (name, email, password, role, created_at) VALUES
('Maya Thompson',   'maya.thompson@example.com',   '$2y$12$Z0KcMuFcuE8p5D2DS7FOjewYOCDYIlK2eF8pyu46auPPoJZtG3vnK', 'admin', '2025-07-01 10:15:00'),
('Oliver Nguyen',   'oliver.nguyen@example.com',  '$2y$12$lDfxnELQ.e4C6/W15PruS.2pQRm695vqT.A7kKVuxIdOwzzals6Wm', 'user',  '2025-07-02 09:20:00'),
('Sophie Carter',   'sophie.carter@example.com',  '$2y$12$RSz1EGeO2jVXXvKMETm6AO1QH6kJFx37.W0NyeVvDR/4u41YhNwIm', 'user',  '2025-07-03 11:05:00');


-- Events 
INSERT INTO events (title, description, event_date, capacity, created_at) VALUES
('Orientation Night — UNSW Quad', 
 'Welcome to the new semester! Meet club officers and other students at the UNSW Quadrangle. Light refreshments provided. Bring a student ID.', 
 '2025-08-20', 150, '2025-06-15 09:00:00'),

('Sydney Harbour Cleanup', 
 'Community service event: help clean a section of the foreshore near Circular Quay. Gloves and bags provided. Great for volunteer hours.', 
 '2025-09-14', 60, '2025-06-20 14:30:00'),

('JavaScript Workshop — Intro to Web', 
 'Hands-on workshop covering modern JavaScript basics and building a small web app. Laptops recommended. Suitable for beginners.', 
 '2025-10-05', 40, '2025-07-01 08:00:00');


-- Registrations 

-- Note: because users and events are just inserted, ids are predictable when tables are empty:
-- users: 1=Maya(admin), 2=Oliver, 3=Sophie
-- events: 1=Orientation Night, 2=Harbour Cleanup, 3=JavaScript Workshop

INSERT INTO registrations (user_id, event_id, created_at) VALUES
(2, 1, '2025-07-10 12:00:00'),  -- Oliver registered for Orientation
(3, 3, '2025-07-12 15:20:00');  -- Sophie registered for JavaScript Workshop


