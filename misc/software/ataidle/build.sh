#!/usr/bin/env bash

build_ataidle() {
	cd /usr/ports/sysutils/ataidle

	make clean
	make

	return 0
}

install_ataidle() {
	cd /usr/ports/sysutils/ataidle

	install -vs work/ataidle*/ataidle $FREENAS/usr/local/sbin

	return 0
}
