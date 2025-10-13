Email: admin@school.com
Password: password



 curl -X GET http://localhost:8001/api/admin/dashboard   -H "Authorization: Bearer 46|cVjrF5SABG1pKJ0YZUg8ZjqZQlGCPNAjM60WtTovdcd0151d"   -H "Accept: application/json"


curl -X POST http://localhost:8001/api/login   -H "Content-Type: application/json"   -d '{"email": "admin@school.com", "password": "password"}'
