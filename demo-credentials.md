# Demo Credentials for Testing

## Admin Accounts

### Super Admin
- **Email:** admin@school.com  
  **Password:** password  
  **Role:** Super Administrator
  **Permissions:** Full access to all features

### School Administrator
- **Email:** principal@school.com  
  **Password:** principal123  
  **Role:** School Administrator  
  **Permissions:** Manage teachers, students, and school settings

## Teacher Accounts

### Senior Teacher
- **Email:** teacher.john@school.com  
  **Password:** teach1234  
  **Role:** Senior Teacher  
  **Subjects:** Mathematics, Physics

### Junior Teacher
- **Email:** teacher.sarah@school.com  
  **Password:** teach5678  
  **Role:** Teacher  
  **Subjects:** English, Literature

## Student Accounts

### High School Student
- **Email:** student.alex@school.com  
  **Password:** student123  
  **Grade:** 10  
  **Section:** A

### Middle School Student
- **Email:** student.mia@school.com  
  **Password:** student456  
  **Grade:** 7  
  **Section:** B

## Parent Accounts

### Parent of Multiple Students
- **Email:** parent.smith@email.com  
  **Password:** parent123  
  **Children:** Alex Smith, Emma Smith

### Single Parent
- **Email:** parent.johnson@email.com  
  **Password:** parent456  
  **Child:** Noah Johnson

## Staff Accounts

### Accountant
- **Email:** accountant@school.com  
  **Password:** account123  
  **Role:** Accountant  
  **Permissions:** Financial management

### Librarian
- **Email:** librarian@school.com  
  **Password:** library123  
  **Role:** Librarian  
  **Permissions:** Book management

## API Testing

### Get Auth Token
```bash
curl -X POST http://localhost:8001/api/login \
  -H "Content-Type: application/json" \
  -d '{"email": "admin@school.com", "password": "password"}'
```

### Access Dashboard (Example with Token)
```bash
curl -X GET http://localhost:8001/api/admin/dashboard \
  -H "Authorization: Bearer YOUR_ACCESS_TOKEN" \
  -H "Accept: application/json"
```

## Notes
- All passwords are stored securely using bcrypt hashing
- Accounts are pre-configured with appropriate roles and permissions
- Test data is automatically seeded when running the development server
- For security, change these passwords in a production environment
