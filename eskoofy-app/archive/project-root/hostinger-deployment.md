# Hostinger Deployment Guide for Your Full-Stack Application

This guide provides step-by-step instructions for deploying your full-stack application to Hostinger. This guide assumes you have a Hostinger account and a domain name.

## 1. Prerequisites

Before you begin, ensure you have the following:

- A Hostinger account with a hosting plan that supports Node.js and SSH access.
- A registered domain name.
- Your application code pushed to a Git repository (e.g., GitHub, GitLab).

## 2. Setting Up Your Hostinger Environment

1.  **Log in to your Hostinger account.**
2.  **Enable SSH access:**
    - Go to your hosting dashboard.
    - Find the "SSH Access" section and enable it.
    - You will get your SSH credentials (host, username, password, and port). Keep these handy.
3.  **Connect to your server via SSH:**
    - Open a terminal or an SSH client.
    - Use the following command to connect:

      ```bash
      ssh username@your_domain_or_ip -p your_ssh_port
      ```

      Replace `username`, `your_domain_or_ip`, and `your_ssh_port` with your actual credentials.

## 3. Deploying the Backend

1.  **Clone your repository:**

    ```bash
    git clone https://github.com/your_username/your_repository.git
    ```

2.  **Navigate to the backend directory:**

    ```bash
    cd your_repository/backend
    ```

3.  **Install dependencies:**

    ```bash
    npm install
    ```

4.  **Create a `.env` file:**

    - Create a `.env` file in the `backend` directory.
    - Add the necessary environment variables, such as database credentials, JWT secrets, etc. For example:

      ```
      DB_HOST=localhost
      DB_USER=your_db_user
      DB_PASSWORD=your_db_password
      DB_NAME=your_db_name
      JWT_SECRET=your_jwt_secret
      ```

5.  **Set up the database:**

    - In your Hostinger control panel, create a new MySQL database.
    - Note down the database name, username, and password.
    - Update your `.env` file with the correct database credentials.

6.  **Run database migrations (if applicable):**

    If your application uses migrations to set up the database schema, run the migration command. For example:

    ```bash
    npm run migrate
    ```

7.  **Start the backend server:**

    You can use a process manager like PM2 to keep your backend server running.

    ```bash
    npm install -g pm2
    pm2 start your_main_backend_file.js --name "backend-app"
    ```

    Replace `your_main_backend_file.js` with the entry file of your backend application (e.g., `server.js`, `app.js`).

## 4. Deploying the Frontend

1.  **Navigate to the frontend directory:**

    ```bash
    cd ../frontend
    ```

2.  **Install dependencies:**

    ```bash
    npm install
    ```

3.  **Create a `.env` file:**

    - Create a `.env` file in the `frontend` directory.
    - Add the backend API URL. For example:

      ```
      VITE_API_URL=http://your_domain_or_ip:your_backend_port
      ```

      Replace `your_domain_or_ip` and `your_backend_port` with your actual backend URL and port.

4.  **Build the frontend:**

    ```bash
    npm run build
    ```

    This will create a `dist` directory with the optimized static files.

5.  **Serve the frontend:**

    - You can use a simple static server like `serve` to serve the frontend.

      ```bash
      npm install -g serve
      serve -s dist -l 3000
      ```

    - Alternatively, you can configure your web server (e.g., Nginx) to serve the files from the `dist` directory.

## 5. Configuring the Web Server (Nginx)

Hostinger often uses Nginx as a web server. You can configure it to act as a reverse proxy for your backend and serve your frontend.

1.  **Create a new Nginx configuration file:**

    ```bash
    sudo nano /etc/nginx/sites-available/your_domain
    ```

2.  **Add the following configuration:**

    ```nginx
    server {
        listen 80;
        server_name your_domain www.your_domain;

        location / {
            proxy_pass http://localhost:3000; # Your frontend server
            proxy_http_version 1.1;
            proxy_set_header Upgrade $http_upgrade;
            proxy_set_header Connection 'upgrade';
            proxy_set_header Host $host;
            proxy_cache_bypass $http_upgrade;
        }

        location /api {
            proxy_pass http://localhost:your_backend_port; # Your backend server
            proxy_http_version 1.1;
            proxy_set_header Upgrade $http_upgrade;
            proxy_set_header Connection 'upgrade';
            proxy_set_header Host $host;
            proxy_cache_bypass $http_upgrade;
        }
    }
    ```

    - Replace `your_domain` with your actual domain name.
    - Replace `your_backend_port` with the port your backend is running on.

3.  **Enable the new configuration:**

    ```bash
    sudo ln -s /etc/nginx/sites-available/your_domain /etc/nginx/sites-enabled/
    ```

4.  **Test the Nginx configuration and restart the server:**

    ```bash
    sudo nginx -t
    sudo systemctl restart nginx
    ```

## 6. Pointing Your Domain to Hostinger

1.  **Log in to your domain registrar's website.**
2.  **Find the DNS settings for your domain.**
3.  **Update the nameservers to Hostinger's nameservers.** You can find Hostinger's nameservers in your hosting dashboard.
4.  **Wait for the DNS changes to propagate.** This can take up to 24 hours.

Once the DNS changes have propagated, your application should be live at your domain.

## 7. Using Docker (Alternative)

Since your project includes a `docker-compose.yml` file, you can also use Docker to deploy your application. This can simplify the deployment process.

1.  **Install Docker and Docker Compose on your Hostinger server.**

2.  **Navigate to your project's root directory.**

3.  **Build and run the containers:**

    ```bash
    docker-compose up -d --build
    ```

4.  **Configure Nginx as a reverse proxy** to forward requests to the appropriate containers, similar to the instructions in step 5.

This guide provides a general overview of the deployment process. You may need to adjust the steps based on your specific application's requirements.
