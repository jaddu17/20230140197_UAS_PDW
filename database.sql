
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100),
    email VARCHAR(100) UNIQUE,
    password VARCHAR(255),
    role ENUM('mahasiswa', 'asisten') NOT NULL
);

CREATE TABLE praktikum (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_praktikum VARCHAR(100),
    deskripsi TEXT
);

CREATE TABLE `modul` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `id_praktikum` INT(11) NOT NULL,
  `id_asisten` INT(11) DEFAULT NULL,
  `judul` VARCHAR(100) NOT NULL,
  `deskripsi` TEXT, 
  `file_materi` VARCHAR(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id_praktikum` (`id_praktikum`),
  KEY `id_asisten` (`id_asisten`)
);

CREATE TABLE pendaftaran (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_user INT,
    id_praktikum INT,
    tanggal_daftar DATE,
    FOREIGN KEY (id_user) REFERENCES users(id),
    FOREIGN KEY (id_praktikum) REFERENCES praktikum(id)
);

CREATE TABLE laporan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_user INT,
    id_modul INT,
    file_laporan VARCHAR(255),
    tanggal_kumpul DATETIME,
    nilai INT DEFAULT NULL,
    feedback TEXT DEFAULT NULL,
    FOREIGN KEY (id_user) REFERENCES users(id),
    FOREIGN KEY (id_modul) REFERENCES modul(id)
);
