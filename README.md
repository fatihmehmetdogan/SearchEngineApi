# Search Engine API

Bu proje, PHP Symfony framework'ü ve MySQL veritabanı kullanarak geliştirilmiş, güçlü bir arama motoru API'sidir. Docker ve Docker Compose sayesinde kolayca ayağa kaldırılabilir ve yönetilebilir.

## Proje Özellikleri

Bu API, farklı içerik sağlayıcılardan (mock olarak implemente edildi) gelen verileri birleştirerek, kullanıcıların arama sorgularına göre en uygun içerikleri bulan, bunları belirli kriterlere göre sıralayan ve sunan bir servis olarak tasarlandı.

## Temel Özellikler
- **İçerik Arama ve Sıralama:**: Anahtar kelimeye göre arama, içerik türüne (video/metin) göre filtreleme ve dinamik sıralama (popülerlik/alakalılık skoru, tarih, başlık).
- **İçerik Puanlama Algoritması**: Sağlayıcılardan gelen farklı formatlardaki verileri standart bir puan sistemine çeviren özel bir algoritma (Final Skor hesaplaması).
- **Provider Entegrasyonu**: JSON ve XML formatlarında iki farklı mock sağlayıcıdan veri alabilen, genişletilebilir bir yapı.
- **Arama Analitikleri**: Arama sorgularının loglanması ve istatistiklerinin tutulması.
- **Basit Yönetim Dashboard'u**: API'nin üzerine geliştirilmiş, arama ve sonuçları listeleme imkanı sunan temel bir web arayüzü.
- **Dockerize edilmiş**: Kolay kurulum ve deployment

## Neden Bu Teknolojiler Tercih Edildi?

- **PHP 8.2 & Symfony 7.0**: Hızlı geliştirme, modüler yapı, geniş topluluk desteği ve güçlü bağımlılık enjeksiyon (Dependency Injection) özellikleriyle API geliştirme için sağlam bir temel sunar. 
- **MySQL 8.0**: İlişkisel veri yönetimi için güvenilir, performant ve yaygın kullanılan bir veritabanıdır. Özellikle arama ve filtreleme için indeksleme kabiliyetleri önemlidir.
- **Docker & Docker Compose**: Geliştirme ortamının tutarlı ve kolayca kurulabilir olmasını sağlar. Bağımlılıkların (veritabanı, web sunucusu, PHP-FPM) izole bir şekilde çalışmasına olanak tanır.
- **Nginx**: Yüksek performanslı bir web sunucusu olarak, gelen istekleri PHP-FPM'ye yönlendirir ve statik dosyaları hızlı servis eder.
- **PHPMyAdmin**: MySQL veritabanını görsel bir arayüz üzerinden kolayca yönetmek için kullanılır.

## Kurulum ve Çalıştırma

### İşletim Sistemi Notu


Bu dokümantasyondaki tüm docker compose komutları macOS ve Linux sistemleri için geçerlidir.
Windows kullanıcıları için volume tanımlamaları ve bazı yol (path) farkları nedeniyle docker-compose.windows.yml gibi özel bir dosya gerekebilir.

Eğer Windows kullanıyorsanız:

- docker-compose.windows.yml gibi özel bir yapılandırma dosyası gerekebilir.
- Alternatif olarak WSL2 (Windows Subsystem for Linux) üzerinden çalışarak, dokümantasyondaki komutları doğrudan kullanabilirsiniz.


### Gereksinimler
- **Docker & Docker Desktop**: macOS, Windows veya Linux için yüklü ve çalışır durumda olmalı.

### Adımlar

1. **Projeyi klonlayın**
```bash
git clone <repository-url>
cd API
```

2. **Docker container'ları başlatın**
```bash
docker compose up -d --build
```

3. **Composer bağımlılıklarını yükleyin**
```bash
docker compose exec app composer install
```

4. **Veritabanı migration'larını çalıştırın**
```bash
docker compose exec app php bin/console doctrine:migrations:migrate
```

5. **Örnek Verileri Yükleyin (Fixtures)**
- Uygulamanın çalışır durumda olduğunu görmek için mock sağlayıcılardan veri çeken ve puanlayan örnek verileri veritabanına yükleyin.
```bash
docker compose exec app php bin/console doctrine:fixtures:load --purge-with-truncate
```

