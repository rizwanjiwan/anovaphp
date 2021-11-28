#!/bin/sh
docker run --rm --mount src="$(pwd)",target=/app,type=bind -ti rizwanjiwan/consoleapp:1.0 /bin/bash