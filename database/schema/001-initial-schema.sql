-- CaveTrip Manager initial schema draft
-- This is a design baseline and will be converted into migrations.

CREATE TABLE grottos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    email VARCHAR(255) NULL,
    phone VARCHAR(100) NULL,
    website_url VARCHAR(255) NULL,
    mailing_address TEXT NULL,
    logo_url VARCHAR(500) NULL,
    logo_file_path VARCHAR(500) NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL
);

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    grotto_id INT NULL,
    role ENUM('super_admin','grotto_admin','member','guest') NOT NULL DEFAULT 'guest',
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    phone VARCHAR(100) NULL,
    password_hash VARCHAR(255) NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    FOREIGN KEY (grotto_id) REFERENCES grottos(id)
);

CREATE TABLE caves (
    id INT AUTO_INCREMENT PRIMARY KEY,
    grotto_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    county VARCHAR(255) NULL,
    general_area VARCHAR(255) NULL,
    gps_latitude DECIMAL(10,7) NULL,
    gps_longitude DECIMAL(10,7) NULL,
    access_notes TEXT NULL,
    sensitive_notes TEXT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    FOREIGN KEY (grotto_id) REFERENCES grottos(id)
);

CREATE TABLE landowners (
    id INT AUTO_INCREMENT PRIMARY KEY,
    grotto_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NULL,
    phone VARCHAR(100) NULL,
    mailing_address TEXT NULL,
    preferred_contact_method VARCHAR(100) NULL,
    notes TEXT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    FOREIGN KEY (grotto_id) REFERENCES grottos(id)
);

CREATE TABLE waiver_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    grotto_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL,
    description TEXT NULL,
    html_body MEDIUMTEXT NOT NULL,
    active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    UNIQUE KEY unique_grotto_template_slug (grotto_id, slug),
    FOREIGN KEY (grotto_id) REFERENCES grottos(id)
);

CREATE TABLE trips (
    id INT AUTO_INCREMENT PRIMARY KEY,
    grotto_id INT NOT NULL,
    trip_number VARCHAR(50) NOT NULL UNIQUE,
    title VARCHAR(255) NOT NULL,
    trip_date DATE NOT NULL,
    meeting_time TIME NULL,
    meeting_location VARCHAR(500) NULL,
    cave_id INT NULL,
    landowner_id INT NULL,
    waiver_template_id INT NULL,
    cave_description TEXT NULL,
    trip_leader_user_id INT NULL,
    min_attendees INT NULL,
    max_attendees INT NULL,
    signup_opens_at DATETIME NULL,
    signup_closes_at DATETIME NULL,
    visibility ENUM('core_group','selected_members','invite_link','private') NOT NULL DEFAULT 'core_group',
    waitlist_enabled TINYINT(1) NOT NULL DEFAULT 1,
    callout_time DATETIME NULL,
    callout_status ENUM('pending','all_out_safe','delayed_ok','emergency','overdue','cancelled') NOT NULL DEFAULT 'pending',
    callout_resolved_at DATETIME NULL,
    status ENUM('draft','open','waiver_signing','finalized','active','completed','cancelled') NOT NULL DEFAULT 'draft',
    cancelled_at DATETIME NULL,
    cancelled_by_user_id INT NULL,
    cancellation_reason TEXT NULL,
    notes TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    FOREIGN KEY (grotto_id) REFERENCES grottos(id),
    FOREIGN KEY (cave_id) REFERENCES caves(id),
    FOREIGN KEY (landowner_id) REFERENCES landowners(id),
    FOREIGN KEY (waiver_template_id) REFERENCES waiver_templates(id),
    FOREIGN KEY (trip_leader_user_id) REFERENCES users(id),
    FOREIGN KEY (cancelled_by_user_id) REFERENCES users(id)
);

CREATE TABLE trip_participants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    trip_id INT NOT NULL,
    user_id INT NULL,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(100) NULL,
    participant_status ENUM('registered','waitlisted','signed','cancelled','removed') NOT NULL DEFAULT 'registered',
    emergency_contact_name VARCHAR(255) NOT NULL,
    emergency_contact_phone VARCHAR(100) NOT NULL,
    emergency_contact_relationship VARCHAR(100) NULL,
    medical_notes TEXT NULL,
    allergies TEXT NULL,
    medications TEXT NULL,
    conditions TEXT NULL,
    physical_limitations TEXT NULL,
    is_minor TINYINT(1) NOT NULL DEFAULT 0,
    guardian_name VARCHAR(255) NULL,
    guardian_email VARCHAR(255) NULL,
    signed_at DATETIME NULL,
    signature_data MEDIUMTEXT NULL,
    signed_ip VARCHAR(100) NULL,
    user_agent TEXT NULL,
    exited_safely_at DATETIME NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL,
    FOREIGN KEY (trip_id) REFERENCES trips(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);
