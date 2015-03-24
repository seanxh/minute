//  timer.c
// gcc timer.c -o timer -I/usr/local/include -L/usr/local/lib/ -levent
//  Created by sean on 14-10-30.
//  Copyright (c) 2014年 sean. All rights reserved.
//

#include <event.h>
#include <sys/time.h>

struct event ev; //事件
struct timeval tv; //定时器


/*事件处理函数,cb=callback*/
void time_cb(int fd,short _event,void *argc)
{
    printf("timer print\n");
    event_add(&ev,&tv);/*重新添加定时器*/
}

int main()
{
    struct event_base *base = event_base_new();//初始化libevent库
    
    tv.tv_sec=0; //间隔(s)
    tv.tv_usec=1000000;//us 微秒
    
    evtimer_set(&ev,time_cb, 0);//初始化关注的事件，并设置回调函数
    //等价于event_set(&ev, -1, 0, timer_cb, NULL);
    
    //base set一定要在evtimer_set之后
    event_base_set(base,&ev);
    event_add(&ev,&tv);//注册事件 相当于调用Reactor::register_handler()函数注册事件
    
    event_base_dispatch(base);//进入消息循环和消息分发
    
}