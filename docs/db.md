Absolutely! Let’s create a comprehensive, detailed MySQL database design documentation for Fundi App MVP, incorporating best practices, normalization, high security, and prevention of duplicates. I’ll also include tables, fields, data types, relationships, constraints, and explanations.

🗄️ Fundi App – MySQL Database Design Documentation

1. Design Principles
Normalization: Up to 3NF to avoid redundancy and ensure consistency.


Security:


Store passwords hashed (bcrypt).


Sensitive documents (NIDA/VETA) stored as secure file references, not raw content.


Use foreign keys with ON DELETE CASCADE where necessary.


Unique Constraints:


Prevent duplicate users by unique phone numbers.


Prevent duplicate job applications for same fundi & job.


Indexes:


On frequently searched columns (location, category, status).


Composite indexes for performance on joins.


Scalability:


Separate tables for media, portfolios, applications, jobs.


Admin settings in a single table for flexibility.



2. Tables Overview
Table Name
Description
users
Stores all user info (fundis & customers)
fundi_profiles
Extended info for fundis, verification, skills, NIDA/VETA
jobs
Customer posted jobs
job_applications
Fundi applications to jobs
portfolio
Fundi portfolio items (media)
portfolio_media
Images/videos for each portfolio item
job_media
Media attached to jobs (images/videos)
admin_settings
Platform configuration (payment modes, fees)
payments
Records Pesapal transactions for platform fees
ratings_reviews
Customer ratings & reviews for fundis
categories
Job/fundi categories for lookup
notifications
Push notifications logs (optional)
sessions
User sessions for security / audit (optional)


3. Detailed Table Design
3.1 users
Stores all registered users (fundis and customers).
Column
Type
Constraints
Description
id
BIGINT UNSIGNED
PK, AUTO_INCREMENT
User ID
phone
VARCHAR(15)
UNIQUE, NOT NULL
User phone number
password
VARCHAR(255)
NOT NULL
Hashed password
role
ENUM('customer','fundi','admin')
NOT NULL
User type
status
ENUM('active','inactive','banned')
DEFAULT 'active'
Account status
nida_number
VARCHAR(20)
NOT NULL
Mandatory ID number
created_at
TIMESTAMP
DEFAULT CURRENT_TIMESTAMP


updated_at
TIMESTAMP
DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP



Notes:
Passwords hashed with bcrypt.


Unique phone ensures no duplicate accounts.



3.2 fundi_profiles
Extended profile info for fundis.
Column
Type
Constraints
Description
id
BIGINT UNSIGNED
PK, AUTO_INCREMENT
Fundi profile ID
user_id
BIGINT UNSIGNED
FK → users(id), UNIQUE
Link to user
full_name
VARCHAR(100)
NOT NULL
Fundi full name
location_lat
DECIMAL(10,7)
NULL
Latitude
location_lng
DECIMAL(10,7)
NULL
Longitude
verification_status
ENUM('pending','approved','rejected')
DEFAULT 'pending'
Admin verification
veta_certificate
VARCHAR(255)
NULL
File path (optional)
skills
TEXT
NULL
Comma-separated skills or JSON
experience_years
TINYINT UNSIGNED
NULL
Years of experience
bio
TEXT
NULL
Short description
created_at
TIMESTAMP
DEFAULT CURRENT_TIMESTAMP


updated_at
TIMESTAMP
DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP



Notes:
skills can be normalized further into a separate table if advanced search/filter needed.


FK user_id ensures each fundi profile belongs to exactly one user.



3.3 categories
Job/fundi categories lookup table.
Column
Type
Constraints
Description
id
INT UNSIGNED
PK, AUTO_INCREMENT
Category ID
name
VARCHAR(50)
UNIQUE, NOT NULL
Category name
description
TEXT
NULL
Optional description


