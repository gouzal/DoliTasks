-- Copyright (C) 2019 Gouzal Larbi
--
-- This program is free software: you can redistribute it and/or modify
-- it under the terms of the GNU General Public License as published by
-- the Free Software Foundation, either version 3 of the License, or
-- (at your option) any later version.
--
-- This program is distributed in the hope that it will be useful,
-- but WITHOUT ANY WARRANTY; without even the implied warranty of
-- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
-- GNU General Public License for more details.
--
-- You should have received a copy of the GNU General Public License
-- along with this program.  If not, see http://www.gnu.org/licenses/.


CREATE TABLE `llx_dolitasks_my_tasks` (
  `rowid` INTEGER NOT NULL AUTO_INCREMENT,
  `title` varchar(254) COLLATE utf8_unicode_ci DEFAULT NULL,
  `priority` INTEGER DEFAULT NULL,
  `created_by_id` INTEGER DEFAULT NULL,
  `created_by_name` varchar(256) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created_at` date DEFAULT NULL,
  `end_at` date DEFAULT NULL,
  PRIMARY KEY (`rowid`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;