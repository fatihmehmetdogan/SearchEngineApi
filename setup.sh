#!/bin/bash

echo "🚀 Arama Motoru API'sini başlatıyor..."

# Docker container'ları başlat
echo "📦 Docker container'ları başlatılıyor..."
docker-compose up -d

# Container'ların hazır olmasını bekle
echo "⏳ Container'ların hazır olması bekleniyor..."
sleep 10

# Composer bağımlılıklarını yükle
echo "📚 Composer bağımlılıkları yükleniyor..."
docker-compose exec -T app composer install --no-interaction

# Veritabanı migration'larını çalıştır
echo "🗄️ Veritabanı migration'ları çalıştırılıyor..."
docker-compose exec -T app php bin/console doctrine:migrations:migrate --no-interaction

# Cache'i temizle
echo "🧹 Cache temizleniyor..."
docker-compose exec -T app php bin/console cache:clear

echo "✅ Kurulum tamamlandı!"
echo ""
echo "🌐 API: http://localhost:8080"
echo "📊 PHPMyAdmin: http://localhost:8081"
echo "📖 API Dokumentasyonu: http://localhost:8080/api/doc"
echo ""
echo "Test için:"
echo "curl http://localhost:8080/api/search"
echo "curl http://localhost:8080/api/documents"