3.4 jobs
Customer posted jobs.
Column
Type
Constraints
Description
id
BIGINT UNSIGNED
PK, AUTO_INCREMENT
Job ID
customer_id
BIGINT UNSIGNED
FK → users(id)
Customer posting the job
category_id
INT UNSIGNED
FK → categories(id)
Job category
title
VARCHAR(150)
NOT NULL
Job title
description
TEXT
NOT NULL
Job details
budget
DECIMAL(10,2)
NULL
Customer budget
deadline
DATETIME
NULL
Job completion deadline
location_lat
DECIMAL(10,7)
NULL
Latitude
location_lng
DECIMAL(10,7)
NULL
Longitude
status
ENUM('open','in_progress','completed','cancelled')
DEFAULT 'open'
Job status
created_at
TIMESTAMP
DEFAULT CURRENT_TIMESTAMP


updated_at
TIMESTAMP
DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP




3.5 job_applications
Fundi applications for jobs.
Column
Type
Constraints
Description
id
BIGINT UNSIGNED
PK, AUTO_INCREMENT
Application ID
job_id
BIGINT UNSIGNED
FK → jobs(id)
Job applied to
fundi_id
BIGINT UNSIGNED
FK → users(id)
Fundi applying
requirements
TEXT
NULL
Fundi notes for job
proposed_budget
DECIMAL(10,2)
NULL
Fundi proposed budget
estimated_time
INT UNSIGNED
NULL
Days/hours estimated
status
ENUM('pending','accepted','rejected')
DEFAULT 'pending'
Application status
created_at
TIMESTAMP
DEFAULT CURRENT_TIMESTAMP


updated_at
TIMESTAMP
DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP



Constraints:
UNIQUE(job_id, fundi_id) → Prevent duplicate applications for same job.



3.6 portfolio
Fundi portfolio items (projects).
Column
Type
Constraints
Description
id
BIGINT UNSIGNED
PK, AUTO_INCREMENT
Portfolio ID
fundi_id
BIGINT UNSIGNED
FK → users(id)
Owner fundi
title
VARCHAR(150)
NULL
Portfolio item title
description
TEXT
NULL
Details about work
skills_used
TEXT
NULL
Skills applied
duration_hours
INT UNSIGNED
NULL
Time spent on project
budget
DECIMAL(10,2)
NULL
Optional project budget
created_at
TIMESTAMP
DEFAULT CURRENT_TIMESTAMP


updated_at
TIMESTAMP
DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP




3.7 portfolio_media
Images/videos for portfolio items.
Column
Type
Constraints
Description
id
BIGINT UNSIGNED
PK, AUTO_INCREMENT
Media ID
portfolio_id
BIGINT UNSIGNED
FK → portfolio(id)
Linked portfolio item
media_type
ENUM('image','video')
NOT NULL
Type
file_path
VARCHAR(255)
NOT NULL
File storage path
order_index
SMALLINT UNSIGNED
DEFAULT 0
For sliding gallery order
created_at
TIMESTAMP
DEFAULT CURRENT_TIMESTAMP




3.8 job_media
Images/videos for jobs (optional).
Column
Type
Constraints
Description
id
BIGINT UNSIGNED
PK, AUTO_INCREMENT
Media ID
job_id
BIGINT UNSIGNED
FK → jobs(id)
Job ID
media_type
ENUM('image','video')
NOT NULL
Type
file_path
VARCHAR(255)
NOT NULL
File storage path
order_index
SMALLINT UNSIGNED
DEFAULT 0
For gallery order
created_at
TIMESTAMP
DEFAULT CURRENT_TIMESTAMP




3.9 ratings_reviews
Customer ratings for fundis.
Column
Type
Constraints
Description
id
BIGINT UNSIGNED
PK, AUTO_INCREMENT
Review ID
fundi_id
BIGINT UNSIGNED
FK → users(id)
Fundi being rated
customer_id
BIGINT UNSIGNED
FK → users(id)
Customer giving rating
rating
TINYINT UNSIGNED
NOT NULL
1-5 stars
review
TEXT
NULL
Optional text review
created_at
TIMESTAMP
DEFAULT CURRENT_TIMESTAMP



Constraint:
UNIQUE(fundi_id, customer_id, job_id) if rating per job



