# School Management System - Admission Module

## Overview
The Admission Module is a comprehensive solution for managing student admissions in an educational institution. It provides a complete workflow from application submission to student enrollment, including document management, status tracking, and approval processes.

## Features

### 1. Application Management
- Online application form with validation
- Multi-step application process with save & resume capability
- Application status tracking (Draft, Submitted, Under Review, Approved, Rejected, Waitlisted, Enrolled, Cancelled)
- Application number generation
- Bulk import/export functionality

### 2. Document Management
- Upload and manage admission documents
- Document type categorization
- Document approval workflow
- Secure file storage with access control

### 3. Workflow & Approvals
- Role-based access control for admission officers
- Multi-level approval process
- Automated notifications for status changes
- Audit trail for all actions

### 4. Student Enrollment
- Seamless transition from admission to student record
- Automatic student ID generation
- Batch assignment
- Document migration to student profile

## Database Schema

### Admissions Table
- `id` - Primary key
- `application_number` - Unique application identifier
- `academic_session_id` - Reference to academic session
- `batch_id` - Reference to batch/class
- `first_name`, `last_name` - Applicant's name
- `gender`, `date_of_birth`, `blood_group` - Personal details
- `contact_info` - Email, phone, address
- `parent_info` - Father, mother, and guardian details
- `previous_education` - Previous school and academic details
- `documents` - References to uploaded documents
- `status` - Current application status
- `timestamps` - Created, updated, and status change timestamps

### Admission Documents Table
- `id` - Primary key
- `admission_id` - Reference to admission
- `type` - Document type (birth certificate, transfer certificate, etc.)
- `file_path` - Storage path
- `file_type`, `file_size` - File metadata
- `is_approved` - Approval status
- `review_notes` - Reviewer comments
- `reviewed_by`, `reviewed_at` - Review metadata

## API Endpoints

### Public Endpoints
- `POST /api/admissions` - Submit new application
- `GET /api/admissions/status/{application_number}` - Check application status

### Protected Endpoints (Require Authentication)
- `GET /api/admissions` - List all applications (with filters)
- `GET /api/admissions/{id}` - Get application details
- `PUT /api/admissions/{id}` - Update application
- `DELETE /api/admissions/{id}` - Delete application
- `POST /api/admissions/{id}/submit` - Submit for review
- `POST /api/admissions/{id}/approve` - Approve application
- `POST /api/admissions/{id}/reject` - Reject application
- `POST /api/admissions/{id}/enroll` - Enroll student
- `POST /api/admissions/{id}/documents` - Upload document
- `DELETE /api/admissions/{id}/documents/{document}` - Delete document

## Setup Instructions

### Prerequisites
- PHP 8.1+
- Laravel 10.x
- MySQL 8.0+ or PostgreSQL 13+
- Composer
- File storage with write permissions

### Installation
1. Clone the repository
2. Install dependencies:
   ```bash
   composer install
   ```
3. Copy `.env.example` to `.env` and configure database connection
4. Generate application key:
   ```bash
   php artisan key:generate
   ```
5. Run migrations and seeders:
   ```bash
   php artisan migrate --seed
   ```
6. Set up storage link:
   ```bash
   php artisan storage:link
   ```

### Configuration
Configure the following in your `.env` file:

```ini
# File Storage
FILESYSTEM_DISK=local  # or 's3' for cloud storage

# Admission Settings
ADMISSION_PREFIX=APP
ADMISSION_YEAR_FORMAT=Y
ADMISSION_START_NUMBER=1000

# File Upload Settings
UPLOAD_MAX_SIZE=10240  # 10MB in KB
ALLOWED_FILE_TYPES=pdf,jpg,jpeg,png
```

## Usage

### Submitting an Application
1. User fills out the online application form
2. System validates the input and creates a draft application
3. User uploads required documents
4. User submits the application for review
5. Admission officer reviews the application
6. Application is approved/rejected
7. If approved, student can be enrolled

### Managing Applications (Admin)
1. Log in to the admin dashboard
2. Navigate to Admissions > Applications
3. Use filters to find specific applications
4. View application details and documents
5. Update status as needed
6. Communicate with applicants

## Testing

### Running Tests
```bash
php artisan test tests/Feature/AdmissionTest.php
```

### Test Coverage
- Application submission and validation
- Document upload and management
- Status transitions
- Permission checks
- Email notifications

## Security Considerations

### Data Protection
- All sensitive data is encrypted at rest
- File uploads are validated for type and size
- Access to documents is restricted based on user roles

### Rate Limiting
- Public endpoints are rate limited to prevent abuse
- File uploads have size and type restrictions

## Troubleshooting

### Common Issues
1. **File Upload Fails**
   - Check file size and type
   - Verify storage permissions
   - Check PHP upload limits

2. **Application Not Saving**
   - Check validation errors in response
   - Verify database connection
   - Check server logs for errors

3. **Permission Denied**
   - Verify user roles and permissions
   - Check middleware configuration
   - Ensure authentication is working

## API Documentation

For detailed API documentation, run the application and visit:
```
/api/documentation
```

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License
This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.
