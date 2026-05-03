CREATE DATABASE IF NOT EXISTS hostel_db;
USE hostel_db;

CREATE TABLE hostels (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    location VARCHAR(255)
);

CREATE TABLE rooms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hostel_id INT NOT NULL,
    room_number VARCHAR(20) NOT NULL,
    capacity INT DEFAULT 4,
    FOREIGN KEY (hostel_id) REFERENCES hostels(id) ON DELETE CASCADE
);

CREATE TABLE students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20),
    hostel_id INT,
    room_id INT,
    FOREIGN KEY (hostel_id) REFERENCES hostels(id) ON DELETE SET NULL,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE SET NULL
);
