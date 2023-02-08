FROM mcr.microsoft.com/vscode/devcontainers/php:latest

# Install dependencies
RUN apt-get update && apt-get install -y \
  fish \
  iputils-ping \
  libnss3-tools \
  nmap \
  postgresql-client \
  vim \
  && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pgsql

# Replace shell with fish
RUN sed -i -e "s/bin\/ash/usr\/bin\/fish/" /etc/passwd
ENV SHELL /usr/bin/fish
