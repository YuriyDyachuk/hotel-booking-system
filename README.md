# 🏨 Hotel Booking System

High-performance hotel booking system built with pure PHP and MySQL, designed for learning database optimization and query performance.

## 📊 System Scale

- **1,000** hotels across 40+ cities
- **50,000** rooms with different types
- **100,000** users
- **1,000,000** bookings
- **~300,000** reviews

## 🚀 Quick Start

### 1. Start Docker containers

```bash
docker-compose up -d
```

### 2. Wait for MySQL to be ready

```bash
docker-compose logs -f mysql
# Wait for "ready for connections"
```

### 3. Run migrations

```bash
docker exec hotel_php php cli/migrate.php
```

### 4. Seed database

```bash
# Seed all (takes 10-20 minutes for 1M bookings)
docker exec hotel_php php cli/seed.php

# Or seed specific tables
docker exec hotel_php php cli/seed.php countries
docker exec hotel_php php cli/seed.php users
docker exec hotel_php php cli/seed.php hotels
docker exec hotel_php php cli/seed.php rooms
docker exec hotel_php php cli/seed.php bookings
docker exec hotel_php php cli/seed.php reviews
```

### 5. Test queries

```bash
docker exec hotel_php php cli/query-test.php
```

## 🔧 Available Commands

### Migrations

```bash
# Run pending migrations
docker exec hotel_php php cli/migrate.php

# Show migration status
docker exec hotel_php php cli/migrate.php status

# Reset database (drop all tables)
docker exec hotel_php php cli/migrate.php reset

# Fresh migration (reset + run)
docker exec hotel_php php cli/migrate.php fresh
```

### Seeders

```bash
# Show available seeders
docker exec hotel_php php cli/seed.php --list

# Run all seeders
docker exec hotel_php php cli/seed.php

# Run specific seeder
docker exec hotel_php php cli/seed.php countries
docker exec hotel_php php cli/seed.php users
docker exec hotel_php php cli/seed.php bookings
```

### Query Testing

```bash
# Run test queries with EXPLAIN and profiling
docker exec hotel_php php cli/query-test.php
```

## 🗄️ Database Schema

### Main Tables

- `countries` - Countries directory
- `cities` - Cities with population data
- `hotels` - Hotels with ratings and details
- `room_types` - Room type classifications
- `rooms` - Individual rooms (50K records)
- `users` - System users (100K records)
- `bookings` - Reservations (1M records, partitioned by year)
- `reviews` - Hotel reviews with ratings
- `payments` - Payment transactions

### Key Features

- **Partitioning**: `bookings` table partitioned by year
- **Indexing**: Optimized composite indexes for search queries
- **Foreign Keys**: Full referential integrity
- **Generated Columns**: Automatic calculation (e.g., nights)
- **JSON Fields**: Flexible data storage (amenities)

## 📈 Performance Testing

The system includes comprehensive query testing:

1. **Simple SELECT** - Basic queries
2. **Complex JOINs** - Multi-table aggregations
3. **Available Rooms Search** - Date range conflicts
4. **Statistics** - Aggregation and grouping
5. **Top Users** - Heavy aggregation queries

All tests include:
- ✅ EXPLAIN analysis
- ⏱️ Execution time measurement
- 💾 Memory usage tracking
- 📊 Result preview

## 🐳 Docker Services

- **MySQL 8.0** - Database server (port 3306)
- **PHP 8.3 CLI** - Application runtime
- **phpMyAdmin** - Web UI (http://localhost:8081)
- **Nginx** - Web server (http://localhost:8080)

## 📦 Access Services

- **phpMyAdmin**: http://localhost:8081
    - Server: `mysql`
    - Username: `hotel_user`
    - Password: `hotel_pass`

- **MySQL Direct**:
  ```bash
  docker exec -it hotel_mysql mysql -u hotel_user -photel_pass hotel_booking
  ```

## 🔍 Query Logging

All query executions are logged with:
- SQL statement
- Execution time (ms)
- Memory usage (KB)
- Rows affected
- EXPLAIN analysis

Logs are saved to `logs/` directory.

## 📚 Learning Path

This project is designed for step-by-step learning:

1. **Start**: Basic SQL queries
2. **Optimize**: Add indexes, analyze EXPLAIN
3. **Scale**: Test with 1M records
4. **Advanced**: Partitioning, replication, caching

## 🛠️ Development

### Enter PHP container

```bash
docker exec -it hotel_php bash
```

### Run custom queries

```bash
docker exec -it hotel_php php -a
# Then in PHP interactive shell:
require 'src/Database/Connection.php';
$config = require 'src/config.php';
App\Database\Connection::init($config['database']);
$pdo = App\Database\Connection::getInstance();
```

## 📊 Expected Seeding Times

- Countries & Cities: ~1 second
- Room Types: <1 second
- Users (100K): ~30-60 seconds
- Hotels (1K): ~5-10 seconds
- Rooms (50K): ~60-120 seconds
- Bookings (1M): ~10-20 minutes
- Reviews: ~2-5 minutes

**Total**: ~15-30 minutes for full database

## 🎯 Next Steps

After setting up, you can:

1. Analyze query performance with EXPLAIN
2. Add custom indexes and measure improvement
3. Implement caching strategies
4. Test replication setup
5. Benchmark different query approaches

---

**Made for learning high-load systems** 🚀