//
//  server.c
//  libevent
//
//  Created by sean on 14/10/31.
//  Copyright (c) 2014年 sean. All rights reserved.
//

#include <stdio.h>
#include <sys/types.h>
#include <sys/socket.h>
#include <netinet/in.h>
#include <arpa/inet.h>
#include <fcntl.h>
#include <unistd.h>
#include <string.h>
#include <event.h>
#include <stdlib.h>
#include "socket.h"

void on_accept(int , short , void* );
void on_read(int, short, void*);

enum{
    false=0,true
};

struct event_base* base;

void on_accept(int sock, short event, void* arg)
{
    struct sockaddr_in cli_addr;
    int newfd;
    socklen_t sin_size;
    // read_ev must allocate from heap memory, otherwise the program would crash from segmant fault
    struct event* read_ev = (struct event*)malloc(sizeof(struct event));
    sin_size = sizeof(struct sockaddr_in);
    newfd = accept(sock, (struct sockaddr*)&cli_addr, &sin_size);
    printf("accept client %s\n",inet_ntoa(cli_addr.sin_addr));
    send(newfd, "welcom to server\n", 21, 0);
    event_set(read_ev, newfd, EV_READ|EV_PERSIST, on_read, read_ev);
    event_base_set(base, read_ev);
    event_add(read_ev, NULL);
}

void on_write(int sock, short event, void* arg)
{
    char* buffer = (char*)arg;
    char response[MEM_SIZE];
    if( !strcasecmp(buffer, "hello") ){
        strcpy(response, "how do you do");
    }else{
        strcpy(response,"sorry");
    }
    printf("send:%s\n",response);
    send(sock,response, strlen(response), 0);

    
    free(buffer);
}

void on_read(int sock, short event, void* arg)
{
    struct event* write_ev;
    int size;
    
    char* buffer = (char*)malloc(MEM_SIZE);
    size = (int)recv(sock, buffer, MEM_SIZE, 0);
    buffer[size] = '\0';
    printf("receive data:%s, size:%d\n", buffer, size);
    if (size == 0) {
        buffer[0] = '\0';
        event_del((struct event*)arg);
        free(arg);
        close(sock);
        return;
    }
    write_ev = (struct event*) malloc(sizeof(struct event));;
    event_set(write_ev, sock, EV_WRITE, on_write, buffer);
    event_base_set(base, write_ev);
    event_add(write_ev, NULL);
}

int main(int argc, char *argv[])
{
    int server_sockfd;//服务器端套接字
    struct sockaddr_in my_addr;   //服务器网络地址结构体
    
    memset(&my_addr,0,sizeof(my_addr)); //数据初始化--清零
    my_addr.sin_family=AF_INET; //设置为IP通信
    my_addr.sin_addr.s_addr=INADDR_ANY;//服务器IP地址--允许连接到所有本地地址上
    my_addr.sin_port=htons(8000); //服务器端口号
    
    /*创建服务器端套接字--IPv4协议，面向连接通信，TCP协议*/
    if((server_sockfd=socket(PF_INET,SOCK_STREAM,0))<0){
        perror("socket");
        return 1;
    }
    
    /*将套接字绑定到服务器的网络地址上*/
    if (bind(server_sockfd,(struct sockaddr *)&my_addr,sizeof(struct sockaddr))<0){
        perror("bind");
        return 1;
    }
    
    /*监听连接请求--监听队列长度为5*/
    listen(server_sockfd,5);
    
    struct event listen_ev;
    base = event_base_new();
    event_set(&listen_ev, server_sockfd, EV_READ|EV_PERSIST, on_accept, NULL);
    event_base_set(base, &listen_ev);
    event_add(&listen_ev, NULL);
    event_base_dispatch(base);
 
    return 0;
}