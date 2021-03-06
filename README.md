# sw-fw-less

>## Description
Swoole http server without framework

>## Features
1. Faster Router
2. Router & Global Middleware
3. Model(MySQL & Eleasticsearch、Json Serializable、Array Accessable)
4. Query Builder(MySQL & Elasticsearch)
5. Connection Pool(MySQL、Redis、Experimental AMQP-0-9-1、Experimental HBase)
6. Storage(File、Qiniu、Alioss)
7. Full Coroutine
8. Log(Based on Monolog)
9. Distributed Lock(Based on Redis)
10. Parameter Validator
11. Monitor
12. AMQP-0-9-1
13. Zipkin Trace
14. Dynamic Fault Injection
15. Hot Reload(including biz code、config、router, recommended for dev only)
16. Experimental Grpc(must open http2)
17. Apollo Config Center

>## Notice
* Don't include io operation in controller or middleware constructor
* Don't open preemptive scheduler

>## Composer Package Review Suggestion
* Namespace conflicts
* Duplication of functions without namespace

>## Requirements
* Composer 1.x
* PHP 7.1+
* Swoole 4.2.10+

>## Installation
```shell
composer create-project luoxiaojun/sw-fw-less-app=dev-master sw-fw-less --prefer-dist -vvv
```

>## Deployment
### Nginx
Nginx Config Demo(Modify according to your requirements)
```shell
server {
    listen 80;
    
    ## Modify according to your requirements
    server_name www.sw-fw-less.dev;

    location / {
        ## Modify according to your requirements
        proxy_pass http://127.0.0.1:9501;
    }
}
```
### Docker
```shell
docker run -d -P luoxiaojun1992/sw-fw-less:latest
```

>## Usage
Start Server
```php
php start.php
```

Demo Api
```shell
curl -i 'http://127.0.0.1:9501/ping'
```

>### Grpc Generator

```shell
cd tools && ./generate_grpc.sh path/to/grpc/bins/opt/grpc_php_plugin
```

>## Performance
Environment:
* OS: MacOS 10.14.1
* CPU: 2.3 GHz Intel Core i5 4 Cores
* Memory: 16 GB 2133 MHz LPDDR3
* Swoole: 4.2.9
* PHP: 7.2.8
* Redis: 4.0.11
* API: http://127.0.0.1:9501/redis?key=key
* Concurrent: 300
* Tool: JMeter 4.0 r1823414

Result:
![Load Testing](./docs/load_test.jpg)

>## Document
Please see [document](https://sw-fw-less.gitbook.io).

>## Roadmap
* Add helper functions namespace
