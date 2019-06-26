FILENAME=komtetkassa-$(shell grep -o '^[0-9]\+\.[0-9]\+\.[0-9]\+' CHANGELOG.rst | head -n1).zip

release:
	@rm ${FILENAME} || echo "No file to remove"
	@zip -r ${FILENAME} app var --exclude=*docker_env*