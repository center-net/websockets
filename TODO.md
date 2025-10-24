# TODO: Configure Redis for Laravel Project

## Steps to Complete

- [x] Edit config/database.php: Update 'default' and 'cache' Redis connections with Upstash details (host=tls://crisp-hornet-17963.upstash.io, port=6379, password=AUYrAAIncDJjMWNkMGMzMTM0MGU0NWExYTQzYmM3YzgzNjU0MmQ5OHAyMTc5NjM, cache DB=0)
- [x] Edit config/cache.php: Set default cache driver to 'redis'
- [x] Edit config/session.php: Set session driver to 'redis'
- [x] Edit config/queue.php: Set default queue connection to 'redis'
- [x] Run php artisan config:clear && php artisan config:cache to clear and cache configuration
- [x] Test Redis connection if needed (optional)
