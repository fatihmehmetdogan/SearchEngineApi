# Search Engine API

Bu proje, PHP Symfony framework'ü ve MySQL veritabanı kullanarak geliştirilmiş, güçlü bir arama motoru API'sidir. **Docker ve Docker Compose sayesinde kolayca ayağa kaldırılabilir ve yönetilebilir.**

---

## Proje Vizyonu ve Mimari

Bu API, farklı içerik sağlayıcılardan (mock olarak implemente edildi) gelen verileri birleştirerek, kullanıcının arama sorgusuna göre en uygun içerikleri bulan, bunları belirli kriterlere göre sıralayan ve sunan bir servis olarak tasarlandı. Projenin mimarisi, **modülerlik, genişletilebilirlik ve temiz kod** prensipleri üzerine inşa edilmiştir.

### Temel Özellikler
* **İçerik Arama ve Sıralama**: Anahtar kelimeye göre arama, içerik türüne (video/metin) göre filtreleme ve dinamik sıralama (popülerlik/alakalılık skoru, tarih, başlık).
* **İçerik Puanlama Algoritması**: Sağlayıcılardan gelen farklı formatlardaki verileri standart bir puan sistemine çeviren özel bir algoritma (`Final Skor` hesaplaması).
* **Provider Entegrasyonu**: JSON ve XML formatlarında iki farklı mock sağlayıcıdan veri alabilen, `ProviderInterface` ve `ProviderManager` deseni sayesinde yeni sağlayıcılar eklemeye uygun, genişletilebilir bir yapı.
* **Arama Analitikleri**: Kullanıcı arama sorgularının loglanması ve istatistiklerinin tutulması.
* **Basit Yönetim Dashboard'u**: API'nin üzerine geliştirilmiş, arama ve sonuçları listeleme imkanı sunan temel bir web arayüzü.
* **Dockerize Edilmiş**: Tüm servisler Docker konteynerlerinde çalışır, bu da kolay kurulum ve dağıtım (deployment) sağlar.

### Neden Bu Teknolojiler Tercih Edildi?

* **PHP 8.2 & Symfony 7.0**: Hızlı geliştirme süreçleri, **modüler ve katmanlı mimari** desteği, **Bağımlılık Enjeksiyonu (Dependency Injection)** prensibini doğal olarak kullanması, geniş geliştirici topluluğu ve güçlü araç setleri sayesinde API geliştirme için sağlam ve güvenilir bir temel sunar.
* **MySQL 8.0**: İlişkisel veri yönetimi için kanıtlanmış güvenilirliği, yüksek performansı ve yaygın kullanımı ile tercih edilmiştir. Özellikle arama ve filtreleme için güçlü indeksleme kabiliyetleri önemlidir.
* **Docker & Docker Compose**: Geliştirme ortamının **tutarlı ve izole** olmasını sağlar. Tüm bağımlılıklar (veritabanı, web sunucusu, PHP-FPM) konteynerler içinde çalıştırılarak "benim makinemde çalışıyordu" sorunlarını ortadan kaldırır ve kolay dağıtım imkanı sunar.
* **Nginx**: Yüksek performanslı, hafif bir web sunucusu olarak seçilmiştir. Gelen HTTP isteklerini etkili bir şekilde PHP-FPM'ye yönlendirir ve statik dosyaları hızlı servis eder.
* **PHPMyAdmin**: MySQL veritabanını web tabanlı bir arayüz üzerinden görsel olarak yönetmek, geliştirme ve hata ayıklama süreçlerini kolaylaştırmak için pratik bir araçtır.

---

## Kurulum ve Çalıştırma

### İşletim Sistemi Notu

Bu dokümantasyondaki tüm `docker compose` komutları **macOS ve Linux** sistemleri için geçerlidir.

* **Windows kullanıcıları** için Docker Desktop ve WSL2 (Windows Subsystem for Linux) kullanımı önerilir. WSL2 üzerinden çalışarak, dokümantasyondaki komutları doğrudan kullanabilirsiniz. Aksi takdirde, volume tanımlamaları ve bazı yol (path) farklılıkları nedeniyle `docker-compose.windows.yml` gibi özel bir yapılandırma dosyası gerekebilir.

### Gereksinimler
* **Docker Desktop**: macOS, Windows veya Linux için yüklü ve çalışır durumda olmalı.

### Adımlar

1.  **Projeyi Klonlayın**:
    Terminalinizi açın ve projenin GitHub deposunu klonlayın:
    ```bash
    git clone <repository-url>
    cd SearchEngineApi
    ```

2.  **Docker Ortamını Başlatın**:
    Tüm Docker servislerini (uygulama, veritabanı, web sunucusu) oluşturur ve arka planda başlatır. Bu adım, Docker imajlarını indirme ve inşa etme süreçlerini içerdiği için biraz zaman alabilir.
    ```bash
    docker compose up -d --build
    ```

3.  **Composer Bağımlılıklarını Yükleyin**:
    PHP konteyneri içinde Composer bağımlılıklarını kurun. Bu, Symfony ve diğer kütüphanelerin çalışması için gereklidir.
    ```bash
    docker compose exec app composer install
    ```

