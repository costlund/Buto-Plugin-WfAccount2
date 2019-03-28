select username from account;
select username from account where isnull(username);

# Create username where username is null.
update account set username=
concat(
	substring('abcdefghijklmnopqrstuvwxyz0123456789', rand()*36+1, 1),
	substring('abcdefghijklmnopqrstuvwxyz0123456789', rand()*36+1, 1),
	substring('abcdefghijklmnopqrstuvwxyz0123456789', rand()*36+1, 1),
	substring('abcdefghijklmnopqrstuvwxyz0123456789', rand()*36+1, 1),
	substring('abcdefghijklmnopqrstuvwxyz0123456789', rand()*36+1, 1),
	substring('abcdefghijklmnopqrstuvwxyz0123456789', rand()*36+1, 1),
	substring('abcdefghijklmnopqrstuvwxyz0123456789', rand()*36+1, 1),
	substring('abcdefghijklmnopqrstuvwxyz0123456789', rand()*36+1, 1)
)
where isnull(username);
