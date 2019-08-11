-- phpMyAdmin SQL Dump
-- version 4.5.4.1deb2ubuntu2.1
-- http://www.phpmyadmin.net
--
-- Хост: localhost
-- Время создания: Авг 11 2019 г., 20:43
-- Версия сервера: 5.7.27-0ubuntu0.16.04.1
-- Версия PHP: 7.0.33-0ubuntu0.16.04.5

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- База данных: `u25499_pijama`
--

-- --------------------------------------------------------

--
-- Структура таблицы `sync.orders`
--

CREATE TABLE `sync.orders` (
  `id_shopkeeper` int(11) NOT NULL,
  `number` int(11) DEFAULT NULL,
  `last_response` text
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Таблица синхронизированных с 1С заказов';

-- --------------------------------------------------------

--
-- Структура таблицы `sync.sizes1C`
--

CREATE TABLE `sync.sizes1C` (
  `id` int(11) NOT NULL,
  `title` varchar(25) NOT NULL,
  `lineid` int(11) NOT NULL,
  `line` varchar(25) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Индексы сохранённых таблиц
--

--
-- Индексы таблицы `sync.orders`
--
ALTER TABLE `sync.orders`
  ADD UNIQUE KEY `sync.orders_id_shopkeeper_uindex` (`id_shopkeeper`);

--
-- Индексы таблицы `sync.sizes1C`
--
ALTER TABLE `sync.sizes1C`
  ADD UNIQUE KEY `sync.sizes1C_id_lineid_uindex` (`id`,`lineid`);

--
-- Ограничения внешнего ключа сохраненных таблиц
--

--
-- Ограничения внешнего ключа таблицы `sync.orders`
--
ALTER TABLE `sync.orders`
  ADD CONSTRAINT `sync.orders_modx_manager_shopkeeper_id_fk` FOREIGN KEY (`id_shopkeeper`) REFERENCES `modx_manager_shopkeeper` (`id`);

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