5. **Cache Temizleyin**
```bash
docker compose exec app php bin/console cache:clear
```

## Projeye Erişim

- **Ana API Erişimi**: http://localhost:8080/api/
- **API Dokümantasyonu (Swagger UI)**: http://localhost:8080/api/doc
- **Arama Dashboard'u**: http://localhost:8080/dashboard
- **PHPMyAdmin**: http://localhost:8081 (MySQL veritabanınızı yönetmek için)

## API Endpoints

### Genel API Bilgisi

- `GET /api/` - API'nin adı, sürümü ve genel endpoint listesi.
- `GET /api/health` - API servislerinin (API, veritabanı) sağlık durumu kontrolü.
- `GET /api/search/popular` - Popüler arama sorguları
- `GET /api/search/categories` - Popüler kategoriler
- `GET /api/search/tags` - Mevcut etiketler
- `GET /api/search/stats` - Arama istatistikleri

### Arama Endpoints

- `GET /api/search` - Doküman arama, filtreleme ve sıralama.
- `GET /api/search/suggestions` - Arama önerileri.
- `GET /api/search/popular` - Popüler arama sorguları listesi.
- `GET /api/search/categories` - Popüler kategoriler listesi.
- `GET /api/search/tags` - Mevcut tüm etiketler listesi.
- `GET /api/search/stats` - Genel arama istatistikleri.

### Doküman Yönetim Endpoints (CRUD)

- `GET /api/documents` - Tüm dokümanları listeleme.
- `GET /api/documents/{id}` - Belirli bir dokümanı ID'sine göre getirme.
- `POST /api/documents` - Yeni doküman oluşturma.
- `PUT /api/documents/{id}` - Mevcut bir dokümanı ID'sine göre güncelleme.
- `DELETE /api/documents/{id}` - Belirli bir dokümanı ID'sine göre silme.
- `GET /api/documents/category/{category}` - Kategoriye göre dokümanları listeleme.

## Arama Parametreleri (GET /api/search)

| Parametre  | Tip     | Açıklama                                               | Örnek Değer      | Varsayılan     |
|------------|---------|--------------------------------------------------------|------------------|----------------|
| `q`        | string  | Arama sorgusu (başlık ve içerikte aranır)             | test document    | `''`           |
| `type`     | string  | İçerik türüne göre filtrele (`video` veya `text`)      | video            | `''`           |
| `category` | string  | Kategoriye göre filtrele                              | Technology       | `''`           |
| `tags`     | string  | Etiketlere göre filtrele (virgülle ayrılmış)          | php,backend      | `''`           |
| `page`     | integer | Sayfa numarası                                         | 2                | `1`            |
| `limit`    | integer | Sayfa başına sonuç sayısı                              | 10               | `20`           |
| `sort`     | string  | Sıralama kriteri (`finalScore`, `createdAt`, `title`, `type`) | finalScore | `finalScore`    |
| `order`    | string  | Sıralama yönü (`asc` veya `desc`)                     | desc             | `desc`         |
| `date_from`| string  | Başlangıç tarihi (YYYY-MM-DD formatında, dahil)       | 2024-01-01       | `''`           |
| `date_to`  | string  | Bitiş tarihi (YYYY-MM-DD formatında, dahil)           | 2024-12-31       | `''`           |

## Örnek Kullanım

### Doküman Oluşturma (POST /api/documents)
```bash
curl -X POST http://localhost:8080/api/documents \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Yeni Doküman Başlığı",
    "content": "Bu, API üzerinden eklenen bir test dokümanıdır. Harika bir içerik.",
    "type": "text",
    "url": "https://yeni-dokuman.com",
    "category": "API Geliştirme",
    "tags": ["symfony", "api", "test"]
  }'
```

### Arama Yapma
```bash
# Sadece anahtar kelime ile arama
curl "http://localhost:8080/api/search?q=test"

# Anahtar kelime ve tür filtresi ile arama
curl "http://localhost:8080/api/search?q=document&type=video"

# Kategori ve sayfalama ile arama
curl "http://localhost:8080/api/search?category=Technology&page=2&limit=5"

# Etiket, tarih aralığı ve sıralama ile arama
curl "http://localhost:8080/api/search?tags=api,backend&date_from=2024-01-01&date_to=2024-07-30&sort=createdAt&order=asc"
```

