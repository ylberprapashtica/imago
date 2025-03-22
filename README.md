# Image Search Application

A Laravel application that provides image search functionality using Elasticsearch. The application allows users to search through a collection of images using various criteria such as title, tags, and dimensions.

## Features

- **Image Search**: Search through images using Elasticsearch for efficient and fast results.
- **Image Management**: Upload, view, and manage images with associated metadata.
- **Tagging System**: Add tags to images for better categorization and searchability.
- **Responsive Design**: A modern, responsive UI built with Laravel Blade and Tailwind CSS.

## Prerequisites

- Docker and Docker Compose installed on your machine.
- Git for cloning the repository.

## Installation

1. **Environment Setup:**
   Copy the `.env.example` file to `.env`:
     ```bash
     cp .env.example .env
     ```
   Update the `.env` file with your configuration settings.

2. **Start the Application:**
   ```bash
   ./setup.sh
   ```

## Microservices

- **Web Application:** `http://localhost:8000`
- **Frontend Server:** `http://localhost:5173`
- **Elasticsearch:** `http://localhost:9200`, (Since in this task the elasticsearch is not part of the network, this is not used)
- **MySQL:** Available on port `3306`
- **Kibana:** `http://localhost:5601`

## Credentials
- User: `admin@imago.com`
- Password: `iamgo`

## Development

- The application uses Laravel for the backend and Blade with Tailwind CSS for the frontend.
- Elasticsearch is used for efficient image search functionality.
- The application is containerized using Docker for easy setup and deployment.

## Debugging
Logging can be found in storage/logs and I have created two vs code lunch configurations, so the appication can be debugged either through frontend or backend.

## Run Tests
   ```bash
   docker compose exec backend php artisan test
   ```

## My two cents:

The entire application is containerized with Docker to ensure scalability and maintainability. Docker provides consistent environments across development, testing, and production, simplifying dependency and configuration management. This setup enables horizontal scaling and allows independent updates of components, reducing conflicts.

To keep the system maintainable as more providers and media types are added, I’ve built a scalable, Dockerized architecture. Nginx runs in multiple instances as a load balancer, efficiently handling high data traffic.

Elasticsearch is ideal for handling large data volumes, supporting horizontal scaling through a multi-node architecture. Its use of shards and replicas (Lucene indexes) across nodes boosts performance and fault tolerance.

The "suchtext" field currently holds too much information. I would restructure it into separate fields like:
- Event Title  
- Person(s) Present  
- Event Context  
- Date and Location  
- Photo Credit  
- Copyright Notice  

This improves indexing and search. Elasticsearch’s NLP features can further enhance search functionality.

I also maintain automated tests that to ensure stability as the system evolves.

So far, I’ve tested the application on macOS only.