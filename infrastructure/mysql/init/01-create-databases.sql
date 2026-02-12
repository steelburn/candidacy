-- Create separate databases for each service
CREATE DATABASE IF NOT EXISTS candidacy_auth;
CREATE DATABASE IF NOT EXISTS candidacy_candidate;
CREATE DATABASE IF NOT EXISTS candidacy_vacancy;
CREATE DATABASE IF NOT EXISTS candidacy_matching;
CREATE DATABASE IF NOT EXISTS candidacy_interview;
CREATE DATABASE IF NOT EXISTS candidacy_offer;
CREATE DATABASE IF NOT EXISTS candidacy_onboarding;
CREATE DATABASE IF NOT EXISTS candidacy_reporting;
CREATE DATABASE IF NOT EXISTS candidacy_admin;
CREATE DATABASE IF NOT EXISTS candidacy_notification;
CREATE DATABASE IF NOT EXISTS candidacy_tenant;
CREATE DATABASE IF NOT EXISTS candidacy_document_parser;
CREATE DATABASE IF NOT EXISTS candidacy_ai;


-- Grant privileges
GRANT ALL PRIVILEGES ON candidacy_auth.* TO 'root'@'%';
GRANT ALL PRIVILEGES ON candidacy_candidate.* TO 'root'@'%';
GRANT ALL PRIVILEGES ON candidacy_vacancy.* TO 'root'@'%';
GRANT ALL PRIVILEGES ON candidacy_matching.* TO 'root'@'%';
GRANT ALL PRIVILEGES ON candidacy_interview.* TO 'root'@'%';
GRANT ALL PRIVILEGES ON candidacy_offer.* TO 'root'@'%';
GRANT ALL PRIVILEGES ON candidacy_onboarding.* TO 'root'@'%';
GRANT ALL PRIVILEGES ON candidacy_reporting.* TO 'root'@'%';
GRANT ALL PRIVILEGES ON candidacy_admin.* TO 'root'@'%';
GRANT ALL PRIVILEGES ON candidacy_notification.* TO 'root'@'%';
GRANT ALL PRIVILEGES ON candidacy_tenant.* TO 'root'@'%';
GRANT ALL PRIVILEGES ON candidacy_document_parser.* TO 'root'@'%';
GRANT ALL PRIVILEGES ON candidacy_ai.* TO 'root'@'%';

FLUSH PRIVILEGES;
