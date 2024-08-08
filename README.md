# VelvetMessenger

VelvetMessenger is a PHP-based messaging application that uses the Slim Framework for routing and Symfony components for verification. It includes user authentication, profile management, and chat functionality. The application leverages Docker for environment setup and PostgreSQL as the database.

## Table of Contents

    1. Introduction
    2. Features
    3. Requirements
    4. Installation
    5. Usage

## Introduction

VelvetMessenger is a real-time messaging application designed for easy integration and scalability. It is built with PHP and utilizes the Slim Framework for its routing and HTTP handling needs, along with Symfony components for verification. The project aims to provide a robust and efficient solution for user communication.

## Features

    - User Authentication
    - Profile Management
    - Real-Time Chat Functionality
    - Dockerized Setup
    - PostgreSQL Database Support

## Requirements

    - PHP 7.4 or higher
    - Composer
    - Docker and Docker Compose
    - PostgreSQL

## Installation

### Clone the Repository
```bash
git clone https://github.com/smileska/velvetmessenger.git
cd velvetmessenger
```
### Setup Docker
```bash
docker-compose up -d
```
### Install PHP Dependencies
1. Access the Container's Shell:
```bash
docker exec -it velvetmessenger-app /bin/bash
```
2. Run Composer Install:
```bash
composer install
```
3. Switch to the postgres User:
```bash
su - postgres
```
4. Create the Database:
```bash
psql -U example -d velvet -f /docker-entrypoint-initdb.d/init.sql
```
This will execute the SQL file that initializes your database with the required tables and data.

## Usage

### Start the Application
With Docker running, you can start the application by navigating to http://localhost:8000 in your browser.

