-- Crear base de datos
CREATE DATABASE IF NOT EXISTS finanzas_seguras;
USE finanzas_seguras;

-- Tabla de clientes
CREATE TABLE clientes (
id_cliente INT AUTO_INCREMENT PRIMARY KEY,
identificacion VARCHAR(20) NOT NULL UNIQUE,
apellidos VARCHAR(100) NOT NULL,
nombre VARCHAR(100) NOT NULL,
telefono_personal VARCHAR(20),
direccion_personal VARCHAR(255),
email VARCHAR(150) UNIQUE,
lugar_trabajo VARCHAR(150),
direccion_trabajo VARCHAR(255),
telefono_trabajo VARCHAR(20),
usuario VARCHAR(50) NOT NULL UNIQUE,
contrasena VARCHAR(255) NOT NULL
);

-- Tabla de prestamos
CREATE TABLE prestamos (
id_prestamo INT AUTO_INCREMENT PRIMARY KEY,
id_cliente INT NOT NULL,
monto_total DECIMAL(10,2) NOT NULL,
fecha_inicio DATE NOT NULL,
estado ENUM('Activo','Cancelado') DEFAULT 'Activo',
FOREIGN KEY (id_cliente) REFERENCES clientes(id_cliente) ON
DELETE CASCADE
);

-- Tabla de pagos
CREATE TABLE pagos (
id_pago INT AUTO_INCREMENT PRIMARY KEY,
id_prestamo INT NOT NULL,
monto_pagado DECIMAL(10,2) NOT NULL,
fecha_pago DATE NOT NULL,
numero_deposito VARCHAR(50) NOT NULL,
FOREIGN KEY (id_prestamo) REFERENCES prestamos(id_prestamo) ON
DELETE CASCADE
);

-- Insertar usuario de prueba
INSERT INTO clientes (
identificacion, apellidos, nombre, telefono_personal,
direccion_personal, email,
lugar_trabajo, direccion_trabajo, telefono_trabajo, usuario,
contrasena
) VALUES (
'12345678', 'Pérez', 'Juan', '8888-8888', 'San José, Costa
Rica', 'juan.perez@example.com',
'Banco Nacional', 'San José Centro', '2222-2222', 'juanp',
'password123'
);

SELECT * FROM clientes;

-- Insertar préstamo de prueba
INSERT INTO prestamos (id_cliente, monto_total, fecha_inicio,
estado)
VALUES (1, 5000.00, '2025-01-15', 'Activo');