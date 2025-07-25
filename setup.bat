@echo off
echo Starting Search Engine API...

REM Build and start Docker containers
echo Building and starting Docker containers...
docker-compose up --build -d

REM Wait for containers to be ready
echo Waiting for containers to be ready...
timeout /t 15 /nobreak > nul

REM Run database migrations
echo Running database migrations...
docker-compose exec -T app php bin/console doctrine:migrations:migrate --no-interaction

REM Clear cache
echo Clearing cache...
docker-compose exec -T app php bin/console cache:clear

echo Setup completed successfully!
echo.
echo API: http://localhost:8080
echo PHPMyAdmin: http://localhost:8081
echo API Documentation: http://localhost:8080/api/doc
echo.
echo For testing:
echo curl http://localhost:8080/api/search
echo curl http://localhost:8080/api/documents

pause
