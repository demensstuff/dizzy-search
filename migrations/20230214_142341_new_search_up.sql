CREATE TABLE `search_top_words` (
	`text` varchar(255) NOT NULL UNIQUE,
	`weight` int(11) NOT NULL
);

CREATE TABLE `search_settings` (
	`key` varchar(255) NOT NULL UNIQUE,
	`value` TEXT
);