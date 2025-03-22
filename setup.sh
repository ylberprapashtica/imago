#!/bin/bash

# Print colorful messages
print_message() {
    echo -e "\033[1;34m==>\033[0m $1"
}

print_error() {
    echo -e "\033[1;31mError:\033[0m $1"
}

print_success() {
    echo -e "\033[1;32mSuccess:\033[0m $1"
}

# Check if Docker is installed
if ! command -v docker &> /dev/null; then
    print_error "Docker is not installed. Please install Docker first."
    exit 1
fi

# Check if Docker Compose is installed
if ! command -v docker-compose &> /dev/null; then
    print_error "Docker Compose is not installed. Please install Docker Compose first."
    exit 1
fi

# Step 1: Environment Setup
print_message "Setting up environment..."
if [ ! -f .env ]; then
    if [ -f .env.example ]; then
        cp .env.example .env
        print_success "Created .env file from .env.example"
    else
        print_error ".env.example file not found"
        exit 1
    fi
else
    print_message ".env file already exists"
fi

# Step 2: Start the Application
print_message "Starting the application..."
docker compose up -d

# Wait for containers to be ready
print_message "Waiting for containers to be ready..."
sleep 10

# Step 3: Install Dependencies
print_message "Installing PHP dependencies..."
docker compose exec backend composer install

print_message "Installing Node.js dependencies..."
docker compose exec frontend npm install

# Step 4: Run Migrations
print_message "Running database migrations..."
docker compose exec backend php artisan migrate

# Step 5: Run Seeders
print_message "Running database seeders..."
docker compose exec backend php artisan db:seed

# Step 6: Run restart
print_message "Restarting the application..."
docker compose down 
docker compose up -d 

print_success "Setup completed successfully!"

print_message "You can now access the application at:"
print_message "Web Application:http://localhost:8000"
print_message "Default credentials:"
print_message "User: admin@imago.com"
print_message "Password: imago" 