select * from account;
select * from account_role;
set @id = 'wm_sdif38jsflj389sflsdf833jl23';
set @username = 'webmaster';
set @password = '123456';
insert into account (id, username, password, activated) values (@id, @username, @password, 1);
insert into account_role (account_id, role) value (@id, 'webmaster');
insert into account_role (account_id, role) value (@id, 'webadmin');
