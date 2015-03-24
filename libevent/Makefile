all:server client timer signal

server:server.c
	cc $^ -o $@ -levent

client:client.c

timer:timer.c
	cc $^ -o $@ -levent

signal:signal.c
	cc $^ -o $@ -levent
	
clean:
	rm -f server client timer signal *.out

.PHONY: all clean