3.10 admin_settings
Platform configurations.
Column
Type
Constraints
Description
id
INT UNSIGNED
PK, AUTO_INCREMENT
Setting ID
payments_enabled
BOOLEAN
DEFAULT TRUE
Enable/disable platform fees
payment_model
ENUM('subscription','pay_per_application','pay_per_job','hybrid')
DEFAULT 'subscription'
Active payment model
subscription_fee
DECIMAL(10,2)
NULL
Subscription amount
application_fee
DECIMAL(10,2)
NULL
Fee per job application
job_post_fee
DECIMAL(10,2)
NULL
Fee per job posting
created_at
TIMESTAMP
DEFAULT CURRENT_TIMESTAMP


updated_at
TIMESTAMP
DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP








3.11 payments
Records Pesapal transactions for platform fees (subscription, application fee, job posting).
Column
Type
Constraints
Description
id
BIGINT UNSIGNED
PK, AUTO_INCREMENT
Payment ID
user_id
BIGINT UNSIGNED
FK → users(id)
User making payment
amount
DECIMAL(10,2)
NOT NULL
Payment amount
payment_type
ENUM('subscription','application_fee','job_posting')
NOT NULL
Type of payment
status
ENUM('pending','completed','failed')
DEFAULT 'pending'
Payment status
pesapal_reference
VARCHAR(100)
UNIQUE
Pesapal transaction ID
created_at
TIMESTAMP
DEFAULT CURRENT_TIMESTAMP


updated_at
TIMESTAMP
DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP



Notes:
FK user_id ensures payment belongs to valid user.


pesapal_reference unique to prevent duplicates.



3.12 notifications (optional but recommended for engagement)
Logs push notifications sent via Firebase.
Column
Type
Constraints
Description
id
BIGINT UNSIGNED
PK, AUTO_INCREMENT
Notification ID
user_id
BIGINT UNSIGNED
FK → users(id)
Recipient user
type
VARCHAR(50)
NULL
Notification type (job_posted, application_accepted, payment_success)
title
VARCHAR(150)
NULL
Notification title
message
TEXT
NULL
Notification body
read_status
BOOLEAN
DEFAULT FALSE
Whether user has read notification
created_at
TIMESTAMP
DEFAULT CURRENT_TIMESTAMP




3.13 sessions (optional but improves security and audit)
Tracks user sessions and devices.
Column
Type
Constraints
Description
id
BIGINT UNSIGNED
PK, AUTO_INCREMENT
Session ID
user_id
BIGINT UNSIGNED
FK → users(id)
Session owner
device_info
VARCHAR(255)
NULL
Device details (model/browser)
ip_address
VARCHAR(45)
NULL
IP of user
token
VARCHAR(255)
UNIQUE, NOT NULL
Session token
login_at
TIMESTAMP
DEFAULT CURRENT_TIMESTAMP
Login timestamp
logout_at
TIMESTAMP
NULL
Logout timestamp
expired_at
TIMESTAMP
NULL
Token expiration

Security Notes:
Helps track active sessions, logouts, suspicious activity.


Supports multi-device login if needed.



4. Relationships Diagram (High-Level)
users → fundi_profiles (1:1)


users → jobs (1:N, for customer)


users → job_applications (1:N, for fundi)


jobs → job_applications (1:N)


portfolio → portfolio_media (1:N)


jobs → job_media (1:N)


users → ratings_reviews (1:N, fundi as recipient)


categories → jobs (1:N)


users → payments (1:N)


users → notifications (1:N)


users → sessions (1:N)


Notes:
Use ON DELETE CASCADE for dependent tables where appropriate (e.g., deleting a fundi deletes portfolio & portfolio_media).


Composite unique constraints prevent duplicate applications or ratings.



5. Indexing Recommendations
users.phone → UNIQUE + INDEX


job_applications.job_id + fundi_id → UNIQUE + INDEX


jobs.location_lat + location_lng → INDEX for proximity search


portfolio.fundi_id → INDEX


payments.user_id → INDEX for transaction queries


ratings_reviews.fundi_id + customer_id + job_id → UNIQUE + INDEX



6. Security Best Practices
Passwords: bcrypt hash, never store raw.


