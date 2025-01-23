SHELL:=/bin/bash
VERSION=$(shell grep -o '^[0-9]\+\.[0-9]\+\.[0-9]\+' CHANGELOG.rst | head -n1)
FILENAME=komtetkassa-$(shell grep -o '^[0-9]\+\.[0-9]\+\.[0-9]\+' CHANGELOG.rst | head -n1).zip

# Colors
Color_Off=\033[0m
Red=\033[1;31m

help:
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}' $(MAKEFILE_LIST) | sort

version:  ## Версия проекта
	@echo -e "${Red}Version:${Color_Off} $(VERSION)";

start:  ## Запустить контейнер
	@docker-compose up --build
	
stop:  ## Остановить контейнер
	@docker-compose down

update:  ## Обновить модуль
	@cp -r -f app/addons/rus_komtet_kassa/. php/app/addons/rus_komtet_kassa/
	@cp -f var/langs/ru/addons/rus_komtet_kassa.po php/var/langs/ru/addons/

release:  ## Архивировать для загрузки в маркет
	@rm ${FILENAME} || echo "No file to remove"
	@zip -r ${FILENAME} app var --exclude=*docker_env*

.PHONY: version  release
.DEFAULT_GOAL := version
