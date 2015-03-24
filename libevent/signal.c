//
//  signal.c
//  libevent
//  gcc signal.c -o signal -I/usr/local/include -L/usr/local/lib/ -levent
//  Created by sean on 14/10/31.
//  Copyright (c) 2014年 sean. All rights reserved.
//
#include <event.h>
#include <signal.h>
#include <sys/time.h>

int called = 0;

static void signal_cb(int fd, short event, void *arg){
    struct event *signal = arg;
    printf("%s: got signal %d\n", __func__, EVENT_SIGNAL(signal));
    if (called >= 2)
        event_del(signal); //如果调用了两次以上，就删除这个信号
    called++;
}

int main (int argc, char **argv){
    struct event signal_int;
    struct event_base *base = event_base_new();//初始化libevent库
    event_set(&signal_int, SIGINT, EV_SIGNAL|EV_PERSIST, signal_cb, &signal_int);
    //base set一定要在evtimer_set之后
    event_base_set(base,&signal_int);
    
    //设置事件属性为信号触发、持续,回调函数为con_accept()
    event_add(&signal_int, NULL); //添加事件
    event_base_dispatch(base);//进入libevent主循环
    
    return 0;
}