4.  **Veritabanı Migrasyonlarını Çalıştırın**:
    Uygulama için gerekli olan veritabanı tablolarını (`documents`, `search_queries`) oluşturur veya günceller.
    ```bash
    docker compose exec app php bin/console doctrine:migrations:migrate
    ```
    * Onay istendiğinde `yes` yazıp Enter tuşuna basın.

5.  **Örnek Verileri Yükleyin (Fixtures)**:
    Uygulamanın çalışır durumda olduğunu görmek için mock sağlayıcılardan (JSON ve XML) veri çeken, puanlayan ve veritabanına kaydeden örnek verileri yükler. Bu adım, API ve Dashboard'un veriyle çalışmasını sağlar.
    ```bash
    docker compose exec app php bin/console doctrine:fixtures:load --purge-with-truncate
    ```
    * Veritabanının temizleneceğine dair onay istendiğinde `yes` yazıp Enter tuşuna basın.

6.  **Cache Temizleyin**:
    Symfony'nin önbelleğini temizler. Bu, özellikle geliştirme sırasında veya yapılandırma değişikliklerinden sonra önemlidir.
    ```bash
    docker compose exec app php bin/console cache:clear
    ```

---

## Projeye Erişim

Tüm kurulum adımları başarıyla tamamlandıktan sonra projenize aşağıdaki adreslerden erişebilirsiniz:

* **Ana API Erişimi**: `http://localhost:8080/api/`
* **API Dokümantasyonu (Swagger UI)**: `http://localhost:8080/api/doc`
* **Arama Dashboard'u**: `http://localhost:8080/dashboard`
* **PHPMyAdmin**: `http://localhost:8081` (MySQL veritabanınızı web arayüzünden yönetmek için)

---

## API Endpoints

### Genel Bilgi Endpoints
* `GET /api/` - API'nin adı, sürümü ve genel endpoint listesi hakkında bilgi sağlar.
* `GET /api/health` - API servislerinin (API'nin kendisi, veritabanı bağlantısı gibi bağımlılıklar) sağlık durumunu kontrol eder.

### Arama ve Analitik Endpoints
* `GET /api/search` - Doküman arama, filtreleme ve sıralama için ana endpoint.
* `GET /api/search/suggestions` - Kullanıcının yazdığı kısmi sorguya göre arama önerileri sunar.
* `GET /api/search/popular` - Sistemde en çok yapılan popüler arama sorgularını listeler.
* `GET /api/search/categories` - Mevcut dokümanlardaki popüler kategorileri listeler.
* `GET /api/search/tags` - Dokümanlarda kullanılan tüm benzersiz etiketleri listeler.
* `GET /api/search/stats` - Genel arama istatistiklerini (toplam arama sayısı, ortalama süre vb.) sağlar.

### Doküman Yönetim Endpoints (CRUD - Create, Read, Update, Delete)
* `GET /api/documents` - Tüm dokümanları listeler.
* `GET /api/documents/{id}` - Belirli bir dokümanı ID'sine göre getirir.
* `POST /api/documents` - Yeni bir doküman oluşturur.
* `PUT /api/documents/{id}` - Mevcut bir dokümanı ID'sine göre günceller.
* `DELETE /api/documents/{id}` - Belirli bir dokümanı ID'sine göre siler.
* `GET /api/documents/category/{category}` - Belirli bir kategoriye ait dokümanları listeler.

---

## Arama Parametreleri (`GET /api/search`)

| Parametre   | Tip       | Açıklama                                                | Örnek Değer             | Varsayılan |
| :---------- | :-------- | :------------------------------------------------------ | :---------------------- | :--------- |
| `q`         | `string`  | Arama sorgusu (doküman başlığı ve içeriğinde aranır)    | `yeni doküman`          | `''`       |
| `type`      | `string`  | İçerik türüne göre filtreleme (`video` veya `text`)     | `video`                 | `''`       |
| `category`  | `string`  | Kategoriye göre filtreleme                              | `Programlama`           | `''`       |
| `tags`      | `string`  | Etiketlere göre filtreleme (virgülle ayrılmış)          | `symfony,api`           | `''`       |
| `page`      | `integer` | Sonuçların sayfa numarası                               | `2`                     | `1`        |
| `limit`     | `integer` | Her sayfada görüntülenecek sonuç sayısı                 | `10`                    | `20`       |
| `sort`      | `string`  | Sıralama kriteri (`finalScore`, `createdAt`, `title`, `type`) | `finalScore`            | `finalScore` |
| `order`     | `string`  | Sıralama yönü (`asc` - artan veya `desc` - azalan)      | `desc`                  | `desc`     |
| `date_from` | `string`  | YYYY-MM-DD formatında başlangıç yayın tarihi (dahil)    | `2024-01-01`            | `''`       |
| `date_to`   | `string`  | YYYY-MM-DD formatında bitiş yayın tarihi (dahil)        | `2024-12-31`            | `''`       |

---

## Örnek Kullanım

### Doküman Oluşturma (`POST /api/documents`)
```bash
curl -X POST http://localhost:8080/api/documents \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Yeni Doküman Başlığı",
    "content": "Bu, API üzerinden eklenen bir test dokümanıdır. Harika bir içerik.",
    "type": "text",
    "url": "[https://yeni-dokuman.com](https://yeni-dokuman.com)",
    "category": "API Geliştirme",
    "tags": ["symfony", "api", "test"]
  }'