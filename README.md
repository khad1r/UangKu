```docker-compose.yml
name: Pembukuan
services:
  app:
    image: khad1r/uangku:latest
    ports:
      - "80:80"
      - "443:443"
    init: true
    env:
      - HOSTNAME=localhost
    restart: unless-stopped
    network_mode: bridge
```