Sensitive Documents: Store file paths only, actual files in secure cloud storage (AWS S3/Firebase Storage) with signed URLs.


Foreign Keys & Constraints: Enforce integrity, prevent orphaned records.


Unique Constraints: Prevent duplicate users, applications, ratings.


Sessions: Token-based authentication via JWT, with expiry.


Encrypted Connections: MySQL over TLS, API HTTPS calls.


Audit Logging: Optional notifications & session tracking for security audits.



7. Optional Advanced Normalizations
Skills Table: If you want multi-select & filtering for fundi skills:


skills(id, name)


fundi_skills(fundi_id, skill_id)


Job Tags: tags(id, name) + job_tags(job_id, tag_id) for better search & filtering


Media Table Unification: Instead of portfolio_media + job_media, create media(id, parent_type, parent_id, media_type, file_path, order_index) for scalability



✅ Conclusion:
 This MySQL design is:
Normalized → avoids duplicates


Secure → hashed passwords, sensitive file paths, session tracking


Scalable → supports portfolio, media, notifications, payment tracking


Flexible → admin settings and future growth (skills, tags, escrow)



complete ERD (Entity-Relationship Diagram) with all tables, relationships, and constraints
Perfect! I’ll create a complete ERD (Entity-Relationship Diagram) for your Fundi App based on the MySQL design we just detailed. Since I can generate visual diagrams, here’s a textual description first, and I can also create an image for you.

Fundi App ERD – Overview
Entities & Relationships
users


PK: id


Attributes: phone, password, role, status, nida_number


Relationships:


1:1 → fundi_profiles (for fundi users)


1:N → jobs (for customers)


1:N → job_applications (for fundis)


1:N → payments


1:N → notifications


1:N → sessions


1:N → ratings_reviews (as recipient fundi)


fundi_profiles


PK: id


FK: user_id → users.id (unique)


Attributes: full_name, location_lat, location_lng, verification_status, veta_certificate, skills, experience_years, bio


categories


PK: id


Attributes: name, description


Relationships: 1:N → jobs


jobs


PK: id


FK: customer_id → users.id


FK: category_id → categories.id


Attributes: title, description, budget, deadline, location_lat, location_lng, status


Relationships:


1:N → job_applications


1:N → job_media


job_applications


PK: id


FK: job_id → jobs.id


FK: fundi_id → users.id


Attributes: requirements, proposed_budget, estimated_time, status


Unique Constraint: job_id + fundi_id (no duplicate applications)


portfolio


PK: id


FK: fundi_id → users.id


Attributes: title, description, skills_used, duration_hours, budget


Relationships: 1:N → portfolio_media


portfolio_media


PK: id


FK: portfolio_id → portfolio.id


Attributes: media_type, file_path, order_index


job_media


PK: id


FK: job_id → jobs.id


Attributes: media_type, file_path, order_index


ratings_reviews


PK: id


FK: fundi_id → users.id


FK: customer_id → users.id


Attributes: rating, review


Unique Constraint: fundi_id + customer_id + job_id


admin_settings


PK: id


Attributes: payments_enabled, payment_model, subscription_fee, application_fee, job_post_fee


payments


PK: id


FK: user_id → users.id


Attributes: amount, payment_type, status, pesapal_reference


notifications


PK: id


FK: user_id → users.id


Attributes: type, title, message, read_status


sessions


PK: id


FK: user_id → users.id


Attributes: device_info, ip_address, token, login_at, logout_at, expired_at



Relationships Summary
Parent Table
Child Table
Type
Notes
users
fundi_profiles
1:1
Only fundi users
users
jobs
1:N
Customer posts jobs
jobs
job_applications
1:N
Fundis apply
users
job_applications
1:N
Fundi applications
portfolio
portfolio_media
1:N
Multiple media per project
jobs
job_media
1:N
Multiple media per job
categories
jobs
1:N
Job categorization
users
payments
1:N
Platform fees
users
ratings_reviews
1:N
Fundi ratings
users
notifications
1:N
Push logs
users
sessions
1:N
Active session tracking


visual ERD image with all tables, PKs, FKs, and relationships




