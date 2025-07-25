# Search Engine API

Bu proje, PHP Symfony framework'ü ve MySQL veritabanı kullanarak geliştirilmiş bir arama motoru API'sidir. Docker kullanarak kolayca çalıştırılabilir.

## Özellikler

- **Full-text arama**: MySQL FULLTEXT indeksleri kullanarak hızlı arama
- **Gelişmiş filtreleme**: Kategori, etiket, tarih filtrelemeleri
- **RESTful API**: Modern REST API standartlarına uygun
- **API Dokumentasyonu**: OpenAPI/Swagger entegrasyonu
- **Arama analitikleri**: Arama sorgularının loglanması ve istatistikleri
- **Dockerize edilmiş**: Kolay kurulum ve deployment
- **CORS desteği**: Frontend entegrasyonu için

## Teknolojiler

- **PHP 8.2**
- **Symfony 7.0**
- **MySQL 8.0**
- **Docker & Docker Compose**
- **Nginx**
- **PHPMyAdmin**

## Kurulum

### Gereksinimler
- Docker
- Docker Compose

### Adımlar

1. **Projeyi klonlayın**
```bash
git clone <repository-url>
cd API
```

2. **Docker container'ları başlatın**
```bash
docker-compose up -d
```

3. **Composer bağımlılıklarını yükleyin**
```bash
docker-compose exec app composer install
```

4. **Veritabanı migration'larını çalıştırın**
```bash
docker-compose exec app php bin/console doctrine:migrations:migrate
```

## API Endpoints

### Arama Endpoints

- `GET /api/search` - Dokuman arama
- `GET /api/search/suggestions` - Arama önerileri
- `GET /api/search/popular` - Popüler arama sorguları
- `GET /api/search/categories` - Popüler kategoriler
- `GET /api/search/tags` - Mevcut etiketler
- `GET /api/search/stats` - Arama istatistikleri

### Dokuman Endpoints

- `GET /api/documents` - Tüm dokomanları listele
- `GET /api/documents/{id}` - Belirli dokomanı getir
- `POST /api/documents` - Yeni dokoman oluştur
- `PUT /api/documents/{id}` - Dokomanı güncelle
- `DELETE /api/documents/{id}` - Dokomanı sil
- `GET /api/documents/category/{category}` - Kategoriye göre dokomanlar

## Arama Parametreleri

### Temel Arama
```
GET /api/search?q=symfony&page=1&limit=20
```

### Filtreleme
```
GET /api/search?q=framework&category=Technology&tags=php,web&date_from=2024-01-01&date_to=2024-12-31
```

## Örnek Kullanım

### Dokuman Oluşturma
```bash
curl -X POST http://localhost:8080/api/documents \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Yeni Dokuman",
    "content": "Bu bir test dokomanıdır.",
    "category": "Test",
    "tags": ["test", "api"],
    "url": "https://example.com"
  }'
```

### Arama Yapma
```bash
curl "http://localhost:8080/api/search?q=symfony&category=Framework"
```

## Servisler

Proje aşağıdaki servisleri içerir:

- **API**: http://localhost:8080
- **PHPMyAdmin**: http://localhost:8081
- **MySQL**: localhost:3307

## Veritabanı

### Tablolar

1. **documents**: Arama yapılacak dokomanlar
   - id, title, content, url, category, tags, created_at, updated_at
   
2. **search_queries**: Arama sorguları logu
   - id, query_text, filters, results_count, execution_time, ip_address, user_agent, created_at

### Örnek Veriler

Proje başlatıldığında otomatik olarak örnek veriler yüklenir.

## API Dokumentasyonu

API dokumentasyonuna şu adresten erişebilirsiniz:
http://localhost:8080/api/doc

## Geliştirme

### Yeni Migration Oluşturma
```bash
docker-compose exec app php bin/console make:migration
```

### Migration Çalıştırma
```bash
docker-compose exec app php bin/console doctrine:migrations:migrate
```

### Cache Temizleme
```bash
docker-compose exec app php bin/console cache:clear
```

## Performans

- **Full-text indeksler**: Hızlı metin arama
- **Veritabanı indeksleri**: Kategori ve tarih filtrelemesi için
- **Pagination**: Büyük sonuç setleri için
- **Query optimizasyonu**: Efficient SQL sorguları

## Güvenlik

- **Input validation**: Symfony Validator kullanarak
- **SQL Injection koruması**: Doctrine ORM parametreleri
- **CORS politikaları**: Yapılandırılabilir
- **Rate limiting**: İsteğe bağlı implementasyon

## Test Etme

Container'ı başlattıktan sonra şu şekilde test edebilirsiniz:

```bash
# API durumunu kontrol et
curl http://localhost:8080/api/search

# Dokomanları listele
curl http://localhost:8080/api/documents

# Arama yap
curl "http://localhost:8080/api/search?q=symfony"
```

## Troubleshooting

### Container'lar başlamıyorsa:
```bash
docker-compose down
docker-compose up --build
```

### Veritabanı bağlantı sorunu:
```bash
docker-compose exec mysql mysql -u search_user -p search_engine_db
```

### Log'ları kontrol etme:
```bash
docker-compose logs app
docker-compose logs mysql
```
