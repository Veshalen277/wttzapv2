-- Disposable test database ONLY. Minimal host contract, no production user data.
CREATE TABLE users_tbl (id INT NOT NULL PRIMARY KEY, fullname VARCHAR(100) NOT NULL, user_role INT NOT NULL);
INSERT INTO users_tbl VALUES (1900000001,'Test Owner',2),(1900000002,'Test Member',2),(1900000003,'Test Moderator',7);
