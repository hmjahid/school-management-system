# Use an official Node.js runtime as a parent image
FROM node:20-alpine

# Set the working directory in the container
WORKDIR /usr/src/app

# Copy package.json and package-lock.json
COPY package*.json ./

# Install dependencies
RUN npm install

# Copy the rest of the application's source code
COPY . .

# Make port 5173 available to the world outside this container
EXPOSE 5173

# Run the Vite dev server
# The --host flag is important to expose the server to the Docker network
CMD ["npm", "run", "dev", "--", "--host"]
