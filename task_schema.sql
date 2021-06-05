create table task
(
    task_id bigint unsigned auto_increment
        primary key,
    task_type varchar(128) null comment '任务类型',
    task_key varchar(64) null comment '任务键值',
    task_from_system varchar(64) null comment '任务来源系统',
    task_request_data mediumtext null comment '任务数据，Json格式',
    task_response_data text null comment '任务执行完车后，返回结果数据，Json格式',
    task_memo text null comment '任务执行中出现异常时,纪录异常日志',
    task_is_synchronization tinyint(1) not null comment '任务同步执行还是异步执行,ture:同步执行，false 异步执行',
    task_status enum('open', 'running', 'error', 'terminated', 'close') default 'open' not null comment '任务状态，初始状态Open， 执行中为runing, 错误为error，重试5次后,还失败状态为terminated，执行完成为close',
    task_next_run_date datetime default '1990-01-01 00:00:00' not null comment '下次重试时间',
    task_retry_times int default 0 not null comment '重试次数',
    task_create_at datetime default '1990-01-01 00:00:00' not null,
    task_update_at timestamp default CURRENT_TIMESTAMP not null on update CURRENT_TIMESTAMP,
    task_suspend_times int default 0 not null comment '挂起次数',
    task_priority int default 3 not null
)
    comment '任务处理表';

create index idx_task_create_at
    on task (task_create_at);

create index idx_task_key
    on task (task_key);

create index idx_task_nextrun_status_priority
    on task (task_status, task_priority, task_next_run_date);

create index idx_task_updateat_status
    on task (task_status, task_update_at);

