-- Create bm_ncc_files table if it doesn't exist
CREATE TABLE IF NOT EXISTS `bm_ncc_files` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `bmid` varchar(50) NOT NULL,
    `file_name` varchar(255) NOT NULL,
    `file_path` varchar(255) NOT NULL,
    `date_uploaded` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create bm_ac_files table if it doesn't exist
CREATE TABLE IF NOT EXISTS `bm_ac_files` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `bmid` varchar(50) NOT NULL,
    `file_name` varchar(255) NOT NULL,
    `file_path` varchar(255) NOT NULL,
    `date_uploaded` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create bm_nvc_files table if it doesn't exist
CREATE TABLE IF NOT EXISTS `bm_nvc_files` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `bmid` varchar(50) NOT NULL,
    `file_name` varchar(255) NOT NULL,
    `file_path` varchar(255) NOT NULL,
    `date_uploaded` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create bm_cs_files table if it doesn't exist
CREATE TABLE IF NOT EXISTS `bm_cs_files` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `bmid` varchar(50) NOT NULL,
    `file_name` varchar(255) NOT NULL,
    `file_path` varchar(255) NOT NULL,
    `date_uploaded` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create bm_wec_files table if it doesn't exist
CREATE TABLE IF NOT EXISTS `bm_wec_files` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `bmid` varchar(50) NOT NULL,
    `file_name` varchar(255) NOT NULL,
    `file_path` varchar(255) NOT NULL,
    `date_uploaded` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
