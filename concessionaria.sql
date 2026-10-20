create database concessionaria;
use concessionaria;

create table clientes(
idcliente int auto_increment primary key,
nome varchar(50) not null,
cpf char(11) not null,
email varchar(30) not null,
telefone varchar(12) not null,
cidade varchar (50) not null
);

select*from clientes;

create table carros(
idcarro int auto_increment primary key,
marca varchar(30) not null,
modelo varchar(30) not null,
anofabricacao year not null,
cor varchar(15) not null,
placa varchar(7) not null,
km int default 0,
preco decimal(12,2) not null,
situacao varchar(10) not null
);

select*from carros;

create table funcionarios(
idfuncionario int auto_increment primary key,
nome varchar(50) not null,
cpf char(11) not null,
email varchar(30) not null,
telefone varchar(12) not null,
cargo varchar (50) not null,
salario decimal(10,2) not null
);

select*from funcionarios;