# Redis Queue Bundle

All in one Redis based messages queue and worker supervisor. With GUI on board


## Usage

- In Symfony app

Configure redis connection and options service in config.yml

```yaml
snc_redis:
    clients:
        options:
            type: predis
            alias: options
            dsn: redis://localhost/1
            logging: false

services:
    r_options:
        class:        Sunra\RedisOptions
        arguments:    [@snc_redis.options]
```



## Installation

- Use Composer (recommended):

  - add to app's composer.json:
 
```json
"require": {
    "sunra/redis-options":"dev-master"
    },
"repositories": [
        {
            "type": "git",
            "url": "https://github.com/sunra/RedisOptions"
        }
    ],
```


- clone via git