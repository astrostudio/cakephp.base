
create table `user`(
    `id` bigint(20) not null,
    `username` varchar(255) not null,
    `password` varchar(100),
    `email` varchar(255) not null,
    `active` smallint(6) not null default 0,
    `token` varchar(100),
    `locale` varchar(6),
    `created` datetime,
    `modified` datetime,
    `removed` datetime,
    primary key (`id`),
    unique (`username`),
    unique (`email`)
);

create table `user_session`(
    `user_id` bigint (20) not null,
    `session` varchar(255) not null,
    `ip` varchar(255),
    `login` datetime,
    `logout` datetime,
    `access` datetime,
    `note` text,
    primary key (`user_id`,`session`),
    foreign key (`user_id`) references `user`(`id`) on delete cascade on update cascade
);

create table `user_setting`(
    `user_id` bigint(20) not null,
    `name` varchar(64) not null,
    `body` text,
    `created` datetime,
    `modified` datetime,
    primary key (`user_id`,`name`);
    foreign key (`user_id`) references `user`(`id`) on delete cascade on update cascade
);





