<?php

namespace App\Constants;

/**
 * Application-wide constants
 * 
 * Centralized location for magic numbers and configuration values
 * used across the application.
 */
class AppConstants
{
    // Pagination
    public const DEFAULT_PAGE_SIZE = 20;
    public const MAX_PAGE_SIZE = 100;
    public const MIN_PAGE_SIZE = 1;
    
    // File Upload
    public const FILE_UPLOAD_MAX_SIZE = 10485760; // 10MB in bytes
    public const CV_MAX_SIZE = 5242880; // 5MB in bytes
    public const ALLOWED_CV_EXTENSIONS = ['pdf', 'doc', 'docx'];
    public const ALLOWED_IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif'];
    
    // API Timeouts
    public const API_TIMEOUT = 120; // seconds
    public const AI_SERVICE_TIMEOUT = 300; // 5 minutes for AI processing
    public const DEFAULT_TIMEOUT = 30; // seconds
    
    // Candidate Status
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_HIRED = 'hired';
    public const STATUS_REJECTED = 'rejected';
    
    // Vacancy Status
    public const VACANCY_STATUS_OPEN = 'open';
    public const VACANCY_STATUS_CLOSED = 'closed';
    public const VACANCY_STATUS_DRAFT = 'draft';
    
    // Match Score Thresholds
    public const MIN_MATCH_SCORE = 0;
    public const MAX_MATCH_SCORE = 100;
    public const GOOD_MATCH_THRESHOLD = 70;
    public const EXCELLENT_MATCH_THRESHOLD = 85;
    
    // Notice Periods
    public const NOTICE_IMMEDIATE = 'immediate';
    public const NOTICE_2_WEEKS = '2 weeks';
    public const NOTICE_1_MONTH = '1 month';
    public const NOTICE_2_MONTHS = '2 months';
    public const NOTICE_3_MONTHS = '3 months';
    
    // Cache TTL (in seconds)
    public const CACHE_TTL_SHORT = 300; // 5 minutes
    public const CACHE_TTL_MEDIUM = 3600; // 1 hour
    public const CACHE_TTL_LONG = 86400; // 24 hours
    
    // Rate Limiting
    public const RATE_LIMIT_PER_MINUTE = 60;
    public const RATE_LIMIT_PER_HOUR = 1000;
    
    // Job Processing
    public const MAX_RETRY_ATTEMPTS = 3;
    public const RETRY_DELAY_SECONDS = 60;
}
