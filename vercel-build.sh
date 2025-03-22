#!/bin/bash

# Install Node.js dependencies
echo "Installing Node.js dependencies..."
npm install

# Build frontend assets
echo "Building frontend assets..."
npm run build

# Create necessary directories
echo "Creating necessary directories..."
mkdir -p storage/framework/{sessions,views,cache}
mkdir -p bootstrap/cache

# Set proper permissions
echo "Setting permissions..."
chmod -R 775 storage bootstrap/cache 